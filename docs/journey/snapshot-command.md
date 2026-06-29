# Journey Analytics Snapshot Command (Phase 9.8)

```bash
php artisan journey:analytics:snapshot [--date=] [--from= --to=] [--granularity=day] [--cause=] [--dry-run] [--limit=]
```

Builds **aggregate** `journey_flow_snapshots` rows from live handoff data + assignment
lifecycle metadata. Idempotent (upsert by bucket key), bounded, dry-run safe. Defaults
to today.

## What a snapshot captures

Per `(snapshot_date, granularity, from_type → to_type, cause, sla_status)` bucket — two
row kinds:

- **Active-state** rows (`sla_status` ∈ within/near_breach/breached/critical_breach):
  point-in-time pressure from the current active handoffs (`handoff_count`, breached,
  critical, elapsed, unassigned/assigned/acknowledged). Captured **only for today** —
  past active state cannot be reconstructed.
- **Resolved-lifecycle** rows (`sla_status` = `resolved`): the day's resolved
  assignments — `resolved_count` + `total_time_to_acknowledge_minutes` +
  `total_time_to_resolve_minutes`. Accurate historically (keyed on `resolved_at`).

The query service sums columns across both kinds, so resolved rows contribute to
resolution/ack-time metrics without inflating the handoff denominator (they carry
`handoff_count = 0`).

## Storage safety

**No patient-identifiable data** — no patient names, visit numbers or journey
timelines. Aggregate counts only. Snapshots are type-level (department ids are nullable
and currently unused; per-department snapshots are a future option).

## Scheduler

`routes/console.php`, gated by `journey.analytics_snapshot.enabled`, daily at
`daily_time` (default 00:30), `withoutOverlapping()->onOneServer()`. For accurate daily
resolved metrics run `--date=yesterday` after midnight; the default (today) keeps the
active-pressure snapshot fresh.

## Output

```
Range: 2026-06-29 → 2026-06-29  Granularity: day  Rows generated: 18  Rows upserted: 18  Departments: 9  Causes: 3
```

## Audit

A non-dry run logs `JOURNEY_ANALYTICS_SNAPSHOT_RUN` (`command_run_id`, date range,
granularity, rows_generated, rows_upserted, dry_run).
