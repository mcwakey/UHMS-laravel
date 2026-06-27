# Department Dashboards — Preclinic-style UI Upgrade (Progress Tracker)

Living checklist for bringing the department dashboards up to the Preclinic
template look (the screenshots: rich KPI cards with sparklines, colored stat
tiles, gradient-area trends, donuts with a centre total, and avatar list cards).

**Keep this file updated as each type is converted so nothing is left out.**

---

## 1. Architecture (how a department dashboard renders)

```
DepartmentDashboardController@index
  → DepartmentContextResolver::resolve()            (DTO: type, name, id, dashboard_key, theme…)
  → DepartmentDashboardDataService::build()          (payload: cards, queue, charts, stock, services…)
  → view: admin/dashboards/department/types/{dashboard_key}.blade.php   (per-type file; fallback types/generic)
```

Two rendering paths for a type file:

- **Family path (default):** `types/{key}` → `dashboard-shell` → `dashboard-body`
  → `_chrome` + `layouts/{layout_family}` (one of 11 shared families).
- **Bespoke showcase path (Preclinic):** `types/{key}` → sets
  `$dashboardPersonalization` → `_chrome` → `layouts/{key}_showcase` (a
  hand-built, type-specific body).

`_chrome` = hero + personality command strip + preview banner + shared styles
(extracted so bespoke and family paths share it with **zero duplication**).

---

## 2. Global design elements (apply to ALL dashboards) — ✅ DONE

| Element | Partial | Status |
|---|---|---|
| KPI card: value + **±% delta badge** + inline **sparkline** (area for money, mini-bars for counts) + "in last 7 days" | `partials/kpi-card.blade.php` + `DataService::metricSpark()` | ✅ |
| Mid-row **stat tiles**: colored rounded-square icon + label + value | `partials/mini-kpi-card.blade.php` | ✅ |
| Trend chart: smooth **gradient area**, rounded bars, clean axes | `partials/chart-card.blade.php` | ✅ |
| Donut: **centre total** ("Total / N") + slice labels | `partials/chart-card.blade.php` (shared ApexCharts JS) | ✅ |
| Rotating KPI background art | `partials/kpi-card.blade.php` | ✅ |

### Reusable building blocks (use these when converting a type)
- `layouts/_primary-cards.blade.php` — KPI row (sparkline cards).
- `layouts/_secondary-cards.blade.php` — stat-tile row (`$col` overridable).
- `_list-card.blade.php` — **rich avatar list** (avatar + name/meta left, status badge right, "View all"). ✅ NEW
- `chart-card.blade.php` — area/line/bar trend (ApexCharts).
- `status-breakdown-card.blade.php` — donut + legend bars (centre total).
- `services-card`, `stock-usage-card`, `quick-actions`, `activity-list`, `work-queue-card`.

---

## 3. Per-type conversion status (15 dashboard keys)

Legend: ✅ bespoke Preclinic showcase · ⬜ still on shared family layout (functional,
globally-upgraded cards/charts, but not yet a hand-built showcase).

| # | dashboard key | layout family | bespoke showcase | status |
|---|---|---|---|---|
| 1 | **consultation** | clinical_queue | `layouts/clinical_showcase` | ✅ done |
| 2 | **pharmacy** | dispensing_stock | `layouts/dispensing_showcase` | ✅ done |
| 3 | **emergency** | emergency_command | `layouts/emergency_showcase` | ✅ done |
| 4 | **investigation** | diagnostic_workbench | `layouts/investigation_showcase` | ✅ done |
| 5 | **theatre** | surgery_board | `layouts/surgery_showcase` | ✅ done |
| 6 | **admission** | ward_board | `layouts/ward_showcase` | ✅ done |
| 7 | **billing** | finance_control | `layouts/finance_showcase` | ✅ done |
| 8 | **accounting** | finance_control | `layouts/finance_showcase` | ✅ done |
| 9 | **claims** | finance_control | `layouts/finance_showcase` | ✅ done |
| 10 | **stock** | stores_inventory | `layouts/stores_showcase` | ✅ done |
| 11 | **blood_bank** | diagnostic_workbench | `layouts/blood_bank_showcase` | ✅ done |
| 12 | **reception** | records_office | `layouts/records_showcase` | ✅ done |
| 13 | **hr** | generic_department | `layouts/generic_showcase` | ✅ done |
| 14 | **management** | generic_department | `layouts/generic_showcase` | ✅ done |
| 15 | **generic** | generic_department | `layouts/generic_showcase` | ✅ done (fallback) |

**All 15/15 dashboard keys now render a bespoke Preclinic-style showcase.**

> Note: dashboard **keys** are coarser than the 19 department **types**
> (e.g. radiology shares the `investigation` key; treatment/procedure share
> `consultation`). Naming/identity stays per-type via the layout registry.
>
> The 11 shared **family layouts** (`layouts/clinical_queue`, `emergency_command`,
> …) and `dashboard-shell` / `dashboard-body` remain in place as the safe fallback
> path (still backed by `$layout_family` + a test). Bespoke showcases are the
> primary render path now that every type file is customised.

---

## 4. Showcase recipe (to convert the next type)

1. `types/{key}.blade.php`:
   ```blade
   @extends('layouts.app')
   @section('title', $dashboard['title'] ?? __('dashboards.department.department_dashboard'))
   @section('content')
   @php $dashboardPersonalization = ['key' => '{key}']; @endphp
   @include('admin.dashboards.department.partials._chrome')
   @include('admin.dashboards.department.partials.layouts.{key}_showcase')
   @endsection
   ```
2. `partials/layouts/{key}_showcase.blade.php` — arrange the reusable blocks for
   that type (queue-first, finance-first, imaging-first…), reusing `_list-card`,
   `chart-card`, `status-breakdown-card`, KPI/tile rows.
3. If the type needs a **new chart/data** (e.g. pharmacy's stock-alert donut),
   add it to `DataService::build()`'s `$charts` (reuse existing scoped queries).
4. Localise any new strings in `lang/{en,fr}/dashboards.php` (keep parity).
5. Add a render smoke test in
   `tests/Feature/Departments/DepartmentDashboardDesignDifferentiationTest.php`
   (`view('…layouts.{key}_showcase', $data)->render()` asserts no error + key markup).
6. Verify: `view:cache`, parity check, localisation audit (0), dept test suites.

---

## 5. Data added for showcases

- **Sparklines/deltas** — `DataService::metricSpark()` builds a 7-day daily
  series for time-based metrics (visits, activity, appointments, revenue,
  requests, dispensed, invoices, stock issues); attaches `spark` + `delta` +
  `delta_dir` to primary cards. Static/restricted metrics get none.
- **Stock-alert donut** — `DataService::stockStatusBreakdown()` → In-stock vs
  Low-stock doughnut (reuses `stockCount()`), injected as
  `charts['stock_status_breakdown']`. Restricted without stock permission.

New lang keys: `dashboards.department.{total,in_last_7_days}`,
`dashboards.department.charts.{stock_status,in_stock,low_stock,stock_items}` (EN+FR).

---

## 6. Verification status (current)

- ✅ `view:cache` compiles all partials/showcases.
- ✅ EN/FR parity OK; localisation audit **0 active runtime candidates**.
- ✅ Department dashboard test suites pass (incl. consultation + pharmacy + emergency render smoke tests, sparkline/delta test).
- ✅ `git diff --check` clean. Nothing committed yet.

## 7. Manual testing (reseed)

`database/seeders/DepartmentTypeShowcaseSeeder.php` guarantees **one department +
one login user per department type** and **corrects mis-typed departments** (dev
data had Pharmacy stored as `investigation`, Emergency as `consultation`, Billing
as `administrative`, wards as `administrative`, etc.).

```
php artisan db:seed --class=DepartmentTypeShowcaseSeeder
php artisan optimize:clear     # serve fresh views
```

- Login: **`<type>@uhms.local`** / **`password`** (e.g. `pharmacy@uhms.local`).
- Then open **`/dashboard`** (→ `admin.my-dashboard`). The dashboard you see is
  chosen by **your user's department type**.
- Reachable bespoke showcases via these logins: consultation, emergency, pharmacy,
  investigation, theatre, admission, blood_bank, accounting, reception, stock,
  management (some types share a key — e.g. ambulance→emergency, procedure→theatre,
  finance→accounting, radiology→investigation, treatment→consultation).

> If a dashboard looks "plain", confirm the logged-in user actually has a
> department (no department → generic fallback) and that `optimize:clear` ran.

## 8. Known gaps / next

- ✅ All 15 dashboard keys converted to bespoke showcases.
- Deeper per-type **data widgets** are the next layer: real bed-occupancy gauge
  (ward), transfusion/expiry safety (blood bank), surgery slot timeline (theatre),
  receivables ageing (finance), turnaround-time SLA (investigation). The showcase
  shells are in place to drop these in.
- Consider promoting `_list-card`'s `view_all_route` by having the data builders
  pass a queue "view all" route per type.
- Optional cleanup: `dashboard-shell.blade.php` is now unreferenced (all type files
  are bespoke). Left in place as a harmless fallback helper; safe to remove later.
