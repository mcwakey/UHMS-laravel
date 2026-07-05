# Antenatal Care Phase 9 Report

## What Was Implemented

Phase 9 adds the first real maternity sub-workflow: Antenatal Care (ANC). UHMS can now record ANC visits against pregnancy profiles, review pregnancy progress, capture vitals and fetal observations, track danger signs and advisory risk flags, schedule next ANC dates, record referrals, and explicitly create maternity admission requests from ANC visits.

## Existing Foundation Audited

Reviewed Phase 8 maternity foundation before implementation:

- `PregnancyProfile` and `MaternityCase` models, enum casts, relationships, and activity context helpers.
- `PregnancyProfileService`, `MaternityCaseService`, and `MaternityOverviewService` patterns.
- Maternity dashboard, pregnancy profile page, case detail page, routes, permissions, and translations.
- Admission request lifecycle and explicit maternity admission request hook.
- Existing patient, visit, admission, ward, and dashboard card conventions.

## Files Changed

- Added ANC enums, model, migration, controller, services, views, tests, and documentation.
- Updated pregnancy profile, maternity case, patient, visit, and admission relationships.
- Updated maternity dashboard and pregnancy profile show page.
- Updated role seeder with ANC permissions.
- Updated EN/FR maternity translations.

## Database

New table: `antenatal_visits`

Stores pregnancy profile linkage, optional case/visit/admission/department context, visit number/date, gestational age, maternal vitals, fetal observations, urine results, haemoglobin, danger signs, risk flags, assessment/plan/counselling, supplements, immunisations, next visit date, referral type/reason, status, authorship, timestamps, and soft deletes.

## New Models And Enums

- `AntenatalVisit`
- `AntenatalVisitStatus`
- `AntenatalReferralType`
- `AntenatalDangerSign`
- `AntenatalRiskFlag`
- `FetalPresentation`
- `UrineProteinResult`
- `UrineGlucoseResult`

## New Services

- `AntenatalVisitService`
- `AntenatalRiskAssessmentService`
- `AntenatalOverviewService`

Risk logic is advisory and lives in services, not Blade.

## Routes And Controllers

Added `AntenatalVisitController` and ANC routes under `admin.maternity.*`:

- ANC list/create/store from pregnancy profile
- ANC detail/edit/update
- ANC cancel
- ANC referral
- ANC admission request

## Permissions

Added:

- `maternity.anc.view`
- `maternity.anc.record`
- `maternity.anc.update`
- `maternity.anc.cancel`
- `maternity.anc.risk.manage`
- `maternity.anc.referral.create`
- `maternity.anc.admission.request`
- `maternity.anc.reports.view`

Clinical maternity roles receive ANC permissions. Reception permissions remain limited.

## Behavior

- ANC visit creation links to pregnancy profile, patient, and available visit/admission/department context.
- Latest ANC gestational age safely updates the pregnancy profile.
- Danger signs and risk flags can mark pregnancy high risk through service rules.
- Next ANC date is stored and surfaced in profile/dashboard metrics.
- Referral recording is explicit.
- Admission request creation is explicit, uses the existing admission request lifecycle, and does not auto-admit.

## Dashboard

Maternity dashboard now includes ANC metrics:

- ANC visits today
- Missed ANC visits
- Profiles without ANC
- Recent ANC visits

Existing maternity case metrics remain.

## Investigation / Ultrasound

Direct ANC investigation/ultrasound shortcuts are deferred. The ANC detail page shows a placeholder directing clinicians to use the existing investigation workflow where clinically required.

## Supplements / Immunisations

Lightweight checkbox capture is stored on the ANC visit JSON fields. No pharmacy dispensing or full immunisation module was added.

## Billing

No billing, service charges, maternity package posting, or accounting posting was added in this phase.

## Workflows Protected

No changes were made to labor, partograph, delivery, newborn, postnatal, admission conversion, ward nursing, discharge readiness, emergency, billing, or MAR workflows.

## Tests And Checks Run

- `php artisan test tests/Feature/AntenatalCarePhase9Test.php`
- `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
- `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
- `php artisan test tests/Feature/AdmissionNursingCarePhase6Test.php`
- `php artisan test tests/Feature/AdmissionDischargeReadinessPhase7Test.php`
- `php artisan route:list --name=maternity`
- `php artisan route:list --name=admissions.requests`
- `php artisan view:clear`
- `php artisan config:clear`
- `git diff --check`
- PHP syntax checks on new/changed PHP files

## Known Risks

- Direct lab/ultrasound integration is intentionally deferred.
- Clinical risk rules are advisory and deliberately conservative.
- ANC admission requests use existing `source_type=maternity` and `source_id=antenatal_visit_id`; downstream screens should treat source IDs as source-specific.

## Deferred

- Labor episodes
- Partograph observations
- Delivery records
- Newborn records
- Postnatal records
- Maternity discharge summaries
- Maternity package billing
- Direct ANC investigation/radiology shortcuts
- Full immunisation module
- Pharmacy dispensing from supplements

## Next Recommended Phase

Phase 10: Labor and Delivery Foundation.
