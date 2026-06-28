# Department Dashboard — Phase 8.3: Experience, Identity & Department Intelligence

Goal: make a user instantly recognise **"this is MY department"** — identity,
context, priority and activity — while staying fast and accurate. No framework
redesign, no theme/KPI/performance changes, no new expensive queries.

## What shipped

| Task | Delivered | Where |
|------|-----------|-------|
| 1 | **`DepartmentStatusPresenter`** — raw status → localized label + colour variant + icon (+ priority flag), reusing `config/ui.php` + `lang/statuses.php` (the same maps as `<x-status-badge>`). Queue/activity rows now show **Queued / Completed / Cancelled** in colour, not raw text. | `app/Services/Department/DepartmentStatusPresenter.php`, wired into `tableRows()` with a per-queue domain (`visit`/`lab`/`requisition`/…) |
| 2 | **Friendly timestamps** — `friendlyTime()`: "10 minutes ago", "Today 08:15", "Yesterday 14:20", "Tue 09:30", localized (Carbon + lang). Replaces raw `2026-06-27 03:34:38`. | `DepartmentDashboardDataService::friendlyTime()` |
| 3 | **`DepartmentQuickActionRegistry`** — type-specific actions (Consultation: New Visit/Queue/Appointments; Pharmacy: Dispense/Stock; Finance: Revenue/Invoices/Payments; …), permission- and route-guarded. | `app/Services/Department/DepartmentQuickActionRegistry.php` |
| 4 | **Personalized header** — the previously-computed but unrendered `menu_heading` ("Main Pharmacy Operations") now shows as the hero eyebrow; welcome + scope already present. | `partials/hero.blade.php` |
| 5 | **Identity widget (one per type)** — Waiting Pressure / Active Cases / Results Pipeline / Imaging Queue / Dispensing Rate / Theatre Utilization / Bed Occupancy / Stock Alerts / Revenue Snapshot / Operational Workload. **Derived from already-computed KPI values — zero new queries.** | `DepartmentIdentityWidgetBuilder.php`, `partials/identity-widget.blade.php` (in `_chrome`) |
| 6 | **Friendlier empty states** — "No active items in the queue right now.", "Department activity will appear here." | `lang/{en,fr}/dashboards.php` |
| 7 | **Queue prioritization** — danger-status rows (critical/overdue/failed) get a red left-border, a priority flag icon and a coloured badge. Order unchanged. | `partials/_list-card.blade.php` |
| 8 | **Context info** — active shift (Morning/Afternoon/Night from the clock) + "Updated HH:mm" last-refresh, plus department name/type, in the hero. | `DepartmentDashboardController`, `partials/hero.blade.php` |
| 9 | **`DepartmentDashboardExperienceTest`** — status translation/colour/priority, friendly time, type-specific actions, one widget per type, header identity/context, FR localization. | `tests/Feature/Departments/DepartmentDashboardExperienceTest.php` |

## Safety validation (Task 10)

- ✅ **63 department tests pass** (57 prior + 6 new experience tests; 494 assertions).
- ✅ **Query counts unchanged** — every new feature reuses already-computed values
  or pure `config`/`lang` lookups; no new DB queries. The performance budget test
  (< 40 uncached) and the cache-hit test (1 query on repeat) still pass.
- ✅ EN/FR parity OK · localisation audit **0** · views compile · `git diff --check` clean.

## Notes

- Quick actions are permission-filtered, so a user only sees actions they can use
  (e.g. a Pharmacist sees Dispense + Services; the stock action appears only with
  `store.purchase.view`). This is intentional and consistent with the route gates.
- The identity widget's "pressure" thresholds are heuristic counts (low/moderate/high)
  — they reflect real values without needing capacity denominators (no extra queries).
