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

        $this->assertConsultationService($department, $service);
        if ($doctor) {
            $this->assertDoctorLinkedToDepartment($doctor, $department);
        }

        return DB::transaction(function () use ($visit, $service, $doctor, $routedBy, $notes, $department) {
            $existing = $visit->consultationRoutes()
                ->where('service_id', $service->id)
                ->whereIn('status', [
                    VisitConsultationRoute::STATUS_PENDING,
                    VisitConsultationRoute::STATUS_ACTIVE,
                    VisitConsultationRoute::STATUS_PAUSED,
                ])
                ->first();

            if ($existing) {
                if ($doctor && ! $existing->doctor_id) {
                    $existing->update(['doctor_id' => $doctor->id]);
                }
                $this->billRouteIfNeeded($visit, $service, $existing);

                return $existing->fresh(['department', 'service', 'doctor', 'medicalRecord']);
            }

            $route = VisitConsultationRoute::create([
                'visit_id' => $visit->id,
                'patient_id' => $visit->patient_id,
                'department_id' => $department->id,
                'service_id' => $service->id,
                'doctor_id' => $doctor?->id,
                'status' => VisitConsultationRoute::STATUS_PENDING,
                'routed_by' => $routedBy->id,
                'notes' => $notes,
            ]);

            $this->log($route, null, VisitConsultationRoute::STATUS_PENDING, 'routed', $notes, $routedBy);
            $this->billRouteIfNeeded($visit, $service, $route);

            return $route->fresh(['department', 'service', 'doctor', 'medicalRecord']);
        });
    }

    public function activateRoute(VisitConsultationRoute $route, User $user): VisitConsultationRoute
    {
        $route->loadMissing(['visit', 'department', 'service', 'doctor']);
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
                $this->log($activeRoute, $from, VisitConsultationRoute::STATUS_PAUSED, 'paused', 'Paused while another consultation session was activated.', $user);
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

            return $route->fresh(['department', 'service', 'doctor', 'medicalRecord']);
        });
    }

    public function completeRoute(VisitConsultationRoute $route, User $user, ?string $notes = null): VisitConsultationRoute
    {
        $route->loadMissing(['visit', 'department', 'service']);
        if (in_array($route->status, [VisitConsultationRoute::STATUS_CANCELLED, VisitConsultationRoute::STATUS_COMPLETED], true)) {
            return $route->fresh(['department', 'service', 'doctor', 'medicalRecord']);
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

            return $route->fresh(['department', 'service', 'doctor', 'medicalRecord']);
        });
    }

    public function cancelRoute(VisitConsultationRoute $route, User $user, ?string $reason = null): VisitConsultationRoute
    {
        if (in_array($route->status, [VisitConsultationRoute::STATUS_CANCELLED, VisitConsultationRoute::STATUS_COMPLETED], true)) {
            return $route->fresh(['department', 'service', 'doctor', 'medicalRecord']);
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

            return $route->fresh(['department', 'service', 'doctor', 'medicalRecord']);
        });
    }

    public function sendToAnotherConsultation(
        Visit $visit,
        Department $department,
        ServiceCatalog $service,
        ?User $doctor,
        User $routedBy,
        ?string $notes = null,
        bool $activateNow = false,
    ): VisitConsultationRoute {
        $this->assertConsultationService($department, $service);
        if ($doctor) {
            $this->assertDoctorLinkedToDepartment($doctor, $department);
        }

        $route = $this->createRouteForVisit($visit, $service, $doctor, $routedBy, $notes);

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

    private function assertConsultationService(Department $department, ServiceCatalog $service): void
    {
        $type = $department->type instanceof DepartmentType ? $department->type->value : (string) $department->type;
        if ($type !== DepartmentType::CONSULTATION->value) {
            throw new \InvalidArgumentException('Selected department is not a consultation department.');
        }

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

        $this->assertConsultationService($route->department, $route->service);
    }

    private function billRouteIfNeeded(Visit $visit, ServiceCatalog $service, VisitConsultationRoute $route): void
    {
        $alreadyBilled = InvoiceItem::where('visit_id', $visit->id)
            ->where('service_catalog_id', $service->id)
            ->exists();

        if ($alreadyBilled) {
            return;
        }

        $this->billingService->addItemToVisitInvoice(
            visit: $visit,
            service: $service,
            sourceType: 'visit_consultation_route',
            sourceId: $route->id,
            quantity: 1,
            departmentId: $route->department_id,
            description: $service->name,
        );
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
