You are working on UHMS — Ultimate Hospital Management System.

We are starting the full accounting transformation of UHMS.

Phase 1 is the Accounting Foundation.

Goal:
Build the accounting core that will later allow UHMS billing, payments, credit notes, write-offs, sponsors, insurance claims, procurement, supplier ledger, stock, payroll, and expenses to generate proper double-entry journal entries.

Do not replace the current billing system.
Do not break invoices, payments, discounts, credit notes, write-offs, stock, procurement, supplier ledger, or reports.
Do not start posting every operational transaction yet unless explicitly requested.
Do not remove existing financial records.

Important rule:

Operational records remain operational.
Accounting records are generated from operational records.

Example:

Invoice = operational billing record.
Journal Entry = accounting record created from invoice.

---

# 1. Accounting Principle

UHMS must support standard double-entry accounting.

Every accounting transaction must satisfy:

Assets = Liabilities + Equity

And every journal entry must satisfy:

Total Debits = Total Credits

The system must reject unbalanced journal entries.

---

# 2. Main Accounting Elements

Support the major accounting elements:

1. Assets
2. Liabilities
3. Equity
4. Income / Revenue
5. Expenses

Recommended account types:

ASSET
LIABILITY
EQUITY
INCOME
EXPENSE

Optional subtypes:

CURRENT_ASSET
NON_CURRENT_ASSET
CURRENT_LIABILITY
NON_CURRENT_LIABILITY
OPERATING_REVENUE
OTHER_INCOME
COST_OF_SALES
OPERATING_EXPENSE
ADMIN_EXPENSE
FINANCE_COST

---

# 3. Phase 1 Scope

Build only the accounting foundation:

1. Chart of Accounts
2. Account groups/categories
3. Fiscal years
4. Accounting periods
5. Journal entries
6. Journal entry lines
7. Manual journal entry screen
8. Double-entry validation
9. Posting/approval workflow
10. Reversal workflow
11. General Ledger report
12. Trial Balance report
13. Basic accounting settings
14. Accounting permissions
15. Audit/activity logs

Do not yet fully automate all postings from billing/procurement/stock.

However, design the foundation so Phase 2 can easily plug operational transactions into accounting posting.

---

# 4. Required Models / Tables

Create or update models/tables using project conventions.

---

## accounts

Fields:

```text
id
code
name
type
subtype nullable
parent_id nullable
description nullable
is_cash_account boolean default false
is_bank_account boolean default false
is_control_account boolean default false
is_active boolean default true
opening_balance decimal default 0
normal_balance debit/credit
created_by nullable
updated_by nullable
timestamps
softDeletes optional
````

Rules:

* code must be unique
* parent_id allows account hierarchy
* type must be one of ASSET, LIABILITY, EQUITY, INCOME, EXPENSE
* normal balance:

  * ASSET = debit
  * EXPENSE = debit
  * LIABILITY = credit
  * EQUITY = credit
  * INCOME = credit
* parent account cannot be its own child
* inactive accounts cannot be used in new journal entries
* control accounts should not normally be posted manually unless allowed by permission/config

---

## fiscal_years

Fields:

```text
id
name
start_date
end_date
status open/closed
created_by nullable
closed_by nullable
closed_at nullable
timestamps
```

Rules:

* fiscal year date range must be valid
* cannot post into closed fiscal year
* only one current open fiscal year should exist unless system config allows otherwise
* closing a fiscal year should be permission-protected

---

## accounting_periods

Fields:

```text
id
fiscal_year_id
name
start_date
end_date
status open/closed
created_by nullable
closed_by nullable
closed_at nullable
timestamps
```

Rules:

* period must belong to fiscal year
* period date range must sit inside fiscal year date range
* cannot post into closed period
* journal date must fall into an open period
* closing a period should be permission-protected

---

## journal_entries

Fields:

```text
id
journal_number
entry_date
fiscal_year_id
accounting_period_id
reference_number nullable
reference_type nullable
reference_id nullable
source_module nullable
description
status draft/posted/reversed/cancelled
posted_at nullable
posted_by nullable
created_by nullable
approved_by nullable
approved_at nullable
reversed_entry_id nullable
reversal_reason nullable
timestamps
```

Rules:

* journal_number must be unique
* entry_date required
* entry_date must fall inside an open accounting period
* draft entries can be edited
* posted entries cannot be edited directly
* posted entries can only be reversed
* cancelled entries should not affect reports
* reversed entries should remain visible for audit

---

## journal_entry_lines

Fields:

```text
id
journal_entry_id
account_id
description nullable
debit decimal default 0
credit decimal default 0
department_id nullable
patient_id nullable
visit_id nullable
invoice_id nullable
supplier_id nullable
sponsor_id nullable
insurance_provider_id nullable
reference_type nullable
reference_id nullable
line_order nullable
timestamps
```

Rules:

* one line cannot have both debit and credit greater than zero
* one line must have either debit or credit greater than zero
* total debit must equal total credit
* journal entry must have at least two lines
* line account must be active
* journal line may optionally carry operational context such as invoice_id, patient_id, supplier_id, department_id, etc.

---

# 5. Accounting Settings

Add an accounting settings structure.

This can be a settings table, config-driven setting, or existing UHMS settings system.

Settings needed for future phases:

```text
default_cash_account_id
default_bank_account_id
default_mobile_money_account_id

patient_receivable_account_id
insurance_receivable_account_id
sponsor_receivable_account_id
corporate_receivable_account_id

supplier_payable_account_id
patient_deposit_liability_account_id

default_revenue_account_id
consultation_revenue_account_id
laboratory_revenue_account_id
pharmacy_revenue_account_id
procedure_revenue_account_id
admission_revenue_account_id
emergency_revenue_account_id

default_discount_account_id
default_credit_note_account_id
default_write_off_account_id
default_refund_account_id

inventory_account_id
pharmacy_inventory_account_id
consumables_inventory_account_id
laboratory_reagents_inventory_account_id

cost_of_goods_sold_account_id
consumables_expense_account_id
bad_debt_expense_account_id
rounding_difference_account_id
retained_earnings_account_id
```

Do not require all of these to be used in Phase 1.

Prepare the structure so later phases can use them.

---

# 6. Accounting Services

Create service classes using project conventions.

Required services:

```text
AccountingService
JournalEntryService
ChartOfAccountsService
AccountingPeriodService
AccountingPostingService
TrialBalanceService
GeneralLedgerService
AccountingSettingsService
```

---

## JournalEntryService

Must handle:

```php
createDraft(array $data): JournalEntry
updateDraft(JournalEntry $entry, array $data): JournalEntry
post(JournalEntry $entry, User $user): JournalEntry
reverse(JournalEntry $entry, string $reason, User $user): JournalEntry
cancelDraft(JournalEntry $entry, User $user): JournalEntry
validateBalanced(array $lines): void
```

Posting must:

* validate entry is balanced
* validate journal has at least two lines
* validate every account is active
* validate accounting period is open
* set status = posted
* set posted_at
* set posted_by
* prevent edits after posting unless reversal workflow is used

---

## AccountingPeriodService

Must handle:

```php
resolveOpenPeriodForDate(Carbon|string $date): AccountingPeriod
ensureDateIsPostable(Carbon|string $date): void
closePeriod(AccountingPeriod $period, User $user): AccountingPeriod
```

---

## AccountingPostingService

For Phase 1, keep this ready for future operational postings.

Do not yet wire all billing/procurement/stock transactions automatically.

It may expose future-friendly methods like:

```php
postFromSource(string $sourceModule, Model $source, array $lines, array $meta = []): JournalEntry
```

But only use it for manual journals in Phase 1 unless existing architecture naturally requires it.

---

# 7. Manual Journal Entries

Add admin/accounting UI for manual journal entries.

Menu:

```text
Accounts & Finance
├── Accounting Dashboard
├── Chart of Accounts
├── Journal Entries
├── General Ledger
├── Trial Balance
├── Fiscal Years
├── Accounting Periods
└── Accounting Settings
```

Manual journal entry page must allow:

* journal date
* description
* reference number optional
* account lines
* debit amount
* credit amount
* department optional
* patient optional if needed
* supplier optional if needed
* add/remove lines
* save as draft
* update draft
* post entry
* cancel draft
* reverse posted entry

UI must clearly show:

```text
Total Debit
Total Credit
Difference
```

If difference is not zero, posting must be blocked.

Use existing UHMS UI standards:

* Bootstrap 5
* Tabler Icons
* existing page headers
* existing cards/tables/forms
* existing confirmation modal/form components if available

Do not introduce Tailwind or a new UI framework.

---

# 8. Journal Numbering

Generate journal numbers automatically.

Example:

```text
JE-2026-000001
JE-2026-000002
```

Use existing numbering system if UHMS already has one.

Do not allow duplicate journal numbers.

Journal numbering should be safe against concurrent creation.

---

# 9. Posting Rules

For Phase 1, allow manual journal entries.

Do not automatically post operational transactions yet.

Rules:

* draft journal entries can be edited
* posted journal entries cannot be edited
* posted journal entries can only be reversed
* reversal creates a new posted journal entry with debit/credit swapped
* reversal must reference original journal entry
* reversal requires a reason
* cancellation is allowed only for draft entries
* closed periods cannot receive new journal entries
* closed fiscal years cannot receive new journal entries
* reports should only include posted entries, not draft or cancelled entries

---

# 10. Opening Balances

Support opening balances carefully.

Recommended approach:

* opening balances should eventually be posted through an Opening Balance journal entry
* do not silently affect trial balance from account.opening_balance alone
* account.opening_balance can exist for setup/reference display
* trial balance must be based on posted journal entries

If opening balances are implemented now:

* ensure they are balanced
* create opening journal entry
* mark source_module = OPENING_BALANCE

If opening balances are not fully implemented now:

* document as Phase 2/3 accounting setup TODO

---

# 11. Chart of Accounts Seeder

Create a default hospital chart of accounts seeder.

Suggested structure:

```text
1000 Assets
1100 Cash and Bank
1110 Cash on Hand
1120 Bank Account
1130 Mobile Money Account

1200 Accounts Receivable
1210 Patient Receivables
1220 Insurance Receivables
1230 Sponsor Receivables
1240 Corporate Receivables

1300 Inventory
1310 Pharmacy Inventory
1320 Medical Consumables Inventory
1330 Laboratory Reagents Inventory
1340 Theatre Supplies Inventory

1400 Fixed Assets
1410 Medical Equipment
1420 Furniture and Fixtures
1430 Computers and IT Equipment
1440 Vehicles

2000 Liabilities
2100 Accounts Payable
2110 Supplier Payables
2200 Patient Deposits
2300 Taxes Payable
2400 Salary Payable
2500 Accrued Expenses

3000 Equity
3100 Owner Capital
3200 Retained Earnings
3300 Current Year Earnings

4000 Revenue
4100 Consultation Revenue
4200 Laboratory Revenue
4300 Pharmacy Revenue
4400 Procedure / Theatre Revenue
4500 Admission Revenue
4600 Emergency Revenue
4700 Insurance Claim Revenue
4800 Sponsor-Funded Revenue
4900 Other Revenue

5000 Expenses
5100 Cost of Goods Sold
5110 Pharmacy Cost of Goods Sold
5120 Consumables Cost of Goods Sold

5200 Medical Consumables Expense
5300 Salaries and Wages
5400 Rent
5500 Utilities
5600 Maintenance
5700 Administrative Expenses
5800 Bad Debt / Write-off Expense
5900 Bank Charges
```

Use proper parent-child relationships.

Do not duplicate accounts if seeder is run multiple times.

---

# 12. Reports

## Trial Balance

Create Trial Balance report.

Columns:

```text
Account Code
Account Name
Debit
Credit
Balance
```

Filters:

```text
Fiscal Year
Date From
Date To
Account Type optional
Department optional
```

Rules:

* include only posted journal entries
* exclude draft/cancelled entries
* reversal entries should naturally offset original entries
* total debit must equal total credit
* show warning if unbalanced, although unbalanced should not happen

---

## General Ledger

Create General Ledger report.

Filters:

```text
Account
Date From
Date To
Department optional
Source Module optional
```

Columns:

```text
Date
Journal No
Description
Reference
Debit
Credit
Running Balance
```

Rules:

* include only posted journal entries
* running balance follows account normal balance
* support print/export if existing report system supports it
* do not calculate from draft journal entries

---

# 13. Permissions

Add or verify permissions:

```text
accounting.dashboard.view

accounting.accounts.view
accounting.accounts.create
accounting.accounts.edit
accounting.accounts.disable

accounting.journals.view
accounting.journals.create
accounting.journals.edit
accounting.journals.post
accounting.journals.reverse
accounting.journals.cancel

accounting.reports.trial_balance
accounting.reports.general_ledger

accounting.periods.view
accounting.periods.manage
accounting.fiscal_years.view
accounting.fiscal_years.manage

accounting.settings.view
accounting.settings.manage
```

Only authorized finance/admin users should manage accounting.

Backend must enforce permissions.

Do not rely only on hiding UI buttons.

---

# 14. Audit Logs

Use ActivityLogService.

Do not create a separate accounting logging system.

Log:

```text
ACCOUNT_CREATED
ACCOUNT_UPDATED
ACCOUNT_DISABLED
ACCOUNT_REACTIVATED

JOURNAL_ENTRY_CREATED
JOURNAL_ENTRY_UPDATED
JOURNAL_ENTRY_POSTED
JOURNAL_ENTRY_REVERSED
JOURNAL_ENTRY_CANCELLED

FISCAL_YEAR_CREATED
FISCAL_YEAR_CLOSED
ACCOUNTING_PERIOD_CREATED
ACCOUNTING_PERIOD_CLOSED
ACCOUNTING_SETTINGS_UPDATED
```

Context:

```text
account_id
journal_entry_id
fiscal_year_id
accounting_period_id
source_module
old_values
new_values
```

Do not attach patient_id/visit_id unless the journal entry line is explicitly linked to a patient/visit.

Manual accounting changes are global finance logs.

logs:audit Stage-2 gate must remain green.

---

# 15. Validation

Validate:

* account code required and unique
* account name required
* account type required
* account normal balance valid
* journal entry date required
* journal entry date inside open accounting period
* journal must have at least two lines
* each line must have account
* each line must have debit or credit, not both
* total debit equals total credit
* cannot post to inactive account
* cannot post into closed period
* cannot post into closed fiscal year
* cannot edit posted journal entry
* cannot delete posted journal entry
* reversal requires reason
* unauthorized users cannot access accounting actions

Do not skip validation because tests are deferred.

---

# 16. Manual Verification Strategy

Do not write the full automated test suite yet.

For now:

* focus on implementation
* keep the code clean and testable
* add only minimal smoke checks if absolutely necessary
* do not spend time building complete feature tests now
* do not block implementation because tests are not complete

Full tests will be written after the whole accounting implementation is complete.

For this phase, provide manual verification notes instead of full automated tests.

Manual verification required:

1. Create account manually.
2. Confirm duplicate account code is rejected.
3. Create fiscal year.
4. Create accounting period.
5. Create balanced journal entry.
6. Confirm unbalanced journal entry is rejected.
7. Confirm journal with less than two lines is rejected.
8. Confirm journal line cannot have both debit and credit.
9. Confirm journal line cannot have neither debit nor credit.
10. Post balanced journal entry.
11. Confirm posted journal cannot be edited.
12. Reverse posted journal.
13. Confirm reversal swaps debit and credit.
14. Open Trial Balance.
15. Confirm debit and credit totals match.
16. Open General Ledger for an account.
17. Confirm running balance displays correctly.
18. Confirm unauthorized users cannot access accounting pages.
19. Confirm accounting actions appear in activity logs.
20. Confirm existing billing/procurement/stock workflows still work.
21. Confirm logs:audit Stage-2 gate still passes.

Do not remove testability.

Do not write messy code because tests are postponed.

Do not skip validation.

Do not skip permissions.

Do not skip audit logs.

---

# 17. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_1_FOUNDATION_REPORT.md
```

Include:

* database changes
* models added/updated
* services added
* chart of accounts structure
* journal entry workflow
* validation rules
* permissions
* reports added
* activity logs added
* manual verification completed
* what is intentionally not automated yet
* what tests must be written later
* known risks/TODOs
* next phase recommendation

Update any existing finance/accounting docs if applicable.

---

# 18. Acceptance Criteria

Phase 1 is complete when:

* Chart of Accounts exists.
* Fiscal years exist.
* Accounting periods exist.
* Journal entries and journal lines exist.
* Manual journal entries can be created.
* Balanced journal entries can be posted.
* Unbalanced entries are rejected.
* Journals with invalid lines are rejected.
* Posted journals cannot be edited directly.
* Reversal workflow works.
* Trial Balance report works.
* General Ledger report works.
* Default hospital chart of accounts is seeded.
* Accounting permissions exist.
* Accounting actions are logged.
* Existing billing/procurement/stock workflows are not broken.
* logs:audit Stage-2 gate still passes.
* Manual verification is completed and documented.
* Full automated tests are deferred until the final accounting implementation pass.

---

# 19. Important Rules

Do not replace invoices with journal entries.

Do not replace payments with journal entries.

Do not replace supplier ledger with journal entries.

Do not replace stock movements with journal entries.

Do not delete operational financial records.

Do not automatically post all modules yet.

Do not allow unbalanced journal entries.

Do not allow posting into closed periods.

Do not allow editing posted journal entries.

Do not bypass permissions.

Do not bypass validation.

Do not create accounting logs outside ActivityLogService.

Do not break Stage-2 logs:audit CI gate.

Proceed with Accounting Phase 1 Foundation now.

````

After this lands, Phase 2 should be:

```text
Billing → Accounting Posting
````

That is where invoices, revenue, patient receivables, insurance receivables, sponsor receivables, payments, discounts, credit notes, write-offs, and refunds start generating real journal entries automatically.
