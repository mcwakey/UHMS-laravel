# Consultation View Gap Analysis Report

Generated: 2026-07-03

## Scope

This report reviews the consultation workspace and workflow without changing application code. The main files inspected were:

- `resources/views/consultations/show.blade.php`
- `app/Http/Controllers/Doctor/ConsultationController.php`
- `app/Services/ConsultationService.php`
- `app/Services/PrescriptionService.php`
- `app/Services/LabService.php`
- `app/Services/ProcedureRequestService.php`
- `app/Services/InvestigationRequestService.php`
- `app/Services/ServicePriceResolver.php`
- `app/Services/MedicalRecordEntryPermissionService.php`
- `resources/views/components/patient-long-card.blade.php`
- `resources/views/components/consultation-preview.blade.php`
- `resources/views/components/patient-protected-field.blade.php`

## Verification Run

Safe verification commands were run only:

- `php artisan route:list | rg 'consultation|consultations|theatre|lab|prescription|vitals|patients.medical-summary|patterns|icd'`
- `php artisan permissions:audit --strict`
- `php scripts/localisation-audit.php`
- `php scripts/localisation-parity-check.php`
- `git diff --check`

Results:

- Consultation routes are registered for sessions, route transitions, complaints, HOPC, examinations, diagnoses, investigations, prescriptions, procedures, tasks, treatment plans, referrals, follow-up, next patient, patterns, ICD search, labs, vitals, pharmacy, and theatre.
- Permission audit passed with exit code 0.
- No missing route permissions were reported.
- No admin mutation route without `can` or role middleware was reported.
- Permission audit still flagged possible duplicate permission names, including `consultation.create` vs `consultations.create`, `procedure.view` vs `procedures.view`, and `reports.consultation` vs `reports.consultations`.
- Localisation parity passed: EN/FR language keys are in parity.
- Localisation audit reported 0 active runtime candidates, but it updated `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.
- `git diff --check` passed.

## Executive Summary

The consultation workspace is functionally broad, but it is carrying too much behavior in one Blade file and one controller. The view is approximately 4,470 lines, and the controller is approximately 1,752 lines. The page covers most expected clinical actions, but several workflow risks remain around duplicate submissions, locked-session enforcement consistency, lab/procedure route context, prescribing safety, completion prerequisites, and JavaScript maintainability.

No single inspected area shows an obvious critical privacy leak in the patient header or consultation preview, because key fields use `x-patient-protected-field`. However, there are still raw demographic fields and a visible clinical summary block in the consultation page that should be reviewed against the patient privacy classification rules.

Critical workflow risks found:

- Duplicate submission risk exists for prescriptions, procedure requests, lab requests, and several clinical entry forms.
- Locked or completed session protection is not uniformly enforced at the service boundary.
- Lab requests created from the consultation modal may lose `consultation_route_id` context.
- Prescription creation does not visibly enforce allergy, interaction, diagnosis, or duplicate-medication safeguards.
- Visit completion and transition flows do not visibly enforce required clinical documentation.
- The page has a high JavaScript failure surface caused by a large inline script, global functions, inline event handlers, and repeated rebinding after partial refreshes.

## Current Workflow Coverage

| Workflow area | Current state | Gap |
| --- | --- | --- |
| Patient context | Patient card, visit context, insurance status, alerts, summary modal, vitals, prior visits. | Some fields are raw; privacy classification should be confirmed for occupation, religion, marital status, and visible allergies/chronic conditions in the page summary. |
| Consultation sessions | Route/session drawer, active/current session handling, locked/selected route state. | UI can be complex; controller creates or loads records during show, which makes opening a session do more than display. |
| Complaints | Create, edit, delete, templates/suggestions, AJAX refresh. | Duplicate submit protection relies mostly on frontend behavior, not idempotency. |
| HOPC | Create, edit, delete, AJAX refresh. | Same duplicate submit and locked-session consistency concerns. |
| Examination | Create, edit, delete, AJAX refresh. | No structured body-system completeness rules visible. |
| Diagnoses | Create, edit, delete, primary diagnosis, ICD search. | Completion does not visibly require a final/primary diagnosis. |
| Investigations | Clinical investigation notes and request modal exist. | Lab request route context is not consistently passed to `LabService::createRequest`. |
| Prescriptions | Prescription form, rows, quantity calculations, medication orders event. | No visible allergy, drug interaction, duplicate-medication, dose-range, or diagnosis prerequisite enforcement. |
| Procedures | Procedure request UI and service pricing exist. | Duplicate submit/idempotency and route-context audit need hardening. |
| Tasks | Task creation, toggle, delete. | Repeated form handling is inconsistent with AJAX forms. |
| Follow-up | Follow-up appointment modal/service exists. | Should be tied to completion workflow and discharge summary requirements. |
| Referral/transition | Admit, complete, cancel, status transition, refer. | No visible clinical prerequisites before completion/admission/referral. |
| Billing linkage | Investigation/procedure pricing uses selected insurance-aware resolver. | Need full reconciliation from request creation to billing for all consultation-generated charges. |

## Detailed Findings

### 1. View and controller are too large

Evidence:

- `resources/views/consultations/show.blade.php` is about 4,470 lines.
- `app/Http/Controllers/Doctor/ConsultationController.php` is about 1,752 lines.
- The controller contains show orchestration and many mutation handlers, including `storeInvestigation` at line 1215, `storePrescription` at line 1388, `storeProcedureRequest` at line 1458, `storeLabRequest` at line 1575, `transitionVisit` at line 1640, and `refer` at line 1666.

Risk:

- Small workflow changes are likely to break unrelated parts of the page.
- Testing individual workflows is harder because page state, session route, billing, privacy, and AJAX refresh behavior are tightly coupled.

Recommendation:

- Split the view into section partials or components.
- Split controller responsibilities into workspace display, clinical entries, requests, prescriptions, session transitions, and summaries.

### 2. Duplicate submission risk is high

Evidence:

- The page contains multiple forms with `data-ajax-form`, inline `onsubmit`, and normal POST forms.
- No idempotency token pattern was found in the inspected services.
- `PrescriptionService::create` creates a prescription and dispatches `PrescriptionCreated`.
- `LabService::createRequest` creates a lab request and dispatches `LabRequestCreated`.
- `ProcedureRequestService::requestProcedure` creates procedure requests.

Risk:

- Double-clicks, network retries, browser back/forward resubmits, and AJAX rebind issues can create duplicate prescriptions, requests, billing items, tasks, or clinical entries.
- The frontend disables buttons in some AJAX paths, but that is not enough for clinical and billing safety.

Recommendation:

- Add server-side idempotency keys for all consultation mutation endpoints.
- Enforce natural duplicate checks where clinically appropriate, for example same visit, same route, same service, same requested item, same timestamp window, and same ordering user.
- Add regression tests that submit the same payload twice.

### 3. Locked-session enforcement is inconsistent

Evidence:

- `ConsultationService` uses `assertRecordEditable` for several clinical record entries.
- `MedicalRecordEntryPermissionService` blocks editing and deleting completed, cancelled, or locked sessions.
- `PrescriptionService::create`, `LabService::createRequest`, and `ProcedureRequestService::requestProcedure` do not visibly call the same record-editability guard.

Risk:

- Some actions may still create new clinical or request records after a session is completed or locked, depending on the route/controller path.

Recommendation:

- Move route/session editability enforcement into a shared consultation action guard.
- Require every consultation mutation endpoint to pass through that guard before persistence.
- Test each mutation endpoint against active, completed, cancelled, and locked consultation routes.

### 4. Lab request route context can be lost

Evidence:

- `LabService::createRequest` accepts `consultation_route_id` and `medical_record_id`.
- The service stores `consultation_route_id` if it is present.
- Consultation controller lab/investigation paths create lab requests, but the inspected calls do not consistently pass `consultation_route_id`.

Risk:

- Lab requests may appear operationally correct but fail consultation-route attribution, session reporting, audit trail, and route-specific billing reconciliation.

Recommendation:

- Ensure all consultation-originated lab requests include `consultation_route_id` and `medical_record_id`.
- Add tests asserting lab requests from consultation are linked to the selected consultation route.

### 5. Prescribing safety needs stronger gates

Evidence:

- The patient card can display allergies/chronic conditions through protected fields.
- `PrescriptionService::create` creates prescriptions and medication orders.
- No inspected code showed hard stops for allergy conflict, drug interaction, duplicate active medication, diagnosis prerequisite, or high-risk dose confirmation.

Risk:

- Clinicians can create prescriptions without the system checking known safety signals.

Recommendation:

- Add a medication safety validation layer before prescription persistence.
- Require explicit override reasons for allergy or interaction warnings.
- Show allergy/chronic-condition context near the prescription form, not only in the page header.
- Add tests for allergy conflict, duplicate medication, missing diagnosis, and override audit logging.

### 6. Completion workflow lacks visible clinical prerequisites

Evidence:

- The page exposes complete/admit/cancel/transition actions.
- `transitionVisit` checks whether a transition is allowed, but no inspected rule visibly requires minimum clinical documentation.

Risk:

- A consultation can potentially be completed without chief complaint, examination, diagnosis, plan, or disposition note.

Recommendation:

- Define a consultation completion checklist by department/visit type.
- Enforce it server-side before completion.
- Present missing requirements in the UI before the clinician attempts completion.

### 7. Privacy is mostly protected but needs classification review

Evidence:

- Phone, membership number, allergies, and chronic conditions in `patient-long-card` use `x-patient-protected-field`.
- Consultation preview uses protected fields for phone and Ghana card in export mode.
- The consultation page still displays allergies and chronic conditions directly in an early summary area.
- Occupation, religion, and marital status are displayed raw in patient card/preview.

Risk:

- If those fields are classified as sensitive under the app's patient privacy rules, the consultation view may bypass masking in some locations.

Recommendation:

- Classify every patient field displayed in the consultation workspace.
- Replace raw sensitive fields with `x-patient-protected-field`.
- Add a consultation-view privacy test that renders the page under restricted permissions.

### 8. Billing path needs end-to-end reconciliation tests

Evidence:

- `ServicePriceResolver` resolves selected insurance-aware prices.
- `InvestigationRequestService` and `ProcedureRequestService` use selected visit/insurance context for billing preview.
- Recent issues around 100 percent insurance coverage and insurance receivables show this area is operationally sensitive.

Risk:

- A request can be clinically created but not billed, billed to the wrong payer, or posted without route attribution.

Recommendation:

- Add tests from consultation action to invoice item to journal entry for cash, partial insurance, and 100 percent insurance.
- Include selected insurance change scenarios and burned-limit tracking per responsible insurance.

## UI and UX Gaps

- The page tries to be a full clinical workspace, historical viewer, billing launcher, procedure/lab order screen, prescribing screen, task manager, and transition console at once.
- The sections are present, but the user can lose context because session selection, route locks, previous visits, forms, modals, and summary preview all live in one page.
- The procedure and investigation request flows are modal-heavy and JS-dependent.
- There is no clear "ready to complete" checklist visible in the main workflow.
- Error handling is inconsistent across inline forms, AJAX forms, modal submissions, and standard POST forms.

Recommended UX direction:

- Keep the first screen as the usable consultation workspace.
- Use a sticky patient/session context bar.
- Group workflow into: clinical notes, orders, prescriptions, plan/disposition, history, and billing impact.
- Put safety warnings adjacent to the action they affect.
- Use a visible completion checklist.

## Testing Gaps

Existing tests cover important pieces, including consultation route/session workflow, clinical sections, follow-up/next patient, logs, visit routing, selected insurance pricing, pharmacy/lab/theatre flows, and privacy areas.

Missing or weak areas:

- Browser-level consultation workflow test for procedure department selection and request submission.
- Double-submit/idempotency tests for each mutation endpoint.
- Locked/completed/cancelled session mutation matrix.
- Lab request route attribution from consultation modal.
- Prescription safety checks.
- Completion prerequisite checks.
- Restricted-user render test for consultation page privacy masking.
- End-to-end billing to journal entry tests from consultation-generated services.

## Risk Matrix

| Risk | Severity | Likelihood | Priority |
| --- | --- | --- | --- |
| Duplicate clinical or billing records from repeat submit | High | High | P0 |
| Creating records after session lock/completion | High | Medium | P0 |
| Lab/procedure records losing consultation route context | High | Medium | P0 |
| Prescribing without allergy/interaction checks | High | Medium | P1 |
| Completing consultation without minimum documentation | Medium | High | P1 |
| Privacy masking gaps in consultation page | Medium | Medium | P1 |
| JS regressions from inline globals/rebinding | High | High | P0 |
| Controller/view maintainability drag | Medium | High | P1 |

## Recommended Phases

1. Stabilize safety-critical workflow
   - Add server-side idempotency.
   - Add shared consultation action guard.
   - Ensure route context for lab/procedure/prescription/request records.

2. Add tests before large refactors
   - Duplicate submit tests.
   - Locked route tests.
   - Selected insurance billing tests.
   - Consultation privacy render tests.

3. Split frontend behavior
   - Move JS out of Blade into a page module.
   - Remove inline event handlers.
   - Centralize AJAX form handling and modal submission.

4. Split backend responsibilities
   - Extract controller actions by workflow.
   - Use FormRequest classes and service-level guards.

5. Improve clinical workflow
   - Add prescription safety checks.
   - Add completion checklist and prerequisites.
   - Improve billing visibility at point of order.

## Direct Answers Required By Prompt

- Critical privacy risk found: No confirmed critical privacy leak in the inspected protected-field paths, but there are manual-review privacy gaps on raw patient fields and clinical summary display.
- Duplicate-submission risk found: Yes, high risk.
- Root cause of repetitive JS: Yes, primarily the inline Blade script, inline event handlers, global functions, mixed event binding styles, and rebinding after AJAX section refreshes.

