<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Models\JourneyHandoffAssignment;
use App\Models\User;

/**
 * Derives the escalation level of a handoff from its SLA status + assignment state.
 * The level is DERIVED (source of truth); it is only persisted on the assignment row
 * when a row already exists and the level has risen — never as a background job.
 */
class JourneyEscalationService
{
    public function __construct(private JourneyHandoffAssignmentService $assignments) {}

    /**
     * within SLA                                  => none
     * near breach & unassigned                    => warning
     * breached & (unassigned | not acknowledged)  => supervisor
     * critical breach                             => critical
     */
    public function levelFor(JourneyHandoff $handoff, ?JourneyHandoffAssignment $assignment): string
    {
        $sla = $handoff->slaStatus;
        $assigned = $assignment !== null && $assignment->isAssigned();
        $acknowledged = $assignment !== null && $assignment->isAcknowledged();

        return match (true) {
            $sla === JourneySlaService::CRITICAL_BREACH => JourneyHandoffAssignment::ESCALATION_CRITICAL,
            $sla === JourneySlaService::BREACHED && ! $acknowledged => JourneyHandoffAssignment::ESCALATION_SUPERVISOR,
            $sla === JourneySlaService::NEAR_BREACH && ! $assigned => JourneyHandoffAssignment::ESCALATION_WARNING,
            default => JourneyHandoffAssignment::ESCALATION_NONE,
        };
    }

    public function shouldEscalate(JourneyHandoff $handoff, ?JourneyHandoffAssignment $assignment): bool
    {
        $current = $assignment?->escalation_level ?? JourneyHandoffAssignment::ESCALATION_NONE;

        return $this->rank($this->levelFor($handoff, $assignment)) > $this->rank($current);
    }

    /** Persist a risen escalation level on an existing assignment row; returns the derived level. */
    public function applyEscalation(JourneyHandoff $handoff, ?JourneyHandoffAssignment $assignment, ?User $actor = null): string
    {
        $level = $this->levelFor($handoff, $assignment);

        if ($assignment !== null && $this->rank($level) > $this->rank($assignment->escalation_level ?? JourneyHandoffAssignment::ESCALATION_NONE)) {
            $this->assignments->recordEscalation($assignment, $level, $actor);
        }

        return $level;
    }

    public function rank(string $level): int
    {
        return match ($level) {
            JourneyHandoffAssignment::ESCALATION_CRITICAL => 3,
            JourneyHandoffAssignment::ESCALATION_SUPERVISOR => 2,
            JourneyHandoffAssignment::ESCALATION_WARNING => 1,
            default => 0,
        };
    }

    public function variant(string $level): string
    {
        return match ($level) {
            JourneyHandoffAssignment::ESCALATION_CRITICAL, JourneyHandoffAssignment::ESCALATION_SUPERVISOR => 'danger',
            JourneyHandoffAssignment::ESCALATION_WARNING => 'warning',
            default => 'secondary',
        };
    }
}
