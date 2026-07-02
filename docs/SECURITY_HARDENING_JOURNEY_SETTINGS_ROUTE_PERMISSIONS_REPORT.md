# Security Hardening: Journey & Settings Route Permissions Report

## Summary

This patch fixes the remaining `php artisan permissions:audit --strict` blocker by adding explicit `can:` middleware to the five journey/settings mutation routes that previously relied only on authentication and service-level checks.

The handoff service still performs destination-capability authorization after the route permission gate. The route middleware is now the front-door guard required by the permission audit.

## Routes Fixed

- `PUT admin/settings/journey-notifications`
  - `can:settings.journey_notifications.update`
- `POST admin/journey/handoffs/claim`
  - `can:journey.handoffs.claim`
- `POST admin/journey/handoffs/assign`
  - `can:journey.handoffs.assign`
- `POST admin/journey/handoffs/{assignment}/acknowledge`
  - `can:journey.handoffs.acknowledge`
- `POST admin/journey/handoffs/{assignment}/resolve`
  - `can:journey.handoffs.resolve`

## Permissions Added

Added to `RoleSeeder`:

- `settings.journey_notifications.update`
- `journey.handoffs.claim`
- `journey.handoffs.assign`
- `journey.handoffs.acknowledge`
- `journey.handoffs.resolve`

## Roles Granted

`Super Admin` and `Admin` receive all five permissions via `syncPermissions(Permission::all())`.

Journey handoff permissions were granted to operational roles that already participate in patient journey workflows. The underlying `JourneyHandoffAssignmentService` still restricts each action to users who can act on the handoff destination domain.

Granted handoff action permissions to:

- Doctor
- Consultant
- Specialist
- Physician Assistant
- Nurse
- Ward Nurse
- Emergency Doctor
- Emergency Nurse
- Triage Nurse
- Theatre Nurse
- Anaesthetist
- Receptionist
- Cashier
- Medical Records Officer
- Lab Technician
- Lab Manager
- Radiologist
- Pharmacist
- Accountant
- Finance Manager
- Claims Officer
- Blood Bank Officer

`settings.journey_notifications.update` was not granted to ordinary operational staff.

## Tests Added

Added `tests/Feature/Security/JourneySettingsRoutePermissionTest.php`.

Covered:

- All five mutation routes declare explicit `can:` middleware.
- Unauthorised user cannot update journey notification settings.
- Authorised user can update journey notification settings.
- Unauthorised user cannot claim, assign, acknowledge, or resolve handoffs.
- Authorised user can reach a permitted handoff mutation route.
- `permissions:audit --strict` passes after `RoleSeeder`.

## Verification

Passed:

- `php artisan db:seed --class=RoleSeeder --force`
- `php artisan route:list --path=admin/settings/journey-notifications -vv`
- `php artisan route:list --path=admin/journey/handoffs -vv`
- `php artisan permissions:audit --strict`
- `php artisan test tests\Feature\Security\JourneySettingsRoutePermissionTest.php`
- `php artisan test tests\Feature\PatientPrivacyInfrastructureTest.php tests\Feature\PatientPrivacyPhase2RuntimeMaskingTest.php tests\Feature\PatientPrivacyPhase3WorkflowMaskingTest.php`
- `git diff --check`

`permissions:audit --strict` now reports:

- Missing route permissions: `0`
- Admin mutation routes with no `can:`/`role:` middleware: `0`

The audit still lists possible duplicate permission-name pairs as advisory output, but strict mode exits successfully.

## Patient Privacy Regression Check

The Phase 1, Phase 2, and Phase 3 patient privacy tests still pass:

- 20 tests
- 83 assertions

No patient privacy route regression was introduced.
