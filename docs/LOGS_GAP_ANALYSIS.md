# Logs & Audit Trail — Gap Analysis

_Generated for the UHMS hospital management system._

## 1. Scope

This report documents the state of activity / audit logging in UHMS **before** the
Phase-Logs overhaul. The audit covered:

- `composer.json` (already pinned `spatie/laravel-activitylog ^4.12`)
- `config/activitylog.php`
- `database/migrations/*_create_activity_log_table.php` and the two follow-up
  migrations adding `event` and `batch_uuid`
- All 21 Eloquent models that already opt into Spatie's `LogsActivity` trait
- The 8 custom domain log tables (visit/medication/medical-record/emergency/
  procedure/claim/patient-merge/consultation-route)
- `app/Services/MedicationAdministrationLogService.php`
- `app/Services/MedicalRecordEntryLogService.php`
- `app/Http/Controllers/Admin/ActivityLogController.php`
- The `resources/views/settings/activity-log.blade.php` viewer
- Auth pipeline (login / logout / failed / password-reset events)
- Role/permission seeders (`database/seeders/RoleSeeder.php`)

## 2. What was already in place

| Capability | Status | Where |
|---|---|---|
| Spatie activity-log package installed | ✅ | `composer.lock`, `config/activitylog.php` |
| `activity_log` table with `event` & `batch_uuid` columns | ✅ | three migrations under `database/migrations/` |
| 365-day retention configured (no auto-prune yet) | ⚠️ Configured, not scheduled | `config/activitylog.php` line 14 |
| `LogsActivity` trait on 21 domain models | ✅ | `app/Models/{User,Visit,Patient,Invoice,Payment,Claim,Admission,Ward,Supplier,StockTransfer,ServicePrice,ServiceCatalog,PurchaseOrder,ProductPrice,Product,Prescription,InvestigationItem,InsuranceProvider,FinancialEntry,Bed,Appointment}.php` |
| Domain-specific status log tables (8) | ✅ | `visit_status_logs`, `patient_merge_logs`, `medication_administration_logs`, `medical_record_entry_logs`, `emergency_case_logs`, `procedure_status_logs`, `claim_status_logs`, `visit_consultation_route_logs` |
| Two dedicated log services (MAR, medical record) | ✅ | `app/Services/*LogService.php` |
| Basic activity log viewer | ✅ | `resources/views/settings/activity-log.blade.php` (settings sidebar) |
| Listing route under settings | ✅ | `routes/web.php` — `admin.settings.activity-log` |

## 3. Gaps identified

### 3.1 No unified write surface
Models emitted activity rows through Spatie automatically, but **manual** events
(corrections, overrides, status changes, finance actions) had no central helper.
Each caller crafted properties differently — module names, severity, and reason
fields were ad-hoc. There was **no `ActivityLogService`** wrapper.

### 3.2 Auth events were not audited
Logins, logouts, failed login attempts, and password resets were **never written
to the activity log**. The `\Illuminate\Auth\Events\*` listeners were not bound
anywhere in `AppServiceProvider` or a dedicated `EventServiceProvider`. Required
by prompt §19 (security events).

### 3.3 No taxonomy
There were no enums describing the canonical module set or severity levels, so
the prompt's §9 module list (EMERGENCY / ADMISSION / CONSULTATION / MAR / …)
and §10 severity scale (DEBUG → SECURITY) had **no in-code representation**.

### 3.4 No sensitive-field masking
Spatie's default `logOnly()` whitelists already exclude `password`, but for
custom payloads passed via the future `ActivityLogService::log()` call there
was **no centralised sanitiser**. Tokens, API keys, recovery codes, card
numbers and CVVs could have been written verbatim in `properties`.

### 3.5 Viewer limitations
The existing viewer:
- Only filtered by `search`, `log_name`, `date_from`, `date_to`.
- Could not filter by **module**, **severity**, **action**, **user**, **patient**, **visit**, or **subject type**.
- Had **no detail page** — every row used an inline collapsed modal that
  showed only a one-shot properties dump and no diff view.
- Had **no CSV export**.
- Was reachable by every signed-in user — there was **no `can:` middleware**
  on the route.

### 3.6 No permission scoping
There were no `logs.*` permissions in `RoleSeeder`. Any user that could reach
`/admin/settings/activity-log` could read **all** rows regardless of clinical
vs financial sensitivity. Required by prompt §22.

### 3.7 Lifecycle hooks missing
The handful of services that emit transitions (procedure cancel/complete,
medication overrides) did not also write an `ActivityLogService` row, so the
unified audit trail had no record of these. Status was logged only into the
domain-specific tables.

### 3.8 No tests
No test asserted that activity rows were written with the correct shape, that
sensitive fields were masked, or that permission scoping rejected unauthorised
users.

## 4. Risk assessment

| Risk | Impact | Likelihood (pre-fix) |
|---|---|---|
| Failed-login bursts go undetected | High (security) | High |
| Password / token leaked into `properties` JSON | High (privacy + compliance) | Medium |
| Non-Super-Admin staff reading finance audit rows | Medium (privacy) | High |
| Cannot reconstruct exact change ("what did the doctor edit?") | Medium (clinical safety) | Medium |
| Auditor cannot pull a CSV for a date range | Medium (compliance) | High |

## 5. Out of scope for this phase

- Per-record retention overrides (e.g. keep MAR longer than finance).
- Streaming to a SIEM / Slack channel.
- Async/queue dispatch of log writes (current writes are synchronous and very
  cheap; bulk seeding bypasses logging via `withoutEvents` where it matters).
- Replacing the 8 domain status-log tables — they are intentionally kept and
  mirrored, not migrated.

See `LOGS_REMAINING_RECOMMENDATIONS.md` for the follow-up backlog.
