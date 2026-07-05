# Consultation Specialist Extension - Phase 3 Layout Engine Report

## Summary

Implemented the specialty consultation layout engine. The consultation workspace now consumes resolved specialty context through a normalized layout service, uses a section registry for core and specialist-only sections, renders a specialty identity strip, and shows specialty-driven sidebar labels/order while preserving existing core section forms, IDs, anchors, and JavaScript behavior.

Structured physiotherapy, ophthalmology, and dental forms were not added in this phase.

## Current Consultation View Findings

- Main workspace view: `resources/views/consultations/show.blade.php`.
- Left navigation: `resources/views/consultations/partials/workflow-sidebar.blade.php`.
- Right clinical/context panel: `resources/views/consultations/partials/right-panel.blade.php`.
- Existing core panes are inline in `show.blade.php`, not separate partials.
- Core section IDs/anchors used by JavaScript include `complaints-section`, `hopc-section`, `examination-section`, `diagnoses-section`, `investigations-section`, `prescriptions-section`, `procedures-section`, `tasks-section`, and `summary-section`.
- Existing JavaScript depends on stable IDs, badge IDs, form `data-refresh-section` values, and list IDs such as `complaints-list`, `hopc-list`, `investigations-list`, and `consultation-summary-body`.
- Existing save behavior uses the current consultation routes and form endpoints; these were left unchanged.

## Files Added

- `app/Services/Consultation/Specialty/ConsultationSpecialtySectionComponentRegistry.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyLayoutService.php`
- `resources/views/consultations/partials/specialty/generic-section.blade.php`
- `resources/views/consultations/partials/specialty/core-section.blade.php`
- `tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php`

## Files Modified

- `app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php`
- `lang/en/consultation_specialties.php`
- `lang/fr/consultation_specialties.php`
- `resources/views/consultations/show.blade.php`
- `resources/views/consultations/partials/workflow-sidebar.blade.php`

## Layout Engine Design

- `ConsultationSpecialtySectionComponentRegistry` maps section keys to core or generic render targets.
- Core section aliases such as `presenting_problem`, `eye_complaint`, and `dental_complaint` route to existing safe core panes where appropriate.
- Unknown specialist-only sections resolve to the generic shell.
- `ConsultationSpecialtyLayoutService` normalizes sections, filters invisible sections, orders by `display_order`, keeps the first duplicate section key, marks core sections, and falls back to general medicine when context is missing or invalid.
- The workspace keeps existing inline core panes to preserve forms, IDs, collapses, modals, Ajax refresh, and JavaScript selectors.

## Specialty Layouts

General Medicine:

1. `patient_summary`
2. `complaints`
3. `hopc`
4. `examination`
5. `diagnosis`
6. `investigations`
7. `prescription`
8. `procedures`
9. `tasks`
10. `notes`
11. `summary`
12. `completion_readiness`

Physiotherapy:

1. `patient_summary`
2. `presenting_problem`
3. `pain_assessment`
4. `functional_limitation`
5. `physical_assessment`
6. `treatment_plan`
7. `therapy_session`
8. `home_exercise_plan`
9. `tasks`
10. `progress_notes`
11. `summary`
12. `completion_readiness`

Ophthalmology:

1. `patient_summary`
2. `eye_complaint`
3. `visual_acuity`
4. `refraction`
5. `iop`
6. `eye_examination`
7. `diagnosis`
8. `investigations`
9. `procedures`
10. `prescription`
11. `follow_up`
12. `summary`
13. `completion_readiness`

Dental:

1. `patient_summary`
2. `dental_complaint`
3. `tooth_chart`
4. `oral_examination`
5. `dental_diagnosis`
6. `dental_xray`
7. `dental_procedures`
8. `consent`
9. `prescription`
10. `follow_up`
11. `summary`
12. `completion_readiness`

## UI Changes

- Added a compact specialty workspace strip beneath the patient card.
- Sidebar section labels and ordering now come from `specialtyLayout`.
- Required section metadata appears as a badge in the layout navigation and generic section shell.
- Specialist-only sections without structured forms render through a neutral generic empty state.

## Backward Compatibility

The general consultation workflow keeps the existing core pane IDs, forms, endpoints, list IDs, badge IDs, modals, collapses, and JavaScript selectors. Core consultation save behavior was not changed.

## Tests Added

- `tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php`

The test covers general layout order, core registry resolution, generic fallback, seeded specialist layout order, required metadata, invalid context fallback, and workspace rendering smoke.

## Checks Run

Passed:

- `php artisan migrate`
- `php artisan db:seed --class=ConsultationSpecialtySeeder`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php`
- `php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php`
- `php artisan route:list`
- `php artisan view:cache`
- `php artisan view:clear`
- PHP lint on new and modified PHP files

Localisation note:

- No project-specific localisation parity/lock command exists beyond Laravel's `lang:publish`.

## Known Issues / Follow-up

- Phase 4 should add structured specialist forms for generic specialist-only sections.
- Specialty-specific completion readiness remains out of scope.
- Specialty-specific summary generation remains out of scope.
- Billing mapping remains out of scope.
