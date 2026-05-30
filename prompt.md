````text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to run a deep analysis, correction, standardization, and documentation of the Roles, Permissions, and Modules system.

UHMS has grown significantly, and many system actions now require permissions but either:

- do not have permissions defined,
- have permissions defined but not enforced,
- are only protected in the UI and not backend,
- are protected in backend but missing in UI,
- use inconsistent permission names,
- are hidden incorrectly,
- are accessible to the wrong roles,
- or are not explained properly to admins.

We need to fix this extensively.

This task must produce:

1. A full Roles & Permissions gap analysis report.
2. A full Modules gap analysis report.
3. A corrected permission structure.
4. A corrected role-permission assignment structure.
5. Backend authorization enforcement.
6. Frontend/UI permission enforcement.
7. Module access enforcement.
8. Permission explanations in the UI.
9. Module explanations in the UI.
10. Updated seeders.
11. Updated documentation/reports.

Do not patch only one page.

Do a full system-wide analysis.

Do not break existing workflows.

---

# 1. Main Objective

Inspect the entire UHMS system and repair the Roles, Permissions, and Modules system so that every sensitive action is properly protected and explained.

The system must support proper access control for:

- Super Admin
- Hospital Admin
- Doctor
- Nurse
- Emergency Doctor
- Emergency Nurse
- Triage Nurse
- Pharmacist
- Pharmacy Manager
- Lab / Investigation Staff
- Radiology Staff
- Theatre Staff
- Surgeon
- Anaesthetist
- Ward Nurse
- Store Officer
- Procurement Officer
- Cashier
- Claims Officer
- Records Officer
- Receptionist
- Accountant
- Auditor
- Department Head
- System Administrator
- Any existing custom roles

Use existing roles if already defined.

Do not blindly replace role names if the system already uses a working structure.

---

# 2. Required Reports

Create these reports in the documentation folder:

```text
docs/ROLES_PERMISSIONS_GAP_ANALYSIS.md
docs/ROLES_PERMISSIONS_SOLUTION_REPORT.md
docs/MODULES_GAP_ANALYSIS.md
docs/MODULES_SOLUTION_REPORT.md
docs/ROLES_PERMISSIONS_REMAINING_RECOMMENDATIONS.md
````

If the project already has a docs convention, follow it.

---

# 3. ROLES_PERMISSIONS_GAP_ANALYSIS.md

This report must include:

* current role system found
* current permission system found
* current module system found
* role tables/models found
* permission tables/models found
* module tables/models found
* seeders found
* middleware found
* policies/gates found
* frontend permission checks found
* missing permissions
* unused permissions
* duplicate permissions
* inconsistent permission names
* permissions defined but not enforced
* actions protected in UI only
* actions protected in backend only
* actions missing both UI and backend protection
* modules missing permission mapping
* roles with excessive access
* roles with insufficient access
* dangerous actions without permission
* root causes
* risk level
* recommended correction plan

---

# 4. ROLES_PERMISSIONS_SOLUTION_REPORT.md

This report must include:

* permissions added
* permissions renamed or normalized
* roles updated
* middleware added/updated
* policies/gates added/updated
* controllers updated
* routes updated
* Vue/Inertia/Blade UI updated
* menu/sidebar updated
* seeders updated
* module access updates
* tests added
* how to verify
* files modified
* known limitations

---

# 5. MODULES_GAP_ANALYSIS.md

Analyze the module system.

Include:

* current module table/model
* current module seeder
* active/inactive modules
* core modules
* optional modules
* modules with missing routes
* modules with missing menu entries
* modules with missing permissions
* modules visible without access
* modules hidden even when user has access
* modules not enforcing backend access
* modules not described in UI
* modules that should be core and cannot be disabled
* modules that can be disabled safely
* module dependencies

---

# 6. MODULES_SOLUTION_REPORT.md

Include:

* module fixes made
* module permissions mapped
* module descriptions added
* module dependency rules added
* menu visibility fixed
* backend module guard fixed
* module UI updated
* seeders updated
* files modified
* tests/verification notes

---

# 7. Existing System Inspection

First inspect the current implementation.

Search for:

```text
Role
Roles
Permission
Permissions
Module
Modules
Gate
Policy
can
cannot
hasPermission
hasRole
middleware
permission:
role:
module:
is_core
is_active
sidebar
menu
navigation
auth
authorize
@can
v-if
permissions
```

Inspect:

```text
routes
controllers
middleware
policies
models
seeders
database migrations
Vue/Inertia pages
Blade views
sidebar/menu components
dashboard components
admin settings pages
```

Do not assume the current structure. Inspect before changing.

---

# 8. Preferred Authorization Architecture

Use the project’s existing authorization system if it is already working.

If the project uses Spatie Laravel Permission, follow Spatie conventions.

If the project uses custom roles/permissions, standardize it carefully.

The system must enforce permissions at both levels:

```text
Backend authorization = security
Frontend authorization = user experience
```

Frontend hiding buttons is not enough.

Backend must always enforce permission.

---

# 9. Permission Naming Convention

Use a consistent naming format.

Recommended:

```text
module.action
```

Examples:

```text
patients.view
patients.create
patients.update
patients.delete
patients.merge.execute

emergency.case.view
emergency.case.create
emergency.triage.perform
emergency.disposition.manage

billing.invoice.view
billing.invoice.create
billing.payment.record

stock.products.view
stock.products.create
stock.movements.adjust

theatre.cases.schedule
theatre.operative_note.create
```

Rules:

* use lowercase
* use dot notation
* module first
* action last
* avoid mixed formats like `View Patients`, `patient-view`, `can_view_patient`
* do not create duplicate names for the same action
* do not rename existing permissions without migration/compatibility if they are already used

If existing permission names are already widely used, create a normalization map and avoid breaking them.

---

# 10. Permission Categories

Organize permissions by module.

At minimum, review/create permissions for:

```text
dashboard
modules
users
roles
permissions
patients
patient_merge
visits
consultation
emergency
admission
triage
vitals
clinical_tasks
mar
pharmacy
billing
payments
claims
insurance
investigations
procedures
theatre
stock
products
procurement
purchase_orders
supplier_ledger
assets
notifications
logs
reports
settings
system
```

---

# 11. Critical Missing Permission Audit

Find every action in the system that creates, updates, deletes, approves, rejects, submits, verifies, pays, dispenses, administers, transfers, merges, exports, prints, or changes status.

Every such action must have a permission.

Audit actions including but not limited to:

## Patients

```text
patients.view
patients.create
patients.update
patients.delete
patients.mark_deceased
patients.restore
patients.documents.upload
patients.documents.view
patients.search
```

## Patient Merge

```text
patients.merge.view
patients.merge.create
patients.merge.preview
patients.merge.approve
patients.merge.execute
patients.merge.cancel
patients.merge.view_logs
patients.merge.confirm_identity
patients.merge.suggest_duplicates
```

## Visits

```text
visits.view
visits.create
visits.update
visits.cancel
visits.complete
visits.reopen
visits.transition
visits.create_while_admitted
visits.view_preview
visits.print_preview
```

## Consultation

```text
consultation.view
consultation.start
consultation.continue
consultation.complete
consultation.sessions.view
consultation.sessions.create
consultation.sessions.transfer
consultation.entries.create
consultation.entries.edit_own
consultation.entries.edit_any
consultation.entries.delete_own
consultation.entries.delete_any
consultation.entries.correct_completed
consultation.summary.view
consultation.summary.print
```

## Emergency

```text
emergency.board.view
emergency.case.view
emergency.case.create
emergency.case.update
emergency.case.cancel
emergency.session.manage
emergency.triage.perform
emergency.triage.override
emergency.vitals.record
emergency.vitals.view_graph
emergency.bay_team.manage
emergency.notes.create
emergency.notes.edit_own
emergency.notes.edit_any
emergency.medication.order
emergency.medication.administer
emergency.mar.view
emergency.investigation.request
emergency.procedure.request
emergency.consumables.use
emergency.billing.view
emergency.billing.manage
emergency.disposition.manage
emergency.transfer.admit
emergency.transfer.opd
emergency.transfer.theatre
emergency.refer
emergency.death.record
emergency.reports.view
```

## Admission

```text
admission.view
admission.create
admission.update
admission.assign_bed
admission.transfer_bed
admission.discharge
admission.cancel
admission.vitals.record
admission.notes.create
admission.medication.view
admission.mar.view
admission.reports.view
```

## MAR / Medication Administration

```text
mar.view
mar.print
medication_orders.view
medication_orders.manage
medication_orders.stop
medication_orders.hold
medication_administration.view
medication_administration.administer
medication_administration.hold
medication_administration.mark_missed
medication_administration.correct
medication_administration.view_reports
```

## Clinical Tasks

```text
clinical_tasks.view
clinical_tasks.create
clinical_tasks.update
clinical_tasks.complete
clinical_tasks.cancel
clinical_tasks.escalate
clinical_tasks.view_overdue
```

## Investigations

```text
investigations.view
investigations.catalogue.view
investigations.catalogue.manage
investigations.request
investigations.accept
investigations.reject
investigations.enter_result
investigations.verify_result
investigations.print_result
investigations.view_result
investigations.consumables.use
investigations.billing.manage
```

## Procedures

```text
procedures.view
procedures.catalogue.view
procedures.catalogue.manage
procedures.request
procedures.accept
procedures.reject
procedures.schedule
procedures.perform
procedures.enter_report
procedures.verify_report
procedures.print_report
procedures.consumables.use
procedures.billing.manage
```

## Theatre

```text
theatre.rooms.view
theatre.rooms.create
theatre.rooms.update
theatre.rooms.deactivate
theatre.rooms.manage_status
theatre.board.view
theatre.calendar.view
theatre.cases.view
theatre.cases.accept
theatre.cases.schedule
theatre.cases.reschedule
theatre.cases.cancel
theatre.cases.postpone
theatre.cases.complete
theatre.team.assign
theatre.preop.manage
theatre.anaesthesia.create
theatre.anaesthesia.edit_own
theatre.anaesthesia.edit_any
theatre.operative_note.create
theatre.operative_note.edit_own
theatre.operative_note.edit_any
theatre.recovery_note.create
theatre.recovery_note.edit_own
theatre.recovery_note.edit_any
theatre.consumables.use
theatre.billing.view
theatre.billing.manage
theatre.schedule.override
theatre.reports.view
```

## Pharmacy

```text
pharmacy.view
pharmacy.catalogue.view
pharmacy.prescriptions.view
pharmacy.prescriptions.bill
pharmacy.prescriptions.dispense
pharmacy.prescriptions.partial_dispense
pharmacy.dispensing.correct
pharmacy.stock.view
pharmacy.reports.view
```

## Billing / Payments

```text
billing.view
billing.invoice.view
billing.invoice.create
billing.invoice.update
billing.invoice.cancel
billing.invoice.print
billing.invoice.discount
billing.invoice.apply_manual_discount
billing.payment.view
billing.payment.record
billing.payment.reverse
billing.payment.refund
billing.reports.view
```

## Insurance / Claims

```text
insurance.view
insurance.create
insurance.update
insurance.delete
insurance.prices.manage
insurance.patient_insurance.manage

claims.view
claims.prepare
claims.review
claims.edit_prepared
claims.validate
claims.submit
claims.export
claims.approve
claims.reject
claims.record_payment
claims.view_reports
```

## Stock / Products

```text
stock.view
stock.products.view
stock.products.create
stock.products.update
stock.products.delete
stock.products.assign_departments
stock.balances.view
stock.movements.view
stock.movements.receive
stock.movements.transfer
stock.movements.adjust
stock.movements.return
stock.movements.purchase_return
stock.requisitions.view
stock.requisitions.create
stock.requisitions.approve
stock.requisitions.issue
stock.requisitions.receive
stock.reports.view
```

## Procurement / Supplier Ledger

```text
procurement.view
purchase_orders.view
purchase_orders.create
purchase_orders.update
purchase_orders.approve
purchase_orders.receive
purchase_orders.cancel
purchase_orders.print

supplier_ledger.view
supplier_ledger.payment.create
supplier_ledger.credit_note.create
supplier_ledger.debit_note.create
supplier_ledger.reports.view
```

## Assets

```text
assets.view
assets.create
assets.update
assets.assign
assets.transfer
assets.maintenance
assets.decommission
assets.reports.view
```

## Notifications

```text
notifications.view
notifications.mark_read
notifications.delete
notifications.manage
notifications.emergency.receive
notifications.admission.receive
notifications.mar.receive
notifications.investigation.receive
notifications.procedure.receive
notifications.pharmacy.receive
notifications.stock.receive
notifications.billing.receive
notifications.claims.receive
notifications.patient_merge.receive
```

## Logs

```text
logs.view
logs.view_clinical
logs.view_financial
logs.view_stock
logs.view_security
logs.export
logs.delete
logs.manage_retention
```

## Reports

```text
reports.view
reports.export
reports.financial
reports.clinical
reports.stock
reports.emergency
reports.admission
reports.theatre
reports.claims
```

## System / Settings

```text
settings.view
settings.update
modules.view
modules.enable
modules.disable
modules.configure
users.view
users.create
users.update
users.disable
users.reset_password
roles.view
roles.create
roles.update
roles.delete
permissions.view
permissions.assign
```

---

# 12. Backend Enforcement

Every protected route/controller action must enforce permission.

Use:

```php
$this->authorize(...)
```

or middleware:

```php
->middleware('permission:patients.view')
```

or project equivalent.

Do not rely only on menu visibility.

Do not rely only on frontend checks.

Audit:

* GET pages
* POST store actions
* PATCH/PUT update actions
* DELETE actions
* export/print actions
* approval actions
* status changes
* correction actions
* override actions

Every route with sensitive data/action must be protected.

---

# 13. Frontend/UI Enforcement

Update UI to respect permissions.

Buttons/actions should only show when the user has permission.

Examples:

* Add Patient button only if `patients.create`
* Edit Patient button only if `patients.update`
* Merge Patient only if `patients.merge.execute` or `patients.merge.create`
* Record Payment only if `billing.payment.record`
* Dispense Drug only if `pharmacy.prescriptions.dispense`
* Administer Medication only if `medication_administration.administer`
* Verify Result only if `investigations.verify_result`
* Schedule Theatre only if `theatre.cases.schedule`
* View Logs only if `logs.view`

If action is locked due to workflow status, show a clear locked reason.

Frontend should receive current user permissions globally.

For Inertia, share:

```php
'auth' => [
    'user' => ...,
    'roles' => ...,
    'permissions' => [...],
    'modules' => [...]
]
```

Do not expose sensitive data unnecessarily.

---

# 14. Sidebar/Menu Access

Update sidebar/menu logic.

A menu item should show only if:

1. module is active, and
2. user has at least one permission required for that module/menu.

Example:

Emergency menu appears only if:

```text
module emergency is active
AND user has emergency.board.view or emergency.case.view or emergency.case.create
```

Do not show empty modules.

Do not hide a module from Super Admin.

Core modules should always be protected but not disabled.

---

# 15. Module System Analysis

Inspect module system.

Each module should have:

```text
name
slug
description
is_core
is_active
icon
sort_order
permissions
dependencies
```

If description field is missing, add it or provide descriptions in config/UI.

Every module must explain:

* what it does
* who uses it
* what happens if disabled
* dependencies
* related permissions

---

# 16. Module Descriptions

Add clear descriptions for modules.

Examples:

## Patients

Manages patient registration, patient folders, demographic records, insurance records, next of kin, documents, deceased status, and patient folder merge.

## Visits

Manages outpatient visits, patient pathway/parcours, visit status flow, visit preview, and visit completion.

## Emergency

Manages emergency cases, emergency triage, emergency sessions, emergency beds/bays, emergency medications, emergency investigations, emergency procedures, emergency billing, and emergency disposition.

## Admission

Manages inpatient admission, wards, beds, inpatient vitals, inpatient medication administration, discharge, and admission billing.

## Consultation

Manages clinical consultation sessions, complaints, history of presenting complaint, examination, diagnosis, prescriptions, investigations, procedures, tasks, notes, and consultation summary.

## MAR

Manages medication administration schedules, nurse dose recording, medication reminders, MAR chart, missed/held/refused doses, and administration reports.

## Pharmacy

Manages prescriptions, drug billing before dispensing, dispensing, pharmacy catalogue, pharmacy stock, and pharmacy reports.

## Investigations

Manages investigation catalogue, investigation requests, result entry, result verification, report printing, and investigation consumables.

## Procedures / Theatre

Manages procedure requests, theatre rooms, theatre scheduling, pre-op checklist, anaesthesia notes, operative notes, recovery notes, procedure consumables, and theatre reports.

## Billing

Manages visit invoices, invoice items, patient payments, discounts, balances, receipts, and billing reports.

## Claims

Manages insurance claim preparation, clinical mirror review, NHIA/NHIS claims, claim submission, rejection, approval, and payment tracking.

## Stock

Manages products, stock locations, stock balances, stock movements, requisitions, transfers, adjustments, returns, and stock reports.

## Procurement

Manages purchase orders, purchase receipts, supplier invoices, and procurement workflow.

## Supplier Ledger

Manages supplier payments, credit notes, debit notes, and supplier transaction history.

## Assets

Manages hospital assets, assignment, transfers, maintenance, and decommissioning.

## Notifications

Manages in-app alerts, due/overdue reminders, task notifications, emergency alerts, stock alerts, and workflow alerts.

## Logs

Manages audit trail, activity logs, clinical logs, financial logs, stock logs, and security logs.

## Reports

Provides clinical, financial, stock, operational, emergency, admission, theatre, and claims reports.

## Settings

Manages hospital configuration, departments, service pricing, insurance pricing, modules, roles, permissions, and system preferences.

---

# 17. Module Dependencies

Define module dependencies.

Examples:

```text
Emergency depends on Patients, Visits, Billing, Stock, Notifications, Logs
Admission depends on Patients, Visits, Billing, Stock, MAR
MAR depends on Patients, Visits, Products, Clinical Tasks, Notifications
Pharmacy depends on Products, Stock, Billing
Investigations depends on Services, Billing, Stock
Theatre depends on Procedures, Billing, Stock
Claims depends on Billing, Insurance, Consultation
Stock depends on Products
Billing depends on Patients, Visits
```

If a module is disabled, dependent modules should warn or prevent disabling.

Core modules should not be disabled:

```text
Authentication
Users & Roles
Patients
Visits
Billing
Settings
Modules
```

Adapt to current system.

---

# 18. Module UI

Update Modules page.

For each module, display:

* module name
* slug
* icon
* description
* status active/inactive
* core yes/no
* dependencies
* permission count
* linked permissions
* users/roles impacted if disabled
* enable/disable action if allowed

When disabling module, show warning:

```text
Disabling Emergency will hide Emergency Board, Emergency Cases, Emergency MAR, and related menu items. Existing emergency records will remain available to authorized administrators.
```

Do not allow disabling core modules.

---

# 19. Permission UI

Update Permission management UI.

Each permission should show:

* permission name
* module
* description
* action type
* risk level
* assigned roles
* created/updated date

Add permission descriptions.

Example:

```text
patients.merge.execute
Allows user to permanently merge two patient folders into one active folder. High-risk permission.
```

Risk levels:

```text
LOW
NORMAL
HIGH
CRITICAL
```

Critical permissions include:

* users.update
* roles.update
* permissions.assign
* patients.merge.execute
* billing.payment.reverse
* stock.movements.adjust
* medication_administration.correct
* logs.delete
* modules.disable
* settings.update

---

# 20. Role UI

Update Role management UI.

For each role, show:

* role name
* description
* assigned permissions grouped by module
* users assigned
* permission count
* risk summary
* last updated

Permission assignment should be grouped by module.

Example:

```text
Patients
[ ] patients.view
[ ] patients.create
[ ] patients.update
[ ] patients.merge.execute

Emergency
[ ] emergency.board.view
[ ] emergency.triage.perform
[ ] emergency.disposition.manage
```

Add search/filter for permissions.

Add “select all module permissions” carefully.

For critical permissions, show warning.

---

# 21. Role Descriptions

Add descriptions for common roles.

Examples:

## Super Admin

Full system access, including users, roles, permissions, modules, settings, logs, and all clinical/financial modules.

## Hospital Admin

Manages hospital operations, users, departments, reports, and most workflows but may not access developer/system-critical settings unless granted.

## Doctor

Can manage consultations, diagnoses, prescriptions, investigation requests, procedure requests, and view patient clinical history.

## Nurse

Can record vitals, nursing notes, medication administration, clinical tasks, admission/emergency care activities depending assignment.

## Pharmacist

Can review prescriptions, bill selected drugs, dispense billed drugs, manage pharmacy stock view, and pharmacy reports.

## Cashier

Can view invoices, record payments, print receipts, and view payment reports.

## Claims Officer

Can prepare, review, validate, submit, and track insurance claims.

## Store Officer

Can manage products, stock movements, requisitions, transfers, stock balances, and purchase receipts depending permissions.

## Theatre Staff

Can view theatre board, manage theatre cases, record pre-op, anaesthesia, operative/recovery notes depending specific role.

## Records Officer

Can register patients, update patient folders, manage patient documents, and request/execute patient merges if authorized.

Adapt descriptions to current roles.

---

# 22. Permission Descriptions

Add a centralized permission description config or database field.

Preferred:

```text
permissions.description
permissions.module
permissions.risk_level
```

If migration is too risky, create config:

```php
config/permissions.php
```

Example:

```php
'patients.view' => [
    'module' => 'patients',
    'description' => 'Allows viewing patient list and patient folders.',
    'risk' => 'NORMAL',
],
```

Use this config to display explanations in UI.

---

# 23. Backend Permission Audit Script / Command

Create an artisan command to audit permissions.

Suggested:

```bash
php artisan permissions:audit
```

The command should report:

* routes missing permission middleware
* permissions used in code but missing in database
* permissions in database but unused in code
* duplicate permission names
* modules without permissions
* menu items without permission checks

Output to console and optionally:

```text
storage/reports/permissions_audit.json
```

or docs report.

This helps future maintenance.

---

# 24. Route Permission Mapping

Create or update route permission mapping.

Option A: permission middleware directly on routes.

Option B: config file:

```php
config/route_permissions.php
```

Example:

```php
'admin.patients.index' => 'patients.view',
'admin.patients.store' => 'patients.create',
'admin.patients.update' => 'patients.update',
'admin.emergency.board' => 'emergency.board.view',
```

The audit command can compare route list against mapping.

---

# 25. Module Permission Mapping

Create or update module-permission mapping.

Example:

```php
'patients' => [
    'patients.view',
    'patients.create',
    'patients.update',
    'patients.merge.execute',
],
'emergency' => [
    'emergency.board.view',
    'emergency.case.view',
    'emergency.triage.perform',
],
```

Use this for:

* module UI permission count
* role assignment grouping
* sidebar visibility
* module dependency warning

---

# 26. Seeder Updates

Update seeders:

* PermissionSeeder
* RoleSeeder
* ModuleSeeder
* RolePermissionSeeder
* any existing access control seeders

Rules:

* seed permissions idempotently
* do not duplicate permissions
* do not remove custom permissions accidentally
* assign Super Admin all permissions
* assign reasonable defaults to other roles
* respect existing custom assignments where possible

If changing existing permission names, write migration/compatibility mapping.

---

# 27. Backend Guard for Modules

Add module guard.

If a module is inactive:

* hide menus
* block module routes unless user is Super Admin or has override permission
* show clear message:

```text
The Emergency module is currently disabled.
```

Core modules cannot be disabled.

Suggested permission:

```text
modules.override_disabled
```

Use carefully.

---

# 28. UI Action Inventory

Audit all frontend action buttons/links.

Every button that changes data must check permission.

Examples:

* Create
* Edit
* Delete
* Cancel
* Approve
* Reject
* Verify
* Submit
* Dispense
* Administer
* Pay
* Reverse
* Refund
* Merge
* Export
* Print
* Assign
* Transfer
* Complete
* Reopen
* Correct
* Override

Add helper:

```js
can('permission.name')
```

or use existing helper.

For Vue/Inertia:

```js
const can = (permission) => page.props.auth.permissions.includes(permission)
```

or project equivalent.

Do not leave dangerous buttons visible to unauthorized users.

---

# 29. Backend Policy Inventory

Where resource ownership matters, use policies.

Examples:

* consultation entries: edit own vs edit any
* notes: edit own vs edit any
* medication administrations: correct permission
* theatre notes: edit own vs edit any
* logs: module-based view permission
* patient merge: execute permission
* payments: reverse permission

Do not use simple global permission only where ownership matters.

---

# 30. Permission-Based Dashboard/Menu

Update dashboards/menus by role.

Consultation-type users should not see irrelevant menus.

Emergency users should see emergency-relevant menus.

Store users should see stock/procurement menus.

Claims users should see claims menus.

Cashiers should see billing/payment menus.

But do not hardcode by role only. Prefer permissions.

Menu visibility should be permission/module based.

---

# 31. Tests Required

Add or update tests.

## Permission Database

1. Permission seeder creates required permissions.
2. Permission names are unique.
3. Permissions have module mapping.
4. Permissions have descriptions.
5. Permissions have risk levels.

## Roles

6. Super Admin has all permissions.
7. Doctor has consultation permissions but not financial reversal permissions.
8. Nurse has vitals/MAR/task permissions but not role management.
9. Pharmacist can bill/dispense drugs but cannot merge patients.
10. Cashier can record payments but cannot edit clinical records.
11. Claims Officer can manage claims but cannot dispense drugs.
12. Store Officer can manage stock but cannot verify lab results.

## Backend Authorization

13. Unauthorized user cannot access patient merge execute.
14. Unauthorized user cannot reverse payment.
15. Unauthorized user cannot adjust stock.
16. Unauthorized user cannot verify investigation result.
17. Unauthorized user cannot administer medication without permission.
18. Unauthorized user cannot view logs without permission.
19. Unauthorized user cannot disable module.
20. Authorized user can access allowed route.

## Frontend Permissions

21. Unauthorized user does not see restricted button.
22. Authorized user sees permitted button.
23. Sidebar hides modules without permission.
24. Module disabled hides menu.
25. Super Admin sees all modules.

## Modules

26. Core module cannot be disabled.
27. Disabled module blocks route access.
28. Module dependencies show warning.
29. Module page shows descriptions.
30. Module page shows linked permissions.

## Audit Command

31. permissions:audit detects missing route permission.
32. permissions:audit detects permission used in code but missing in DB.
33. permissions:audit detects unused permission.
34. permissions:audit generates report.

---

# 32. Verification Checklist

After implementation, manually verify:

* Login as Super Admin.
* Open Roles page.
* Permissions are grouped by module.
* Permissions have descriptions.
* Critical permissions show warning.
* Open Modules page.
* Modules show descriptions/dependencies/permissions.
* Disable non-core module and verify menu/route behavior.
* Try disabled core module and confirm blocked.
* Login as Doctor and confirm only relevant menus show.
* Login as Cashier and confirm clinical edit buttons hidden.
* Login as Nurse and confirm MAR actions available.
* Try unauthorized backend route and confirm 403.
* Run permissions audit command.
* Review generated reports.

---

# 33. Deliverables

Provide:

1. ROLES_PERMISSIONS_GAP_ANALYSIS.md
2. ROLES_PERMISSIONS_SOLUTION_REPORT.md
3. MODULES_GAP_ANALYSIS.md
4. MODULES_SOLUTION_REPORT.md
5. ROLES_PERMISSIONS_REMAINING_RECOMMENDATIONS.md
6. Updated permissions list.
7. Updated module list and descriptions.
8. Updated role descriptions.
9. Updated seeders.
10. Backend authorization fixes.
11. Frontend UI permission fixes.
12. Sidebar/menu permission/module fixes.
13. Permission descriptions in UI.
14. Module descriptions in UI.
15. Permission audit command.
16. Tests or verification notes.
17. Files modified.
18. Remaining TODOs.

---

# 34. Important Rules

Do not rely only on frontend permissions.

Do not leave sensitive POST/PATCH/DELETE routes unprotected.

Do not create duplicate permission names.

Do not remove existing custom permissions without mapping.

Do not hardcode menus only by role where permission-based checks should be used.

Do not allow disabled modules to be accessed through direct URL.

Do not allow core modules to be disabled.

Do not give all roles excessive permissions just to make errors disappear.

Do not break existing workflows.

Do not hide important admin explanations.

Now inspect the current UHMS roles, permissions, modules, menus, routes, controllers, policies, seeders, and frontend UI. Produce the required gap reports, fix the authorization/module system extensively, update the UI explanations, and document all solutions.

```
```
