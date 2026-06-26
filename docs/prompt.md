# UHMS Department Type UI Expansion — Phase 6: Modern Department Dashboards, Department Context & Scoped Menus

## Goal

Upgrade UHMS from technically department-aware to visually and operationally department-oriented.

The system already supports:

```text
19 department types
department dashboard registry
department menu profiles
workflow routing type groups
department metrics registry
```

Now build a modern department dashboard experience where:

```text
when a user logs in, their department-type dashboard loads
the sidebar/menu is department-oriented
the dashboard theme matches the department type
all dashboard content is scoped to the user's actual department
metrics, services, stock, prices, requests and usages are department-specific
```

Important distinction:

```text
Department Type = dashboard layout / menu profile / workflow family
Department ID = actual data scope
```

Example:

```text
A lab technician belongs to Laboratory department.
Laboratory department type is investigation.
They get the Investigation dashboard layout and investigation menu profile.
But all services, stock, prices, requests and usages shown must be Laboratory-only.

An X-ray technician belongs to X-Ray/Radiology department.
They get the Radiology dashboard layout if department_type is radiology.
If still grouped under investigation, they may share the diagnostic dashboard layout.
But all content must still be scoped to X-Ray/Radiology department_id only.
```

Do not show all department-type data to every user of that type.

Do not use department type as a permission system.

Permissions and modules remain the security layer.

---

## 1. Required Context

Read:

```text
docs/DEPARTMENT_TYPE_EXPANSION_GAP_ANALYSIS_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_1_CANONICALISATION_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_2_DASHBOARD_REGISTRY_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_3_MENU_PROFILES_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_4_WORKFLOW_ROUTING_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_5_METRICS_REPORTS_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Also inspect these dashboard inspiration files if present in the project or supplied separately:

```text
resources/views/dashboard/doctor-dashboard.blade.php
resources/views/dashboard/patient-dashboard.blade.php
resources/views/admin/dashboards/index.blade.php
```

Use their design direction:

```text
modern KPI cards
department-themed accents
SVG/card background accents
compact secondary metrics
queue/list tables
quick action buttons
chart-ready cards
stock/usage alert cards
empty states
```

Do not introduce Tailwind.

Do not introduce a new frontend framework.

Use Bootstrap 5, existing components and Tabler Icons.

---

## 2. Main Deliverables

Implement:

```text
DepartmentContextResolver
DepartmentDashboardThemeRegistry
DepartmentDashboardDataService
modern reusable department dashboard components
department-scoped dashboard metrics
department-scoped services/prices/stock/usage cards
department-oriented quick actions
department login redirect behavior
department-oriented menu context
modern dashboard Blade layout
focused tests
documentation report
```

---

## 3. Department Context Resolver

Create:

```text
App\Services\Department\DepartmentContextResolver
```

Responsibilities:

```text
resolve current user
resolve user's department
resolve department_id
resolve department_type
resolve dashboard key from DepartmentDashboardRegistry
resolve menu profile from DepartmentMenuProfileService
resolve theme from DepartmentDashboardThemeRegistry
resolve whether user is global/admin
resolve fallback context
```

Context object should include:

```text
user
department nullable
department_id nullable
department_type nullable
department_type_label
dashboard_key
menu_profile_key
theme
is_global_context
can_preview_departments
```

Rules:

```text
If user has one department:
    dashboard context = that department

If user has no department:
    fallback to role/global dashboard

If user is Admin/Super Admin:
    allow global dashboard and department preview

If department type is null:
    fallback safely

If department type is unknown:
    fallback safely
```

Do not implement multi-department switcher yet unless the project already has department_user pivot.

Document multi-department switcher as future.

---

## 4. Login Redirect

Inspect current login/auth redirect flow.

Update safely so that after login:

```text
if user has department:
    redirect to /admin/my-dashboard or existing department dashboard route
else:
    redirect to role/global dashboard
```

The dashboard route itself must use DepartmentContextResolver.

Do not create duplicate dashboard routes unless needed.

Admin/Super Admin should keep access to global/admin dashboard.

---

## 5. Department Dashboard Theme Registry

Create:

```text
App\Services\Department\DepartmentDashboardThemeRegistry
```

Every department type must have a theme.

Theme fields:

```text
key
accent_class
soft_bg_class
icon
hero_icon
bg_asset optional
chart_accent optional
badge_class
empty_state_icon
```

Suggested themes:

```text
consultation: blue, ti-stethoscope
emergency: red, ti-ambulance
investigation: indigo/purple, ti-test-pipe
radiology: cyan, ti-scan
procedure: orange, ti-tools
theatre: violet, ti-surgical-mask
treatment: teal, ti-first-aid-kit
nursing: green, ti-nurse
pharmacy: emerald, ti-pill
inpatient: blue-gray, ti-bed
maternity: pink, ti-baby-carriage
blood_bank: red, ti-droplet
mortuary: slate, ti-building-warehouse
ambulance: red-orange, ti-ambulance
records: gray-blue, ti-folder
finance: green, ti-cash-banknote
stores: amber, ti-packages
support: dark, ti-tool
administrative: navy, ti-settings
generic: secondary, ti-layout-dashboard
```

Use only Tabler icons already available.

No hard failure if an icon is unavailable; fallback to dashboard icon.

---

## 6. Modern Reusable Dashboard Components

Create reusable components/partials instead of duplicating Blade.

Suggested components:

```text
resources/views/admin/dashboards/department/partials/hero.blade.php
resources/views/admin/dashboards/department/partials/kpi-card.blade.php
resources/views/admin/dashboards/department/partials/mini-kpi-card.blade.php
resources/views/admin/dashboards/department/partials/work-queue-card.blade.php
resources/views/admin/dashboards/department/partials/quick-actions.blade.php
resources/views/admin/dashboards/department/partials/activity-list.blade.php
resources/views/admin/dashboards/department/partials/stock-usage-card.blade.php
resources/views/admin/dashboards/department/partials/services-card.blade.php
resources/views/admin/dashboards/department/partials/chart-card.blade.php
resources/views/admin/dashboards/department/partials/empty-card.blade.php
resources/views/admin/dashboards/department/partials/restricted-card.blade.php
```

Design direction:

```text
top hero with department name/type/user/date
4 large KPI cards using background SVG accents
secondary compact metrics row
main work queue / request list
right-side quick actions and alerts
department services/prices card
stock and usage card where applicable
trend/chart-ready card
recent activity table
```

Use current Bootstrap design style.

Do not add new JS chart library if Chart.js is already used.

If no chart data exists, render an empty-state card.

---

## 7. Department Dashboard Data Service

Create:

```text
App\Services\Department\DepartmentDashboardDataService
```

Responsibilities:

```text
build dashboard payload for context
load department-scoped metrics
load department-scoped work queues
load department services
load department prices if permitted
load department stock/usage if permitted
load quick actions
load activity lists
load chart-ready trend data
hide restricted metrics before querying sensitive data
```

All data methods must receive or use:

```text
department_id
department_type
user
permissions
enabled modules
date range
```

Critical rule:

```text
Every operational metric must be scoped to department_id unless user is explicitly in global/admin preview mode.
```

Do not use only department_type for normal users.

---

## 8. Department-Specific Content Rules

### Investigation / Laboratory

For a user in Laboratory department:

```text
show pending lab requests for Laboratory only
show samples awaiting acceptance for Laboratory only
show completed lab results for Laboratory only
show Laboratory services only
show Laboratory prices only if permitted
show Laboratory stock/consumable usage only if permitted
```

### Radiology / X-Ray

For a user in X-Ray/Radiology department:

```text
show pending imaging requests for that radiology department only
show completed imaging results for that department only
show radiology services only
show radiology prices only if permitted
show radiology consumables/usage only if permitted
```

### Pharmacy

```text
show pending prescriptions routed to pharmacy
show dispensing queue
show pharmacy stock for that department/location where available
show low stock/near expiry/out of stock if permitted
show product prices/sales only if permitted
```

### Emergency

```text
show active emergency cases
show emergency triage queue
show emergency sessions
show emergency services/billing only if permitted
show emergency stock/consumables only if permitted
```

### Inpatient / Nursing / Maternity

```text
show active admissions/ward patients for that department
show beds/occupancy if department maps to ward/bed data
show vitals due/recorded
show discharges pending
show nursing/treatment tasks if available
```

### Finance

```text
show collections, invoices, receivables, claims only if financial permissions allow
department context should not bypass financial permissions
```

### Stores

```text
show stock requests, stock issues, procurement, low stock if permitted
stock cost must remain permission protected
```

### Generic / Unsupported

```text
show department profile
assigned staff
services count
recent activity if available
quick links
empty states for unavailable module-specific metrics
```

---

## 9. Department Services / Prices / Usage Cards

Add cards for:

```text
department services
department prices
department stock/consumables
department usage
```

Rules:

```text
services are filtered by department_id
prices are shown only if user has permission
stock/usage shown only if user has inventory/stock permission
stock cost shown only if user has stock-cost/finance permission
do not expose prices/costs to users without permission
do not change service pricing logic
do not alter invoice totals
```

This is important for the lab/x-ray case:

```text
Lab sees Laboratory services/prices/usage.
X-ray sees X-ray services/prices/usage.
They must not see each other's department content unless permitted globally.
```

---

## 10. Department-Oriented Menu Load

Update menu/profile integration so that when the user logs in:

```text
the department menu profile is applied
department dashboard shortcut is visible
quick links are department-relevant
links can carry department_id filters where useful
```

Example:

```text
Lab:
My Dashboard
Lab Requests
Samples
Results
Laboratory Services
Laboratory Stock Usage
Reports

X-ray:
My Dashboard
Radiology Requests
Imaging Results
Radiology Services
Radiology Stock Usage
Reports
```

Rules:

```text
Permissions/modules still filter the menu first.
Department profile only reorders/enriches allowed items.
Missing routes must be skipped safely.
No menu item should grant access.
```

---

## 11. Dashboard Route / Controller

Update the department dashboard controller to use:

```text
DepartmentContextResolver
DepartmentDashboardThemeRegistry
DepartmentDashboardDataService
DepartmentDashboardRegistry
DepartmentMenuProfileService
```

Controller should pass to Blade:

```text
context
theme
dashboard
metrics
primary_cards
secondary_cards
work_queue
quick_actions
services
stock_usage
trends
activities
empty_states
```

Keep controller thin.

No complex queries in controller.

No complex queries in Blade.

---

## 12. Blade Layout

Create or update:

```text
resources/views/admin/dashboards/department/show.blade.php
```

The layout should include:

```text
hero section
primary KPI cards row
secondary compact metrics row
main work queue / requests section
quick actions
services/prices/usage cards
trend/chart area
recent activity
empty/restricted states
```

Use the uploaded dashboard inspiration:

```text
admin-style KPI cards with background SVG accents
doctor-style operational queue
staff-style generic stats/lists fallback
```

No hardcoded English labels.

All labels must use lang files.

---

## 13. Department Type Specific Layout Profiles

Add layout profile definitions.

Each type defines:

```text
primary cards
secondary cards
queue/list blocks
service/usage blocks
quick actions
trend blocks
fallback blocks
```

Example:

```php
'investigation' => [
    'primary_cards' => [
        'pending_requests',
        'samples_awaiting_acceptance',
        'completed_results_today',
        'department_revenue_today',
    ],
    'main_queue' => 'lab_requests',
    'side_cards' => ['services', 'stock_usage', 'recent_results'],
]
```

Radiology can use:

```php
'radiology' => [
    'primary_cards' => [
        'pending_imaging',
        'scheduled_imaging',
        'completed_imaging_today',
        'department_revenue_today',
    ],
    'main_queue' => 'radiology_requests',
    'side_cards' => ['services', 'stock_usage', 'recent_results'],
]
```

---

## 14. Permission and Data Safety

Before querying any restricted metric:

```text
check permission
check module enabled
check department scope
```

Restricted examples:

```text
prices
revenue
invoice balances
stock cost
sales
claims
clinical details
patient identifiable lists
```

Rules:

```text
If user lacks permission:
    do not query sensitive data
    hide card or show restricted state

If module disabled:
    show unavailable module state or hide card

If department_id missing:
    use fallback/global only when user has admin/global permission
```

---

## 15. Localisation

Extend:

```text
lang/en/dashboards.php
lang/fr/dashboards.php
lang/en/departments.php
lang/fr/departments.php
lang/en/menu.php
lang/fr/menu.php
```

Add keys for:

```text
department_dashboard
my_department_dashboard
department_context
department_services
department_prices
department_usage
department_stock
department_work_queue
department_activity
department_quick_actions
scoped_to_department
global_preview
restricted_metric
metric_unavailable
no_department_assigned
no_department_activity
view_services
view_requests
view_results
view_stock
view_prices
```

Add type-specific dashboard titles/subtitles for all 19 types.

Maintain EN/FR parity.

Run localisation audit and parity checks.

Active runtime candidates must remain:

```text
0
```

---

## 16. Tests To Add

Add focused tests only.

Required tests:

```text
DepartmentContextResolver resolves user department_id and department_type.
User with laboratory department gets investigation dashboard context.
Laboratory dashboard data is scoped to Laboratory department_id only.
X-ray/radiology user does not see Laboratory services/stock/usage.
Radiology dashboard data is scoped to Radiology department_id only.
User with pharmacy department gets pharmacy dashboard context.
Pharmacy dashboard data is scoped to Pharmacy department_id only.
User with no department falls back safely.
Admin can preview department dashboards if permitted.
Non-admin cannot bypass department scope through preview.
Department dashboard route redirects/loads after login.
Menu profile applies after login for user department.
Department services card only shows services for current department.
Department prices card is hidden/restricted without price permission.
Stock usage card is hidden/restricted without inventory permission.
Finance metrics are hidden without finance permission.
All 19 department types have theme definitions.
All 19 department types have layout profiles or safe fallback.
Modern department dashboard view compiles.
EN/FR dashboard labels exist.
```

Allowed focused command:

```bash
php artisan test tests/Feature/Departments/DepartmentDashboardUiPhase6Test.php
```

Also run focused dashboard/menu tests if touched:

```bash
php artisan test tests/Feature/Departments
```

Do not run the wide full suite unless explicitly instructed.

---

## 17. Minimal Verification Commands

Run:

```bash
php artisan route:list
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan permissions:audit --strict
git diff --check
```

Also run PHP lint on changed PHP files if practical:

```bash
find app database routes lang resources/views tests -name "*.php" -print0 | xargs -0 -n1 php -l
```

Do not apply department backfill unless explicitly instructed.

Do not run the wide full suite.

---

## 18. Documentation

Create:

```text
docs/DEPARTMENT_DASHBOARD_UI_PHASE_6_REPORT.md
```

Include:

```text
summary
department context resolver design
login redirect behavior
theme registry
dashboard data service
layout profile design
department-scoped metrics
department services/prices/usage behavior
menu behavior after login
permission/module safety
Blade/components added
localisation changes
tests added
focused tests run
minimal verification commands run
known limitations
next recommended phase
```

Known limitations should mention:

```text
multi-department user switcher is deferred
some department types use generic themed fallback until dedicated modules exist
advanced charts can be improved later
department backfill apply was not run unless explicitly instructed
```

---

## 19. Acceptance Criteria

This phase is complete only when:

```text
login loads the user's department-type dashboard
sidebar/menu is department-oriented after login
dashboard theme matches department type
dashboard data is scoped to actual department_id
lab user sees only laboratory services/stock/prices/usage
radiology/x-ray user sees only radiology services/stock/prices/usage
all 19 department types have theme/layout fallback
services/prices/stock/usage cards are permission-safe
finance/stock-cost sensitive data is not leaked
admin/global preview remains controlled
user with no department falls back safely
modern dashboard view compiles
EN/FR localisation parity is maintained
active runtime localisation candidates remain 0
focused tests pass
documentation report is created
wide full suite is intentionally deferred
```

Proceed with Department Type UI Expansion Phase 6 now.
