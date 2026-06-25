# Dynamic Visit Status Flow — Implementation Report

## Why

`visits.status` previously conflated three different ideas — **where the visit is
in the workflow**, **how it started**, and **its statistical attendance category** —
into one field, which made clean reporting impossible. This change separates them:

| Concept | Storage | Notes |
|---------|---------|-------|
| `visit_status` | `App\Enums\VisitStatus` (typed enum) | workflow position |
| `visit_source` | `visits.visit_source` (code) → `visit_sources` lookup | how the visit started |
| `attendance_class` | `visits.attendance_class` (code) → `attendance_classes` lookup | auto-computed, **not user-editable** |

## Status rename

Two statuses were renamed (values + all references, ~20 files), and three added.
`queue_entries.status` (a different domain) was deliberately left untouched.

- `waiting` → **`queued`** (the triage-queue state)
- `waiting_consultation` → **`waiting`** (post-triage wait)
- added `created`, `walked_in`, `abandoned`

A data migration renames existing rows (`visits.status` and
`visit_status_logs.from/to_status`) in the correct order so values don't collide,
and is reversible.

## Initial status + arrival model

`walked_in` and `checked_in` are the same "patient present" stage, split by source:
**direct → `walked_in`, appointment → `checked_in`** (no crossover in the
transition map).

| source | attendance_class | initial status | chain to queue |
|--------|------------------|----------------|----------------|
| direct | first_ever | `created` | created → walked_in → queued |
| direct | first_attendance_of_year | `registered` | registered → walked_in → queued |
| direct | subsequent_attendance | `walked_in` | walked_in → queued |
| appointment | (any) | `scheduled` | scheduled → checked_in → queued |

Attendance is classified by the visit **year** (not server date): `first_ever`
(no prior attendances), `first_attendance_of_year` (priors, none this year),
else `subsequent_attendance`. Cancelled / no-show / rescheduled visits don't
count as attendances.

## Key new code

- **`app/Services/VisitStatusFlowService.php`** — the canonical engine:
  `determineAttendanceClass()`, `resolveInitialStatus()`, `classifyAndInitialize()`,
  `transition()` (validates via `VisitStatus::allowedTransitions()`, with a
  `visits.override_transition` permission bypass when `force: true`), and
  `advanceToQueue()` (walks the source-specific arrival chain to `queued`).
- **`app/Models/VisitSource.php`, `AttendanceClass.php`** — dynamic lookup models.
- **`app/Services/VisitStatisticsService.php`** — `attendanceReport()` built on the
  new scopes; counts by source / attendance_class / status, first-ever / first-of-year /
  subsequent, checked-in, appointment no-shows, cancelled appointments, and
  patients with >1 attendance in the period.
- **`Visit` scopes**: `scopeStatus`, `scopeSource`, `scopeAttendanceClass`,
  `scopeBetweenVisitDates`; new columns `visit_source`, `attendance_class`,
  `arrived_at`, `cancelled_at`, `no_show_at`, `abandoned_at`.

## Wiring

- `VisitService::create()` → `flowService->classifyAndInitialize($visit, $source)`
  (`emergency` source for emergency-type visits, else `direct`).
- `AppointmentService::checkIn()` → source `appointment`; walks
  `scheduled → checked_in (→ queued when consultation services exist)`.
- `VisitWorkflowService::queueForTriage()` → delegates to `advanceToQueue()`.
- `VisitController::transition()` → optional `force` override (permission-gated);
  new `GET admin/visits/attendance-preview` powers the create/check-in badges.

## UI

- **Visit create** — read-only attendance badge after patient selection (AJAX to
  `attendance-preview`).
- **Appointment show** — attendance badge before check-in (computed server-side).
- **Visit list** — `visit_source` + `attendance_class` badges next to status.
- **Visit details** — status-flow timeline is now parcours-aware (direct shows
  `walked_in`, appointment shows `scheduled/checked_in`); status history unchanged.
- All labels via `lang/{en,fr}/visit_flow.php` + `statuses.php`; colours in `config/ui.php`.

## Permissions

`visits.override_transition` and `visit_flow.configure` (seeded in `RoleSeeder`,
granted to Super Admin / Admin). `attendance_class` is never user-editable.

## How to extend later

1. **New source / attendance class** — add a row to `visit_sources` /
   `attendance_classes` (seeded in `database/seeders/VisitFlowSeeder.php`) and a
   label in `lang/*/visit_flow.php`. No code change to store/report it.
2. **New status** — add a case to `App\Enums\VisitStatus`, give it `label()`,
   `color()`, and edges in `allowedTransitions()`, add a `statuses.php` (`visit`)
   label and a `config/ui.php` colour.
3. **New attendance rule** — adjust `VisitStatusFlowService::determineAttendanceClass()`.
4. **New initial-status mapping** — adjust `resolveInitialStatus()` /
   the `QUEUE_CHAIN` in `advanceToQueue()`.

## Tests

`tests/Feature/VisitStatusFlowTest.php` (6 cases: first-ever/first-of-year/
subsequent classification + initial status, appointment chain, invalid-transition
rejection vs. override, statistics). Existing renamed-status tests updated.

> Note: `ConsultationClinicalSectionsTest::test_consultation_page_shows_required_clinical_order`
> fails on this branch **independently of this change** (verified by stashing all
> changes — it fails identically on the base). It asserts clinical-section DOM
> ordering unrelated to the visit-status flow.
