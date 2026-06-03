# Patient Profile Log — Fix Report

## Why the patient profile logs were empty/incomplete

The profile fetched **only** logs whose subject is the Patient row:

```php
Activity::where('subject_type', Patient::class)->where('subject_id', $patient->id)
```

Almost every patient action is logged against a **different** subject — a `Visit`
(consultation, diagnosis), an `Invoice`/`Payment` (billing), an `EmergencyCase`,
an `Admission`, a `LabRequest`, etc. None of those matched `subject = Patient`, so
the folder showed next to nothing. And because patient context (where present)
sat in the `properties` **longText**, it could not be queried on **MariaDB 10.1**
(no `JSON_EXTRACT`).

## How patient-related logs are now discovered

1. **Indexed columns** `patient_id` / `visit_id` were added to `activity_log`.
2. **Auto-attachment:** `ActivityLogService::log()` runs every log through
   `ActivityContextResolver`, which derives the patient/visit from the subject
   (Visit→patient, Invoice→patient, …) and stores them in the columns (via the
   `ActivityLog` model `saving` hook — works for sync and queued writes).
3. **Timeline query:** `getPatientTimeline()` selects
   `WHERE patient_id IN (…)` (indexed) `OR subject = Patient` (legacy rows).

## patient_id linkage rules

| Subject | patient_id | visit_id |
|---------|-----------|----------|
| Patient | its id | — |
| Visit | visit.patient_id | its id |
| Invoice / InvoiceItem / EmergencyCase / Admission* / MedicalRecord / LabRequest | model.patient_id (or via visit) | model.visit_id |
| Payment (invoice_id only) | invoice.patient_id | invoice.visit_id |
| any model with `visit_id` only | resolved from the visit | model.visit_id |

\*Admission links to the patient through its visit; resolved automatically.

## Merge handling

`patientIdsFor()` = the patient **plus** every duplicate folder merged into it
(`patients.merged_to_patient_id = patient.id`). So after a merge, the duplicate's
historical activity appears on the surviving (main) folder, and the merge event
itself is logged. No duplicate activity is lost.

## Modules included in the patient timeline

Any module that logs through `ActivityLogService` with a patient/visit-bearing
subject now appears: patients, visits, consultation, emergency, admission, MAR,
pharmacy, investigations, procedures/theatre, billing/payments, claims, blood
bank, stock/consumables (when tied to a patient/visit), insurance, documents,
merge, deceased marking, corrections, overrides, and system events.

## Existing data

`php artisan logs:backfill-context` recovered **343** historical patient logs onto
profiles (run it once on deploy).

## Manual verification steps

1. Open a patient with prior activity → **Activity Log** tab now lists
   consultation/billing/pharmacy/etc. events, not just patient-row edits.
2. Edit the patient's phone → entry with old/new values in the detail view.
3. Create a visit, start consultation, add a diagnosis, record a payment, dispense
   a drug → each appears on the patient timeline with the right module badge and
   user.
4. Merge a duplicate folder → the duplicate's history shows on the main folder and
   a merge event is recorded.
5. A user without `logs.view_sensitive` should not see masked/sensitive detail.
