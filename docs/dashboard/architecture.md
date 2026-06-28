# Architecture & Dependency Map

## Request flow (Task 9)

```
HTTP GET /dashboard  →  redirect → admin.my-dashboard
        │
        ▼
DepartmentDashboardController@index
        │  resolves
        ▼
DepartmentContextResolver ──────────────► DepartmentDashboardContext (DTO)
  (DepartmentContextSwitcherService,        user, department, department_id,
   DepartmentDashboardRegistry,             department_type, dashboard_key,
   DepartmentDashboardResolver,             theme, is_preview, can_switch …)
   DepartmentMenuProfileService,
   DepartmentDashboardThemeRegistry)
        │
        ▼
DepartmentDashboardCacheService::remember(context, fn)      ← 30s payload cache
        │  (miss →)
        ▼
DepartmentDashboardDataService::build(context)
        │
        ├─ DepartmentDashboardLayoutRegistry  → card profile + name + menu heading
        ├─ DepartmentDashboardCapabilityService + DepartmentMetricDefinitionService
        │       → metric VISIBILITY (card value / drilldown / quick action / widget)
        ├─ metric values  (countTable / sumTable / stockCount …, schema-cached)
        ├─ DepartmentDashboardDrilldownUrlBuilder → drilldown URLs (capability-gated)
        ├─ DepartmentDashboardChartService(+Registry,+TrendRepository) → only needed charts
        ├─ DepartmentStatusPresenter        → queue/activity status badges
        ├─ DepartmentQuickActionRegistry    → type-specific actions (capability-gated)
        ├─ DepartmentIdentityWidgetBuilder  → the operational widget (from card values)
        └─ DepartmentAlertService           → alerts + operational status (from card values)
        │
        ▼
payload  →  view: types/{dashboard_key}.blade.php
        │            └─ _chrome (hero + priority-banner + identity-widget + personality strip)
        │            └─ layouts/{key}_showcase (cards, queues, charts, lists)
        ▼
HTML (Blade, Bootstrap 5, Tabler icons, ApexCharts)
```

## Components

| Layer | Class / file | Responsibility |
|---|---|---|
| HTTP | `Http/Controllers/Admin/Dashboard/DepartmentDashboardController` | Resolve context, cache+build payload, choose `types/{key}` view |
| Context | `Services/Department/DepartmentContextResolver` → `DepartmentDashboardContext` | Who/where/which dashboard; preview & switching |
| Build | `Services/Department/DepartmentDashboardDataService` | Assemble the whole payload (the only place metric values are computed) |
| Registries | see [registries.md](registries.md) | Pure config: layout/cards, charts, actions, capabilities, metric→capability |
| Security | `DepartmentDashboardCapabilityService` (+ `DepartmentMetricDefinitionService`) | One capability gates card+drilldown+action+widget |
| Performance | `DepartmentDashboardCacheService`, `DepartmentSchemaCache`, `DepartmentTrendRepository` | Payload cache, schema memo, one grouped query/trend |
| Intelligence | `DepartmentIdentityWidgetBuilder`, `DepartmentAlertService` | Operational widget, alerts, status — all from existing values |
| Presentation | `DepartmentStatusPresenter` + Blade under `resources/views/admin/dashboards/department/` | Localized badges, showcases, chrome |

## Render path (one path, no fallbacks)

`types/{key}.blade.php` → `@include('_chrome')` → `@include('layouts.{key}_showcase')`.

`_chrome` = hero + priority-banner + identity-widget + (optional) personality strip
+ shared styles. Showcases reuse the low-level partials: `_primary-cards`,
`_secondary-cards`, `kpi-card`, `mini-kpi-card`, `_list-card`, `chart-card`,
`status-breakdown-card`, `services-card`, `stock-usage-card`, `quick-actions`,
`activity-list`.

> The Phase 6–8.1 "layout family" path (`show` / `dashboard-shell` / `dashboard-body`
> + `layouts/{family}`) was **removed in 8.6**. There is now exactly one render path.

## Invariants (enforced by tests)

- Every metric is scoped by `department_id` (or a join to it); see `DepartmentDashboardDataService`.
- A card and its drilldown share one capability — never one visible without the other.
- The dashboard issues **< 40 queries** (typically 11–13) and is cached for 30s.
- All user-facing strings are translated; EN/FR keys stay in parity (audit = 0).
- `DepartmentDashboardArchitectureTest` keeps the orphan/dead code from returning.
