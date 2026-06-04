# Logging Burn-Down — Emergency / Admission

Module-focused burn-down. Reuse-first: emergency case creation and admission
admit/discharge were already logged; the gaps were **emergency triage and
disposition**. Care was taken **not** to dual-write the emergency timeline (which
carries module-owned events) to avoid duplicating MAR/pharmacy/investigation/
procedure/consumable logs.

## 1. Write paths inspected

- **Emergency:** `EmergencyCaseService` (create), `EmergencyTriageService` (record),
  `EmergencyDispositionService` (dispose), `EmergencyBayService`,
  `EmergencyVitalsService`, `EmergencyTimelineService` (emergency-specific timeline),
  plus medication/consumable/investigation/procedure emergency services (each owned
  by its own module).
- **Admission:** `AdmissionService` (admit / discharge / ward round), bed/ward services.

## 2. Existing emergency/admission logs found (REUSED, not duplicated)

| Already logged | By |
|----------------|----|
| `EMERGENCY / CASE_CREATED` | `EmergencyCaseService::create` |
| `ADMISSION / ADMITTED`, `ADMISSION / DISCHARGED` | `AdmissionService` |
| Emergency medication / consumable / investigation / procedure | MAR / ConsumableUsage / Lab / Procedure modules |
| Emergency clinical entries | consultation funnel (`MedicalRecordEntryLogService`) |

**None duplicated.** The emergency timeline (`EmergencyTimelineService`) was
deliberately **not** dual-written, since it records events those modules already own.

## 3. Actions now logged (the gaps)

- **`EmergencyTriageService::record`** → `TRIAGE_RECORDED`, or **`TRIAGE_OVERRIDDEN`**
  (severity WARNING, old/new category + override reason) when the final category
  differs from the automated one.
- **`EmergencyDispositionService::dispose`** → a disposition-specific EMERGENCY event:
  `EMERGENCY_TRANSFERRED_TO_ADMISSION` / `_TO_THEATRE` / `_TO_OPD`,
  `EMERGENCY_REFERRED_OUT`, `EMERGENCY_LEFT_AGAINST_MEDICAL_ADVICE`,
  `EMERGENCY_ABSCONDED`, `EMERGENCY_DEATH_RECORDED`, `EMERGENCY_DOA_RECORDED`, or
  `EMERGENCY_DISPOSITION_COMPLETED` (discharged) — with old/new status + disposition
  notes as the reason.

## 4. Duplication risks avoided

- **MAR / Pharmacy / Investigations / Procedures / Consumables / Billing** each keep
  ownership of their events; this burn-down added **only** triage + disposition.
- The emergency **timeline** was not dual-written (it carries module-owned events).
- Admission admit/discharge were **reused** (already logged), not re-added.

## 5. Emergency → admission boundary (clean, no duplicate)

Two distinct workflow events:

```
EMERGENCY / EMERGENCY_TRANSFERRED_TO_ADMISSION   (EmergencyDispositionService::dispose)
ADMISSION / ADMITTED                             (AdmissionService::admit)
```

Verified by test: a dispose-to-admission produces `EMERGENCY_TRANSFERRED_TO_ADMISSION`;
the later admission creates `ADMITTED` — no duplicate identical rows.

## 6. Bed / ward context

Emergency bay/bed and admission ward/bed transfer events are **not yet** added (see
§12) — initial bed assignment is captured in `ADMITTED` (admission carries `bed_id`),
and emergency bay is in the case context (`emergency_bay_id`). New context keys
added: `ward_id`, `bed_id`, `emergency_bay_id` (`emergency_case_id`/`admission_id`
were already present).

## 7. Module ownership boundaries

EMERGENCY logs the emergency case lifecycle (triage/disposition); ADMISSION logs the
inpatient lifecycle (admit/discharge); MAR/Pharmacy/Investigations/Procedures/Billing/
Stock each own their own events. No cross-module duplication.

## 8. Context fields included

Via `EmergencyCase::toActivityContext()` (patient/visit/emergency_case/admission/bay)
and `Admission::toActivityContext()` (patient/visit/admission/bed), plus old/new
status, reason, and metadata. Triage carries `triage_score`; disposition carries
the disposition code.

## 9. Patient timeline verification

Test asserts `TRIAGE_OVERRIDDEN` (module EMERGENCY, `emergency_case_id`, reason,
old/new category) and `EMERGENCY_TRANSFERRED_TO_ADMISSION` (patient_id, disposition
metadata) appear via `getPatientTimeline()`.

## 10. Tests added/passing

Added to `tests/Feature/EmergencyCaseManagementTest.php`: triage-override +
disposition-to-admission timeline test. Full suite: emergency + admission
**22 passing** — no break, no duplicate.

## 11. logs:audit before/after

`73 → 73`. Emergency/admission logging lives in service funnels; controllers
delegate, so any controller flagged is a false positive.

## 12. Remaining Emergency/Admission logging TODOs

- **Emergency bay/bed assignment & release** (`EmergencyBayService`) — add
  `EMERGENCY_BAY_ASSIGNED` / `EMERGENCY_BED_RELEASED`.
- **Emergency vitals** (`EmergencyVitalsService` / `Vital`) — add
  `EMERGENCY_VITALS_RECORDED` (carries `emergency_case_id`) if a vitals activity log
  is wanted (currently timeline-only).
- **Emergency team / contributor** add/remove.
- **Admission bed transfer / release / ward change** — add `BED_TRANSFERRED` /
  `BED_RELEASED` / `WARD_CHANGED` at the bed/ward service funnel.
- **Admission cancel, discharge initiated/summary, nursing notes** (where not already
  via the clinical funnel).
- **Emergency/admission/discharge summary print** — add `*_SUMMARY_PRINTED`.
- (Pre-existing) the disposition visit-transition writes `visit_status_logs.changed_by`
  from `Auth::id()`; service-level callers must run within an authenticated context.

## 13. Files modified

`app/Services/EmergencyTriageService.php` (triage log), `app/Services/EmergencyDispositionService.php`
(disposition log + `dispositionEvent` helper), `app/Models/EmergencyCase.php` +
`app/Models/Admission.php` (`toActivityContext()`), `app/Services/ActivityLogService.php`
(+ward_id/bed_id/emergency_bay_id context keys), `tests/Feature/EmergencyCaseManagementTest.php` (1 test).
