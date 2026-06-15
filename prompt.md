You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Execution Phase B — Bank Accounts, Statement Import & Formal Bank Reconciliation

## Goal

Implement formal bank account management, bank statement import, statement-line matching, bank reconciliation preparation, approval, reopening, and adjustment posting.

This phase must build on:

```text
Accounting Phase 0 — Shared Controls and Readiness
Accounting Phase A — Basic-to-Advanced Posting Bridge
```

Do not create a parallel accounting or reconciliation system.

Use the existing Advanced Accounting module, existing journal services, Phase 0 posting attempts, Phase 0 account mappings, Phase A bridge conventions, and existing permissions/module middleware.

---

# 1. Required Context

Read:

```text
docs/ACCOUNTING_GAP_EXECUTION_MASTER_PLAN.md
docs/ACCOUNTING_PHASE_0_SHARED_CONTROLS_AND_READINESS_REPORT.md
docs/ACCOUNTING_PHASE_A_BASIC_TO_ADVANCED_POSTING_BRIDGE_REPORT.md
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Current confirmed baseline:

```text
Phase 0 complete.
Phase A complete.
Full suite passing.
Accounting suite passing.
Localization suite passing.
Active runtime candidates: 0.
Basic Accounting entries can now post to GL through controlled bridge.
```

Do not break this baseline.

---

# 2. Scope of This Phase

Implement:

```text
bank account register
bank statement import
CSV statement parser
statement-line storage
duplicate import/line protection
manual and suggested matching
bank reconciliation preparation
bank reconciliation approval
bank reconciliation reopening
bank reconciliation adjustment proposal
bank charge / interest adjustment posting
reconciliation statement view
permissions
audit logging
tests
documentation
```

Do not yet implement:

```text
cash flow statement
subledger reconciliation workbench
payroll posting
budgets
fixed assets
statutory tax returns
receivables collector workbench
claims settlement accounting
```

Those come later.

---

# 3. Core Rules

Bank reconciliation must be:

```text
auditable
permission-aware
module-aware
reversible/reopenable
traceable to statement lines and book transactions
safe from duplicate imports
safe from silent auto-matching
```

Important rules:

```text
Imported bank files and statement lines are immutable.
Rejected imports stay historically visible.
Approved reconciliations are locked.
Reopening requires elevated permission and a reason.
Bank charges and interest must be proposed first, then approved before journal posting.
Matching suggestions must not silently become approved matches unless explicitly confirmed.
Do not delete bank statement history.
Do not create journal entries directly in controllers.
Do not bypass AccountingPostingService, JournalEntryService, or ActivityLogService.
```

---

# 4. Module Rules

Bank reconciliation belongs to:

```text
accounting_advanced
```

All routes must use:

```text
auth
module:accounting_basic
module:accounting_advanced
permission middleware
```

Do not add a separate bank reconciliation module toggle in this phase.

Do not make Billing & Collections depend on Accounting.

---

# 5. Database Tables

Create the following tables if they do not already exist:

```text
bank_accounts
bank_statement_imports
bank_statement_lines
bank_reconciliations
bank_reconciliation_matches
bank_reconciliation_adjustments
```

Use safe additive migrations.

Use string statuses with application validation.

Do not use database enums.

Use `DECIMAL(18,2)` for monetary amounts.

Use `LONGTEXT` for imported file metadata or parser snapshots if JSON compatibility is uncertain.

Use explicit short index names compatible with MariaDB.

Do not cascade-delete financial history.

---

# 6. bank_accounts

Fields:

```text
id
name
bank_name
branch_name
account_name
account_number_masked
account_number_hash
currency
gl_account_id
opening_date
opening_balance
is_active
notes
created_by
updated_by
timestamps
```

Rules:

```text
Store masked account number for display.
Store hash for duplicate/reference checks if needed.
Do not expose full bank account number broadly.
Each bank account must map to a GL cash/bank account.
Inactive accounts cannot receive new imports.
Historical imports and reconciliations remain visible.
```

Permissions must control who can view and manage bank accounts.

---

# 7. bank_statement_imports

Fields:

```text
id
bank_account_id
format
original_filename
file_hash
period_start
period_end
opening_balance
closing_balance
total_debit
total_credit
line_count
status
imported_by
imported_at
approved_by
approved_at
rejected_by
rejected_at
rejection_reason
error_summary
metadata_snapshot
timestamps
```

Statuses:

```text
draft
validated
imported
approved
rejected
cancelled
```

Rules:

```text
Duplicate file_hash for the same bank account must be blocked.
Validation preview should happen before final import.
Rejected imports remain visible but unusable for reconciliation.
Approved/imported lines are immutable.
```

---

# 8. bank_statement_lines

Fields:

```text
id
bank_statement_import_id
bank_account_id
line_number
transaction_date
value_date
reference
normalized_reference
description
debit_amount
credit_amount
balance_after
external_transaction_id
line_hash
match_status
matched_amount
unmatched_amount
metadata_snapshot
timestamps
```

Match statuses:

```text
unmatched
suggested
partially_matched
matched
ignored
```

Rules:

```text
A line must not be matched above its debit/credit amount.
Duplicate line hashes within the same bank account and date/reference/amount context must be detected.
Statement lines are not deleted after import.
```

---

# 9. bank_reconciliations

Fields:

```text
id
bank_account_id
period_start
period_end
statement_opening_balance
statement_closing_balance
book_opening_balance
book_closing_balance
outstanding_deposits_total
outstanding_withdrawals_total
adjustments_total
difference
status
prepared_by
prepared_at
approved_by
approved_at
reopened_by
reopened_at
reopen_reason
reversed_by
reversed_at
reversal_reason
notes
timestamps
```

Statuses:

```text
draft
prepared
approved
reopened
reversed
cancelled
```

Rules:

```text
Approved reconciliation is locked.
Reopening requires permission and reason.
Reversing must preserve the original record.
Reconciliation must show why statement and book balances agree or differ.
```

---

# 10. bank_reconciliation_matches

Fields:

```text
id
bank_reconciliation_id
bank_statement_line_id
matchable_type
matchable_id
matched_amount
match_method
confidence_score
status
matched_by
matched_at
unmatched_by
unmatched_at
unmatch_reason
metadata_snapshot
timestamps
```

Match methods:

```text
manual
suggested_exact
suggested_reference
suggested_amount_date
suggested_many_to_one
suggested_one_to_many
```

Rules:

```text
Matches must be reversible/unmatchable before reconciliation approval.
Approved reconciliation locks matches.
Matching must never silently alter the source transaction.
```

Matchable sources may include:

```text
journal lines affecting the linked bank GL account
patient payments
supplier payments
Basic Accounting posted cash/bank entries
payroll settlements later
bank adjustment journals
```

Start with journal/cashbook based matching if that is safest.

---

# 11. bank_reconciliation_adjustments

Fields:

```text
id
bank_reconciliation_id
bank_account_id
type
description
amount
side
account_id
journal_entry_id
status
proposed_by
proposed_at
approved_by
approved_at
posted_by
posted_at
rejected_by
rejected_at
rejection_reason
metadata_snapshot
timestamps
```

Types:

```text
bank_charge
interest_income
transfer_fee
correction
other
```

Side:

```text
debit
credit
```

Statuses:

```text
draft
proposed
approved
posted
rejected
cancelled
reversed
```

Rules:

```text
Adjustments are proposed first.
Approved adjustments post through accounting services.
Do not post adjustment journals directly from controllers.
Do not allow unapproved adjustments to affect reconciliation as final.
```

Suggested journals:

Bank charge:

```text
Dr Bank Charges Expense
Cr Bank Account
```

Interest income:

```text
Dr Bank Account
Cr Interest Income
```

Use account mappings or selected accounts.

Do not hardcode account IDs.

---

# 12. Services To Add

Create:

```text
BankAccountService
BankStatementImportService
BankStatementCsvParser
BankMatchSuggestionService
BankReconciliationService
BankReconciliationAdjustmentPostingService
```

Use existing:

```text
AccountingPostingService
AccountingPostingAttemptService
AccountingAccountMappingService
JournalEntryService
ActivityLogService
```

Controllers must call services.

Do not put reconciliation logic in Blade.

---

# 13. BankStatementImportService

This service should:

```text
validate CSV file
preview parsed rows
normalize dates
normalize debit/credit values
normalize references
calculate line hashes
detect duplicate files
detect duplicate lines
store import metadata
store statement lines
reject invalid rows with clear errors
support import cancellation/rejection
```

CSV parser requirements:

```text
support configurable column mapping
support date format configuration
support debit/credit separate columns
support signed amount column where possible
support opening and closing balance input/manual confirmation
```

First release can support CSV only.

Do not implement OFX/MT940 yet.

---

# 14. BankMatchSuggestionService

Generate match suggestions using:

```text
exact amount
transaction date tolerance
value date tolerance
reference match
normalized reference match
description text match
known payment reference
journal line amount
bank GL account
```

Return explainable suggestions:

```text
suggested source
matched amount
confidence score
reason
date difference
reference comparison
```

Do not auto-approve suggestions.

User must confirm.

---

# 15. BankReconciliationService

This service should:

```text
prepare reconciliation
calculate statement opening/closing balances
calculate book opening/closing balances from linked GL account
calculate outstanding deposits
calculate outstanding withdrawals
calculate adjustments
calculate difference
create draft/prepared reconciliation
approve reconciliation
reopen reconciliation
reverse reconciliation where supported
lock approved reconciliation
```

Formula:

```text
Adjusted bank statement balance
= statement closing balance
+ outstanding deposits
- outstanding withdrawals
+/- approved adjustments

Book balance
= GL cash/bank balance at period end

Difference
= adjusted statement balance - book balance
```

Use the project’s existing money precision rules.

Do not rely on PHP floats as authoritative.

---

# 16. Matching UI

Add screens for:

```text
Bank accounts index/show/create/edit
Statement import wizard
Statement import preview
Statement lines list
Match suggestions
Manual matching workspace
Reconciliation draft
Reconciliation statement
Approval screen
Reopen screen
Adjustment proposal screen
```

Use Bootstrap 5 and Tabler Icons only.

Do not introduce new frontend frameworks.

---

# 17. Matching Workflow

Workflow:

```text
Upload CSV
Preview parsed lines
Confirm import
Review unmatched statement lines
View suggested matches
Confirm manual/suggested matches
Propose bank charges/interest if needed
Prepare reconciliation
Approve reconciliation if difference is zero or within tolerance
Lock approved reconciliation
```

If difference is not zero, show clear warning and block approval unless a configured tolerance/override permission allows it.

---

# 18. Permissions

Add permissions:

```text
accounting.bank_accounts.view
accounting.bank_accounts.manage
accounting.bank_statements.import
accounting.bank_statements.view
accounting.bank_statements.reject
accounting.bank_reconciliation.view
accounting.bank_reconciliation.manage
accounting.bank_reconciliation.match
accounting.bank_reconciliation.approve
accounting.bank_reconciliation.reopen
accounting.bank_reconciliation.reverse
accounting.bank_adjustments.propose
accounting.bank_adjustments.approve
accounting.bank_adjustments.post
```

Suggested role defaults:

```text
Accountant:
- view bank accounts
- import/view statements
- manage reconciliation
- match lines
- propose adjustments

Finance Manager:
- approve/reopen/reverse reconciliations
- approve/post adjustments
- manage bank accounts

Administrator:
- all seeded permissions
```

Do not grant these permissions to broad clinical roles.

---

# 19. Audit Logging

Use `ActivityLogService`.

Audit:

```text
BANK_ACCOUNT_CREATED
BANK_ACCOUNT_UPDATED
BANK_ACCOUNT_DISABLED
BANK_STATEMENT_IMPORT_PREVIEWED
BANK_STATEMENT_IMPORTED
BANK_STATEMENT_REJECTED
BANK_STATEMENT_LINE_MATCHED
BANK_STATEMENT_LINE_UNMATCHED
BANK_RECONCILIATION_PREPARED
BANK_RECONCILIATION_APPROVED
BANK_RECONCILIATION_REOPENED
BANK_RECONCILIATION_REVERSED
BANK_ADJUSTMENT_PROPOSED
BANK_ADJUSTMENT_APPROVED
BANK_ADJUSTMENT_POSTED
BANK_ADJUSTMENT_REJECTED
```

Run:

```bash
php artisan logs:audit --json
```

Fix any missing/needs-review logs.

---

# 20. Localisation

All new labels must be localized EN/FR.

Use or extend:

```text
lang/en/accounting.php
lang/fr/accounting.php
```

Required keys include:

```text
bank_accounts
bank_account
bank_name
branch_name
account_name
account_number
masked_account_number
opening_balance
statement_import
statement_lines
statement_period
opening_statement_balance
closing_statement_balance
transaction_date
value_date
reference
normalized_reference
debit_amount
credit_amount
balance_after
match_status
matched_amount
unmatched_amount
match_suggestions
manual_match
confirm_match
unmatch
reconciliation
bank_reconciliation
prepare_reconciliation
approve_reconciliation
reopen_reconciliation
reconciliation_statement
outstanding_deposits
outstanding_withdrawals
book_balance
statement_balance
adjusted_statement_balance
difference
bank_charge
interest_income
adjustment
adjustments
propose_adjustment
approve_adjustment
post_adjustment
```

Maintain EN/FR parity.

Run:

```bash
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
```

Active runtime candidates must remain:

```text
0
```

---

# 21. Tests

Add tests for:

```text
bank account can be created with GL account mapping
unauthorized user cannot manage bank accounts
duplicate bank statement file is rejected
CSV preview writes no statement lines
CSV import creates statement lines
invalid CSV rows produce clear errors
duplicate statement lines are blocked or skipped safely
match suggestion finds exact amount/reference match
manual match links statement line to journal line
statement line cannot be overmatched
match can be reversed before approval
approved reconciliation locks matches
bank charge adjustment posts balanced journal
interest income adjustment posts balanced journal
reconciliation calculates statement/book difference
reconciliation cannot approve with unexplained difference
reconciliation can approve when difference is zero
reopening approved reconciliation requires permission and reason
ActivityLogService records import/match/approval events
module middleware blocks direct route when Advanced Accounting disabled
localisation lock remains active runtime 0
```

Existing Phase 0 and Phase A accounting tests must still pass.

---

# 22. Verification Commands

Run:

```bash
php artisan migrate:fresh --env=testing --force
php artisan test tests/Feature/Accounting/AccountingPhase0ControlsTest.php
php artisan test tests/Feature/Accounting
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan logs:audit --json
php artisan route:list
php artisan view:cache
php artisan view:clear
php artisan test
git diff --check
```

If frontend assets are touched:

```bash
npm run build
```

---

# 23. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_B_BANK_ACCOUNTS_AND_RECONCILIATION_REPORT.md
```

Include:

```text
summary
database changes
models added
services added
commands added if any
permissions added
routes/controllers/views added
CSV import behavior
duplicate detection
matching strategy
reconciliation formula
adjustment posting strategy
audit logging
tests added
commands run
localisation audit result
known limitations
next recommended phase
```

---

# 24. Acceptance Criteria

Phase B is complete only when:

```text
bank accounts can be configured and linked to GL accounts
CSV statement import supports preview and confirmed import
duplicate imports are blocked
statement lines are stored immutably
match suggestions are explainable and not auto-approved
manual matching works
statement lines cannot be overmatched
matches can be reversed before approval
reconciliation calculates book/statement/outstanding/adjustment differences
approved reconciliations are locked
reopening requires permission and reason
bank charges and interest can post through accounting services
permissions are enforced
module middleware protects direct routes
ActivityLogService is used
EN/FR localisation parity passes
active runtime candidates remain 0
Phase 0 tests still pass
Phase A tests still pass
Accounting suite passes
route list works
view cache compiles
logs:audit is clean or documented with root-cause fixes
full test suite is run
documentation report is created
```

Proceed with Accounting Execution Phase B now.