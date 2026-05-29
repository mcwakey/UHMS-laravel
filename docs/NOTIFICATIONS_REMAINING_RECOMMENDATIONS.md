# UHMS Notifications — Remaining Recommendations

_Date: 2026-05-29_

This is the deliberately-deferred backlog. Everything below is
**non-breaking** — the system shipped in
[NOTIFICATIONS_SOLUTION_REPORT.md](NOTIFICATIONS_SOLUTION_REPORT.md)
works end-to-end without these.

## 1. Migrate the seven legacy listeners onto NotificationService

The pre-existing event listeners still call
`Notification::send($users, new XNotification(...))` directly.

| Listener | New call |
|---|---|
| `NotifyLabTechnicians` | `notifyRole('Lab Technician', [...module=INVESTIGATION])` |
| `NotifyDoctorLabResults` | `notifyUser($doctor, [...module=INVESTIGATION])` |
| `NotifyPharmacists` | `notifyRole('Pharmacist', [...module=PHARMACY])` |
| `NotifyAccountants` | `notifyRole('Accountant', [...module=BILLING])` |
| `NotifyAccountantsDischarge` | `notifyRole('Accountant', [...module=BILLING])` |
| `NotifyWardStaffAdmission` | `notifyDepartment($wardId, [...module=ADMISSION])` |
| `NotifyStockManagers` | `notifyPermission('inventory.manage', [...module=STOCK])` |

Benefit: uniform `module`/`priority` so the filter bar works
immediately, dedupe applies automatically, and role renames no longer
silently break stock notifications.

## 2. Reminder commands (overdue MAR, due clinical tasks, claims)

Add Artisan commands and schedule them every 5–15 minutes:

- `medications:check-overdue` → for each due MAR slot past its window,
  notify the assigned nurse + ward, `priority=URGENT`,
  `source=mar_slot:{id}`. Dedupe window 30 min.
- `clinical-tasks:check-due` → 15 min before due, notify assignee.
- `claims:check-stale` → claim sitting in `submitted` > 7 days notify
  claims manager.
- `theatre:check-late-start` → case still in `scheduled` after
  `scheduled_start + 15min` notify on-call coordinator.

Each lives in `app/Console/Commands/`, registered in `bootstrap/app.php`
under `withSchedule()`. All should call `NotificationService` so
dedupe is automatic.

## 3. Extra channels

`DatabaseNotification::via()` returns `['database']` only. To add SMS
or email, introduce a per-user `NotificationPreference` model
(`user_id`, `module`, `channels[]`, `quiet_hours_start`,
`quiet_hours_end`) and have `NotificationService::notifyUser` resolve
channels via the preference (defaulting to database). Build channel
drivers (`MailChannel` already exists in Laravel; `TwilioChannel` /
`AfricasTalkingChannel` are SDKs away).

## 4. Real-time push (broadcast)

Polling every 30 s is fine for the current operator volume. When user
count or expected latency forces a change, add Laravel Echo + Reverb
(or Pusher) and have `DatabaseNotification` also `via('broadcast')`.
The header dropdown JS can subscribe to a private channel
`App.Models.User.{id}` and merge events into the existing render path —
the polling loop becomes the fallback.

## 5. Per-user quiet hours and digest

Add a "digest" channel that buffers `LOW/NORMAL` notifications during
quiet hours and emits one rollup email at the next active window.
`URGENT/CRITICAL` always bypass the digest.

## 6. Permission-based broadcast UI

`notifications.manage` was added to the permission list but no UI uses
it yet. A small admin page under `admin/notifications/broadcast`
allowing a Super Admin / Communication Officer to fan out an
announcement to a role / department / permission would be a 1-day add
on top of `notifyRole`/`notifyDepartment`/`notifyPermission`.

## 7. Analytics

`notifications.data` now carries `module`, `priority`, `source_type`,
`source_id`. A monthly report (`reports:notifications-summary`) that
buckets sent vs read vs deleted per module would expose dead-letter
modules (everyone deletes them unread → tune them down or off).

## 8. Escalation rules

For `URGENT/CRITICAL` notifications not acknowledged within N minutes,
escalate to the recipient's manager or on-call. Implemented as a
companion `notifications:check-escalations` scheduled command reading
unread `URGENT+` rows older than the SLA and re-firing through
`NotificationService` with `priority=CRITICAL` and a new
`source_type=escalation:{original_id}` (so dedupe still works).

## 9. Front-end: surface module/priority in the topbar dropdown

The dropdown JS in `resources/views/layouts/app.blade.php` currently
renders `message + time`. Module name and priority chip should be
shown so urgent items are visible at a glance. This is a ~20-line JS
edit; no backend change required.

## 10. Notification preferences page

A `/profile/notifications` page allowing each user to toggle channels
per module ("MAR overdue → SMS + database, Billing → database only").
Stores rows in the `notification_preferences` table from §3.

## 11. Drop unused early-stage notification classes

Once §1 lands, the six domain-specific notification classes become
thin wrappers. Either delete them and route everything through
`DatabaseNotification`, or convert them to factories
(`AdmissionNotification::for($admission, 'admitted'): array $payload`)
that return canonical payloads.
