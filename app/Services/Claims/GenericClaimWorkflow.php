<?php

namespace App\Services\Claims;

use App\Enums\ClaimItemStatus;
use App\Enums\ClaimStatus;
use App\Enums\ServiceType;
use App\Models\Claim;
use App\Models\ClaimItem;
use App\Models\InsuranceProvider;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\User;
use App\Models\Visit;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenericClaimWorkflow implements ClaimWorkflowInterface
{
    public function __construct(
        protected ClaimStatusService $statusService,
    ) {}

    public function prepareFromVisit(Visit $visit, User $user): Claim
    {
        $visit->loadMissing(['latestInvoice.items.serviceCatalog', 'latestInvoice.items.product', 'visitInsurance.insuranceProvider.insuranceType']);

        if (! $visit->latestInvoice) {
            throw new \InvalidArgumentException('This visit has no invoice to prepare a claim from.');
        }

        $provider = $visit->visitInsurance?->insuranceProvider;
        if (! $provider) {
            throw new \InvalidArgumentException('Select an insurance provider before creating this claim.');
        }

        return $this->prepareFromInvoice($visit->latestInvoice, $provider, $user);
    }

    public function prepareFromInvoice(Invoice $invoice, InsuranceProvider $provider, User $user, ?int $doctorId = null): Claim
    {
        return DB::transaction(function () use ($invoice, $provider, $user, $doctorId) {
            $invoice->loadMissing([
                'items.serviceCatalog.department',
                'items.product',
                'items.department',
                'visit.visitInsurance.insuranceProvider.insuranceType',
                'visit.insuranceVerification',
                'patient',
            ]);
            $provider->loadMissing('insuranceType');

            $claimableItems = $this->claimableInvoiceItems($invoice);
            if ($claimableItems->isEmpty()) {
                throw new \InvalidArgumentException('This invoice has no insurance-covered items to claim.');
            }

            $existingClaim = $this->existingActiveClaim($invoice, $provider, $claimableItems);
            if ($existingClaim) {
                return $existingClaim->fresh(['items', 'insuranceProvider.insuranceType', 'patient', 'invoice']);
            }

            $visitInsurance = $invoice->visit?->visitInsurance;
            $periodDate = $invoice->visit?->visit_date?->toDateString()
                ?? $invoice->created_at?->toDateString()
                ?? now()->toDateString();
            $verification = $invoice->visit?->insuranceVerification;

            $claim = Claim::create([
                'claim_number' => Claim::generateClaimNumber(),
                'claim_type_code' => $provider->claimTypeCode(),
                'claim_workflow_code' => $provider->claimWorkflowCode(),
                'insurance_type_id' => $provider->insurance_type_id,
                'insurance_provider_id' => $provider->id,
                'patient_id' => $invoice->patient_id,
                'visit_id' => $invoice->visit_id,
                'invoice_id' => $invoice->id,
                'patient_insurance_id' => $visitInsurance?->id,
                'insurance_verification_id' => $verification?->id,
                'verification_reference' => $verification?->reference_code,
                'membership_number' => $visitInsurance?->membership_number,
                'verification_code' => $visitInsurance?->ccc_code ?: $verification?->reference_code,
                'claim_date' => now()->toDateString(),
                'claim_period_start' => $periodDate,
                'claim_period_end' => $periodDate,
                'period_from' => $periodDate,
                'period_to' => $periodDate,
                'total_amount' => 0,
                'total_claim_amount' => 0,
                'approved_amount' => 0,
                'rejected_amount' => 0,
                'paid_amount' => 0,
                'status' => ClaimStatus::DRAFT,
                'assigned_doctor_id' => $doctorId,
                'prepared_by' => $user->id,
                'created_by' => $user->id,
            ]);

            foreach ($claimableItems as $item) {
                $this->createClaimItem($claim, $item);
            }

            $claim->recalculateTotal();

            $this->statusService->transition($claim->fresh(), ClaimStatus::DRAFT, $user, 'Claim prepared from invoice.');

            return $claim->fresh(['items.invoiceItem', 'insuranceProvider.insuranceType', 'insuranceType', 'patient', 'invoice']);
        });
    }

    public function validateClaim(Claim $claim): ClaimValidationResult
    {
        $claim->loadMissing(['items.invoiceItem', 'patient', 'visit', 'invoice', 'insuranceProvider.insuranceType']);

        $errors = [];
        if (! $claim->patient) {
            $errors[] = 'Patient is required.';
        }
        if (! $claim->visit) {
            $errors[] = 'Visit is required.';
        }
        if (! $claim->invoice) {
            $errors[] = 'Invoice is required.';
        }
        if (! $claim->insuranceProvider) {
            $errors[] = 'Insurance provider is required.';
        }
        if ($claim->items->isEmpty()) {
            $errors[] = 'Claim must have at least one item.';
        }
        foreach ($claim->items as $item) {
            if ((float) ($item->claim_amount ?: $item->total_price) <= 0) {
                $errors[] = "Claim item {$item->description} must have a positive claim amount.";
            }
        }

        return ClaimValidationResult::make($errors);
    }

    public function markReady(Claim $claim, User $user): Claim
    {
        $result = $this->validateClaim($claim);
        if (! $result->valid) {
            throw new \InvalidArgumentException(implode(' ', $result->errors));
        }

        return $this->statusService->transition($claim, ClaimStatus::READY, $user, 'Claim marked ready.');
    }

    public function submit(Claim $claim, User $user, array $data = []): Claim
    {
        $result = $this->validateClaim($claim);
        if (! $result->valid) {
            throw new \InvalidArgumentException(implode(' ', $result->errors));
        }

        return $this->statusService->transition($claim, ClaimStatus::SUBMITTED, $user, 'Claim submitted.', [
            'submitted_at' => now(),
            'submitted_by' => $user->id,
            'submission_mode' => $data['submission_mode'] ?? 'MANUAL',
            'submission_reference' => $data['submission_reference'] ?? null,
        ]);
    }

    public function export(Claim $claim, ?string $format = null): StreamedResponse
    {
        $claim->loadMissing([
            'items.department',
            'items.invoiceItem',
            'insuranceProvider',
            'patient',
            'visit',
            'invoice',
            'assignedDoctor',
        ]);

        $filename = strtolower($claim->claim_workflow_code ?: 'generic').'_claim_'.$claim->claim_number.'.csv';
        $callback = function () use ($claim) {
            $file = fopen('php://output', 'w');
            fputcsv($file, [
                'Claim Number',
                'Claim Type',
                'Provider',
                'Patient Name',
                'Patient Number',
                'Membership Number',
                'Verification Code',
                'Visit Date',
                'Invoice Number',
                'Diagnosis',
                'Doctor',
                'Department',
                'Item Description',
                'Quantity',
                'Claim Amount',
            ]);

            foreach ($claim->items as $item) {
                fputcsv($file, [
                    $claim->claim_number,
                    $claim->claim_type_code,
                    $claim->insuranceProvider?->name,
                    trim(($claim->patient?->first_name ?? '').' '.($claim->patient?->last_name ?? '')),
                    $claim->patient?->patient_number,
                    $claim->membership_number,
                    $claim->verification_code,
                    $claim->visit?->visit_date?->format('Y-m-d'),
                    $claim->invoice?->invoice_number,
                    $this->primaryDiagnosis($claim),
                    $claim->assignedDoctor?->full_name,
                    $item->department?->name,
                    $item->description ?: $item->service_name,
                    $item->quantity,
                    number_format((float) ($item->claim_amount ?: $item->total_price), 2, '.', ''),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    protected function createClaimItem(Claim $claim, InvoiceItem $item): ClaimItem
    {
        $quantity = max(1, (int) ($item->quantity ?? 1));
        $lineTotal = (float) ($item->total_price ?: ((float) $item->selected_price * $quantity));
        $claimAmount = min($this->insuranceCoveredAmount($item), $lineTotal);
        $unitPrice = round($claimAmount / $quantity, 2);

        return ClaimItem::create([
            'claim_id' => $claim->id,
            'invoice_item_id' => $item->id,
            'visit_id' => $item->visit_id ?: $claim->visit_id,
            'patient_id' => $item->patient_id ?: $claim->patient_id,
            'service_id' => $item->service_catalog_id,
            'product_id' => $item->product_id,
            'department_id' => $item->department_id ?: $item->serviceCatalog?->department_id,
            'service_name' => $item->description ?? $item->service_name ?? 'Service',
            'service_type' => $this->mapServiceType($item),
            'description' => $item->description ?? $item->service_name ?? 'Service',
            'item_type' => $this->mapServiceType($item),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'cash_price' => $item->cash_price,
            'insurance_price' => $item->insurance_price,
            'selected_price' => $item->selected_price ?: $item->unit_price,
            'claim_amount' => $claimAmount,
            'total_price' => $claimAmount,
            'status' => ClaimItemStatus::PENDING,
            'metadata' => [],
        ]);
    }

    protected function claimableInvoiceItems(Invoice $invoice): Collection
    {
        return $invoice->items
            ->filter(fn ($item) => $this->insuranceCoveredAmount($item) > 0)
            ->values();
    }

    protected function insuranceCoveredAmount($item): float
    {
        $legacyAmount = (float) ($item->nhis_approved_amount ?? 0);
        if ($legacyAmount > 0 && (bool) $item->is_nhis_covered) {
            return $legacyAmount;
        }

        return (float) ($item->insurance_covered ?? 0);
    }

    protected function mapServiceType($item): string
    {
        $serviceType = $item->service_type ?? null;
        if ($serviceType instanceof ServiceType) {
            return $serviceType->value;
        }
        if (is_string($serviceType) && $serviceType !== '') {
            return $serviceType;
        }

        $category = strtolower((string) ($item->serviceCatalog?->category ?? ''));

        return match ($category) {
            'consultation' => ServiceType::CONSULTATION->value,
            'investigation', 'lab', 'laboratory', 'radiology', 'imaging' => ServiceType::INVESTIGATION->value,
            'procedure', 'procedures' => ServiceType::PROCEDURE->value,
            'pharmacy', 'drug', 'drugs', 'medication', 'medications' => ServiceType::MEDICATION->value,
            'bed', 'bed_charge', 'ward', 'admission' => ServiceType::BED_CHARGE->value,
            default => ServiceType::OTHER->value,
        };
    }

    protected function existingActiveClaim(Invoice $invoice, InsuranceProvider $provider, Collection $claimableItems): ?Claim
    {
        $invoiceItemIds = $claimableItems->pluck('id')->all();

        return Claim::query()
            ->where('invoice_id', $invoice->id)
            ->where('insurance_provider_id', $provider->id)
            ->whereNotIn('status', [ClaimStatus::CANCELLED->value, ClaimStatus::REJECTED->value])
            ->whereHas('items', fn ($query) => $query->whereIn('invoice_item_id', $invoiceItemIds))
            ->first()
            ?: Claim::where('invoice_id', $invoice->id)->first();
    }

    protected function primaryDiagnosis(Claim $claim): ?string
    {
        $claim->loadMissing('visit.medicalRecords.diagnoses');
        $diagnoses = $claim->visit?->medicalRecords?->flatMap(fn ($record) => $record->diagnoses ?? collect()) ?? collect();

        return $diagnoses->firstWhere('is_primary', true)?->description
            ?: $diagnoses->first()?->description;
    }
}
