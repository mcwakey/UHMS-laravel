# Consultation Specialist Extension - Phase 8 Summary Builder Report

## Summary

Phase 8 adds a specialty-aware consultation summary builder for physiotherapy, ophthalmology, and dental consultations. It reads existing core consultation records, active-profile structured specialty entries, readiness results, and order-set application metadata, then generates a concise preview summary. Generated summaries are preview-only and are not saved unless the doctor explicitly inserts the text and uses the existing final clinical note save action.

## Existing Notes/Summary Findings

- Final clinician notes are stored on `medical_records.final_note`, with `final_note_updated_by` and `final_note_updated_at`.
- Final notes are saved through `ConsultationWorkspaceController::updateFinalNote()` via `PATCH admin/consultations/{visit}/final-note`.
- Final-note saves validate `final_note` as nullable text up to 20,000 characters and log through `MedicalRecordEntryLogService::updated()`.
- Generated consultation summaries are read-only workspace output assembled by `ConsultationSummaryService::forRecord()`.
- The generated summary fragment is served by `summaryFragment()` and rendered in `resources/views/consultations/partials/summary-sections.blade.php`.
- The consultation summary tab keeps final note editing and generated summary display separate.
- Completion readiness treats plan/disposition as present when treatments, prescriptions, tasks, or route notes exist.
- Ajax refresh for summary uses `resources/js/Pages/consultation-show.js` and `admin.consultations.summary-fragment`.
- Notes and generated summary are already decoupled; Phase 8 preserves that structure.

## Files Added

- `app/Data/Consultation/Specialty/ConsultationSpecialtySummaryResult.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummarySourceCollector.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummaryTemplateRegistry.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummaryFormatter.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtySummaryBuilder.php`
- `app/Http/Controllers/Doctor/Consultations/ConsultationSpecialtySummaryController.php`
- `resources/views/consultations/partials/specialty-summary-preview.blade.php`
- `tests/Feature/Consultations/ConsultationSpecialtySummaryBuilderTest.php`
- `docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_8_SUMMARY_BUILDER_REPORT.md`

## Files Modified

- `app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php`
- `resources/views/consultations/show.blade.php`
- `resources/views/consultations/partials/page-config.blade.php`
- `routes/web.php`
- `lang/en/consultation_specialties.php`
- `lang/fr/consultation_specialties.php`

## Summary Builder Design

`ConsultationSpecialtySummarySourceCollector` gathers route-scoped core data, active-profile specialty entries, readiness payloads, and order-set applications into a normalized read-only source array.

`ConsultationSpecialtySummaryTemplateRegistry` defines code-based summary templates for general medicine, physiotherapy, ophthalmology, and dental.

`ConsultationSpecialtySummaryFormatter` formats core lists, specialty entry fields, booleans, arrays, prescriptions, tasks, procedures, and readiness warnings into human-readable text.

`ConsultationSpecialtySummaryBuilder` combines the collector, template registry, and formatter into a `ConsultationSpecialtySummaryResult` with Blade/JSON-safe sections, plain text, HTML preview, warnings, generated timestamp, fallback state, and source completeness metadata.

General medicine uses the existing `ConsultationSummaryService` as a safe fallback.

## Specialty Templates Implemented

- General Medicine: chief complaint, history, examination, diagnosis, investigations, treatment/prescription, follow-up.
- Physiotherapy: presenting problem, pain assessment, functional limitation, physical assessment, treatment plan, therapy session, home exercise plan, progress/next review, tasks/follow-up, readiness warnings.
- Ophthalmology: eye complaint, visual acuity, refraction, IOP, eye examination, diagnosis, investigations, treatment/prescription, follow-up, readiness warnings.
- Dental: dental complaint, tooth chart, oral examination, dental diagnosis, X-ray/investigation, procedure plan/performed procedure, consent, prescription/medication, post-procedure instructions, readiness warnings.

## UI Changes

The consultation summary tab now shows a compact specialty summary builder panel above the existing final clinical note textarea. Doctors can generate a preview modal, copy the generated summary, or insert it into the final-note textarea. If the textarea already has content, the doctor is asked whether to replace it; cancelling appends the generated text.

## Persistence Behavior

Phase 8 implements preview-only generation. The preview endpoint does not write to the medical record. Insert is client-side only and does not persist. The doctor must still click the existing Save button for `final_note`.

## Readiness Integration

The builder includes warning text when readiness has blocking or warning items. Missing readiness items do not block summary generation.

## Backward Compatibility

Existing general generated summary behavior remains intact through `ConsultationSummaryService`. Existing final note saving, audit logging, summary fragment refresh, completion readiness, and prescription safety behavior are unchanged.

## Tests Added

`ConsultationSpecialtySummaryBuilderTest` covers general fallback, physiotherapy structured fields and arrays, ophthalmology eye fields, dental fields and booleans, empty-field skipping, readiness warnings, preview endpoint JSON, no persistence on preview, workspace metadata, wrong-profile isolation, and localization keys.

## Checks Run

- `php -l` on all new Phase 8 PHP files.
- `php artisan migrate`
- `php artisan db:seed --class=ConsultationSpecialtySeeder`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtySummaryBuilderTest.php`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php tests/Feature/Consultations/ConsultationSpecialtySummaryBuilderTest.php tests/Feature/ConsultationWorkspaceStabilisationTest.php`
- `php artisan route:list`
- `php artisan view:cache`
- `php artisan view:clear`

Result: focused Phase 1-8 specialty/workspace tests passed with 82 tests and 395 assertions.

## Known Issues / Follow-up

- Phase 9 doctor personal workspace can surface summary-builder availability in doctor task queues.
- A later admin configuration UI can expose summary templates without editing code.
- Later billing/service mapping remains out of scope.
- Print/export formatting can later consume the same summary result if a specialty print layout is needed.
