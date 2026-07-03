# Consultation Workflow Stabilisation Phase 6 Browser Smoke Report

## Summary

Phase 6 completed the browser fixture/auth gap left by Phase 5 and enabled the consultation workspace Playwright smoke test.

The work also moved the remaining practical clinical-entry orchestration out of `HandlesConsultationClinicalEntries` into a workflow service while preserving the existing validation rules, route names, middleware, response shapes, idempotency behaviour, and Phase 2 JavaScript contract.

## Fixture Discovery Strategy

Implemented both preferred safe discovery paths:

- `App\Services\Consultation\ConsultationBrowserFixtureService`
- `php artisan consultation:e2e-fixture --json`
- `storage/app/testing/consultation-workspace-e2e.json`

The fixture is guarded to `local` and `testing` environments only. It refuses to run elsewhere.

## Authentication Strategy

The fixture user remains:

- Email: `consultation.e2e@uhms.test`
- Password: `password`

`tests-e2e/tests/support/auth.ts` now exposes `loginWithCredentials(page, email, password)`, allowing the Playwright spec to authenticate directly from fixture metadata without production credentials.

## E2E Seeder Behaviour

`Database\Seeders\Testing\ConsultationWorkspaceE2ESeeder` now delegates to `ConsultationBrowserFixtureService`.

The fixture safely re-runs by reusing core records:

- Doctor user
- Fixture role and permissions
- Patient
- Visit
- Active consultation route
- Medical record
- Consultation, lab, and procedure departments
- Consultation, lab, and procedure services
- Fixture drug

The fixture doctor also receives `notifications.view` because the shared app layout polls `/admin/notifications/recent`; without it, the consultation page produced 403 browser console errors.

## Metadata Format

The fixture metadata includes:

```json
{
  "email": "consultation.e2e@uhms.test",
  "password": "password",
  "consultation_url": "/admin/consultations/{visit}/routes/{route}",
  "consultation_absolute_url": "http://localhost/admin/consultations/{visit}/routes/{route}",
  "visit_id": 1,
  "visit_number": "E2E-CONSULTATION-VISIT",
  "patient_id": 1,
  "patient_number": "E2E-CONSULTATION-000001",
  "consultation_route_id": 1,
  "medical_record_id": 1,
  "doctor_id": 1,
  "consultation_department_id": 1,
  "lab_department_id": 1,
  "procedure_department_id": 1,
  "consultation_service_id": 1,
  "lab_service_id": 1,
  "procedure_service_id": 1,
  "drug_id": 1,
  "metadata_path": "storage/app/testing/consultation-workspace-e2e.json"
}
```

## Playwright Smoke

`tests-e2e/tests/consultation-workspace.spec.ts` is now enabled and passing.

Coverage:

- Seeds/discovers fixture metadata through `consultation:e2e-fixture --json`
- Logs in as the fixture doctor
- Opens the route-specific consultation workspace
- Fails on page errors, console errors, and failed HTTP responses
- Verifies `consultation-page-config`
- Verifies route context hidden inputs
- Verifies idempotency key fields
- Exercises procedure department-to-service loading
- Opens the lab request modal and validates required fields
- Exercises prescription row add/remove
- Refreshes a section and confirms delegated controls still work

## Clinical Entry Service Cleanup

Added `App\Services\Consultation\ConsultationClinicalEntryWorkflowService`.

Moved practical orchestration out of the clinical-entry concern:

- Idempotent clinical entry creation
- Complaint create/update/delete delegation
- HOPC create/update/delete logging flow
- Examination create/update/delete logging flow
- Diagnosis create/update/delete and primary-setting delegation
- Treatment create/update/delete delegation

The controller concern still owns request validation and response formatting, so the existing HTTP contract remains stable.

## Contracts Preserved

Preserved:

- Route names
- Middleware
- Phase 2 JavaScript data attributes and JSON config block
- AJAX response shape
- Redirect response shape
- Section refresh behaviour
- Phase 4 FormRequests
- `ConsultationActionGuard`
- `ConsultationIdempotencyService`

Deferred as instructed:

- Prescription clinical safety checks
- Consultation completion checklist
- UI redesign

## Tests Added

Added:

- `tests/Feature/Consultations/ConsultationBrowserFixturePhase6Test.php`
- `tests-e2e/tests/support/consultation-fixture.ts`

Updated:

- `tests-e2e/tests/consultation-workspace.spec.ts`
- `tests-e2e/tests/support/auth.ts`
- Phase 5 service extraction assertions

## Verification Commands Run

Passed:

```bash
php artisan test tests/Feature/Consultations/ConsultationWorkflowSafetyPhase1Test.php tests/Feature/Consultations/ConsultationJavascriptLifecyclePhase2Test.php tests/Feature/Consultations/ConsultationStructurePhase3Test.php tests/Feature/Consultations/ConsultationControllerExtractionPhase4Test.php tests/Feature/Consultations/ConsultationServiceExtractionPhase5Test.php tests/Feature/Consultations/ConsultationBrowserFixturePhase6Test.php
```

Result: 45 passed, 343 assertions.

Passed:

```bash
php artisan test tests/Feature/ConsultationRouteSessionWorkflowTest.php tests/Feature/ConsultationClinicalSectionsTest.php tests/Feature/InsuranceAwareClinicalPricingTest.php tests/Feature/LabWorkflowTest.php tests/Feature/PharmacyWorkflowTest.php tests/Feature/ServiceRenderingWorkflowTest.php
```

Result: 49 passed, 213 assertions.

Passed:

```bash
npx playwright test tests-e2e/tests/consultation-workspace.spec.ts
```

Result: 1 passed.

Passed:

```bash
npm run build
php artisan view:cache
php artisan view:clear
php artisan route:list | rg consultation
php artisan permissions:audit --strict
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
git diff --check -- app database docs resources routes scripts tests tests-e2e lang
```

Permission audit result:

- Missing route permissions: 0
- Admin mutation routes without `can:` or `role:` middleware: 0
- Duplicate permission-name advisory remains: 12 pairs

Localisation result:

- Active runtime candidates: 0
- EN/FR parity OK

## Known Limitations

- Full `git diff --check` across the entire repository could not complete because the worktree currently contains generated `public/build` artifact deletions, including `public/build/scss/pages/_appointment.scss`. The source/docs/tests path-scoped diff check passed.
- Wide full suite was not run.
- Duplicate permission-name advisory remains pending.
- Prescription clinical safety checks remain deferred.
- Consultation completion checklist remains deferred.
- Full UI redesign remains deferred.

## Next Recommended Phase

Proceed to the next consultation workflow phase focused on clinical safety and completion readiness:

- Prescription allergy/interaction/duplicate checks
- Completion checklist prerequisites
- Broader browser coverage for successful lab/procedure/prescription submissions
