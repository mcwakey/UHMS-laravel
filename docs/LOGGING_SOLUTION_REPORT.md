# Logging / Audit Trail — Solution Report

The fix is architectural and **MariaDB-10.1-safe**: make patient/visit context a
**queryable, indexed column** and attach it automatically, then point the patient
timeline at it. One backend (Spatie), no duplicate logging system, no existing
logs removed.

## ActivityLogService updates

- `log()` now resolves patient/visit context from the subject via
  `ActivityContextResolver` and merges `patient_id`/`visit_id` into the data
  **before** the async dispatch, so both sync and queued paths carry it. Explicit
  values are preserved; logging still never throws.
- New patient/visit wrappers: `logPatientAction`, `logVisitAction`,
  `logClinicalAction`, `logFinancialAction`, `logStockAction`.
- New timeline API: `getPatientTimeline(Patient, filters)` and `patientIdsFor()`
  (main patient + merged duplicates).

## Context resolver added

`App\Services\ActivityContextResolver` derives `[patient_id, visit_id]` from any
subject: `Patient`→id; `Visit`→patient_id+id; models with `patient_id`/`visit_id`
attributes (Invoice, InvoiceItem, EmergencyCase, Admission, MedicalRecord,
LabRequest, …); `invoice_id`-only models (Payment) via the invoice; and
visit-only subjects resolve the patient from the visit.

## Custom Activity model

`App\Models\ActivityLog extends Spatie\…\Activity` with a `saving` hook that
mirrors `properties.patient_id/visit_id` into the new columns (covers sync **and**
queued writes), `patient()`/`visit()` relations, and a `forPatient()` scope (new
columns **or** legacy `subject = Patient`, so pre-backfill rows still surface).
Wired via `config/activitylog.php → activity_model`.

## Migration

`add_patient_visit_context_to_activity_log` — nullable **indexed** `patient_id`
and `visit_id` columns (no FK, so logs are durable). Verified on real MariaDB 10.1.

## Patient profile log query fixed

`PatientController::show` now calls
`ActivityLogService::getPatientTimeline($patient)` → every connected action across
all modules, plus merged-folder history. The existing Blade tab is unchanged
(same `$activityLogs` shape).

## Backfill

`php artisan logs:backfill-context` populates `patient_id`/`visit_id` on existing
rows from properties then subject. Dev run: **343** historical patient logs
recovered onto patient profiles.

## Audit / guardrail command

`php artisan logs:audit [--json] [--module=] [--fail]` scans controllers for
mutating actions lacking a logging marker. Initial run flags **75** controllers
(heuristic — see remaining-TODOs).

## UI updates

Patient profile Activity tab now shows cross-module activity (no markup change
needed). Raw JSON stays out of the list; old/new values remain in the detail
area gated by permission.

## Permissions added

`logs.view_patient`, `logs.view_details`, `logs.view_sensitive`, `logs.delete`
(joining existing `logs.view/view_clinical/view_financial/view_stock/view_security/
export/manage_retention`).

## Tests added

`tests/Feature/ActivityLogContextTest.php` (**9 passing**): context attachment
from Visit & Invoice subjects; user/module/action/reason/old/new captured;
sensitive masking; timeline spans all modules; merged-duplicate history; legacy
subject rows; backfill; `logs:audit` runs + JSON. Logging-heavy regression suites
(billing/workflow/module/reason — 29 tests) stay green after the `activity_model`
swap.

## Files modified / added

**Added:** `app/Models/ActivityLog.php`; `app/Services/ActivityContextResolver.php`;
`app/Console/Commands/{BackfillActivityLogContextCommand,LogsAuditCommand}.php`;
migration `2026_06_03_000002_add_patient_visit_context_to_activity_log.php`;
`tests/Feature/ActivityLogContextTest.php`; the four `docs/LOGGING_*` reports.
**Edited:** `app/Services/ActivityLogService.php` (context + timeline API),
`config/activitylog.php` (activity_model), `app/Http/Controllers/Admin/PatientController.php`
(timeline query), `database/seeders/RoleSeeder.php` (+4 permissions).
