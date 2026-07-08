<?php

namespace App\Services\Consultation;

use App\Enums\AdmissionStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;

class ConsultationReopenEligibilityService
{
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

        $visit->loadMissing('admission');
        $admission = $visit->admission;

        if ($admission && $this->isActiveAdmission($admission)) {
            return ReopenEligibilityResult::deny('active_admission', __('consultations.reopen.blocked'), 'CONSULTATION_REOPEN_BLOCKED');
        }

        if ($admission && $admission->actual_discharge_date) {
            if (! $admission->actual_discharge_date->timezone(config('app.timezone'))->isSameDay(today())) {
                return ReopenEligibilityResult::deny('discharge_too_old', __('consultations.reopen.discharge_too_old'), 'CONSULTATION_REOPEN_BLOCKED');
            }

            if (! $user->can('consultations.reopen_same_day_discharge')) {
                return ReopenEligibilityResult::deny('permission_denied', __('consultations.reopen.permission_denied'), 'CONSULTATION_REOPEN_BLOCKED');
            }

            return ReopenEligibilityResult::allow(
                'same_day_discharge',
                __('consultations.reopen.same_day_discharge_allowed'),
                'CONSULTATION_REOPEN_AFTER_SAME_DAY_DISCHARGE',
            );
        }

        if (
            $visit->visit_type === VisitType::OUTPATIENT
            && $visit->status === VisitStatus::COMPLETED
            && $visit->completed_at?->timezone(config('app.timezone'))->isSameDay(today())
        ) {
            if (! $user->can('consultations.reopen_completed')) {
                return ReopenEligibilityResult::deny('permission_denied', __('consultations.reopen.permission_denied'), 'CONSULTATION_REOPEN_BLOCKED');
            }

            return ReopenEligibilityResult::allow(
                'completed_outpatient',
                __('consultations.reopen.completed_outpatient_allowed'),
                'CONSULTATION_REOPEN_COMPLETED_OUTPATIENT',
            );
        }

        return ReopenEligibilityResult::deny('blocked', __('consultations.reopen.blocked'), 'CONSULTATION_REOPEN_BLOCKED');
    }

    public function isActiveAdmission($admission): bool
    {
        return ! $admission->actual_discharge_date
            && in_array($admission->status, [AdmissionStatus::ADMITTED, AdmissionStatus::ON_LEAVE], true);
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
