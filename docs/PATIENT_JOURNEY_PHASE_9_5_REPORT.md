# Phase 9.5 — Handoff Assignment, Escalation & Live Operations

- 9.1 where? · 9.2 why? · 9.3 who acts & where? · 9.4 which dept blocks which + SLA?
- **9.5 → who owns the action, has it been acknowledged, and when should it escalate?**

Turns handoff intelligence into coordinated live operations. The delay/cause/SLA/
handoff stay **derived** from existing records; only *ownership & escalation
metadata* is persisted. No visit-status/workflow changes, no dashboard redesign.

## 1–2. Assignment table + model

`journey_handoff_assignments` (migration) stores only coordination state: visit,
cause, from/to department (+ to-type), assignee/assigner, status
(`unassigned|assigned|acknowledged|resolved|dismissed`), escalation level
(`none|warning|supervisor|critical`), timestamps, notes. Indexed on visit/cause/
to-dept/assignee/status/escalation + a composite identity index.
[`JourneyHandoffAssignment`](app/Models/JourneyHandoffAssignment.php) casts the
cause enum + timestamps and exposes `isAssigned/isAcknowledged/isResolved/
isEscalated/canBeClaimedBy/canBeResolvedBy`.

## 3–4. Assignment service + identity key

[`JourneyHandoffAssignmentService`](app/Services/Journey/JourneyHandoffAssignmentService.php):
`activeHandoffFor` (re-derives the current handoff; null if no longer active),
`findFor` (no create — safe for display), `assignmentFor` (find-or-create),
`claim / assignTo / acknowledge / resolve / dismissIfResolved`. The **identity key**
`visit + cause + from-dept + to-dept(or to-type)` ensures the same unresolved handoff
reuses one row and a changed cause never inherits old state. Authorization is
enforced (capability over the destination domain); resolved handoffs can't be
re-mutated; every change is **audited** (Task 16).

## 5. DTO assignment overlay

`JourneyHandoff` gains a mutable overlay (`assignment_id/status`, `assigned_to_*`,
`assigned/acknowledged/resolved_at`, `escalation_level`, `last_escalated_at`)
attached *after* resolution via `attachAssignment()` — the derived fields stay
immutable. Rows are fetched, never force-created, for display.

## 6. Escalation service

[`JourneyEscalationService`](app/Services/Journey/JourneyEscalationService.php)
`levelFor` derives: within→none · near-breach & unassigned→warning · breached &
not-acknowledged→supervisor · critical-breach→critical. `applyEscalation` persists a
*risen* level on an existing row only (no GET writes, no background jobs); display
uses the derived level.

## 7. Assignable user service

[`JourneyAssignableUserService`](app/Services/Journey/JourneyAssignableUserService.php)
lists active staff in the destination department/domain, so a handoff is only ever
assigned to someone who can resolve it (empty → "No eligible staff found").

## 8. Coordination controller + routes

[`JourneyHandoffAssignmentController`](app/Http/Controllers/Admin/Journey/JourneyHandoffAssignmentController.php)
— CSRF-protected POSTs: `journey/handoffs/claim`, `/assign`,
`/{assignment}/acknowledge`, `/{assignment}/resolve`. Claim/assign re-derive the
handoff from the visit and fail safely ("handoff no longer active") if it has moved
on; service failures map to 403 or a flash.

## 9. Worklist UI

A 5th tab **Assigned To Me**; assignment columns (assignee, assignment status,
escalation badge); filters `assignment_status / escalation_level / mine_only /
unassigned_only`; per-row **Claim / Assign / Acknowledge / Resolve** buttons shown
only when the user can act. Resolved handoffs leave the active worklist. Batched
assignment load (1 query) + ≤ a few eligible-user queries.

## 10. Live refresh (Tasks 11–12)

`GET journey/worklist/refresh` returns just the summary+rows partial (shared with the
page). Lightweight polling (config `journey.worklist_refresh`, default 60s, no
websockets): skips while a field is focused or the tab is hidden, preserves tab +
filters, updates rows/summary/last-refreshed, and degrades to the manual button on
error or when disabled.

## 11. Patient widget

The visit-page widget shows **Assigned to / status / escalation** (or *Unassigned*),
with Claim/Resolve buttons when the viewer can act — built from the already-resolved
action via `fromAction` (no re-resolution; +2 bounded queries).

## 12. Dashboard

The handoff insight gains **critical / supervisor escalation** badges — derived from
the *same* light pass as the 9.4 handoff insight, so **+0 extra dashboard queries**.

## 13. Audit (Task 16)

Claim/assign/acknowledge/resolve/dismiss/escalate each log to `LogModule::CLINICAL_TASKS`
with visit/patient/cause/from/to/actor/assignee + old→new status & escalation.
`php artisan logs:audit` reports **no real gaps** (the controller is funnel-covered).

## 14. Tests

`JourneyHandoffAssignmentTest`, `JourneyEscalationTest`, `JourneyWorklistRefreshTest`
(**19 new; 75 journey tests total**): claim / assign-eligible / reject-ineligible /
unauthorized claim+resolve / acknowledge / resolve / resolved-disappears / stale-
dismissed / identity-reuse / audit-created; escalation none/warning/supervisor/
critical + acknowledged-suppresses; refresh auth + partial-only; assigned-to-me +
unassigned-only filters.

## 15. Query impact

- **Dashboard:** **+0** beyond Phase 9.4 (escalation reuses the handoff insight pass);
  total 19–26 (< 40).
- **Worklist page:** +1 batched assignment query + ≤ a few eligible-user queries on
  top of Phase 9.4; bounded, only the active tab resolves. Refresh endpoint = same
  shape, no layout overhead.
- **Patient widget:** +2 bounded queries when delayed.
- **Verification:** 75 journey + 65 dashboard tests pass · parity OK · localisation
  audit 0 · logs:audit no gaps · views compile · routes registered · `git diff
  --check` clean · no workflow/status/KPI/permission changes.

## Remaining opportunities (Phase 9.6+)

- **Notifications** on assignment/escalation (reuse the existing notification stack).
- **Scheduled escalation sweep** (a safe queued job) to persist escalation without a
  viewer, and to auto-dismiss stale rows.
- **Websocket/SSE** live updates to replace polling.
- **SLA/assignment analytics**: time-to-acknowledge, time-to-resolve, re-open rates
  per department and cause.
- **Bulk actions** (claim/assign multiple) and shift-based default assignees.
