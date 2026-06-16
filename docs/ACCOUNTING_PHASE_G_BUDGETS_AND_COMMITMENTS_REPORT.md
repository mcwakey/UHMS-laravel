# Accounting Execution Phase G: Budgets and Commitments

## Scope

Phase G establishes the budget and encumbrance foundation for UHMS accounting.

## Delivered

- Added optional `budgets` module dependent on `accounting_advanced`.
- Added budget, budget line, period, revision, transfer, commitment, movement and approval-limit tables.
- Added budget dimensions for fiscal year, department, account, branch, project and grant placeholders.
- Added nullable purchase-order budget dimension and commitment linkage columns.
- Added budget and commitment models.
- Added services for:
  - Budget approval
  - Budget availability
  - Budget revisions and transfers
  - Budget commitments
  - Budget actual summaries
- Added budget and commitment accounting workbench pages.
- Added sidebar links under Advanced Accounting.
- Added route permissions for budget and commitment workflows.
- Added procurement hook:
  - Disabled `budgets` module creates no procurement dependency.
  - Enabled module with PO budget dimensions creates a commitment on approval.
  - Goods receipt releases commitment value.
  - PO cancellation releases remaining commitment.

## Rules Implemented

- Approved budget lines are immutable.
- Revisions and transfers are append-only adjustment records.
- Availability is calculated as:

```text
approved budget + approved revisions + approved transfers in - approved transfers out - posted actuals - open commitments
```

- Actuals are taken from posted GL journal lines for the fiscal year, department and account.
- Default budget enforcement is warning with acknowledgement.
- Blocking mode is available in the commitment service for procurement-facing enforcement.

## Verification

Covered by `tests/Feature/Accounting/BudgetCommitmentPhaseGTest.php`.
