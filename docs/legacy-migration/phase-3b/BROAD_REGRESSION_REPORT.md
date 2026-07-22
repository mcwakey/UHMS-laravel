# Phase 3B broad regression report

Assessment date: 2026-07-22  
Scope: one complete repository PHPUnit run after the three specialist reviews passed.

## Authoritative run

The repository suite completed under the retained test-only `phpunit.phase3b-broad.xml` configuration, which changes only `memory_limit` from `512M` to `-1`. This was necessary because the unchanged suite exhausted 512 MB while repeatedly loading `routes/web.php`. Its exact SHA-256 and command hash are pinned in `BROAD_REGRESSION_MANIFEST.json`.

| Measure | Result |
|---|---:|
| Tests | 2,367 |
| Assertions | 31,756 |
| Errors | 3 |
| Failures | 44 |
| Skipped | 3 |
| Peak memory | 718 MB |
| Duration | 22 minutes 10 seconds |

The machine-readable JUnit result was generated outside source control for local classification. The mandatory privacy scan then detected eight unallowlisted identifier/credential-pattern matches in the verbose synthetic-test output. The artifact was fail-closed purged. `BROAD_REGRESSION_MANIFEST.json` retains the privacy-safe command/configuration hashes, all 28 affected file hashes, per-file broad and isolated counts, classification rationale and aggregate result without failure output or record values.

## Failure classification

- One failure, `RouteLoadMemoryTest`, is an expected artifact of the test-only `memory_limit=-1` override. The same test passes under the repository's normal 512 MB configuration.
- The other 46 error/failure testcases span accounting module gating, admission/consultation presentation and routing, localisation parity, NHIS claims, error-page routing and visit JSON routing.
- All 28 affected files were rerun in fresh isolated PHP processes. Forty-four distinct application methods remained failing; duplicate discovery of two workflow methods accounts for 46 broad non-harness cases. The deliberate memory-limit testcase passed under its normal configuration.
- No failing stack trace or assertion refers to a Phase 3B namespace, migration table, migration command, isolation denial, protected-store boundary, recovery path, allocator or schema installer.
- The implicated views, controllers, routes, language files and test files are unchanged from repository `HEAD`. Where an operational service contains a Phase 3B first-statement guard, the failures are downstream value/rendering assertions rather than `RuntimeIsolationException` or guard denial. Normal operational mode is independently covered by the runtime-isolation suite.
- The post-review-correction focused current-tree foundation suite passes 320 tests and 1,676 assertions. The evidence suite passes 58 tests and 20,350 assertions.

Conclusion: the broad suite is not globally green because of reproducible application-suite debt, but **no Phase 3B regression was identified**. This conclusion does not waive those application failures and does not authorize an importer or pilot.

## Safety statement

The run used SQLite/in-memory and local synthetic test data. It did not access Classic `uuhms`, a renewed shared or production database, production-like patient data, Cohort B or any importer. The raw JUnit output is prohibited from retention because it failed the privacy detector even though its values originated from synthetic fixtures.
