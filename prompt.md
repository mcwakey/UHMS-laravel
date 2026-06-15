Yes — now we move to the **final accounting phase**.

Your Phase 7 report confirms that the main financial reports, closing controls, reconciliation warnings, and dashboard/report routes are in place, with full automated testing intentionally deferred. It also clearly lists the final follow-ups: year-end closing entry, reopen workflow, report exports, GL-summary dashboard cards, and the full automated accounting test suite. 

So the next step is:

```text
Accounting Phase 8: Final Test Suite, Audit, Stabilization & Accounting Hardening
```

You are working on UHMS — Ultimate Hospital Management System.

Accounting Phases 1–7 are complete:

Phase 1: Accounting Foundation
Phase 2: Billing → Accounting Posting
Phase 3: Payments, Discounts, Credit Notes, Write-offs, Refunds & Reversals
Phase 4: Sponsors, Insurance, Corporate Receivables & AR Aging
Phase 5: Procurement, Supplier Ledger, Accounts Payable & AP Aging
Phase 6: Stock Valuation, COGS, Consumables Expense & Inventory Accounting
Phase 7: Financial Reports, Closing Controls & Management Dashboards

Now proceed with Accounting Phase 8:

Final Test Suite, Audit, Stabilization & Accounting Hardening

This is the final accounting pass.

Goal:
Write the full automated accounting test suite, stabilize all accounting flows, complete final missing accounting governance items, verify report correctness, and ensure UHMS accounting is reliable end-to-end.

This phase should also complete the known Phase 7 follow-ups:

1. Year-end closing entry
2. Period/fiscal reopen workflow
3. GL-summary accounting dashboard cards
4. Report exports / print where existing utilities support it
5. Full automated test suite across Phases 1–7

Do not rewrite the whole accounting system.
Do not replace operational records with journals.
Do not weaken accounting validation.
Do not bypass ActivityLogService.
Do not remove manual verification documentation.
Do not disable logs:audit Stage-2 gate.

---

# 1. Main Objective

Complete the accounting implementation by adding:

- automated tests
- accounting reconciliation checks
- year-end closing entry
- period/fiscal reopen workflow
- dashboard GL summary cards
- report export/print support
- failed posting regression tests
- permission tests
- activity log tests
- final documentation
- stabilization fixes

---

# 2. Accounting Rules to Protect

Every test and fix must protect these core rules:

```text
Total Debits = Total Credits
Assets = Liabilities + Equity
Operational records remain operational
Journal entries record financial impact
Posted journals cannot be edited
Closed periods cannot receive postings
Financial reversals use reversing journals
Payments are not revenue
Write-offs are not payments
Sponsors are not discounts
Insurance is not hardcoded as NHIS
Stock transfers are not expenses
Purchase orders are not liabilities by default
````

---

# 3. Year-End Closing Entry

Implement the year-end closing entry if not already done.

When closing a fiscal year:

* ensure all periods are closed
* ensure Trial Balance is balanced
* calculate net profit/loss
* close income accounts
* close expense accounts
* transfer net result to retained earnings or current year earnings
* create a posted journal entry
* link the closing entry to the fiscal year
* log the action
* prevent duplicate year-end closing entries

Example if profit:

```text
Dr Income Accounts
Cr Expense Accounts
Cr Retained Earnings / Current Year Earnings
```

Example if loss:

```text
Dr Retained Earnings / Current Year Earnings
Dr Income Accounts
Cr Expense Accounts
```

Use the proper debit/credit mechanics from account normal balances.

Do not silently update retained earnings without a journal entry.

---

# 4. Period / Fiscal Reopen Workflow

Implement controlled reopen actions if not already done.

Required actions:

```text
ACCOUNTING_PERIOD_REOPENED
FISCAL_YEAR_REOPENED
```

Rules:

* only authorized users can reopen
* reason is required
* reopening fiscal year may require reopening at least one period
* reopening must be logged
* reopening should not modify existing journals
* reopening should allow new postings only after status is open again
* closed fiscal year should not be reopened if later fiscal year closing depends on it unless policy permits

Permissions:

```text
accounting.periods.reopen
accounting.fiscal_years.reopen
```

---

# 5. Accounting Dashboard GL Summary Cards

Update the accounting dashboard to use GL-backed values.

Cards:

```text
Cash / Bank Balance
Total Receivables
Total Payables
Inventory Value
Revenue This Month
Expenses This Month
Net Profit This Month
Failed Accounting Postings
Open Fiscal Year
Open Accounting Period
```

Rules:

* cash/bank balance from posted journal lines hitting cash/bank accounts
* receivables from GL control accounts, with reconciliation warning against operational receivables
* payables from GL supplier payable account, with reconciliation warning against supplier payables
* inventory from GL inventory accounts, with reconciliation warning against stock valuation
* revenue/expense/profit from posted journal entries only
* failed postings from accounting_status = failed sources if available

Do not calculate official dashboard accounting values from invoices/payments only.

---

# 6. Report Export / Print

Add export/print support where existing UHMS report utilities already support it.

Reports:

```text
General Ledger
Trial Balance
Profit & Loss
Balance Sheet
Cashbook
AR Aging
AP Aging
Inventory Valuation
Revenue by Department
Expense by Department
```

Preferred:

```text
Print view
PDF if existing PDF utility exists
Excel/CSV if existing export utility exists
```

Do not build a heavy new export framework if one already exists.

Permissions:

```text
accounting.exports
```

Log:

```text
ACCOUNTING_REPORT_EXPORTED
```

Context:

```text
report_type
export_type
date_from
date_to
fiscal_year_id
accounting_period_id
user_id
```

---

# 7. Full Automated Test Suite

Now write the full automated test suite that was deferred.

Organize tests by accounting phase.

Recommended test files:

```text
tests/Feature/Accounting/AccountingFoundationTest.php
tests/Feature/Accounting/BillingAccountingPostingTest.php
tests/Feature/Accounting/SettlementAdjustmentAccountingTest.php
tests/Feature/Accounting/ReceivablesARAgingTest.php
tests/Feature/Accounting/ProcurementAPAgingTest.php
tests/Feature/Accounting/InventoryAccountingTest.php
tests/Feature/Accounting/AccountingReportsClosingTest.php
tests/Feature/Accounting/AccountingPermissionsAuditTest.php
```

Use project test conventions.

Do not create brittle tests that depend on unstable UI text where service-level testing is cleaner.

---

# 8. Phase 1 Tests — Accounting Foundation

Test:

1. Account can be created.
2. Duplicate account code is rejected.
3. Account normal balance is set correctly.
4. Parent/child accounts work.
5. Fiscal year can be created.
6. Accounting period can be created.
7. Period must be inside fiscal year.
8. Draft journal can be created.
9. Unbalanced journal cannot be posted.
10. Journal with one line cannot be posted.
11. Journal line cannot have both debit and credit.
12. Journal line cannot have neither debit nor credit.
13. Balanced journal can be posted.
14. Posted journal cannot be edited.
15. Posted journal can be reversed.
16. Reversal swaps debit and credit.
17. Posting into closed period is blocked.
18. Posting into closed fiscal year is blocked.
19. Accounting actions are logged.

---

# 9. Phase 2 Tests — Billing Posting

Test:

1. Invoice finalization creates journal entry.
2. Invoice posting debits receivable.
3. Invoice posting credits correct revenue account.
4. Multi-category invoice credits multiple revenue accounts.
5. Payment debits cash/bank/mobile money.
6. Payment credits receivable.
7. Payment is not posted as revenue.
8. Discount debits discount account and credits receivable.
9. Credit note debits credit note adjustment and credits receivable.
10. Write-off debits bad debt/write-off expense and credits receivable.
11. Refund posts correctly.
12. Duplicate invoice posting is prevented.
13. Failed posting stores accounting_error.
14. Retry after fixing mapping creates one journal only.
15. Emergency/admission billing does not get blocked by OPD payment rules.

---

# 10. Phase 3 Tests — Settlements, Adjustments, Reversals

Test:

1. Payment reduces invoice balance.
2. Payment reversal restores balance.
3. Discount reduces invoice balance.
4. Discount reversal restores balance.
5. Credit note reduces invoice balance.
6. Credit note reversal restores balance.
7. Write-off reduces collectible balance.
8. Write-off reversal restores receivable.
9. Refund affects balance correctly.
10. Adjustment history appears on invoice.
11. Approval-required actions enforce permissions.
12. Unauthorized user cannot issue write-off/refund.
13. Reversal requires reason.
14. Reversing twice is blocked.
15. Posted journal is never edited during reversal.

---

# 11. Phase 4 Tests — Receivables / AR Aging

Test:

1. Patient receivable is created.
2. Insurance receivable is created.
3. Sponsor receivable is created.
4. Corporate receivable is created if supported.
5. Payer allocations cannot exceed invoice net total.
6. Sponsor allocation does not reduce invoice total.
7. Insurance is not hardcoded to NHIS.
8. Patient payment reduces patient receivable only.
9. Insurance payment reduces insurance receivable only.
10. Sponsor payment reduces sponsor receivable only.
11. Corporate payment reduces corporate receivable only.
12. Reallocation does not double-recognize revenue.
13. Paid receivables disappear from outstanding AR.
14. Written-off receivables do not appear as collectible AR.
15. AR Aging buckets are correct.
16. AR summary reconciles or warns against GL.

---

# 12. Phase 5 Tests — Procurement / AP Aging

Test:

1. Purchase order does not create accounting liability.
2. Goods receiving creates supplier payable.
3. Goods receiving posts Dr Inventory / Cr Supplier Payable.
4. Supplier ledger credit is created.
5. Supplier payment reduces payable.
6. Supplier payment posts Dr Supplier Payable / Cr Cash or Bank.
7. Supplier return reduces inventory and payable.
8. Purchase return posts Dr Supplier Payable / Cr Inventory.
9. Duplicate goods receipt posting is blocked.
10. Supplier statement shows goods received, payments, returns.
11. Paid supplier payables disappear from AP Aging.
12. AP Aging buckets are correct.
13. AP summary reconciles or warns against GL.

---

# 13. Phase 6 Tests — Inventory Accounting

Test:

1. Weighted average cost updates on stock receipt.
2. Stock balance total value updates.
3. Pharmacy dispense reduces stock.
4. Pharmacy dispense posts Dr COGS / Cr Inventory.
5. Billing revenue is not duplicated by inventory posting.
6. Consumable usage posts Dr Expense / Cr Inventory.
7. Stock adjustment increase posts Dr Inventory / Cr Adjustment Gain.
8. Stock adjustment decrease posts Dr Adjustment Loss / Cr Inventory.
9. Damaged stock posts Dr Damaged Stock Expense / Cr Inventory.
10. Expired stock posts Dr Expired Stock Expense / Cr Inventory.
11. Same-account transfer creates no journal.
12. Different-account transfer posts Dr destination inventory / Cr source inventory.
13. Inventory valuation report matches stock balances.
14. Unauthorized users cannot see cost fields.
15. Duplicate stock accounting posting is blocked.

---

# 14. Phase 7 Tests — Reports, Closing, Dashboard

Test:

1. General Ledger uses posted entries only.
2. General Ledger excludes draft/cancelled journals.
3. General Ledger running balance is correct.
4. Trial Balance debit total equals credit total.
5. Profit & Loss calculates revenue, COGS, expenses, net profit.
6. Balance Sheet includes current year earnings.
7. Balance Sheet validates Assets = Liabilities + Equity.
8. Cashbook shows money in/out and running balance.
9. AR Aging summary appears on dashboard/report.
10. AP Aging summary appears on dashboard/report.
11. Inventory valuation summary appears.
12. Revenue by department groups correctly.
13. Expense by department groups correctly.
14. Closing a period blocks further posting.
15. Closing fiscal year requires all periods closed.
16. Year-end closing entry is created once.
17. Reopen period/fiscal year requires permission and reason.
18. Reconciliation warnings appear when operational and GL balances differ.

---

# 15. Permission Tests

Test unauthorized users cannot:

```text
create accounts
post journals
reverse journals
close periods
reopen periods
view restricted accounting reports
export accounting reports
issue write-offs
approve refunds
record supplier payments
view inventory costs
```

Test authorized users can perform allowed actions.

Do not rely only on hidden buttons.

Backend must enforce permissions.

---

# 16. Activity Log Tests

Verify ActivityLogService logs:

```text
ACCOUNT_CREATED
JOURNAL_ENTRY_POSTED
JOURNAL_ENTRY_REVERSED
ACCOUNTING_POSTED_FOR_INVOICE
ACCOUNTING_POSTED_FOR_PAYMENT
ACCOUNTING_POSTED_FOR_DISCOUNT
ACCOUNTING_POSTED_FOR_CREDIT_NOTE
ACCOUNTING_POSTED_FOR_WRITE_OFF
ACCOUNTING_POSTED_FOR_GOODS_RECEIPT
ACCOUNTING_POSTED_FOR_SUPPLIER_PAYMENT
ACCOUNTING_POSTED_FOR_STOCK_DISPENSE
ACCOUNTING_POSTING_FAILED
ACCOUNTING_PERIOD_CLOSED
ACCOUNTING_PERIOD_REOPENED
FISCAL_YEAR_CLOSED
FISCAL_YEAR_REOPENED
YEAR_END_CLOSING_ENTRY_CREATED
ACCOUNTING_REPORT_EXPORTED
```

Check context includes relevant IDs:

```text
invoice_id
payment_id
journal_entry_id
supplier_id
stock_movement_id
patient_id where applicable
visit_id where applicable
```

Do not attach patient context to generic facility-level supplier/admin logs.

---

# 17. Reconciliation Tests

Add tests or verification commands for:

```text
Trial Balance balanced
Balance Sheet balanced
Receivables GL vs invoice_receivables
Supplier Payables GL vs supplier_payables
Inventory GL vs stock valuation
Cashbook GL vs cash/bank accounts
```

Where mismatch exists, system should show warning, not crash.

---

# 18. Failed Posting Tests

Test:

1. Missing revenue account causes posting failure.
2. Missing receivable account causes posting failure.
3. Missing inventory account causes posting failure.
4. Missing supplier payable account causes posting failure.
5. Failure stores accounting_status = failed.
6. Failure stores accounting_error.
7. Authorized retry works after fixing setting.
8. Retry does not duplicate journal entry.
9. Unauthorized retry is blocked.

---

# 19. logs:audit / CI Verification

Run and keep green:

```bash
composer logs:audit
composer logs:audit:json
composer logs:audit:stage2
php artisan logs:audit --fail --only-real-gaps --min-severity=HIGH
```

No new HIGH/CRITICAL MISSING_LOG may be introduced.

If new mutating accounting controllers are added, make sure they reach ActivityLogService through service funnels.

Do not bypass the Stage-2 gate.

---

# 20. Report Export Verification

If export/print is implemented:

Verify:

1. General Ledger export works.
2. Trial Balance export works.
3. Profit & Loss export works.
4. Balance Sheet export works.
5. Cashbook export works.
6. AR Aging export works.
7. AP Aging export works.
8. Inventory Valuation export works.
9. Export respects filters.
10. Export requires permission.
11. Export logs ACCOUNTING_REPORT_EXPORTED.

---

# 21. Stabilization / Refactoring Rules

During this final pass:

* remove duplicate accounting helper logic
* ensure controllers stay thin
* keep posting rules in services
* keep account resolution in resolver services
* keep reporting logic in report services
* keep journal validation centralized
* avoid massive unrelated refactors
* fix only accounting-related issues discovered by the tests

Do not refactor unrelated clinical, stock, billing, emergency, or UI modules unless required by accounting correctness.

---

# 22. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_8_TESTING_AUDIT_STABILIZATION_REPORT.md
```

Include:

* test files added
* test coverage by phase
* year-end closing entry implementation
* reopen workflow implementation
* dashboard improvements
* export/print support
* reconciliation checks
* bugs found and fixed
* manual verification retained
* remaining known risks
* deployment notes
* migration/seeder notes
* commands run
* logs:audit status
* final recommendation

Update:

```text
docs/UHMS_IMPLEMENTATION_SKILL.md
docs/ACCOUNTING_PHASE_7_REPORTS_CLOSING_DASHBOARDS_REPORT.md
docs/LOGGING_REMAINING_TODOS.md
```

only if needed.

---

# 23. Deployment Checklist

Document deployment steps:

```bash
php artisan migrate
php artisan db:seed --class=AccountingChartSeeder
php artisan db:seed --class=RoleSeeder
php artisan config:clear
php artisan cache:clear
php artisan route:clear
composer logs:audit:stage2
php artisan test --filter=Accounting
```

Add any required backfill commands if accounting fields were added to existing records.

---

# 24. Acceptance Criteria

Phase 8 is complete when:

* full accounting automated tests exist
* accounting tests pass
* existing critical workflows still pass
* Trial Balance remains balanced
* Balance Sheet balances
* P&L calculates correctly
* AR/AP Aging work
* Inventory valuation works
* year-end closing entry works
* period/fiscal reopen works
* exports/prints work where implemented
* accounting dashboard uses GL-backed values
* failed posting retry works
* duplicate posting is prevented
* permissions are enforced
* activity logs are written
* logs:audit Stage-2 gate remains green
* documentation is updated
* accounting implementation is ready for real operational use

---

# 25. Important Rules

Do not weaken accounting validation to make tests pass.
Do not fake journal entries in tests without validating real service behavior.
Do not include draft journals in official reports.
Do not allow unbalanced journal entries.
Do not allow posting into closed periods.
Do not edit posted journals.
Do not delete operational financial records.
Do not bypass ActivityLogService.
Do not bypass permissions.
Do not disable logs:audit Stage-2 gate.
Do not refactor unrelated modules.

Proceed with Accounting Phase 8: Final Test Suite, Audit, Stabilization & Accounting Hardening now.
