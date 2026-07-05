# Consultation Specialist Extension Phase 4 Structured Forms Report

## Scope

Phase 4 replaces the Phase 3 generic specialist-only shell with reusable structured forms for physiotherapy, ophthalmology, and dental sections. The shared consultation workspace remains the entry point, and core consultation panes keep their existing IDs, routes, forms, Ajax attributes, and section behavior.

## Existing Save Pattern Findings

- Core consultation mutations are routed through `routes/web.php` under the authenticated consultation admin group and the `can:consultations.create` middleware where applicable.
- Controllers use `ConsultationWorkflowController::consultationMutationContext()` to resolve and guard the active `VisitConsultationRoute`, then return JSON for Ajax/API-style requests or redirect back with flash messages for normal form posts.
- Existing workspace forms use `data-ajax-form`, `data-consultation-form`, `data-refresh-section`, and `data-route-context-required` so the JavaScript layer can preserve route context and refresh the affected section.
- Validation is generally action-local in controllers/traits rather than always using Form Request classes.
- Clinical saves attribute authors through `created_by`/`updated_by` or related fields, and clinical entry changes are logged with `MedicalRecordEntryLogService`, which also mirrors to the activity log.

## Implementation

- Added `ConsultationSpecialtySectionSchema` as the section-aware schema and validation source for all Phase 4 structured sections.
- Added `ConsultationSpecialtyEntryService` with `getEntriesForConsultation`, `getEntry`, `upsertEntry`, `deleteEntry`, and `entriesAsArray`.
- Added `ConsultationSpecialtyEntryController` using the existing consultation mutation guard, active specialty resolver, section validation, profile mismatch protection, empty-entry no-op/clear behavior, and JSON/redirect response conventions.
- Added specialty entry routes under the existing consultation clinical save middleware.
- Added `structured-section.blade.php`, a schema-driven specialist form partial that stores to `consultation_specialty_entries`, preserves route context, supports boolean clearing, and loads saved values.
- Updated the layout registry so schema-backed sections render structured specialist panes even when their Phase 3 aliases pointed at core panes.
- Hydrated saved specialist entries into the consultation workspace through `HandlesConsultationWorkspace`.
- Added EN/FR labels and messages for structured specialist form actions, fields, and states.

## Specialist Sections Covered

- Physiotherapy: presenting problem, pain assessment, functional limitation, physical assessment, treatment plan, therapy session, home exercise plan, progress notes.
- Ophthalmology: visual acuity, refraction, intraocular pressure, eye examination, follow-up.
- Dental: tooth chart, oral examination, dental diagnosis, dental X-ray, dental procedures, consent.

## Verification

Focused checks run during implementation:

- `php -l` for the new schema, service, controller, and entry test file.
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php`

Additional focused checks are expected before phase handoff:

- `php artisan migrate`
- `php artisan db:seed --class=ConsultationSpecialtySeeder`
- Phase 1, 2, 3, and 4 specialty feature tests
- `tests/Feature/ConsultationWorkspaceStabilisationTest.php`
- `php artisan route:list`
- `php artisan view:cache`
- `php artisan view:clear`
