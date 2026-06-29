# Journey Analytics (Phase 9.8)

Aggregate operational analytics over patient-flow performance — built from
`journey_flow_snapshots`, never from live scans.

## Pipeline

```
live handoffs + assignment lifecycle
   → journey:analytics:snapshot (daily)         [snapshot service]
   → journey_flow_snapshots (aggregate rows)     [no patient data]
   → JourneyAnalyticsQueryService (cached)        [summary/trend/rankings/matrix/compare]
   → /admin/journey/analytics                     [report page]
```

## Metric registry — `JourneyAnalyticsMetricRegistry`

Canonical keys + format + **documented formulas** (numerator / denominator):

| Metric | Formula |
|---|---|
| breach_rate | Σ breached_count / Σ handoff_count |
| resolution_rate | Σ resolved_count / Σ (assigned + acknowledged + resolved) |
| acknowledgement_rate | Σ (acknowledged + resolved) / Σ (assigned + acknowledged + resolved) |
| time_to_acknowledge_avg | Σ total_time_to_acknowledge_minutes / Σ resolved_count |
| time_to_resolve_avg | Σ total_time_to_resolve_minutes / Σ resolved_count |
| avg_wait_minutes | Σ total_elapsed_minutes / Σ handoff_count |

Counts (handoff_volume, sla_breaches, critical_breaches, unassigned, …) are plain sums.
Rates guard against a zero denominator (return 0 / "not enough data").

## Query service

`summary · trend · departmentRanking(to=blocking|from=waiting) · handoffPathRanking
(matrix) · causeBreakdown · slaBreakdown · comparePeriods`. Filters: date range,
cause, from/to type, sla_status, granularity, **scope_types** (capability scope).
Results are cached per scope+filter (`Cache::remember`), so there is **no
cross-user/scope leakage**; `_ttl <= 0` bypasses the cache (tests).

## Permissions

- `journey.oversight` → hospital-wide analytics (`scope_types = null`).
- Other users → restricted to their capability domains via `scope_types`; a handoff is
  visible only if its from OR to type is in scope.
- No capability + no oversight → **403**.

## Caching

`journey.analytics.cache_ttl` (dashboard, 5 min) / `report_cache_ttl` (reports,
30 min). Key includes the visibility scope + filters + date range + granularity.

See [sla-reports.md](sla-reports.md) and [snapshot-command.md](snapshot-command.md).
