You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS Phase 18 — Domain Failure Stabilisation: MAR, Lab Billing Gate, Visit Pathway

## Current Status

Phase 17 full-suite stabilisation improved the test suite from:

```text id="ytdcfc"
525 passing / 15 failing
```

to:

```text id="uwit64"
535 passing / 5 failing
```

Localisation is locked and must remain locked:

```text id="4eibqq"
Active runtime candidates: 0
tests/Feature/Localization: 12 passing
```

Do not restart localisation work.
Do not touch broad translation files unless a localisation test fails.
Do not change test strictness to hide domain bugs.

---

## Goal

Fix the remaining 5 domain/feature failures documented in Phase 17.

These failures are not localisation failures. They are domain regressions in:

```text id="b9seyg"
Medication Administration / MAR
Lab billing display gating
Visit status / emergency admission pathway
```

The goal is to bring the full suite to green without weakening permissions, hiding clinical/billing problems, or changing unrelated workflows.

---

## 1. Required Reports To Read First

Read:

```text id="np3g8v"
docs/PHASE_17_FULL_TEST_SUITE_REGRESSION_STABILISATION_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Use Phase 17’s remaining-failure list as the source of truth.

---

## 2. Remaining Failures To Fix

The 5 remaining failures are:

```text id="m6wh1c"
LabWorkflowTest › billed request opens from results route
MedicationAdministrationWorkflowTest › admission mar chart renders dose-cell modal trigger
MedicationAdministrationWorkflowTest › prn medications render PRN/SOS section
MedicationAdministrationWorkflowTest › mar chart service returns time_columns
VisitStatusPatientPathwayWorkflowTest › emergency bed billing allows Admit Patient → Admitted transition
```

Fix these only.

Do not make broad refactors.

---

# Part A — Lab Billing Display Gate

## Failure

```text id="zq3m32"
LabWorkflowTest › billed request opens from results route
```

Root cause documented in Phase 17:

```text id="zeg2cf"
Results-show page renders the "Bill Selected" action for an already-billed request.
```

## Required Fix

Find the lab results/results-show Blade/controller logic that renders the billing action.

The `Bill Selected` button/action must only appear when there are billable, unbilled selected items.

If the request/items are already billed, the action must not be shown.

Do not merely change the test.

Implement a proper guard based on existing billing state.

Check existing fields/relationships before adding anything new. Likely sources:

```text id="qtykrn"
invoice_id
billing_status
is_billed
invoice relationship
accepted/billable lab items
```

Use whichever exists in the current UHMS schema.

## Rules

Do not change invoice totals.
Do not create duplicate invoices.
Do not change lab acceptance workflow.
Do not hide legitimate billing actions for unbilled investigations.
Do not bypass permissions.

## Expected Result

The lab test should pass because already-billed requests no longer show `Bill Selected`.

---

# Part B — MAR Dose-Cell Modal Trigger

## Failure

```text id="6ptid3"
MedicationAdministrationWorkflowTest › admission mar chart renders dose-cell modal trigger
```

Root cause documented in Phase 17:

```text id="y1unjf"
Dose-cell modal trigger data-bs-target="#mar-dose-{id}" is not rendered.
Patient/drug/DUE content renders correctly.
```

## Required Fix

Inspect:

```text id="edmzba"
MedicationAdministrationWorkflowTest
MAR chart Blade view
MAR chart partials/components
MarChartService
medication administration schedule/dose models
```

Find where each due medication dose cell should render its modal trigger.

Restore or correctly render the expected Bootstrap modal trigger:

```html id="d4ofik"
data-bs-target="#mar-dose-{id}"
```

Use the actual dose/schedule/admin record ID expected by the existing test and view structure.

## Rules

Do not hardcode IDs.
Do not fake the markup only for tests.
Do not remove modal behavior.
Do not change clinical dose semantics.
Do not break administered/skipped/held status display.
Do not expose restricted medication data.

## Expected Result

The MAR chart renders actionable dose cells with modal triggers where appropriate.

---

# Part C — MAR PRN / SOS Section

## Failure

```text id="b9kr60"
MedicationAdministrationWorkflowTest › prn medications render PRN/SOS section
```

Root cause documented in Phase 17:

```text id="zky014"
PRN / SOS Medications section is not rendered for PRN schedules.
```

## Required Fix

Inspect the PRN branch in:

```text id="xz9fyr"
MarChartService
MAR chart Blade view
MedicationAdministrationWorkflowTest factory/setup
Medication schedule model fields
```

Confirm how the system marks PRN/SOS schedules.

Possible fields may include:

```text id="qkoz9p"
is_prn
schedule_type
frequency
administration_type
prn_reason
```

Render the PRN/SOS section when PRN medication schedules exist.

The section label must use existing localisation keys. Do not introduce hardcoded English.

## Rules

Do not convert regular scheduled meds into PRN meds.
Do not hide PRN meds inside regular time columns.
Do not change actual administration recording rules unless the service is wrong.
Do not break localisation lock.

## Expected Result

PRN/SOS medication schedules appear under the PRN/SOS section and the test passes.

---

# Part D — MAR Time Columns

## Failure

```text id="t1zwz4"
MedicationAdministrationWorkflowTest › mar chart service returns time_columns
```

Root cause documented in Phase 17:

```text id="kizgyw"
MarChartService returns empty time_columns for the test schedule window.
```

## Required Fix

Inspect `MarChartService`.

Check how it computes:

```text id="mf3jmd"
time_columns
schedule window
dose times
frequency
start/end date
admission date
current chart date
timezone/date normalisation
```

The service must return expected time columns for scheduled medications that fall within the MAR chart window.

Likely issue types:

```text id="hd4kb5"
date mismatch
time parsing mismatch
timezone normalisation issue
frequency mapping issue
schedule start/end not included
empty collection because status filter excludes due schedules
```

Fix the root cause.

## Rules

Do not hardcode the test date.
Do not force time_columns globally.
Do not break real MAR chart grouping.
Do not change PRN behavior unless required for the PRN fix.

## Expected Result

Scheduled medication charts produce non-empty `time_columns` when schedules exist in the requested window.

---

# Part E — Visit Pathway / Emergency Admission Transition

## Failure

```text id="u2qqla"
VisitStatusPatientPathwayWorkflowTest › emergency bed billing allows Admit Patient → Admitted transition
```

Root cause documented in Phase 17:

```text id="ozoe19"
VisitStatusService rejects Admit Patient → Admitted transition.
```

## Required Fix

Inspect:

```text id="58xr58"
VisitStatusService
VisitStatusPatientPathwayWorkflowTest
Emergency bed billing workflow
Admission workflow
Visit status constants/enums
```

The emergency bed billing pathway must allow the intended transition:

```text id="arivri"
Admit Patient → Admitted
```

Only if it is clinically and workflow-valid.

Reconcile the state machine with the emergency admission pathway.

## Rules

Do not allow invalid transitions globally.
Do not bypass VisitStatusService.
Do not bypass emergency bed billing workflow.
Do not hardcode emergency service IDs.
Do not hardcode Emergency/Casualty service IDs.
Do not create NHIS-specific logic.
Do not break normal OPD/admission transitions.

If the transition should only apply to emergency/admission contexts, scope it properly.

## Expected Result

Emergency bed billing pathway can complete the intended admission transition, and invalid transitions remain blocked.

---

# 3. Test Strategy

Run the focused tests first:

```bash id="4wuly3"
php artisan test tests/Feature/LabWorkflowTest.php
php artisan test tests/Feature/MedicationAdministrationWorkflowTest.php
php artisan test tests/Feature/VisitStatusPatientPathwayWorkflowTest.php
```

Then run localisation lock:

```bash id="c817pl"
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
```

Then run the full suite:

```bash id="m03bao"
php artisan test
```

Expected final target:

```text id="vvnlx9"
540 passing / 0 failing
```

If the total number of tests changes, report the exact final count.

---

# 4. Safety Rules

Do not weaken:

```text id="bcyjuf"
permissions
policies
gates
middleware
clinical confidentiality
financial visibility
stock-cost visibility
audit logging
```

Do not bypass:

```text id="16ot0w"
ActivityLogService
VisitStatusService
Lab billing services
MAR services
```

Do not move business logic into Blade.

Do not change tests to hide real bugs.

Test changes are allowed only when the test expectation is demonstrably outdated and the production behavior is correct. For the 5 current failures, assume production behavior needs inspection first.

---

# 5. Localisation Lock Rules

These must remain true:

```text id="z4p69g"
Active runtime candidates: 0
EN/FR parity passes
12 localisation tests pass
```

If any new label is needed, add EN/FR keys with parity.

Do not hardcode English labels.

Run:

```bash id="j4zsm0"
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
```

---

# 6. Verification Commands

Run:

```bash id="wdgmll"
php artisan view:clear
php artisan config:clear
php artisan cache:clear
php artisan route:list
php artisan view:cache
php artisan view:clear
```

Run:

```bash id="v5l9c8"
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done
```

Run:

```bash id="g2ioi1"
php artisan test tests/Feature/LabWorkflowTest.php
php artisan test tests/Feature/MedicationAdministrationWorkflowTest.php
php artisan test tests/Feature/VisitStatusPatientPathwayWorkflowTest.php
php artisan test tests/Feature/Localization
php scripts/localisation-audit.php
php artisan test
```

Run:

```bash id="mcb1z2"
git diff --check
```

If frontend assets are touched and dependencies are available:

```bash id="x9p1gy"
npm run build
```

---

# 7. Documentation Required

Create:

```text id="oqu6f5"
docs/PHASE_18_DOMAIN_FAILURE_STABILISATION_REPORT.md
```

Include:

* summary
* starting full-suite result
* focused failures fixed
* root cause per failure
* files changed
* production code changed
* test code changed, if any
* whether any language keys were added
* localisation audit result
* localisation test result
* route list result
* view cache result
* full-suite final result
* remaining failures, if any
* known risks
* next recommended phase

---

# 8. Acceptance Criteria

Phase 18 is complete only when:

* the 5 remaining Phase 17 failures are fixed or explicitly documented with deeper evidence
* LabWorkflowTest passes
* MedicationAdministrationWorkflowTest passes
* VisitStatusPatientPathwayWorkflowTest passes
* localisation tests still pass
* active runtime candidates remain 0
* route list works
* view cache compiles
* full test suite is run
* no permissions are weakened
* no clinical/financial data exposure is introduced
* no business logic is moved into Blade
* no NHIS-only logic is introduced
* no audit logging is bypassed
* documentation report is created

Proceed with UHMS Phase 18 now.