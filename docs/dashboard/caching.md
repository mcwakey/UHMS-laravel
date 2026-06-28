# Caching & Performance

Baseline (pre-8.2) was ~165–209 queries per load. It is now **11–13** (budget: < 40),
via three layers. None of them changes what the user sees.

## 1. Schema metadata cache — `DepartmentSchemaCache`

`Schema::hasTable()` / `hasColumn()` hit `information_schema` and were the dominant
cost (called dozens of times per request). `DepartmentSchemaCache` memoizes them in a
**process-static** array — the schema doesn't change at runtime, so each table/column
resolves once. Always use it (not `Schema::`) inside the dashboard services.

## 2. Shared trend repository — `DepartmentTrendRepository`

`dailySeries()` / `dailySeriesWithLabels()` return a 7-day series with **one
`GROUP BY DATE(...)` query** instead of a 7-iteration loop. Used by both the chart
trends and the KPI sparklines, so the same shape of work runs once.

## 3. Payload cache — `DepartmentDashboardCacheService`

`remember($context, fn)` caches the whole assembled payload for **30s**, keyed per
**department + dashboard key + user** (`dept_dashboard:{id}:{key}:u{userId}`). Per-user
because metric/stock/revenue visibility depends on the viewer's capabilities — a
cached payload is never shared across users. Entries auto-expire; no manual clearing.

Repeat load of the same dashboard ⇒ **1 query** (the cache read).

### Test safety

The cache is **disabled during automated tests by default** (reused, rolled-back
department IDs would collide). Tests that need it on call
`DepartmentDashboardCacheService::enable()` / `::reset()` (see
`DepartmentDashboardPerformanceTest`).

## Build-only-what's-needed

`DepartmentDashboardChartRegistry` makes the chart service build only the 1–2 charts a
type's showcase renders (was all 7). `DataService::trends` is derived from the already
built `activity_trend` chart (no extra query). Restricted metrics **skip** their query
entirely.

## Guardrails

- `DepartmentDashboardPerformanceTest`: query budget < 40, chart registry, single
  grouped trend query, cache hit reduces queries.
- Rule for new code: reuse existing card values and the trend repository; **do not add
  per-day loops or un-memoized `Schema::` calls**.
