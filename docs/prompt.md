You are working on the UHMS Laravel codebase.

The personalised consultation workspace hardening phase passed focused consultation, workspace, frontend, and browser smoke checks.

However, the wide test suite could not complete because the local runtime repeatedly hit:

```text
Allowed memory size of 134217728 bytes exhausted
```

while loading `routes/web.php`.

This is now:

# Phase 14A — Test Runtime Memory Stabilisation and Wide Suite Recovery

## Goal

Make the wide Laravel test suite executable again without changing clinical/business behavior.

This is a test/runtime hardening phase, not a feature phase.

---

# Important Rules

Do not rewrite the consultation module.

Do not change clinical workflow behavior.

Do not remove routes.

Do not remove permissions.

Do not hide real test failures.

Do not mark failing tests as skipped unless the failure is proven unrelated and documented.

Do not use this phase to add new features.

The goal is to identify why test runtime memory remains capped at 128 MB and fix the test execution environment or obvious route-loading memory issue.

---

# Current Known Issue

The hardening report recorded:

```text
php artisan test tests/Feature
php -d memory_limit=512M artisan test tests/Feature
php -d memory_limit=-1 artisan test tests/Feature
php artisan test
```

All eventually failed with the same 128 MB memory cap while loading `routes/web.php`.

Focused consultation checks passed:

```text
php artisan test tests/Feature/Consultations
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan route:list
php artisan view:cache && php artisan view:clear
npm run build
npx playwright test tests/consultation-workspace.spec.ts
```

---

# Required Deliverables

## 1. Diagnose PHP Memory Configuration

Inspect:

```text
php.ini used by CLI
artisan runtime memory_limit
Pest/PHPUnit configuration
composer scripts
.env.testing
bootstrap/app.php
tests/TestCase.php
phpunit.xml
pest.php if present
any custom test runner config
```

Find why `php -d memory_limit=512M` and `php -d memory_limit=-1` still behave like 128 MB.

Check and document:

```bash
php -i | grep memory_limit
php -r "echo ini_get('memory_limit').PHP_EOL;"
php -d memory_limit=512M -r "echo ini_get('memory_limit').PHP_EOL;"
php -d memory_limit=-1 -r "echo ini_get('memory_limit').PHP_EOL;"
php artisan about
```

If the memory cap is coming from Xdebug, Sail, Herd, Valet, PHPUnit process isolation, Composer script wrapper, or a local server/runtime wrapper, document it.

---

## 2. Inspect Route Loading Memory Pressure

Since the failure happens while loading `routes/web.php`, inspect:

```text
routes/web.php
route files included from routes/web.php
large inline closures
large route arrays
heavy service instantiation inside route files
config/database access during route registration
model queries during route registration
permission checks executed at route-registration time
controller imports that trigger heavy boot logic
```

Rules:

* Route files should register routes only.
* No database queries should run during route registration.
* No heavy service should be instantiated during route registration.
* No large closures should capture heavy objects.
* Middleware strings/classes are fine.
* Controllers should not be instantiated during route registration.

If you find route-time heavy work, move it into controller/service runtime safely.

Do not change route names or URLs unless absolutely necessary.

---

## 3. Add A Route Load Memory Smoke Check

Add a focused test or command if useful.

Suggested command:

```text
php artisan uhms:route-memory-check
```

or a focused test:

```text
tests/Feature/System/RouteLoadMemoryTest.php
```

The check should:

```text
load application routes
report memory usage
assert route list can be generated
```

Do not make it brittle. Use a generous threshold.

If adding a command is too much, document manual memory measurements instead.

---

## 4. Try Safe Runtime Fixes

Depending on findings, apply the safest fix.

Possible fixes:

### Option A — PHPUnit memory limit config

If PHPUnit/Pest is forcing memory:

```xml
<ini name="memory_limit" value="512M"/>
```

or equivalent in `phpunit.xml`.

### Option B — Test bootstrap memory limit

If project convention allows, add to test bootstrap only:

```php
ini_set('memory_limit', '512M');
```

Do not put this in production runtime unless the project already does so.

### Option C — Composer test script

If Composer script wraps tests, update it to:

```bash
php -d memory_limit=512M artisan test
```

only if it actually works.

### Option D — Remove route-registration heavy work

Move heavy code out of `routes/web.php` into controller/service runtime.

### Option E — Split loaded route files

If `routes/web.php` is structurally huge, split into route files by domain:

```text
routes/admin.php
routes/consultations.php
routes/billing.php
routes/reports.php
```

Then include them safely from `web.php`.

Important: preserve route names, middleware, prefixes, and permissions.

---

## 5. Re-run Test Matrix

After fix, run:

```bash
php artisan test tests/Feature/Consultations
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
php artisan route:list
php artisan view:cache
php artisan view:clear
npm run build
```

Then run:

```bash
php artisan test tests/Feature
```

Then run:

```bash
php artisan test
```

If full suite still fails:

* capture first failure
* distinguish memory failure from real test failure
* do not hide it
* document exact command output

---

## 6. Create Report

Create:

```text
docs/UHMS_TEST_RUNTIME_MEMORY_STABILISATION_REPORT.md
```

Report must include:

```text
# UHMS Test Runtime Memory Stabilisation Report

## Summary
Explain what was diagnosed and fixed.

## Memory Diagnosis
Show CLI memory_limit findings and why 128 MB was still applied.

## Route Loading Findings
Document whether routes/web.php or included route files had heavy route-time work.

## Files Added
List new files.

## Files Modified
List modified files.

## Fix Applied
Explain the exact fix.

## Test Results
List all commands run and results.

## Full Suite Result
State whether php artisan test now completes.
If failures remain, classify:
- new regression
- pre-existing failure
- environment failure

## Backward Compatibility
Confirm no consultation/billing/admin behavior was changed.

## Known Issues / Follow-up
List remaining test/runtime concerns.
```

---

# Acceptance Criteria

This phase is complete only when:

* The 128 MB cap cause is diagnosed.
* A safe runtime/config/route-loading fix is applied, or the blocker is clearly proven external.
* Focused consultation suite still passes.
* Workspace stabilisation still passes.
* Route list and view cache pass.
* Frontend build passes if frontend files are touched.
* `php artisan test tests/Feature` is attempted and result documented.
* `php artisan test` is attempted and result documented.
* Report is created.

Stop after this phase. Do not implement reporting/dashboard integration yet.
