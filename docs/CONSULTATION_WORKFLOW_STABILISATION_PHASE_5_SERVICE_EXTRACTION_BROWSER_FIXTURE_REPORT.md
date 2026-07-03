# Consultation Workflow Stabilisation Phase 5 Service Extraction Browser Fixture Report

## Summary

Phase 5 moved business-heavy consultation orchestration out of controller concerns into dedicated workflow services. The workflow controllers and concerns still preserve route names, middleware, permissions, FormRequests, redirects, and the Phase 2 JavaScript contract.

The browser smoke test remains skipped, but there is now a guarded E2E consultation fixture strategy that can seed a stable consultation workspace when the Playwright harness is ready to discover the seeded route URL and authenticate as the fixture user.

## Services Created

Created under `app/Services/Consultation`:

- `ConsultationWorkspacePayloadService`
- `ConsultationOrderWorkflowService`
- `ConsultationPrescriptionWorkflowService`
- `ConsultationPlanningWorkflowService`
- `ConsultationSessionWorkflowService`

## Logic Moved Out Of Controller Concerns

Moved into services:

- workspace summary payload preparation
- department service pricing payloads
- department investigation-info payloads
- investigation note plus lab-request creation orchestration
- lab request route-context payload preparation
- procedure request route-context payload preparation
- prescription creation idempotency orchestration
- route completion and cancellation orchestration
- visit transition orchestration
- follow-up creation/update/cancel orchestration
- next-patient routing orchestration
- referral service/doctor/department resolution

The concerns now focus on request validation, guard/context resolution, calling the workflow service, and returning the existing JSON or redirect response.

## Legacy And Workflow Controller Status

`Doctor\ConsultationController` remains a compatibility shell only.

Workflow controllers remain the route owners:

- `ConsultationWorkspaceController`
- `ConsultationSessionController`
- `ConsultationClinicalEntryController`
- `ConsultationOrderController`
- `ConsultationPrescriptionController`
- `ConsultationPlanningController`

The domain concerns remain as an intermediate HTTP layer, but their heaviest persistence and payload orchestration has moved into services.

## FormRequests

The Phase 4 FormRequests remain in use for high-risk actions:

- prescriptions
- lab requests
- procedure requests
- diagnoses
- referrals
- follow-ups
- route transition/completion/cancellation

Validation semantics were not intentionally changed.

## Guard And Idempotency

Phase 1 safety remains preserved:

- mutation endpoints still resolve guarded consultation context
- locked, completed, and cancelled route blocking remains enforced
- wrong route rejection remains enforced
- lab/procedure/prescription duplicate-submit protection remains idempotent
- billing duplicate protection remains covered by the Phase 1 tests

`ConsultationActionGuard` and `ConsultationIdempotencyService` are still central dependencies on the shared workflow boundary and workflow services.

## JavaScript And Blade Contracts

The Phase 2 JavaScript contract remains unchanged:

- `consultation-page-config`
- `resources/js/Pages/consultation-show.js`
- delegated `data-consultation-action`
- delegated `data-consultation-form`
- route-context/idempotency attributes

The Phase 3 Blade partial structure remains unchanged.

## Browser Fixture Strategy

Added:

- `database/seeders/Testing/ConsultationWorkspaceE2ESeeder.php`

Run manually in local/testing only:

```bash
php artisan db:seed --class=Database\\Seeders\\Testing\\ConsultationWorkspaceE2ESeeder
```

The seeder creates:

- consultation E2E doctor user
- permissions and role needed for consultation workflow actions
- patient
- consulting visit
- active consultation route
- medical record
- consultation, lab, and procedure departments
- lab/procedure/consultation services
- prescription drug fixture

The seeder is guarded to `local` and `testing` environments and is not referenced by the default seed chain.

## Playwright Smoke Status

`tests-e2e/tests/consultation-workspace.spec.ts` remains skipped.

Reason: the fixture exists, but the browser harness still needs a stable way to discover the seeded route URL and authenticate as `consultation.e2e@uhms.test`. The skipped spec now documents the fixture command and the remaining smoke coverage targets.

## Tests Added

Added:

- `tests/Feature/Consultations/ConsultationServiceExtractionPhase5Test.php`

Coverage includes:

- concern-to-service delegation
- workspace payload service output
- lab/procedure route-context preservation
- prescription workflow idempotency replay
- session workflow completed-route blocking
- Phase 4 FormRequest preservation
- Phase 2 JS config rendering
- view cache compilation
- browser fixture strategy

## Verification

Completed successfully during Phase 5:

- `php -l` over new consultation services, testing seeder, and Phase 5 test
- `php artisan test tests/Feature/Consultations/ConsultationServiceExtractionPhase5Test.php`
- `php artisan test tests/Feature/Consultations/ConsultationWorkflowSafetyPhase1Test.php tests/Feature/Consultations/ConsultationJavascriptLifecyclePhase2Test.php tests/Feature/Consultations/ConsultationStructurePhase3Test.php tests/Feature/Consultations/ConsultationControllerExtractionPhase4Test.php tests/Feature/Consultations/ConsultationServiceExtractionPhase5Test.php`
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
- Full UI redesign remains deferred.
- Playwright consultation smoke remains skipped until browser fixture URL discovery and login are wired.
- Duplicate permission-name advisory remains pending manual review.
- Wide full suite was not run.

## Next Recommended Phase

Phase 6 should wire the Playwright consultation fixture discovery/auth flow, enable the smoke test, and continue moving any remaining business logic from clinical-entry concerns into focused services.
