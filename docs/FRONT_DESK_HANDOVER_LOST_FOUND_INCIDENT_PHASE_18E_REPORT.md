# Front Desk Handover, Lost & Found, and Incident Desk Phase 18E Report

## Summary

Phase 18E completes the Front Desk Operations module with three operational,
non-clinical reception/security workflows, extending the existing module:

- **Shift Handover** — draft → submit → accept → cancel, capturing a safe
  operational snapshot (visitors inside, pending callbacks/couriers, open
  incidents, unclaimed lost/found) at handover time.
- **Lost & Found** — register found / reported-lost items with `LF-YYYYMMDD-0001`
  references, then claim / release / cancel. Phone numbers are masked.
- **Incident / Security Desk** — log operational incidents (aggressive visitor,
  unauthorized access, queue dispute, …) with `INC-YYYYMMDD-0001` references,
  then assign / escalate / resolve / cancel, optionally linked to a visitor /
  call / courier record.

Everything is aggregate/operational only: no clinical data is read or written,
no billing records or clinical notes are created, and these are explicitly Front
Desk / Security / Facility incidents (never "clinical incidents").

## Files Added

### Enums
- `app/Enums/FrontDesk/ShiftHandoverStatus.php`, `LostFoundStatus.php`,
  `LostFoundCategory.php`, `FrontDeskIncidentType.php`,
  `FrontDeskIncidentSeverity.php`, `FrontDeskIncidentStatus.php`

### Migrations
- `2026_07_11_000006_create_front_desk_shift_handovers_table.php`
- `2026_07_11_000007_create_front_desk_lost_found_items_table.php`
- `2026_07_11_000008_create_front_desk_incident_logs_table.php`
- `2026_07_11_000009_seed_front_desk_facility_permissions.php`

### Models / Services / Controllers
- `app/Models/FrontDeskShiftHandover.php`, `FrontDeskLostFoundItem.php`,
  `FrontDeskIncidentLog.php`, `Concerns/MasksFrontDeskPhone.php`
- `app/Services/FrontDesk/ShiftHandoverService.php`, `LostFoundService.php`,
  `IncidentLogService.php`
- `app/Http/Controllers/Admin/FrontDesk/ShiftHandoverController.php`,
  `LostFoundController.php`, `IncidentLogController.php`

### Views
- `resources/views/admin/front-desk/handovers/{index,create,edit,show,_form}.blade.php`
- `resources/views/admin/front-desk/lost-found/{index,create,edit,show,_form}.blade.php`
- `resources/views/admin/front-desk/incidents/{index,create,edit,show,_form}.blade.php`

### Tests / Docs
- `tests/Feature/FrontDesk/ShiftHandoverTest.php`, `LostFoundTest.php`, `IncidentDeskTest.php`
- `docs/FRONT_DESK_HANDOVER_LOST_FOUND_INCIDENT_PHASE_18E_REPORT.md` (this file)

## Files Modified

- `config/front_desk.php` — `handovers`, `lost_found`, `incidents` config.
- `app/Services/FrontDesk/FrontDeskDashboardService.php` — handover / lost-found /
  incident metrics + recent lists.
- `app/Services/FrontDesk/FrontDeskReportService.php` — `facilityMetrics()` +
  handovers / lost_found / incidents export datasets + export types.
- `routes/web.php` — 3 route groups (28 routes) + controller imports.
- `database/seeders/RoleSeeder.php` — 19 permissions + role grants.
- `tests/Feature/FrontDesk/FrontDeskTestCase.php` — grant the new permissions.
- `tests/Feature/FrontDesk/FrontDeskReportsTest.php` — cover the 3 new export types.
- `app/Services/SidebarMenuBuilder.php` — Front Desk → Handovers / Lost & Found /
  Incident Desk menu items.
- `resources/views/admin/front-desk/index.blade.php` — dashboard facility cards.
- `resources/views/admin/front-desk/reports/index.blade.php` — Facility report tab.
- `lang/en/front_desk.php`, `lang/fr/front_desk.php` — new keys (parity 559/559).

## Database Changes

- **front_desk_shift_handovers** — shift date/name, outgoing/incoming user,
  department, status, five snapshot counts, `open_items_snapshot` (json),
  submit/accept/cancel actor+timestamp, metadata.
- **front_desk_lost_found_items** — unique reference, status, category,
  description, found/reported context, stored location, claim/release actor +
  claimant details, metadata. Phones masked on display.
- **front_desk_incident_logs** — unique incident number, type, severity, status,
  reporter (masked phone), location, department, optional links to
  visitor/call/courier logs, assign/escalate/resolve actors + timestamps,
  description/action/resolution, metadata.

## Shift Handover Workflow

`createDraft` snapshots live counts from the existing Front Desk services and
records them; `submit` (draft → submitted) waits for the incoming shift; `accept`
(submitted → accepted, cannot accept a cancelled one) stamps the incoming staff
and completion time; `cancel` (draft/submitted → cancelled, never an accepted
one) records a reason. A draft is the only editable state.

## Lost and Found Workflow

`create` auto-assigns an `LF-…` reference when blank and defaults to *found*;
`markClaimed` records the claimant + verifier; `release` records the release
actor (guards: cannot release a cancelled or already-released item); `cancel`
closes with a reason. Reporter/claimant phones are masked everywhere.

## Incident Desk Workflow

`create` auto-assigns an `INC-…` reference and opens the incident; `assign`
(open → in progress) sets the assignee; `escalate` sets the escalation target +
status; `resolve` stamps the resolver + resolution note; `cancel` records a
reason. Critical open incidents surface prominently on the dashboard. Linked
visitor/call/courier records show safe operational context only.

## Dashboard Improvements

New metrics: `pending_handovers_count`, `handovers_waiting_acceptance_count`,
`unclaimed_lost_found_count`, `lost_found_reported_today_count`,
`open_incidents_count`, `critical_open_incidents_count`,
`incidents_reported_today_count`, plus `recent_handovers`, `recent_lost_found`,
`recent_incidents`. The dashboard shows a compact facility card row.

## Reports / Exports

Phase 18D was lightly extended: a **Facility** report tab with `handovers_by_status`,
`lost_found_by_status`, `lost_found_by_category`, `incidents_by_type`,
`incidents_by_severity`, `incidents_by_status`, `daily_incident_trend`, and three
new CSV export types — `handovers`, `lost_found`, `incidents`. Exports mask
phones and, for incidents, deliberately export summary/status fields only (the
full narrative description is NOT exported).

## Permissions

19 new permissions (RoleSeeder + migration `..._000009`):
`front_desk.handovers.view/create/update/submit/accept/cancel`,
`front_desk.lost_found.view/create/update/claim/release/cancel`,
`front_desk.incidents.view/create/update/assign/escalate/resolve/cancel`.

Role defaults: **Super Admin / Admin** — all; **Receptionist** — operational set
(handover view/create/update/submit/accept, lost-found view/create/update/claim/
release, incident view/create/update); **Medical Records Officer** — view-only on
all three. Supervisory actions (handover cancel, lost-found cancel, incident
assign/escalate/resolve/cancel) are Admin-level by default. No dedicated Security
role exists in this install (documented as follow-up).

## Audit Logging

16 events via `ActivityLogService` under the `FRONT_DESK` module:
`FRONT_DESK_HANDOVER_{CREATED,UPDATED,SUBMITTED,ACCEPTED,CANCELLED}`,
`FRONT_DESK_LOST_FOUND_{CREATED,UPDATED,CLAIMED,RELEASED,CANCELLED}`,
`FRONT_DESK_INCIDENT_{CREATED,UPDATED,ASSIGNED,ESCALATED,RESOLVED,CANCELLED}`.
Context carries record id / status / severity / department / actor — long
narrative descriptions are never written to the audit properties.

## Privacy / Safety

No clinical or billing data anywhere. Incidents are Front Desk / Security /
Facility incidents, never clinical. Phones are masked in lists, detail pages and
exports. Notes/reasons live in-column or in metadata, never in the audit trail.

## Tests Added

- `ShiftHandoverTest` (6): access, draft-with-snapshot, submit+accept,
  cannot-accept-cancelled, dashboard counts, audit.
- `LostFoundTest` (8): access, found/reported create with reference, phone
  masking, claim+release, cannot-release-twice/cancelled, dashboard unclaimed
  count, audit.
- `IncidentDeskTest` (7): access, create with number, assign/escalate/resolve,
  cancel, critical dashboard count, linked-visitor safe context, audit.
- `FrontDeskReportsTest` extended to cover the handovers/lost_found/incidents
  CSV exports.

## Verification Results

```text
php artisan migrate            → DONE (4 Phase 18E migrations)
php artisan route:list         → 65 admin.front-desk.* routes (28 new)
php artisan view:clear/cache   → Blade templates cached successfully
npm run build                  → built OK (PWA generated, legacy assets copied)

php artisan test tests/Feature/FrontDesk  → 109 passed
  (18A 31 · 18B 20 · 18C 22 · 18D 15 · 18E ShiftHandover 6 + LostFound 8 + IncidentDesk 7 = 21)

Safety gates (all PASS):
  Localization/LanguageParityTest                 (front_desk 559/559)
  Localization/ActiveRuntimeLocalizationAuditTest (0 active runtime candidates)
  System/RouteLoadMemoryTest
  Permissions/PermissionsAuditStrictTest
Total across FrontDesk + gates: 115 passed (382 assertions).
```

## Full Suite

Deferred until the Front Desk module wrap-up, per the phase instruction. The known
baseline has 3 pre-existing consultation failures (2 × inline-handler assertions
on committed `consultations/show.blade.php`, 1 × clinical-section-order) that are
unrelated to Front Desk and are to be addressed at module wrap-up.

## Known Issues / Follow-up

- SMS / email / push notifications (e.g. incident escalation alerts)
- Incident attachments / photos
- Police / security agency report export
- Dedicated Security role (not present in this install)
- Shift handover PDF
- Deeper SLA / response-time analytics
