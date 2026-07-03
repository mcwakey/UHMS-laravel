# Consultation Workflow Stabilisation Phase 4 Controller Extraction Report

## Summary

Phase 4 moved consultation route handling away from the inherited monolithic `Doctor\ConsultationController` and into workflow-specific controllers. The old controller is now a compatibility shell only. Shared dependencies, guard access, idempotency access, and response helpers live on `ConsultationWorkflowController`.

Business rules, route names, middleware, permissions, Blade structure, and the Phase 2 JavaScript data contract were preserved.

## Controllers Extracted

Workflow routes now resolve to:

- `ConsultationWorkspaceController`
- `ConsultationSessionController`
- `ConsultationClinicalEntryController`
- `ConsultationOrderController`
- `ConsultationPrescriptionController`
- `ConsultationPlanningController`

To keep the extraction safe and avoid duplicated code, each workflow controller uses one domain concern:

- `HandlesConsultationWorkspace`
- `HandlesConsultationSessions`
- `HandlesConsultationClinicalEntries`
- `HandlesConsultationOrders`
- `HandlesConsultationPrescriptions`
- `HandlesConsultationPlanning`

## Methods Moved

Moved out of the legacy controller:

- workspace: index, show, history, summary fragment, consultation preview helpers
- sessions: start, route store, route activation, completion, cancellation, visit transition
- clinical entries: complaints, HOPC, examination, diagnoses, treatments, suggestions
- orders: investigation notes, department service lookup, lab requests, procedure requests, send to investigation
- prescriptions: store, update, delete prescription
- planning: follow-up, referral, next patient workflows

## Legacy Controller

`Doctor\ConsultationController` now remains only as a compatibility shell while older imports are retired. It no longer declares the high-risk consultation mutation route handlers.

## FormRequests Added

Focused FormRequests added under `app/Http/Requests/Consultations`:

- `StoreConsultationPrescriptionRequest`
- `StoreConsultationLabRequest`
- `StoreConsultationProcedureRequest`
- `StoreConsultationDiagnosisRequest`
- `StoreConsultationReferralRequest`
- `StoreConsultationFollowUpRequest`
- `TransitionConsultationRouteRequest`
- `CompleteConsultationRouteRequest`
- `CancelConsultationRouteRequest`

Rules mirror the previous inline validation to avoid business-rule drift.

## Guard And Idempotency

`ConsultationActionGuard` and `ConsultationIdempotencyService` remain central dependencies on `ConsultationWorkflowController`. Mutation handlers still go through the Phase 1 guard and idempotency flow for locked, completed, cancelled, and duplicate-submission protection.

## Route And Response Stability

Consultation route names, URIs, HTTP methods, middleware, and permission middleware were preserved. AJAX response shapes and redirect behaviour were not intentionally changed.

## Tests Added

Added:

- `tests/Feature/Consultations/ConsultationControllerExtractionPhase4Test.php`

Coverage includes route ownership, legacy shell status, workflow controller methods, high-risk FormRequest type hints, and guard/idempotency placement.

## Verification

Completed successfully:

- `php -l` over extracted workflow controllers and consultation FormRequests
- `php artisan test tests/Feature/Consultations/ConsultationWorkflowSafetyPhase1Test.php tests/Feature/Consultations/ConsultationJavascriptLifecyclePhase2Test.php tests/Feature/Consultations/ConsultationStructurePhase3Test.php tests/Feature/Consultations/ConsultationControllerExtractionPhase4Test.php`
- `php artisan test tests/Feature/ConsultationRouteSessionWorkflowTest.php tests/Feature/ConsultationClinicalSectionsTest.php tests/Feature/InsuranceAwareClinicalPricingTest.php tests/Feature/LabWorkflowTest.php tests/Feature/PharmacyWorkflowTest.php tests/Feature/ServiceRenderingWorkflowTest.php`
- `npm run build`
- `php artisan view:cache && php artisan view:clear`
- `php artisan route:list | grep consultation`
- `php artisan permissions:audit --strict`
- `php scripts/localisation-audit.php`
- `php scripts/localisation-parity-check.php`
- `git diff --check`

## Known Limitations

- Prescription clinical safety checks remain deferred.
- Consultation completion checklist remains deferred.
- Browser smoke remains skipped until stable consultation fixture data exists.
- Duplicate permission-name advisory remains pending manual review.
- Wide full suite was not run.
- Domain concerns are an intermediate extraction step; a later phase can move methods directly into controller classes or service classes once behaviour remains stable across more cycles.

## Next Recommended Phase

Phase 5 should continue shrinking controller concerns into dedicated services where business logic still sits close to HTTP handling, then enable the browser smoke test once stable seed data is available.
