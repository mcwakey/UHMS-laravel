# Consultation Workflow Stabilisation Phase 7 Clinical Safety Completion Report

## Summary

Phase 7 adds additive prescription safety checks and consultation completion readiness gates to the stabilised consultation workflow.

The implementation preserves existing route names, middleware, idempotency keys, Phase 2 JavaScript contracts, section refresh behaviour, and consultation controller extraction boundaries.

## Prescription Safety

Added:

- `App\Services\Consultation\PrescriptionSafetyService`
- `App\Services\Consultation\PrescriptionSafetyResult`
- `App\Services\Consultation\PrescriptionSafetyException`

Safety checks now run before a new prescription is created:

- recorded patient allergy conflict
- duplicate active or recent medication
- missing diagnosis when enabled by config policy
- unverified placeholder dose
- defensive required-field blocking

Warnings require `safety_override_reason` before creation. Blocking errors prevent creation.

The idempotency boundary remains intact: duplicate submits and payload mismatches are resolved before safety checks create duplicate-medication false positives.

## Completion Readiness

Added:

- `App\Services\Consultation\ConsultationCompletionReadinessService`
- `App\Services\Consultation\ConsultationCompletionReadinessResult`
- `App\Services\Consultation\ConsultationCompletionException`

Route completion is now blocked until the selected consultation route has:

- presenting complaint or reason for visit
- examination findings
- diagnosis
- treatment plan, prescription, task, or disposition note

The readiness gate is applied to:

- normal route completion
- visit transition to completed
- complete-and-open-next-patient shortcut

Completion override is intentionally not enabled in Phase 7; blocked attempts are audited.

## UI

Added a compact completion-readiness card to the consultation right panel.

Added a prescription safety override panel inside the existing prescription form. AJAX warning responses now reveal the panel, list warning messages, preserve warning codes, and focus the override reason field.

## Audit Trail

Audited events:

- `PRESCRIPTION_SAFETY_WARNING_TRIGGERED`
- `PRESCRIPTION_SAFETY_BLOCKED`
- `PRESCRIPTION_SAFETY_OVERRIDE_ACCEPTED`
- `CONSULTATION_COMPLETION_BLOCKED`

Blocked prescription warning audits are written outside the idempotent create transaction so the audit record is not rolled back when the prescription creation is intentionally rejected.

## Configuration

Added `config/consultation.php`:

- `prescriptions.require_diagnosis_before_prescribing`
- `prescriptions.allow_missing_diagnosis_override`
- `prescriptions.duplicate_active_medication_days`
- `completion_checklist.enabled`
- `completion_checklist.requirements`

The default keeps diagnosis-before-prescribing policy disabled to avoid breaking existing workflows; tests cover the enabled policy.

## Localisation

Added EN/FR keys in:

- `lang/en/consultation.php`
- `lang/fr/consultation.php`

Covered keys include prescription warnings, override reason text, completion readiness status, missing requirements, and individual checklist requirement labels.

## Browser Coverage

Updated the consultation workspace Playwright smoke:

- fixture patient has a deterministic Amoxicillin allergy
- readiness card is visible
- prescription submit with fixture drug shows the safety warning/override panel

## Tests Added

Added:

- `tests/Feature/Consultations/ConsultationClinicalSafetyPhase7Test.php`

Updated:

- consultation prescription workflow to keep idempotency replay stable
- route completion fixtures to satisfy the new readiness gate
- consultation browser fixture and Playwright smoke

## Verification Commands Run

Passed:

```bash
php artisan test tests/Feature/Consultations/ConsultationClinicalSafetyPhase7Test.php tests/Feature/Consultations/ConsultationWorkflowSafetyPhase1Test.php tests/Feature/Consultations/ConsultationServiceExtractionPhase5Test.php tests/Feature/ConsultationRouteSessionWorkflowTest.php tests/Feature/ConsultationFollowUpAndNextPatientTest.php
```

Result: 45 passed, 221 assertions.

Passed:

```bash
php artisan test tests/Feature/Consultations/ConsultationWorkflowSafetyPhase1Test.php tests/Feature/Consultations/ConsultationJavascriptLifecyclePhase2Test.php tests/Feature/Consultations/ConsultationStructurePhase3Test.php tests/Feature/Consultations/ConsultationControllerExtractionPhase4Test.php tests/Feature/Consultations/ConsultationServiceExtractionPhase5Test.php tests/Feature/Consultations/ConsultationBrowserFixturePhase6Test.php tests/Feature/Consultations/ConsultationClinicalSafetyPhase7Test.php
```

Result: 52 passed, 372 assertions.

Passed:

```bash
php artisan test tests/Feature/ConsultationClinicalSectionsTest.php tests/Feature/InsuranceAwareClinicalPricingTest.php tests/Feature/LabWorkflowTest.php tests/Feature/PharmacyWorkflowTest.php tests/Feature/ServiceRenderingWorkflowTest.php
```

Result: 38 passed, 159 assertions.

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
git diff --check -- app config docs lang resources tests tests-e2e
```

Permission audit result:

- Missing route permissions: 0
- Admin mutation routes without `can:` or `role:` middleware: 0
- Duplicate permission-name advisory remains: 12 pairs

Localisation result:

- Active runtime candidates: 0
- EN/FR parity OK

## Known Limitations

- Drug-drug interaction rules remain a placeholder hook; no external interaction database is integrated.
- Completion override is not enabled in Phase 7.
- The readiness card is refreshed on page load and normal section refresh reloads, not as a standalone live widget.
- Wide full suite was not run.
