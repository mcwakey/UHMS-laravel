# Consultation Specialist Extension - Phase 7 Completion Readiness Report

## Summary

Phase 7 makes consultation completion readiness specialty-aware without replacing the existing consultation completion system. General medicine continues to use the existing core readiness checklist. Physiotherapy, ophthalmology, and dental now add specialty-specific readiness items from structured specialty entries and core consultation records, with blocking, warning, optional, and completed states.

## Existing Readiness Findings

- Existing completion readiness is implemented in `ConsultationCompletionReadinessService`.
- General readiness checks are config-driven through `config/consultation.php` and require complaint, examination, diagnosis, and plan/disposition.
- Route completion uses `ConsultationSessionWorkflowService::completeRoute()`, which calls `ConsultationCompletionReadinessService::assertReady()`.
- Visit completion through status transition also calls the same readiness assertion when completing a visit with an active/latest route.
- Complete-and-open-next uses `ConsultationPlanningWorkflowService::openNext()` and calls the same readiness assertion before opening the next patient.
- Completion failures throw `ConsultationCompletionException`, return event `CONSULTATION_COMPLETION_BLOCKED`, and include the readiness payload in JSON responses.
- Blocking attempts are audited through `ActivityLogService`.
- The readiness card is `resources/views/consultations/partials/right-panel.blade.php`.
- Prescription safety remains separate from completion readiness and continues to run through prescription workflow checks before prescription creation.

## Files Added

- `app/Data/Consultation/Specialty/ConsultationSpecialtyReadinessResult.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessRuleRegistry.php`
- `app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessService.php`
- `tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php`
- `docs/CONSULTATION_SPECIALIST_EXTENSION_PHASE_7_COMPLETION_READINESS_REPORT.md`

## Files Modified

- `app/Services/Consultation/ConsultationCompletionReadinessService.php`
- `app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php`
- `resources/views/consultations/partials/right-panel.blade.php`
- `resources/views/consultations/partials/page-config.blade.php`
- `lang/en/consultation_specialties.php`
- `lang/fr/consultation_specialties.php`

## Specialty Readiness Design

`ConsultationSpecialtyReadinessRuleRegistry` defines code-based readiness rules per specialty profile. `ConsultationSpecialtyReadinessService` evaluates those rules against the active consultation route, core medical record data, procedure requests, and `consultation_specialty_entries`.

`ConsultationSpecialtyReadinessResult` exposes a Blade/JSON-safe payload with status, score, blocking items, warning items, optional items, completed items, all items, completion eligibility, fallback state, and summary text.

Supported evaluation paths include core complaint, examination, diagnosis, prescription, investigation, task, summary/plan, specialty entries, specialty entry fields, and custom specialty rules. If specialty context is missing, invalid, general medicine, or evaluation fails, the service falls back to the existing general readiness result.

## Specialty Rules Implemented

- General Medicine: wraps the existing core readiness checklist and preserves current behavior.
- Physiotherapy blocking: presenting problem, pain assessment, physical assessment, treatment plan.
- Physiotherapy warnings: missing session schedule and missing home exercise plan.
- Ophthalmology blocking: eye complaint, visual acuity, eye examination, diagnosis.
- Ophthalmology warnings: missing IOP and missing follow-up/plan.
- Dental blocking: dental complaint, oral/tooth exam, dental diagnosis, procedure/clinical plan, consent obtained when consent is required.
- Dental warnings: X-ray missing when extraction is planned and follow-up missing.

## Completion Blocking Integration

`ConsultationCompletionReadinessService::assertReady()` still evaluates the original general checklist first. It then resolves the active specialty profile and adds specialty blocking items to the same completion exception payload. Warning-only and optional specialty items do not block completion. If specialty readiness cannot evaluate, the existing general completion behavior is used.

## UI Changes

The existing completion readiness card now includes a compact specialty readiness section when the active profile is specialist. It shows the specialty status, score, blocking items, warnings, completed items, and optional items. Items include anchors where section targets are available. The original general readiness checklist remains visible.

The workspace now passes `specialtyReadiness` to both Blade and page config JSON.

## Backward Compatibility

General medicine readiness remains based on the existing `ConsultationCompletionReadinessService` and `config/consultation.php`. Existing route completion, visit completion, complete-and-open-next, activity logging, and prescription safety behavior remain active.

## Tests Added

`ConsultationSpecialtyReadinessTest` covers general fallback, physiotherapy blockers, physiotherapy warning-only readiness, ophthalmology visual acuity and IOP behavior, dental specialty diagnosis and consent behavior, wrong-profile isolation, order-set patches feeding readiness, workspace payload hydration, completion route blocking, warning-only completion allowance, and EN/FR readiness keys.

## Checks Run

- `php -l app/Data/Consultation/Specialty/ConsultationSpecialtyReadinessResult.php`
- `php -l app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessRuleRegistry.php`
- `php -l app/Services/Consultation/Specialty/ConsultationSpecialtyReadinessService.php`
- `php -l app/Services/Consultation/ConsultationCompletionReadinessService.php`
- `php -l tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php`
- `php artisan migrate`
- `php artisan db:seed --class=ConsultationSpecialtySeeder`
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php`
- `php artisan test tests/Feature/Consultations/ConsultationClinicalSafetyPhase7Test.php tests/Feature/Consultations/ConsultationSpecialtyFoundationTest.php tests/Feature/Consultations/ConsultationSpecialtyResolverTest.php tests/Feature/Consultations/ConsultationSpecialtyLayoutTest.php tests/Feature/Consultations/ConsultationSpecialtyEntryTest.php tests/Feature/Consultations/ConsultationSpecialtyFavoriteTest.php tests/Feature/Consultations/ConsultationSpecialtyOrderSetTest.php tests/Feature/Consultations/ConsultationSpecialtyReadinessTest.php tests/Feature/ConsultationWorkspaceStabilisationTest.php`
- `php artisan route:list`
- `php artisan view:cache`
- `php artisan view:clear`

Result: focused clinical/specialty/workspace tests passed with 80 tests and 380 assertions.

## Known Issues / Follow-up

- Phase 8 can use the same readiness sources for the specialty summary builder.
- Phase 9 doctor personal workspace can surface the same readiness payload in doctor-specific queues.
- A later admin configuration UI can expose readiness rules without changing this code registry.
- Later billing/service mapping can add billing readiness warnings without changing the clinical completion gate.
