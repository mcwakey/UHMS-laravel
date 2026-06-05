<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Enums\AdmissionStatus;
use App\Enums\ServiceType;
use App\Enums\TriageScore;
use App\Enums\UserStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\Department;
use App\Models\EmergencyCase;
use App\Models\InvoiceItem;
use App\Models\ServiceCatalog;
use App\Models\Triage;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteService;
use App\Models\VisitDepartmentHistory;
use App\Models\VisitServiceItem;
use App\Models\Vital;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class VisitService
{
    public function __construct(
        protected QueueService $queueService,
        protected InsuranceService $insuranceService,
        protected VisitWorkflowService $workflowService,
        protected ServicePricingService $pricingService,
        protected BillingService $billingService,
        protected PatientMergeGuard $patientMergeGuard,
        protected VisitGuardService $visitGuard,
        protected VisitStatusService $statusService,
        protected VisitPathwayService $pathway,
    ) {}

    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Visit::with([
            'patient',
            'createdBy',
            'visitInsurance.insuranceProvider',
            'visitInsurance.insuranceTier',
            'activeConsultationRoute.doctor',
            'pendingConsultationRoutes.doctor',
        ]);

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['visit_type'])) {
            $query->where('visit_type', $filters['visit_type']);
        }

        if (! empty($filters['insurance_provider_id'])) {
            $insuranceProviderId = (string) $filters['insurance_provider_id'];
            if ($insuranceProviderId === 'cash') {
                $query->where(function ($q) {
                    $q->whereNull('visit_insurance_id')
                        ->orWhereHas('visitInsurance.insuranceProvider', fn ($providerQuery) => $providerQuery->where('is_default', true));
                });
            } else {
                $query->whereHas('visitInsurance', fn ($q) => $q->where('insurance_provider_id', $insuranceProviderId));
            }
        }

        if (! empty($filters['priority'])) {
            $query->where('priority', $filters['priority']);
        }

        if (! empty($filters['doctor_id'])) {
            $query->whereHas('consultationRoutes', fn ($q) => $q->where('doctor_id', $filters['doctor_id']));
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('visit_date', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('visit_date', '<=', $filters['date_to']);
        }

        if (isset($filters['today']) && $filters['today']) {
            $query->today();
        }

        // Include scheduled visits in listing
        if (! empty($filters['include_scheduled'])) {
            $query->orWhere(function ($q) {
                $q->whereIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED]);
            });
        }

        return $query
            ->orderBy('visit_date')
            ->orderByRaw('COALESCE(checked_in_at, created_at) asc')
            ->orderBy('id')
            ->paginate($filters['per_page'] ?? 15);
    }

    public function create(array $data): Visit
    {
        $data['visit_number'] = Visit::generateVisitNumber();
        $data['visit_date'] = $data['visit_date'] ?? today();
        $data['created_by'] = Auth::id();

        // Calculate and store patient age at time of visit
        $patient = $this->visitGuard->assertCanCreateVisit(
            $data['patient_id'],
            Auth::user(),
            $data['admission_override_reason'] ?? null,
        );
        if ($patient && $patient->date_of_birth) {
            $data['patient_age'] = $patient->date_of_birth->age;
        }
        unset($data['admission_override_reason']);

        // Determine if this is a scheduled visit (future date) or walk-in
        $visitDate = Carbon::parse($data['visit_date']);
        $isScheduled = $visitDate->isAfter(today());

        if ($isScheduled) {
            $data['status'] = VisitStatus::SCHEDULED->value;
        } else {
            $data['status'] = VisitStatus::REGISTERED->value;
        }

        // One visit per day per patient check
        if (Visit::patientHasVisitOnDate($data['patient_id'], $data['visit_date'])) {
            throw new \InvalidArgumentException('Patient already has a visit on this date.');
        }

        $visit = Visit::create($data);
        $this->pathway->record($visit, 'VISIT_REGISTERED', [
            'title' => 'Visit registered',
            'description' => $isScheduled ? 'Scheduled visit created.' : 'Walk-in visit registered.',
        ]);

        $visit = $this->workflowService
            ->initialize($visit, $isScheduled)
            ->load(['patient', 'activeConsultationRoute.doctor', 'pendingConsultationRoutes.doctor']);

        $this->pathway->record($visit, $isScheduled ? 'VISIT_SCHEDULED' : 'VISIT_WAITING', [
            'title' => $isScheduled ? 'Visit scheduled' : 'Visit waiting',
            'description' => $isScheduled ? 'Visit is scheduled for a future date.' : 'Patient is waiting for triage or consultation.',
        ]);

        return $visit;
    }

    /**
     * Attach services to a visit by creating invoice line items directly.
     *
     * NOTE: This method creates both invoice line items (billing source of truth)
     * and visit_services rows (an operational service-assignment snapshot).
     *
     * @param  array  $services  Array of ['service_catalog_id' => int, 'quantity' => int, 'doctor_id' => ?int, 'notes' => ?string]
     */
    public function attachServices(Visit $visit, array $services): Visit
    {
        foreach ($services as $serviceData) {
            $catalog = ServiceCatalog::findOrFail($serviceData['service_catalog_id']);
            $quantity = max(1, (int) ($serviceData['quantity'] ?? 1));
            $doctorId = $this->isConsultationService($catalog)
                ? ($serviceData['doctor_id'] ?? null)
                : null;

            $invoiceItem = null;
            try {
                $invoiceItem = $this->billingService->addItemToVisitInvoice(
                    visit: $visit,
                    service: $catalog,
                    sourceType: 'service_catalog',
                    sourceId: $catalog->id,
                    quantity: $quantity,
                    departmentId: $catalog->department_id,
                    description: $serviceData['notes'] ?? null,
                );
            } catch (\RuntimeException $e) {
                // Silently skip duplicates so re-attaching the same service is idempotent.
                if (! str_contains($e->getMessage(), 'Duplicate billing prevented')) {
                    throw $e;
                }

                $invoiceItem = InvoiceItem::where('visit_id', $visit->id)
                    ->where('service_catalog_id', $catalog->id)
                    ->orderBy('id')
                    ->first();
            }

            // Create / update visit_services row as an audit snapshot.
            // Doctor assignment belongs to visit_consultation_routes.
            VisitServiceItem::updateOrCreate(
                [
                    'visit_id' => $visit->id,
                    'service_catalog_id' => $catalog->id,
                ],
                [
                    'department_id' => $catalog->department_id,
                    'quantity' => $quantity,
                    'unit_price' => $catalog->price ?? 0,
                    'total_price' => ($catalog->price ?? 0) * $quantity,
                    'notes' => $serviceData['notes'] ?? null,
                ]
            );

            // If the service belongs to a consultation-type department, create
            // a PENDING consultation route so the patient appears in the right
            // consultation queue after triage.
            $route = $this->ensurePendingConsultationRoute($visit, $catalog, $doctorId);
            if ($route) {
                VisitConsultationRouteService::updateOrCreate(
                    [
                        'visit_consultation_route_id' => $route->id,
                        'service_id' => $catalog->id,
                    ],
                    [
                        'visit_id' => $visit->id,
                        'invoice_item_id' => $invoiceItem?->id,
                    ]
                );

                $this->pathway->record($visit, 'CONSULTATION_ROUTE_CREATED', [
                    'source' => $route,
                    'department_id' => $route->department_id,
                    'title' => 'Consultation route created',
                    'description' => $catalog->name,
                ]);
            }
        }

        return $visit->fresh(['invoices.items.serviceCatalog', 'invoices.items.department']);
    }

    /**
     * Create a PENDING department-level visit_consultation_routes row for the
     * given service if:
     *   - the service has a department,
     *   - that department's type is CONSULTATION,
     *   - and no non-cancelled route already exists for this (visit, department) pair.
     */
    private function ensurePendingConsultationRoute(
        Visit $visit,
        ServiceCatalog $catalog,
        ?int $doctorId = null,
    ): ?VisitConsultationRoute {
        if (! $catalog->department_id) {
            return null;
        }

        $department = $catalog->department ?? Department::find($catalog->department_id);
        if (! $department) {
            return null;
        }

        $type = $department->type instanceof DepartmentType
            ? $department->type->value
            : (string) $department->type;

        if ($type !== DepartmentType::CONSULTATION->value) {
            return null;
        }

        $route = VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $department->id)
            ->where('status', '!=', VisitConsultationRoute::STATUS_CANCELLED)
            ->oldest('id')
            ->first() ?? new VisitConsultationRoute([
                'visit_id' => $visit->id,
                'department_id' => $department->id,
            ]);

        if (! $route->exists || $route->status === VisitConsultationRoute::STATUS_PENDING) {
            $route->fill([
                'patient_id' => $visit->patient_id,
                'department_id' => $department->id,
                'service_id' => $route->service_id ?: $catalog->id,
                'doctor_id' => $route->doctor_id ?: $doctorId,
                'status' => VisitConsultationRoute::STATUS_PENDING,
                'routed_by' => $route->routed_by ?? Auth::id(),
            ])->save();
        }

        return $route;
    }

    public function isConsultationService(ServiceCatalog $catalog): bool
    {
        $department = $catalog->department ?? Department::find($catalog->department_id);
        $type = $department?->type ?? $catalog->department_type;
        $typeValue = $type instanceof DepartmentType ? $type->value : (string) $type;

        return $typeValue === DepartmentType::CONSULTATION->value;
    }

    /**
     * Get services available for a department.
     * Includes services whose primary department_id matches, OR whose
     * specialties are linked to the department.
     */
    public function getServicesForDepartment(int $departmentId): Collection
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

    public function getDoctorsForDepartment(int $departmentId): Collection
    {
        return User::query()
            ->whereHas('specialties', fn ($q) => $q->where('specialties.department_id', $departmentId))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', User::CONSULTATION_ROLES))
            ->where('status', UserStatus::ACTIVE->value)
            ->with(['specialties' => fn ($q) => $q->where('specialties.department_id', $departmentId)])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Get doctors available for specific services (via specialties).
     */
    public function getDoctorsForServices(array $serviceIds): Collection
    {
        $specialtyIds = DB::table('service_specialty')
            ->whereIn('service_catalog_id', $serviceIds)
            ->pluck('specialty_id')
            ->unique();

        if ($specialtyIds->isEmpty()) {
            // Fallback: return all active doctors
            return User::query()
                ->whereHas('roles', fn ($q) => $q->whereIn('name', User::CONSULTATION_ROLES))
                ->where('status', UserStatus::ACTIVE->value)
                ->orderBy('first_name')
                ->get();
        }

        return User::query()
            ->whereHas('roles', fn ($q) => $q->whereIn('name', User::CONSULTATION_ROLES))
            ->where('status', UserStatus::ACTIVE->value)
            ->whereHas('specialties', fn ($q) => $q->whereIn('specialties.id', $specialtyIds))
            ->orderBy('first_name')
            ->get();
    }

    /**
     * Get services available for a doctor (via specialties).
     */
    public function getServicesForDoctor(int $doctorId): Collection
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

        if (! $data['visit_date'] || ! Carbon::parse($data['visit_date'])->isAfter(today())) {
            throw new \InvalidArgumentException('Scheduled visits must be for a future date.');
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
        if (! in_array($visit->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])) {
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
        if (! in_array($visit->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])) {
            throw new \InvalidArgumentException('Only scheduled or confirmed visits can be rescheduled.');
        }

        // Mark old visit as rescheduled
        $this->workflowService->markRescheduled($visit, $data['reason'] ?? 'Rescheduled');

        // Create new visit with rescheduled_from reference
        $newData = $visit->only(['patient_id', 'visit_type', 'priority', 'chief_complaint', 'notes', 'visit_insurance_id', 'consultation_mode', 'meeting_link']);
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
        return $this->statusService->transition($visit, $newStatus, $notes);
    }

    /**
     * Send patient from triage (or current service) to a specific department.
     * Completes the current active queue entry and creates a new one for the target dept.
     */
    public function sendToDepartment(Visit $visit, int $departmentId, ?string $notes = null): Visit
    {
        $serviceStatuses = [
            VisitStatus::TRIAGE,
            VisitStatus::WAITING_CONSULTATION,
            VisitStatus::CONSULTING,
            VisitStatus::ACTIVE,
            VisitStatus::EMERGENCY,
        ];

        if (! in_array($visit->status, $serviceStatuses)) {
            throw new \InvalidArgumentException(
                "Cannot send to department from status: {$visit->status->label()}"
            );
        }

        $department = Department::findOrFail($departmentId);

        // Complete the current active queue entry
        $this->queueService->completeCurrentEntry($visit);

        if ($visit->status === VisitStatus::TRIAGE) {
            $this->statusService->setWaitingConsultation($visit, $notes ?? "Assigned to {$department->name}");
        } elseif (! in_array($visit->status, [VisitStatus::CONSULTING, VisitStatus::EMERGENCY], true)) {
            $this->statusService->setActive($visit, $notes ?? "Sent to {$department->name}");
        }

        $this->pathway->record($visit->fresh(), 'DEPARTMENT_MOVEMENT', [
            'department_id' => $departmentId,
            'title' => "Sent to {$department->name}",
            'description' => $notes,
        ]);

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
     * @param  array  $data  {
     *                       blood_pressure_systolic, blood_pressure_diastolic, heart_rate,
     *                       temperature, respiratory_rate, spo2, weight?, height?,
     *                       department_id (consultation dept), notes?
     *                       }
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

        // Determine next visit status based on triage score.
        // Non-emergency: WAITING_CONSULTATION (patient is queued for a doctor to
        // start the consultation). Emergency: EMERGENCY.
        $nextStatus = match ($score) {
            TriageScore::EMERGENCY => VisitStatus::EMERGENCY,
            default => VisitStatus::WAITING_CONSULTATION,
        };

        // Assign consultation department if provided
        if (! empty($data['department_id'])) {
            $this->assignDepartment($visit, (int) $data['department_id'], $nextStatus);
        } else {
            $this->statusService->transition($visit, $nextStatus, "Triage complete - score: {$score->label()}");
        }

        $this->pathway->record($visit->fresh(), 'TRIAGE_COMPLETED', [
            'source' => $triage,
            'department_id' => $data['department_id'] ?? null,
            'status' => $score->value,
            'title' => 'Triage completed',
            'description' => "Score: {$score->label()}",
        ]);

        return $visit->fresh(['triage', 'currentDepartment']);
    }

    /**
     * Assign a department to a visit and create a billing line + queue entry.
     */
    public function assignDepartment(Visit $visit, int $departmentId, VisitStatus $newStatus): Visit
    {
        $department = Department::findOrFail($departmentId);

        // Update current department on visit
        $visit->update(['current_department_id' => $departmentId]);

        // Record dept history
        VisitDepartmentHistory::create([
            'visit_id' => $visit->id,
            'department_id' => $departmentId,
            'type' => VisitDepartmentHistory::TYPE_CONSULTATION,
            'status' => VisitDepartmentHistory::STATUS_WAITING,
            'assigned_by' => Auth::id(),
        ]);

        // Create a billing line for consultation if a consultation service exists for the dept
        $this->createConsultationBilling($visit, $departmentId);

        // Ensure there is a PENDING consultation route for this department. If
        // the visit was created with a consultation service for this dept it
        // already exists; otherwise the billing call above generated one.
        // Mark it as PENDING (do NOT activate yet) so the doctor explicitly
        // starts the consultation from the queue.
        VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $departmentId)
            ->where('status', VisitConsultationRoute::STATUS_PENDING)
            ->limit(1)
            ->update([
                'routed_by' => Auth::id(),
                'updated_at' => now(),
            ]);

        // Complete old queue entry, create new one for the dept
        $this->queueService->completeCurrentEntry($visit);

        // Transition status
        $this->statusService->transition($visit, $newStatus, "Assigned to {$department->name}");

        $this->pathway->record($visit->fresh(), 'CONSULTATION_ASSIGNED', [
            'department_id' => $departmentId,
            'title' => "Assigned to {$department->name}",
        ]);

        // Create queue entry for the new department
        $this->queueService->addForDepartment($visit->fresh(), $departmentId);

        return $visit->fresh();
    }

    /**
     * Refer a visit to another consultation department/service.
     *
     * Per the consultation routing rules the visit's status MUST remain
     * CONSULTING throughout a consultation-to-consultation transition; only
     * the current department, the active consultation route, and (optionally)
     * billing change. If no consultation service is currently active or the
     * target service has not yet been billed the appropriate billing is
     * created — but only once, never duplicating an existing invoice item.
     *
     * @param  int  $departmentId  Target consultation department.
     * @param  int|null  $serviceId  Specific consultation service catalog id
     *                               within the target department. When NULL
     *                               a sensible default service is chosen via
     *                               {@see createConsultationBilling()}.
     *
     * @throws \InvalidArgumentException
     */
    public function referPatient(
        Visit $visit,
        int $departmentId,
        ?string $notes = null,
        ?int $serviceId = null,
    ): Visit {
        if ($visit->status !== VisitStatus::CONSULTING) {
            throw new \InvalidArgumentException('Can only refer from CONSULTING status.');
        }

        if ($visit->current_department_id === $departmentId) {
            throw new \InvalidArgumentException('Cannot refer to the same department.');
        }

        $department = Department::findOrFail($departmentId);

        if (! ($department->type instanceof DepartmentType
            ? $department->type === DepartmentType::CONSULTATION
            : (string) $department->type === DepartmentType::CONSULTATION->value)) {
            throw new \InvalidArgumentException('Target department is not a consultation department.');
        }

        // Validate / resolve the target consultation service.
        $service = null;
        if ($serviceId) {
            $service = ServiceCatalog::where('id', $serviceId)
                ->where('department_id', $departmentId)
                ->where('category', ServiceType::CONSULTATION->value)
                ->first();
            if (! $service) {
                throw new \InvalidArgumentException(
                    'Selected service is not a consultation service of the target department.'
                );
            }
        }

        // Pause the currently active consultation route (if any). A session
        // is completed only when the user explicitly completes it.
        $current = $visit->activeConsultationRoute()->first();
        if ($current) {
            $current->update([
                'status' => VisitConsultationRoute::STATUS_PAUSED,
                'paused_at' => now(),
            ]);
        }

        // Record department history (referral). We keep this for audit and
        // for the UI's referral history, but the canonical "where is the
        // patient now" lives on the active consultation route.
        $this->completeDepartmentHistory($visit);
        $visit->update(['current_department_id' => $departmentId]);
        VisitDepartmentHistory::create([
            'visit_id' => $visit->id,
            'department_id' => $departmentId,
            'type' => VisitDepartmentHistory::TYPE_REFERRAL,
            'status' => VisitDepartmentHistory::STATUS_IN_PROGRESS,
            'assigned_by' => Auth::id(),
            'notes' => $notes,
        ]);

        $this->pathway->record($visit, 'CONSULTATION_REFERRED', [
            'department_id' => $departmentId,
            'title' => "Referred to {$department->name}",
            'description' => $notes,
        ]);

        // Billing: only if the target service has not already been billed on
        // this visit. createConsultationBilling() (which routes through
        // attachServices → BillingService) is itself idempotent, but checking
        // here avoids the round-trip when we already know the answer.
        if ($service) {
            $alreadyBilled = $visit->visitServices()
                ->where('service_catalog_id', $service->id)
                ->exists();
            if (! $alreadyBilled) {
                $this->attachServices($visit, [[
                    'service_catalog_id' => $service->id,
                    'quantity' => 1,
                ]]);
            } else {
                // attachServices() also creates the route; if we skipped
                // it because billing already exists we still must guarantee
                // the route exists.
                $this->ensurePendingConsultationRoute($visit, $service);
            }
        } else {
            $this->createConsultationBilling($visit, $departmentId);
        }

        // Activate the target route so the patient is immediately "in" the
        // new consultation. Status REMAINS CONSULTING — no transition.
        $targetRouteQuery = VisitConsultationRoute::where('visit_id', $visit->id)
            ->where('department_id', $departmentId);
        $targetRoute = $targetRouteQuery
            ->orderByRaw("CASE status WHEN 'PENDING' THEN 0 WHEN 'ACTIVE' THEN 1 ELSE 2 END")
            ->first();

        if ($targetRoute) {
            $targetRoute->update([
                'status' => VisitConsultationRoute::STATUS_ACTIVE,
                'started_by' => $targetRoute->started_by ?? Auth::id(),
                'started_at' => $targetRoute->started_at ?? now(),
                'routed_by' => Auth::id(),
                'notes' => $notes ?? $targetRoute->notes,
            ]);
        }

        // Move the patient's queue entry but DO NOT change visit status.
        $this->queueService->completeCurrentEntry($visit);
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

        $department = Department::findOrFail($departmentId);

        // Mark current dept history entry as in-progress (consultation still ongoing)
        // Investigation is additional, not replacing the consultation dept

        // Record investigation history
        VisitDepartmentHistory::create([
            'visit_id' => $visit->id,
            'department_id' => $departmentId,
            'type' => VisitDepartmentHistory::TYPE_INVESTIGATION,
            'status' => VisitDepartmentHistory::STATUS_WAITING,
            'assigned_by' => Auth::id(),
            'notes' => $notes,
        ]);

        // Billing line for investigation
        $this->createInvestigationBilling($visit, $departmentId);

        $this->pathway->record($visit, 'INVESTIGATION_REQUESTED', [
            'department_id' => $departmentId,
            'title' => "Investigation requested - {$department->name}",
            'description' => $notes,
        ]);

        // Queue movement only; global visit status remains CONSULTING so the
        // patient can return to the same consultation session.
        $this->queueService->completeCurrentEntry($visit);
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
                'status' => VisitDepartmentHistory::STATUS_COMPLETED,
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
            ->where('category', ServiceType::CONSULTATION->value)
            ->where('is_active', true)
            ->first();

        // Fallback: any active consultation service in the catalog
        if (! $service) {
            $service = ServiceCatalog::where('category', ServiceType::CONSULTATION->value)
                ->where('is_active', true)
                ->first();
        }

        if ($service) {
            // Avoid duplicate consultation billing for this visit
            $alreadyBilled = $visit->visitServices()
                ->where('service_catalog_id', $service->id)
                ->exists();

            if (! $alreadyBilled) {
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
            ->where('service_type', ServiceType::INVESTIGATION->value)
            ->where('is_active', true)
            ->first();

        if ($service) {
            $this->attachServices($visit, [[
                'service_catalog_id' => $service->id,
                'quantity' => 1,
            ]]);
        }
    }

    public function todayStats(array $filters = []): array
    {
        $hasFilters = ! empty(array_filter($filters, fn ($value) => $value !== null && $value !== ''));
        $from = $filters['date_from'] ?? ($hasFilters ? null : today()->toDateString());
        $to = $filters['date_to'] ?? ($hasFilters ? null : today()->toDateString());

        if ($from && ! $to) {
            $to = $from;
        }

        if ($to && ! $from) {
            $from = $to;
        }

        $visitQuery = Visit::query()
            ->when($from, fn ($query) => $query->whereDate('visit_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('visit_date', '<=', $to));

        $activeAdmissions = Admission::query()
            ->where('status', '!=', AdmissionStatus::DISCHARGED->value)
            ->when($from, fn ($query) => $query->whereDate('admission_date', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('admission_date', '<=', $to));

        $activeEmergencyCases = EmergencyCase::query()
            ->active()
            ->when($from, fn ($query) => $query->whereDate('arrival_time', '>=', $from))
            ->when($to, fn ($query) => $query->whereDate('arrival_time', '<=', $to));

        return [
            'total' => (clone $visitQuery)->count(),
            'outpatient' => (clone $visitQuery)->where('visit_type', VisitType::OUTPATIENT->value)->count(),
            'inpatient' => (clone $activeAdmissions)->count(),
            'emergency' => (clone $activeEmergencyCases)->count(),
            'waiting_consulting' => (clone $visitQuery)->whereIn('status', [
                VisitStatus::WAITING->value,
                VisitStatus::TRIAGE->value,
                VisitStatus::WAITING_CONSULTATION->value,
                VisitStatus::ACTIVE->value,
                VisitStatus::CONSULTING->value,
            ])->count(),
            'completed_cancelled' => (clone $visitQuery)->whereIn('status', [
                VisitStatus::COMPLETED->value,
                VisitStatus::CANCELLED->value,
            ])->count(),
        ];
    }

    /**
     * Get upcoming scheduled visits for a patient.
     */
    public function upcomingForPatient(int $patientId): Collection
    {
        return Visit::where('patient_id', $patientId)
            ->whereIn('status', [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])
            ->where('visit_date', '>=', today())
            ->orderBy('visit_date')
            ->orderBy('start_time')
            ->with(['activeConsultationRoute.doctor', 'pendingConsultationRoutes.doctor'])
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

        if (! empty($filters['doctor_id'])) {
            $query->whereHas('consultationRoutes', fn ($q) => $q->where('doctor_id', $filters['doctor_id']));
        }

        if (! empty($filters['start'])) {
            $query->whereDate('visit_date', '>=', $filters['start']);
        }

        if (! empty($filters['end'])) {
            $query->whereDate('visit_date', '<=', $filters['end']);
        }

        return $query->with(['patient', 'activeConsultationRoute.doctor', 'pendingConsultationRoutes.doctor'])
            ->get()
            ->map(fn (Visit $v) => [
                'id' => $v->id,
                'title' => $v->patient->full_name,
                'start' => $v->visit_date->format('Y-m-d').($v->start_time ? 'T'.$v->start_time : ''),
                'end' => $v->visit_date->format('Y-m-d').($v->end_time ? 'T'.$v->end_time : ''),
                'color' => $v->status->color(),
                'extendedProps' => [
                    'visit_id' => $v->id,
                    'patient_name' => $v->patient->full_name,
                    'doctor' => $v->currentConsultationDoctor()?->name,
                    'status' => $v->status->label(),
                    'visit_type' => $v->visit_type?->label(),
                ],
            ])->toArray();
    }
}
