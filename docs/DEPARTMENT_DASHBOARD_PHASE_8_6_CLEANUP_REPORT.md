# Department Dashboard — Phase 8.6: Architecture Cleanup & Maintenance

Final phase. Goal: **longevity** — remove dead code, simplify the architecture, and
document it so future developers maintain and extend it safely. No UI / KPI /
permission / performance changes.

## 1. Files removed (14)

The Phase 6–8.1 "layout family" render path was fully superseded by the per-key
showcase system, so it (and everything only it referenced) is gone:

- `partials/show.blade.php`, `partials/dashboard-shell.blade.php`, `partials/dashboard-body.blade.php`
- 11 family layouts: `partials/layouts/{clinical_queue, emergency_command,
  diagnostic_workbench, imaging_workbench, surgery_board, ward_board, dispensing_stock,
  finance_control, stores_inventory, records_office, generic_department}.blade.php`

Kept (still used by showcases): `layouts/_primary-cards`, `layouts/_secondary-cards`,
all `layouts/{key}_showcase`, and every low-level card partial.

## 2. Dead code removed from services

| Where | Removed | Why |
|---|---|---|
| `DepartmentDashboardLayoutRegistry` | `LAYOUT_FAMILIES`, `layoutFamilyFor()`, `heroVariantFor()`, `hasLayoutFamilyForEveryDepartmentType()`, the `layout_family`/`hero_variant` columns; `FAMILIES`→`MENU_HEADINGS` | render path retired; only the menu-heading template was still used |
| `DepartmentDashboardController` | `$data['layout_family']` | nothing renders it |
| `DepartmentDashboardChartService` | `fallbackForType()`, `serviceUsageTrend()`, `stockUsageTrend()`, `workloadByDay()`, `revenueTrend()`, `dailySumDataset()`, `restrictedDataset()` + their 4 `chartFor` cases; unused `DepartmentType`/`Schema` imports | no showcase renders these 4 charts; the chart registry never returns them |
| `DepartmentDashboardDrilldownUrlBuilder` | `permissionFor()` | unused since 8.4 unified gating moved to the data service |

`scopeDepartmentIfColumnExists()` was already gone (removed in 8.1).

## 3. Services consolidated

`DepartmentDashboardLayoutRegistry` shrank to its real job (card profile + name +
menu-heading). The chart service collapsed `dailyCountDataset`/`dailySumDataset`/
`dailyDataset` into a single trend builder. The five registries now have crisp,
non-overlapping responsibilities — documented in `docs/dashboard/registries.md`.

## 4. Documentation added — `docs/dashboard/`

| File | Contents |
|---|---|
| `README.md` | index + one-paragraph mental model + phase history |
| `architecture.md` | request flow, components, **dependency map** (Task 9), invariants |
| `registries.md` | the five registries and their single responsibilities (Task 5) |
| `capabilities.md` | capability profiles, metric→capability, unified gating |
| `caching.md` | payload cache, schema cache, trend repository |
| `intelligence.md` | operational widget, alerts, status |
| `extending.md` | **how to add a type / KPI / chart / capability / widget** (Task 7) |

## 5. Tests

- **Added** `DepartmentDashboardArchitectureTest` (5) — orphan views removed, type
  files + helpers exist, layout-family registry metadata gone, retired charts never
  built, no dead `permissionFor`.
- **Updated**: the `show.blade` compile test now renders the real showcase body; the
  `fallbackForType` and `layout_family`/`LAYOUT_FAMILIES` tests were removed/retargeted
  to the card-profile + name contract.

## 6. Final validation (Task 10)

- ✅ **78 department tests pass** (583 assertions).
- ✅ **Query count 11–13 (< 40), unchanged**; cache, KPI values and permissions
  unchanged.
- ✅ EN/FR parity OK · localisation audit 0 · views compile · `git diff --check` clean.

## Dashboard subsystem summary

One render path (`types/{key}` → `_chrome` → `{key}_showcase`), one place that
computes metric values (`DepartmentDashboardDataService`), one capability vocabulary
gating card+drilldown+action+widget, three caching layers, and config-only registries
for everything that varies by type. The engine is closed for modification and open for
extension via the recipes in `docs/dashboard/extending.md`.

## Remaining technical debt (small, documented)

- Unused `dashboards.department.charts.{service_usage_trend, stock_usage_trend,
  revenue_trend, workload_by_day}` lang keys retained (harmless; left to avoid parity
  churn — safe to delete in a focused pass).
- Route middleware can be stricter than a dashboard capability (workflow drilldown may
  403); aligning route gates with capabilities is a future enhancement.
- Operational widget thresholds are fixed constants; could become per-department config.
- Real turnaround/oldest-age widgets would need one lightweight timestamp aggregate
  each (deferred to keep "reuse existing values / no new queries").
