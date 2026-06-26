You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Department Type Expansion — Phase 0 Gap Analysis & Adaptation Plan

## Goal

Perform a full-system gap analysis for UHMS department types.

The current department type list is incomplete and must be expanded so the system can understand hospital departments better and later build dashboards, menus, workflows, queues, reports, and permissions based on department types.

This is an analysis/planning phase only.

Do not implement the changes yet.

Do not run the full test suite.

Create a clear report showing:

```text
where department types are currently defined
where they are used
what will break if we change them
what database fields need updates
what seeders/enums/constants need updates
what dashboards and menus depend on them
how to migrate old department types safely
what implementation phases should follow
```

---

# 1. New Department Type List

The new proposed department type list is:

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

Old/current list likely includes:

```php
CONSULTATION = 'consultation';
INVESTIGATION = 'investigation';
PROCEDURE = 'procedure';
TREATMENT = 'treatment';
PHARMACY = 'pharmacy';
RADIOLOGY = 'radiology';
SUPPORT = 'support';
ADMINISTRATIVE = 'administrative';
```

The gap is that many real hospital operational departments are currently forced into generic types like `support`, `administrative`, `procedure`, or `treatment`.

---

# 2. Core Design Direction

UHMS must understand department behavior from the department record itself.

The system should eventually use:

```text
departments.department_type
```

as the first-level operational classification.

The system should not rely only on hardcoded module names, route names, or dashboard assumptions.

Department type should support:

```text
department-specific dashboards
department-aware menu sections
department-specific queue/session behavior
department-based service routing
department-based billing/service mapping
department-based reports
department-specific permissions
department-based statistics
department-specific operational widgets
```

But this prompt is only for the gap analysis and adaptation plan.

---

# 3. Important Rules

Do not implement now.

Do not change production data.

Do not rename existing values yet.

Do not delete old values.

Do not run the wide full test suite.

Do not create a parallel department system.

Do not hardcode dashboard logic directly inside Blade.

Do not hardcode menu visibility directly inside Blade.

Do not make clinical workflows depend on incomplete department mapping.

Do not break existing visits, consultations, pharmacy, theatre, emergency, billing, stock, admissions, or reports.

Use services/config/enums where appropriate.

---

# 4. Search Areas

Inspect the entire codebase for department type usage.

Search for:

```text
department_type
department type
DepartmentType
departmentTypes
department_types
consultation
investigation
procedure
treatment
pharmacy
radiology
support
administrative
departments
DepartmentSeeder
DepartmentTypeSeeder
department dashboard
department menu
```

Also inspect:

```text
app/Enums
app/Models
app/Services
app/Http/Controllers
database/migrations
database/seeders
resources/views
routes
lang/en
lang/fr
tests
config
```

---

# 5. Find Current Source of Truth

Identify where department types are currently defined.

Check whether they are defined in:

```text
PHP enum
model constants
config file
database lookup table
migration enum/string validation
form request validation
Seeder
Blade select options
JavaScript arrays
translation files
tests/factories
```

Report whether the system has one source of truth or multiple conflicting sources.

Recommended future direction:

```text
Use one canonical DepartmentType enum/service/config source.
Store values as strings in database.
Avoid database enum columns.
Expose translated labels through lang files.
```

---

# 6. Database Gap Analysis

Inspect department-related tables.

Look for:

```text
departments
services
department_services
visit_sessions
consultation_routes
queues
outpatient_sessions
emergency_cases
admissions
wards
beds
pharmacy
lab/investigations
radiology
theatre/procedures
billing
stock/store
users/staff assignments
roles/permissions
```

Answer:

```text
Which tables store department_id?
Which tables store department_type?
Which tables infer department type from module?
Which tables should be department-aware but are not?
Which records would need migration or backfill?
Which old department type values exist in seeders or demo data?
```

Do not alter schema in this phase.

---

# 7. New Type Meaning

For the gap report, document the operational meaning of each new type.

Use this interpretation:

```text
consultation:
OPD/doctor consultation rooms, specialist consultation, general clinical consultation.

emergency:
Emergency / casualty department, emergency triage, emergency sessions, emergency bays.

investigation:
Laboratory and diagnostic investigation requests/results that are not radiology.

radiology:
X-ray, ultrasound, CT, MRI, imaging department.

procedure:
Minor procedures and procedure services outside full theatre workflow.

theatre:
Operating theatre, surgery workflow, anaesthesia, pre-op, post-op, surgical scheduling.

treatment:
Treatment room, injections, wound dressing, physiotherapy-like treatment services where applicable.

nursing:
Nursing station, nursing tasks, MAR, observations, ward nursing workflows.

pharmacy:
Dispensing, prescription fulfilment, medicine sales, medication stock.

inpatient:
Admission, wards, beds, inpatient care, inpatient billing.

maternity:
Antenatal, delivery, postnatal, maternity-specific workflows.

blood_bank:
Blood donation, screening, storage, crossmatch, blood issue/transfusion support.

mortuary:
Mortuary registration, body storage, release, mortuary billing.

ambulance:
Ambulance dispatch, transport requests, ambulance billing.

records:
Medical records, folder management, patient records office, file merge, archives.

finance:
Cashier, billing, collections, insurance claims, accounting, sponsors, receivables.

stores:
General store, procurement, stock issue, inventory, non-pharmacy stores.

support:
Maintenance, biomedical, IT, security, laundry, housekeeping, CSSD, general support.

administrative:
HR, management, administration, settings, user management, governance.
```

Flag any ambiguity discovered in the current code.

---

# 8. Workflow Impact Analysis

For each type, identify whether UHMS already has matching workflows.

Produce a table:

```text
Department Type | Existing module/workflow found? | Current mapping | Gap | Suggested action
```

Expected examples:

```text
emergency:
Existing emergency module exists.
Gap: emergency may currently be hidden under consultation/treatment/support.
Action: add explicit department type and map Emergency/Casualty departments.

theatre:
Existing theatre/procedure workflow likely exists.
Gap: theatre may currently be mixed with procedure.
Action: separate theatre from procedure.

inpatient:
Admissions/wards/beds exist.
Gap: inpatient may not be a department type.
Action: map wards/admissions to inpatient.

blood_bank:
Blood workflows exist.
Gap: no explicit department type.
Action: add blood_bank.

finance:
Billing/accounting exists.
Gap: finance may be administrative/support.
Action: add finance.

stores:
Stock/procurement exists.
Gap: stores may be support/admin.
Action: add stores.

records:
Patient records/folder merge exists.
Gap: no explicit records type.
Action: add records.
```

---

# 9. Dashboard Impact Analysis

We want dashboards based on department types.

Analyse current dashboards:

```text
admin dashboard
doctor dashboard
nurse dashboard
reception dashboard
billing dashboard
pharmacy dashboard
lab dashboard
radiology dashboard
emergency dashboard
theatre dashboard
accounting dashboard
stock dashboard
admissions dashboard
```

Report:

```text
Which dashboards already exist?
Which dashboards are role-based only?
Which dashboards are module-based only?
Which dashboards can become department-type dashboards?
Which department types need new dashboards?
Which department types can share a generic dashboard at first?
```

Recommended future direction:

```text
Create DepartmentDashboardRegistry or DepartmentDashboardService.
Map department_type to dashboard widgets.
Do not hardcode dashboards in controllers.
```

Example future mapping:

```php
consultation => ConsultationDashboard
emergency => EmergencyDashboard
investigation => LabDashboard
radiology => RadiologyDashboard
procedure => ProcedureDashboard
theatre => TheatreDashboard
treatment => TreatmentDashboard
nursing => NursingDashboard
pharmacy => PharmacyDashboard
inpatient => InpatientDashboard
maternity => MaternityDashboard
blood_bank => BloodBankDashboard
mortuary => MortuaryDashboard
ambulance => AmbulanceDashboard
records => RecordsDashboard
finance => FinanceDashboard
stores => StoresDashboard
support => SupportDashboard
administrative => AdminDashboard
```

---

# 10. Menu Impact Analysis

We may customise menu lists based on department type.

Analyse current menu generation.

Find:

```text
SidebarMenuBuilder
menu config
role-based menu logic
module-based menu logic
permission-based menu logic
department-specific menu logic if any
```

Report:

```text
Is menu currently role-based?
Is menu module-based?
Is menu permission-based?
Does it know user department?
Can a user belong to multiple departments?
Can a department have a type?
Can menu visibility be filtered by department type safely?
```

Recommended future direction:

```text
Keep permission and module checks as the primary security layer.
Use department type only to organise/prioritise menus, not to replace permissions.
Add DepartmentMenuProfileService or extend SidebarMenuBuilder cleanly.
Do not hide permitted routes only because department mapping is missing.
```

Possible menu strategy:

```text
User role + permissions decide access.
Enabled modules decide availability.
User department type decides preferred/visible dashboard/menu grouping.
Super Admin/Admin can see all.
Clinical users see their department-type menu first.
Finance users see finance/billing/accounting first.
Stores users see procurement/stock first.
```

---

# 11. Service Routing Impact Analysis

Department type may affect service routing.

Inspect how UHMS routes:

```text
visit sessions
consultation queues
lab requests
radiology requests
procedures
theatre cases
pharmacy prescriptions
emergency sessions
admissions
service rendering
billing items
```

Report:

```text
Which workflows already use department?
Which use service type?
Which use module name?
Which use hardcoded department names?
Which should move to department_type?
```

Important:

```text
Do not replace service type with department type.
A service remains the billable item.
A department is the operational owner/location.
Department type helps route the service to the correct workflow/dashboard.
```

---

# 12. Billing Impact Analysis

Inspect billing and service configuration.

Find:

```text
services
service_categories
department_id on services
department_type on services if any
billing routing
invoice item source modules
cashier/payment workflows
insurance/sponsor billing
```

Report:

```text
Can a billable service be assigned to a department?
Can a billable service be assigned to a department type?
Does emergency billing depend on a hardcoded consultation service?
Does theatre/procedure billing know the difference between procedure and theatre?
Does radiology billing differ from lab/investigation?
Does pharmacy use product billing rather than service billing?
```

Recommended future direction:

```text
Services should map to departments or department types.
Emergency/Casualty consultation should be configured, not hardcoded.
Radiology should be separated from laboratory investigation.
Theatre should be separated from minor procedure.
Pharmacy should remain product/dispensing-aware.
Finance should not be a clinical billable service department unless needed for cashier work.
```

---

# 13. Permissions and User Department Impact

Inspect:

```text
users
staff
employees
roles
permissions
departments
department_user
staff department assignments
```

Answer:

```text
Can users be assigned to departments?
Can users have multiple departments?
Can department type affect dashboard?
Can department type affect menu priority?
Can department type affect queue visibility?
What should happen if user has no department?
What should happen if user belongs to multiple departments?
```

Suggested logic:

```text
If user has one department:
    use that department_type as dashboard/menu context.

If user has multiple departments:
    allow switching current department context.

If user is admin/super admin:
    allow all department dashboards.

If user has no department:
    fall back to role-based dashboard/menu.
```

---

# 14. Reporting and Statistics Impact

Analyse how department-based reporting currently works.

Find:

```text
department reports
department revenue
department expense
department service count
department visit count
department queue count
department stock issue
department staff performance
department dashboards
```

Report gaps for:

```text
consultation stats
emergency stats
investigation stats
radiology stats
procedure stats
theatre stats
treatment stats
nursing stats
pharmacy stats
inpatient stats
maternity stats
blood bank stats
mortuary stats
ambulance stats
records stats
finance stats
stores stats
support stats
administrative stats
```

Recommended future direction:

```text
Build DepartmentMetricsRegistry.
Each department type should provide widget definitions and metrics.
Start with generic metrics for types without dedicated modules.
```

---

# 15. Localisation Impact

New department type labels must be translated.

Report where labels should go:

```text
lang/en/departments.php
lang/fr/departments.php
or existing lang/en/app.php / lang/fr/app.php if project convention uses those
```

Required labels:

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
```

Also add dashboard/menu labels later.

Do not implement translation in this phase.

Only report exact files that need changes.

---

# 16. Migration and Backward Compatibility Plan

Produce a proposed safe migration/backfill plan.

The plan must include:

```text
how to add new department types without breaking old values
how to map existing departments to new department types
how to handle unknown/missing department types
how to update seeders
how to update validation
how to update tests/factories
how to preserve historical data
how to avoid destructive migrations
```

Recommended migration approach:

```text
1. Ensure department_type column is string, not DB enum.
2. Add canonical DepartmentType enum/config.
3. Add translations.
4. Update validation to use canonical values.
5. Update seeders.
6. Add mapping/backfill command with dry-run.
7. Backfill known departments by name/module/service mapping.
8. Leave unknown departments unchanged or classify as support/admin only with explicit review.
9. Add report for unmapped departments.
```

Backfill command proposal:

```bash
php artisan departments:backfill-types --dry-run
php artisan departments:backfill-types --apply
```

Command should output:

```text
department id
department name
old type
suggested new type
confidence
reason
action
```

Do not auto-apply low-confidence mappings.

---

# 17. Suggested Mapping Rules

Propose mapping rules but do not apply them.

Examples:

```text
Emergency / Casualty => emergency
OPD / Consultation / Consulting Room => consultation
Laboratory / Lab => investigation
Radiology / X-Ray / Ultrasound / Imaging => radiology
Theatre / Surgery / Operating Room => theatre
Procedure Room / Minor Procedure => procedure
Treatment Room / Dressing / Injection => treatment
Nursing Station / MAR / Ward Nursing => nursing
Pharmacy / Dispensary => pharmacy
Ward / Admission / Inpatient => inpatient
Maternity / Delivery / Antenatal / Postnatal => maternity
Blood Bank / Blood Storage => blood_bank
Mortuary => mortuary
Ambulance / Transport => ambulance
Records / Folder / Archive => records
Billing / Cashier / Accounts / Claims / Finance => finance
Stores / Procurement / Inventory / Warehouse => stores
Maintenance / IT / Laundry / Security / CSSD / Housekeeping => support
HR / Admin / Management / Settings => administrative
```

Report conflicts:

```text
Department name matches more than one type.
Department has services from multiple types.
Department has no obvious workflow.
Department currently has invalid/blank type.
```

---

# 18. Future Implementation Phases To Recommend

At the end of the report, recommend implementation phases.

Suggested:

```text
Phase 1 — Department Type Canonicalisation
- enum/config
- validation
- translations
- seeders
- safe migration/backfill command
- unmapped department report

Phase 2 — Department-Aware Dashboard Registry
- dashboard mapping by department type
- generic fallback widgets
- role/admin override
- current department context for multi-department users

Phase 3 — Department-Aware Menu Profiles
- menu grouping/prioritisation by department type
- keep permissions/modules as security
- department context switcher

Phase 4 — Workflow Routing Cleanup
- emergency/session routing
- radiology vs investigation
- theatre vs procedure
- inpatient/nursing/maternity/blood bank/mortuary/ambulance workflows

Phase 5 — Department Metrics and Reports
- metrics registry
- department-type reports
- dashboard widgets
- export/print
```

---

# 19. Minimal Verification Only

For this gap analysis phase, run only safe inspection commands.

Allowed:

```bash
php artisan route:list
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php artisan permissions:audit --strict
git diff --check
```

Also run grep/search commands as needed.

Do not run:

```bash
php artisan test
```

Do not run migrations.

Do not alter data.

Do not modify implementation code except to create the report if necessary.

---

# 20. Deliverable

Create:

```text
docs/DEPARTMENT_TYPE_EXPANSION_GAP_ANALYSIS_REPORT.md
```

The report must include:

```text
executive summary
current department type source of truth
current department type list found in code
new proposed department type list
files/classes/controllers/services/views using department types
database tables affected
seeders affected
validation affected
forms/views affected
dashboard impact
menu impact
workflow routing impact
billing impact
permissions/user-department impact
reporting/statistics impact
localisation impact
backward compatibility risks
proposed safe migration/backfill plan
suggested department mapping rules
unmapped/ambiguous department list if discoverable
recommended implementation phases
minimal verification commands run
```

---

# 21. Acceptance Criteria

This analysis phase is complete only when:

```text
all current department type definitions are identified
all direct department_type usages are listed
current source-of-truth problems are documented
database impact is documented
dashboard impact is documented
menu impact is documented
workflow routing impact is documented
billing impact is documented
permissions/user-department impact is documented
reporting impact is documented
localisation impact is documented
safe migration/backfill strategy is proposed
implementation phases are recommended
no production data is changed
no full test suite is run
report file is created
```

Proceed with the Department Type Expansion Gap Analysis now.
