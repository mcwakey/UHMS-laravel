# UHMS Department Type UI Expansion — Phase 8: Dashboard Drilldowns, Assignment Management & Chart Rendering Polish

## Goal

Polish the department dashboard experience after Phase 7.

Phase 7 delivered:

```text id="sjnmve"
multi-department user support through department_user
session-based current department switching
dashboard/menu context switching
department-scoped chart datasets
department comparison report
permission-safe comparison CSV export
all 19 department types have chart fallbacks
localisation audit Active runtime candidates = 0
```

Phase 8 must now improve the user-facing operational experience:

```text id="92wp6o"
dashboard drilldowns
clickable KPI cards
richer chart rendering
department assignment management UI
user department assignment screens
department context switcher polish
comparison report UI polish
saved filters / date presets
empty states and restricted states polish
final dashboard responsiveness polish
```

Do not change the Phase 7 scoping model.

The core rule remains:

```text id="x52aea"
Department Type = layout / theme / menu / workflow family
Department ID = actual data scope
```

Do not expose cross-department data to ordinary users.

Do not use department type as a permission layer.

Permissions and modules remain the security layer.

---

## 1. Required Context

Read:

```text id="0dtp5k"
docs/DEPARTMENT_TYPE_EXPANSION_GAP_ANALYSIS_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_1_CANONICALISATION_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_2_DASHBOARD_REGISTRY_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_3_MENU_PROFILES_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_4_WORKFLOW_ROUTING_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_5_METRICS_REPORTS_REPORT.md
docs/DEPARTMENT_DASHBOARD_UI_PHASE_6_REPORT.md
docs/DEPARTMENT_DASHBOARD_UI_PHASE_7_ADVANCED_CHARTS_CONTEXT_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Inspect:

```text id="7z68ra"
app/Services/Department/DepartmentContextResolver.php
app/Services/Department/DepartmentContextSwitcherService.php
app/Services/Department/DepartmentDashboardDataService.php
app/Services/Department/DepartmentDashboardChartService.php
app/Services/Department/DepartmentDashboardThemeRegistry.php
app/Services/Department/DepartmentMenuProfileService.php
app/Services/Department/DepartmentMetricsRegistry.php
app/Http/Controllers/Admin/Dashboard/DepartmentDashboardController.php
app/Http/Controllers/Admin/Reports/DepartmentComparisonController.php
resources/views/admin/dashboards/department
resources/views/admin/reports
resources/views/admin/settings
resources/views/admin/users
routes/web.php
lang/en
lang/fr
tests/Feature/Departments
```

Testing instruction:

```text id="zke3xa"
Do not run the wide full application test suite after this phase.
Run focused dashboard/UI/assignment/localisation tests only.
The wide full-suite test remains deferred unless explicitly requested.
```

---

## 2. Main Deliverables

Implement:

```text id="j9m251"
clickable dashboard KPI drilldowns
department-scoped drilldown routes
richer Chart.js rendering for Phase 7 datasets
department assignment management UI
user department assignment UI
department switcher UI polish
comparison report UI polish
saved date presets
department dashboard responsive polish
restricted/unavailable state polish
documentation
focused tests
```

Do not apply department backfill.

Do not rewrite the full dashboard system.

Do not introduce a new frontend framework.

Use Bootstrap 5, existing components, Tabler Icons, and existing Chart.js usage if already present.

---

## 3. Clickable KPI Drilldowns

Make dashboard KPI cards actionable where safe.

Each KPI card should support:

```text id="ncnmjx"
title
value
subtitle
icon
theme
route/action URL nullable
permission nullable
module nullable
filters
restricted state
empty state
```

Examples:

```text id="v77qmk"
pending_lab_requests → lab requests index filtered by current department
completed_results_today → results filtered by current department and today
pending_prescriptions → prescription queue filtered by pharmacy department
low_stock_items → stock alerts filtered by current department/location
active_admissions → admissions filtered by ward/department
collections_today → payments report filtered by current department/date if permitted
```

Rules:

```text id="j6bp4w"
Do not create a drilldown link if the route does not exist.
Do not show drilldown link if user lacks permission.
Do not leak filters for departments the user cannot access.
Use current_department_id for ordinary users.
Use selected/admin preview department only when authorised.
```

---

## 4. Drilldown Filter Convention

Create a consistent filter convention.

Suggested query parameters:

```text id="socnkl"
department_id
department_type
date_from
date_to
status
scope=current_department
```

But respect existing route filter names if the module already uses a different convention.

Add helper:

```text id="mo35ad"
DepartmentDashboardDrilldownUrlBuilder
```

Responsibilities:

```text id="n6f07h"
build route URLs safely
skip missing routes
inject department_id/date/status filters
check permission/module before returning URL
avoid exposing inaccessible department IDs
```

---

## 5. Chart Rendering Polish

Phase 7 created chart-ready datasets.

Now render them cleanly.

Use existing Chart.js if already used.

Add or update partial:

```text id="22nyam"
resources/views/admin/dashboards/department/partials/chart-card.blade.php
```

Support chart types:

```text id="zlycol"
line
bar
doughnut
area-style line if already supported
```

Charts to render:

```text id="9thhzy"
activity_trend
revenue_trend if permitted
request_status_breakdown
service_usage_trend
stock_usage_trend if permitted
queue_status_breakdown
department_workload_by_day
```

Rules:

```text id="5vnn5l"
If no data, show empty state.
If restricted, show restricted state.
If module disabled, show unavailable state.
No hardcoded English labels in JS.
Use @json(__('...')) for labels.
Use theme chart accent from DepartmentDashboardThemeRegistry.
Do not add a new chart package.
```

---

## 6. Department Assignment Management UI

Phase 7 added the `department_user` pivot.

Now create management screens.

Add UI under user management or department settings.

Suggested screens:

```text id="yphyai"
User profile → Departments tab
Department show/edit → Assigned Users tab
```

Features:

```text id="2csgmy"
view assigned departments for a user
assign user to additional department
set primary department
remove department assignment
set role_context optional
set starts_at / ends_at optional
show expired/future assignments clearly
sync users.department_id when primary changes, if appropriate
```

Rules:

```text id="f5s690"
Do not remove users.department_id yet.
Keep users.department_id as backward-compatible primary/default.
One primary department per user.
Prevent duplicate active user-department assignment.
Expired/future assignments should not be available for context switching.
Do not allow ordinary users to assign themselves departments.
```

Permissions:

```text id="7klfdv"
users.departments.view
users.departments.manage
departments.users.view
departments.users.manage
```

Grant to Admin/Super Admin only by default unless project role rules say otherwise.

---

## 7. Department Switcher UI Polish

Improve the hero switcher.

Show:

```text id="t9dgts"
current department name
current department type badge
primary department badge
switched context indicator
department search if many departments
clear switch option if user has fallback
```

Rules:

```text id="4u45ou"
One-department users should not see unnecessary switcher clutter.
Multi-department users should clearly see the active department.
Admin/global preview users should see a clear global preview warning/badge.
```

---

## 8. Department Comparison UI Polish

Improve the Phase 7 comparison report.

Add:

```text id="zoxjps"
date presets: Today, This Week, This Month, Last 30 Days
department type filter
department multi-select if existing UI supports it
summary cards
comparison table sticky header if existing style supports it
restricted column indicators
export button only when permitted
empty state
```

Do not expose financial/stock-cost columns without permission.

Do not show patient-level details in comparison.

---

## 9. Saved Dashboard Filters

Add lightweight saved filter support only if simple.

Preferred minimum:

```text id="adxs6k"
remember last selected date range in session
remember selected chart tab in session
remember comparison filters in session
```

Do not create a heavy saved-report builder in this phase.

If adding database persistence is too large, defer it.

---

## 10. Department Dashboard Responsiveness

Review the modern dashboard on:

```text id="p6h4hf"
desktop
tablet
mobile
```

Fix:

```text id="ltle18"
KPI card wrapping
hero switcher overflow
chart height on mobile
table responsiveness
quick action stacking
badge wrapping
comparison filters layout
```

Use Bootstrap responsive utilities.

Do not add Tailwind.

---

## 11. Restricted / Unavailable State Polish

Create consistent components:

```text id="hwzm9n"
restricted-card
unavailable-card
empty-metric-card
```

States:

```text id="0ljpgg"
restricted because permission missing
unavailable because module disabled
empty because no data
not configured because department has no matching service/workflow
```

Do not confuse restricted with empty.

Restricted means the user may not view it.

Empty means there is nothing to show.

Unavailable means the feature/module is absent or disabled.

---

## 12. Localisation

Extend:

```text id="e0dc8j"
lang/en/dashboards.php
lang/fr/dashboards.php
lang/en/departments.php
lang/fr/departments.php
lang/en/reports.php
lang/fr/reports.php
lang/en/users.php
lang/fr/users.php
lang/en/common.php
lang/fr/common.php
```

Add keys:

```text id="0ks0fx"
view_details
drilldown
filtered_by_department
current_department_scope
restricted_data
module_unavailable
not_configured
no_department_data
assigned_departments
assign_department
remove_department_assignment
primary_department
set_primary_department
department_assignment
department_assignments
role_context
starts_at
ends_at
expired_assignment
future_assignment
active_assignment
switch_context
clear_department_context
comparison_presets
today
this_week
this_month
last_30_days
```

Maintain EN/FR parity.

Run:

```bash id="0t3e4k"
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
```

Required:

```text id="1dyyi9"
Active runtime candidates: 0
EN/FR parity OK
```

---

## 13. Audit Logging

Use `ActivityLogService`.

Audit:

```text id="t7flx8"
USER_DEPARTMENT_ASSIGNED
USER_DEPARTMENT_REMOVED
USER_DEPARTMENT_PRIMARY_SET
DEPARTMENT_CONTEXT_SWITCHED
DEPARTMENT_CONTEXT_CLEARED
DEPARTMENT_COMPARISON_EXPORTED
```

Do not log sensitive metric values.

---

## 14. Tests To Add

Add focused tests only.

Required tests:

```text id="ctqhr1"
KPI card drilldown uses current department_id filter.
KPI drilldown is hidden when route is missing.
KPI drilldown is hidden when permission is missing.
Chart card renders empty state for no data.
Chart card renders restricted state for restricted dataset.
Multi-department switcher shows only for users with multiple available departments.
Department assignment UI is permission protected.
Admin can assign user to department.
Admin can set primary department.
Duplicate active assignment is blocked.
Expired assignment is not available for switching.
Future assignment is not available for switching.
User cannot assign themselves a department.
Comparison report date presets work.
Comparison export button hidden without permission.
Responsive dashboard view compiles.
Localisation audit remains 0 active candidates.
```

Allowed focused command:

```bash id="zqmnbe"
php artisan test tests/Feature/Departments/DepartmentDashboardUiPhase8Test.php
```

Also run:

```bash id="cjtxue"
php artisan test tests/Feature/Departments
php artisan test tests/Feature/DepartmentDashboardTest.php
```

Do not run the wide full suite unless explicitly instructed.

---

## 15. Minimal Verification Commands

Run:

```bash id="8f0vo2"
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
php artisan route:list
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan permissions:audit --strict
git diff --check
```

Also run PHP lint on changed PHP files if practical:

```bash id="bb42qm"
find app database routes lang resources/views tests -name "*.php" -print0 | xargs -0 -n1 php -l
```

Do not apply department backfill unless explicitly instructed.

Do not run the wide full suite.

---

## 16. Documentation

Create:

```text id="lqo9b3"
docs/DEPARTMENT_DASHBOARD_UI_PHASE_8_DRILLDOWNS_ASSIGNMENTS_POLISH_REPORT.md
```

Include:

```text id="9kjvch"
summary
KPI drilldown behavior
drilldown URL builder design
chart rendering polish
assignment management UI
primary department behavior
department switcher polish
comparison report polish
saved filter/session behavior
responsive UI fixes
restricted/unavailable/empty states
audit logging
localisation changes
tests added
focused tests run
minimal verification commands run
known limitations
next recommended phase
```

Known limitations:

```text id="u84q5l"
full custom dashboard widget builder is deferred
predictive analytics are deferred
wide full-suite regression remains deferred unless explicitly run
department backfill apply was not run unless explicitly instructed
```

---

## 17. Acceptance Criteria

Phase 8 is complete only when:

```text id="j8xi94"
dashboard KPI cards have permission-safe drilldowns
chart cards render using Phase 7 chart datasets
empty/restricted/unavailable states are visually consistent
department assignment management UI exists and is permission protected
primary department management works
department switcher UI is polished
comparison report UI is improved
comparison export remains permission safe
responsive layout compiles and behaves cleanly
ActivityLogService audits assignment/context/export actions
EN/FR parity passes
localisation audit Active runtime candidates = 0
focused tests pass
permissions audit is clean
documentation report is created
department backfill is not applied unless explicitly instructed
wide full suite is intentionally deferred
```

Proceed with Department Type UI Expansion Phase 8 now.
