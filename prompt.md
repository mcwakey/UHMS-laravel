You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Execution Phase A — Basic-to-Advanced Posting Bridge

## Goal

Implement the Basic-to-Advanced Accounting posting bridge.

Approved Basic Accounting income and expense entries currently remain in the operational `financial_entries` ledger. When Advanced Accounting is enabled, those approved Basic entries must be able to post into the Advanced Accounting general ledger as balanced journal entries.

This phase must use the Phase 0 shared controls:

```text id="wpobrf"
accounting_posting_attempts
accounting_posting_attempt_events
accounting_account_mappings
AccountingPostingAttemptService
AccountingIdempotencyService
AccountingAccountMappingService
AccountingCloseReadinessService
AccountingPostingService
journal_entries.idempotency_key
```

Do not create a parallel posting system.

---

# 1. Required Context

Read:

```text id="q1212c"
docs/ACCOUNTING_GAP_EXECUTION_MASTER_PLAN.md
docs/ACCOUNTING_PHASE_0_SHARED_CONTROLS_AND_READINESS_REPORT.md
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/PHASE_17_FULL_TEST_SUITE_REGRESSION_STABILISATION_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Important Phase 0 status:

```text id="qtel1s"
Accounting shared controls implemented.
Full suite passed: 649 tests.
Accounting suite passed.
Localization lock preserved.
Active runtime candidates: 0.
logs:audit: 0 missing / 0 needs-review.
```

Do not break this baseline.

---

# 2. Current Problem

Basic Accounting supports:

```text id="eq2u9l"
manual income
manual expenses
daily collection
cashier handover
operational reconciliation
income/expense categories
```

But approved manual income/expense entries remain operational only.

They do not create balanced journal entries in Advanced Accounting.

This creates a risk:

```text id="hkk2q7"
Basic Accounting view says income/expense exists.
Advanced Accounting GL does not contain it.
Financial statements can diverge unless manually reconciled.
```

This phase fixes that gap.

---

# 3. Core Rules

The bridge must follow these rules:

```text id="ku13lc"
Only approved Basic Accounting entries can post to GL.
Advanced Accounting must be enabled before posting.
Do not post draft/unapproved entries.
Do not post twice.
Do not silently change historical entries.
Do not auto-post historical data.
Do not create journals directly from controllers.
Do not bypass Phase 0 posting attempts.
Do not bypass JournalEntryService or AccountingPostingService.
Do not weaken permissions.
Do not bypass ActivityLogService.
```

Posted entries are immutable.

Corrections must use:

```text id="myevub"
reversal
replacement entry
controlled backfill
```

not direct mutation of posted historical amounts.

---

# 4. Module Rules

Respect the module split:

```text id="lkd5to"
Billing & Collections remains independent.
Basic Accounting module slug: accounting_basic.
Advanced Accounting module slug: accounting_advanced.
Advanced Accounting depends on Basic Accounting.
```

Rules:

```text id="fcwvmh"
Basic Accounting entries can still be created when Advanced Accounting is disabled.
If Advanced Accounting is disabled, Basic entries must not post to GL.
When Advanced Accounting is disabled, mark Basic entries as eligible/not_applicable, not posted.
If Advanced Accounting is enabled later, historical posting must happen only through controlled preview/backfill.
```

All Phase A routes must use:

```text id="eaewuy"
auth
module:accounting_basic
module:accounting_advanced where GL posting is required
permission middleware
```

Do not make Billing dependent on Accounting.

---

# 5. Database Changes

Inspect the current Basic Accounting tables, especially the table that stores manual income/expense entries, likely:

```text id="9o02fp"
financial_entries
```

Add only safe nullable columns where missing:

```text id="1kc3h7"
approval_status
accounting_status
journal_entry_id
accounting_posted_at
accounting_error
reversal_journal_entry_id
reversed_at
reversed_by
reversal_reason
posting_version
posted_by
```

If equivalent columns already exist, reuse them.

Do not duplicate columns.

Recommended statuses:

```text id="qnjxz7"
not_applicable
eligible
pending
posted
failed
reversed
```

Add safe indexes:

```text id="fjrbvw"
approval_status
accounting_status
journal_entry_id
accounting_posted_at
entry_date
category_id
```

Use explicit short index names compatible with MariaDB.

Do not add non-null columns that would lock large production tables.

---

# 6. Posting Template Tables

Add posting-template support for Basic Accounting entries.

Create tables if they do not already exist:

```text id="v5h6j9"
accounting_posting_templates
accounting_posting_template_lines
```

## accounting_posting_templates

Fields:

```text id="vkcewk"
id
code
name
source_module
posting_type
entry_type
status
effective_from
effective_to
is_active
approved_by
approved_at
created_by
updated_by
timestamps
```

Example template codes:

```text id="4oj79b"
basic_income_cash
basic_income_bank
basic_expense_cash
basic_expense_bank
```

Statuses:

```text id="zovl5x"
draft
active
inactive
retired
```

## accounting_posting_template_lines

Fields:

```text id="kwksjc"
id
template_id
line_order
side
account_source
mapping_scope
mapping_key
mapping_value_source
fixed_account_id
amount_source
description_template
is_active
timestamps
```

Line side:

```text id="epn6cs"
debit
credit
```

Account source examples:

```text id="c71dtp"
fixed_account
category_mapping
payment_method_mapping
cash_account_mapping
bank_account_mapping
```

Amount source examples:

```text id="p574a2"
entry_amount
tax_amount
net_amount
gross_amount
```

For Phase A, start with simple two-line templates.

---

# 7. Account Mapping Rules

Use the existing Phase 0 table:

```text id="7117ip"
accounting_account_mappings
```

Do not create another mapping table unless absolutely necessary.

Required mapping scopes:

```text id="5n8o3x"
basic_income_category
basic_expense_category
basic_payment_method
basic_cash_account
basic_bank_account
```

Minimum mapping behavior:

```text id="w6ij58"
income category → income/revenue account
expense category → expense account
payment method/cash/bank → cash or bank account
```

If a mapping is missing, posting must fail loudly and create a failed posting attempt with the error retained.

Do not fall back to a random account.

---

# 8. Default Journal Strategy

For approved Basic income:

```text id="b5hzh8"
Dr Cash/Bank Account
Cr Mapped Income Account
```

For approved Basic expense:

```text id="2xgydm"
Dr Mapped Expense Account
Cr Cash/Bank Account
```

The cash/bank account should come from payment method or configured mapping.

The income/expense account should come from category mapping.

Do not use hardcoded account IDs.

Do not use account names as permanent logic.

---

# 9. Services To Add

Create:

```text id="gzx9pw"
BasicAccountingPostingService
BasicAccountingBackfillService
PostingTemplateService
```

Use existing:

```text id="zdvrew"
AccountingPostingService
AccountingPostingAttemptService
AccountingIdempotencyService
AccountingAccountMappingService
JournalEntryService
ActivityLogService
```

Controllers must call services.

Do not put posting logic in controllers or Blade.

---

# 10. BasicAccountingPostingService

This service should:

```text id="3pchfn"
validate entry approval state
validate Advanced Accounting module state
validate fiscal period is open
resolve posting template
resolve account mappings
build balanced journal lines
generate idempotency key
create or reuse posting attempt
post through AccountingPostingService
link journal_entry_id to financial entry
update accounting_status
retain accounting_error on failure
audit success/failure
```

Posting identity:

```text id="0s1s9d"
source_type = financial_entry
source_id = financial_entries.id
posting_type = basic_income or basic_expense
posting_version = financial_entries.posting_version or 1
```

Idempotency key should follow Phase 0 conventions.

Same entry/version must never produce duplicate journals.

---

# 11. Posting Eligibility

An entry is eligible only when:

```text id="0f8wak"
entry is approved
entry is not already posted
entry is not reversed
entry amount is valid
entry date is inside an open accounting period
Advanced Accounting is enabled
required account mappings exist
required posting template exists and is active
```

If not eligible, return structured reasons.

Do not throw raw exceptions to the UI.

---

# 12. Failure Handling

If posting fails:

```text id="nhmj95"
record failed accounting_posting_attempt
store error code/message/context
set financial entry accounting_status = failed
store accounting_error
do not create partial journal
do not mark entry posted
do not swallow the error silently
```

If the failure is due to missing mapping, the user should clearly see which mapping is missing.

---

# 13. Reversal Handling

Add controlled reversal support for posted Basic entries.

Rules:

```text id="ezz1vv"
Only posted entries can be reversed.
Reversal requires permission.
Reversal requires reason.
Reversal creates a reversal journal through existing journal reversal service.
Reversal links reversal_journal_entry_id.
Original entry remains historically visible.
Entry status becomes reversed.
```

Do not delete original journals.

Do not edit posted journal lines.

---

# 14. Posting Template UI

Add screens under Advanced Accounting settings or accounting controls:

```text id="5rkrx5"
Posting templates index
Posting template create/edit
Posting template lines editor
Posting template activate/deactivate
```

If a full editor is too large for this phase, seed default templates and provide read-only UI plus mapping UI.

Do not skip the service/model foundation.

---

# 15. Basic Entry Posting UI

Update Basic Accounting income/expense listing/show screens to display:

```text id="9mb047"
approval status
accounting status
journal link
posting error
posting attempt link
posted date
posted by
```

Add actions where permitted:

```text id="536ld9"
Post to GL
Batch post selected
Preview posting
Reverse GL posting
```

Actions must be permission-protected.

Do not show GL posting actions when Advanced Accounting is disabled.

---

# 16. Batch Posting and Preview

Implement batch posting safely.

Features:

```text id="u796nd"
date range filter
entry type filter
category filter
payment method filter
approval status filter
dry-run preview
eligible count
ineligible count
missing mapping list
expected debit total
expected credit total
per-entry journal preview
execute approved batch
```

Dry-run preview must not write journals.

Execution must process entries safely and idempotently.

If one entry fails, decide whether batch continues or stops; document the behavior.

Recommended default:

```text id="vp4hjx"
continue processing other entries
record failed attempts for failures
show batch summary
```

---

# 17. Controlled Historical Backfill

Create command:

```bash id="t92c4f"
php artisan accounting:basic-entries-post-to-gl
```

Options:

```text id="jik2x0"
--dry-run
--from=
--to=
--chunk=
--entry-type=
--category-id=
--entry-id=
--resume-from=
--approved-batch-id=
```

Rules:

```text id="8s5v5m"
Dry run writes no journals.
Historical backfill requires explicit execution.
Do not auto-post all history.
Do not post unapproved entries.
Do not post entries from closed periods unless explicitly allowed by an authorized reopen workflow.
Do not create duplicate journals.
```

Command output must include:

```text id="8j48da"
selected
eligible
ineligible
posted
already_posted
failed
debit_total
credit_total
missing_mappings
```

---

# 18. Permissions

Add permissions:

```text id="6356xz"
accounting.basic.post_to_gl
accounting.basic.post_batch
accounting.basic.preview_posting
accounting.basic.reverse_gl
accounting.basic.backfill.preview
accounting.basic.backfill.execute
accounting.posting_templates.view
accounting.posting_templates.manage
accounting.posting_templates.approve
```

Suggested role defaults:

```text id="v14kom"
Accountant: preview, post single, post batch, view templates
Finance Manager: all including reverse, backfill execute, approve templates
Administrator: all seeded permissions
```

Do not grant these permissions to broad clinical roles.

---

# 19. Audit Logging

Use `ActivityLogService`.

Audit:

```text id="3ht37w"
ACCOUNTING_POSTING_TEMPLATE_CREATED
ACCOUNTING_POSTING_TEMPLATE_UPDATED
ACCOUNTING_POSTING_TEMPLATE_APPROVED
ACCOUNTING_POSTING_TEMPLATE_DISABLED
BASIC_ENTRY_POSTING_PREVIEWED
BASIC_ENTRY_POSTED_TO_GL
BASIC_ENTRY_POSTING_FAILED
BASIC_ENTRY_BATCH_POSTING_STARTED
BASIC_ENTRY_BATCH_POSTING_COMPLETED
BASIC_ENTRY_REVERSED
BASIC_ENTRY_BACKFILL_PREVIEWED
BASIC_ENTRY_BACKFILL_APPROVED
BASIC_ENTRY_BACKFILL_COMPLETED
```

Run:

```bash id="k10zqa"
php artisan logs:audit --json
```

Fix any missing/needs-review logs.

---

# 20. Localisation

All new labels must be localized EN/FR.

Use or extend:

```text id="4wsfjw"
lang/en/accounting.php
lang/fr/accounting.php
```

Required labels include:

```text id="23egje"
post_to_gl
post_selected_to_gl
preview_posting
posting_preview
posting_template
posting_templates
template_lines
account_source
amount_source
entry_posted_to_gl
entry_posting_failed
reverse_gl_posting
reversal_reason
batch_posting
eligible_entries
ineligible_entries
missing_mappings
already_posted
advanced_accounting_required
mapping_required
journal_created
journal_reversed
```

Maintain EN/FR parity.

Run:

```bash id="ezmxuj"
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
```

Active runtime candidates must remain:

```text id="jtmjho"
0
```

---

# 21. Tests

Add tests for:

```text id="nsbge3"
unapproved Basic entry cannot post
approved income posts balanced journal
approved expense posts balanced journal
Advanced Accounting disabled prevents posting
missing category mapping creates failed posting attempt
missing cash/bank mapping creates failed posting attempt
duplicate post request returns existing journal
posted entry cannot be edited in a way that changes GL silently
posted entry can be reversed with reason
reversal creates linked reversal journal
batch preview writes no journals
batch execution posts eligible entries and records failures
historical backfill dry-run writes no journals
historical backfill execution is idempotent
posting template can be created
posting template can be activated/deactivated
unauthorized user cannot post Basic entry to GL
module middleware blocks direct GL posting route when Advanced Accounting disabled
ActivityLogService records posting and reversal
localisation lock remains active runtime 0
```

Existing Phase 0 tests must still pass.

---

# 22. Verification Commands

Run:

```bash id="fwk1ih"
php artisan migrate:fresh --env=testing --force
php artisan test tests/Feature/Accounting/AccountingPhase0ControlsTest.php
php artisan test tests/Feature/Accounting
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan logs:audit --json
php artisan route:list
php artisan view:cache
php artisan view:clear
php artisan test
git diff --check
```

If frontend assets are touched:

```bash id="agmz0t"
npm run build
```

---

# 23. Documentation

Create:

```text id="gm0omr"
docs/ACCOUNTING_PHASE_A_BASIC_TO_ADVANCED_POSTING_BRIDGE_REPORT.md
```

Include:

```text id="9wqpap"
summary
database changes
posting templates
account mappings used
services added
commands added
permissions added
routes/controllers/views added
journal strategy
posting eligibility rules
failure handling
reversal handling
batch posting behavior
historical backfill behavior
audit logging
tests added
commands run
localisation audit result
known limitations
next recommended phase
```

---

# 24. Acceptance Criteria

Phase A is complete only when:

```text id="4jcbjp"
approved Basic income can post to GL
approved Basic expense can post to GL
journals are balanced
journal entries are linked to source financial entries
posting attempts are created and updated
idempotency prevents duplicate journals
missing mappings create retained failed attempts
batch preview performs no journal writes
batch posting is safe and auditable
historical backfill is dry-run capable and idempotent
posted entries can be reversed through journal reversal
permissions are enforced
module middleware protects direct routes
ActivityLogService is used
EN/FR localisation parity passes
active runtime candidates remain 0
Phase 0 tests still pass
Accounting suite passes
route list works
view cache compiles
logs:audit is clean or documented with root-cause fixes
full test suite is run
documentation report is created
```

Proceed with Accounting Execution Phase A now.
