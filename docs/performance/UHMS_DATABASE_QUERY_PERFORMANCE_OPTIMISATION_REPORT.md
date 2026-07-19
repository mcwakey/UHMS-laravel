# UHMS Database Query Performance Optimisation Report

Date: 2026-07-19

## 1. Executive summary

The shared authenticated shell was reduced from an uncapped 12,395-query reproduction to 12 queries. Duplicate queries fell from 12,381 to zero on the representative departments request. Against the supplied Debugbar count, the departments page fell from approximately 441 to 12 queries, a 97.28% reduction.

The fix preserves permission, department, privacy, clinical, billing and audit boundaries. It adds no production SQL logging, no patient-specific cache, no Redis dependency and no migration.

## 2. Original problem

Unrelated authenticated GET requests consistently showed roughly 425-445 queries. Redirecting POST requests were inexpensive, while the resulting GET rebuilt the expensive shared shell. The baseline artifact is `docs/performance/QUERY_PERFORMANCE_BASELINE.md`.

## 3. Root causes

The wildcard view composer was invoked for every nested view. Each invocation recomputed `WorkspaceRouteResolver::viewContext()`, whose workspace checks repeatedly resolved the active department. For Admin and Super Admin users, that path queried all active departments on every call. One normalized SQL pattern therefore occurred 12,380 times in a 12,395-query uncapped request.

Secondary causes were repeated role/permission checks during menu construction, duplicate notification reads, repeated module lookups, and presentation-layer queries in a small set of Blade templates.

The consultation detail follow-up found four additional causes: the route-bound session model was used instead of the eager-loaded instance already present in the session collection; the preview offcanvas rebuilt the same visit/session/summary/lab/procedure graph; specialty favorites queried once per type; and the same clinician was loaded independently for creator, updater, doctor, contributor and assignee relationships.

## 4. Shared shell and request context

- `DepartmentContextSwitcherService` is request-scoped and memoizes available, primary, current and global department context per user.
- Current-department memoization includes the session value, so switching departments inside a request cannot return stale context.
- `WorkspaceRouteResolver::viewContext()` stores its resolved context on the current `Request`; nested view composer calls reuse it.
- `SidebarMenuBuilder` is request-scoped, builds once per user/route/unread-count/locale/active-department key, preloads roles and permissions, and filters definitions in memory.
- `ModuleService` and `NotificationService` use request-level memoization with mutation invalidation.
- Payment timing services that hold request memoization were changed from singleton to scoped bindings to prevent cross-request state.

## 5. AJAX and notification work

The unused Inertia `notifications` shared property was removed after confirming that the legacy shell uses `/admin/notifications/recent` as its canonical source. This avoids fetching latest notifications in the page response immediately before the browser performs the same AJAX fetch. Enabled module flags now come from the memoized `ModuleService` map.

## 6. Blade and N+1 work

- Insurance tiers now use `withCount('patientInsurances')` instead of one count query per tier.
- Purchase-order products are loaded by the controller only for editable orders.
- Invoice organization settings and pending payment-provider transactions are prepared by the controller.
- Consultation investigation fallback departments, referral data and pharmacy fallback department are prepared by the workspace controller.
- The department scaling test verifies that increasing data from 5 to 65 rows does not cause query growth; the warmed larger request used 3 queries.
- Consultation detail now reuses its session, clinical summary, lab and procedure collections for the preview payload.
- A request-scoped consultation user relation loader batches and identity-maps `BelongsTo<User>` relationships without changing their named model relations.
- Specialty favorites are fetched once and grouped in memory; specialty entries are fetched once for both array and grouped workspace representations.
- General specialty fallback resolution no longer synchronizes profile configuration during every GET request.

Some print, PDF and receipt views still call `Setting::getGroup()` once per document. These are bounded single reads, not list-loop N+1 paths, and were left unchanged to keep the batch focused.

## 7. Safe instrumentation

`DatabaseQueryProfiler` records query count, cumulative database time and normalized fingerprints without bindings. `ProfileDatabaseQueries` is active only in local/testing environments and can emit local response headers:

- `X-UHMS-Query-Count`
- `X-UHMS-Query-Unique`
- `X-UHMS-Query-Duplicates`
- `X-UHMS-Database-Time-Ms`
- `X-UHMS-Request-Time-Ms`

Threshold logs contain route, method, path and normalized patterns only. Existing database-exception logging was also hardened to omit SQL exception messages, full URLs, IP addresses and user agents. Lazy-loading detection is local/testing only and logs model/relation metadata without record values or bindings.

## 8. Benchmark results

All after-results below are isolated SQLite feature benchmarks. Browser screenshot counts are shown only where a supplied before value exists; before database/request time was not available.

| Route/workload | Before queries | After total | Unique | Duplicate | DB ms | Request ms | Budget |
|---|---:|---:|---:|---:|---:|---:|---:|
| Departments full | 441 screenshot; 12,395 uncapped | 12 | 12 | 0 | 1.19 | 305.97 | 50 |
| Departments AJAX | 437-439 | 12 | 12 | 0 | not retained | not retained | 25 |
| Users | 441 | 14 | 13 | 1 | 1.11 | 258.25 | 50 |
| Patients AJAX | 445 | 11 | 11 | 0 | 0.94 | 158.46 | 25 |
| Visits AJAX | not captured | 17 | 17 | 0 | 1.31 | 185.26 | 25 |
| Products | not captured | 13 | 13 | 0 | 1.02 | 240.89 | 50 |
| Journey worklist | not captured | 16 | 14 | 2 | 2.29 | 273.43 | 50 |
| Notifications JSON | 9 | 6 | 6 | 0 | 0.59 | 25.25 | 15 |
| Finance invoices | not captured | 16 | 14 | 2 | 1.07 | 62.24 | 50 |
| Stores products | not captured | 14 | 14 | 0 | 1.06 | 61.71 | 50 |
| Consultation workspace | not captured | 11 | 11 | 0 | 1.22 | 242.58 | 90 |
| Consultation detail, populated MariaDB | 280 | 143 | 104 | 39 | 134.94 | 825.19 | materially lower |
| Consultation detail, SQLite budget fixture | not captured | 98 | 84 | 14 | 6.45 | 454.34 | 110 |
| Department dashboard | not captured | 23 | 18 | 5 | 2.61 | 196.06 | 90 |

The uncapped departments harness improved by 99.90% in query count and approximately 98.9% in request time (27.74 seconds to 305.97 milliseconds). Runtime percentages are directional because the browser and feature environments differ.

The populated consultation detail route improved from 280 to 143 queries (48.93%), from 171 to 39 normalized duplicates (77.19%), and from 2,297.07 ms to 825.19 ms request time (64.07%). Its database time improved from 185.74 ms to 134.94 ms (27.35%). No lazy-loading warning remained on the final request.

## 9. Query budgets

Three regression suites now enforce simple-page, AJAX, dashboard and consultation budgets. They assert totals, duplicate ceilings and non-linear scaling. Set `UHMS_QUERY_BENCHMARK_OUTPUT=1` to print route, count, time and memory metrics during focused runs.

Current budgets are 50 for standard authenticated lists, 25 for AJAX lists, 15 for recent notifications, and 90 for department dashboards and the consultation workspace.

The populated consultation-detail fixture has a 110-query ceiling and 20-duplicate ceiling. Adding 30 investigations did not increase its query count, protecting the route from clinical-entry N+1 regressions.

## 10. Cache and invalidation

Request memory is the primary cache and requires no external service. Notification memoization is invalidated after notify, mark-read and mark-all-read operations. Module mutation clears both the application cache key and request map. Menu keys include user, route, unread count, locale and active department. Department switch/clear operations update session state and invalidate the relevant request entries.

The local application currently uses the database cache driver. Redis may reduce cross-request cache-store SQL later, but it is optional and was not introduced as a dependency.

## 11. Index review

No migration or index was added. MariaDB showed 30 departments and existing primary, unique-code, supervisor and escalation indexes. The department pivot already has user/department and primary-status coverage. The hot query was repeated application execution against a tiny table; an index would not address the root cause.

`php artisan db:show --counts` could not run because this MariaDB installation lacks `performance_schema.session_status`; engine/version and indexes were inspected directly with SQL instead.

## 12. Files created

- `config/performance.php`
- `app/Support/DatabaseQueryProfiler.php`
- `app/Services/Consultation/ConsultationUserRelationLoader.php`
- `app/Http/Middleware/ProfileDatabaseQueries.php`
- `tests/Concerns/InteractsWithDatabaseQueryBudgets.php`
- `tests/Feature/Performance/SharedShellQueryBudgetTest.php`
- `tests/Feature/Performance/RepresentativeRouteQueryBudgetTest.php`
- `tests/Feature/Performance/ConsultationDetailQueryBudgetTest.php`
- `docs/performance/QUERY_PERFORMANCE_BASELINE.md`
- `docs/performance/UHMS_DATABASE_QUERY_PERFORMANCE_OPTIMISATION_REPORT.md`

## 13. Configuration and deployment

Four diagnostics flags were added to `.env.example`; all are false there. Defaults in `config/performance.php` permit profiling/detection only in local/testing and middleware checks the runtime environment again. Production should retain `APP_DEBUG=false`, install without development packages, and run the existing config, route, view and event cache commands documented in `docs/DEPLOYMENT.md`.

## 14. Verification

- Performance suites: 15 tests, 71 assertions passed.
- Representative budgets: 10 tests, 48 assertions passed.
- Focused module, security, privacy, audit, notification, billing, finance, stores, menu, Inertia and localization checks passed.
- Department dashboard advanced switching: 7 tests passed after making the request cache session-aware.
- PHP syntax checks, route cache/clear, view cache and localization parity passed.
- Pint checks pass for all newly created PHP files. Existing touched files retain unrelated pre-existing style findings and were not mechanically reformatted.

One focused consultation-hardening group retains 3 legacy failures: it expects a Doctor request to the old `admin.consultations.routes.show` URL to return 200, while existing `records.redirect` middleware intentionally redirects that workspace to the doctor-prefixed route. The optimisation did not alter that middleware or routing rule; the other 10 tests in that rerun passed.

The consultation-detail follow-up produced the same known redirect-only mismatch in older tests: specialty/resolver/summary groups had 29 passes and 4 legacy admin-URL failures; clinical/follow-up groups had 13 passes and 6 legacy admin-URL failures; and the route/session workflow had 10 passes and 2 legacy admin/visit-URL failures. The canonical `doctor.consultations.routes.show` performance and rendering tests pass.

The required wide suite was invoked once through `composer test:wide`; Composer terminated it at its 300-second process timeout after roughly 427 of 1,976 tests. A direct `php -d memory_limit=512M vendor/bin/phpunit --colors=never` retry then ran to process completion, but its final summary was lost when the execution output exceeded the orchestration context and the handle was discarded. It is therefore not represented as a passing full suite. Focused results above are the reproducible regression signal for this change.

## 15. Security and limitations

No permission, department scope, privacy mask, payment policy, clinical transition or audit event was removed. No sensitive model payload is persistently cached. Profiling never stores bindings. Benchmark timings are environment-specific and should be compared by trend, not treated as production latency predictions.

Recommended later work is to reconcile the legacy consultation redirect expectations, add durable CI JUnit output for the long wide suite, and evaluate Redis only if production metrics show database-backed cache traffic is material.
