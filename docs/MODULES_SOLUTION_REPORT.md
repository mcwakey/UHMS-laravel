# Modules — Solution Report

_Last updated: November 2025_

This report describes the changes made to close the gaps in [MODULES_GAP_ANALYSIS.md](MODULES_GAP_ANALYSIS.md).

## 1. Emergency override

A new permission, **`modules.override_disabled`** (`CRITICAL`), allows holders to traverse a module that is otherwise gated off.

`App\Http\Middleware\EnsureModuleEnabled` was updated:

```php
if ($this->modules->disabled($slug)) {
    if ($user?->can('modules.override_disabled')) {
        return $next($request);
    }
    if ($request->expectsJson()) {
        return response()->json([...], 503);
    }
    abort(404, ...);
}
```

The override is granted to **Super Admin** and **Admin** only by `RoleSeeder`. Any other role must be explicitly extended through `/admin/roles`.

## 2. Front-end visibility

`HandleInertiaRequests::share()` now exposes `auth.modules` (an array of enabled-module slugs) on every authenticated Inertia response. Vue pages can branch on the list directly:

```js
const enabled = usePage().props.auth.modules;
if (!enabled.includes('pharmacy')) { ... }
```

The list is computed lazily — guests and partial reloads pay nothing.

## 3. Audit log

Toggles are persisted by `App\Services\ModuleService::enable()` / `disable()`, which call `$module->save()`. The Spatie `ActivityLog` model observer (registered in `LogsServiceProvider`) records every change to the `modules` table out of the box. No new code required — this confirms the prompt's requirement that module mutations are auditable.

## 4. Tests

`tests/Feature/Permissions/ModuleOverrideTest.php` covers:

| Scenario | Expected |
|---|---|
| Regular user requests a disabled module | 404 |
| User with `modules.override_disabled` requests the same module | passes through |
| Module is `is_core` and disable is attempted | request rejected, module stays enabled |

## Verification

```
D:\xampp3\php\php.exe artisan db:seed --class=RoleSeeder
D:\xampp3\php\php.exe artisan test --filter=ModuleOverrideTest
```
