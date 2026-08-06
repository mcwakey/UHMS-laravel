<?php

namespace App\Services;

use App\Enums\DepartmentType;
use App\Enums\UserStatus;
use App\Enums\VisitStatus;
use App\Models\Department;
use App\Models\InvoiceItem;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteLog;
use App\Models\VisitConsultationRouteService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ConsultationRouteService
{
    public function __construct(
        protected BillingService $billingService,
        protected QueueService $queueService,
        protected VisitWorkflowService $visitWorkflowService,
        protected ConsultationSessionService $consultationSessionService,
    ) {}

    public function createRouteForVisit(
        Visit $visit,
        ServiceCatalog $service,
        ?User $doctor,
        User $routedBy,
        ?string $notes = null,
    ): VisitConsultationRoute {
        $department = $service->department;
        if (! $department) {
            throw new \InvalidArgumentException('Selected consultation service has no department.');
        }

        return $this->createRouteForDepartment($visit, $department, [$service], $doctor, $routedBy, $notes);
    }

    public function createRouteForDepartment(
        Visit $visit,
        Department $department,
        iterable $services,
        ?User $doctor,
        User $routedBy,
        ?string $notes = null,
    ): VisitConsultationRoute {
        $this->assertConsultationDepartment($department);
        if ($doctor) {
            $this->assertDoctorLinkedToDepartment($doctor, $department);
        }

        $services = $this->normalizeServices($services);
        foreach ($services as $service) {
            $this->assertServiceBelongsToDepartment($department, $service);
        }

        return DB::transaction(function () use ($visit, $department, $services, $doctor, $routedBy, $notes) {
            $route = $visit->consultationRoutes()
                ->where('department_id', $department->id)
                ->where('status', '!=', VisitConsultationRoute::STATUS_CANCELLED)
                ->oldest('id')
                ->first();

            if ($route) {
                $updates = [];
                if ($doctor && ! $route->doctor_id) {
                    $updates['doctor_id'] = $doctor->id;
                }
                if ($notes && ! $route->notes) {
                    $updates['notes'] = $notes;
                }
                if ($updates) {
                    $route->update($updates);
                }
            } else {
                $route = VisitConsultationRoute::create([
                    'visit_id' => $visit->id,
                    'patient_id' => $visit->patient_id,
                    'department_id' => $department->id,
                    'service_id' => $services->first()?->id,
                    'doctor_id' => $doctor?->id,
                    'status' => VisitConsultationRoute::STATUS_PENDING,
                    'routed_by' => $routedBy->id,
                    'notes' => $notes,
                ]);

                $this->log($route, null, VisitConsultationRoute::STATUS_PENDING, 'routed', $notes, $routedBy);
            }

            foreach ($services as $service) {
                $this->attachServiceToRoute($route, $service, true);
            }

            $this->refreshLegacyPrimaryService($route);

            return $this->freshRoute($route);
        });
    }

    public function attachServiceToRoute(
        VisitConsultationRoute $route,
        ServiceCatalog $service,
        bool $billIfNeeded = true,
    ): VisitConsultationRouteService {
        $route->loadMissing(['visit', 'department']);
        $this->assertServiceBelongsToDepartment($route->department, $service);

        $existingInvoiceItem = InvoiceItem::where('visit_id', $route->visit_id)
            ->where('service_catalog_id', $service->id)
            ->orderBy('id')
            ->first();

        $routeService = VisitConsultationRouteService::updateOrCreate(
            [
                'visit_consultation_route_id' => $route->id,
                'service_id' => $service->id,
            ],
            [
                'visit_id' => $route->visit_id,
                'invoice_item_id' => $existingInvoiceItem?->id,
            ]
        );

        if (! $route->service_id) {
            $route->forceFill(['service_id' => $service->id])->save();
        }

        if ($billIfNeeded && ! $routeService->invoice_item_id && $this->isBillable($service)) {
            try {
                $invoiceItem = $this->billingService->addItemToVisitInvoice(
                    visit: $route->visit,
                    service: $service,
                    sourceType: 'visit_consultation_route_service',
                    sourceId: $routeService->id,
                    quantity: 1,
                    departmentId: $route->department_id,
                    description: $service->name,
                );
            } catch (\RuntimeException $e) {
                if (! str_contains($e->getMessage(), 'Duplicate billing prevented')) {
                    throw $e;
                }

                $invoiceItem = InvoiceItem::where('visit_id', $route->visit_id)
                    ->where('service_catalog_id', $service->id)
                    ->orderBy('id')
                    ->first();

                $this->billingService->recalculateInvoiceForItem($invoiceItem);
            }

            if ($invoiceItem) {
                $routeService->update(['invoice_item_id' => $invoiceItem->id]);
            }
        }

        return $routeService->fresh(['service', 'invoiceItem']);
    }

    public function activateRouteOnly(VisitConsultationRoute $route, User $user): VisitConsultationRoute
    {
        $route->loadMissing(['visit', 'department', 'routeServices.service', 'doctor']);
        $visit = $route->visit;

        $this->assertRouteCanBeActivated($route);

        return DB::transaction(function () use ($route, $visit, $user) {
            $previousActiveRoutes = $visit->consultationRoutes()
                ->where('id', '!=', $route->id)
                ->where('status', VisitConsultationRoute::STATUS_ACTIVE)
                ->get();

            foreach ($previousActiveRoutes as $activeRoute) {
                $from = $activeRoute->status;
                $activeRoute->update([
                    'status' => VisitConsultationRoute::STATUS_PAUSED,
                    'paused_at' => now(),
                ]);
                $this->log($activeRoute, $from, VisitConsultationRoute::STATUS_PAUSED, 'paused', 'Paused while another consultation department session was activated.', $user);
            }

            $fromStatus = $route->status;

            $route->forceFill([
                'status' => VisitConsultationRoute::STATUS_ACTIVE,
                'activated_at' => now(),
            ])->save();

            $action = $fromStatus === VisitConsultationRoute::STATUS_PAUSED ? 'resumed' : 'activated';
            if ($fromStatus !== VisitConsultationRoute::STATUS_ACTIVE) {
                $this->log($route, $fromStatus, VisitConsultationRoute::STATUS_ACTIVE, $action, null, $user);
            }

            if ($fromStatus === VisitConsultationRoute::STATUS_PENDING) {
                $this->queueService->ensureForDepartment($visit->fresh(), $route->department_id, true);
            }

            return $this->freshRoute($route);
        });
    }

    public function activateRoute(VisitConsultationRoute $route, User $user): VisitConsultationRoute
    {
        $route->loadMissing(['visit', 'department', 'routeServices.service', 'doctor']);
        $visit = $route->visit;

        if (! in_array($visit->status, [
            VisitStatus::WAITING,
            VisitStatus::ACTIVE,
            VisitStatus::CONSULTING,
            VisitStatus::EMERGENCY,
            VisitStatus::WAITING_INVESTIGATION,
            VisitStatus::LAB,
            VisitStatus::PHARMACY,
            VisitStatus::BILLING,
        ], true)) {
            throw new \InvalidArgumentException('Route can only be started when the visit is active in the clinical workflow.');
        }

        return DB::transaction(function () use ($route, $visit, $user) {
            $fromStatus = $route->status;
            $route = $this->activateRouteOnly($route, $user);
            $route->loadMissing(['visit', 'department']);
            $visit = $route->visit;
            $visit->forceFill(['current_department_id' => $route->department_id])->save();

            if (in_array($visit->status, [VisitStatus::WAITING, VisitStatus::ACTIVE], true)) {
                $this->queueService->completeCurrentEntry($visit);
                $this->visitWorkflowService->transition($visit->fresh(), VisitStatus::CONSULTING, 'Consultation started');
                $visit = $visit->fresh();
            } elseif ($visit->status !== VisitStatus::CONSULTING) {
                $previousVisitStatus = $visit->status;
                $visit->forceFill([
                    'status' => VisitStatus::CONSULTING,
                    'checked_out_at' => null,
                    'completed_at' => null,
                    'completed_by' => null,
                ])->save();
                $visit->statusLogs()->create([
                    'from_status' => $previousVisitStatus->value,
                    'to_status' => VisitStatus::CONSULTING->value,
                    'changed_by' => $user->id,
                    'notes' => 'Consultation session resumed',
                ]);
                $visit = $visit->fresh();
            }

            $route->forceFill([
                'doctor_id' => $route->doctor_id ?: $user->id,
                'started_by' => $route->started_by ?: $user->id,
                'started_at' => $route->started_at ?: now(),
                'activated_at' => now(),
            ])->save();

            $this->consultationSessionService->getOrCreateMedicalRecordForRoute($route, $user);

            $action = $fromStatus === VisitConsultationRoute::STATUS_PAUSED ? 'resumed' : 'activated';

            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::CONSULTATION,
                $action === 'resumed' ? 'SESSION_RESUMED' : 'SESSION_STARTED',
                [
                    'patient_id' => $visit->patient_id,
                    'visit_id' => $visit->id,
                    'consultation_route_id' => $route->id,
                    'department_id' => $route->department_id,
                ],
                $route,
                'Consultation session ' . $action . ($route->department?->name ? ' — ' . $route->department->name : ''),
            );

            return $this->freshRoute($route);
        });
    }

    public function completeRoute(VisitConsultationRoute $route, User $user, ?string $notes = null): VisitConsultationRoute
    {
        $route->loadMissing(['visit', 'department', 'routeServices.service']);
        if (in_array($route->status, [VisitConsultationRoute::STATUS_CANCELLED, VisitConsultationRoute::STATUS_COMPLETED], true)) {
            return $this->freshRoute($route);
        }

        return DB::transaction(function () use ($route, $user, $notes) {
            // Re-read the route UNDER LOCK inside the transaction. The check
            // above is only a cheap early exit; this is the one that has to be
            // right.
            //
            // Without it, a double-submitted completion (two requests that both
            // loaded the route while it was still ACTIVE) would run the
            // completion write twice, mint a SECOND completion occurrence and
            // write a duplicate snapshot version with byte-identical clinical
            // content — a ledger row asserting a completion that never
            // happened. The pre-14R.8 timestamp identity masked this by
            // collapsing both writes; occurrence identity correctly refuses to,
            // so the guard has to be explicit.
            $locked = VisitConsultationRoute::query()
                ->whereKey($route->id)
                ->lockForUpdate()
                ->first();

            if ($locked && in_array($locked->status, [
                VisitConsultationRoute::STATUS_CANCELLED,
                VisitConsultationRoute::STATUS_COMPLETED,
            ], true)) {
                return $this->freshRoute($route);
            }

            $from = $locked?->status ?? $route->status;
            $route->update([
                'status' => VisitConsultationRoute::STATUS_COMPLETED,
                'completed_by' => $user->id,
                'completed_at' => now(),
                'notes' => $notes ?: $route->notes,
            ]);

            // Phase 14R.8 — record the authoritative completion OCCURRENCE.
            // This is what makes a reopen-and-recompletion inside the same
            // second a genuinely distinct completion (risk P2). It runs inside
            // this transaction, after the status write, so an occurrence can
            // only exist for a completion that really happened.
            $occurrence = $this->recordCompletionOccurrence($route, $user, $from);

            // Phase 14R.6 — immutable maternity completion snapshot, captured
            // INSIDE this transaction and AFTER the completion state is
            // written, so the snapshot can only ever describe a consultation
            // that really completed. A capture failure aborts the transaction
            // rather than leaving a completed consultation with a half-written
            // medico-legal record. Inert and query-free while
            // CONSULTATION_MATERNITY_COMPLETION_SNAPSHOT_ENABLED is false.
            $this->captureMaternitySnapshot($route, $user, $occurrence);

            $this->log($route, $from, VisitConsultationRoute::STATUS_COMPLETED, 'completed', $notes, $user);

            app(\App\Services\ActivityLogService::class)->log(
                \App\Enums\LogModule::CONSULTATION,
                'SESSION_COMPLETED',
                [
                    'patient_id' => $route->visit?->patient_id,
                    'visit_id' => $route->visit_id,
                    'consultation_route_id' => $route->id,
                    'department_id' => $route->department_id,
                    'reason' => $notes,
                ],
                $route,
                'Consultation session completed' . ($route->department?->name ? ' — ' . $route->department->name : ''),
            );

            return $this->freshRoute($route);
        });
    }

    /**
     * Phase 14R.6 — capture the maternity completion snapshot.
     *
     * Locks the route row first so two concurrent completions serialise; the
     * snapshot service is then idempotent per completion occurrence, and the
     * unique (route, completion_reference) index is the final guard.
     *
     * Returns silently when the feature is off or no EXPLICIT maternity context
     * exists — readiness is advisory in this phase and must never block
     * completion.
     */
    private function captureMaternitySnapshot(
        VisitConsultationRoute $route,
        User $user,
        ?\App\Models\ConsultationCompletionOccurrence $occurrence = null,
    ): void {
        $snapshots = app(\App\Services\Consultation\Maternity\ConsultationMaternitySnapshotService::class);

        if (! $snapshots->enabled()) {
            return;
        }

        $snapshots->captureForCompletion($route->refresh(), $user, $occurrence);
    }

    /**
     * Phase 14R.8 — allocate this completion's occurrence identity.
     *
     * The route row is locked first so two concurrent completion requests
     * serialise on it; the occurrence service then allocates a monotonic number
     * and an immutable ULID, with a unique index as the final guard.
     *
     * A snapshot never mints an occurrence — it only consumes one.
     */
    private function recordCompletionOccurrence(
        VisitConsultationRoute $route,
        User $user,
        ?string $fromStatus,
    ): \App\Models\ConsultationCompletionOccurrence {
        // The route row is already locked by completeRoute() above, which is
        // what serialises concurrent completions of the SAME route.
        return app(\App\Services\Consultation\ConsultationCompletionOccurrenceService::class)
            ->record($route->refresh(), $user, $fromStatus);
    }

    public function cancelRoute(VisitConsultationRoute $route, User $user, ?string $reason = null): VisitConsultationRoute
    {
        if (in_array($route->status, [VisitConsultationRoute::STATUS_CANCELLED, VisitConsultationRoute::STATUS_COMPLETED], true)) {
            return $this->freshRoute($route);
        }

        return DB::transaction(function () use ($route, $user, $reason) {
            $from = $route->status;
            $route->update([
                'status' => VisitConsultationRoute::STATUS_CANCELLED,
                'cancelled_by' => $user->id,
                'cancelled_at' => now(),
                'cancellation_reason' => $reason,
            ]);

            $this->log($route, $from, VisitConsultationRoute::STATUS_CANCELLED, 'cancelled', $reason, $user);

            return $this->freshRoute($route);
        });
    }

    public function sendToAnotherConsultation(
        Visit $visit,
        Department $department,
        ServiceCatalog|array|Collection|null $service,
        ?User $doctor,
        User $routedBy,
        ?string $notes = null,
        bool $activateNow = false,
    ): VisitConsultationRoute {
        $services = $this->normalizeServices($service);

        $route = $this->createRouteForDepartment($visit, $department, $services, $doctor, $routedBy, $notes);

        if ($activateNow) {
            return $this->activateRoute($route, $routedBy);
        }

        return $route;
    }

    public function assertDoctorLinkedToDepartment(User $doctor, Department $department): void
    {
        $linked = User::query()
            ->whereKey($doctor->id)
            ->whereHas('specialties', fn ($q) => $q->where('specialties.department_id', $department->id))
            ->whereHas('roles', fn ($q) => $q->whereIn('name', User::CONSULTATION_ROLES))
            ->where('status', UserStatus::ACTIVE->value)
            ->exists();

        if (! $linked) {
            throw new \InvalidArgumentException('Selected doctor is not linked to this consultation department.');
        }
    }

    private function assertConsultationDepartment(Department $department): void
    {
        $type = $department->type instanceof DepartmentType ? $department->type->value : (string) $department->type;
        if ($type !== DepartmentType::CONSULTATION->value) {
            throw new \InvalidArgumentException('Selected department is not a consultation department.');
        }
    }

    private function assertServiceBelongsToDepartment(Department $department, ServiceCatalog $service): void
    {
        $this->assertConsultationDepartment($department);

        $belongs = (int) $service->department_id === (int) $department->id
            || $service->specialties()->where('specialties.department_id', $department->id)->exists();

        if (! $belongs) {
            throw new \InvalidArgumentException('Selected service does not belong to the consultation department.');
        }
    }

    private function assertRouteCanBeActivated(VisitConsultationRoute $route): void
    {
        if (in_array($route->status, [VisitConsultationRoute::STATUS_CANCELLED, VisitConsultationRoute::STATUS_COMPLETED], true)) {
            throw new \InvalidArgumentException('Completed or cancelled consultation sessions cannot be activated.');
        }

        $this->assertConsultationDepartment($route->department);
    }

    private function normalizeServices(ServiceCatalog|iterable|null $services): Collection
    {
        if ($services instanceof ServiceCatalog) {
            return collect([$services]);
        }

        if ($services instanceof Collection) {
            return $services->filter()->values();
        }

        if (is_iterable($services)) {
            return collect($services)->filter()->values();
        }

        return collect();
    }

    private function isBillable(ServiceCatalog $service): bool
    {
        return ! array_key_exists('is_billable', $service->getAttributes()) || $service->is_billable !== false;
    }

    private function refreshLegacyPrimaryService(VisitConsultationRoute $route): void
    {
        if ($route->service_id) {
            return;
        }

        $firstServiceId = $route->routeServices()->orderBy('id')->value('service_id');
        if ($firstServiceId) {
            $route->forceFill(['service_id' => $firstServiceId])->save();
        }
    }

    private function freshRoute(VisitConsultationRoute $route): VisitConsultationRoute
    {
        return $route->fresh([
            'department',
            'service',
            'services',
            'routeServices.service',
            'routeServices.invoiceItem',
            'doctor',
            'medicalRecord',
            'medicalRecords',
        ]);
    }

    private function log(
        VisitConsultationRoute $route,
        ?string $fromStatus,
        string $toStatus,
        string $action,
        ?string $notes,
        ?User $user,
    ): void {
        VisitConsultationRouteLog::create([
            'visit_consultation_route_id' => $route->id,
            'visit_id' => $route->visit_id,
            'from_status' => $fromStatus,
            'to_status' => $toStatus,
            'action' => $action,
            'notes' => $notes,
            'performed_by' => $user?->id,
        ]);
    }
}
