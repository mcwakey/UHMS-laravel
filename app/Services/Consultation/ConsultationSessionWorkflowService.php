<?php

namespace App\Services\Consultation;

use App\Enums\LogModule;
use App\Enums\VisitStatus;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Models\VisitConsultationRouteLog;
use App\Services\ActivityLogService;
use App\Services\ConsultationRouteService;
use App\Services\ConsultationSessionService;
use App\Services\VisitService;
use Illuminate\Support\Facades\DB;

class ConsultationSessionWorkflowService
{
    public function __construct(
        private readonly ConsultationRouteService $routes,
        private readonly VisitService $visits,
        private readonly ConsultationActionGuard $guard,
        private readonly ConsultationCompletionReadinessService $readiness,
        private readonly ConsultationReopenEligibilityService $reopenEligibility,
        private readonly ConsultationAutoCompletionService $autoCompletion,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function completeRoute(Visit $visit, VisitConsultationRoute $route, User $user, ?string $notes = null): VisitConsultationRoute
    {
        $this->guard->assertEditableRoute($visit, $route, $user, 'consultation.route.complete');
        $this->readiness->assertReady($route, $user);

        $route = $this->routes->completeRoute($route, $user, $notes);

        $this->autoCompletion->evaluate($visit, $user, $route, $notes);

        return $route;
    }

    public function cancelRoute(Visit $visit, VisitConsultationRoute $route, User $user, ?string $reason = null): VisitConsultationRoute
    {
        $this->guard->assertEditableRoute($visit, $route, $user, 'consultation.route.cancel');

        return $this->routes->cancelRoute($route, $user, $reason);
    }

    public function reopenRoute(Visit $visit, VisitConsultationRoute $route, User $user, string $reason): VisitConsultationRoute
    {
        $route->loadMissing(['visit', 'department', 'medicalRecord']);
        $visit->loadMissing(['admission']);

        $this->logReopenEvent('CONSULTATION_REOPEN_REQUESTED', $visit, $route, $user, $reason, [
            'previous_route_status' => $route->status,
            'discharged_at' => $visit->admission?->actual_discharge_date?->toDateTimeString(),
        ]);

        $eligibility = $this->reopenEligibility->canReopen($user, $visit, $route);
        if (! $eligibility->allowed) {
            $this->logReopenEvent($eligibility->auditEvent ?? 'CONSULTATION_REOPEN_BLOCKED', $visit, $route, $user, $reason, [
                'previous_route_status' => $route->status,
                'blocked_code' => $eligibility->code,
                'discharged_at' => $visit->admission?->actual_discharge_date?->toDateTimeString(),
            ]);

            $status = match ($eligibility->code) {
                'permission_denied' => 403,
                'locked_session' => 423,
                default => 422,
            };

            throw new ConsultationActionException($eligibility->message, $status, 'CONSULTATION_REOPEN_BLOCKED');
        }

        return DB::transaction(function () use ($visit, $route, $user, $reason, $eligibility) {
            $previousStatus = $route->status;

            $visit->consultationRoutes()
                ->whereKeyNot($route->id)
                ->where('status', VisitConsultationRoute::STATUS_ACTIVE)
                ->update([
                    'status' => VisitConsultationRoute::STATUS_PAUSED,
                    'paused_at' => now(),
                    'updated_at' => now(),
                ]);

            $route->forceFill([
                'status' => VisitConsultationRoute::STATUS_ACTIVE,
                'activated_at' => now(),
                'reopened_at' => now(),
                'reopened_by' => $user->id,
                'reopen_reason' => $reason,
                'reopen_count' => ((int) $route->reopen_count) + 1,
            ])->save();

            $this->restoreVisitForConsultation($visit, $route, $user, $reason);

            app(ConsultationSessionService::class)->getOrCreateMedicalRecordForRoute($route, $user);

            VisitConsultationRouteLog::create([
                'visit_consultation_route_id' => $route->id,
                'visit_id' => $visit->id,
                'from_status' => $previousStatus,
                'to_status' => VisitConsultationRoute::STATUS_ACTIVE,
                'action' => 'reopened',
                'notes' => $reason,
                'performed_by' => $user->id,
            ]);

            $metadata = [
                'previous_route_status' => $previousStatus,
                'new_route_status' => VisitConsultationRoute::STATUS_ACTIVE,
                'eligibility_code' => $eligibility->code,
                'discharged_at' => $visit->admission?->actual_discharge_date?->toDateTimeString(),
            ];

            $this->logReopenEvent('CONSULTATION_REOPEN_ACCEPTED', $visit, $route->fresh(), $user, $reason, $metadata);
            if ($eligibility->auditEvent) {
                $this->logReopenEvent($eligibility->auditEvent, $visit, $route->fresh(), $user, $reason, $metadata);
            }

            return $route->fresh(['department', 'service', 'routeServices.service', 'medicalRecord']);
        });
    }

    public function reopenVisitForRouteActivation(Visit $visit, VisitConsultationRoute $route, User $user, string $reason): Visit
    {
        $route->loadMissing(['visit', 'department']);

        if (! $user->can('consultations.reopen')) {
            throw new ConsultationActionException(__('consultations.reopen.permission_denied'), 403, 'CONSULTATION_REOPEN_BLOCKED');
        }

        if ($route->isLocked()) {
            throw new ConsultationActionException(__('consultations.reopen.locked_session'), 423, 'CONSULTATION_REOPEN_BLOCKED');
        }

        if ($route->status === VisitConsultationRoute::STATUS_CANCELLED) {
            throw new ConsultationActionException(__('messages.consultations.consultation_cancelled_readonly'), 423, 'CONSULTATION_REOPEN_BLOCKED');
        }

        if ($route->status === VisitConsultationRoute::STATUS_COMPLETED) {
            return $this->reopenRoute($visit, $route, $user, $reason)->visit;
        }

        return DB::transaction(function () use ($visit, $route, $user, $reason) {
            $this->restoreVisitForConsultation($visit, $route, $user, $reason);

            $this->logReopenEvent('CONSULTATION_VISIT_REOPENED_FOR_ROUTE_ACTIVATION', $visit->fresh(), $route, $user, $reason, [
                'route_status' => $route->status,
            ]);

            return $visit->fresh(['consultationRoutes']);
        });
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

    private function logReopenEvent(
        string $event,
        Visit $visit,
        VisitConsultationRoute $route,
        User $user,
        string $reason,
        array $metadata = [],
    ): void {
        $this->activityLog->log(
            LogModule::CONSULTATION,
            $event,
            [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'consultation_route_id' => $route->id,
                'department_id' => $route->department_id,
                'causer' => $user,
                'reason' => $reason,
                'metadata' => $metadata,
            ],
            $route,
            'Consultation reopen workflow event.',
        );
    }

    private function restoreVisitForConsultation(Visit $visit, VisitConsultationRoute $route, User $user, string $reason): void
    {
        if ($visit->status === VisitStatus::CONSULTING) {
            return;
        }

        $from = $visit->status;

        $visit->forceFill([
            'status' => VisitStatus::CONSULTING,
            'current_department_id' => $route->department_id,
            'checked_out_at' => null,
            'completed_at' => null,
            'completed_by' => null,
        ])->save();

        $visit->statusLogs()->create([
            'from_status' => $from->value,
            'to_status' => VisitStatus::CONSULTING->value,
            'changed_by' => $user->id,
            'notes' => $reason,
        ]);

        $this->logReopenEvent('CONSULTATION_VISIT_STATUS_REOPENED', $visit->fresh(), $route, $user, $reason, [
            'previous_visit_status' => $from->value,
            'new_visit_status' => VisitStatus::CONSULTING->value,
        ]);
    }
}
