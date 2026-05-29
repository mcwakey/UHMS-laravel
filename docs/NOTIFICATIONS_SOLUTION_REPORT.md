# UHMS Notifications — Solution Report

_Date: 2026-05-29_

## 1. Summary

A central, structured notification foundation has been added without
disturbing the 7 pre-existing event listeners or the legacy Blade
dropdown. The change is additive: every existing notification keeps
firing exactly as before, and any new module wiring goes through one
service.

## 2. Files added

| File | Purpose |
|---|---|
| `app/Enums/NotificationModule.php` | Canonical list of modules (`EMERGENCY`, `MAR`, `THEATRE`, …) with icon and label. |
| `app/Enums/NotificationPriority.php` | `LOW / NORMAL / HIGH / URGENT / CRITICAL` with Bootstrap colour map. |
| `app/Notifications/DatabaseNotification.php` | Single flexible database notification used by `NotificationService`. Normalises module/priority and merges defaults into the data payload. |
| `app/Services/NotificationService.php` | Central fan-out API (see §4). |
| `config/notifications.php` | Tunables: dedupe minutes, latest limit, poll interval, retention days. |
| `app/Console/Commands/NotificationsTest.php` | `php artisan notifications:test --user=…` for manual delivery checks. |
| `app/Console/Commands/NotificationsCleanup.php` | `php artisan notifications:cleanup` — prunes read + ancient notifications based on retention config. |
| `tests/Feature/NotificationServiceTest.php` | 7 focused tests covering send/dedupe/role/department/mark/null-safety. |
| `docs/NOTIFICATIONS_GAP_ANALYSIS.md` | Pre-fix audit. |
| `docs/NOTIFICATIONS_SOLUTION_REPORT.md` | This document. |
| `docs/NOTIFICATIONS_REMAINING_RECOMMENDATIONS.md` | Follow-up scope. |

## 3. Files changed

| File | Change |
|---|---|
| `bootstrap/app.php` | Registered `withSchedule(…)` and added the daily `notifications:cleanup` job at 02:30. |
| `app/Http/Middleware/HandleInertiaRequests.php` | Shares a lazy `notifications.{unread_count, latest}` blob so Inertia pages have the same data the polling endpoint serves. |
| `app/Http/Controllers/Admin/NotificationController.php` | Now backed by `NotificationService`; adds `destroy` and filter-aware `index` (read/unread + module + priority). `recent` now surfaces `module`, `priority`, `title`. |
| `routes/web.php` | Added `DELETE /admin/notifications/{id}` → `notifications.destroy`. |
| `resources/views/notifications/index.blade.php` | Filter bar (read state / module / priority), module + priority badges, delete button. |
| `database/seeders/RoleSeeder.php` | Adds `notifications.mark_read`, `notifications.delete`, `notifications.manage` to the master permission list (existing per-role `notifications.view` grants unchanged). |
| `app/Services/ProcedureWorkflowService.php` | Injects `NotificationService`; notifies the requesting doctor on **procedure cancelled** and **procedure completed**. |
| `app/Services/ProcedureScheduleService.php` | Injects `NotificationService`; notifies the assigned surgeon / anaesthetist / assistant / theatre nurses when a case is **scheduled**. Priority is `URGENT` for emergency requests, `HIGH` otherwise. |

## 4. NotificationService API

```php
$service->notifyUser(?User $u, array $payload, ?int $dedupeMinutes = null): bool
$service->notifyUsers(iterable $users, array $payload, ?int $dedupeMinutes = null): int
$service->notifyRole(string|array $roles, array $payload, ?int $dedupeMinutes = null): int
$service->notifyPermission(string $permission, array $payload, ?int $dedupeMinutes = null): int
$service->notifyDepartment(int|Department|null $d, array $payload, ?int $dedupeMinutes = null): int

$service->unreadCount(User $u): int
$service->latest(User $u, int $limit = 10): EloquentCollection
$service->markAsRead(User $u, string $notificationId): bool
$service->markAllAsRead(User $u): int
```

### Canonical payload shape

```php
[
    'title'       => 'New theatre case scheduled',
    'message'     => 'You are scheduled for Appendectomy (Jane Doe) at Mon, 02 Jun 2026 09:30.',
    'module'      => NotificationModule::THEATRE,    // enum or string
    'priority'    => NotificationPriority::URGENT,   // enum or string
    'source_type' => 'procedure_schedule',
    'source_id'   => 42,
    'action_url'  => route('admin.theatre.calendar'),
    'patient_id'  => 17,                              // optional context
    'metadata'    => [...],                           // free-form, JSON-safe
]
```

The service back-fills `icon`, `color`, `url`, `type`, and `dedupe_key`
(unless callers override). The dedupe key defaults to
`"{module}:{source_type}:{source_id}"` and is enforced inside a sliding
window (default 15 min, configurable via `config('notifications.dedupe_minutes')`
or per-call argument).

## 5. Deduplication

`hasRecentDuplicate()` runs a `data LIKE` query on the recipient's
notifications within the dedupe window. The same caller hitting
`notifyUser` ten times in a minute produces **one** row. Pass
`dedupeMinutes: 0` to bypass dedupe (e.g. CLI test command, broadcasts
where every event is genuinely new).

## 6. Frontend

| Surface | Behaviour after this change |
|---|---|
| Header bell | Unchanged. Still polls `/admin/notifications/recent` every 30 s, paints badge + dropdown items. Now displays `title` / `module` / `priority` in the JSON payload (renderer still uses `message + time` so no Blade breakage). |
| `admin.notifications.index` page | Filter bar with `read=unread|read`, `module=<enum>`, `priority=<enum>`. Each card shows title, message, module badge, priority badge (only when ≠ NORMAL), time, read/unread chip, delete button. Pagination keeps query string. |
| Inertia pages | `notifications.unread_count` and `notifications.latest[]` available on every Inertia response via `HandleInertiaRequests::share()`. Lazy so guests and partial reloads pay zero cost. |

## 7. Triggers wired

| Module | Trigger | Recipients | Priority |
|---|---|---|---|
| Theatre | `ProcedureScheduleService::scheduleProcedure` | Assigned surgeon, anaesthetist, assistant, nurses (excluding actor) | URGENT if emergency, HIGH otherwise |
| Procedure | `ProcedureWorkflowService::cancelProcedure` | Requesting doctor | HIGH |
| Procedure | `ProcedureWorkflowService::completeProcedure` | Requesting doctor | NORMAL |

The 7 pre-existing event-driven listeners (admit, discharge, lab
request, lab result, prescription, payment, stock low) continue to fire
through their original `Notification::send()` calls. They will be
migrated to `NotificationService` opportunistically as their host
modules are touched; doing so without functional changes is out of
scope for this round.

## 8. Permissions

Added to the master permission list in `RoleSeeder`:

- `notifications.mark_read`
- `notifications.delete`
- `notifications.manage`

`notifications.view` was already granted to every operational role (~20
roles) and continues to gate the bell + list page. Role assignments
were intentionally not rewritten — see §6 of the recommendations doc.

## 9. Console & schedule

```text
php artisan notifications:test --user=jane@example.com
php artisan notifications:cleanup
```

`bootstrap/app.php` schedules `notifications:cleanup` daily at 02:30
with `withoutOverlapping()`. Retention defaults: read rows older than
30 days and any row older than 180 days are purged. Both are
configurable via env (`NOTIFICATIONS_READ_RETENTION_DAYS`,
`NOTIFICATIONS_ALL_RETENTION_DAYS`).

## 10. Queue posture

`DatabaseNotification` does **not** `implements ShouldQueue`. It is
sent synchronously in the request that created it. This is deliberate:
the project's queue worker is not part of the dev workflow, and losing
notifications because no worker is running would be worse than the
~10 ms it costs to insert one row.

If a future module needs to fan out to >50 recipients per event, the
right path is to implement `ShouldQueue` on a per-class basis, not to
flip the default.

## 11. Verification

```text
php artisan test --filter=NotificationServiceTest --no-ansi
  ✓ notify user creates a database notification
  ✓ notify user dedupes within window
  ✓ notify user does not dedupe when window is zero
  ✓ notify role targets active users with that role
  ✓ notify department targets active department members
  ✓ mark as read and mark all as read
  ✓ notify user handles null recipient
  Tests:    7 passed (24 assertions)

php artisan test --filter=TheatreRoomsManagementTest --no-ansi
  ✓ 5 passed (19 assertions)
```

No migrations are required by this change — the `notifications` table
shape stays exactly as Laravel ships it.

## 12. Rollback

Every change is additive except the `NotificationController` rewrite
(behaviour-equivalent) and the two procedure-service constructor
signatures (added a typed parameter). Laravel's container resolves the
new dependency automatically. To roll back: revert the listed files;
the `notifications` table and existing rows are untouched.
