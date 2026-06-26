# UHMS Department Type Expansion — Phase 5: Department Metrics & Reports Registry

## Goal

Implement a department-type metrics and reporting registry for UHMS.

Phase 0 completed department type gap analysis.

Phase 1 completed department type canonicalisation and safety.

Phase 2 completed the department-aware dashboard registry.

Phase 3 completed department-aware menu profiles.

Phase 4 completed workflow routing cleanup and type groups.

Phase 5 must now provide department-type reporting and metrics so UHMS can produce meaningful operational statistics across the new department types.

This phase should answer questions like:

```text
How many patients passed through Emergency today?
How many lab/radiology requests are pending?
How many procedures/theatre cases were completed?
How many inpatient admissions are active?
How much revenue came from Pharmacy, Finance, Radiology, Theatre, etc.?
Which departments have pending tasks, requests, queue items, stock requests, billing items, or unresolved work?
```

Do not create a parallel reporting system.

Do not duplicate existing accounting, billing, dashboard, or report services.

Use a central registry so new department metrics can be added cleanly later.

---

## 1. Required Context

Read:

```text
docs/DEPARTMENT_TYPE_EXPANSION_GAP_ANALYSIS_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_1_CANONICALISATION_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_2_DASHBOARD_REGISTRY_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_3_MENU_PROFILES_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_4_WORKFLOW_ROUTING_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Inspect:

```text
app/Enums/DepartmentType.php
app/Models/Department.php
app/Services/Dashboard
app/Services/SidebarMenuBuilder.php
app/Services/Reports
app/Services/Accounting
app/Services/Billing
app/Services/VisitService.php
app/Services/Department
app/Http/Controllers/Admin/Reports
app/Http/Controllers/Admin/Dashboard
app/Http/Controllers/Admin/Departments
resources/views/admin/reports
resources/views/admin/dashboard
resources/views/admin/departments
routes/web.php
lang/en
lang/fr
tests
```

Search for existing department reports:

```text
department revenue
department expense
department report
department statistics
department dashboard
department_id
department_type
service_catalog.department_type
revenue by department
expense by department
```

Testing instruction:

```text
Do not run the wide full application test suite after this phase.
Run focused department metrics/reporting/localisation checks only.
The wide full-suite test remains deferred until the current implementation batch is complete.
```

---

## 2. Core Design

Create a central department metrics registry.

Recommended service:

```text
DepartmentMetricsRegistry
```

Recommended supporting services:

```text
DepartmentMetricsService
DepartmentTypeReportService
DepartmentOperationalSummaryService
DepartmentMetricExportService
```

If existing report/export services already exist, extend them instead of creating duplicates.

The registry should map:

```text
department_type
metric keys
metric labels
metric calculators
required permissions
required modules
fallback behavior
export support
```

The goal is a declarative structure, not scattered hardcoded report logic.

---

## 3. Supported Department Types

Support all 19 canonical types:

```php
CONSULTATION = 'consultation';
EMERGENCY = 'emergency';
INVESTIGATION = 'investigation';
RADIOLOGY = 'radiology';
PROCEDURE = 'procedure';
THEATRE = 'theatre';
TREATMENT = 'treatment';
NURSING = 'nursing';
PHARMACY = 'pharmacy';
INPATIENT = 'inpatient';
MATERNITY = 'maternity';
BLOOD_BANK = 'blood_bank';
MORTUARY = 'mortuary';
AMBULANCE = 'ambulance';
RECORDS = 'records';
FINANCE = 'finance';
STORES = 'stores';
SUPPORT = 'support';
ADMINISTRATIVE = 'administrative';
```

Every type must have at least a safe generic metrics profile.

Dedicated metrics should be added only where reliable data exists.

Do not fake numbers.

If a metric cannot be calculated safely, return an unavailable/empty state with a reason.

---

## 4. DepartmentMetricsRegistry

Create:

```text
App\Services\Department\DepartmentMetricsRegistry
```

Suggested structure:

```php
[
    'emergency' => [
        'label_key' => 'departments.metrics.emergency',
        'dashboard_key' => 'emergency',
        'metrics' => [
            'active_emergency_cases',
            'pending_triage',
            'emergency_visits_today',
            'emergency_revenue',
        ],
    ],
]
```

Each metric definition should support:

```text
key
label_key
description_key
calculator
permission optional
module optional
type
format
default_value
unavailable_reason
```

Metric formats:

```text
number
money
percentage
duration
status
table
chart_data
```

Do not introduce new chart libraries.

If charting exists, use existing tooling.

Otherwise return chart-ready data only.

---

## 5. Generic Metrics Profile

Create a safe generic profile for any department type.

Generic metrics:

```text
department_count
active_departments
assigned_users
services_count
today_activity_count if safely available
open_queue_items if safely available
revenue_today if user has billing/report permission
pending_tasks if safely available
```

Rules:

```text
If department has no services, show 0.
If activity source is unavailable, show unavailable.
If user lacks permission, hide restricted metric.
If module is disabled, hide module metric.
```

---

## 6. Metrics By Department Type

### consultation

Metrics:

```text
consultations_today
waiting_consultations
completed_consultations
followups_due
consultation_revenue
average_wait_time if available
```

### emergency

Metrics:

```text
active_emergency_cases
emergency_cases_today
pending_emergency_triage
emergency_sessions_today
emergency_revenue
emergency_unbilled_items if available
```

### investigation

Metrics:

```text
lab_requests_today
pending_lab_requests
samples_awaiting_acceptance
completed_lab_results
urgent_lab_requests
lab_revenue
```

### radiology

Metrics:

```text
radiology_requests_today
pending_radiology_requests
completed_radiology_results
scheduled_imaging
urgent_radiology_requests
radiology_revenue
```

If radiology workflow is still shared with investigation, use diagnostic-safe queries and document limitation.

### procedure

Metrics:

```text
minor_procedures_today
pending_procedures
completed_procedures
procedure_consumables_used
procedure_revenue
```

### theatre

Metrics:

```text
scheduled_surgeries
surgeries_today
preop_pending
postop_pending
theatre_consumables_used
theatre_revenue
```

### treatment

Metrics:

```text
treatment_tasks_today
pending_treatments
completed_treatments
treatment_revenue
```

### nursing

Metrics:

```text
nursing_tasks_pending
vitals_due
vitals_recorded_today
ward_observations
medication_tasks_placeholder
```

Do not expose medication administration data unless a reliable source exists.

### pharmacy

Metrics:

```text
prescriptions_pending
prescriptions_dispensed_today
pharmacy_sales_today
low_stock_items
out_of_stock_items
near_expiry_items
```

Respect stock-cost permissions.

### inpatient

Metrics:

```text
active_admissions
occupied_beds
available_beds
discharges_pending
inpatient_revenue
average_length_of_stay if available
```

### maternity

Metrics:

```text
antenatal_visits_today
maternity_admissions
delivery_cases
postnatal_followups
maternity_revenue
```

If dedicated maternity workflow does not exist, use admissions/visits safely and document limitation.

### blood_bank

Metrics:

```text
available_blood_units
reserved_blood_units
expired_units
near_expiry_units
pending_crossmatches
blood_requests_pending
```

### mortuary

Metrics:

```text
active_mortuary_cases
body_storage_occupancy
pending_releases
mortuary_revenue
```

If mortuary module is not available, generic/unavailable metrics only.

### ambulance

Metrics:

```text
ambulance_requests_today
active_transports
completed_transports
available_vehicles
ambulance_revenue
```

If ambulance module is not available, generic/unavailable metrics only.

### records

Metrics:

```text
new_patient_records_today
record_merge_requests
folder_requests_pending
archived_records_activity
```

### finance

Metrics:

```text
collections_today
unpaid_invoices
ar_aging_total
pending_claims
credit_notes_today
writeoffs_today
cashier_sessions_open
```

Respect financial permissions.

### stores

Metrics:

```text
stock_requests_pending
stock_issues_today
low_stock_items
pending_purchase_requests
supplier_payables_if_permitted
```

Respect stock-cost and procurement permissions.

### support

Metrics:

```text
support_requests_open
maintenance_requests_open
asset_issues_placeholder
general_support_activity
```

If no support module exists, generic/unavailable metrics only.

### administrative

Metrics:

```text
active_users
departments_count
pending_admin_tasks
hr_pending_items_if_available
system_activity
```

Respect admin permissions.

---

## 7. Department Type Report Screen

Add or extend report screen:

```text
Admin > Reports > Department Type Reports
```

Suggested route:

```text
admin.reports.department-types.index
```

or use existing reports route naming convention.

Filters:

```text
date_from
date_to
department_type
department_id
branch/facility if supported
module
status
include_unavailable
```

Report should show:

```text
summary cards
department type rollups
department-level drill-down
metric availability status
export buttons where permitted
```

Do not expose restricted clinical/financial/stock-cost data.

---

## 8. Department Drill-down

For each department type, allow drill-down to departments of that type.

Columns:

```text
department
department type
assigned users
services count
activity count
revenue if permitted
pending items if available
last activity if available
```

If user lacks permission for revenue, omit revenue.

If module disabled, omit module-specific metrics.

---

## 9. Export Support

Use existing export/report tooling.

Supported first:

```text
CSV
print view
PDF only if existing tooling already supports it
```

Do not introduce a new export library.

Exports must respect:

```text
permissions
modules
filters
date range
data visibility rules
```

Add permission if needed:

```text
reports.department_types.view
reports.department_types.export
```

Do not grant broadly to clinical roles unless appropriate.

---

## 10. Dashboard Integration

Do not rebuild dashboards.

But expose registry data so dashboards can use it later.

Add helper:

```text
DepartmentMetricsService::summaryForDepartmentType()
DepartmentMetricsService::summaryForDepartment()
```

DepartmentDashboardService may use these helpers for generic widgets if safe.

Do not rewrite all dashboard widgets in this phase unless needed.

---

## 11. Permission and Module Safety

Each metric must define access requirements.

Examples:

```text
finance collections_today:
permission: billing.payments.view or accounting.reports.view
module: billing or accounting_basic

pharmacy low_stock_items:
permission: inventory.view or pharmacy.view
module: pharmacy/inventory

stock cost values:
permission: inventory.costs.view or accounting.reports.view

clinical counts:
permission: related module view permission
```

Rules:

```text
Check permission before querying sensitive data.
Check module before querying module tables.
Unavailable metric should not crash if module table is absent.
Do not leak financial totals to users without permission.
Do not leak clinical details to finance/support users.
```

---

## 12. Metric Calculator Pattern

Create metric calculators as small classes or methods.

Suggested approaches:

```text
DepartmentMetricCalculatorInterface
```

Example:

```php
interface DepartmentMetricCalculatorInterface
{
    public function calculate(DepartmentMetricContext $context): DepartmentMetricResult;
}
```

Or keep simple closures/methods if project style prefers services.

Metric context should include:

```text
user
department_type
department_id nullable
date_from
date_to
filters
permissions
modules
```

Metric result should include:

```text
key
label
value
format
available
unavailable_reason
meta
```

Do not return raw query builders to views.

---

## 13. DepartmentMetrics Diagnostic Command

Add command:

```bash
php artisan departments:metrics-map
```

Output:

```text
department_type
metric_key
label
calculator
permission
module
available?
fallback?
```

The command must not modify data.

It should help confirm every department type has metrics.

---

## 14. Localisation

Extend:

```text
lang/en/departments.php
lang/fr/departments.php
lang/en/reports.php
lang/fr/reports.php
```

Add keys:

```text
department_type_reports
department_metrics
metrics_registry
metric_unavailable
metric_restricted
metric_module_disabled
department_type_rollup
department_drilldown
include_unavailable_metrics
active_departments
assigned_users
services_count
activity_count
revenue_today
pending_items
collections_today
unpaid_invoices
ar_aging_total
pending_claims
low_stock_items
out_of_stock_items
available_beds
occupied_beds
active_admissions
active_emergency_cases
pending_lab_requests
pending_radiology_requests
scheduled_surgeries
available_blood_units
active_mortuary_cases
ambulance_requests
```

Maintain EN/FR parity.

Run:

```bash
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
```

Active runtime candidates must remain:

```text
0
```

---

## 15. Tests To Add

Add focused tests only.

Required tests:

```text
all 19 department types have a metrics profile
generic metrics profile works for support/mortuary/ambulance
metric registry does not throw for any department type
metric service hides restricted finance metrics without permission
metric service hides stock-cost metrics without permission
module-disabled metric returns unavailable instead of crashing
department type report route is permission protected
department type report filters by date range
department type report filters by department type
department drill-down lists departments of selected type
CSV export requires export permission
CSV export respects selected filters
departments:metrics-map lists all 19 department types
EN/FR metric labels exist
```

Allowed focused command:

```bash
php artisan test tests/Feature/Departments/DepartmentMetricsRegistryPhase5Test.php
```

Also run any small focused reports tests if touched.

Do not run:

```bash
php artisan test
```

unless explicitly instructed.

---

## 16. Minimal Verification Commands

Run only:

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

## 17. Documentation

Create:

```text
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_5_METRICS_REPORTS_REPORT.md
```

Include:

```text
summary
metrics registry design
department type to metrics mapping
generic fallback metrics
metric permission/module safety
department type report route/screen
department drill-down behavior
export behavior
dashboard integration points
diagnostic command output
localisation changes
tests added
focused tests run
minimal verification commands run
known limitations
next recommended phase
```

Known limitations should mention:

```text
multi-department user context switcher is deferred
some department types use generic metrics until dedicated modules exist
department backfill apply was not run unless explicitly instructed
wide full-suite regression remains deferred
```

---

## 18. Acceptance Criteria

Phase 5 is complete only when:

```text
all 19 department types have metrics profiles
generic fallback metrics exist
metrics do not crash for unsupported modules
restricted metrics are hidden before sensitive queries run
department type report screen exists
department drill-down works
CSV/export support works where existing tooling permits
dashboard integration helper exists
departments:metrics-map command exists
EN/FR localisation parity is maintained
active runtime localisation candidates remain 0
route list works
view cache compiles
permissions audit is clean
documentation report is created
focused tests pass
full test suite is intentionally deferred
department backfill is not applied unless explicitly instructed
```

Proceed with Department Type Expansion Phase 5 now.
