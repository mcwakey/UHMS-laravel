# Patient Privacy Phase 3 Workflow, Reports, Prints & Exports Report

## Objective

Phase 3 extends the patient privacy infrastructure into clinical workflows, billing/payment screens, reports, print views, export-mode rendering, activity-log inspection, and external notification/admin surfaces.

The implementation keeps masking centralized in `App\Services\PatientPrivacyService` and the `x-patient-protected-field` component. Views do not contain new ad hoc masking rules.

## Protected Surfaces

### Clinical and Workflow Cards

- Shared `x-patient-card` and `x-patient-long-card` continue to protect patient phone, insurance membership numbers, allergies, and chronic conditions.
- `x-consultation-preview` now renders phone and ID card values through export-mode privacy controls.
- Consultation, lab, pharmacy, service-rendering, visit, and appointment surfaces that reuse shared patient cards inherit the same permission-driven masking behavior.

### Billing and Payments

- Invoice show, print, and PDF views now protect patient phone values.
- Payment receive and receipt views now protect patient phone values.
- Patient statement PDF views now protect patient phone values.
- Cashier-facing screens still show operational payment fields where the user is entering a payment target, but patient-origin contact data is masked through the shared privacy layer.

### Reports and Print Views

- Patient report HTML/PDF views now protect patient phone values.
- Patient statement report HTML/PDF views now protect patient phone values.
- Statement search now protects patient phone values.
- Export-mode rendering is now explicit: users need `patients.export_sensitive.view` to receive unmasked sensitive values in export/print contexts.

### Activity Logs

- Activity-log detail views now sanitize old values, new values, metadata, and raw properties before rendering.
- Historical patient phone/email-like values are protected even when they were stored before the privacy UI pass.

### Notifications and External Admin Views

- SMS event/message admin views now protect recipient phone numbers.
- Payment transaction admin views now protect payer phone numbers.

## Export Behavior

`PatientPrivacyService` now includes:

- `displayForExport()`
- `canExportSensitive()`
- `protectValueForDisplay()`
- `protectArrayForDisplay()`

Export mode is intentionally stricter than normal UI display. A user who can view a sensitive field in the application still needs `patients.export_sensitive.view` to receive the raw value in export/print-oriented contexts.

## Permissions Used

Phase 3 relies on the existing privacy permission model from `config/patient_privacy.php`, including granular field permissions and:

- `patients.export_sensitive.view`

No new route-level permissions were required for this phase.

## Localisation

Added and aligned EN/FR labels for Phase 3 privacy and workflow strings, including protected-data messaging, diagnosis finalisation labels, prescription route labels, and insurance fallback text.

`php scripts\localisation-audit.php` reports:

- `Active runtime candidates: 0`

`php scripts\localisation-parity-check.php` reports:

- EN/FR language keys are in parity.

## Tests

Added `tests/Feature/PatientPrivacyPhase3WorkflowMaskingTest.php`.

Covered:

- Consultation workspace masking for contact and level 3 clinical fields.
- PII permission revealing level 2 values without revealing level 3 clinical values.
- Clinical-sensitive permission revealing allergies/chronic conditions.
- Shared cards used by lab, pharmacy, and service-rendering workflows.
- Billing invoice, report, and print phone masking.
- Export-sensitive permission gate for raw export values.
- Consultation preview print masking.
- Activity-log historical value masking.
- SMS admin recipient masking.

## Verification

Passed:

- `php artisan test tests\Feature\PatientPrivacyInfrastructureTest.php tests\Feature\PatientPrivacyPhase2RuntimeMaskingTest.php tests\Feature\PatientPrivacyPhase3WorkflowMaskingTest.php`
- `php -l app\Services\PatientFieldAuthorizationService.php`
- `php -l app\Services\PatientMaskingService.php`
- `php -l app\Services\PatientPrivacyService.php`
- `php -l tests\Feature\PatientPrivacyPhase3WorkflowMaskingTest.php`
- `php artisan view:cache`
- `php artisan view:clear`
- `php scripts\localisation-audit.php`
- `php scripts\localisation-parity-check.php`
- `git diff --check`

Observed:

- `php artisan permissions:audit --strict` still fails because five pre-existing non-patient journey/settings mutation routes lack `can:`/`role:` middleware:
  - `[PUT] admin/settings/journey-notifications`
  - `[POST] admin/journey/handoffs/claim`
  - `[POST] admin/journey/handoffs/assign`
  - `[POST] admin/journey/handoffs/{assignment}/acknowledge`
  - `[POST] admin/journey/handoffs/{assignment}/resolve`
- The permission audit reports no missing route permissions.
- `git diff --check` only reports the existing CRLF/LF warning for `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.

## Remaining Gaps

- Patient create/edit forms still show editable values to users with access to those forms. Hardening edit forms should be a separate phase because it affects validation, update semantics, and usability.
- Break-glass access, patient consent directives, and emergency override reporting are not implemented in Phase 3.
- Stored historical logs are masked at display time, but no destructive backfill/sanitisation command was introduced.
- A wider end-to-end browser regression pass should be run before production rollout.
- No dedicated radiology-specific Blade surface was found beyond shared investigation/service workflows; shared cards and reports are protected where reused.
