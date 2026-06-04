# Logging Burn-Down — Procedures / Theatre

Module-focused burn-down. No new logging system, no parallel table, no
procedure/theatre business-logic change. The lifecycle already funnelled every
status transition through one method; this routes that funnel into the central
activity log so the whole procedure→theatre journey shows on the patient timeline.

## 1. Write paths inspected

- **`ProcedureWorkflowService`** — `acceptProcedure`, `rejectProcedure`,
  `generateBilling`, `cancelProcedure`, `completeProcedure`, `transition`, and the
  central **`logStatusChange()`**.
- **`ProcedureClinicalService`** — `recordPreOp`, `recordAnaesthesiaNote`,
  `startSurgery`, `recordOperativeNote`, `completeSurgery`, `recordPostOp` (each
  calls `workflow->transition(...)`).
- **`ProcedureScheduleService`** — `scheduleProcedure`, `reschedule` (route through
  `logStatusChange`/`transition`).
- **Existing logging:** `ProcedureStatusLog` table (custom) + visit pathway events;
  only `cancel`/`complete` also hit `activity_log` (partial, with two explicit calls).

## 2. Existing custom procedure/theatre logs found

Yes — `procedure_status_logs` (via `logStatusChange`). It was not writing to
`activity_log` for most transitions, so the lifecycle was largely invisible on the
patient timeline.

## 3. Actions now logged

`logStatusChange()` now **dual-writes** to `ActivityLogService`. The `to` status
maps to an event + module:

| To status | Event | Module |
|-----------|-------|--------|
| ACCEPTED | `PROCEDURE_ACCEPTED` | PROCEDURE |
| REJECTED | `PROCEDURE_REJECTED` | PROCEDURE |
| BILLED | `PROCEDURE_BILLED` | PROCEDURE |
| CANCELLED | `PROCEDURE_CANCELLED` | PROCEDURE |
| ON_HOLD | `PROCEDURE_POSTPONED` | PROCEDURE |
| COMPLETED | `PROCEDURE_COMPLETED` | PROCEDURE |
| SCHEDULED | `THEATRE_CASE_SCHEDULED` | THEATRE |
| RESCHEDULED | `THEATRE_CASE_RESCHEDULED` | THEATRE |
| PRE_OP | `PREOP_CHECKLIST_UPDATED` | THEATRE |
| ANAESTHESIA | `ANAESTHESIA_NOTE_ADDED` | THEATRE |
| IN_SURGERY | `PROCEDURE_STARTED` | THEATRE |
| SURGERY_DONE | `OPERATIVE_NOTE_ADDED` | THEATRE |
| POST_OP | `RECOVERY_NOTE_ADDED` | THEATRE |

One change covers the whole accept → bill → schedule → pre-op → anaesthesia →
surgery → post-op → complete lifecycle, since the clinical-note and scheduling
services all transition through `logStatusChange`.

## 4. Duplication risks avoided

- **Consultation request:** the initial `REQUESTED` status is set at creation, **not
  via a transition**, so `logStatusChange` never fires for it — the consultation-side
  procedure-request entry is not duplicated. Verified by test (`assertNotContains
  PROCEDURE_REQUESTED`).
- **Removed the two redundant explicit `logger->log()` calls** in `cancel`/`complete`
  (now emitted once by `logStatusChange`). Verified: exactly one `PROCEDURE_CANCELLED`.
- **Consumables:** theatre/procedure consumables run through `ConsumableUsageService`
  (`recordUsageForSource('procedure_request', …)`), already logging
  `STOCK / CONSUMABLE_USED` with patient context — not re-logged here.

## 5. Consumable / stock relationship

Procedure consumables remain owned by `ConsumableUsageService` (their own
`CONSUMABLE_USED` event with patient/visit/stock context). This module only logs
the procedure/theatre **workflow** status events.

## 6. Context fields included

From the `ProcedureRequest`: `patient_id`, `visit_id`, `emergency_case_id`,
`medical_record_id`, `consultation_route_id`, `department_id`, `service_id`
(service_catalog_id), `procedure_request_id`, `invoice_item_id` (billing_item_id),
plus `old_values`/`new_values` = the status change, `reason`, and `causer`.
Procedure/theatre context keys were already persisted by `ActivityLogService`.

## 7. Patient timeline verification

Test drives REQUESTED→ACCEPTED→BILLED→SCHEDULED→PRE_OP and asserts the
`PROCEDURE_*` (module PROCEDURE) and `THEATRE_*` (module THEATRE) events appear via
`getPatientTimeline()` with procedure context, old/new status, and readable
descriptions (*“Procedure scheduled: …”*). Reject/cancel carry their reason.

## 8. Tests added/passing

`tests/Feature/ProcedureTheatreLogTest.php` (**3**): full lifecycle (PROCEDURE +
THEATRE events, no `PROCEDURE_REQUESTED` duplicate, context, old/new); rejection
with reason; cancellation with reason + **no duplicate**. Regression: 23 tests
(theatre rooms + consultation) pass.

## 9. Bug found & fixed (required for cancel/complete logging to persist)

`notifyProcedureCancelled` / `notifyProcedureCompleted` referenced a non-existent
`requestedBy` relation (the relation is `requestingDoctor`). This threw
`RelationNotFoundException` inside the cancel/complete **DB transaction**, rolling
it back — so those actions (and their new activity log) would never persist. Fixed
the relation name (`requestedBy` → `requestingDoctor`). Pure typo fix, no logic
change.

## 10. logs:audit before/after

`73 → 73`. Procedure/theatre logging lives in the service funnel; controllers
delegate to it, so any controller still flagged is a false positive.

## 11. Remaining procedure/theatre logging TODOs

- **Theatre team assignment** (`THEATRE_TEAM_ASSIGNED/UPDATED`) — no distinct team
  service surfaced in this pass; wire when the team-assignment path is confirmed.
- **Theatre room assignment** is currently captured within `THEATRE_CASE_SCHEDULED`
  (room id lives on the schedule); add a dedicated `THEATRE_ROOM_ASSIGNED` with
  `theatre_room_id` if a separate room-assign action exists.
- **Report printing** (`ProcedureReportService`) — add `THEATRE_REPORT_PRINTED` on
  official print/download.
- **Direct/emergency procedure request** initial log — if a request is created
  outside consultation, add a one-time `PROCEDURE_REQUESTED` at creation.

## 12. Files modified

`app/Services/ProcedureWorkflowService.php` (dual-write `logStatusChange` +
event/module/description helpers; removed 2 redundant log calls; `requestedBy →
requestingDoctor` fix), `tests/Feature/ProcedureTheatreLogTest.php` (new).
