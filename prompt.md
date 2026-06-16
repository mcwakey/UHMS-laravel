You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Execution Phase E2 — Statutory Liability Settlement Controls for PAYE, Pension / SSNIT & Payroll Deductions

## Goal

Complete the statutory payroll liability settlement gap left after Phase E.

Phase E implemented:

```text
approved payroll accrual posting
net salary payable recognition
PAYE payable recognition
pension / SSNIT payable recognition
other deduction payable recognition
salary settlement posting
payroll accrual reversal
salary settlement reversal
```

But Phase E explicitly deferred:

```text
PAYE statutory remittance settlement records
pension / SSNIT statutory remittance settlement records
other payroll deduction settlement controls
```

This phase must implement those settlement controls.

Do not rebuild payroll calculation.

Do not rebuild HR attendance.

Do not rebuild payslips.

Do not build full statutory tax return filing yet.

This phase is only for accounting settlement of already-recognised payroll liabilities.

---

# 1. Required Context

Read:

```text
docs/ACCOUNTING_GAP_EXECUTION_MASTER_PLAN.md
docs/ACCOUNTING_PHASE_0_SHARED_CONTROLS_AND_READINESS_REPORT.md
docs/ACCOUNTING_PHASE_A_BASIC_TO_ADVANCED_POSTING_BRIDGE_REPORT.md
docs/ACCOUNTING_PHASE_B_BANK_ACCOUNTS_AND_RECONCILIATION_REPORT.md
docs/ACCOUNTING_PHASE_C_FAILED_POSTING_WORKBENCH_REPORT.md
docs/ACCOUNTING_PHASE_D_SUBLEDGER_RECONCILIATION_WORKBENCH_REPORT.md
docs/ACCOUNTING_PHASE_E_PAYROLL_ACCOUNTING_POSTING_REPORT.md
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Current baseline:

```text
Phase 0 complete.
Phase A complete.
Phase B complete.
Phase C complete.
Phase D complete.
Phase E complete for payroll accrual and salary settlement.
PAYE and pension liabilities are recognised in GL.
PAYE and pension statutory remittance settlement records are not yet modelled.
Active runtime candidates must remain 0.
```

Important testing instruction:

```text
Do not run the wide full application test suite after this individual phase.
The wide full-suite test is deferred until all accounting-gap implementation phases in this batch are complete.
For this phase, run only necessary safety checks: migrations, route list, view cache, localisation audit, permission audit, logs:audit, PHP lint where practical, focused accounting checks where needed, and git diff check.
```

---

# 2. Scope of This Phase

Implement settlement controls for:

```text
PAYE payable settlement
pension / SSNIT payable settlement
other payroll deduction payable settlement
staff loan recovery settlement where applicable
statutory liability settlement approval
statutory liability settlement posting
statutory liability settlement reversal
liability outstanding balance calculation
settlement allocation to payroll runs
failed posting workbench integration
subledger reconciliation integration
close readiness integration
permissions
audit logging
localisation
documentation
```

Do not implement yet:

```text
full statutory tax return filing
VAT/NHIL/GETFund returns
withholding tax certificates
electronic filing APIs
bank payment file generation
department payroll expense analytics
cash flow statement
budgets
fixed assets
receivables collector workbench
claims settlement accounting
```

---

# 3. Core Rules

Statutory liability settlement must be:

```text
approval-gated
balanced
transactional
idempotent
auditable
permission-aware
module-aware
reversible
reconcilable
```

Rules:

```text
Only recognised liabilities can be settled.
Settlement cannot exceed outstanding liability unless an explicit overpayment/credit workflow exists.
Draft settlement must not post.
Approved settlement can post.
Posted settlement cannot be edited silently.
Corrections use reversal and replacement settlement.
Do not create journals directly in controllers.
Do not bypass JournalEntryService.
Do not bypass AccountingPostingService.
Do not bypass Phase 0 posting attempts.
Do not bypass ActivityLogService.
```

---

# 4. Module Rules

Routes must require:

```text
auth
module:accounting_basic
module:accounting_advanced
permission middleware
```

Payroll calculation must remain independent from Advanced Accounting.

If Advanced Accounting is disabled:

```text
statutory liability settlement controls are hidden/inaccessible
existing payroll calculation remains unaffected
no false posted/settled status is shown
```

---

# 5. Database Tables

Create additive tables if they do not already exist:

```text
payroll_liability_settlements
payroll_liability_settlement_allocations
```

Use string statuses with application validation.

Do not use database enums.

Use `DECIMAL(18,2)` for money.

Use `LONGTEXT` for metadata snapshots where needed.

Use explicit short MariaDB-safe index names.

Do not cascade-delete financial history.

---

# 6. payroll_liability_settlements

Fields:

```text
id
settlement_reference
liability_type
period_start
period_end
payment_date
amount
bank_account_id nullable
cash_account_id nullable
gl_payment_account_id
liability_account_id
journal_entry_id nullable
reversal_journal_entry_id nullable
status
approved_by nullable
approved_at nullable
posted_by nullable
posted_at nullable
reversed_by nullable
reversed_at nullable
reversal_reason nullable
accounting_error nullable
metadata_snapshot
notes
created_by
updated_by
timestamps
```

Liability types:

```text
paye
pension
ssnit
other_deduction
staff_loan
```

Statuses:

```text
draft
approved
posted
failed
cancelled
reversed
```

Rules:

```text
draft settlement can be edited
approved settlement cannot be edited except posting/reversal controls
posted settlement is immutable
reversal uses reversal journal
cancelled settlement creates no journal
```

---

# 7. payroll_liability_settlement_allocations

Purpose:

```text
Allocate a statutory payment to one or more payroll runs or liability sources.
```

Fields:

```text
id
payroll_liability_settlement_id
payroll_run_id nullable
source_type nullable
source_id nullable
liability_type
allocated_amount
metadata_snapshot
timestamps
```

Rules:

```text
total allocations must equal settlement amount unless unapplied balance is explicitly allowed
allocation cannot exceed outstanding liability for the source
allocation records are retained after posting
```

---

# 8. Liability Outstanding Calculation

Create a reliable outstanding-liability calculation.

For PAYE:

```text
PAYE outstanding =
posted payroll PAYE payable
- posted PAYE settlements
+ reversed settlement amounts
```

For pension / SSNIT:

```text
Pension outstanding =
posted payroll pension payable
- posted pension / SSNIT settlements
+ reversed settlement amounts
```

For other deductions:

```text
Other deduction outstanding =
posted payroll other deduction payable
- posted other deduction settlements
+ reversed settlement amounts
```

For staff loans:

```text
Staff loan recovery outstanding =
posted payroll loan recovery credit
- posted loan allocation/settlement where available
```

If staff loan accounting is not fully implemented, mark it partially available and document the missing source model.

Do not infer payments without records.

---

# 9. Journal Strategy

PAYE settlement:

```text
Dr PAYE Payable
Cr Bank/Cash
```

Pension / SSNIT settlement:

```text
Dr Pension / SSNIT Payable
Cr Bank/Cash
```

Other payroll deduction settlement:

```text
Dr Other Deduction Payable
Cr Bank/Cash
```

Staff loan recovery settlement, if external settlement is required:

```text
Dr Staff Loan Recovery Clearing / Payable
Cr Bank/Cash
```

If staff loan recovery should reduce staff loan receivable directly, use the existing staff loan accounting design. Do not guess.

All accounts must come from configurable mappings/settings.

Do not hardcode account IDs.

---

# 10. Account Mapping

Use existing accounting settings and Phase 0 mappings where possible.

Required accounts:

```text
PAYE payable account
pension / SSNIT payable account
other deduction payable account
staff loan receivable or clearing account where applicable
bank/cash payment account
```

Use existing Phase D settings where available:

```text
paye_payable_account_id
pension_payable_account_id
payroll_payable_account_id
```

Add missing mapping scopes only if needed:

```text
payroll_liability
payroll_liability_payment
statutory_payment_account
```

Missing mappings must:

```text
block posting
create retained failed posting attempt
show clear error message
not create partial journal
```

---

# 11. Service To Add

Create:

```text
PayrollLiabilitySettlementService
```

Responsibilities:

```text
calculate outstanding liabilities
create draft settlement
validate allocations
approve settlement
post settlement journal
prevent over-allocation
reverse posted settlement
link journal and reversal journal
update status
retain accounting errors
create posting attempts
audit state changes
```

Do not put settlement logic in controllers or Blade.

---

# 12. Posting Identity

Use Phase 0 idempotency.

Suggested identities:

```text
source_type = payroll_liability_settlement
source_id = payroll_liability_settlements.id
posting_type = paye_payment
posting_type = pension_payment
posting_type = ssnit_payment
posting_type = other_deduction_payment
posting_type = staff_loan_settlement
posting_version = 1
```

Same settlement and posting type must never produce duplicate journals.

---

# 13. Reversal Rules

Posted settlement reversal:

```text
requires permission
requires reason
creates reversal journal
links reversal_journal_entry_id
marks settlement reversed
restores outstanding liability
does not delete original journal
does not delete allocation records
```

A reversed settlement cannot be posted again.

Create a replacement settlement if needed.

---

# 14. UI Screens

Add screens under Advanced Accounting / Payroll Accounting:

```text
Payroll Liability Settlement Dashboard
Outstanding PAYE Liabilities
Outstanding Pension / SSNIT Liabilities
Other Deduction Liabilities
Create Settlement
Settlement Preview
Settlement Detail
Approve Settlement
Post Settlement
Reverse Settlement
```

Each settlement detail should show:

```text
liability type
period
amount
payment account
liability account
allocations
journal link
reversal journal link
status
approval data
posting data
error message
audit summary
```

Do not expose payroll financial data to unauthorized users.

---

# 15. Failed Posting Workbench Integration

Add retry handler support for:

```text
payroll_liability_settlement:paye_payment
payroll_liability_settlement:pension_payment
payroll_liability_settlement:ssnit_payment
payroll_liability_settlement:other_deduction_payment
payroll_liability_settlement:staff_loan_settlement
```

Retry must:

```text
reuse the same idempotency key
not duplicate journals
retain prior error history
mark posted only after successful journal creation
```

Unsupported liability states must remain visibly failed.

---

# 16. Subledger Reconciliation Integration

Update Phase D reconciliation.

PAYE domain should compare:

```text
posted PAYE liability accruals
- posted PAYE settlements
versus PAYE payable GL account
```

Pension domain should compare:

```text
posted pension / SSNIT liability accruals
- posted pension / SSNIT settlements
versus pension payable GL account
```

Other deductions can be added as:

```text
available
partially_available
not_available
```

depending on existing payroll data.

After this phase, PAYE and pension reconciliation should no longer be marked partial due to missing settlement records.

If some payroll source records are incomplete, document that exact limitation.

---

# 17. Close Readiness Integration

Update `AccountingCloseReadinessService` to show:

```text
unpaid PAYE liabilities by period
unpaid pension / SSNIT liabilities by period
unpaid other deduction liabilities
failed statutory settlement postings
reversed settlements requiring replacement
settlements awaiting approval
settlements approved but not posted
PAYE/pension reconciliation not run
PAYE/pension reconciliation unresolved differences
```

Do not hard-block period close unless existing close code safely supports it.

Document recommended future close-block behavior.

---

# 18. Permissions

Add permissions:

```text
accounting.payroll_liability.view
accounting.payroll_liability.create
accounting.payroll_liability.approve
accounting.payroll_liability.post
accounting.payroll_liability.reverse
```

Suggested role defaults:

```text
Accountant:
- view
- create

Finance Manager:
- view
- create
- approve
- post
- reverse

Administrator / Super Admin:
- all
```

Do not grant to broad clinical roles.

---

# 19. Audit Logging

Use `ActivityLogService`.

Audit:

```text
PAYROLL_LIABILITY_SETTLEMENT_CREATED
PAYROLL_LIABILITY_SETTLEMENT_APPROVED
PAYROLL_LIABILITY_SETTLEMENT_POSTED
PAYROLL_LIABILITY_SETTLEMENT_FAILED
PAYROLL_LIABILITY_SETTLEMENT_REVERSED
PAYROLL_LIABILITY_SETTLEMENT_CANCELLED
PAYROLL_LIABILITY_OUTSTANDING_VIEWED
```

Run:

```bash
php artisan logs:audit --json
```

Fix new missing/needs-review audit gaps.

---

# 20. Localisation

All new labels must be localised EN/FR.

Use or extend:

```text
lang/en/accounting.php
lang/fr/accounting.php
lang/en/payroll.php
lang/fr/payroll.php
```

Required labels:

```text
payroll_liability_settlement
payroll_liability_settlements
statutory_liability
statutory_liabilities
paye_liability
paye_settlement
paye_outstanding
pension_liability
pension_settlement
pension_outstanding
ssnit_liability
ssnit_settlement
ssnit_outstanding
other_deduction_liability
other_deduction_settlement
liability_payment
settlement_allocation
settlement_reference
approve_liability_settlement
post_liability_settlement
reverse_liability_settlement
settlement_reversal_reason
unpaid_statutory_liabilities
approved_not_posted_settlements
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

# 21. Navigation

Add navigation under Advanced Accounting / Payroll Accounting:

```text
Liability Settlements
PAYE Settlements
Pension / SSNIT Settlements
```

If sidebar assertions are locked, update tests later during final wide testing.

Route access must still work through direct URL and permissions.

---

# 22. Focused Tests To Add

Add focused tests but do not run the wide full suite.

Required coverage:

```text
draft settlement can be created
settlement approval requires permission
approved PAYE settlement posts balanced journal
approved pension settlement posts balanced journal
settlement cannot exceed outstanding liability
allocation cannot exceed payroll-run liability
duplicate settlement posting does not duplicate journal
posted settlement can be reversed with reason
reversed settlement restores outstanding liability
missing payment account mapping creates failed posting attempt
failed posting workbench can retry statutory settlement
PAYE reconciliation includes posted settlements
pension reconciliation includes posted settlements
close readiness reports unpaid statutory liabilities
unauthorized user cannot post settlement
module middleware blocks route when Advanced Accounting disabled
audit events are recorded
localisation keys exist
```

Do not run:

```bash
php artisan test
```

during this phase unless explicitly instructed.

The wide full-suite test will be run after all accounting implementation phases in this batch are complete.

---

# 23. Minimal Verification Commands

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

# 24. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_E2_STATUTORY_LIABILITY_SETTLEMENT_REPORT.md
```

Include:

```text
summary
database changes
models added
services added
permissions added
routes/controllers/views added
PAYE settlement strategy
pension / SSNIT settlement strategy
other deduction settlement strategy
allocation rules
overpayment prevention
reversal behavior
account mapping behavior
failed posting integration
subledger reconciliation integration
close readiness integration
audit logging
localisation audit result
minimal verification commands run
tests added but not fully executed
known limitations
next recommended phase
```

---

# 25. Acceptance Criteria

Phase E2 is complete only when:

```text
PAYE settlement records exist
pension / SSNIT settlement records exist
other deduction settlements are supported where source data exists
settlements can be approved and posted
settlement journals are balanced
settlements cannot exceed outstanding liability
allocations cannot exceed source liability
settlement posting is idempotent
posted settlement can be reversed with reason
reversal restores outstanding liability
failed posting workbench can retry settlement posting
PAYE reconciliation includes settlement records
pension reconciliation includes settlement records
close readiness reports unpaid/failed statutory liabilities
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

Proceed with Accounting Execution Phase E2 now.
