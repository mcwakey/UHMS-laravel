You are working on the UHMS Laravel codebase.

Phase 15 is complete. Specialist reporting and dashboard integration has been implemented and the full suite is green.

Current personalised consultation profiles:

```text
general_medicine
physiotherapy
ophthalmology
dental
obstetrics
gynecology
ent
pediatrics
emergency
orthopedics
surgery
```

The system now supports:

```text
specialty profiles
specialty sections
structured forms
favorites
order sets
readiness
summary builder
doctor personal workspace
admin configuration
billing/service mapping
reporting/dashboard analytics
CSV export
full-suite green baseline
```

Now implement:

# Phase 16 — Specialist Browser Fixtures, Visual QA, and UAT Pack

## Goal

Create a practical browser/manual testing foundation for all personalised consultation workspaces.

This phase should make it easy to open each specialty workspace in the browser and verify that the real UI behaves correctly.

This is not a new clinical feature phase.

The goal is:

```text
seed realistic opt-in test data
create browser fixtures for representative specialist workspaces
expand Playwright coverage safely
prepare a manual UAT checklist
validate responsive UI and no-console-error behavior
keep the full suite green
```

---

# Important Rules

Do not modify default production seeders in a way that creates fake clinical data.

Do not create fake invoices or patient records in default launch seeders.

All browser/manual test data must be opt-in and clearly marked as testing/demo data.

Do not rewrite the consultation workspace.

Do not create separate specialty pages/controllers.

Do not change clinical save behavior.

Do not change billing apply behavior.

Do not run destructive operations against non-testing environments.

Do not add a huge slow browser suite. Keep it representative and stable.

---

# Required Deliverables

## 1. Inspect Existing Browser Fixture Pattern

Inspect existing consultation E2E/browser fixture code.

Look for:

```text
ConsultationBrowserFixtureService
ConsultationE2EFixtureCommand
consultation:e2e-fixture
tests-e2e/tests/consultation-workspace.spec.ts
Playwright auth/session setup
fixture metadata format
test users
test departments
test visits/routes
```

Document the current fixture behavior in the report.

---

## 2. Add Specialist Browser Fixture Support

Extend the existing fixture service/command or add a focused service:

```text
app/Services/Consultation/Specialty/ConsultationSpecialtyBrowserFixtureService.php
```

Only add a new service if it keeps the existing fixture clean.

The fixture should be opt-in and available only in local/testing environments.

Suggested command:

```bash
php artisan consultation:specialty-e2e-fixture --json
```

or extend existing:

```bash
php artisan consultation:e2e-fixture --specialties --json
```

The command should create or return fixture metadata for these representative profiles:

```text
general_medicine
physiotherapy
ophthalmology
dental
obstetrics
ent
pediatrics
emergency
surgery
```

Optional, if easy:

```text
gynecology
orthopedics
```

Fixture metadata should include:

```json
{
  "login_url": "...",
  "workspace_url": "...",
  "profile_code": "...",
  "profile_label": "...",
  "doctor_email": "...",
  "doctor_password": "...",
  "patient_number": "...",
  "visit_id": "...",
  "route_id": "...",
  "expected_sections": [],
  "expected_quick_actions": [],
  "expected_structured_section": "..."
}
```

Rules:

* Use stable test users.
* Use stable departments mapped to specialty profiles.
* Create visit/consultation route/session per profile.
* Use existing factories/models where possible.
* Mark records with testing metadata where available.
* Do not create billing charges unless explicitly safe and already supported by test patterns.
* If service mappings exist, expose billing context but do not apply charges automatically.

---

## 3. Seed Representative Structured Entries

For each fixture profile, seed one or two representative entries so the browser can verify reload behavior.

Examples:

```text
physiotherapy: pain assessment
ophthalmology: visual acuity
dental: tooth chart
obstetrics: antenatal vitals
ent: ear assessment
pediatrics: growth assessment
emergency: primary survey
surgery: consent
```

Also create at least one incomplete case where readiness blockers are visible.

Do not over-seed.

---

## 4. Expand Playwright Browser Smoke

Create:

```text
tests-e2e/tests/consultation-specialty-workspaces.spec.ts
```

Keep the existing general consultation smoke intact.

The new smoke test should be data-driven.

For each selected profile fixture:

```text
login as fixture doctor
open workspace URL
confirm no console/server errors
confirm profile label/header appears
confirm sidebar sections render
confirm at least one expected quick action appears
click one quick action
confirm target section becomes active/visible
save one structured field
refresh page
confirm saved value reloads
open readiness card/section
generate summary preview
if order sets exist, open preview for one order set
confirm billing card/context does not crash
```

Profiles to cover at minimum:

```text
general_medicine
physiotherapy
ophthalmology
dental
obstetrics
ent
pediatrics
emergency
surgery
```

If runtime becomes too slow, split into two Playwright projects or mark as specialist smoke and run separately.

Do not make this flaky.

Use stable selectors.

Avoid relying on text that is too translation-sensitive unless the fixture sets locale.

---

## 5. Add Responsive Smoke Checks

For at least these viewports:

```text
desktop
tablet
mobile
```

Run representative profiles:

```text
general_medicine
obstetrics
emergency
dental
```

Check:

```text
workspace header does not overlap
sidebar/tabs are usable
structured form fields are accessible
right panel/readiness card remains reachable
modal closes correctly
no horizontal layout break that hides critical actions
```

Keep these checks light.

---

## 6. Add Manual UAT Checklist

Create:

```text
docs/CONSULTATION_SPECIALIST_WORKSPACE_UAT_CHECKLIST.md
```

The checklist should be usable by non-developer testers.

Include sections:

```text
Test preparation
Login credentials / fixture command
How to open each specialty workspace
General medicine checklist
Physiotherapy checklist
Ophthalmology checklist
Dental checklist
Obstetrics checklist
Gynecology checklist
ENT checklist
Pediatrics checklist
Emergency checklist
Orthopedics checklist
Surgery checklist
Billing awareness checklist
Readiness checklist
Summary builder checklist
Order set checklist
Reporting dashboard checklist
Responsive/mobile checklist
Bug reporting template
Pass/fail sign-off table
```

Each specialty checklist should include:

```text
workspace opens
correct specialty name appears
expected sections appear
quick actions work
structured field saves
value reloads after refresh
readiness shows correct status
summary preview generates
order set preview works
billing card does not crash
no console/page error
```

---

## 7. Add Visual QA Notes

Create or include in the UAT checklist:

```text
docs/CONSULTATION_SPECIALIST_VISUAL_QA_NOTES.md
```

or combine with the UAT checklist.

Document:

```text
expected workspace header behavior
section/sidebar behavior
card spacing
empty states
mobile behavior
known acceptable limitations
screens that need future redesign
```

Do not redesign everything in this phase. Only fix clear breakages.

---

## 8. Admin Setup Verification

Add browser or feature-level verification that admin can inspect the profiles.

At minimum:

```text
profiles list shows all 11 profiles
sections page opens for each profile
favorites page opens
order sets page opens
service mappings page opens
report page opens
```

This can remain feature-test coverage if browser coverage would be too slow.

---

## 9. Reporting Browser Smoke

Add a small browser smoke for the specialist report page:

```text
login as admin
open /admin/reports/consultation-specialties
confirm summary cards render
apply a specialty/date filter
confirm table/chart area remains visible
trigger CSV export request or verify export link exists
```

Do not deeply test analytics in browser. Feature tests already cover metrics.

---

## 10. Stability and Test Commands

Run:

```bash
php artisan migrate
php artisan db:seed --class=ConsultationSpecialtySeeder
php artisan test tests/Feature/Consultations
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan test tests/Feature/System/RouteLoadMemoryTest.php
php artisan route:list
php artisan view:cache
php artisan view:clear
npm run build
```

Run Playwright:

```bash
cd tests-e2e
npx playwright test tests/consultation-workspace.spec.ts
npx playwright test tests/consultation-specialty-workspaces.spec.ts
```

If the project path expects Playwright from root, use the existing project convention.

Then run full suite:

```bash
php artisan test
```

If full suite fails, document honestly and classify failures.

---

## 11. Report

Create:

```text
docs/CONSULTATION_SPECIALIST_BROWSER_UAT_REPORT.md
```

The report must include:

```text
# Consultation Specialist Browser UAT Report

## Summary
Explain what was implemented and validated.

## Existing Browser Fixture Findings
Document the existing fixture/Playwright setup.

## Files Added
List new files.

## Files Modified
List modified files.

## Fixture Design
Explain command/service, profiles covered, test users, departments, visits/routes, and metadata.

## Browser Coverage
List Playwright files and profiles covered.

## Responsive Coverage
List profiles/viewports checked.

## Manual UAT Checklist
Link to checklist and summarize content.

## Report Page Smoke
Explain specialist reporting browser coverage.

## Defects Found and Fixed
List any UI/Blade/JS/responsive/selector issues fixed.

## Test Results
Include all command results.

## Full Suite Result
Include final `php artisan test` result.

## Backward Compatibility
Confirm consultation workspace, specialist reporting, billing, admin config, and patient workflows remain stable.

## Known Issues / Follow-up
List:
- deeper specialist browser coverage if deferred
- advanced visual redesign needs
- future mobile polish
- specialist fixtures for gynecology/orthopedics if not covered
- future partograph/odontogram/growth-chart feature work
```

---

# Acceptance Criteria

Phase 16 is complete only when:

* Specialist browser fixture command/service exists or existing fixture supports specialist profiles.
* Browser fixture metadata is available for representative profiles.
* Playwright specialist workspace smoke exists.
* General consultation smoke remains green.
* At least 9 profiles are covered in browser smoke or clearly documented if fewer are covered.
* Responsive smoke covers desktop/tablet/mobile for representative profiles.
* Manual UAT checklist exists.
* Specialist report page browser smoke exists.
* Admin/profile setup remains accessible.
* Focused consultation feature suite passes.
* Workspace stabilisation passes.
* Route memory test passes.
* Route list and view cache pass.
* Frontend build passes.
* Full suite is run and documented.
* Browser UAT report is created.

Stop after Phase 16.
