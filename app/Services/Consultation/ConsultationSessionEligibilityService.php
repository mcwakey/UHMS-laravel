<?php

namespace App\Services\Consultation;

use App\Enums\AdmissionStatus;
use App\Enums\VisitStatus;
use App\Enums\VisitType;
use App\Models\Admission;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitConsultationRoute;
use App\Services\Admissions\AdmissionExtensionService;
use Illuminate\Support\Carbon;

class ConsultationSessionEligibilityService
{
    public function __construct(private readonly AdmissionExtensionService $admissionExtensions) {}

    public function canAddItem(Visit $visit, VisitConsultationRoute $session, User $user): bool
    {
        return $this->addItemDecision($visit, $session, $user)['allowed'];
    }

    public function canEditSession(Visit $visit, VisitConsultationRoute $session, User $user): bool
    {
        return $this->canAddItem($visit, $session, $user);
    }

    public function canReopenSession(Visit $visit, VisitConsultationRoute $session, User $user): bool
    {
        return $this->reopenDecision($visit, $session, $user)['allowed'];
    }

    public function canReopenConsultation(Visit $visit, User $user): bool
    {
        $session = $visit->consultationRoutes()
            ->where('status', VisitConsultationRoute::STATUS_COMPLETED)
            ->latest('completed_at')
            ->latest('id')
            ->first();

        return $session ? $this->canReopenSession($visit, $session, $user) : false;
    }

    public function isSessionLocked(Visit $visit, VisitConsultationRoute $session): bool
    {
        return $session->isLocked()
            || in_array($session->status, [
                VisitConsultationRoute::STATUS_COMPLETED,
                VisitConsultationRoute::STATUS_CANCELLED,
            ], true);
    }

    public function isVisitClinicallyLocked(Visit $visit): bool
    {
        $visit->loadMissing('admission');

        if ($visit->locked_at) {
            return true;
        }

        if ($this->isActiveAdmission($visit->admission)) {
            return false;
        }

        if ($visit->admission?->actual_discharge_date) {
            return ! $this->isWithinDischargedInpatientGraceWindow($visit);
        }

        if ($visit->visit_type === VisitType::OUTPATIENT) {
            return ! $this->isWithinOutpatientEditWindow($visit);
        }

        return false;
    }

    public function isWithinOutpatientEditWindow(Visit $visit): bool
    {
        return $this->hospitalDay($visit->visit_date ?? $visit->created_at)->isSameDay($this->today());
    }

    public function isWithinDischargedInpatientGraceWindow(Visit $visit): bool
    {
        $visit->loadMissing('admission');

        return (bool) $visit->admission?->actual_discharge_date
            && $this->hospitalDay($visit->admission->actual_discharge_date)->isSameDay($this->today());
    }

    public function shouldAutoCompleteConsultation(Visit $visit): bool
    {
        $visit->loadMissing('admission');

        if ($this->isActiveAdmission($visit->admission)) {
            return false;
        }

        return $visit->consultationRoutes()
            ->where('status', '!=', VisitConsultationRoute::STATUS_CANCELLED)
            ->exists()
            && ! $visit->consultationRoutes()
                ->where('status', '!=', VisitConsultationRoute::STATUS_CANCELLED)
                ->where('status', '!=', VisitConsultationRoute::STATUS_COMPLETED)
                ->exists();
    }

    public function isConsultationSystemCompleted(Visit $visit): bool
    {
        return $visit->status === VisitStatus::COMPLETED || $visit->completed_at !== null;
    }

    public function shouldOfferReadmitOrExtend(Visit $visit, ?User $user = null): bool
    {
        $visit->loadMissing('admission');

        return (bool) $visit->admission
            && $this->admissionExtensions->canExtend($visit->admission)
            && (! $user || $user->can('admissions.extend') || $user->can('admissions.readmit'));
    }

    public function availableActionsFor(Visit $visit, ?VisitConsultationRoute $session, User $user): array
    {
        return [
            'can_add_item' => $session ? $this->canAddItem($visit, $session, $user) : false,
            'can_edit_session' => $session ? $this->canEditSession($visit, $session, $user) : false,
            'can_reopen_session' => $session ? $this->canReopenSession($visit, $session, $user) : false,
            'can_reopen_consultation' => $this->canReopenConsultation($visit, $user),
            'is_session_locked' => $session ? $this->isSessionLocked($visit, $session) : false,
            'is_visit_clinically_locked' => $this->isVisitClinicallyLocked($visit),
            'is_system_completed' => $this->isConsultationSystemCompleted($visit),
            'should_auto_complete' => $this->shouldAutoCompleteConsultation($visit),
            'offer_readmit_or_extend' => $this->shouldOfferReadmitOrExtend($visit, $user),
            'lock_reason' => $session ? $this->addItemDecision($visit, $session, $user)['message'] : null,
        ];
    }

    public function addItemDecision(Visit $visit, VisitConsultationRoute $session, User $user): array
    {
        if ((int) $session->visit_id !== (int) $visit->id || (int) $session->patient_id !== (int) $visit->patient_id) {
            return $this->deny('invalid_session', __('messages.consultations.invalid_consultation_route'), 422);
        }

        if ($session->isLocked()) {
            return $this->deny('locked_session', __('consultations.lock_reasons.session_completed'), 423);
        }

        if ($session->status === VisitConsultationRoute::STATUS_PENDING) {
            return $this->deny('session_not_started', __('consultations.lock_reasons.session_not_started'), 423);
        }

        if ($session->status === VisitConsultationRoute::STATUS_PAUSED) {
            return $this->deny('session_paused', __('consultations.lock_reasons.session_paused'), 423);
        }

        if ($session->status === VisitConsultationRoute::STATUS_ACTIVE && ! $session->started_at) {
            return $this->deny('session_not_started', __('consultations.lock_reasons.session_not_started'), 423);
        }

        if ($session->status === VisitConsultationRoute::STATUS_COMPLETED) {
            return $this->deny('completed_session', __('consultations.lock_reasons.session_completed'), 423);
        }

        if ($session->status === VisitConsultationRoute::STATUS_CANCELLED) {
            return $this->deny('cancelled_session', __('messages.consultations.consultation_cancelled_readonly'), 423);
        }

        $visit->loadMissing('admission');

        if ($this->isActiveAdmission($visit->admission)) {
            return $this->allow('active_admission', __('consultations.lock_reasons.active_admission_sessions_editable'));
        }

        if ($visit->admission?->actual_discharge_date) {
            if ($this->isWithinDischargedInpatientGraceWindow($visit)) {
                return $this->allow('same_day_discharge');
            }

            return $this->deny('discharge_grace_expired', __('consultations.lock_reasons.discharged_inpatient_grace_expired'), 423);
        }

        if ($visit->visit_type === VisitType::OUTPATIENT && ! $this->isWithinOutpatientEditWindow($visit)) {
            return $this->deny('outpatient_visit_day_expired', __('consultations.lock_reasons.visit_closed_after_visit_day'), 423);
        }

        return $this->allow('editable');
    }

    public function reopenDecision(Visit $visit, VisitConsultationRoute $session, User $user): array
    {
        if (! $this->hasAnyReopenPermission($user)) {
            return $this->deny('permission_denied', __('consultations.reopen.permission_denied'), 403);
        }

        if ($session->isLocked()) {
            return $this->deny('locked_session', __('consultations.reopen.locked_session'), 423);
        }

        if ($session->status !== VisitConsultationRoute::STATUS_COMPLETED) {
            return $this->deny('not_completed', __('consultations.reopen.blocked'), 422);
        }

        $visit->loadMissing('admission');

        if ($this->isActiveAdmission($visit->admission) && $user->can('consultations.reopen')) {
            return $this->allow('active_admission', __('consultations.reopen.active_admission_allowed'));
        }

        if ($visit->admission?->actual_discharge_date) {
            if (! $this->isWithinDischargedInpatientGraceWindow($visit)) {
                return $this->deny('discharge_too_old', __('consultations.reopen.discharge_too_old'), 422);
            }

            return $user->can('consultations.reopen_same_day_discharge')
                ? $this->allow('same_day_discharge', __('consultations.reopen.same_day_discharge_allowed'))
                : $this->deny('permission_denied', __('consultations.reopen.permission_denied'), 403);
        }

        if ($visit->visit_type === VisitType::OUTPATIENT && $this->isWithinOutpatientEditWindow($visit)) {
            return $user->can('consultations.reopen_completed') || $user->can('consultations.reopen')
                ? $this->allow('same_day_outpatient', __('consultations.reopen.completed_outpatient_allowed'))
                : $this->deny('permission_denied', __('consultations.reopen.permission_denied'), 403);
        }

        return $this->deny('blocked', __('consultations.reopen.blocked'), 422);
    }

    public function isActiveAdmission(?Admission $admission): bool
    {
        return (bool) $admission
            && ! $admission->actual_discharge_date
            && in_array($admission->status, [AdmissionStatus::ADMITTED, AdmissionStatus::ON_LEAVE], true);
    }

    private function hasAnyReopenPermission(User $user): bool
    {
        return $user->can('consultations.reopen')
            || $user->can('consultations.reopen_completed')
            || $user->can('consultations.reopen_same_day_discharge');
    }

    private function today(): Carbon
    {
        return Carbon::today(config('app.timezone'));
    }

    private function hospitalDay($value): Carbon
    {
        return Carbon::parse($value)->timezone(config('app.timezone'));
    }

    private function allow(string $code, ?string $message = null): array
    {
        return ['allowed' => true, 'code' => $code, 'message' => $message, 'status' => 200];
    }

    private function deny(string $code, string $message, int $status): array
    {
        return ['allowed' => false, 'code' => $code, 'message' => $message, 'status' => $status];
    }
}
