You are working on UHMS — Ultimate Hospital Management System.

# UHMS Accounting Execution Phase 0 — Shared Posting Controls, Idempotency, Mapping Foundation & Close Readiness

## Goal

Implement the shared accounting control layer required before executing the accounting gap roadmap.

This phase must create the reusable foundation for later phases:

```text id="bujl6y"
Basic-to-Advanced posting bridge
Bank reconciliation
Failed posting workbench
Subledger reconciliation
Payroll accounting posting
Cash flow
Budgets
Fixed assets
Tax ledgers
Receivables workbench
Claims settlement accounting
```

Do not implement those later modules yet.

This phase is only for the shared posting controls and readiness layer.

---

# 1. Required Context

Read:

```text id="huzhja"
docs/ACCOUNTING_GAP_EXECUTION_MASTER_PLAN.md
docs/ACCOUNTING_MODULE_SPLIT_AND_GAP_REPORT.md
docs/PHASE_17_FULL_TEST_SUITE_REGRESSION_STABILISATION_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Respect the current finance module split:

```text id="lmp2n5"
Billing & Collections remains independent.
Basic Accounting module slug: accounting_basic.
Advanced Accounting module slug: accounting_advanced.
Advanced Accounting depends on Basic Accounting.
Direct routes must use module middleware, not only sidebar hiding.
Existing permissions remain the source of action-level authorization.
```

Do not weaken billing, accounting, stock, payroll, claims, or audit behavior.

---

# 2. Scope of This Phase

Implement only the common foundation:

```text id="t2wxpg"
accounting_posting_attempts table
accounting_account_mappings table
posting idempotency service
posting attempt lifecycle service
account mapping lookup service
close-readiness service foundation
safe posting contract support
backfill metadata support
tests
documentation
```

Do not yet implement:

```text id="h6ywt8"
Basic-to-Advanced posting bridge
bank reconciliation
cash flow statement
payroll posting
budgets
fixed assets
tax returns
AR collector workbench
claims settlement accounting
```

Those come after this phase.

---

# 3. Core Accounting Rules

Every ledger-affecting operation must be:

```text id="8dy1zd"
balanced
transactional
idempotent
traceable to a source record
auditable
reversible
permission-aware
module-aware
```

Important rules:

```text id="5eiob4"
Posted journal entries are immutable.
Corrections use reversal and replacement entries.
Source modules calculate business amounts.
Accounting services map those amounts to accounts and post journals.
No source module should directly write journal rows.
Historical records must not be auto-posted.
Backfills must be previewed and explicitly approved.
```

---

# 4. Database: accounting_posting_attempts

Create a new table:

```text id="rp1ryi"
accounting_posting_attempts
```

Recommended fields:

```text id="xcnmx3"
id
source_module
source_type
source_id
posting_type
posting_version
idempotency_key
status
journal_entry_id
reversal_journal_entry_id
attempt_count
first_attempted_at
last_attempted_at
next_retry_at
error_code
error_message
error_context
source_snapshot
posting_snapshot
resolved_by
resolved_at
resolution_type
resolution_note
created_by
updated_by
timestamps
```

Statuses:

```text id="6dch02"
pending
processing
posted
failed
waived
resolved
reversed
```

Use string status values with application validation.

Do not use database enum.

For snapshot fields, use:

```text id="7zn7tt"
LONGTEXT
```

with JSON-encoded content for MariaDB compatibility.

Indexes:

```text id="dvk8em"
unique idempotency_key
source_module + source_type + source_id
source_type + source_id + posting_type + posting_version
status + last_attempted_at
journal_entry_id
reversal_journal_entry_id
```

Use explicit short index names compatible with MariaDB.

---

# 5. Database: accounting_account_mappings

Create a new table:

```text id="z3nw8i"
accounting_account_mappings
```

Recommended fields:

```text id="c6pfvx"
id
mapping_scope
mapping_key
mapping_value
account_id
facility_id nullable
department_id nullable
branch_id nullable
currency nullable
effective_from
effective_to
priority
is_active
notes
created_by
updated_by
timestamps
```

Purpose:

```text id="zdn6gz"
map operational categories, payment methods, payroll liabilities, tax types, inventory classes, bank accounts, and other source concepts to chart-of-account IDs.
```

Rules:

```text id="izz9wm"
Mappings must be effective-dated.
Only active mappings are used.
If multiple mappings match, highest priority wins.
If mappings conflict, service validation must fail loudly.
Do not silently pick a random account.
```

Do not delete mappings used by historical postings.

Use `restrictOnDelete` or `nullOnDelete` where appropriate.

Do not cascade-delete financial history.

---

# 6. Optional Journal Idempotency Field

Inspect the current `journal_entries` table.

If safe, add:

```text id="bycj6p"
idempotency_key nullable unique
```

But only after checking for existing duplicate source journals.

If unsafe in this phase, document it and keep idempotency enforced in `accounting_posting_attempts`.

Do not risk breaking existing journals.

---

# 7. Services To Add

Create:

```text id="whxhs1"
AccountingPostingAttemptService
AccountingIdempotencyService
AccountingAccountMappingService
AccountingCloseReadinessService
```

Extend existing accounting posting services only where safe.

Do not rewrite all existing posting services in this phase.

---

# 8. AccountingPostingAttemptService

This service should handle:

```text id="q9oiv3"
create pending attempt
mark processing
mark posted
mark failed
mark waived
mark resolved
mark reversed
increment retry count
retain error messages
retain source snapshot
retain posting snapshot
link journal entry
link reversal journal entry
```

Important:

```text id="chsl0o"
Never overwrite old errors without retaining attempt history.
Never mark posted without a journal entry.
Never mark resolved/waived without actor and reason.
Never retry in a way that can duplicate journal entries.
```

If you need separate attempt-history rows, add:

```text id="x2na8o"
accounting_posting_attempt_events
```

only if the current structure cannot preserve retry history properly.

---

# 9. AccountingIdempotencyService

Implement stable idempotency keys.

Default identity:

```text id="1ezf0m"
source_type + source_id + posting_type + posting_version
```

Recommended key format:

```text id="dry5ui"
{source_type}:{source_id}:{posting_type}:v{posting_version}
```

Rules:

```text id="0mrlq1"
Same source identity must not create duplicate posted journals.
Retried failed attempts must reuse the same identity.
Reversals must use their own reversal identity but link back to the original attempt.
```

Add tests proving duplicate posting requests return or reference the existing posted journal instead of creating a second journal.

---

# 10. AccountingAccountMappingService

Implement account mapping lookup.

The service must support:

```text id="kjom88"
mapping scope
mapping key
mapping value
facility override
department override
branch override
currency filter
effective date
priority ordering
active flag
```

It should return:

```text id="a5a3jr"
matched account
matched mapping record
explanation of why it matched
```

If no mapping is found, throw a controlled exception that can be captured by posting attempts.

Do not fall back to arbitrary accounts.

---

# 11. AccountingCloseReadinessService

Create the foundation for period-close readiness.

This service should report:

```text id="swou6v"
unresolved failed postings
waived postings
unposted eligible source records
unreconciled control accounts later
open bank reconciliations later
unmapped cash-flow activity later
```

For this phase, implement at least:

```text id="fuwiyv"
unresolved failed accounting_posting_attempts inside a date range
failed attempts by source module
waived attempts by source module
posted attempts summary
```

Do not hard-block closing yet unless existing period-close code already supports it safely.

Return structured data that later UI screens can use.

---

# 12. Integrate Lightly With Existing Posting Flow

Inspect:

```text id="prg4bs"
JournalEntryService
AccountingPostingService
existing source posting services
billing posting services
payment posting services
inventory posting services
supplier payable posting services
credit note posting services
```

Add light integration only where safe:

```text id="7yb317"
create posting attempt before posting
mark posted after successful journal creation
mark failed when controlled posting exception occurs
store source and posting snapshots
```

If integrating all posting paths is too risky, integrate only the shared `AccountingPostingService` entry point and document the remaining paths.

Do not break existing posting behavior.

Do not change invoice totals, payment allocation, inventory valuation, supplier balances, payroll summaries, or credit note logic.

---

# 13. Backfill Existing Posted/Error Statuses

Create an artisan command:

```bash id="1oag0d"
php artisan accounting:posting-attempts-backfill
```

It must support:

```text id="amfs3d"
--dry-run
--from=
--to=
--chunk=
--source-type=
--source-id=
--resume-from=
```

Purpose:

```text id="p7ma1f"
Backfill metadata only.
Do not create new journals.
Do not alter historical financial amounts.
Do not auto-post historical records.
```

The command should:

```text id="4rnzgu"
detect existing posted source records with journal_entry_id
create posted accounting_posting_attempt rows
detect existing source records with accounting_error
create failed accounting_posting_attempt rows
skip records already backfilled
report selected, created, skipped, failed
```

If source tables differ, support the ones already used in accounting services first and document unsupported sources.

Dry-run must perform no writes.

---

# 14. Permissions

Seed or normalize permissions:

```text id="gphhjj"
accounting.failed_postings.view
accounting.failed_postings.retry
accounting.failed_postings.resolve
accounting.failed_postings.waive
accounting.mappings.view
accounting.mappings.manage
accounting.close_readiness.view
```

If older permissions already exist:

```text id="0zgo62"
accounting.posting.view
accounting.posting.retry
accounting.posting.reverse
```

do not remove them.

Map new permissions to Finance Manager / Accountant roles as appropriate.

Do not grant new permissions to broad clinical roles.

---

# 15. Minimal Admin UI

Add minimal screens only if current accounting UI structure allows it safely.

Screens:

```text id="gmciyg"
Accounting Posting Attempts index
Accounting Posting Attempt show
Account Mapping index
Account Mapping create/edit
Close Readiness summary
```

If UI scope is too much for Phase 0, create routes/services/tests and document UI as Phase C/Phase A follow-up.

Do not overbuild the failed-posting workbench yet.

That is Phase C.

---

# 16. Module Middleware

All new accounting control routes must be protected by:

```text id="4v3432"
auth
permission middleware
module:accounting_advanced
```

Exception:

```text id="9u538z"
If a read-only close readiness or mapping preview is required for Basic Accounting only, document why.
```

Billing routes must not depend on accounting module toggles.

---

# 17. Audit Logging

Use `ActivityLogService`.

Audit events:

```text id="cdfisy"
ACCOUNTING_POSTING_ATTEMPT_CREATED
ACCOUNTING_POSTING_ATTEMPT_FAILED
ACCOUNTING_POSTING_ATTEMPT_POSTED
ACCOUNTING_POSTING_ATTEMPT_RETRIED
ACCOUNTING_POSTING_ATTEMPT_RESOLVED
ACCOUNTING_POSTING_ATTEMPT_WAIVED
ACCOUNTING_ACCOUNT_MAPPING_CREATED
ACCOUNTING_ACCOUNT_MAPPING_UPDATED
ACCOUNTING_ACCOUNT_MAPPING_DISABLED
ACCOUNTING_POSTING_ATTEMPTS_BACKFILLED
CLOSE_READINESS_CHECKED
```

Do not bypass audit logging.

Run:

```bash id="1htcis"
php artisan logs:audit --json
```

If the audit command reports missing/needs-review logs, fix root causes.

---

# 18. Localisation

All new UI strings must be localised EN/FR.

Use or extend:

```text id="9e2tpp"
lang/en/accounting.php
lang/fr/accounting.php
```

Add keys for:

```text id="3z4l3u"
posting_attempts
posting_attempt
source_module
source_type
source_id
posting_type
posting_version
idempotency_key
attempt_count
last_attempted_at
next_retry_at
error_code
error_message
source_snapshot
posting_snapshot
resolution_type
resolution_note
account_mappings
mapping_scope
mapping_key
mapping_value
effective_from
effective_to
priority
close_readiness
unresolved_failed_postings
waived_postings
posted_attempts
```

Maintain EN/FR parity.

Run:

```bash id="0sy480"
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
```

Active runtime candidates must remain:

```text id="iherrk"
0
```

---

# 19. Tests

Add tests for:

```text id="dbu2qb"
posting attempt can be created
posting attempt can be marked processing
posting attempt can be marked posted with journal
posting attempt can be marked failed with retained error
posting attempt cannot be posted twice
same idempotency key prevents duplicate journal posting
account mapping resolves by scope/key/value/date
account mapping respects priority
missing account mapping throws controlled exception
close readiness lists unresolved failed attempts
backfill dry-run performs no writes
backfill creates metadata only, no journals
unauthorized user cannot view posting attempts
unauthorized user cannot manage mappings
module middleware blocks direct route when accounting_advanced disabled
ActivityLogService records state changes
localisation lock remains active runtime 0
```

Existing accounting posting tests must still pass.

---

# 20. Verification Commands

Run:

```bash id="r2e8x7"
php artisan route:list
php artisan view:cache
php artisan view:clear
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan logs:audit --json
php artisan test
git diff --check
```

If frontend assets are touched:

```bash id="6uxoia"
npm run build
```

---

# 21. Documentation

Create:

```text id="e6x05d"
docs/ACCOUNTING_PHASE_0_SHARED_CONTROLS_AND_READINESS_REPORT.md
```

Include:

```text id="lf1zfj"
summary
database changes
services added
commands added
permissions added
routes/controllers/views added
posting attempt lifecycle
idempotency strategy
account mapping strategy
close readiness strategy
backfill strategy
audit logging
tests added
commands run
localisation audit result
known limitations
unsupported source posting paths
next recommended phase
```

---

# 22. Acceptance Criteria

Phase 0 is complete only when:

```text id="sasepz"
accounting_posting_attempts exists
accounting_account_mappings exists
posting attempts can track posted/failed/waived/resolved/reversed states
idempotency prevents duplicate posted journals
account mappings resolve predictably and fail loudly when missing
close readiness can report unresolved failed postings
backfill command supports dry-run and metadata-only backfill
permissions are enforced
module middleware protects direct routes
ActivityLogService is used
EN/FR localisation parity passes
active runtime candidates remain 0
route list works
view cache compiles
logs:audit is clean or documented with root-cause fixes
full test suite is run
documentation report is created
```

Proceed with Accounting Execution Phase 0 now.
