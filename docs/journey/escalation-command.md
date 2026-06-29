# Journey Escalation Command (Phase 9.6)

```bash
php artisan journey:handoffs:escalate [--dry-run] [--limit=500] [--department=ID] [--cause=CODE] [--critical-only]
```

A scheduled, **idempotent** sweep over **active** handoff assignments
(`unassigned/assigned/acknowledged`). For each it re-derives the current handoff
(Phase 9.1–9.4) and:

1. **Auto-dismisses stale rows** — if the visit is terminal/completed, the cause no
   longer applies, or the from/to identity changed, the assignment is marked
   `dismissed` (with `dismissed_at`, a note, audit, and an assignee notification).
2. **Persists risen escalation** — computes the level (`JourneyEscalationService`)
   and, only when it has risen, persists it + notifies destination staff/assignee +
   audits. Already-at-level rows are skipped (no duplicate escalation).

It never mutates resolved/dismissed assignments and never touches visit status or
clinical workflow.

## Options

| Option | Effect |
|---|---|
| `--dry-run` | Report only — **mutates nothing**, sends nothing, writes no run-audit |
| `--limit=N` | Max assignments processed (default 500) — bounded |
| `--department=ID` | Only destination department `ID` |
| `--cause=CODE` | Only this delay cause (e.g. `awaiting_lab_result`) |
| `--critical-only` | Only act on critical escalations |

## Output

```
Checked: 420  Escalated: 37  Critical: 8  Dismissed stale: 12  Notifications: 45  Skipped: 363
```

## Scheduler

Registered in `routes/console.php`, gated by config and protected with
`withoutOverlapping()->onOneServer()`:

```php
// config/journey.php
'handoff_escalation' => [
    'enabled'   => env('JOURNEY_HANDOFF_ESCALATION_ENABLED', true),
    'frequency' => 'everyFiveMinutes',
    'batch_limit' => 500,
],
```

Set `JOURNEY_HANDOFF_ESCALATION_ENABLED=false` to fall back to Phase 9.5's
on-view escalation. No queue dependency; notifications use the existing
NotificationService.

## Performance

One bounded id query + chunks of 100 (eager-loaded `assignedTo`, `visit`). Per row
the re-derivation reuses loaded relations. Respect `--limit` for very large backlogs.

## Audit

A non-dry run logs `JOURNEY_HANDOFF_ESCALATION_RUN` with a `command_run_id`,
`dry_run` flag and the summary counts; each escalation/dismiss/notification is
individually audited by the assignment + notification services.
