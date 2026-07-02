# Patient Privacy Phase 4 - Edit Policy, Break-Glass Access & Directives

## Summary

Phase 4 extends the UHMS patient privacy rollout from display masking into edit/create policy, emergency break-glass access, patient privacy directives, and privacy audit review.

The implementation keeps `PatientPrivacyService` and `PatientFieldAuthorizationService` as the central policy path. Blade components ask the service for field state instead of hardcoding masking/edit decisions.

## Edit / Create Form Policy

- Level 0/1 fields remain editable with the normal patient create/edit permissions.
- New patient creation may capture phone, secondary phone, and email with `patients.create`.
- Identity, address, insurance identifiers, emergency contact phone data, and Level 3 clinical-sensitive fields require their specific edit permission when present.
- Masked or restricted edit fields are rendered disabled and without a submitted `name`, so masked placeholders cannot overwrite real patient data.
- `UpdatePatientRequest` safely preserves unchanged restricted fields when disabled inputs are omitted, but rejects malicious changed values with a validation error.

## Sensitive Field Edit Permissions

Added edit-specific permissions:

- `patients.pii.edit`
- `patients.contact.edit`
- `patients.identity.edit`
- `patients.address.edit`
- `patients.insurance.edit`
- `patients.emergency_contact.edit`
- `patients.clinical_sensitive.edit`

Rules enforced:

- `patients.pii.edit` can act as the broad Level 2 edit permission.
- `patients.pii.edit` does not permit Level 3 clinical-sensitive edits.
- Level 3 edits require `patients.clinical_sensitive.edit`.

## Server-Side Update Protection

Server-side protection was added to:

- `StorePatientRequest`
- `UpdatePatientRequest`
- `PatientController::updateMedicalSummary`

Unauthorized crafted requests cannot change protected sensitive patient fields.

## Break-Glass Access

Implemented:

- `patient_privacy_overrides` table
- `PatientPrivacyOverride` model
- start/revoke routes under patient profile
- active warning banner on patient profile
- 30-minute default duration from `config/patient_privacy.php`

Break-glass rules:

- Requires `patients.privacy.break_glass`
- Requires a reason
- Applies to the selected patient, with optional visit scope
- Reveals only configured Level 2 fields
- Does not grant export-sensitive access
- Does not grant edit access
- Can be revoked

Audit events:

- `PATIENT_PRIVACY_BREAK_GLASS_STARTED`
- `PATIENT_PRIVACY_BREAK_GLASS_USED`
- `PATIENT_PRIVACY_BREAK_GLASS_REVOKED`

No raw sensitive values are logged.

## Privacy Directives Foundation

Implemented:

- `patient_privacy_directives` table
- `PatientPrivacyDirective` model
- active directives relation on `Patient`
- directive creation UI on patient profile
- directive warning badge/details on patient profile

Permissions:

- `patients.privacy_directives.view`
- `patients.privacy_directives.manage`

Directive details are hidden from users without directive view permission.

## Privacy Audit Report

Added:

- `GET admin/patient-privacy/audit`
- `GET admin/patient-privacy/historical-log-dry-run`

Permission:

- `patients.privacy_audit.view`

The audit screen filters privacy events by patient, user, action, and date range. It shows sanitized metadata only.

## Historical Log Sanitisation Plan

Added dry-run command:

```bash
php artisan patient-privacy:audit-historical-logs --dry-run
```

Current dry-run result:

- Potentially affected logs: 148
- No records modified

Destructive sanitisation remains deferred until explicitly approved, with backup/rollback planning required before any mutation command is added.

## Roles Granted

`RoleSeeder` now creates the new permissions.

Default grants:

- Super Admin / Admin: all permissions through existing all-permission sync.
- Medical Records Officer: Level 2 patient edit permissions, privacy directive view/manage, privacy audit view.
- Clinical roles: clinical-sensitive edit, break-glass access, directive view.
- Finance / claims roles: insurance edit and directive view.

## Localisation

Added EN/FR keys for edit restriction, masked edit behavior, break-glass UI/audit descriptions, privacy directives, audit report, and historical dry-run output.

Verification:

- `php scripts\localisation-audit.php`: Active runtime candidates: 0
- `php scripts\localisation-parity-check.php`: EN/FR parity OK

## Tests Added

Added:

- `tests/Feature/PatientPrivacyPhase4EditBreakGlassTest.php`

Coverage includes:

- unauthorized contact edit blocked
- authorized contact edit allowed
- masked omitted value does not overwrite real data
- Level 2 edit separation from Level 3 clinical-sensitive edit
- break-glass permission, reason, expiry, audit, and export separation
- expired break-glass masking
- privacy directive creation and visibility controls
- privacy audit route permission
- historical dry-run non-mutation

## Verification Commands Run

```bash
php artisan migrate --force
php artisan db:seed --class=RoleSeeder --force
php artisan view:cache
php artisan view:clear
php artisan test tests\Feature\PatientPrivacyInfrastructureTest.php tests\Feature\PatientPrivacyPhase2RuntimeMaskingTest.php tests\Feature\PatientPrivacyPhase3WorkflowMaskingTest.php tests\Feature\PatientPrivacyPhase4EditBreakGlassTest.php
php scripts\localisation-audit.php
php scripts\localisation-parity-check.php
php artisan permissions:audit --strict
php artisan patient-privacy:audit-historical-logs --dry-run
git diff --check
```

Results:

- Privacy tests: 30 passed, 119 assertions
- View cache: passed
- Localisation audit: Active runtime candidates 0
- EN/FR parity: OK
- Permissions audit strict: passes
  - missing route permissions: 0
  - admin mutation routes without can/role middleware: 0
- Historical dry-run: 148 potential rows, no mutation
- `git diff --check`: passed; reported only CRLF/LF warning for generated localisation report

## Migration Note

`php artisan migrate --force` was initially blocked by a pre-existing pending archive migration using a non-null `timestamp` without a default. The migration was adjusted narrowly to use `dateTime('archived_at')`. The new privacy override migration also uses `dateTime` for non-null `starts_at` and `expires_at` to stay compatible with this MySQL configuration.

## Remaining Gaps

- Full privacy directive enforcement across all operational modules.
- Privacy dashboard analytics and breach-review workflow.
- Approved destructive historical log sanitisation command.
- Browser-level regression for create/edit masking states across all roles.
- `PATIENT_PRIVACY_BREAK_GLASS_EXPIRED` event emission can be added through a scheduler if expiry notification/audit is required beyond passive expiry.

## Next Recommended Phase

Phase 5 should enforce patient privacy directives inside operational workflows, add privacy analytics, and design the approved historical log sanitisation apply command with backup and rollback safeguards.
