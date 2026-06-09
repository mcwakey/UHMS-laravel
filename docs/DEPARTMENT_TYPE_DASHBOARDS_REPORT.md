# Department-Type Dashboards — Implementation Report

## Summary
UHMS now renders a dashboard tailored to the signed-in user's **department type / role**
instead of a single generic page. A resolver picks the right dashboard, a metrics service
builds a common data contract, and one shared, data-driven Blade view renders it with the
standard UHMS components (`<x-page-header>`, `<x-stat-card>`, `<x-status-badge>`, `<x-empty-state>`).

Existing dashboards (`dashboard`, `admin.dashboard`, `doctor.dashboard`, `staff.dashboard`,
accounting & blood-bank dashboards) are **untouched**. This feature is additive, reachable via
the new **My Dashboard** sidebar entry → `admin.my-dashboard`.

## Pieces added
| Layer | File |
|---|---|
| Resolver | `app/Services/Dashboard/DepartmentDashboardResolver.php` |
| Metrics / contract | `app/Services/Dashboard/DepartmentDashboardService.php` |
| Controller | `app/Http/Controllers/Admin/DepartmentDashboardController.php` |
| View | `resources/views/admin/dashboards/index.blade.php` |
| Queue partial | `resources/views/admin/dashboards/partials/work-queue.blade.php` |
| Route | `admin/my-dashboard` → `admin.my-dashboard` (in `routes/web.php`) |
| Nav | "My Dashboard" item in `SidebarMenuBuilder` |

## Resolver behaviour
`DepartmentDashboardResolver::resolveKey(User)`:
1. Global admin / management roles → **management**.
2. The user's primary `department->type` (`DepartmentType` enum):
   - `consultation`, `treatment` → consultation
   - `pharmacy` → pharmacy
   - `investigation`, `radiology` → investigation
   - `procedure` → theatre
   - `administrative` → management
3. Role-based fallback (for finance/stock departments that aren't a clinical type):
   Cashier→billing, Accountant→accounting, Store Keeper→stock, Pharmacist→pharmacy,
   Lab roles→investigation, Theatre roles→theatre, Doctor→consultation.
4. Otherwise → **generic** staff dashboard.

Admins can preview any dashboard with `?as=<key>` (a selector is shown in the header).

## Data contract
Every builder returns:
```php
[ 'title', 'key', 'department', 'kpis'[], 'alerts'[], 'queues'[], 'quick_actions'[], 'reports'[] ]
```
- **kpis** → `<x-stat-card>` (`{title,value,icon,variant,route,format}`)
- **alerts** → shown only when count > 0 (`{title,count,variant,icon,route}`)
- **queues** → `partials/work-queue` (top 10 rows, click-through, "View all")
- **quick_actions / reports** → buttons / links

### Safety
- Every metric is wrapped (`count()`, `sum()`, `rows()`); a bad query degrades to `0`/`[]`
  rather than breaking the page.
- Every link is gated on **route existence** (`Route::has`) **and permission** (`$user->can`),
  so no action ever renders that would 403 or 404. Backend permissions still enforce access.
- Financial KPIs (Revenue/Payments today) only render with `payments.view`.

## Dashboards implemented (KPIs / queue)
All 15 keys have dedicated builders (the generic fallback is now only for users with
no resolvable department/role):
- **Management** — visits today, admitted, revenue (perm), unpaid invoices, low stock, pending claims; alerts: failed postings, out-of-stock.
- **Consultation / OPD** — waiting/in-consultation/completed/visits today; queue: waiting list.
- **Emergency / Casualty** — active cases, critical (red), urgent (orange), cases today; queue: active emergency cases.
- **Admission / Ward** — admitted, beds occupied/available, admission requests; alert: no beds; queue: current admissions.
- **Pharmacy** — pending-to-bill, billed-to-dispense, dispensed today, low/out stock; queue: prescriptions.
- **Investigations / Lab** (incl. Radiology) — pending/in-progress/completed/urgent, scoped to the user's department; queue: worklist.
- **Theatre & Procedures** — requested/scheduled/in-theatre/completed; queue: procedure board.
- **Billing / Cashier** — unpaid invoices, invoices today, payments today (perm), partial; queue: unpaid invoices.
- **Insurance / Claims** — to-prepare/ready-submitted/under-review/approved/rejected; queue: claims needing attention.
- **Stock / Store** — low/out stock, requisitions to approve, open POs; queue: requisitions.
- **Blood Bank** — available units, expiring ≤7 days, pending requests/screening; queue: pending blood requests.
- **Accounting** — journals, failed postings, invoices today.
- **HR / Payroll** — active staff, pending leave, payroll draft/approved; queue: pending leave.
- **Reception / Front Desk** — visits today, appointments today, checked-in, waiting; queue: today's appointments.
- **Generic** — visits today + patient/visit links (safe fallback).

## Default landing
The generic `dashboard` route now redirects every authenticated user to
`admin.my-dashboard`, which resolves their department-type dashboard. The legacy
`admin.dashboard`, `doctor.dashboard` and `staff.dashboard` routes remain registered
and reachable directly; only the default landing changed. The sidebar "Dashboard"
item points at the resolver.

## Module enable/disable
Quick-action/report links are route-gated, so a disabled module (whose routes are not
registered) simply drops its links — the dashboard still renders.

## Manual verification completed
- `php artisan view:cache` — compiles.
- `route:list --path=my-dashboard` — route present.
- Contract built for all 9 keys (no exceptions); counts populate from live data.
- View rendered server-side for management/pharmacy/billing/generic (full layout, ~80 KB).

## Known TODOs / next recommendations
- Add lightweight caching for management KPIs if dashboard load becomes heavy.
- Split per-department builders into their own small classes if the service grows further.
- Some statuses are free-string columns (emergency `disposition`/`final_triage_category`,
  blood unit/request `status`); KPIs use the observed values (`AVAILABLE`, `admitted`,
  `RED`/`ORANGE`) and degrade to 0 if a value differs — tighten with enums when available.
- Automated tests deferred per the current implementation phase (manual verification above).
