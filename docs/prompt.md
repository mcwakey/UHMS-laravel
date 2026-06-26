# UHMS Department Type UI Expansion — Phase 6.1: Localisation Audit Cleanup & Worktree Hygiene

## Goal

Complete the Phase 6 acceptance criteria by fixing the localisation audit issue and cleaning the worktree before any commit or next UI phase.

Phase 6 is functionally implemented and focused tests are passing, but this gate is still failing:

```text
php scripts/localisation-audit.php
Active runtime candidates: 23
```

The prompt required active runtime candidates to remain 0.

Do not continue to advanced dashboard charts, comparison dashboards, or multi-department switching until this is resolved or clearly classified as a pre-existing baseline issue with documented proof.

---

## 1. Current Known Status

Phase 6 delivered:

```text
DepartmentContextResolver
DepartmentDashboardThemeRegistry
DepartmentDashboardDataService
modern department dashboard Blade layout
dashboard partials
login redirect to admin.my-dashboard for users with department_id
department dashboard localisation keys
focused Phase 6 tests
Phase 6 report
```

Passing checks:

```text
php -l on changed PHP/lang/test files
php artisan view:cache
php artisan test tests\Feature\Departments\DepartmentDashboardUiPhase6Test.php
php artisan test tests\Feature\Departments
php artisan test tests\Feature\DepartmentDashboardTest.php
php scripts\localisation-parity-check.php
php artisan permissions:audit --strict
git diff --check
```

Failing / unresolved:

```text
php scripts\localisation-audit.php reports Active runtime candidates: 23
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md was regenerated
docs/prompt.md already existed in worktree
untracked dashboard inspiration files already existed in worktree
```

---

## 2. First: Inspect the 23 Active Runtime Candidates

Run:

```bash
php scripts/localisation-audit.php
```

Then inspect the regenerated report:

```text
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Find the exact active runtime candidates.

Report them in a table:

```text
file
line
literal text
category
recommended translation key
fix required?
```

Categories:

```text
Phase 6 dashboard Blade literal
Phase 6 service/controller literal
Phase 6 lang key missing
pre-existing runtime literal
false positive
audit script classification issue
```

Do not guess.

Do not mark as baseline unless you prove it by comparing against previous committed state or previous audit report.

---

## 3. Fix Real Runtime Literals

For each true runtime literal:

```text
Move the literal into the correct lang file.
Replace hardcoded text with __('...') or @lang.
Keep EN/FR parity.
Do not put English directly in Blade, controller, service, enum, registry, or component output.
```

Likely files to inspect:

```text
resources/views/admin/dashboards/department/show.blade.php
resources/views/admin/dashboards/department/partials/*.blade.php
app/Services/Department/DepartmentDashboardThemeRegistry.php
app/Services/Department/DepartmentDashboardDataService.php
app/Services/Department/DepartmentContextResolver.php
app/Http/Controllers/Admin/Dashboard/DepartmentDashboardController.php
lang/en/dashboards.php
lang/fr/dashboards.php
lang/en/departments.php
lang/fr/departments.php
lang/en/menu.php
lang/fr/menu.php
```

Suggested key locations:

```text
dashboards.*
departments.dashboard.*
departments.ui.*
departments.metrics.*
menu.*
common.*
```

Do not create duplicate keys if a suitable key already exists.

---

## 4. Fix Missing EN/FR Keys

Run:

```bash
php scripts/localisation-parity-check.php
```

If parity fails after adding keys:

```text
Add matching keys in EN and FR.
Use clear French translations.
Do not leave English text in FR files.
```

---

## 5. Re-run Localisation Audit

Run:

```bash
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
```

Required result:

```text
Active runtime candidates: 0
EN/FR parity OK
```

If active candidates remain, repeat the inspection/fix cycle.

If any candidate is truly a false positive, document:

```text
file
line
reason it is false positive
why it is safe
whether audit script needs adjustment
```

Only adjust the audit script if the classification is clearly wrong and the change is safe.

---

## 6. Worktree Hygiene

Inspect:

```bash
git status --short
git diff --stat
git diff -- docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Handle these carefully:

### docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md

If regenerated because of the audit:

```text
Keep it only if it now reflects the correct final 0 active runtime state.
Otherwise do not commit a failing regenerated audit report.
```

### docs/prompt.md

This was already in the worktree.

Do not stage it unless it is intentionally part of this task.

### Untracked dashboard inspiration files

These were already in the worktree.

Do not stage them unless they are intentionally part of this task.

Report them separately as pre-existing/unrelated worktree items.

---

## 7. Re-run Phase 6 Focused Checks

After localisation cleanup, run:

```bash
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
php artisan permissions:audit --strict
git diff --check
```

Run focused tests:

```bash
php artisan test tests\Feature\Departments\DepartmentDashboardUiPhase6Test.php
php artisan test tests\Feature\Departments
php artisan test tests\Feature\DepartmentDashboardTest.php
```

Do not run the wide full suite.

---

## 8. Update Phase 6 Report

Update:

```text
docs/DEPARTMENT_DASHBOARD_UI_PHASE_6_REPORT.md
```

Add a section:

```text
Phase 6.1 Localisation Cleanup
```

Include:

```text
original active runtime candidates count
files fixed
translation keys added
final active runtime candidates count
parity result
focused tests re-run
worktree notes
```

---

## 9. Acceptance Criteria

Phase 6.1 is complete only when:

```text
localisation audit active runtime candidates = 0
EN/FR parity passes
modern department dashboard still compiles
focused dashboard/department tests still pass
permissions audit is clean
git diff --check is clean
Phase 6 report documents the localisation cleanup
unrelated worktree files are not staged
```

---

## 10. Final Response

Report back:

```text
whether Active runtime candidates is now 0
which files were fixed
which translation keys were added
tests/checks run
whether docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md was kept or reverted
whether docs/prompt.md remains untouched
whether untracked inspiration files remain untouched
whether Phase 6 is now fully accepted
```
