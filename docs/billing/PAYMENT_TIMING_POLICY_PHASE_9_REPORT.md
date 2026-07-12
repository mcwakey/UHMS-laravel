# Payment Timing Policy — Phase 9 Report

**Financial Clearance, Conditional Closure, Outstanding-Balance Approval, and Receivable Preservation**

Date: 2026-07-12 · Status: Implemented · Deployment default: disabled

## 1. Architecture and source-of-truth decision

Phase 9 is a derived administrative layer. `invoice_items` remains authoritative for item-level patient responsibility/payment/balance and `invoice_receivables` remains authoritative for payer-separated balances. Clearance amount fields are snapshots only. No Phase 9 path creates a payment, waiver, credit note, invoice adjustment, write-off, payment arrangement, previous-balance override, or accounting entry.

The summary is visit-scoped and excludes cancelled/refunded invoices and cancelled/voided items. Patient, insurer, sponsor, and corporate responsibility remain separate. Rendered/active service records without an invoice item are treated as unbilled blockers. Previous-visit debt is not included.

## 2. Vocabulary and modes

Added typed modes `disabled`, `observe`, and `active`; statuses `pending`, `cleared`, `conditionally_cleared`, `financially_closed`, and `stale`; supported settlement bases; immutable clearance events; and exception types/statuses. Manual preview remains possible while disabled. Only active mode permits the dedicated financial-close mutation.

Configuration lives in `config/visit_financial_clearance.php`. Defaults are disabled and `VISIT_FINANCIAL_CLEARANCE_FORCE_DISABLED=true`; environment force-disabled takes precedence over the database setting and does not mutate operational data.

## 3. Persistence

Added:

- `visit_financial_clearances` — one current row per visit, policy/arrangement and ledger snapshots, actors and lifecycle timestamps.
- `visit_financial_clearance_history` — append-only material snapshots.
- `visit_financial_clearance_exceptions` — maker-checker request/approval lifecycle with bounded request text and financial/policy/risk snapshots.
- `visit_financial_clearance_exception_history` — append-only exception transitions.

Indexes cover status, basis, finance action, assessment, close, stale, expiry, type, and visit/status lookups. Migrations are additive and were applied successfully on the local SQLite development database.

## 4. Decision rules

- Known unbilled activity or pending adjustment: pending; cannot close.
- Zero patient responsibility: cleared using zero-responsibility, fully-insured, or fully-sponsored basis according to payer receivables.
- Patient outstanding within tolerance: cleared / fully settled.
- Live patient outstanding: pending unless a current approved exception covers the live amount.
- Valid approved exception: conditionally cleared using the exception-specific basis; the receivable stays open.
- Pay-after and running-bill policy snapshots explain service-time timing only and never erase the final debt test.

`snapshotIsStale()` compares live responsibility, paid/outstanding, invoice/item/receivable counts, and the current exception against the stored snapshot.

## 5. Financial-close behavior

`VisitFinancialClearanceService` provides assess, refresh, close, stale, reopen, and staleness diagnosis. Financial close:

1. requires `visits.financial_clearance.close`;
2. requires effective mode `active`;
3. re-assesses under a transaction;
4. accepts only cleared or conditionally-cleared decisions;
5. requires a bounded reason;
6. appends history and one activity event;
7. changes only the financial-clearance row.

It does not change visit, consultation, admission, discharge, emergency, invoice, payment, receivable, or GL state.

## 6. Conditional-clearance workflow

Added request, approve, reject, withdraw, revoke, and expiry transitions using transactions and row locks. One pending request and one current approved exception per visit are enforced in the service. Live outstanding is recalculated on request and approval; requested/approved amounts cannot exceed it. Separate approver is required by default. Insurance-pending, corporate-guarantee, and management approvals require a supporting reference. Terminal records are preserved.

Approval refreshes clearance to conditional where valid. Revocation/expiry marks the related clearance stale. It never marks the invoice or receivable paid.

## 7. Financial mutation integration

Invoice, invoice-item, receivable, and payment observers mark an existing clearance stale after successful commits. Failures are caught and reported so the original financial mutation succeeds. No clearance record is automatically created by observers, no financial close occurs automatically, and no clinical controller queries clearance.

## 8. Permissions and roles

Added the 15 specified clearance, exception, and settings permissions. Route middleware and dedicated Form Requests enforce them.

- Super Admin/Admin: all.
- Finance Manager: workflow/settings management, including close/approve/reject/revoke, excluding activation and rollback.
- Accountant: view, assess, history/report, request/withdraw and exception history; no close or approval.
- Clinical/reception roles: none added.

Permission audit result: 887 database permissions, 520 route-referenced permissions, 0 missing, and 0 unprotected admin mutation routes.

## 9. Admin UI and reporting

Added `/admin/billing/visit-financial-clearances` worklist, visit detail/actions, conditional exception request/approval display, aggregate report, patient-level CSV export, and restricted settings/rollback page. Patient identity is limited to existing masked/model display boundaries; history JSON is not rendered raw.

## 10. Commands and scheduling

Added:

- `billing:visit-financial-clearance-status`
- `billing:visit-financial-clearance-audit`
- `billing:visit-financial-clearance-backfill` (dry-run default; `--active-only`)
- `billing:visit-financial-clearance-refresh` (dry-run default)
- `billing:visit-financial-clearance-exception-expire` (dry-run default)

Committed exception expiry is scheduled daily with `withoutOverlapping()` and `onOneServer()`.

## 11. Localization

Added matching English/French `visit_financial_clearance.php` files. Recursive localization parity passed.

## 12. Verification results

- Phase 9 focused behavior: **2 passed, 7 assertions** (settlement/close, conditional approval, receivable and clinical-state preservation).
- Phase 9 plus localization: **4 passed, 9 assertions**.
- Payment-timing/billing regression selection: **194 passed, 630 assertions**, 42.28s.
- Permission/activity-log selection: **160 passed, 513 assertions**, 159.38s.
- PHP syntax: **45 touched PHP files passed**.
- Routes: **15 Phase 9 routes registered**.
- Migrations: passed and applied.
- Blade compilation: passed.
- Diagnostics: status/audit/expiry dry-run passed; audit reported 0 clearance findings.
- Permission audit: 0 missing route permissions; 0 unprotected admin mutation routes.
- `git diff --check`: passed.

The complete Laravel suite was attempted twice. The runner produced no buffered summary before the execution wrapper timed out (first at 2 minutes, then at 15 minutes), so the full-suite result is **inconclusive**, not reported as passing or failing. The broad focused selections above completed successfully and include the Phase 1–8 payment regressions.

The complete Playwright suite was invoked but stopped before discovery because `UHMS_RECEPTION_EMAIL` and `UHMS_RECEPTION_PASSWORD` were not configured. No credentials were invented; browser verification remains environment-blocked.

## 13. Deployment and rollback

Deployment remains safe-by-default: configured/effective mode is disabled and the environment force-disabled switch is true. Activation requires a distinct critical permission and explicit confirmation. Rollback immediately sets configured mode to disabled and logs the action; it preserves clearance/exception history, invoices, payments, receivables, arrangements, and clinical state.

## 14. Confirmations

- Clinical completion/discharge workflows were not modified and do not query financial clearance.
- Emergency stabilization/disposition remains unchanged.
- Financial close never means paid, clinically complete, or discharged.
- Outstanding balances remain collectible after conditional or financial closure.
- New financial activity after closure is allowed and marks the snapshot stale.
- Phase 7 arrangements are never converted automatically into Phase 9 exceptions.
