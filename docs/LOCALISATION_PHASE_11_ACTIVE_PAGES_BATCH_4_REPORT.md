# UHMS Localisation Phase 11 - Active Pages Translation Batch 4

Date: 2026-06-12

## Scope

Phase 11 continued from the active route/page inventory and focused on operational inventory and workforce runtime pages:

- Store, stock, inventory, procurement, suppliers, purchase orders, returns, transfers, adjustments, and stock requisitions
- HR, employees/staff, attendance, leave, and payroll

No demo/template/sample views or `routes/web.php.bak`-only pages were translated.

## Active Routes / Views Checked

Active route-linked views checked in this batch included:

- `store.suppliers`
- `store.supplier-ledger`
- `store.purchase-orders.index`
- `store.purchase-orders.create`
- `store.purchase-orders.show`
- `store.purchase-returns.index`
- `store.purchase-returns.create`
- `store.purchase-returns.show`
- `store.stock-requisitions.index`
- `store.stock-requisitions.create`
- `store.stock-requisitions.show`
- `store.stock.balances`
- `store.stock.valuation`
- `store.stock.ledger`
- `store.stock.locations`
- `store.stock.adjustments-index`
- `store.stock.returns-index`
- `store.stock.transfers-index`
- `store.stock.batch-show`
- `store.stock.movement-show`
- `store.stock.adjustment`
- `store.stock.return`
- `store.stock.transfer`
- `admin.stock-locations.index`
- `admin.stock-locations._form`
- `admin.products.index`
- `admin.products.show`
- `admin.products._form_fields`
- `admin.products._edit_modal`
- `admin.products._prices_modal`
- `hr.attendance.index`
- `hr.attendance.summary`
- `hr.employees.index`
- `hr.employees.create`
- `hr.employees.show`
- `hr.employees.edit`
- `hr.leave.index`
- `hr.leave.create`
- `hr.payroll.index`
- `hr.payroll.payslip`

## Store / Stock / Inventory Cleaned

Updated stock and inventory display surfaces:

- `resources/views/store/stock/batch-show.blade.php`
- `resources/views/store/stock/ledger.blade.php`
- `resources/views/store/stock/movement-show.blade.php`
- `resources/views/store/stock/adjustment.blade.php`
- `resources/views/store/stock/return.blade.php`
- `resources/views/store/stock/transfer.blade.php`
- `resources/views/store/stock-requisitions/index.blade.php`
- `resources/views/store/stock-requisitions/create.blade.php`

Work completed:

- Stock movement, requisition, batch, and ledger enum labels now use translated labels instead of raw enum `label()` output.
- Stock batch type rendering now has a translated model helper through `StockBatch::translatedTypeLabel()`.
- Stock adjustment, return, transfer, and requisition JavaScript alerts now use `stock.*` translation keys.
- Shared stock movement status/type keys were added to `statuses.default.*`.
- Existing stock balance, cost, valuation, transfer, adjustment, and movement logic was not changed.

## Procurement / Suppliers / Purchase Orders Cleaned

Updated active procurement surfaces:

- `resources/views/store/purchase-orders/index.blade.php`
- `resources/views/store/purchase-returns/index.blade.php`
- `resources/views/store/purchase-returns/create.blade.php`

Work completed:

- Purchase order and purchase return status labels now resolve through translated enum helpers.
- Purchase return JavaScript validation alert now uses a translation key.
- Supplier and supplier ledger route-linked pages were inspected and remain in the active backlog for a dedicated supplier/procurement form pass.
- Purchase order workflow, receiving workflow, supplier ledger logic, accounts payable logic, and approval roles were not changed.

## HR / Employees / Staff Cleaned

Added HR localisation files:

- `lang/en/hr.php`
- `lang/fr/hr.php`

Updated HR employee views:

- `resources/views/hr/employees/index.blade.php`
- `resources/views/hr/employees/show.blade.php`
- `resources/views/hr/employees/create.blade.php`
- `resources/views/hr/employees/edit.blade.php`

Work completed:

- Employee index titles, filters, action labels, table headers, action tooltips, counts, and empty state now use translation keys.
- Employee show section headings, personal/employment/bank/leave labels, action labels, and leave request table headers now use translation keys.
- Employee create/edit enum option labels now use translated enum helpers for gender and status values.
- Employee create/edit full form label translation remains deferred.

## Attendance Cleaned

Updated active attendance views:

- `resources/views/hr/attendance/index.blade.php`
- `resources/views/hr/attendance/summary.blade.php`

Work completed:

- Attendance page title, heading, filters, summary action, form labels, status options, table headers, hour suffixes, status badges, and empty state now use translation keys.
- Attendance summary heading, action label, table headers, status columns, and empty state now use translation keys.
- Attendance recording logic and summary calculations were not changed.

## Leave Cleaned

Updated active leave views:

- `resources/views/hr/leave/index.blade.php`
- `resources/views/hr/leave/create.blade.php`

Work completed:

- Leave list title, heading, filters, request button, table headers, action labels, rejection modal labels, and empty state now use translation keys.
- Leave type/status enum labels now use translated enum helpers.
- Leave create title, heading, back button, form labels, employee/type placeholders, reason label, submit action, and policy heading now use translation keys.
- Individual leave policy bullet text remains deferred.

## Payroll Cleaned

Added payroll localisation files:

- `lang/en/payroll.php`
- `lang/fr/payroll.php`

Updated active payroll views:

- `resources/views/hr/payroll/index.blade.php`
- `resources/views/hr/payroll/payslip.blade.php`

Work completed:

- Payroll index title, heading, status filters, summary labels, action buttons, table headers, payroll components, payslip action title, empty state, and employer SSNIT note now use translation keys.
- Payroll status enum labels now use translated enum helpers.
- Payslip heading, action labels, pay period, employee/bank labels, earnings/deductions labels, net pay/status/paid labels, processed/generated labels, and system fallback now use translation keys.
- Payroll calculations, salary values, permission boundaries, and payslip logic were not changed.

## Language Files Added / Updated

Added paired EN/FR files:

- `lang/en/hr.php`
- `lang/fr/hr.php`
- `lang/en/payroll.php`
- `lang/fr/payroll.php`

Updated paired/shared files:

- `lang/en/statuses.php`
- `lang/fr/statuses.php`
- `lang/en/stock.php`
- `lang/fr/stock.php`

## JavaScript Strings Translated

Translated active inline JavaScript alerts:

- Stock adjustment line validation
- Stock return line validation
- Stock transfer line validation
- Stock requisition product validation
- Purchase return quantity validation

## Dynamic Labels Updated

Updated raw enum label rendering to translated helpers in:

- Stock requisition status labels
- Purchase order status labels
- Purchase return status labels
- Stock ledger movement type filters and rows
- Stock movement detail type label
- Stock batch type and movement labels
- HR employee gender/status labels
- Leave type/status labels
- Payroll status labels

A targeted scan for `->label()`, `->statusLabel()`, `->typeLabel()`, `getLabelAttribute()`, `displayName()`, and `humanName()` in the Phase 11 target paths returned no remaining matches.

## Responsive / Security Notes

- Existing Bootstrap `table-responsive` and card/form layouts were preserved.
- No Tailwind, package, or localisation framework was introduced.
- No stock, procurement, payroll, attendance, leave, valuation, or calculation logic was changed.
- Existing cost, salary, payroll, and valuation visibility boundaries were not broadened.

## Verification

Completed checks:

- `php artisan view:clear` passed.
- `php artisan config:clear` passed.
- `php artisan cache:clear` passed.
- `php artisan route:list` passed with 714 routes.
- `php artisan view:cache` passed.
- Final `php artisan view:clear` passed.
- PHP lint passed for all `lang/en/*.php`, `lang/fr/*.php`, and `app/Models/StockBatch.php`.
- Nested EN/FR language key parity passed with `PARITY_OK`.
- Targeted dynamic-label scan returned no remaining matches.
- `php scripts/localisation-audit.php` passed and refreshed `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.

Audit result after this batch:

- Files scanned: 1266
- Files with candidates: 516
- Candidates: 18825

## Remaining Active Backlog

Known active residual pages for later batches:

- `resources/views/store/suppliers.blade.php`
- `resources/views/store/supplier-ledger.blade.php`
- `resources/views/store/purchase-orders/create.blade.php`
- `resources/views/store/purchase-orders/show.blade.php`
- `resources/views/store/purchase-returns/create.blade.php`
- `resources/views/store/purchase-returns/show.blade.php`
- `resources/views/store/stock-requisitions/create.blade.php`
- `resources/views/store/stock-requisitions/show.blade.php`
- `resources/views/hr/employees/create.blade.php`
- `resources/views/hr/employees/edit.blade.php`
- Leave policy bullet text in `resources/views/hr/leave/create.blade.php`
- Remaining admin product and stock-location residual literals from the broader audit

