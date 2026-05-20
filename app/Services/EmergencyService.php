<?php

namespace App\Services;

use App\Enums\EmergencyArrivalMode;
use App\Enums\EmergencyCaseStatus;
use App\Enums\EmergencyDisposition;
use App\Enums\EmergencyTriageCategory;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\Patient;
use App\Models\Product;
use App\Models\ServiceCatalog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * EmergencyService — orchestrator for the Emergency Unit module.
 *
 * Non-negotiables enforced here:
 *  - All status changes route through VisitWorkflowService::transition()
 *  - All billing routes through BillingService::add*ToVisitInvoice()
 *  - Visit is created via VisitService::create() (single visit + single invoice)
 *  - Consumables go through ConsumableUsageService (Emergency stock location
 *    is resolved automatically via StockLocationResolver / DepartmentType::EMERGENCY).
 */
class EmergencyService
{
    public function __construct(
        protected VisitService $visitService,
        protected VisitWorkflowService $workflowService,
        protected BillingService $billingService,
        protected ConsumableUsageService $consumableService,
        protected AdmissionService $admissionService,
    ) {}

    /**
     * Register a new Emergency case. Creates a Visit (visit_type=EMERGENCY,
     * status=EMERGENCY) plus an EmergencyCase record linked to it.
     *
     * Expected $data keys:
     *  - patient_id (required)
     *  - arrival_mode (EmergencyArrivalMode|string, default WALK_IN)
     *  - arrival_time (default now())
     *  - brought_by, accompanied_by, referral_source, chief_complaint
     *  - department_id (Emergency dept; auto-resolved if omitted)
     *  - triage_category (optional initial triage)
     */
    public function registerCase(array $data): EmergencyCase
    {
        return DB::transaction(function () use ($data) {
            $patient = Patient::findOrFail($data['patient_id']);

            $department = $this->resolveEmergencyDepartment($data['department_id'] ?? null);

            // 1. Create the underlying Visit via VisitService
            $visit = $this->visitService->create([
                'patient_id'          => $patient->id,
                'department_id'       => $department?->id,
                'visit_type'          => VisitType::EMERGENCY->value,
                'visit_date'          => today(),
                'chief_complaint'     => $data['chief_complaint'] ?? null,
                'is_emergency'        => true,
            ]);

            // 2. Create the EmergencyCase record
            $case = EmergencyCase::create([
                'emergency_number'   => EmergencyCase::generateEmergencyNumber(),
                'visit_id'           => $visit->id,
                'patient_id'         => $patient->id,
                'registered_by'      => Auth::id(),
                'arrival_mode'       => $this->normaliseArrivalMode($data['arrival_mode'] ?? null),
                'arrival_time'       => $data['arrival_time'] ?? now(),
                'brought_by'         => $data['brought_by'] ?? null,
                'accompanied_by'     => $data['accompanied_by'] ?? null,
                'referral_source'    => $data['referral_source'] ?? null,
                'chief_complaint'    => $data['chief_complaint'] ?? null,
                'triage_category'    => $this->normaliseTriage($data['triage_category'] ?? null),
                'treatment_area_id'  => $department?->id,
                'status'             => EmergencyCaseStatus::REGISTERED->value,
            ]);

            return $case->load(['visit', 'patient', 'treatmentArea']);
        });
    }

    /**
     * Perform / update triage for an existing case. Updates the EmergencyCase
     * and advances the case status; the underlying visit stays in EMERGENCY.
     */
    public function triage(EmergencyCase $case, array $data): EmergencyCase
    {
        $category = $this->normaliseTriage($data['triage_category'] ?? null);
        if (! $category) {
            throw new \InvalidArgumentException('triage_category is required.');
        }

        $case->update([
            'triage_category' => $category,
            'triaged_at'      => now(),
            'triaged_by'      => Auth::id(),
            'status'          => EmergencyCaseStatus::TRIAGED->value,
        ]);

        return $case->fresh();
    }

    /**
     * Assign a clinician (doctor / nurse) and move case into IN_TREATMENT.
     */
    public function assign(EmergencyCase $case, array $data): EmergencyCase
    {
        $updates = array_filter([
            'assigned_doctor_id' => $data['assigned_doctor_id'] ?? null,
            'assigned_nurse_id'  => $data['assigned_nurse_id'] ?? null,
            'treatment_area_id'  => $data['treatment_area_id'] ?? null,
        ], fn ($v) => $v !== null);

        if (! empty($updates)) {
            $case->update($updates + ['status' => EmergencyCaseStatus::IN_TREATMENT->value]);
        }

        return $case->fresh();
    }

    /**
     * Bill an emergency procedure / service to the case visit invoice.
     *
     * @param string $sourceType  e.g. 'emergency_service' / 'emergency_observation'
     */
    public function billService(
        EmergencyCase $case,
        ServiceCatalog $service,
        string $sourceType = 'emergency_service',
        ?int $sourceId = null,
        int $quantity = 1,
        ?string $description = null,
    ): void {
        $this->billingService->addItemToVisitInvoice(
            visit:        $case->visit,
            service:      $service,
            sourceType:   $sourceType,
            sourceId:     $sourceId ?? $case->id,
            quantity:     $quantity,
            departmentId: $case->treatment_area_id,
            description:  $description,
        );
    }

    /**
     * Consume a product (drug / consumable) on the case. Records stock
     * movement via ConsumableUsageService (Emergency store, resolved via
     * StockLocationResolver) AND bills the product to the visit invoice.
     */
    public function consumeProduct(
        EmergencyCase $case,
        Product $product,
        float $quantity,
        ?string $notes = null,
    ): void {
        $sourceType = 'emergency_case';

        DB::transaction(function () use ($case, $product, $quantity, $notes, $sourceType) {
            $this->consumableService->recordUsageForSource(
                visit:      $case->visit,
                service:    null,
                sourceType: $sourceType,
                sourceId:   $case->id,
                items:      [['product_id' => $product->id, 'quantity' => $quantity, 'notes' => $notes]],
            );

            // Bill the consumable to the same visit invoice. Use a unique
            // (sourceType, sourceId) per consumption to bypass the duplicate
            // guard: emergency_consumable / case_id*1e6+product_id.
            try {
                $this->billingService->addProductToVisitInvoice(
                    visit:        $case->visit,
                    product:      $product,
                    sourceType:   'emergency_consumable',
                    sourceId:     ($case->id * 1_000_000) + $product->id,
                    quantity:     (int) ceil($quantity),
                    departmentId: $case->treatment_area_id,
                    description:  $notes,
                );
            } catch (\Throwable $e) {
                // Non-billable product → consumption still happens, billing skipped.
                if (! str_contains($e->getMessage(), 'not billable')
                    && ! str_contains($e->getMessage(), 'non-billable')) {
                    throw $e;
                }
            }
        });
    }

    /**
     * Set disposition for the case (discharge / admit / refer / death / DAMA / LAMA).
     *
     * @param array $data
     *   - disposition (EmergencyDisposition|string, required)
     *   - notes / discharge_summary / referral_facility / referral_reason
     *   - bed_id, admission_type, admission_fee_service_id, consumable_fee_service_id  (for admit)
     *   - death_time, death_cause                                                       (for death)
     */
    public function setDisposition(EmergencyCase $case, array $data): EmergencyCase
    {
        $dispEnum = $this->normaliseDisposition($data['disposition'] ?? null);
        if (! $dispEnum) {
            throw new \InvalidArgumentException('disposition is required.');
        }

        return DB::transaction(function () use ($case, $data, $dispEnum) {
            $case->update([
                'disposition'        => $dispEnum->value,
                'disposition_at'     => now(),
                'disposition_by'     => Auth::id(),
                'status'             => EmergencyCaseStatus::DISPOSED->value,
                'discharge_summary'  => $data['discharge_summary'] ?? $case->discharge_summary,
                'referral_facility'  => $data['referral_facility'] ?? $case->referral_facility,
                'referral_reason'    => $data['referral_reason']   ?? $case->referral_reason,
                'death_time'         => $data['death_time']        ?? $case->death_time,
                'death_cause'        => $data['death_cause']       ?? $case->death_cause,
                'certified_by'       => in_array($dispEnum, [EmergencyDisposition::DECEASED])
                                            ? Auth::id() : $case->certified_by,
            ]);

            // Drive the visit status via VisitWorkflowService
            $visit = $case->visit;
            $notes = $data['notes'] ?? ('Emergency disposition: ' . $dispEnum->label());

            match (true) {
                $dispEnum === EmergencyDisposition::ADMITTED_TO_WARD => $this->dispatchAdmission($case, $data),

                in_array($dispEnum, [
                    EmergencyDisposition::DISCHARGED_HOME,
                    EmergencyDisposition::LEFT_AGAINST_MEDICAL_ADVICE,
                    EmergencyDisposition::ABSCONDED,
                    EmergencyDisposition::DECEASED,
                ], true) => $this->workflowService->transition($visit, VisitStatus::DISCHARGED, $notes),

                $dispEnum === EmergencyDisposition::REFERRED_OUT
                    => $this->workflowService->transition($visit, VisitStatus::REFERRED_CONSULTATION, $notes),

                $dispEnum === EmergencyDisposition::TRANSFERRED_TO_OPD
                    => $this->workflowService->transition($visit, VisitStatus::CONSULTING, $notes),

                $dispEnum === EmergencyDisposition::TRANSFERRED_TO_THEATRE
                    => $this->workflowService->transition($visit, VisitStatus::CONSULTING, $notes),

                $dispEnum === EmergencyDisposition::CANCELLED
                    => $this->workflowService->transition($visit, VisitStatus::CANCELLED, $notes),

                default => null,
            };

            return $case->fresh(['visit', 'admission']);
        });
    }

    /**
     * Internal: route to AdmissionService when disposition = ADMITTED.
     */
    protected function dispatchAdmission(EmergencyCase $case, array $data): void
    {
        if (empty($data['bed_id'])) {
            throw new \InvalidArgumentException('bed_id is required to admit from Emergency.');
        }

        $admission = $this->admissionService->admit([
            'visit_id'                   => $case->visit_id,
            'patient_id'                 => $case->patient_id,
            'bed_id'                     => $data['bed_id'],
            'admitting_diagnosis'        => $data['admitting_diagnosis'] ?? $case->chief_complaint,
            'admission_date'             => now(),
            'admission_type'             => $data['admission_type'] ?? 'emergency_admission',
            'admission_fee_service_id'   => $data['admission_fee_service_id'] ?? null,
            'consumable_fee_service_id'  => $data['consumable_fee_service_id'] ?? null,
        ]);

        // Link the admission back to the emergency case.
        $admission->update(['emergency_case_id' => $case->id]);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    protected function resolveEmergencyDepartment(?int $departmentId): ?Department
    {
        if ($departmentId) {
            return Department::find($departmentId);
        }
        return Department::where('code', 'EMR')
            ->orWhere('type', \App\Enums\DepartmentType::EMERGENCY->value)
            ->first();
    }

    protected function normaliseArrivalMode(mixed $value): string
    {
        if ($value instanceof EmergencyArrivalMode) return $value->value;
        if (is_string($value) && EmergencyArrivalMode::tryFrom($value)) return $value;
        return EmergencyArrivalMode::WALK_IN->value;
    }

    protected function normaliseTriage(mixed $value): ?string
    {
        if ($value === null || $value === '') return null;
        if ($value instanceof EmergencyTriageCategory) return $value->value;
        if (is_string($value) && EmergencyTriageCategory::tryFrom($value)) return $value;
        return null;
    }

    protected function normaliseDisposition(mixed $value): ?EmergencyDisposition
    {
        if ($value === null || $value === '') return null;
        if ($value instanceof EmergencyDisposition) return $value;
        return EmergencyDisposition::tryFrom((string) $value);
    }
}
