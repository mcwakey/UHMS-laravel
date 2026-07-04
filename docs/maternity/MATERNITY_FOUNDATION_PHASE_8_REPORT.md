# Maternity Foundation Phase 8 Report

## Scope Completed

Phase 8 adds the optional maternity foundation without forcing maternity workflow onto general visits or admissions.

Implemented:

- Pregnancy profile data model with patient, visit, admission, and maternity department links.
- Maternity case data model with source tracking and clinical status/risk state.
- Maternity dashboard route and view.
- Pregnancy profile list, create, edit, and detail views.
- Maternity case detail view with case update, closure, and maternity admission request action.
- Manual maternity admission request hook using `source_type=maternity` and `source_id=<maternity_case_id>`.
- Maternity permissions and sidebar entry.
- Activity logging under the `MATERNITY` module.
- English and French translation coverage for maternity foundation screens and enums.

## Main Files

- `database/migrations/2026_07_04_000005_create_maternity_foundation_tables.php`
- `app/Models/PregnancyProfile.php`
- `app/Models/MaternityCase.php`
- `app/Services/Maternity/PregnancyProfileService.php`
- `app/Services/Maternity/MaternityCaseService.php`
- `app/Services/Maternity/MaternityOverviewService.php`
- `app/Http/Controllers/Admin/Maternity/PregnancyProfileController.php`
- `app/Http/Controllers/Admin/Maternity/MaternityCaseController.php`
- `app/Http/Controllers/Admin/Maternity/MaternityDashboardController.php`
- `resources/views/maternity/**`
- `lang/en/maternity.php`
- `lang/fr/maternity.php`
- `tests/Feature/MaternityFoundationPhase8Test.php`

## Workflow Notes

- A pregnancy profile can be created independently of admission.
- LMP automatically derives EDD and gestational age when EDD/GA is not supplied.
- High-risk marking appends a risk note and keeps the pregnancy active.
- Closing a profile records closure status, timestamp, user, and reason.
- Opening a maternity case from a profile inherits patient, visit, admission, and department links.
- Admission requests created from maternity cases remain normal admission requests but are traceable to the maternity case through source fields.

## Guardrails Preserved

- General admission still does not require a pregnancy profile.
- Existing direct admission creation remains unchanged.
- Full ANC, labor, delivery, newborn, postnatal, maternity package billing, and investigation ordering were not implemented in this phase.
- Existing admission billing, nursing/MAR, discharge readiness, and emergency-to-admission behavior were not changed.

## Verification

Passed:

- `php artisan test tests/Feature/MaternityFoundationPhase8Test.php`
- `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
- PHP syntax checks for new maternity classes, routes, role seeder, sidebar builder, language files, and migration.

## Deferred

- Full antenatal visit workflow.
- Labor monitoring and partograph.
- Delivery and newborn records.
- Postnatal observation and mother/newborn discharge summaries.
- Maternity service package billing.
- Ultrasound/lab ordering from ANC.
- Department dashboard deep integration beyond the dedicated maternity dashboard route and sidebar entry.
