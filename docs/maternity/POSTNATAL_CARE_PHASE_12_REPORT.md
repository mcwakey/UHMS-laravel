# Postnatal Care Phase 12 Report

## What Was Implemented

Implemented the Postnatal Care Workflow as an additive maternity module. The workflow now supports opening a postnatal case from a completed delivery, recording mother observations, recording newborn observations, tracking readiness for discharge, marking referral/follow-up needs, and surfacing postnatal state on delivery, newborn, pregnancy profile, dashboard, and admission discharge readiness contexts.

## Existing Foundation Audited

Reviewed the Phase 10 delivery foundation and Phase 11 newborn foundation before implementation. The new workflow links to `DeliveryRecord`, `LaborEpisode`, `PregnancyProfile`, `MaternityCase`, `NewbornRecord`, `Admission`, `Visit`, `Department`, and mother/newborn `Patient` records without rewriting those workflows.

Admission discharge readiness and nursing care patterns were also checked. The admission integration is advisory by default and does not block discharge unless a new disabled-by-default config flag is explicitly enabled.

## Files Changed

- Added postnatal enums under `app/Enums`.
- Added postnatal models under `app/Models`.
- Added postnatal services under `app/Services/Maternity`.
- Added `PostnatalCaseController`.
- Added postnatal routes in `routes/web.php`.
- Added postnatal migration `2026_07_04_000011_create_postnatal_care_tables.php`.
- Updated maternity controllers, overview service, models, dashboard/profile/delivery/newborn views.
- Updated admission discharge readiness config/service/localisation.
- Updated role permissions and EN/FR maternity localisation.
- Added `tests/Feature/PostnatalCarePhase12Test.php`.

## New Tables

- `postnatal_cases`
- `postnatal_mother_observations`
- `postnatal_newborn_observations`

The tables store linked delivery/pregnancy/admission context, mother/newborn readiness, referral/follow-up fields, vitals, danger signs, risk flags, counselling, assessment, plan, cancellation, and audit actor/timestamp fields.

## New Models And Enums

Models:

- `PostnatalCase`
- `PostnatalMotherObservation`
- `PostnatalNewbornObservation`

Enums:

- `PostnatalCaseStatus`
- `PostnatalObservationStatus`
- `BleedingStatus`
- `UterusCondition`
- `WoundCondition`
- `BreastfeedingStatus`
- `JaundiceStatus`
- `PostnatalMotherDangerSign`
- `PostnatalMotherRiskFlag`
- `PostnatalNewbornDangerSign`
- `PostnatalNewbornRiskFlag`

## Services

- `PostnatalCaseService`
- `PostnatalMotherObservationService`
- `PostnatalNewbornObservationService`
- `PostnatalRiskAssessmentService`
- `PostnatalOverviewService`

These services own opening cases, observation recording/updating/cancelling, advisory risk assessment, readiness actions, referral/follow-up updates, dashboard metrics, and activity logging.

## Routes And Controllers

Added protected maternity routes for:

- Postnatal case list/show/open/update/status/close/cancel
- Mother observation create/store/show/edit/update/cancel
- Newborn observation create/store/show/edit/update/cancel

The implementation uses `PostnatalCaseController` and permission middleware consistent with the existing maternity route style.

## Permissions

Added:

- `maternity.postnatal.view`
- `maternity.postnatal.open`
- `maternity.postnatal.update`
- `maternity.postnatal.close`
- `maternity.postnatal.cancel`
- `maternity.postnatal.mother.record`
- `maternity.postnatal.mother.update`
- `maternity.postnatal.newborn.record`
- `maternity.postnatal.newborn.update`
- `maternity.postnatal.risk.manage`
- `maternity.postnatal.discharge.manage`
- `maternity.postnatal.referral.manage`
- `maternity.postnatal.reports.view`

Admin receives all permissions through the existing sync. Maternity clinical roles inherit the postnatal permissions via the maternity clinical permission bundle. Reception permissions were not expanded into clinical postnatal actions.

## Localisation

Added EN/FR keys for postnatal care labels, case statuses, observation statuses, danger signs, risk flags, bleeding/uterus/wound/breastfeeding/jaundice states, warnings, success messages, discharge readiness labels, referral/follow-up labels, and billing placeholders.

## Case Behaviour

A postnatal case is opened by explicit action from the delivery record. It links delivery, labor, pregnancy profile, maternity case, mother patient, visit, admission, and department. Duplicate active cases are avoided by reusing an existing active postnatal case for the delivery.

Stillbirth-only deliveries are treated as not requiring newborn postnatal observation readiness.

## Observation Behaviour

Mother observations record vitals, bleeding, uterus, pain, wound, breastfeeding, mobility, urination, mental wellbeing, danger signs, risk flags, assessment, plan, and counselling.

Newborn observations record temperature, weight, feeding, breathing, cord, jaundice, stooling, urination, activity, danger signs, risk flags, immunisation note, assessment, plan, and counselling.

Risk assessment remains advisory. It escalates postnatal case visibility/status where clinically relevant but does not block data entry.

## Discharge Readiness

Postnatal cases support marking mother ready, newborn ready, ready for discharge, referral required, follow-up date, and follow-up instructions.

Admission discharge readiness now includes a postnatal advisory area when linked postnatal care exists. Enforcement is disabled by default through:

`ADMISSION_REQUIRE_POSTNATAL_READY_BEFORE_DISCHARGE=false`

No existing admission discharge workflow was rewritten.

## Dashboard Behaviour

Maternity dashboard now includes active postnatal cases, mother observations today, newborn observations today, postnatal referrals required, follow-ups due this week, and recent postnatal cases.

Pregnancy profile, delivery detail, and newborn detail pages show postnatal summaries and links/actions.

## Billing Behaviour

Billing is placeholders only. The workflow does not create invoices, post charges, recalculate invoices, dispense pharmacy items, or consume stock.

Deferred billing mapping placeholders cover postnatal mother care, newborn care, neonatal observation, immunisation, and newborn consumables.

## Existing Workflows Protected

The implementation does not rewrite:

- Delivery records
- Newborn records
- Labor episodes
- Antenatal care
- Admission discharge readiness
- Nursing care
- MAR/medication workflows
- Billing
- Theatre
- Emergency
- Pharmacy/dispensing

## Tests And Checks Run

- `php artisan test tests/Feature/PostnatalCarePhase12Test.php`
- `php artisan test tests/Feature/NewbornBirthOutcomePhase11Test.php`
- `php artisan test tests/Feature/LaborDeliveryFoundationPhase10Test.php`
- `php artisan test tests/Feature/AntenatalCarePhase9Test.php`
- `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
- `php artisan test tests/Feature/AdmissionDischargeReadinessPhase7Test.php`
- `php artisan route:list --name=maternity`
- `php artisan route:list --name=admissions`
- `php artisan route:list --name=admissions.requests`
- `php artisan view:clear`
- `php artisan config:clear`
- `git diff --check`
- PHP syntax checks on new/changed postnatal PHP files and localisation files

## Known Risks

- Postnatal discharge enforcement is available by config but intentionally disabled by default. Sites must decide whether they want enforcement.
- There is no appointment module integration yet; follow-up is stored directly on the postnatal case.
- Billing mappings are placeholders only.
- Postnatal risk rules are advisory and may need local clinical tuning.

## Deferred Items

- Maternity package billing
- Newborn billing
- Pharmacy dispensing or stock consumption
- Official birth registration/civil registry integration
- Maternity-specific discharge summary sections
- Appointment module integration
- Final wide regression/manual test data

## Next Recommended Phase

Phase 13: Maternity Reports, Billing Mapping Readiness, Manual Test Data, and Final Wide Regression.
