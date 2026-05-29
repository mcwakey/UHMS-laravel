<?php

namespace App\Services;

use App\Enums\ServiceType;
use App\Models\Admission;
use App\Models\AdmissionBedCharge;
use App\Models\AdmissionDailyConsumableCharge;
use App\Models\ServiceCatalog;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class AdmissionBedBillingService
{
    public function __construct(
        private BillingService $billing,
        private VisitPathwayService $pathway,
    ) {}

    public function createInitialCharges(Admission $admission, array $data): void
    {
        $admission->loadMissing(['visit', 'bed.ward']);
        $visit = $admission->visit;
        $bed = $admission->bed;
        $days = $this->expectedDays($admission);

        if (! $visit || ! $bed) {
            return;
        }

        if ($admissionService = $this->resolveAdmissionFeeService($data)) {
            $item = $this->billing->addItemToVisitInvoice(
                $visit,
                $admissionService,
                'admission_fee',
                $admission->id,
                1,
                null,
                ucfirst((string) ($data['admission_type'] ?? 'admission')) . ' fee',
                isset($data['admission_fee_amount']) ? (float) $data['admission_fee_amount'] : null,
            );

            $this->pathway->record($visit, 'ADMISSION_FEE_BILLED', [
                'source' => $item,
                'title' => 'Admission fee billed',
                'description' => $admissionService->name,
            ]);
        }

        $bedService = $this->resolveBedService($bed, $data);
        $bedCharge = AdmissionBedCharge::firstOrCreate(
            ['admission_id' => $admission->id],
            [
                'visit_id' => $admission->visit_id,
                'patient_id' => $admission->patient_id,
                'bed_id' => $admission->bed_id,
                'ward_id' => $bed->ward_id,
                'started_at' => $admission->admission_date ?? now(),
                'billing_unit' => 'PER_DAY',
                'quantity' => $days,
                'service_id' => $bedService?->id,
                'status' => AdmissionBedCharge::STATUS_ACTIVE,
                'created_by' => Auth::id(),
            ]
        );

        if ($bedService && ! $bedCharge->invoice_item_id) {
            $unitOverride = isset($data['bed_fee_amount'])
                ? (float) $data['bed_fee_amount'] / max(1, $days)
                : null;
            $item = $this->billing->addItemToVisitInvoice(
                $visit,
                $bedService,
                'admission_bed_charge',
                $bedCharge->id,
                $days,
                null,
                'Admission bed charge - ' . $bed->ward?->name . ' / ' . $bed->bed_number,
                $unitOverride,
            );
            $bedCharge->update(['invoice_item_id' => $item->id, 'status' => AdmissionBedCharge::STATUS_BILLED]);
        }

        if ($consumableService = $this->resolveConsumableService($data)) {
            $charge = AdmissionDailyConsumableCharge::firstOrCreate(
                ['admission_id' => $admission->id],
                [
                    'visit_id' => $admission->visit_id,
                    'patient_id' => $admission->patient_id,
                    'bed_id' => $admission->bed_id,
                    'ward_id' => $bed->ward_id,
                    'started_at' => $admission->admission_date ?? now(),
                    'billing_unit' => 'PER_DAY',
                    'quantity' => $days,
                    'service_id' => $consumableService->id,
                    'status' => AdmissionDailyConsumableCharge::STATUS_ACTIVE,
                    'created_by' => Auth::id(),
                ]
            );

            if (! $charge->invoice_item_id) {
                $unitOverride = isset($data['consumable_fee_amount'])
                    ? (float) $data['consumable_fee_amount'] / max(1, $days)
                    : null;
                $item = $this->billing->addItemToVisitInvoice(
                    $visit,
                    $consumableService,
                    'admission_daily_consumable_charge',
                    $charge->id,
                    $days,
                    null,
                    'Admission daily consumables',
                    $unitOverride,
                );
                $charge->update(['invoice_item_id' => $item->id, 'status' => AdmissionDailyConsumableCharge::STATUS_BILLED]);
            }
        }

        $this->pathway->record($visit, 'ADMISSION_BED_STARTED', [
            'source' => $admission,
            'title' => 'Admission bed count started',
            'description' => $bed->ward?->name . ' / Bed ' . $bed->bed_number,
        ]);
    }

    private function resolveBedService($bed, array $data): ?ServiceCatalog
    {
        $service = ServiceCatalog::query()
            ->where('is_active', true)
            ->whereIn('code', ['ADM-BED-DAY', 'WARD-BED-DAY'])
            ->first();

        if ($service) {
            return $service;
        }

        $service = ServiceCatalog::query()
            ->where('is_active', true)
            ->where('category', ServiceType::BED_CHARGE->value)
            ->where(function ($query) {
                $query->where('code', 'like', 'ADM%')
                    ->orWhere('code', 'like', 'WARD%')
                    ->orWhere('code', 'like', 'INPATIENT%')
                    ->orWhere('name', 'like', '%Admission%')
                    ->orWhere('name', 'like', '%Ward%')
                    ->orWhere('name', 'like', '%Inpatient%');
            })
            ->first();

        if ($service) {
            return $service;
        }

        $price = isset($data['bed_fee_amount'])
            ? (float) $data['bed_fee_amount'] / max(1, $this->expectedDaysFromData($data))
            : (float) ($bed->daily_rate ?? 0);

        if ($price <= 0) {
            return null;
        }

        return ServiceCatalog::firstOrCreate(
            ['code' => 'ADM-BED-DAY'],
            [
                'name' => 'Admission Bed Daily Charge',
                'category' => ServiceType::BED_CHARGE->value,
                'price' => $price,
                'is_active' => true,
                'is_billable' => true,
            ]
        );
    }

    private function service(?int $id): ?ServiceCatalog
    {
        return $id ? ServiceCatalog::find($id) : null;
    }

    private function resolveAdmissionFeeService(array $data): ?ServiceCatalog
    {
        if ($service = $this->service($data['admission_fee_service_id'] ?? null)) {
            return $service;
        }

        $price = (float) ($data['admission_fee_amount'] ?? 0);
        if ($price <= 0) {
            return null;
        }

        return ServiceCatalog::firstOrCreate(
            ['code' => 'ADM-FEE'],
            [
                'name' => 'Admission Fee',
                'category' => ServiceType::OTHER->value,
                'price' => $price,
                'is_active' => true,
                'is_billable' => true,
            ]
        );
    }

    private function resolveConsumableService(array $data): ?ServiceCatalog
    {
        if ($service = $this->service($data['consumable_fee_service_id'] ?? null)) {
            return $service;
        }

        $price = (float) ($data['consumable_fee_amount'] ?? 0);
        if ($price <= 0) {
            return null;
        }

        return ServiceCatalog::firstOrCreate(
            ['code' => 'ADM-CONSUMABLE-DAY'],
            [
                'name' => 'Admission Daily Consumables',
                'category' => ServiceType::OTHER->value,
                'price' => $price / max(1, $this->expectedDaysFromData($data)),
                'is_active' => true,
                'is_billable' => true,
            ]
        );
    }

    private function expectedDays(Admission $admission): int
    {
        if (! $admission->expected_discharge_date) {
            return 1;
        }

        return max(1, $admission->admission_date->diffInDays($admission->expected_discharge_date));
    }

    private function expectedDaysFromData(array $data): int
    {
        if (empty($data['expected_discharge_date'])) {
            return 1;
        }

        return max(1, Carbon::parse($data['admission_date'] ?? now())->diffInDays($data['expected_discharge_date']));
    }
}
