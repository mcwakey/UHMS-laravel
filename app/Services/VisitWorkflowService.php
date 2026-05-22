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
     *
     * Walk-in flow: REGISTERED → WAITING (two logs created automatically).
     * Scheduled flow: SCHEDULED (single log, no queue entry).
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

        // Log 1: NULL → REGISTERED
        $visit->statusLogs()->create([
            'from_status' => null,
            'to_status'   => VisitStatus::REGISTERED->value,
            'changed_by'  => Auth::id(),
            'notes'       => $registrationNote,
        ]);

        // Log 2: REGISTERED → WAITING (auto-transition to triage queue)
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
     * Doctor explicitly starts a consultation. Kept for backward compatibility;
     * triage now moves visits directly to CONSULTING so this is a no-op when
     * the visit is already consulting.
     */
    public function startConsultation(Visit $visit, ?\App\Models\User $doctor = null): Visit
    {
        if ($visit->status === VisitStatus::CONSULTING) {
            return $visit;
        }
        throw new \RuntimeException('Visit is not in a consultable state. Current: ' . $visit->status->label());
    }
}