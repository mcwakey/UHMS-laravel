# Front Desk Reports and Analytics Phase 18D Report

## Summary

Phase 18D adds operational reporting and privacy-safe CSV exports to the Front
Desk module (extending it — no new module). A single report page with tabs
(Overview / Visitors / Calls / Callbacks / Couriers / Courier Workflow) surfaces
aggregate metrics with date / department / ward filters, and each section offers
a CSV export. All reporting is aggregate-only: it reads no clinical data, and
exports carry only safe columns with masked phone numbers.

It answers questions like: how many visitors today/this window, patient vs
facility, which wards, currently inside / overdue; incoming vs outgoing calls,
categories, pending / overdue callbacks, calls by department; couriers in/out,
by status / handover status, pending dispatch / in transit / delivered /
returned; and daily front desk workload trends.

## Files Added

- `app/Services/FrontDesk/FrontDeskReportService.php`
- `app/Services/FrontDesk/FrontDeskReportExportService.php`
- `app/Http/Controllers/Admin/FrontDesk/FrontDeskReportController.php`
- `database/migrations/2026_07_11_000005_seed_front_desk_report_export_permission.php`
- `resources/views/admin/front-desk/reports/index.blade.php`
- `resources/views/admin/front-desk/reports/_metric-table.blade.php`
- `tests/Feature/FrontDesk/FrontDeskReportsTest.php`
- `docs/FRONT_DESK_REPORTS_ANALYTICS_PHASE_18D_REPORT.md` (this file)

## Files Modified

- `routes/web.php` — reports route group + controller import.
- `database/seeders/RoleSeeder.php` — `front_desk.reports.export` permission;
  Receptionist granted `front_desk.reports.view`; Medical Records Officer granted
  `front_desk.reports.export`.
- `tests/Feature/FrontDesk/FrontDeskTestCase.php` — grant the export permission.
- `app/Services/SidebarMenuBuilder.php` — Front Desk → Reports menu item.
- `lang/en/front_desk.php`, `lang/fr/front_desk.php` — `reports` block (parity 399/399).

## Report Routes

```
GET admin/front-desk/reports         admin.front-desk.reports.index   (front_desk.reports.view)
GET admin/front-desk/reports/data    admin.front-desk.reports.data    (front_desk.reports.view)  JSON payload
GET admin/front-desk/reports/export  admin.front-desk.reports.export  (front_desk.reports.export) CSV stream
```

## Report Metrics

`FrontDeskReportService` (aggregate-only, default window = last 30 days, filters
degrade to zero/empty):

- **Summary cards** — total / patient / facility visitors, currently inside,
  overdue; total / incoming / outgoing calls; pending / overdue callbacks; total /
  pending / in-transit / delivered / returned couriers.
- **Visitors** — by context / status / ward / department, daily trend, current,
  overdue, and top patients by visitor count (patient number + name only).
- **Calls** — by direction / category / outcome / department / handler, daily trend.
- **Callbacks** — by status, pending / overdue / due-today counts, by assignee,
  completion rate. (Average callback delay skipped — see follow-up.)
- **Couriers** — by direction / type / status / handover status / department,
  daily trend, pending dispatch, in transit, delivered today, returned.
- **Courier workflow** — handoffs by action / department / user, recent handoffs.

All "by X" breakdowns return a uniform `{label, count}` list; enum values are
resolved to translated labels for display.

## CSV Exports

`FrontDeskReportExportService` streams `{columns, rows}` via `response()->streamDownload`
(the established maternity export convention), prefixed with app name, generated-by,
generated-at and the applied filters. Export types:

```
summary · visitors · visitor_currently_inside · visitor_overdue
calls · callbacks · couriers · courier_workflow
```

Privacy rules — CSVs **never** include diagnosis, clinical notes, medications,
lab results or billing. Visitor / call **phone numbers are masked** (first 3 +
last 2 digits, middle starred). Only safe patient identifiers (patient number +
name) appear, consistent with what the module already displays. Column headers
follow the codebase's existing English-CSV convention.

## Permissions

- Existing `front_desk.reports.view` gates the report page + JSON.
- New `front_desk.reports.export` gates CSV export.
- Role defaults (RoleSeeder + migration `..._000005`): **Super Admin / Admin** —
  view + export; **Medical Records Officer** — view + export; **Receptionist** —
  view only. No new permissions granted to unrelated clinical roles.

## Privacy / Safety

No clinical or billing data is read or exposed anywhere. Reports are aggregate;
detail exports carry only safe columns with masked phones. No billing records and
no clinical notes are created.

## Audit Logging

Exports are audited (UHMS already logs report exports elsewhere, e.g.
`MATERNITY_REPORT_EXPORTED`): `FRONT_DESK_REPORT_EXPORTED` under the `FRONT_DESK`
module with context `export_type` + normalised `filters` + actor. Exported row
contents are never logged.

## Tests Added

`tests/Feature/FrontDesk/FrontDeskReportsTest.php` (15): permission enforcement
(view / export separation), page + empty-state render, visitor summary + date +
context/ward metrics, call metrics, callback metrics (incl. completion rate),
courier metrics, CSV exports for every type, phone masking + clinical-field
exclusion, export audit, invalid-type rejection, and EN/FR keys.

## Verification Results

```text
php artisan migrate            → DONE (report export permission)
php artisan route:list         → 37 admin.front-desk.* routes (incl. 3 reports)
php artisan view:clear/cache   → Blade templates cached successfully
npm run build                  → built OK (PWA generated, legacy assets copied)

php artisan test tests/Feature/FrontDesk  → 88 passed
  (18A 31 · 18B 20 · 18C 22 · 18D FrontDeskReportsTest 15)

Safety gates (all PASS):
  Localization/LanguageParityTest                 (front_desk 399/399)
  Localization/ActiveRuntimeLocalizationAuditTest (0 active runtime candidates)
  System/RouteLoadMemoryTest
  Permissions/PermissionsAuditStrictTest
Total across FrontDesk + gates: 94 passed (312 assertions).
```

## Full Suite

Deferred until the end of the Front Desk module, per the phase instruction. The
known baseline has **3 pre-existing consultation failures** (2 × inline-handler
assertions on committed `consultations/show.blade.php`, 1 × clinical-section-order)
that are unrelated to Front Desk. Phase 18D is additive (new reporting surface,
no workflow changes); all focused Front Desk tests + safety gates pass.

## Known Issues / Follow-up

- PDF reports
- Scheduled / emailed reports
- Deeper SLA analytics (e.g. average callback delay, average delivery time)
- Visual charts (this phase is tabular, not BI)
- Central management-report registration
- Callback reminder automation
