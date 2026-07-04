# Admission Discharge Readiness Phase 7 Report

## What Was Implemented

Phase 7 adds an advisory discharge readiness workflow around the existing admission discharge action. It improves visibility for discharge planning, clearance, structured summaries, billing warnings, nursing readiness, medication readiness, and bed-release confidence while preserving the existing discharge behavior by default.

## Files Changed

- `config/admissions.php`
- `app/Models/Admission.php`
- `app/Services/Admissions/AdmissionDischargeReadinessService.php`
- `app/Services/Admissions/AdmissionDischargeWorkflowService.php`
- `app/Http/Controllers/Admin/AdmissionsWard/AdmissionController.php`
- `app/Http/Controllers/Admin/AdmissionsWard/AdmissionDischargeWorkflowController.php`
- `app/Services/WardService.php`
- `resources/views/admissions/show.blade.php`
- `resources/views/admissions/discharge.blade.php`
- `resources/views/admissions/partials/discharge-readiness-tab.blade.php`
- `resources/views/admissions/discharge-summary-print.blade.php`
- `resources/views/wards/bed-map.blade.php`
- `database/seeders/RoleSeeder.php`
- `lang/en/admissions.php`
- `lang/fr/admissions.php`
- `tests/Feature/AdmissionDischargeReadinessPhase7Test.php`

## New Migrations, Tables, and Columns

Migration:

- `database/migrations/2026_07_04_000004_create_admission_discharge_readiness_tables.php`

Added admission planning columns:

- `discharge_planning_started_at`
- `discharge_planning_started_by`
- `expected_discharge_at`
- `discharge_planning_note`

Added tables:

- `admission_discharge_clearances`
- `admission_discharge_summaries`

## New Services and View Models

- `AdmissionDischargeWorkflowService`
  - Ensures default clearance records.
  - Starts/updates discharge planning.
  - Clears, blocks, and revokes discharge clearance.
  - Creates, updates, prepares, and approves structured summaries.
  - Logs sensitive workflow actions without copying long clinical text into metadata.

- `AdmissionDischargeReadinessService`
  - Composes readiness across clinical, nursing, medication/MAR, billing, bed release, documentation, and follow-up areas.
  - Uses Phase 6 nursing/care overview data.
  - Produces advisory readiness by default.
  - Enforces blockers only when config flags are enabled.

## New Routes and Controllers

Controller:

- `AdmissionDischargeWorkflowController`

Routes:

- `POST admin/admissions/{admission}/discharge-planning`
- `PATCH admin/admissions/{admission}/discharge-planning`
- `PATCH admin/admissions/{admission}/discharge-clearances/{clearance}`
- `PATCH admin/admissions/{admission}/discharge-clearances/{clearance}/revoke`
- `POST admin/admissions/{admission}/discharge-summary`
- `PATCH admin/admissions/{admission}/discharge-summary`
- `PATCH admin/admissions/{admission}/discharge-summary/{summary}/prepare`
- `PATCH admin/admissions/{admission}/discharge-summary/{summary}/approve`
- `GET admin/admissions/{admission}/discharge-summary/{summary}/print`

## New Permissions

- `admission.discharge.readiness.view`
- `admission.discharge.plan`
- `admission.discharge.clearance.view`
- `admission.discharge.clearance.manage`
- `admission.discharge.summary.view`
- `admission.discharge.summary.create`
- `admission.discharge.summary.update`
- `admission.discharge.summary.approve`
- `admission.discharge.enforced.override`

## Localisation

Added EN/FR keys for:

- Discharge readiness
- Planning state and expected discharge
- Clearance types/statuses/actions
- Summary draft/prepared/approved states
- Enforcement enabled/disabled messaging
- Billing, medication, nursing, documentation, and follow-up warnings
- Bed-map discharge indicators

## Discharge Readiness Behavior

Readiness areas:

- Clinical
- Nursing
- Medication/MAR
- Billing
- Bed release
- Documentation
- Follow-up

Statuses:

- Ready
- Warning
- Blocked
- Unavailable

By default, warnings do not prevent discharge.

## Clearance Workflow Behavior

Default clearance records are created for each admission when readiness is viewed. A permitted user can:

- Clear an item
- Block an item
- Revoke a clearance

Revocation requires a note.

## Summary Workflow Behavior

Structured discharge summary supports:

- Draft creation
- Draft/prepared updates
- Prepared state
- Approved state
- Print-friendly view

Approved summaries are protected from casual edit through this workflow.

## Enforcement Config Behavior

Added config:

- `ADMISSION_REQUIRE_DISCHARGE_CLEARANCE`
- `ADMISSION_REQUIRE_DISCHARGE_SUMMARY`
- `ADMISSION_REQUIRE_BILLING_CLEARANCE`

Defaults are false, preserving existing discharge behavior.

## Final Discharge Integration

The existing `AdmissionService::discharge()` still performs final discharge, bed release, visit transition, event dispatch, and existing activity logging. Phase 7 adds a readiness check before that call.

When enforcement blocks discharge, an activity log entry is written:

- `FINAL_DISCHARGE_BLOCKED_BY_READINESS_ENFORCEMENT`

## Ward Board Discharge Indicators

Bed map now shows safe discharge indicators:

- Discharge planning started
- Expected discharge today
- Clearance pending
- Clearance blocked
- Summary missing
- Billing warning
- Ready for discharge

## Existing Workflows Protected

Not changed:

- Billing posting
- Invoice recalculation
- MAR/medication administration behavior
- Existing discharge form/action
- Bed release behavior
- Admission request conversion
- Reservation expiry
- Direct admission
- Emergency-to-admission compatibility
- Maternity/ANC/labor/newborn/postnatal workflows

## Tests and Checks Run

- `php artisan test tests/Feature/AdmissionDischargeReadinessPhase7Test.php`
- `php artisan test tests/Feature/AdmissionNursingCarePhase6Test.php`
- `php artisan test tests/Feature/AdmissionBedWorkflowPhase5Test.php`
- `php artisan test tests/Feature/AdmissionBedWorkflowPhase4Test.php`
- `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
- `php artisan test tests/Feature/WardAdmissionTest.php`
- `php artisan route:list --name=admissions | rg "discharge|clearance|summary|admissions\\.show"`
- `php artisan view:clear`
- `php artisan config:clear`
- PHP syntax checks on new controller, services, models, enums, migration, and test.

## Known Risks

- Billing readiness uses the existing latest invoice balance only. It does not perform a billing recalculation.
- Clearance actions in the workspace use quick action buttons with standard notes. A richer note-entry modal can be added later.
- Summary print is intentionally basic and browser-print based.

## Intentionally Deferred

- Appointment creation/linking for follow-up.
- PDF generation.
- Maternity-specific discharge summary sections.
- Discharge clearance by department-specific ownership rules.
- Full discharge clearance enforcement in production defaults.

## Next Recommended Phase

Phase 8: Maternity Foundation and Pregnancy Profile.
