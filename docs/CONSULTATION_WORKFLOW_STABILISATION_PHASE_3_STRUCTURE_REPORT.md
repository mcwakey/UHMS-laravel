# Consultation Workflow Stabilisation Phase 3 Structure Report

## Scope

Phase 3 focused on reducing the consultation workspace blast radius without changing the public workflow surface. The route names, URIs, middleware, Blade data attributes, Vite entrypoint, and AJAX JSON contract were kept stable so existing links, permissions, and the Phase 2 JavaScript lifecycle continue to work.

## Controller Structure

The consultation routes are now grouped behind workflow-specific controller classes:

- `ConsultationWorkspaceController`
- `ConsultationSessionController`
- `ConsultationClinicalEntryController`
- `ConsultationOrderController`
- `ConsultationPrescriptionController`
- `ConsultationPlanningController`

These classes currently extend the existing `Doctor\ConsultationController` so Phase 3 can split route ownership safely while preserving the guarded implementation added in Phase 1. A deeper method extraction into standalone controller methods and FormRequests remains a later, lower-risk refactor.

## Blade Structure

The consultation page is now composed from focused partials:

- `resources/views/consultations/partials/session-context.blade.php`
- `resources/views/consultations/partials/workflow-sidebar.blade.php`
- `resources/views/consultations/partials/right-panel.blade.php`
- `resources/views/consultations/partials/modals.blade.php`
- `resources/views/consultations/partials/page-config.blade.php`

The root `resources/views/consultations/show.blade.php` remains the page shell and tab content owner, but the surrounding session context, sidebar, right rail, modal stack, and JavaScript config are no longer embedded in one large file.

## JavaScript Contract

The Phase 2 module contract is preserved:

- `@vite('resources/js/Pages/consultation-show.js')`
- `id="consultation-page-config"`
- safe `application/json` config with `JSON_HEX_*` encoding
- delegated `data-consultation-action` controls
- delegated `data-consultation-form` AJAX forms
- route-context and idempotency hidden inputs

No inline consultation workflow handlers are present in the root view or extracted partials.

## Browser Smoke Coverage

A skipped Playwright smoke spec was added at `tests-e2e/tests/consultation-workspace.spec.ts`. It documents the intended browser checks for Phase 4:

- workspace load without console errors
- procedure department/service loading
- lab request modal validation/submission
- prescription row add/remove
- route-context hidden input before submit

The test remains skipped because this repository does not yet expose a stable seeded consultation visit fixture for end-to-end browser execution.

## Privacy And Permissions

The structure split did not add raw patient-data rendering. Existing patient display components remain in place, and no new broad patient fields were introduced in the extracted partials.

`php artisan permissions:audit --strict` completed successfully with zero missing route permissions and zero unprotected admin mutation routes. The audit still reports the same possible duplicate permission pairs for manual review.

## Localisation

Localisation checks completed successfully:

- `php scripts/localisation-audit.php`
- `php scripts/localisation-parity-check.php`

The audit report remains at `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.

## Verification

Commands completed successfully:

- `php artisan test tests/Feature/Consultations/ConsultationWorkflowSafetyPhase1Test.php`
- `php artisan test tests/Feature/Consultations/ConsultationJavascriptLifecyclePhase2Test.php`
- `php artisan test tests/Feature/Consultations/ConsultationStructurePhase3Test.php`
- `php artisan test tests/Feature/ConsultationRouteSessionWorkflowTest.php tests/Feature/ConsultationClinicalSectionsTest.php tests/Feature/InsuranceAwareClinicalPricingTest.php tests/Feature/LabWorkflowTest.php tests/Feature/PharmacyWorkflowTest.php tests/Feature/ServiceRenderingWorkflowTest.php`
- `php artisan view:cache && php artisan view:clear`
- `npm run build`
- `php artisan route:list | grep consultation`
- `php artisan permissions:audit --strict`
- `php scripts/localisation-audit.php`
- `php scripts/localisation-parity-check.php`

## Remaining Work

- Extract inherited workflow methods from `Doctor\ConsultationController` into the new workflow controllers.
- Introduce focused FormRequests for mutation actions where validation is still embedded in controller methods.
- Enable the skipped Playwright smoke once stable consultation seed data and login helpers exist.
- Continue clinical-safety tightening around prescription completeness, route completion readiness, and session handover guardrails.

