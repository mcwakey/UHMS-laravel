# Accounting Execution Phase E - Payroll Accounting Posting

## Summary

Phase E adds authoritative payroll accounting for approved Ghana payroll runs.
Finance can now post payroll accruals, recognise PAYE and pension liabilities, settle net salary
payable from cash/bank, reverse settlements, and reverse payroll accruals when no posted
settlements remain.

## Scope Delivered

- Approved payroll accrual posting into the general ledger.
- Net salary payable, PAYE payable, pension / SSNIT payable, and other deduction payable lines.
- Salary settlement posting from salary payable to cash/bank.
- Accounting posting attempts for payroll accruals and settlements.
- Reversal controls for settlements and payroll accruals.
- Advanced Accounting workbench for preview, post, settle, and reverse actions.
- Reconciliation services updated so payroll, PAYE, and pension use Phase E posted liabilities.

## Database Changes

Migration:
`2026_06_15_000007_create_accounting_phase_e_payroll_posting.php`

Adds payroll run accounting fields:

- `accounting_status`
- `journal_entry_id`
- `accounting_posted_at`
- `accounting_error`
- `reversal_journal_entry_id`
- `reversed_at`
- `reversed_by`
- `reversal_reason`
- `settlement_status`
- `settled_amount`

Adds `payroll_settlements` for auditable salary settlement records and linked journal entries.

## Posting Logic

Payroll accrual:

- Debit salary expense for earned gross pay after attendance deductions.
- Debit employer pension expense.
- Credit net salary payable.
- Credit PAYE payable.
- Credit pension / SSNIT payable for employee plus employer portions.
- Credit other payroll deductions payable.

Salary settlement:

- Debit salary payable.
- Credit the selected cash or bank account.

The implementation uses existing `JournalEntryService` controls, accounting period validation,
control-account locking rules, and `AccountingPostingAttemptService` idempotency/audit records.

## Routes And UI

Controller:
`App\Http\Controllers\Accounting\PayrollPostingController`

Routes under:
`admin/accounting/payroll-posting`

Actions:

- Workbench index
- Post payroll accrual
- Reverse payroll accrual
- Post salary settlement
- Reverse salary settlement

Sidebar:
`Advanced Accounting > Payroll Posting`

## Permissions

Added:

- `accounting.payroll_posting.view`
- `accounting.payroll_posting.post`
- `accounting.payroll_posting.settle`
- `accounting.payroll_posting.reverse`

Accountants receive view, post, and settle. Finance Managers additionally receive reverse.
Administrators and Super Admins receive all permissions through the existing full permission path.

## Reconciliation Updates

`PayrollReconciliationService` now uses posted payroll runs and posted salary settlements to
calculate outstanding net salary payable.

`TaxLiabilityReconciliationService` now uses posted payroll runs for PAYE and pension liability
reconciliation. PAYE and pension statutory payment records remain a later statutory settlement
phase; Phase E covers liability recognition.

## Tests Added

`tests/Feature/Accounting/PayrollAccountingPhaseETest.php`

Coverage:

- Approved payroll accrual posts balanced journal lines.
- PAYE, pension, net salary, and other deduction liabilities are recognised.
- Full salary settlement posts against salary payable and marks payroll paid.
- Posted settlements block payroll accrual reversal until reversed.
- Workbench route is permission and module protected.
- Payroll/PAYE/pension reconciliation domains use Phase E posted liabilities.

Focused result:

- `php artisan test tests/Feature/Accounting/PayrollAccountingPhaseETest.php`
- **5 passed, 31 assertions**

## Known Limitations

- PAYE and pension statutory remittance settlement records are delivered in Phase E2.
- Settlement approval is permission-gated but not yet a multi-step maker/checker workflow.
- Payroll expense is mapped at run level, not split by employee department yet.

## Next Recommended Phase

Proceed to maker/checker approval and filing evidence for PAYE and pension / SSNIT remittances,
then add department-level payroll expense analytics once departmental cost centres are finalised.
