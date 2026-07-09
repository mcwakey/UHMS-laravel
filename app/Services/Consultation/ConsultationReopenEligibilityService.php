<?php

namespace App\Services\Consultation;

use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;

class ConsultationReopenEligibilityService
{
    public function __construct(
        private readonly ConsultationSessionEligibilityService $eligibility,
    ) {}

    public function canReopen(User $user, Visit $visit, ?VisitConsultationRoute $route = null): ReopenEligibilityResult
    {
        $route ??= $visit->consultationRoutes()
            ->where('status', VisitConsultationRoute::STATUS_COMPLETED)
            ->latest('completed_at')
            ->latest('id')
            ->first();

        if (! $route) {
            return ReopenEligibilityResult::deny('missing_route', __('consultations.reopen.blocked'), 'CONSULTATION_REOPEN_BLOCKED');
        }

        if ((int) $route->visit_id !== (int) $visit->id || (int) $route->patient_id !== (int) $visit->patient_id) {
            return ReopenEligibilityResult::deny('invalid_route', __('consultations.reopen.blocked'), 'CONSULTATION_REOPEN_BLOCKED');
        }

        if (! $this->hasAnyReopenPermission($user)) {
            return ReopenEligibilityResult::deny('permission_denied', __('consultations.reopen.permission_denied'), 'CONSULTATION_REOPEN_BLOCKED');
        }

        if ($route->isLocked()) {
            return ReopenEligibilityResult::deny('locked_session', __('consultations.reopen.locked_session'), 'CONSULTATION_REOPEN_BLOCKED');
        }

        if ($route->status === VisitConsultationRoute::STATUS_CANCELLED) {
            return ReopenEligibilityResult::deny('cancelled_session', __('consultations.reopen.blocked'), 'CONSULTATION_REOPEN_BLOCKED');
        }

        if ($route->status !== VisitConsultationRoute::STATUS_COMPLETED) {
            return ReopenEligibilityResult::deny('not_completed', __('consultations.reopen.blocked'), 'CONSULTATION_REOPEN_BLOCKED');
        }

        $decision = $this->eligibility->reopenDecision($visit, $route, $user);

        return match ($decision['code']) {
            'active_admission' => ReopenEligibilityResult::allow(
                'active_admission',
                $decision['message'],
                'CONSULTATION_REOPEN_DURING_ACTIVE_ADMISSION',
            ),
            'same_day_discharge' => ReopenEligibilityResult::allow(
                'same_day_discharge',
                $decision['message'],
                'CONSULTATION_REOPEN_AFTER_SAME_DAY_DISCHARGE',
            ),
            'same_day_outpatient' => ReopenEligibilityResult::allow(
                'completed_outpatient',
                $decision['message'],
                'CONSULTATION_REOPEN_COMPLETED_OUTPATIENT',
            ),
            'override' => ReopenEligibilityResult::allow(
                'override',
                __('consultations.reopen.completed_outpatient_allowed'),
                'CONSULTATION_REOPEN_AFTER_WINDOW_OVERRIDE',
            ),
            default => ReopenEligibilityResult::deny($decision['code'], $decision['message'], 'CONSULTATION_REOPEN_BLOCKED'),
        };
    }

    public function isActiveAdmission($admission): bool
    {
        return $this->eligibility->isActiveAdmission($admission);
    }

    public function isDischargedToday($admission): bool
    {
        return $admission->actual_discharge_date
            && $admission->actual_discharge_date->timezone(config('app.timezone'))->isSameDay(today());
    }

    private function hasAnyReopenPermission(User $user): bool
    {
        return $user->can('consultations.reopen')
            || $user->can('consultations.reopen_completed')
            || $user->can('consultations.reopen_same_day_discharge');
    }
}
