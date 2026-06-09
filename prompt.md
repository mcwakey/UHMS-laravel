Next is **Accounting Phase 3: Payments, Discounts, Credit Notes, Write-offs, Refunds & Reversals hardening**.

Phase 2 connected billing to accounting. Phase 3 makes sure all adjustment/payment actions are financially clean, reversible, permission-protected, and reportable.

You are working on UHMS — Ultimate Hospital Management System.

Accounting Phase 1 Foundation is complete.
Accounting Phase 2 Billing → Accounting Posting is complete.

Now proceed with Accounting Phase 3:

Payments, Discounts, Credit Notes, Write-offs, Refunds & Reversals Hardening

Goal:
Make all billing settlement and adjustment workflows financially correct, accounting-aware, auditable, reversible, and safe.

This phase focuses on:

1. Payments
2. Discounts
3. Credit Notes
4. Write-offs
5. Refunds
6. Overpayments
7. Reversals
8. Invoice balance recalculation
9. Accounting posting consistency
10. Permission and approval rules
11. Manual verification documentation

Do not replace the current billing system.
Do not replace invoices with journal entries.
Do not replace payments with journal entries.
Do not delete invoice items.
Do not silently edit posted accounting journals.
Do not write the full automated test suite yet. Full tests will be done after the full accounting implementation is complete.

Important rule:

Operational records remain operational.
Accounting records are generated from operational records.

---

# 1. Main Objective

Ensure every payment or invoice adjustment has:

- clear operational record
- correct invoice balance impact
- correct accounting journal entry
- activity log
- permission enforcement
- approval rules where needed
- reversal workflow
- no duplicate posting
- visible history on invoice detail page

---

# 2. Core Difference Between Actions

Use these business meanings consistently:

```text
Payment = money received
Discount = hospital-approved price reduction
Credit Note = invoice correction or adjustment
Write-off = approved forgiveness of unpaid balance
Refund = money returned to patient/payer
````

Do not mix these.

Examples:

* patient pays cash → Payment
* management gives 10% reduction → Discount
* duplicate lab test billed → Credit Note
* patient cannot pay old debt → Write-off
* patient overpaid and hospital returns money → Refund

---

# 3. Invoice Balance Formula

Standardize invoice balance calculation.

Recommended formula:

```text
gross_invoice_total
- discounts
- credit_notes
- write_offs
- payments
+ refunds
= outstanding_balance
```

But if refunds are modeled as reversing payments, make sure the final balance effect is equivalent.

Invoice detail must clearly show:

```text
Gross Total
Discounts
Credit Notes
Write-offs
Payments
Refunds
Outstanding Balance
```

Do not hide adjustments inside payment totals.

---

# 4. Payment Workflow

When a payment is recorded:

Operational effect:

* create payment record
* apply payment to invoice
* reduce outstanding balance
* update invoice payment status
* show payment in payment history

Accounting effect:

```text
Dr Cash / Bank / Mobile Money
Cr Receivable
```

Rules:

* payment must not be treated as revenue
* payment amount must be greater than zero
* payment cannot exceed outstanding balance unless overpayment/deposit workflow exists
* payment method must resolve to valid accounting account
* payment must be idempotently posted once
* payment reversal must create reversal journal entry

If overpayment is allowed, move excess to:

```text
Patient Deposit Liability
```

or document as TODO if deposit workflow is not ready.

---

# 5. Discount Workflow

When discount is applied:

Operational effect:

* create invoice discount record
* require reason
* require authorized user
* reduce invoice outstanding balance
* show discount in invoice adjustment history

Accounting effect:

```text
Dr Discount Allowed
Cr Receivable
```

Rules:

* discount must not delete or change original invoice items
* discount must be permission protected
* discount may require approval depending amount/percentage
* discount cannot exceed remaining eligible balance
* discount reversal must create reversal journal entry

Permissions:

```text
billing.discount.apply
billing.discount.approve
billing.discount.override_limit
billing.discount.reverse
```

Use existing permissions if already implemented.

---

# 6. Credit Note Workflow

When credit note is issued:

Operational effect:

* create credit note record
* require reason
* optionally require approval
* reduce invoice outstanding balance
* show credit note in invoice adjustment history
* optionally link to invoice item if correcting a specific item

Accounting effect:

```text
Dr Credit Note / Revenue Adjustment
Cr Receivable
```

Rules:

* credit note is for correction/adjustment
* credit note must not delete invoice item
* credit note may be invoice-level or item-level
* credit note cannot exceed eligible invoice balance
* credit note should have number/reference
* credit note reversal must create reversal journal entry

Permissions:

```text
billing.credit_note.issue
billing.credit_note.approve
billing.credit_note.reverse
```

---

# 7. Write-off Workflow

When write-off is approved:

Operational effect:

* create write-off record
* require reason
* require approval
* reduce collectible balance
* show write-off in invoice adjustment history
* update AR aging so written-off balance is no longer collectible

Accounting effect:

```text
Dr Bad Debt / Write-off Expense
Cr Receivable
```

Rules:

* write-off means the hospital forgives/uncollectible balance
* write-off must be high-risk permission protected
* ordinary cashier must not write off unless explicitly permitted
* write-off cannot exceed outstanding collectible balance
* write-off reversal must create reversal journal entry
* write-off should appear in write-off report

Permissions:

```text
billing.write_off.issue
billing.write_off.approve
billing.write_off.reverse
```

---

# 8. Refund Workflow

When refund is issued:

Operational effect:

* create refund record
* link to payment/invoice/patient where possible
* require reason
* require approval
* increase invoice outstanding balance if refund reverses payment
* or reduce patient deposit liability if refunding deposit
* show refund in invoice/payment history

Accounting effect depends on refund source.

If refund reverses patient payment:

```text
Dr Receivable
Cr Cash / Bank / Mobile Money
```

If refund returns patient deposit:

```text
Dr Patient Deposit Liability
Cr Cash / Bank / Mobile Money
```

Rules:

* refund must not be treated as expense by default
* refund must not be treated as negative revenue by default
* refund must be permission protected
* refund cannot exceed refundable amount
* refund should have reference/number
* refund reversal must be carefully permission protected

Permissions:

```text
billing.refund.issue
billing.refund.approve
billing.refund.reverse
```

---

# 9. Reversal Workflow

Every posted financial action must be reversible only through a controlled reversal.

Do not delete original records.

Do not edit posted journals.

For reversal:

* mark original operational record as reversed/cancelled if appropriate
* create reversal operational record or reversal metadata
* create reversal journal entry with debit/credit swapped
* link reversal to original source
* require reason
* require authorized user
* log the reversal

Examples:

Payment reversal:

```text
Original:
Dr Cash
Cr Patient Receivable

Reversal:
Dr Patient Receivable
Cr Cash
```

Discount reversal:

```text
Original:
Dr Discount Allowed
Cr Patient Receivable

Reversal:
Dr Patient Receivable
Cr Discount Allowed
```

Write-off reversal:

```text
Original:
Dr Bad Debt Expense
Cr Patient Receivable

Reversal:
Dr Patient Receivable
Cr Bad Debt Expense
```

---

# 10. Idempotency / Duplicate Prevention

Every operational record should post once.

Use or add fields:

```text
journal_entry_id
accounting_status
accounting_posted_at
accounting_error
reversed_at
reversed_by
reversal_reason
reversal_journal_entry_id
```

Apply where appropriate:

* payments
* invoice_discounts
* credit_notes
* write_offs
* refunds

Rules:

* retry failed posting must not duplicate journal entry
* reversing twice must be blocked
* posting reversed/cancelled source must be blocked
* posting amount must match operational amount
* accounting status must be visible to authorized users

---

# 11. Invoice Adjustment History UI

On invoice detail page, add a clear section:

```text
Adjustments & Settlements
```

Show:

```text
Date
Type
Reference
Amount
Reason
Status
Approved By
Journal Entry
Action
```

Types:

```text
Payment
Discount
Credit Note
Write-off
Refund
Reversal
```

Invoice summary should show:

```text
Gross Total
Discounts
Credit Notes
Write-offs
Payments
Refunds
Outstanding Balance
Accounting Status
```

Do not hide adjustments inside payment list only.

---

# 12. Approval Rules

Implement or align with existing approval rules.

Recommended:

## Discount

* small discount may be applied directly by authorized billing officer
* high discount requires approval
* override limit requires higher permission

## Credit Note

* issuing requires billing credit note permission
* approval may be required depending amount

## Write-off

* approval should always be required or restricted to management
* high severity audit event

## Refund

* approval should be required
* must reference original payment/deposit where possible

Use existing approval system if available.

Do not create a parallel approval engine if one exists.

---

# 13. Accounting Posting Services

Create or update:

```text
PaymentSettlementService
InvoiceAdjustmentService
CreditNoteService
WriteOffService
RefundService
FinancialReversalService
InvoiceBalanceService
BillingAccountingPostingService
PaymentAccountingPostingService
AccountingPostingRetryService
```

Use existing services if already present.

Controllers should remain thin.

Do not put accounting logic directly in controllers.

---

# 14. Account Mapping

Use AccountingSettingsService.

Required accounts:

```text
patient_receivable_account_id
insurance_receivable_account_id
sponsor_receivable_account_id
corporate_receivable_account_id

default_cash_account_id
default_bank_account_id
default_mobile_money_account_id

default_discount_account_id
default_credit_note_account_id
default_write_off_account_id
default_refund_account_id
patient_deposit_liability_account_id
rounding_difference_account_id
```

Fail clearly if required account is missing:

```text
Accounting posting failed: Missing default credit note account.
```

Do not silently post to a random account.

---

# 15. Activity Logs

Use ActivityLogService.

Log operational events:

```text
PAYMENT_RECORDED
PAYMENT_REVERSED
DISCOUNT_APPLIED
DISCOUNT_APPROVED
DISCOUNT_REVERSED
CREDIT_NOTE_ISSUED
CREDIT_NOTE_APPROVED
CREDIT_NOTE_REVERSED
WRITE_OFF_ISSUED
WRITE_OFF_APPROVED
WRITE_OFF_REVERSED
REFUND_ISSUED
REFUND_APPROVED
REFUND_REVERSED
```

Log accounting events:

```text
ACCOUNTING_POSTED_FOR_PAYMENT
ACCOUNTING_POSTED_FOR_DISCOUNT
ACCOUNTING_POSTED_FOR_CREDIT_NOTE
ACCOUNTING_POSTED_FOR_WRITE_OFF
ACCOUNTING_POSTED_FOR_REFUND
ACCOUNTING_REVERSAL_CREATED
ACCOUNTING_POSTING_FAILED
```

Avoid duplicates.

Billing operational log and accounting posting log are different.

Context:

```text
patient_id
visit_id
invoice_id
payment_id
discount_id
credit_note_id
write_off_id
refund_id
journal_entry_id
amount
old_balance
new_balance
reason
approved_by
reversed_by
```

---

# 16. AR Aging Impact

Prepare adjustment impact for AR aging.

Rules:

* payments reduce receivable balance
* discounts reduce receivable balance
* credit notes reduce receivable balance
* write-offs remove amount from collectible AR and classify as written off
* refunds may increase receivable or reduce deposit liability depending source

If AR aging module is not yet implemented, expose clean balances so Phase 4 can build on them.

Do not build full AR aging here unless already easy from existing balance data.

---

# 17. Manual Verification Strategy

Do not write the full automated test suite yet.

Full accounting tests will be written after all accounting phases are implemented.

For this phase, provide manual verification notes.

Manual verification required:

1. Record payment and confirm invoice balance reduces.
2. Confirm payment journal debits cash/bank/mobile money and credits receivable.
3. Reverse payment and confirm reversal journal is created.
4. Apply discount and confirm invoice balance reduces.
5. Confirm discount journal debits discount account and credits receivable.
6. Reverse discount and confirm balance/journal reverses.
7. Issue credit note and confirm balance reduces.
8. Confirm credit note journal debits credit note account and credits receivable.
9. Reverse credit note and confirm balance/journal reverses.
10. Approve write-off and confirm collectible balance reduces.
11. Confirm write-off journal debits bad debt/write-off expense and credits receivable.
12. Reverse write-off and confirm receivable returns.
13. Issue refund and confirm correct cash/bank credit.
14. Confirm refund does not post as expense by default.
15. Confirm invoice detail shows adjustment history.
16. Confirm duplicate posting is prevented.
17. Confirm missing account mapping creates clear failed status.
18. Confirm authorized user can retry failed posting.
19. Confirm unauthorized user cannot issue/approve/reverse high-risk adjustments.
20. Confirm Trial Balance remains balanced.
21. Confirm General Ledger shows all postings.
22. Confirm logs:audit Stage-2 gate still passes.
23. Confirm existing billing, emergency, admission, pharmacy, and claims workflows still work.

Do not skip validation, permissions, audit logs, or idempotency because tests are deferred.

---

# 18. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_3_SETTLEMENTS_ADJUSTMENTS_REPORT.md
```

Include:

* payment workflow
* discount workflow
* credit note workflow
* write-off workflow
* refund workflow
* reversal rules
* accounting postings
* invoice balance formula
* approval/permission rules
* UI changes
* activity logs
* manual verification completed
* tests deferred list
* known risks/TODOs
* next phase recommendation

---

# 19. Acceptance Criteria

Phase 3 is complete when:

* payments are correctly posted and reversible
* discounts are correctly posted and reversible
* credit notes are correctly posted and reversible
* write-offs are correctly posted and reversible
* refunds are correctly posted and reversible if supported
* invoice outstanding balance formula is consistent
* adjustment history is visible on invoice
* high-risk actions require permissions/approval
* duplicate posting is prevented
* failed posting can be retried safely
* Trial Balance remains balanced
* General Ledger shows settlement/adjustment postings
* Activity logs are written
* logs:audit Stage-2 gate remains green
* manual verification is documented
* full automated tests remain deferred until final accounting implementation pass

---

# 20. Important Rules

Do not delete invoice items.
Do not silently change original invoice item prices.
Do not treat payment as revenue.
Do not treat refund as expense by default.
Do not treat sponsor payment as discount.
Do not treat write-off as payment.
Do not treat credit note as payment.
Do not edit posted journal entries.
Do not reverse by deleting journals.
Do not duplicate postings.
Do not bypass permissions.
Do not bypass ActivityLogService.
Do not enable full automated tests yet.

Proceed with Accounting Phase 3: Payments, Discounts, Credit Notes, Write-offs, Refunds & Reversals Hardening now.
