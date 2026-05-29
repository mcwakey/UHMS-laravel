# UHMS Notifications — Gap Analysis

_Date: 2026-05-29_

## 1. Goal of this report

Audit the in-app notification system **as it existed before this round of
work** and identify exactly what was missing, broken, or fragile so the
solution report can show a one-to-one fix.

## 2. Current architecture (pre-fix)

| Layer | State |
|---|---|
| DB table | `notifications` (UUID, polymorphic, Laravel standard) — present and migrated. |
| User model | `Notifiable` trait active on `App\Models\User`. |
| Channels | `database` only. No mail/SMS/broadcast wired. |
| Notification classes | 6 narrow classes (`AdmissionNotification`, `GeneralNotification`, `LabRequestNotification`, `PaymentNotification`, `PrescriptionNotification`, `StockAlertNotification`). Each hard-codes its own payload shape. |
| Listeners | 7 listeners on 7 events (admit/discharge, lab create/result, prescription create, payment, stock low). Each calls `Notification::send($users, new XNotification(...))` directly. |
| Recipient targeting | `User::role([...])->where('status','active')->get()` repeated per listener. No central rule. |
| Routes | `admin.notifications.{index, recent, mark-read, mark-all-read}` exist under `module:notifications` middleware. |
| Controller | `App\Http\Controllers\Admin\NotificationController` — index/recent/markAsRead/markAllAsRead. No filtering, no delete, no module/priority awareness. |
| Header dropdown | `resources/views/layouts/partials/header.blade.php` — bell + badge + simplebar list, "View All" link. |
| Polling | `resources/views/layouts/app.blade.php` polls `/admin/notifications/recent` every 30 s. |
| List page | `resources/views/notifications/index.blade.php` — paginated list, single "Mark all" button, no filters, no module/priority badge, no delete. |
| Permissions | Single permission `notifications.view`, granted to ~20 roles. No `mark_read`, `delete`, `manage`. |
| Scheduler | None. `app/Console/Kernel.php` does not exist (Laravel 11). `routes/console.php` only ships `inspire`. No `notifications:*` commands. |
| Queue | `QUEUE_CONNECTION=database`, `jobs`/`failed_jobs` tables present. Notification classes use the `Queueable` **trait** but **do not** `implements ShouldQueue`, so they are sent synchronously. |
| Inertia shared props | `HandleInertiaRequests::share()` shares auth, flash, csrf, legacyChrome only — **no notification payload**. SPA layouts therefore cannot show a bell unless they poll the legacy endpoint themselves. |
| Sidebar/topbar badge | `AppServiceProvider` View::composer for `layouts.partials.sidebar` computes `unreadNotifications` count and passes it into `SidebarMenuBuilder::build($user, $route, $unreadNotifications)`. |
| Cleanup | None. Notifications table grows without bound. |

## 3. What was broken / missing

### 3.1 No central NotificationService
Every caller had to instantiate a domain-specific notification class.
Result: most modules (MAR, clinical tasks, theatre, claims, patient
merge, stock requisitions, billing reminders, …) had **zero** triggers
because writing a new `Notification` class per use-case was too heavy.

### 3.2 No deduplication
A scheduled job (none existed, but if one had) firing every minute
would create one notification per minute per recipient. Nothing checked
"did we already notify this user about this source in the last X
minutes?". This made any reminder loop unsafe to enable.

### 3.3 Inconsistent payload shape
Each Notification class hand-rolled its own keys. The dropdown JS reads
`message/url/icon/color/type`. The list page reads
`message/url/icon/color/type` too, but no class produced
`module`/`priority`/`title`/`action_url`. There was no contract.

### 3.4 No module-aware filtering
The list page returned everything ever sent to the user. With ~20 roles
all getting notifications.view, an admin's inbox would be unreadable
within days. No filter by read/unread, module, or priority.

### 3.5 No scheduler / no reminder commands
Nothing on disk to fire due/overdue reminders or to purge old rows.

### 3.6 No retention / cleanup
The `notifications` table is append-only by default. No `notifications:cleanup`
existed.

### 3.7 No Inertia surface
SPA pages can't pull notification data without their own request because
`HandleInertiaRequests` does not share it. The legacy Blade dropdown is
the only consumer.

### 3.8 Permission model is too coarse
Only `notifications.view`. There is no separation for `mark_read`,
`delete`, or admin-level `manage` (clear another user's inbox, fan-out a
broadcast).

### 3.9 Coupling to specific roles
Listeners like `NotifyStockManagers` target `['Pharmacist','Store Keeper']`
literally. If the role name is renamed or removed, notifications go
nowhere with no warning. A permission-based fan-out (`notifyPermission`)
would be more robust.

### 3.10 Quiet failure paths
`Notification::send()` throwing inside a listener would bubble up and
fail the originating user action (admit, discharge, payment). There was
no try/catch around delivery, no `Log::warning` on failure.

## 4. Root causes

1. **No abstraction layer.** Without a NotificationService, every team
   had to either write a new Notification class or skip notifications.
   Skipping won.
2. **Implicit "queue or sync" model.** The Queueable trait without
   `implements ShouldQueue` is sync — but that's a Laravel-internals
   subtlety, not a deliberate choice documented anywhere. A worker
   misconfiguration was always one tweak away from silently losing
   every notification.
3. **No structured payload.** The freeform `data` JSON was useful for
   v1, but the absence of a `module` and `priority` field made
   filtering, dedupe, and analytics impossible after the fact.
4. **No bootstrap of Laravel 11 scheduler.** The codebase had no
   `app/Console/Kernel.php` and no `withSchedule()` in `bootstrap/app.php`,
   so scheduled reminders had nowhere to live.

## 5. Impact on the operator experience

- Clinicians don't see emergency case escalations.
- Pharmacy doesn't see stock-out reminders past the first event-driven
  one.
- Theatre teams aren't told they're on a case until they open the
  calendar.
- The notifications bell looks useless because nothing ever appears in
  it for most users.
- Admins can't triage by module or priority.

See [NOTIFICATIONS_SOLUTION_REPORT.md](NOTIFICATIONS_SOLUTION_REPORT.md)
for what was changed to fix each item above, and
[NOTIFICATIONS_REMAINING_RECOMMENDATIONS.md](NOTIFICATIONS_REMAINING_RECOMMENDATIONS.md)
for what is intentionally left for a follow-up phase.
