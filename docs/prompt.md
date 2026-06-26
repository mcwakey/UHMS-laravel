# UHMS Department Dashboard UI — Phase 8.1: Design Differentiation, Dashboard Naming, Department Personalisation & Menu Identity Correction

## Goal

Correct the current department dashboard UI issue where all department dashboards look too similar.

The system is already department-aware, scoped, permission-safe, and supports modern dashboard cards, drilldowns, ApexCharts, department switching, comparison reports, and assignment management.

However, the dashboards still feel too generic because they share almost the same layout and only change colors/metrics.

This phase must make dashboards visually and structurally different by department type, while also personalising each dashboard with the actual department name.

The core rule is:

```text
Department Type = dashboard design / personality / workflow family / menu profile
Department Name = dashboard identity / personalisation
Department ID = actual data scope
```

Example:

```text
A Laboratory user belongs to Laboratory Department.
Department type = investigation.
Dashboard name = Investigation Dashboard.
Dashboard identity = Laboratory Department.
Data scope = Laboratory department_id only.

An X-Ray user belongs to X-Ray / Radiology Unit.
Department type = radiology.
Dashboard name = Radiology Dashboard.
Dashboard identity = X-Ray / Radiology Unit.
Data scope = X-Ray / Radiology department_id only.
```

Do not show all investigation data to every investigation-type user.

Do not show all radiology data to every radiology-type user.

Do not use department type as a permission layer.

Permissions and modules remain the security layer.

Do not apply department backfill in this phase.

Do not run the wide full suite unless explicitly instructed.

---

## 1. Required Context

Read:

```text
docs/DEPARTMENT_DASHBOARD_UI_PHASE_6_REPORT.md
docs/DEPARTMENT_DASHBOARD_UI_PHASE_7_ADVANCED_CHARTS_CONTEXT_REPORT.md
docs/DEPARTMENT_DASHBOARD_UI_PHASE_8_DRILLDOWNS_ASSIGNMENTS_POLISH_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Also inspect:

```text
app/Services/Department/DepartmentContextResolver.php
app/Services/Department/DepartmentContextSwitcherService.php
app/Services/Department/DepartmentDashboardThemeRegistry.php
app/Services/Department/DepartmentDashboardDataService.php
app/Services/Department/DepartmentDashboardChartService.php
app/Services/Department/DepartmentDashboardDrilldownUrlBuilder.php
app/Services/Department/DepartmentMenuProfileService.php
app/Services/Department/DepartmentMetricsRegistry.php
app/Services/Dashboard/DepartmentDashboardRegistry.php
app/Http/Controllers/Admin/Dashboard/DepartmentDashboardController.php
app/Http/Controllers/Admin/Reports/DepartmentComparisonController.php
resources/views/admin/dashboards/department/show.blade.php
resources/views/admin/dashboards/department/partials
resources/views/layouts
resources/views/components
lang/en/dashboards.php
lang/fr/dashboards.php
lang/en/departments.php
lang/fr/departments.php
lang/en/menu.php
lang/fr/menu.php
tests/Feature/Departments
```

Inspect the existing dashboard inspiration:

```text
resources/views/admin/dashboards/admin.blade.php
resources/views/admin/dashboards/doctor.blade.php
resources/views/admin/dashboards/staff.blade.php
```

Use them as inspiration:

```text
admin dashboard = executive / KPI / trend / finance / system overview
doctor dashboard = queue-first / action-first / clinical workflow
staff dashboard = generic fallback / simple stats and lists
```

Do not introduce Tailwind.

Do not introduce a new frontend framework.

Use Bootstrap 5, existing UI components, Tabler Icons, and the already-bundled ApexCharts asset.

---

## 2. Problem To Fix

Currently, the department dashboards are too similar because they share mostly the same layout.

They likely share:

```text
same hero structure
same KPI card grid
same chart area
same services/stock blocks
same quick actions style
same layout order
same generic page title
```

This creates a weak user experience.

Emergency should not feel like Pharmacy.

Pharmacy should not feel like Records.

Finance should not feel like Radiology.

Laboratory and X-Ray may share a diagnostics family, but they must still have different dashboard identity and department-specific content.

Fix this by creating dashboard layout personalities.

Do not only change colors.

Each department type must have:

```text
unique dashboard name
unique hero wording
actual department name personalisation
unique layout family
unique primary visual emphasis
unique metric order
unique main work area
unique side panels
department-type-specific empty states
department-type-specific icons
department-specific menu heading
department-specific quick actions where useful
```

---

## 3. Dashboard Naming Strategy

Dashboard titles must come from the department type.

Add translated dashboard names for all 19 department types.

Required dashboard names:

```text
Consultation Dashboard
Emergency Dashboard
Investigation Dashboard
Radiology Dashboard
Procedure Dashboard
Theatre Dashboard
Treatment Dashboard
Nursing Dashboard
Pharmacy Dashboard
Inpatient Dashboard
Maternity Dashboard
Blood Bank Dashboard
Mortuary Dashboard
Ambulance Dashboard
Records Dashboard
Finance Dashboard
Stores Dashboard
Support Dashboard
Administrative Dashboard
```

The page subtitle must include the actual department name and department type.

Examples:

```text
Laboratory Department · Investigation
X-Ray Unit · Radiology
Main Pharmacy · Pharmacy
Emergency / Casualty · Emergency
Billing Office · Finance
```

Do not use one generic “My Dashboard” title for all department dashboards.

Do not hardcode English dashboard names in Blade.

Use lang files.

---

## 4. Department Name Personalisation

Every department dashboard must feel like it belongs to the actual department.

The rule is:

```text
Department Type = dashboard design/personality
Department Name = dashboard identity/personalisation
Department ID = dashboard data scope
```

Every department dashboard must show:

```text
department-type dashboard name
actual department name
department type label
current department badge
scoped-to-department message
department-specific menu heading
department-specific quick action labels where useful
```

Examples:

```text
Radiology Dashboard
X-Ray Unit · Radiology
Welcome to X-Ray Unit
Showing metrics scoped to X-Ray Unit only
X-Ray Unit Workbench
```

```text
Investigation Dashboard
Laboratory Department · Investigation
Welcome to Laboratory Department
Showing metrics scoped to Laboratory Department only
Laboratory Department Workbench
```

```text
Pharmacy Dashboard
Main Pharmacy · Pharmacy
Welcome to Main Pharmacy
Showing pharmacy data scoped to Main Pharmacy only
Main Pharmacy Operations
```

```text
Emergency Dashboard
Emergency / Casualty · Emergency
Welcome to Emergency / Casualty
Showing emergency activity scoped to Emergency / Casualty only
Emergency / Casualty Command Center
```

Avoid overloading every tiny label with the department name.

The department name must be very visible in:

```text
hero
subtitle
scope badge
menu heading
major card titles where useful
```

---

## 5. Hero Section Personalisation

Update the hero section so it does not use generic copy only.

The hero must include:

```text
dashboard name from department type
department name from current department
department type label
logged-in user name
date
current scope message
theme icon
switcher if user has multiple departments
global preview badge when applicable
```

Suggested hero display for normal department user:

```text
Emergency Dashboard
Emergency / Casualty · Emergency

Welcome, Dr. Mensah
You are viewing Emergency / Casualty data only.
```

Suggested hero display for Laboratory user:

```text
Investigation Dashboard
Laboratory Department · Investigation

Welcome, Ama
You are viewing Laboratory Department data only.
```

Suggested hero display for X-Ray user:

```text
Radiology Dashboard
X-Ray Unit · Radiology

Welcome, Kofi
You are viewing X-Ray Unit data only.
```

For users without a department:

```text
Use role/global fallback title.
Show clear no-department assigned message.
Do not pretend the dashboard is scoped.
```

For admin/global preview:

```text
Show Global Preview or Viewing as {Department Name}.
Make it visually clear this is preview/global mode.
```

---

## 6. KPI and Card Personalisation

Where useful, cards should reference the department name.

Examples:

```text
Pending requests in Laboratory Department
X-Ray Unit imaging queue
Main Pharmacy low stock
Emergency / Casualty active cases
Billing Office collections
Stores Department stock requests
```

Rules:

```text
Use department name on major card titles or subtitles.
Avoid noisy repetition on every small metric.
Do not hardcode English labels.
All labels must be localised.
```

For ordinary users, every card must remain scoped to the current department ID.

For switched-context users, every card must be scoped to the selected current department ID.

For admin/global preview, wider scope is allowed only with explicit permission.

---

## 7. Dashboard Layout Families

Create layout families, not 19 fully duplicated dashboards.

Recommended layout families:

```text
clinical_queue
emergency_command
diagnostic_workbench
imaging_workbench
surgery_board
ward_board
dispensing_stock
finance_control
stores_inventory
records_office
generic_department
```

Map department types:

```text
consultation => clinical_queue
emergency => emergency_command
investigation => diagnostic_workbench
radiology => imaging_workbench
procedure => clinical_queue or surgery_board_light
theatre => surgery_board
treatment => clinical_queue
nursing => ward_board
pharmacy => dispensing_stock
inpatient => ward_board
maternity => ward_board with maternity emphasis
blood_bank => diagnostic_workbench with blood-bank emphasis
mortuary => generic_department with mortuary emphasis
ambulance => emergency_command with ambulance emphasis
records => records_office
finance => finance_control
stores => stores_inventory
support => generic_department
administrative => generic_department or admin_control
```

The layout family controls:

```text
card order
main content area
right sidebar content
chart style
table/list style
quick actions
empty-state language
visual rhythm
```

---

## 8. Department Dashboard Layout Registry

Create or extend:

```text
DepartmentDashboardLayoutRegistry
```

or extend the existing department dashboard registry cleanly.

Each department type should define:

```text
dashboard_name_key
layout_family
theme_key
hero_variant
primary_cards
secondary_cards
main_panel
side_panels
chart_panels
quick_actions
empty_state_key
menu_heading_key
personalisation_variant
```

Example for investigation:

```php
'investigation' => [
    'name_key' => 'departments.dashboards.investigation.name',
    'layout_family' => 'diagnostic_workbench',
    'hero_variant' => 'diagnostic',
    'primary_cards' => [
        'pending_requests',
        'samples_awaiting_acceptance',
        'completed_results_today',
        'services_count',
    ],
    'main_panel' => 'requests_queue',
    'side_panels' => ['services', 'stock_usage', 'recent_results'],
    'menu_heading_key' => 'departments.menu_profiles.investigation.heading',
]
```

Example for radiology:

```php
'radiology' => [
    'name_key' => 'departments.dashboards.radiology.name',
    'layout_family' => 'imaging_workbench',
    'hero_variant' => 'imaging',
    'primary_cards' => [
        'pending_imaging',
        'scheduled_imaging',
        'completed_imaging_today',
        'services_count',
    ],
    'main_panel' => 'imaging_queue',
    'side_panels' => ['radiology_services', 'consumables', 'recent_imaging'],
    'menu_heading_key' => 'departments.menu_profiles.radiology.heading',
]
```

Radiology must not look exactly like investigation.

Laboratory and X-Ray can share some diagnostic logic, but the layout family, naming, labels, and data scope must differ where appropriate.

---

## 9. Distinct Dashboard Personalities

### Consultation Dashboard

Style:

```text
queue-first
patient-flow focused
doctor/clinic action buttons
```

Main sections:

```text
Today’s consultation queue
Waiting / consulting / completed
Follow-ups due
Recent consultations
Clinical quick actions
```

### Emergency Dashboard

Style:

```text
command-center
alert-first
triage-focused
urgent visual priority
```

Main sections:

```text
active emergency cases
triage status
critical/urgent counters
emergency queue
rapid actions
emergency alerts
```

### Investigation Dashboard

Style:

```text
laboratory workbench
sample/results focused
technical workflow layout
```

Main sections:

```text
pending lab requests
samples awaiting acceptance
completed results
lab services
lab stock usage
recent results
```

### Radiology Dashboard

Style:

```text
imaging workbench
schedule/results focused
visual scan/imaging identity
```

Main sections:

```text
pending imaging requests
scheduled imaging
completed imaging
radiology services
radiology consumables
recent imaging
```

### Procedure Dashboard

Style:

```text
minor procedure board
task/procedure focused
```

Main sections:

```text
pending procedures
completed procedures
procedure consumables
procedure services
patient procedure queue
```

### Theatre Dashboard

Style:

```text
surgery board
timeline/schedule focused
pre-op to post-op flow
```

Main sections:

```text
scheduled surgeries
pre-op
in-progress
post-op
theatre consumables
surgery schedule
```

### Treatment Dashboard

Style:

```text
treatment room board
care-task focused
```

Main sections:

```text
pending treatments
completed treatments
treatment tasks
treatment services
treatment room usage
```

### Nursing Dashboard

Style:

```text
ward care board
observations/vitals focused
```

Main sections:

```text
nursing tasks
vitals due
vitals recorded
ward observations
patient care queue
```

### Pharmacy Dashboard

Style:

```text
dispensing + stock control
prescription queue focused
```

Main sections:

```text
pending prescriptions
dispensed today
low stock
near expiry
pharmacy products
pharmacy stock usage
```

### Inpatient Dashboard

Style:

```text
ward board
bed/admission focused
```

Main sections:

```text
active admissions
bed occupancy
discharges pending
ward patients
vitals overview
```

### Maternity Dashboard

Style:

```text
maternity ward board
antenatal/delivery/postnatal focused
```

Main sections:

```text
antenatal visits
delivery cases
postnatal follow-ups
maternity admissions
maternity ward activity
```

### Blood Bank Dashboard

Style:

```text
blood inventory and request board
availability/safety focused
```

Main sections:

```text
available blood units
reserved blood units
near expiry units
pending crossmatches
blood requests
```

### Mortuary Dashboard

Style:

```text
controlled registry board
storage/release focused
```

Main sections:

```text
active mortuary cases
storage occupancy
pending releases
mortuary records
```

### Ambulance Dashboard

Style:

```text
dispatch command board
movement/transport focused
```

Main sections:

```text
active dispatches
transport requests
completed transports
ambulance availability
```

### Records Dashboard

Style:

```text
records office
folder/patient-file focused
```

Main sections:

```text
new records
folder requests
merge requests
archive activity
patient file actions
```

### Finance Dashboard

Style:

```text
control-room
money/reconciliation focused
```

Main sections:

```text
collections
unpaid invoices
AR aging
claims
cashier sessions
credit notes/write-offs where permitted
```

### Stores Dashboard

Style:

```text
inventory operations
stock movement focused
```

Main sections:

```text
stock requests
stock issues
low stock
purchase requests
supplier activity
store usage
```

### Support Dashboard

Style:

```text
support operations
requests/task focused
```

Main sections:

```text
support requests
maintenance tasks
general activity
assets placeholder if available
```

### Administrative Dashboard

Style:

```text
admin control
system/user/department focused
```

Main sections:

```text
users
departments
settings
HR/admin activity
system overview
```

### Generic Department Dashboard

Style:

```text
simple department profile
```

Main sections:

```text
department profile
assigned users
services count
recent activity
quick links
```

---

## 10. Menu Management Correction

The menu must be managed through department menu profiles, not hardcoded Blade.

Current rule remains:

```text
Permissions/modules filter first.
Department menu profile only reorders and enriches allowed sections.
```

Now improve menu identity and department personalisation.

Each department type profile should define:

```text
dashboard label
department-personalised menu heading
primary section names
quick links
department-scoped links
optional badges
menu group heading
```

Examples:

### Investigation / Laboratory

```text
Menu heading: Laboratory Department Workbench
Dashboard: Investigation Dashboard
Quick links:
- Pending Requests
- Sample Acceptance
- Results Entry
- Laboratory Services
- Laboratory Stock Usage
```

### Radiology

```text
Menu heading: X-Ray Unit Workbench
Dashboard: Radiology Dashboard
Quick links:
- Imaging Requests
- Scheduled Imaging
- Imaging Results
- Radiology Services
- Radiology Stock Usage
```

### Pharmacy

```text
Menu heading: Main Pharmacy Operations
Dashboard: Pharmacy Dashboard
Quick links:
- Dispensing Queue
- Prescriptions
- Products
- Low Stock
- Near Expiry
```

### Emergency

```text
Menu heading: Emergency / Casualty Command Center
Dashboard: Emergency Dashboard
Quick links:
- Active Emergency Cases
- Triage
- Emergency Queue
- Rapid Billing
- Emergency Stock Usage
```

Rules:

```text
Menu links must include department_id/current scope where the destination supports it.
Missing routes must be skipped.
User permissions/modules must still be checked.
Department type must not grant access.
Department name personalisation must not grant access.
```

---

## 11. Actual Department Scope

All dashboard panels and menu drilldowns must be scoped to actual department_id.

For ordinary users:

```text
current department_id only
```

For switched context users:

```text
selected current_department_id only
```

For Admin/Super Admin/global preview:

```text
allow wider scope only with explicit permission
```

Examples:

```text
Laboratory user must not see X-Ray services.
X-Ray user must not see Laboratory services.
Main Pharmacy user must not see Stores stock unless permitted.
Finance user must not see clinical lists unless permitted.
```

Both Laboratory and X-Ray may share diagnostic-related code, but their data scope must differ.

---

## 12. Blade Refactor

Refactor:

```text
resources/views/admin/dashboards/department/show.blade.php
```

so it switches layout family, not just theme.

Suggested layout partials:

```text
resources/views/admin/dashboards/department/partials/layouts/clinical-queue.blade.php
resources/views/admin/dashboards/department/partials/layouts/emergency-command.blade.php
resources/views/admin/dashboards/department/partials/layouts/diagnostic-workbench.blade.php
resources/views/admin/dashboards/department/partials/layouts/imaging-workbench.blade.php
resources/views/admin/dashboards/department/partials/layouts/surgery-board.blade.php
resources/views/admin/dashboards/department/partials/layouts/ward-board.blade.php
resources/views/admin/dashboards/department/partials/layouts/dispensing-stock.blade.php
resources/views/admin/dashboards/department/partials/layouts/finance-control.blade.php
resources/views/admin/dashboards/department/partials/layouts/stores-inventory.blade.php
resources/views/admin/dashboards/department/partials/layouts/records-office.blade.php
resources/views/admin/dashboards/department/partials/layouts/generic-department.blade.php
```

Keep common components:

```text
hero
kpi-card
mini-kpi-card
chart-card
work-queue-card
quick-actions
services-card
stock-usage-card
empty-card
restricted-card
unavailable-card
```

Do not duplicate low-level card markup everywhere.

The low-level card components should remain reusable.

The layout families should decide placement, emphasis, and section order.

---

## 13. Layout Family Differences

At minimum, make these visibly different:

```text
consultation
emergency
investigation
radiology
theatre
pharmacy
inpatient / nursing / maternity
finance
stores
records
generic
```

Examples:

```text
Emergency:
alert strip, priority cards, queue first, rapid actions side rail

Investigation:
sample/request pipeline, results table, service/stock cards in side column

Radiology:
schedule board, imaging request list, scan-result emphasis, different iconography

Pharmacy:
dispensing queue, stock alerts, near-expiry, prescription actions

Finance:
collections/AR/claims cards, money trend, reconciliation table

Stores:
stock request/issue cards, low-stock table, procurement panel

Records:
folder/request list, merge requests, archives, patient-file actions
```

Do not let every type render the exact same sequence:

```text
hero → 4 KPI cards → chart → services → stock → activity
```

That sequence can exist as fallback only.

---

## 14. Dashboard Data Payload

Update `DepartmentDashboardDataService` or layout payload builder so each layout receives the data it needs.

Payload should include:

```text
context
theme
layout
dashboard_name
department_name
department_type_label
scope_message
primary_cards
secondary_cards
main_panel
side_panels
chart_panels
quick_actions
services
stock_usage
activities
empty_states
restricted_states
```

The dashboard name should come from department type.

The department name should come from the current department.

The scope message should clearly say what department data is being viewed.

---

## 15. Department-Specific Menu Headings

Extend `DepartmentMenuProfileService` so it can return a menu heading.

Suggested method:

```php
public function headingForContext(DepartmentContext $context): string
```

or equivalent.

It should produce labels like:

```text
Laboratory Department Workbench
X-Ray Unit Workbench
Emergency / Casualty Command Center
Main Pharmacy Operations
Billing Office Control Room
Stores Department Inventory
Records Office
```

Use translation templates and inject department name.

Examples:

```php
__('departments.menu_profiles.workbench', ['department' => $department->name])
__('departments.menu_profiles.command_center', ['department' => $department->name])
__('departments.menu_profiles.operations', ['department' => $department->name])
__('departments.menu_profiles.control_room', ['department' => $department->name])
```

Do not hardcode English in service or Blade.

---

## 16. Localisation

Extend:

```text
lang/en/departments.php
lang/fr/departments.php
lang/en/dashboards.php
lang/fr/dashboards.php
lang/en/menu.php
lang/fr/menu.php
lang/en/common.php
lang/fr/common.php
```

Add keys for:

```text
dashboard names
dashboard subtitles
layout family labels
menu profile headings
quick action labels
department-specific empty states
department-specific section titles
scope messages
personalised welcome messages
```

Required keys include:

```text
departments.dashboards.consultation.name
departments.dashboards.emergency.name
departments.dashboards.investigation.name
departments.dashboards.radiology.name
departments.dashboards.procedure.name
departments.dashboards.theatre.name
departments.dashboards.treatment.name
departments.dashboards.nursing.name
departments.dashboards.pharmacy.name
departments.dashboards.inpatient.name
departments.dashboards.maternity.name
departments.dashboards.blood_bank.name
departments.dashboards.mortuary.name
departments.dashboards.ambulance.name
departments.dashboards.records.name
departments.dashboards.finance.name
departments.dashboards.stores.name
departments.dashboards.support.name
departments.dashboards.administrative.name

departments.dashboard.subtitle
departments.dashboard.welcome_to_department
departments.dashboard.scoped_to_department_name
departments.dashboard.viewing_department_data_only
departments.dashboard.viewing_as_department
departments.dashboard.global_preview_mode
departments.dashboard.no_department_assigned_dashboard

departments.menu_profiles.workbench
departments.menu_profiles.command_center
departments.menu_profiles.operations
departments.menu_profiles.control_room
departments.menu_profiles.inventory
departments.menu_profiles.records_office
departments.menu_profiles.generic

departments.sections.samples
departments.sections.imaging_schedule
departments.sections.dispensing_queue
departments.sections.bed_occupancy
departments.sections.triage_status
departments.sections.surgery_schedule
departments.sections.stock_movements
departments.sections.folder_requests
departments.sections.cashier_sessions
```

Maintain EN/FR parity.

Run:

```bash
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
```

Required:

```text
Active runtime candidates: 0
EN/FR parity OK
```

---

## 17. Tests To Add

Add focused tests only.

Required tests:

```text
all 19 department types have translated dashboard names
all 19 department types resolve a layout family
consultation uses clinical_queue layout
emergency uses emergency_command layout
investigation uses diagnostic_workbench layout
radiology uses imaging_workbench layout
theatre uses surgery_board layout
pharmacy uses dispensing_stock layout
finance uses finance_control layout
stores uses stores_inventory layout
records uses records_office layout
generic/support/mortuary fallback safely
dashboard title is department-type specific
dashboard subtitle includes actual department name
dashboard hero shows actual department name
scope badge shows current department name
lab user sees Investigation Dashboard and Laboratory Department identity
radiology user sees Radiology Dashboard and X-Ray/Radiology department identity
lab user does not see radiology services
radiology user does not see laboratory services
menu heading includes department name
menu heading changes by department type
menu links remain permission filtered
menu links remain module filtered
missing quick-link routes are skipped
admin/global preview shows preview badge instead of normal scoped message
user with no department gets safe fallback title/message
view cache compiles for every layout family
localisation audit remains 0 active candidates
```

Allowed focused command:

```bash
php artisan test tests/Feature/Departments/DepartmentDashboardDesignDifferentiationTest.php
```

Also run:

```bash
php artisan test tests/Feature/Departments
php artisan test tests/Feature/DepartmentDashboardTest.php
```

Do not run the wide full suite unless explicitly instructed.

---

## 18. Verification

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

Also lint changed PHP files:

```bash
find app database routes lang resources/views tests -name "*.php" -print0 | xargs -0 -n1 php -l
```

Do not apply department backfill.

Do not run the wide full suite.

---

## 19. Documentation

Create:

```text
docs/DEPARTMENT_DASHBOARD_UI_PHASE_8_1_DESIGN_DIFFERENTIATION_REPORT.md
```

Include:

```text
summary
problem fixed
dashboard naming strategy
department name personalisation strategy
layout family registry
department type to layout family mapping
distinct dashboard personalities
menu management strategy
department-scoped data behavior
Blade layout family changes
localisation changes
tests added
focused tests run
minimal verification commands run
known limitations
next recommended phase
```

Known limitations should mention:

```text
some highly specialised department dashboards may still need deeper module-specific widgets later
drag-and-drop custom dashboard builder is deferred
predictive analytics are deferred
wide full-suite regression remains deferred unless explicitly run
department backfill apply was not run unless explicitly instructed
```

---

## 20. Acceptance Criteria

This correction phase is complete only when:

```text
department dashboards no longer all look alike
all 19 department types have translated dashboard names
all 19 department types resolve to layout families
major dashboard families have visibly different layouts
consultation, emergency, investigation, radiology, theatre, pharmacy, inpatient/ward, finance, stores, records feel different
dashboard title is based on department type
dashboard subtitle shows actual department name
dashboard hero welcomes the user to the actual department
scope badge shows actual department name
dashboard data remains scoped by department_id
lab user sees Laboratory Department identity and not Radiology services
radiology user sees Radiology identity and not Laboratory services
menu heading/profile changes by department type
menu heading includes actual department name where possible
menu remains permission/module safe
view cache compiles
localisation audit Active runtime candidates = 0
EN/FR parity passes
focused tests pass
documentation report is created
department backfill is not applied
wide full suite is not run
```

Proceed with Department Dashboard UI Phase 8.1 now.
