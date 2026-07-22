<?php

namespace App\Services\Journey;

use App\Data\Journey\JourneyHandoff;
use App\Enums\JourneyHandoffNotificationEvent;
use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Enums\NotificationModule;
use App\Models\JourneyHandoffAssignment;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
use App\Services\NotificationService;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * In-app notifications for journey handoff coordination. Reuses the app's
 * NotificationService (built-in per-user dedupe, channel preferences, safe failure)
 * — never builds a parallel notification system. Recipients are always capability-
 * scoped; messages carry no patient identifiers (the action link is capability-
 * filtered). Every dispatch is audited.
 */
class JourneyHandoffNotificationService
{
    public function __construct(
        private NotificationService $notifications,
        private JourneyNotificationRecipientResolver $recipients,
        private JourneySupervisorResolver $supervisors,
        private JourneyNotificationPreferenceService $preferences,
        private ActivityLogService $activity,
    ) {}

    public function notifyAssigned(JourneyHandoffAssignment $assignment, ?User $actor): int
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::JourneyNotifications);

        return $this->dispatch(JourneyHandoffNotificationEvent::ASSIGNED, $assignment, $this->recipients->forAssignee($assignment), $actor);
    }

    public function notifyAcknowledged(JourneyHandoffAssignment $assignment, ?User $actor): int
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::JourneyNotifications);

        return $this->dispatch(JourneyHandoffNotificationEvent::ACKNOWLEDGED, $assignment, $this->recipients->forAssigner($assignment, $actor), $actor);
    }

    public function notifyResolved(JourneyHandoffAssignment $assignment, ?User $actor): int
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::JourneyNotifications);

        return $this->dispatch(JourneyHandoffNotificationEvent::RESOLVED, $assignment, $this->recipients->forResolution($assignment, $actor), $actor);
    }

    public function notifyStaleDismissed(JourneyHandoffAssignment $assignment): int
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::JourneyNotifications);

        return $this->dispatch(JourneyHandoffNotificationEvent::STALE_DISMISSED, $assignment, $this->recipients->forAssignee($assignment), null);
    }

    public function notifyEscalated(JourneyHandoffAssignment $assignment, string $oldLevel, string $newLevel, ?User $actor = null): int
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::JourneyNotifications);

        $event = $newLevel === JourneyHandoffAssignment::ESCALATION_CRITICAL
            ? JourneyHandoffNotificationEvent::CRITICAL
            : JourneyHandoffNotificationEvent::ESCALATED;

        // Precise routing: department supervisor → escalation user → oversight (critical)
        // → eligible staff; plus the current assignee.
        $recipients = $this->supervisors->escalationRecipientsFor($assignment->to_department_id, $assignment->to_department_type, $newLevel)
            ->merge($this->recipients->forAssignee($assignment))
            ->unique('id')
            ->values();

        return $this->dispatch($event, $assignment, $recipients, $actor, ['old_level' => $oldLevel, 'new_level' => $newLevel], $newLevel);
    }

    public function notifyCriticalUnassigned(JourneyHandoff $handoff): int
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::JourneyNotifications);

        $event = $handoff->slaStatus === 'critical_breach'
            ? JourneyHandoffNotificationEvent::UNASSIGNED_BREACHED
            : JourneyHandoffNotificationEvent::UNASSIGNED_NEAR_BREACH;
        $level = $handoff->slaStatus === 'critical_breach' ? 'critical' : 'supervisor';

        $recipients = $this->supervisors->escalationRecipientsFor($handoff->toDepartmentId, $handoff->toDepartmentType, $level);

        return $this->dispatchHandoff($event, $handoff, $recipients);
    }

    // ------------------------------------------------------------------

    /** Dispatch for an assignment-backed event. */
    private function dispatch(
        JourneyHandoffNotificationEvent $event,
        JourneyHandoffAssignment $assignment,
        Collection $recipients,
        ?User $actor,
        array $extra = [],
        ?string $dedupeSuffix = null,
    ): int {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::JourneyNotifications);

        // Never notify the actor about their own action (e.g. self-claim); honour
        // per-user, per-event preferences.
        $recipients = $recipients
            ->reject(fn (User $user) => $actor !== null && $user->id === $actor->id)
            ->filter(fn (User $user) => $this->preferences->allows($user, $event, 'in_app'))
            ->values();

        if ($recipients->isEmpty() || ! config('journey.notifications.in_app', true)) {
            $this->audit($event, $assignment->visit_id, $assignment->id, $assignment->cause?->value, $recipients, $actor, $extra, 0);

            return 0;
        }

        $assignment->loadMissing('visit');
        $payload = [
            'module' => NotificationModule::CLINICAL_TASKS,
            'priority' => $event->priority(),
            'title' => $event->translatedLabel(),
            'message' => $this->message($assignment->cause?->value, $assignment->from_department_id, $assignment->to_department_type),
            'action_url' => $this->actionUrl(),
            'source_type' => 'journey_handoff_assignment',
            'source_id' => $assignment->id,
            'dedupe_key' => 'journey:'.$event->value.':a'.$assignment->id.($dedupeSuffix ? ':'.$dedupeSuffix : ''),
        ];

        $sent = $this->notifications->notifyUsers($recipients, $payload, config('journey.notifications.dedupe_minutes', 60));
        $this->audit($event, $assignment->visit_id, $assignment->id, $assignment->cause?->value, $recipients, $actor, $extra, $sent);

        return $sent;
    }

    /** Dispatch for an unassigned (no row) handoff. */
    private function dispatchHandoff(JourneyHandoffNotificationEvent $event, JourneyHandoff $handoff, Collection $recipients): int
    {
        $recipients = $recipients->filter(fn (User $user) => $this->preferences->allows($user, $event, 'in_app'))->values();

        if ($recipients->isEmpty() || ! config('journey.notifications.in_app', true)) {
            $this->audit($event, $handoff->visitId, null, $handoff->cause->value, $recipients, null, [], 0);

            return 0;
        }

        $payload = [
            'module' => NotificationModule::CLINICAL_TASKS,
            'priority' => $event->priority(),
            'title' => $event->translatedLabel(),
            'message' => $this->message($handoff->cause->value, $handoff->fromDepartmentId, $handoff->toDepartmentType),
            'action_url' => $this->actionUrl(),
            'source_type' => 'journey_handoff_visit',
            'source_id' => $handoff->visitId,
            // Stable per (visit, cause, route, SLA state): repeated sweeps don't spam,
            // but a worsening SLA notifies once more.
            'dedupe_key' => 'unassigned:'.$handoff->visitId.':'.$handoff->cause->value.':'.($handoff->fromDepartmentId ?? 0).':'.($handoff->toDepartmentId ?? 't'.$handoff->toDepartmentType).':'.$handoff->slaStatus,
        ];

        $sent = $this->notifications->notifyUsers($recipients, $payload, config('journey.notifications.dedupe_minutes', 60));
        $this->audit($event, $handoff->visitId, null, $handoff->cause->value, $recipients, null, [], $sent);

        return $sent;
    }

    private function message(?string $cause, ?int $fromDeptId, ?string $toType): string
    {
        $causeLabel = $cause ? __('journey.cause.'.$cause) : '';
        $destination = $toType ? Str::headline($toType) : '';

        return trim($causeLabel.($destination ? ' · '.$destination : ''));
    }

    private function actionUrl(): string
    {
        return route('admin.journey.worklist', ['tab' => 'assigned_to_me']);
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function audit(JourneyHandoffNotificationEvent $event, ?int $visitId, ?int $assignmentId, ?string $cause, Collection $recipients, ?User $actor, array $extra, int $sent): void
    {
        $this->activity->log(LogModule::CLINICAL_TASKS, 'JOURNEY_HANDOFF_NOTIFIED', [
            'severity' => LogSeverity::INFO,
            'event' => $event->value,
            'visit_id' => $visitId,
            'assignment_id' => $assignmentId,
            'cause' => $cause,
            'actor_user_id' => $actor?->id,
            'recipient_user_ids' => $recipients->pluck('id')->all(),
            'sent' => $sent,
            'old_escalation_level' => $extra['old_level'] ?? null,
            'new_escalation_level' => $extra['new_level'] ?? null,
        ], null, 'Journey handoff notification: '.$event->value);
    }
}
