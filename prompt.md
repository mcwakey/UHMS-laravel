Next is **Accounting Phase 7: Financial Reports, Closing Controls & Management Dashboards**.

This phase turns the accounting engine into useful management reports: Trial Balance, General Ledger, Profit & Loss, Balance Sheet, Cash Flow, AR/AP Aging summaries, revenue/expense dashboards, and period closing controls.

You are working on UHMS — Ultimate Hospital Management System.

Accounting Phase 1 Foundation is complete.
Accounting Phase 2 Billing → Accounting Posting is complete.
Accounting Phase 3 Payments, Discounts, Credit Notes, Write-offs, Refunds & Reversals is complete.
Accounting Phase 4 Sponsors, Insurance, Corporate Receivables & AR Aging is complete.
Accounting Phase 5 Procurement, Supplier Ledger, Accounts Payable & AP Aging is complete.
Accounting Phase 6 Stock Valuation, COGS, Consumables Expense & Inventory Accounting is complete.

Now proceed with Accounting Phase 7:

Financial Reports, Closing Controls & Management Dashboards

Goal:
Build the main accounting reports, financial dashboards, and period closing controls needed for UHMS to operate as a full accounting-aware hospital management system.

Do not replace the existing operational reports.
Do not replace billing, procurement, stock, supplier, AR, or AP reports.
Do not change posted journal entries.
Do not enable full automated tests yet.
Full tests will be written after the whole accounting implementation is complete.

Important rule:

Operational reports show hospital workflow activity.
Accounting reports show financial impact from posted journal entries.

---

# 1. Main Objective

Implement complete accounting reporting and finance dashboards.

This phase must include:

1. General Ledger
2. Trial Balance
3. Profit & Loss / Income Statement
4. Balance Sheet
5. Cashbook / Cash & Bank Summary
6. Cash Flow report if feasible
7. AR Aging summary
8. AP Aging summary
9. Revenue by Department
10. Expense by Department
11. Inventory Valuation summary
12. Supplier Payables summary
13. Receivables summary
14. Period closing controls
15. Accounting dashboard
16. Report exports/printing where existing report system supports it
17. Activity logs
18. Manual verification documentation

---

# 2. Reporting Principle

All core accounting reports must use only:

```text
posted journal entries
posted journal entry lines
valid accounting periods
valid fiscal years
````

Do not include:

```text
draft journals
cancelled journals
unposted operational records
failed accounting postings
```

Operational records can be linked for drill-down, but report totals must come from accounting records.

---

# 3. General Ledger

Create or finalize the General Ledger report.

Filters:

```text
Account
Account Type
Date From
Date To
Fiscal Year
Accounting Period
Department
Patient
Supplier
Sponsor
Insurance Provider
Source Module
Reference Type
Reference Number
```

Columns:

```text
Date
Journal Number
Account Code
Account Name
Description
Reference
Source Module
Debit
Credit
Running Balance
Posted By
```

Rules:

* include only posted journal entries
* running balance must respect account normal balance
* allow drill-down to journal entry
* allow drill-down to source record if available
* support print/export if existing report system supports it
* show opening balance before date_from if date filter is used

Opening balance logic:

For debit-normal accounts:

```text
opening_balance = prior_debits - prior_credits
```

For credit-normal accounts:

```text
opening_balance = prior_credits - prior_debits
```

---

# 4. Trial Balance

Create or finalize Trial Balance report.

Filters:

```text
Fiscal Year
Date From
Date To
Account Type
Department optional
Include Zero Balances yes/no
```

Columns:

```text
Account Code
Account Name
Account Type
Opening Debit
Opening Credit
Period Debit
Period Credit
Closing Debit
Closing Credit
```

Rules:

* posted entries only
* total debits must equal total credits
* show imbalance warning if totals differ
* parent accounts should optionally roll up child accounts
* allow detailed view per account

Trial Balance must be usable for Balance Sheet and P&L.

---

# 5. Profit & Loss / Income Statement

Create Profit & Loss report.

Sections:

```text
Revenue
Cost of Goods Sold
Gross Profit
Operating Expenses
Administrative Expenses
Finance Costs
Net Profit / Loss
```

Filters:

```text
Fiscal Year
Accounting Period
Date From
Date To
Department
Branch if multi-branch exists
```

Rules:

* Revenue accounts use INCOME type
* Expenses use EXPENSE type
* COGS can use EXPENSE subtype COST_OF_SALES
* Net Profit = Total Income - Total Expenses
* support department-level P&L if department_id exists on journal lines

Example:

```text
Consultation Revenue
Laboratory Revenue
Pharmacy Revenue
Procedure Revenue
Emergency Revenue
Admission Revenue
Other Revenue

Less:
Pharmacy COGS
Consumables COGS
Salaries
Utilities
Rent
Maintenance
Administrative Expenses
Bad Debt / Write-off Expense

Net Profit / Loss
```

---

# 6. Balance Sheet

Create Balance Sheet report.

Sections:

```text
Assets
Liabilities
Equity
```

Rules:

* Assets from ASSET accounts
* Liabilities from LIABILITY accounts
* Equity from EQUITY accounts
* Include current year profit/loss from P&L if not already closed to retained earnings
* Must validate:

```text
Assets = Liabilities + Equity
```

Show warning if out of balance.

Recommended sections:

```text
Current Assets
Non-current Assets
Current Liabilities
Non-current Liabilities
Equity
```

Examples:

Assets:

* Cash on Hand
* Bank Account
* Mobile Money
* Patient Receivables
* Insurance Receivables
* Sponsor Receivables
* Corporate Receivables
* Inventory
* Fixed Assets

Liabilities:

* Supplier Payables
* Patient Deposits
* Taxes Payable
* Salary Payable
* Accrued Expenses

Equity:

* Owner Capital
* Retained Earnings
* Current Year Earnings

---

# 7. Cashbook / Cash & Bank Summary

Create Cashbook report.

Filters:

```text
Cash/Bank/Mobile Money Account
Date From
Date To
Payment Method
Source Module
```

Columns:

```text
Date
Reference
Description
Debit / Money In
Credit / Money Out
Running Balance
Source
Created By / Posted By
```

Rules:

* use accounts flagged as cash/bank/mobile money
* include posted journal entries only
* cash receipts from patients/sponsors/insurance should appear
* supplier payments/refunds should appear as money out
* support account-specific running balance

---

# 8. Cash Flow Report

If feasible in this phase, create a simple cash flow report.

Minimum:

```text
Cash Inflows
Cash Outflows
Net Cash Movement
Opening Cash Balance
Closing Cash Balance
```

Sources:

* posted journal lines hitting cash/bank/mobile money accounts

Do not overcomplicate into full indirect-method cash flow unless already easy.

---

# 9. AR Aging Summary

Integrate Phase 4 AR Aging into accounting dashboard/reports.

Show:

```text
Total Receivables
Patient Receivables
Insurance Receivables
Sponsor Receivables
Corporate Receivables
0–30
31–60
61–90
91–120
120+
```

Rules:

* use payer receivables from Phase 4
* reconcile with receivable account balances where possible
* show warning if operational AR and GL receivable balance differ

---

# 10. AP Aging Summary

Integrate Phase 5 AP Aging.

Show:

```text
Total Payables
Supplier Payables
0–30
31–60
61–90
91–120
120+
```

Rules:

* use supplier payables / supplier ledger from Phase 5
* reconcile with Supplier Payables GL balance where possible
* show warning if operational AP and GL payable balance differ

---

# 11. Inventory Valuation Summary

Integrate Phase 6 Inventory Valuation.

Show:

```text
Total Inventory Value
Pharmacy Inventory
Consumables Inventory
Laboratory Reagents Inventory
Theatre Supplies Inventory
Inventory by Location
Inventory by Product Type
```

Rules:

* use stock balance valuation fields
* reconcile with Inventory GL account balance where possible
* show warning if operational inventory value and GL inventory balance differ

---

# 12. Revenue by Department

Create Revenue by Department report.

Filters:

```text
Date From
Date To
Department
Service Type
Source Module
```

Columns:

```text
Department
Revenue Account
Gross Revenue
Discounts
Credit Notes
Net Revenue
Payments Received optional
Outstanding Receivables optional
```

Rules:

* accounting revenue comes from posted journal entries
* operational billing can be shown as comparison if useful
* department_id should come from journal lines
* if missing department_id, classify as Unassigned

---

# 13. Expense by Department

Create Expense by Department report.

Filters:

```text
Date From
Date To
Department
Expense Type
Source Module
```

Columns:

```text
Department
Expense Account
Amount
Source Module
```

Include:

* consumables expense
* COGS
* salary/payroll expense if already posted
* utilities/admin expenses from manual journals
* damaged/expired stock expense
* write-off expense

---

# 14. Accounting Dashboard

Create/update Accounting Dashboard.

Cards:

```text
Cash / Bank Balance
Total Receivables
Total Payables
Inventory Value
Revenue This Month
Expenses This Month
Net Profit This Month
Unposted / Failed Accounting Items
Open Fiscal Year
Open Period
```

Charts/tables:

```text
Monthly Revenue Trend
Monthly Expense Trend
AR Aging Summary
AP Aging Summary
Top Revenue Departments
Top Expense Departments
Recent Journal Entries
Failed Accounting Postings
```

Use existing UI standards:

* Bootstrap 5
* Tabler Icons
* existing card/table components
* no new frontend framework

---

# 15. Period Closing Controls

Implement or finalize accounting closing controls.

## Accounting Period Closing

When closing an accounting period:

* require permission
* ensure no draft journals in period, or warn/block depending policy
* ensure no failed accounting postings in period, or warn/block
* ensure trial balance is balanced
* set period status = closed
* log ACCOUNTING_PERIOD_CLOSED

After closing:

* no new journals can be posted into that period
* no operational posting can create journal entries in that period
* reversals for that period must post into an open period unless policy allows reopening

## Fiscal Year Closing

For fiscal year closing:

* all periods should be closed
* trial balance must be balanced
* calculate current year profit/loss
* optionally create closing entry to retained earnings
* set fiscal year status = closed
* log FISCAL_YEAR_CLOSED

If full year-end closing is too much now, document as TODO but enforce no posting into closed fiscal years.

---

# 16. Closing Entry

If implementing year-end closing entry:

Close income and expense accounts to current year earnings/retained earnings.

Example:

If profit:

```text
Dr Income Accounts
Cr Expense Accounts
Cr Retained Earnings / Current Year Earnings
```

If loss:

```text
Dr Retained Earnings / Current Year Earnings
Dr Income Accounts
Cr Expense Accounts
```

Use JournalEntryService.

Do not silently close without journal entry.

If not implemented now, document as future TODO.

---

# 17. Reconciliation Warnings

Add report-level reconciliation checks.

Examples:

```text
Patient Receivable GL balance vs invoice_receivables patient balance
Insurance Receivable GL balance vs insurance receivables balance
Sponsor Receivable GL balance vs sponsor receivables balance
Supplier Payables GL balance vs supplier payables balance
Inventory GL balance vs inventory valuation report
Cash account balance vs cashbook
```

Do not block reports if mismatch exists.

Show warning:

```text
Warning: Operational receivable balance does not match GL balance.
```

These warnings are extremely useful for management.

---

# 18. Exports / Print

If UHMS already supports exports/printing, add export/print to:

```text
General Ledger
Trial Balance
Profit & Loss
Balance Sheet
Cashbook
AR Aging
AP Aging
Inventory Valuation
```

Preferred formats:

```text
PDF
Excel/CSV
Print view
```

Do not build a heavy export system if one already exists; reuse existing report/export utilities.

---

# 19. Permissions

Add or verify:

```text
accounting.dashboard.view

accounting.reports.general_ledger
accounting.reports.trial_balance
accounting.reports.profit_loss
accounting.reports.balance_sheet
accounting.reports.cashbook
accounting.reports.cash_flow
accounting.reports.revenue_by_department
accounting.reports.expense_by_department

accounting.periods.close
accounting.periods.reopen
accounting.fiscal_years.close
accounting.fiscal_years.reopen

accounting.reconciliation.view
accounting.failed_postings.view
accounting.exports
```

Restrict financial reports to authorized finance/admin roles.

---

# 20. Activity Logs

Use ActivityLogService.

Log:

```text
GENERAL_LEDGER_VIEWED
TRIAL_BALANCE_VIEWED
PROFIT_LOSS_VIEWED
BALANCE_SHEET_VIEWED
CASHBOOK_VIEWED
AR_AGING_VIEWED
AP_AGING_VIEWED
INVENTORY_VALUATION_VIEWED
ACCOUNTING_DASHBOARD_VIEWED

ACCOUNTING_PERIOD_CLOSED
ACCOUNTING_PERIOD_REOPENED
FISCAL_YEAR_CLOSED
FISCAL_YEAR_REOPENED
YEAR_END_CLOSING_ENTRY_CREATED

ACCOUNTING_REPORT_EXPORTED
```

Context:

```text
fiscal_year_id
accounting_period_id
date_from
date_to
account_id
department_id
report_type
export_type
journal_entry_id
old_values
new_values
```

Do not log every simple page refresh too noisily if the project avoids report-view logs.

If report-view logging is considered too noisy, log exports and period/fiscal closing at minimum.

---

# 21. Services to Create / Update

Create or update:

```text
GeneralLedgerService
TrialBalanceService
ProfitLossService
BalanceSheetService
CashbookService
CashFlowService
AccountingDashboardService
AccountingReconciliationService
AccountingPeriodCloseService
FiscalYearCloseService
AccountingExportService
```

Use existing services where already available.

Controllers should remain thin.

Reports should not contain heavy SQL directly inside controllers.

---

# 22. Manual Verification Strategy

Do not write the full automated test suite yet.

Full accounting tests will be written after all accounting phases are implemented.

For this phase, provide manual verification notes.

Manual verification required:

1. Open General Ledger and confirm posted journals appear.
2. Confirm General Ledger running balance is correct.
3. Open Trial Balance and confirm debit/credit totals match.
4. Confirm Trial Balance excludes draft/cancelled journals.
5. Open Profit & Loss and confirm revenue/expense totals.
6. Open Balance Sheet and confirm Assets = Liabilities + Equity.
7. Open Cashbook and confirm cash/bank movement.
8. Open AR Aging summary and confirm payer balances.
9. Open AP Aging summary and confirm supplier balances.
10. Open Inventory Valuation summary and confirm stock value.
11. Confirm dashboard cards show correct values.
12. Confirm reconciliation warnings appear when mismatch exists.
13. Close an accounting period and confirm posting into it is blocked.
14. Confirm unauthorized users cannot view restricted accounting reports.
15. Export/print reports if supported.
16. Confirm activity logs are written for closing/export events.
17. Confirm logs:audit Stage-2 gate remains green.
18. Confirm billing, payments, procurement, stock, AR, and AP workflows still work.

Do not skip validation, permissions, audit logs, reconciliation warnings, or report correctness because tests are deferred.

---

# 23. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_7_REPORTS_CLOSING_DASHBOARDS_REPORT.md
```

Include:

* reports implemented
* report formulas
* report filters
* closing controls
* reconciliation warnings
* dashboard widgets
* permissions
* exports/printing
* activity logs
* manual verification completed
* tests deferred list
* known risks/TODOs
* next phase recommendation

---

# 24. Acceptance Criteria

Phase 7 is complete when:

* General Ledger works
* Trial Balance works
* Profit & Loss works
* Balance Sheet works
* Cashbook works
* AR Aging summary is integrated
* AP Aging summary is integrated
* Inventory Valuation summary is integrated
* Accounting Dashboard works
* Period closing blocks further posting
* Fiscal year posting restrictions work
* Reconciliation warnings exist
* Reports are permission-protected
* Export/print is available where supported
* Activity logs are written for important report/closing/export actions
* logs:audit Stage-2 gate remains green
* manual verification is documented
* full automated tests remain deferred until final accounting implementation pass

---

# 25. Important Rules

Do not calculate accounting reports from invoices only.
Do not calculate P&L from payments only.
Do not include draft journals in official reports.
Do not include cancelled journals in official reports.
Do not allow posting into closed periods.
Do not silently close fiscal year without audit.
Do not expose financial reports to unauthorized users.
Do not hide reconciliation mismatches.
Do not enable full automated tests yet.

Proceed with Accounting Phase 7: Financial Reports, Closing Controls & Management Dashboards now.
