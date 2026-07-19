You are working directly inside my UHMS Laravel project.

Your task is to perform a comprehensive database-query and page-performance optimisation across the application.

This is not merely an analysis or recommendation task. Inspect the existing codebase, establish measurable baselines, identify the real causes of the excessive queries, implement the fixes, add regression protection, and produce a final implementation report.

======================================================================
PROBLEM
======================================================================

Almost every authenticated page in the UHMS application is executing an excessive number of database queries.

A typical example is:

- GET /admin/departments?page=1: approximately 441 queries
- GET /admin/departments AJAX request: approximately 437–439 queries
- POST /admin/departments: only a few queries, but the redirected GET executes hundreds
- Similar high query counts are expected across many other pages

The almost identical query counts on unrelated GET requests strongly suggest that much of the problem may come from shared application infrastructure, including:

- Shared layouts
- Sidebar/menu construction
- Authenticated-user context
- Current department resolution
- Roles and permissions
- Global view composers
- Blade components
- Header counters
- Dashboard widgets
- Application settings
- Patient privacy services
- Journey intelligence widgets
- Notification counters
- Department capabilities
- Specialty profile resolution
- Repeated AJAX rendering
- Lazy-loaded relationships
- Queries executed inside model accessors, policies, Blade templates or loops

Do not assume that the departments controller is the only or primary source of the problem.

======================================================================
PRIMARY OBJECTIVE
======================================================================

Reduce database queries and request time across UHMS while preserving all existing functional, permission, localisation, privacy, billing, clinical and audit behaviour.

The optimisation must improve the shared application shell first so that the benefits apply across the whole system.

Then optimise representative high-traffic pages and introduce permanent query-budget regression protection.

======================================================================
IMPORTANT PROJECT CONTEXT
======================================================================

UHMS is a Laravel/PHP hospital-management application with many modules, including:

- Authentication
- Users, roles and permissions
- Departments
- Patients
- Visits
- Triage
- Consultation
- Specialist consultation workspaces
- Investigations
- Radiology
- Procedures
- Theatre
- Treatment
- Nursing
- Inpatient
- Maternity
- Pharmacy
- Blood bank
- Mortuary
- Ambulance
- Records
- Finance and billing
- Stores and inventory
- Support
- Administrative
- Department dashboards
- Patient Journey Intelligence
- Patient privacy and sensitive-field masking
- Reporting
- Audit logging
- English and French localisation

Department types currently include:

- consultation
- emergency
- investigation
- radiology
- procedure
- theatre
- treatment
- nursing
- pharmacy
- inpatient
- maternity
- blood_bank
- mortuary
- ambulance
- records
- finance
- stores
- support
- administrative

The application includes:

- Department-aware menus
- Department-specific dashboards
- Multi-department user assignment
- Active department context switching
- Department capabilities
- Department metrics
- Dashboard widgets and alerts
- Journey Intelligence worklists and analytics
- Specialist consultation profiles
- Patient privacy masking
- Billing/payment policies
- Activity logging
- English and French localisation

Treat these systems as sensitive. Optimisation must not bypass them.

======================================================================
NON-NEGOTIABLE RULES
======================================================================

1. Do not change clinical, billing, permission, privacy or audit behaviour merely to reduce queries.

2. Do not remove permission checks.

3. Do not bypass department scoping.

4. Do not bypass patient privacy masking.

5. Do not expose sensitive patient or billing information in logs.

6. Do not remove ActivityLog or other required audit events.

7. Do not replace correct dynamic behaviour with stale global data.

8. Do not introduce persistent cross-user or cross-department state in singleton services.

9. Request-specific services must be request-scoped or otherwise safely isolated.

10. Do not cache sensitive patient-specific or visit-specific payloads unless the existing architecture explicitly supports secure and correctly scoped caching.

11. Do not blindly add indexes before identifying the actual query patterns.

12. Do not assume Redis is available. Inspect the current cache configuration first.

13. Do not introduce Redis as a hard runtime dependency unless the project already uses it or a safe fallback is provided.

14. Do not enable full SQL logging in production.

15. Query logging must avoid exposing patient names, phone numbers, emails, diagnoses, notes, prescriptions, identifiers, payment references or other sensitive bindings.

16. Do not run a broad/full application test suite after every phase.

17. During implementation phases, run only focused checks that are necessary for:
   - Migrations
   - Route compilation
   - Blade/view compilation
   - Localisation parity
   - Audit integrity
   - Targeted feature behaviour
   - Query-budget verification
   - Preventing obvious regressions

18. Run one wide/full test suite only after all implementation phases in this optimisation batch are complete.

19. Do not stop after producing an investigation report. Implement the safe and justified fixes.

20. Do not perform unrelated refactoring.

======================================================================
SUCCESS CRITERIA
======================================================================

The optimisation must produce measurable improvements.

Use these as initial performance-budget targets, but first establish the current baseline and adjust only when there is a clear technical justification:

- Simple authenticated CRUD list page:
  target at or below 40–50 queries

- CRUD list with shared layout, permissions and department context:
  target at or below 50 queries

- Standard AJAX table/filter request:
  target at or below 15–25 queries

- Complex operational dashboard:
  target at or below 70–90 queries

- Consultation workspace:
  target should be materially lower than baseline and free from obvious N+1 behaviour

- Repeated rendering of the same page with more rows:
  query count should remain approximately constant rather than growing linearly with the number of records

The optimisation is successful only when:

- The query count drops significantly
- Duplicate queries are substantially reduced
- Database execution time improves
- No major behaviour is changed
- Query count does not grow linearly with list size
- Shared layout queries are resolved once per request
- AJAX responses do not unnecessarily rebuild the full page shell
- Query-budget regression tests are added
- Final benchmarks are documented

======================================================================
PHASE 1 — CODEBASE DISCOVERY AND BASELINE
======================================================================

Begin by inspecting the existing architecture.

Identify:

- Laravel version
- PHP version
- Database engine and version
- Cache driver
- Session driver
- Queue driver
- Permission implementation
- Whether Spatie Laravel Permission or a custom permission system is used
- Existing Debugbar, Telescope or query profiling tools
- Existing service providers
- Existing view composers
- Existing request-context services
- Existing department-context services
- Existing menu services
- Existing dashboard registries
- Existing widget registries
- Existing query-count tests
- Existing caching abstractions
- Existing cache invalidation events
- Existing production deployment optimisation commands

Search the project for likely query sources, including:

- resources/views
- app/View
- app/View/Components
- app/Providers
- app/Services
- app/Http/Middleware
- app/Policies
- app/Models
- app/Observers
- app/Support
- app/Repositories
- app/Http/Controllers
- menu registries
- dashboard registries
- navigation builders
- department resolvers
- permission helpers
- global helpers

Search specifically for database access patterns in presentation and shared-layer code:

- Model::query()
- Model::where()
- ->find()
- ->first()
- ->get()
- ->count()
- ->exists()
- ->pluck()
- ->value()
- ->sum()
- ->load()
- ->loadMissing()
- relationship method calls such as ->users()
- relationship property access such as ->users
- auth()->user()->roles()
- auth()->user()->permissions()
- auth()->user()->departments()
- Department::find(...)
- settings database reads
- notification count queries
- repeated session-based active department resolution

Inspect model accessors and appended attributes for database queries.

Inspect policies for database queries that repeat for every row or menu item.

Inspect Blade components for queries performed inside render(), constructor methods or computed properties.

Inspect global and wildcard view composers.

Inspect middleware executed on every authenticated route.

Inspect the JavaScript for duplicate initial requests, repeated pagination requests, multiple filter initialisations or AJAX requests that immediately reload data already rendered server-side.

Create a baseline benchmark for representative routes. Locate the actual route names and URLs in the project instead of assuming them.

At minimum benchmark:

1. Department list
2. Patient list
3. Visit list
4. Department dashboard
5. Consultation workspace
6. Pharmacy page
7. Billing or invoice page
8. Stores/inventory page
9. Journey worklist or analytics page
10. One AJAX list/filter endpoint

For each route record:

- Route name
- URL
- HTTP method
- Response status
- Total query count
- Unique query count
- Duplicate query count
- Total database time
- Total request time where measurable
- Peak memory where measurable
- Models or relationships repeatedly loaded
- Most frequently repeated SQL query patterns
- Source files or call sites causing repeated queries

Use safe local/testing instrumentation.

Do not persist raw sensitive bindings.

Produce an internal baseline artifact such as:

docs/performance/QUERY_PERFORMANCE_BASELINE.md

or another location consistent with the existing project documentation structure.

Acceptance criteria for Phase 1:

- Representative routes are benchmarked
- Duplicate queries are grouped by SQL pattern
- Shared-shell query sources are distinguished from page-specific sources
- At least the top ten repeated query patterns have identified or narrowed call sites
- No production behaviour has changed yet
- No sensitive bindings are written to logs

======================================================================
PHASE 2 — SAFE QUERY INSTRUMENTATION
======================================================================

Add safe development/testing instrumentation where appropriate.

Implement or extend support for:

- Counting total queries per request
- Counting duplicate SQL patterns
- Measuring cumulative database time
- Capturing slow-query metadata
- Recording route name and request context
- Excluding or redacting bindings
- Local/testing-only output
- Optional warning thresholds

Use Laravel-supported mechanisms where compatible with the installed version, such as:

- DB::listen(...)
- DB::whenQueryingForLongerThan(...)
- QueryExecuted events
- Test-only query listeners

Do not log full sensitive SQL bindings.

Prefer normalised query fingerprints rather than raw SQL values.

Example information that may safely be recorded:

- Route name
- Query duration
- Normalised SQL pattern
- Query count
- Duplicate count
- Database connection name
- Source call site if safely obtainable

Add a configuration file only if justified, for example:

config/performance.php

Possible settings:

- instrumentation enabled
- slow query threshold
- cumulative query threshold
- route query budget warnings
- duplicate-query threshold
- environments in which instrumentation is active

Do not add unnecessary configuration if the project already has an appropriate performance configuration structure.

Acceptance criteria for Phase 2:

- Local/testing query profiling is reproducible
- Production logging remains safe
- Query metrics can be gathered without Debugbar
- Instrumentation does not materially affect normal production requests
- Existing application behaviour remains unchanged

======================================================================
PHASE 3 — LAZY-LOADING DETECTION
======================================================================

Enable Eloquent lazy-loading detection in development and testing, using the correct mechanism for the installed Laravel version.

Do not enable exception-throwing lazy-loading prevention in production unless the existing project explicitly supports that policy.

Provide a controlled handler that identifies:

- Model class
- Relationship name
- Route name
- Request URL or route context
- Relevant call site where possible

Use focused tests and representative route requests to identify lazy-loading violations.

Do not “fix” violations by indiscriminately eager-loading every relationship. Load only relationships actually required by the use case.

Acceptance criteria for Phase 3:

- Lazy-loading violations are detected in local/testing
- Representative pages have no obvious list-loop N+1 violations
- Eager loading is deliberate and column-restricted where appropriate
- Production behaviour remains safe

======================================================================
PHASE 4 — REQUEST-SCOPED UHMS CONTEXT
======================================================================

Investigate whether the following information is repeatedly queried within the same request:

- Authenticated user
- User roles
- Direct permissions
- Role permissions
- Assigned departments
- Primary department
- Active department
- Current department type
- Facility or branch
- Department menu profile
- Department capabilities
- Dashboard profile
- Privacy capabilities
- Locale
- Relevant global settings

If repeated resolution exists, introduce or improve one request-scoped context service.

Use a project-appropriate name, such as:

- UhmsRequestContext
- AuthenticatedUserContext
- CurrentDepartmentContext
- ApplicationRequestContext

Do not create duplicate context abstractions if an existing service can be improved.

The request context should:

- Resolve the authenticated user once
- Use loadMissing() or a deliberate equivalent
- Resolve department assignments once
- Resolve the active department once
- Validate that the active department belongs to the user
- Fall back safely to the primary department or another existing project rule
- Resolve role and permission information once
- Expose immutable or controlled access to commonly needed context
- Avoid repeated database access from controllers, middleware, menus and views
- Be request-scoped
- Never persist one user’s context into another request

Where supported, bind it using a scoped container binding.

Avoid calling auth()->user()->relation()->query() repeatedly throughout the request.

Refactor shared consumers to use the request context:

- Menu services
- Sidebar
- Header
- Department switcher
- Dashboard services
- Policies where appropriate
- View composers
- Blade components
- Shared layouts
- Notification counters
- Privacy capability checks

Do not force all domain services to depend on a giant context object. Use it for genuinely request-shared information, not as a universal service locator.

Acceptance criteria for Phase 4:

- Authenticated-user context is not reloaded repeatedly
- Active department is not queried repeatedly
- Assigned departments are not queried repeatedly
- Roles and permissions are resolved once per request or through the permission package’s supported cache
- Switching department still works
- Invalid or stale department session values are handled safely
- Multi-department behaviour remains intact
- Department scoping remains enforced
- Relevant tests pass

======================================================================
PHASE 5 — SHARED LAYOUT, MENU AND VIEW-COMPOSER OPTIMISATION
======================================================================

Treat the shared page shell as the highest-priority optimisation target.

Inspect and optimise:

- Main authenticated layout
- Sidebar
- Top navigation
- User profile menu
- Department switcher
- Breadcrumbs
- Header metrics
- Notification badges
- Journey alert badges
- Billing alerts
- Privacy indicators
- Dashboard shortcuts
- Menu capability checks
- Global settings
- Locale switcher
- Shared Blade components
- Wildcard view composers

The menu definitions should primarily be configuration or registry data.

Do not query the database separately for every menu item.

The menu builder should:

1. Receive the already-resolved request context
2. Obtain permission names from the already-loaded permission context
3. Obtain the active department type from memory
4. Filter menu definitions in memory
5. Build the menu once per request
6. Share the final menu with the layout
7. Avoid rebuilding it in nested components

If there are multiple calls to permission methods that hit the database, optimise them using the permission package’s intended caching mechanism or the project’s existing permission cache.

Do not implement a custom permission cache that conflicts with the package’s own cache.

Inspect whether helpers such as these cause repeated queries:

- can(...)
- hasRole(...)
- hasPermissionTo(...)
- getAllPermissions()
- department capability checks
- feature availability checks
- route menu visibility checks

Where possible, prepare sets/maps in memory for repeated membership checks.

Example conceptual approach:

- Permission names keyed in memory
- Department capabilities keyed in memory
- Menu items filtered without SQL
- Menu result built only once

Cache stable menu output only where safe and useful.

Any cached menu key must account for all relevant dimensions, such as:

- User or permission signature
- Active department
- Locale
- Role or permission version
- Feature configuration version

Do not serve one user’s menu to another user.

Implement reliable invalidation when:

- Roles change
- Permissions change
- Department assignments change
- Active department changes
- Department menu configuration changes
- Feature configuration changes

Prefer request-level memoisation before distributed caching. A value used repeatedly in the same request should first be held in memory.

Acceptance criteria for Phase 5:

- Shared shell no longer performs hundreds of queries
- Menu construction does not issue one query per menu item
- The menu is built once per request
- Active department switching immediately produces the correct menu
- Permission changes do not leave permanently stale menus
- English and French menu behaviour remains correct
- Shared pages show a large query-count reduction

======================================================================
PHASE 6 — REMOVE DATABASE QUERIES FROM PRESENTATION CODE
======================================================================

Remove direct database access from Blade templates wherever reasonably possible.

Blade should render prepared data rather than execute business queries.

Inspect:

- Blade templates
- Blade components
- Component constructors
- Component render methods
- View composers
- Inline @php blocks
- Custom Blade directives
- Model accessors used by views
- Appended model attributes

Replace patterns such as:

- $model->relation()->count()
- $model->relation()->exists()
- $model->relation()->first()
- Model::where(...)->exists()
- SettingsModel::where(...)->value(...)
- auth()->user()->departments()->...
- Queries inside @foreach loops

Use appropriate techniques:

- with(...)
- loadMissing(...)
- withCount(...)
- loadCount(...)
- withExists(...)
- selectSub(...)
- grouped aggregates
- prepared DTOs
- view models
- collection maps
- request-context values
- registry/configuration data

Restrict eager-loaded columns while preserving relationship keys.

Do not eager-load large sensitive relationships when only a count or existence flag is required.

Acceptance criteria for Phase 6:

- No obvious query calls remain in high-traffic Blade templates
- Relationship counts use aggregate loading where appropriate
- Relationship existence uses withExists or equivalent where appropriate
- Accessors do not silently issue repeated queries
- List query counts remain approximately stable as page size increases

======================================================================
PHASE 7 — DEPARTMENT LIST AND CORE CRUD OPTIMISATION
======================================================================

Optimise the department list page and its related AJAX endpoint as the first concrete page-level implementation.

Inspect the real table columns and actions.

Load only the information actually displayed, including appropriate:

- Selected department columns
- Department head relationship
- User count
- Service count
- Active/inactive status
- Department type
- Any capability indicators
- Any locations or branches actually displayed

Use:

- select(...)
- with(...)
- withCount(...)
- withExists(...)
- appropriate scopes
- pagination
- withQueryString()

Avoid:

- Loading full user or service collections just to count them
- Relationship queries inside each table row
- Per-row policy queries
- Per-row settings queries
- Per-row department capability queries
- Per-row activity-log queries unless explicitly required

Inspect bulk actions, action menus and policy checks.

Where each row performs the same authorisation pattern, determine whether it can be evaluated safely from already-loaded context without changing authorisation semantics.

Optimise at least these additional representative pages:

- Patient list
- Visit list
- Pharmacy list/workspace
- Billing list/workspace
- Stores/inventory list
- Journey worklist
- Consultation workspace where safe

Do not rewrite the full domain modules. Focus on demonstrated query problems.

Acceptance criteria for Phase 7:

- Department list meets or approaches the defined query budget
- Query count does not grow linearly with the number of rows
- Pagination, search and filters continue working
- Department CRUD behaviour remains unchanged
- Permissions remain correct
- Audit logging remains correct
- AJAX responses remain correct
- Additional representative pages show meaningful improvements

======================================================================
PHASE 8 — AJAX, LIVE SEARCH AND DUPLICATE REQUEST CLEANUP
======================================================================

Inspect all relevant frontend code for duplicate requests.

Determine whether pages currently:

- Render the initial table server-side
- Immediately request the same table through AJAX
- Trigger the same request from multiple initialisation hooks
- Trigger searches on every keystroke without debounce
- Leave previous requests running after a newer request starts
- Reload the full layout for table-only changes
- Fire duplicate pagination requests
- Initialise the same component more than once
- Send both normal and AJAX requests for the same user action

For AJAX table/filter requests:

- Return only the required partial or structured JSON
- Do not render the full authenticated layout
- Do not rebuild the sidebar, header, menu and shared widgets
- Keep permissions and department scoping intact
- Preserve localisation
- Preserve pagination metadata
- Preserve validation error behaviour

Implement suitable frontend protections where applicable:

- Debounce search input
- Abort superseded requests
- Prevent duplicate component initialisation
- Use one initial data source
- Avoid immediate duplicate fetch after server-rendered HTML
- Prevent double submission
- Preserve browser history where currently supported

Inspect the Network tab behaviour through existing browser/E2E tooling if available.

Acceptance criteria for Phase 8:

- Initial page load does not immediately duplicate the same data request without justification
- AJAX table refreshes do not rebuild the full application shell
- Superseded searches are cancelled or ignored
- Search and pagination behaviour remains correct
- AJAX query budgets are met or materially improved

======================================================================
PHASE 9 — DASHBOARD AND KPI QUERY CONSOLIDATION
======================================================================

Inspect department dashboards, management dashboards, journey widgets and other metric-heavy pages.

Identify patterns such as:

- One COUNT query per status
- One SUM query per widget
- One query per department
- One query per chart point
- One query per alert type
- All widget services being calculated even when widgets are hidden
- The same base dataset being queried independently by several widgets

Consolidate safe metric queries using:

- Conditional aggregation
- GROUP BY
- selectRaw(...)
- subqueries
- CTEs where supported and appropriate
- Existing aggregate snapshot tables
- Existing analytics query services
- Shared query objects
- Batch loading
- Prepared metric DTOs

Do not combine unrelated queries into unreadable or dangerous SQL only to reduce the query count.

Optimise for both:

- Query count
- Total database time

A single extremely expensive query is not automatically better than several efficient queries.

Ensure department_id, facility, date range and permission scoping remain correct.

Only calculate widgets that are actually visible for the current department/user.

Where safe:

- Resolve visible widget keys first
- Execute only those widget services
- Reuse shared aggregate results between widgets
- Lazy-load unusually expensive widgets through dedicated endpoints

Do not make clinical or operational dashboards stale without a documented and acceptable cache policy.

Acceptance criteria for Phase 9:

- Hidden widgets are not calculated unnecessarily
- Repeated status/count queries are consolidated
- Dashboard queries remain department-scoped
- Existing metrics continue returning equivalent values
- Query and request time improve materially

======================================================================
PHASE 10 — CACHING STRATEGY
======================================================================

After eliminating request-level duplication and N+1 queries, evaluate caching.

Do not use caching to hide inefficient code that should first be corrected.

Classify data into:

A. Safe stable/reference data

Examples:

- Department-type definitions
- Menu definitions
- Static capability mappings
- Service categories
- Specialty profile definitions
- Application configuration
- Non-sensitive lookup tables

B. Permission/user-derived data

Examples:

- Final visible menu
- User capability set
- Department assignment summary

These require strongly scoped keys and reliable invalidation.

C. Operational data

Examples:

- Dashboard counts
- Journey metrics
- Billing metrics
- Stock metrics

Use short-lived caching only if current behaviour allows it and staleness is acceptable.

D. Sensitive/request-specific data

Examples:

- Patient details
- Consultation notes
- Diagnoses
- Prescriptions
- Billing details
- Private contact details

Do not add broad caching for these without an existing secure architecture and explicit need.

Inspect the current cache driver.

If the application uses the database cache driver, avoid replacing database queries with hundreds of database-backed cache lookups.

Prefer:

1. Request-level in-memory memoisation
2. Existing permission-package cache
3. Existing application cache abstraction
4. Redis or another configured fast cache where available
5. Safe fallback to the configured cache store

Use tagged caches only if the active driver supports them.

Implement invalidation through existing events, observers or service boundaries where appropriate.

Acceptance criteria for Phase 10:

- Caching is applied only after structural query fixes
- Cache keys are correctly scoped
- Cache invalidation is implemented
- No cross-user or cross-department leakage is possible
- Database cache is not abused for high-frequency micro-lookups
- Application works when optional Redis is unavailable unless Redis was already mandatory

======================================================================
PHASE 11 — DATABASE INDEX REVIEW
======================================================================

After duplicate-query and N+1 cleanup, analyse the remaining slow queries.

Use the project’s supported database tools and EXPLAIN output.

Review:

- WHERE clauses
- JOIN columns
- ORDER BY columns
- GROUP BY columns
- Department-scoping columns
- Facility-scoping columns
- Status/date combinations
- Foreign keys
- Pivot tables
- Common search fields
- Pagination order columns

Potential index candidates may include combinations such as:

- department_id + status + created_at
- facility_id + department_id + status
- visit_id + status
- patient_id + created_at
- user_id + department_id
- department_id + type
- invoice_id + status
- admission_id + status
- assigned_user_id + status
- journey stage + department + status

These are only examples. Add indexes based on actual queries.

Check existing indexes before creating new ones.

Avoid:

- Duplicate indexes
- Redundant indexes
- Excessively wide indexes
- Indexes that significantly harm write performance without measurable benefit
- MySQL identifier names that exceed the database identifier-length limit

Use explicit short index names where necessary because this project has previously encountered MySQL identifier-length errors.

Any new migration must:

- Use short explicit names
- Be reversible
- Avoid destructive changes
- Be compatible with the project database
- Preserve existing data

Acceptance criteria for Phase 11:

- Remaining slow queries have been reviewed with EXPLAIN
- New indexes are justified by real query patterns
- No duplicate or redundant indexes are introduced
- Migration identifiers remain within MySQL limits
- Focused migration and query tests pass

======================================================================
PHASE 12 — QUERY-BUDGET TEST INFRASTRUCTURE
======================================================================

Add permanent query-count regression protection.

Do not rely only on Laravel Debugbar.

Create a reusable test helper or trait that can assert a maximum number of queries rather than only an exact brittle count.

The helper should support:

- Starting query capture after test setup
- Ignoring setup/factory queries
- Counting queries triggered by the target HTTP request
- Optional ignoring of known framework/session queries where justified
- Reporting duplicate query patterns on failure
- Configurable maximum count
- Capturing total database time where practical
- Safe redaction of bindings

Possible API:

$this->assertMaxDatabaseQueries(
    maximum: 45,
    callback: fn () => $this->get(...)
);

Use the existing Laravel query assertion helper if it supports the required behaviour in the installed version. Otherwise implement a project-local helper.

Add query-budget tests for representative routes:

- Department list
- Patient list
- Visit list
- Department dashboard
- One AJAX table endpoint
- Consultation workspace
- Journey worklist or analytics
- Billing page
- Pharmacy page

Also add an N+1 scaling test where useful:

- Create a small dataset
- Measure queries
- Create a much larger dataset
- Measure again
- Assert that query count remains approximately constant or increases only by a small fixed amount

Avoid exact counts that are unnecessarily fragile.

Document why each budget was selected.

Acceptance criteria for Phase 12:

- Query-budget tests fail when major query regressions are introduced
- Test setup queries are excluded
- At least one scaling/N+1 regression test exists
- Tests provide useful failure diagnostics
- Tests do not expose sensitive bindings

======================================================================
PHASE 13 — PRODUCTION DEPLOYMENT OPTIMISATION
======================================================================

Inspect the current deployment process.

Ensure production deployments safely use the Laravel optimisation commands supported by the installed version, such as:

- Configuration cache
- Route cache
- View cache
- Event cache where applicable
- Composer optimised autoloading

Do not run commands that are incompatible with the project.

Ensure:

- APP_DEBUG is false in production
- Debugbar is disabled in production
- Telescope access is restricted if installed
- Query profiling is disabled or reduced in production
- OPcache recommendations are documented if applicable
- Queue workers are restarted after deployment where required
- Cache invalidation is performed safely
- No secrets or environment values are committed

Do not claim that configuration or route caching reduces database-query count directly. Treat deployment optimisation as a complementary improvement.

Acceptance criteria for Phase 13:

- Production debug tooling cannot accidentally expose sensitive information
- Deployment commands are documented
- Existing deployment remains compatible
- Cache invalidation and queue restart requirements are clear

======================================================================
PHASE 14 — FINAL VERIFICATION
======================================================================

After all implementation phases are complete, perform final verification.

Run:

1. Focused performance/query-budget tests
2. Relevant department/menu/context tests
3. Permission tests
4. Department-switching tests
5. Patient privacy tests affected by shared context
6. Audit-log tests affected by optimised services
7. Relevant AJAX tests
8. Relevant dashboard metric tests
9. Route compilation check
10. Blade/view compilation check
11. English/French localisation parity check
12. Migration check if migrations were added
13. One broad/full application test suite

The broad/full suite should be run once at the end of this implementation batch.

If the full suite contains unrelated pre-existing failures:

- Clearly distinguish them
- Show evidence that they existed before the optimisation where possible
- Do not conceal them
- Fix only failures caused by this work unless a tiny related fix is required

Re-run the original performance benchmark.

For each representative route compare:

- Before query count
- After query count
- Percentage reduction
- Before duplicate count
- After duplicate count
- Before database time
- After database time
- Before request time
- After request time
- Any remaining known hotspot

Acceptance criteria for Phase 14:

- Final benchmarks are captured
- Query reductions are measurable
- Existing workflows remain functional
- Query-budget tests pass
- One final full suite has been executed
- Any unresolved performance hotspot is documented honestly

======================================================================
REQUIRED IMPLEMENTATION REPORT
======================================================================

Create a final report following the project’s existing documentation conventions.

Suggested filename:

docs/performance/UHMS_DATABASE_QUERY_PERFORMANCE_OPTIMISATION_REPORT.md

The report must include:

1. Executive summary

2. Original problem

3. Baseline measurements

4. Root causes discovered

5. Shared-shell findings

6. Request-context changes

7. Menu and permission optimisation

8. Blade/view-query cleanup

9. AJAX duplicate-request cleanup

10. Page-level N+1 fixes

11. Dashboard/KPI aggregation changes

12. Caching strategy

13. Index changes

14. Query-budget test infrastructure

15. Before-and-after benchmark table

16. Files created

17. Files modified

18. Migrations added

19. Configuration added or changed

20. Cache invalidation rules

21. Security and privacy considerations

22. Test results

23. Full-suite result

24. Remaining limitations

25. Recommended future improvements

Include a benchmark table similar to:

| Route | Before queries | After queries | Reduction | Before DB time | After DB time |
|------|---------------:|--------------:|----------:|---------------:|--------------:|
| Departments index | 441 | ... | ...% | ... | ... |
| Departments AJAX | 439 | ... | ...% | ... | ... |
| Patients index | ... | ... | ...% | ... | ... |
| Visits index | ... | ... | ...% | ... | ... |
| Department dashboard | ... | ... | ...% | ... | ... |
| Consultation workspace | ... | ... | ...% | ... | ... |

======================================================================
CODE QUALITY REQUIREMENTS
======================================================================

All code must:

- Follow existing project conventions
- Use strict types where the project does
- Use typed return values where consistent
- Avoid introducing unnecessary abstractions
- Avoid service-locator patterns
- Avoid static mutable request state
- Use dependency injection
- Use request-scoped services where appropriate
- Preserve existing route names
- Preserve existing URLs
- Preserve existing permission names
- Preserve English and French localisation
- Preserve audit logging
- Preserve department scoping
- Preserve patient privacy
- Preserve billing integrity
- Preserve clinical workflow behaviour
- Include comments only where the reason is not obvious
- Avoid premature micro-optimisation
- Prefer measurable improvements

======================================================================
EXPECTED ROOT-CAUSE PRIORITY
======================================================================

Investigate in this order unless the measurements prove another order is better:

1. Shared authenticated layout
2. Sidebar/menu builder
3. Permission and role loading
4. Active department resolver
5. Global view composers
6. Header and notification counters
7. Shared dashboard/widget resolution
8. Patient privacy capability checks
9. Blade components
10. Model accessors
11. Policies called inside loops
12. Page-specific Eloquent N+1 queries
13. AJAX rendering of full layouts
14. Duplicate frontend requests
15. Dashboard metric query multiplication
16. Caching opportunities
17. Database indexes

Do not begin by adding indexes or caching everything.

======================================================================
WORKING METHOD
======================================================================

For every phase:

1. Inspect the relevant existing code.
2. Explain briefly what was discovered.
3. Implement the smallest coherent fix.
4. Run focused checks.
5. Record measurable results.
6. Continue to the next phase.

Do not ask me to identify every affected file manually. Explore the repository.

Do not stop for confirmation between phases unless:

- A destructive migration would be required
- A major architectural replacement is unavoidable
- Existing behaviour is genuinely ambiguous and cannot be inferred from code/tests
- A required external service is unavailable
- Credentials or environment-specific access are required

Otherwise proceed using the safest implementation consistent with the existing architecture.

======================================================================
FINAL RESPONSE FORMAT
======================================================================

When implementation is complete, respond with:

1. A concise completion statement

2. Root causes found

3. Major changes implemented

4. Before/after benchmark summary

5. Query-budget targets achieved

6. Tests executed and results

7. Full-suite result

8. Migrations/configuration/cache changes

9. Remaining hotspots or limitations

10. Link/path to the final implementation report

Do not state that the optimisation is complete unless actual before-and-after measurements have been performed.

Begin now by inspecting the codebase and establishing the baseline.
