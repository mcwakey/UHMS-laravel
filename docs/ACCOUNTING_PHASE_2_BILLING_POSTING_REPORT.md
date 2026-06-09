# Accounting Phase 2 Billing Posting Report

Date: 2026-06-09

## Scope

Implemented automated double-entry posting for UHMS billing events using the Accounting Phase 1 foundation.

Covered source events:

- Invoice item revenue recognition
- Patient/insurance/sponsor/corporate receivable recognition
- Payment collection
- Payment reversal offsets
- Billing discounts after invoice recognition
- Credit notes
- Write-offs
- Invoice recognition reversal on invoice cancellation

## Schema Updates

Added accounting posting fields to:

- invoices
- invoice_items
- payments
- invoice_discounts
- credit_notes

Fields added:

- journal_entry_id
- accounting_posted_at
- accounting_status
- accounting_error

Statuses used:

- pending
- posted
- failed
- reversed

## Services Added

- App\Services\BillingAccountingPostingService
- App\Services\PaymentAccountingPostingService
- App\Services\ReceivableAccountingService
- App\Services\RevenueAccountResolver
- App\Services\PaymentAccountResolver
- App\Services\AccountingPostingRetryService

## Posting Rules Implemented

Invoice item billing:

- Debit receivable account
- Credit revenue account
- Uses item-level idempotency so running invoices can post new items without duplicating older posted items.

Payment collection:

- Debit cash, bank, or mobile money account
- Credit receivable account
- Payment is not treated as revenue.

Discounts:

- If discount is applied before invoice item posting, it is absorbed into the net invoice posting.
- If discount is applied after invoice item posting, it creates a separate entry:
  - Debit discount/allowance account
  - Credit receivable account
- Discount removal creates an offsetting discount reversal entry.

Credit notes/write-offs:

- Debit credit note/write-off account
- Credit receivable account

Payment reversals:

- Create an offsetting journal through the reversal payment row.
- Original payment accounting status is marked reversed after the offset posts.

Invoice cancellation:

- Reverses posted invoice recognition journals where available.

## Account Resolution

Receivables:

- cash/patient -> patient_receivable_account_id
- insurance -> insurance_receivable_account_id
- corporate with sponsor -> sponsor_receivable_account_id
- corporate without sponsor -> corporate_receivable_account_id

Payment accounts:

- cash -> default_cash_account_id
- mobile money -> default_mobile_money_account_id
- bank transfer/card/cheque/insurance settlement -> default_bank_account_id

Revenue accounts:

- consultation -> consultation_revenue_account_id
- investigation/lab/radiology -> laboratory_revenue_account_id
- pharmacy/products -> pharmacy_revenue_account_id
- procedures/theatre -> procedure_revenue_account_id
- admission/ward/bed charges -> admission_revenue_account_id
- emergency -> emergency_revenue_account_id
- fallback -> default_revenue_account_id

## Backend Enforcement

- Automated postings are generated inside service-layer billing/payment/credit-note workflows.
- Manual journals still respect the control-account lock.
- Automated source postings are allowed to post to receivable control accounts.
- Failed postings are not silently ignored: source records are marked failed and store the accounting error.
- Retry endpoint is backend-protected by accounting.posting.retry.

## UI Updates

Invoice detail page:

- Shows invoice accounting status.
- Shows linked journal entry.
- Shows accounting failure details when permitted.
- Shows retry button for failed invoice postings when permitted.

Payment history:

- Shows accounting status and linked journal entry.
- Shows retry/failure details when permitted.

Discount history:

- Shows accounting status and linked journal entry.
- Shows retry/failure details when permitted.

Journal entry detail page:

- Shows a source record link back to invoice/payment/discount/credit note where available.

## Permissions Added

- accounting.posting.view
- accounting.posting.retry
- accounting.posting.reverse
- accounting.posting.failure.view

Default role assignment:

- Super Admin/Admin: all via Permission::all()
- Accountant: view, retry, failure view
- Critical reverse permission is not assigned to lower-trust roles by default.

## Verification

Commands run:

- php artisan migrate
- php artisan db:seed --class=RoleSeeder
- php artisan route:list --path=accounting
- php artisan route:list --path=billing
- php artisan view:cache
- php artisan view:clear
- php artisan permissions:audit
- php artisan logs:audit
- PHP syntax checks on touched PHP files

Rollback smoke tests:

- Temporary invoice posting created a balanced journal: posted, debit 25.00, credit 25.00.
- Temporary payment posting created a balanced journal: posted, debit 25.00, credit 25.00.
- Both smoke tests were wrapped in transactions and rolled back.
- Journal entry count after smoke tests remained 0.

Audit results:

- permissions:audit: no route-referenced permissions missing.
- logs:audit: accounting/billing/payment controller changes are covered by service funnels. Existing unrelated logging backlog remains.

## Remaining TODOs

- Add formal feature tests for invoice, payment, discount, credit-note, write-off, and reversal posting paths.
- Add configurable service-specific and department-specific revenue account mappings if finance needs more granularity than current setting buckets.
- Add sponsor/insurance allocation-specific receivable splitting if future invoices contain mixed payer allocations on the same invoice.
- Add an accounting posting worklist/report for failed postings across all source tables.
- Add explicit source-level reversal UI guarded by accounting.posting.reverse if finance wants reversal outside operational cancellation/reversal workflows.
