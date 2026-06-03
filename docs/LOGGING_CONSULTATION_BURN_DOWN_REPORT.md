# Logging Burn-Down — Consultation Clinical Entries

Module-focused burn-down. No new logging system, no parallel table, no clinical
business-logic change. The fix routes the existing clinical-entry funnel into the
central activity log so consultation actions appear on the patient timeline.

## 1. Write paths inspected

- **`Doctor/ConsultationController`** — store/update/destroy for complaint, HOPC,
  examination, diagnosis, investigation, treatment, prescription, procedure
  request, lab request; session start/complete/cancel; route store.
- **Services** — `ConsultationService` (add/update/delete complaint, diagnosis,
  investigation, treatment), `PatientComplaintService`, `HistoryOfPresentingComplaintService`,
  `PhysicalExaminationService`, `PrescriptionService`, `ConsultationRouteService`
  (activate/complete/cancel), `MedicalPatternService` (applyPattern).
- **Existing logging** — clinical entry create/update/delete already funnelled
  through **`MedicalRecordEntryLogService`** (via `afterEntryCreated`, the section
  services, and the controller), but it wrote **only** to its own
  `medical_record_entry_logs` table — never to `activity_log`, so the patient
  timeline never saw it. Same class of root cause as before.

## 2. Actions now logged (to activity_log → patient timeline)

| Entry | Create | Update | Delete | Correct (locked) |
|-------|:------:|:------:|:------:|:----------------:|
| Complaint | ✅ | ✅ | ✅ | ✅ |
| HOPC | ✅ | ✅ | ✅ | ✅ |
| Examination | ✅ | ✅ | ✅ | ✅ |
| Diagnosis | ✅ | ✅ | ✅ | ✅ |
| Treatment | ✅ | ✅ | ✅ | ✅ |
| Prescription | ✅ | ✅ | — | ✅ |
| Investigation (consultation entry) | ✅ | ✅ | ✅ | ✅ |

Events: `COMPLAINT_ADDED/UPDATED/REMOVED/CORRECTED`, `DIAGNOSIS_*`, `HOPC_*`,
`EXAMINATION_*`, `TREATMENT_*`, `PRESCRIPTION_*`, `INVESTIGATION_*` (module
`CONSULTATION`). Plus session lifecycle `SESSION_STARTED/RESUMED/COMPLETED` and
`PATTERN_APPLIED`.

**How:** a single change — `MedicalRecordEntryLogService::write()` now mirrors
every entry change to `ActivityLogService` with full context — surfaces all of
the above at once. Correction (locked-session) edits map to the `*_CORRECTED`
action with `severity=WARNING` and the reason.

## 3. Context fields included

`patient_id`, `visit_id`, `medical_record_id`, `consultation_route_id`,
`department_id`, `source_type` (entry class), `source_id`, plus `old_values` /
`new_values` (changed clinical fields only — timestamps/ids/context stripped) and
`reason`. A human description is built per entry (e.g. *“Diagnosis corrected:
Typhoid fever”*). `consultation_route_id` was added to the persisted context keys.

## 4. Patient timeline verification

`ActivityLogService::getPatientTimeline()` now returns consultation entries (the
existing query already keys on the indexed `patient_id`). Verified by tests that
the complaint/diagnosis/treatment/session/pattern events appear with module
`CONSULTATION`, correct user, description, and old/new values — no raw JSON in the
list.

## 5. Tests added/passing

- `tests/Feature/ConsultationClinicalLogTest.php` (**7**) — complaint add/update/
  delete with old/new values; diagnosis add + correction; pattern application
  (`PATTERN_APPLIED` + the created complaint under the current user); no duplicate
  logs; CONSULTATION module on timeline.
- `tests/Feature/ConsultationRouteSessionWorkflowTest.php` — added a
  `SESSION_STARTED` timeline assertion through the real controller→service flow.
- Regression: 61 tests across consultation/emergency/patient/logging suites pass;
  no duplicate logs introduced.

## 6. logs:audit before/after

`75 → 74` controllers flagged. The audit now recognises the clinical funnel
(`MedicalRecordEntryLog` / `entryLogs->` / `timeline->record` markers added), so
`Doctor/ConsultationController` is **no longer a false positive** (`--module=Consultation`
→ 0 flagged).

## 7. Remaining consultation logging TODOs

- **Pattern-created non-complaint records**: `applyPattern` direct-creates HOPC/
  examination/diagnosis/treatment/etc. (bypassing the section services), so those
  individual records aren't yet logged (the `PATTERN_APPLIED` summary is). Route
  `applyPattern` through the section services to log each, or call `entryLogs->created`.
- **Prescription delete** (`destroyPrescription`) and **clinical task / follow-up**
  create/complete don't funnel through `MedicalRecordEntryLogService` yet.
- **ConsultationNote / session summary** updates — funnel through the entry-log
  service if/when a note model is used.
- **Session lock/reopen / contributor added** — add explicit logs (lifecycle done
  for start/resume/complete; cancel already has a route-status log).

## 8. Files modified

`app/Services/MedicalRecordEntryLogService.php` (dual-write — the core change),
`app/Services/ConsultationRouteService.php` (session start/resume/complete logs),
`app/Services/MedicalPatternService.php` (`PATTERN_APPLIED`),
`app/Services/ActivityLogService.php` (`consultation_route_id` context key),
`app/Console/Commands/LogsAuditCommand.php` (recognise clinical funnel),
`tests/Feature/ConsultationClinicalLogTest.php` (new),
`tests/Feature/ConsultationRouteSessionWorkflowTest.php` (session assertion).
