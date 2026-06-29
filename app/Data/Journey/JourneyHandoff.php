<?php

namespace App\Data\Journey;

use App\Enums\JourneyDelayCause;
use App\Enums\PatientJourneyStage;
use App\Services\Journey\JourneySlaService;
use Illuminate\Support\Carbon;

/**
 * Immutable view model for a cross-department handoff: the FROM department waiting
 * and the TO department responsible, with SLA status. Primitives + enums only — no
 * Eloquent leaks into Blade.
 */
final class JourneyHandoff
{
    public function __construct(
        public readonly int $visitId,
        public readonly ?int $patientId,
        public readonly string $patientName,
        public readonly ?string $visitNumber,
        public readonly ?PatientJourneyStage $stage,
        public readonly JourneyDelayCause $cause,
        public readonly string $severity,        // normal | delayed | critical
        public readonly ?int $fromDepartmentId,
        public readonly ?string $fromDepartmentName,
        public readonly ?string $fromDepartmentType,
        public readonly ?int $toDepartmentId,
        public readonly ?string $toDepartmentName,
        public readonly ?string $toDepartmentType,
        public readonly string $actionLabel,
        public readonly string $actionStatus,    // open | actionable | blocked | resolved
        public readonly ?string $actionUrl,
        public readonly int $elapsedMinutes,
        public readonly int $slaMinutes,
        public readonly string $slaStatus,        // within | near_breach | breached | critical_breach
        public readonly ?int $minutesToBreach,
        public readonly ?Carbon $waitingSince,
    ) {}

    // ---- Assignment overlay (Phase 9.5) --------------------------------
    // Operational coordination state ATTACHED after resolution (not derived).
    public ?int $assignmentId = null;

    public string $assignmentStatus = 'unassigned';

    public ?int $assignedToUserId = null;

    public ?string $assignedToName = null;

    public ?Carbon $assignedAt = null;

    public ?Carbon $acknowledgedAt = null;

    public ?Carbon $resolvedAt = null;

    public string $escalationLevel = 'none';

    public ?Carbon $lastEscalatedAt = null;

    /** Attach a persisted assignment (and the derived escalation level) for display. */
    public function attachAssignment(?\App\Models\JourneyHandoffAssignment $assignment, string $escalationLevel): self
    {
        $this->escalationLevel = $escalationLevel;

        if ($assignment !== null) {
            $this->assignmentId = $assignment->id;
            $this->assignmentStatus = $assignment->status;
            $this->assignedToUserId = $assignment->assigned_to_user_id;
            $this->assignedToName = $assignment->assignedTo?->full_name
                ?? trim(($assignment->assignedTo->first_name ?? '').' '.($assignment->assignedTo->last_name ?? '')) ?: null;
            $this->assignedAt = $assignment->assigned_at;
            $this->acknowledgedAt = $assignment->acknowledged_at;
            $this->resolvedAt = $assignment->resolved_at;
            $this->lastEscalatedAt = $assignment->last_escalated_at;
        }

        return $this;
    }

    public function isUnassigned(): bool
    {
        return $this->assignedToUserId === null;
    }

    /** True when FROM and TO are different departments (a real handoff, not self-owned work). */
    public function isCrossDepartment(): bool
    {
        // Compare domains/types first — the TO department id can fall back to the
        // current (FROM) department when the specific target isn't recorded.
        if ($this->fromDepartmentType !== null && $this->toDepartmentType !== null) {
            return $this->fromDepartmentType !== $this->toDepartmentType;
        }

        return $this->fromDepartmentId !== null
            && $this->toDepartmentId !== null
            && $this->fromDepartmentId !== $this->toDepartmentId;
    }

    public function isBreached(): bool
    {
        return in_array($this->slaStatus, [JourneySlaService::BREACHED, JourneySlaService::CRITICAL_BREACH], true);
    }

    /**
     * Worst-first sort weight: SLA breach state, then journey severity, then waiting.
     */
    public function rank(): int
    {
        $sla = match ($this->slaStatus) {
            JourneySlaService::CRITICAL_BREACH => 40_000_000,
            JourneySlaService::BREACHED => 30_000_000,
            JourneySlaService::NEAR_BREACH => 20_000_000,
            default => 0,
        };
        $severity = match ($this->severity) {
            'critical' => 2_000_000,
            'delayed' => 1_000_000,
            default => 0,
        };

        return $sla + $severity + min($this->elapsedMinutes, 999_999);
    }

    /** @return array<string, mixed> a flat, view-safe representation. */
    public function toArray(): array
    {
        return [
            'visit_id' => $this->visitId,
            'patient_id' => $this->patientId,
            'patient_name' => $this->patientName,
            'visit_number' => $this->visitNumber,
            'stage' => $this->stage?->value,
            'cause' => $this->cause->value,
            'severity' => $this->severity,
            'from_department_id' => $this->fromDepartmentId,
            'from_department_name' => $this->fromDepartmentName,
            'from_department_type' => $this->fromDepartmentType,
            'to_department_id' => $this->toDepartmentId,
            'to_department_name' => $this->toDepartmentName,
            'to_department_type' => $this->toDepartmentType,
            'action_label' => $this->actionLabel,
            'action_status' => $this->actionStatus,
            'action_url' => $this->actionUrl,
            'elapsed_minutes' => $this->elapsedMinutes,
            'sla_minutes' => $this->slaMinutes,
            'sla_status' => $this->slaStatus,
            'minutes_to_breach' => $this->minutesToBreach,
            'waiting_since' => $this->waitingSince,
            'assignment_id' => $this->assignmentId,
            'assignment_status' => $this->assignmentStatus,
            'assigned_to_user_id' => $this->assignedToUserId,
            'assigned_to_name' => $this->assignedToName,
            'assigned_at' => $this->assignedAt,
            'acknowledged_at' => $this->acknowledgedAt,
            'resolved_at' => $this->resolvedAt,
            'escalation_level' => $this->escalationLevel,
            'last_escalated_at' => $this->lastEscalatedAt,
        ];
    }
}
