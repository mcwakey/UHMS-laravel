<?php

namespace App\Services\Consultation;

use App\Enums\LogModule;
use App\Models\MedicalRecord;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\ActivityLogService;
use App\Services\ConsultationSessionService;
use Illuminate\Support\Facades\Gate;

class ConsultationActionGuard
{
    public function __construct(
        private ConsultationSessionService $sessions,
        private ActivityLogService $activityLog,
        private ConsultationSessionEligibilityService $eligibility,
    ) {}

    public function editable(
        Visit $visit,
        ?int $routeId,
        User $user,
        string $action,
        ?string $ability = null,
        bool $allowCorrection = false,
    ): ConsultationActionContext {
        $route = $this->sessions->resolveRouteForVisit($visit, $routeId);

        if (! $route) {
            $this->logBlocked($visit, null, $user, 'CONSULTATION_ROUTE_CONTEXT_MISSING', $action);
            throw new ConsultationActionException(__('messages.consultations.consultation_route_required'), 422, 'CONSULTATION_ROUTE_CONTEXT_MISSING');
        }

        if ((int) $route->visit_id !== (int) $visit->id || (int) $route->patient_id !== (int) $visit->patient_id) {
            $this->logBlocked($visit, $route, $user, 'CONSULTATION_ROUTE_CONTEXT_INVALID', $action);
            throw new ConsultationActionException(__('messages.consultations.invalid_consultation_route'), 422, 'CONSULTATION_ROUTE_CONTEXT_INVALID');
        }

        if ($ability && Gate::forUser($user)->denies($ability)) {
            $this->logBlocked($visit, $route, $user, 'CONSULTATION_ACTION_BLOCKED_PERMISSION', $action);
            throw new ConsultationActionException(__('messages.consultations.action_not_allowed_for_session'), 403, 'CONSULTATION_ACTION_BLOCKED_PERMISSION');
        }

        $this->assertEditableRoute($visit, $route, $user, $action, $allowCorrection);

        $record = $this->sessions->getOrCreateMedicalRecordForRoute($route, $user);

        $this->activityLog->log(
            LogModule::CONSULTATION,
            'CONSULTATION_ROUTE_CONTEXT_PRESERVED',
            [
                'patient_id' => $visit->patient_id,
                'visit_id' => $visit->id,
                'consultation_route_id' => $route->id,
                'medical_record_id' => $record->id,
                'department_id' => $route->department_id,
                'causer' => $user,
                'metadata' => ['action' => $action],
            ],
            $route,
            'Consultation route context preserved.',
        );

        return new ConsultationActionContext(
            visit: $visit->fresh(['patient']),
            route: $route->fresh(['department', 'service', 'routeServices.service']),
            medicalRecord: $record,
            user: $user,
        );
    }

    public function assertEditableRoute(
        Visit $visit,
        VisitConsultationRoute $route,
        User $user,
        string $action,
        bool $allowCorrection = false,
    ): void {
        if ($allowCorrection && $this->canCorrectCompleted($user)) {
            return;
        }

        $decision = $this->eligibility->addItemDecision($visit, $route, $user);
        if (! $decision['allowed']) {
            $event = match ($decision['code']) {
                'locked_session' => 'CONSULTATION_ACTION_BLOCKED_LOCKED_ROUTE',
                'session_not_started' => 'CONSULTATION_ACTION_BLOCKED_SESSION_NOT_STARTED',
                'session_paused' => 'CONSULTATION_ACTION_BLOCKED_SESSION_PAUSED',
                'completed_session' => 'CONSULTATION_ACTION_BLOCKED_COMPLETED_ROUTE',
                'cancelled_session' => 'CONSULTATION_ACTION_BLOCKED_CANCELLED_ROUTE',
                'outpatient_visit_day_expired' => 'CONSULTATION_ACTION_BLOCKED_OUTPATIENT_WINDOW_EXPIRED',
                'discharge_grace_expired' => 'CONSULTATION_ACTION_BLOCKED_DISCHARGE_GRACE_EXPIRED',
                default => 'CONSULTATION_ACTION_BLOCKED_SESSION_ELIGIBILITY',
            };

            $this->logBlocked($visit, $route, $user, $event, $action, ['blocked_code' => $decision['code']]);
            throw new ConsultationActionException($decision['message'], $decision['status'], $event);
        }
    }

    public function assertEntryRecordEditable(?MedicalRecord $record, User $user, string $action, bool $allowCorrection = false): void
    {
        if (! $record) {
            throw new ConsultationActionException(__('messages.consultations.consultation_route_required'), 422, 'CONSULTATION_ROUTE_CONTEXT_MISSING');
        }

        $record->loadMissing(['visit', 'consultationRoute']);
        if (! $record->visit || ! $record->consultationRoute) {
            $this->logBlocked($record->visit, $record->consultationRoute, $user, 'CONSULTATION_ROUTE_CONTEXT_MISSING', $action);
            throw new ConsultationActionException(__('messages.consultations.consultation_route_required'), 422, 'CONSULTATION_ROUTE_CONTEXT_MISSING');
        }

        $this->assertEditableRoute($record->visit, $record->consultationRoute, $user, $action, $allowCorrection);
    }

    private function canCorrectCompleted(User $user): bool
    {
        return $user->can('consultation.entries.correct_completed')
            || $user->can('visits.reopen_locked_session');
    }

    private function logBlocked(?Visit $visit, ?VisitConsultationRoute $route, User $user, string $event, string $action, array $metadata = []): void
    {
        $this->activityLog->log(
            LogModule::CONSULTATION,
            $event,
            [
                'patient_id' => $visit?->patient_id ?? $route?->patient_id,
                'visit_id' => $visit?->id ?? $route?->visit_id,
                'consultation_route_id' => $route?->id,
                'department_id' => $route?->department_id,
                'causer' => $user,
                'metadata' => array_merge([
                    'action' => $action,
                    'route_status' => $route?->status,
                    'locked' => (bool) $route?->locked_at,
                ], $metadata),
            ],
            $route,
            'Consultation action blocked.',
        );
    }
}
