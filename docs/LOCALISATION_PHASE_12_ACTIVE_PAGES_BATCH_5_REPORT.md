# UHMS Localisation Phase 12 - Active Pages Translation Batch 5

Date: 2026-06-12

## Scope

Phase 12 Batch 5 continued from the active route/page inventory and focused on a high-value runtime slice across:

- Billing, invoices, payments, receipts, statements, and aging print/PDF surfaces
- Claims list/create/detail/review enum and form-label cleanup
- Insurance provider enum labels and verification driver labels
- Cash/account entry list/create dynamic labels
- Queue and ward/bed management enum labels

No demo/template/sample views or `routes/web.php.bak`-only pages were translated.

## Active Routes / Views Checked

Active route-linked views checked in this batch included:

- `billing.invoices.index`
- `billing.invoices.create`
- `billing.invoices.show`
- `billing.invoices.print`
- `billing.invoices.invoice-pdf`
- `billing.payments.index`
- `billing.payments.receive`
- `billing.payments.receipt`
- `billing.payments.receipt-pdf`
- `billing.reports.aging-pdf`
- `billing.statements.statement-pdf`
- `claims.index`
- `claims.create`
- `claims.show`
- `claims.review`
- `accounts.categories`
- `accounts.daily-collection`
- `accounts.entries.index`
- `accounts.entries.create`
- `accounts.reconciliation`
- `insurance.index`
- `insurance._verification_fields`
- `queue.manage`
- `wards.beds`
- `wards.bed-map`

## Billing / Invoices / Payments Cleaned

Updated active billing views:

- `resources/views/billing/invoices/index.blade.php`
- `resources/views/billing/invoices/create.blade.php`
- `resources/views/billing/invoices/show.blade.php`
- `resources/views/billing/invoices/print.blade.php`
- `resources/views/billing/invoices/invoice-pdf.blade.php`
- `resources/views/billing/payments/index.blade.php`
- `resources/views/billing/payments/receive.blade.php`
- `resources/views/billing/payments/receipt.blade.php`
- `resources/views/billing/payments/receipt-pdf.blade.php`
- `resources/views/billing/reports/aging-pdf.blade.php`
- `resources/views/billing/statements/statement-pdf.blade.php`

Work completed:

- Invoice status, billing type, visit status, payment method, payer type, receivable status, credit note type, statement ledger type, and aging report payer/status labels now use translated labels.
- Source fallback labels for invoice/receipt print surfaces now use translation keys instead of title-casing raw values.
- Existing invoice, payment, receivable, credit note, posting, discount, refund, and print logic was not changed.

## Claims / Insurance Cleaned

Added paired claims language files:

- `lang/en/claims.php`
- `lang/fr/claims.php`

Updated active claims and insurance views:

- `resources/views/claims/index.blade.php`
- `resources/views/claims/create.blade.php`
- `resources/views/claims/show.blade.php`
- `resources/views/claims/review.blade.php`
- `resources/views/insurance/index.blade.php`
- `resources/views/insurance/_verification_fields.blade.php`

Work completed:

- Claims index title, actions, stats, filters, table headers, action labels, and empty state now use `claims.*` keys.
- Claims create page headings, provider/patient/visit/doctor labels, invoice claim section labels, claim item table labels, add-row JavaScript service type options, and delete tooltips now use translation keys or translated enum helpers.
- Claims show/review service type and member type enum labels now use translated helpers.
- Insurance provider type labels and verification driver labels now resolve through translation keys.
- Claims workflow, review, submission, appeal, payment, and provider logic was not changed.

## Accounting / Cashier Entry Labels Cleaned

Updated active accounts/cash views:

- `resources/views/accounts/categories.blade.php`
- `resources/views/accounts/daily-collection.blade.php`
- `resources/views/accounts/entries/index.blade.php`
- `resources/views/accounts/entries/create.blade.php`
- `resources/views/accounts/reconciliation.blade.php`

Work completed:

- Entry type, account category type, and payment method enum labels now use translated helpers.
- Entry index title, heading, record action, stats, filters, table headers, approval status labels, and approve action now use translation keys.
- Entry create title, heading, detail heading, back action, and submit action now use translation keys.
- Cashier, reconciliation, collection, approval, and accounting posting logic was not changed.

## Queue / Wards / Beds Cleaned

Updated active shared workflow/ward views:

- `resources/views/queue/manage.blade.php`
- `resources/views/wards/beds.blade.php`
- `resources/views/wards/bed-map.blade.php`

Work completed:

- Queue visit type labels now use translated enum helpers.
- Bed status and bed type filters, table cells, badges, and modal options now use translated enum helpers.
- Bed map bed type labels now use translated enum helpers.
- Queue, admission, bed assignment, and permission logic was not changed.

## Language Files Added / Updated

Added:

- `lang/en/claims.php`
- `lang/fr/claims.php`

Updated:

- `lang/en/accounting.php`
- `lang/fr/accounting.php`
- `lang/en/statuses.php`
- `lang/fr/statuses.php`

New shared status/default keys include payment methods, payer types, insurance types, verification drivers, bed types, bed statuses, account entry types, and source fallback labels.

## Dynamic Labels Updated

Updated raw enum label rendering to translated helpers in:

- Invoice status and billing type labels
- Visit status labels on invoice print/detail pages
- Payment method labels on payment, receipt, reconciliation, and account entry pages
- Credit note type labels on invoice PDF pages
- Insurance type labels on insurance and claim pages
- Claim member/service type labels
- Account entry/category type labels
- Queue visit type labels
- Bed type/status labels

A targeted scan for `->label()`, `ucfirst()`, `ucwords()`, and `str_replace('_', ' ', ...)` in the Phase 12 target paths returned no remaining matches.

## Deferred Items

Known active residual surfaces for later batches:

- Deeper claim show/review visible copy beyond the enum/form cleanup done here
- Remaining insurance provider form/help text
- Cashier handover full-page copy
- Accounts daily collection/reconciliation remaining table and modal literals
- Accounting report pages, journals, fiscal years, periods, accounts payable, and accounting settings
- Billing Inertia pages for sponsors, credit notes, dashboard, statements, aging, and discounts
- Reports hub and operational/report print pages
- Settings/users/roles/departments/modules pages
- Wider shared layout/component audit allowlist cleanup

## Verification

Completed checks:

- `php artisan view:clear` passed.
- `php artisan config:clear` passed.
- `php artisan cache:clear` passed.
- `php artisan route:list` passed with 714 routes.
- `php artisan view:cache` passed.
- Final `php artisan view:clear` passed.
- PHP lint passed for all `lang/en/*.php` and `lang/fr/*.php`.
- Nested EN/FR language key parity passed with `PARITY_OK`.
- Targeted dynamic-label scan returned no remaining matches.
- `php scripts/localisation-audit.php` passed and refreshed `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.

Audit result after this batch:

- Files scanned: 1268
- Files with candidates: 518
- Candidates: 18751

