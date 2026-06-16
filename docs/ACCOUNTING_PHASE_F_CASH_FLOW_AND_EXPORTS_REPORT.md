# Accounting Execution Phase F: Cash Flow Statement and Exports

## Scope

Phase F adds a direct-method cash flow statement and CSV export foundation for core accounting reports.

## Delivered

- Added a GL-derived direct cash flow statement service.
- Classified posted cash and bank journal movements into operating, investing, financing, and other cash adjustments.
- Excluded pure cash/bank transfers from net cash flow by relying on net cash-equivalent movement per journal.
- Added the Cash Flow Statement page under Advanced Accounting reports.
- Added CSV exports for:
  - Cash Flow Statement
  - Trial Balance
  - General Ledger
- Added sidebar navigation for Cash Flow.
- Extended Accountant role permissions with report and export access.

## Classification Rules

- Opening balance journals are shown under other cash adjustments.
- Equity and non-current liability counterpart accounts are financing activities.
- Non-current asset counterpart accounts are investing activities.
- All other cash movements default to operating activities.

## Verification

Covered by `tests/Feature/Accounting/CashFlowAndExportsPhaseFTest.php`.
