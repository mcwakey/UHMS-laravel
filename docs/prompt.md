Reopen the UHMS database-query optimisation work.

The previous optimisation report claimed that the consultation workspace used
11 queries in an isolated SQLite feature benchmark. However, the real browser
request running against MariaDB shows the following for the exact doctor
consultation workspace:

Route:
GET /doctor/consultations/3/routes/3

Actual Laravel Debugbar result:

- 280 database queries
- 190 duplicate queries
- 90 unique queries
- 1,360 Eloquent models loaded
- 450 Blade views rendered
- 192 Gate checks
- 58 MB memory
- 2.73-second request time

Treat this browser result as the current source of truth.

The consultation performance optimisation is not complete. Do not merely update
the report or explain the discrepancy. Investigate and implement the remaining
fixes.

======================================================================
OBJECTIVE
======================================================================

Optimise the real doctor consultation workspace, including its actual:

- Middleware
- Doctor-prefixed route
- Shared authenticated layout
- Sidebar
- Workspace resolver
- Consultation session services
- Specialty workspace
- Blade components
- Policies and gates
- Patient privacy checks
- Billing checks
- Investigation, prescription and procedure selectors
- Consultation tasks
- Favorites
- Order sets
- Readiness checks
- Summary builder
- AJAX endpoints

Preserve all clinical, billing, privacy, permission, audit, session and
department behaviour.

======================================================================
PHASE 1 — REPRODUCE THE EXACT REQUEST
======================================================================

Locate the exact named route corresponding to:

GET /doctor/consultations/{...}/routes/{...}

Create a representative authenticated doctor fixture that uses:

- The real doctor role
- The real department assignment
- The real active department context
- A consultation route
- An active consultation session
- Representative diagnoses, complaints, investigations, prescriptions,
  procedures, tasks and specialty data
- The same middleware stack as the browser route

Do not benchmark only the admin consultation route.

Do not bypass middleware in the performance test.

Capture:

- Query total
- Unique queries
- Duplicate queries
- Database time
- Request time
- Models hydrated by class
- Views rendered by view name
- Gate checks by permission/ability
- The top 20 duplicate SQL fingerprints
- Their call sites

Compare the browser request with the existing feature benchmark and explain
precisely why the earlier test reported 11 queries while the real route reports
280.

Likely causes to verify include:

- Different route or middleware stack
- Incomplete fixture data
- Admin route versus doctor-prefixed route
- Views or components not rendered in the benchmark
- Different department context
- Specialty workspace sections missing from the fixture
- Database cache differences
- Different authentication role
- Lazy-loaded production relationships
- Browser-only shared shell data

======================================================================
PHASE 2 — VIEW-RENDER EXPLOSION
======================================================================

The request renders approximately 450 Blade views.

Use Laravel Debugbar, view events or safe test instrumentation to group rendered
views by name and count.

Identify:

- The most frequently rendered view
- Components rendered inside loops
- Duplicate specialty sections
- Hidden sections that are still rendered
- Repeated dropdown-option components
- Recursive or repeated layout partials
- Wildcard view composers executing for nested views
- Components whose render methods query the database
- Components whose constructors resolve expensive services

Ensure that:

- Workspace context is calculated once per request
- Shared layout data is prepared once
- Consultation section definitions are calculated once
- Hidden or inactive sections are not fully rendered unnecessarily
- A component is not rendered once per lookup option
- Static UI elements use lightweight markup rather than hundreds of components

Do not remove visible consultation functionality.

======================================================================
PHASE 3 — MODEL HYDRATION
======================================================================

The request hydrates approximately 1,360 Eloquent models.

Group model hydration by class and identify the largest contributors.

Investigate whether the initial page unnecessarily loads complete collections
for:

- ICD-10 diagnoses
- Products and medications
- Investigation services
- Procedure services
- Departments
- Referral destinations
- Consultation favorites
- Order-set items
- Specialty templates
- Tasks
- Billing mappings
- Sessions
- Notifications

Convert large selector datasets to server-side searchable AJAX endpoints where
appropriate.

For ICD-10, medications, investigations, procedures and services:

- Do not load the entire database into the page
- Load only existing selected values initially
- Use debounced server-side search
- Limit each response
- Preserve insurer pricing, department scoping and service availability
- Preserve English/French labels
- Preserve selected values after validation errors

Use DTOs or prepared arrays when full Eloquent models are not required.

Restrict selected columns.

Use withCount() and withExists() instead of loading full relationships merely
to count or test existence.

======================================================================
PHASE 4 — DUPLICATE QUERY REMOVAL
======================================================================

Group all 190 duplicate queries by normalized SQL fingerprint.

For each major duplicate group, identify:

- Count
- Total time
- Call sites
- Whether it is shared-shell or consultation-specific
- Correct remediation

Inspect particularly:

- ConsultationSessionService
- WorkspaceRouteResolver
- DepartmentContextSwitcherService
- Specialty profile resolver
- Consultation workspace controller
- Readiness services
- Summary services
- Favorites services
- Order-set services
- Billing mapping services
- Privacy services
- Policies
- Blade components
- View composers

Request-shared values must be memoized safely per request.

Do not use static mutable state.

Do not use singleton services for user-, visit-, session- or
department-specific memoized values.

Ensure memoization keys include all relevant identity:

- User
- Visit
- Consultation route
- Consultation session
- Active department
- Specialty profile
- Locale

======================================================================
PHASE 5 — GATE AND POLICY CHECKS
======================================================================

The request performs approximately 192 Gate checks.

Group checks by ability and model type.

Identify checks repeated many times in Blade components or loops.

Prepare a consultation capability DTO or associative map once per request.

Examples:

- Can edit complaints
- Can add diagnosis
- Can request investigations
- Can prescribe
- Can add procedures
- Can create tasks
- Can complete session
- Can reopen session
- Can override readiness
- Can view sensitive patient fields
- Can view billing information

Blade should consume prepared capability values instead of repeatedly calling
Gate or policies for the same user/resource pair.

Do not weaken or remove authorization.

Resource-dependent permissions that genuinely vary by row must remain
resource-aware.

======================================================================
PHASE 6 — CONSULTATION DATA LOADING
======================================================================

Construct one deliberate consultation workspace query/data-loading plan.

Load only relationships needed by the visible workspace.

Use:

- Explicit select()
- Eager loading
- loadMissing()
- withCount()
- withExists()
- Batch loading
- Grouped aggregates
- Prepared DTOs

Avoid:

- Queries inside Blade
- Queries inside accessors
- Queries inside loops
- Repeated visit/route/session resolution
- Repeated patient privacy resolution
- Repeated department resolution
- Repeated specialty profile resolution
- Repeated billing-context resolution

Verify that the following are resolved once:

- Visit consultation route
- Visit
- Patient
- Current consultation session
- Admission where applicable
- Active department
- Doctor department
- Specialty profile
- Privacy capabilities
- Consultation permissions
- Existing selected investigation/procedure/prescription values

======================================================================
PHASE 7 — NOTIFICATION POLLING
======================================================================

The browser separately requests:

GET /admin/notifications/recent

approximately every 30 seconds, using nine queries per request.

This is secondary to the consultation page, but optimise it after the main
request.

Investigate whether the endpoint can:

- Reuse request-loaded user permissions
- Fetch count and recent records efficiently
- Avoid duplicate role/permission reads
- Use a single lightweight notification query plus count where appropriate
- Stop polling when the browser tab is hidden
- Poll less frequently where acceptable
- Avoid starting multiple polling intervals when components remount

Do not merge notification polling into the main consultation query count.

======================================================================
PHASE 8 — ACCURATE REGRESSION TEST
======================================================================

Add a query-budget test for the exact doctor-prefixed consultation route.

It must:

- Use the real middleware stack
- Use the doctor role
- Use representative consultation data
- Render the full Blade workspace
- Exclude fixture/setup queries
- Record duplicate fingerprints when it fails
- Assert a maximum query count
- Assert a duplicate-query ceiling
- Include an N+1 scaling test

Initial acceptance target:

- Total queries: at or below 90
- Duplicate queries: at or below 10
- No duplicate query executed dozens of times
- Query count remains approximately constant as consultation items increase
- No full lookup-table hydration for ICD-10, medications, investigations or
  procedures

Also add diagnostics for:

- Maximum rendered views
- Maximum hydrated models
- Maximum repeated Gate checks where stable enough for regression testing

Do not replace the existing 11-query test blindly. Determine why it was
unrepresentative and correct the fixture or route.

======================================================================
PHASE 9 — REAL BROWSER VERIFICATION
======================================================================

After implementation, verify the actual route in Laravel Debugbar using MariaDB.

Report:

- Before and after query count
- Before and after duplicate count
- Before and after model count
- Before and after view count
- Before and after Gate count
- Before and after memory
- Before and after request time
- Top remaining duplicate queries
- Top remaining hydrated model classes
- Top remaining rendered views

Do not declare the consultation workspace optimised based only on SQLite tests.

======================================================================
TESTING RULES
======================================================================

During implementation, run focused tests only:

- Consultation workspace
- Doctor routing and middleware
- Department switching
- Permission checks
- Patient privacy
- Billing context
- Session lifecycle
- Investigation/prescription/procedure selectors
- Query budgets
- Blade compilation
- Route compilation
- English/French localisation parity
- Audit integrity

After all consultation optimisation phases are complete, run the wide suite
once and save the output durably to a log and JUnit XML file.

======================================================================
REPORT UPDATE
======================================================================

Update:

docs/performance/UHMS_DATABASE_QUERY_PERFORMANCE_OPTIMISATION_REPORT.md

Correct the consultation benchmark section.

Clearly distinguish:

- Isolated SQLite benchmark
- Real MariaDB browser benchmark
- Admin route
- Doctor-prefixed route
- Test fixture limitations

Do not retain the claim that the real consultation workspace uses 11 queries
unless the actual browser request confirms it.

Begin by reproducing and profiling the exact doctor route.
