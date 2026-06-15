# Accounting Execution Phase A

## Basic-to-Advanced Posting Bridge Report

Date: 2026-06-15

## Outcome

Phase A is implemented. Approved Basic Accounting income and expense entries can now be explicitly previewed, posted, retried, batch-posted, and reversed through the Advanced Accounting general ledger.

The bridge does not change Basic-only behavior:

- Creating or approving a Basic entry does not automatically create a journal.
- Basic Accounting remains usable while Advanced Accounting is disabled.
- Advanced posting routes require both Accounting modules and dedicated permissions.
- Historical backfill is preview-first and command execution requires an explicit batch reference.

## Posting Rules

Default templates are seeded for:

- `basic_income`: debit the mapped payment-method account and credit the mapped income-category account.
- `basic_expense`: debit the mapped expense-category account and credit the mapped payment-method account.

Runtime posting never relies on hardcoded account IDs. Operators configure effective-dated Phase 0 mappings with these scopes:

- `basic_income_category` using key `category_id` and the Basic category ID as value.
- `basic_expense_category` using key `category_id` and the Basic category ID as value.
- `basic_payment_method` using key `method` and values such as `cash`, `bank_transfer`, `card`, or `mtn_momo`.
- `basic_cash_account` and `basic_bank_account` are available for future or custom templates.

Missing, conflicting, inactive, or out-of-date mappings block posting with a controlled error. The source entry is marked `failed`, and a Phase 0 posting attempt retains the source and preparation error.

## Data Model

`financial_entries` now carries nullable posting metadata:

- approval and accounting status
- original and reversal journal links
- posting and reversal timestamps
- posting error
- posting version
- posting and reversal actors
- reversal reason

New template tables:

- `accounting_posting_templates`
- `accounting_posting_template_lines`

Templates are effective-dated. Any edit returns a template to draft and clears approval. Activation uses the dedicated approval action, which rejects overlapping active templates for the same source, entry type, posting type, and effective period.

## Services

- `PostingTemplateService` resolves effective templates, mappings, accounts, amounts, and descriptions into an explainable balanced journal preview.
- `BasicAccountingPostingService` enforces eligibility, posts through the Phase 0 idempotent posting contract, retains failures, updates source metadata atomically, and performs explicit reversals.
- `BasicAccountingBackfillService` provides a shared filtered query, read-only preview, chunked execution, and continue-on-failure totals.

Each successful posting uses the deterministic Phase 0 identity for the financial entry, posting type, and posting version. Repeated requests return the same journal rather than duplicating ledger impact.

## Operator Surfaces

Basic income and expense lists now show:

- approval status
- GL status
- posting error summary
- original journal link
- preview, post, and reversal actions where permitted

Advanced Accounting now includes:

- Basic Posting Bridge
- Posting Templates
- existing Account Mappings with Phase A scope suggestions

The batch bridge supports date, type, category, and entry ID filters. Preview writes no journals or attempts. Execution requires selected eligible entries and explicit confirmation, and continues when an individual entry fails.

## Command

```text
php artisan accounting:basic-entries-post-to-gl --dry-run
php artisan accounting:basic-entries-post-to-gl --from=2026-01-01 --to=2026-06-15 --approved-batch-id=FIN-2026-001
```

Supported options:

- `--dry-run`
- `--from`
- `--to`
- `--chunk`
- `--entry-type`
- `--category-id`
- `--entry-id`
- `--resume-from`
- `--approved-batch-id`

Non-dry execution requires `--approved-batch-id`. Command runs record a batch audit event with the filters, totals, and approval reference.

## Permissions

Added permissions:

- `accounting.basic.post`
- `accounting.basic.reverse`
- `accounting.basic.batch.view`
- `accounting.basic.batch.execute`
- `accounting.posting_templates.view`
- `accounting.posting_templates.manage`
- `accounting.posting_templates.approve`

Accountants receive posting, preview, and template-view access. Finance Managers additionally receive reversal, batch execution, template management, and template approval.

## Audit Events

Phase A records:

- `BASIC_ENTRY_POSTING_PREVIEWED`
- `BASIC_ENTRY_POSTED_TO_GL`
- `BASIC_ENTRY_POSTING_FAILED`
- `BASIC_ENTRY_POSTING_REVERSED`
- `BASIC_ENTRY_POSTING_BATCH_PREVIEWED`
- `BASIC_ENTRY_POSTING_BATCH_EXECUTED`
- posting template create, update, approve, and disable events

Phase 0 attempt lifecycle events remain the authoritative retry and failure history.

## Verification

Completed:

- Phase A migration applied successfully.
- Default posting templates seeded successfully.
- Role permissions seeded successfully.
- Blade views compiled successfully.
- Phase A routes registered and module/permission gated.
- Strict permission audit: 0 missing route permissions and 0 unguarded admin mutation routes.
- Accounting suite: 85 tests passed, 201 assertions.
- Localization suite: 12 tests passed, 60 assertions.
- Permissions suite: 12 tests passed, 47 assertions.
- Phase A focused suite: 12 tests passed, 46 assertions.
- Full application suite: 661 tests passed, 2,346 assertions.
- `git diff --check`: clean apart from existing line-ending notices.

Focused coverage includes:

- unapproved entry rejection
- balanced income and expense postings
- payment-method and category mappings
- duplicate-post idempotency
- retained missing-mapping failure
- Advanced-disabled isolation
- explicit reversal linkage
- posted-entry immutability
- dry-run no-write behavior
- continue-on-failure batch execution
- module and permission route gates
- posting and reversal audit events

## Operational Readiness

Before production backfill:

1. Review the seeded income and expense templates.
2. Configure and approve all category and payment-method mappings.
3. Run a filtered dry run.
4. Resolve every blocked mapping or period error.
5. Capture an approved batch reference.
6. Execute in controlled date ranges and review Phase 0 attempts plus journal totals.

No historical journal is created merely by applying this phase.
