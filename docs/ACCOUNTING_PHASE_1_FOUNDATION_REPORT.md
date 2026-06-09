# Accounting Phase 1 Foundation Report

Date: 2026-06-09

## Scope Completed

Built the UHMS double-entry accounting foundation without replacing existing operational billing, payments, procurement, stock, supplier ledger, or legacy income/expense entry flows.

Operational records remain operational. Accounting records now have a separate foundation ready for Phase 2 posting integrations.

## Database Changes

Added migrations:

- `accounts`
- `fiscal_years`
- `accounting_periods`
- `journal_entries`
- `journal_entry_lines`
- `accounting_settings`

Key safeguards included:

- Unique account codes.
- Account hierarchy through `parent_id`.
- Open/closed fiscal year and period states.
- Journal status workflow: draft, posted, reversed, cancelled.
- Journal line context columns for future billing, patient, visit, invoice, supplier, sponsor, insurance, and department posting.
- Reports are based on posted/reversed ledger-affecting journals, not account opening-balance display fields.

## Models And Enums Added

Models:

- `Account`
- `FiscalYear`
- `AccountingPeriod`
- `JournalEntry`
- `JournalEntryLine`
- `AccountingSetting`

Enums:

- `AccountType`
- `NormalBalance`
- `PeriodStatus`
- `JournalEntryStatus`

## Services Added

- `AccountingPeriodService`
- `AccountingSettingsService`
- `ChartOfAccountsService`
- `JournalEntryService`
- `TrialBalanceService`
- `GeneralLedgerService`
- `AccountingPostingService`

Extended existing `AccountingService` with a Phase 1 accounting dashboard summary.

## Chart Of Accounts

Added `AccountingChartSeeder`, called by `DatabaseSeeder`.

Seeder creates the hospital default chart, including:

- Assets: cash/bank, receivables, inventory, fixed assets.
- Liabilities: payables, patient deposits, taxes, salary payable, accruals.
- Equity: capital, retained earnings, current year earnings.
- Revenue: consultation, lab, pharmacy, procedure/theatre, admission, emergency, insurance, sponsor, other revenue.
- Expenses: COGS, consumables, salaries, rent, utilities, maintenance, admin, bad debt/write-off, bank charges.

The seeder is idempotent and also creates the current fiscal year plus 12 monthly open periods when missing.

## Manual Journal Workflow

Added admin routes and screens under:

- `admin/accounting`
- `admin/accounting/accounts`
- `admin/accounting/journals`
- `admin/accounting/trial-balance`
- `admin/accounting/general-ledger`
- `admin/accounting/fiscal-years`
- `admin/accounting/periods`
- `admin/accounting/settings`

Workflow supported:

- Create balanced draft journal.
- Edit draft journal.
- Post balanced journal.
- Cancel draft journal.
- Reverse posted journal through a posted offsetting journal.
- View journal lines and totals.

Posted journals cannot be edited directly.

## Validation Rules Enforced

Implemented backend validation for:

- Account code required and unique.
- Account type and normal balance compatibility.
- Parent account cannot be itself.
- Journal date must resolve to an open accounting period in an open fiscal year.
- Journal must have at least two lines.
- Each line must choose an active account.
- One line cannot have both debit and credit.
- One line cannot have neither debit nor credit.
- Total debit must equal total credit.
- Control accounts are blocked for manual journals unless accounting settings explicitly allow them.
- Closed periods and fiscal years cannot receive postings.
- Reversal requires a reason.

## Reports Added

Trial Balance:

- Account code/name.
- Debit.
- Credit.
- Balance.
- Fiscal year/date/type/department filters.
- Warns if debit and credit totals do not match.

General Ledger:

- Account filter.
- Date/source filters.
- Date, journal number, description, reference, debit, credit, running balance.
- Running balance respects account normal balance.

## Permissions Added

Added canonical permissions:

- `accounting.dashboard.view`
- `accounting.accounts.view`
- `accounting.accounts.create`
- `accounting.accounts.edit`
- `accounting.accounts.disable`
- `accounting.journals.view`
- `accounting.journals.create`
- `accounting.journals.edit`
- `accounting.journals.post`
- `accounting.journals.reverse`
- `accounting.journals.cancel`
- `accounting.reports.trial_balance`
- `accounting.reports.general_ledger`
- `accounting.periods.view`
- `accounting.periods.manage`
- `accounting.fiscal_years.view`
- `accounting.fiscal_years.manage`
- `accounting.settings.view`
- `accounting.settings.manage`

Role defaults:

- Super Admin/Admin: receive all through existing `Permission::all()` sync.
- Accountant: receives operational accounting access, reports, posting/reversal/cancel, and settings/period/fiscal-year view. Critical setup management remains admin-only by default.
- Cashier and clinical roles: no new accounting ledger permissions by default.

Permission metadata was updated with descriptions and risk levels.

## Activity Logs

Accounting actions use `ActivityLogService` with module `ACCOUNTING`.

Logged actions:

- `ACCOUNT_CREATED`
- `ACCOUNT_UPDATED`
- `ACCOUNT_DISABLED`
- `ACCOUNT_REACTIVATED`
- `JOURNAL_ENTRY_CREATED`
- `JOURNAL_ENTRY_UPDATED`
- `JOURNAL_ENTRY_POSTED`
- `JOURNAL_ENTRY_REVERSED`
- `JOURNAL_ENTRY_CANCELLED`
- `FISCAL_YEAR_CREATED`
- `FISCAL_YEAR_CLOSED`
- `ACCOUNTING_PERIOD_CREATED`
- `ACCOUNTING_PERIOD_CLOSED`
- `ACCOUNTING_SETTINGS_UPDATED`

Added accounting context keys to activity log properties:

- `account_id`
- `journal_entry_id`
- `fiscal_year_id`
- `accounting_period_id`

## Manual Verification Completed

Commands run:

- `php -l` on new/touched PHP files.
- `php artisan route:list --path=accounting`
- `php artisan migrate --pretend`
- `php artisan migrate`
- `php artisan db:seed --class=AccountingChartSeeder`
- `php artisan db:seed --class=RoleSeeder`
- `php artisan view:cache`
- `php artisan logs:audit`

Runtime smoke checks:

- Seeded counts confirmed: 56 accounts, 12 periods, 30 settings, 19 accounting permissions.
- Balanced journal create/post/reverse passed inside a DB rollback.
- Unbalanced journal creation was rejected.
- Rollback check confirmed smoke-test journal rows were not left in the database.

`logs:audit` result:

- Exit code: 0.
- New accounting controllers were classified as service-funnel covered.
- Existing non-accounting logging backlog remains, including Blood Storage Location and Emergency Task review.

## Intentionally Not Automated Yet

Phase 1 does not automatically post:

- Invoices.
- Payments.
- Discounts.
- Credit notes.
- Write-offs.
- Refunds.
- Claims.
- Procurement.
- Supplier ledger.
- Stock movements.
- Payroll.

Opening balances are stored as setup/reference values only. They do not affect reports until posted through a balanced journal.

## Tests Deferred

Full feature tests are intentionally deferred per the Phase 1 prompt.

Later test coverage should include:

- Account create/update/disable authorization.
- Duplicate account code rejection.
- Fiscal year/period validation and closure rules.
- Balanced and unbalanced journal creation.
- Draft-only edit enforcement.
- Post/reverse/cancel permissions.
- Trial balance and general ledger calculations.
- Settings update audit logs.
- Unauthorized access to all accounting routes.

## Known Risks And TODOs

- Add browser-level UI verification after the full accounting workflow test pass.
- Add export/print actions for Trial Balance and General Ledger if the reporting framework requires it.
- Add explicit opening-balance journal workflow.
- Add a dedicated permission for manual control-account posting if policy wants user-level override instead of the current settings-level override.
- Revisit whether original reversed journals should remain status `reversed` but still report-affecting; the current report logic includes both `posted` and `reversed` originals so reversal entries naturally offset them.

## Phase 2 Recommendation

Next phase should be:

Billing to Accounting Posting

Priority posting flows:

- Invoice posted: debit patient/insurance/sponsor receivable, credit revenue.
- Payment recorded: debit cash/bank/mobile money, credit receivable.
- Discount: debit discount/allowance, credit receivable or revenue adjustment according to policy.
- Credit note/write-off/refund: generate controlled reversal/adjustment journals.

Do this through `AccountingPostingService` and keep operational records as the source of truth.
