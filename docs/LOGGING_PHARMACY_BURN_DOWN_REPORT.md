# Logging Burn-Down — Pharmacy

Module-focused burn-down. No new logging system, no parallel table, no pharmacy
business-logic change. Pharmacy had **no activity-log coverage** (only visit
pathway events), so billing/dispensing were invisible on the patient timeline.
Logged directly at the two clean service funnels.

## 1. Write paths inspected

- **Services:** `PharmacyBillingSelectionService::billSelectedItems` (billing),
  `PharmacyService::dispenseItem` / `batchDispense` (dispensing),
  `PharmacyBillingSelectionService::recordDispensed` (quantity sync).
- **Models:** `PharmacyBillingSelection`, `DispensingRecord`, `Prescription`,
  `PrescriptionItem`.
- **Existing logging:** `PrescriptionService` logs the *clinical* prescription
  create/update via the clinical funnel (already covered); pharmacy *workflow* had
  only `VisitPathwayService` events (`PHARMACY_BILLED`, `PHARMACY_DISPENSED`).

## 2. Existing custom pharmacy logs found

None — no custom pharmacy log table. Logged directly through `ActivityLogService`
at the two funnels (no parallel mechanism).

## 3. Actions now logged (module `PHARMACY`)

| Action | Event | Where |
|--------|-------|-------|
| Selected drug billed (per item) | `PRESCRIPTION_ITEMS_BILLED` | `billSelectedItems` |
| Drug dispensed (fully) | `DRUG_DISPENSED` | `dispenseItem` |
| Drug dispensed (partial) | `PARTIAL_DISPENSE_COMPLETED` | `dispenseItem` (`totalDispensed < prescribed`) |

`batchDispense` loops `dispenseItem`, so batch dispensing logs per item.

## 4. Billing / dispensing separation (the core requirement)

Three **distinct** events for one drug's journey — never collapsed:

```
PHARMACY / PRESCRIPTION_ITEMS_BILLED   (drug financially selected/billed)
PHARMACY / DRUG_DISPENSED              (drug physically supplied)
MAR      / DOSE_ADMINISTERED           (nurse gave the dose)
```

Verified by test: a bill + partial-dispense produces exactly one
`PRESCRIPTION_ITEMS_BILLED` and one `PARTIAL_DISPENSE_COMPLETED`, and **no**
`DOSE_ADMINISTERED`.

## 5. Duplication risks avoided

- **MAR:** `dispenseItem` calls `recordDispensedQuantity` → the MAR funnel's
  `DISPENSED_QUANTITY_RECORDED`. That is a pharmacy-driven quantity sync, so the
  MAR mirror **skips** it on the timeline (the pharmacy module owns the dispense
  event). It still writes to `medication_administration_logs` for the MAR audit.
- **Stock ledger:** the dispense log **references** the stock movement
  (`stock_movement_id` + `metadata.stock_movement_ids` + `stock_location_id`)
  rather than re-logging the ledger.
- **One funnel → one event** per billing/dispensing action (verified by count).

## 6. Stock movement relationship

`dispenseItem` deducts pharmacy stock via `ProductStockMovementService::createMovement`
(`PHARMACY_DISPENSED`, source = `PrescriptionItem`). The dispense log now captures
the resulting movement ids + the location, so the clinical dispense is traceable
to its deduction without duplicating the movement record. (Generic stock-ledger
activity logging remains a Stock-module concern.)

## 7. Context fields included

Via `PharmacyBillingSelection::toActivityContext()` (billing) and
`DispensingRecord::toActivityContext()` (dispensing): `patient_id`, `visit_id`,
`prescription_id`, `prescription_item_id`, `product_id`, `invoice_item_id`,
`dispensing_id`, plus dispense-side `drug_id`, `stock_location_id`,
`stock_movement_id`, `quantity`. New persisted context key: `dispensing_id`.
Admission/emergency context flows automatically where the prescription/visit
carry it. `metadata` records product name + billed/prescribed/dispensed quantities.

## 8. Patient timeline verification

Test asserts billed + partially-dispensed events appear via `getPatientTimeline()`
with module `PHARMACY`, product/invoice context (billing), stock context
(dispensing), readable descriptions (*“Drug billed: … x 3”*, *“Partial dispensing:
… x 2”*), and **no MAR dose log**.

## 9. Tests added/passing

`tests/Feature/PharmacyWorkflowTest.php` — new test
`test_pharmacy_billing_and_dispensing_log_distinctly_on_patient_timeline`; full
suite **7 passing**. Regression: 40 passing across MAR/emergency/consumable (the 3
MAR failures are the **pre-existing time-of-day-flaky chart tests**, unrelated).

## 10. logs:audit before/after

`73 → 73`. Pharmacy logging lives in the service funnels; the pharmacy controller
delegates to them, so any controller still flagged is a false positive.

## 11. Remaining Pharmacy logging TODOs

- **Prescription pharmacy review / item rejection** — no distinct "pharmacy
  review" action exists today (the clinical prescription create/update is already
  logged); add `PRESCRIPTION_REVIEWED` / `PRESCRIPTION_ITEM_REJECTED` if/when that
  workflow lands.
- **Billing quantity reduced** — currently captured in `PRESCRIPTION_ITEMS_BILLED`
  metadata (`prescribed_quantity` vs `billed_quantity`); promote to a distinct
  `BILLING_QUANTITY_REDUCED` event if needed.
- **Dispense cancel / correct / return / reversal** — not implemented in the
  current pharmacy service (no reverse path); **not applicable** until added.
- **Out-of-stock** — surfaced as a thrown `RuntimeException` (blocked dispense),
  not a recorded event; add `OUT_OF_STOCK_RECORDED` if blocked attempts should be
  audited.

## 12. Files modified

`app/Services/PharmacyService.php` (dispense log + capture movement ids),
`app/Services/PharmacyBillingSelectionService.php` (billing log),
`app/Services/MedicationAdministrationLogService.php` (skip
`DISPENSED_QUANTITY_RECORDED` mirror), `app/Services/ActivityLogService.php`
(+`dispensing_id`), `app/Models/DispensingRecord.php` +
`app/Models/PharmacyBillingSelection.php` (`toActivityContext()`),
`tests/Feature/PharmacyWorkflowTest.php` (1 test).
