You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

Localisation is still not complete.

Phase 15C reduced active runtime candidates from 437 to 370, but the report clearly says it was a partial pass. Do not move to dashboards. Do not move to the Full Test Suite. Do not claim localisation is complete.

# UHMS Localisation Phase 15D — Runtime Burn-Down Continuation

## Goal

Continue the localisation burn-down from the Phase 15C exit state.

Starting point:

```text
Active runtime candidates: 370
```

Target:

```text
Active runtime candidates: below 150 if possible
```

Preferred target:

```text
Active runtime candidates: below 100
```

Do not touch dormant demo/template pages unless they are route-linked or included by active layouts/components.

---

# 1. Required Reports To Read First

Read:

```text
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
docs/LOCALISATION_PHASE_15C_REMAINING_RUNTIME_BURNDOWN_REPORT.md
```

Use the active runtime worklist in `LOCALISATION_COVERAGE_AUDIT_REPORT.md` as the source of truth.

Do not repeat files already completed in Phase 15C unless the scanner still reports them.

---

# 2. Current Known Status

Phase 15C completed:

```text
resources/views/pharmacy/drug-history.blade.php
resources/views/pharmacy/history.blade.php
resources/views/theatre/rooms/index.blade.php
resources/views/admin/procedures/index.blade.php
resources/views/admin/procedure-catalogue/show.blade.php
```

Phase 15C created:

```text
lang/en/procedures.php
lang/fr/procedures.php
```

Phase 15C extended:

```text
lang/en/pharmacy.php
lang/fr/pharmacy.php
lang/en/theatre.php
lang/fr/theatre.php
```

Remaining active runtime candidates after Phase 15C:

```text
370
```

---

# 3. Batch 2 Remainder — Finish Theatre / Procedures

First complete the remaining Batch 2 files:

```text
resources/views/admin/procedures/schedule.blade.php
resources/views/theatre/show.blade.php
resources/views/admin/procedure-catalogue/index.blade.php
resources/views/theatre/partials/schedule-form.blade.php
resources/views/theatre/rooms/partials/form.blade.php
```

Translate:

* procedure schedule labels
* theatre case labels
* theatre status badges
* room form labels
* schedule form labels
* filters
* table headers
* action buttons
* modal titles
* empty states
* confirmation messages
* placeholders
* helper text
* JavaScript strings if present

Use or extend:

```text
lang/en/theatre.php
lang/fr/theatre.php
lang/en/procedures.php
lang/fr/procedures.php
```

Do not change theatre workflow.
Do not change scheduling logic.
Do not expose restricted clinical data.

Manual-review partials must be checked carefully because they may be shared.

If safe, translate them.
If not safe, document exactly why.

---

# 4. Batch 3 — Wards and Beds

Fix:

```text
resources/views/wards/index.blade.php
resources/views/wards/beds.blade.php
resources/views/wards/bed-map.blade.php
resources/views/settings/ward.blade.php
```

Create or extend:

```text
lang/en/wards.php
lang/fr/wards.php
```

Translate:

* ward labels
* bed labels
* occupancy labels
* bed status labels
* admission labels
* patient labels
* room labels
* availability labels
* filters
* cards
* table headers
* action buttons
* modal titles
* empty states
* helper text
* JavaScript messages if present

Do not translate patient names or ward names entered by users.
Do not change bed allocation logic.
Do not expose restricted admission data.

---

# 5. Batch 4 — Product Stock / Store / Suppliers / Procurement

Fix:

```text
resources/views/admin/product-stock/ledger.blade.php
resources/views/admin/product-stock/balances.blade.php
resources/views/admin/product-stock/receive.blade.php
resources/views/admin/product-stock/transfer.blade.php
resources/views/admin/product-stock/adjust.blade.php
resources/views/admin/product-stock/return.blade.php
resources/views/admin/stock-locations/index.blade.php
resources/views/store/purchase-orders/index.blade.php
resources/views/store/purchase-orders/create.blade.php
resources/views/store/purchase-orders/show.blade.php
resources/views/store/purchase-returns/index.blade.php
resources/views/store/purchase-returns/create.blade.php
resources/views/store/purchase-returns/show.blade.php
resources/views/store/supplier-ledger.blade.php
resources/views/store/suppliers.blade.php
resources/views/store/stock-requisitions/index.blade.php
resources/views/department-consumables/index.blade.php
```

Use or extend:

```text
lang/en/stock.php
lang/fr/stock.php
lang/en/store.php
lang/fr/store.php
```

Create `store.php` if it does not exist and if store/procurement vocabulary does not fit cleanly inside `stock.php`.

Translate:

* stock ledger labels
* stock balance labels
* receive/transfer/adjust/return labels
* purchase order labels
* purchase return labels
* supplier labels
* supplier ledger labels
* requisition labels
* department consumable labels
* filters
* table headers
* buttons
* modal titles
* empty states
* confirmation messages
* print labels if present

Important UHMS rule:

```text
Products = physical stock items.
Services = billable activities.
```

Do not mix products and services.

Protect stock cost visibility.

Do not remove or weaken:

```text
@can
@cannot
Gate
policy checks
stock-cost visibility checks
financial visibility checks
```

---

# 6. Batch 5 — Prescriptions / Investigations / Vitals / Lab

Fix:

```text
resources/views/prescriptions/show.blade.php
resources/views/prescriptions/index.blade.php
resources/views/investigations/items/index.blade.php
resources/views/admin/investigation-catalogue/index.blade.php
resources/views/admin/investigation-catalogue/show.blade.php
resources/views/vitals/record.blade.php
resources/views/lab/results.blade.php
resources/views/lab/tests.blade.php
```

Use or extend:

```text
lang/en/prescriptions.php
lang/fr/prescriptions.php
lang/en/investigations.php
lang/fr/investigations.php
lang/en/lab.php
lang/fr/lab.php
```

Translate:

* prescription labels
* medication order labels
* dosage labels
* investigation item labels
* catalogue labels
* lab result labels
* vital sign labels
* status badges
* patient/visit labels
* filters
* table headers
* action buttons
* modal titles
* empty states
* JavaScript messages if present

Do not translate:

* medicine names from database
* lab test names from database
* clinician-entered notes
* diagnosis text
* patient names
* clinical units like mmHg, bpm, kg, cm, °C, %, SpO2

Do not change clinical workflow.

---

# 7. Batch 6 — Emergency Remaining Candidates

Fix:

```text
resources/views/emergency/show.blade.php
```

Use or extend:

```text
lang/en/emergency.php
lang/fr/emergency.php
```

Translate remaining active candidates only.

Do not change emergency workflows.
Do not change emergency billing/session logic.
Do not expose restricted clinical or financial data.

---

# 8. Run Scanner After Every Batch

After each batch, run:

```bash
php scripts/localisation-audit.php
```

Record before/after counts for:

```text
Batch 2 remainder
Batch 3
Batch 4
Batch 5
Batch 6
```

If a batch creates parse errors, stop and fix before continuing.

---

# 9. JavaScript Manual Review

Review but do not necessarily complete unless safe:

```text
resources/js/script.js
resources/js/doctors.js
```

If strings are active at runtime, wire them through the existing `window.UHMS_I18N` bridge.

Do not introduce:

```text
i18next
Vue
React
new frontend localisation package
new localisation framework
```

If strings are dormant template/demo examples, document them as false positives with evidence.

---

# 10. Class-A Service Candidates

Do not rush these in this phase unless they are simple and clearly user-facing.

Review the 67 class-A service candidates and translate only confirmed user-facing output labels.

Examples:

```text
ConsultationNextPatientService.php
FinancialReportService.php
PatientMergePreviewService.php
ProcedureReportService.php
StatisticsService.php
```

Safe examples:

```php
'label' => __('accounting.revenue')
```

Unsafe examples:

```text
stored historical event titles
audit descriptions
journal descriptions
SQL expressions
canonical workflow event names
```

Leave unsafe items unchanged and document them.

---

# 11. Translation Rules

Use Laravel localisation only.

Use:

```php
__('module.key')
```

or:

```blade
{{ __('module.key') }}
```

For placeholders:

```php
__('stock.remaining_quantity', ['qty' => $qty])
```

Do not concatenate translated fragments.

Bad:

```php
'Remaining: ' . $qty
```

Good:

```php
__('stock.remaining_quantity', ['qty' => $qty])
```

---

# 12. Language File Rules

Use appropriate namespaces.

Generic UI words go in:

```text
common.php
```

Only for:

```text
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

```text
theatre.php
procedures.php
wards.php
stock.php
store.php
prescriptions.php
investigations.php
lab.php
emergency.php
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

```text
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

```text
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

# 16. Required Documentation

Create:

```text
docs/LOCALISATION_PHASE_15D_RUNTIME_BURNDOWN_CONTINUATION_REPORT.md
```

Include:

* summary
* starting active runtime candidate count: 370
* ending active runtime candidate count
* per-batch before/after counts
* Batch 2 remainder files fixed/deferred
* Batch 3 files fixed/deferred
* Batch 4 files fixed/deferred
* Batch 5 files fixed/deferred
* Batch 6 files fixed/deferred
* language files changed
* namespaces created
* keys added
* JavaScript files reviewed
* class-A service candidates reviewed
* dynamic labels converted
* permissions/security confirmation
* EN/FR parity result
* PHP lint result
* view cache result
* scanner result
* manual French verification checklist
* next recommendation if candidates remain

Do not claim “all pages translated” unless the scanner and manual French checks support it.

---

# 17. Verification Commands

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Run:

```bash
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run:

```bash
php -l scripts/localisation-audit.php
php scripts/localisation-audit.php
```

Run:

```bash
git diff --check
```

If compiled Blade cache exists, lint compiled views:

```bash
find storage/framework/views -type f -name "*.php" -print0 | xargs -0 -n1 php -l
```

---

# 18. Manual French Verification

Switch the app to French and manually verify every fixed batch:

```text
procedure schedule
theatre show
procedure catalogue index
theatre schedule form
theatre room form
wards index
wards beds
wards bed map
ward settings
product stock ledger
product stock balances
product stock receive
product stock transfer
product stock adjust
product stock return
stock locations
purchase orders index/create/show
purchase returns index/create/show
supplier ledger
suppliers
stock requisitions
department consumables
prescriptions index/show
investigation items
investigation catalogue index/show
vitals record
lab results/tests
emergency show
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

---

# 19. Acceptance Criteria

Phase 15D is complete only when:

* Batch 2 remainder is finished or clearly documented
* Batches 3–6 are finished or clearly documented
* active runtime candidates are reduced from 370
* target below 150 is attempted
* all added keys have EN/FR parity
* PHP lint passes
* view cache compiles
* localisation scanner runs
* permissions are unchanged
* no business logic is moved into Blade
* no new localisation framework is introduced
* no new frontend package is introduced
* documentation report is created
* manual French verification checklist is updated

Proceed with UHMS Localisation Phase 15D now.
