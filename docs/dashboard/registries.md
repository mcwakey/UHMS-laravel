# Registries (Task 5 — consolidation)

The dashboard's behaviour is driven by small, single-responsibility config classes.
Keeping each one focused is what makes the system extensible without touching the
engine. There is **no overlap** between them.

| Registry / service | Keyed by | Returns | Used by |
|---|---|---|---|
| `DepartmentDashboardLayoutRegistry` | department **type** | card profile (`primary_cards`, `secondary_cards`, `main_queue`, `side_cards`), `name_key`, `menu_heading_template` | DataService (which metrics), MenuProfileService (heading), name |
| `DepartmentDashboardChartRegistry` | department **type** | which chart-service charts to build; `needsStockStatus()` | ChartService, DataService |
| `DepartmentQuickActionRegistry` | department **type** | `[label_key, icon, route, capability]` list | DataService::quickActions |
| `DepartmentMetricDefinitionService` | **metric key** | the governing capability (or null) | DataService (gate), DrilldownUrlBuilder |
| `DepartmentDashboardCapabilityService` | **capability** | `can(user, capability)` — permissions OR department-type workflow | everywhere a value/action/widget is gated |

Adjacent single-purpose helpers (not "registries" but config-like):

| Class | Responsibility |
|---|---|
| `DepartmentIdentityWidgetBuilder` | the one operational widget per type (from card values) |
| `DepartmentAlertService` | alerts + operational status (from card values) |
| `DepartmentStatusPresenter` | raw status → localized label + colour + icon |
| `DepartmentDashboardThemeRegistry` | per-type theme (accent, icons, badge classes) |
| `DepartmentDashboardDrilldownUrlBuilder` | metric → drilldown route + filters |

## Why "type" appears in several registries

Layout, charts, and quick actions are all chosen by **department type** because a
type defines the workflow. They are intentionally separate files so each concern can
change independently (e.g. add a chart without touching the card profile). The
**type → key** mapping (`DepartmentDashboardRegistry::keyForType`) is what selects the
Blade `types/{key}` showcase; several types can share a key (radiology → investigation).

## Rules of thumb

- A registry is **pure config** — no DB queries, no request state.
- Visibility lives in exactly one place: `DepartmentMetricDefinitionService` (metric →
  capability) + `DepartmentDashboardCapabilityService` (capability → bool).
- If you find yourself duplicating a `match($type)` across two registries, that's a
  smell — they should still be separate, but double-check the responsibility split.
