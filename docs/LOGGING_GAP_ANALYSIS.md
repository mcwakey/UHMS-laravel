# Logging / Audit Trail — Gap Analysis

## Current logging architecture

- **Spatie Activitylog** is the single logging backend. Table `activity_log`
  (`log_name`, `description`, `event`, `subject_*` morph, `causer_*` morph,
  `properties` **longText**, `batch_uuid`, timestamps).
- **`App\Services\ActivityLogService`** is the central entry point (used in **28
  files**: services, observers, controllers, listeners). It builds properties
  (module/action/severity/context/old/new/reason/ip/ua), masks sensitive fields,
  supports an async queue path (`ProcessActivityLogJob`), and exposes
  `log/logCreated/logUpdated/logDeleted/logCorrection/logOverride/logSecurity`.
- Observers exist for `Invoice`, `Payment`, `User`; auth events via `LogAuthEvents`.
- Visit pathway (`visit_pathway_events`) is a **separate, clinical** timeline.

## Log tables found

`activity_log` (spatie) + `visit_status_logs` + `visit_pathway_events`. No second
general-purpose audit table — good, no consolidation needed.

## Patient profile log implementation found

`PatientController::show` ran:

```php
Activity::where('subject_type', Patient::class)->where('subject_id', $patient->id)…
```

## Modules/actions currently logged

Broad service-level coverage already exists: billing (invoice/payment/credit/
override), admission, emergency, blood bank (donor/donation/crossmatch/issue/
request), claims, auth, plus `Invoice`/`Payment`/`User` observers.

## Modules/actions missing logs

`logs:audit` flags **75 controllers** with mutating actions and no direct logging
marker (many delegate to services that DO log; the rest are genuine gaps — see
`docs/LOGGING_REMAINING_TODOS.md`).

## Root causes (why patient profile logs were empty/incomplete)

1. **CRITICAL — wrong query.** The profile only fetched logs whose **subject is the
   Patient row**. Actions logged against `Visit`/`Invoice`/`EmergencyCase`/… (the
   overwhelming majority of patient activity) were invisible.
2. **Context not queryable.** Where `patient_id` was recorded it lived inside the
   `properties` **longText**. **MariaDB 10.1 has no `JSON_EXTRACT`** (added in
   10.2.3), so `properties->patient_id` cannot be queried — there was no way to
   fetch "all logs for patient X" from the DB.
3. **Context not always attached.** Many callers never passed `patient_id`, so even
   the JSON had nothing to find.

## Logs missing patient_id / visit_id

Before the fix: **all** of them at the column level (no such columns existed).
After backfilling dev: of 642 rows lacking patient context, **343** were
patient-related and got `patient_id`/`visit_id`; the remaining ~299 are
system/auth/admin logs with no patient (correct).

## Duplicate log mechanisms

None to consolidate — one backend (spatie) + a separate clinical pathway.

## Risk level

**High (traceability)** before the fix — patient activity was effectively
untraceable from the folder. **Low** after: the timeline now queries indexed
columns and surfaces all connected activity.
