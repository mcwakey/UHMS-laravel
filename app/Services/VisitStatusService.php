<?php

namespace App\Services;

use App\Enums\VisitStatus;
use App\Models\Visit;

class VisitStatusService
{
    public function transition(Visit $visit, VisitStatus $status, ?string $notes = null): Visit
    {
        if ($this->isDepartmentMovementStatus($status)) {
            throw new \InvalidArgumentException("{$status->label()} is a department movement status and cannot be used as the visit's global status.");
        }

        if ($visit->status !== $status && ! $visit->canTransitionTo($status)) {
            throw new \InvalidArgumentException("Cannot transition from {$visit->status->label()} to {$status->label()}");
        }

        if ($visit->status !== $status) {
            $visit->transitionTo($status, $notes);
        }

        return $visit->fresh();
    }

    public function setWaitingTriage(Visit $visit, ?string $notes = null): Visit
    {
        return $this->transition($visit, VisitStatus::WAITING, $notes ?? 'Waiting for triage');
    }

    public function setTriage(Visit $visit, ?string $notes = null): Visit
    {
        return $this->transition($visit, VisitStatus::TRIAGE, $notes ?? 'Triage started');
    }

    public function setWaitingConsultation(Visit $visit, ?string $notes = null): Visit
    {
        return $this->transition($visit, VisitStatus::WAITING_CONSULTATION, $notes ?? 'Waiting for consultation');
    }

    public function setConsulting(Visit $visit, ?string $notes = null): Visit
    {
        return $this->transition($visit, VisitStatus::CONSULTING, $notes ?? 'Consultation started');
    }

    public function setActive(Visit $visit, ?string $notes = null): Visit
    {
        return $this->transition($visit, VisitStatus::ACTIVE, $notes ?? 'Visit remains active');
    }

    public function setEmergency(Visit $visit, ?string $notes = null): Visit
    {
        return $this->transition($visit, VisitStatus::EMERGENCY, $notes ?? 'Emergency care started');
    }

    public function setAdmitted(Visit $visit, ?string $notes = null): Visit
    {
        return $this->transition($visit, VisitStatus::ADMITTED, $notes ?? 'Patient admitted');
    }

    public function setCompleted(Visit $visit, ?string $notes = null): Visit
    {
        return $this->transition($visit, VisitStatus::COMPLETED, $notes ?? 'Visit completed');
    }

    public function setCancelled(Visit $visit, ?string $notes = null): Visit
    {
        return $this->transition($visit, VisitStatus::CANCELLED, $notes ?? 'Visit cancelled');
    }

    private function isDepartmentMovementStatus(VisitStatus $status): bool
    {
        return in_array($status, [
            VisitStatus::WAITING_INVESTIGATION,
            VisitStatus::LAB,
            VisitStatus::PHARMACY,
            VisitStatus::BILLING,
        ], true);
    }
}
