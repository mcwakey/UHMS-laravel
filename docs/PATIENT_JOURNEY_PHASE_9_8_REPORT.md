# Phase 9.8 — Journey Analytics, SLA Reports & Operational Performance

- 9.1–9.7 built live coordination (where/why/who/SLA/ownership/escalation/notify/route).
- **9.8 → how well is the hospital handling patient-flow delays over time?**

Turns live coordination into measurable operational performance — via **safe
aggregate snapshots**, never stored patient journeys. No visit-status/workflow/KPI/
dashboard changes.

## 1. Metric registry

[`JourneyAnalyticsMetricRegistry`](app/Services/Journey/JourneyAnalyticsMetricRegistry.php)
— 17 metrics with stable keys, display format and **documented formulas** (EN/FR
labels). Single source of metric meaning.

## 2–3. Aggregate snapshot table + model

`journey_flow_snapshots` — counts per `(date, granularity, from-type → to-type, cause,
sla_status)`. **No patient names / visit numbers / timelines.** Stores totals (not
averages) so the query service re-aggregates correctly. Model adds casts + scopes.

## 4–5. Snapshot service + command

[`JourneyAnalyticsSnapshotService`](app/Services/Journey/JourneyAnalyticsSnapshotService.php)
builds active-state rows (today) + resolved-lifecycle rows (historical) from live
handoffs + assignment metadata. `journey:analytics:snapshot` — idempotent (upsert),
bounded, dry-run safe, audited. See
[docs/journey/snapshot-command.md](docs/journey/snapshot-command.md).

## 6. Scheduler

`routes/console.php`, daily at `journey.analytics_snapshot.daily_time` (00:30),
`withoutOverlapping()->onOneServer()`, config-gated.

## 7,12–14,17. Query service + cache

[`JourneyAnalyticsQueryService`](app/Services/Journey/JourneyAnalyticsQueryService.php):
`summary · trend · departmentRanking · handoffPathRanking (matrix) · causeBreakdown ·
slaBreakdown · comparePeriods`. Includes **time-to-acknowledge / time-to-resolve**
(Task 13) and **period comparison** (Task 14, zero-denominator-safe). Results cached
per scope+filter — no cross-user leakage. See
[docs/journey/analytics.md](docs/journey/analytics.md).

## 8–11,15,16. Report page

`/admin/journey/analytics` (+ menu **Flow Analytics**): summary cards (with comparison),
SLA trend, top causes, blocking/waiting department rankings, handoff matrix — lightweight
CSS-bar + table charts, graceful empty states. **Capability-scoped**: oversight =
hospital-wide; department users restricted to their domains (`scope_types`); 403 for
unauthorized. See [docs/journey/sla-reports.md](docs/journey/sla-reports.md).

## 18. Export foundation

[`JourneyAnalyticsExportService`](app/Services/Journey/JourneyAnalyticsExportService.php)
→ aggregate CSV (matrix / departments / causes). Permission- and scope-gated. **No
patient-level export.**

## 22. Audit

`JOURNEY_ANALYTICS_SNAPSHOT_RUN` (command_run_id, date range, granularity, rows
generated/upserted, dry_run). `logs:audit` → **MISSING_LOG 0**.

## 20. Tests

`JourneyAnalyticsSnapshotTest` (4), `JourneyAnalyticsQueryTest` (6),
`JourneyAnalyticsReportTest` (3), `JourneyAnalyticsPermissionTest` (4) — **17 new; 126
journey tests total**: dry-run no-op, idempotent upsert, resolved-lifecycle capture,
run audited; breach-rate / ttack / ttresolve / dept ranking / cause filter / date range
/ period comparison; report renders + empty state + **no patient/visit columns in
export**; unauthorized 403, department scoping, oversight hospital-wide, cache scoped by
filter.

## Query impact

- **Dashboard: unchanged** (analytics is a separate page) — department dashboard stays
  ~28 (< 40).
- **Report page:** reads the snapshot table (grouped aggregation), cached per scope —
  never rebuilds snapshots on render.
- **Snapshot command:** one candidate query + one assignment query + a bounded resolved
  scan; `--limit` respected; **dry-run mutates nothing**.
- **Verification:** 126 journey + 65 dashboard tests pass · parity OK · localisation
  audit 0 · logs:audit no gaps · views compile · 10 routes · `git diff --check` clean ·
  no patient-level leakage.

## Remaining opportunities (Phase 9.9+)

- **Per-department (id-level) snapshots** + drill-down (the columns already exist).
- **Hourly granularity** snapshots + intraday trends.
- **Predictive analytics**: forecast breach risk / ETA from historical snapshots.
- **Median** (not just average) ack/resolve times via a percentile sketch.
- **Scheduled report digests** + richer charts (ApexCharts) and XLSX export.
