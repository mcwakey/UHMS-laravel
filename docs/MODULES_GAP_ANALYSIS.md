# Modules — Gap Analysis

_Last updated: November 2025_

## Baseline (what already existed)

- `modules` table with: `slug`, `name`, `description`, `icon`, `is_core` (cannot be disabled), `is_enabled`, `depends_on` (foreign-key on `slug`), `sort_order`.
- `App\Models\Module` Eloquent model with `dependents()` / `dependsOn()` relations.
- `App\Services\ModuleService` provides cache-backed `enabled()` / `disabled()` / `enable()` / `disable()` / `flush()`.
- Middleware alias `module:<slug>` — `App\Http\Middleware\EnsureModuleEnabled` returns **503** (JSON) or **404** (HTML) when a module is disabled.
- 21 modules seeded by `ModuleSeeder` — 10 marked `is_core`, 11 optional.
- Admin UI at `/admin/modules`:
  - Lists modules ordered by core-first then `sort_order`.
  - Toggle endpoint at `POST /admin/modules/{module}/toggle`.
  - Refuses to disable a core module.
  - Refuses to disable a module with enabled dependents (lists them).
  - Refuses to enable a module whose `depends_on` parent is disabled (asks user to enable parent first).
  - Cache flush button on the page.

## Gaps identified

| # | Gap | Severity | Resolution |
|---|---|---|---|
| 1 | No emergency override — Super Admins locked out of a disabled module they were trying to re-enable | High during incident response | New `modules.override_disabled` permission; `EnsureModuleEnabled` bypasses for users that hold it. |
| 2 | Module dependency / enable state not visible to the front-end SPA — Vue pages had to call an API to know if a module was on | Medium | `auth.modules` (slugs of enabled modules) shared via Inertia. |
| 3 | No telemetry on which modules are being toggled or how often | Low | Module toggles are already audited via Spatie ActivityLog (see `LogsServiceProvider`); no extra work needed. Documented here for completeness. |
| 4 | `description` column populated but never surfaced in the admin index card | Cosmetic | Tracked in [REMAINING_RECOMMENDATIONS](ROLES_PERMISSIONS_REMAINING_RECOMMENDATIONS.md). |

## Out of scope

The module list itself is **not** under-populated. The 21 modules cover every functional area in the system:

`auth, users, patients, visits, triage, queue, consultations, departments, services, billing, claims, pharmacy, lab, wards, inpatient, mar, emergency, appointments, store, hr, settings`.

New modules introduced in future feature work follow the `php artisan make:migration` + add row in `ModuleSeeder` pattern that is already established.
