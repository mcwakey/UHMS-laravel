# Accounting Phase 7 — Financial Reports, Closing Controls & Management Dashboards

## Summary
Turns the accounting engine into management reports. All new statements read **only posted
journal entry lines** (`ledgerAffecting` scope) — never drafts/cancelled. Reuses the existing
`GeneralLedgerService`, `TrialBalanceService`, `ARAgingService` (Phase 4), `APAgingService`
(Phase 5), `InventoryValuationReportService` (Phase 6) and `AccountingPeriodService` (closing).

## Reports implemented
| Report | Source / formula |
|---|---|
| General Ledger | existing — posted lines, running balance by normal balance |
| Trial Balance | existing — posted lines grouped by account; Σdebit = Σcredit |
| **Profit & Loss** | INCOME accounts = revenue (credit−debit); EXPENSE split by subtype: COST_OF_SALES→COGS, ADMIN_EXPENSE→Admin, FINANCE_COST→Finance, else Operating. Gross Profit = Revenue − COGS; Net Profit = Revenue − Total Expenses |
| **Balance Sheet** | ASSET/LIABILITY/EQUITY cumulative balances + **Current Year Earnings** (P&L for the year) added to equity; validates **Assets = Liabilities + Equity** with out-of-balance warning |
| **Cashbook / Cash & Bank** | posted lines hitting `is_cash_account`/`is_bank_account` accounts; opening (prior cumulative) → money-in/out → running balance. Provides the simple cash-flow view (opening, inflows, outflows, net, closing) |
| **Revenue by Department** | posted INCOME lines grouped by `journal_entry_lines.department_id` (null → "Unassigned") |
| **Expense by Department** | posted EXPENSE lines grouped by department |
| AR / AP Aging summaries | Phase 4 / Phase 5 services + reconciliation against GL control accounts |
| Inventory Valuation | Phase 6 service + reconciliation against the GL inventory accounts |

Formulas honour each account's `normal_balance` (credit-normal = credit−debit).

## Reconciliation warnings (`AccountingReconciliationService`)
Compares GL control-account balances with operational subledgers and flags mismatches
(never blocks): **Receivables** (GL 1210–1240 vs open `invoice_receivables`),
**Supplier Payables** (GL 2110 vs open `supplier_payables`), **Inventory** (GL 1300–1340 vs
`stock_balances.total_value`). Shown on the Balance Sheet. *(Verified: surfaced a real AR
GL-vs-operational difference during testing — exactly its purpose.)*

## Closing controls (already enforced — `AccountingPeriodService`)
- `closePeriod` / `closeFiscalYear` set status = closed and log `ACCOUNTING_PERIOD_CLOSED` /
  `FISCAL_YEAR_CLOSED`. Fiscal-year close requires all periods closed.
- `ensureDateIsPostable` resolves an **open** period (open fiscal year) for the entry date;
  posting into a closed period/year throws — so **no journal can post into a closed period**
  (verified). Reversals must target an open period.
- Close routes exist: `accounting.periods.close`, `accounting.fiscal-years.close`.

## UI / routes
New routes under `admin/accounting/`: `reports.profit-loss`, `reports.balance-sheet`,
`reports.cashbook`, `reports.revenue-by-department`, `reports.expense-by-department`.
Lean Blade views reusing `<x-page-header>`, `<x-empty-state>`, Bootstrap tables (no new
framework). Sidebar entries added under **Accounts & Finance**.

## Permissions (RoleSeeder)
`accounting.reports.profit_loss / balance_sheet / cashbook / cash_flow /
revenue_by_department / expense_by_department`, `accounting.reconciliation.view`,
`accounting.failed_postings.view`, `accounting.exports`, `accounting.periods.close/reopen`,
`accounting.fiscal_years.close/reopen`. Each report route is permission-gated.

## Activity logs
Period/fiscal closing already log via `ActivityLogService`
(`ACCOUNTING_PERIOD_CLOSED`, `FISCAL_YEAR_CLOSED`). Report-view logging is intentionally
omitted to avoid noise (per the spec's allowance); closing/export are the audited events.

## Manual verification completed (tinker)
- P&L computed (revenue 3,684.60; net profit 3,684.60).
- **Balance Sheet balances**: Assets = Liabilities + Equity (diff 0) once current-year earnings flow into equity.
- Cashbook opening→closing running balance correct.
- Revenue-by-department grouped across 6 departments.
- Reconciliation flagged a real AR GL-vs-operational mismatch (warning only).
- All four new report views render.
- **Closed-period posting blocked** (`ensureDateIsPostable` throws).
- Trial Balance still balanced; existing billing/stock/AR/AP tests unaffected.

## Tests deferred
Full automated accounting test suite deferred to the final pass (per spec). Validation,
permissions, posting-source restriction (posted-only), reconciliation warnings and closing
controls are implemented now.

## Known risks / TODOs
- **Year-end closing entry** (close income/expense to retained earnings) is not auto-created —
  documented TODO; posting into closed periods/years is already prevented.
- **Period/fiscal reopen** actions: permissions added; explicit reopen controller actions are a
  small follow-up (close is implemented).
- **PDF/Excel export** of the new statements not added — reuse the existing report/print
  utilities in a follow-up (`accounting.exports` permission reserved).
- **GL-based dashboard cards** (cash/AR/AP/inventory/revenue/expense/profit-this-month) — the
  existing accounting dashboard remains; a GL-summary card set is a recommended enhancement.
- Department-level P&L depends on `department_id` being stamped on journal lines; lines without
  it classify as "Unassigned".

## Next phase recommendation
Phase 8 (final): year-end closing entry + reopen workflow, GL-summary management dashboard,
report exports (PDF/Excel), and the full automated accounting test suite across Phases 1–7.
