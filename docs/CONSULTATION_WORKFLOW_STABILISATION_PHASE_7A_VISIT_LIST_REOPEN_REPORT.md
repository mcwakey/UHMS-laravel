# Consultation Workflow Stabilisation Phase 7A Visit List Reopen Report

## Summary

Phase 7A adds visit-list visibility for active admissions and same-day discharges, plus a controlled audited consultation-session reopen workflow.

The Phase 1 mutation guard remains intact: completed, locked, and cancelled consultation routes still block clinical mutations unless a completed route is explicitly reopened through the new workflow.

## Visit List Visibility Rules

The visit list now includes:

- normal current-day visits according to existing filters
- visits with active admissions, even when the visit date is older
- visits with admission discharge dates in the selected date range
- same-day completed outpatient visits, so authorised users can reopen the consultation session when needed

Historical discharged inpatients are still hidden by the default current-day visit list after discharge day. They appear when the user explicitly filters the relevant historical discharge date.

## Active Admission Detection

Active admission is detected from the admission row:

- `status` is `admitted` or `on_leave`
- `actual_discharge_date` is null

The list uses the application timezone through Laravel date helpers.

## Same-Day Discharge Detection

Same-day discharge is detected from `admissions.actual_discharge_date` using the application timezone and `today()`.

## Completed Outpatient Reopen Policy

Completed outpatient consultation routes are reopen-eligible when:

- the visit is outpatient
- the visit is completed today
- the consultation route status is `COMPLETED`
- the route is not cancelled
- the route is not locked
- the user has `consultations.reopen` and `consultations.reopen_completed`

## Same-Day Discharged Inpatient Reopen Policy

Completed inpatient consultation routes are reopen-eligible when:

- the linked admission was discharged today
- the consultation route status is `COMPLETED`
- the route is not cancelled
- the route is not locked
- the user has `consultations.reopen` and `consultations.reopen_same_day_discharge`

Discharges before today remain blocked for normal reopen permission and require a future stronger correction workflow.

## Permissions Added

Added narrow permissions:

- `consultations.reopen`
- `consultations.reopen_completed`
- `consultations.reopen_same_day_discharge`

Fresh seeding and an idempotent migration create the permissions. Super Admin/Admin receive all three. Doctor receives `consultations.reopen` and `consultations.reopen_completed`.

## Routes Added

Added:

```text
POST admin/consultations/{visit}/routes/{route}/reopen
name: admin.consultations.routes.reopen
middleware: can:consultations.reopen
```

The request requires `reason`.

## Guard Changes

`ConsultationActionGuard` was not weakened.

The new workflow changes an eligible completed route back to `ACTIVE` with reopen metadata. After that explicit audited transition, existing guarded mutation endpoints work through the normal active-route path. Completed routes without reopen, locked routes, and cancelled routes still block mutation.

## Status Transition Behaviour

Reopen keeps the official visit lifecycle intact. The visit may remain `completed` or `discharged`.

The consultation route changes from `COMPLETED` to `ACTIVE` and records:

- `reopened_at`
- `reopened_by`
- `reopen_reason`
- `reopen_count`

Any other active route on the same visit is paused before the reopened route is activated.

## UI Changes

Visit and consultation lists now show badges for:

- On admission
- Discharged today
- Completed today
- Reopen available
- Read-only

Eligible completed sessions open into a read-only workspace with a reopen form requiring a reason. After reopen, the workspace reloads as editable through the normal active-route contract.

## Audit Events

The reopen workflow logs through `ActivityLogService`:

- `CONSULTATION_REOPEN_REQUESTED`
- `CONSULTATION_REOPEN_ACCEPTED`
- `CONSULTATION_REOPEN_BLOCKED`
- `CONSULTATION_REOPEN_AFTER_SAME_DAY_DISCHARGE`
- `CONSULTATION_REOPEN_COMPLETED_OUTPATIENT`

Metadata includes visit id, consultation route id, patient id, previous/new route status, reopen reason, user, and discharge timestamp where relevant. Raw clinical notes are not logged.

## Tests Added

Added:

```text
tests/Feature/Consultations/ConsultationReopenPhase7ATest.php
```

Coverage includes active admission visibility, same-day discharge visibility, historical discharge default exclusion, authorised/unauthorised reopen actions, reason validation, route reactivation, duplicate route/medical-record prevention, guarded mutation after reopen, completed-without-reopen blocking, cancelled/locked blocking, and audit logging.

## Verification Commands Run

Passed:

```bash
npm run build
php artisan view:cache
php artisan view:clear
php artisan route:list | Select-String consultation
php artisan route:list | Select-String visit
php artisan permissions:audit --strict
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan test tests\Feature\Consultations\ConsultationWorkflowSafetyPhase1Test.php tests\Feature\Consultations\ConsultationJavascriptLifecyclePhase2Test.php tests\Feature\Consultations\ConsultationStructurePhase3Test.php tests\Feature\Consultations\ConsultationControllerExtractionPhase4Test.php tests\Feature\Consultations\ConsultationServiceExtractionPhase5Test.php tests\Feature\Consultations\ConsultationBrowserFixturePhase6Test.php tests\Feature\Consultations\ConsultationReopenPhase7ATest.php
php artisan test tests\Feature\ConsultationRouteSessionWorkflowTest.php tests\Feature\ConsultationClinicalSectionsTest.php tests\Feature\LabWorkflowTest.php tests\Feature\PharmacyWorkflowTest.php tests\Feature\ServiceRenderingWorkflowTest.php
php artisan test tests\Feature\VisitStatusPatientPathwayWorkflowTest.php
git diff --check -- app database docs resources routes scripts tests tests-e2e lang
```

Results:

- Consultation Phase 1-7A tests: 58 passed, 387 assertions
- Neighbouring workflow tests: 47 passed, 203 assertions
- Substitute visit workflow test: 6 passed, 28 assertions
- Localisation audit: Active runtime candidates: 0
- Localisation parity: EN/FR parity OK
- Permission audit: 0 missing route permissions, 0 unprotected admin mutation routes

The requested `tests\Feature\VisitWorkflowTest.php` file does not exist in this repository. `tests\Feature\VisitStatusPatientPathwayWorkflowTest.php` was run as the closest available visit workflow coverage.

Browser smoke:

```bash
npx playwright test tests-e2e/tests/consultation-workspace.spec.ts
```

Blocked by missing local Playwright Chromium binary:

```text
Executable doesn't exist at C:\Users\WAKEY\AppData\Local\ms-playwright\chromium_headless_shell-1228\chrome-headless-shell-win64\chrome-headless-shell.exe
```

Two attempts to run `npx playwright install chromium` timed out, so the smoke could not be completed in this environment.

## Known Limitations

- Historical discharged inpatient reopen remains blocked without a stronger correction policy.
- Locked/legal-finalised consultation sessions remain blocked.
- The duplicate permission-name advisory remains pre-existing and unchanged.
- The wide full suite was not run.
- Playwright smoke is environment-blocked until the Chromium browser binary can be installed.

## Next Recommended Phase

Proceed with a stronger clinical correction policy for historical discharged inpatient records and legal/finalised sessions, keeping it separate from normal same-day reopen permissions.
