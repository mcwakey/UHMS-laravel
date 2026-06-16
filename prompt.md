You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Execution Phase G — Budgets, Commitments & Encumbrances

## Goal

Implement budgeting, commitments, encumbrances, and budget-vs-actual reporting for UHMS Advanced Accounting.

This phase must build on the accounting foundation already completed:

```text
Phase 0 — Shared posting controls, idempotency, mappings, close readiness
Phase A — Basic-to-Advanced posting bridge
Phase B — Bank accounts and bank reconciliation
Phase C — Failed posting workbench
Phase D — Subledger reconciliation workbench
Phase E — Payroll accounting posting
Phase E2 — PAYE and Pension / SSNIT statutory settlement
Phase F — Cash Flow Statement and accounting exports
```

Do not create a parallel accounting system.

Do not weaken existing procurement, stock, supplier payable, GL, audit, localisation, permission, or module middleware behavior.

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
docs/ACCOUNTING_PHASE_F_CASH_FLOW_AND_EXPORTS_REPORT.md
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Current accounting status:

```text
Basic-to-Advanced posting bridge exists.
Bank reconciliation exists.
Failed posting workbench exists.
Subledger reconciliation exists.
Payroll accounting posting exists.
PAYE/Pension settlement exists.
Cash flow statement and accounting CSV exports exist.
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
budget years
budget periods
budget headers
budget lines
department/account budgets
budget submission
budget approval
budget revisions
budget transfers
budget availability calculation
commitment creation
encumbrance tracking
commitment release
budget-vs-actual report
budget-vs-actual export if existing export service supports it
procurement integration where safe
supplier payable integration where safe
close-readiness visibility
permissions
audit logging
localisation
documentation
```

Do not implement yet:

```text
fixed assets
statutory tax returns
receivables collector workbench
claims settlement accounting
multi-currency budget revaluation
donor fund accounting
grant accounting
project accounting
branch consolidation
electronic bank payment files
```

---

# 3. Core Rules

Budgeting must be:

```text
approval-gated
versioned
auditable
permission-aware
module-aware
non-destructive
reconcilable to actual GL postings
safe for hospital operations
```

Important rules:

```text
Approved budget versions are immutable.
Budget changes use revisions or transfers.
Commitments are separate from actuals.
Actuals come from posted GL journals.
Commitments reduce available budget but do not create GL journals.
Encumbrances are operational controls, not accounting journals.
Clinical care must not be blocked by budget rules.
Emergency care must not be blocked by budget rules.
Budget enforcement should default to warning mode.
Hard blocking should apply only to configured procurement workflows.
```

---

# 4. Module Rules

Add a dedicated optional module if the module catalogue supports it safely:

```text
budgets
```

Recommended dependency:

```text
budgets depends on accounting_advanced
```

If adding the module toggle is risky in this phase, keep budgets under `accounting_advanced` and document the module toggle as deferred.

Routes must use:

```text
auth
module:accounting_basic
module:accounting_advanced
permission middleware
```

If `budgets` module is added, budget routes must also use:

```text
module:budgets
```

Do not make Billing & Collections dependent on budgets.

Do not make clinical workflows dependent on budgets.

---

# 5. Database Tables

Create additive tables:

```text
budgets
budget_periods
budget_lines
budget_revisions
budget_revision_lines
budget_transfers
budget_commitments
budget_commitment_movements
budget_approval_limits
```

Use string statuses with application validation.

Do not use database enums.

Use `DECIMAL(18,2)` for money.

Use `LONGTEXT` for snapshots where needed.

Use explicit short MariaDB-safe index names.

Do not cascade-delete financial history.

---

# 6. budgets

Fields:

```text
id
budget_code
name
fiscal_year_id nullable
period_start
period_end
currency
department_id nullable
branch_id nullable
status
approved_by nullable
approved_at nullable
closed_by nullable
closed_at nullable
cancelled_by nullable
cancelled_at nullable
cancellation_reason nullable
notes
created_by
updated_by
timestamps
```

Statuses:

```text
draft
submitted
approved
active
closed
cancelled
superseded
```

Rules:

```text
draft budgets can be edited
submitted budgets require review
approved budgets become immutable
active budgets drive availability checks
closed budgets cannot receive new commitments
cancelled budgets cannot be used
```

---

# 7. budget_periods

Fields:

```text
id
budget_id
name
period_start
period_end
status
created_by
updated_by
timestamps
```

Purpose:

```text
Allow monthly, quarterly, annual, or custom budget periods.
```

---

# 8. budget_lines

Fields:

```text
id
budget_id
budget_period_id nullable
department_id nullable
branch_id nullable
gl_account_id
budget_amount
revised_amount
committed_amount
actual_amount
available_amount
enforcement_mode
status
notes
created_by
updated_by
timestamps
```

Enforcement modes:

```text
none
warning
blocking
approval_override
```

Rules:

```text
budget_amount is the approved base amount
revised_amount changes only through approved revisions/transfers
committed_amount comes from open commitments
actual_amount comes from posted GL journals
available_amount is calculated, not trusted blindly
```

Do not rely only on stored available_amount. Recalculate when needed.

---

# 9. budget_revisions and budget_revision_lines

Purpose:

```text
Increase or decrease approved budget amounts with audit trail.
```

budget_revisions fields:

```text
id
budget_id
revision_number
reason
status
submitted_by
submitted_at
approved_by
approved_at
rejected_by
rejected_at
rejection_reason
created_by
updated_by
timestamps
```

budget_revision_lines fields:

```text
id
budget_revision_id
budget_line_id
old_amount
change_amount
new_amount
reason
timestamps
```

Statuses:

```text
draft
submitted
approved
rejected
cancelled
```

Rules:

```text
approved revisions update revised budget
rejected revisions change nothing
budget history remains visible
```

---

# 10. budget_transfers

Purpose:

```text
Move budget amount from one approved budget line to another.
```

Fields:

```text
id
budget_id
transfer_number
from_budget_line_id
to_budget_line_id
amount
reason
status
submitted_by
submitted_at
approved_by
approved_at
rejected_by
rejected_at
rejection_reason
created_by
updated_by
timestamps
```

Rules:

```text
source budget line must have enough available amount unless elevated override is used
approved transfer reduces source revised amount and increases target revised amount
transfer must be balanced
```

---

# 11. budget_commitments

Purpose:

```text
Reserve budget for purchase orders, procurement requests, department requests, or other controlled spending before actual supplier payable/payment occurs.
```

Fields:

```text
id
commitment_number
budget_id
budget_line_id
source_type
source_id
source_reference
department_id nullable
branch_id nullable
gl_account_id
amount
open_amount
released_amount
actualized_amount
status
committed_by
committed_at
released_by nullable
released_at nullable
release_reason nullable
metadata_snapshot
timestamps
```

Statuses:

```text
draft
committed
partially_released
released
actualized
cancelled
reversed
```

Rules:

```text
committed amount reduces available budget
actualized amount becomes actual when supplier payable/payment posts to GL
released amount restores available budget
commitment does not create journal
commitment must remain linked to source record
```

---

# 12. budget_commitment_movements

Purpose:

```text
Append-only movement history for commitments.
```

Fields:

```text
id
budget_commitment_id
movement_type
amount
old_open_amount
new_open_amount
source_type nullable
source_id nullable
reason
actor_id
metadata_snapshot
created_at
updated_at
```

Movement types:

```text
created
increased
decreased
released
actualized
cancelled
reversed
```

---

# 13. budget_approval_limits

Purpose:

```text
Define who can approve budgets, revisions, transfers, and overrides by amount and department.
```

Fields:

```text
id
approval_type
role_id nullable
user_id nullable
department_id nullable
branch_id nullable
min_amount
max_amount
is_active
effective_from
effective_to
notes
created_by
updated_by
timestamps
```

Approval types:

```text
budget
revision
transfer
commitment_override
```

---

# 14. Budget Availability Formula

Budget availability should be calculated as:

```text
approved budget
+ approved revisions
+ approved incoming transfers
- approved outgoing transfers
- open commitments
- actual posted GL expenditure
= available budget
```

For revenue budgets later:

```text
actual posted GL income can be compared against budgeted income
```

Start with expense-control budgets first.

Do not block clinical workflows.

Do not calculate actuals from operational source records when posted GL data exists.

---

# 15. Services To Add

Create:

```text
BudgetService
BudgetApprovalService
BudgetRevisionService
BudgetTransferService
BudgetAvailabilityService
CommitmentService
BudgetActualsService
BudgetReportService
```

Controllers must call services.

Do not put budget calculations in controllers or Blade.

---

# 16. BudgetAvailabilityService

This service should:

```text
resolve applicable budget line
calculate approved/revised budget
calculate open commitments
calculate actual posted GL expense
calculate available amount
detect over-budget state
apply enforcement mode
return warning/block/override decision
```

Inputs:

```text
gl_account_id
department_id
branch_id
amount
date
source_type
source_id
```

Output:

```text
budget line
approved amount
revised amount
committed amount
actual amount
available amount
requested amount
decision
message
override_required
```

---

# 17. CommitmentService

This service should:

```text
create commitment
increase commitment
decrease commitment
release commitment
actualize commitment
cancel commitment
reverse commitment
append movement history
update commitment totals
audit each movement
```

Commitments must be idempotent by:

```text
source_type + source_id + gl_account_id + budget_line_id
```

Do not duplicate commitments for the same source line.

---

# 18. Procurement Integration

Inspect existing procurement/purchase-order/supplier payable workflow.

Where safe, integrate budget checks at configured points:

```text
purchase request approval
purchase order approval
supplier payable creation
goods received note approval
```

Recommended first integration point:

```text
purchase order approval or supplier payable creation
```

depending on the existing UHMS workflow.

Rules:

```text
if budgets disabled: no effect
if no budget line found: warn, do not block by default
if enforcement_mode = warning: allow with warning and audit
if enforcement_mode = blocking: block unless override permission exists
if enforcement_mode = approval_override: require authorized override
emergency/clinical direct care must not be blocked
```

Do not rewrite procurement logic.

Add budget hooks through services.

---

# 19. Actuals Integration

Actuals should come from posted GL journal lines.

BudgetActualsService should:

```text
query posted journal lines
filter by expense accounts
filter by date/period
filter by department/branch where dimension exists
exclude reversed journals
include reversal effects correctly
group by budget line/account/department
```

Do not use unposted source records as actuals.

---

# 20. Budget Reports

Implement:

```text
budget summary
budget line detail
budget vs actual
commitment register
over-budget report
budget revision history
budget transfer history
```

Budget-vs-actual columns:

```text
budget amount
revisions
transfers in
transfers out
revised budget
open commitments
actuals
available
variance amount
variance percentage
status
```

Exports:

```text
CSV
print
PDF if existing export tooling supports it
```

Use existing `AccountingExportService` where possible.

Do not introduce a new export library.

---

# 21. UI Screens

Add Advanced Accounting / Budgeting screens:

```text
Budget dashboard
Budget index
Budget create/edit
Budget line editor
Budget submit
Budget approval
Budget detail
Budget revisions
Budget transfers
Budget commitments
Budget availability check
Budget-vs-actual report
Commitment register
Approval limits
```

Use Bootstrap 5 and Tabler Icons only.

Do not introduce new frontend frameworks.

---

# 22. Permissions

Add:

```text
accounting.budgets.view
accounting.budgets.manage
accounting.budgets.submit
accounting.budgets.approve
accounting.budgets.revise
accounting.budgets.transfer
accounting.budgets.close
accounting.budgets.cancel
accounting.commitments.view
accounting.commitments.manage
accounting.commitments.release
accounting.commitments.override
accounting.budget_reports.view
accounting.budget_reports.export
accounting.budget_approval_limits.view
accounting.budget_approval_limits.manage
```

Suggested role defaults:

```text
Accountant:
- view budgets
- manage draft budgets
- submit budgets
- view commitments
- view budget reports

Finance Manager:
- approve budgets
- revise/transfer
- close/cancel
- release/override commitments
- manage approval limits
- export reports

Department Head:
- view assigned department budgets
- submit budget requests if role exists

Administrator / Super Admin:
- all
```

Do not grant to broad clinical roles.

---

# 23. Audit Logging

Use `ActivityLogService`.

Audit:

```text
BUDGET_CREATED
BUDGET_UPDATED
BUDGET_SUBMITTED
BUDGET_APPROVED
BUDGET_CLOSED
BUDGET_CANCELLED
BUDGET_REVISION_CREATED
BUDGET_REVISION_SUBMITTED
BUDGET_REVISION_APPROVED
BUDGET_REVISION_REJECTED
BUDGET_TRANSFER_CREATED
BUDGET_TRANSFER_APPROVED
BUDGET_TRANSFER_REJECTED
BUDGET_COMMITMENT_CREATED
BUDGET_COMMITMENT_RELEASED
BUDGET_COMMITMENT_ACTUALIZED
BUDGET_COMMITMENT_CANCELLED
BUDGET_OVERRIDE_USED
BUDGET_AVAILABILITY_CHECKED
BUDGET_REPORT_VIEWED
BUDGET_REPORT_EXPORTED
```

Run:

```bash
php artisan logs:audit --json
```

Fix new missing/needs-review audit gaps.

---

# 24. Close Readiness Integration

Update `AccountingCloseReadinessService` to show:

```text
open commitments for the period
over-budget lines
unapproved budget revisions
unapproved budget transfers
commitments not released after payable actualization
budget-vs-actual report not generated
```

Do not hard-block period close yet unless current close code safely supports it.

Document recommended future close-block behavior.

---

# 25. Localisation

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
budgets
budget
budgeting
budget_period
budget_line
budget_amount
revised_budget
budget_revision
budget_revisions
budget_transfer
budget_transfers
commitment
commitments
encumbrance
encumbrances
open_commitments
actual_amount
available_budget
budget_vs_actual
budget_variance
variance_percentage
over_budget
under_budget
within_budget
enforcement_mode
warning_mode
blocking_mode
approval_override
commitment_register
approval_limits
submit_budget
approve_budget
close_budget
cancel_budget
release_commitment
actualize_commitment
budget_override
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

# 26. Navigation

Add Advanced Accounting navigation:

```text
Budgets
Budget Reports
Commitments
```

If a dedicated `budgets` module is added, navigation must respect module state.

Route access must still work correctly through middleware and permissions.

---

# 27. Focused Tests To Add

Add focused tests but do not run the wide full suite.

Required coverage:

```text
budget can be created in draft
budget can be submitted
budget can be approved
approved budget cannot be edited directly
budget revision changes revised amount only after approval
budget transfer moves amount between lines after approval
availability formula includes budget, revisions, transfers, commitments and actuals
commitment reduces available budget
commitment release restores available budget
payable actualization releases commitment and increases actual
warning mode allows over-budget with audit
blocking mode blocks over-budget without override
override permission allows approved over-budget commitment
budget-vs-actual report uses posted GL actuals
unposted source records are not counted as actuals
permissions protect budget actions
module middleware protects budget routes
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

# 28. Minimal Verification Commands

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

# 29. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_G_BUDGETS_COMMITMENTS_REPORT.md
```

Include:

```text
summary
database changes
models added
services added
permissions added
routes/controllers/views added
module toggle decision
budget lifecycle
revision lifecycle
transfer lifecycle
commitment lifecycle
budget availability formula
procurement integration point
actuals calculation strategy
budget-vs-actual report
close readiness integration
audit logging
localisation audit result
minimal verification commands run
tests added but not fully executed
known limitations
next recommended phase
```

---

# 30. Acceptance Criteria

Phase G is complete only when:

```text
budgets can be created
budget lines can be configured
budgets can be submitted and approved
approved budgets are immutable
budget revisions are versioned and approval-gated
budget transfers are balanced and approval-gated
budget availability is calculated correctly
commitments can be created and released
commitments reduce available budget
actual posted GL expenses affect budget actuals
budget-vs-actual report exists
over-budget behavior respects enforcement mode
clinical/emergency workflows are not blocked
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

Proceed with Accounting Execution Phase G now.
