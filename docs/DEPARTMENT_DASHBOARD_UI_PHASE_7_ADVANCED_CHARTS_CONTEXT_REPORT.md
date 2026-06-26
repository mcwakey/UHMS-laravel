# Department Dashboard UI Phase 7 - Advanced Charts, Context Switching & Comparison

## Summary

Phase 7 adds an additive multi-department foundation, session-based current department switching, chart-ready department dashboard datasets, permission-safe comparison reporting, and CSV export.

The Phase 6 rule is preserved:

- Department type controls dashboard layout, theme, menu profile, and workflow family.
- Department ID controls actual data scope.

## Multi-Department Support Design

Added `department_user` as a safe additive pivot. `users.department_id` remains the backward-compatible default department.

Users can now have:

- one default department through `users.department_id`
- optional extra departments through `department_user`
- one primary pivot assignment when configured

## Department User Pivot Behavior

The pivot includes:

- `user_id`
- `department_id`
- `is_primary`
- `role_context`
- `starts_at`
- `ends_at`
- timestamps

Current assignment resolution ignores expired or future-dated pivot rows.

## Context Switcher Behavior

Added `DepartmentContextSwitcherService`.

It:

- lists departments available to a user
- falls back to `users.department_id` when no pivot rows exist
- stores the selected department in `current_department_id`
- clears invalid session department IDs
- allows Admin/Super Admin/global-preview users to access active departments for preview
- limits ordinary users to assigned departments only

Switch mutations are protected by `departments.context.switch`.

The seeder grants `departments.context.switch` to all roles because the service
still limits switching to assigned departments only. This permission does not
grant global preview or comparison access.

## Context Resolver Changes

`DepartmentContextResolver` now resolves in this order:

1. valid session `current_department_id`
2. primary `department_user` pivot department
3. `users.department_id`
4. global/admin fallback

The context now exposes:

- available departments
- current department
- current department ID/type
- switched-context state
- switch/global-preview capability flags

## Menu Behavior After Switching

`SidebarMenuBuilder` now prioritises menu sections from the current switched department context instead of only `users.department_id`.

Permission and module filtering still happen first. Department type only reorders allowed menu items.

## Chart Service Design

Added `DepartmentDashboardChartService`.

Charts return Blade/JSON-ready arrays:

- `labels`
- `datasets`
- `format`
- `empty_state`
- `restricted`

Restricted datasets, especially revenue and stock, are hidden before querying.

## Chart Datasets Added

Dashboard chart datasets now include:

- activity trend
- revenue trend
- request status breakdown
- service usage trend
- stock usage trend
- queue status breakdown
- department workload by day

All 19 department types have chart fallbacks.

## Department Comparison View

Added:

- `GET admin/reports/department-comparison`
- `GET admin/reports/department-comparison/export`

The comparison screen supports department/type/date filters and shows aggregate-only metrics:

- assigned users
- services
- activity
- pending work
- completed work
- revenue when permitted
- stock alerts when permitted

## Department Type Comparison Behavior

The comparison service groups rows into department-type rollups without exposing patient-level clinical details.

## Export Behavior

CSV export respects:

- selected filters
- allowed department scope
- financial permissions
- stock permissions

Restricted revenue and stock columns are not included when the user lacks permission.

## Permission/Module Safety

Added permissions:

- `departments.context.switch`
- `reports.department_comparison.view`
- `reports.department_comparison.export`
- `dashboards.department.global_preview`

`permissions:audit --strict` passes for missing route permissions and unguarded admin mutations. Existing duplicate-name warnings remain pre-existing audit warnings.

## Localisation Changes

Updated EN/FR keys in:

- `dashboards.php`
- `departments.php`
- `reports.php`
- `menu.php`

Localisation audit remains:

- Active runtime candidates: 0
- EN/FR parity: OK

## Tests Added

Added `tests/Feature/Departments/DepartmentDashboardAdvancedUiPhase7Test.php`.

Coverage includes:

- one-department users do not see switching capability
- assigned multi-department users can switch
- unassigned department switches are rejected
- invalid session department is cleared
- dashboard data scope changes after switch
- revenue charts are restricted without permission
- comparison route is permission protected
- comparison service lists only allowed departments
- export hides restricted columns
- all department types have chart fallback

## Focused Tests Run

Passed:

```bash
php artisan test tests\Feature\Departments\DepartmentDashboardAdvancedUiPhase7Test.php
php artisan test tests\Feature\Departments tests\Feature\DepartmentDashboardTest.php
```

Result: 22 tests passed, 147 assertions.

## Minimal Verification Commands Run

Passed:

```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
php artisan route:list --path=admin/reports/department-comparison
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan permissions:audit --strict
git diff --check
```

`git diff --check` only reported the existing generated-report CRLF warning on `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.

## Known Limitations

- advanced predictive analytics are deferred
- custom user dashboard widgets are deferred
- full wide-suite regression is still deferred unless explicitly run
- department backfill apply was not run
- chart rendering uses Bootstrap/Blade fallback visuals; adding full Chart.js rendering can be layered later if desired

## Next Recommended Phase

Phase 8 should harden operational polish around dashboard drilldowns, user-facing assignment management, and richer chart rendering without changing the Phase 7 scoping model.
