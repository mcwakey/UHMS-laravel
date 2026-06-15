You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Execution Phase D — Subledger Reconciliation Workbench

## Goal

Implement a Subledger Reconciliation Workbench for UHMS.

The workbench must compare operational subledger balances against Advanced Accounting GL control accounts and show explainable differences.

This phase builds on:

```text
Accounting Phase 0 — Shared Posting Controls
Accounting Phase A — Basic-to-Advanced Posting Bridge
Accounting Phase B — Bank Accounts and Bank Reconciliation
Accounting Phase C — Failed Posting Workbench
```

Do not create a parallel accounting engine.

Use existing:

```text
JournalEntryService
GeneralLedgerService
AccountingPostingService
AccountingPostingAttemptService
AccountingCloseReadinessService
ActivityLogService
```

---

# 1. Required Context

Read:

```text
docs/ACCOUNTING_GAP_EXECUTION_MASTER_PLAN.md
docs/ACCOUNTING_PHASE_0_SHARED_CONTROLS_AND_READINESS_REPORT.md
docs/ACCOUNTING_PHASE_A_BASIC_TO_ADVANCED_POSTING_BRIDGE_REPORT.md
docs/ACCOUNTING_PHASE_B_BANK_ACCOUNTS_AND_RECONCILIATION_REPORT.md
docs/ACCOUNTING_PHASE_C_FAILED_POSTING_WORKBENCH_REPORT.md
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Current baseline:

```text
Phase 0 complete.
Phase A complete.
Phase B complete.
Phase C complete.
Failed posting workbench exists.
Bank reconciliation exists.
Basic-to-Advanced posting bridge exists.
Active runtime candidates: 0.
Audit funnel clean for recent accounting code.
```

Important testing instruction:

```text
Do not run the wide full test suite after this individual phase.
The wide full-suite test will be left until all accounting-gap implementation phases in this batch are complete.
For this phase, run only necessary safety checks: migrations, route list, view cache, localisation audit, permission audit, logs:audit, PHP lint where relevant, and git diff check.
```

---

# 2. Scope of This Phase

Implement reconciliation for these domains:

```text
Accounts receivable control account vs open receivables
Supplier payables control account vs open payables
Inventory control account vs inventory valuation
Payroll payable/liability accounts vs payroll subledger where available
Cash/bank GL accounts vs bank/cash positions where available
PAYE payable account vs payroll tax calculations where available
Pension/SSNIT payable account vs payroll pension calculations where available
```

If some source subledgers are not fully implemented yet, add the reconciliation domain as:

```text
available
partially_available
not_available
```

and explain the missing source data.

Do not fake balances.

---

# 3. Core Rules

The reconciliation workbench must be:

```text
snapshot-based
repeatable
auditable
permission-aware
module-aware
drillable
non-destructive
```

Rules:

```text
Reconciliation does not auto-create corrections.
Reconciliation does not silently change GL or source records.
Differences must be classified and explained.
Corrections must happen through existing posting, reversal, adjustment, payment, credit-note, write-off, or source services.
Manual journal differences must be visible separately.
```

---

# 4. Database Tables

Create additive tables:

```text
accounting_reconciliation_runs
accounting_reconciliation_items
accounting_reconciliation_resolutions
```

Use string statuses with application validation.

Do not use database enums.

Use `DECIMAL(18,2)` for money.

Use `LONGTEXT` for snapshots where needed.

Use explicit short MariaDB-safe index names.

---

# 5. accounting_reconciliation_runs

Fields:

```text
id
reconciliation_type
period_start
period_end
as_of_date
status
tolerance_amount
subledger_total
gl_total
difference_amount
difference_classification
source_snapshot
gl_snapshot
summary_snapshot
started_by
started_at
completed_by
completed_at
approved_by
approved_at
cancelled_by
cancelled_at
cancellation_reason
notes
timestamps
```

Statuses:

```text
draft
running
completed
approved
cancelled
superseded
```

Reconciliation types:

```text
accounts_receivable
accounts_payable
inventory
payroll
cash_bank
paye
pension
```

---

# 6. accounting_reconciliation_items

Fields:

```text
id
accounting_reconciliation_run_id
source_type
source_id
source_reference
source_description
gl_account_id
subledger_amount
gl_amount
difference_amount
classification
resolution_status
metadata_snapshot
timestamps
```

Classifications:

```text
balanced
timing_difference
unposted_source
failed_posting
manual_journal
mapping_issue
source_data_issue
period_cutoff
unknown_difference
not_available
```

Resolution statuses:

```text
open
explained
resolved
accepted_timing
waived
```

---

# 7. accounting_reconciliation_resolutions

Fields:

```text
id
accounting_reconciliation_run_id
accounting_reconciliation_item_id nullable
resolution_type
resolution_note
linked_journal_entry_id nullable
linked_posting_attempt_id nullable
linked_source_type nullable
linked_source_id nullable
resolved_by
resolved_at
metadata_snapshot
timestamps
```

Resolution types:

```text
retry_posting
reverse_journal
source_corrected
manual_journal_linked
accepted_timing_difference
mapping_corrected
waived_after_review
other
```

---

# 8. Reconciliation Domains

## 8.1 Accounts Receivable

Compare:

```text
open invoice receivables / payer balances
```

against:

```text
patient receivable control account
insurance receivable control account
sponsor receivable control account
corporate receivable control account
```

Use existing receivable models and AR aging foundation.

Do not create a new receivable balance table.

Show drill-down by:

```text
payer type
payer
invoice
visit/patient where permitted
aging bucket
control account
```

## 8.2 Accounts Payable

Compare:

```text
open supplier payables
supplier balances
unpaid supplier payments where relevant
```

against:

```text
supplier payable control account
```

Show supplier-level drill-down.

## 8.3 Inventory

Compare:

```text
stock valuation by inventory class/location/product
```

against:

```text
inventory control accounts
```

Show differences from:

```text
unposted stock movement
failed inventory posting
manual GL journal
valuation mismatch
period cutoff
```

## 8.4 Payroll

Compare:

```text
approved unpaid payroll
payroll deductions/liabilities
```

against:

```text
payroll payable
salary payable
PAYE payable
pension payable
staff loan receivable if available
```

If payroll posting is not yet fully connected, mark payroll as partially available and document required Phase E dependency.

## 8.5 Cash/Bank

Compare:

```text
bank reconciliation/book balances
cashier cashbook/daily collection where available
```

against:

```text
cash and bank GL accounts
```

Use Phase B bank reconciliation data where available.

## 8.6 PAYE

Compare:

```text
payroll PAYE calculations less PAYE settlements
```

against:

```text
PAYE payable GL account
```

If payroll tax data exists but posting is not connected, classify as partially available.

## 8.7 Pension / SSNIT

Compare:

```text
employee/employer pension calculations less settlements
```

against:

```text
pension payable GL account
```

If source data is not complete, classify as partially available.

---

# 9. Services To Add

Create:

```text
SubledgerReconciliationService
ReceivablesReconciliationService
PayablesReconciliationService
InventoryReconciliationService
PayrollReconciliationService
CashBankReconciliationService
TaxLiabilityReconciliationService
ReconciliationResolutionService
```

Use shared helpers where possible.

Do not put reconciliation calculations in controllers or Blade.

---

# 10. SubledgerReconciliationService

This service should:

```text
start reconciliation run
calculate subledger total
calculate GL total
calculate difference
create reconciliation items
classify differences
store snapshots
complete run
approve run
cancel run
supersede old draft runs
```

A reconciliation run must be reproducible from stored snapshots.

Do not depend only on live totals after the run is completed.

---

# 11. Difference Classification

Classify differences where possible:

```text
failed_posting → source has failed posting attempt
unposted_source → source approved/eligible but not posted
manual_journal → GL control account entry has no source link
mapping_issue → source/account mapping missing or wrong
timing_difference → source and GL dates fall in different periods
source_data_issue → source amount differs from posted snapshot
period_cutoff → source/posting outside selected period
unknown_difference → cannot classify safely
```

Do not claim a difference is solved unless evidence exists.

---

# 12. Workbench UI

Add screens under Advanced Accounting:

```text
Reconciliation dashboard
New reconciliation run
Reconciliation run detail
Reconciliation item drill-down
Resolution form
Approval screen
History screen
```

Dashboard cards:

```text
balanced domains
difference detected
failed posting linked
manual journals detected
unposted source records
oldest unresolved difference
last reconciliation date
```

Use Bootstrap 5 and Tabler Icons only.

Do not introduce new frontend frameworks.

---

# 13. Resolution Workflow

Allow finance users to resolve or explain differences.

Supported actions:

```text
link failed posting attempt
link corrective journal
mark as accepted timing difference
mark mapping corrected
mark source corrected
waive after review
add resolution note
```

Rules:

```text
Approval of a reconciliation with unresolved differences requires elevated permission.
Resolved/explained items remain visible.
Waived differences remain visible.
Do not auto-post corrections.
```

---

# 14. Close Readiness Integration

Extend `AccountingCloseReadinessService` to include:

```text
latest reconciliation per domain
unapproved reconciliation runs
domains with unresolved differences
domains not run for the period
failed postings linked to reconciliation differences
manual control-account journals
```

Do not hard-block period close in this phase unless existing close code already supports safe blocking.

Document recommended future close-block behavior.

---

# 15. Permissions

Add permissions:

```text
accounting.subledger_reconciliation.view
accounting.subledger_reconciliation.run
accounting.subledger_reconciliation.resolve
accounting.subledger_reconciliation.approve
accounting.subledger_reconciliation.cancel
```

Suggested role defaults:

```text
Accountant:
- view
- run
- resolve

Finance Manager:
- view
- run
- resolve
- approve
- cancel

Administrator / Super Admin:
- all
```

Do not grant to broad clinical roles.

---

# 16. Audit Logging

Use `ActivityLogService`.

Audit:

```text
SUBLEDGER_RECONCILIATION_STARTED
SUBLEDGER_RECONCILIATION_COMPLETED
SUBLEDGER_RECONCILIATION_APPROVED
SUBLEDGER_RECONCILIATION_CANCELLED
SUBLEDGER_RECONCILIATION_RESOLUTION_ADDED
SUBLEDGER_RECONCILIATION_ITEM_WAIVED
SUBLEDGER_RECONCILIATION_CLOSE_READINESS_VIEWED
```

Run:

```bash
php artisan logs:audit --json
```

Fix new missing/needs-review audit gaps.

---

# 17. Localisation

All new labels must be localised EN/FR.

Use or extend:

```text
lang/en/accounting.php
lang/fr/accounting.php
```

Keys:

```text
subledger_reconciliation
reconciliation_run
reconciliation_runs
reconciliation_type
subledger_total
gl_total
difference_amount
difference_classification
balanced_domains
difference_detected
manual_journals_detected
unposted_source_records
oldest_unresolved_difference
run_reconciliation
approve_reconciliation_run
cancel_reconciliation_run
resolution_note
accepted_timing_difference
mapping_corrected
source_corrected
manual_journal_linked
waived_after_review
accounts_receivable_reconciliation
accounts_payable_reconciliation
inventory_reconciliation
payroll_reconciliation
cash_bank_reconciliation
paye_reconciliation
pension_reconciliation
```

Maintain EN/FR parity.

Run localisation audit scanner:

```bash
php scripts/localisation-audit.php
```

Active runtime candidates must remain:

```text
0
```

---

# 18. Navigation

Add a sidebar link under Advanced Accounting:

```text
Subledger Reconciliation
```

If sidebar assertions are locked, update tests later during the final wide test phase instead of forcing broad test rewrites now.

Route access must work even if navigation is adjusted later.

---

# 19. Tests

Add or update tests for Phase D, but do not run the wide full suite yet.

Required test coverage to add:

```text
permission-protected reconciliation dashboard
module middleware blocks direct routes when Advanced Accounting disabled
AR reconciliation calculates subledger and GL totals
AP reconciliation calculates subledger and GL totals where source exists
inventory reconciliation calculates valuation vs GL where source exists
payroll reconciliation marks partially available if payroll posting is not ready
cash/bank reconciliation uses Phase B data where available
failed posting is classified as failed_posting
manual control-account journal is classified as manual_journal
unposted source is classified as unposted_source
resolution note can be added
approval requires permission
unresolved differences block normal approval unless elevated permission exists
close readiness includes reconciliation status
audit logs are recorded
localisation keys exist
```

Do not run:

```bash
php artisan test
```

during this phase unless explicitly instructed.

The wide full-suite test will be run after all accounting implementation phases in the current batch are completed.

---

# 20. Minimal Verification Commands For This Phase

Run only necessary safety checks:

```bash
php artisan migrate --force
php artisan route:list
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php artisan logs:audit --json
php artisan permissions:audit --strict
git diff --check
```

Also run PHP lint on changed files if practical:

```bash
find app database routes lang resources/views -name "*.php" -print0 | xargs -0 -n1 php -l
```

Do not run the full application test suite yet.

---

# 21. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_D_SUBLEDGER_RECONCILIATION_WORKBENCH_REPORT.md
```

Include:

```text
summary
database changes
models added
services added
permissions added
routes/controllers/views added
reconciliation domains implemented
domains marked partially available
difference classifications
resolution workflow
close readiness integration
audit logging
localisation audit result
minimal verification commands run
tests added but not fully executed
known limitations
next recommended phase
```

---

# 22. Acceptance Criteria

Phase D is complete only when:

```text
subledger reconciliation dashboard exists
reconciliation runs can be created
AR reconciliation is available
AP reconciliation is available where source data exists
inventory reconciliation is available where source data exists
cash/bank reconciliation can use Phase B data
payroll/PAYE/pension domains are marked available or partially available honestly
differences are classified
items are drillable
resolutions can be added
waived/explained items remain visible
close readiness includes reconciliation status
permissions are enforced
module middleware protects direct routes
ActivityLogService is used
EN/FR localisation parity is maintained
active runtime candidates remain 0
route list works
view cache compiles
logs:audit has no new missing/needs-review gaps
permissions audit is clean
documentation report is created
full test suite is intentionally deferred to the final wide accounting test phase
```

Proceed with Accounting Execution Phase D now.
