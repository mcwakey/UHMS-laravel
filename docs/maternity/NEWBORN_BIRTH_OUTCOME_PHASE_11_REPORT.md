# Newborn Birth Outcome Phase 11 Report

## What Was Implemented

Phase 11 adds newborn records and birth outcome workflow linked to delivery records, labor episodes, pregnancy profiles, maternity cases, mother patients, visits, admissions, and optional newborn patient records.

UHMS can now:

- Create newborn records from completed or recorded delivery records.
- Record one newborn at a time.
- Bulk create placeholder newborn records from the delivery `newborn_count`.
- Support twins/triplets as separate newborn records.
- Prevent duplicate birth order within the same delivery.
- Record newborn demographics, birth details, APGAR scores, measurements, outcome, condition, status, risk flags, and danger signs.
- Store resuscitation required/details.
- Track delivery-level newborn pending/completion state.
- Explicitly link an existing patient record or create a minimal newborn patient record when safe and permitted.
- Show newborn summaries on delivery, labor, pregnancy profile, and maternity dashboard screens.

## Existing Labor / Delivery Foundation Audited

Reviewed before implementation:

- `PregnancyProfile`, `MaternityCase`, `AntenatalVisit`, `LaborEpisode`, `LaborObservation`, and `DeliveryRecord`.
- Delivery completion and `newborn_records_pending`.
- Maternity dashboard labor/delivery metrics.
- Pregnancy profile, labor episode, and delivery record screens.
- Patient model fields, patient numbering service, and patient creation requirements.
- Admission/visit relationships.
- Activity logging with `LogModule::MATERNITY`.
- Existing permission and EN/FR localisation conventions.

## Files Changed

- Added newborn enums, model, migration, services, controller, views, tests, and documentation.
- Updated delivery, labor, pregnancy profile, maternity case, patient, visit, and admission relationships.
- Updated maternity overview service and dashboard.
- Updated delivery record detail, labor episode detail, and pregnancy profile detail views.
- Updated role seeder permissions.
- Updated EN/FR maternity translations.

## New Migration / Table

Added `database/migrations/2026_07_04_000010_create_newborn_records_table.php`.

New table: `newborn_records`

Stores delivery/labor/profile context, mother patient, optional newborn patient, birth order, sex, birth time, birth weight, measurements, APGAR scores, resuscitation, congenital concerns, feeding/breathing/cord status, risk flags, danger signs, neonatal condition, outcome, status, transfer destination, notes, authorship, closure fields, timestamps, and soft deletes.

Birth order is unique per delivery.

## New Models And Enums

Model:

- `NewbornRecord`

Enums:

- `NewbornRecordStatus`
- `NewbornOutcome`
- `NewbornSex`
- `NewbornFeedingStatus`
- `NewbornBreathingStatus`
- `NewbornCordStatus`
- `NewbornCondition`
- `NewbornRiskFlag`
- `NewbornDangerSign`

## New Services

- `NewbornRecordService`
- `NewbornRiskAssessmentService`
- `NewbornOverviewService`

Risk rules are advisory and do not block clinical action.

## New Routes / Controllers

Controller:

- `NewbornRecordController`

Routes added under `admin.maternity.*`:

- Delivery newborn list/create/store
- Delivery newborn bulk-create
- Newborn show/edit/update
- Newborn status update
- Newborn close
- Newborn link patient
- Newborn create patient

## New Permissions

Added:

- `maternity.newborn.view`
- `maternity.newborn.record`
- `maternity.newborn.update`
- `maternity.newborn.close`
- `maternity.newborn.link_patient`
- `maternity.newborn.create_patient`
- `maternity.newborn.risk.manage`
- `maternity.newborn.reports.view`
- `maternity.birth_outcome.view`
- `maternity.birth_outcome.manage`

Clinical maternity roles receive these permissions. Reception permissions remain limited.

## New Localisation Keys

EN/FR keys were added for newborn records, birth outcomes, baby number, birth order, sex, birth time, measurements, APGAR, resuscitation, congenital concerns, feeding, breathing, cord status, neonatal condition, outcome/status, risk/danger signs, dashboard metrics, patient linking/creation, postnatal placeholders, billing placeholders, success messages, and advisory warnings.

## Newborn Record Behavior

- Newborn records link to delivery, labor episode, pregnancy profile, mother patient, visit, admission, and department.
- `newborn_patient_id` remains nullable.
- Newborn patient creation is explicit and permission-protected.
- Newborn records can be updated, status-changed, closed, or cancelled.
- Stillbirth outcomes are supported without forcing patient creation.

## Multiple Birth Behavior

- `newborn_count` on the delivery record is treated as the expected count.
- Bulk create fills missing birth orders.
- Each baby has its own APGAR, weight, condition, outcome, and status.
- Duplicate birth order is prevented for the same delivery.
- Missing/extra newborn record counts are shown as warnings, not hard clinical blockers.

## Birth Outcome Behavior

Delivery newborn state is complete when:

- expected newborn count is met or exceeded, and
- newborn records have status and outcome recorded.

This updates `delivery_records.newborn_records_pending` but does not block delivery completion.

## Optional Newborn Patient Linking Behavior

- Existing patient records can be linked explicitly.
- Newborn patient records can be created explicitly when:
  - the newborn is not already linked,
  - outcome is not stillbirth,
  - sex is male or female.
- Created patient records use existing patient number generation.
- Only safe fields are copied: generated baby name, date of birth, sex, mother phone/address context, and registering user.

## Dashboard Behavior

Maternity dashboard now includes:

- Newborn records pending
- Newborns recorded today
- Live births today
- Stillbirths today
- Recent newborn records

Newborn summary also appears on pregnancy profile, labor episode, and delivery detail pages.

## Postnatal Preparation Behavior

Postnatal workflow is not implemented. Newborn and delivery pages include placeholders indicating records are ready for the next postnatal phase.

## Billing Behavior

No billing is posted in this phase.

No invoices, newborn package charges, neonatal observation charges, resuscitation charges, consumable charges, pharmacy dispensing, or accounting postings are created.

## Existing Workflows Protected

No changes were made to:

- Labor workflow rules
- Delivery completion behavior
- Admission, nursing, MAR, discharge, billing, theatre, or emergency workflows
- General patient creation workflow
- Civil birth registration
- Postnatal records
- Maternity discharge summaries

## Tests And Checks Run

- `php artisan test tests/Feature/NewbornBirthOutcomePhase11Test.php`
- `php artisan test tests/Feature/LaborDeliveryFoundationPhase10Test.php`
- `php artisan test tests/Feature/AntenatalCarePhase9Test.php`
- `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
- `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
- `php artisan route:list --name=maternity`
- `php artisan route:list --name=admissions.requests`
- `php artisan view:clear`
- `php artisan config:clear`
- `git diff --check`
- PHP syntax checks on new/changed PHP files

The full test suite was not run.

## Known Risks

- Newborn patient creation uses a minimal generated baby name because no mother-child relationship field exists yet.
- Delivery-level newborn completion is advisory and does not block downstream work.
- Postnatal workflow is intentionally deferred, so newborn records are prepared but not yet consumed by postnatal observations.
- Civil birth registration and official registry integrations are not included.

## Intentionally Deferred

- Full postnatal care workflow
- Mother/newborn postnatal observation records
- Newborn admission workflow
- Newborn billing/package billing
- Newborn discharge summaries
- Civil birth registration
- Pharmacy dispensing or consumable posting
- Automatic newborn patient creation
- Mother-child relationship schema beyond `newborn_patient_id`

## Next Recommended Phase

Phase 12: Postnatal Care Workflow.
