# Accounting Execution Phase E2 - Statutory Payroll Settlement

## Summary

Phase E2 completes the payroll accounting foundation by adding statutory liability settlement for
PAYE and Pension / SSNIT. Phase E recognised the liabilities; Phase E2 now lets finance remit
those liabilities from cash/bank, reverse remittances, and reconcile the remaining statutory
balances.

## Scope Delivered

- PAYE remittance settlement from PAYE payable to cash/bank.
- Pension / SSNIT remittance settlement from pension payable to cash/bank.
- Posting attempts for statutory settlements.
- Reversal controls for statutory settlements.
- Payroll accrual reversal now blocks while posted PAYE or pension settlements exist.
- Payroll Posting workbench now displays statutory liability, settled amount, outstanding amount,
  settlement forms, settlement journals, and reversal forms.
- PAYE and pension reconciliation now subtract posted statutory settlements.

## Database Changes

Migration:
`2026_06_15_000008_create_accounting_phase_e2_statutory_payroll_settlements.php`

Table:
`payroll_statutory_settlements`

Key fields:

- `payroll_run_id`
- `settlement_number`
- `liability_type` (`paye`, `pension`)
- `settlement_date`
- `amount`
- `payment_account_id`
- `status`
- `accounting_status`
- `journal_entry_id`
- `reversal_journal_entry_id`
- reversal and audit actor fields

## Posting Logic

PAYE remittance:

- Debit PAYE payable.
- Credit selected cash/bank account.

Pension / SSNIT remittance:

- Debit pension / SSNIT payable.
- Credit selected cash/bank account.

Both paths use `JournalEntryService`, accounting-period validation, control-account posting
permissions for automated sources, idempotency keys, and `AccountingPostingAttemptService`.

## Routes And UI

Existing workbench:
`admin/accounting/payroll-posting`

New actions:

- `POST {payrollRun}/statutory-settle`
- `POST statutory-settlements/{settlement}/reverse`

The workbench remains under:
`Advanced Accounting > Payroll Posting`

## Permissions

Phase E2 reuses the Phase E payroll accounting permissions:

- `accounting.payroll_posting.view`
- `accounting.payroll_posting.settle`
- `accounting.payroll_posting.reverse`

Accountants can settle statutory liabilities. Finance Managers can reverse posted remittances.

## Reconciliation Updates

`TaxLiabilityReconciliationService` now calculates open PAYE and pension balances as:

```text
posted payroll liability - posted statutory settlements
```

The result is compared against the mapped PAYE and pension GL control accounts as of the
reconciliation date.

## Tests Updated

`tests/Feature/Accounting/PayrollAccountingPhaseETest.php`

Added coverage:

- PAYE and pension statutory settlements post balanced journals.
- PAYE settlement debits PAYE payable and credits bank.
- Pension / SSNIT settlement debits pension payable and credits bank.
- Posted statutory settlements reduce reconciliation balances.
- Payroll accrual reversal is blocked until statutory settlements are reversed.

## Known Limitations

- Statutory remittance approval is permission-gated but not yet a maker/checker approval queue.
- Statutory settlement evidence attachments and formal GRA/SSNIT filing references are plain notes
  for now.
- Liability settlement is still payroll-run based, not cross-period batch remittance.

## Next Recommended Phase

Add maker/checker approval queues and filing evidence for PAYE and SSNIT remittances, then add
department-level payroll cost analytics.
