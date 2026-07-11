You are working on the UHMS Laravel codebase.

The Front Desk Operations module is now feature-complete across:

```text id="d5xlcc"
18A — Visitor Logs, Call Logs, Courier Logs, Dashboard
18B — Patient Visitor Management, Visitor Pass, Warnings, History
18C — Call Follow-up Queue, Courier Dispatch/Handover Workflow
18D — Reports, Exports, Operational Analytics
18E — Shift Handover, Lost & Found, Incident/Security Desk
```

Now implement:

# Phase 18F — Front Desk Module Wrap-up, Full-Suite Recovery, and Closure

## Goal

Close the Front Desk Operations module properly.

This phase must:

```text id="tv3n4r"
run the full suite
fix the 3 deferred consultation failures
confirm Front Desk still passes
confirm localisation/permission/audit gates still pass
produce final module closure documentation
```

This is a cleanup and closure phase.

Do not add new Front Desk features unless a test exposes a real defect.

---

# Known Baseline

Front Desk focused tests are green:

```text id="c9adnf"
109 passed
```

Safety gates are green:

```text id="pvzve4"
LanguageParityTest
ActiveRuntimeLocalizationAuditTest
RouteLoadMemoryTest
PermissionsAuditStrictTest
```

Known deferred failures from previous full-suite runs:

```text id="smo5co"
1. ConsultationClinicalSectionsTest::consultation page shows required clinical order
2. ConsultationJavascriptLifecyclePhase2Test::workflow controls no longer use inline event handlers
3. ConsultationStructurePhase3Test::no inline workflow handlers exist in show or partials
```

Known source:

```text id="p6oydk"
resources/views/consultations/show.blade.php
committed inline onsubmit handlers around lines 938 / 1299 / 1491
clinical-section-order assertion involving Treatments
```

These were deferred throughout Phase 18A–18E and must now be fixed.

---

# Important Rules

Do not weaken or skip tests.

Do not hide the failures.

Do not remove audit guards.

Do not disable UI structure checks.

Do not change Front Desk behavior unless a Front Desk test proves it is involved.

Do not introduce inline handlers:

```text id="wed1d8"
onclick=
onsubmit=
onchange=
window.location.href
location.reload()
```

Use existing UHMS delegated JavaScript / consultation bridge patterns.

Preserve consultation workflow behavior.

Preserve Front Desk module behavior.

---

# 1. Start With Status Verification

Run:

```bash id="2eccfj"
php artisan test tests/Feature/FrontDesk
php artisan test tests/Feature/Localization/LanguageParityTest.php
php artisan test tests/Feature/Localization/ActiveRuntimeLocalizationAuditTest.php
php artisan test tests/Feature/System/RouteLoadMemoryTest.php
php artisan test tests/Feature/Permissions/PermissionsAuditStrictTest.php
```

Expected:

```text id="z1ub8s"
FrontDesk passes
safety gates pass
```

Then reproduce the three deferred failures individually:

```bash id="3hbigz"
php artisan test tests/Feature/Consultations/ConsultationClinicalSectionsTest.php
php artisan test tests/Feature/Consultations/ConsultationJavascriptLifecyclePhase2Test.php
php artisan test tests/Feature/Consultations/ConsultationStructurePhase3Test.php
```

Document the exact failure messages before fixing.

---

# 2. Fix Inline Handler Failures

Inspect:

```text id="7c4uan"
resources/views/consultations/show.blade.php
consultation partials included by show.blade.php
consultation JavaScript bridge files
```

Find all inline workflow handlers:

```bash id="hj0mxy"
grep -R "onsubmit=" -n resources/views/consultations
grep -R "onclick=" -n resources/views/consultations
grep -R "onchange=" -n resources/views/consultations
```

Replace inline handlers with existing project-approved delegated behavior.

Use existing conventions such as:

```text id="me9qhw"
data-ajax-form
data-consultation-form
data-refresh-section
data-route-context-required
data-modal-close-on-success
data-confirm
```

or the existing UHMS consultation bridge pattern already used elsewhere.

For each form that previously used `onsubmit=`:

```text id="ul2mg9"
preserve POST route
preserve CSRF
preserve validation display
preserve modal behavior
preserve section refresh
preserve route context
preserve normal submit fallback if possible
```

Do not simply delete behavior.

---

# 3. Fix Consultation Clinical Section Order Failure

Investigate:

```text id="6kqvcn"
ConsultationClinicalSectionsTest::consultation page shows required clinical order
```

The known issue involves the **Treatments** clinical section.

Determine whether:

```text id="cccv12"
the Treatments section no longer renders
the section was renamed
the section exists but its stable marker changed
the test expectation is stale
the specialty layout/dedup changes hid the expected marker
```

Preferred fix:

```text id="f2s096"
restore the intended Treatments section/order marker
```

If the user-facing label intentionally changed, add a backward-compatible stable marker instead of weakening the test.

Do not remove treatment/procedure/task workflow.

Do not break specialist workspace section presentation labels.

---

# 4. Run Focused Consultation Checks

After fixes, run:

```bash id="bhurz9"
php artisan test tests/Feature/Consultations/ConsultationClinicalSectionsTest.php
php artisan test tests/Feature/Consultations/ConsultationJavascriptLifecyclePhase2Test.php
php artisan test tests/Feature/Consultations/ConsultationStructurePhase3Test.php
```

Then run:

```bash id="3pydvg"
php artisan test tests/Feature/Consultations
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan test tests/Feature/System/RouteLoadMemoryTest.php
```

If any focused consultation test fails, fix the underlying issue.

---

# 5. Reconfirm Front Desk Module

Run:

```bash id="p1q8yi"
php artisan test tests/Feature/FrontDesk
```

Expected:

```text id="cmf3fe"
all Front Desk tests pass
```

Also run the Front Desk gates:

```bash id="7cm8ad"
php artisan test tests/Feature/Localization/LanguageParityTest.php
php artisan test tests/Feature/Localization/ActiveRuntimeLocalizationAuditTest.php
php artisan test tests/Feature/Permissions/PermissionsAuditStrictTest.php
php artisan test tests/Feature/System/RouteLoadMemoryTest.php
```

---

# 6. Build / Route / View Checks

Run:

```bash id="3j7a7c"
php artisan route:list
php artisan view:cache
php artisan view:clear
npm run build
```

Fix any issues honestly.

---

# 7. Run Full Suite

Now run the broad suite:

```bash id="o19wwp"
php artisan test
```

Expected target:

```text id="aszae3"
full suite green
```

If failures remain:

```text id="syydnp"
classify every failure
identify whether it is caused by Phase 18F changes, Front Desk, or pre-existing debt
do not skip or weaken tests
fix caused failures
document any true unrelated remaining failure honestly
```

---

# 8. Front Desk Module Closure Audit

Create a short internal closure audit confirming:

```text id="l968q3"
all Front Desk routes are permission-gated
all Front Desk write actions audit-log
all Front Desk pages use EN/FR localisation
all Front Desk exports are privacy-safe
no clinical or billing data is exposed
phones are masked where required
visitor/call/courier/handover/lost-found/incident workflows pass tests
report/export permissions behave correctly
sidebar entries are permission-gated
```

Do not overbuild. This can be a report section plus tests already in place.

---

# 9. Documentation

Create:

```text id="h6d2s3"
docs/FRONT_DESK_MODULE_WRAPUP_AND_FULL_SUITE_RECOVERY_REPORT.md
```

Report structure:

```text id="xi6pzh"
# Front Desk Module Wrap-up and Full-Suite Recovery Report

## Summary
Explain that Front Desk 18A–18E is complete and this phase closed the module.

## Initial State
List focused Front Desk status and the 3 deferred consultation failures.

## Consultation Cleanup
Explain the inline handler fixes and clinical section order fix.

## Files Modified
List modified files.

## Front Desk Closure Audit
Summarize route/permission/audit/localisation/privacy/export coverage.

## Tests Run
List focused Front Desk, focused consultation, safety gates, route/view/build checks.

## Full Suite Result
Include final php artisan test result.

## Risk / Safety Notes
Confirm Front Desk behavior was preserved and no clinical/billing data exposure was introduced.

## Known Issues / Follow-up
Only list true remaining follow-ups, such as:
- visitor pass QR/PDF
- SMS callback reminders
- incident attachments/photos
- dedicated Security role
- scheduled front desk reports
```

---

# Acceptance Criteria

Phase 18F is complete only when:

```text id="k3n4kh"
the 3 deferred consultation failures are fixed
focused consultation tests pass
FrontDesk focused suite passes
localisation gate passes
active runtime localisation audit passes
route memory test passes
permissions strict audit passes
route list passes
view cache passes
frontend build passes
full suite is run
full suite result is documented
Front Desk closure report is created
```

Stop after Phase 18F and upload the report.
