# Statistical Reports / Analytics — Implementation Report

## 1. Gap analysis

UHMS already had **record-level** reporting:

- `OperationalReportService` + `reports.*` routes → paginated detail tables with a status breakdown (consultations, diagnoses, complaints, pharmacy, investigations, procedures, theatre, emergency, admission, mar, billing, claims, stock, blood-bank).
- `ReportService` → financial/clinical PDF/CSV reports (income, visits, patients, pharmacy sales, etc.).

What was missing: **statistical / analytical** reporting — KPIs, trends, rankings, percentages, charts, and a management dashboard. This module adds that layer **on top of the existing data** (no new source-of-truth tables) and drills back down into the existing operational reports.

> The prompt assumed Inertia/Vue. UHMS is a **Blade** application with **Chart.js and ApexCharts already bundled** (`public/build/plugins/`). Chart.js is loaded globally in `layouts/app.blade.php`, so the module uses Chart.js with no new dependency.

## 2. What was built

| Area | File |
|------|------|
| Aggregation service | `app/Services/StatisticsService.php` |
| Controller | `app/Http/Controllers/Admin/StatisticsController.php` |
| Routes | `routes/web.php` → `admin.statistics.*` (prefix `/admin/statistics`) |
| Generic view | `resources/views/statistics/show.blade.php` |
| Dashboard view | `resources/views/statistics/dashboard.blade.php` |
| Nav partial | `resources/views/statistics/_nav.blade.php` |
| Permissions | `database/seeders/RoleSeeder.php` (`statistics.*`) |
| Menu | `app/Services/SidebarMenuBuilder.php` (Reports → Statistical Reports) |
| Tests | `tests/Feature/StatisticsTest.php` |

### Pages (16)

Dashboard + Hospital Activity, Diagnosis, Complaint, Consultation, Pharmacy, Investigation, Procedure/Theatre, Emergency, Admission, MAR, Billing, Claims, Stock, Blood Bank, Staff Performance.

A single normalised structure (`kpis`, `charts`, `lists`) is rendered by **one generic Blade view**, so every page gets KPI cards, Chart.js charts (bar / line / doughnut), and drill-down top-lists consistently.

## 3. Data sources (source-of-truth rules honoured)

| Statistic | Source table(s) |
|-----------|-----------------|
| Diagnoses | `diagnoses` (never invoice items) |
| Complaints | `complaints` (structured records, not HOPC text) |
| Prescribed drugs | `prescription_items` |
| Dispensed drugs | `dispensing_records` (qty = `quantity_dispensed`) |
| Drug sales / revenue | `invoice_items` (product_id) + `payments` |
| Administered meds | `medication_administrations` |
| Investigations | `lab_requests` / `lab_request_items` |
| Procedures | `procedure_requests` |
| Emergency | `emergency_cases` |
| Admissions / occupancy | `admissions`, `beds`, `wards` |
| Billing / collection | `invoices`, `payments`, `invoice_items` |
| Claims | `claims` |
| Stock | `stock_movements`, `stock_balances` (never product.quantity) |
| Blood available | `blood_units` where `status = AVAILABLE` |
| Staff performance | acting-user columns (`doctor_id`, `dispensed_by`, `administered_by`, `received_by`) |

## 4. Charts (Chart.js)

Visits trend (line), revenue trend (line), top diagnoses/complaints/drugs/tests/procedures (bar), triage & disposition (doughnut), claims by status (doughnut), bed occupancy by ward (bar), stock consumption (bar), blood units by group (bar). Empty datasets render a graceful "No data" message.

## 5. Drill-down

KPI cards and top-list "Details" buttons link into the existing operational reports (`admin.reports.diagnoses`, `…pharmacy`, `…emergency`, `…billing`, `…stock`, `…blood-bank`, etc.), passing the active date range. Dashboard KPI cards link to the matching statistics page.

## 6. Permissions

Umbrella `statistics.view` grants every page **except** the sensitive **Staff Performance**, which requires `statistics.staff_performance.view` specifically (enforced in routes-controller, not just UI). Per-page permissions (`statistics.<page>.view`) and `statistics.export` are also defined. Super Admin/Admin receive all; Accountant receives the analytics umbrella + financial pages + export.

## 7. Export

CSV export per page (`?export=csv`), gated by `statistics.export`, and **logged** via `ActivityLogService` (`STATISTICS_EXPORTED`, with report key + filters in metadata).

## 8. Performance

- Default range = **current month → today** (never all-time).
- All figures use **aggregate SQL** (`COUNT`/`SUM`/`AVG`, `GROUP BY`) — no loading records into memory, except the bounded time-difference averages (capped at 10k rows) which are computed in PHP for DB portability.
- Top-lists are `LIMIT`-ed (15–20 rows).

## 9. Database portability

Tests run on sqlite (`:memory:`); production is **MariaDB 10.1**. The service therefore avoids MySQL-only SQL: user names are concatenated in PHP, and waiting-time / length-of-stay averages are computed in PHP (`avgMinutesBetween` / `avgDaysBetween`) rather than via `TIMESTAMPDIFF`. String literals use single quotes. Verified green on **both** sqlite (tests) and MariaDB (live smoke test).

## 10. Tests

`tests/Feature/StatisticsTest.php` (9 tests, all passing): dashboard loads + default range, permission gate (403), every non-sensitive page loads, staff-performance gating (umbrella forbidden / specific allowed), blood-inventory AVAILABLE-only correctness, export permission gate, export succeeds + is logged.

## 11. Known limitations / future TODOs

- Theatre utilisation is approximated from `procedure_requests` (no dedicated `theatre_cases` schedule table was present); true scheduled-vs-actual-hours utilisation is a TODO.
- Low/out-of-stock uses a fixed `≤10 / ≤0` heuristic (no per-product reorder level column); wire to reorder levels when available.
- Department/doctor/visit-type filters are supported by the date range today; extra filter inputs (per the spec's global filter list) can be added to the filter bar incrementally.
- Excel/PDF export not added (only CSV + print); the app's existing PDF tooling can be wired later.
- Optional dashboard caching is not yet enabled (queries are already aggregate and fast for monthly ranges).
- Drill-downs reuse the existing operational reports; deep per-row drill-downs (e.g. a single drug's dispensing list) can be added as those detail routes gain query params.
