# Front Desk Operations Phase 18A Report

## Summary

Phase 18A adds a new **Front Desk Operations** module for non-clinical
reception / facility desk activities. It is fully separate from clinical patient
"visits" and never surfaces clinical data (diagnoses, notes, medications, lab
results).

The module delivers three logs plus a dashboard:

- **Visitor Logs** — people entering the facility (relatives of admitted
  patients, and people visiting departments/admin offices), with check-in /
  check-out tracking and a "currently inside" / "overdue" view.
- **Call Logs** — incoming / outgoing calls handled by reception, with category,
  outcome and an optional follow-up workflow.
- **Courier Logs** — incoming / outgoing letters, parcels, documents, reports,
  invoices and supplies, with a mark-delivered action.
- **Front Desk Dashboard** — eight aggregate cards plus recent-activity panels.

All writes go through a service layer, are permission-gated, audit-logged, and
fully EN/FR localised. Patient-linked records only ever show safe identifiers
(patient number, name where the role permits, ward/bed/department).

Per the "Visit vs Visitor" naming rule, the visitor table/model/routes all use
**Visitor** (`front_desk_visitor_logs`, `FrontDeskVisitorLog`,
`admin.front-desk.visitors.*`) — never `VisitLog` — so there is no collision
with the clinical `visits` domain.

## Files Added

### Config & Enums
- `config/front_desk.php` — overdue-hours threshold, dashboard recent limit, page size.
- `app/Enums/FrontDesk/VisitorContext.php`
- `app/Enums/FrontDesk/VisitorStatus.php`
- `app/Enums/FrontDesk/CallDirection.php`
- `app/Enums/FrontDesk/CallCategory.php`
- `app/Enums/FrontDesk/CallOutcome.php`
- `app/Enums/FrontDesk/CourierDirection.php`
- `app/Enums/FrontDesk/CourierType.php`
- `app/Enums/FrontDesk/CourierStatus.php`

### Migrations
- `database/migrations/2026_07_10_000001_create_front_desk_visitor_logs_table.php`
- `database/migrations/2026_07_10_000002_create_front_desk_call_logs_table.php`
- `database/migrations/2026_07_10_000003_create_front_desk_courier_logs_table.php`
- `database/migrations/2026_07_10_000004_seed_front_desk_permissions.php`

### Models
- `app/Models/FrontDeskVisitorLog.php`
- `app/Models/FrontDeskCallLog.php`
- `app/Models/FrontDeskCourierLog.php`

### Services
- `app/Services/FrontDesk/FrontDeskDashboardService.php`
- `app/Services/FrontDesk/VisitorLogService.php`
- `app/Services/FrontDesk/CallLogService.php`
- `app/Services/FrontDesk/CourierLogService.php`

### Form Requests
- `app/Http/Requests/FrontDesk/StoreVisitorLogRequest.php`
- `app/Http/Requests/FrontDesk/UpdateVisitorLogRequest.php`
- `app/Http/Requests/FrontDesk/StoreCallLogRequest.php`
- `app/Http/Requests/FrontDesk/UpdateCallLogRequest.php`
- `app/Http/Requests/FrontDesk/StoreCourierLogRequest.php`
- `app/Http/Requests/FrontDesk/UpdateCourierLogRequest.php`

### Controllers
- `app/Http/Controllers/Admin/FrontDesk/FrontDeskDashboardController.php`
- `app/Http/Controllers/Admin/FrontDesk/VisitorLogController.php`
- `app/Http/Controllers/Admin/FrontDesk/CallLogController.php`
- `app/Http/Controllers/Admin/FrontDesk/CourierLogController.php`

### Views (`resources/views/admin/front-desk/`)
- `index.blade.php` (dashboard)
- `visitors/{index,create,edit,show,_form}.blade.php`
- `calls/{index,create,edit,show,_form}.blade.php`
- `couriers/{index,create,edit,show,_form}.blade.php`

### Localisation
- `lang/en/front_desk.php`
- `lang/fr/front_desk.php`

### Tests (`tests/Feature/FrontDesk/`)
- `FrontDeskTestCase.php` (shared scaffolding)
- `FrontDeskOperationsTest.php`
- `VisitorLogTest.php`
- `CallLogTest.php`
- `CourierLogTest.php`

### Documentation
- `docs/FRONT_DESK_OPERATIONS_PHASE_18A_REPORT.md` (this file)

## Files Modified

- `app/Enums/LogModule.php` — added the `FRONT_DESK` audit module (+ colour).
- `app/Services/SidebarMenuBuilder.php` — added the "Front Desk" sidebar section
  (Dashboard / Visitor Logs / Call Logs / Courier Logs), permission-gated.
- `database/seeders/RoleSeeder.php` — registered the 14 front desk permissions and
  granted the operational set to Receptionist + Medical Records Officer
  (Super Admin / Admin receive all via `syncPermissions(Permission::all())`).
- `routes/web.php` — controller imports + the `admin/front-desk` route group.
- `lang/fr/consultations.php` — added 3 missing FR keys (`workspace.follow_up_date`,
  `workspace.follow_up_time`, `workspace.select_follow_up_service`) to restore
  EN/FR parity. This drift was **pre-existing** (from concurrent follow-up work
  already in the working tree), not introduced by Phase 18A, but it was blocking
  the `LanguageParityTest` gate so it was corrected here.

## Database Design

All three tables use string columns for enum-like fields (SQLite/MariaDB safe,
cast to PHP enums in the models), nullable foreign keys for every optional link
(`nullOnDelete`), a `metadata` JSON column, and appropriate filter indexes.

### `front_desk_visitor_logs`
People entering the facility. Key fields: `visitor_context` (patient/facility/
other), `visitor_name`, optional `patient_id`/`visit_id`/`admission_id`/`ward_id`/
`bed_id`/`department_id`, `person_to_see`, `purpose`, `time_in`, `time_out`,
`status` (checked_in/checked_out/denied/cancelled), and `checked_in_by`/
`checked_out_by`/`approved_by` user references. A `checked_in` record with no
`time_out` is "currently inside".

### `front_desk_call_logs`
Reception calls. Key fields: `direction` (incoming/outgoing), `category`,
`outcome`, `handled_by`, `started_at`/`ended_at`, optional `related_patient_id`/
`related_visit_id`/`department_id`, and a follow-up trio
(`follow_up_required`, `follow_up_status`, `assigned_follow_up_user_id`).

### `front_desk_courier_logs`
Letters/parcels/documents. Key fields: `direction`, `courier_type`, sender/
recipient identity, optional `recipient_department_id`/`related_patient_id`,
`tracking_number`/`reference_number`, `received_or_sent_at`, `received_by`/
`sent_by`, `delivered_to`/`delivered_at`, `status` and `signature_required`.

## Permissions

14 permissions, dot-notation, seeded both in `RoleSeeder` (fresh installs) and via
an idempotent migration (existing databases):

| Permission | Purpose |
|---|---|
| `front_desk.view` | Enter the front desk module |
| `front_desk.dashboard.view` | View the dashboard |
| `front_desk.visitors.view/create/update/checkout` | Visitor log CRUD + checkout |
| `front_desk.calls.view/create/update` | Call log CRUD + follow-up completion |
| `front_desk.couriers.view/create/update/deliver` | Courier log CRUD + delivery |
| `front_desk.reports.view` | Reserved for later reporting |

**Role defaults**
- **Super Admin / Admin** — all permissions.
- **Receptionist** — full operational set (view, dashboard, visitors incl.
  checkout, calls, couriers incl. deliver). No reports/config.
- **Medical Records Officer** — operational set + `front_desk.reports.view`.
- No dedicated "Security" role exists in this install, so the "visitor-only"
  security grant was not applied (documented for a later phase).

## Workflows

- **Visitor check-in** — `VisitorLogService::create()` stamps `checked_in_by`,
  defaults `status = checked_in` and `time_in = now`, and logs
  `FRONT_DESK_VISITOR_CREATED`.
- **Visitor check-out** — `checkOut()` refuses denied/cancelled records and
  double check-outs (`ValidationException`), then sets `status = checked_out`,
  `time_out` and `checked_out_by`, logging `FRONT_DESK_VISITOR_CHECKED_OUT`.
  Denied / cancelled transitions on update emit their own audit events.
- **Call logging** — `CallLogService::create()` stamps `handled_by`, defaults a
  flagged follow-up to `pending`; `markFollowUpCompleted()` closes it.
- **Courier tracking** — `CourierLogService::create()` stamps the direction-
  appropriate handler (`received_by` / `sent_by`); `markDelivered()` refuses
  already-delivered / returned / lost / cancelled items and then stamps
  `status = delivered` + `delivered_at`.

## Dashboard

`FrontDeskDashboardService::metrics()` (read-only) returns eight card counts —
visitors inside, visitors today, overdue visitors, calls today, pending call
follow-ups, couriers today, pending couriers, delivered today — plus incoming/
outgoing call splits and three recent-activity collections. "Overdue" = a
checked-in visitor whose `time_in` is older than
`config('front_desk.visitor_overdue_hours')` (default **4**).

## Privacy / Safety

- Patient links are optional on every log.
- Screens display only safe identifiers (patient number, name, ward/bed/dept).
- No diagnosis, clinical notes, medications or lab results are shown anywhere.
- A privacy notice is rendered on patient-linked forms/detail screens.
- No billing records and no clinical notes are ever created by this module.
- Audit context carries ids/status/`patient_id`/`department_id` only — never
  free-text notes (the shared `ActivityLogService` also masks sensitive fields).

## Audit Logging

All writes funnel through the existing `ActivityLogService` under the new
`FRONT_DESK` log module. Events emitted:

```
FRONT_DESK_VISITOR_CREATED / _UPDATED / _CHECKED_OUT / _DENIED / _CANCELLED
FRONT_DESK_CALL_CREATED / _UPDATED / _FOLLOWUP_COMPLETED
FRONT_DESK_COURIER_CREATED / _UPDATED / _DELIVERED / _CANCELLED
```

`patient_id` / `department_id` are recorded top-level (so patient-linked actions
appear on the patient timeline); the log id, direction/status/category are kept
under `metadata`.

## Tests Added

`tests/Feature/FrontDesk/` — `FrontDeskTestCase` (scaffolding) plus:
- `FrontDeskOperationsTest` — permission enforcement (unauthorised vs authorised,
  visitor/call/courier separation, create-vs-view), dashboard aggregates + empty
  state, page rendering, `view:cache` compilation, EN/FR key existence.
- `VisitorLogTest` — create (facility + patient-linked), list/search/filter,
  show, update, check-out, double-checkout guard, currently-inside/overdue
  scopes, audit logging.
- `CallLogTest` — incoming/outgoing create, follow-up flag defaulting,
  list/search/filter, pending-follow-up count + completion, audit logging.
- `CourierLogTest` — incoming/outgoing create (handler stamping), list/search/
  filter, mark-delivered, double-deliver guard, pending count, audit logging.

## Verification Results

```text
php artisan migrate            → DONE (4 front desk migrations applied)
php artisan route:list         → 22 admin.front-desk.* routes registered
php artisan view:clear/cache   → Blade templates cached successfully
npm run build                  → built in ~5s (PWA generated, legacy assets copied)

php artisan test tests/Feature/FrontDesk
  → 31 passed (Visitor 9, Call 6, Courier 7, Operations 9)

Regression / gates:
  Localization/LanguageParityTest          → PASS
  Localization/ActiveRuntimeLocalizationAuditTest → PASS (0 active runtime candidates)
  System/RouteLoadMemoryTest               → PASS
  LogsAuditCommandTest                     → PASS
  Permissions/PermissionsAuditCommandTest  → PASS
  Permissions/PermissionsAuditStrictTest   → PASS
```

## Full Suite Result

```text
php artisan test
Tests:    3 failed, 1563 passed (8543 assertions)
Duration: 832s
```

**All 3 failures are pre-existing and unrelated to Phase 18A** — every one is in
the **consultation** module, and none reference the front desk module or any file
it created/edited:

1. `ConsultationStructurePhase3Test::test_no_inline_workflow_handlers_exist_in_show_or_partials`
2. `ConsultationJavascriptLifecyclePhase2Test::test_workflow_controls_no_longer_use_inline_event_handlers`
3. `ConsultationClinicalSectionsTest::test_consultation_page_shows_required_clinical_order`

Failures 1 & 2 share one root cause: `resources/views/consultations/show.blade.php`
carries committed inline `onsubmit="…"` handlers (lines 938 / 1299 / 1491) that
these newer structure tests forbid. That file is **tracked and unmodified at
HEAD** (`git status` clean), so they reproduce on committed code with none of this
branch's working-tree changes applied.

Failure 3 asserts a "Treatments" clinical section renders in order on the
consultation show page — a consultation section-ordering issue, again with no
connection to the front desk module.

The only front-desk edit that touches a consultation file is three added FR keys
in `lang/fr/consultations.php`; these tests run in the `en` locale and assert HTML
structure / section order, so that edit cannot affect them.

Isolation evidence for the Front Desk module:
- `tests/Feature/FrontDesk/*` — 31/31 pass.
- Shared-file edits verified green: `ActivityLogContextTest`,
  `ActivityLogServiceTest`, `AdmissionBedWorkflowPhase5Test` (LogModule/status
  coverage), `DepartmentMenuProfileTest`, `AccountingModuleSplitTest`,
  `ModuleAccessTest`, `InertiaAuthShareTest`, `PreviousBalancePolicyTest`,
  and all localisation / permissions / route-load / logs-audit gates.

Fixing the committed consultation `show.blade.php` handlers is outside the
Phase 18A scope and would touch an unrelated, actively-developed area, so it is
intentionally left untouched here.

## Known Issues / Follow-up

Deferred to later phases (intentionally out of scope for 18A):

- Visitor pass printing
- Callback / follow-up reminder scheduler (queue)
- Courier proof-of-delivery attachment upload
- Front desk handover log
- Lost and found
- Incident / security desk (and a dedicated Security role → visitor-only grant)
- CSV export / advanced reporting (`front_desk.reports.view` is reserved)
