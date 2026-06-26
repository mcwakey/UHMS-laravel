# UHMS Department Type UI Expansion — Phase 7: Advanced Charts, Department Comparison & Multi-Department Context Switching

## Goal

Enhance the modern department dashboards with advanced visual analytics, department comparison tools, and a controlled multi-department context switcher.

Phase 6 delivered:

```text id="wh2gnm"
DepartmentContextResolver
DepartmentDashboardThemeRegistry
DepartmentDashboardDataService
modern reusable department dashboard layout
department-scoped dashboard metrics
department services/prices/usage cards
department-oriented menu loading
login redirect to the user's department dashboard
localisation audit cleanup with active runtime candidates = 0
```

Phase 7 must now add:

```text id="e1ioji"
advanced department dashboard charts
department comparison view
department performance trends
department type rollups
multi-department user support foundation
current department context switcher
admin/global preview improvements
chart-ready datasets
exportable comparison data
permission-safe analytics
```

Do not break Phase 6 department scoping.

Do not expose department data a user should not see.

Do not introduce a new frontend framework.

Use Bootstrap 5, existing UI components, Tabler Icons, and existing chart tooling if already present.

---

## 1. Required Context

Read:

```text id="pe9rj7"
docs/DEPARTMENT_TYPE_EXPANSION_GAP_ANALYSIS_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_1_CANONICALISATION_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_2_DASHBOARD_REGISTRY_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_3_MENU_PROFILES_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_4_WORKFLOW_ROUTING_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_5_METRICS_REPORTS_REPORT.md
docs/DEPARTMENT_DASHBOARD_UI_PHASE_6_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Inspect:

```text id="qo76t3"
app/Services/Department/DepartmentContextResolver.php
app/Services/Department/DepartmentDashboardThemeRegistry.php
app/Services/Department/DepartmentDashboardDataService.php
app/Services/Department/DepartmentMetricsRegistry.php
app/Services/Department/DepartmentMenuProfileService.php
app/Services/Dashboard/DepartmentDashboardRegistry.php
app/Http/Controllers/Admin/Dashboard/DepartmentDashboardController.php
app/Http/Controllers/Auth/LoginController.php
resources/views/admin/dashboards/department/show.blade.php
resources/views/admin/dashboards/department/partials
resources/views/admin/reports
routes/web.php
lang/en
lang/fr
tests/Feature/Departments
```

Testing instruction:

```text id="l128vg"
Do not run the wide full application test suite after this phase.
Run focused dashboard/chart/context/localisation tests only.
The wide full-suite test remains deferred unless explicitly requested.
```

---

## 2. Core Rule

The most important rule remains:

```text id="ljl2ug"
Department Type = dashboard layout / theme / workflow family
Department ID = actual data scope
```

For ordinary users:

```text id="lnm4rb"
All dashboard data must be scoped to the selected/current department_id.
```

For Admin/Super Admin or authorised global users:

```text id="mj4z5i"
Allow global and comparison views only through explicit permissions.
```

Permissions and modules remain the security layer.

Department type must never grant access by itself.

---

## 3. Multi-Department User Support

Currently UHMS uses:

```text id="6j4zec"
users.department_id
```

for a single department.

Add a controlled multi-department foundation.

Create a pivot table if it does not already exist:

```text id="zop7ec"
department_user
```

Suggested fields:

```text id="fvh0t4"
id
user_id
department_id
is_primary
role_context nullable
starts_at nullable
ends_at nullable
created_at
updated_at
```

Rules:

```text id="g4lcce"
Do not remove users.department_id yet.
Keep users.department_id as the primary/default department for backward compatibility.
department_user adds optional additional departments.
A user can have one primary department.
A user can belong to multiple departments.
If no pivot rows exist, fallback to users.department_id.
```

Migration must be additive and safe.

Do not change existing login behavior for users with only one department.

---

## 4. Department Context Switcher

Add current department context switching.

Create service:

```text id="q1pa4w"
DepartmentContextSwitcherService
```

Responsibilities:

```text id="umcgkt"
list departments available to the user
set current department context
store current department context in session
validate selected department belongs to user or user has global permission
fallback to primary department
clear invalid session department
```

Session key suggestion:

```text id="3f2zm3"
current_department_id
```

Rules:

```text id="5qx0gn"
Ordinary users may switch only between their assigned departments.
Admin/Super Admin may preview departments if permission allows.
Switching department changes dashboard scope and menu priority.
Switching department does not grant route permission.
```

Routes:

```text id="em2sni"
POST admin/my-dashboard/context
DELETE admin/my-dashboard/context
```

or follow project route conventions.

---

## 5. Context Resolver Update

Update:

```text id="53c4j0"
DepartmentContextResolver
```

to resolve in this order:

```text id="z4cfjg"
1. Valid session current_department_id
2. User primary department from department_user pivot
3. users.department_id fallback
4. role/global fallback
```

Context object should now include:

```text id="tjv7fx"
available_departments
current_department
current_department_id
current_department_type
is_switched_context
can_switch_department
can_use_global_context
```

If user has multiple departments:

```text id="gz259v"
show department switcher in the dashboard hero
```

If user has only one department:

```text id="q89wzb"
do not clutter UI with switcher
```

---

## 6. Dashboard Hero Switcher

Update the modern dashboard hero partial.

Add:

```text id="mlij74"
current department badge
department type badge
department switcher dropdown if user has multiple departments
global preview badge if admin/global mode
```

Switcher should show:

```text id="qol2n9"
department name
department type
primary badge
```

Rules:

```text id="qxqu9h"
Switch action must be POST, not GET, unless project convention says otherwise.
CSRF protection required.
Do not expose departments not available to the user.
```

---

## 7. Department-Oriented Menu After Switch

When context switches:

```text id="zbg48y"
menu should reorder based on selected department type
dashboard shortcut should reflect selected department type
quick links should use selected department context where useful
```

Update:

```text id="bw7ehf"
DepartmentMenuProfileService
SidebarMenuBuilder
```

if needed so they use `DepartmentContextResolver`, not only `auth()->user()->department`.

Rules:

```text id="n6gzxt"
Permissions/modules filter first.
Department profile only reorders/enriches allowed menu items.
Missing routes are skipped safely.
```

---

## 8. Advanced Chart Data Service

Create:

```text id="qgy4ex"
DepartmentDashboardChartService
```

or add a clean chart layer inside `DepartmentDashboardDataService`.

Charts should return JSON/chart-ready arrays.

Do not hardcode chart JavaScript data in Blade manually.

Recommended chart datasets:

```text id="25y4ok"
activity_trend
revenue_trend
request_status_breakdown
service_usage_trend
stock_usage_trend
queue_status_breakdown
department_workload_by_day
```

Each dataset must include:

```text id="ixylho"
labels
datasets
format
empty_state
permission/module requirements
```

---

## 9. Per-Type Chart Suggestions

### consultation

```text id="5k9ad1"
consultations by day
waiting vs consulting vs completed
follow-ups due trend
```

### emergency

```text id="xxgttg"
emergency cases by priority
active vs completed cases
triage status breakdown
```

### investigation

```text id="4ajm6p"
lab requests by status
samples accepted vs pending
results completed trend
```

### radiology

```text id="2isauc"
imaging requests by status
scheduled vs completed imaging
urgent imaging trend
```

### procedure

```text id="kz53dq"
procedures by status
consumables usage trend
procedure revenue if permitted
```

### theatre

```text id="5idms1"
surgery schedule trend
pre-op/in-progress/post-op breakdown
theatre consumables trend
```

### pharmacy

```text id="x9z9wl"
prescriptions pending vs dispensed
low stock trend
near-expiry stock trend
pharmacy sales if permitted
```

### inpatient / nursing / maternity

```text id="w8a4du"
bed occupancy trend
admissions vs discharges
vitals recorded trend
```

### blood_bank

```text id="shiqto"
blood units by status
near-expiry units
blood requests trend
```

### finance

```text id="dl2ny6"
collections trend
invoice aging breakdown
claim status breakdown
```

### stores

```text id="zn7xr0"
stock requests trend
stock issues trend
low stock trend
```

Generic types should get:

```text id="hzidxn"
activity trend
services count
assigned users
available/unavailable states
```

---

## 10. Department Comparison View

Add a department comparison screen.

Suggested route:

```text id="utf95x"
admin.reports.department-comparison.index
```

or place under existing department reports route group.

Purpose:

```text id="hv9zdi"
compare departments side-by-side by type, workload, service usage, revenue if permitted, stock usage if permitted, and activity.
```

Filters:

```text id="8mzijt"
date_from
date_to
department_type
department_ids[]
metric_group
branch/facility if supported
include_inactive
```

Comparison cards:

```text id="dzdwbs"
total departments
total activity
total services
total revenue if permitted
total pending requests
```

Comparison table:

```text id="jrk754"
department
type
assigned users
services
activity
pending work
completed work
revenue if permitted
stock alerts if permitted
```

Rules:

```text id="6s56ng"
Only show departments the user can view.
Only show revenue/cost data with financial permissions.
Do not show clinical details in comparison view, only aggregate counts.
```

---

## 11. Department Type Comparison

Add rollup comparison by type.

Example:

```text id="le3jxx"
investigation vs radiology
procedure vs theatre
inpatient vs maternity
finance vs pharmacy revenue where permitted
```

Use:

```text id="sveq6y"
DepartmentMetricsRegistry
```

where possible.

Do not duplicate calculations.

---

## 12. Export Support

Add CSV export for comparison if existing export style supports it.

Route suggestion:

```text id="dh0ysy"
admin.reports.department-comparison.export
```

Rules:

```text id="osk17l"
Export respects filters.
Export respects permissions.
Export never includes restricted financial/stock-cost columns if user lacks permission.
```

---

## 13. Chart UI Components

Add reusable chart partials/components:

```text id="n4pqe6"
resources/views/admin/dashboards/department/partials/chart-card.blade.php
resources/views/admin/dashboards/department/partials/status-breakdown-card.blade.php
resources/views/admin/dashboards/department/partials/comparison-table.blade.php
```

If Chart.js is already loaded globally or used in dashboards, reuse it.

If Chart.js is only locally loaded in admin dashboard, load it safely for this dashboard only.

Do not add a new chart package.

If JavaScript is disabled:

```text id="70hkrk"
show table fallback or empty chart card
```

---

## 14. Theme-Aware Charts

Charts should use department theme accents.

Example:

```text id="0zv897"
primary dataset uses theme chart_accent
secondary dataset uses neutral color
danger/warning states use Bootstrap semantic classes
```

Keep CSS minimal.

No hardcoded English labels in JS.

Pass translated labels from Blade to JS via `@json(__('...'))`.

---

## 15. Permissions

Add only if needed:

```text id="77t9cq"
departments.context.switch
reports.department_comparison.view
reports.department_comparison.export
dashboards.department.global_preview
```

Suggested access:

```text id="xxgnxa"
Super Admin/Admin: all
Department heads: switch between assigned departments and view assigned comparison
Finance Manager: comparison with revenue where permitted
Clinical staff: own department only unless assigned to multiple departments
```

Do not give ordinary users global comparison by default.

---

## 16. Localisation

Extend:

```text id="m4fgn7"
lang/en/dashboards.php
lang/fr/dashboards.php
lang/en/departments.php
lang/fr/departments.php
lang/en/reports.php
lang/fr/reports.php
lang/en/menu.php
lang/fr/menu.php
```

Add keys:

```text id="7xu5rp"
switch_department
current_department
primary_department
available_departments
department_context_switched
department_context_cleared
global_department_view
department_comparison
compare_departments
comparison_filters
metric_group
activity_trend
revenue_trend
request_status_breakdown
service_usage_trend
stock_usage_trend
queue_status_breakdown
workload_by_day
no_chart_data
chart_unavailable
comparison_export
assigned_departments
```

Maintain EN/FR parity.

Run:

```bash id="wvyubf"
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
```

Required:

```text id="q18rps"
Active runtime candidates: 0
EN/FR parity OK
```

---

## 17. Tests To Add

Add focused tests only.

Required tests:

```text id="ljxcbd"
user with one department does not see switcher
user with multiple departments sees switcher
user can switch only to assigned department
user cannot switch to unassigned department
admin can preview/global switch if permitted
context resolver uses session department when valid
invalid session department is cleared safely
menu profile changes after department switch
dashboard data scope changes after department switch
lab/radiology switch shows correct department-specific services
chart service returns datasets scoped to department_id
chart service hides revenue datasets without permission
comparison route is permission protected
comparison view lists only allowed departments
comparison export respects filters
comparison export hides restricted columns
all 19 department types have chart fallback
localisation audit remains 0 active candidates
```

Allowed focused command:

```bash id="le9u7z"
php artisan test tests/Feature/Departments/DepartmentDashboardAdvancedUiPhase7Test.php
```

Also run:

```bash id="a9uekj"
php artisan test tests/Feature/Departments
php artisan test tests/Feature/DepartmentDashboardTest.php
```

Do not run the wide full suite unless explicitly instructed.

---

## 18. Minimal Verification Commands

Run:

```bash id="hqq3a1"
php artisan migrate --force
php artisan route:list
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan permissions:audit --strict
git diff --check
```

Also run PHP lint on changed PHP files if practical:

```bash id="d8f1h6"
find app database routes lang resources/views tests -name "*.php" -print0 | xargs -0 -n1 php -l
```

Do not apply department backfill unless explicitly instructed.

Do not run the wide full suite.

---

## 19. Documentation

Create:

```text id="zk4akt"
docs/DEPARTMENT_DASHBOARD_UI_PHASE_7_ADVANCED_CHARTS_CONTEXT_REPORT.md
```

Include:

```text id="u7b2q1"
summary
multi-department support design
department_user pivot behavior
context switcher behavior
context resolver changes
menu behavior after switching
chart service design
chart datasets added
department comparison view
department type comparison behavior
export behavior
permission/module safety
localisation changes
tests added
focused tests run
minimal verification commands run
known limitations
next recommended phase
```

Known limitations:

```text id="cny4d2"
advanced predictive analytics are deferred
custom user dashboard widgets are deferred
full wide-suite regression is still deferred unless explicitly run
department backfill apply was not run unless explicitly instructed
```

---

## 20. Acceptance Criteria

Phase 7 is complete only when:

```text id="a3xz9z"
users can have multiple departments through safe additive structure
users can switch only between allowed departments
dashboard context changes after switch
menu profile changes after switch
dashboard data remains scoped to current department_id
charts are department-scoped
comparison view is permission protected
comparison export respects permissions
all chart labels are localised
all 19 department types have chart fallback
restricted revenue/stock-cost data is hidden before querying
view cache compiles
localisation audit Active runtime candidates = 0
EN/FR parity passes
focused tests pass
permissions audit is clean
documentation report is created
department backfill is not applied unless explicitly instructed
wide full suite is intentionally deferred
```

Proceed with Department Type UI Expansion Phase 7 now.
