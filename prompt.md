You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Gap Execution — Master Planning Phase

## Goal

Create a complete execution plan for the accounting gaps identified in:

```text id="0g0v52"
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
```

Do not implement code yet.

This phase is for planning the execution thoroughly so no accounting, billing, payroll, bank, tax, reconciliation, reporting, permission, module-toggle, audit, or migration detail is missed.

---

# 1. Required Context

Read:

```text id="q1y2df"
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/PHASE_17_FULL_TEST_SUITE_REGRESSION_STABILISATION_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Respect the current finance module split:

```text id="371lsh"
Billing & Collections
Basic Accounting
Advanced Accounting
```

Current module rules:

```text id="w8lh9c"
Billing & Collections remains independent from accounting module toggles.
Basic Accounting module slug: accounting_basic.
Advanced Accounting module slug: accounting_advanced.
Advanced Accounting depends on Basic Accounting.
Direct routes must be protected with module middleware, not just hidden from sidebar.
Existing permissions remain the source of action-level authorization.
```

Do not weaken existing accounting, billing, stock, payroll, or audit logic.

---

# 2. Planning Deliverable

Create:

```text id="niz7z5"
docs/ACCOUNTING_GAP_EXECUTION_MASTER_PLAN.md
```

The document must be implementation-ready and must include:

```text id="j0sxnz"
executive summary
current implemented accounting coverage
gap-by-gap execution plan
recommended delivery phases
database changes per phase
services per phase
controllers/views per phase
permissions per phase
module-toggle impact
journal posting strategy
reconciliation strategy
audit logging strategy
migration/backfill strategy
testing strategy
risk register
open decisions
acceptance criteria
```

---

# 3. High-Priority Gaps To Plan

Plan execution for these high-priority gaps from the report:

```text id="dau31g"
1. Basic-to-Advanced posting bridge
2. Bank accounts and bank reconciliation
3. Cash flow statement
4. Failed posting workbench
5. Payroll accounting posting
6. Budgeting and commitments
7. Fixed assets
8. Statutory tax accounting
9. Dedicated receivables workbench
10. Claims settlement accounting
```

Do not skip any.

---

# 4. Recommended Delivery Order

Use this delivery order unless you discover a blocking dependency:

```text id="iwzf9s"
1. Basic-to-Advanced posting bridge
2. Bank accounts, statement import, and formal bank reconciliation
3. Failed-posting and subledger reconciliation workbenches
4. Approved payroll posting and salary-payment settlement
5. Cash flow reporting and accounting exports
6. Budgets and commitments
7. Fixed assets and depreciation
8. Statutory tax ledgers and returns
9. Dedicated receivables workbench
10. Claims settlement accounting
```

Explain why this order is safest.

---

# 5. Phase A — Basic-to-Advanced Posting Bridge

This is the most urgent accounting gap.

Current problem:

```text id="o0r907"
Manual income and expense entries remain in the financial_entries operational ledger.
They do not create balanced journal entries in the advanced general ledger.
When Basic and Advanced Accounting are both enabled, this can create two financial views requiring manual reconciliation.
```

Plan a bridge where approved Basic Accounting entries can generate balanced journal entries.

Plan:

```text id="cjr4y0"
posting templates
income templates
expense templates
cash/bank account mapping
category-to-COA mapping
posting status
failed posting state
reversal handling
reposting rules
module toggle behavior
permission checks
audit logs
```

Important rules:

```text id="qnvuf7"
Do not auto-post unapproved Basic Accounting entries.
Do not duplicate journals.
Do not silently change historical financial_entries.
Do not post if Advanced Accounting is disabled.
If Advanced Accounting is later enabled, plan a controlled backfill/reconciliation workflow.
```

---

# 6. Phase B — Bank Accounts and Bank Reconciliation

Plan a formal bank reconciliation module.

Must include:

```text id="h0v5fy"
bank account register
bank statement import
statement lines
internal cashbook/bank ledger matching
manual match
auto-match suggestions
outstanding cheques
outstanding deposits
bank charges
interest income
reconciliation period
reconciliation statement
approval workflow
reopening/reversal rules
```

Plan database tables such as:

```text id="eo744h"
bank_accounts
bank_statement_imports
bank_statement_lines
bank_reconciliations
bank_reconciliation_matches
bank_reconciliation_adjustments
```

Plan integration with:

```text id="n3g268"
Basic Accounting cash records
Advanced Accounting cashbook
payment collections
supplier payments
payroll payments
bank charges
electronic payment references
```

---

# 7. Phase C — Failed Posting Workbench

Plan a dedicated workbench for failed accounting postings.

The report says permissions and dashboard counts exist, but there is no searchable workbench.

Plan:

```text id="df22jt"
failed posting list
source module
source record
posting type
error message
retry count
last attempted at
resolved status
manual resolution
retry action
ignore/waive action with permission
audit trail
```

Supported source modules:

```text id="cnxhzz"
billing
payments
credit notes
sponsors
supplier payables
inventory
stock adjustments
clinical consumables
payroll
manual income/expense
bank reconciliation
claims settlement
```

Do not lose failed posting errors.

Do not hide posting failures.

---

# 8. Phase D — Subledger Reconciliation Workbench

Plan reconciliation between GL and subledgers.

Include:

```text id="ki21t3"
AR vs receivable control account
AP vs supplier payable control account
inventory valuation vs inventory control account
payroll payable vs payroll subledger
cash/bank vs cashbook/bank accounts
PAYE payable vs payroll tax calculations
pension payable vs payroll pension calculations
```

Plan dashboard cards:

```text id="4juhqn"
balanced
difference detected
unposted source records
failed postings
manual adjustments
last reconciliation date
```

---

# 9. Phase E — Payroll Accounting Posting

Plan payroll accounting posting but do not assume payroll automation is fully built yet.

The report says payroll journal preparation exists, but approved payroll is not automatically posted.

Plan:

```text id="6kvptg"
approved payroll posting
salary expense
allowance expense
employer pension expense
payroll payable
PAYE payable
pension payable
loan receivable
salary payment settlement
payroll reversal/adjustment
```

Rules:

```text id="ct2pyb"
Only approved payroll can post.
Do not auto-post draft payroll.
Do not post twice.
Do not bypass accounting services.
Do not create NHIS or insurance-specific payroll logic.
```

---

# 10. Phase F — Cash Flow Statement and Accounting Exports

Plan implementation for the missing cash flow report.

Include:

```text id="c4a0ub"
operating activities
investing activities
financing activities
direct method
indirect method if feasible
cash/bank account mapping
opening cash balance
closing cash balance
period filters
department/branch filters if supported
export permissions
PDF/Excel/CSV/print
```

The report says permission exists:

```text id="qkj11p"
accounting.reports.cash_flow
```

but route/controller/service/screen are missing.

Plan all missing pieces.

---

# 11. Phase G — Budgets and Commitments

Plan:

```text id="tiq2mb"
annual budgets
department budgets
account budgets
budget periods
budget approval
budget revisions
budget transfers
budget vs actual report
purchase commitments
encumbrances
commitment release
approval limits
```

Integrate with:

```text id="wl9frx"
purchase orders
stock procurement
supplier payables
department requests
projects/grants later
```

Do not block clinical operations because a budget module is disabled unless configured.

---

# 12. Phase H — Fixed Assets

Plan fixed asset accounting.

Include:

```text id="9nwcbr"
asset register
asset categories
capitalization workflow
asset acquisition from procurement
asset locations
custodian assignment
depreciation methods
depreciation runs
disposal
impairment
asset transfer
asset verification
asset maintenance link later
```

Accounting:

```text id="04iq0z"
Dr Fixed Asset
Cr Cash/Bank/AP
Dr Depreciation Expense
Cr Accumulated Depreciation
Dr Loss/Gain on Disposal
```

---

# 13. Phase I — Statutory Tax Accounting

Plan statutory tax accounting.

Include:

```text id="3bhlip"
PAYE payable ledger
SSNIT/pension payable ledger
VAT/NHIL/GETFund if applicable
withholding tax
tax input ledger
tax output ledger
statutory returns
tax payment settlement
tax reconciliation
```

Do not mix tax calculation with tax accounting.

Tax calculation belongs to source modules such as payroll or billing.
Tax accounting records payable/receivable and settlement.

---

# 14. Phase J — Dedicated Receivables Workbench

Plan AR collector workbench.

Include:

```text id="ox57mb"
payer statements
patient receivables
sponsor receivables
insurance receivables
corporate receivables
promises to pay
collection notes
disputes
write-off queue
credit note queue
aging buckets
collector assignment
follow-up reminders
remittance matching
```

Do not replace existing AR aging.
Extend it into an operational collector workbench.

---

# 15. Phase K — Claims Settlement Accounting

Plan claims settlement accounting.

Include:

```text id="9e0dwg"
claim submission
insurer remittance advice
partial settlement allocation
denial accounting
write-down accounting
resubmission differences
claim reconciliation
insurer statement reconciliation
claim receivable control account
```

Rules:

```text id="d0trg1"
NHIS is just another insurance provider.
Do not hardcode NHIS.
Support generic insurance providers and sponsors.
```

---

# 16. Medium-Priority Gap Planning

Also plan later roadmap items for:

```text id="igephf"
multi-currency
cost centers
projects
grants
donor funds
recurring journals
accrual schedules
prepayments
deferred revenue
staff loan accounting
opening balance import
branch consolidation
electronic payment files
GL/subledger reconciliation dashboard
```

Mark these as later phases unless dependencies require earlier work.

---

# 17. Module Catalogue Planning

The report identifies existing workflows without dedicated module flags.

Plan whether to add module toggles for:

```text id="y2f3dv"
appointments
theatre
procedures
accounting integrations
cashier operations
fixed assets
budgets
bank reconciliation
radiology
CSSD
maintenance
advanced rostering
```

Rules:

```text id="k4w9wo"
Do not split modules unnecessarily if it makes deployment harder.
Do not make Billing dependent on Accounting.
Advanced Accounting must still depend on Basic Accounting.
Use module middleware for direct routes.
```

---

# 18. Permissions Planning

For each accounting gap, define permissions.

Examples:

```text id="gvah12"
accounting.basic.post_to_gl
accounting.bank_accounts.view
accounting.bank_accounts.manage
accounting.bank_reconciliation.view
accounting.bank_reconciliation.manage
accounting.bank_reconciliation.approve
accounting.failed_postings.view
accounting.failed_postings.retry
accounting.failed_postings.resolve
accounting.subledger_reconciliation.view
accounting.payroll_posting.view
accounting.payroll_posting.post
accounting.cash_flow.view
accounting.exports
accounting.budgets.view
accounting.budgets.manage
accounting.budgets.approve
accounting.commitments.view
accounting.commitments.manage
accounting.fixed_assets.view
accounting.fixed_assets.manage
accounting.fixed_assets.depreciate
accounting.tax_ledgers.view
accounting.tax_ledgers.manage
accounting.receivables_workbench.view
accounting.claims_settlement.view
accounting.claims_settlement.manage
```

Map permissions to roles.

---

# 19. Audit Logging Planning

Use `ActivityLogService`.

Plan audit events for:

```text id="kd2idd"
posting bridge template created
basic entry posted to GL
posting failed
posting retried
posting resolved
bank account created
bank statement imported
bank line matched
bank reconciliation approved
budget approved
commitment created
asset capitalized
depreciation run posted
tax return prepared
tax payment recorded
payroll posted
receivable dispute logged
claim remittance allocated
```

Do not bypass audit logging.

---

# 20. Data Migration and Backfill Planning

Plan safe migration/backfill strategies.

For each phase, define:

```text id="f9l147"
new tables
new nullable columns
indexes
backfill command
dry-run mode
rollback/reversal strategy
audit trail
large database safety
MariaDB compatibility
```

Important:

```text id="sbdr9i"
Do not destructively migrate existing financial data.
Do not auto-post historical records without user approval.
Historical backfill must be previewed and approved.
```

---

# 21. Test Strategy

Plan tests for:

```text id="6ztgv4"
module middleware
permissions
posting bridge
journal balance
duplicate posting prevention
failed posting retry
bank statement import
bank reconciliation matching
cash flow report
payroll posting
budget approval
commitment release
asset depreciation
tax ledger settlement
AR collector workbench
claims remittance allocation
localisation lock
audit logging
```

Always run:

```bash id="j8vq2m"
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan test
```

Active runtime candidates must remain:

```text id="ptcxzg"
0
```

---

# 22. Risk Register

Create a risk register covering:

```text id="4kxeb7"
duplicate journal posting
unbalanced journals
historical data mismatch
Basic vs Advanced Accounting divergence
wrong bank reconciliation matches
incorrect payroll liabilities
tax payable mismatch
claims settlement under/over allocation
budget blocking clinical operations
fixed asset depreciation errors
permission exposure
audit gaps
performance on large ledgers
migration rollback risk
```

For each risk, define mitigation.

---

# 23. Output Format

The master plan must include:

```text id="vfotrp"
phase roadmap table
gap-to-phase mapping
database table proposal
service proposal
permission matrix
audit event matrix
test matrix
risk register
open decisions
acceptance criteria
```

Use Mermaid diagrams for:

```text id="tlg1zb"
Basic-to-Advanced posting bridge
Bank reconciliation flow
Failed posting retry flow
Payroll posting flow
Budget commitment flow
Claims settlement allocation flow
```

---

# 24. Open Decisions

Document open decisions such as:

```text id="r8hzqh"
Should Basic entries auto-post to GL after approval or require manual batch posting?
Should old Basic entries be backfilled into GL?
Which bank statement formats are supported first?
Should bank reconciliation auto-create bank charges?
Should failed postings block period close?
Should payroll posting require Advanced Accounting?
Should budgets block purchase orders or only warn?
Which depreciation method is default?
How should insurance denials be accounted for?
Should statutory tax returns be generated inside UHMS or only tracked?
```

Recommend defaults but do not hide uncertainty.

---

# 25. Acceptance Criteria For This Planning Phase

This phase is complete only when:

```text id="o0v9ll"
all accounting gaps in the report are mapped to execution phases
dependencies are clear
database impact is planned
services are planned
permissions are planned
audit logs are planned
module-toggle behavior is planned
migration/backfill strategy is planned
test strategy is planned
risks are documented
open decisions are documented
the planning document is created
no implementation code is changed
```

Proceed with the Accounting Gap Execution Master Planning phase now.
