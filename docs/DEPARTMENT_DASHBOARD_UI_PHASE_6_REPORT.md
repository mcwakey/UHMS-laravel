# Department Dashboard UI Phase 6 Report

## Summary

Phase 6 adds the modern department-oriented dashboard path on top of the existing department type expansion work. The canonical route remains `admin.my-dashboard`; legacy role dashboards remain available separately.

## Department Context Resolver

Added `App\Services\Department\DepartmentContextResolver` and `DepartmentDashboardContext`.

The context resolves:

- current user
- assigned department and `department_id`
- department type and translated label
- dashboard key
- menu profile key
- theme
- global/admin context state
- admin preview state

Rules implemented:

- users with an assigned department receive department-scoped context
- users without a department fall back safely to role/global dashboard context
- Admin and Super Admin users may preview dashboard layouts using `?as=...`
- preview changes the dashboard layout key only; normal operational data remains department scoped unless global context is explicit

## Login Redirect Behavior

`LoginController` now sends users with `department_id` to `admin.my-dashboard` after login. Users without a department fall back to admin/global or department dashboard fallback behavior.

## Theme Registry

Added `DepartmentDashboardThemeRegistry`.

Every `DepartmentType` case has a theme definition with:

- accent class
- soft background class
- icon
- hero icon
- chart accent
- badge class
- empty state icon

## Dashboard Data Service

Added `DepartmentDashboardDataService`.

The service builds a Blade-ready payload:

- primary cards
- secondary cards
- work queue
- quick actions
- department services
- stock/usage card
- trend data
- recent activity
- restricted cards

Every operational metric is scoped by `department_id` where the source table supports it. Sensitive metrics are permission-gated before querying:

- prices/revenue require billing/invoice permissions
- stock/usage requires stock permissions
- stock cost requires cost-report permissions

## Layout Profiles

Added `DepartmentDashboardLayoutRegistry`.

Dedicated profiles exist for major department workflows. All 19 department types are covered by either a dedicated profile or the safe default profile.

## Blade Components / Partials Added

Added modern reusable dashboard partials under:

`resources/views/admin/dashboards/department/partials`

The new layout is:

`resources/views/admin/dashboards/department/show.blade.php`

It includes:

- hero section
- large KPI cards
- compact KPI cards
- work queue
- quick actions
- department services/prices card
- stock/usage card
- chart-ready trend card
- recent activity list
- empty and restricted states

## Department Services / Prices / Usage Behavior

Department services are filtered by `service_catalog.department_id`.

Prices render only when the user has price/billing permissions. Otherwise they render as hidden/restricted.

Stock usage is filtered through stock locations by `department_id` when available. Stock value/cost remains protected by explicit cost permissions.

## Menu Behavior

`DepartmentMenuProfileService` now exposes the active `profileKeyForType()` used by the dashboard context. The existing sidebar still applies department profile ordering after permission/module filtering, so profile logic does not grant access.

## Localisation

Added Phase 6 dashboard strings to:

- `lang/en/dashboards.php`
- `lang/fr/dashboards.php`

Existing department type labels remain in:

- `lang/en/departments.php`
- `lang/fr/departments.php`

## Tests Added

Added focused test file:

`tests/Feature/Departments/DepartmentDashboardUiPhase6Test.php`

Covered:

- context resolution
- laboratory/investigation scoping
- radiology scoping
- hidden prices/stock without permissions
- price display with permission
- admin preview behavior
- non-admin preview bypass prevention
- login redirect to department dashboard
- theme coverage for all department types
- layout fallback coverage

## Known Limitations

- Multi-department user switching is deferred.
- Some department types intentionally use the generic themed fallback until their dedicated modules mature.
- Trend rendering is chart-ready but uses lightweight Blade bars instead of adding new JavaScript.
- Department backfill apply was not run.

## Next Recommended Phase

Add optional department switching for users with multiple department assignments if a `department_user` pivot is introduced later.
