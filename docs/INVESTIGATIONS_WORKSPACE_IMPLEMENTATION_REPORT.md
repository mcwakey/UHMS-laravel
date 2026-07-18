# Investigations Department Workspace — Implementation Report

Dedicated `/investigations/*` browser workspace for `DepartmentType::INVESTIGATION`
departments (laboratory, radiology and other diagnostic sections), following the
established Records / Nursing / Emergency / Inpatient workspace architecture.
All pages are thin browser adapters over the existing lab, catalogue, journey
and reporting services — no domain logic was duplicated.

## 1. Scoping model

Diagnostic work is bounded by the **performing (target) department**:
`lab_requests.target_department_id`.

- `app/Services/InvestigationWorkspaceScope.php` (new) resolves the active
  department (via `DepartmentContextSwitcherService`) and returns its id only
  when the type is `INVESTIGATION`. It offers `labRequests()` / `samples()`
  query bounders and `contains()` / `containsSample()` membership checks.
- The shared lab controllers apply the boundary only on `investigations.*`
  routes (same pattern the inpatient workspace uses with `routeIs('inpatient.*')`):
  - `Lab\LabRequestController` — `index()` forces
    `filters['target_department_id']`; `show/accept/acceptSelected/cancel`
    abort 404 for out-of-scope requests.
  - `Lab\SampleController` — `index()` forces the filter;
    `generate/collect/receive/reject/dispose` abort 404 for out-of-scope
    specimens.
  - `Lab\LabResultController` — `index()` forces the filter;
    `showRequest/batchStore` abort 404 for out-of-scope requests.

Admin (`admin.lab.*`) and other workspaces are untouched: outside
`investigations.*` routes the scope contributes nothing.

## 2. Routes (`routes/web.php`)

`Route::prefix('investigations')->name('investigations.')`
`->middleware('department.type:investigation')` — inserted after the inpatient
group. Route **names mirror the admin names** (minus `admin.`) so the generic
`WorkspaceRouteResolver` fallback maps them automatically.

| Page | URL | Name | Controller | Permission |
|---|---|---|---|---|
| Dashboard | `/investigations` | `investigations.dashboard` | `Investigations\DashboardController` (new) | auth + dept guard |
| Dashboard redirect | `/investigations/dashboard` | `investigations.dashboard.redirect` | closure redirect | — |
| Requests | `/investigations/requests[...]` | `investigations.lab.requests.index/show/accept/accept-selected/cancel` | `Lab\LabRequestController` | `lab.requests.view` (+ `lab.results.create` for actions) |
| Specimens | `/investigations/specimens[...]` | `investigations.lab.samples.index/generate/collect/receive/reject/dispose` | `Lab\SampleController` | `lab.samples.*` |
| Results | `/investigations/results[...]` | `investigations.lab.results.index/show/batch/verify/view/print/print-request/store` | `Lab\LabResultController` | `lab.results.*` |
| Tests catalogue | `/investigations/tests` | `investigations.lab.tests.index` | `Admin\Lab\LabTestController` | `lab.tests.manage` |
| Services catalogue | `/investigations/services[/{service}]` | `investigations.investigation-catalogue.index/show` | `Admin\Lab\InvestigationCatalogueController` | `lab.tests.manage` |
| Items usage | `/investigations/items` | `investigations.items.index` | `Admin\Lab\InvestigationItemController` | `lab.tests.manage` |
| Item stock | `/investigations/stock` | `investigations.stock.index` | `Admin\Lab\InvestigationItemController@stock` | `pharmacy.stock.manage` |
| Patients | `/investigations/patients[/{patient}]` | `investigations.patients.index/show` | `Admin\Patients\PatientController` | `patients.view` |
| Handoffs | `/investigations/handoffs[...]` | `investigations.handoffs.index/refresh/claim/assign/acknowledge/resolve` | `Admin\Journey\JourneyWorklistController` + `JourneyHandoffAssignmentController` | journey capabilities |
| Reports | `/investigations/reports` | `investigations.reports.index` | `Admin\Reporting\OperationalReportController` (`report=investigations`) | `reports.investigations` |

**Deliberately not registered** (no real functionality exists — no placeholder
pages per spec): equipment management, quality control. Catalogue/items/stock
*mutations* stay on the admin POST/PUT endpoints (non-GET requests are never
redirected, so forms keep working).

## 3. URL resolution & redirects

- `app/Services/WorkspaceRouteResolver.php`: `isInvestigation()`, included in
  `isDepartmentWorkspace()`, `dashboardRouteName() → investigations.dashboard`,
  a `routeName()` branch (explicit map for `admin.journey.worklist[.refresh]` →
  `investigations.handoffs.*` and `admin.investigations.*` → `investigations.*`
  to avoid a doubled prefix; generic `admin.` → `investigations.` fallback
  guarded by `Route::has()`), `handoffIndex()` / `handoffRouteName()` cases, a
  `viewContext()` branch (`workspaceKey: investigations`) and
  `investigationsBreadcrumbs()`.
- `records.redirect` (`RedirectRecordsWorkspace`) added to the admin `lab`,
  `investigation-catalogue` and `investigations` (items/stock) groups: generic
  browser GETs bounce into the workspace for investigation users; JSON / AJAX /
  signed URLs and all non-GET verbs pass through untouched. This also makes the
  hardcoded `admin.lab.results.index` redirects in the accept flows land in the
  workspace.
- `EnsureActiveDepartmentType` returns `__('investigations.unauthorized')` for
  the workspace guard.
- Login and department switching land on `investigations.dashboard`
  automatically via the resolver (verified by test).

## 4. Diagnostic operations dashboard

- `app/Services/Dashboards/InvestigationsDashboardService.php` (new): all
  queries bounded by `InvestigationWorkspaceScope`. Payload: backlog insight
  banner (pending + urgent + oldest wait), diagnostic-load pressure widget
  (`BuildsPressure`), KPIs (pending / in-process / completed today / abnormal
  today), 7-day received-vs-completed volume trend, specimen-status donut,
  urgency-ordered active worklist, results awaiting verification.
- `app/Http/Controllers/Investigations/DashboardController.php` (new, invokable).
- `resources/views/dashboards/investigations.blade.php` (new): modern Preclinic
  design language — `_insight` / `_pressure` / `_kpi` / `_avatar` partials,
  local ApexCharts, `badge-soft-*` chips.

## 5. Sidebar

`SidebarMenuBuilder`: `INVESTIGATION` branch in `build()` (before the
consultation-user fallback) + `investigationsSections()` — Dashboard;
Diagnostics (Requests, Specimens, Results); Patients & Coordination (Patients,
Handoffs — capability-gated); Catalogue & Stock (Services, Tests, Items, Stock);
Reports; General (Notifications, Profile). All items permission/module filtered.

## 6. Localization

`lang/en/investigations.php` + `lang/fr/investigations.php` extended with
`unauthorized`, `workspace.title`, `menu.*`, `dashboard.*`, `breadcrumbs.*`.
Recursive key parity verified by test (228 = 228 keys).

## 7. Tests

`tests/Feature/InvestigationsWorkspaceTest.php` — 9 tests, 49 assertions, all
passing:

1. Workspace URLs + `department.type:investigation` + permission middleware.
2. Non-investigation department gets 403 on all workspace pages.
3. Sidebar is workspace-specific, permission filtered, active-state correct.
4. Request queue and direct access scoped by target department (404 out of scope).
5. Specimen queue and mutation actions scoped (404 out of scope).
6. Dashboard renders only the active department's metrics; `/dashboard` redirects.
7. Legacy browser lab route redirects; JSON stays compatible (no redirect).
8. Login and department switch land on the workspace dashboard.
9. EN/FR recursive locale parity.

Regression runs (all green):

- `InpatientWorkspaceTest`, `EmergencyWorkspaceTest`, `NursingWorkspaceTest`,
  `RecordsWorkspaceTest` — 46 passed.
- `LabWorkflowTest`, `tests/Feature/Lab`, `InvestigationOverallResultTypeTest`,
  `LaboratoryPaymentGateTest`, `DoctorWorkspaceTest`, `RoleDashboardTest`,
  `InvestigationCatalogueEditingTest` — all passed. One stale assertion in
  `InvestigationCatalogueEditingTest` was updated: an investigation-department
  manager opening the generic catalogue URL is now (intentionally) redirected
  into the workspace copy; the test asserts the redirect and then the workspace
  page content.
- `InvestigationLogTest`, `DepartmentDashboardTest`, `DepartmentMenuProfileTest`
  — 17 passed.

## 8. Security & privacy notes

- Permissions remain authoritative — the workspace adds the department-type
  guard *on top of* the existing `can:` middleware; it never widens access.
- Out-of-scope records return 404 (existence not revealed), matching the
  inpatient workspace convention.
- Result verification, print auditing (`RESULT_PRINTED` activity log) and the
  billing acceptance flow are reused untouched.
- JSON/AJAX/signed/print/non-GET requests are never redirected.
