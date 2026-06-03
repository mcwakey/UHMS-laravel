# Logging Burn-Down — Investigations

Module-focused burn-down of the investigation (lab) **department lifecycle**. No
new logging system, no parallel table, no business-logic change. The lab workflow
already recorded **visit-pathway** events but never wrote to `activity_log`, so it
was invisible on the patient profile timeline. This adds the central activity log
alongside the existing pathway records.

## 1. Write paths inspected

- **Controllers:** `Lab/LabRequestController` (accept, acceptSelected, cancel),
  `Lab/LabResultController` (store/result entry, batch, verify, print).
- **Services (the funnels):** `LabService` (createRequest, acceptRequest,
  cancelRequest, enterResult, verifyResult, batchEnterResults),
  `InvestigationRequestService::acceptSelectedItems`, `EmergencyInvestigationService`
  (→ `LabService::createRequest` with `emergency_case_id`).
- **Existing logging:** only `VisitPathwayService` events (visit timeline) — **nothing in `activity_log`**.

## 2. Actions now logged (module `INVESTIGATION`)

| Action | Event | Where |
|--------|-------|-------|
| Direct/emergency request created | `INVESTIGATION_REQUESTED` | `LabService::createRequest` (guarded) |
| Request accepted | `INVESTIGATION_ACCEPTED` | `LabService::acceptRequest` |
| Selected items accepted | `INVESTIGATION_ACCEPTED` | `InvestigationRequestService::acceptSelectedItems` |
| Request cancelled | `INVESTIGATION_CANCELLED` | `LabService::cancelRequest` |
| Result entered | `RESULT_ENTERED` | `LabService::enterResult` (new) |
| Result updated | `RESULT_UPDATED` | `LabService::enterResult` (`updateOrCreate` → existing) |
| Result verified | `RESULT_VERIFIED` | `LabService::verifyResult` |
| Verified result printed | `RESULT_PRINTED` | `LabResultController::print` |

`batchEnterResults` loops `enterResult`, so batch entry logs per item with no
extra code. Entered-vs-updated is distinguished via `wasRecentlyCreated`.

## 3. Duplication risks avoided

- **Consultation-created requests:** `createRequest` logs `INVESTIGATION_REQUESTED`
  **only when `consultation_route_id` is null** (direct OPD / emergency). Requests
  raised inside a consultation are already logged as a `CONSULTATION` investigation
  entry, so the lab service skips them. Verified by test.
- **Consumables:** investigation consumable usage stays with `ConsumableUsageService`
  (`STOCK / CONSUMABLE_USED`); not re-logged here.
- **Billing:** invoice/payment logs are untouched; only lab workflow states are logged.

## 4. Context fields included

Via `LabRequest::toActivityContext()`: `patient_id`, `visit_id`,
`medical_record_id`, `consultation_route_id`, `emergency_case_id`, `department_id`
(target dept preferred), `investigation_request_id`, `sample_id`,
`source_type`/`source_id`. Result logs add `investigation_result_id`, `service_id`,
`invoice_item_id`. New persisted context keys: `investigation_result_id`,
`sample_id`, `service_id`. Result `new_values` store a **summary** only
(result_value / truncated rich-text / file name + `is_abnormal`) — never raw files
or large report bodies.

## 5. Patient timeline verification

Tests assert request/accept/result/verify/cancel/update events appear via
`getPatientTimeline()` with module `INVESTIGATION`, the request/result ids, and a
readable description (e.g. *“Result entered: Full Blood Count”*). Emergency
requests carry `emergency_case_id` (from `EmergencyInvestigationService`).

## 6. Tests added/passing

`tests/Feature/InvestigationLogTest.php` (**4**): full lifecycle on the timeline
with ids/description; result update old→new value; cancellation; and
**consultation-created request does not duplicate** the request log. Regression:
34 tests across inventory/consumable/emergency suites pass.

## 7. logs:audit before/after

`74 → 73`. `--module=Lab` → **1** remaining: `LabRequestController` — a **false
positive** (it logs via `LabService`/`InvestigationRequestService`; the heuristic
only inspects controller source). `LabResultController` is recognised (it logs the
print directly).

## 8. Remaining investigation logging TODOs

- **Sample collection / receipt / rejection** — the current lab model has no
  explicit sample-collection step (`sample_id` exists on the request but there is
  no `collectSample` action); add `SAMPLE_COLLECTED/RECEIVED/REJECTED` if/when that
  workflow lands.
- **Result rejection / correction-requested** — no reject-result action exists yet
  in `LabService`; add `RESULT_REJECTED` + reason when implemented.
- **Verification reversal** — not currently supported.
- **Radiology/imaging** — if a separate imaging path exists outside `LabService`,
  wire it the same way.

## 9. Files modified

`app/Services/LabService.php` (5 lifecycle logs), `app/Services/InvestigationRequestService.php`
(acceptSelectedItems log), `app/Http/Controllers/Lab/LabResultController.php`
(`RESULT_PRINTED`), `app/Models/LabRequest.php` (`toActivityContext()`),
`app/Services/ActivityLogService.php` (+`investigation_result_id`, `sample_id`,
`service_id` context keys), `tests/Feature/InvestigationLogTest.php` (new).
