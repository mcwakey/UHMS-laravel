# Maternity Department Workspace — Implementation Report

Dedicated `/maternity/*` browser workspace for `DepartmentType::MATERNITY`,
following the established workspace architecture (Records / Nursing / Emergency
/ Inpatient / Investigations / Pharmacy / Stores / Finance). The maternity
module itself was already complete — this phase gives it a first-class
workspace mount with zero duplication.

## 1. What the audit found (and the key design decision)

UHMS already ships a full maternity module under `admin.maternity.*` (87
routes, `Admin\Maternity\*` controllers + `Services\Maternity\*`):

- **Pregnancies & antenatal** — `PregnancyProfileController`,
  `AntenatalVisitController` (profiles, ANC visits, risk status, referrals,
  admission requests).
- **Labour** — `LaborEpisodeController`: episodes, stage transitions,
  labour observations (the partograph data set: maternal/fetal fields,
  contractions, dilatation), theatre and emergency escalation, admission
  requests, close/cancel.
- **Delivery & newborns** — `DeliveryRecordController`,
  `NewbornRecordController`: delivery records, completion, newborn records
  (bulk create, birth-outcome status, patient linking/creation).
- **Postnatal** — `PostnatalCaseController`: cases + mother and newborn
  observations, discharge status, close/cancel.
- **Cases, dashboard, billing readiness, reports** — `MaternityCaseController`,
  `MaternityDashboardController` (existing operations dashboard fed by
  `MaternityOverviewService`), `MaternityBillingReadinessController`,
  `MaternityReportController` (7 report pages + CSV export).
- Granular `maternity.*` permissions on every route.

**Design decision — single source of truth:** instead of copying ~90 route
definitions into the workspace group (the drift risk would be severe), the
admin group body was extracted into **`routes/partials/maternity.php`** and is
now mounted twice:

- `/admin/maternity` → `admin.maternity.*` (unchanged names/URLs, plus
  `records.redirect`), and
- `/maternity` → `maternity.*` under `department.type:maternity`.

Both mounts share identical definitions, so the resolver's
`admin.maternity.X → maternity.X` mapping is total by construction. The
extraction was performed by script with sanity checks; `route:list` confirms
all 87 admin routes survived unchanged and 87 workspace twins now exist
(+ dashboard redirect, patients, handoffs).

## 2. Routes

Workspace group in `routes/web.php`:

- `/maternity` = `maternity.dashboard` (existing `MaternityDashboardController`
  reused as the workspace command board); `/maternity/dashboard` redirects to it.
- The entire maternity module via the shared partial: pregnancies, antenatal,
  labour + observations, deliveries, newborns, postnatal + observations,
  cases, billing readiness, and the maternity report suite — all under
  `/maternity/*` with their existing per-route `can:maternity.*` permissions
  plus the group-level `can:maternity.view` and the department-type guard.
- Workspace-only additions: `maternity.patients.index/show`
  (`patients.view`), `maternity.handoffs.index/refresh/claim/assign/
  acknowledge/resolve` (Journey Intelligence).

**Deliberately not registered** (per the no-placeholder rule): wards/beds,
vitals/tasks/medications/treatments, theatre/blood-request/investigation
worklists, referral/transfer/discharge/readmission pages — these have no
maternity-specific pages today. Admissions, escalations, referrals and theatre
requests flow through the existing maternity actions
(`cases.admission-request`, `labor.theatre-escalation`,
`labor.emergency-escalation`, `antenatal.referral`) which are all mounted;
ward-side care lives in the inpatient/nursing workspaces, which remain
authoritative as the spec requires.

## 3. URL resolution & redirects

- `WorkspaceRouteResolver`: `isMaternity()`, `isDepartmentWorkspace()`
  inclusion, `dashboardRouteName() → maternity.dashboard`, a `routeName()`
  branch (`admin.maternity.*` → `maternity.*` prefix collapse + handoff map +
  generic fallback guarded by `Route::has()`), handoff helpers, `viewContext()`
  (`workspaceKey: maternity`, `workspaceScope: maternity_care`) and
  `maternityBreadcrumbs()`.
- `records.redirect` on the admin maternity mount: generic browser GETs bounce
  into `/maternity/*` for maternity users; JSON/AJAX/signed and all non-GET
  verbs pass through. Module mutations redirect with `back()` or into
  `admin.maternity.*` show pages, which then bounce into the workspace.
- `EnsureActiveDepartmentType` returns `__('maternity.workspace.unauthorized')`.
- Login and department switching land on `/maternity`.

## 4. Dashboard

The existing maternity operations dashboard (`MaternityDashboardController` +
`MaternityOverviewService` + `maternity.dashboard` view) is the workspace
command board at `/maternity` — it already carries the ANC/labour/delivery/
postnatal/newborn metrics the spec asks for, so no parallel dashboard was
built (reuse over duplication).

## 5. Sidebar

`maternitySections()` in `SidebarMenuBuilder` — Dashboard; Maternity Care
(Pregnancies & Antenatal, Labour & Delivery, Postnatal); Coordination
(Patients, Handoffs — capability-gated, Billing Readiness); Reports; General.
Nested pages (antenatal, deliveries, newborns, observations) highlight their
parent items via `active_patterns`. Permissions remain authoritative.

## 6. Localization

`lang/en/maternity.php` + `lang/fr/maternity.php` extended with a `workspace`
subtree (title, unauthorized, breadcrumb root, menu, breadcrumbs). Recursive
EN/FR parity of the subtree is test-verified.

## 7. Tests

`tests/Feature/MaternityWorkspaceTest.php` — 8 tests, 45 assertions, all
passing:

1. Workspace URLs + guard middleware stack (dept type + `maternity.view` +
   per-route permission), and both mounts present.
2. Non-maternity department gets 403 on all workspace pages.
3. Sidebar workspace-specific, permission filtered, active-state correct
   (midwife without report/billing permissions loses those entries).
4. Pregnancies / labour / postnatal worklists render in the workspace.
5. Dashboard renders; `/maternity/dashboard` redirects to `/maternity`.
6. Legacy admin URLs redirect into the workspace; JSON stays put.
7. Login and department switch land on `/maternity`.
8. Workspace locale subtree EN/FR parity.

Regression runs (all green):

- **Maternity module suites** (`MaternityFoundationPhase8`,
  `LaborDeliveryFoundationPhase10`, `NewbornBirthOutcomePhase11`,
  `PostnatalCarePhase12`, `MaternityReportsBillingReadinessPhase13`,
  `MaternityBillingPostingPhase14_1`) — 48 passed after retargeting 20
  page-render GET calls: those tests use maternity-department users whose
  generic-URL GETs are now (intentionally) redirected into the workspace, so
  the render assertions now hit the equivalent `maternity.*` routes directly.
  POST/PATCH calls and `assertRedirect(admin.maternity.*)` expectations were
  left untouched — mutations are never redirected and controllers still emit
  admin route names.
- **Broad workspace suite** (all 9 workspace tests + DoctorWorkspace +
  DepartmentMenuProfile + DepartmentDashboard + FrenchRouteSmoke) —
  102 passed, no changes needed.

## 8. Safety & privacy notes

- Zero new clinical write paths: every mutation the workspace mounts is the
  existing audited, permission-gated maternity module action, unchanged.
- Obstetric safety flows (risk status, escalations, birth-outcome management,
  session close/cancel rules) live in the module services and are untouched.
- The department-type guard adds to the existing `can:maternity.*` permission
  lattice; a broad clinical permission alone does not open the workspace.
- Newborn patient creation/linking keeps its dedicated permissions
  (`maternity.newborn.link_patient` / `create_patient`).

## 9. Remaining limitations

- Maternity ward/bed pages, dedicated maternity triage, and maternity-scoped
  medication/investigation worklists would require either new module pages or
  scoped mounts of the inpatient facilities; the inpatient/nursing workspaces
  currently remain authoritative for ward-side care.
- `routes/partials/maternity.php` is now the single definition for both
  mounts — future maternity routes should be added there (a header comment in
  the file says so).
