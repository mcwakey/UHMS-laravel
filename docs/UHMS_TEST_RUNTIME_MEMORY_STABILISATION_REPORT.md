# UHMS Test Runtime Memory Stabilisation Report

Date: 2026-07-07

Phase: 14A - Test Runtime Memory Stabilisation and Wide Suite Recovery

## Summary

The wide Laravel test suite can now execute past the previous `128M` memory exhaustion point.

The failure was not caused by a clinical workflow change or an obvious heavy route-registration query. The local CLI PHP runtime defaults to `128M`, and the `artisan test`/PHPUnit test process needed an explicit PHPUnit memory budget so the spawned test runner consistently used the higher limit.

Applied fix:

- Added `<ini name="memory_limit" value="512M"/>` to `phpunit.xml`.
- Added `tests/Feature/System/RouteLoadMemoryTest.php` to guard route loading and route-list generation under the test memory budget.

No consultation, billing, patient, routing, or clinical business behavior was changed in this phase.

## Memory Diagnosis

Commands checked:

```bash
php -i | rg '^memory_limit|Loaded Configuration|Scan this dir|Additional .ini'
php -r 'echo ini_get("memory_limit"), PHP_EOL;'
php -d memory_limit=512M -r 'echo ini_get("memory_limit"), PHP_EOL;'
php -d memory_limit=-1 -r 'echo ini_get("memory_limit"), PHP_EOL;'
php artisan about
```

Findings:

- CLI PHP configuration file: `/usr/local/etc/php/8.4/php.ini`
- Additional CLI ini scan: `/usr/local/etc/php/8.4/conf.d/ext-opcache.ini`
- Default CLI `memory_limit`: `128M`
- Direct CLI override works:
  - `php -d memory_limit=512M ...` reports `512M`
  - `php -d memory_limit=-1 ...` reports `-1`
- Application runtime:
  - Laravel `12.56.0`
  - PHP `8.4.5`
  - Environment `local`
  - Database `sqlite`
  - Routes not cached
  - Views cached

Project runner findings:

- `phpunit.xml` previously did not set `memory_limit`.
- No `pest.php` file is present.
- No `.env.testing` file is present.
- `composer.json` already contains a `test:wide` script using `php -d memory_limit=512M vendor/bin/phpunit --colors=never`.
- The normal Composer `test` script still runs `@php artisan test`.

Conclusion:

The safest project-level fix is to declare the PHPUnit memory budget in `phpunit.xml`, because that applies consistently whenever the PHPUnit process runs.

## Route Loading Inspection

The previous memory fatal occurred while loading `routes/web.php`, so route registration was inspected for route-time heavy work.

Search performed:

```bash
rg -n "DB::|Schema::|::query\(|->get\(|->count\(|app\(|resolve\(|new [A-Z].*\(" routes -g '*.php'
```

Findings:

- No database query or schema access was found during route registration.
- No heavy service construction was found at route-registration time.
- The only service resolution in `routes/web.php` is inside request-time route closures:
  - `ProcedureRequestService::procedureDepartments()`
  - `ProcedureRequestService::servicesForDepartment(...)`
- Locale setup is also inside a request-time route closure.

Route count:

- `php artisan route:list` completed successfully.
- The application currently registers more than 1,000 routes.

Conclusion:

`routes/web.php` is large, but no obvious route-registration side effect required a behavioral route refactor during this phase.

## Files Changed

Modified:

- `phpunit.xml`

Added:

- `tests/Feature/System/RouteLoadMemoryTest.php`
- `docs/UHMS_TEST_RUNTIME_MEMORY_STABILISATION_REPORT.md`

## Route Memory Smoke Check

Added `Tests\Feature\System\RouteLoadMemoryTest`.

It verifies:

- PHPUnit is running with `memory_limit=512M`.
- Application routes are loaded.
- Route count remains above 1,000.
- Peak memory remains below a generous `480M` test budget.
- `route:list --json` can be generated.

The memory threshold is intentionally generous so the test catches runtime regressions without becoming brittle during normal route growth.

## Verification Results

Passed:

```bash
php artisan test tests/Feature/System/RouteLoadMemoryTest.php
```

Result:

```text
2 passed, 6 assertions
```

Passed:

```bash
php artisan test tests/Feature/ConsultationWorkspaceStabilisationTest.php
```

Result:

```text
4 passed, 20 assertions
```

Passed:

```bash
php artisan test tests/Feature/Consultations
```

Result:

```text
163 passed, 2541 assertions
```

Passed:

```bash
php artisan route:list
php artisan view:cache && php artisan view:clear
npm run build
```

Full suite:

```bash
php artisan test
```

Result:

```text
20 failed, 1395 passed, 7065 assertions
Duration: 575.70s
```

Important: the full suite now completes without the previous `Allowed memory size of 134217728 bytes exhausted` fatal error. The remaining failures are real application/test assertion failures exposed after the runtime memory issue was removed.

## Remaining Failures Exposed By Wide Suite

The remaining failures are outside the runtime memory stabilisation scope:

- `ActivityLogContextTest`: sensitive phone masking expectation changed.
- `AsyncPageBehaviorTest`: appointment show page crashes when `PatientJourneyService::snapshot()` receives a null visit.
- `AuthTest`: doctor login redirects to `admin/my-dashboard` instead of `doctor.dashboard`.
- `BillingEnhancementsTest`: invoice page still renders `Payment History`.
- `ConsultationClinicalSectionsTest`: expected `HOPC` section ordering is missing.
- `InertiaBridgeLeakGuardTest`: several Blade views still use direct `location.reload()` or `window.location.href`.
- `LegacyInertiaBridgeTest`: complaint, diagnosis, and treatment legacy submissions no longer match expected redirect/session/json behavior.
- `PatientManagementTest`: patient create form fields, creation flow, insurance registration, and doctor summary update assertions fail.
- `PatientMergeTest`: merge index/compare page assertions fail.
- `Stage2NeedsReviewLogTest`: logs audit still reports `2` `NEEDS_REVIEW` items.
- `WorkflowJsonResponsesTest`: triage assessment page still exposes the unbilled `Radiology` department.

## Compatibility Notes

- Production runtime memory was not changed.
- Clinical workflow behavior was not changed.
- Route names, URLs, middleware, permissions, and controllers were not changed.
- No tests were skipped or hidden.
- The wide suite now reaches normal assertion failures instead of terminating on PHP memory exhaustion.

## Recommendation

Treat Phase 14A as complete. The next phase should address the 20 remaining application failures in focused groups, starting with the failures that indicate user-facing 500s and navigation regressions.
