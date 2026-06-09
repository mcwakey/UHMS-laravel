Next is **Accounting Phase 2: Billing → Accounting Posting**.

Now that the accounting foundation exists, we connect billing events to journal entries without replacing invoices/payments.

You are working on UHMS — Ultimate Hospital Management System.

Accounting Phase 1 Foundation is complete.

Now proceed with Accounting Phase 2:

Billing → Accounting Posting

Goal:
When billing events happen in UHMS, the system should automatically generate proper double-entry journal entries using the accounting foundation.

Do not replace the existing billing system.
Do not remove invoices, invoice items, payments, discounts, credit notes, write-offs, refunds, sponsor allocations, or insurance claims.
Do not break the existing billing workflow.
Do not post stock/procurement/payroll accounting yet unless directly required by billing.
Do not write the full automated test suite yet. Full tests will be done after the whole accounting implementation is complete.

Important rule:

Operational billing records remain operational.
Journal entries are accounting records generated from billing records.

Example:

Invoice = operational record.
Journal entry = accounting impact of that invoice.

---

# 1. Main Objective

Automatically create accounting journal entries for billing-related transactions:

1. Invoice creation / invoice finalization
2. Invoice item billing
3. Patient receivable recognition
4. Revenue recognition by department/service type
5. Payments
6. Discounts
7. Credit notes
8. Write-offs
9. Refunds
10. Patient deposits if already supported
11. Sponsor allocation if already supported
12. Insurance receivable if already supported

This phase should focus on billing and receivables.

---

# 2. Accounting Principle

Every billing posting must satisfy:

Total Debits = Total Credits

Never create unbalanced journal entries.

Never silently ignore a posting failure.

If accounting posting fails, decide safely:

- either block the financial transaction, or
- save the operational transaction and mark accounting posting as failed/pending

Use the safest existing project convention.

Recommended:

For high-risk finalized financial transactions, posting should happen in the same transaction if possible.

---

# 3. Required Posting Events

Implement automatic journal posting for these billing events.

---

## A. Invoice Finalized / Invoice Posted

When an invoice is finalized or becomes officially billable:

```text
Debit: Patient Receivables / Insurance Receivables / Sponsor Receivables
Credit: Revenue Account
````

Example:

Invoice item:

* Consultation service: GHS 100

Posting:

```text
Dr Patient Receivables      100
Cr Consultation Revenue     100
```

If invoice has multiple item types:

```text
Dr Patient Receivables      500
Cr Consultation Revenue     100
Cr Laboratory Revenue       150
Cr Pharmacy Revenue         200
Cr Procedure Revenue         50
```

Use revenue account mapping by:

* service department
* service type
* invoice item source
* billing category
* default revenue account fallback

Do not use one revenue account for everything if better mappings exist.

---

## B. Payment Received

When a payment is recorded:

```text
Debit: Cash / Bank / Mobile Money Account
Credit: Receivable Account
```

Example:

```text
Dr Cash on Hand             300
Cr Patient Receivables      300
```

Payment account depends on payment method:

* Cash → default_cash_account_id
* Bank → default_bank_account_id
* Mobile Money → default_mobile_money_account_id
* Card/POS → bank or clearing account if configured

Do not treat payment as revenue again.

Revenue was recognized when the invoice was posted.

---

## C. Discount Applied

When a discount is approved/applied:

```text
Debit: Discount Account
Credit: Receivable Account
```

Example:

```text
Dr Discount Allowed         50
Cr Patient Receivables      50
```

Discount reduces the amount collectible but does not delete invoice items.

---

## D. Credit Note Issued

When a credit note is issued:

```text
Debit: Credit Note / Revenue Adjustment Account
Credit: Receivable Account
```

Example:

```text
Dr Credit Note Adjustment   80
Cr Patient Receivables      80
```

Credit note is for invoice correction/adjustment, not patient payment.

---

## E. Write-off Approved

When write-off is approved:

```text
Debit: Bad Debt / Write-off Expense
Credit: Receivable Account
```

Example:

```text
Dr Bad Debt Expense         200
Cr Patient Receivables      200
```

Write-off means the hospital forgives or accepts the uncollectible balance.

---

## F. Refund Issued

When a refund is issued:

If refund reverses a patient overpayment:

```text
Debit: Patient Refund Liability / Patient Receivable
Credit: Cash / Bank
```

Use existing UHMS refund logic.

Recommended simple approach:

If refund reduces overpaid patient balance:

```text
Dr Patient Receivables / Patient Deposits / Refund Payable
Cr Cash or Bank
```

Do not treat refund as expense unless project accounting rules require it.

---

## G. Patient Deposit Received

If patient deposit/prepayment exists:

When deposit is received before invoice:

```text
Dr Cash / Bank
Cr Patient Deposit Liability
```

When deposit is applied to invoice:

```text
Dr Patient Deposit Liability
Cr Patient Receivables
```

Only implement if deposit workflow already exists.

Do not create a new deposit workflow unless necessary.

---

## H. Sponsor Allocation

If sponsor allocation exists:

When invoice responsibility is allocated to sponsor:

```text
Dr Sponsor Receivables
Cr Patient Receivables
```

or, if invoice is directly billed to sponsor:

```text
Dr Sponsor Receivables
Cr Revenue
```

Use the current billing design.

Important:

Sponsor does not reduce invoice total.
Sponsor shifts responsibility from patient to sponsor.

---

## I. Insurance Receivable

If insurance/claims receivable exists:

When invoice responsibility is allocated to insurance:

```text
Dr Insurance Receivables
Cr Patient Receivables
```

or, if invoice is directly billed to insurance:

```text
Dr Insurance Receivables
Cr Revenue
```

Use existing insurance billing workflow.

Do not hardcode NHIS.
NHIS is just one insurance provider/type.

---

# 4. Avoid Duplicate Posting

Every operational record should be posted once.

Add fields where needed:

```text
journal_entry_id nullable
accounting_posted_at nullable
accounting_status nullable: pending/posted/failed/reversed
accounting_error nullable
```

Possible tables:

* invoices
* payments
* invoice_discounts
* credit_notes
* write_offs
* refunds
* sponsor allocations
* insurance claim allocations

Use existing columns if already present.

Do not create duplicate journal entries if the user retries the action.

Posting must be idempotent.

---

# 5. Accounting Source Metadata

Every journal entry generated from billing should include:

```text
source_module = BILLING / PAYMENTS / CLAIMS / SPONSORS
reference_type = model class or source type
reference_id = source model id
description = clear human-readable text
```

Every journal line should include relevant context:

```text
patient_id
visit_id
invoice_id
department_id
supplier_id nullable
sponsor_id nullable
insurance_provider_id nullable
reference_type
reference_id
```

---

# 6. Services to Create / Update

Create or update:

```text
BillingAccountingPostingService
PaymentAccountingPostingService
ReceivableAccountingService
RevenueAccountResolver
PaymentAccountResolver
AccountingPostingService
AccountingSettingsService
JournalEntryService
```

Controllers should not contain accounting logic.

Operational services should call posting services after successful billing actions.

Suggested methods:

```php
BillingAccountingPostingService::postInvoice(Invoice $invoice): JournalEntry
PaymentAccountingPostingService::postPayment(Payment $payment): JournalEntry
BillingAccountingPostingService::postDiscount(InvoiceDiscount $discount): JournalEntry
BillingAccountingPostingService::postCreditNote(CreditNote $creditNote): JournalEntry
BillingAccountingPostingService::postWriteOff(WriteOff $writeOff): JournalEntry
BillingAccountingPostingService::postRefund(Refund $refund): JournalEntry
```

Use actual existing model names.

---

# 7. Revenue Account Mapping

Implement account resolution.

Priority order:

```text
1. Service-specific revenue account if configured
2. Department revenue account if configured
3. Invoice item source/category account mapping
4. Accounting settings default revenue account
```

Examples:

* consultation service → Consultation Revenue
* lab investigation → Laboratory Revenue
* pharmacy product → Pharmacy Revenue
* procedure/theatre service → Procedure Revenue
* admission charge → Admission Revenue
* emergency consultation → Emergency Revenue

If no mapping exists, use default_revenue_account_id.

If even default revenue account is missing, fail clearly:

```text
No revenue account configured for this billing item.
```

Do not silently post to a random account.

---

# 8. Receivable Account Mapping

Resolve receivable account based on payer responsibility.

Possible payer types:

```text
patient
insurance
sponsor
corporate
```

Mapping:

* patient → patient_receivable_account_id
* insurance → insurance_receivable_account_id
* sponsor → sponsor_receivable_account_id
* corporate → corporate_receivable_account_id

If invoice is mixed responsibility, split receivable lines by payer.

Example:

Invoice total: GHS 1,000
Patient responsible: GHS 300
Insurance responsible: GHS 700

Posting:

```text
Dr Patient Receivables       300
Dr Insurance Receivables     700
Cr Revenue                 1,000
```

If current UHMS does not yet support split payer responsibility, post to Patient Receivables for now and document payer-split as TODO for Phase 4.

---

# 9. Payment Account Mapping

Resolve payment account based on payment method.

Examples:

```text
Cash → Cash on Hand
Bank Transfer → Bank Account
Mobile Money → Mobile Money Account
Card/POS → Bank/POS Clearing Account
```

Use existing payment method model/config if available.

Do not hardcode payment methods only in service logic if the system has configurable methods.

---

# 10. Reversal Behavior

If an operational financial transaction is reversed, cancelled, or voided:

Do not delete the original journal entry.

Create reversal journal entry.

Examples:

Payment reversed:

```text
Original:
Dr Cash
Cr Patient Receivable

Reversal:
Dr Patient Receivable
Cr Cash
```

Credit note reversed:

```text
Original:
Dr Credit Note Adjustment
Cr Patient Receivable

Reversal:
Dr Patient Receivable
Cr Credit Note Adjustment
```

Use JournalEntryService::reverse() or equivalent.

---

# 11. Invoice Status and Accounting Timing

Decide when invoice is posted to accounting.

Recommended:

Post accounting when invoice becomes:

```text
FINALIZED
POSTED
APPROVED
ISSUED
```

Do not post draft invoices.

If UHMS currently creates invoices immediately as official bills, then post on creation only if invoice is not draft.

Document the rule.

Avoid posting incomplete draft billing lines.

---

# 12. Emergency / Admission Billing

Emergency and Admission may use running bills.

Rules:

* emergency service can be rendered before payment
* admission charges can accumulate
* accounting revenue should post when invoice item becomes billable/finalized according to billing design
* payments reduce receivables
* do not block emergency care because accounting posting is pending unless project policy says so

Document how running-bill invoices are posted.

Recommended:

* invoice item can be operationally added during emergency/admission
* accounting post happens when invoice is finalized, or when item is approved as billable
* choose one consistent rule

---

# 13. Activity Logs

Do not create a separate accounting audit system.

Use existing ActivityLogService.

Log accounting posting events:

```text
ACCOUNTING_POSTED_FOR_INVOICE
ACCOUNTING_POSTED_FOR_PAYMENT
ACCOUNTING_POSTED_FOR_DISCOUNT
ACCOUNTING_POSTED_FOR_CREDIT_NOTE
ACCOUNTING_POSTED_FOR_WRITE_OFF
ACCOUNTING_POSTING_FAILED
ACCOUNTING_REVERSAL_CREATED
```

Context:

```text
invoice_id
payment_id
discount_id
credit_note_id
write_off_id
journal_entry_id
patient_id
visit_id
source_module
old_values
new_values
error message if failed
```

Avoid duplicate billing logs.

Billing logs say “invoice created/payment recorded”.
Accounting logs say “journal entry posted for invoice/payment”.

Both are different.

---

# 14. UI Updates

On invoice detail page, show accounting status:

```text
Accounting Status: Posted / Pending / Failed / Reversed
Journal Entry: JE-2026-000123
```

On payment detail/history, show journal entry if posted.

On accounting journal entry page, show source link:

```text
Source: Invoice INV-2026-00045
Source: Payment PAY-2026-00033
```

If posting failed, show friendly message to authorized users:

```text
Accounting posting failed: Missing revenue account for Laboratory Revenue.
```

Do not expose internal stack traces.

---

# 15. Permissions

Add or verify:

```text
accounting.posting.view
accounting.posting.retry
accounting.posting.reverse
accounting.posting.failure.view
```

Only authorized finance/admin users should retry failed postings.

Normal billing users should not manually manipulate accounting journals unless they also have accounting permissions.

---

# 16. Manual Retry

If posting fails because of missing account mapping, allow authorized user to retry after fixing settings.

Recommended:

```php
AccountingPostingRetryService::retry(Model $source): JournalEntry
```

or buttons:

```text
Retry Accounting Posting
```

Available only when:

```text
accounting_status = failed
```

Do not create duplicate journal entry on retry.

---

# 17. Manual Verification Strategy

Do not write the full automated test suite yet.

Full accounting tests will be written after all accounting phases are implemented.

For this phase, provide manual verification notes.

Manual verification required:

1. Finalize/create invoice and confirm journal entry is created.
2. Confirm invoice journal debits receivable and credits revenue.
3. Confirm invoice with multiple item categories credits correct revenue accounts.
4. Record payment and confirm cash/bank/mobile money is debited.
5. Confirm payment credits receivable, not revenue.
6. Apply discount and confirm discount account is debited.
7. Issue credit note and confirm credit note adjustment account is debited.
8. Approve write-off and confirm bad debt/write-off expense is debited.
9. Confirm outstanding balance is correct after payment/discount/credit/write-off.
10. Confirm no duplicate journal entry is created on retry.
11. Confirm reversal creates reversing journal entry.
12. Confirm invoice detail shows accounting status and journal link.
13. Confirm failed posting can be retried after fixing settings.
14. Confirm emergency/admission billing still works.
15. Confirm OPD billing still works.
16. Confirm logs:audit Stage-2 gate still passes.
17. Confirm Trial Balance remains balanced after billing postings.
18. Confirm General Ledger shows invoice/payment postings.

Do not skip validation, permissions, audit logs, or idempotency because tests are deferred.

---

# 18. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_2_BILLING_POSTING_REPORT.md
```

Include:

* billing events wired
* posting rules
* account mappings
* invoice accounting timing
* emergency/admission running bill treatment
* payment treatment
* discount/credit note/write-off treatment
* reversal handling
* UI changes
* permissions
* manual verification completed
* tests deferred list
* known risks/TODOs
* next phase recommendation

---

# 19. Acceptance Criteria

Phase 2 is complete when:

* invoices can generate balanced journal entries
* payments generate balanced journal entries
* discounts generate balanced journal entries
* credit notes generate balanced journal entries
* write-offs generate balanced journal entries
* refunds/reversals are handled if supported
* journal entries are idempotent
* accounting status appears on billing records
* failed postings can be retried by authorized users
* Trial Balance remains balanced
* General Ledger shows billing postings
* emergency/admission billing remains functional
* OPD billing remains functional
* Activity logs capture accounting posting events
* logs:audit Stage-2 gate remains green
* manual verification is documented
* full automated tests remain deferred until final accounting implementation pass

---

# 20. Important Rules

Do not replace invoices with journal entries.
Do not replace payments with journal entries.
Do not treat payments as revenue.
Do not delete invoice items when posting adjustments.
Do not create duplicate journal entries.
Do not post draft invoices unless the current billing design treats them as official.
Do not hardcode NHIS.
Do not hardcode all revenue to one account.
Do not block emergency care because of OPD payment rules.
Do not create a separate accounting audit system.
Do not bypass ActivityLogService.
Do not bypass permissions.
Do not skip validation.
Do not enable full automated tests yet.

Proceed with Accounting Phase 2: Billing → Accounting Posting now.
