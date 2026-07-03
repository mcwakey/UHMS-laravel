# Consultation Workflow Improvement Plan

Generated: 2026-07-03

## Goal

Improve the consultation workspace so clinicians can safely move from patient context to clinical documentation, orders, prescriptions, procedures, follow-up, billing impact, and completion without JavaScript fragility, duplicate records, lost route attribution, privacy leaks, or unsafe clinical shortcuts.

## Target Workflow

1. Open consultation
   - Show patient identity, visit, selected insurance, route/session state, allergies, chronic conditions, and key alerts.
   - Clearly show whether the current session is editable, locked, completed, cancelled, or read-only.

2. Review context
   - Vitals.
   - Previous visits.
   - Active diagnoses.
   - Active medications.
   - Recent lab/procedure results.
   - Insurance/billing status.

3. Document clinical findings
   - Complaints.
   - HOPC.
   - Examination.
   - Diagnosis.
   - Treatment plan.

4. Order services
   - Labs and imaging.
   - Procedures and theatre.
   - Department routing.
   - Each order should show billing impact and selected payer before submit.

5. Prescribe
   - Show allergy and chronic-condition warnings next to the prescription form.
   - Check duplicate active medication, interactions, allergy conflicts, and unsafe doses.
   - Require override reason where needed.

6. Plan and disposition
   - Follow-up.
   - Referral/admission.
   - Tasks.
   - Complete, cancel, or transfer route.

7. Complete consultation
   - Require minimum clinical documentation.
   - Show a checklist of missing items.
   - Lock session after completion.
   - Keep correction flow separate and audited.

## Workstream 1: Safety and Data Integrity

### 1.1 Add server-side idempotency

Apply to:

- Complaints
- HOPC
- Examinations
- Diagnoses
- Investigations
- Lab requests
- Procedure requests
- Prescriptions
- Treatments
- Tasks
- Follow-ups
- Route transitions

Acceptance criteria:

- Submitting the same payload twice with the same idempotency key creates one record.
- Replaying a completed request returns the original result or a safe no-op.
- Tests cover double-click and network retry scenarios.

### 1.2 Add shared consultation action guard

Create one service-level guard that checks:

- Visit exists and is active where required.
- Selected consultation route belongs to the visit.
- Route is editable.
- Route is not locked, completed, or cancelled unless correction permission applies.
- User has permission for the action.
- Patient privacy access rules are respected.

Acceptance criteria:

- Every consultation mutation endpoint calls the same guard.
- Tests verify blocked actions for locked, completed, and cancelled routes.

### 1.3 Preserve consultation route context

All consultation-originated records should store:

- `visit_id`
- `patient_id` where applicable
- `medical_record_id` where applicable
- `consultation_route_id`
- `requested_by` or `created_by`

Priority records:

- Lab requests
- Procedure requests
- Prescriptions
- Medication orders
- Investigation notes
- Tasks
- Follow-up appointments

Acceptance criteria:

- Tests assert each record is linked to the selected consultation route.
- General ledger/billing reconciliation can trace back to the consultation source.

## Workstream 2: Clinical Safety

### 2.1 Prescription safety checks

Add checks before prescription creation:

- Known allergy conflicts.
- Drug-drug interactions where data exists.
- Duplicate active medication.
- Missing dose, frequency, duration, or quantity.
- Unsafe or unusual dose requiring confirmation.
- Missing diagnosis where policy requires diagnosis before prescribing.

Acceptance criteria:

- Unsafe prescription attempts return actionable warnings.
- Override requires reason and is logged.
- Medication orders are created only after passing checks or approved override.

### 2.2 Completion checklist

Define completion requirements by consultation type or department.

Example baseline:

- At least one complaint or reason for visit.
- Examination or documented reason not examined.
- Diagnosis or provisional diagnosis.
- Plan/disposition.
- Prescriptions/orders reviewed.
- Follow-up/admission/referral decision.

Acceptance criteria:

- Complete action is blocked when required items are missing.
- UI shows missing requirements before submit.
- Emergency workflows can use a department-specific checklist.

## Workstream 3: Frontend Stabilization

### 3.1 Introduce one page lifecycle

Create one consultation page initializer responsible for:

- Reading page config.
- Binding delegated events.
- Registering forms.
- Initializing modals.
- Refreshing sections.
- Destroying/rebinding cleanly after navigation or partial refresh.

Acceptance criteria:

- No form depends on inline `onsubmit`.
- No select depends on inline `onchange`.
- No button depends on inline `onclick`.

### 3.2 Centralize AJAX form handling

Shared behavior:

- CSRF.
- Route/session hidden input.
- Submit disabling.
- Idempotency key.
- Validation error rendering.
- Toast/error messages.
- Section refresh.
- Summary refresh.
- Button state restore.

Acceptance criteria:

- Clinical forms use one helper.
- Modal forms use one helper.
- Duplicate-submit UI protection is consistent.

### 3.3 Split JavaScript by workflow

Suggested modules:

- `route-context`
- `ajax-forms`
- `section-refresh`
- `diagnoses`
- `investigations`
- `procedures`
- `prescriptions`
- `tasks`
- `patterns`
- `previous-visits`
- `summary`

Acceptance criteria:

- Consultation Blade contains minimal config and no large inline behavior block.
- Frontend behavior can be linted and tested.

## Workstream 4: Backend Refactor

### 4.1 Split controller responsibilities

Recommended controller boundaries:

- `ConsultationWorkspaceController`
  - show page
  - summary fragment
  - history
- `ConsultationClinicalEntryController`
  - complaints
  - HOPC
  - examination
  - diagnoses
  - treatments
- `ConsultationOrderController`
  - investigations
  - lab requests
  - procedures
- `ConsultationPrescriptionController`
  - prescriptions
- `ConsultationSessionController`
  - start
  - activate
  - complete
  - cancel
  - transition
- `ConsultationPlanningController`
  - tasks
  - follow-up
  - next patient
  - referral

Acceptance criteria:

- Each controller is small enough to review safely.
- FormRequest classes validate each mutation.
- Business rules live in services, not Blade or controller branches.

### 4.2 Create workflow services

Suggested services:

- `ConsultationActionGuard`
- `ConsultationIdempotencyService`
- `ConsultationRouteContextResolver`
- `ConsultationCompletionService`
- `PrescriptionSafetyService`
- `ConsultationBillingImpactService`

Acceptance criteria:

- Shared rules are tested once and reused everywhere.
- Controller actions become orchestration, not policy containers.

## Workstream 5: Privacy and Permissions

### 5.1 Field classification audit

Classify all patient fields shown on the consultation page:

- Identity fields.
- Contact fields.
- Insurance fields.
- Clinical alerts.
- Demographic fields.
- Government ID fields.
- Previous visit and export fields.

Acceptance criteria:

- Sensitive fields use `x-patient-protected-field` or an equivalent masking path.
- Restricted users see masked values in consultation page and preview/export.
- Tests cover restricted and privileged users.

### 5.2 Permission naming cleanup

Resolve duplicate or near-duplicate permission names reported by the audit:

- `consultation.create` vs `consultations.create`
- `procedure.view` vs `procedures.view`
- `reports.consultation` vs `reports.consultations`

Acceptance criteria:

- Route middleware uses canonical permission names.
- Old names are either migrated, aliased, or removed with a data migration.
- Permission audit stays clean.

## Workstream 6: Billing and Accounting Reconciliation

### 6.1 Billing impact preview

Every orderable item should show:

- Selected payer.
- Cash price.
- Insurance tariff.
- Patient portion.
- Insurance portion.
- Limit/burned usage impact.
- Existing duplicate or pending request warning.

Acceptance criteria:

- Clinician sees billing impact before submission.
- Insurance changes use usage for the selected responsible insurance.

### 6.2 End-to-end posting tests

Add tests for:

- Cash service from consultation to invoice to journal.
- Partial insurance service from consultation to invoice to patient receivable and insurance receivable.
- 100 percent insurance service from consultation to invoice to insurance receivable.
- Procedure request with selected insurance tariff.
- Lab request with selected insurance tariff.
- Insurance switch and limit usage recalculation.

Acceptance criteria:

- General ledger and operational reconciliation match.
- Balance sheet does not show orphaned asset balances without matching revenue/equity/liability postings.

## Workstream 7: Test Plan

Priority tests:

- Consultation page smoke test with no JS console errors.
- Procedure department selection and request submission.
- Investigation/lab department selection and request submission.
- Prescription create with duplicate-click protection.
- Locked route blocks every mutation.
- Completed route blocks every mutation except authorized correction.
- Completion checklist blocks incomplete consultations.
- Restricted user sees masked patient fields.
- Consultation-generated billing posts correctly.
- Section refresh does not break controls.

## Phased Delivery Plan

### Phase 0: Freeze risky behavior with tests

- Add regression tests for currently reported failures.
- Add duplicate-submit tests for prescriptions, lab requests, and procedure requests.
- Add route-context tests for lab/procedure/prescription records.

### Phase 1: Safety patches

- Add idempotency.
- Add shared action guard.
- Fix lab route attribution.
- Add locked/completed route enforcement everywhere.

### Phase 2: JavaScript stabilization

- Introduce one page lifecycle.
- Replace inline procedure/investigation/lab handlers.
- Centralize AJAX forms.
- Remove duplicate DOM ids.

### Phase 3: Backend split

- Extract controllers by workflow.
- Add FormRequest classes.
- Move completion and safety rules into services.

### Phase 4: Clinical workflow upgrade

- Add prescription safety checks.
- Add completion checklist.
- Improve request/order billing preview.

### Phase 5: Privacy and permissions cleanup

- Classify consultation page fields.
- Mask sensitive fields consistently.
- Resolve duplicate permission names.

### Phase 6: E2E and reporting

- Add browser workflow coverage.
- Add accounting reconciliation coverage.
- Add performance checks for consultation page load and section refresh.

## Definition Of Done

The consultation workflow can be considered healthy when:

- Every mutation is idempotent.
- Every mutation is route-aware.
- Locked/completed/cancelled sessions cannot be changed accidentally.
- Prescriptions have safety checks and auditable overrides.
- Completion requires minimum clinical documentation.
- Frontend behavior does not depend on inline global handlers.
- Patient privacy masking is consistent across page, preview, and export.
- Billing and general ledger postings reconcile for cash, partial insurance, and full insurance.
- Tests cover clinical, operational, privacy, billing, and browser workflows.

