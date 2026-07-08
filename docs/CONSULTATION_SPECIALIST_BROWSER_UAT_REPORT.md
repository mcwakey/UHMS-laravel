# Consultation Specialist Browser UAT Report

## Summary

Phase 16 adds an opt-in browser/manual testing foundation for personalised consultation workspaces. It provides stable specialist fixture data, metadata for browser automation, Playwright smoke coverage, responsive checks, a specialist report page smoke, and non-developer UAT documentation.

## Existing Browser Fixture Findings

The existing consultation browser fixture uses `ConsultationBrowserFixtureService` and the `consultation:e2e-fixture --json` command. It is guarded to local/testing environments, creates one stable general consultation doctor, patient, visit, active consultation route, medical record, lab service, procedure service, and prescription drug, then writes JSON metadata to `storage/app/testing/consultation-workspace-e2e.json`.

The Playwright helper `tests-e2e/tests/support/consultation-fixture.ts` shells into Artisan, validates the JSON, and checks that the metadata file was written. The existing `tests-e2e/tests/consultation-workspace.spec.ts` logs in with fixture credentials, opens the workspace, checks no console/server errors, verifies consultation config, and exercises delegated controls for procedure, lab, prescription, safety warning, and section refresh behavior.

## Files Added

- `app/Services/Consultation/Specialty/ConsultationSpecialtyBrowserFixtureService.php`
- `app/Console/Commands/ConsultationSpecialtyE2EFixtureCommand.php`
- `tests/Feature/Consultations/ConsultationSpecialtyBrowserFixtureTest.php`
- `tests-e2e/tests/consultation-specialty-workspaces.spec.ts`
- `docs/CONSULTATION_SPECIALIST_WORKSPACE_UAT_CHECKLIST.md`
- `docs/CONSULTATION_SPECIALIST_VISUAL_QA_NOTES.md`
- `docs/CONSULTATION_SPECIALIST_BROWSER_UAT_REPORT.md`

## Files Modified

- `tests-e2e/tests/support/consultation-fixture.ts`
- `tests-e2e/package.json`

## Fixture Design

The new command is:

```bash
php artisan consultation:specialty-e2e-fixture --json
```

It is available only in local/testing environments. It seeds specialist profile and order-set definitions if needed, creates one stable admin user, and creates stable doctors, departments, patients, visits, active consultation routes, medical records, profile mappings, and representative structured entries for:

- general_medicine
- physiotherapy
- ophthalmology
- dental
- obstetrics
- gynecology
- ent
- pediatrics
- emergency
- orthopedics
- surgery

Fixture users use stable emails and the password `password`. Records are marked with `E2E` names/numbers/descriptions and route notes. The fixture does not create fake invoices or apply billing charges.

Each profile metadata item includes login URL, workspace URL, profile code/label, doctor credentials, patient number, visit ID, route ID, expected sections, expected quick actions, expected structured section, reload text, and a save probe.

## Browser Coverage

New Playwright file:

- `tests-e2e/tests/consultation-specialty-workspaces.spec.ts`

Workspace smoke covers:

- general_medicine
- physiotherapy
- ophthalmology
- dental
- obstetrics
- ent
- pediatrics
- emergency
- surgery

The smoke checks workspace load, console/server errors, profile header, sidebar sections, quick action navigation, structured save/reload where applicable, readiness card, summary preview, order-set preview where present, and billing context safety.

## Responsive Coverage

Responsive smoke checks desktop, tablet, and mobile viewports for:

- general_medicine
- obstetrics
- emergency
- dental

It checks that the workspace header, tab content, structured forms, readiness card, and order-set modal remain reachable without major horizontal overflow.

## Manual UAT Checklist

Manual checklist:

- `docs/CONSULTATION_SPECIALIST_WORKSPACE_UAT_CHECKLIST.md`

It includes preparation, fixture credentials, per-specialty checklists, billing awareness, readiness, summary builder, order sets, reporting dashboard, responsive/mobile checks, bug report template, and pass/fail sign-off table.

Visual QA notes:

- `docs/CONSULTATION_SPECIALIST_VISUAL_QA_NOTES.md`

## Report Page Smoke

The specialist Playwright smoke logs in as the fixture admin, opens `/admin/reports/consultation-specialties`, confirms summary/report content, applies a specialty filter, verifies a table remains visible, and confirms the CSV export link exists.

## Defects Found And Fixed

- The fixture feature test initially counted the admin user as a specialist doctor because the admin email matched the broad `specialist.*.e2e@uhms.test` pattern. The assertion was tightened to exclude the admin email.
- The specialist Playwright smoke exposed brittle assumptions around structured section pane IDs. Browser assertions now use the same `specialty-{section}-section` target convention as the workspace registry.
- The structured-entry save probe now submits through the workspace JSON contract with a fresh idempotency key and verifies the saved record after reload.
- The summary and order-set preview checks were made tolerant of the existing Bootstrap/modal runtime while still validating the preview content.
- The reporting smoke now submits the actual report filter form and ignores the known background notification poll 403 that is unrelated to the report page.

## Test Results

- `php -l app/Services/Consultation/Specialty/ConsultationSpecialtyBrowserFixtureService.php`: passed.
- `php -l app/Console/Commands/ConsultationSpecialtyE2EFixtureCommand.php`: passed.
- `php artisan migrate --force`: passed; nothing to migrate.
- `php artisan db:seed --class=ConsultationSpecialtySeeder --force`: passed.
- `php artisan test tests/Feature/Consultations/ConsultationSpecialtyBrowserFixtureTest.php`: passed, 6 tests, 145 assertions.
- `php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php`: passed, 4 tests, 20 assertions.
- `php artisan test tests/Feature/System/RouteLoadMemoryTest.php`: passed, 2 tests, 6 assertions.
- `php artisan route:list`: passed.
- `php artisan test tests/Feature/Consultations`: passed, 173 tests, 2704 assertions.
- `php artisan view:cache`: passed.
- `npm run build`: passed.
- `php artisan view:clear`: passed.
- `npx playwright test tests/consultation-workspace.spec.ts --config=playwright.config.ts --workers=1`: passed, 1 test.
- `npx playwright test tests/consultation-specialty-workspaces.spec.ts --config=playwright.config.ts --workers=1`: passed, 22 tests.

## Full Suite Result

`php artisan test` passed: 1425 tests, 7267 assertions, 1239.07 seconds.

## Backward Compatibility

The fixture service is opt-in and local/testing-only. It does not modify production seeders, create production fake clinical data, apply billing charges, or change consultation save/billing behavior.

## Known Issues / Follow-up

- Deeper browser assertions can be added later for every field in every specialist section.
- Advanced visual redesign is still future work.
- Mobile polish may be needed for very dense specialist forms.
- Gynecology and orthopedics fixtures are included, but not in the minimum Playwright workspace smoke loop yet.
- Future feature work remains for partograph, odontogram, pediatric growth charting, and orthopedic diagram views.
