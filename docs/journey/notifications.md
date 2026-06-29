# Journey Handoff Notifications (Phase 9.6)

In-app notifications for cross-department handoff coordination. They **reuse the
existing** `App\Services\NotificationService` (Laravel notifications + per-user
dedupe + channel preferences) — no parallel notification system is built.

## Events — `App\Enums\JourneyHandoffNotificationEvent`

`handoff_assigned · handoff_claimed · handoff_acknowledged · handoff_resolved ·
handoff_escalated · handoff_critical · handoff_stale_dismissed ·
handoff_unassigned_near_breach · handoff_unassigned_breached`

Each maps to a translatable title (`journey.notification.*`), an icon and a
`NotificationPriority` (critical → CRITICAL, escalation → URGENT, assignment → HIGH).

## Service — `JourneyHandoffNotificationService`

`notifyAssigned · notifyAcknowledged · notifyResolved · notifyEscalated ·
notifyCriticalUnassigned · notifyStaleDismissed`. Each builds a payload
(`module = CLINICAL_TASKS`, priority, title, message, **action_url = the worklist**,
stable `dedupe_key`) and calls `NotificationService::notifyUsers(...)`.

Safety properties:
- **Actor is never notified about their own action** (self-claim notifies no one).
- **Messages carry no patient identifiers** — only the cause + destination domain;
  the action link is the capability-filtered worklist.
- **Dedupe** via a stable key (`journey:<event>:a<id>[:<level>]`) within
  `journey.notifications.dedupe_minutes` (default 60) → no notification storms;
  repeated escalation runs to the same level send nothing new.
- **In-app only** by default (`journey.notifications.in_app`); email/sms are config
  flags, OFF, and only ride existing configured providers — no new external infra.

## Recipients — `JourneyNotificationRecipientResolver`

| Event | Recipients |
|---|---|
| assigned | the assignee (active) |
| acknowledged | the assigner (if not the actor) |
| resolved | the assignee (if resolved by someone else) |
| escalated / critical | eligible destination-domain staff (+ assignee), capped at 8 |
| unassigned breached | eligible destination-domain staff, capped at 8 |
| stale dismissed | the assignee, if any |

There is no department-head field in UHMS, so "supervisor" recipients fall back to
**eligible destination-domain staff** (who by definition hold the capability to act).
Inactive users are never notified; recipients are always capability- and
department-scoped and **bounded**.

## Preferences

`NotificationService` already resolves **per-module, per-user channel preferences**
(`NotificationPreference`) and quiet-hours digests, so journey notifications inherit
those automatically under the `CLINICAL_TASKS` module. Fine-grained per-event journey
preferences (assigned / critical / supervisor / resolved) are **deferred** — the
default behaviour is: assigned-to-me, critical and supervisor escalations on;
resolved off unless you were the assignee.

## Audit

Every dispatch logs `JOURNEY_HANDOFF_NOTIFIED` (`LogModule::CLINICAL_TASKS`) with the
event, visit/assignment/cause, actor, **recipient_user_ids**, sent count and
old→new escalation level.
