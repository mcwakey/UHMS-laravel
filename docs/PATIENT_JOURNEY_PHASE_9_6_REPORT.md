# Phase 9.6 — Notifications, Scheduled Escalation & Auto-Cleanup

- 9.1 where? · 9.2 why? · 9.3 who acts & where? · 9.4 which dept blocks + SLA? ·
  9.5 who owns it & when escalate?
- **9.6 → who gets notified, what escalates automatically, and what stale records get cleaned?**

Makes journey coordination **proactive** — without a user opening the worklist —
while staying safe, non-spammy and auditable. No visit-status/workflow/KPI/dashboard
changes; derived journey data is never duplicated.

## 1. Notification events

[`JourneyHandoffNotificationEvent`](app/Enums/JourneyHandoffNotificationEvent.php) — 9
events (assigned/claimed/acknowledged/resolved/escalated/critical/stale_dismissed/
unassigned_near_breach/unassigned_breached), each with a translatable title, icon and
`NotificationPriority`.

## 2–3. Notification service + recipient resolver

[`JourneyHandoffNotificationService`](app/Services/Journey/JourneyHandoffNotificationService.php)
**reuses** the app's `NotificationService` (built-in dedupe, channel preferences,
quiet-hours, safe failure). [`JourneyNotificationRecipientResolver`](app/Services/Journey/JourneyNotificationRecipientResolver.php)
resolves capability-/department-scoped, **bounded** recipients (assignee, assigner,
eligible destination staff ≤ 8). Actor is never self-notified; messages carry **no
patient identifiers**; the action link is the capability-filtered worklist. See
[docs/journey/notifications.md](docs/journey/notifications.md).

## 4. Assignment-service hooks

`claim` (self → no one) / `assignTo` / `acknowledge` / `resolve` / `dismissStale` /
`recordEscalation` fire the matching notification **only after** the DB change
succeeds — audit intact, no notification on failure or no-op.

## 5–6. Scheduled command + auto-cleanup

[`journey:handoffs:escalate`](app/Console/Commands/JourneyEscalateHandoffsCommand.php)
— idempotent, bounded (`--limit`, chunked, eager-loaded). Re-derives each active
assignment's handoff, **auto-dismisses stale rows** (`dismissed_at` + note + audit +
assignee notice) and **persists risen escalation** (+ notify + audit). Options
`--dry-run --limit --department --cause --critical-only`. See
[docs/journey/escalation-command.md](docs/journey/escalation-command.md).

## 7. Scheduler

Registered in `routes/console.php`, gated by `journey.handoff_escalation.enabled`,
`withoutOverlapping()->onOneServer()`, configurable frequency (default
`everyFiveMinutes`).

## 8. Notification UI

**Reused** — journey notifications flow through the existing notifications table with
a translated title/message + `action_url` to the worklist, so they render in the
current notification UI with the `CLINICAL_TASKS` module styling. No new UI built.

## 9. Worklist badges

"New" badges on recently-assigned / recently-escalated rows (derived from
`assigned_at` / `last_escalated_at`, ≤ 15 min) plus the existing escalation badge —
no redesign, no extra queries.

## 10. Dashboard summary

The `journey_insight` banner gains an **"N assigned to you"** chip (deep-linked to the
*Assigned To Me* tab) alongside the 9.5 escalation badges. Cost: **+1 cached count
query** (`myActiveAssignmentCount`); escalation counts reuse the existing pass.

## 11. Preferences

`NotificationService` already honours per-module/per-user channel preferences +
quiet-hours, which journey notifications inherit under `CLINICAL_TASKS`. Fine-grained
per-event journey preferences are **deferred** (documented defaults: assigned/
critical/supervisor on, resolved off).

## 12. External channels

Config flags `journey.notifications.{in_app,email,sms}` (in-app on; email/sms **off**).
No new SMS/email infrastructure; flags only ride existing configured providers. No
external sending in tests.

## 13. Audit

`JOURNEY_HANDOFF_NOTIFIED` (event + recipient_user_ids + sent), `JOURNEY_HANDOFF_ESCALATION_RUN`
(command_run_id + dry_run + counts), plus the per-action escalate/dismiss audits.
`php artisan logs:audit` → **MISSING_LOG 0**.

## 14. Tests

`JourneyEscalationCommandTest` (9), `JourneyHandoffNotificationTest` (5), extended
`JourneyWorklistRefreshTest` (2) — **16 new; 91 journey tests total**: dry-run no-op,
escalate-to-critical, no-duplicate-on-repeat, stale-dismiss, resolved-skip,
cause/limit filters, run-audited, escalation-notifies-assignee; assignee notified +
actor not + unauthorized not + critical-unassigned + resolved-by-other + dedupe;
refresh shows assignment/escalation + does not mutate escalation state.

## 15. Query impact

- **Dashboard:** **+1** cached query (`journey_my_assignments`) beyond 9.5; measured
  19–29 (< 40).
- **Command:** 1 id query + chunks of 100 eager-loaded; respects `--limit`.
- **Worklist refresh:** unchanged from 9.5 — **no GET writes** (escalation persisted
  only by explicit actions or the command, never by polling/display).
- **Notifications:** batched recipient lookups (`notifyUsers`), capped at 8, deduped.
- **Verification:** 91 journey + 65 dashboard tests pass · parity OK · localisation
  audit 0 · logs:audit no gaps · views compile · routes registered · `git diff
  --check` clean.

## 16. Documentation

[PHASE_9_6_REPORT](docs/PATIENT_JOURNEY_PHASE_9_6_REPORT.md) · [notifications.md](docs/journey/notifications.md) ·
[escalation-command.md](docs/journey/escalation-command.md).

## Remaining opportunities (Phase 9.7+)

- **Per-event notification preferences** + a journey notification settings screen.
- **Unassigned-handoff sweep** in the command (notify critical unassigned without a row).
- **Real department-supervisor mapping** (a head/manager field) for precise routing.
- **Websocket/SSE** push to replace worklist polling.
- **Escalation analytics**: time-to-acknowledge / time-to-resolve / re-open rates.
