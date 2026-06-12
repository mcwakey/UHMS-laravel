You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no docs/UHMS_IMPLEMENTATION_SKILL.md file in this project.
Do not try to read it.
Follow the instructions in this prompt directly.

We completed UHMS Localisation Phase 9 — Active Pages Translation Batch 2.

Phase 9 focused on:

* consultations
* theatre/procedures
* lab/investigations/catalogues
* medication administration
* blood bank

The next active localisation batch must now move to operational inventory and workforce pages.

Proceed with:

# UHMS Localisation Phase 11 — Active Pages Translation Batch 4

## Goal

Translate and clean remaining active runtime pages for:

1. Store
2. Stock
3. Inventory
4. Procurement
5. Suppliers
6. Purchase Orders
7. Goods Receiving / Receipts
8. Returns
9. Transfers
10. Adjustments
11. Stock Requisitions
12. HR
13. Employees / Staff
14. Attendance
15. Leave
16. Payroll

This phase must continue from the active route/page inventory.

Do not translate demo/template/sample pages.
Do not translate backup-route-only pages.
Do not translate files only referenced by `routes/web.php.bak`.
Do not guess based only on folder names.
Use active routes, controllers, and the route/view inventory.

Do not change stock logic.
Do not change procurement logic.
Do not change payroll logic.
Do not change attendance logic.
Do not expose stock cost to unauthorized users.
Do not create a new localisation system.
Do not introduce new packages.
Do not introduce Tailwind.

---

# 1. Source Reports

Use:

```text
docs/LOCALISATION_PHASE_7_COMPLETE_ACTIVE_PAGE_TRANSLATION_REPORT.md
docs/LOCALISATION_PHASE_8_ACTIVE_PAGES_BATCH_1_REPORT.md
docs/LOCALISATION_PHASE_9_ACTIVE_PAGES_BATCH_2_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Continue from the active backlog.

---

# 2. Target Area A — Store / Stock / Inventory

Check active route-linked views and partials under actual project paths, including:

```text
resources/views/store/
resources/views/admin/stock-locations/
resources/views/admin/products/
resources/views/stock/
resources/views/inventory/
```

Some prompt-named folders may not exist as standalone directories. If functionality exists under `store` or `admin`, use the real active path.

Translate visible UI text in:

* stock dashboard
* stock balances
* stock ledger
* stock movements
* stock movement details
* batch details
* stock adjustments
* adjustment index
* stock transfers
* transfer index
* stock returns
* returns index
* stock locations
* valuation pages
* expired stock pages
* low-stock/out-of-stock pages
* stock action buttons
* filters
* form labels
* table headers
* modal labels
* empty states
* inline JavaScript strings

Use or extend:

```text
lang/en/stock.php
lang/fr/stock.php
lang/en/products.php
lang/fr/products.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Rules:

* Products are physical stock items.
* Services are billable activities.
* Do not mix products and services.
* Do not expose stock cost unless existing permission allows it.
* Do not change stock balance calculations.
* Do not duplicate stock movement logic.
* Do not calculate stock in Blade.

---

# 3. Target Area B — Procurement / Suppliers / Purchase Orders

Check active route-linked views and partials for:

```text
resources/views/store/purchase-orders/
resources/views/store/suppliers/
resources/views/store/receipts/
resources/views/store/returns/
resources/views/store/procurement/
resources/views/store/stock-requisitions/
resources/views/admin/suppliers/
resources/views/admin/purchase-orders/
```

depending on actual active controller/view usage.

Translate visible UI text in:

* supplier list
* supplier create/edit/show
* supplier ledger labels
* purchase order list
* purchase order create/edit/show
* approval labels
* receiving labels
* goods receipt labels
* supplier return labels
* requisition labels
* procurement filters
* status labels
* table headers
* modal labels
* action buttons
* empty states
* print/export labels if present
* inline JavaScript strings

Use or extend:

```text
lang/en/stock.php
lang/fr/stock.php
lang/en/procurement.php
lang/fr/procurement.php
lang/en/suppliers.php
lang/fr/suppliers.php
lang/en/purchase_orders.php
lang/fr/purchase_orders.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Create new paired EN/FR files only if the module is active and the existing `stock.php` file would become too messy.

Rules:

* Do not change supplier ledger logic.
* Do not change AP/payables logic.
* Do not change purchase order workflow.
* Do not expose cost/valuation to unauthorized users.
* Do not hardcode suppliers.
* Do not hardcode approval roles.

---

# 4. Target Area C — HR / Employees / Staff

Check active route-linked views and partials for:

```text
resources/views/hr/
resources/views/staff/
resources/views/employees/
resources/views/admin/hr/
```

depending on actual active route/controller usage.

Translate visible UI text in:

* employee list
* employee create/edit/show
* staff profile
* staff status labels
* department/role labels shown in HR pages
* HR filters
* form labels
* table headers
* modal labels
* action buttons
* empty states
* inline JavaScript strings

Use or extend:

```text
lang/en/hr.php
lang/fr/hr.php
lang/en/users.php
lang/fr/users.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Rules:

* Do not translate staff names.
* Do not change user/role permission logic.
* Do not expose salary/payroll values to unauthorized users.

---

# 5. Target Area D — Attendance / Leave / Payroll

Check active route-linked views and partials for:

```text
resources/views/hr/attendance/
resources/views/hr/leave/
resources/views/hr/payroll/
resources/views/attendance/
resources/views/leave/
resources/views/payroll/
```

depending on actual active project structure.

Translate visible UI text in:

* attendance dashboard/list
* attendance create/edit/show
* check-in/check-out labels
* attendance status labels
* leave requests
* leave approvals
* leave balances
* payroll runs
* payroll details
* payslip labels
* payroll report labels
* payroll filters
* table headers
* modal labels
* action buttons
* empty states
* print/export labels if present
* inline JavaScript strings

Use or extend:

```text
lang/en/hr.php
lang/fr/hr.php
lang/en/payroll.php
lang/fr/payroll.php
lang/en/common.php
lang/fr/common.php
lang/en/statuses.php
lang/fr/statuses.php
lang/en/messages.php
lang/fr/messages.php
```

Create `payroll.php` only if payroll has enough active text to justify its own file.

Rules:

* Do not change payroll calculations.
* Do not change attendance calculations.
* Do not change leave approval workflow.
* Do not expose payroll/salary values to unauthorized users.
* Do not calculate payroll in Blade.

---

# 6. Dynamic Labels

Search target files for:

```php
->label()
->statusLabel()
->typeLabel()
getLabelAttribute()
displayName()
humanName()
```

Where displayed to users and safe, replace with:

```php
->translatedLabel()
```

or an existing shared component:

```blade
<x-status-badge>
```

Do not change stored enum values.
Do not change enum constants.
Do not change workflow/status transitions.

---

# 7. JavaScript / Frontend Strings

Translate visible JavaScript UI strings in targeted Blade/frontend files:

* alerts
* confirmations
* loading text
* empty messages
* Select2 placeholders
* AJAX success/error messages
* dynamic row labels
* chart labels
* date range labels

Use existing `window.UHMS_I18N`, `useTrans()`, or module-level Blade i18n bridge.

Do not introduce a new frontend localisation package.
Do not expose restricted stock cost, salary, payroll, or financial data to JavaScript.

---

# 8. Responsive Cleanup While Translating

Fix obvious responsive issues while touching these pages:

* table overflow
* filter wrapping
* action button overflow
* modal sizing
* tab overflow
* long French labels breaking layout
* stock/procurement tables overflowing on mobile

Use Bootstrap 5 utilities only.
Do not introduce Tailwind.

---

# 9. Verification

Run:

```bash
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Run language lint:

```bash
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run nested EN/FR parity verification.

Run localisation audit again:

```bash
php scripts/localisation-audit.php
```

The raw candidate count may remain high because demo/template files are still included.

But the report must specifically state:

* store/stock/inventory pages cleaned
* procurement/supplier/purchase-order pages cleaned
* HR/employee/staff pages cleaned
* attendance/leave/payroll pages cleaned
* remaining active pages after this batch

---

# 10. Documentation

Create:

```text
docs/LOCALISATION_PHASE_11_ACTIVE_PAGES_BATCH_4_REPORT.md
```

Include:

* active routes/views checked in this batch
* store/stock/inventory files translated
* procurement/supplier/purchase-order files translated
* HR/staff/employee files translated
* attendance/leave/payroll files translated
* language files added/updated
* JavaScript strings translated
* dynamic labels updated
* responsive fixes made
* EN/FR parity result
* PHP lint result
* cache/route/view-cache verification result
* localisation audit result
* remaining active untranslated pages

---

# 11. Acceptance Criteria

This phase is complete when:

* active store/stock/inventory pages are checked and translated or documented
* active procurement/supplier/purchase-order pages are checked and translated or documented
* active HR/staff/employee pages are checked and translated or documented
* active attendance/leave/payroll pages are checked and translated or documented
* active frontend strings in these areas are checked and translated or documented
* EN/FR parity remains clean
* touched files pass lint
* caches clear
* route list works
* view cache works
* no business logic changed
* no workflows changed
* no duplicate localisation system created
* no new packages introduced

Proceed with UHMS Localisation Phase 11 — Active Pages Translation Batch 4 now.
