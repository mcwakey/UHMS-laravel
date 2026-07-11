# Front Desk Patient Visitor Management Phase 18B Report

## Summary

Phase 18B extends the Phase 18A Front Desk **Visitor Log** so reception / security
can properly manage people visiting admitted patients — without ever exposing
clinical or billing data. It builds on the existing records and services
(`front_desk_visitor_logs`, `FrontDeskVisitorLog`, `VisitorLogService`,
`FrontDeskDashboardService`, `VisitorLogController`) rather than creating a
parallel module. No schema migration was required — visitor pass numbers reuse
`badge_number`, and the checkout note lives in the existing `metadata` column.

Delivered:

- **Badge / pass numbers** — auto-generated `VIS-YYYYMMDD-0001`, unique per day,
  never regenerated on update, manual numbers preserved.
- **Admission-linked workflow** — linking a patient auto-resolves their active
  admission → ward → bed (server-side); a ward selector and safe admission
  context are shown; discharged patients raise a clear (advisory) warning.
- **Advisory visitor rules** — `PatientVisitorRuleService` produces non-blocking
  warnings (no admission, discharged, same-day discharge, per-patient /
  per-admission / per-ward limits, same-phone-already-inside). Warning codes are
  snapshotted into `metadata.visitor_warnings`.
- **Checkout improvements** — optional checkout note + optional checkout time via
  a modal; on-site duration, expected-checkout and an overdue badge on the show
  page.
- **Visitor pass** — a print-friendly pass page behind `front_desk.visitors.print_pass`,
  showing safe identifiers only and audited when opened.
- **History & quick filters** — patient / admission visitor history (filtered
  index) plus quick filters (inside, overdue, patient, facility, checked-out
  today, denied/cancelled) and patient-linked / overdue badges.
- **Dashboard** — patient vs facility visitors inside, overdue patient visitors,
  ward visitor load (top wards) and recent patient visitors.

## Files Added

- `app/Services/FrontDesk/VisitorBadgeNumberService.php`
- `app/Services/FrontDesk/PatientVisitorRuleService.php`
- `database/migrations/2026_07_10_000005_seed_front_desk_visitor_pass_permission.php`
- `resources/views/admin/front-desk/partials/visitor-warnings.blade.php`
- `resources/views/admin/front-desk/visitors/pass.blade.php`
- `resources/views/admin/front-desk/visitors/history.blade.php`
- `tests/Feature/FrontDesk/PatientVisitorManagementTest.php`
- `docs/FRONT_DESK_PATIENT_VISITOR_MANAGEMENT_PHASE_18B_REPORT.md` (this file)

## Files Modified

- `config/front_desk.php` — added the `visitors` settings block.
- `app/Models/FrontDeskVisitorLog.php` — quick-filter scopes (`patientLinked`,
  `facilityVisitor`, `forPatient`, `forAdmission`, `checkedOutToday`) and helpers
  (`overdueHours`, `isOverdue`, `durationMinutes`, `expectedCheckoutAt`,
  `checkoutNote`); overdue scope now prefers `visitors.overdue_hours`.
- `app/Services/FrontDesk/VisitorLogService.php` — badge generation, admission
  auto-resolution, warning snapshot, checkout note/time, `logPassPrinted()`.
- `app/Services/FrontDesk/FrontDeskDashboardService.php` — patient/facility/overdue
  patient counts, ward visitor load, recent patient visitors.
- `app/Http/Controllers/Admin/FrontDesk/VisitorLogController.php` — quick filters,
  patient/admission history, visitor pass, checkout note, live warnings.
- `routes/web.php` — `visitors.pass`, `visitors.patient-history`,
  `visitors.admission-history` routes.
- `database/seeders/RoleSeeder.php` — `front_desk.visitors.print_pass` permission.
- `tests/Feature/FrontDesk/FrontDeskTestCase.php` — grant the new permission.
- `resources/views/admin/front-desk/index.blade.php` — Phase 18B dashboard cards,
  ward load and recent patient visitors.
- `resources/views/admin/front-desk/visitors/_form.blade.php` — ward selector,
  warnings panel, preselected-patient support.
- `resources/views/admin/front-desk/visitors/index.blade.php` — quick-filter pills,
  patient-linked / overdue badges.
- `resources/views/admin/front-desk/visitors/show.blade.php` — badge, duration,
  expected checkout, admission context, warnings, print-pass + history links,
  checkout modal.
- `lang/en/front_desk.php`, `lang/fr/front_desk.php` — new keys (parity 244/244).

## Visitor Pass / Badge Design

- **Badge number** — `VisitorBadgeNumberService::generate()` returns
  `{prefix}-YYYYMMDD-NNNN` (default prefix `VIS`), sequential and unique per day,
  with a short re-check loop against `badge_number` to avoid concurrent
  collisions. `VisitorLogService::create()` only auto-generates when the field is
  blank and `front_desk.visitors.auto_generate_badge_number` is true, and never
  regenerates on update.
- **Pass page** — `GET admin/front-desk/visitors/{visitor}/pass`
  (`admin.front-desk.visitors.pass`), a standalone print-friendly HTML page
  (`pass.blade.php`) showing facility name, visitor name, pass number, safe
  patient display (number + name), ward/bed, department, purpose, time in,
  expected checkout and who checked them in. It never shows diagnosis, notes,
  medications, lab results or billing. The print button uses a bound
  `addEventListener` (no inline handler).

## Admission-Linked Workflow

When a patient is linked and no admission was supplied, the service loads the
patient's `activeAdmission` (via the existing `Patient::activeAdmission()`
relationship) and fills `admission_id`, `ward_id` (from the bed) and `bed_id`,
defaulting the context to "patient". Forms expose a ward selector; the show page
renders an **Admission Context** card (patient link, ward, bed, admission number,
department, relationship, person to see, purpose) with a privacy notice. Only
safe identifiers are shown — no clinical fields are ever read into the view.

## Visitor Rules / Warnings

`PatientVisitorRuleService` returns advisory (never blocking) warnings:

| Code | When |
|---|---|
| `patient_not_admitted` | Linked patient has no active admission |
| `patient_discharged` | Latest admission is discharged (not today) |
| `patient_discharged_same_day` | Discharged today (entry still allowed per config) |
| `visitor_limit_for_patient_reached` | Active visitors ≥ `max_active_visitors_per_patient` |
| `visitor_limit_for_admission_reached` | Active visitors ≥ `max_active_visitors_per_admission` |
| `ward_visitor_limit_reached` | Active visitors ≥ `max_active_visitors_per_ward` |
| `visitor_already_inside_same_phone` | Another currently-inside visitor shares the phone |

Warnings are shown in the create/edit forms and on the show page, and their codes
are persisted in `metadata.visitor_warnings`. Staff can always proceed.

## Checkout Improvements

Checkout accepts an optional **note** (stored in `metadata.checkout_note`) and an
optional **checkout time** (defaults to now) via a modal. The show page displays
on-site **duration**, **expected checkout** (time in + configured default visit
duration) and an **overdue** badge. Guards from Phase 18A remain: a denied /
cancelled record cannot be checked out, and a record cannot be checked out twice
(both raise a localized validation error). Checkout is audited.

## Dashboard Improvements

New metrics in `FrontDeskDashboardService::metrics()`:
`patient_visitors_inside_count`, `facility_visitors_inside_count`,
`overdue_patient_visitors_count`, `wards_with_visitors`,
`top_wards_by_active_visitors` (grouped once, ward names fetched in a single
query — no N+1) and `recent_patient_visitors`. Ward load returns empty/zero
gracefully when there are no ward-linked visitors.

## Permissions

- New: `front_desk.visitors.print_pass` — gates the pass page.
- Seeded in `RoleSeeder` (fresh installs) and migration `..._000005` (existing
  DBs); granted to Super Admin, Admin, Receptionist and Medical Records Officer.
- Patient / admission history routes reuse the existing `front_desk.visitors.view`
  permission (no extra permission introduced for history).

## Privacy / Safety

No clinical or billing data is read or displayed anywhere in this phase.
Patient-linked screens show only patient number, name, ward/bed/admission number,
department and visit purpose. The audit context carries ids/status/badge only
(codes, not free text). A test asserts an admission's `admitting_diagnosis` never
appears on the visitor show or pass pages.

## Audit Logging

- `FRONT_DESK_VISITOR_CHECKED_OUT` — unchanged from 18A (still audited, now with
  the optional note captured in metadata).
- `FRONT_DESK_VISITOR_PASS_PRINTED` — logged when the pass page is opened by a
  user holding `front_desk.visitors.print_pass`. Context: visitor log id,
  patient/admission id (if linked), badge number, actor id.

## Tests Added

`tests/Feature/FrontDesk/PatientVisitorManagementTest.php` (20 tests): badge
generation (auto/manual/unique), admission auto-resolution, clinical
non-exposure, discharged warning, no-admission / limit / same-phone warnings,
warnings-don't-block-create, checkout note + duration, cannot-checkout-twice,
pass authorized/unauthorized/safe-data/audited, patient & admission history,
quick filters, dashboard metrics, and EN/FR + view-cache.

## Verification Results

```text
php artisan migrate            → DONE (visitor-pass permission migration)
php artisan route:list         → 25 admin.front-desk.* routes (incl. pass + 2 history)
php artisan view:clear/cache   → Blade templates cached successfully
npm run build                  → built OK (PWA generated, legacy assets copied)

php artisan test tests/Feature/FrontDesk          → 51 passed (177 assertions)
  (Phase 18A: 31 · Phase 18B PatientVisitorManagementTest: 20)

Gates:
  Localization/LanguageParityTest                 → PASS (front_desk 244/244)
  Localization/ActiveRuntimeLocalizationAuditTest → PASS (0 active runtime candidates)
  System/RouteLoadMemoryTest                      → PASS
  Permissions/PermissionsAuditStrictTest          → PASS

Consultation focused regression (tests/Feature/Consultations):
  248 passed, 2 failed — the 2 failures are the PRE-EXISTING inline-handler
  assertions on committed consultations/show.blade.php (documented in Phase 18A),
  unrelated to Phase 18B.
```

## Full Suite Result

```text
php artisan test
Tests:    3 failed, 1583 passed (8605 assertions)
```

Phase 18B added exactly **+20 passing tests** (1563 → 1583) and introduced **zero
new failures**. The 3 failures are the identical pre-existing consultation
failures from the Phase 18A baseline, all on committed HEAD code and unrelated to
front desk:

1. `ConsultationClinicalSectionsTest::consultation page shows required clinical order`
2. `ConsultationJavascriptLifecyclePhase2Test::workflow controls no longer use inline event handlers`
3. `ConsultationStructurePhase3Test::no inline workflow handlers exist in show or partials`

Failures 2 & 3 assert that `resources/views/consultations/show.blade.php` carries
no inline `onsubmit=` handlers, but that file (tracked, unmodified) has committed
inline handlers at lines 938 / 1299 / 1491. Failure 1 asserts a "Treatments"
clinical section renders in order on the consultation page. None touch the front
desk module or any file Phase 18B created or edited.

## Known Issues / Follow-up

- The 3 pre-existing full-suite failures are all in the **consultation** module
  (2 × inline-handler structure tests on committed `consultations/show.blade.php`,
  1 × clinical-section-order assertion). They reproduce on committed HEAD and are
  not caused by Phase 18A or 18B.

Deferred to later phases (intentionally out of scope for 18B):
- Visitor pass PDF / QR code
- Visitor photo capture
- Watchlist / restricted (blacklisted) visitors
- Dedicated Security role (visitor-only grant)
- SMS visitor notifications
- Advanced ward visitor policies (hard caps, time-window rules)
