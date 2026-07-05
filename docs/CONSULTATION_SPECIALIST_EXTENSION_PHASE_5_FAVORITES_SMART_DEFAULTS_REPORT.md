# Consultation Specialist Extension — Phase 5 Favorites and Smart Defaults Report

## Summary

Phase 5 adds specialty favorites and smart defaults for consultation workspaces without changing the shared consultation engine. Physiotherapy, ophthalmology, and dental now have seeded specialty-aware suggestions for diagnoses, investigations, procedures, drugs, frequencies, tasks, and follow-up instructions. General medicine remains light and continues to rely primarily on global data.

## Existing Option Source Findings

- ICD-10 search uses `IcdCodeController::search` through `admin.icd-search`, wired to `#icd_code_select` with Select2 in `resources/js/Pages/consultation-show.js`. Diagnosis free-text suggestions use `ConsultationClinicalEntryController::suggestDiagnoses`.
- Investigation services are loaded by department through `ConsultationOrderController::getDepartmentServices`, then inserted into `#investigationServicesSelect`.
- Procedure services are loaded by theatre/procedure department through `ProcedureRequestService` endpoints and inserted into `#procedureServiceSelect`.
- Prescription drugs are loaded server-side in `HandlesConsultationWorkspace` from active `Drug` records and rendered into `.drug-select`.
- Prescription and task frequency options come from `ClinicalFrequencyOptionService`.
- Consultation task title and follow-up instruction fields are free-text fields in the shared workspace.
- Existing mutation forms use `data-ajax-form`, `data-consultation-form`, `data-refresh-section`, and route-context preservation.

## Files Added

- `database/migrations/2026_07_05_000004_create_consultation_specialty_favorites_table.php`
- `app/Models/ConsultationSpecialtyFavorite.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyFavoriteService.php`
- `database/seeders/ConsultationSpecialtyFavoriteSeeder.php`
- `tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php`
- `docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_5_FAVORITES_SMART_DEFAULTS_REPORT.md`

## Files Modified

- `app/Models/ConsultationSpecialtyProfile.php`
- `app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php`
- `app/Services/ClinicalFrequencyOptionService.php`
- `database/seeders/ConsultationSpecialtySeeder.php`
- `resources/views/consultations/show.blade.php`
- `resources/views/consultations/partials/page-config.blade.php`
- `lang/en/consultation_specialties.php`
- `lang/fr/consultation_specialties.php`

## Database Changes

Created `consultation_specialty_favorites` with profile ownership, favorite type, optional polymorphic linked model, fallback code/label fields, description/search metadata, sort order, active state, timestamps, indexes, and a profile/type/code uniqueness guard.

## Specialty Favorite Design

Favorites support linked catalogue records through `favoritable_type` and `favoritable_id`, but also support label-only configuration through `code` and `label`. The service returns only active favorites for active profiles, skips deleted linked models defensively, merges favorites before global options, and dedupes by linked model identity first, then code/value/label.

## Seeded Favorites

- Physiotherapy: rehab diagnoses/problems, therapy procedures, physiotherapy frequencies, therapy tasks, and home exercise/follow-up instructions.
- Ophthalmology: eye diagnoses, eye investigations, eye procedures, eye medication suggestions, eye-use frequencies, and eye safety/follow-up instructions.
- Dental: dental diagnoses, dental X-rays/investigations, dental procedures, dental medication suggestions, dental frequencies, and extraction/oral-care follow-up instructions.
- General medicine: only light frequency/review defaults.

## Workspace Integration

`HandlesConsultationWorkspace` now passes `specialtyFavorites` to the Blade view and page config JSON. Prescription and task frequency selects receive favorite-first merged defaults while preserving global frequency values.

## UI Changes

- Diagnosis favorites appear as opt-in chips and datalist suggestions.
- Investigation/procedure favorites appear as subtle hints while existing department/service selection remains required.
- Drug favorites appear as hints; actual prescription submission still requires an existing drug selection.
- Task favorites appear as opt-in chips and datalist suggestions.
- Follow-up instruction favorites can be inserted into the clinical instruction textarea.

## Frequency Defaults

The shared frequency service now includes once weekly, twice weekly, three times weekly, review in 3 days, review in 1 week, review in 2 weeks, and clearer labels for at night and as needed. Existing standard values such as OD, BD, TDS, QDS, Q4H, Q6H, Q8H, Q12H, AC, and PC remain available.

## Backward Compatibility

General consultation behavior is unchanged. Global ICD, service, procedure, drug, and frequency options remain available. Label-only favorites are suggestions only and are not silently submitted as linked catalogue records.

## Tests Added

Added `ConsultationSpecialtyFavoriteTest` covering seeding, grouping, ordering, inactive filtering, deleted linked model safety, merge ordering/deduping, workspace payload, general medicine light defaults, specialist smoke checks, and standard frequency defaults.

## Checks Run

- `php artisan migrate`
- `php artisan db:seed --class=ConsultationSpecialtySeeder`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php tests/Feature/ConsultationWorkspaceStabilisationTest.php`
- `php artisan route:list`
- `php artisan view:cache`
- `php artisan view:clear`
- PHP lint on new/modified PHP files

Result: focused tests passed with 50 tests and 257 assertions.

## Known Issues / Follow-up

- Phase 6 can use this favorite layer as the foundation for order sets.
- Later completion readiness and summary-builder phases can read the same favorite metadata if needed.
- A future admin configuration UI can manage favorites without editing seeders.
- Label-only drug/service favorites intentionally remain suggestions until linked catalogue records exist.
