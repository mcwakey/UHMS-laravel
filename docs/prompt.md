# UHMS Consultation Workflow Stabilisation — Phase 7: Prescription Safety Checks & Completion Readiness

## Goal

Continue consultation workflow stabilisation after Phases 1–6.

Previous phases stabilised the consultation workspace technically:

```text id="bfhxsj"
Phase 1: server-side idempotency, action guard, route context safety
Phase 2: JavaScript lifecycle and AJAX form refactor
Phase 3: Blade/controller structure split
Phase 4: controller extraction and FormRequest validation
Phase 5: service extraction and E2E fixture strategy
Phase 6: Playwright fixture auth, smoke enablement, and clinical-entry service cleanup
```

Phase 7 must now improve clinical safety and completion readiness.

Focus on:

```text id="zqgmce"
prescription safety checks
prescription override workflow
consultation completion checklist
minimum documentation requirements
safe completion blocking
clinical warning visibility
audit trail for overrides and completion decisions
broader browser smoke coverage for successful lab/procedure/prescription actions
```

Do not redesign the full UI.

Do not change route names.

Do not change middleware.

Do not change the Phase 2 JavaScript contract.

Do not change the default launch seed.

Do not run the wide full suite unless explicitly instructed.

---

## 1. Required Context

Read:

```text id="csu771"
docs/CONSULTATION_VIEW_GAP_ANALYSIS_REPORT.md
docs/CONSULTATION_JS_REPETITION_AUDIT_REPORT.md
docs/CONSULTATION_WORKFLOW_IMPROVEMENT_PLAN.md
docs/CONSULTATION_WORKFLOW_STABILISATION_PHASE_1_SAFETY_REPORT.md
docs/CONSULTATION_WORKFLOW_STABILISATION_PHASE_2_JS_LIFECYCLE_REPORT.md
docs/CONSULTATION_WORKFLOW_STABILISATION_PHASE_3_STRUCTURE_REPORT.md
docs/CONSULTATION_WORKFLOW_STABILISATION_PHASE_4_CONTROLLER_EXTRACTION_REPORT.md
docs/CONSULTATION_WORKFLOW_STABILISATION_PHASE_5_SERVICE_EXTRACTION_BROWSER_FIXTURE_REPORT.md
docs/CONSULTATION_WORKFLOW_STABILISATION_PHASE_6_BROWSER_SMOKE_REPORT.md
```

Inspect:

```text id="z0b01j"
app/Services/Consultation
app/Services/PrescriptionService.php
app/Services/ConsultationPrescriptionWorkflowService.php
app/Services/ConsultationSessionWorkflowService.php
app/Services/ConsultationCompletionService.php if present
app/Models/Prescription.php
app/Models/Patient.php
app/Models/MedicalRecord.php
app/Models/Diagnosis.php
app/Models/MedicationOrder.php
app/Http/Requests/Consultations
app/Http/Controllers/Doctor/Consultations
resources/views/consultations/show.blade.php
resources/views/consultations/partials
resources/js/Pages/consultation-show.js
tests/Feature/Consultations
tests-e2e/tests/consultation-workspace.spec.ts
```

---

## 2. Main Deliverables

Implement:

```text id="i2l4u8"
PrescriptionSafetyService
PrescriptionSafetyResult / DTO if useful
prescription warning and override workflow
minimum consultation completion checklist
CompletionReadinessService
completion blocking with clear missing requirements
audit logging for safety overrides and blocked completion
Phase 7 feature tests
updated Playwright smoke for successful prescription/lab/procedure paths where stable
Phase 7 report
```

---

# Part A — Prescription Safety Checks

## 3. Prescription Safety Service

Create:

```text id="wyj64r"
App\Services\Consultation\PrescriptionSafetyService
```

or a namespace matching the project.

The service should check prescription payloads before prescription persistence.

Required checks:

```text id="vjj4sa"
known allergy conflicts
duplicate active medication
missing dose
missing frequency
missing duration
missing quantity
missing route if route is required by current prescription model
missing diagnosis if policy requires diagnosis before prescribing
unsafe or unusual dose placeholder check if dose-range data does not exist yet
```

If drug-drug interaction data does not exist yet, implement the service hook and return no interaction warnings for now.

Do not fake medical interaction logic.

Document the limitation clearly.

---

## 4. Allergy Conflict Check

Use existing patient allergy data.

Check against:

```text id="1cb3q8"
patient allergies
medicine/drug name
generic name if available
active ingredient if available
drug class if available
```

If only drug/product name exists, use conservative name matching.

Result should be:

```text id="7m0hqr"
warning, not hard failure, unless policy says hard block
```

Override should require reason.

---

## 5. Duplicate Active Medication Check

Check whether the patient already has an active medication/order for the same drug.

Scope:

```text id="g9gk40"
same patient
active/current visit or active medication window
same drug/product
not cancelled
not completed/discontinued
```

If duplicate found:

```text id="xqfu9v"
warn and require override reason
```

Do not block legitimate repeat prescription if override reason is provided.

---

## 6. Required Prescription Fields

Before prescription creation, validate clinical completeness beyond basic form validation.

Required baseline:

```text id="c2wrs3"
drug/product
dose
frequency
duration
quantity
instructions if currently required by the system
```

If these are already FormRequest rules, keep them there.

If they are clinical-safety checks, centralise in `PrescriptionSafetyService`.

Do not duplicate validation in controller.

---

## 7. Diagnosis Requirement Policy

Add configurable policy.

Suggested config:

```text id="ygm9tf"
config/consultation.php

'prescriptions' => [
    'require_diagnosis_before_prescribing' => true,
]
```

If diagnosis is required:

```text id="0hxdwx"
Prescription cannot be created unless consultation has at least one diagnosis or provisional diagnosis.
```

Emergency override may be allowed if existing emergency workflow requires it.

If override is allowed:

```text id="15x2jy"
reason required
audit event logged
```

---

## 8. Prescription Safety Result

Return a structured result.

Example:

```php id="qmbi4j"
PrescriptionSafetyResult {
    public bool $passed;
    public bool $requiresOverride;
    public array $warnings;
    public array $blockingErrors;
}
```

Warnings examples:

```text id="8qipgf"
allergy_conflict
duplicate_active_medication
missing_diagnosis
unusual_dose_unverified
```

Blocking errors examples:

```text id="4ruixl"
missing_drug
missing_dose
missing_frequency
missing_duration
missing_quantity
```

---

## 9. Override Workflow

Add override support for warnings.

Required fields:

```text id="7t8pp3"
safety_override_reason
safety_override_codes[]
```

Rules:

```text id="z3l580"
warnings can be overridden with reason
blocking errors cannot be overridden
override reason is required when warnings exist
override reason must be stored/audited
override must not expose patient PII in logs
```

Add audit events:

```text id="1drgd1"
PRESCRIPTION_SAFETY_WARNING_TRIGGERED
PRESCRIPTION_SAFETY_OVERRIDE_ACCEPTED
PRESCRIPTION_SAFETY_BLOCKED
```

Use `ActivityLogService`.

Do not log raw sensitive clinical payload unnecessarily.

---

## 10. UI Integration For Prescription Warnings

Keep the UI simple.

When prescription submit returns warnings:

```text id="6m3w98"
show warning list near prescription form
show override reason textarea
allow resubmit with override reason
do not create prescription until override reason is submitted
```

The Phase 2 AJAX helper must handle this response without breaking.

Preferred response shape:

```json id="ladzye"
{
  "success": false,
  "requires_override": true,
  "warnings": [
    {"code": "allergy_conflict", "message": "..."}
  ]
}
```

If existing AJAX helper needs adjustment, keep it generic.

Do not hardcode safety logic in JavaScript.

JavaScript only displays server-provided warnings and collects override reason.

---

# Part B — Consultation Completion Checklist

## 11. Completion Readiness Service

Create:

```text id="l9r3ds"
App\Services\Consultation\ConsultationCompletionReadinessService
```

or equivalent.

Purpose:

```text id="i6bghv"
Before a consultation route/session can be completed, verify minimum clinical documentation exists.
```

Baseline requirements:

```text id="n3r1zo"
complaint or reason for visit
examination or documented reason not examined
diagnosis or provisional diagnosis
treatment plan or disposition note
orders/prescriptions reviewed
follow-up/referral/admission/discharge decision
```

Do not overcomplicate initially.

Make checklist configurable per department type if practical.

Suggested config:

```text id="zj1voc"
config/consultation.php

'completion_checklist' => [
    'default' => [
        'complaint',
        'examination',
        'diagnosis',
        'plan_or_disposition',
    ],
    'emergency' => [
        'triage',
        'diagnosis_or_provisional',
        'disposition',
    ],
]
```

---

## 12. Completion Blocking

When user attempts to complete consultation:

```text id="rcw5xt"
run ConsultationActionGuard
run CompletionReadinessService
if missing requirements exist, block completion
return clear missing items
do not change route/session status
```

Expected response for AJAX:

```json id="g55tjq"
{
  "success": false,
  "completion_blocked": true,
  "missing_requirements": [
    {"code": "diagnosis", "message": "Diagnosis is required before completion."}
  ]
}
```

For normal redirect:

```text id="9lw2pz"
redirect back with translated error and missing checklist details
```

---

## 13. Completion Checklist UI

Add a lightweight checklist panel or status indicator.

Location:

```text id="tx3ajo"
right panel
completion modal
or near complete button
```

Show:

```text id="t27v7t"
Ready to complete / Missing requirements
complaint
examination
diagnosis
plan/disposition
follow-up/referral/admission decision
```

Do not redesign the whole page.

Use existing partials.

Checklist should refresh after section updates if possible.

---

## 14. Completion Override

Decide whether completion can be overridden.

Recommended:

```text id="4k4a1g"
No override for standard users.
Only users with consultations.complete_with_missing_requirements can override.
Override requires reason.
Override is audited.
```

Add permission only if needed:

```text id="1wpwan"
consultations.complete_with_missing_requirements
```

If not implemented in this phase, document as deferred.

Do not silently allow incomplete completion.

---

## 15. Audit Events For Completion

Add audit events:

```text id="y4w146"
CONSULTATION_COMPLETION_BLOCKED
CONSULTATION_COMPLETION_REQUIREMENTS_MET
CONSULTATION_COMPLETION_OVERRIDE_ACCEPTED
```

Do not log raw sensitive clinical text.

Log only:

```text id="1wglhx"
visit_id
consultation_route_id
missing requirement codes
user_id
override reason if provided, but avoid sensitive clinical details
```

---

# Part C — Browser Coverage

## 16. Extend Playwright Smoke Carefully

The Phase 6 smoke now passes.

Extend only if stable.

Add coverage for:

```text id="6y7wcz"
successful procedure request submit if fixture supports it
successful lab request submit if fixture supports it
prescription warning display path
completion blocked when required checklist items are missing
```

Do not create a huge brittle E2E test.

It is okay to add a second focused smoke spec if cleaner.

---

## 17. Tests To Add

Add focused feature test file:

```text id="w8y9cc"
tests/Feature/Consultations/ConsultationClinicalSafetyPhase7Test.php
```

Required tests:

```text id="f3xbg2"
prescription is blocked when required fields are missing
prescription warns when patient allergy conflicts with selected drug
prescription warning requires override reason
prescription with allergy warning and override reason is allowed
duplicate active medication triggers warning
duplicate active medication requires override reason
missing diagnosis blocks or warns according to configured policy
prescription safety override is audited
blocking safety errors do not create prescriptions
completion is blocked when complaint is missing
completion is blocked when diagnosis is missing
completion is blocked when plan/disposition is missing
completion does not change route status when blocked
completion succeeds when checklist is satisfied
completion blocked event is audited
completion readiness payload renders in consultation page
```

Add config-specific tests if department-specific rules are added.

---

## 18. Existing Tests To Re-run

Run Phase 1–7 consultation tests:

```bash id="ebzqax"
php artisan test tests\Feature\Consultations\ConsultationWorkflowSafetyPhase1Test.php
php artisan test tests\Feature\Consultations\ConsultationJavascriptLifecyclePhase2Test.php
php artisan test tests\Feature\Consultations\ConsultationStructurePhase3Test.php
php artisan test tests\Feature\Consultations\ConsultationControllerExtractionPhase4Test.php
php artisan test tests\Feature\Consultations\ConsultationServiceExtractionPhase5Test.php
php artisan test tests\Feature\Consultations\ConsultationBrowserFixturePhase6Test.php
php artisan test tests\Feature\Consultations\ConsultationClinicalSafetyPhase7Test.php
```

Focused neighbouring workflow tests:

```bash id="cnqyb3"
php artisan test tests\Feature\ConsultationRouteSessionWorkflowTest.php tests\Feature\ConsultationClinicalSectionsTest.php tests\Feature\InsuranceAwareClinicalPricingTest.php tests\Feature\LabWorkflowTest.php tests\Feature\PharmacyWorkflowTest.php tests\Feature\ServiceRenderingWorkflowTest.php
```

Browser smoke:

```bash id="4sl7bk"
npx playwright test tests-e2e/tests/consultation-workspace.spec.ts
```

Do not run the wide full suite unless explicitly instructed.

---

## 19. Localisation

Add EN/FR keys for:

```text id="ee0e3h"
consultation.safety.prescription_warning
consultation.safety.override_required
consultation.safety.override_reason
consultation.safety.allergy_conflict
consultation.safety.duplicate_active_medication
consultation.safety.missing_diagnosis
consultation.safety.unusual_dose_unverified
consultation.completion.ready
consultation.completion.not_ready
consultation.completion.missing_requirements
consultation.completion.requirement.complaint
consultation.completion.requirement.examination
consultation.completion.requirement.diagnosis
consultation.completion.requirement.plan_or_disposition
consultation.completion.blocked
consultation.completion.override_reason
```

Run:

```bash id="pujdn5"
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
```

Expected:

```text id="i9nhrp"
Active runtime candidates: 0
EN/FR parity OK
```

---

## 20. Verification

Run:

```bash id="iifusd"
npm run build
php artisan view:cache
php artisan view:clear
php artisan route:list | grep consultation
php artisan permissions:audit --strict
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan test tests\Feature\Consultations\ConsultationWorkflowSafetyPhase1Test.php
php artisan test tests\Feature\Consultations\ConsultationJavascriptLifecyclePhase2Test.php
php artisan test tests\Feature\Consultations\ConsultationStructurePhase3Test.php
php artisan test tests\Feature\Consultations\ConsultationControllerExtractionPhase4Test.php
php artisan test tests\Feature\Consultations\ConsultationServiceExtractionPhase5Test.php
php artisan test tests\Feature\Consultations\ConsultationBrowserFixturePhase6Test.php
php artisan test tests\Feature\Consultations\ConsultationClinicalSafetyPhase7Test.php
php artisan test tests\Feature\ConsultationRouteSessionWorkflowTest.php tests\Feature\ConsultationClinicalSectionsTest.php tests\Feature\InsuranceAwareClinicalPricingTest.php tests\Feature\LabWorkflowTest.php tests\Feature\PharmacyWorkflowTest.php tests\Feature\ServiceRenderingWorkflowTest.php
npx playwright test tests-e2e/tests/consultation-workspace.spec.ts
git diff --check -- app database docs resources routes scripts tests tests-e2e lang
```

Also address the generated build artifact issue separately:

```bash id="xc0vn5"
git status --short public/build
```

Either restore unintended generated deletions or document why they are intentional.

Do not run the wide full suite unless explicitly instructed.

---

## 21. Documentation

Create:

```text id="7v6j53"
docs/CONSULTATION_WORKFLOW_STABILISATION_PHASE_7_CLINICAL_SAFETY_COMPLETION_REPORT.md
```

Include:

```text id="z0283p"
summary
PrescriptionSafetyService design
checks implemented
checks deferred because required data does not exist
override workflow
audit events
CompletionReadinessService design
completion requirements
completion blocking behaviour
completion checklist UI
permissions added if any
Playwright coverage added
tests added
verification commands run
generated public/build artifact caveat status
known limitations
next recommended phase
```

Known limitations may include:

```text id="wfu2uo"
drug-drug interaction data not available yet
dose-range database not available yet
full UI redesign deferred
duplicate permission-name advisory pending
wide full suite not run
```

---

## 22. Acceptance Criteria

Phase 7 is complete only when:

```text id="j0rnpt"
PrescriptionSafetyService exists and is tested
prescription required-field safety is enforced
allergy conflict warning is implemented where data exists
duplicate active medication warning is implemented
override reason is required for overrideable warnings
blocking prescription errors do not create prescriptions
prescription safety overrides are audited
CompletionReadinessService exists and is tested
completion is blocked when required checklist items are missing
completion does not change route status when blocked
completion succeeds when checklist is satisfied
completion blocked events are audited
completion checklist/status is visible in the consultation UI
Phase 2 JS contract remains stable
Phase 1 guard/idempotency remains preserved
Phase 6 Playwright smoke still passes
npm build passes
view cache compiles
permissions audit passes
localisation audit Active runtime candidates = 0
EN/FR parity passes
Phase 1-7 consultation tests pass
focused neighbouring workflow tests pass
documentation report is created
generated public/build artifact caveat is resolved or explicitly documented
wide full suite is not run unless explicitly requested
```

Proceed with Consultation Workflow Stabilisation Phase 7 now.
