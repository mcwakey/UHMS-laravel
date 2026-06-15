# Accounting Execution Phase C - Failed Posting Workbench and Resolution Controls

## Summary

Phase C adds an operational workbench for accounting postings that failed after a source
transaction was created. Finance users can inspect failure context, retry supported sources
individually or in a batch, resolve failures with evidence, and apply controlled waivers.

The implementation extends the Phase 0 posting-attempt ledger and Phase A/Phase B posting
services. It does not introduce a second journal engine: successful retries continue through
the existing posting services and `JournalEntryService`.

Final regression result: **700 passed (2,436 assertions)**. The accounting suite reports
**124 passed (291 assertions)**, including **18 Phase C tests (44 assertions)**.

## Database Change

Migration:
`2026_06_15_000005_add_phase_c_resolution_evidence_to_posting_attempts.php`

Additive fields on `accounting_posting_attempts`:

| Field | Purpose |
|-------|---------|
| `resolution_journal_entry_id` | Corrective journal supplied as resolution evidence |
| `resolution_source_type` / `resolution_source_id` | Alternative source record evidence |
| `resolution_reference` | External ticket, document, or approval reference |
| `resolution_evidence` | Structured supporting evidence |
| `materiality_note` | Required justification for a waiver |
| `waiver_review_date` | Follow-up date for temporary or reviewable waivers |

Actor and journal references use nullable evidence links so financial history is retained if
supporting records later become unavailable.

## Retry Handler Registry

`AccountingPostingHandlerRegistry` resolves retry behavior by source module and source type.
Handlers are deliberately thin adapters around existing posting services:

| Handler | Supported sources |
|---------|-------------------|
| `LegacyBillingPostingHandler` | Invoice, payment, discount, credit note |
| `BasicAccountingPostingHandler` | Financial entry and Phase A basic accounting sources |
| `BankAdjustmentPostingHandler` | Approved Phase B bank reconciliation adjustments |

Unknown source types remain failed and receive an explicit unsupported-source error. They are
never marked resolved automatically.

Retry outcomes are normalized through `RetryResult`, including whether the underlying service
already managed the posting-attempt state. This prevents duplicate attempt updates and preserves
existing idempotency controls.

## Workbench Service

`FailedPostingWorkbenchService` provides:

- Filtered and paginated failure search.
- Dashboard counts for unresolved, resolved, waived, retried, unsupported, and aged failures.
- Single retry with supported-source validation.
- Batch retry that continues after one item fails.
- Evidence-backed resolution.
- Elevated waiver with materiality justification.
- Activity-log events for views, retries, batch outcomes, resolutions, and waivers.

`AccountingPostingAttemptService` now owns the final state transitions. Only failed attempts can
be resolved or waived; posted attempts cannot be waived.

## Resolution and Waiver Controls

A normal resolution requires a note plus useful evidence. For accounting corrections, a posted
corrective journal can be linked directly. Resolution evidence may also include a source record,
reference, and structured details.

A waiver:

- Uses the existing elevated posting-resolution permission.
- Requires a resolution note.
- Requires a materiality note.
- May include a review date and supporting reference.
- Remains visible in close-readiness reporting.

These actions change the posting-attempt workflow state only. They do not alter or delete the
original source transaction.

## Routes, Controller, and Views

Six routes were added under `admin/accounting/failed-postings`:

| Method | Action |
|--------|--------|
| GET | Workbench index |
| GET | Attempt detail |
| POST | Retry one attempt |
| POST | Retry selected attempts |
| POST | Resolve with evidence |
| POST | Waive with materiality evidence |

All routes are guarded by `accounting_basic`, `accounting_advanced`, and the existing accounting
posting permissions. The strict permission audit reports **0 missing route permissions** and
**0 unguarded admin mutation routes**.

The index includes status, source, date, error, retryability, and free-text filters; readiness
cards; source-module summaries; bulk selection; and pagination.

The detail screen includes:

- Attempt identity and lifecycle timeline.
- Source, posted journal, reversal, and corrective-journal links where available.
- Error history and retry controls.
- Pretty JSON snapshots with raw fallback.
- Resolution and waiver evidence forms.

The accounting sidebar now places **Failed Postings** with the Advanced Accounting controls.

## Source and Journal Links

`AccountingPostingSourceLinkService` maps known source records to their existing application
screens. Missing or unsupported records degrade to descriptive text rather than generating an
invalid route. Posted, reversal, and corrective journals link to the journal detail screen.

## Close Readiness Integration

`AccountingCloseReadinessService` now reports:

- Unresolved failed postings.
- Resolved and waived postings.
- Postings recovered by retry.
- Unsupported failed sources.
- Oldest unresolved failure.
- Material unresolved count and amount.
- Resolution totals grouped by source module.

The close-readiness page links directly to the filtered workbench and keeps waived items visible
for review.

## Audit Logging

Phase C records workbench access and mutation events through `ActivityLogService`, including
retry requested/completed/failed, batch retry outcomes, evidence resolution, waiver, and
close-readiness review.

`php artisan logs:audit --json` result:

- `MISSING_LOG`: **0**
- `NEEDS_REVIEW`: **0**
- Existing known backlog: **21**

## Localization

English and French accounting language files include the Phase C labels, filters, messages,
timeline states, evidence fields, and validation text.

`php scripts/localisation-audit.php` result:

- Files scanned: **1,371**
- Active runtime candidates: **0**

## Tests Added

`tests/Feature/Accounting/FailedPostingWorkbenchPhaseCTest.php` contains 18 tests covering:

- Index and detail permission enforcement.
- Timeline and snapshot display.
- Unsupported-source retention and audit evidence.
- Basic financial-entry retry.
- Bank-adjustment retry.
- Duplicate retry idempotency.
- Batch continuation after an item failure.
- Resolution note and evidence requirements.
- Corrective-journal validation and linking.
- Waiver materiality requirements and elevated access.
- Prevention of waiving posted attempts.
- Close-readiness unresolved, waived, unsupported, retry, and aging metrics.
- Unknown-source display fallback.
- Module middleware.
- Activity-log events.

## Verification

Commands completed:

- `php artisan migrate --force`
- `php artisan migrate:fresh --env=testing --force`
- `php artisan db:seed --force`
- `php artisan test tests/Feature/Accounting/FailedPostingWorkbenchPhaseCTest.php`
  - **18 passed (44 assertions)**
- `php artisan test tests/Feature/Accounting`
  - **124 passed (291 assertions)**
- `php artisan test tests/Feature/Localization`
  - **12 passed (60 assertions)**
- `php artisan test`
  - **700 passed (2,436 assertions)**
- `php artisan permissions:audit --strict`
  - **0 missing permissions, 0 unguarded admin mutations**
- `php scripts/localisation-audit.php`
  - **0 active runtime candidates**
- `php artisan logs:audit --json`
  - **0 missing, 0 needs review**
- `php artisan route:list --json`
  - **780 total routes, 6 Phase C routes**
- `php artisan view:cache` and `php artisan view:clear`
  - Successful
- `git diff --check`
  - No whitespace errors; only the existing localization-report line-ending warning

## Environment Note

The command-line `--env=testing` invocation does not inherit PHPUnit's in-memory SQLite settings
from `phpunit.xml`, so the required fresh-migration check rebuilt the configured local database.
The standard `DatabaseSeeder` was run immediately afterward to restore the normal development
roles, users, modules, reference data, and accounting setup.

## Known Limitations

- Retry support is registry-based and intentionally limited to source types with a trusted,
  idempotent posting service.
- Batch retry is synchronous in this phase; a queued worker is appropriate if failure volumes
  become large.
- Evidence files are represented by references and structured metadata; binary document storage
  remains outside this phase.
- Existing logging-audit backlog entries are unchanged and unrelated to Phase C.

## Recommended Next Phase

Proceed with a subledger reconciliation workbench covering receivables, payables, inventory,
payroll liabilities, and general-ledger control-account differences.
