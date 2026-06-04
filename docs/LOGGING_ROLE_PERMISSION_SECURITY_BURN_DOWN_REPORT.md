# Logging Burn-Down — Role / Permission Security

Closes the **one remaining CRITICAL `MISSING_LOG`** from the funnel-aware audit:
`Admin/RoleController`. Role and permission mutations are now fully traceable in
the global security log.

## Root cause

`Admin/RoleController` mutated roles directly (`Role::create`, `$role->update`,
`$role->delete`, `$role->syncPermissions`) and reached **no** service that calls
`ActivityLogService`. So the most security-sensitive actions in the system —
creating/renaming/deleting roles and changing what a role can do — left no audit
trail. `logs:audit` correctly flagged it as the lone CRITICAL real gap.

## Write paths inspected

| Path | Before | Action |
|---|---|---|
| `Admin/RoleController@store/update/destroy` | unlogged | **wired** |
| `Admin/RoleController@updatePermissions` (syncPermissions) | unlogged | **wired** |
| `Admin/UserPermissionController@update` (direct per-user perms) | logged via an ad-hoc `activity('user-permissions')` call on a non-standard channel, no module/severity/diff | **consolidated** into the funnel |
| `UserService::assignRole/syncRoles` (role→user assignment) | unlogged | **documented** as remaining (see below) |

## Architecture — a logging-only funnel

New `app/Services/RolePermissionAuditService.php`. It **never mutates** roles or
permissions (business logic untouched); callers keep their logic and hand it the
before/after state. It computes the diff, flags CRITICAL permissions, and writes
structured events via the central `ActivityLogService`. One funnel covers role
CRUD + role-permission sync + user-direct-permission sync, so there are no
scattered duplicate logging calls.

```
RoleController ─┐
                ├─► RolePermissionAuditService ─► ActivityLogService (ROLES / PERMISSIONS)
UserPermission ─┘
```

## Actions now logged

| Event | Module | Severity |
|---|---|---|
| `ROLE_CREATED` | ROLES | INFO |
| `ROLE_UPDATED` | ROLES | WARNING (old/new name + guard) |
| `ROLE_DELETED` | ROLES | WARNING |
| `ROLE_PERMISSIONS_UPDATED` | PERMISSIONS | WARNING, **escalated to CRITICAL** if a critical permission is added/removed |
| `USER_PERMISSIONS_UPDATED` | PERMISSIONS | WARNING / CRITICAL (same rule) |
| `CRITICAL_PERMISSION_ASSIGNED` | PERMISSIONS | CRITICAL |
| `CRITICAL_PERMISSION_REMOVED` | PERMISSIONS | CRITICAL |

The umbrella `*_PERMISSIONS_UPDATED` event always fires on a change; a **separate**
`CRITICAL_PERMISSION_ASSIGNED` / `_REMOVED` event additionally fires (listing the
critical permissions) so a SECURITY-grade event exists on its own for alerting.

## Critical permission detection

Reuses the existing `App\Support\PermissionMeta::risk()` (driven by
`config/permissions.php` risk rules/overrides) — **no hardcoded list**. A
permission is critical when `PermissionMeta::risk($name) === 'CRITICAL'`, e.g.
`*.payment.reverse`, `*.delete`, `*.override*`, `roles.*`, `permissions.*`,
`modules.*`, `patients.merge.execute`, `medication_administration.correct`, etc.
This is the same source the controller already uses to gate critical changes
behind `permissions.assign_critical`.

## Old / new values captured

- **Role update:** `old = {name, guard_name}` → `new = {name, guard_name}`.
- **Permission sync:** `old = {permissions: [...]}` → `new = {permissions: [...]}`,
  plus `metadata.added_permissions`, `removed_permissions`,
  `critical_permissions_added`, `critical_permissions_removed`, `permission_count`.
- **User direct perms:** same, plus the mandatory `reason` and `target_user_id`.

Sensitive masking still applies via `ActivityLogService::sanitise()` (permission
*names* are not secrets, so nothing is masked here — but passwords/tokens never
reach these payloads).

## Not patient activity

Subjects are `Role` / `User` — neither has `patient_id`/`visit_id`, so
`ActivityContextResolver` attaches none. A test asserts **zero** ROLES/PERMISSIONS
logs carry patient/visit context; they surface only in the global/security log.

## Tests

`tests/Feature/RolePermissionSecurityLogTest.php` — **11 passing** (51 assertions),
end-to-end through the real routes (so authorization is exercised):

1. role create → `ROLE_CREATED`
2. role update → `ROLE_UPDATED` with old/new
3. role delete → `ROLE_DELETED`
4. permission sync → added/removed captured
5. critical add → `CRITICAL_PERMISSION_ASSIGNED` (severity CRITICAL) + umbrella escalated
6. critical remove → `CRITICAL_PERMISSION_REMOVED`
7. user direct perms → `USER_PERMISSIONS_UPDATED` (with reason + target_user_id)
8. security logs carry **no** patient/visit context
9. unauthorized user (`can:roles.manage` missing) → 403, nothing logged, role not created
10. critical change without `permissions.assign_critical` → 403, no critical log, no mutation
11. `logs:audit` classifies `RoleController` as `SERVICE_FUNNEL_COVERED`, summary `MISSING_LOG = 0`

## `logs:audit` before / after

| | Before | After |
|---|--:|--:|
| MISSING_LOG (real gaps) | **1** (RoleController, CRITICAL) | **0** |
| SERVICE_FUNNEL_COVERED | 42 | 43 |
| NEEDS_REVIEW | 7 | 7 |
| KNOWN_BACKLOG | 22 | 22 |
| INTENTIONALLY_SKIPPED | 1 | 1 |

Baseline regenerated: 67 accepted, **6** high-risk gaps still refused/kept loud
(FinancialEntry · CashierShift · InsuranceVerification · Leave · Payroll ·
PurchaseReturn — RoleController is no longer among them).

## Is the Stage-1 blocking gate now safe?

**Yes.** `php artisan logs:audit --fail --only-real-gaps --min-severity=CRITICAL`
now **exits 0** — there are no CRITICAL real gaps left. The gate can be enabled in
CI as a regression guard (blocks only a brand-new un-funnelled CRITICAL action)
**when you choose to** — this pass does not flip CI to blocking.

## Remaining needs-review items (unchanged, out of scope here)

The 7 `NEEDS_REVIEW` controllers (financial/HR/insurance/purchase-return/theatre-
room) still delegate to services not verified to log — surfaced, not hidden.
Plus, newly documented:

- **`UserService::assignRole` / `syncRoles`** — assigning/replacing a user's
  *role* (distinct from role definition and direct permissions) is not yet logged;
  `UserObserver` only catches column changes, not the role pivot. Wire a
  `roleAssignedToUser` event into `RolePermissionAuditService` next (HIGH).

## Files changed

- `app/Services/RolePermissionAuditService.php` — **new** logging funnel.
- `app/Http/Controllers/Admin/RoleController.php` — inject funnel; log
  create/update/delete/permission-sync.
- `app/Http/Controllers/Admin/UserPermissionController.php` — replace ad-hoc
  `activity()` with the funnel (preserves `reason`).
- `config/logging_audit.php` — register `RolePermissionAuditService`.
- `storage/app/logs-audit-baseline.json` — regenerated (RoleController now covered).
- `tests/Feature/RolePermissionSecurityLogTest.php` — **new** (11 tests).
