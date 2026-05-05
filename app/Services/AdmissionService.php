<?php

namespace App\Services;

use App\Enums\AdmissionStatus;
use App\Enums\BillingType;
use App\Enums\InvoiceStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Events\PatientAdmitted;
use App\Events\PatientDischarged;
use App\Models\Admission;
use App\Models\Invoice;
use App\Models\WardRound;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class AdmissionService
{
    public function __construct(
        protected InsuranceService $insuranceService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Admission::with(['patient', 'bed.ward', 'admittedBy', 'visit']);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['ward_id'])) {
            $query->byWard($filters['ward_id']);
        }

        return $query->latest('admission_date')->paginate($filters['per_page'] ?? 15);
    }

    public function admit(array $data): Admission
    {
        $admission = DB::transaction(function () use ($data) {
            $admissionFields = array_intersect_key($data, array_flip([
                'admission_number', 'visit_id', 'patient_id', 'bed_id', 'admitted_by',
                'admitting_diagnosis', 'admission_date', 'expected_discharge_date',
                'admission_type', 'admission_fee_service_id', 'consumable_fee_service_id',
            ]));
            $admissionFields['admission_number'] = Admission::generateAdmissionNumber();
            $admissionFields['admitted_by'] = Auth::id();
            $admissionFields['admission_date'] = $data['admission_date'] ?? now();

            $admission = Admission::create($admissionFields);

            // Mark bed as occupied
            $bed = $admission->bed;
            $bed->markOccupied();

            // Transition visit status to ADMITTED and update type to INPATIENT
            $visit = $admission->visit;
            if ($visit->canTransitionTo(VisitStatus::ADMITTED)) {
                $visit->transitionTo(VisitStatus::ADMITTED,
                    'Patient admitted (' . ($data['admission_type'] ?? 'admission') . ') to '
                    . $bed->ward->name . ' — Bed ' . $bed->bed_number);
            }
            $visit->update(['visit_type' => VisitType::INPATIENT->value]);

            // Auto-create admission invoice with 3 lines
            $this->createAdmissionInvoice($admission, $bed, $data);

            return $admission->load(['patient', 'bed.ward', 'admittedBy']);
        });

        PatientAdmitted::dispatch($admission);

        return $admission;
    }

    /**
     * Create the admission invoice with 3 standard lines with insurance applied:
     *  1. Admission/Detention fee (one-time, mapped service or manual)
     *  2. Bed fee (per day × days stay)
     *  3. Consumable fee (per day × days stay)
     */
    protected function createAdmissionInvoice(Admission $admission, $bed, array $data): Invoice
    {
        $admissionType = $data['admission_type'] ?? 'admission';
        $admissionDate = \Carbon\Carbon::parse($data['admission_date'] ?? now());
        $expectedDischarge = isset($data['expected_discharge_date'])
            ? \Carbon\Carbon::parse($data['expected_discharge_date'])
            : null;
        $days = $expectedDischarge ? max(1, $admissionDate->diffInDays($expectedDischarge)) : 1;

        // Fee amounts from form (user-editable in summary) or defaults
        $admissionFeeAmount  = (float)($data['admission_fee_amount'] ?? 0);
        $bedFeePerDay        = (float)($data['bed_fee_amount'] !== null ? $data['bed_fee_amount'] / max(1, $days) : ($bed->daily_rate ?? 0));
        $consumableFeePerDay = (float)($data['consumable_fee_amount'] !== null ? $data['consumable_fee_amount'] / max(1, $days) : 0);

        // Fallback: get amounts from service catalog if zero
        if ($admissionFeeAmount == 0 && !empty($data['admission_fee_service_id'])) {
            $svc = \App\Models\ServiceCatalog::find($data['admission_fee_service_id']);
            if ($svc) $admissionFeeAmount = (float)$svc->price;
        }
        if ($consumableFeePerDay == 0 && !empty($data['consumable_fee_service_id'])) {
            $svc = \App\Models\ServiceCatalog::find($data['consumable_fee_service_id']);
            if ($svc) $consumableFeePerDay = (float)$svc->price;
        }

        // Resolve insurance for this visit
        $visit          = $admission->visit;
        $visitInsurance = $visit->visitInsurance;
        $hasInsurance   = $visitInsurance
            && $visitInsurance->is_active
            && ! $visitInsurance->is_expired
            && $visitInsurance->insuranceProvider
            && ! $visitInsurance->insuranceProvider->is_default;

        $label          = ucfirst($admissionType);
        $totalInsurance = 0;
        $sessionOffset = 0.0;

        $buildItem = function (
            ?int $serviceCatalogId, string $description, int $quantity, float $unitPrice
        ) use ($hasInsurance, $visitInsurance, $visit, &$sessionOffset, &$totalInsurance): array {
            $lineTotal        = round($unitPrice * $quantity, 2);
            $insuranceCovered = 0.0;
            $isInsured        = false;

            if ($hasInsurance && $lineTotal > 0) {
                $coverage = $this->insuranceService->evaluateCoverage(
                    $visitInsurance, $visit, $lineTotal, $sessionOffset
                );
                if ($coverage['can_use']) {
                    $insuranceCovered = $coverage['covered_amount'];
                    $isInsured        = $insuranceCovered > 0;
                    $sessionOffset    += $insuranceCovered;
                    $totalInsurance   += $insuranceCovered;
                }
            }

            return [
                'service_catalog_id'   => $serviceCatalogId,
                'description'          => $description,
                'quantity'             => $quantity,
                'unit_price'           => $unitPrice,
                'total_price'          => $lineTotal,
                'is_nhis_covered'      => $isInsured,
                'nhis_approved_amount' => $insuranceCovered,
            ];
        };

        $items = [
            $buildItem(
                $data['admission_fee_service_id'] ?? null,
                $label . ' Fee',
                1,
                $admissionFeeAmount
            ),
            $buildItem(
                null,
                'Bed Fee — ' . $bed->ward->name . ' / ' . $bed->bed_number . ' (' . $bed->bed_type->label() . ')',
                $days,
                $bedFeePerDay
            ),
            $buildItem(
                $data['consumable_fee_service_id'] ?? null,
                'Consumable Fee',
                $days,
                $consumableFeePerDay
            ),
        ];

        $subtotal    = array_sum(array_column($items, 'total_price'));
        $totalAmount = $subtotal;
        $balance     = max(0, $totalAmount - $totalInsurance);

        // Determine billing type
        $billingType = $hasInsurance ? BillingType::INSURANCE : BillingType::CASH;

        $invoice = Invoice::create([
            'invoice_number'  => Invoice::generateNumber('INV', 'invoices', 'invoice_number'),
            'visit_id'        => $admission->visit_id,
            'patient_id'      => $admission->patient_id,
            'billing_type'    => $billingType,
            'subtotal'        => $subtotal,
            'tax_amount'      => 0,
            'discount_amount' => 0,
            'nhis_amount'     => $totalInsurance,
            'total_amount'    => $totalAmount,
            'amount_paid'     => $totalInsurance,
            'balance'         => $balance,
            'status'          => $balance <= 0 ? InvoiceStatus::PAID : InvoiceStatus::PENDING,
            'created_by'      => Auth::id(),
            'notes'           => $label . ' invoice for ' . $admission->patient->full_name,
        ]);

        foreach ($items as $item) {
            $invoice->items()->create($item);
        }

        // Record insurance usage per covered line
        if ($hasInsurance) {
            foreach ($items as $item) {
                if ($item['nhis_approved_amount'] > 0) {
                    $this->insuranceService->recordUsage(
                        $visitInsurance,
                        $visit,
                        $item['nhis_approved_amount'],
                        $item['total_price'] - $item['nhis_approved_amount'],
                        'Admission billing: ' . $item['description'],
                        $invoice->id
                    );
                }
            }
        }

        return $invoice;
    }

    public function discharge(Admission $admission, array $data): Admission
    {
        return DB::transaction(function () use ($admission, $data) {
            $admission->update([
                'actual_discharge_date' => now(),
                'discharged_by' => Auth::id(),
                'discharge_summary' => $data['discharge_summary'] ?? null,
                'discharge_instructions' => $data['discharge_instructions'] ?? null,
                'status' => AdmissionStatus::DISCHARGED,
            ]);

            // Free up the bed
            $admission->bed->markAvailable();

            // Transition visit to discharging (pending billing)
            $visit = $admission->visit;
            if ($visit->canTransitionTo(VisitStatus::DISCHARGING)) {
                $visit->transitionTo(VisitStatus::DISCHARGING, 'Patient discharge initiated');
            }

            return $admission->fresh(['patient', 'bed.ward', 'dischargedBy']);
        });

        PatientDischarged::dispatch($admission);

        return $admission;
    }

    public function addWardRound(Admission $admission, array $data): WardRound
    {
        return $admission->wardRounds()->create([
            'recorded_by' => Auth::id(),
            'round_date' => $data['round_date'] ?? now(),
            'notes' => $data['notes'],
            'instructions' => $data['instructions'] ?? null,
        ]);
    }

    public function getCurrentInpatients(array $filters = []): LengthAwarePaginator
    {
        $query = Admission::with(['patient', 'bed.ward', 'admittedBy', 'visit'])
            ->where('status', AdmissionStatus::ADMITTED);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['ward_id'])) {
            $query->byWard($filters['ward_id']);
        }

        return $query->latest('admission_date')->paginate($filters['per_page'] ?? 15);
    }

    public function getStats(): array
    {
        return [
            'total_admitted' => Admission::where('status', AdmissionStatus::ADMITTED)->count(),
            'discharged_today' => Admission::where('status', AdmissionStatus::DISCHARGED)
                ->whereDate('actual_discharge_date', today())->count(),
            'admitted_today' => Admission::whereDate('admission_date', today())->count(),
        ];
    }
}
