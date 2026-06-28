# Department Dashboard — Full Gap Analysis

Scope: the department-type dashboard feature — `DepartmentDashboardController`,
`DepartmentContextResolver`, `DepartmentDashboardDataService`,
`DepartmentDashboardChartService`, the layout registry / theme registry / menu
profile service, the drilldown builder, and the Blade showcase views.

Severity: **P1** = correctness/security (user-visible wrong data or leakage),
**P2** = performance/UX, **P3** = tech-debt/feature depth.

---

## Status (updated)

**Resolved (P1 correctness + drilldown permissions):**
- ✅ #1 Metric scoping — `pending_prescriptions`/`dispensed_today` scope by
  `prescriptions.department_id`; `in_theatre` by `procedure_requests.department_id`;
  emergency cases via `visits`, beds/admissions via `bed→ward→department`, stock
  issues via `stock_locations`. Invoices/payments documented as hospital-wide.
- ✅ #2 `critical_cases` now = active **and** emergency-triaged (distinct from
  `active_cases`); also fixed the wrong `status` column (`emergency_status`).
- ✅ #3 `vitals_due` (active admissions with no vitals in 8h) and
  `discharges_pending` (admitted past expected discharge) are now real queries.
- ✅ #4 Sparkline series honour each card's status filter (e.g. `dispensed_today`
  spark = `status=dispensed`, scoped).
- ✅ #6 Drilldown permissions corrected — earlier names `pharmacy.prescriptions.view`
  / `admissions.view` / `emergency.view` **don't exist**, which silently disabled
  those drilldowns; now `prescriptions.view` / `ward.view` / `emergency.case.view` /
  `theatre.cases.view`, single-sourced in `permissionFor()`.

**Deferred (with reason):**
- ⏸ #5 Value-level permission gating — attempted, **backed off**: clinical roles
  grant view access via module-specific permissions (doctors see visits through
  consultation perms, not `visits.view`; pharmacists via `pharmacy.stock.manage`,
  not `store.purchase.view`), so gating each value on one permission name
  **over-restricted legitimate dashboards** (consultation went fully `Restricted`).
  Needs a per-role RBAC mapping before re-attempting. Values remain visible (counts,
  route is auth-gated); financial/stock values still self-gate as before.

Tests added: `visit metrics scope to the users department`,
`critical cases counts only emergency-triaged active cases`.

---

## Executive summary

The feature is structurally complete and visually strong (per-type showcases,
sparklines, donuts, scoped queues). The gaps are mostly in **data correctness**,
**permission consistency**, **performance**, and **dead code from the showcase
refactor** — not in the UI shell.

| # | Gap | Severity | Area |
|---|-----|----------|------|
| 1 | Several KPIs are **not department-scoped** (global counts) | P1 | Correctness |
| 2 | `active_cases` == `critical_cases` (identical query) | P1 | Correctness |
| 3 | Stub metrics return hardcoded `0` (`vitals_due`, `discharges_pending`, `default`) | P1 | Correctness |
| 4 | Sparkline series don't match their card's filter (e.g. `dispensed_today`) | P2 | Correctness |
| 5 | Most clinical metrics are **not permission-gated** (only finance/stock are) | P1 | Security |
| 6 | Card value vs drilldown permission mismatch (count shows, link 403s) | P2 | Security/UX |
| 7 | **No caching**; chart service eagerly builds all 7 charts → ~60–80 queries/load | P2 | Performance |
| 8 | Queue/status badges show **raw, untranslated, uncoloured** statuses | P2 | UX/i18n |
| 9 | Quick actions are a **fixed generic set**, not type-specific | P2 | UX |
| 10 | Personalised **menu heading computed but never rendered** | P3 | Dead feature |
| 11 | Orphaned render path: `show` / `dashboard-shell` / `dashboard-body` / 11 family layouts | P3 | Tech-debt |
| 12 | Duplicate logic: `trends()` ≡ `activity_trend`; `activity_trend` ≡ `service_usage_trend` | P3 | Tech-debt |
| 13 | Thin test coverage (no chart/scoping/permission/switcher/drilldown tests) | P2 | Quality |
| 14 | Per-type widgets still generic; no period filter / export / auto-refresh | P3 | Feature depth |

---

## P1 — Correctness

### 1. KPIs that are NOT department-scoped
`DepartmentDashboardDataService::metricCard()` scopes most metrics via
`scopeDepartment()`, but these are **global counts** despite the dashboard
claiming "viewing <department> data only":

- `pending_prescriptions` ([:119](app/Services/Department/DepartmentDashboardDataService.php#L119)) — all prescriptions, any department.
- `dispensed_today` ([:120](app/Services/Department/DepartmentDashboardDataService.php#L120)) — global.
- `active_cases` / `critical_cases` ([:125](app/Services/Department/DepartmentDashboardDataService.php#L125)) — global `emergency_cases`.
- `beds_occupied` ([:127](app/Services/Department/DepartmentDashboardDataService.php#L127)) — global `beds`.
- `stock_issues` ([:124](app/Services/Department/DepartmentDashboardDataService.php#L124)) — global `stock_movements`.
- `in_theatre` ([:132](app/Services/Department/DepartmentDashboardDataService.php#L132)) — global `procedure_requests`.
- `invoices_today` / `payments_today` / `receivables` ([:129–131](app/Services/Department/DepartmentDashboardDataService.php#L129)) — global (these tables have no `department_id`).

**Impact:** two departments of the same type show identical "scoped" numbers;
the scope badge is misleading. **Fix:** scope each by the right column, or where
the table has no department column (invoices/payments), join through
`invoice_items`/`visits` or explicitly label the card as hospital-wide.

### 2. `active_cases` and `critical_cases` are the same number
Both map to the same `emergency_cases` query (`status in active,in_progress`)
([:125](app/Services/Department/DepartmentDashboardDataService.php#L125)). The Emergency dashboard shows the **same value twice** and the
priority-alert banner's "critical" count equals "active". **Fix:** `critical_cases`
should filter by triage/priority/severity (e.g. `priority in (critical,emergency)`).

### 3. Hardcoded stub metrics
`vitals_due`, `discharges_pending` return literal `0`
([:128](app/Services/Department/DepartmentDashboardDataService.php#L128)); `default => 0`
([:133](app/Services/Department/DepartmentDashboardDataService.php#L133)) means any unmapped key silently shows 0. The Ward (`inpatient`)
profile uses `vitals_due` and `discharges_pending`, so that dashboard always shows 0
for two of its four KPIs.

### 4. Sparkline series don't match the card's definition
`metricSpark()` ([:174](app/Services/Department/DepartmentDashboardDataService.php#L174)) builds a generic daily series that ignores the card's
status filter:
- `dispensed_today` card filters `status=dispensed` but its spark counts **all**
  prescriptions by `updated_at` (unscoped) — value 14, spark peak 70 observed.
- `completed_results_today` / `completed_imaging_today` spark counts all
  `lab_requests` (not `status=completed`).

**Impact:** the sparkline trend can contradict the headline number.

---

## P1/P2 — Security & permissions

### 5. Most metrics are not permission-gated
Only financial (`invoices.view`, `payments.view`, `reports.financial_values.view`)
and stock (`store.purchase.view`, `pharmacy.stock.manage`) are gated
(`restrictedCount`/`restrictedSum`/`stockCount`). Clinical counts —
`visits_today`, `waiting_queue`, `pending_requests`, `pending_prescriptions`,
`active_cases`, `active_admissions`, `beds_occupied`, `staff_count` — render for
**any** authenticated user whose type profile includes them, regardless of module
permission. The stated design ("permissions are the security layer; type/name
never grant access") is only half-enforced. **Impact:** low (counts only, route is
auth-gated), but a Store Keeper previewing a clinical dashboard sees patient-flow
counts. **Fix:** gate each metric on its module's `*.view` permission and return a
`restricted` card otherwise (the machinery already exists).

### 6. Card value vs drilldown permission mismatch
`metricCard` shows a value with no permission check, but
`DepartmentDashboardDrilldownUrlBuilder` ([:37](app/Services/Department/DepartmentDashboardDrilldownUrlBuilder.php#L37)) attaches a permission-gated
route. A user can see e.g. `waiting_queue = 12` but the drilldown route is omitted
(no link) or, if linked, lands on a 403. Value visibility and drilldown visibility
should use the same permission.

---

## P2 — Performance

### 7. No caching + eager chart computation
- **No caching anywhere** in `app/Services/Department/*` or the controller — every
  page load recomputes all metrics, sparklines, charts, queues.
- `DepartmentDashboardChartService::build()` ([:16](app/Services/Department/DepartmentDashboardChartService.php#L16)) **always builds all 7 charts**
  (activity_trend, queue/request breakdowns, service_usage_trend, stock_usage_trend,
  workload_by_day, revenue_trend) — each a 7-day loop — even though a showcase
  renders only 2–3. That's ~35 daily-bucket queries + 2 group-bys per load, plus
  the data service's `stock_status_breakdown`.
- Per-card sparklines run a 7-iteration query loop each ([:195](app/Services/Department/DepartmentDashboardDataService.php#L195)); 4 primary cards ≈ 28 queries.
- `trends()` ([:345](app/Services/Department/DepartmentDashboardDataService.php#L345)) runs **another** 7-day invoice_items loop (duplicate of `activity_trend`).
- Frequent `Schema::hasTable()` / `Schema::hasColumn()` calls hit `information_schema`.

**Estimate:** ~60–80 queries per dashboard load. **Fix:** build only the charts the
layout needs; short-TTL cache (e.g. 60–120s) keyed by `department_id`; collapse
the per-day loops into a single grouped `GROUP BY DATE(...)` query per series.

---

## P2 — UX / i18n

### 8. Queue/status badges are raw, untranslated, single-colour
`tableRows()` ([:457](app/Services/Department/DepartmentDashboardDataService.php#L457)) emits `badge => raw status string`, `badge_variant => 'secondary'`
(always grey), and `meta => raw created_at` (full timestamp). So the rich list
cards show `queued`/`pending` (not localized) in grey, with `2026-06-27 03:34:38`.
**Fix:** map status → localized label + colour (reuse `<x-status-badge>` /
`config/ui.php`), and format `created_at` as a friendly/relative time.
Chart status labels use `statuses.default.$status` ([ChartService:247](app/Services/Department/DepartmentDashboardChartService.php#L247)) but visit
statuses live under `statuses.visit.*`, so they fall back to `headline()` instead
of the curated translations.

### 9. Quick actions are a fixed generic set
`quickActions()` ([:319](app/Services/Department/DepartmentDashboardDataService.php#L319)) returns the same 5 links (services, lab requests, lab
results, stock, prices) for every department type, so Pharmacy shows "View Lab
Requests/Results". **Fix:** drive quick actions from the type profile.

---

## P3 — Dead code / tech-debt

### 10. Personalised menu heading is computed but never rendered
`DepartmentMenuProfileService::headingForType()` and the controller's
`$data['menu_heading']` / `$data['dashboard']['menu_heading']` are never output in
any view (`grep menu_heading` over views = 0 hits). The sidebar
(`SidebarMenuBuilder` [:1669](app/Services/SidebarMenuBuilder.php#L1669)) uses only `prioritiseForType()` for section ordering,
**not** the heading. The personalised-heading work is effectively dead.

### 11. Orphaned rendering path (post-showcase refactor)
All 15 `types/{key}.blade.php` are now bespoke (`_chrome` + `{key}_showcase`), so
these are no longer rendered by the controller and are referenced only by each
other (and a `View::exists` test):
- `partials/show.blade.php`, `partials/dashboard-shell.blade.php`,
  `partials/dashboard-body.blade.php`
- the 11 family layouts `partials/layouts/{clinical_queue,emergency_command,
  diagnostic_workbench,imaging_workbench,surgery_board,ward_board,dispensing_stock,
  finance_control,stores_inventory,records_office,generic_department}.blade.php`

The registry still computes `layout_family` / `hero_variant` per type
([Registry](app/Services/Department/DepartmentDashboardLayoutRegistry.php)) but the showcases ignore them. **Fix:** delete the dead path (keep
`_chrome` + `_primary-cards` + `_secondary-cards`) or formally make families the
fallback and document it; drop the unused metadata.

### 12. Duplicate logic
- `trends()` ([Data:345](app/Services/Department/DepartmentDashboardDataService.php#L345)) duplicates `charts['activity_trend']`.
- `activity_trend` ≡ `service_usage_trend` ([Chart:45,58](app/Services/Department/DepartmentDashboardChartService.php#L45)) — identical query.
- `fallbackForType()` ([Chart:27](app/Services/Department/DepartmentDashboardChartService.php#L27)) is unused.
- `scopeDepartmentIfColumnExists()` ([Data:490](app/Services/Department/DepartmentDashboardDataService.php#L490)) just calls `scopeDepartment()`.
- `activities()` ([Data:362](app/Services/Department/DepartmentDashboardDataService.php#L362)) surfaces `invoice_items.description` as "Department Activity" —
  billing lines, not real activity/audit events.

---

## P2 — Test coverage gaps

Existing: `DepartmentDashboardTest`, `DepartmentMenuProfileTest`,
`Departments/{UiPhase6,AdvancedUiPhase7,UiPhase8,DesignDifferentiation}Test`
(resolution, layout families, render smoke, name/identity, services scoping,
sparkline presence). **Missing:**
- Chart service (`DepartmentDashboardChartService`) — no test.
- **Metric department-scoping** — nothing asserts a metric counts only the
  department's rows (would catch gap #1).
- **Permission gating** — no test that revenue/stock/clinical metrics restrict.
- **`active_cases` ≠ `critical_cases`** — would catch gap #2.
- **Context switcher / preview / global context** (`DepartmentContextSwitcherService`).
- **Drilldown URL builder**.
- Data-correctness (tests assert markup presence, not values).

---

## P3 — Feature depth

- **Per-type deep widgets** still generic: bed-occupancy gauge (ward),
  transfusion/expiry safety (blood bank), surgery slot timeline (theatre),
  receivables ageing (finance), turnaround-time SLA (investigation).
- **No period selector** — fixed "today / 7-day"; the admin/doctor dashboards have
  Monthly/Weekly toggles.
- **No export/print, no auto-refresh** (manual refresh button only).
- **Radiology** has a distinct type/profile/theme but no dedicated key/showcase —
  it renders the `investigation` showcase. Same for treatment→consultation,
  ambulance→emergency, mortuary/support→management; their identity is shallow.
- **Multi-department**: data scopes by `current_department_id`; a user whose
  primary department differs from where their data is recorded sees an empty board.

---

## Recommended priority order

1. **P1 correctness**: scope the global metrics (#1), fix `critical_cases` (#2),
   implement or hide stub metrics (#3), align sparkline series with card filters (#4).
2. **P1 security**: gate clinical metrics + align value/drilldown permissions (#5, #6).
3. **P2 perf**: build only needed charts + short-TTL cache + collapse day-loops (#7).
4. **P2 UX/i18n**: localized, coloured status badges + friendly timestamps;
   type-specific quick actions (#8, #9).
5. **P3 cleanup**: delete the orphaned render path + duplicate logic; surface or
   remove the menu heading (#10, #11, #12).
6. **P2 tests**: add scoping, permission, chart, switcher, drilldown tests (#13).
7. **P3 depth**: per-type widgets, period filter, export, dedicated radiology key (#14).
