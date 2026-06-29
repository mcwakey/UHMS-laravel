# Journey Unassigned Sweep (Phase 9.7)

Makes critical handoffs visible **before** an assignment row exists.

```bash
php artisan journey:handoffs:escalate --include-unassigned [--dry-run] [--limit=N] [--department=ID] [--cause=CODE] [--critical-only]
```

When `--include-unassigned` is set, after the normal assignment escalation pass the
command also scans **active near-breach+ handoffs that have no assignment row** and
notifies the destination supervisor (or eligible staff / oversight) for each.

## How it works

`JourneyHandoffWorklistService::unassignedBreachedHandoffs()`:
1. Light candidate scan (quickCause + SLA) → near-breach+ visits.
2. Excludes visits that already have an active assignment (one query).
3. Full-resolves only the top rows; keeps cross-department, near-breach+ handoffs.

For each, `notifyCriticalUnassigned()` routes via the supervisor resolver and
**deduplicates** on a stable key:

```
unassigned:{visit}:{cause}:{from_department}:{to_department_or_type}:{sla_status}
```

So repeated sweeps don't spam, but a **worsening SLA** (breached → critical_breach)
notifies once more. Resolved/no-longer-active handoffs drop out naturally.

## Important

- **No assignment rows are created** for delayed visits — dedupe is handled by the
  notification key, not by persisting state.
- Idempotent, bounded by `--limit`, dry-run safe (no mutation, no notifications).
- Capability-aware recipients, capped, never inactive.

## Output

```
Checked: 420  Escalated: 37  Critical: 8  Dismissed stale: 12  Unassigned: 19  Notifications: 51  Skipped: 363
```

The scheduler (`routes/console.php`) runs the base command every 5 min; enable the
unassigned pass there by adding `--include-unassigned` if desired.
