# Extension Guide (Task 7)

How to extend the dashboard **without reverse-engineering it**. Each recipe touches a
small, predictable set of files. After any change run:

```
php artisan view:cache              # blades compile
php artisan test tests/Feature/Departments
php scripts/localisation-parity-check.php && php scripts/localisation-audit.php
```

---

## Add a new department TYPE

Most types already resolve via the registries' `default`/`DEFAULT_PROFILE`, so a new
`DepartmentType` case usually "just works" with the generic showcase. To give it a
first-class board:

1. `app/Enums/DepartmentType.php` — add the case.
2. `DepartmentDashboardLayoutRegistry::PROFILES` — add a card profile (`primary_cards`,
   `secondary_cards`, `main_queue`, `side_cards`). Add a `MENU_HEADINGS` entry.
3. `lang/{en,fr}/departments.php` — `dashboards.{type}.name` (+ menu-profile template
   string if new).
4. `DepartmentDashboardRegistry::keyForType` — map the type to a dashboard **key**
   (reuse an existing key, e.g. `consultation`, to reuse its showcase, or add a new key).
5. If a new key: add `resources/views/admin/dashboards/department/types/{key}.blade.php`
   (copy an existing one — set `$dashboardPersonalization` + include `_chrome` +
   `layouts.{key}_showcase`) and a `layouts/{key}_showcase.blade.php`.
6. Capabilities: add the type's value to the relevant `DepartmentDashboardCapabilityService::PROFILES[...]['types']`.
7. Chart registry, quick action registry, identity widget, alert service: add a
   `match` arm (or rely on `default`).
8. Theme: `DepartmentDashboardThemeRegistry` (or inherit the default).

## Add a new KPI (metric card)

1. `DepartmentDashboardDataService::metricCard()` — add a `match` arm computing the
   value (use `countTable` / `sumTable` / the trend repo; **scope by department**).
2. `DepartmentMetricDefinitionService::METRIC_CAPABILITIES` — map it to a capability
   (or omit for always-visible).
3. Add the key to a type's `primary_cards`/`secondary_cards` in the layout registry.
4. `lang/{en,fr}/dashboards.php` — `metrics.{key}` label.
5. (Optional) `DepartmentDashboardDrilldownUrlBuilder::definition()` — a drilldown
   route + filters. (Optional) `metricSpark()` spec for an inline sparkline.

## Add a new chart

1. `DepartmentDashboardChartService::chartFor()` — add a `match` arm + a builder
   (reuse `dailyCountDataset` for trends or `statusBreakdown` for donuts).
2. `DepartmentDashboardChartRegistry::chartServiceChartsFor()` — return the new key
   for the types that should build it. **A chart is only built if listed here.**
3. Reference it in the showcase: `@include('...chart-card', ['chart' => $charts['{key}']])`.
4. `lang/{en,fr}/dashboards.php` — `charts.{key}` title.

## Add a new capability

1. `DepartmentDashboardCapabilityService::PROFILES` — add `['permissions' => [...],
   'types' => [...]]`. Verify the permission names exist (they're checked against the
   seeded set).
2. `DepartmentMetricDefinitionService` — point the relevant metrics at it.
3. Gate any section/quick action on it via `capabilities->can($user, '{capability}')`.

## Add a new operational widget / alert

- Widget: add a `match` arm in `DepartmentIdentityWidgetBuilder::build()` returning
  `assemble(...)` (status + metrics from `cardValue(...)`). Add `widget.{key}`,
  `widget.status.*`, `widget.metric.*` lang keys.
- Alert: add a threshold rule in `DepartmentAlertService::alertsFor()` and an
  `alerts.{key}` lang key. **Derive from existing card values — no queries.**

## Golden rules

- Reuse already-computed **card values** and the **trend repository**; never add per-day
  loops or raw `Schema::` calls (use `DepartmentSchemaCache`).
- Gate value + drilldown + action + widget on the **same capability**.
- All strings via `lang/{en,fr}` with parity; audit must stay 0.
- Keep query count < 40; the cache and `DepartmentDashboardArchitectureTest` will catch
  regressions and resurrected dead code.
