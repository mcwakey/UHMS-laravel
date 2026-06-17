You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Execution Phase J — Dedicated Receivables Workbench, Collections, Dunning & AR Controls

## Goal

Implement a dedicated receivables workbench for UHMS.

The workbench must give finance users one place to manage:

```text
patient receivables
insurance receivables
sponsor receivables
corporate receivables
claims receivables
aged receivables
collection follow-up
payment promises
disputes
write-off recommendations
credit-note follow-up
statement generation
receivable reconciliation
```

This phase must build on:

```text
Phase 0 — Shared posting controls and idempotency
Phase A — Basic-to-Advanced posting bridge
Phase B — Bank accounts and reconciliation
Phase C — Failed posting workbench
Phase D — Subledger reconciliation workbench
Phase E — Payroll accounting posting
Phase E2 — PAYE and Pension / SSNIT settlements
Phase F — Cash Flow Statement and exports
Phase G — Budgets and commitments
Phase H — Fixed assets and depreciation
Phase I — Statutory tax accounting
```

Do not create a parallel billing system.

Do not create a parallel accounting system.

Receivables must remain linked to invoices, payments, credit notes, write-offs, sponsors, claims, and GL control accounts.

---

## 1. Required Context

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
docs/ACCOUNTING_PHASE_G_BUDGETS_AND_COMMITMENTS_REPORT.md
docs/ACCOUNTING_PHASE_H_FIXED_ASSETS_AND_DEPRECIATION_REPORT.md
docs/ACCOUNTING_PHASE_I_STATUTORY_TAX_ACCOUNTING_REPORT.md
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Current status:

```text
Billing and Collections already exist.
Invoice receivables foundation exists.
AR aging foundation exists.
Sponsor/corporate/insurance payer handling exists.
Claims preparation exists.
Subledger reconciliation can compare receivables to GL.
The missing gap is a dedicated receivables operations workbench.
```

Important testing instruction:

```text
Do not run the wide full application test suite after this individual phase.
The wide full-suite test is deferred until all accounting-gap implementation phases in this batch are complete.
Run only necessary safety checks: migrations, route list, view cache, localisation audit, permission audit, logs:audit, PHP lint where practical, focused accounting checks where needed, and git diff check.
```

---

## 2. Scope of This Phase

Implement:

```text
receivables dashboard
payer balance workbench
patient receivable workbench
insurance receivable workbench
sponsor receivable workbench
corporate receivable workbench
claims receivable workbench
AR aging drill-down
collection follow-up records
payment promise records
dispute tracking
dunning/reminder notices
statement generation
collector assignment
receivable notes
receivable status workflow
write-off recommendation workflow
credit-note recommendation workflow
receivable reconciliation links
export-ready AR reports
close-readiness integration
permissions
audit logging
localisation
documentation
```

Do not implement yet:

```text
external debt collection agency integration
SMS gateway sending if not already configured
email sending automation if not already configured
legal case management
claims electronic submission
insurance portal API integration
credit scoring
automatic write-off posting without approval
```

---

## 3. Core Rules

Receivables workbench must be:

```text
payer-aware
invoice-linked
payment-linked
claim-linked where applicable
sponsor-aware
insurance-aware
permission-aware
auditable
non-destructive
reconcilable to GL
```

Rules:

```text
Do not change invoice totals from the receivables workbench.
Do not post write-offs directly without approval and existing accounting controls.
Do not post credit notes directly without approval and existing credit-note controls.
Do not delete receivable history.
Do not hide disputed balances.
Do not treat NHIS as special or hardcoded.
NHIS remains just another insurance provider.
Do not expose clinical details to finance users beyond what permissions allow.
Do not expose restricted financial data to clinical users.
```

---

## 4. Module Rules

This phase belongs to:

```text
Billing & Collections
Advanced Accounting
```

Receivable operations must work for Billing users, but GL reconciliation links require Advanced Accounting.

Routes should use appropriate middleware:

```text
auth
permission middleware
module middleware for billing/collections where applicable
module:accounting_basic and module:accounting_advanced only for accounting-specific reconciliation screens
```

Do not make basic billing unusable when Advanced Accounting is disabled.

If Advanced Accounting is disabled:

```text
receivable follow-up still works
GL reconciliation links are hidden/inaccessible
accounting posting controls are hidden/inaccessible
```

---

## 5. Database Tables

Create additive tables if they do not already exist:

```text
receivable_cases
receivable_case_items
receivable_followups
receivable_promises
receivable_disputes
receivable_assignments
receivable_dunning_notices
receivable_statement_runs
receivable_statement_items
receivable_writeoff_recommendations
receivable_creditnote_recommendations
```

Use string statuses with application validation.

Do not use database enums.

Use `DECIMAL(18,2)` for money.

Use `LONGTEXT` for snapshots where needed.

Use explicit short MariaDB-safe index names.

Do not cascade-delete financial or billing history.

---

## 6. receivable_cases

Purpose:

```text
Group one payer’s outstanding receivables into an operational collection case.
```

Fields:

```text
id
case_number
payer_type
payer_id nullable
payer_name_snapshot
patient_id nullable
insurance_provider_id nullable
sponsor_id nullable
corporate_client_id nullable
claim_id nullable
case_type
priority
status
assigned_to nullable
opened_by
opened_at
closed_by nullable
closed_at nullable
closure_reason nullable
total_original_amount
total_outstanding_amount
total_disputed_amount
total_promised_amount
oldest_due_date nullable
aging_bucket
metadata_snapshot
notes
timestamps
```

Payer types:

```text
patient
insurance
sponsor
corporate
claim
mixed
unknown
```

Case types:

```text
normal_collection
insurance_followup
sponsor_followup
corporate_followup
claims_followup
dispute
writeoff_review
credit_note_review
```

Statuses:

```text
open
in_progress
awaiting_payer
promised
partially_paid
disputed
escalated
recommended_writeoff
recommended_credit_note
resolved
closed
cancelled
```

---

## 7. receivable_case_items

Purpose:

```text
Link collection cases to invoices, invoice receivables, claim receivables, or payer balances.
```

Fields:

```text
id
receivable_case_id
source_type
source_id
invoice_id nullable
invoice_number nullable
claim_id nullable
payer_type
payer_id nullable
original_amount
outstanding_amount
disputed_amount
promised_amount
due_date nullable
aging_bucket
status
metadata_snapshot
timestamps
```

Statuses:

```text
open
partially_paid
paid
disputed
written_off
credited
cancelled
removed
```

Rules:

```text
One receivable source can be linked to multiple historical cases, but only one active case unless explicitly allowed.
Case item snapshots must not replace invoice/payment source-of-truth.
```

---

## 8. receivable_followups

Fields:

```text
id
receivable_case_id
followup_type
followup_date
next_followup_date nullable
contact_person nullable
contact_channel
summary
outcome
created_by
timestamps
```

Follow-up types:

```text
phone
sms
email
letter
in_person
portal
internal_note
other
```

Outcomes:

```text
no_response
payer_contacted
payment_promised
dispute_raised
documents_requested
claim_resubmission_needed
escalated
resolved
other
```

---

## 9. receivable_promises

Fields:

```text
id
receivable_case_id
promised_by
promise_date
expected_payment_date
promised_amount
status
fulfilled_amount
fulfilled_at nullable
broken_at nullable
broken_reason nullable
notes
created_by
updated_by
timestamps
```

Statuses:

```text
active
fulfilled
partially_fulfilled
broken
cancelled
```

Rules:

```text
A promise does not reduce receivable balance.
A promise is operational follow-up only.
Payment reduces balance only when actual payment is posted.
```

---

## 10. receivable_disputes

Fields:

```text
id
receivable_case_id
source_type nullable
source_id nullable
dispute_reason
disputed_amount
status
raised_by
raised_at
resolved_by nullable
resolved_at nullable
resolution_note nullable
recommended_action nullable
metadata_snapshot
timestamps
```

Statuses:

```text
open
under_review
resolved_valid
resolved_invalid
credit_note_recommended
writeoff_recommended
cancelled
```

Recommended actions:

```text
collect
credit_note
writeoff
rebill
claim_resubmit
payer_correction
other
```

Rules:

```text
Disputed amount remains visible in AR aging.
Resolved dispute does not itself change financial balances.
Financial correction must use credit note, write-off, payment, or rebilling workflow.
```

---

## 11. receivable_assignments

Fields:

```text
id
receivable_case_id
assigned_to
assigned_by
assigned_at
released_at nullable
release_reason nullable
timestamps
```

Only one active assignment should exist per case.

---

## 12. receivable_dunning_notices

Purpose:

```text
Generate controlled reminder notices without forcing immediate sending.
```

Fields:

```text
id
receivable_case_id
notice_number
notice_level
notice_date
delivery_channel
recipient_name
recipient_contact
subject
body
status
generated_by
generated_at
sent_by nullable
sent_at nullable
metadata_snapshot
timestamps
```

Notice levels:

```text
friendly_reminder
first_notice
second_notice
final_notice
legal_notice
```

Statuses:

```text
draft
generated
sent
cancelled
failed
```

Rules:

```text
Generate notice first.
Send only if an existing email/SMS mechanism is safely available.
If no sending mechanism exists, leave as generated/printable.
```

---

## 13. receivable_statement_runs and items

Purpose:

```text
Prepare payer statements for patient, sponsor, insurance, or corporate clients.
```

receivable_statement_runs fields:

```text
id
statement_number
payer_type
payer_id nullable
payer_name_snapshot
period_start
period_end
status
opening_balance
charges
payments
credit_notes
writeoffs
closing_balance
generated_by
generated_at
approved_by nullable
approved_at nullable
metadata_snapshot
timestamps
```

Statuses:

```text
draft
generated
approved
sent
cancelled
```

receivable_statement_items fields:

```text
id
receivable_statement_run_id
source_type
source_id
transaction_date
description
debit_amount
credit_amount
balance_after
metadata_snapshot
timestamps
```

Rules:

```text
Statement is a snapshot.
Statement does not post accounting entries.
Statement totals must reconcile to source invoices/payments/credit notes/write-offs.
```

---

## 14. Write-off and Credit-note Recommendations

Create:

```text
receivable_writeoff_recommendations
receivable_creditnote_recommendations
```

Purpose:

```text
Recommend financial corrections without bypassing approval and posting controls.
```

Fields:

```text
id
receivable_case_id
source_type
source_id
recommended_amount
reason
status
recommended_by
recommended_at
approved_by nullable
approved_at nullable
rejected_by nullable
rejected_at nullable
rejection_reason nullable
linked_writeoff_id nullable
linked_credit_note_id nullable
metadata_snapshot
timestamps
```

Statuses:

```text
draft
recommended
approved
rejected
converted
cancelled
```

Rules:

```text
Recommendation does not affect AR balance.
Actual write-off must use existing write-off workflow.
Actual credit note must use existing credit-note workflow.
Link converted recommendation to final financial record.
```

---

## 15. Services To Add

Create:

```text
ReceivableWorkbenchService
ReceivableCaseService
ReceivableAgingService
ReceivableFollowupService
ReceivablePromiseService
ReceivableDisputeService
ReceivableAssignmentService
ReceivableDunningService
ReceivableStatementService
ReceivableRecommendationService
ReceivableReconciliationService
ReceivableExportService
```

Use existing where available:

```text
InvoiceService
PaymentService
CreditNoteService
WriteOffService if present
StatementService if present
AccountingPostingService
AccountingCloseReadinessService
ActivityLogService
AccountingExportService
```

Do not put receivable calculations in controllers or Blade.

---

## 16. ReceivableWorkbenchService

Responsibilities:

```text
build dashboard metrics
group receivables by payer
group receivables by aging bucket
show top overdue payers
show disputed balances
show promised payments
show broken promises
show unassigned cases
show high-risk cases
show claims awaiting settlement
show sponsor/corporate overdue balances
```

Metrics:

```text
total AR
current
1-30 days
31-60 days
61-90 days
over 90 days
disputed amount
promised amount
collection rate
days sales outstanding if enough data exists
top debtors
```

Do not include restricted clinical data in finance dashboard.

---

## 17. ReceivableAgingService

Calculate aging from due date or invoice date.

Aging buckets:

```text
current
1_30
31_60
61_90
over_90
```

Support filters:

```text
payer type
payer
department
branch
invoice type
claim status
sponsor
insurance provider
corporate client
date range
```

Rules:

```text
Aging must use outstanding balances after payments, credit notes, and write-offs.
Disputed balances remain included but separately labelled.
Claims receivables should show claim status where available.
```

---

## 18. ReceivableCaseService

Responsibilities:

```text
open case
add receivable items
refresh outstanding snapshots
change status
close case
reopen case
assign collector
escalate case
link to payer/patient/claim/sponsor
```

Case opening should support:

```text
single invoice
payer balance
aging bucket selection
claim batch
sponsor statement
insurance payer
corporate payer
```

---

## 19. Dunning / Reminder Notices

Dunning service should:

```text
generate reminder notice from case
choose template by notice level
include payer statement summary
include invoice list
include payment instructions where configured
support printable output
support email/SMS only if existing safe channels exist
record generated/sent status
```

Do not send automatically unless an existing notification preference and channel exists.

Default to printable/generated notices.

---

## 20. Statement Generation

Statement service should:

```text
generate payer statement snapshot
include opening balance
include invoices/charges
include payments
include credit notes
include write-offs
include closing balance
support patient, sponsor, insurance, corporate payer types
support PDF/print/CSV if existing export service supports it
```

Statement must not post accounting entries.

---

## 21. Receivable Reconciliation Integration

Update Phase D receivables reconciliation.

It should link to:

```text
receivable cases
disputes
writeoff recommendations
credit-note recommendations
unassigned overdue balances
unresolved case amounts
```

Classifications:

```text
open_receivable
disputed_receivable
unassigned_overdue
payment_promise_active
payment_promise_broken
writeoff_recommended
creditnote_recommended
claim_pending
sponsor_pending
corporate_pending
unknown_difference
```

---

## 22. Failed Posting Workbench Integration

Link failed receivable-related postings to the receivables workbench:

```text
invoice receivable posting
payment posting
credit note posting
write-off posting
claim receivable posting
sponsor receivable posting
```

Receivable case detail should show related failed posting attempts.

Do not retry postings directly from the receivables case unless existing Phase C permissions and services are used.

---

## 23. Close Readiness Integration

Update `AccountingCloseReadinessService` to show:

```text
large overdue receivables
unassigned overdue receivables
unresolved receivable disputes
approved writeoff recommendations not converted
approved credit-note recommendations not converted
broken payment promises
claims receivables over threshold
sponsor/corporate balances over threshold
AR reconciliation not run
AR reconciliation unresolved differences
```

Do not hard-block period close unless existing close code safely supports it.

Document recommended future close-block behavior.

---

## 24. Permissions

Add:

```text
receivables.workbench.view
receivables.cases.view
receivables.cases.manage
receivables.cases.assign
receivables.followups.create
receivables.promises.manage
receivables.disputes.manage
receivables.dunning.generate
receivables.dunning.send
receivables.statements.generate
receivables.statements.approve
receivables.recommendations.writeoff
receivables.recommendations.creditnote
receivables.reports.view
receivables.reports.export
```

Suggested defaults:

```text
Cashier / Billing Officer:
- view assigned receivable cases
- create followups
- record payment promises

Accountant:
- view workbench
- manage cases
- generate statements
- generate dunning notices
- manage disputes

Finance Manager:
- assign cases
- approve statements
- recommend write-offs / credit notes
- export reports

Administrator / Super Admin:
- all
```

Do not grant broadly to clinical roles.

---

## 25. Audit Logging

Use `ActivityLogService`.

Audit:

```text
RECEIVABLE_CASE_OPENED
RECEIVABLE_CASE_UPDATED
RECEIVABLE_CASE_ASSIGNED
RECEIVABLE_CASE_ESCALATED
RECEIVABLE_CASE_CLOSED
RECEIVABLE_FOLLOWUP_CREATED
RECEIVABLE_PROMISE_CREATED
RECEIVABLE_PROMISE_UPDATED
RECEIVABLE_DISPUTE_CREATED
RECEIVABLE_DISPUTE_RESOLVED
RECEIVABLE_DUNNING_GENERATED
RECEIVABLE_DUNNING_SENT
RECEIVABLE_STATEMENT_GENERATED
RECEIVABLE_STATEMENT_APPROVED
RECEIVABLE_WRITEOFF_RECOMMENDED
RECEIVABLE_CREDITNOTE_RECOMMENDED
RECEIVABLE_REPORT_VIEWED
RECEIVABLE_REPORT_EXPORTED
```

Run:

```bash
php artisan logs:audit --json
```

Fix new missing/needs-review audit gaps.

---

## 26. Localisation

All new labels must be localised EN/FR.

Use or extend:

```text
lang/en/receivables.php
lang/fr/receivables.php
lang/en/accounting.php
lang/fr/accounting.php
lang/en/reports.php
lang/fr/reports.php
```

Required keys include:

```text
receivables
receivable_workbench
receivable_case
receivable_cases
payer_type
payer_balance
aging_bucket
aged_receivables
patient_receivables
insurance_receivables
sponsor_receivables
corporate_receivables
claims_receivables
collection_followup
payment_promise
payment_promises
broken_promise
receivable_dispute
receivable_disputes
dunning_notice
dunning_notices
friendly_reminder
first_notice
second_notice
final_notice
legal_notice
payer_statement
statement_run
writeoff_recommendation
creditnote_recommendation
assigned_collector
top_debtors
overdue_receivables
unassigned_overdue
disputed_balance
promised_balance
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

## 27. Navigation

Add navigation:

```text
Billing & Collections > Receivables Workbench
Billing & Collections > AR Aging
Billing & Collections > Payer Statements
Advanced Accounting > Receivable Reconciliation
```

Do not hide route access behind navigation only.

Use permissions and module middleware.

---

## 28. Focused Tests To Add

Add focused tests but do not run the wide full suite.

Required coverage:

```text
receivables dashboard is permission protected
aging buckets calculate correctly
payer balance groups patient/insurance/sponsor/corporate balances
case can be opened from invoice receivable
case can be opened from payer balance
case assignment records active collector
follow-up record updates next follow-up date
payment promise does not reduce AR balance
fulfilled promise links to actual payment
broken promise appears in dashboard
dispute remains included in aging but separately labelled
dunning notice can be generated without sending
statement snapshot reconciles invoices/payments/credit notes/write-offs
writeoff recommendation does not affect balance
credit-note recommendation does not affect balance
converted recommendation links to final record
failed receivable posting appears on case detail
AR reconciliation links to receivable cases/disputes
close readiness reports overdue/unassigned/disputed AR
permissions protect mutation routes
module middleware protects accounting reconciliation routes
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

## 29. Minimal Verification Commands

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

## 30. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_J_RECEIVABLES_WORKBENCH_REPORT.md
```

Include:

```text
summary
database changes
models added
services added
permissions added
routes/controllers/views added
receivable case lifecycle
aging calculation
payer balance logic
follow-up workflow
payment promise workflow
dispute workflow
dunning behavior
statement generation
recommendation workflows
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

## 31. Acceptance Criteria

Phase J is complete only when:

```text
receivables workbench exists
payer balances are visible by type
AR aging drill-down exists
cases can be opened and managed
collector assignment works
follow-ups are recorded
payment promises are tracked without changing AR balance
disputes are tracked and remain visible in aging
dunning notices can be generated
payer statements can be generated
write-off recommendations do not post directly
credit-note recommendations do not post directly
recommendations can link to final approved financial records
failed posting attempts can be viewed from receivable context
AR reconciliation links receivable cases and disputes
close readiness reports receivable exceptions
permissions are enforced
module middleware protects accounting routes
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

Proceed with Accounting Execution Phase J now.
