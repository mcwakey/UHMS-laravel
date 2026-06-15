# Accounting Execution Phase D - Subledger Reconciliation Workbench

## Summary

Phase D adds a snapshot-based Subledger Reconciliation Workbench under Advanced Accounting.
It compares operational balances with mapped GL control accounts without creating journals,
changing source records, or introducing a parallel accounting engine.

The workbench supports seven domains:

- Accounts receivable
- Accounts payable
- Inventory
- Payroll
- Cash / bank
- PAYE
- Pension / SSNIT

AR, AP, inventory, and configured Phase B bank positions are available. Payroll, PAYE, and
pension use real calculation data but are explicitly marked **partially available** until Phase E
adds authoritative payroll posting and settlement, and Phase I adds statutory settlement records.

## Database Changes

Migration:
`2026_06_15_000006_create_accounting_phase_d_subledger_reconciliation.php`

Tables:

| Table | Purpose |
|-------|---------|
| `accounting_reconciliation_runs` | Period, status, availability, totals, actors, and immutable source/GL/summary snapshots |
| `accounting_reconciliation_items` | Drill-down records, control-account comparisons, classifications, and resolution state |
| `accounting_reconciliation_resolutions` | Evidence notes and links to journals, posting attempts, or source records |

Money columns use `DECIMAL(18,2)`, snapshots use `LONGTEXT`, statuses are application-validated
strings, and long MariaDB foreign-key/index names were replaced with explicit short names.

## Models Added

- `AccountingReconciliationRun`
- `AccountingReconciliationItem`
- `AccountingReconciliationResolution`

The models define valid statuses/types, casts, relationships, active-run filtering, availability,
and unresolved-difference checks.

## Services Added

| Service | Responsibility |
|---------|----------------|
| `SubledgerReconciliationService` | Start, calculate, snapshot, complete, approve, cancel, supersede drafts, and provide dashboard metrics |
| `ReceivablesReconciliationService` | Open payer receivables versus four mapped AR control accounts |
| `PayablesReconciliationService` | Open supplier payables versus the supplier control account |
| `InventoryReconciliationService` | Product/location valuation versus mapped inventory control accounts |
| `PayrollReconciliationService` | Approved unpaid net payroll versus payroll payable, marked partial |
| `CashBankReconciliationService` | Latest Phase B bank positions versus mapped bank GL accounts |
| `TaxLiabilityReconciliationService` | PAYE and pension calculations versus liability accounts, marked partial |
| `ReconciliationResolutionService` | Evidence-backed explanation, resolution, timing acceptance, and waiver |
| `AbstractReconciliationDomainService` | Shared GL balance, failed-posting, manual-journal, item, and result helpers |

`AccountingReconciliationService` remains available for its existing lightweight dashboard
warnings. Phase D adds the immutable operational workbench rather than changing that legacy API.

## Account Settings

Added control-account settings:

- `payroll_payable_account_id`
- `paye_payable_account_id`
- `pension_payable_account_id`

The chart seeder adds dedicated PAYE (`2310`) and Pension / SSNIT (`2320`) payable accounts and
maps payroll payable to the existing Salary Payable account (`2400`).

## Reconciliation Domains

### Accounts Receivable

Uses open `invoice_receivables`, grouped by patient, insurance, sponsor, and corporate payer
types. Each receivable is stored as a drill-down item with invoice, patient/visit, payer, aging,
due-date, control-account, and posting-attempt metadata. Aggregate control rows hold the
authoritative GL comparison.

### Accounts Payable

Uses open `supplier_payables`, with supplier-level drill-down and posting state. The aggregate
supplier control row compares the subledger total to the mapped AP account.

### Inventory

Uses `stock_balances.total_value`, grouped by each product's mapped inventory account. Product and
location rows remain individually drillable, while account-level summary rows compare valuation
to the GL.

### Cash / Bank

Uses active Phase B bank accounts and the latest approved or prepared reconciliation position:

```text
statement closing + outstanding deposits
- outstanding withdrawals + adjustments
```

The result is compared with the mapped bank GL balance as of the run date. With no active Phase B
bank account, the domain is honestly marked `not_available`.

### Payroll, PAYE, and Pension

These domains use approved payroll records and tax calculations:

- Payroll: approved unpaid net pay
- PAYE: calculated tax
- Pension: employee plus employer SSNIT

They are marked `partially_available` because authoritative posting and settlement are not yet
implemented. No settlement amounts are fabricated or inferred.

## Difference Classification

Items support:

- `balanced`
- `timing_difference`
- `unposted_source`
- `failed_posting`
- `manual_journal`
- `mapping_issue`
- `source_data_issue`
- `period_cutoff`
- `unknown_difference`
- `not_available`

Failed posting attempts and unposted source records are separated from aggregate control
differences. Posted manual journals affecting mapped control accounts are stored as distinct
`manual_journal` items.

## Resolution Workflow

Finance users can record:

- Posting retry evidence
- Journal reversal evidence
- Source correction
- Corrective/manual journal link
- Accepted timing difference
- Mapping correction
- Waiver after review
- Other explanation

Resolutions require a note and may link a journal, posting attempt, or source record. Explained,
resolved, accepted-timing, and waived items remain visible.

Completed runs with unresolved differences cannot be approved normally. A user with the elevated
approval permission must explicitly confirm approval with unresolved differences.

## Routes, Controller, and Views

Controller:
`SubledgerReconciliationController`

Ten routes under `admin/accounting/subledger-reconciliation` provide:

- Dashboard
- New run form and execution
- Run detail
- Item drill-down and resolution
- Approval screen and action
- Cancellation
- History

Views:

- `accounting/subledger-reconciliation/index.blade.php`
- `create.blade.php`
- `show.blade.php`
- `item.blade.php`
- `approval.blade.php`
- `history.blade.php`

The UI uses Bootstrap 5 and Tabler Icons. A **Subledger Reconciliation** link was added under
Advanced Accounting.

## Permissions

Added:

- `accounting.subledger_reconciliation.view`
- `accounting.subledger_reconciliation.run`
- `accounting.subledger_reconciliation.resolve`
- `accounting.subledger_reconciliation.approve`
- `accounting.subledger_reconciliation.cancel`

Accountants receive view/run/resolve. Finance Managers additionally receive approve/cancel.
Administrator and Super Admin receive all through the existing complete permission assignment.
No clinical role receives these permissions.

## Close Readiness Integration

`AccountingCloseReadinessService` now includes:

- Latest reconciliation per domain
- Unapproved completed runs
- Domains with unresolved differences
- Domains not run for the selected period
- Reconciliation resolutions linked to failed postings
- Manual control-account journals

The close-readiness screen links to the workbench and displays the domain table and exception
cards. Phase D does not add a new hard close block; the existing failed-posting readiness rule is
preserved. A future close-control phase can safely promote configured material domains to gates.

## Audit Logging

Events:

- `SUBLEDGER_RECONCILIATION_STARTED`
- `SUBLEDGER_RECONCILIATION_COMPLETED`
- `SUBLEDGER_RECONCILIATION_APPROVED`
- `SUBLEDGER_RECONCILIATION_CANCELLED`
- `SUBLEDGER_RECONCILIATION_RESOLUTION_ADDED`
- `SUBLEDGER_RECONCILIATION_ITEM_WAIVED`
- `SUBLEDGER_RECONCILIATION_CLOSE_READINESS_VIEWED`

`logs:audit --json` reports:

- `MISSING_LOG`: **0**
- `NEEDS_REVIEW`: **0**
- Existing unrelated backlog: **21**

## Localization

Phase D labels, domain names, statuses, classifications, resolutions, warnings, and messages were
added to both `lang/en/accounting.php` and `lang/fr/accounting.php`.

Localization audit:

- Files scanned: **1,390**
- Active runtime candidates: **0**

## Tests Added

`tests/Feature/Accounting/SubledgerReconciliationPhaseDTest.php`

Coverage includes:

- Dashboard permission and module middleware
- AR, AP, inventory, and cash/bank totals
- Honest payroll/PAYE/pension partial availability
- Failed posting, manual journal, and unposted source classifications
- Resolution note/evidence retention
- Elevated approval permission
- Explicit unresolved-difference approval override
- Close-readiness status
- Audit events
- EN/FR keys

Final focused result: **16 passed (39 assertions)**.

The updated sidebar contract plus Phase D tests report **21 passed (54 assertions)**.

## Minimal Verification Commands

Completed:

- `php artisan migrate --force`
- `php artisan db:seed --class=AccountingChartSeeder --force`
- `php artisan db:seed --class=RoleSeeder --force`
- `php artisan route:list --json`
  - **790 total routes**
  - **10 Phase D routes**
- `php artisan view:cache`
- `php artisan view:clear`
- `php scripts/localisation-audit.php`
  - **0 active runtime candidates**
- `php artisan logs:audit --json`
  - **0 missing, 0 needs review**
- `php artisan permissions:audit --strict`
  - **0 missing route permissions, 0 unguarded admin mutation routes**
- PHP lint on **33 changed PHP files**
- `git diff --check`
  - No whitespace errors; only existing line-ending warnings

Per the Phase D instruction, the wide `php artisan test` suite was intentionally not run.

## Known Limitations

- Historical operational balances are based on records currently available as of the selected
  date; source tables without full historical balance-event ledgers cannot reconstruct every
  prior-day state.
- Payroll, PAYE, and pension remain partial until posting and settlement phases are complete.
- Cashier cash-on-hand reconciliation is not yet as formal as Phase B bank reconciliation.
- Classification is conservative: unexplained aggregate differences remain
  `unknown_difference` until evidence is recorded.
- Runs are synchronous; large installations may later move calculation to queued jobs.

## Next Recommended Phase

Proceed with Accounting Execution Phase E: approved payroll posting, salary settlement, PAYE and
pension liability posting, reversals, and reconciliation closure.
