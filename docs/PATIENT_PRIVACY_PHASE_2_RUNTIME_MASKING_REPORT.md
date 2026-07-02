# Patient Privacy Phase 2 Runtime Masking Report

## Objective

Phase 2 protects patient-sensitive data at runtime on patient search, patient profile/listing, and shared patient card surfaces by routing display through the Phase 1 privacy services and Blade component.

## Protected Runtime Surfaces

- Patient search JSON for visit creation now masks contact, email, and identity fields unless the current user has the required privacy permission.
- Patient merge search JSON now masks phone numbers.
- Patient insurance JSON payloads used by visit/appointment flows now mask membership, policy, and CCC values.
- Patient index now masks phone and email columns.
- Patient profile/show now masks contact, address, digital address, identity, insurance, emergency contact, allergy, and chronic condition values.
- Shared `x-patient-card`, `x-patient-long-card`, and `x-patient-selection-card` now use `x-patient-protected-field` for sensitive patient fields.
- Appointment and visit edit patient summaries now mask patient phone numbers.

## Authorization Model

Runtime masking continues to use `App\Services\PatientPrivacyService`, backed by:

- `App\Services\PatientFieldAuthorizationService`
- `App\Services\PatientMaskingService`
- `config/patient_privacy.php`
- `x-patient-protected-field`

No new inline masking rules were introduced in views. Display decisions continue to be permission-driven.

## Localisation

Added Phase 2 patient privacy labels in English and French:

- `patients.privacy.sensitive_patient_information`
- `patients.privacy.restricted_patient_data`
- `patients.privacy.protected_data`
- `patients.privacy.hidden_for_privacy`
- `patients.privacy.insufficient_privacy_permission`

Language parity check passed.

## Tests

Added `tests/Feature/PatientPrivacyPhase2RuntimeMaskingTest.php`.

Covered:

- Search JSON masks sensitive fields without permission.
- Search JSON reveals full contact with contact permission.
- Merge search JSON does not leak raw phone numbers.
- Patient index masks and reveals contact fields by permission.
- Patient profile masks level 2 and level 3 fields correctly.
- Clinical-sensitive permission reveals allergies/chronic conditions.
- Shared patient card masks contact fields.

## Verification

Passed:

- `php artisan test tests\Feature\PatientPrivacyInfrastructureTest.php tests\Feature\PatientPrivacyPhase2RuntimeMaskingTest.php`
- `php -l` on touched PHP service/controller/test files
- `php artisan view:cache`
- `php artisan view:clear`
- `php scripts\localisation-parity-check.php`
- `git diff --check`

Observed:

- `php scripts\localisation-audit.php` completed and wrote `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`.
- `php artisan permissions:audit --strict` reported no missing route permissions, but failed strict mode because five non-patient journey/settings mutation routes are missing `can:`/`role:` middleware. These are outside the Phase 2 patient privacy scope.

## Remaining Gaps

- Patient create/edit forms still intentionally show editable values to users who can access those forms. If edit-screen privacy hardening is required, it should be handled as a separate phase because it affects form usability and update semantics.
- Non-patient-specific reporting exports and deep clinical workspace screens should be reviewed in the next privacy phase.
