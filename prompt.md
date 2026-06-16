You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Execution Phase F — Cash Flow Statement & Controlled Accounting Exports

## Goal

Implement the missing Cash Flow Statement and controlled accounting exports for UHMS Advanced Accounting.

This phase must use the accounting foundation already completed:

```text
Phase 0 — Shared posting controls, idempotency, account mappings, close readiness
Phase A — Basic-to-Advanced posting bridge
Phase B — Bank accounts, statement import, reconciliation and bank adjustments
Phase C — Failed posting workbench
Phase D — Subledger reconciliation workbench
Phase E — Payroll accrual and salary settlement posting
Phase E2 — PAYE and Pension / SSNIT statutory settlement posting
```

Do not create a parallel reporting engine.

Do not bypass existing journal, ledger, reconciliation, permission, audit, localisation, or export controls.

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
docs/ACCOUNTING_PHASE_E2_STATUTORY_PAYROLL_SETTLEMENT_REPORT.md
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
Phase E complete.
Phase E2 complete.
PAYE and Pension / SSNIT settlements are now posted through accounting.
Cash and bank movements are now richer and ready for cash-flow reporting.
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

Implement:

```text
cash flow statement
direct-method cash flow report
cash flow mapping configuration
cash/bank account selection
operating activities classification
investing activities classification
financing activities classification
unmapped cash movement detection
cash flow drill-down
opening cash balance
cash movement summary
closing cash balance
comparison period support where practical
controlled exports for accounting reports
PDF export
CSV export
Excel-compatible export where existing export tooling supports it
print view
export permission enforcement
export audit logging
close-readiness integration for unmapped cash-flow movements
documentation
```

Do not implement yet:

```text
budgets and commitments
fixed assets
full statutory tax returns
receivables collector workbench
claims settlement accounting
multi-currency exchange gains/losses
donor fund accounting
branch consolidation
electronic bank payment files
```

---

# 3. Core Rules

Cash flow reporting must be:

```text
ledger-based
period-based
drillable
auditable
permission-aware
module-aware
export-controlled
reconcilable to cash/bank GL balances
```

Rules:

```text
Cash flow must be derived from posted journal entries.
Draft, failed, reversed, or unposted source records must not be counted as final cash movement.
Opening balance plus cash movement must equal closing balance.
Closing balance must agree to selected cash/bank GL accounts.
Unmapped cash movements must be shown explicitly.
Do not hide unmapped movements.
Do not invent cash-flow classifications.
Do not use source modules directly when posted GL data is available.
Do not bypass permissions.
Do not bypass ActivityLogService.
```

---

# 4. Reporting Method

Implement the **direct method** first.

Direct method categories:

```text
operating activities
investing activities
financing activities
```

Examples:

```text
Operating:
- patient collections
- sponsor / insurance collections
- supplier payments for operating goods
- salary payments
- PAYE / pension remittances
- bank charges
- operating income and expenses

Investing:
- fixed asset purchases
- asset disposals
- long-term investment activity

Financing:
- loans received
- loan repayments
- owner/capital contributions
- dividends/distributions if ever supported
```

Do not implement indirect method yet unless the codebase already has all required non-cash adjustment data.

Document indirect method as future.

---

# 5. Database Tables

Create additive table if needed:

```text
cash_flow_mappings
```

Use existing accounting account mapping table if it cleanly supports this. Do not duplicate mapping systems unnecessarily.

## cash_flow_mappings

Fields:

```text
id
mapping_type
mapping_key
mapping_value
gl_account_id nullable
source_module nullable
source_type nullable
posting_type nullable
cash_flow_section
cash_flow_category
direction
priority
effective_from
effective_to
is_active
notes
created_by
updated_by
timestamps
```

Cash flow sections:

```text
operating
investing
financing
```

Direction:

```text
inflow
outflow
auto
```

Rules:

```text
Mappings must be effective-dated.
Highest priority mapping wins.
Conflicts must fail loudly.
Inactive mappings must not classify new reports.
Historical mappings should remain visible.
Do not hardcode account IDs.
```

If Phase 0 `accounting_account_mappings` is reused, define scopes such as:

```text
cash_flow_section
cash_flow_category
cash_flow_source_type
cash_flow_posting_type
```

---

# 6. CashFlowReportService

Create:

```text
CashFlowReportService
```

Responsibilities:

```text
resolve selected cash/bank GL accounts
calculate opening cash balance
load posted journal lines affecting cash/bank accounts
classify movements using mappings
separate operating/investing/financing sections
detect unmapped cash movements
calculate net cash movement
calculate closing cash balance
compare closing balance to GL balance
build drill-down rows
build export-ready report data
support comparison period where practical
```

Do not put report calculations in controllers or Blade.

---

# 7. Cash Flow Calculation

For selected period:

```text
opening_cash_balance = posted cash/bank GL balance before period_start

cash_movements = posted journal lines affecting selected cash/bank accounts during period

net_cash_movement = total inflows - total outflows

closing_cash_balance = opening_cash_balance + net_cash_movement

gl_closing_cash_balance = posted cash/bank GL balance at period_end

difference = closing_cash_balance - gl_closing_cash_balance
```

Acceptance:

```text
opening_cash_balance + net_cash_movement = closing_cash_balance
closing_cash_balance must match GL cash/bank balance
difference should be 0 unless clearly explained
```

Use decimal arithmetic according to existing money precision policy.

Do not rely on PHP floats as authoritative.

---

# 8. Classification Strategy

Classify each cash/bank journal line using:

```text
source module
source type
posting type
contra account
journal description
mapping priority
effective date
```

Recommended priority:

```text
1. explicit source/posting type mapping
2. explicit GL account mapping
3. contra account mapping
4. default section mapping
5. unmapped
```

If no mapping is found:

```text
classify as unmapped
show in unmapped section
include in total cash movement
prevent "complete" badge
add close-readiness warning
```

Do not silently exclude unmapped cash activity.

---

# 9. Cash Flow UI

Add screens under Advanced Accounting:

```text
Cash Flow Statement
Cash Flow Mapping Settings
Cash Flow Drill-down
Cash Flow Export Preview
```

Report filters:

```text
date range
fiscal period
cash/bank account
branch/facility where supported
department where reliably populated
comparison period optional
include/exclude unmapped
```

Report sections:

```text
Opening cash balance
Operating activities
Investing activities
Financing activities
Net increase/decrease in cash
Closing cash balance
GL closing cash balance
Difference
Unmapped cash movements
```

Each line should be drillable to:

```text
journal entry
journal line
source record where linked
posting attempt where linked
bank reconciliation where linked
```

---

# 10. Controlled Accounting Exports

Implement controlled exports for accounting reports.

Start with cash flow and extend common export service where safe.

Supported formats:

```text
PDF
CSV
print
Excel-compatible CSV or XLSX only if existing library/tooling is already present
```

Do not introduce a new export library unless the project already uses one.

All exports must require:

```text
accounting.exports
```

and report-specific permission.

Exported reports should include:

```text
facility/system name
report name
period
filters
generated by
generated at
currency
totals
page number for PDF/print where practical
```

---

# 11. AccountingExportService

Create or extend:

```text
AccountingExportService
```

Responsibilities:

```text
authorize export
record export audit
prepare export metadata
render PDF/print views
generate CSV
reuse existing report layouts where practical
ensure exported totals match screen totals
```

Do not duplicate report calculation in the export layer.

The export layer must receive already-calculated report data from `CashFlowReportService`.

---

# 12. Permissions

Use existing permission where already present:

```text
accounting.reports.cash_flow
accounting.exports
```

Add only if missing:

```text
accounting.cash_flow.view
accounting.cash_flow.manage_mappings
accounting.cash_flow.export
```

Avoid duplicate effective permissions if `accounting.reports.cash_flow` and `accounting.exports` are already enough.

Suggested defaults:

```text
Accountant:
- view cash flow
- export if accounting.exports already granted

Finance Manager:
- view cash flow
- manage mappings
- export

Administrator / Super Admin:
- all
```

Do not grant to broad clinical roles.

---

# 13. Audit Logging

Use `ActivityLogService`.

Audit:

```text
CASH_FLOW_REPORT_VIEWED
CASH_FLOW_REPORT_EXPORTED
CASH_FLOW_MAPPING_CREATED
CASH_FLOW_MAPPING_UPDATED
CASH_FLOW_MAPPING_DISABLED
CASH_FLOW_UNMAPPED_MOVEMENTS_REVIEWED
```

If export service records generic accounting export events, use those and add report-specific context.

Run:

```bash
php artisan logs:audit --json
```

Fix new missing/needs-review audit gaps.

---

# 14. Close Readiness Integration

Update `AccountingCloseReadinessService` to include:

```text
cash flow report not generated for period
unmapped cash flow movements
cash flow closing difference
cash/bank GL mismatch
cash flow mappings missing
```

Do not hard-block period close in this phase unless existing close code safely supports it.

Document recommended future close-block behavior:

```text
period close should warn/block if cash flow has unmapped material cash movements or unexplained closing difference
```

---

# 15. Localisation

All new labels must be localised EN/FR.

Use or extend:

```text
lang/en/accounting.php
lang/fr/accounting.php
lang/en/reports.php
lang/fr/reports.php
```

Required labels:

```text
cash_flow
cash_flow_statement
cash_flow_mappings
cash_flow_mapping
operating_activities
investing_activities
financing_activities
cash_inflows
cash_outflows
net_cash_flow
opening_cash_balance
closing_cash_balance
gl_closing_cash_balance
net_increase_decrease_cash
unmapped_cash_movements
cash_flow_difference
complete_cash_flow
incomplete_cash_flow
export_cash_flow
print_cash_flow
cash_flow_report_viewed
cash_flow_report_exported
source_posting_type
contra_account
classification_priority
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

# 16. Navigation

Add Advanced Accounting navigation:

```text
Cash Flow Statement
Cash Flow Mappings
```

If sidebar tests are locked, update them during final wide test phase.

Route access must still work via direct URL and permissions.

---

# 17. Focused Tests To Add

Add focused tests but do not run the wide full suite.

Required coverage:

```text
cash flow route is permission protected
module middleware blocks cash flow route when Advanced Accounting disabled
cash flow calculates opening balance
cash flow classifies operating cash movement
cash flow classifies investing cash movement
cash flow classifies financing cash movement
unmapped cash movement is shown and not hidden
opening plus movement equals closing balance
closing balance agrees to GL cash/bank balance
cash flow drill-down links to journal
cash flow export requires accounting.exports
CSV export totals match screen totals
PDF/print view renders
cash flow mapping conflict fails clearly
close readiness reports unmapped cash movements
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

# 18. Minimal Verification Commands

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

# 19. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_F_CASH_FLOW_AND_EXPORTS_REPORT.md
```

Include:

```text
summary
database changes
models added/changed
services added
permissions added
routes/controllers/views added
cash flow method
cash flow formula
classification strategy
mapping strategy
unmapped movement handling
drill-down behavior
export behavior
export permissions
close readiness integration
audit logging
localisation audit result
minimal verification commands run
tests added but not fully executed
known limitations
next recommended phase
```

---

# 20. Acceptance Criteria

Phase F is complete only when:

```text
cash flow statement route exists
cash flow statement calculates opening balance
cash flow statement calculates operating/investing/financing movements
cash flow statement calculates closing cash balance
closing balance agrees to GL cash/bank balance
unmapped cash movements are visible
classification mappings are configurable
cash flow drill-down links to journals/source records where available
cash flow export is permission controlled
PDF/CSV/print exports work using existing tooling
export totals match screen totals
close readiness reports unmapped cash-flow issues
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

Proceed with Accounting Execution Phase F now.
