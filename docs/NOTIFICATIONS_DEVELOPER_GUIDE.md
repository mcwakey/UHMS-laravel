# Notifications — Developer Guide

In-app notifications are produced through `NotificationService` and delivered
via the `DatabaseNotification` notification class which supports four
channels: `database`, `broadcast`, `mail`, `sms`.

## 1. API

```php
use App\Services\NotificationService;
use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;

app(NotificationService::class)->notifyRole('Pharmacist', [
    'module'      => NotificationModule::PHARMACY,
    'priority'    => NotificationPriority::HIGH,
    'title'       => 'New prescription',
    'message'     => 'Prescription #'.$rx->id.' is awaiting dispensing.',
    'url'         => route('admin.prescriptions.show', $rx),
    'source_type' => 'prescription',
    'source_id'   => $rx->id,
    'patient_id'  => $rx->patient_id,
]);
```

Targeting helpers:
- `notifyUser(User $user, array $payload, ?int $dedupeMinutes = null)`
- `notifyUsers(iterable $users, array $payload, …)`
- `notifyRole(string|array $roles, array $payload, …)`
- `notifyPermission(string $permission, array $payload, …)` ← preferred
- `notifyDepartment(int|Department $dept, array $payload, …)`

## 2. Payload contract

| Key | Required | Notes |
| --- | --- | --- |
| `module` | yes | `NotificationModule` enum (or its `value`) |
| `priority` | yes | `NotificationPriority` enum; defaults to `NORMAL` |
| `title` | yes | Short headline |
| `message` | yes | Human-readable body |
| `url` / `action_url` | recommended | Deep link |
| `source_type` + `source_id` | strongly recommended | Drives dedupe key + audit pivot |
| `patient_id`, `visit_id`, etc. | when relevant | Stored on the notification row |

## 3. Dedupe

If the recipient already received an identical `dedupe_key` within the
configured window (`notifications.dedupe_minutes`, default 15) the new
notification is suppressed. Pass `dedupeMinutes: 0` to opt out (used for
broadcast and escalation paths).

## 4. Per-user preferences

Stored in `notification_preferences` (managed under
**Profile → Notification preferences**, route
`admin.notification-preferences.index`). For each module the user picks:

- Channels: `database`, `broadcast`, `mail`, `sms`
- Digest mode (defer non-urgent alerts until quiet hours end)
- Quiet hours window

`NotificationService::resolvePreference()` consults this table for every
recipient and writes the chosen channels into the payload as `_channels`,
which `DatabaseNotification::via()` honours.

## 5. Digest queue

Non-urgent notifications received during quiet hours are buffered into
`notification_digest_queue` and flushed every 10 minutes by
`notifications:flush-digest`. Urgent/critical priorities bypass digest.

## 6. Broadcast (admin)

`Admin → Notifications → Broadcast` — permission `notifications.broadcast` —
lets staff target a role / permission / department with any priority.
Uses `dedupeMinutes=0` so duplicates are intentional.

## 7. Escalations

`notifications:check-escalations` runs every 15 minutes and re-fires unread
`URGENT`/`CRITICAL` notifications older than 30 minutes with the
`source_type` prefixed `escalation:` and an `escalated=true` flag.

## 8. Cleanup & reporting

- `notifications:cleanup` — daily 02:30, prunes read/old rows per `notifications.cleanup_days`.
- `reports:notifications-summary` — monthly first-of-month, prints per-module sent/read stats.
- `notifications:check-overdue` — every 15min, MAR slots that missed administration window.
- `clinical-tasks:check-due` — every 5min, upcoming clinical tasks within the lookahead.
- `claims:check-stale` — daily 06:00, claims unresolved for too long.

## 9. Topbar UI

Recent notifications show:
- Module chip (light)
- Priority badge (when ≠ `NORMAL`)
- Title bold + message
- Relative time

See [resources/views/layouts/app.blade.php](resources/views/layouts/app.blade.php#L166).

## 10. SMS & broadcast channels

Provider integrations are env-gated. Wire your SMS gateway as a custom
notification channel and add it to Laravel's notification channel manager
(see `DatabaseNotification::toSms()`). Broadcasting works the moment a
broadcaster is configured (`BROADCAST_CONNECTION=reverb|pusher`); the
notification ships through `toBroadcast()`.
