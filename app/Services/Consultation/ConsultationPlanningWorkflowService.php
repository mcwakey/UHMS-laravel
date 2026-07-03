<?php

namespace App\Services\Consultation;

use App\Models\Appointment;
use App\Models\Department;
use App\Models\ServiceCatalog;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ConsultationFollowUpService;
use App\Services\ConsultationNextPatientService;
use App\Services\ConsultationRouteService;
use App\Services\ConsultationSessionService;

class ConsultationPlanningWorkflowService
{
    public function __construct(
        private readonly ConsultationFollowUpService $followUps,
        private readonly ConsultationNextPatientService $nextPatients,
        private readonly ConsultationRouteService $routes,
        private readonly ConsultationSessionService $sessions,
        private readonly ConsultationActionGuard $guard,
        private readonly ConsultationCompletionReadinessService $readiness,
    ) {}

    public function createFollowUp(Visit $visit, VisitConsultationRoute $route, array $data, User $user): void
    {
        $this->guard->assertEditableRoute($visit, $route, $user, 'consultation.followup.create');
        $record = $this->sessions->getOrCreateMedicalRecordForRoute($route, $user);

        $this->followUps->create($visit, $route, $record, $data, $user);
    }

    public function updateFollowUp(Appointment $appointment, Visit $visit, VisitConsultationRoute $route, array $data, User $user): void
    {
        $record = $this->sessions->getOrCreateMedicalRecordForRoute($route, $user);

        $this->followUps->update($appointment, $visit, $route, $record, $data, $user);
    }

    public function cancelFollowUp(Appointment $appointment, Visit $visit, VisitConsultationRoute $route, string $reason, User $user): void
    {
        $this->followUps->cancel($appointment, $visit, $route, $reason, $user);
    }

    public function openNext(VisitConsultationRoute $route, User $user, bool $completeCurrent): VisitConsultationRoute
    {
        if ($completeCurrent) {
            $this->readiness->assertReady($route, $user);
        }

        return $this->nextPatients->openNext($route, $user, $completeCurrent);
    }

    public function refer(Visit $visit, array $data, User $user): VisitConsultationRoute
    {
        $serviceIds = collect($data['service_ids'] ?? [])
            ->when(! empty($data['service_id']), fn ($ids) => $ids->push((int) $data['service_id']))
            ->filter()
            ->unique()
            ->values();

        return $this->routes->sendToAnotherConsultation(
            visit: $visit,
            department: Department::findOrFail((int) $data['department_id']),
            service: $serviceIds->isNotEmpty()
                ? ServiceCatalog::whereIn('id', $serviceIds)->get()
                : null,
            doctor: ! empty($data['doctor_id']) ? User::findOrFail((int) $data['doctor_id']) : null,
            routedBy: $user,
            notes: $data['notes'] ?? null,
            activateNow: (bool) ($data['activate_now'] ?? false),
        );
    }
}
