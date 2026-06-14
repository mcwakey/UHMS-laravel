You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

Localisation is still not complete.

Phase 15E reduced active runtime candidates from 317 to 192. The primary target below 200 was met, but 192 active runtime candidates remain. Do not move to dashboards. Do not move to the Full Test Suite. Do not claim localisation is complete.

# UHMS Localisation Phase 15F — Clinical, Emergency, Admin Config, Billing Runtime Burn-Down

## Goal

Continue the localisation burn-down from the Phase 15E exit state.

Starting point:

```text id="pw6jbo"
Active runtime candidates: 192
```

Primary target:

```text id="k4e6lz"
Active runtime candidates: below 100
```

Preferred target:

```text id="k6d4dz"
Active runtime candidates: below 50
```

Do not work on dormant demo/template files unless they are route-linked or included by active layouts/components.

---

# 1. Required Reports To Read First

Read:

```text id="z54typ"
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
docs/LOCALISATION_PHASE_15E_STOCK_STORE_CLINICAL_BURNDOWN_REPORT.md
```

Use the active runtime worklist in `LOCALISATION_COVERAGE_AUDIT_REPORT.md` as the source of truth.

Do not repeat files already completed in Phase 15E unless the scanner still reports them.

---

# 2. Current Known Status

Phase 15E completed:

```text id="v6fu5b"
Batch 4 — stock/product-stock/store/suppliers/procurement
Batch 5 subset — prescriptions/index, investigations/items, investigation-catalogue/index, vitals/record, lab/results, lab/tests
```

Phase 15E created:

```text id="h84gwk"
lang/en/store.php
lang/fr/store.php
lang/en/prescriptions.php
lang/fr/prescriptions.php
lang/en/vitals.php
lang/fr/vitals.php
```

Phase 15E extended:

```text id="ao5t89"
lang/en/stock.php
lang/fr/stock.php
lang/en/investigations.php
lang/fr/investigations.php
lang/en/lab.php
lang/fr/lab.php
```

Remaining active runtime candidates after Phase 15E:

```text id="qfrikd"
192
```

---

# 3. Batch 5 Remainder — Clinical Pages

Fix these remaining clinical pages first:

```text id="rlwxfh"
resources/views/prescriptions/show.blade.php
resources/views/admin/investigation-catalogue/show.blade.php
```

Also check whether any deeper active lab/investigation pages still appear in the latest audit and fix them if still listed.

Use or extend:

```text id="h7wufd"
lang/en/prescriptions.php
lang/fr/prescriptions.php
lang/en/investigations.php
lang/fr/investigations.php
lang/en/lab.php
lang/fr/lab.php
```

Translate:

* prescription detail labels
* medication/order labels
* dosage labels
* route/frequency labels
* investigation catalogue detail labels
* consumables labels
* pricing/service labels
* status badges
* patient labels
* visit labels
* doctor labels
* filters
* table headers
* form labels
* action buttons
* modal titles
* empty states
* confirmation messages
* JavaScript strings if present

Do not translate:

* medicine names from database
* lab test names from database
* clinician-entered notes
* diagnosis text
* patient names
* clinical units such as mmHg, bpm, kg, cm, °C, %, SpO2

Do not change clinical workflow.

---

# 4. Batch 6 — Emergency Remaining Candidates

Fix:

```text id="vn3jcs"
resources/views/emergency/show.blade.php
```

Use or extend:

```text id="omdcuf"
lang/en/emergency.php
lang/fr/emergency.php
```

Translate:

* remaining emergency section labels
* emergency action buttons
* emergency status badges
* emergency clinical labels
* emergency billing/session labels
* emergency notes labels
* modal labels
* empty states
* JavaScript confirmation messages if present

Do not change emergency workflows.
Do not change emergency billing/session logic.
Do not expose restricted clinical or financial data.

---

# 5. Batch 7 — Admin Configuration Pages

Fix:

```text id="h5n0db"
resources/views/admin/icd-codes/index.blade.php
resources/views/departments/index.blade.php
resources/views/admin/services/index.blade.php
resources/views/designations/index.blade.php
resources/views/admin/permissions/index.blade.php
resources/views/admin/modules/index.blade.php
resources/views/complaints/catalogue/index.blade.php
resources/views/admin/notifications/broadcast.blade.php
resources/views/admin/specialties/index.blade.php
resources/views/admin/users/permissions.blade.php
resources/views/admin/dashboards/index.blade.php
resources/views/admin/analyzers/index.blade.php
```

Use existing namespaces where possible:

```text id="wv6r42"
lang/en/settings.php
lang/fr/settings.php
lang/en/users.php
lang/fr/users.php
lang/en/roles.php
lang/fr/roles.php
lang/en/messages.php
lang/fr/messages.php
lang/en/common.php
lang/fr/common.php
```

Create small domain namespaces only if needed:

```text id="wwwg9r"
lang/en/admin.php
lang/fr/admin.php
lang/en/departments.php
lang/fr/departments.php
lang/en/services.php
lang/fr/services.php
```

Translate:

* headings
* filters
* table headers
* action buttons
* modal titles
* form labels
* placeholders
* helper text
* empty states
* confirmation messages
* status labels
* permission management labels
* module management labels
* service management labels
* ICD code labels
* department/designation labels
* complaint catalogue labels
* notification broadcast labels

Important:

Do not translate permission slugs.
Do not translate route names.
Do not translate database codes.
Do not hardcode services.
Do not hardcode departments.
Do not change module enable/disable logic.

---

# 6. Batch 8 — Billing, Accounting, Accounts

Fix:

```text id="szxbmq"
resources/views/billing/invoices/show.blade.php
resources/views/accounting/payable/payables.blade.php
resources/views/accounting/settings/index.blade.php
resources/views/accounts/categories.blade.php
resources/views/accounts/entries/create.blade.php
resources/views/accounts/entries/index.blade.php
```

Use or extend:

```text id="ua6xkz"
lang/en/billing.php
lang/fr/billing.php
lang/en/invoices.php
lang/fr/invoices.php
lang/en/accounting.php
lang/fr/accounting.php
```

Translate:

* invoice labels
* payable labels
* accounting setting labels
* account categories
* journal entry labels
* debit/credit labels
* filters
* table headers
* status badges
* action buttons
* form labels
* modal titles
* empty states
* confirmation messages

Protect financial visibility.

Do not change:

* journal posting logic
* invoice totals
* payment logic
* credit note logic
* write-off logic
* sponsor logic
* insurance logic
* accounting semantics

Do not expose financial data to unauthorized users.

---

# 7. Batch 9 — Queue, Notifications, Service Renderings

Fix:

```text id="k60fpa"
resources/views/queue/manage.blade.php
resources/views/queue/board.blade.php
resources/views/notifications/index.blade.php
resources/views/service-renderings/index.blade.php
```

Use or extend:

```text id="l1otyl"
lang/en/queue.php
lang/fr/queue.php
lang/en/notifications.php
lang/fr/notifications.php
lang/en/services.php
lang/fr/services.php
```

Translate:

* queue board labels
* queue management labels
* notification labels
* service rendering labels
* filters
* table headers
* buttons
* empty states
* badges
* JavaScript messages if present

Do not change queue workflow.
Do not hardcode services.

---

# 8. Batch 10 — HR, Blood Bank, Settings Long Tail

If the previous batches complete safely, process the long tail:

```text id="uaddtg"
resources/views/hr/employees/create.blade.php
resources/views/hr/employees/edit.blade.php
resources/views/hr/attendance/summary.blade.php
resources/views/hr/leave/create.blade.php
resources/views/blood-bank/donations.blade.php
resources/views/blood-bank/reports.blade.php
resources/views/blood-bank/requests.blade.php
resources/views/blood-bank/storage.blade.php
resources/views/blood-bank/units.blade.php
resources/views/blood-bank/donation-view.blade.php
resources/views/blood-bank/donor-profile.blade.php
resources/views/statistics/dashboard.blade.php
resources/views/visits/create.blade.php
resources/views/partials/patient-card.blade.php
resources/views/settings/activity-log-show.blade.php
resources/views/settings/activity-log.blade.php
resources/views/settings/invoice.blade.php
resources/views/settings/log-retention.blade.php
resources/views/settings/notification-preferences.blade.php
resources/views/settings/organization.blade.php
resources/views/settings/payment-methods.blade.php
resources/views/settings/profile.blade.php
```

Use existing namespaces where possible:

```text id="l5lban"
lang/en/hr.php
lang/fr/hr.php
lang/en/blood_bank.php
lang/fr/blood_bank.php
lang/en/settings.php
lang/fr/settings.php
lang/en/visits.php
lang/fr/visits.php
lang/en/patients.php
lang/fr/patients.php
```

Create namespaces only if they are missing and necessary.

---

# 9. JavaScript Manual Review

Review:

```text id="zsgy1g"
resources/js/script.js
resources/js/doctors.js
```

If strings are active at runtime, wire them through the existing `window.UHMS_I18N` bridge.

Do not introduce:

```text id="ctns8d"
i18next
Vue
React
new frontend localisation package
new localisation framework
```

If strings are dormant template/demo examples, document them as false positives with evidence.

---

# 10. Class-A Service Candidates

Review the 67 class-A service candidates.

Translate only confirmed user-facing output labels.

Likely user-facing examples:

```text id="q8hi4r"
ConsultationNextPatientService.php message output
FinancialReportService.php report section labels
PatientMergePreviewService.php merge preview table labels
ProcedureReportService.php procedure report stage labels
StatisticsService.php chart/KPI labels
```

Unsafe examples:

```text id="n2cr0t"
stored historical event titles
audit descriptions
journal descriptions
SQL expressions
canonical workflow event names
```

For safe user-facing labels, use translations:

```php id="c70ix3"
'label' => __('accounting.revenue')
```

Do not alter stored semantics.

---

# 11. Translation Rules

Use Laravel localisation only.

Use:

```php id="mtkv0r"
__('module.key')
```

or:

```blade id="7nsze1"
{{ __('module.key') }}
```

For placeholders:

```php id="5j0blb"
__('queue.patient_waiting_for', ['department' => $departmentName])
```

Do not concatenate translated fragments.

Bad:

```php id="gkzmkp"
'Patient waiting for ' . $departmentName
```

Good:

```php id="4wtcup"
__('queue.patient_waiting_for', ['department' => $departmentName])
```

---

# 12. Language File Rules

Use appropriate namespaces.

Generic UI words go in:

```text id="w0zq65"
common.php
```

Only for truly generic words:

```text id="bhxv04"
save
cancel
close
search
filter
clear
actions
status
active
inactive
view
edit
delete
yes
no
loading
error
success
```

Domain words go in domain files:

```text id="7m495q"
prescriptions.php
investigations.php
lab.php
emergency.php
admin.php
departments.php
services.php
billing.php
accounting.php
queue.php
notifications.php
hr.php
blood_bank.php
settings.php
```

Every English key must exist in French.
Every French key must exist in English.

---

# 13. Do Not Translate These

Do not translate:

* patient names
* staff names
* doctor names
* supplier names
* medicine names from database
* product names from database
* service names from database unless they are system-defined hardcoded labels
* diagnosis text typed by clinicians
* clinical notes typed by clinicians
* lab test names from catalogue/database
* insurance provider names
* sponsor names
* permission slugs
* role slugs
* route names
* database column names
* internal enum values
* CSS classes
* JS selectors
* data attributes
* clinical units such as mmHg, bpm, kg, cm, °C, %, SpO2
* currency symbols
* UHMS acronym

---

# 14. Security Rules

Do not weaken permissions.

Preserve:

```text id="h82xho"
@can
@cannot
Gate
policies
middleware
role checks
permission checks
financial visibility checks
stock-cost visibility checks
clinical confidentiality checks
```

Do not expose:

```text id="je80nk"
restricted clinical data
financial data
accounting data
stock cost
insurance financial details
sponsor financial details
audit logs
user permissions
```

No business logic should be moved into Blade.

---

# 15. Responsiveness

While touching these pages, fix obvious responsiveness problems only where directly encountered:

* wrap large tables in `.table-responsive`
* ensure action buttons wrap on small screens
* ensure filters stack on mobile
* ensure modals are usable on mobile
* do not break print views

Use Bootstrap 5 and Tabler Icons only.
Do not introduce Tailwind.

---

# 16. Scanner Burn-Down

Run scanner after every batch:

```bash id="whgozm"
php scripts/localisation-audit.php
```

Record before/after counts for:

```text id="uxhlwo"
Batch 5 remainder
Batch 6
Batch 7
Batch 8
Batch 9
Batch 10
JavaScript review
Class-A services
```

If a batch introduces parse errors, stop and fix before continuing.

---

# 17. Required Documentation

Create:

```text id="24jrfj"
docs/LOCALISATION_PHASE_15F_CLINICAL_ADMIN_BILLING_BURNDOWN_REPORT.md
```

Include:

* summary
* starting active runtime candidate count: 192
* ending active runtime candidate count
* per-batch before/after counts
* Batch 5 remainder files fixed/deferred
* Batch 6 files fixed/deferred
* Batch 7 files fixed/deferred
* Batch 8 files fixed/deferred
* Batch 9 files fixed/deferred
* Batch 10 files fixed/deferred
* JavaScript files reviewed/touched/deferred
* class-A service candidates reviewed/touched/deferred
* language files changed
* namespaces created
* keys added
* dynamic labels converted
* permissions/security confirmation
* EN/FR parity result
* PHP lint result
* view cache result
* scanner result
* manual French verification checklist
* next recommendation if candidates remain

Do not claim “all pages translated” unless scanner and manual French checks support it.

---

# 18. Verification Commands

Run:

```bash id="tb409a"
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Run:

```bash id="h8nwni"
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run:

```bash id="mz0cdi"
php -l scripts/localisation-audit.php
php scripts/localisation-audit.php
```

Run:

```bash id="m9mjk8"
git diff --check
```

If compiled Blade cache exists, lint compiled views:

```bash id="rn65we"
find storage/framework/views -type f -name "*.php" -print0 | xargs -0 -n1 php -l
```

---

# 19. Manual French Verification

Switch the app to French and manually verify every fixed batch:

```text id="uvj8z2"
prescriptions/show
investigation catalogue/show
emergency/show
ICD codes
departments
services
designations
permissions
modules
complaints catalogue
notification broadcast
specialties
user permissions
admin dashboards
billing invoice show
payables
accounting settings
accounts categories
journal entries create/index
queue manage/board
notifications index
service renderings
HR employee create/edit
HR attendance summary
HR leave create
blood bank donations/reports/requests/storage/units/profile
settings pages
statistics dashboard
visits/create
patient card
```

Check:

* page title
* breadcrumbs
* headings
* cards
* filters
* form labels
* placeholders
* helper text
* buttons
* tables
* badges
* modals
* empty states
* alerts
* print/PDF labels if present
* JavaScript messages
* validation errors
* financial data remains permission-controlled
* clinical data remains permission-controlled

---

# 20. Acceptance Criteria

Phase 15F is complete only when:

* Batch 5 remainder is finished or clearly documented
* Batch 6 is finished or clearly documented
* Batch 7 is finished or clearly documented
* Batch 8 is finished or clearly documented
* Batch 9 is finished or clearly documented
* Batch 10 is attempted or clearly deferred
* active runtime candidates are reduced from 192
* target below 100 is attempted
* all added keys have EN/FR parity
* PHP lint passes
* view cache compiles
* localisation scanner runs
* permissions are unchanged
* financial visibility remains protected
* clinical confidentiality remains protected
* no business logic is moved into Blade
* no new localisation framework is introduced
* no new frontend package is introduced
* documentation report is created
* manual French verification checklist is updated

Proceed with UHMS Localisation Phase 15F now.
