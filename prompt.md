You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

Localisation is still not complete.

Phase 15D reduced active runtime candidates from 370 to 317. The report clearly says the pass is partial and not complete. Do not move to dashboards. Do not move to the Full Test Suite. Do not claim localisation is complete.

# UHMS Localisation Phase 15E — Stock, Store, Procurement, Clinical Runtime Burn-Down

## Goal

Continue the localisation burn-down from the Phase 15D exit state.

Starting point:

```text
Active runtime candidates: 317
```

Primary target:

```text
Active runtime candidates: below 200
```

Preferred target:

```text
Active runtime candidates: below 150
```

Do not work on dormant demo/template files unless they are route-linked or included by active layouts/components.

---

# 1. Required Reports To Read First

Read:

```text
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
docs/LOCALISATION_PHASE_15D_RUNTIME_BURNDOWN_CONTINUATION_REPORT.md
```

Use the active runtime worklist in `LOCALISATION_COVERAGE_AUDIT_REPORT.md` as the source of truth.

Do not repeat files already completed in Phase 15D unless the scanner still reports them.

---

# 2. Current Known Status

Phase 15D completed:

```text
resources/views/admin/procedures/schedule.blade.php
resources/views/admin/procedure-catalogue/index.blade.php
resources/views/theatre/show.blade.php
resources/views/theatre/partials/schedule-form.blade.php
resources/views/theatre/rooms/partials/form.blade.php
resources/views/wards/index.blade.php
resources/views/wards/beds.blade.php
resources/views/wards/bed-map.blade.php
resources/views/settings/ward.blade.php
```

Phase 15D created:

```text
lang/en/wards.php
lang/fr/wards.php
```

Phase 15D extended:

```text
lang/en/theatre.php
lang/fr/theatre.php
lang/en/procedures.php
lang/fr/procedures.php
```

Remaining active runtime candidates after Phase 15D:

```text
317
```

---

# 3. Batch 4 — Stock, Product Stock, Store, Suppliers, Procurement

This is the main priority for Phase 15E.

Fix these files:

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
* receive stock labels
* transfer stock labels
* adjust stock labels
* return stock labels
* stock location labels
* purchase order labels
* purchase return labels
* supplier labels
* supplier ledger labels
* requisition labels
* department consumable labels
* product labels
* batch labels
* quantity labels
* unit cost labels
* selling price labels
* expiry date labels
* filters
* table headers
* form labels
* placeholders
* helper text
* buttons
* modal titles
* empty states
* confirmation messages
* validation labels
* print labels if present
* JavaScript messages if present

Important UHMS rule:

```text
Products = physical stock items.
Services = billable activities.
```

Do not mix products and services.

---

# 4. Stock Cost / Financial Security

This batch touches sensitive stock and procurement pages.

Do not expose stock cost to unauthorized users.

Preserve all permission checks:

```text
@can
@cannot
Gate
policy checks
stock-cost visibility checks
financial visibility checks
```

Do not modify business rules for:

```text
stock receiving
stock transfer
stock adjustment
stock returns
purchase orders
purchase returns
supplier ledger
department consumables
stock requisitions
```

Only translate visible UI strings.

Do not move business logic into Blade.

---

# 5. Batch 5 — Prescriptions, Investigations, Vitals, Lab

After Batch 4, if time/change-set size remains safe, continue with Batch 5.

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
* route/frequency labels
* investigation item labels
* investigation catalogue labels
* lab result labels
* vital sign labels
* status badges
* patient labels
* visit labels
* filters
* table headers
* form labels
* action buttons
* modal titles
* empty states
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

# 6. Batch 6 — Emergency Remaining Candidates

If Batch 4 and Batch 5 are completed safely, process:

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

# 7. JavaScript Manual Review

Review but do not rush unless safe:

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

# 8. Class-A Service Candidates

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

Safe example:

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

# 9. Translation Rules

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

# 10. Language File Rules

Use appropriate namespaces.

Generic UI words go in:

```text
common.php
```

Only for truly generic words:

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

# 11. Do Not Translate These

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

# 12. Security Rules

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

# 13. Responsiveness

While touching these pages, fix obvious responsiveness problems only where directly encountered:

* wrap large tables in `.table-responsive`
* ensure action buttons wrap on small screens
* ensure filters stack on mobile
* ensure modals are usable on mobile
* do not break print views

Use Bootstrap 5 and Tabler Icons only.
Do not introduce Tailwind.

---

# 14. Scanner Burn-Down

Run the scanner after every batch:

```bash
php scripts/localisation-audit.php
```

Record before/after counts for:

```text
Batch 4
Batch 5
Batch 6
JavaScript review if touched
Class-A services if touched
```

If a batch introduces parse errors, stop and fix before continuing.

---

# 15. Required Documentation

Create:

```text
docs/LOCALISATION_PHASE_15E_STOCK_STORE_CLINICAL_BURNDOWN_REPORT.md
```

Include:

* summary
* starting active runtime candidate count: 317
* ending active runtime candidate count
* per-batch before/after counts
* Batch 4 files fixed/deferred
* Batch 5 files fixed/deferred
* Batch 6 files fixed/deferred
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

# 16. Verification Commands

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

# 17. Manual French Verification

Switch the app to French and manually verify every fixed batch:

```text
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
* stock-cost visibility still permission-controlled
* clinical data still permission-controlled

---

# 18. Acceptance Criteria

Phase 15E is complete only when:

* Batch 4 is finished or clearly documented
* Batch 5 is finished or clearly documented
* Batch 6 is finished or clearly documented
* active runtime candidates are reduced from 317
* target below 200 is attempted
* all added keys have EN/FR parity
* PHP lint passes 
* view cache compiles
* localisation scanner runs
* permissions are unchanged
* stock-cost visibility remains protected
* clinical confidentiality remains protected
* no business logic is moved into Blade
* no new localisation framework is introduced
* no new frontend package is introduced
* documentation report is created
* manual French verification checklist is updated

Proceed with UHMS Localisation Phase 15E now.
