<?php

namespace App\Services\Consultation;

use App\Enums\VisitStatus;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ConsultationRouteService;
use App\Services\VisitService;

class ConsultationSessionWorkflowService
{
    public function __construct(
        private readonly ConsultationRouteService $routes,
        private readonly VisitService $visits,
        private readonly ConsultationActionGuard $guard,
        private readonly ConsultationCompletionReadinessService $readiness,
    ) {}

    public function completeRoute(Visit $visit, VisitConsultationRoute $route, User $user, ?string $notes = null): VisitConsultationRoute
    {
        $this->guard->assertEditableRoute($visit, $route, $user, 'consultation.route.complete');
        $this->readiness->assertReady($route, $user);

        return $this->routes->completeRoute($route, $user, $notes);
    }

    public function cancelRoute(Visit $visit, VisitConsultationRoute $route, User $user, ?string $reason = null): VisitConsultationRoute
    {
        $this->guard->assertEditableRoute($visit, $route, $user, 'consultation.route.cancel');

        return $this->routes->cancelRoute($route, $user, $reason);
    }

    public function transitionVisit(Visit $visit, string $status, ?string $notes = null, ?User $user = null): VisitStatus
    {
        $newStatus = VisitStatus::from($status);

        if (! $visit->canTransitionTo($newStatus)) {
            throw new \DomainException(__('messages.visits.cannot_transition', [
                'from' => $visit->status->label(),
                'to' => $newStatus->label(),
            ]));
        }

        if ($newStatus === VisitStatus::COMPLETED && $user) {
            $route = $visit->activeConsultationRoute()->first()
                ?? $visit->consultationRoutes()->latest('activated_at')->latest('id')->first();

            if ($route) {
                $this->readiness->assertReady($route, $user);
            }
        }

        $this->visits->transition($visit, $newStatus, $notes);

        return $newStatus;
    }
}
