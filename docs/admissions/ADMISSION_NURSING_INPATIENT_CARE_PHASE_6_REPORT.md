# Admission Nursing and Inpatient Care Phase 6 Report

## Scope

Implemented the Phase 6 nursing and inpatient care layer without changing billing posting, MAR/medication administration behavior, maternity workflows, or discharge clearance.

## Existing Surface Audit

- Admission show already exposed ward rounds, vitals, medication-board counts, consultation summary, visit services, billing, location history, and consultation clinical tasks.
- `ClinicalTask` already supports `admission_id`, but it is shared with consultation and medication reminder flows, so Phase 6 uses a dedicated lightweight nursing task model to avoid changing medication reminder behavior.
- Vitals and ward rounds are reused as the clinical freshness signals.
- MAR and medication board links/counts are reused as read-only medication visibility.
- Bed map already showed admission occupancy; Phase 6 adds nursing indicators on occupied beds.

## Implementation

- Added `admissions.care_flags` JSON storage.
- Added `nursing_notes` and `nursing_tasks` tables.
- Added nursing enums for care flags, note types, task types, and task statuses.
- Added `NursingNote` and `NursingTask` models and relationships on `Admission`.
- Added `AdmissionNursingCareService` for note/task/care flag mutations with activity logging.
- Added `AdmissionCareOverviewService` to compose handover, checklist, stale vitals, stale ward rounds, open tasks, overdue tasks, and medication counts.
- Added `AdmissionNursingCareController` and protected routes for nursing notes, nursing tasks, task completion, and care flag updates.
- Added nursing handover/checklist panels to both the simplified nurse admission view and the full admission management view.
- Added a Nursing tab to the full admission page.
- Added bed-map indicators for open nursing tasks, overdue nursing tasks, and overdue vitals.
- Added config defaults:
  - `ADMISSION_VITALS_OVERDUE_HOURS`, default `8`
  - `ADMISSION_WARD_ROUND_OVERDUE_HOURS`, default `24`
- Added EN/FR localisation parity for new nursing UI and enum labels.
- Added role permissions for Admin/Super Admin, Doctor, Nurse, Ward Nurse, and Physician Assistant visibility where appropriate.

## Permissions

- `admission.nursing.view`
- `admission.nursing.notes.create`
- `admission.nursing.notes.update`
- `admission.nursing.tasks.create`
- `admission.nursing.tasks.update`
- `admission.nursing.tasks.complete`
- `admission.care_flags.manage`
- `admission.care_overview.view`

## Activity Logs

Logged admission activity actions:

- `NURSING_NOTE_CREATED`
- `NURSING_NOTE_UPDATED`
- `NURSING_TASK_CREATED`
- `NURSING_TASK_UPDATED`
- `NURSING_TASK_COMPLETED`
- `NURSING_TASK_CANCELLED`
- `ADMISSION_CARE_FLAGS_UPDATED`

Nursing note text is not copied into activity metadata.

## Verification

- `php artisan test tests/Feature/AdmissionNursingCarePhase6Test.php`
- `php artisan test tests/Feature/AdmissionBedWorkflowPhase5Test.php`
- `php artisan test tests/Feature/AdmissionBedWorkflowPhase4Test.php`
- `php artisan test tests/Feature/AdmissionWorkflowFoundationTest.php`
- `php artisan test tests/Feature/WardAdmissionTest.php`
- `php artisan route:list --name=admissions | rg "nursing|care-flags|admissions\\.show|medications"`
- `php artisan view:clear`
- `php artisan config:clear`
- PHP lint checks on new controller, services, models, enums, migration, and feature test.

## Notes

- The implementation intentionally does not reuse medication-linked `ClinicalTask` records for nursing task creation, because that model is already part of broader clinical and medication reminder behavior.
- The simplified nurse admission page now receives the nursing handover and care checklist directly, so ward nurses do not need the full management view to use Phase 6.
