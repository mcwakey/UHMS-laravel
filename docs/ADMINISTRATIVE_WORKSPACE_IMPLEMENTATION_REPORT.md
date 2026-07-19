# Administrative Department Workspace — Implementation Report

Dedicated `/administrative/*` browser workspace for
`DepartmentType::ADMINISTRATIVE`, completing the department-workspace family
(Records / Nursing / Emergency / Inpatient / Investigations / Pharmacy /
Stores / Finance / Maternity). All pages are thin oversight adapters over the
existing department, user, HR, audit-log, journey and reporting services —
specialist workspaces remain authoritative for their own operations, and no
management logic was duplicated.

## 1. What the audit found (and what shaped the scope)

Real administrative/oversight functionality already in the codebase, all reused:

- **Departments & designations** — `Admin\Settings\DepartmentController`,
  `Admin\Hr\DesignationController` (`departments.view/create/edit/delete`).
- **Users & roles** — `Admin\Settings\UserController`, `RoleController`
  (`users.*`, `roles.manage`), user-department assignments, per-user permission
  overrides.
- **Staff & coverage (HR)** — `Admin\Hr\EmployeeController`,
  `AttendanceController` (+ summary), `LeaveController` with `LeaveStatus`
  approval flow (`hr.*` permissions, module `hr`).
- **Audit** — the dedicated activity-log viewer
  (`Admin\Reporting\ActivityLogController`, `logs.view`), with export and
  retention kept admin-side.
- **Announcements** — `NotificationBroadcastController`
  (`notifications.broadcast`).
- **Executive reporting** — the reports hub (`ReportsHubController`),
  department metrics, department comparison, and Journey Intelligence
  analytics (`JourneyAnalyticsController`).
- **Coordination** — Journey handoff worklist + actions.

**Not built** (no backing modules — per the no-placeholder rule): approval
worklists as a distinct module, policies/SOPs/guidelines, document/
correspondence registers, memos, incident/grievance management (the existing
"complaints" module is *clinical presenting complaints*, not administrative
grievances), meetings/committees/action items, risk register, facility issue
tracking, duty rosters beyond HR attendance. These are listed as mount-ready
gaps.

## 2. Routes (`routes/web.php`)

`Route::prefix('administrative')->name('administrative.')`
`->middleware('department.type:administrative')` — 24 routes. Names mirror the
admin names (minus `admin.`) so the resolver's generic fallback maps them.

| Page | URL | Name | Permission |
|---|---|---|---|
| Dashboard | `/administrative` (+ `/dashboard` redirect) | `administrative.dashboard[.redirect]` | dept guard |
| Departments / designations | `/administrative/departments`, `/administrative/designations` | `administrative.departments.index`, `administrative.designations.index` | `departments.view` |
| Users / roles | `/administrative/users`, `/administrative/roles` | `administrative.users.index`, `administrative.roles.index` | `users.view`, `roles.manage` |
| Staff | `/administrative/hr/employees[/{id}]`, `/hr/attendance[,/summary]`, `/hr/leave` | `administrative.hr.employees.index/show`, `administrative.hr.attendance.index/summary`, `administrative.hr.leave.index` | `hr.*` (module `hr`) |
| Audit trail | `/administrative/audit[/{id}]` | `administrative.logs.index/show` | `logs.view` |
| Announcements | `/administrative/announcements` | `administrative.notifications.broadcast.create` | `notifications.broadcast` |
| Reports | `/administrative/reports[...]` | `administrative.reports.index/department-metrics/department-comparison.index` | `reports.view` (+ comparison perm) |
| Journey analytics | `/administrative/analytics` | `administrative.journey.analytics` | journey capability (controller-gated) |
| Handoffs | `/administrative/handoffs[...]` | `administrative.handoffs.*` | journey capabilities |

Mutations (department create/update, user create/edit, role edits, leave
approve, broadcast send, log export/retention) stay on the admin endpoints —
non-GET is never redirected, and `back()` returns users into the workspace.

## 3. URL resolution & redirects

- `WorkspaceRouteResolver`: `isAdministrative()`, `isDepartmentWorkspace()`
  inclusion, `dashboardRouteName() → administrative.dashboard`, `routeName()`
  branch (handoff map + generic `admin.` → `administrative.` fallback guarded
  by `Route::has()`), handoff helpers, `viewContext()`
  (`workspaceKey: administrative`, `workspaceScope: hospital_operations`) and
  `administrativeBreadcrumbs()`.
- `records.redirect` added to the admin users, roles, departments, HR, and
  logs groups (the reports group already had it): generic browser GETs bounce
  into `/administrative/*` for administrative users; JSON / AJAX / signed /
  exports (`admin.logs.export` has no workspace mirror, so it never redirects)
  and all non-GET verbs pass through. Other typed departments are unaffected —
  their mapped candidates don't exist.
- `EnsureActiveDepartmentType` returns `__('administrative.unauthorized')`.
- Login and department switching land on `/administrative`.

## 4. Dashboard (hospital operations board)

New `AdministrativeDashboardService` + `dashboards/administrative.blade.php` in
the modern design language, built from **counts only** (no clinical or
financial detail):

- **Insight** — pending leave-approval banner with oldest-wait chip.
- **Pressure** — hospital activity (visits today) with visits/admissions/
  active-departments/pending-leave metric chips.
- **KPIs** — active departments, active staff accounts, visits today,
  admissions today.
- **Charts** — 7-day hospital visit trend (area), active departments by type
  (donut).
- **Lists** — busiest departments today (visits by current department) and the
  recent audit trail (latest activity-log entries with actor and module),
  linking into `/administrative/audit`.

## 5. Sidebar

`administrativeSections()` in `SidebarMenuBuilder` — Dashboard; Hospital
Oversight (Departments, Designations, User Accounts, Roles & Permissions);
Staff & Coverage (Employees, Attendance, Leave Requests); Governance (Audit
Trail, Announcements, Handoffs — capability-gated); Reports & Analytics
(Reports Hub, Department Metrics, Department Comparison, Journey Analytics);
General. All permission/module filtered — a coordinator with only
`departments.view` sees no user-management or audit entries (test-verified).

## 6. Localization

New `lang/en/administrative.php` + `lang/fr/administrative.php`
(`unauthorized`, `workspace.title`, `menu.*`, `dashboard.*`, `breadcrumbs.*`)
with recursive EN/FR parity verified by test.

## 7. Tests

`tests/Feature/AdministrativeWorkspaceTest.php` — 8 tests, 51 assertions, all
passing on the first run:

1. Workspace URLs + `department.type:administrative` + permission middleware.
2. Non-administrative department gets 403 on all workspace pages.
3. Sidebar workspace-specific, permission filtered, active-state correct.
4. Departments / users / audit pages render inside the workspace.
5. Dashboard renders the operations board; `/administrative/dashboard`
   redirects.
6. Legacy admin URLs (`admin.departments.index`, `admin.users.index`,
   `admin.logs.index`) redirect into the workspace; JSON stays put.
7. Login and department switch land on `/administrative`.
8. EN/FR recursive locale parity.

Regression runs:

- Suites touching the redirected admin groups (`AuthTest`,
  `DepartmentManagementTest`, `HRWorkflowTest`, `UserManagementTest`,
  `UserPermissionOverrideTest`, `RolePermissionSecurityLogTest`,
  `UserRoleAssignmentSecurityLogTest`, `JourneyOversightTest`,
  `DepartmentDashboardUiPhase8Test`) — 57 passed, 1 failed:
  `AuthTest::test_admin_redirected_to_admin_dashboard` — **stash-verified
  pre-existing** (fails identically on HEAD without this work; a Super Admin
  login currently lands on `/doctor` due to the earlier doctor-dashboard
  restructure). Left untouched and flagged here for follow-up.
- Broad workspace suite (all 10 workspace tests + DoctorWorkspace +
  DepartmentMenuProfile + DepartmentDashboard) — 108 passed.

## 8. Security & boundaries

- Oversight only: the workspace registers **no new write endpoints** — every
  mutation remains the existing audited, permission-gated admin action.
- Broad view permissions alone do not open the workspace; the department-type
  guard sits on top of the `can:` lattice.
- The dashboard exposes operational counts only — no patient identities,
  clinical detail, or financial values.
- Technical system administration (settings, modules, integrations, log
  retention) deliberately stays in the admin area, per the spec's separation
  rule.

## 9. Remaining limitations

- Approval worklists, policies/SOPs, document registers, memos, administrative
  incident/grievance tracking, meetings/committees/action items, risk
  register, and facility issue tracking have no backing modules yet; mount
  them under `/administrative/*` with this same adapter pattern when built.
- Pre-existing (unrelated) failure: `AuthTest::test_admin_redirected_to_admin_dashboard`
  — Super Admin login lands on `/doctor`; needs a decision on intended
  Super Admin landing behaviour.
