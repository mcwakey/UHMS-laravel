# Roles & Permissions — Gap Analysis

_Last updated: November 2025_

## Scope

This report compares the requirements articulated in `prompt.md` (Roles, Permissions and Modules system) against the **current implementation** of the UHMS Laravel application. It accompanies:

- [docs/ROLES_PERMISSIONS_SOLUTION_REPORT.md](ROLES_PERMISSIONS_SOLUTION_REPORT.md)
- [docs/MODULES_GAP_ANALYSIS.md](MODULES_GAP_ANALYSIS.md)
- [docs/MODULES_SOLUTION_REPORT.md](MODULES_SOLUTION_REPORT.md)
- [docs/ROLES_PERMISSIONS_REMAINING_RECOMMENDATIONS.md](ROLES_PERMISSIONS_REMAINING_RECOMMENDATIONS.md)

## Baseline (what already existed)

| Area | Status | Notes |
|---|---|---|
| Authorisation library | ✅ | `spatie/laravel-permission` v6.25 |
| Permissions catalogue | ✅ | 261 dot-notation permissions seeded via `RoleSeeder` (idempotent — `Permission::firstOrCreate`) |
| Roles | ✅ | 28 roles seeded (Super Admin, Admin, Doctor, Nurse, Pharmacist, …) |
| Modules table | ✅ | 21 modules with `is_core`, `is_enabled`, `depends_on`, `description`, `icon`, `sort_order` |
| Module middleware | ✅ | `module:<slug>` alias mapped to `EnsureModuleEnabled` |
| Role middleware | ✅ | `role:<list>` alias mapped to `EnsureUserHasRole` |
| Permission middleware | ✅ | Laravel built-in `can:<perm>` used throughout `routes/web.php` |
| Sidebar gating | ✅ | `App\Services\SidebarMenuBuilder` filters items by `permission` and `module` keys |
| Module admin UI | ✅ | `/admin/modules` with toggle + dependency check + cache flush |
| Roles admin UI | ✅ | `/admin/roles` (index, create, edit, destroy) |

## Gaps identified

| # | Gap | Severity | Resolution |
|---|---|---|---|
| 1 | No metadata layer (description, owning module, risk level) for permissions | High — admins editing roles see opaque dot-names | New `config/permissions.php` + `App\Support\PermissionMeta` |
| 2 | No `auth.permissions` / `auth.roles` / `auth.modules` shared with Inertia frontend; Vue pages can't run a `can()` check without re-fetching | High — front-end fell back to hiding by route only | Inertia `share()` extended (lazy, cached per request) |
| 3 | No automated audit linking DB permissions ↔ routes ↔ Spatie cache | Medium — drift between code and seed undetectable | New `php artisan permissions:audit` command |
| 4 | `modules.override_disabled` permission missing — Super Admins lock themselves out of disabled modules | High during incident response | New permission + bypass in `EnsureModuleEnabled` |
| 5 | Granular role-management permissions missing — only `roles.manage` was seeded | Medium — RBAC could not be split between an "auditor" (view roles) and a "controller" (mutate roles) | New `roles.{view,create,update,delete}` + `permissions.{view,assign}` |
| 6 | Granular user-account permissions missing — no `users.disable` or `users.reset_password` | Medium | New permissions seeded; UI gating updated |
| 7 | Possible naming duplicates (e.g. `product.link_departments` vs `product.link_department`, `stock.location.manage` vs `stock_location.manage`) | Low — flagged for cleanup | `permissions:audit` reports them |
| 8 | A few admin index routes (e.g. `admin.visits.index`, `admin.appointments.index`) gate only on `auth`, not on `can:` | Low — sidebar already hides them, but defence-in-depth lacking | Tracked in [REMAINING_RECOMMENDATIONS](ROLES_PERMISSIONS_REMAINING_RECOMMENDATIONS.md) |

## What this gap analysis does **not** find

- The system does **not** lack permissions. 261 permissions covers every clinical, financial, inventory, HR, and administrative module currently shipped.
- The system does **not** lack module gating. The `module:<slug>` middleware is applied wherever it should be.
- Roles are not over-granted: every clinical role is scoped to its module (Pharmacist → pharmacy/lab read-only/MAR; Nurse → patient + visit + vitals + prescriptions; Cashier → billing only).

## Method

1. Inventoried `database/seeders/RoleSeeder.php` (permission list, role-to-permission matrix).
2. Inventoried `database/seeders/ModuleSeeder.php`.
3. Crawled `routes/web.php` for `can:`, `role:`, `module:` middleware coverage.
4. Crawled `app/Http/Controllers` for `$this->authorize(...)` and policy classes.
5. Compared the result against the requirements list in `prompt.md`.

The audit command (`permissions:audit --json`) reproduces steps 3 and 4 on demand and writes a JSON report to `storage/reports/permissions_audit.json`.
