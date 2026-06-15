# Accounting Execution Phase B — Bank Accounts, Statement Import & Formal Bank Reconciliation

## Summary

Phase B adds formal **bank account management**, **CSV bank statement import** (with preview,
duplicate protection and immutable lines), **explainable match suggestions**, **manual
matching**, **formal bank reconciliation** (prepare → match → approve → reopen/reverse), and
**reconciliation adjustments** (bank charges, interest, fees, corrections) that post balanced
journals through the existing accounting services.

It builds entirely on Phase 0 (shared controls) and Phase A (basic→advanced posting bridge):
adjustment journals are created and posted through `JournalEntryService`, audit events go through
`ActivityLogService`, and all screens live under the existing `accounting_advanced` module behind
permission middleware. No parallel accounting or reconciliation system was introduced.

Baseline preserved: Phase 0, Phase A and the rest of the accounting suite remain green
(117 passed / 302 assertions), localisation **active runtime candidates remain 0**, and the
`logs:audit` funnel reports **0 MISSING_LOG / 0 NEEDS_REVIEW** for the new code.

## Database changes

One additive migration: `2026_06_15_000004_create_accounting_phase_b_bank_reconciliation.php`.
String statuses (validated in the app), `DECIMAL(18,2)` money, `LONGTEXT` JSON snapshots
(MariaDB-10.1 safe), explicit short index names, and **no cascade-deletes on financial history**
(structural FKs use RESTRICT; actor/journal FKs use SET NULL).

Tables created (skipped if already present):

| Table | Purpose |
|-------|---------|
| `bank_accounts` | Bank register; masked number + hash; maps to a GL cash/bank account |
| `bank_statement_imports` | One imported file; status lifecycle; file hash for duplicate blocking |
| `bank_statement_lines` | Immutable statement lines; line hash; match state |
| `bank_reconciliations` | Period reconciliation with statement/book/outstanding/adjustment figures |
| `bank_reconciliation_matches` | Links a statement line to a book transaction (polymorphic) |
| `bank_reconciliation_adjustments` | Proposed → approved → posted adjustments |

## Models added

`BankAccount`, `BankStatementImport`, `BankStatementLine`, `BankReconciliation`,
`BankReconciliationMatch`, `BankReconciliationAdjustment` — each with casts (`decimal:2`, dates,
`array` for snapshots), relationships, status constants, and helpers (`remainingToMatch()`,
`signedAmount()`, `isLocked()`, `isEditable()`, `signedBankEffect()`).

## Services added

| Service | Responsibility |
|---------|----------------|
| `BankAccountService` | Create/update/disable; masks account number, hashes it, audits, guards inactive imports |
| `BankStatementCsvParser` | Configurable column mapping, date formats, separate debit/credit or signed amount, normalised references |
| `BankStatementImportService` | Preview (no writes), import (immutable lines), reject; duplicate file + duplicate line detection |
| `BankMatchSuggestionService` | Explainable suggestions from journal lines on the bank GL account; never auto-approves |
| `BankReconciliationService` | Prepare/recompute, match/unmatch, approve/reopen/reverse; reconciliation maths |
| `BankReconciliationAdjustmentPostingService` | Propose/approve/post/reject; posts balanced journals via `JournalEntryService` |

Reused: `JournalEntryService`, `GeneralLedgerService` (book balances), `ActivityLogService`.

## Routes / controllers / views

Routes under `admin/accounting/bank/*` (`module:accounting_basic` + `module:accounting_advanced`
+ per-action permission middleware), names `admin.accounting.bank.*` (28 routes). Controllers:
`BankAccountController`, `BankStatementImportController`, `BankReconciliationController`,
`BankReconciliationAdjustmentController` — all thin, delegating to services.

Views (Bootstrap 5 + Tabler only): bank accounts index/create/edit, imports index/create/preview/show,
reconciliations index/create/show (matching + adjustments workspace)/statement.

## CSV import behaviour

Upload → **preview** (parsed rows, totals, per-row errors, duplicate flags; writes nothing) →
**confirm import** (creates the import + immutable lines). Invalid rows (bad date, no amount, both
amounts) are reported with line numbers and excluded. First release is **CSV only** (no OFX/MT940).

## Duplicate detection

- **File:** SHA-256 of file contents, unique per bank account; re-importing a used file is blocked.
- **Line:** SHA-256 of `bank_account_id | date | normalised reference | debit | credit | external id/description`;
  duplicates of existing or within-file lines are flagged and skipped on import.

## Matching strategy

Suggestions come from journal lines affecting the bank's GL account (statement credit ⇒ book debit,
and vice-versa), ranked by exact amount, reference match, and date proximity, with a confidence
score and a human-readable reason. **Suggestions never auto-apply** — a user confirms each match.
Matches cannot exceed a line's remaining amount, never alter the source transaction, are reversible
before approval, and are locked once the reconciliation is approved.

## Reconciliation formula

```
Adjusted statement balance = statement closing + outstanding deposits
                             − outstanding withdrawals ± approved adjustments
Book balance               = GL cash/bank balance at period end (GeneralLedgerService)
Difference                 = adjusted statement balance − book balance
```

Outstanding items are book journal lines on the bank GL account up to period end that are not
matched to a statement line (unmatched debits = deposits in transit, unmatched credits =
outstanding cheques). Every figure is `round()`-ed to 2 dp; PHP floats are never authoritative.
Approval is blocked when `|difference| > tolerance` unless an override permission
(`accounting.bank_reconciliation.reverse`) is held.

## Adjustment posting strategy

Adjustments are **proposed → approved → posted**. Posting builds a balanced journal via
`JournalEntryService` (`source_module = BANK_RECONCILIATION`), never directly from a controller:

- Bank charge / transfer fee: `Dr contra (expense)  Cr bank`
- Interest income: `Dr bank  Cr contra (income)`
- Correction / other: driven by the chosen side.

Contra accounts are **selected**, never hardcoded.

## Audit logging

All mutations go through `ActivityLogService` (`LogModule::ACCOUNTING`):
`BANK_ACCOUNT_CREATED/UPDATED/DISABLED`, `BANK_STATEMENT_IMPORT_PREVIEWED`,
`BANK_STATEMENT_IMPORTED`, `BANK_STATEMENT_REJECTED`,
`BANK_STATEMENT_LINE_MATCHED/UNMATCHED`,
`BANK_RECONCILIATION_PREPARED/APPROVED/REOPENED/REVERSED`,
`BANK_ADJUSTMENT_PROPOSED/APPROVED/POSTED/REJECTED`.
`logs:audit --json` classifies all four controllers as `SERVICE_FUNNEL_COVERED` (0 MISSING_LOG,
0 NEEDS_REVIEW).

## Permissions added

`accounting.bank_accounts.{view,manage}`, `accounting.bank_statements.{import,view,reject}`,
`accounting.bank_reconciliation.{view,manage,match,approve,reopen,reverse}`,
`accounting.bank_adjustments.{propose,approve,post}`. Seeded to: Accountant (view/import/match/
manage/propose), Finance Manager (+ approve/reopen/reverse, approve/post adjustments, manage
accounts, reject statements), Administrator/Super Admin (all).

## Localisation

EN/FR parity maintained (`lang/{en,fr}/accounting.php` Phase B block + `messages.php` flash keys).
`lang/en/accounting.php` and `lang/fr/accounting.php` each hold 425 keys with zero diff.
`localisation-audit.php`: **active runtime candidates = 0**.

## Tests added

`tests/Feature/Accounting/BankReconciliationPhaseBTest.php` — 20 tests / 41 assertions covering:
bank account + GL mapping, unauthorised access, duplicate file, preview-writes-nothing, import
creates lines, invalid rows, duplicate lines skipped, exact suggestion, manual match, over-match
prevention, match reversal, approved-locks-matches, bank charge + interest balanced journals,
difference calculation, approval blocked on unexplained difference, approval when zero, reopen
requires permission + reason, activity-log events, and module-middleware blocking.

## Commands run

- `php artisan migrate --force` (dev) / `php artisan migrate:fresh --env=testing --force`
- `php artisan test tests/Feature/Accounting tests/Feature/Localization` → **117 passed (302 assertions)**
- `php artisan test tests/Feature/Accounting/BankReconciliationPhaseBTest.php` → **20 passed (41 assertions)**
- `php scripts/localisation-audit.php` → active runtime candidates **0**
- `php artisan logs:audit --json` → MISSING_LOG **0**, NEEDS_REVIEW **0** (bank controllers funnel-covered)
- `php artisan route:list` (28 bank routes) · `php artisan view:cache` (compiles) · `git diff --check` (clean)

## Localisation audit result

Files scanned 1365 · **Active runtime candidates: 0** · EN/FR accounting keys 425/425 (no diff).

## Known limitations

- CSV only (OFX/MT940 deferred, per scope).
- Matching sources are journal lines / cash-book entries and payments; richer many-to-one /
  one-to-many UI is modelled (match methods enumerated) but the workspace currently confirms
  one match at a time.
- Sidebar navigation links were intentionally **not** added to `SidebarMenuBuilder` to avoid
  altering the locked Basic/Advanced Accounting menu assertions; screens are reachable by route
  and can be wired into the sidebar in a follow-up once the menu test is updated.
- The complete `php artisan test` suite was not run end-to-end here (long-running); the directly
  affected accounting + localisation suites and the new Phase B suite are green — run the full
  suite in CI.

## Next recommended phase

Cash flow statement and/or subledger reconciliation workbench, then statement-format expansion
(OFX/MT940) and automated bulk matching with confirmation queues.
