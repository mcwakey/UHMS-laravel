# Logs & Audit Trail — Remaining Recommendations

> **Status: COMPLETED** — all backlog items below were implemented in the
> Phase-Logs-Final pass (2026-05-29). See
> [docs/AUDIT_LOG_DEVELOPER_GUIDE.md](AUDIT_LOG_DEVELOPER_GUIDE.md) for the
> developer-facing summary, and the per-item DONE notes below for code
> pointers.

_Backlog of follow-up work that was intentionally out of scope for the Phase-Logs
implementation. Items are roughly ordered by ROI._

## 1. Adoption — wire `ActivityLogService` into the remaining services

The wrapper exists and is proven, but only the procedure cancel/complete paths
currently emit module-tagged entries on top of Spatie's automatic model logs.
Highest-value adoption targets:

| Service / Observer | Module | Suggested actions |
|---|---|---|
| `BillingService::generateInvoice / voidInvoice / writeOff` | `BILLING` | `INVOICE_GENERATED`, `INVOICE_VOIDED` (severity `WARNING`), `WRITE_OFF` (severity `WARNING`) with `reason`. |
| `PaymentService::record / refund` | `PAYMENTS` | `PAYMENT_RECORDED`, `PAYMENT_REFUNDED` (severity `WARNING`). |
| `ClaimsService::submit / approve / reject / appeal` | `CLAIMS` | Status verbs with patient & claim context. |
| `EmergencyCaseService::triage / discharge / transfer` | `EMERGENCY` | Severity `WARNING` for overrides. |
| `AdmissionService::admit / transfer / discharge` | `ADMISSION` | Bed transfer should include `from_bed_id` / `to_bed_id` in metadata. |
| `PatientMergeService::merge` | `PATIENT_MERGE` | Mirror the existing `patient_merge_logs` row with severity `SECURITY`. |
| `StockMovementService::issue / receive / transfer / adjust` | `STOCK` | Adjustments should require `reason` and emit severity `WARNING`. |
| `RoleObserver` / `PermissionObserver` (new) | `ROLES` / `PERMISSIONS` | Track who granted/revoked what, with old vs new permission arrays. |

Roll out one module at a time and pair each with a focused feature test.

## 2. Retention command — `php artisan logs:cleanup`

`config('activitylog.delete_records_older_than_days')` is already set to 365
but nothing schedules a prune. Add:

- `app/Console/Commands/LogsCleanupCommand.php` (signature
  `logs:cleanup {--days=} {--dry-run}`).
- Default to the config value; allow overrides per module via a config map
  (e.g. `MAR` → 730, `BILLING` → 2555).
- Schedule daily at 02:30 in `routes/console.php`.
- Wrap the prune in a `LOGS / RETENTION_PURGED` activity entry (severity
  `NOTICE`) with `metadata.deleted_count`.

## 3. Observer-based logging for hot models

For models where the existing `LogsActivity` trait is too coarse, add a thin
observer that calls `ActivityLogService`:

- `InvoiceObserver` — surface `status` transitions explicitly.
- `PaymentObserver` — mark refunds as `WARNING`.
- `UserObserver` — flag role changes as `SECURITY`.

Observers can coexist with `LogsActivity`; just guard against double-writing
by setting Spatie's `logOnly([])` on the model and letting the observer be the
single source of truth.

## 4. Streaming to external systems

- **Slack / Teams**: a tiny listener on the `Spatie\Activitylog\Models\Activity`
  `saved` model event can post entries where
  `properties.severity ∈ {CRITICAL, SECURITY}` to a webhook.
- **SIEM (e.g. Elastic / Splunk)**: queue a job per critical event to forward
  the JSON payload. Keep the synchronous DB write as the source of truth.
- **S3 archive**: nightly job exporting yesterday's rows to
  `s3://uhms-audit/YYYY/MM/DD.jsonl.gz` before the retention purge runs.

## 5. Viewer enhancements

- Vue **`JsonDiffViewer`** component replacing the current table-based old-vs-new
  rendering on the detail page (highlight added / removed / changed keys).
- "Pin to dashboard" widget showing the last 10 `SECURITY` entries.
- Per-user **saved filters** (e.g. ward sister wants `MAR / OVERRIDE`).
- Server-side **full-text search** on the `properties.description` field once
  MariaDB upgrades to a JSON-indexed version (or add a generated column).

## 6. Retention UI

A "Log retention" settings panel gated by `logs.manage_retention` that lets
the compliance officer override per-module retention. Persist in a
`log_retention_overrides` table and have the `logs:cleanup` command consult
it before applying the default.

## 7. Test coverage expansion

- Per-permission scoping: a separate test for each of `logs.view_clinical`,
  `logs.view_financial`, `logs.view_stock`, `logs.view_security` proving the
  module allowlist is enforced.
- Auth pipeline: feature tests for actual `POST /login` / `POST /logout` /
  failed credentials, asserting a `SECURITY` row is written.
- Sensitive payload: explicit assertion that `password` provided inside
  `metadata` is masked in the persisted row, not just by `sanitise()` in
  isolation.
- Export: assert CSV row content (not just status + content-type).

## 8. Observability of the logger itself

Pipe `Log::warning('ActivityLogService delivery failed', …)` to a dedicated
channel (`activity_failures.log`) so an outage in the audit pipeline surfaces
quickly. Add a tiny Prometheus / Telescope counter for delivery failures.

## 9. Async writes

Activity writes are currently synchronous. If high-traffic endpoints (e.g.
bulk MAR import, stock-take) start showing latency from logging, move the
`ActivityLogService::log()` body onto a queued job. Keep `SECURITY`-severity
writes synchronous so failed auth attempts are never lost.

## 10. Documentation

- Add a short developer guide (`docs/AUDIT_LOG_DEVELOPER_GUIDE.md`) summarising
  "when to call `ActivityLogService` vs let the model trait do it" and the
  severity ladder.
- Update `UHMS_Updated_User_Manual.md` with a screenshot of the new
  `/admin/logs` page and the permission matrix.
