You are working on the UHMS Laravel codebase.

Phase 14A fixed the test runtime memory cap. The wide suite now completes without the old `Allowed memory size of 134217728 bytes exhausted` fatal error.

Current full-suite result:

```text
20 failed, 1395 passed, 7065 assertions
```

The remaining failures are real application/test assertion failures, not runtime memory failures.

This phase is:

# Phase 14B — Wide Suite Failure Cleanup

## Goal

Fix the 20 remaining full-suite failures exposed after the test runtime memory issue was resolved.

This is a bug-fix and regression cleanup phase.

Do not add new features.

Do not rewrite modules.

Do not skip or hide failing tests unless a test is proven obsolete and the report explains why.

---

# Remaining Failure Groups

The Phase 14A report listed these remaining failures:

```text
ActivityLogContextTest
AsyncPageBehaviorTest
AuthTest
BillingEnhancementsTest
ConsultationClinicalSectionsTest
InertiaBridgeLeakGuardTest
LegacyInertiaBridgeTest
PatientManagementTest
PatientMergeTest
Stage2NeedsReviewLogTest
WorkflowJsonResponsesTest
```

Treat them in priority order.

---

# Important Rules

Do not change personalised consultation workspace behavior unless a failure directly proves a regression.

Do not weaken security, audit, masking, billing, or patient validation behavior just to satisfy tests.

Do not remove route/middleware/permission protection.

Do not skip tests.

Do not use broad snapshots as a shortcut.

Fix root causes.

Where a test expectation is genuinely outdated because the intended behavior changed, update the test with a clear explanation in the report.

---

# Required Work

## 1. Reproduce Failures Individually

Run each failing test class/file individually first.

Suggested commands:

```bash
php artisan test tests/Feature/ActivityLogContextTest.php
php artisan test tests/Feature/AsyncPageBehaviorTest.php
php artisan test tests/Feature/AuthTest.php
php artisan test tests/Feature/BillingEnhancementsTest.php
php artisan test tests/Feature/Consultations/ConsultationClinicalSectionsTest.php
php artisan test tests/Feature/InertiaBridgeLeakGuardTest.php
php artisan test tests/Feature/LegacyInertiaBridgeTest.php
php artisan test tests/Feature/PatientManagementTest.php
php artisan test tests/Feature/PatientMergeTest.php
php artisan test tests/Feature/Stage2NeedsReviewLogTest.php
php artisan test tests/Feature/WorkflowJsonResponsesTest.php
```

If file paths differ, locate them with `rg`.

Capture exact failing assertions/errors before fixing.

---

## 2. Fix User-Facing 500 First

### AsyncPageBehaviorTest

Reported issue:

```text
appointment show page crashes when PatientJourneyService::snapshot() receives a null visit
```

Expected fix:

* Inspect appointment show page/controller/view.
* Inspect `PatientJourneyService::snapshot()`.
* If appointment has no visit yet, page should not crash.
* Return a safe empty journey snapshot or hide the journey widget.
* Add/adjust test that appointment show page renders when visit is null.
* Do not fabricate a visit just to avoid null.
* Do not weaken patient journey behavior for real visits.

Acceptance:

```bash
php artisan test tests/Feature/AsyncPageBehaviorTest.php
```

passes.

---

## 3. Fix Navigation/Auth Regression

### AuthTest

Reported issue:

```text
doctor login redirects to admin/my-dashboard instead of doctor.dashboard
```

Expected fix:

* Inspect login redirect logic.
* Inspect role/permission dashboard routing.
* Decide intended behavior:

  * If doctor should go to doctor dashboard, fix redirect logic.
  * If UHMS now intentionally routes all clinical users to `admin/my-dashboard`, update the test only if the new behavior is documented and accepted.
* Prefer preserving role-specific dashboard expectations unless the project already standardized on `admin/my-dashboard`.

Acceptance:

```bash
php artisan test tests/Feature/AuthTest.php
```

passes.

---

## 4. Fix Inertia/Async Bridge Regressions

### InertiaBridgeLeakGuardTest

Reported issue:

```text
several Blade views still use direct location.reload() or window.location.href
```

Expected fix:

* Locate direct uses of:

  * `location.reload()`
  * `window.location.reload()`
  * `window.location.href`
  * inline navigation patterns forbidden by the guard
* Replace with existing project-safe bridge/helper pattern.
* If some location usage is legitimate, update allowlist narrowly with explanation.
* Do not introduce new inline event handlers.

Acceptance:

```bash
php artisan test tests/Feature/InertiaBridgeLeakGuardTest.php
```

passes.

### LegacyInertiaBridgeTest

Reported issue:

```text
complaint, diagnosis, and treatment legacy submissions no longer match expected redirect/session/json behavior
```

Expected fix:

* Inspect legacy submission endpoints.
* Restore expected response behavior:

  * normal form submit redirects with session flash/errors
  * JSON/Ajax submit returns expected JSON payload/status
  * route context is preserved
* Do not break current consultation Ajax flows.
* Add small regression coverage if needed.

Acceptance:

```bash
php artisan test tests/Feature/LegacyInertiaBridgeTest.php
```

passes.

---

## 5. Fix Consultation Clinical Section Regression

### ConsultationClinicalSectionsTest

Reported issue:

```text
expected HOPC section ordering is missing
```

Expected fix:

* Inspect consultation layout ordering after personalised workspace work.
* Ensure general medicine still includes HOPC in the expected order.
* Ensure sidebar/core section markers still expose HOPC where older tests expect it.
* If test expects literal text/key that has been renamed, preserve backward-compatible alias.
* Do not remove personalised section ordering.

Acceptance:

```bash
php artisan test tests/Feature/Consultations/ConsultationClinicalSectionsTest.php
php artisan test tests/Feature/Consultations
```

pass.

---

## 6. Fix Billing View Regression

### BillingEnhancementsTest

Reported issue:

```text
invoice page still renders Payment History
```

Expected fix:

* Inspect intended billing UI from the test.
* If “Payment History” should have been replaced/hidden, update the Blade view.
* If it is still intended to display, update test only if accepted behavior changed and document it.
* Make sure credit notes/write-offs/discount/sponsor behavior is not broken.

Acceptance:

```bash
php artisan test tests/Feature/BillingEnhancementsTest.php
```

passes.

---

## 7. Fix Patient Management Regressions

### PatientManagementTest

Reported issues:

```text
patient create form fields
patient creation flow
insurance registration
doctor summary update assertions
```

Expected fix:

* Inspect patient create/edit/show forms.
* Restore expected form field names/labels/inputs.
* Fix patient creation validation/redirect/session behavior.
* Fix insurance registration flow if field names or relationship changed.
* Fix doctor summary update route or test expectation.
* Do not break current patient registration behavior.

Acceptance:

```bash
php artisan test tests/Feature/PatientManagementTest.php
```

passes.

### PatientMergeTest

Reported issue:

```text
merge index/compare page assertions fail
```

Expected fix:

* Inspect patient merge index/compare views.
* Restore expected content/links/forms.
* Ensure merge pages render without crash.
* Do not change merge semantics unless the current implementation is incorrect.

Acceptance:

```bash
php artisan test tests/Feature/PatientMergeTest.php
```

passes.

---

## 8. Fix Audit/Logging Regressions

### ActivityLogContextTest

Reported issue:

```text
sensitive phone masking expectation changed
```

Expected fix:

* Inspect masking helper/service.
* Confirm intended masking policy.
* For phone numbers, apply consistent masking in activity log context.
* Do not expose full sensitive phone numbers.
* Update test only if the new masking policy is better and documented.

Acceptance:

```bash
php artisan test tests/Feature/ActivityLogContextTest.php
```

passes.

### Stage2NeedsReviewLogTest

Reported issue:

```text
logs audit still reports 2 NEEDS_REVIEW items
```

Expected fix:

* Run the logs audit command/test.
* Inspect the two remaining `NEEDS_REVIEW` log items.
* Either:

  * add proper module/action mapping, or
  * add a justified allowlist entry if genuinely acceptable.
* Do not suppress real audit gaps.

Acceptance:

```bash
php artisan test tests/Feature/Stage2NeedsReviewLogTest.php
```

passes.

---

## 9. Fix Workflow JSON Regression

### WorkflowJsonResponsesTest

Reported issue:

```text
triage assessment page still exposes the unbilled Radiology department
```

Expected fix:

* Inspect triage assessment JSON/page payload.
* Ensure unbilled Radiology department is not exposed where test expects filtered departments.
* Preserve legitimate radiology workflow elsewhere.
* Do not globally hide Radiology.

Acceptance:

```bash
php artisan test tests/Feature/WorkflowJsonResponsesTest.php
```

passes.

---

# 10. Run Focused Regression Matrix

After fixing individual failures, run:

```bash
php artisan test tests/Feature/ActivityLogContextTest.php
php artisan test tests/Feature/AsyncPageBehaviorTest.php
php artisan test tests/Feature/AuthTest.php
php artisan test tests/Feature/BillingEnhancementsTest.php
php artisan test tests/Feature/Consultations/ConsultationClinicalSectionsTest.php
php artisan test tests/Feature/InertiaBridgeLeakGuardTest.php
php artisan test tests/Feature/LegacyInertiaBridgeTest.php
php artisan test tests/Feature/PatientManagementTest.php
php artisan test tests/Feature/PatientMergeTest.php
php artisan test tests/Feature/Stage2NeedsReviewLogTest.php
php artisan test tests/Feature/WorkflowJsonResponsesTest.php
```

Then run:

```bash
php artisan test tests/Feature/Consultations
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan test tests/Feature/System/RouteLoadMemoryTest.php
php artisan route:list
php artisan view:cache
php artisan view:clear
npm run build
```

Then run the wide suite:

```bash
php artisan test
```

---

# 11. Create Report

Create:

```text
docs/UHMS_WIDE_SUITE_FAILURE_CLEANUP_REPORT.md
```

The report must include:

```text
# UHMS Wide Suite Failure Cleanup Report

## Summary
Explain what was fixed.

## Initial Failure Baseline
List the 20 failing tests/classes from Phase 14A.

## Files Added
List new files.

## Files Modified
List modified files.

## Fixes By Failure Group

### ActivityLogContextTest
Cause, fix, result.

### AsyncPageBehaviorTest
Cause, fix, result.

### AuthTest
Cause, fix, result.

### BillingEnhancementsTest
Cause, fix, result.

### ConsultationClinicalSectionsTest
Cause, fix, result.

### InertiaBridgeLeakGuardTest
Cause, fix, result.

### LegacyInertiaBridgeTest
Cause, fix, result.

### PatientManagementTest
Cause, fix, result.

### PatientMergeTest
Cause, fix, result.

### Stage2NeedsReviewLogTest
Cause, fix, result.

### WorkflowJsonResponsesTest
Cause, fix, result.

## Test Results
List focused commands and results.

## Full Suite Result
Include final `php artisan test` result.

## Backward Compatibility
Confirm personalised consultation workspace, billing mapping, admin configuration, patient management, auth, and audit behavior remain stable.

## Known Issues / Follow-up
List any remaining failures honestly.
```

---

# Acceptance Criteria

Phase 14B is complete only when:

* Each previously failing test class has been reproduced and addressed.
* User-facing 500 is fixed.
* Auth redirect behavior is corrected or documented.
* Inertia bridge guard passes.
* Patient management tests pass.
* Patient merge tests pass.
* Audit/masking tests pass.
* Workflow JSON test passes.
* Consultation focused tests still pass.
* Workspace stabilisation still passes.
* Route memory test still passes.
* Route list and view cache pass.
* Frontend build passes.
* `php artisan test` is run and result documented.
* Report is created.

Stop after Phase 14B and upload the report.
