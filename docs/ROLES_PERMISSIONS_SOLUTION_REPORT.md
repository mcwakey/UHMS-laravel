# Roles & Permissions — Solution Report

_Last updated: November 2025_

This report describes **what was implemented** to close the gaps listed in [ROLES_PERMISSIONS_GAP_ANALYSIS.md](ROLES_PERMISSIONS_GAP_ANALYSIS.md).

## Summary of changes

| Change | File(s) |
|---|---|
| Permission metadata layer (description / module / risk) | [config/permissions.php](../config/permissions.php), [app/Support/PermissionMeta.php](../app/Support/PermissionMeta.php) |
| Audit command — `php artisan permissions:audit` | [app/Console/Commands/PermissionsAuditCommand.php](../app/Console/Commands/PermissionsAuditCommand.php) |
| Inertia share — `auth.permissions / roles / modules` | [app/Http/Middleware/HandleInertiaRequests.php](../app/Http/Middleware/HandleInertiaRequests.php) |
| Module override permission for emergencies | [app/Http/Middleware/EnsureModuleEnabled.php](../app/Http/Middleware/EnsureModuleEnabled.php) |
| New permissions seeded idempotently | [database/seeders/RoleSeeder.php](../database/seeders/RoleSeeder.php) |
| Tests | [tests/Feature/Permissions/](../tests/Feature/Permissions) |

## 1. Permission metadata layer

Adding metadata in the database would mean a Spatie schema fork; instead it lives in `config/permissions.php`. Three rule sets:

- **`risk_rules`** — regex → `LOW` / `NORMAL` / `HIGH` / `CRITICAL`. Suffix-driven so the 261 existing names need no change.
- **`risk_overrides`** — explicit override for permissions whose suffix is misleading (`patients.merge.execute` is irreversible even though it ends in a verb that's normally `HIGH`).
- **`module_overrides`** — when the dot-prefix doesn't match the owning module slug.
- **`descriptions`** — human-readable copy. Falls back to a templated description (`"Create pharmacy dispense"`) for unspecified entries.

`App\Support\PermissionMeta::for($name)` returns `['name','module','description','risk']` and is the single read path used by the audit command, the admin UI, and the front-end.

## 2. Audit command

```
php artisan permissions:audit          # console table
php artisan permissions:audit --json   # also writes storage/reports/permissions_audit.json
```

Cross-checks performed:

| Check | What it catches |
|---|---|
| Permissions referenced by `can:` middleware but not in DB | Code drift — a developer wrote `can:foo.bar` but never seeded it |
| DB permissions never used in any route | Dead permissions left over from removed features |
| Admin mutation routes (`POST/PUT/PATCH/DELETE /admin/*`) without `can:` or `role:` | Defence-in-depth violations |
| Permission name pairs with Levenshtein ≤ 2 | Probable typos / duplicates |

The command is deterministic and exits `0` even when warnings are emitted (CI can choose to `--strict` later).

## 3. Inertia auth share

`HandleInertiaRequests::share()` now exposes lazy props:

```php
'auth' => [
    'user'        => fn() => $request->user()?->only(['id','name','email']),
    'permissions' => fn() => $request->user()?->getAllPermissions()->pluck('name')->all() ?? [],
    'roles'       => fn() => $request->user()?->getRoleNames()->all() ?? [],
    'modules'     => fn() => Module::where('is_enabled', true)->pluck('slug')->all(),
]
```

Lazy closures are only evaluated if a Vue page reads the prop — guests pay nothing.
Spatie's `getAllPermissions()` is request-cached, so the cost is one query per authenticated full-page load.

A Vue composable consumes this:

```js
// resources/js/Composables/usePermissions.js (consumer-side)
import { usePage } from '@inertiajs/vue3';
export function can(name) {
  return usePage().props.auth?.permissions?.includes(name) ?? false;
}
```

## 4. Module override permission

`modules.override_disabled` was added to the catalogue and granted to Super Admin / Admin only.
`EnsureModuleEnabled` now checks `$user->can('modules.override_disabled')` before returning 404 / 503, so administrators can still reach a disabled module to re-enable it during an incident.

## 5. Seeded permissions (delta)

Added to the canonical list inside `RoleSeeder` (idempotent — `Permission::firstOrCreate`):

| Permission | Risk | Description |
|---|---|---|
| `modules.override_disabled` | CRITICAL | Bypass module-disabled gate. |
| `permissions.view` | LOW | View permission catalogue. |
| `permissions.assign` | CRITICAL | Assign individual permissions to a user. |
| `roles.view` | LOW | View roles. |
| `roles.create` | NORMAL | Create a role. |
| `roles.update` | NORMAL | Edit a role's permission set. |
| `roles.delete` | CRITICAL | Delete a role. |
| `users.disable` | CRITICAL | Disable (deactivate) a user. |
| `users.reset_password` | CRITICAL | Reset another user's password. |

All nine are auto-granted to `Super Admin` and `Admin` (they `syncPermissions(Permission::all())`); other roles must be explicitly extended via the admin UI.

## 6. Tests

Added under `tests/Feature/Permissions/`:

- `PermissionsAuditCommandTest` — runs `permissions:audit --json`, asserts the JSON file exists and contains the expected keys.
- `InertiaAuthShareTest` — authenticated request to a routine page; asserts `auth.permissions` / `auth.roles` / `auth.modules` are present.
- `ModuleOverrideTest` — disabled-module request returns 404 for a regular user, 200 for a user with `modules.override_disabled`.

Run:

```
D:\xampp3\php\php.exe artisan test --filter="PermissionsAuditCommandTest|InertiaAuthShareTest|ModuleOverrideTest"
```

## Verification

After running the migrations and seeders:

```
D:\xampp3\php\php.exe artisan migrate --seed
D:\xampp3\php\php.exe artisan permissions:audit --json
```

The JSON report at `storage/reports/permissions_audit.json` is the canonical source for the admin permissions dashboard.
