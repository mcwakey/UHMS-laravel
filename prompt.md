Next is **Accounting Phase 5: Procurement, Supplier Ledger, Accounts Payable & AP Aging**.

This phase handles the other side of accounting: **what the hospital owes suppliers**.


You are working on UHMS — Ultimate Hospital Management System.

Accounting Phase 1 Foundation is complete.
Accounting Phase 2 Billing → Accounting Posting is complete.
Accounting Phase 3 Payments, Discounts, Credit Notes, Write-offs, Refunds & Reversals is complete.
Accounting Phase 4 Sponsors, Insurance, Corporate Receivables & AR Aging is complete.

Now proceed with Accounting Phase 5:

Procurement, Supplier Ledger, Accounts Payable & AP Aging

Goal:
Implement full supplier-side accounting so UHMS can track:

1. Purchase orders
2. Goods receiving
3. Supplier invoices
4. Supplier payables
5. Supplier payments
6. Supplier returns
7. Supplier credit/debit adjustments
8. Accounts Payable aging
9. Supplier ledger accounting integration

Do not replace the existing procurement system.
Do not replace stock movements.
Do not replace supplier ledger records.
Do not automatically treat purchase orders as accounting liabilities.
Do not write the full automated test suite yet. Full tests will be written after the full accounting implementation is complete.

Important rule:

Purchase Order = procurement request/commitment, not yet accounting liability.
Goods Received / Supplier Invoice = accounting liability.
Supplier Payment = settlement of supplier liability.
Supplier Return / Credit Note = reduction of supplier liability or inventory.

---

# 1. Main Objective

Implement supplier-side accounting and AP aging.

The system must support:

- supplier payables
- supplier invoices
- goods receiving accounting
- supplier payment accounting
- supplier returns accounting
- supplier ledger integration
- AP aging report
- supplier statement
- supplier balance
- accounting posting status
- activity logs
- permissions
- manual verification notes

---

# 2. Business Meaning

Use these meanings consistently:

```text
Purchase Order = request/approval to buy; no accounting posting by default
Goods Receiving = goods physically received; may create inventory and payable
Supplier Invoice = supplier’s official billing document
Supplier Payable = amount hospital owes supplier
Supplier Payment = money paid to supplier
Supplier Return = goods returned to supplier
Supplier Credit Note = supplier reduces amount owed
AP Aging = unpaid supplier balances grouped by age
````

Do not confuse purchase order approval with supplier debt.

A PO should not increase liabilities until goods/invoice are received according to the hospital’s accounting rule.

---

# 3. Accounting Rules

## A. Purchase Order Created / Approved

Operational only.

No accounting journal entry by default.

Log procurement activity, but do not post:

```text
No debit
No credit
```

Reason:

The hospital has not yet received goods or incurred a liability.

---

## B. Goods Received

When goods are received and supplier liability should be recognized:

```text
Dr Inventory / Expense
Cr Supplier Payables
```

Example:

```text
Dr Pharmacy Inventory        2,000
Cr Supplier Payables         2,000
```

If the received item is not stock/inventory but an expense:

```text
Dr Maintenance Expense       500
Cr Supplier Payables         500
```

Use product/category/account mapping.

---

## C. Supplier Invoice Recorded

If UHMS separates goods receiving from supplier invoice:

Option 1 — Liability recognized on goods receiving:

```text
Goods receiving already posted:
Dr Inventory
Cr Supplier Payables
```

Supplier invoice only confirms/updates payable.

Option 2 — Liability recognized on supplier invoice:

```text
Dr Inventory / Expense
Cr Supplier Payables
```

Choose one rule and document it.

Recommended for UHMS:

Recognize liability on goods receiving when goods are accepted into stock.

Avoid duplicate liability when supplier invoice is later attached.

---

## D. Supplier Payment

When paying supplier:

```text
Dr Supplier Payables
Cr Cash / Bank / Mobile Money
```

Example:

```text
Dr Supplier Payables         1,000
Cr Bank Account              1,000
```

Payment reduces AP.

Payment must not be treated as expense again.

---

## E. Supplier Return

When goods are returned to supplier after receiving:

If payable is still outstanding:

```text
Dr Supplier Payables
Cr Inventory
```

If supplier already paid and refund expected:

```text
Dr Supplier Refund Receivable
Cr Inventory
```

For Phase 5, if supplier refund receivable is not implemented, reduce supplier payable where possible and document refund receivable as TODO.

---

## F. Supplier Credit Note

When supplier issues credit note:

```text
Dr Supplier Payables
Cr Inventory / Expense Adjustment
```

or if linked to returned goods:

```text
Dr Supplier Payables
Cr Inventory
```

Use project’s inventory/expense treatment.

---

## G. Supplier Debit Note

If hospital owes more due to adjustment:

```text
Dr Inventory / Expense
Cr Supplier Payables
```

Only implement if existing supplier ledger supports debit notes.

---

# 4. Required Data Model

Inspect current procurement and supplier tables first.

Search for:

```text
suppliers
supplier_ledger_entries
purchase_orders
purchase_order_items
goods_receipts
goods_receipt_items
supplier_payments
supplier_returns
purchase_returns
stock_movements
product_stock_movements
stock_balances
accounts
journal_entries
```

Use existing tables where possible.

Add accounting fields if missing:

```text
journal_entry_id nullable
accounting_status nullable: pending/posted/failed/reversed
accounting_posted_at nullable
accounting_error nullable
reversal_journal_entry_id nullable
reversed_at nullable
reversed_by nullable
reversal_reason nullable
```

Possible tables:

* goods_receipts
* purchase_receipts
* supplier_invoices
* supplier_ledger_entries
* supplier_payments
* supplier_returns
* purchase_returns

Do not duplicate supplier ledger if already exists.

---

# 5. Supplier Payables Structure

If supplier ledger already tracks balance, keep it.

But accounting must also support AP aging.

Recommended table if missing:

```text
supplier_payables
```

Fields:

```text
id
supplier_id
purchase_order_id nullable
goods_receipt_id nullable
supplier_invoice_id nullable
supplier_ledger_entry_id nullable

original_amount decimal
paid_amount decimal default 0
credit_note_amount decimal default 0
return_amount decimal default 0
adjustment_amount decimal default 0
balance decimal

invoice_date nullable
aging_start_date date
due_date nullable
status enum: pending, partially_paid, paid, overdue, cancelled, written_off

journal_entry_id nullable
accounting_status nullable
accounting_posted_at nullable
accounting_error nullable

created_by nullable
updated_by nullable
timestamps
```

Rules:

* supplier payable balance must reconcile with supplier ledger
* paid supplier payables should not appear as outstanding AP
* cancelled supplier payables should not appear as outstanding AP
* supplier returns reduce payable balance
* supplier payments reduce payable balance

If existing supplier ledger already has enough structure, avoid adding duplicate tables and instead derive AP aging from it.

---

# 6. Supplier Ledger Integration

Supplier ledger must clearly show:

```text
Date
Supplier
Type
Description
Debit
Credit
Balance
Source
Journal Entry
Created By
```

Recommended convention:

```text
Credit = amount hospital owes supplier
Debit = amount paid/reduced
Balance = Credits - Debits
```

Examples:

Goods received worth 5,000:

```text
Credit = 5,000
```

Supplier payment of 2,000:

```text
Debit = 2,000
```

Return to supplier worth 500:

```text
Debit = 500
```

Outstanding balance:

```text
Credits - Debits
```

---

# 7. Inventory / Expense Account Mapping

Create or update:

```text
ProcurementAccountingPostingService
SupplierAccountingPostingService
InventoryAccountResolver
ExpenseAccountResolver
SupplierPayableAccountResolver
PaymentAccountResolver
```

Inventory account resolution priority:

```text
1. Product-specific inventory account
2. Product category/type inventory account
3. Department/location inventory account
4. Accounting settings inventory account
5. Accounting settings default inventory account
```

Expense account resolution priority:

```text
1. Expense category account
2. Supplier invoice line account
3. Department expense account
4. Accounting settings default expense account
```

Supplier payable account:

```text
supplier_payable_account_id
```

Payment account:

* Cash → default_cash_account_id
* Bank → default_bank_account_id
* Mobile Money → default_mobile_money_account_id

Fail clearly if required account is missing.

Do not silently post to random accounts.

---

# 8. Goods Receiving Posting

When goods are received:

Operational actions:

* create goods receipt
* create stock movement IN
* update stock balance
* create supplier ledger credit
* create or update supplier payable

Accounting posting:

```text
Dr Inventory
Cr Supplier Payables
```

If multiple product categories map to different inventory accounts:

```text
Dr Pharmacy Inventory              1,200
Dr Medical Consumables Inventory     800
Cr Supplier Payables               2,000
```

Do not duplicate posting if stock movement retries.

Use the goods receipt / supplier ledger source as the accounting source.

---

# 9. Supplier Payment Posting

When supplier payment is recorded:

Operational actions:

* create supplier payment
* create supplier ledger debit
* reduce supplier payable balance

Accounting posting:

```text
Dr Supplier Payables
Cr Bank / Cash / Mobile Money
```

Rules:

* payment amount must be greater than zero
* payment cannot exceed supplier outstanding balance unless supplier advance workflow exists
* supplier advance/prepayment can be documented as TODO if not supported
* payment must be idempotently posted once

---

# 10. Purchase Return / Supplier Return Posting

When goods are returned to supplier:

Operational actions:

* create purchase return
* create stock movement OUT
* reduce stock balance
* create supplier ledger debit or credit note
* reduce supplier payable balance

Accounting posting:

```text
Dr Supplier Payables
Cr Inventory
```

If goods were already paid and supplier owes refund:

* either create supplier refund receivable if supported
* or document as TODO

Rules:

* returned quantity cannot exceed received/available quantity
* do not post return twice
* do not create duplicate stock movements
* do not duplicate supplier ledger entry

---

# 11. AP Aging

Implement Accounts Payable Aging report.

Aging buckets:

```text
Current / Not Due
0–30 days
31–60 days
61–90 days
91–120 days
120+ days
```

AP Aging should show:

```text
Supplier
Reference
Purchase Order
Goods Receipt / Supplier Invoice
Original Amount
Paid
Returns / Credits
Balance
Aging Start Date
Due Date
Age Days
Bucket
Status
```

Summary:

```text
0–30
31–60
61–90
91–120
120+
Total
```

Rules:

* include only unpaid supplier payables
* exclude paid/cancelled fully settled records
* due date should come from supplier terms or invoice date + default payment terms
* if no due date, age from aging_start_date

---

# 12. Supplier Statement

Add supplier statement view/report.

Supplier statement should show:

```text
Opening Balance
Goods Received / Supplier Invoices
Payments
Returns
Credit Notes
Debit Notes
Closing Balance
```

With date filters.

This can use supplier ledger entries.

---

# 13. UI Updates

Add or update menu under Store / Procurement and Accounts & Finance.

Recommended:

```text
Accounts & Finance
├── Accounts Payable
│   ├── Supplier Payables
│   ├── Supplier Payments
│   ├── Supplier Statements
│   └── AP Aging
```

Supplier details page should show:

* profile
* purchase orders
* goods received
* ledger
* payments
* returns
* outstanding balance
* AP aging
* journal entries

Goods receipt detail should show:

```text
Accounting Status
Journal Entry
Supplier Payable Status
```

Supplier payment detail should show:

```text
Accounting Status
Journal Entry
```

Purchase return detail should show:

```text
Accounting Status
Journal Entry
```

---

# 14. Activity Logs

Use ActivityLogService.

Do not create a separate audit system.

Operational events:

```text
GOODS_RECEIVED
SUPPLIER_PAYABLE_CREATED
SUPPLIER_PAYMENT_RECORDED
SUPPLIER_PAYMENT_REVERSED
PURCHASE_RETURN_CREATED
PURCHASE_RETURN_APPROVED
PURCHASE_RETURN_POSTED
SUPPLIER_CREDIT_NOTE_RECORDED
SUPPLIER_DEBIT_NOTE_RECORDED
```

Accounting events:

```text
ACCOUNTING_POSTED_FOR_GOODS_RECEIPT
ACCOUNTING_POSTED_FOR_SUPPLIER_PAYMENT
ACCOUNTING_POSTED_FOR_PURCHASE_RETURN
ACCOUNTING_POSTING_FAILED
ACCOUNTING_REVERSAL_CREATED
```

Context:

```text
supplier_id
purchase_order_id
goods_receipt_id
supplier_payment_id
purchase_return_id
supplier_ledger_entry_id
journal_entry_id
stock_movement_id
amount
old_values
new_values
```

Do not attach patient_id/visit_id.

Supplier/procurement logs are facility-level logs.

---

# 15. Permissions

Add or verify:

```text
accounts_payable.view
accounts_payable.payment.record
accounts_payable.payment.reverse
accounts_payable.aging.view
accounts_payable.statement.view

supplier_payables.view
supplier_payables.manage

supplier_payments.view
supplier_payments.create
supplier_payments.reverse

purchase_returns.view
purchase_returns.create
purchase_returns.approve
purchase_returns.post
purchase_returns.cancel

reports.ap_aging.view
reports.supplier_statement.view
```

Only authorized finance/procurement/admin users should record supplier payments or approve purchase returns.

---

# 16. Reversal Behavior

Do not delete posted financial records.

For reversal:

* reverse supplier payment by creating reversal journal entry
* reverse supplier ledger impact if existing workflow supports it
* reverse purchase return only through controlled cancellation/reversal
* reverse goods receipt only if stock/procurement workflow supports it

Do not edit posted journal entries.

Use JournalEntryService::reverse() where possible.

---

# 17. Idempotency

Every source posts once.

Use:

```text
journal_entry_id
accounting_status
accounting_posted_at
accounting_error
reversal_journal_entry_id
```

Rules:

* retry failed posting must not duplicate journal entry
* posting same goods receipt twice is blocked
* posting same supplier payment twice is blocked
* posting same purchase return twice is blocked
* reversal twice is blocked

---

# 18. Manual Verification Strategy

Do not write the full automated test suite yet.

Full accounting tests will be written after all accounting phases are implemented.

For this phase, provide manual verification notes.

Manual verification required:

1. Create purchase order and confirm no accounting journal is posted.
2. Receive goods and confirm stock balance increases.
3. Confirm supplier ledger credit is created.
4. Confirm supplier payable is created.
5. Confirm goods receipt journal debits inventory and credits supplier payable.
6. Confirm multiple inventory accounts are used when products map differently.
7. Record supplier payment and confirm payable balance reduces.
8. Confirm supplier payment journal debits supplier payable and credits cash/bank.
9. Create purchase return and confirm stock reduces.
10. Confirm purchase return reduces supplier payable.
11. Confirm purchase return journal debits supplier payable and credits inventory.
12. Confirm duplicate posting is prevented.
13. Confirm AP Aging shows unpaid supplier balances in correct buckets.
14. Confirm paid supplier payables disappear from outstanding AP.
15. Confirm supplier statement shows goods received, payments, returns, and balance.
16. Confirm Trial Balance remains balanced.
17. Confirm General Ledger shows procurement/AP postings.
18. Confirm activity logs are written.
19. Confirm unauthorized users cannot record supplier payment or post purchase return.
20. Confirm logs:audit Stage-2 gate remains green.
21. Confirm existing procurement, stock, billing, and supplier workflows still work.

Do not skip validation, permissions, accounting posting, activity logs, or idempotency because tests are deferred.

---

# 19. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_5_PROCUREMENT_AP_AGING_REPORT.md
```

Include:

* goods receiving accounting rule
* supplier invoice/payable treatment
* supplier ledger convention
* supplier payment posting
* purchase return posting
* AP aging buckets
* supplier statement behavior
* account mappings
* UI changes
* permissions
* activity logs
* manual verification completed
* tests deferred list
* known risks/TODOs
* next phase recommendation

---

# 20. Acceptance Criteria

Phase 5 is complete when:

* purchase orders do not create accounting liability by default
* goods receiving can create supplier payable
* goods receiving posts Dr Inventory / Cr Supplier Payable
* supplier payments post Dr Supplier Payable / Cr Cash/Bank
* supplier returns post Dr Supplier Payable / Cr Inventory where applicable
* supplier ledger reconciles with supplier payable balance
* AP Aging report works
* Supplier Statement works
* accounting status appears on procurement/AP records
* duplicate posting is prevented
* reversals are controlled
* Trial Balance remains balanced
* General Ledger shows AP/procurement postings
* activity logs are written
* logs:audit Stage-2 gate remains green
* manual verification is documented
* full automated tests remain deferred until final accounting implementation pass

---

# 21. Important Rules

Do not treat purchase order as accounting liability.
Do not treat supplier payment as expense.
Do not duplicate supplier ledger entries.
Do not duplicate stock movements.
Do not duplicate journal entries.
Do not post the same goods receipt twice.
Do not silently post to random accounts.
Do not edit posted journal entries.
Do not bypass stock movement service.
Do not bypass supplier ledger service.
Do not bypass ActivityLogService.
Do not enable full automated tests yet.

Proceed with Accounting Phase 5: Procurement, Supplier Ledger, Accounts Payable & AP Aging now.
