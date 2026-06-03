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
            }

            if ($invoiceItem) {
                $routeService->update(['invoice_item_id' => $invoiceItem->id]);
            }
        }

        return $routeService->fresh(['service', 'invoiceItem']);
    }

    public function activateRoute(VisitConsultationRoute $route, User $user): VisitConsultationRoute
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

            $workflowStarted = false;
            if ($visit->status === VisitStatus::WAITING_CONSULTATION) {
                $this->visitWorkflowService->startConsultation($visit, $user, $route->id);
                $route->refresh();
                $workflowStarted = true;
            } elseif ($visit->status !== VisitStatus::CONSULTING) {
                throw new \InvalidArgumentException('Route can only be activated when the visit is waiting consultation or consulting.');
            }

            $route->forceFill([
                'status' => VisitConsultationRoute::STATUS_ACTIVE,
                'doctor_id' => $route->doctor_id ?: $user->id,
                'started_by' => $route->started_by ?: $user->id,
                'started_at' => $route->started_at ?: now(),
                'activated_at' => now(),
            ])->save();

            $visit->forceFill(['current_department_id' => $route->department_id])->save();
            $this->queueService->completeCurrentEntry($visit);
            $this->queueService->addForDepartment($visit->fresh(), $route->department_id);

            $action = $fromStatus === VisitConsultationRoute::STATUS_PAUSED ? 'resumed' : 'activated';
            if (! $workflowStarted) {
                $this->log($route, $fromStatus, VisitConsultationRoute::STATUS_ACTIVE, $action, null, $user);
            }

            $this->consultationSessionService->getOrCreateMedicalRecordForRoute($route, $user);

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
            $from = $route->status;
            $route->update([
                'status' => VisitConsultationRoute::STATUS_COMPLETED,
                'completed_by' => $user->id,
                'completed_at' => now(),
                'notes' => $notes ?: $route->notes,
            ]);

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
