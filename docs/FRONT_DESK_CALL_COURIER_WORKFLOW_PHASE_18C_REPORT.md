# Front Desk Call and Courier Workflow Phase 18C Report

## Summary

Phase 18C turns the Phase 18A Call Log and Courier Log into real front desk
workflows, extending the existing models, services, controllers, views, routes
and dashboard (no new module). It adds:

- **Call follow-up / callback queue** — assign a follow-up (assignee + due time
  + note), complete it (with a completion note), cancel it (with a reason), and
  record a call transfer to a department / user. A dedicated callback-queue page
  with overdue / due-today / assigned-to-me filters.
- **Courier dispatch / handover workflow** — dispatch (→ in transit), internal
  handover, mark delivered (with proof reference + delivery note), and mark
  returned, all recorded on a clean **handover trail** timeline. A courier
  workflow board with pending-dispatch / in-transit / awaiting-handover /
  delivered-today / returned / overdue quick filters.
- **Dashboard** — a Callback Queue section (pending / overdue / due today /
  assigned-to-me) and a Courier Workflow section (pending dispatch / in transit /
  awaiting handover / overdue) plus recent callbacks and recent handoffs.

No clinical or billing data is read or shown. Notes (follow-up / completion /
cancellation / delivery / return) are stored in `metadata`, never in the audit
free-text. `status` remains the primary courier status; `handover_status` is the
new workflow detail.

## Files Added

- `app/Enums/FrontDesk/CallFollowUpStatus.php`
- `app/Enums/FrontDesk/CourierHandoverStatus.php`
- `app/Enums/FrontDesk/CourierHandoffAction.php`
- `app/Models/FrontDeskCourierHandoff.php`
- `database/migrations/2026_07_11_000001_add_followup_workflow_to_front_desk_call_logs.php`
- `database/migrations/2026_07_11_000002_add_workflow_to_front_desk_courier_logs.php`
- `database/migrations/2026_07_11_000003_create_front_desk_courier_handoffs_table.php`
- `database/migrations/2026_07_11_000004_seed_front_desk_workflow_permissions.php`
- `resources/views/admin/front-desk/calls/follow-ups.blade.php`
- `resources/views/admin/front-desk/couriers/workflow.blade.php`
- `tests/Feature/FrontDesk/CallFollowUpQueueTest.php`
- `tests/Feature/FrontDesk/CourierWorkflowTest.php`
- `docs/FRONT_DESK_CALL_COURIER_WORKFLOW_PHASE_18C_REPORT.md` (this file)

## Files Modified

- `config/front_desk.php` — `calls.callback_overdue_minutes`, `couriers.overdue_hours`.
- `app/Models/FrontDeskCallLog.php` — follow-up workflow fields, casts, relations,
  scopes (`pendingCallback`, `overdueCallback`, `dueTodayCallback`, `assignedTo`,
  `followUpStatus`, `transferred`) and helpers.
- `app/Models/FrontDeskCourierLog.php` — workflow fields, `handover_status` enum
  cast, relations (`dispatchedBy`, `receivedInternallyBy`, `handoffs`, …), scopes
  (`pendingDispatch`, `inTransit`, `awaitingHandover`, `overdueCourier`,
  `returnedItems`, `handoverStatus`) and helpers.
- `app/Services/FrontDesk/CallLogService.php` — `assignFollowUp`, `completeFollowUp`,
  `cancelFollowUp`, `transferCall` (+ `markFollowUpCompleted` now delegates).
- `app/Services/FrontDesk/CourierLogService.php` — `dispatch`, `handOver`,
  enhanced `markDelivered(deliveredAt, deliveryNote, proofReference)`,
  `markReturned`, and a `recordHandoff` trail writer (also on create).
- `app/Services/FrontDesk/FrontDeskDashboardService.php` — callback + courier
  workflow metrics; `metrics()` now accepts the current user (assigned-to-me).
- `app/Http/Controllers/Admin/FrontDesk/CallLogController.php` — queue + actions + filters.
- `app/Http/Controllers/Admin/FrontDesk/CourierLogController.php` — workflow board + actions + filters.
- `app/Http/Controllers/Admin/FrontDesk/FrontDeskDashboardController.php` — passes the user.
- `routes/web.php` — call follow-up queue/actions + courier workflow/actions.
- `database/seeders/RoleSeeder.php` — 8 new permissions.
- `tests/Feature/FrontDesk/FrontDeskTestCase.php` — grant the new permissions.
- `resources/views/admin/front-desk/index.blade.php` — dashboard sections.
- `resources/views/admin/front-desk/calls/{index,show}.blade.php` — filters + panels.
- `resources/views/admin/front-desk/couriers/{index,show}.blade.php` — filters + panels + timeline.
- `lang/en/front_desk.php`, `lang/fr/front_desk.php` — new keys (parity 332/332).

## Database Changes

- **front_desk_call_logs (additive)** — `follow_up_due_at`, `follow_up_completed_at`,
  `follow_up_completed_by`, `follow_up_cancelled_at`, `follow_up_cancelled_by`,
  `transfer_department_id`, `transferred_to_user_id`. Reuses the Phase 18A
  `follow_up_required` / `follow_up_status` / `assigned_follow_up_user_id`. Notes
  live in `metadata` (`follow_up_note` / `completion_note` / `cancellation_reason`).
- **front_desk_courier_logs (additive)** — `handover_status`, `dispatch_department_id`,
  `dispatched_at`, `dispatched_by`, `received_internally_by`, `proof_reference`.
  Delivery/return notes live in `metadata` (`delivery_note` / `return_reason`).
- **front_desk_courier_handoffs (new)** — the custody trail: `courier_log_id`,
  from/to user + department, `action` (enum), `action_at`, `note`, `metadata`,
  `created_by`.

## Call Follow-up Queue

- **Assign** (`assignFollowUp`) → `follow_up_required = true`, status `pending`,
  assignee, due time, note; re-opens a previously closed follow-up.
- **Complete** (`completeFollowUp`) → only when `follow_up_required`; sets status
  `completed`, completed at/by, completion note.
- **Cancel** (`cancelFollowUp`) → status `cancelled`, cancelled at/by, reason.
- **Transfer** (`transferCall`) → records transfer department and/or user.
- Queue page `admin.front-desk.calls.follow-ups` lists pending callbacks ordered
  by due time, with overdue / due-today / assigned-to-me filters. The call show
  page has a follow-up panel (assign / complete / cancel) and a transfer panel.

## Courier Workflow

- **Dispatch** (`dispatch`) → `status = dispatched`, `handover_status = in_transit`,
  dispatched at/by, optional dispatch department; blocked for delivered / lost /
  cancelled items.
- **Handover** (`handOver`) → `handover_status = handed_over`, internal recipient;
  writes a trail event.
- **Delivered** (`markDelivered`) → `status = delivered`, `handover_status = delivered`,
  delivered at, proof reference + delivery note (metadata); cannot deliver twice.
- **Returned** (`markReturned`) → `status = returned`, `handover_status = returned`,
  reason; cannot return a delivered item.
- Every action appends to the **handover trail** (received on create, then
  dispatched / handed_over / delivered / returned), rendered as a timeline on the
  courier show page. Workflow board `admin.front-desk.couriers.workflow` with
  quick filters.

## Dashboard Improvements

Call: `pending_callbacks_count`, `overdue_callbacks_count`,
`callbacks_due_today_count`, `assigned_to_me_callbacks_count`,
`recent_pending_callbacks`. Courier: `pending_dispatch_count`,
`in_transit_couriers_count`, `awaiting_handover_count`, `overdue_couriers_count`,
`recent_courier_handoffs`. Ward-load style single grouped queries — no N+1.

## Permissions

New (seeded in `RoleSeeder` + migration `..._000004`; granted to Super Admin,
Admin, Receptionist, Medical Records Officer):

```
front_desk.calls.followups.view / .assign / .complete
front_desk.calls.transfer
front_desk.couriers.workflow.view / .dispatch / .handover / .return
```

Cancel-follow-up reuses `followups.complete`. No new permissions granted to
unrelated clinical roles.

## Audit Logging

New events: `FRONT_DESK_CALL_FOLLOWUP_ASSIGNED`, `FRONT_DESK_CALL_FOLLOWUP_CANCELLED`,
`FRONT_DESK_CALL_TRANSFERRED`, `FRONT_DESK_COURIER_DISPATCHED`,
`FRONT_DESK_COURIER_HANDED_OVER`, `FRONT_DESK_COURIER_RETURNED`,
`FRONT_DESK_COURIER_DELIVERY_PROOF_RECORDED`. The existing
`FRONT_DESK_CALL_FOLLOWUP_COMPLETED` and `FRONT_DESK_COURIER_DELIVERED` are
reused (no duplicates). Context carries ids / status / follow_up_status /
assigned_user_id / handover_status / department_id / actor — never free-text notes.

## Privacy / Safety

No clinical or billing data is read or displayed. All workflow notes are stored
in `metadata` (never in the audit properties). No clinical notes or billing
records are created.

## Tests Added

- `tests/Feature/FrontDesk/CallFollowUpQueueTest.php` (10): queue access,
  assign/complete/cancel with notes, complete-when-not-required guard, overdue /
  due-today / assigned-to-me filters, transfer, dashboard counts, audit.
- `tests/Feature/FrontDesk/CourierWorkflowTest.php` (12): workflow access,
  dispatch (+ guard), handover + trail, received-on-create trail, delivery with
  proof, cannot-deliver-twice, return, workflow filters, dashboard counts, audit.

## Verification Results

```text
php artisan migrate            → DONE (4 Phase 18C migrations)
php artisan route:list         → 34 admin.front-desk.* routes
php artisan view:clear/cache   → Blade templates cached successfully
npm run build                  → built OK (PWA generated, legacy assets copied)

php artisan test tests/Feature/FrontDesk  → 73 passed
  (18A 31 · 18B 20 · 18C CallFollowUpQueue 10 + CourierWorkflow 12)

Safety gates (all PASS):
  Localization/LanguageParityTest                 (front_desk 332/332)
  Localization/ActiveRuntimeLocalizationAuditTest (0 active runtime candidates)
  System/RouteLoadMemoryTest
  Permissions/PermissionsAuditStrictTest
Total across FrontDesk + gates: 79 passed (267 assertions).
```

## Full Suite

Deferred until the end of the Front Desk module, per the phase instruction (do
not run the broad full suite mid-module). The Phase 18B baseline was **1583
passed, 3 failed**, where the 3 failures are pre-existing consultation-module
tests (2 × inline-handler assertions on committed `consultations/show.blade.php`,
1 × clinical-section-order) unrelated to Front Desk. Phase 18C is additive to the
Front Desk module and all focused Front Desk tests + safety gates pass; when the
full suite is next run, those same 3 consultation failures are expected and
should be documented as pre-existing and unrelated.

## Known Issues / Follow-up

Deferred to later phases:
- SMS callback reminders / email / calendar notifications
- Courier file / photo proof-of-delivery upload
- QR courier scanning
- Advanced courier SLA reports
- Front desk shift handover log
- Lost and found
- Incident / security desk
