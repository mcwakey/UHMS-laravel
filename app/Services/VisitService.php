<?php

namespace App\Services;

use App\Enums\TriageScore;
use App\Enums\VisitStatus;
use App\Models\Patient;
use App\Models\Triage;
use App\Models\Vital;
use App\Models\VisitDepartmentHistory;
use App\Models\Visit;
use App\Models\VisitServiceItem;
use App\Models\ServiceCatalog;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Services\VisitWorkflowService;
use Illuminate\Pagination\LengthAwarePaginator;

class VisitService
{
    public function __construct(
        protected QueueService $queueService,
        protected InsuranceService $insuranceService,
        protected VisitWorkflowService $workflowService,
        protected ServicePricingService $pricingService,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Visit::with(['patient', 'assignedDoctor', 'createdBy']);

        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['visit_type'])) {
            $query->where('visit_type', $filters['visit_type']);
        }

        if (!empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (!empty($filters['assigned_doctor_id'])) {
            $query->where('assigned_doctor_id', $filters['assigned_doctor_id']);
        }

        if (!empty($filters['date_from'])) {
            $query->whereDate('visit_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('visit_date', '<=', $filters['date_to']);
        }

        if (isset($filters['today']) && $filters['today']) {
            $query->today();
        }

        // Include scheduled visits in listing
        if (!empty($filters['include_scheduled'])) {
            $query->orWhere(function ($q) {
                $q->whereIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED]);
            });
        }

        return $query->latest()->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Visit
    {
        $data['visit_number'] = Visit::generateVisitNumber();
        $data['visit_date'] = $data['visit_date'] ?? today();
        $data['created_by'] = Auth::id();

        // Calculate and store patient age at time of visit
        $patient = Patient::find($data['patient_id']);
        if ($patient && $patient->date_of_birth) {
            $data['patient_age'] = $patient->date_of_birth->age;
        }

        // Determine if this is a scheduled visit (future date) or walk-in
        $visitDate = Carbon::parse($data['visit_date']);
        $isScheduled = $visitDate->isAfter(today());

        if ($isScheduled) {
            $data['status'] = VisitStatus::SCHEDULED->value;
        } else {
            $data['status'] = VisitStatus::TRIAGE->value;
        }

        // One visit per day per patient check
        if (Visit::patientHasVisitOnDate($data['patient_id'], $data['visit_date'])) {
            throw new \InvalidArgumentException('Patient already has a visit on this date.');
        }

        $visit = Visit::create($data);

        return $this->workflowService
            ->initialize($visit, $isScheduled)
            ->load(['patient', 'assignedDoctor']);
    }

    /**
     * Attach services to a visit and create billing line items.
     * Each service is evaluated against all four insurance constraints in real
     * time; if a limit is reached the remainder is recorded as cash & carry.
     *
     * @param array $services Array of ['service_catalog_id' => int, 'quantity' => int, 'notes' => ?string]
     */
    public function attachServices(Visit $visit, array $services): Visit
    {
        $patient         = $visit->patient;
        $insuranceResult = $this->insuranceService->resolveForVisit($patient, $visit->visit_insurance_id);
        $insurance       = $insuranceResult['insurance'];
        $isFallback      = $insuranceResult['is_fallback'];

        // The insurance used for pricing; ignore the synthetic cash & carry record.
        $pricingInsurance = ($insurance && ! $isFallback) ? $insurance : null;

        foreach ($services as $serviceData) {
            $catalog  = ServiceCatalog::with('prices')->findOrFail($serviceData['service_catalog_id']);
            $quantity = max(1, (int) ($serviceData['quantity'] ?? 1));

            // Resolve full pricing snapshot from the single source of truth.
            $snapshot = $this->pricingService->resolvePriceForVisitService(
                $catalog,
                $pricingInsurance,
                $quantity
            );

            $unitPrice        = $snapshot['unit_price'];
            $insurancePrice   = $snapshot['insurance_price'];
            $totalPrice       = $snapshot['total_price'];
            $insuranceCovered = $snapshot['insurance_covered'];
            $patientPayable   = $snapshot['patient_payable'];

            // ── Real-time constraint evaluation (only for true insurance) ────
            // The pricing snapshot already accounts for the negotiated rate;
            // the constraint engine may further reduce coverage if a hard
            // limit (per-visit cap, monthly cap, annual cap) is hit.
            if ($pricingInsurance) {
                $evaluation = $this->insuranceService->evaluateCoverage($pricingInsurance, $visit, $totalPrice);

                $insuranceCovered = (float) $evaluation['covered_amount'];
                $patientPayable   = (float) $evaluation['patient_amount'];

                if ($evaluation['covered_amount'] > 0) {
                    $this->insuranceService->recordUsage(
                        $pricingInsurance,
                        $visit,
                        $evaluation['covered_amount'],
                        $evaluation['patient_amount'],
                        $evaluation['reason'],
                    );
                }
            }

            VisitServiceItem::create([
                'visit_id'             => $visit->id,
                'service_catalog_id'   => $catalog->id,
                'department_id'        => $catalog->department_id,
                'patient_insurance_id' => $pricingInsurance?->id,
                'payment_type'         => $snapshot['payment_type'],
                'insurance_type'       => $snapshot['insurance_type'],
                'pricing_source'       => $snapshot['pricing_source'],
                'quantity'             => $quantity,
                'unit_price'           => $unitPrice,
                'insurance_price'      => $insurancePrice,
                'insurance_covered'    => $insuranceCovered,
                'patient_payable'      => $patientPayable,
                'total_price'          => $totalPrice,
                'notes'                => $serviceData['notes'] ?? null,
            ]);
        }

        return $visit->fresh(['visitServices.serviceCatalog', 'visitServices.department']);
    }

    /**
     * Get services available for a department.
     * Includes services whose primary department_id matches, OR whose
     * specialties are linked to the department.
     */
    public function getServicesForDepartment(int $departmentId): \Illuminate\Database\Eloquent\Collection
    {
        return ServiceCatalog::where('is_active', true)
            ->where(function ($q) use ($departmentId) {
                $q->where('department_id', $departmentId)
                  ->orWhereHas('specialties', fn ($sq) => $sq->where('department_id', $departmentId));
            })
            ->with('prices.insuranceProvider')
            ->orderBy('name')
            ->get();
    }

    /**
     * Get doctors available for specific services (via specialties).
     */
    public function getDoctorsForServices(array $serviceIds): \Illuminate\Database\Eloquent\Collection
    {
        $specialtyIds = DB::table('service_specialty')
            ->whereIn('service_catalog_id', $serviceIds)
            ->pluck('specialty_id')
            ->unique();

        if ($specialtyIds->isEmpty()) {
            // Fallback: return all active doctors
            return \App\Models\User::role('Doctor')
                ->where('status', \App\Enums\UserStatus::ACTIVE)
                ->orderBy('first_name')
                ->get();
        }

        return \App\Models\User::role('Doctor')
            ->where('status', \App\Enums\UserStatus::ACTIVE)
            ->whereHas('specialties', fn ($q) => $q->whereIn('specialties.id', $specialtyIds))
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Get services available for a doctor (via specialties).
     */
    public function getServicesForDoctor(int $doctorId): \Illuminate\Database\Eloquent\Collection
    {
        $specialtyIds = DB::table('doctor_specialty')
            ->where('user_id', $doctorId)
            ->pluck('specialty_id');

        if ($specialtyIds->isEmpty()) {
            return ServiceCatalog::where('is_active', true)->orderBy('name')->get();
        }

        return ServiceCatalog::where('is_active', true)
            ->whereHas('specialties', fn ($q) => $q->whereIn('specialties.id', $specialtyIds))
            ->with('prices.insuranceProvider')
            ->orderBy('name')
            ->get();
    }

    /**
     * Schedule a visit for a future date (replaces appointment creation).
     */
    public function schedule(array $data): Visit
    {
        $data['visit_date'] = $data['visit_date'] ?? $data['appointment_date'] ?? null;

        if (!$data['visit_date'] || !Carbon::parse($data['visit_date'])->isAfter(today())) {
            throw new \InvalidArgumentException('Scheduled visits must be for a future date.');
        }

        // Check for time slot conflicts if times provided
        if (!empty($data['start_time']) && !empty($data['end_time']) && !empty($data['assigned_doctor_id'])) {
            $conflict = Visit::where('assigned_doctor_id', $data['assigned_doctor_id'])
                ->whereDate('visit_date', $data['visit_date'])
                ->whereIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])
                ->where(function ($q) use ($data) {
                    $q->where(function ($q2) use ($data) {
                        $q2->where('start_time', '<', $data['end_time'])
                           ->where('end_time', '>', $data['start_time']);
                    });
                })->exists();

            if ($conflict) {
                throw new \InvalidArgumentException('This time slot conflicts with an existing scheduled visit.');
            }
        }

        return $this->create($data);
    }

    /**
     * Confirm a scheduled visit.
     */
    public function confirm(Visit $visit, ?string $notes = null): Visit
    {
        return $this->workflowService->confirm($visit, $notes);
    }

    /**
     * Check in a scheduled/confirmed visit (patient arrives).
     */
    public function checkIn(Visit $visit): Visit
    {
        if (!in_array($visit->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])) {
            throw new \InvalidArgumentException('Only scheduled or confirmed visits can be checked in.');
        }

        // One visit per day check
        if (Visit::where('patient_id', $visit->patient_id)
            ->whereDate('visit_date', today())
            ->whereNotIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED, VisitStatus::CANCELLED, VisitStatus::RESCHEDULED, VisitStatus::NO_SHOW])
            ->where('id', '!=', $visit->id)
            ->exists()) {
            throw new \InvalidArgumentException('Patient already has an active visit today.');
        }

        return $this->workflowService->checkIn($visit);
    }

    /**
     * Reschedule a visit to a new date/time.
     */
    public function reschedule(Visit $visit, array $data): Visit
    {
        if (!in_array($visit->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])) {
            throw new \InvalidArgumentException('Only scheduled or confirmed visits can be rescheduled.');
        }

        // Mark old visit as rescheduled
        $this->workflowService->markRescheduled($visit, $data['reason'] ?? 'Rescheduled');

        // Create new visit with rescheduled_from reference
        $newData = $visit->only(['patient_id', 'visit_type', 'priority', 'assigned_doctor_id', 'chief_complaint', 'notes', 'visit_insurance_id', 'consultation_mode', 'meeting_link']);
        $newData['visit_date'] = $data['visit_date'];
        $newData['start_time'] = $data['start_time'] ?? $visit->start_time;
        $newData['end_time'] = $data['end_time'] ?? $visit->end_time;
        $newData['rescheduled_from_id'] = $visit->id;
        $newData['rescheduled_at'] = now();
        $newData['rescheduled_reason'] = $data['reason'] ?? null;

        return $this->schedule($newData);
    }

    /**
     * Mark a scheduled visit as no-show.
     */
    public function markNoShow(Visit $visit, ?string $notes = null): Visit
    {
        return $this->workflowService->markNoShow($visit, $notes);
    }

    /**
     * Cancel a visit with reason.
     */
    public function cancel(Visit $visit, ?string $reason = null): Visit
    {
        return $this->workflowService->cancel($visit, $reason);
    }

    public function transition(Visit $visit, VisitStatus $newStatus, ?string $notes = null): Visit
    {
        return $this->workflowService->transition($visit, $newStatus, $notes);
    }

    /**
     * Send patient from triage (or current service) to a specific department.
     * Completes the current active queue entry and creates a new one for the target dept.
     */
    public function sendToDepartment(Visit $visit, int $departmentId, ?string $notes = null): Visit
    {
        $serviceStatuses = [
            VisitStatus::TRIAGE,
            VisitStatus::CONSULTING,
            VisitStatus::LAB,
            VisitStatus::PHARMACY,
            VisitStatus::BILLING,
        ];

        if (!in_array($visit->status, $serviceStatuses)) {
            throw new \InvalidArgumentException(
                "Cannot send to department from status: {$visit->status->label()}"
            );
        }

        $department = \App\Models\Department::findOrFail($departmentId);

        // Map department type to visit status
        $newStatus = $department->type
            ? $department->type->toVisitStatus()
            : VisitStatus::CONSULTING;

        // Complete the current active queue entry
        $this->queueService->completeCurrentEntry($visit);

        // Transition visit status
        $this->workflowService->transition($visit, $newStatus, $notes ?? "Sent to {$department->name}");

        // Create new queue entry for the target department
        $this->queueService->addForDepartment($visit->fresh(), $departmentId);

        return $visit->fresh();
    }

    public function update(Visit $visit, array $data): Visit
    {
        $visit->update($data);
        return $visit->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Triage Workflow Methods
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Process triage: record vitals, compute score, select dept, transition visit.
     *
     * @param array $data {
     *   blood_pressure_systolic, blood_pressure_diastolic, heart_rate,
     *   temperature, respiratory_rate, spo2, weight?, height?,
     *   department_id (consultation dept), notes?
     * }
     */
    public function processTriage(Visit $visit, array $data): Visit
    {
        if ($visit->status !== VisitStatus::TRIAGE) {
            throw new \InvalidArgumentException('Visit must be in TRIAGE status to process triage.');
        }

        // Compute triage score
        $score = TriageScore::compute($data);

        // Store (or update) triage record
        $triage = Triage::updateOrCreate(
            ['visit_id' => $visit->id],
            array_merge($data, [
                'patient_id' => $visit->patient_id,
                'triage_score' => $score->value,
                'triaged_by' => Auth::id(),
                'triaged_at' => now(),
            ])
        );

        $this->syncTriageVitalRecord($triage, $data);

        // Save score on the visit itself
        $visit->update(['triage_score' => $score->value]);

        // Determine next visit status based on triage score
        $nextStatus = match ($score) {
            TriageScore::EMERGENCY => VisitStatus::EMERGENCY,
            default                => VisitStatus::WAITING_CONSULTATION,
        };

        // Assign consultation department if provided
        if (!empty($data['department_id'])) {
            $this->assignDepartment($visit, (int) $data['department_id'], $nextStatus);
        } else {
            $this->workflowService->transition($visit, $nextStatus, "Triage complete — score: {$score->label()}");
        }

        return $visit->fresh(['triage', 'currentDepartment']);
    }

    /**
     * Assign a department to a visit and create a billing line + queue entry.
     */
    public function assignDepartment(Visit $visit, int $departmentId, VisitStatus $newStatus): Visit
    {
        $department = \App\Models\Department::findOrFail($departmentId);

        // Update current department on visit
        $visit->update(['current_department_id' => $departmentId]);

        // Record dept history
        VisitDepartmentHistory::create([
            'visit_id'    => $visit->id,
            'department_id' => $departmentId,
            'type'        => VisitDepartmentHistory::TYPE_CONSULTATION,
            'status'      => VisitDepartmentHistory::STATUS_WAITING,
            'assigned_by' => Auth::id(),
        ]);

        // Create a billing line for consultation if a consultation service exists for the dept
        $this->createConsultationBilling($visit, $departmentId);

        // Complete old queue entry, create new one for the dept
        $this->queueService->completeCurrentEntry($visit);

        // Transition status
        $this->workflowService->transition($visit, $newStatus, "Assigned to {$department->name}");

        // Create queue entry for the new department
        $this->queueService->addForDepartment($visit->fresh(), $departmentId);

        return $visit->fresh();
    }

    /**
     * Refer a visit to another consultation department.
     *
     * @throws \InvalidArgumentException if referral to same department or invalid status.
     */
    public function referPatient(Visit $visit, int $departmentId, ?string $notes = null): Visit
    {
        if ($visit->status !== VisitStatus::CONSULTING) {
            throw new \InvalidArgumentException('Can only refer from CONSULTING status.');
        }

        if ($visit->current_department_id === $departmentId) {
            throw new \InvalidArgumentException('Cannot refer to the same department.');
        }

        // Check if previously referred to this department (prevent loops — optional strictness)
        $alreadyVisited = $visit->departmentHistory()
            ->where('department_id', $departmentId)
            ->whereIn('type', [VisitDepartmentHistory::TYPE_CONSULTATION, VisitDepartmentHistory::TYPE_REFERRAL])
            ->exists();

        if ($alreadyVisited) {
            throw new \InvalidArgumentException('Patient was already referred to this department in this visit.');
        }

        $department = \App\Models\Department::findOrFail($departmentId);

        // Mark current dept history entry as completed
        $this->completeDepartmentHistory($visit);

        // Update current department
        $visit->update(['current_department_id' => $departmentId]);

        // Record referral history
        VisitDepartmentHistory::create([
            'visit_id'     => $visit->id,
            'department_id' => $departmentId,
            'type'         => VisitDepartmentHistory::TYPE_REFERRAL,
            'status'       => VisitDepartmentHistory::STATUS_WAITING,
            'assigned_by'  => Auth::id(),
            'notes'        => $notes,
        ]);

        // Billing line for the referral consultation
        $this->createConsultationBilling($visit, $departmentId);

        // Queue transition
        $this->queueService->completeCurrentEntry($visit);
        $this->workflowService->transition($visit, VisitStatus::REFERRED_CONSULTATION, $notes ?? "Referred to {$department->name}");
        $this->queueService->addForDepartment($visit->fresh(), $departmentId);

        return $visit->fresh();
    }

    /**
     * Send patient to investigation (lab / scan / x-ray etc.).
     */
    public function sendToInvestigation(Visit $visit, int $departmentId, ?string $notes = null): Visit
    {
        if ($visit->status !== VisitStatus::CONSULTING) {
            throw new \InvalidArgumentException('Can only send to investigation from CONSULTING status.');
        }

        $department = \App\Models\Department::findOrFail($departmentId);

        // Mark current dept history entry as in-progress (consultation still ongoing)
        // Investigation is additional, not replacing the consultation dept

        // Record investigation history
        VisitDepartmentHistory::create([
            'visit_id'     => $visit->id,
            'department_id' => $departmentId,
            'type'         => VisitDepartmentHistory::TYPE_INVESTIGATION,
            'status'       => VisitDepartmentHistory::STATUS_WAITING,
            'assigned_by'  => Auth::id(),
            'notes'        => $notes,
        ]);

        // Billing line for investigation
        $this->createInvestigationBilling($visit, $departmentId);

        // Queue transition
        $this->queueService->completeCurrentEntry($visit);
        $this->workflowService->transition($visit, VisitStatus::WAITING_INVESTIGATION, $notes ?? "Sent to {$department->name} for investigation");
        $this->queueService->addForDepartment($visit->fresh(), $departmentId);

        return $visit->fresh();
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Private helpers
    // ─────────────────────────────────────────────────────────────────────────

    private function completeDepartmentHistory(Visit $visit): void
    {
        $active = $visit->departmentHistory()
            ->where('department_id', $visit->current_department_id)
            ->whereIn('status', [VisitDepartmentHistory::STATUS_WAITING, VisitDepartmentHistory::STATUS_IN_PROGRESS])
            ->latest()
            ->first();

        if ($active) {
            $active->update([
                'status'       => VisitDepartmentHistory::STATUS_COMPLETED,
                'completed_at' => now(),
            ]);
        }
    }

    private function syncTriageVitalRecord(Triage $triage, array $data): void
    {
        $vitalFields = [
            'blood_pressure_systolic',
            'blood_pressure_diastolic',
            'heart_rate',
            'temperature',
            'respiratory_rate',
            'spo2',
            'weight',
            'height',
            'bmi',
            'notes',
        ];

        $vitals = array_intersect_key($data, array_flip($vitalFields));

        Vital::updateOrCreate(
            ['triage_id' => $triage->id],
            array_merge($vitals, [
                'visit_id' => $triage->visit_id,
                'patient_id' => $triage->patient_id,
                'recorded_by' => $triage->triaged_by,
                'recorded_at' => $triage->triaged_at ?? now(),
            ])
        );
    }

    /**
     * Create a consultation billing line for the given department if a default
     * consultation service is configured for it.
     */
    private function createConsultationBilling(Visit $visit, int $departmentId): void
    {
        $service = ServiceCatalog::where('department_id', $departmentId)
            ->where('category', \App\Enums\ServiceType::CONSULTATION->value)
            ->where('is_active', true)
            ->first();

        // Fallback: any active consultation service in the catalog
        if (!$service) {
            $service = ServiceCatalog::where('category', \App\Enums\ServiceType::CONSULTATION->value)
                ->where('is_active', true)
                ->first();
        }

        if ($service) {
            // Avoid duplicate consultation billing for this visit
            $alreadyBilled = $visit->visitServices()
                ->where('service_catalog_id', $service->id)
                ->exists();

            if (!$alreadyBilled) {
                $this->attachServices($visit, [[
                    'service_catalog_id' => $service->id,
                    'quantity' => 1,
                ]]);
            }
        }
    }

    /**
     * Create an investigation billing line for the given department.
     */
    private function createInvestigationBilling(Visit $visit, int $departmentId): void
    {
        $service = ServiceCatalog::where('department_id', $departmentId)
            ->where('service_type', \App\Enums\ServiceType::INVESTIGATION->value)
            ->where('is_active', true)
            ->first();

        if ($service) {
            $this->attachServices($visit, [[
                'service_catalog_id' => $service->id,
                'quantity' => 1,
            ]]);
        }
    }

    public function todayStats(): array
    {
        $today = Visit::today();

        return [
            'total' => (clone $today)->count(),
            'waiting' => (clone $today)->byStatus(VisitStatus::WAITING)->count(),
            'consulting' => (clone $today)->byStatus(VisitStatus::CONSULTING)->count(),
            'completed' => (clone $today)->byStatus(VisitStatus::COMPLETED)->count(),
            'cancelled' => (clone $today)->byStatus(VisitStatus::CANCELLED)->count(),
            'emergency' => (clone $today)->where('priority', 'emergency')->count(),
        ];
    }

    /**
     * Get upcoming scheduled visits for a patient.
     */
    public function upcomingForPatient(int $patientId): \Illuminate\Database\Eloquent\Collection
    {
        return Visit::where('patient_id', $patientId)
            ->whereIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])
            ->where('visit_date', '>=', today())
            ->orderBy('visit_date')
            ->orderBy('start_time')
            ->with(['assignedDoctor'])
            ->get();
    }

    /**
     * Get calendar data for scheduled visits.
     */
    public function calendarData(array $filters = []): array
    {
        $query = Visit::whereIn('status', [
            VisitStatus::SCHEDULED,
            VisitStatus::CONFIRMED,
        ]);

        if (!empty($filters['doctor_id'])) {
            $query->where('assigned_doctor_id', $filters['doctor_id']);
        }

        if (!empty($filters['start'])) {
            $query->whereDate('visit_date', '>=', $filters['start']);
        }

        if (!empty($filters['end'])) {
            $query->whereDate('visit_date', '<=', $filters['end']);
        }

        return $query->with(['patient', 'assignedDoctor'])
            ->get()
            ->map(fn(Visit $v) => [
                'id' => $v->id,
                'title' => $v->patient->full_name,
                'start' => $v->visit_date->format('Y-m-d') . ($v->start_time ? 'T' . $v->start_time : ''),
                'end' => $v->visit_date->format('Y-m-d') . ($v->end_time ? 'T' . $v->end_time : ''),
                'color' => $v->status->color(),
                'extendedProps' => [
                    'visit_id' => $v->id,
                    'patient_name' => $v->patient->full_name,
                    'doctor' => $v->assignedDoctor?->name,
                    'status' => $v->status->label(),
                    'visit_type' => $v->visit_type?->label(),
                ],
            ])->toArray();
    }
}
