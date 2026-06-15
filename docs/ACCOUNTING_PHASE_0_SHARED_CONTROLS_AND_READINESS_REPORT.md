# UHMS Accounting Phase 0 Shared Controls and Readiness Report

**Date:** 2026-06-15  
**Status:** Complete  
**Scope:** Shared posting controls, idempotency, account mapping, close readiness, metadata backfill, minimal administration UI, permissions, audit logging, localization and tests.

## 1. Summary

Accounting Execution Phase 0 is implemented.

UHMS now has a shared accounting posting-attempt register with append-only lifecycle events, deterministic posting identities, an effective-dated account mapping foundation, close-readiness summaries, and a metadata-only historical backfill command.

The existing `AccountingPostingService` now uses the shared control layer. Existing billing, payment, receivable, supplier and inventory posting services were not rewritten in this phase; their current financial calculations and source status behavior remain unchanged.

## 2. Database Changes

Migration:

```text
database/migrations/2026_06_15_000002_create_accounting_phase_0_controls.php
```

### `accounting_posting_attempts`

Stores:

- source module, type, ID, posting type and version;
- unique idempotency key;
- pending, processing, posted, failed, waived, resolved and reversed states;
- original and reversal journal links;
- attempt counts and timestamps;
- retained current error details;
- source and posting snapshots as `LONGTEXT`;
- resolution actor, type, note and timestamp;
- creation and update actors.

### `accounting_posting_attempt_events`

Stores append-only lifecycle history:

- event type;
- previous and next status;
- error code and full message at that attempt;
- event context;
- actor and occurrence time.

The event table preserves prior errors when a later retry updates the current attempt state.

### `accounting_account_mappings`

Stores effective-dated mappings with:

- scope, key and value;
- chart-of-account link;
- optional facility, department, branch and currency dimensions;
- effective dates;
- priority and active status;
- audit actors and notes.

Accounts use `restrictOnDelete`. Departments and actors use `nullOnDelete`. Mapping records are disabled rather than deleted through the supplied administration flow.

### `journal_entries.idempotency_key`

A nullable unique key was added. Existing journals remain `NULL`, so historical records are not altered and no historical posting is inferred. New shared posting requests populate the key.

### MariaDB Compatibility

Indexed identifiers were limited to old-MariaDB-safe widths because the installed server enforces the 767-byte index limit. Long logical identities are deterministically shortened with a SHA-256 suffix.

The backfill command uses a direct `information_schema.COLUMNS` count instead of Laravel `Schema::hasColumn()`, because the installed MariaDB version does not expose the `generation_expression` field expected by the framework query.

## 3. Models Added

- `AccountingPostingAttempt`
- `AccountingPostingAttemptEvent`
- `AccountingAccountMapping`

Controlled mapping failures use:

- `AccountingAccountMappingNotFoundException`

## 4. Services Added

### `AccountingIdempotencyService`

Creates stable identities from:

```text
source type + source ID + posting type + posting version
```

It returns an existing posted journal when the identity has already completed.

### `AccountingPostingAttemptService`

Supports:

- pending creation;
- processing and retry counting;
- posted and journal linkage;
- failed with retained event history;
- waived and resolved with actor and reason;
- reversed with reversal journal linkage;
- structured accounting audit events.

Posted attempts cannot be linked to a different journal. Posted attempts must be reversed rather than waived or manually resolved.

### `AccountingAccountMappingService`

Resolves mappings by:

- scope, key and value;
- effective date;
- facility, department, branch and currency specificity;
- priority;
- active status.

The highest priority and most specific mapping wins. Equal-ranked conflicts and missing/inactive accounts throw a controlled exception; the service never selects an arbitrary account.

### `AccountingCloseReadinessService`

Returns a structured date-range summary for:

- unresolved failed attempts;
- waived attempts;
- posted attempts;
- failed and waived counts by source module.

Placeholder fields are included for later unposted-source, reconciliation, bank and cash-flow checks. Phase 0 reports readiness but does not hard-block period close.

## 5. Existing Service Integration

`AccountingPostingService` now:

1. creates or reuses the posting identity;
2. returns an existing posted journal for duplicate requests;
3. creates and marks a posting attempt as processing;
4. creates and posts the journal transactionally through `JournalEntryService`;
5. marks the attempt posted;
6. records a retained failed state if posting throws.

`JournalEntryService` accepts the shared idempotency key when creating a source journal.

No invoice totals, payment allocation, stock valuation, supplier balances, payroll summaries, credit note calculations or existing journal validation rules were changed.

## 6. Command Added

```bash
php artisan accounting:posting-attempts-backfill
```

Options:

```text
--dry-run
--from=
--to=
--chunk=
--source-type=
--source-id=
--resume-from=
```

The command:

- reads existing journal/error metadata;
- creates posted or failed attempt metadata;
- skips existing identities;
- creates no journals;
- changes no source financial amounts;
- reports selected, created/eligible, skipped and failed counts;
- writes one aggregate audit event after a write run.

Supported source aliases:

- invoice;
- payment;
- discount;
- credit note;
- invoice receivable;
- goods received note;
- supplier payable;
- supplier payment;
- purchase return;
- stock movement.

## 7. Permissions and Roles

Added:

```text
accounting.failed_postings.retry
accounting.failed_postings.resolve
accounting.failed_postings.waive
accounting.mappings.view
accounting.mappings.manage
accounting.close_readiness.view
```

Existing `accounting.failed_postings.view`, `accounting.posting.view`, `accounting.posting.retry` and `accounting.posting.reverse` remain.

Role defaults:

- Accountant: view/retry attempts, view/manage mappings and view close readiness.
- Finance Manager: Accountant permissions plus resolve/waive and elevated accounting management permissions.
- Administrator: receives all seeded permissions through the existing seeder behavior.
- No broad clinical role receives the new permissions.

## 8. Routes, Controllers and Views

All new routes are inside the existing authenticated `accounting_advanced` route group and use permission middleware.

### Posting Attempts

- searchable/filterable index;
- read-only attempt detail;
- journal links;
- error, source snapshot, posting snapshot and event history.

This is intentionally not the Phase C failed-posting workbench. Retry, resolve and waive UI actions remain deferred.

### Account Mappings

- index and filters;
- create and edit forms;
- disable action;
- active account and department selection.

### Close Readiness

- date range filter;
- readiness result;
- failed, waived and posted summary cards;
- source-module exception breakdown.

The Accounting dashboard links to these controls only when the user has the matching permission.

## 9. Audit Logging

Implemented through `ActivityLogService`:

```text
ACCOUNTING_POSTING_ATTEMPT_CREATED
ACCOUNTING_POSTING_ATTEMPT_FAILED
ACCOUNTING_POSTING_ATTEMPT_POSTED
ACCOUNTING_POSTING_ATTEMPT_RETRIED
ACCOUNTING_POSTING_ATTEMPT_RESOLVED
ACCOUNTING_POSTING_ATTEMPT_WAIVED
ACCOUNTING_POSTING_ATTEMPT_REVERSED
ACCOUNTING_ACCOUNT_MAPPING_CREATED
ACCOUNTING_ACCOUNT_MAPPING_UPDATED
ACCOUNTING_ACCOUNT_MAPPING_DISABLED
ACCOUNTING_POSTING_ATTEMPTS_BACKFILLED
CLOSE_READINESS_CHECKED
```

`php artisan logs:audit --json` reported:

```text
MISSING_LOG 0
NEEDS_REVIEW 0
```

The existing known audit backlog remains outside this phase.

## 10. Localization

Phase 0 accounting labels were added to:

```text
lang/en/accounting.php
lang/fr/accounting.php
```

Language parity passed. The standalone scan reported:

```text
Active runtime candidates: 0
```

## 11. Tests Added

`tests/Feature/Accounting/AccountingPhase0ControlsTest.php` contains 15 tests covering:

- pending, processing, posted and failed lifecycle behavior;
- retained retry error history;
- immutable posted journal linkage;
- resolve/waive reason requirements;
- duplicate posting prevention;
- effective-date and priority mapping resolution;
- conflict and missing mapping failures;
- close-readiness summaries;
- backfill dry-run and metadata-only execution;
- permission denial;
- module middleware;
- authorized page rendering;
- activity logging.

## 12. Verification Results

Commands run:

```text
php artisan migrate:fresh --env=testing --force
php artisan test tests/Feature/Accounting/AccountingPhase0ControlsTest.php
php artisan test tests/Feature/Accounting
php artisan route:list
php artisan view:cache
php artisan view:clear
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan logs:audit --json
php artisan db:seed --class=RoleSeeder --env=testing --force
php artisan accounting:posting-attempts-backfill --dry-run --env=testing
php artisan test
git diff --check
```

Results:

```text
Phase 0 focused: 15 passed, 39 assertions
Accounting suite: 73 passed, 155 assertions
Localization suite: 12 passed, 60 assertions
Full suite: 649 passed, 2300 assertions
Routes: 735 registered
Blade view cache: compiled successfully
Localization active runtime candidates: 0
Audit missing/needs-review: 0 / 0
RoleSeeder: completed
Backfill dry run: completed with no writes
```

No frontend asset source was changed, so `npm run build` was not required.

## 13. Known Limitations and Deferred Paths

1. Existing billing, payment, receivable, supplier and inventory posting services still use their established source-level status fields directly. They are not yet dual-writing live attempts.
2. The shared `AccountingPostingService` is Phase 0-ready, but current production source-specific posting services do not all call it.
3. Historical metadata backfill covers the listed source models only.
4. The existing retry controller still supports invoice, payment, discount and credit-note handlers only.
5. Posting-attempt retry, resolve and waive actions are service/permission foundations only; the full operator UI belongs to Phase C.
6. Close readiness uses attempt timestamps and does not yet evaluate source effective dates, unposted eligible records, subledger reconciliation, bank reconciliation or cash-flow mapping.
7. Close readiness does not hard-block period close in Phase 0.
8. Facility and branch mapping dimensions are reserved numeric identifiers because UHMS does not currently have facility/branch master tables to reference.
9. Historical journals do not receive inferred idempotency keys. This avoids silently asserting that potentially duplicated historical source journals are unique.

## 14. Next Recommended Phase

Proceed with **Accounting Execution Phase A: Basic-to-Advanced Posting Bridge**.

Phase A should use the new shared posting contract, account mapping service, attempt lifecycle, idempotency key, close-readiness summary and metadata backfill conventions. It should not introduce a parallel posting or mapping mechanism.
