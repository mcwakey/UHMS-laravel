# Department Dashboard — Phase 8.2: Performance & Query Optimization

Objective: make the dashboard fast **without changing what users see** — fewer
queries, less SQL time, lower load. No layout/KPI/theme/widget changes.

## Task 1 — Baseline (before optimization)

Measured by building the controller payload per dashboard with `DB::enableQueryLog()`
against the seeded dev database.

| Dashboard | Queries | SQL time (ms) |
|-----------|--------:|--------------:|
| consultation   | 165 | 189.1 |
| investigation  | 190 | 233.2 |
| pharmacy       | 209 | 234.7 |
| radiology      | 190 | 287.3 |
| administrative | 178 | 223.2 |
| **avg**        | **186** | **233** |

Root causes (from the gap analysis):
- `Schema::hasTable()` / `Schema::hasColumn()` re-queried `information_schema` on
  every metric/scope/chart call (the dominant cost).
- The chart service built **all 7 charts** (each a 7-day loop) regardless of which
  the showcase renders; 4 of them are never used by any showcase.
- Per-card sparklines and `trends()` ran 7-iteration query loops.
- `trends()` duplicated `activity_trend`; `activity_trend` ≡ `service_usage_trend`.

## After optimization

| Dashboard | Queries before | Queries after¹ | SQL before (ms) | SQL after (ms) |
|-----------|---------------:|---------------:|----------------:|---------------:|
| consultation   | 165 | 29 | 189.1 | 27.7 |
| investigation  | 190 | 19 | 233.2 | 11.6 |
| pharmacy       | 209 | 24 | 234.7 | 14.8 |
| radiology      | 190 | 18 | 287.3 |  9.5 |
| administrative | 178 | 15 | 223.2 |  7.6 |
| **avg**        | **186** | **21** | **233** | **14** |

¹ Steady-state (warm process-static schema cache). The first request in a fresh
PHP worker also pays a one-time ~13-query schema warm-up; every request after reuses it.

**~9× fewer queries, ~16× less SQL time.** Target (20–30) met; most dashboards hit
the stretch goal (15–20).

### Cache hit (Task 3/6)

With `DepartmentDashboardCacheService` enabled, a repeat load of the same dashboard
by the same user within the 30s TTL:

```
pharmacy  cold load: 37 queries   →   cached load: 1 query
```

The cached payload is served whole; only context resolution runs. Auto-expires
after 30s; isolated per `department_id` + dashboard key + user.

### Charts eliminated per department type (Task 2)

Before, the chart service built **all 7** charts every load. After, it builds only
what the showcase renders:

| Type group | Charts built (was 7) | Eliminated |
|---|---|---|
| consultation, emergency, theatre, ward, records, generic | `activity_trend`, `queue_status_breakdown` | 5 |
| investigation, radiology, blood_bank | `activity_trend`, `request_status_breakdown` | 5 |
| pharmacy, stores | `activity_trend` (+ data-service stock donut) | 5–6 |
| finance, administrative | `activity_trend` | 6 |

Four charts were **never** rendered by any showcase and are now never built at all:
`service_usage_trend`, `stock_usage_trend`, `department_workload_by_day`,
`revenue_trend`.

## What changed (code)

| Task | Change |
|------|--------|
| 7 | `DepartmentSchemaCache` — process-static memo of `hasTable`/`hasColumn` (24 call sites). This removed the dominant query share. |
| 4/5 | `DepartmentTrendRepository::dailySeries()` — one `GROUP BY DATE` query per series; used by chart service trends **and** KPI sparklines. |
| 2/9 | `DepartmentDashboardChartRegistry` — chart service builds only the type's charts; data service adds the stock donut only for stock types. |
| 8 | `trends()` now derives from the already-built `activity_trend` (0 extra queries); the duplicate `activity_trend`/`service_usage_trend` and the per-day `tableValue()` loop were removed. |
| 3/6 | `DepartmentDashboardCacheService` — 30s per-(department, type, user) payload cache, wired into the controller; disabled in tests by default (opt-in for the perf test) to avoid reused-ID collisions. |
| 10 | `DepartmentDashboardPerformanceTest` — query budget, chart-registry, single-query trend, cache-hit. |

## Safety checks

- ✅ 57 department tests pass (53 prior + 4 new perf tests); KPI values, charts,
  queues and layouts unchanged (the value-asserting suites still pass verbatim).
- ✅ Chart datasets identical (same 7-day values; grouped query == per-day loop).
- ✅ EN/FR parity OK · localisation audit 0 · views compile · `route:list` OK.

## Remaining optimization opportunities

- **Per-section TTLs**: a single 30s payload TTL is used (the strictest section
  window) for simplicity/freshness; splitting into metrics 60s / charts 120s /
  queues 30s would raise hit rates.
- **Eager-load** the `current_department`/`departments` relations once (resolver
  already `loadMissing`s) and pass IDs to avoid re-resolution on cached loads.
- **Combine metric counts**: several single-count metrics on the same table could
  be folded into one grouped/conditional-aggregate query.
- **Dead code**: `DepartmentDashboardChartService::fallbackForType()` and the unused
  `revenueTrend`/`serviceUsageTrend`/`workloadByDay`/`stockUsageTrend` chart builders
  can be removed (kept for now to minimise churn).
- **Cache the rendered HTML fragment** (full-page or section) for read-heavy boards.

