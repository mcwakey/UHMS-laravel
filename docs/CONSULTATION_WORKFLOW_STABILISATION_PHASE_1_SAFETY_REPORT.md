# Consultation Workflow Stabilisation Phase 1 Safety Report

## Summary

Phase 1 adds server-side safety controls for consultation mutations before the larger consultation view, JavaScript, and controller refactors.

The implementation focuses on:

- shared consultation action validation
- durable idempotency for repeat submits
- consultation route context preservation
- locked, completed, and cancelled session blocking
- natural duplicate checks for billing-sensitive actions
- focused regression coverage for consultation-originated requests

## Idempotency Design

Added `App\Services\Consultation\ConsultationIdempotencyService` with durable storage in `consultation_action_idempotency_keys`.

The service reads keys from:

- `Idempotency-Key`
- `_idempotency_key`
- `idempotency_key`

Payloads are normalized and hashed with framework/control fields excluded. The matching scope is user, action, idempotency key, and payload hash.

Rules enforced:

- same key, same action, same payload replays the original reference
- same key, different payload is rejected
- missing keys are allowed to continue so legacy clients do not break
- replay events are logged without clinical payloads

A small Blade component now adds hidden idempotency keys to consultation forms:

- `resources/views/components/consultation-idempotency-key.blade.php`

The consultation page regenerates the key after a successful AJAX submit.

## Action Guard Design

Added shared guard classes under `App\Services\Consultation`:

- `ConsultationActionGuard`
- `ConsultationActionContext`
- `ConsultationActionException`

The guard validates:

- visit existence
- patient and visit ownership
- selected consultation route existence
- selected consultation route belongs to the visit
- current route can be safely resolved when route context is omitted
- route is not locked
- route is not completed unless correction access is allowed
- route is not cancelled
- user permission for the requested action where supplied

The controller receives a validated `ConsultationActionContext` and uses that context for downstream service calls.

## Mutation Endpoints Protected

The shared guard and idempotency service were wired into the consultation-originated mutation paths in:

- `app/Http/Controllers/Doctor/ConsultationController.php`
- `app/Http/Controllers/Admin/Appointments/ConsultationTaskController.php`

Covered workflows include:

- complaints
- HOPC
- examination
- diagnoses
- investigation notes
- lab requests
- procedure requests
- prescriptions
- treatments
- consultation tasks
- follow-up appointments
- referrals
- route transitions
- complete route
- cancel route
- send to investigation
- handoff/next-patient style transitions where routed through the consultation controller

Existing medical record edit checks remain in place, with route status enforcement made consistent in the shared services.

## Route Context Preservation

Consultation-originated records now preserve route context where the schema supports it.

Updated paths include:

- lab requests receive `visit_id`, `medical_record_id`, and `consultation_route_id`
- procedure requests receive `visit_id`, `medical_record_id`, and `consultation_route_id`
- prescriptions continue to preserve their medical record and route-backed context
- consultation tasks preserve the medical record and consultation route context

The specific lab modal issue was addressed by passing the validated route context into `LabService::createRequest`.

## Locked, Completed, And Cancelled Enforcement

Server-side blocking now applies before unsafe consultation mutations run.

Additional enforcement was added in:

- `app/Services/ConsultationService.php`
- `app/Services/PatientComplaintService.php`

These services now block locked, completed, and cancelled routes unless an explicit correction flow is authorized.

## Billing Duplicate Protection

Idempotency is the primary duplicate-submit protection.

Natural duplicate checks were also added as a safety net in:

- `app/Services/PrescriptionService.php`
- `app/Services/LabService.php`
- `app/Services/ProcedureRequestService.php`

The checks are intentionally narrow and recent-window based so legitimate repeat clinical orders are not broadly blocked.

The Phase 1 regression test confirms duplicate procedure submits do not duplicate invoice items or service rendering records for the create-only procedure request path.

## Activity Logging

The guard and idempotency service log meaningful workflow safety events without raw clinical payloads:

- `CONSULTATION_ACTION_BLOCKED_LOCKED_ROUTE`
- `CONSULTATION_ACTION_BLOCKED_COMPLETED_ROUTE`
- `CONSULTATION_ACTION_BLOCKED_CANCELLED_ROUTE`
- `CONSULTATION_IDEMPOTENCY_REPLAYED`
- `CONSULTATION_ROUTE_CONTEXT_MISSING`
- `CONSULTATION_ROUTE_CONTEXT_INVALID`
- `CONSULTATION_ROUTE_CONTEXT_PRESERVED`
- `CONSULTATION_ACTION_BLOCKED_PERMISSION`

## Tests Added

Added:

- `tests/Feature/Consultations/ConsultationWorkflowSafetyPhase1Test.php`

Coverage includes:

- double-submit prescription with same idempotency key creates one prescription
- double-submit lab request with same idempotency key creates one lab request
- double-submit procedure request with same idempotency key creates one procedure request
- same idempotency key with different payload is rejected
- completed route blocks prescription, lab, and procedure creation
- locked route blocks clinical mutation
- cancelled route blocks clinical mutation
- wrong route for visit is rejected
- missing route context resolves safely to the current consultation session
- lab request stores consultation route context
- procedure request stores consultation route context
- prescription route-backed context is preserved where supported
- duplicate procedure submit does not duplicate invoice items or service renderings

## Focused Tests Run

Verification completed:

```bash
php artisan migrate --force
php artisan view:cache && php artisan view:clear
php artisan permissions:audit --strict
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan test tests/Feature/Consultations/ConsultationWorkflowSafetyPhase1Test.php
php artisan test tests/Feature/ConsultationRouteSessionWorkflowTest.php tests/Feature/ConsultationClinicalSectionsTest.php tests/Feature/InsuranceAwareClinicalPricingTest.php tests/Feature/LabWorkflowTest.php tests/Feature/PharmacyWorkflowTest.php tests/Feature/ServiceRenderingWorkflowTest.php
git diff --check
find app database routes lang resources/views tests -name "*.php" -print0 | xargs -0 -n1 php -l
```

Results:

- migration completed successfully
- view cache and clear completed successfully
- permissions audit exited successfully with zero missing route permissions and zero unprotected admin mutation routes
- localisation audit reported active runtime candidates: 0
- localisation parity check passed
- Phase 1 safety tests passed: 10 tests, 38 assertions
- focused neighboring workflow tests passed: 49 tests, 210 assertions
- PHP lint completed successfully
- `git diff --check` completed successfully before this report was added

Observed existing warnings:

- permissions audit still reports pre-existing possible duplicate permission names such as `consultation.create` and `consultations.create`
- PHP lint reports an existing PHP deprecation warning in `app/Services/HRService.php` for nullable parameter handling on `getLeaveBalance()`

## Known Limitations

- the large consultation Blade file remains
- the large consultation controller remains
- the full JavaScript refactor is deferred to Phase 2
- prescription clinical safety checks are deferred
- the completion checklist is deferred
- the wide full suite was not run because Phase 1 requested focused tests only
- this phase does not redesign billing, payer selection, or insurance pricing logic

## Next Recommended Phase

Phase 2 should extract the consultation page JavaScript into focused modules and reduce repeated inline AJAX handling.

Recommended Phase 2 targets:

- one shared AJAX form submit helper
- one shared idempotency key refresh path
- centralized modal lifecycle handling
- reduced duplicated success/error rendering
- controller split into smaller action controllers after frontend behavior is stable
