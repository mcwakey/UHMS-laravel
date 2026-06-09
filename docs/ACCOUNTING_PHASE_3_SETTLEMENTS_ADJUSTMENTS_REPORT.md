# Accounting Phase 3 Settlements & Adjustments Report

Date: 2026-06-09

## Scope

Phase 3 hardened billing settlement and adjustment workflows after Phase 2 connected billing to accounting.

Covered operational actions:

- Payments
- Payment reversals/refunds
- Discounts
- Discount reversals/reductions
- Credit notes
- Write-offs
- Credit note/write-off reversals
- Invoice adjustment history
- Invoice balance formula display
- Posting retry and idempotency hardening

Operational records remain the source of truth. Accounting journal entries are generated from those records.

## Invoice Balance Formula

Invoice detail now exposes the standardized formula:

```text
Gross Total
- Discounts
- Credit Notes
- Write-offs
- Payments
+ Refunds / Reversals
= Outstanding Balance
```

The formula is computed by `InvoiceBalanceService`.

If the displayed formula balance differs from the stored invoice balance, the invoice page shows a warning.

## Payment Workflow

Supported behavior:

- Payment creates an operational payment record.
- Payment allocations reduce invoice item balances.
- Invoice totals/status recalculate after payment.
- Accounting posts:

```text
Dr Cash / Bank / Mobile Money
Cr Receivable
```

Hardening:

- Payment amount must be greater than zero.
- Overpayment remains blocked unless a later patient deposit workflow is implemented.
- Payment posting is idempotent.
- Reversed original payments are not reposted.
- Payment history and the unified adjustment history show accounting status and journal links.

## Refund / Payment Reversal Workflow

Refunds are currently modeled as controlled payment reversals.

Supported behavior:

- Original payment is not deleted.
- A negative reversal payment is created.
- Invoice item paid amounts are reduced.
- Invoice balance increases by the refunded amount.
- Accounting posts:

```text
Dr Receivable
Cr Cash / Bank / Mobile Money
```

Hardening:

- Original payment stores `reversal_journal_entry_id`.
- Refund/reversal payment has its own `journal_entry_id`.
- Double reversal is blocked.
- Reversal requires reason.
- Permission aliases are supported:
  - `payments.refund`
  - `billing.refund.issue`
  - `billing.refund.reverse`
- Activity logs include `PAYMENT_REVERSED` and `REFUND_ISSUED`.

Deposit refunds are documented as a future TODO because patient deposit workflow is not yet implemented.

## Discount Workflow

Supported behavior:

- Discount creates an `invoice_discounts` operational record.
- Discount requires reason and authorized user.
- Discount reduces item patient payable and invoice balance.
- Accounting posts after invoice recognition:

```text
Dr Discount Allowed
Cr Receivable
```

If discount is applied before invoice recognition, it is absorbed into the net invoice posting to avoid duplicate reduction.

Hardening:

- Any reduction of an existing discount now requires reversal/remove permission, not only setting discount to zero.
- Discount reversal/reduction creates an offsetting discount event.
- Discount reversal can link to the prior discount event through `reverses_discount_id`.
- Original discount events can store `reversal_journal_entry_id`, `reversed_at`, `reversed_by`, and `reversal_reason`.
- Permission aliases are supported:
  - `billing.discount.remove`
  - `billing.discount.reverse`

## Credit Note Workflow

Supported behavior:

- Credit note creates an operational `credit_notes` record.
- Credit note requires reason and authorized user.
- Credit note reduces invoice collectible balance through `adjustment_amount`.
- Accounting posts:

```text
Dr Credit Note / Revenue Adjustment
Cr Receivable
```

Hardening:

- Credit note cannot exceed available collectible balance.
- Credit note cancellation does not delete the record.
- Cancellation creates accounting reversal through `JournalEntryService::reverse`.
- Reversal metadata is stored on the credit note.
- Permission aliases are supported:
  - `credit_notes.create`
  - `billing.credit_note.issue`
  - `billing.credit_note.reverse`

## Write-off Workflow

Write-offs continue to use the existing `credit_notes` table with `type = write_off`.

Supported behavior:

- Write-off creates an operational record.
- Write-off requires reason and high-risk authorization.
- Write-off reduces collectible balance.
- Accounting posts:

```text
Dr Bad Debt / Write-off Expense
Cr Receivable
```

Hardening:

- Write-off logs are distinct from ordinary credit notes:
  - `WRITE_OFF_ISSUED`
  - `WRITE_OFF_REVERSED`
  - `ACCOUNTING_POSTED_FOR_WRITE_OFF`
- Reversal metadata is stored on the record.
- Permission aliases are supported:
  - `credit_notes.write_off`
  - `billing.write_off.issue`
  - `billing.write_off.reverse`

Approval workflow is permission-based for now. A separate approval engine was not introduced.

## Reversal Rules

Implemented principles:

- Original operational records are not deleted.
- Posted journals are not edited.
- Reversals create offsetting journals.
- Reversal reason is required.
- Reversal metadata is stored where appropriate.
- Double reversal is blocked for payments and blocked by credit-note status for credit notes/write-offs.

## UI Changes

Invoice detail page now includes:

- Standardized formula summary
- Accounting status in summary
- Formula mismatch warning
- Unified `Adjustments & Settlements` table with:
  - Date
  - Type
  - Reference
  - Amount
  - Reason
  - Status
  - Approved By
  - Journal Entry
  - Action
- Retry buttons for failed postings when the user has `accounting.posting.retry`

Existing discount history and payment history remain for detail-level review.

## Permissions Added

Added compatibility/high-risk permissions:

- billing.discount.reverse
- billing.credit_note.issue
- billing.credit_note.approve
- billing.credit_note.reverse
- billing.write_off.issue
- billing.write_off.approve
- billing.write_off.reverse
- billing.refund.issue
- billing.refund.approve
- billing.refund.reverse

Default role updates:

- Super Admin/Admin receive all via `Permission::all()`.
- Accountant receives finance workflow aliases.
- Cashier does not receive refund/write-off/reversal permissions by default.

## Activity Logs

Operational logs covered through service funnels:

- PAYMENT_RECORDED
- PAYMENT_REVERSED
- REFUND_ISSUED
- DISCOUNT_APPLIED
- DISCOUNT_OVERRIDE_APPLIED
- DISCOUNT_REMOVED
- CREDIT_NOTE_ISSUED
- CREDIT_NOTE_REVERSED
- WRITE_OFF_ISSUED
- WRITE_OFF_REVERSED

Accounting logs covered:

- ACCOUNTING_POSTED_FOR_PAYMENT
- ACCOUNTING_POSTED_FOR_REFUND
- ACCOUNTING_POSTED_FOR_DISCOUNT
- ACCOUNTING_POSTED_FOR_CREDIT_NOTE
- ACCOUNTING_POSTED_FOR_WRITE_OFF
- ACCOUNTING_REVERSAL_CREATED
- ACCOUNTING_POSTING_FAILED

## Manual Verification Completed

Commands run:

- `php artisan migrate`
- `php artisan db:seed --class=RoleSeeder`
- `php artisan view:cache`
- `php artisan view:clear`
- `php artisan route:list --path=billing`
- `php artisan route:list --path=accounting`
- `php artisan permissions:audit`
- `php artisan logs:audit`
- PHP syntax checks on touched PHP files

Manual smoke:

- Created temporary invoice and invoice item inside a transaction.
- Posted invoice recognition.
- Applied discount.
- Issued credit note.
- Recorded payment.
- Reversed payment as refund.
- Confirmed invoice balance was `85.00`.
- Confirmed formula balance matched stored invoice balance.
- Confirmed unified history returned four rows.
- Confirmed refund accounting status was `posted`.
- Rolled back transaction.
- Confirmed journal entry count remained `0`.

Audit results:

- `permissions:audit`: no route-referenced permissions missing; no admin mutation route missing can/role middleware.
- `logs:audit`: billing/payment/credit-note controller changes are service-funnel covered. Existing unrelated logging backlog remains.

## Tests Deferred

Full automated tests remain deferred until the final accounting implementation pass.

Deferred tests:

- Payment posting and reversal
- Discount posting and reversal
- Credit note posting and reversal
- Write-off posting and reversal
- Refund/deposit refund behavior
- Duplicate posting prevention
- Failed posting retry
- Permission denial for high-risk settlement actions
- Trial balance/general ledger assertions across all accounting phases

## Known Risks / TODOs

- Patient deposit liability workflow is not implemented; overpayments remain blocked and deposit refunds are deferred.
- Approval is permission-based, not queue/workflow-based. A future approval engine could add pending/approved states for discounts, write-offs, and refunds.
- AR aging can use clean balances now, but full AR aging reporting remains a future phase.
- Credit notes/write-offs are invoice-level only in the current workflow; item-level credit note linkage remains a future enhancement.
- A global failed-postings worklist/report would improve accounting operations.

## Next Phase Recommendation

Proceed to AR aging, receivables reporting, and failed-posting operational dashboards after remaining accounting source modules are connected.
