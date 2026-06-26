# UHMS Department Type Expansion — Phase 3: Department-Aware Menu Profiles

## Goal

Implement department-aware menu profiles for UHMS.

Phase 0 completed department type gap analysis.

Phase 1 completed department type canonicalisation and safety.

Phase 2 completed the department-aware dashboard registry:

```text
DepartmentDashboardRegistry now maps department types to dashboard keys.
DepartmentDashboardResolver uses the registry.
Admin "view as" dashboard switcher is registry-driven.
All department types resolve safely.
Unknown preview keys fall back safely.
```

Phase 3 must now make the sidebar/menu experience department-aware.

The menu should adapt to the user's department type by prioritising, grouping, and surfacing the most relevant menu sections first.

Do not use department type as a security layer.

Permissions and modules remain the real access-control system.

---

## 1. Required Context

Read:

```text
docs/DEPARTMENT_TYPE_EXPANSION_GAP_ANALYSIS_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_1_CANONICALISATION_REPORT.md
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_2_DASHBOARD_REGISTRY_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Inspect:

```text
app/Enums/DepartmentType.php
app/Models/User.php
app/Models/Department.php
app/Services/SidebarMenuBuilder.php
app/Services/Dashboard/DepartmentDashboardRegistry.php
app/Services/Dashboard/DepartmentDashboardResolver.php
resources/views/layouts
resources/views/components
resources/views/partials
resources/views/admin
lang/en/menu.php
lang/fr/menu.php
lang/en/departments.php
lang/fr/departments.php
routes
tests
```

Important project behaviour to remember:

```text
Some Blade responses may be rewritten through ConvertBladeViewsToInertia middleware.
Avoid fragile assertViewHas HTTP tests where this middleware interferes.
Use status/assertSee/structure checks, or controller/service-level assertions when needed.
```

Testing instruction:

```text
Do not run the wide full application test suite after this phase.
Run only focused menu/department/localisation checks and minimal verification.
The wide full-suite test remains deferred until the current implementation batch is complete.
```

---

## 2. Core Design

Create a department-aware menu profile layer.

The menu system should be based on:

```text
enabled modules
permissions
roles
user department type
dashboard registry
menu profile registry
```

Security rule:

```text
Permissions and module middleware decide whether a route is accessible.
Department type only decides menu grouping, ordering, highlighting, and recommended shortcuts.
```

Do not hide a permitted route only because department mapping is missing.

Do not expose a route just because department type matches.

Do not bypass existing permission checks.

Do not move route/security logic into Blade.

---

## 3. Menu Profile Service

Create:

```text
DepartmentMenuProfileRegistry
```

or, if cleaner, extend the existing `SidebarMenuBuilder` through a separate helper:

```text
DepartmentMenuProfileService
```

Recommended responsibilities:

```text
resolve user's department type
resolve department menu profile
prioritise menu sections
inject department dashboard shortcut
inject department quick links
group relevant menu items
preserve existing permission/module filtering
provide fallback profile
provide admin/global profile
```

The service should be declarative, not scattered inside Blade.

Suggested structure:

```php
[
    'pharmacy' => [
        'primary_sections' => ['pharmacy', 'prescriptions', 'inventory'],
        'secondary_sections' => ['billing', 'patients', 'reports'],
        'dashboard_key' => 'pharmacy',
        'quick_links' => [
            'pharmacy.prescriptions.index',
            'pharmacy.dispensing.index',
            'inventory.stock-alerts.index',
        ],
    ],
]
```

---

## 4. Department Type Menu Profiles

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

Every type must resolve to a profile.

If a dedicated profile is not ready, use the generic profile safely.

---

## 5. Initial Profile Mapping

Use these initial menu priorities.

### consultation

Prioritise:

```text
My Dashboard
Visits
Consultations
Patients
Appointments
Investigations
Prescriptions
Reports
```

### emergency

Prioritise:

```text
My Dashboard
Emergency Cases
Triage
Visits
Patients
Procedures
Billing
Reports
```

### investigation

Prioritise:

```text
My Dashboard
Laboratory
Investigation Requests
Samples
Results
Patients
Billing
Reports
```

### radiology

Prioritise:

```text
My Dashboard
Radiology
Imaging Requests
Results
Patients
Billing
Reports
```

If radiology still shares investigation routes, group it under diagnostics but label it clearly.

### procedure

Prioritise:

```text
My Dashboard
Procedures
Treatment
Patients
Consumables
Billing
Reports
```

### theatre

Prioritise:

```text
My Dashboard
Theatre
Surgery Schedule
Pre-op
Anaesthesia
Post-op
Procedures
Consumables
Reports
```

### treatment

Prioritise:

```text
My Dashboard
Treatment
Visits
Patients
Procedures
Nursing Tasks
Billing
Reports
```

### nursing

Prioritise:

```text
My Dashboard
Admissions
Wards
Vitals
Nursing Tasks
Medication Administration
Patients
Reports
```

Only show medication-administration routes if they exist and the user has permission.

### pharmacy

Prioritise:

```text
My Dashboard
Pharmacy
Prescriptions
Dispensing
Products
Stock
Low Stock
Reports
```

### inpatient

Prioritise:

```text
My Dashboard
Admissions
Wards
Beds
Vitals
Discharges
Patients
Billing
Reports
```

### maternity

Prioritise:

```text
My Dashboard
Maternity
Antenatal
Delivery
Postnatal
Admissions
Patients
Reports
```

If dedicated maternity routes do not exist, use admissions/visits safely and document limitation.

### blood_bank

Prioritise:

```text
My Dashboard
Blood Bank
Blood Storage
Blood Requests
Crossmatch
Issue Blood
Reports
```

### mortuary

Prioritise:

```text
My Dashboard
Mortuary
Cases
Storage
Release
Billing
Reports
```

If mortuary routes do not exist, use generic profile and document limitation.

### ambulance

Prioritise:

```text
My Dashboard
Ambulance
Dispatch
Transport Requests
Vehicles
Billing
Reports
```

If ambulance routes do not exist, use generic profile and document limitation.

### records

Prioritise:

```text
My Dashboard
Patients
Patient Records
Folders
Record Merge
Appointments
Reports
```

### finance

Prioritise:

```text
My Dashboard
Billing & Collections
Invoices
Payments
Receivables
Claims
Accounting
Reports
```

Only show accounting sections when the user has accounting permissions and modules are enabled.

### stores

Prioritise:

```text
My Dashboard
Stores
Inventory
Stock Requests
Stock Issues
Procurement
Suppliers
Reports
```

### support

Prioritise:

```text
My Dashboard
Support
Requests
Maintenance
Assets
Reports
```

Use generic if dedicated support routes do not exist.

### administrative

Prioritise:

```text
My Dashboard
Administration
Users & Roles
Departments
Settings
HR
Reports
```

---

## 6. Existing SidebarMenuBuilder Integration

Inspect the current:

```text
SidebarMenuBuilder
```

Determine whether it returns:

```text
flat menu array
grouped menu sections
permission-filtered sections
module-filtered sections
role-based sections
```

Integrate department menu profiles without breaking existing output.

Recommended strategy:

```text
1. Build the existing menu exactly as before.
2. Apply permission/module filters exactly as before.
3. Pass the filtered menu to DepartmentMenuProfileService.
4. Reorder/prioritise visible sections based on department profile.
5. Add department dashboard shortcut where safe.
6. Return final menu.
```

Do not change route access logic.

Do not remove existing permission checks.

Do not make menu profile the source of permission truth.

---

## 7. Dashboard Shortcut

Add a safe department dashboard shortcut at the top of the menu when useful.

Example:

```text
My Department Dashboard
```

The shortcut should point to:

```text
/admin/my-dashboard
```

or the existing dashboard route.

The label should include the department type if helpful:

```text
Pharmacy Dashboard
Emergency Dashboard
Finance Dashboard
```

Use `DepartmentDashboardRegistry` for title/label where possible.

Rules:

```text
Only show shortcut if the route exists and user can access it.
Do not duplicate existing dashboard link if already present.
```

---

## 8. User Department Context

Current system has one department per user.

Implement:

```text
If user has one department:
    use that department type for menu profile.

If user has no department:
    use role/global fallback profile.

If user is Admin/Super Admin:
    use administrative/global profile by default,
    but allow all existing admin menu sections as before.

If department type is null:
    fallback safely.

If department type is unknown:
    fallback safely.
```

Do not implement multi-department switcher yet.

Document it as future.

---

## 9. Admin Preview / Debug

Add a diagnostic command:

```bash
php artisan departments:menu-profiles
```

Output:

```text
department_type
translated label
dashboard_key
primary sections
secondary sections
fallback?
missing route names
missing permissions
```

The command must not modify data.

Optionally add admin-only debug output in development, but not required.

---

## 10. Menu Profile Route Safety

Because profiles may list route names that may not exist yet, the system must handle missing routes safely.

Rules:

```text
If a configured route does not exist:
    skip it
    record it in diagnostic command
    do not crash sidebar rendering

If user lacks permission:
    do not show link

If module is disabled:
    do not show link

If route exists but menu item is not in current menu:
    do not invent access unless explicitly defined as a permitted quick link
```

---

## 11. Quick Links

Support quick links in the profile.

Quick links must include:

```text
label key
route name
permission optional
module optional
icon optional
```

Example:

```php
[
    'label_key' => 'menu.prescriptions',
    'route' => 'admin.pharmacy.prescriptions.index',
    'permission' => 'pharmacy.prescriptions.view',
    'module' => 'pharmacy',
    'icon' => 'ti-prescription',
]
```

Rules:

```text
Quick links are optional.
Quick links must be permission/module filtered.
Missing routes must be skipped.
Do not add too many quick links.
```

---

## 12. Localisation

Extend:

```text
lang/en/menu.php
lang/fr/menu.php
lang/en/departments.php
lang/fr/departments.php
```

Add labels:

```text
my_department_dashboard
department_menu_profile
menu_profile
primary_menu
secondary_menu
quick_links
department_shortcuts
consultation_menu
emergency_menu
investigation_menu
radiology_menu
procedure_menu
theatre_menu
treatment_menu
nursing_menu
pharmacy_menu
inpatient_menu
maternity_menu
blood_bank_menu
mortuary_menu
ambulance_menu
records_menu
finance_menu
stores_menu
support_menu
administrative_menu
generic_menu
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

## 13. Tests To Add

Add focused tests only.

Required tests:

```text
all 19 department types resolve to a menu profile
unknown department type falls back safely
null department type falls back safely
user with pharmacy department sees pharmacy-prioritised menu
user with finance department sees finance-prioritised menu
user with emergency department sees emergency-prioritised menu
user without department gets fallback menu
admin retains full/global menu behaviour
department menu profile does not bypass permissions
department menu profile does not bypass disabled modules
missing profile route is skipped without crashing
department dashboard shortcut appears when allowed
department dashboard shortcut is not duplicated
quick links are permission filtered
departments:menu-profiles command lists all 19 types
EN/FR menu labels exist
```

Allowed focused command:

```bash
php artisan test tests/Feature/Departments/DepartmentMenuProfilesPhase3Test.php
```

Do not run:

```bash
php artisan test
```

unless explicitly instructed.

Remember: because Blade responses may be converted by middleware, avoid fragile `assertViewHas` assertions where the middleware rewrites the response. Prefer service-level tests, controller-level tests, `assertStatus`, `assertSee`, or structure assertions.

---

## 14. Minimal Verification Commands

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

## 15. Documentation

Create:

```text
docs/DEPARTMENT_TYPE_EXPANSION_PHASE_3_MENU_PROFILES_REPORT.md
```

Include:

```text
summary
menu profile design
department type to menu profile mapping
SidebarMenuBuilder integration
dashboard shortcut behavior
quick link behavior
permission/module safety
admin/global fallback behavior
missing-route handling
localisation changes
diagnostic command output
tests added
minimal verification commands run
known limitations
next recommended phase
```

Known limitations should mention:

```text
workflow routing cleanup is Phase 4
department metrics/reporting registry is Phase 5
multi-department user context switcher is deferred
department backfill apply was not run unless explicitly instructed
```

---

## 16. Acceptance Criteria

Phase 3 is complete only when:

```text
all 19 department types resolve safely to menu profiles
SidebarMenuBuilder integrates menu profiles without breaking existing permission/module filtering
department type prioritises and groups menu items
department dashboard shortcut works where allowed
quick links are permission/module filtered
missing routes do not crash menu rendering
admin/global fallback still works
users without department still get a usable menu
department menu profile does not grant unauthorised access
department menu profile does not expose disabled modules
menu labels are localised EN/FR
departments:menu-profiles command exists
active runtime localisation candidates remain 0
EN/FR localisation parity is maintained
route list works
view cache compiles
permissions audit is clean
documentation report is created
full test suite is intentionally deferred
department backfill is not applied unless explicitly instructed
```

Proceed with Department Type Expansion Phase 3 now.
