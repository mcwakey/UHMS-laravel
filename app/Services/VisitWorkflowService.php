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
    ) {}

    /**
     * Record the initial visit state and create any immediate queue entries.
     */
    public function initialize(Visit $visit, bool $isScheduled): Visit
    {
        $visit->statusLogs()->create([
            'from_status' => null,
            'to_status' => $visit->status->value,
            'changed_by' => Auth::id(),
            'notes' => $isScheduled ? 'Visit scheduled' : 'Visit created',
        ]);

        if (! $isScheduled && $visit->status === VisitStatus::TRIAGE) {
            $this->queueService->addTriageEntry($visit->fresh());
        }

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
        if (! in_array(VisitStatus::BILLING, $visit->status->allowedTransitions())) {
            return $visit->fresh();
        }

        return $this->transition($visit, VisitStatus::BILLING, $notes ?? 'Invoice created');
    }

    public function completeAfterPayment(Visit $visit, ?string $notes = null): Visit
    {
        if ($visit->status !== VisitStatus::BILLING) {
            return $visit->fresh();
        }

        return $this->transition($visit, VisitStatus::COMPLETED, $notes ?? 'Visit completed after full payment');
    }

    public function transition(Visit $visit, VisitStatus $newStatus, ?string $notes = null): Visit
    {
        if (! $visit->canTransitionTo($newStatus)) {
            throw new \InvalidArgumentException(
                "Cannot transition from {$visit->status->label()} to {$newStatus->label()}"
            );
        }

        $visit->transitionTo($newStatus, $notes);

        if ($newStatus === VisitStatus::TRIAGE) {
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
}