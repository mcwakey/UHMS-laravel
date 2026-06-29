<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Models\JourneyHandoffAssignment;
use App\Models\User;
use App\Models\Visit;
use App\Services\ActivityLogService;
use App\Services\Department\DepartmentDashboardCapabilityService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\UnauthorizedException;
use RuntimeException;

/**
 * Operational coordination for cross-department handoffs: claim / assign /
 * acknowledge / resolve. The handoff itself stays derived (Phase 9.1–9.4); this
 * service only persists ownership/escalation metadata and audits every change.
 */
class JourneyHandoffAssignmentService
{
    public function __construct(
        private JourneyHandoffResolver $handoffs,
        private DepartmentDashboardCapabilityService $capabilities,
        private ActivityLogService $activity,
        private JourneyHandoffNotificationService $notifications,
    ) {}

    /** Re-derive the current handoff for a visit; null if it is no longer an active delayed handoff. */
    public function activeHandoffFor(int $visitId, ?User $user = null): ?JourneyHandoff
    {
        $visit = Visit::with(['patient', 'currentDepartment', 'statusLogs', 'labRequests', 'prescriptions', 'admission'])->find($visitId);
        if ($visit === null) {
            return null;
        }

        $handoff = $this->handoffs->resolve($visit, $user);
        if ($handoff->actionStatus === 'resolved' || ! in_array($handoff->severity, ['delayed', 'critical'], true)) {
            return null;
        }

        return $handoff;
    }

    /** Existing active assignment for a handoff — never creates a row (safe for display). */
    public function findFor(JourneyHandoff $handoff): ?JourneyHandoffAssignment
    {
        return $this->baseQuery($handoff)
            ->whereIn('status', JourneyHandoffAssignment::ACTIVE_STATUSES)
            ->latest('id')
            ->first();
    }

    /** Find or create the assignment row for a handoff (explicit actions only). */
    public function assignmentFor(JourneyHandoff $handoff): JourneyHandoffAssignment
    {
        return $this->findFor($handoff) ?? JourneyHandoffAssignment::create([
            'visit_id' => $handoff->visitId,
            'cause' => $handoff->cause->value,
            'from_department_id' => $handoff->fromDepartmentId,
            'to_department_id' => $handoff->toDepartmentId,
            'to_department_type' => $handoff->toDepartmentType,
            'status' => JourneyHandoffAssignment::STATUS_UNASSIGNED,
            'escalation_level' => JourneyHandoffAssignment::ESCALATION_NONE,
        ]);
    }

    public function claim(JourneyHandoff $handoff, User $user): JourneyHandoffAssignment
    {
        $this->authoriseDestination($user, $handoff->toDepartmentType);
        $assignment = $this->assignmentFor($handoff);
        $this->guardActive($assignment);

        $old = $this->snapshot($assignment);
        $assignment->update([
            'assigned_to_user_id' => $user->id,
            'assigned_by_user_id' => $user->id,
            'status' => JourneyHandoffAssignment::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);
        $this->audit('JOURNEY_HANDOFF_CLAIMED', $assignment, $user, $old);
        $this->notifications->notifyAssigned($assignment, $user); // self-claim notifies no one

        return $assignment;
    }

    public function assignTo(JourneyHandoff $handoff, User $assignee, User $assignedBy): JourneyHandoffAssignment
    {
        $this->authoriseDestination($assignedBy, $handoff->toDepartmentType);
        if (! $this->canActOnDestination($assignee, $handoff->toDepartmentType)) {
            throw new RuntimeException(__('journey.handoff.no_eligible_staff'));
        }

        $assignment = $this->assignmentFor($handoff);
        $this->guardActive($assignment);

        $old = $this->snapshot($assignment);
        $assignment->update([
            'assigned_to_user_id' => $assignee->id,
            'assigned_by_user_id' => $assignedBy->id,
            'status' => JourneyHandoffAssignment::STATUS_ASSIGNED,
            'assigned_at' => now(),
        ]);
        $this->audit('JOURNEY_HANDOFF_ASSIGNED', $assignment, $assignedBy, $old);
        $this->notifications->notifyAssigned($assignment, $assignedBy);

        return $assignment;
    }

    public function acknowledge(JourneyHandoffAssignment $assignment, User $user): JourneyHandoffAssignment
    {
        if ($assignment->assigned_to_user_id !== $user->id && ! $this->canActOnDestination($user, $assignment->to_department_type)) {
            throw new UnauthorizedException;
        }
        $this->guardActive($assignment);

        $old = $this->snapshot($assignment);
        $assignment->update([
            'status' => JourneyHandoffAssignment::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ]);
        $this->audit('JOURNEY_HANDOFF_ACKNOWLEDGED', $assignment, $user, $old);
        $this->notifications->notifyAcknowledged($assignment, $user);

        return $assignment;
    }

    public function resolve(JourneyHandoffAssignment $assignment, User $user, ?string $note = null): JourneyHandoffAssignment
    {
        if (! $assignment->canBeResolvedBy($user)) {
            throw new UnauthorizedException;
        }
        $this->guardActive($assignment);

        $old = $this->snapshot($assignment);
        $assignment->update([
            'status' => JourneyHandoffAssignment::STATUS_RESOLVED,
            'resolved_at' => now(),
            'notes' => $note ?: $assignment->notes,
        ]);
        $this->audit('JOURNEY_HANDOFF_RESOLVED', $assignment, $user, $old);
        $this->notifications->notifyResolved($assignment, $user);

        return $assignment;
    }

    /** Mark a stale assignment as dismissed when the underlying handoff is no longer active. */
    public function dismissIfResolved(JourneyHandoffAssignment $assignment): void
    {
        if (! $assignment->isActive()) {
            return;
        }

        $handoff = $this->activeHandoffFor($assignment->visit_id);
        $stillCurrent = $handoff !== null && JourneyHandoffAssignment::buildIdentityKey(
            $handoff->visitId, $handoff->cause, $handoff->fromDepartmentId, $handoff->toDepartmentId, $handoff->toDepartmentType
        ) === $assignment->identityKey();

        if (! $stillCurrent) {
            $this->dismissStale($assignment);
        }
    }

    /** Auto-dismiss a stale assignment (the caller has already confirmed it is stale). */
    public function dismissStale(JourneyHandoffAssignment $assignment): void
    {
        if (! $assignment->isActive()) {
            return;
        }

        $old = $this->snapshot($assignment);
        $assignment->update([
            'status' => JourneyHandoffAssignment::STATUS_DISMISSED,
            'dismissed_at' => now(),
            'resolved_at' => $assignment->resolved_at ?? now(),
            'notes' => $assignment->notes ?: __('journey.notification.auto_dismissed'),
        ]);
        $this->audit('JOURNEY_HANDOFF_DISMISSED', $assignment, null, $old);

        // Only the assignee (if any) is told their assignment was auto-cleared.
        if ($assignment->assigned_to_user_id !== null) {
            $this->notifications->notifyStaleDismissed($assignment);
        }
    }

    /** Identity-stable check that the assignment still matches its derived handoff. */
    public function matchesCurrentHandoff(JourneyHandoffAssignment $assignment, ?JourneyHandoff $handoff): bool
    {
        return $handoff !== null && JourneyHandoffAssignment::buildIdentityKey(
            $handoff->visitId, $handoff->cause, $handoff->fromDepartmentId, $handoff->toDepartmentId, $handoff->toDepartmentType
        ) === $assignment->identityKey();
    }

    /** Persist an escalation level change (called by the escalation service). */
    public function recordEscalation(JourneyHandoffAssignment $assignment, string $level, ?User $actor = null): void
    {
        if ($assignment->escalation_level === $level) {
            return;
        }

        $old = $this->snapshot($assignment);
        $oldLevel = $assignment->escalation_level ?? JourneyHandoffAssignment::ESCALATION_NONE;
        $assignment->update([
            'escalation_level' => $level,
            'last_escalated_at' => now(),
        ]);
        $this->audit('JOURNEY_HANDOFF_ESCALATED', $assignment, $actor, $old, LogSeverity::WARNING);
        $this->notifications->notifyEscalated($assignment, $oldLevel, $level, $actor);
    }

    // ------------------------------------------------------------------

    private function baseQuery(JourneyHandoff $handoff): Builder
    {
        $query = JourneyHandoffAssignment::query()
            ->where('visit_id', $handoff->visitId)
            ->where('cause', $handoff->cause->value)
            ->where('from_department_id', $handoff->fromDepartmentId);

        if ($handoff->toDepartmentId !== null) {
            $query->where('to_department_id', $handoff->toDepartmentId);
        } else {
            $query->whereNull('to_department_id')->where('to_department_type', $handoff->toDepartmentType);
        }

        return $query;
    }

    private function guardActive(JourneyHandoffAssignment $assignment): void
    {
        if ($assignment->isResolved()) {
            throw new RuntimeException(__('journey.handoff.no_longer_active'));
        }
    }

    private function authoriseDestination(User $user, ?string $toType): void
    {
        if (! $this->canActOnDestination($user, $toType)) {
            throw new UnauthorizedException;
        }
    }

    private function canActOnDestination(?User $user, ?string $toType): bool
    {
        if ($user === null) {
            return false;
        }
        $capability = $this->capabilityForType($toType);

        return $capability === null || $this->capabilities->can($user, $capability);
    }

    private function capabilityForType(?string $type): ?string
    {
        return match ($type) {
            'investigation', 'radiology', 'blood_bank' => 'investigation_access',
            'pharmacy' => 'pharmacy_access',
            'inpatient', 'maternity' => 'ward_access',
            'finance', 'administrative' => 'financial_access',
            'consultation', 'treatment', 'procedure', 'theatre', 'emergency', 'ambulance', 'nursing', 'records' => 'consultation_access',
            default => null,
        };
    }

    /** @return array{status:string,escalation_level:string,assigned_to_user_id:?int} */
    private function snapshot(JourneyHandoffAssignment $assignment): array
    {
        return [
            'status' => $assignment->status,
            'escalation_level' => $assignment->escalation_level,
            'assigned_to_user_id' => $assignment->assigned_to_user_id,
        ];
    }

    private function audit(string $event, JourneyHandoffAssignment $assignment, ?User $actor, array $old, LogSeverity $severity = LogSeverity::INFO): void
    {
        $assignment->loadMissing('visit');

        $this->activity->log(LogModule::CLINICAL_TASKS, $event, [
            'severity' => $severity,
            'visit_id' => $assignment->visit_id,
            'patient_id' => $assignment->visit?->patient_id,
            'cause' => $assignment->cause instanceof \App\Enums\JourneyDelayCause ? $assignment->cause->value : $assignment->cause,
            'from_department_id' => $assignment->from_department_id,
            'to_department_id' => $assignment->to_department_id,
            'actor_user_id' => $actor?->id,
            'assigned_to_user_id' => $assignment->assigned_to_user_id,
            'old_values' => $old,
            'new_values' => $this->snapshot($assignment),
        ], $assignment, 'Journey handoff: '.$event);
    }
}
