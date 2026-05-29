<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Models\Visit;
use Illuminate\Support\Facades\Auth;

class VisitWorkflowService
{
    public function __construct(
        protected QueueService $queueService,
        protected InsuranceService $insuranceService,
        protected VisitPathwayService $pathway,
    ) {}

    /**
     * Record the initial visit state.
     *
     * Walk-in flow: logs NULL → REGISTERED only. The caller is responsible
     * for moving the visit to WAITING / the triage queue (via
     * {@see queueForTriage()}) ONLY when the visit has consultation
     * services attached. Visits with no consultation service (lab-only,
     * pharmacy-only, etc.) must not be queued for triage.
     *
     * Scheduled flow: SCHEDULED log only, no queue entry.
     */
    public function initialize(Visit $visit, bool $isScheduled): Visit
    {
        if ($isScheduled) {
            $visit->statusLogs()->create([
                'from_status' => null,
                'to_status'   => $visit->status->value,
                'changed_by'  => Auth::id(),
                'notes'       => 'Visit scheduled',
            ]);

            return $visit->fresh();
        }

        // Walk-in: determine first visit vs returning patient
        $isFirstVisit = ! $visit->patient->visits()
            ->where('id', '!=', $visit->id)
            ->exists();

        $registrationNote = $isFirstVisit ? 'First visit — registered' : 'Returning patient — checked in';

        // Log NULL → REGISTERED. Status stays REGISTERED until the caller
        // decides whether triage is needed.
        $visit->statusLogs()->create([
            'from_status' => null,
            'to_status'   => VisitStatus::REGISTERED->value,
            'changed_by'  => Auth::id(),
            'notes'       => $registrationNote,
        ]);

        return $visit->fresh();
    }

    /**
     * Push a freshly-registered walk-in visit into the triage queue.
     * Idempotent: if the visit is already past REGISTERED it is returned
     * unchanged.
     */
    public function queueForTriage(Visit $visit): Visit
    {
        if ($visit->status !== VisitStatus::REGISTERED) {
            return $visit;
        }

        $visit->transitionTo(VisitStatus::WAITING, 'Added to triage queue');
        $this->queueService->addTriageEntry($visit->fresh());

        return $visit->fresh();
    }

    public function confirm(Visit $visit, ?string $notes = null): Visit
    {
        if ($visit->status !== VisitStatus::SCHEDULED) {
            throw new \InvalidArgumentException('Only scheduled visits can be confirmed.');
        }

        return $this->transition($visit, VisitStatus::CONFIRMED, $notes);
    }

    public function checkIn(Visit $visit): Visit
    {
        if (! in_array($visit->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])) {
            throw new \InvalidArgumentException('Only scheduled or confirmed visits can be checked in.');
        }

        $visit->update([
            'visit_date' => today(),
            'checked_in_at' => now(),
        ]);

        $this->transition($visit, VisitStatus::REGISTERED);

        return $this->transition($visit->fresh(), VisitStatus::WAITING);
    }

    public function markRescheduled(Visit $visit, ?string $reason = null): Visit
    {
        return $this->transition($visit, VisitStatus::RESCHEDULED, $reason ?? 'Rescheduled');
    }

    public function markNoShow(Visit $visit, ?string $notes = null): Visit
    {
        if (! in_array($visit->status, [VisitStatus::SCHEDULED, VisitStatus::CONFIRMED])) {
            throw new \InvalidArgumentException('Only scheduled or confirmed visits can be marked as no-show.');
        }

        return $this->transition($visit, VisitStatus::NO_SHOW, $notes ?? 'Patient did not show up');
    }

    public function cancel(Visit $visit, ?string $reason = null): Visit
    {
        $visit->update([
            'cancelled_by' => Auth::id(),
            'cancellation_reason' => $reason,
        ]);

        $this->insuranceService->voidVisitUsages($visit->id);

        return $this->transition($visit, VisitStatus::CANCELLED, $reason);
    }

    public function moveToBilling(Visit $visit, ?string $notes = null): Visit
    {
        $this->pathway->record($visit, 'BILLING_READY', [
            'title' => 'Billing ready',
            'description' => $notes ?? 'Invoice created',
        ]);

        return $visit->fresh();
    }

    public function completeAfterPayment(Visit $visit, ?string $notes = null): Visit
    {
        $this->pathway->record($visit, 'PAYMENT_COMPLETED', [
            'title' => 'Payment completed',
            'description' => $notes ?? 'Invoice fully paid',
        ]);

        return $visit->fresh();
    }

    public function transition(Visit $visit, VisitStatus $newStatus, ?string $notes = null): Visit
    {
        if ($newStatus->isDepartmentMovementStatus()) {
            throw new \InvalidArgumentException("{$newStatus->label()} is tracked through pathway events, not visit.status.");
        }

        if (! $visit->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$visit->status->label()} to {$newStatus->label()}"
            );
        }

        $visit->transitionTo($newStatus, $notes);

        $this->pathway->record($visit->fresh(), 'VISIT_STATUS_CHANGED', [
            'status' => $newStatus->value,
            'title' => 'Visit status changed',
            'description' => $notes,
        ]);

        if ($newStatus === VisitStatus::WAITING) {
            $this->queueService->addTriageEntry($visit->fresh());
        }

        if (in_array($newStatus, [
            VisitStatus::CANCELLED,
            VisitStatus::NO_SHOW,
            VisitStatus::COMPLETED,
            VisitStatus::DISCHARGED,
        ])) {
            $this->queueService->completeCurrentEntry($visit);
        }

        return $visit->fresh();
    }

    /**
     * Doctor explicitly starts a consultation.
     * Requires the visit to be in WAITING_CONSULTATION (post-triage / referral).
     * Transitions to CONSULTING and activates a PENDING consultation route.
     *
     * @param int|null $routeId When the visit has multiple PENDING routes the
     *                          caller MUST specify which route to activate.
     */
    public function startConsultation(
        Visit $visit,
        ?\App\Models\User $doctor = null,
        ?int $routeId = null,
    ): Visit {
        if ($visit->status === VisitStatus::CONSULTING) {
            return $visit;
        }
        if (! in_array($visit->status, [VisitStatus::WAITING_CONSULTATION, VisitStatus::ACTIVE, VisitStatus::EMERGENCY], true)) {
            throw new \RuntimeException(
                'Visit is not in a consultable state. Current: ' . $visit->status->label()
            );
        }

        // Resolve the consultation route to activate.
        $pendingRoutes = $visit->consultationRoutes()
            ->where('status', \App\Models\VisitConsultationRoute::STATUS_PENDING);

        if ($routeId !== null) {
            $route = (clone $pendingRoutes)->whereKey($routeId)->first();
        } else {
            $count = (clone $pendingRoutes)->count();
            if ($count > 1) {
                throw new \RuntimeException(
                    'Visit has multiple pending consultation routes; specify which one to start.'
                );
            }
            $route = (clone $pendingRoutes)->first();
        }

        if (! $route) {
            throw new \RuntimeException(
                'No pending consultation route found for this visit.'
            );
        }

        $route->update([
            'status'     => \App\Models\VisitConsultationRoute::STATUS_ACTIVE,
            'doctor_id'  => $route->doctor_id ?? $doctor?->id,
            'started_by' => Auth::id(),
            'started_at' => now(),
            'activated_at' => now(),
        ]);

        \App\Models\VisitConsultationRouteLog::create([
            'visit_consultation_route_id' => $route->id,
            'visit_id' => $visit->id,
            'from_status' => \App\Models\VisitConsultationRoute::STATUS_PENDING,
            'to_status' => \App\Models\VisitConsultationRoute::STATUS_ACTIVE,
            'action' => 'activated',
            'performed_by' => Auth::id(),
        ]);

        // Doctor identification is recorded on the route, not on the visit
        // itself, to support multi-department routing.

        $this->pathway->record($visit, 'CONSULTATION_STARTED', [
            'source' => $route,
            'department_id' => $route->department_id,
            'title' => $route->isEmergencySession() ? 'Emergency session started' : 'Consultation started',
        ]);

        if ($visit->status === VisitStatus::EMERGENCY) {
            return $visit->fresh();
        }

        return $this->transition($visit, VisitStatus::CONSULTING, 'Consultation started');
    }
}
