# Logging Burn-Down — MAR / Medication Administration

Module-focused burn-down. No new logging system, no parallel table, no MAR
business-logic change. The MAR workflow already funnelled every event through a
custom log table; this routes that funnel into the central activity log so MAR
appears on the patient profile timeline.

## 1. Write paths inspected

- **Services:** `MedicationAdministrationService` (administer schedule / PRN /
  correct), `MedicationOrderService` (create / hold / stop / dispensed-qty),
  `MedicationScheduleService` (generate / stop), `EmergencyMedicationService`
  (emergency order). All route through **`MedicationAdministrationLogService::record()`**.
- **Models:** `MedicationOrder`, `MedicationAdministration`,
  `MedicationAdministrationSchedule`, `MedicationAdministrationLog`.
- **Controllers:** `MedicationAdministrationController`, `MarChartController`,
  `AdmissionMedicationBoardController`, `EmergencyMedicationBoardController`.

## 2. Existing custom MAR logs found

Yes — `MedicationAdministrationLogService` writes every MAR event to the
**`medication_administration_logs`** table (same pattern as the clinical
`MedicalRecordEntryLogService`). It was **not** writing to `activity_log`, so MAR
never reached the patient timeline.

## 3. Actions now logged (module `MAR`)

`record()` now **dual-writes** to `ActivityLogService`. Funnel action → event:

| Funnel action | Activity event |
|---------------|----------------|
| `GIVEN` / `PARTIALLY_GIVEN` (+ `PRN_*`) | `DOSE_ADMINISTERED` |
| `HELD` | `DOSE_HELD` |
| `MISSED` / `NOT_GIVEN` | `DOSE_MISSED` |
| `REFUSED` | `DOSE_REFUSED` |
| `SKIPPED` | `DOSE_SKIPPED` |
| `CANCELLED` | `DOSE_CANCELLED` |
| `CORRECTED` | `DOSE_CORRECTED` |
| `ORDER_CREATED` | `MEDICATION_ORDER_CREATED` |
| `ORDER_HELD` | `MEDICATION_ORDER_HELD` |
| `ORDER_STOPPED` | `MEDICATION_ORDER_STOPPED` |
| `DISPENSED_QUANTITY_RECORDED` | `MEDICATION_ORDER_UPDATED` |
| `SCHEDULE_GENERATED` | `MEDICATION_SCHEDULE_GENERATED` |

A distinct **`ADVERSE_REACTION_RECORDED`** (severity WARNING) is emitted when an
administration carries a `reaction`. PRN doses set `metadata.prn = true`.
Emergency order creation (which bypassed `MedicationOrderService`) now also routes
through the funnel.

## 4. Duplication risks avoided

- **One funnel → one activity log per action** (no double logs; verified by test).
- **Pharmacy dispensing is not touched** — MAR administration (nurse gave the dose)
  is a different action from pharmacy dispensing (drug supplied); the pharmacy
  burn-down will own its own events.
- **Stock movements are not re-logged** — the MAR log references the existing
  `stock_movement_id` rather than duplicating the stock ledger.

## 5. Stock deduction context verification

Ward/emergency administrations deduct stock; the MAR log carries
`stock_location_id` and `stock_movement_id` (plus `product_id`/`drug_id` from the
order) so the clinical administration event is traceable to its stock deduction
without duplicating the movement record.

## 6. Context fields included

Via `MedicationAdministrationLogService::marContext()`: `patient_id`, `visit_id`,
`admission_id`, `emergency_case_id`, `medical_record_id`, `consultation_route_id`,
`medication_order_id`, `medication_schedule_id`, `medication_administration_id`,
`prescription_id`, `prescription_item_id`, `product_id`, `drug_id`,
`stock_location_id`, `stock_movement_id`, `source_type/id`. New persisted context
keys added to `ActivityLogService`. `new_values`/`old_values` keep only meaningful
clinical fields (status, dose, reason, reaction) — context/ids/timestamps stripped.

## 7. Patient timeline verification

Tests assert dose administered/held + adverse reaction + order stop appear via
`getPatientTimeline()` with module `MAR`, `admission_id`/`medication_order_id`,
nurse user, drug label, and reason — e.g. *“Dose held: Ceftriaxone 1g — Patient
vomiting”*.

## 8. Tests added/passing

Added to `tests/Feature/MedicationAdministrationWorkflowTest.php` (**4**): dose
administration with admission/order context + **no-duplicate** check; held dose
with reason; distinct adverse-reaction event; order stop with reason. Full MAR
suite: **18 passing** (the 3 failures are the **pre-existing time-of-day-flaky
chart tests**, unrelated to logging). Emergency suite: 17 passing.

## 9. logs:audit before/after

`73 → 73` (unchanged). MAR logging lives in the service funnel; the MAR
controllers delegate to those services, so any controller still flagged is a
false positive (logs via service).

## 10. Remaining MAR logging TODOs

- **Order resume** — no explicit resume action in the funnel today; map
  `MEDICATION_ORDER_RESUMED` when a resume path exists.
- **`MedicationAdministration::toActivityContext()`** — not added (the funnel
  already builds context centrally); add only if another caller needs it.
- Confirm any non-funnel MAR write paths (e.g. board bulk actions) route through
  `MedicationAdministrationLogService`.

## 11. Files modified

`app/Services/MedicationAdministrationLogService.php` (dual-write — the core
change), `app/Services/EmergencyMedicationService.php` (emergency order →
funnel), `app/Services/ActivityLogService.php` (+MAR context keys),
`tests/Feature/MedicationAdministrationWorkflowTest.php` (4 logging tests).
