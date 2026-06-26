You are working on UHMS — Ultimate Hospital Management System.

# UHMS Department Type Expansion — Phase 2: Department-Aware Dashboard Registry

## Goal

Implement a department-aware dashboard registry for UHMS.

Phase 0 completed the gap analysis.

Phase 1 completed department type canonicalisation and safety:

```text
DepartmentType now supports all 19 canonical values.
DepartmentType label/color/toVisitStatus/translatedLabel are safe.
DepartmentDashboardResolver no longer crashes on new types.
EN/FR department type labels exist.
Service catalog validation accepts the full enum.
DepartmentSeeder uses precise department types.
departments:backfill-types exists and defaults to dry-run.
service_catalog.department_type can be re-synced.
Full suite remains deferred.
```

Phase 2 must now make dashboards properly department-type aware.

Do not apply `departments:backfill-types --apply` in this phase unless explicitly instructed.

Do not rework workflow routing yet.

Do not customise sidebar menu profiles yet.

Do not run the wide full suite.

---

## 1. Required Context

Read:

```text
docs/DEPARTMENT_TYPE_EXPANSION_GAP_ANALYSIS_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_1_CANONICALISATION_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Inspect:

```text
app/Enums/DepartmentType.php
app/Models/User.php
app/Models/Department.php
app/Services/Dashboard/DepartmentDashboardResolver.php
app/Services/Dashboard/DepartmentDashboardService.php
app/Http/Controllers/Dashboard/DepartmentDashboardController.php
app/Services/SidebarMenuBuilder.php
resources/views/admin/dashboard
resources/views/dashboard
resources/views/admin/my-dashboard*
routes/web.php
lang/en
lang/fr
tests
```

Important testing instruction:

```text
Do not run the wide full application test suite after this phase.
Run only focused dashboard/department/localisation checks and minimal verification.
The wide full-suite test remains deferred until the current implementation batch is complete.
```

---

## 2. Canonical Department Types

The dashboard registry must support all 19 department types:

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

No dashboard request should crash for any of these values.

---

## 3. Core Design Rules

Dashboard access must remain secure through:

```text
roles
permissions
enabled modules
existing route middleware
```

Department type should control:

```text
dashboard context
dashboard widget set
dashboard labels
dashboard grouping
dashboard fallback
```

Department type must not replace permissions.

Rules:

```text
Do not expose financial data to clinical users without permission.
Do not expose clinical data to finance/support users without permission.
Do not hide admin/super-admin dashboards.
Do not hardcode dashboard cards directly in Blade.
Do not move complex dashboard logic into controllers.
Do not build a parallel dashboard system.
```

---

## 4. Department Dashboard Registry

Create or formalise:

```text
DepartmentDashboardRegistry
```

or extend the existing:

```text
DepartmentDashboardService
DepartmentDashboardResolver
```

The registry should map:

```text
department_type → dashboard_key
department_type → dashboard_title
department_type → dashboard_description
department_type → widget group definitions
department_type → fallback behavior
```

Suggested structure:

```php
[
    'consultation' => [
        'key' => 'consultation',
        'title_key' => 'departments.dashboards.consultation.title',
        'description_key' => 'departments.dashboards.consultation.description',
        'widgets' => ['visits_today', 'waiting_patients', 'completed_consultations'],
    ],
]
```

Keep it service/config based.

Do not scatter mapping across Blade files.

---

## 5. Dashboard Key Mapping

Use this initial mapping:

```text
consultation => consultation
emergency => emergency
investigation => investigation
radiology => investigation
procedure => theatre or generic_procedure
theatre => theatre
treatment => treatment or generic_clinical
nursing => nursing or admission
pharmacy => pharmacy
inpatient => admission
maternity => maternity or admission
blood_bank => blood_bank
mortuary => mortuary or generic
ambulance => ambulance or generic
records => reception
finance => accounting
stores => stock
support => support or generic
administrative => management
```

If dedicated builders do not exist yet, map to the nearest safe existing builder.

Required minimum:

```text
Every department type must resolve to a dashboard key.
Every dashboard key must build successfully.
Unknown/null department type must fall back safely.
```

Do not build all advanced widgets now if the source modules are not ready.

Use generic placeholders where needed.

---

## 6. Existing Dashboard Builders

Audit existing builders in:

```text
DepartmentDashboardService
```

Identify which are already available, likely:

```text
management
consultation
pharmacy
investigation
theatre
billing
stock
accounting
emergency
admission
blood_bank
claims
hr
reception
generic
```

Add missing lightweight builders only where useful:

```text
treatment
nursing
maternity
mortuary
ambulance
records
finance
stores
support
administrative
```

These builders can initially use generic widgets but must have correct labels and safe data.

Do not overbuild clinical workflows in this phase.

---

## 7. Generic Dashboard Fallback

Implement a strong generic department dashboard.

Generic dashboard should show safe widgets such as:

```text
department name
department type
assigned users count
today's visits/requests if safely available
open tasks if safely available
recent department activity if safely available
quick links permitted for user
```

If metrics are unavailable:

```text
show empty-state widget
do not crash
do not fake numbers
```

---

## 8. User Department Context

Current system has:

```text
users.department_id
```

One user belongs to one department.

Implement dashboard context logic:

```text
If user has a department:
    use user's department.type as department dashboard context.

If user has no department:
    use role-based dashboard fallback.

If user is Admin/Super Admin:
    allow role/global dashboard and optional department preview.

If department type is null/unknown:
    use role-based fallback or generic dashboard.
```

Do not implement multi-department switching yet unless the system already has a clean structure for it.

Document multi-department context switcher as future.

---

## 9. Admin Preview

If not already present, add or improve an admin-safe preview.

Suggested query parameter:

```text
/admin/my-dashboard?as=department_type
```

or:

```text
/admin/my-dashboard?department_type=pharmacy
```

Rules:

```text
Only Admin/Super Admin or users with dashboard preview permission can use this.
Preview must not grant access to data the user is not permitted to see.
Preview is for layout/widget testing, not permission bypass.
Invalid department_type falls back safely with a warning.
```

Add permission if needed:

```text
dashboard.department_preview
```

---

## 10. Widget Permission Safety

Each widget must define or check its required permission/module.

Example:

```php
[
    'key' => 'billing_today',
    'permission' => 'billing.view',
    'module' => 'billing',
]
```

Rules:

```text
If user lacks permission, hide widget or show restricted placeholder.
If module is disabled, hide widget or show module-disabled placeholder.
Do not query sensitive data before permission/module check.
```

This is especially important for:

```text
finance
billing
accounting
claims
clinical summaries
pharmacy stock cost
stores stock cost
```

---

## 11. Department Dashboard Widgets

Start with safe widget groups by type.

### consultation

```text
today's consultations
waiting patients
completed consultations
follow-ups due
```

### emergency

```text
active emergency cases
pending triage
emergency sessions
critical cases placeholder if available
```

### investigation

```text
pending lab requests
samples awaiting acceptance
completed results
urgent investigations
```

### radiology

```text
pending imaging requests
scheduled imaging
completed imaging
urgent imaging
```

If radiology has no separate workflow yet, use investigation-safe widgets and document limitation.

### procedure

```text
pending procedures
scheduled procedures
completed procedures
consumable usage if safe
```

### theatre

```text
scheduled surgeries
pre-op pending
in-progress surgeries
post-op pending
```

### treatment

```text
pending treatment tasks
completed treatments
open treatment sessions
```

### nursing

```text
ward observations due
nursing tasks
medication administration placeholder
```

### pharmacy

```text
pending prescriptions
dispensed today
low stock alerts
sales/dispensing summary where permitted
```

### inpatient

```text
current admissions
occupied beds
available beds
discharges pending
```

### maternity

```text
antenatal visits
delivery cases
postnatal follow-up
maternity admissions
```

Use existing admission/visit widgets if dedicated maternity workflow is not available.

### blood_bank

```text
available blood units
reserved blood units
expired/near-expiry units
pending crossmatch
```

### mortuary

```text
active mortuary cases
body storage occupancy
pending release
mortuary billing placeholder
```

If mortuary module is not available, generic dashboard with clear limitation.

### ambulance

```text
active ambulance requests
completed transports
vehicle availability placeholder
```

If ambulance module is not available, generic dashboard with clear limitation.

### records

```text
new patient records
record merge requests
folder requests
archives activity
```

### finance

```text
today's collections
unpaid invoices
AR aging summary
pending claims
```

Respect financial permissions.

### stores

```text
stock requests
pending issues
low stock
purchase requests
```

### support

```text
support tasks
maintenance requests placeholder
department activity
```

### administrative

```text
user/admin overview
HR/admin tasks
system activity
```

---

## 12. Dashboard Views

Use existing dashboard design/components.

Do not create a completely separate visual style.

If widget system exists, reuse it.

If not, create reusable dashboard partials:

```text
resources/views/admin/dashboard/partials/widget-card.blade.php
resources/views/admin/dashboard/partials/metric-card.blade.php
resources/views/admin/dashboard/partials/empty-widget.blade.php
```

Keep UI Bootstrap 5 + Tabler Icons.

No Tailwind.

No new frontend framework.

---

## 13. Localisation

Extend:

```text
lang/en/departments.php
lang/fr/departments.php
```

Add dashboard labels:

```php
'dashboards' => [
    'consultation' => [
        'title' => 'Consultation Dashboard',
        'description' => 'Overview of consultation activity.',
    ],
]
```

Required dashboard title keys for all 19 types:

```text
consultation
emergency
investigation
radiology
procedure
theatre
treatment
nursing
pharmacy
inpatient
maternity
blood_bank
mortuary
ambulance
records
finance
stores
support
administrative
generic
```

Also add common widget labels where used.

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

## 14. Dashboard Report / Diagnostics

Add a diagnostic command:

```bash
php artisan departments:dashboard-map
```

Output:

```text
department_type
translated label
dashboard_key
builder exists?
fallback?
module dependencies
permission notes
```

This helps confirm all 19 types are mapped.

The command must not modify data.

---

## 15. Tests To Add

Add focused tests only.

Required tests:

```text
all 19 department types resolve to a dashboard key
all 19 department dashboard keys build without exception
unknown department type falls back safely
null department type falls back safely
user with department gets matching department dashboard
user without department gets role/generic fallback
admin can preview a department type dashboard if permitted
non-admin cannot bypass permissions through preview
finance dashboard does not expose finance widgets without permission
pharmacy dashboard hides restricted stock-cost widgets without permission
radiology maps safely even if using investigation builder
maternity maps safely even if using admission builder
mortuary generic fallback does not crash
ambulance generic fallback does not crash
departments:dashboard-map lists all 19 types
EN/FR dashboard labels exist
```

Allowed focused command:

```bash
php artisan test tests/Feature/Departments/DepartmentDashboardRegistryPhase2Test.php
```

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

Do not run migrations unless absolutely necessary.

Do not apply department backfill.

Do not run the wide full suite.

---

## 17. Documentation

Create:

```text
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_2_DASHBOARD_REGISTRY_REPORT.md
```

Include:

```text
summary
dashboard registry design
department type to dashboard key mapping
new/updated services
new/updated views
admin preview behavior
widget permission behavior
generic fallback behavior
localisation changes
diagnostic command output
tests added
minimal verification commands run
known limitations
next recommended phase
```

Known limitations should mention:

```text
department-aware menu profiles are Phase 3
workflow routing cleanup is Phase 4
department metrics/reporting registry is Phase 5
backfill apply was not run unless explicitly instructed
```

---

## 18. Acceptance Criteria

Phase 2 is complete only when:

```text
all 19 department types resolve safely to dashboard keys
all dashboard keys build without exception
/admin/my-dashboard no longer risks crashing from department type
user department type controls dashboard context
role fallback still works
admin preview is permission controlled
generic fallback exists and is safe
widget permissions/modules are respected
no sensitive finance/clinical/stock-cost data leaks
dashboard labels are localised EN/FR
departments:dashboard-map command exists
active runtime localisation candidates remain 0
EN/FR localisation parity is maintained
route list works
view cache compiles
permissions audit is clean
documentation report is created
full test suite is intentionally deferred
department backfill is not applied unless explicitly instructed
```

Proceed with Department Type Expansion Phase 2 now.
