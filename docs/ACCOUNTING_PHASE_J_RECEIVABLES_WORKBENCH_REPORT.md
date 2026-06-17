# Accounting Execution Phase J: Receivables Workbench

## Summary

Phase J adds a dedicated receivables operations workbench over the existing UHMS billing and `invoice_receivables` foundation. It does not create a parallel billing ledger or alternate AR balance table.

Finance users can now review AR aging, payer balances, overdue receivables, collection cases, collector assignments, follow-ups, payment promises, disputes, dunning notices, statements, and write-off / credit-note recommendations.

## Database Changes

Added:

- `receivable_cases`
- `receivable_case_items`
- `receivable_followups`
- `receivable_promises`
- `receivable_disputes`
- `receivable_assignments`
- `receivable_dunning_notices`
- `receivable_statement_runs`
- `receivable_statement_items`
- `receivable_writeoff_recommendations`
- `receivable_creditnote_recommendations`

The source of financial balance remains `invoice_receivables`.

## Models Added

Added Eloquent models for each new receivables operations table.

## Services Added

- `ReceivableWorkbenchService`
- `ReceivableCaseService`
- `ReceivableStatementService`
- `ReceivableRecommendationService`

Existing `ARAgingService`, `InvoiceReceivableService`, `ReceivablesReconciliationService`, and `AccountingCloseReadinessService` are reused or extended.

## Permissions Added

Added Phase J permissions for workbench access, case management, assignment, follow-ups, promises, disputes, dunning, statements, recommendations, reports and exports.

## Routes, Controllers, Views

Added:

- `ReceivableWorkbenchController`
- `resources/views/accounting/receivables/index.blade.php`
- Advanced Accounting route group: `admin.accounting.receivables.*`
- Sidebar link: Accounting > Receivables Workbench

Routes are protected by `module:accounting_advanced` and explicit `can:` middleware.

## Lifecycle And Controls

Cases can be opened from invoice receivables and linked through `receivable_case_items`. Only one active case is allowed for a receivable unless explicitly bypassed in service input.

Assignments close prior active assignment rows before creating a new active assignment.

Follow-ups and promises are operational records only. They do not reduce AR balance.

Disputes remain included in AR aging while being separately tracked.

Dunning notices are generated as printable records by default. No automatic sending is performed.

Statements are snapshots and do not post journals.

Write-off and credit-note recommendations do not affect balances and must be converted through existing approved financial workflows.

## Aging And Payer Balance Logic

AR aging continues to use existing open `invoice_receivables` balances after payments, credit notes and write-offs. Phase J adds payer-balance grouping and case-aware operational visibility.

## Reconciliation And Close Readiness

Receivables reconciliation now classifies receivable exceptions such as disputes, active or broken promises, recommendations, pending claims, sponsor balances, and corporate balances.

Close readiness now reports receivable exceptions including large overdue balances, unassigned overdue balances, unresolved disputes, unconverted approved recommendations, broken promises, active cases and thresholded claim/sponsor/corporate balances. It does not hard-block period close.

## Audit Logging

`ActivityLogService` is used for case opening, assignment, follow-ups, promises, disputes, dunning generation, statement generation/approval and recommendations.

## Localisation

Added EN/FR receivables language files with paired keys for new labels.

## Verification

Focused tests were added in `tests/Feature/Accounting/ReceivableWorkbenchPhaseJTest.php`.

Full application suite remains intentionally deferred until the final wide accounting test phase.

## Known Limitations

- Dunning is generated/printable only; sending is reserved for a safe notification-channel integration.
- Statement export/PDF generation is not added in this phase.
- Recommendation conversion links are ready in schema but conversion still relies on existing write-off and credit-note approval workflows.

## Next Recommended Phase

Proceed to Phase K: Claims Settlement Accounting.
