You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Accounting Execution Phase C — Failed Posting Workbench & Posting Resolution Controls

## Goal

Implement the dedicated Failed Posting Workbench for UHMS accounting.

Phase 0 created the shared posting-attempt register and services.
Phase A connected Basic Accounting entries to the GL.
Phase B added bank reconciliation and bank adjustment posting.

Now finance users need a proper operator workbench to:

```text
view failed accounting postings
inspect source snapshots and posting snapshots
see retained error history
retry failed postings
resolve postings manually with evidence
waive postings with elevated permission
link corrective journals
monitor failed postings by source module
support period-close readiness
```

Do not create a parallel accounting system.

Use the Phase 0 shared controls:

```text
accounting_posting_attempts
accounting_posting_attempt_events
AccountingPostingAttemptService
AccountingIdempotencyService
AccountingCloseReadinessService
AccountingPostingService
ActivityLogService
```

---

# 1. Required Context

Read:

```text
docs/ACCOUNTING_GAP_EXECUTION_MASTER_PLAN.md
docs/ACCOUNTING_PHASE_0_SHARED_CONTROLS_AND_READINESS_REPORT.md
docs/ACCOUNTING_PHASE_A_BASIC_TO_ADVANCED_POSTING_BRIDGE_REPORT.md
docs/ACCOUNTING_PHASE_B_BANK_ACCOUNTS_AND_RECONCILIATION_REPORT.md
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Current confirmed baseline:

```text
Phase 0 complete.
Phase A complete.
Phase B complete.
Accounting + localisation suites pass.
Phase B focused suite passes.
Active runtime candidates: 0.
logs:audit: 0 missing / 0 needs-review.
```

Important Phase B caveat:

```text
The complete php artisan test suite was not run end-to-end during Phase B.
```

So before implementation, run the full suite and record the baseline.

---

# 2. Baseline Confirmation First

Before changing code, run:

```bash
php artisan test
php artisan test tests/Feature/Accounting
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan logs:audit --json
php artisan route:list
php artisan view:cache
php artisan view:clear
git diff --check
```

Record:

```text
full suite result
accounting suite result
localisation suite result
active runtime candidate count
audit result
route count
view cache result
```

If the full suite has pre-existing failures, document them and confirm they are unrelated to Phase C before continuing.

Do not hide unrelated failures.

---

# 3. Scope of This Phase

Implement:

```text
failed posting workbench index
failed posting detail page
retry action
retry selected action
manual resolution action
waive action
source record link
journal link
attempt event timeline
error context viewer
source snapshot viewer
posting snapshot viewer
filters
dashboard cards
close readiness integration
permissions
audit logging
tests
documentation
```

Do not implement yet:

```text
subledger reconciliation workbench
cash flow statement
payroll posting
budgets
fixed assets
statutory tax returns
receivables collector workbench
claims settlement accounting
```

Those come later.

---

# 4. Core Rules

The failed posting workbench must be:

```text
auditable
permission-aware
module-aware
idempotent
safe from duplicate journals
safe from silent error loss
traceable to source records
traceable to journal entries
```

Important:

```text
Retry must never create duplicate journals.
Resolving must require evidence.
Waiving must require elevated permission and a reason.
Posted attempts cannot be waived.
Resolved attempts cannot be silently retried.
Waived attempts must remain visible.
Errors must not be deleted.
Attempt event history must be append-only.
```

Do not delete failed attempts.

Do not overwrite historical event rows.

---

# 5. Workbench Routes

Add routes under Advanced Accounting.

Suggested route prefix:

```text
admin/accounting/failed-postings
```

Suggested route names:

```text
admin.accounting.failed-postings.index
admin.accounting.failed-postings.show
admin.accounting.failed-postings.retry
admin.accounting.failed-postings.retry-selected
admin.accounting.failed-postings.resolve
admin.accounting.failed-postings.waive
```

All routes must use:

```text
auth
module:accounting_basic
module:accounting_advanced
permission middleware
```

Do not rely on sidebar hiding.

---

# 6. Workbench Index

The index should show failed, waived, resolved, reversed, and posted attempts depending on filters.

Default view:

```text
failed unresolved attempts
```

Columns:

```text
status
source module
source type
source ID
posting type
posting version
attempt count
error code
short error message
last attempted at
next retry at
journal link if any
created by
actions
```

Filters:

```text
status
source module
source type
posting type
date range
attempt count
error code
has journal
has reversal
resolved by
waived only
```

Dashboard cards:

```text
failed unresolved
failed over 7 days
failed over 30 days
waived
resolved
posted after retry
by source module
```

Keep queries indexed and paginated.

---

# 7. Workbench Detail Page

The detail page must show:

```text
source identity
idempotency key
status
journal entry link
reversal journal link
attempt count
first attempted at
last attempted at
next retry at
error code
full error message
error context
source snapshot
posting snapshot
resolution details
event timeline
```

Event timeline should show:

```text
created
processing
failed
retried
posted
resolved
waived
reversed
```

Snapshots may be LONGTEXT JSON. Display them safely:

```text
pretty formatted if valid JSON
raw text fallback if invalid
```

Do not expose sensitive financial data to unauthorized users.

---

# 8. Retry Handling

Implement retry for supported sources.

Use a handler registry:

```text
AccountingPostingHandlerRegistry
```

Each handler should support:

```php
public function supports(AccountingPostingAttempt $attempt): bool;
public function retry(AccountingPostingAttempt $attempt, User $actor): RetryResult;
```

Initial handlers should include existing supported paths:

```text
invoice
payment
discount
credit note
financial_entry / Basic Accounting entry
bank_reconciliation_adjustment
```

Add supplier/inventory handlers only if existing posting services make it safe.

If a source type has no handler:

```text
show controlled unsupported-source error
do not mark resolved
do not waive automatically
append retry-failed event
```

Retry rules:

```text
same idempotency key
same source identity
increment attempt_count
retain previous error event
on success link journal
on failure retain new error event
do not duplicate journal
```

---

# 9. Retry Selected

Add bulk retry for selected failed attempts.

Rules:

```text
only failed attempts can be selected
skip unsupported attempts with clear reason
continue on individual failure
show batch summary
audit batch action
```

Batch summary:

```text
selected
retried
posted
failed_again
unsupported
skipped
```

Do not use one huge transaction for all selected attempts.

Use one transaction per source attempt unless the existing posting service requires otherwise.

---

# 10. Manual Resolution

Manual resolution is for cases where the accounting issue was fixed outside automated retry.

Resolution requires:

```text
resolution type
resolution note
optional linked journal entry
optional linked source record
evidence/reference
actor
timestamp
```

Allowed resolution types:

```text
corrected_by_manual_journal
source_cancelled
not_required_after_review
duplicate_source_record
external_adjustment
other
```

Rules:

```text
cannot resolve posted attempt
cannot resolve without note
cannot resolve without permission
must append event
must remain visible in workbench
must appear in close readiness as resolved, not hidden
```

If linked journal is required for certain resolution types, enforce it.

Recommended:

```text
corrected_by_manual_journal requires journal_entry_id
```

---

# 11. Waive Handling

Waive is an elevated action for non-material or non-ledger items.

Waive requires:

```text
permission
reason
review date or expiry date if supported
materiality note
actor
timestamp
```

Rules:

```text
cannot waive posted attempt
cannot waive without reason
waived attempts remain visible
close readiness must show waived attempts separately
waive does not create journal
waive does not change source financial amounts
```

Finance Manager or Administrator only.

---

# 12. Close Readiness Integration

Extend `AccountingCloseReadinessService` to report:

```text
unresolved failed attempts by period
unresolved failed attempts by source module
waived attempts by period
resolved attempts by period
unsupported failed attempts
posted-after-retry attempts
oldest unresolved failure
material unresolved failures if amount is available
```

If existing close/period screens exist, add a read-only link/card to failed posting readiness.

Do not hard-block period close yet unless an existing close gate already safely supports this.

Document recommended future close-blocking behavior.

---

# 13. Source Links

Where possible, provide source links for:

```text
invoice
payment
discount
credit note
financial entry
bank reconciliation adjustment
supplier payable
supplier payment
goods received note
stock movement
purchase return
```

If a route is unknown or module disabled:

```text
show source identity text only
do not crash
```

---

# 14. Sidebar / Navigation

Add a navigation link under Advanced Accounting if doing so does not break existing sidebar tests.

Suggested label:

```text
Failed Postings
```

If sidebar assertions are still locked from Phase B, update the tests correctly and document it.

Do not bypass menu tests by hiding the route.

---

# 15. Permissions

Use existing Phase 0 permissions where present:

```text
accounting.failed_postings.view
accounting.failed_postings.retry
accounting.failed_postings.resolve
accounting.failed_postings.waive
```

Add only if missing.

Suggested role defaults:

```text
Accountant:
- view
- retry

Finance Manager:
- view
- retry
- resolve
- waive

Administrator / Super Admin:
- all
```

Do not grant to broad clinical roles.

---

# 16. Audit Logging

Use `ActivityLogService`.

Audit:

```text
FAILED_POSTING_VIEWED
FAILED_POSTING_RETRY_REQUESTED
FAILED_POSTING_RETRY_SUCCEEDED
FAILED_POSTING_RETRY_FAILED
FAILED_POSTING_BATCH_RETRY_REQUESTED
FAILED_POSTING_BATCH_RETRY_COMPLETED
FAILED_POSTING_RESOLVED
FAILED_POSTING_WAIVED
FAILED_POSTING_CLOSE_READINESS_VIEWED
```

If `AccountingPostingAttemptService` already audits lower-level state changes, do not duplicate low-level event spam unnecessarily. But user actions must still be auditable.

Run:

```bash
php artisan logs:audit --json
```

Fix any missing/needs-review logs.

---

# 17. Localisation

All new labels must be localized EN/FR.

Use or extend:

```text
lang/en/accounting.php
lang/fr/accounting.php
```

Keys:

```text
failed_postings
failed_posting
failed_posting_workbench
retry_posting
retry_selected
retry_result
retry_succeeded
retry_failed
unsupported_source_type
resolve_posting
waive_posting
resolution_type
resolution_note
waive_reason
materiality_note
attempt_timeline
source_snapshot
posting_snapshot
error_context
posted_after_retry
failed_again
unsupported
oldest_unresolved_failure
failed_over_7_days
failed_over_30_days
close_readiness_failed_postings
corrected_by_manual_journal
source_cancelled
not_required_after_review
duplicate_source_record
external_adjustment
```

Maintain EN/FR parity.

Run:

```bash
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
```

Active runtime candidates must remain:

```text
0
```

---

# 18. Tests

Add tests for:

```text
failed posting index is permission protected
failed posting show is permission protected
failed posting index lists failed attempts
failed posting detail shows event timeline
retry unsupported source records retained failure
retry supported Basic entry posts through Phase A service
retry supported bank adjustment posts through Phase B service
duplicate retry does not duplicate journal
batch retry continues on individual failure
manual resolution requires note
corrected_by_manual_journal requires linked journal
waive requires elevated permission and reason
posted attempt cannot be waived
resolved attempt remains visible
waived attempt appears separately in close readiness
close readiness shows unresolved failed counts
source link fallback does not crash for unknown source
module middleware blocks direct route when Advanced Accounting disabled
ActivityLogService records retry/resolve/waive user actions
localisation lock remains active runtime 0
```

Existing Phase 0, Phase A, and Phase B tests must still pass.

---

# 19. Verification Commands

Run:

```bash
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

```bash
npm run build
```

---

# 20. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_C_FAILED_POSTING_WORKBENCH_REPORT.md
```

Include:

```text
summary
baseline before implementation
database changes if any
services added
handler registry
supported retry sources
unsupported retry sources
permissions
routes/controllers/views
workbench filters
detail page behavior
retry behavior
batch retry behavior
manual resolution behavior
waive behavior
close readiness integration
source-link behavior
audit logging
tests added
commands run
localisation audit result
full suite result
known limitations
next recommended phase
```

---

# 21. Acceptance Criteria

Phase C is complete only when:

```text
failed posting workbench exists
failed posting detail page exists
failed attempts can be filtered and inspected
attempt event timeline is visible
retry works for supported sources
unsupported sources fail safely and visibly
retry does not duplicate journals
batch retry continues safely
manual resolution requires evidence
waive requires elevated permission and reason
waived attempts remain visible
close readiness separates failed/resolved/waived attempts
permissions are enforced
module middleware protects direct routes
ActivityLogService is used
EN/FR localisation parity passes
active runtime candidates remain 0
Phase 0 tests still pass
Phase A tests still pass
Phase B tests still pass
Accounting suite passes
route list works
view cache compiles
logs:audit is clean or documented with root-cause fixes
full test suite is run or any inability is clearly documented
documentation report is created
```

Proceed with Accounting Execution Phase C now.
