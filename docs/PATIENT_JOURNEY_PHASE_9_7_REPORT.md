# Phase 9.7 — Supervisor Routing, Notification Preferences & Coordination Hardening

- 9.1 where? · 9.2 why? · 9.3 who acts & where? · 9.4 which dept blocks + SLA? ·
  9.5 who owns it & when escalate? · 9.6 who's notified + auto-escalate + cleanup?
- **9.7 → who *exactly* should be notified, which notifications do they want, and how
  do we handle critical *unassigned* handoffs?**

Precision and configurability over noise. No visit-status/workflow/KPI/dashboard
changes; derived journey data is never duplicated.

## 1. Department supervisor mapping

`departments.supervisor_user_id` + `escalation_user_id` (nullable, indexed) — added to
the model, the **Department edit** form (active-only, ineligible rejected) and audited
on change. Multi-supervisor deferred. See
[docs/journey/supervisor-routing.md](docs/journey/supervisor-routing.md).

## 2–3. Supervisor resolver + routing

[`JourneySupervisorResolver`](app/Services/Journey/JourneySupervisorResolver.php):
supervisor → escalation user → oversight (critical) → eligible staff → none. The
notification service now routes escalation/critical/unassigned through it (instead of
broadcasting), always capped + active-only.

## 4–5. Notification preferences

`journey_notification_preferences` (per user+event) +
[`JourneyNotificationPreferenceService`](app/Services/Journey/JourneyNotificationPreferenceService.php):
`allows()` gates on global channel config → user row → default; per-request cached.
The notification service filters every recipient through it. See
[docs/journey/notification-preferences.md](docs/journey/notification-preferences.md).

## 6. Settings UI

`/admin/settings/journey-notifications` — a per-user event × channel grid; globally
disabled channels render disabled. EN/FR.

## 7–8. Unassigned sweep + dedupe

`journey:handoffs:escalate --include-unassigned` notifies near-breach+ handoffs that
have **no assignment row** — routed via the supervisor resolver, deduped on
`unassigned:{visit}:{cause}:{from}:{to}:{sla_status}` (worsening SLA notifies once
more). **No rows created** for delayed visits. See
[docs/journey/unassigned-sweep.md](docs/journey/unassigned-sweep.md).

## 9 / 15. Config

`escalation_policy` (prefer_supervisor, fallback, route_critical_to_oversight,
max_recipients=8) + `notification_digest` (deferred foundation).

## 10–11. Oversight permission + routing

`journey.oversight` (RoleSeeder → Super Admin/Admin via syncPermissions(all)). Critical
escalations route to oversight holders when supervisor info is missing/insufficient,
config-gated and active-only.

## 12. Oversight worklist

A permission-gated **Oversight** tab showing unassigned near-breach+ handoffs across
domains; hidden and silently inaccessible for non-oversight users (`?tab=oversight`
falls back). No unauthorized action links.

## 13. Department supervisor admin UI

Supervisor + escalation-contact selects on the Department edit form (active candidates
only), validated + audited.

## 14. Dashboard

The `journey_insight` banner gains a **"Supervisor missing"** chip (when the context
department has escalations but no supervisor). Cost: **+1 cached query**
(`supervisor_missing` check) beyond Phase 9.6; measured 28 (< 40).

## 16. Audit

`JOURNEY_DEPARTMENT_SUPERVISOR_CHANGED`, `JOURNEY_NOTIFICATION_PREFERENCES_UPDATED`,
the escalation-run audit (with `command_run_id`/`dry_run`/counts incl. `unassigned`),
plus the per-notification audit. `logs:audit` → **MISSING_LOG 0**.

## 17. Tests

`JourneySupervisorRoutingTest` (4), `JourneyNotificationPreferenceTest` (5),
`JourneyUnassignedSweepTest` (4), `JourneyOversightTest` (5) — **18 new; 109 journey
tests total**: supervisor receives escalation, fallback when none, inactive ignored,
actor not self-notified; defaults without rows, email/sms off, preference disables,
audited, settings page renders; sweep notifies/dry-run/dedupe/cause-filter; oversight
recipient notified, non-oversight can't see the tab, dept admin saves eligible /
rejects inactive.

## Query impact

- **Dashboard: +1** (`supervisor_missing`) beyond 9.6 → 28 (< 40).
- **Notifications:** recipient lookups capped at 8; preference check is one cached
  query per distinct recipient. Oversight lookup is permission-indexed + limited.
- **Unassigned sweep:** light candidate scan + one assignment-exclusion query +
  bounded full resolve; `--limit` respected; **no GET writes**.
- **Verification:** 109 journey + 65 dashboard tests pass · parity OK · localisation
  audit 0 · logs:audit no gaps · views compile · 8 routes · `git diff --check` clean.

## Remaining opportunities (Phase 9.8+)

- **Multi-supervisor** per department (pivot with `is_supervisor`).
- **Digest scheduler** (the config foundation is in place).
- **Per-event quiet hours** + working-hours-aware routing.
- **Oversight analytics**: supervisor coverage %, unrouted critical handoffs over time.
- **Websocket/SSE** to replace polling.
