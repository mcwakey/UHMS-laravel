# Department Dashboard — Phase 8.5: Operational Intelligence

Goal: the dashboard answers **"what requires my attention right now?"** — not just
"what numbers exist?". Every signal is derived from values **already computed** for
the KPI cards (capability-gated), so it adds **zero queries**.

## What shipped

### Operational widget per type (Tasks 1–8)
`DepartmentIdentityWidgetBuilder` now produces a status + coloured level bar + 2–3
supporting metric lines (e.g. `Waiting Pressure · Critical · 128 waiting · 5
clinicians available`). One widget per type:

| Type | Widget | Status scale | Supporting metrics |
|---|---|---|---|
| consultation | Waiting Pressure | low/moderate/high/critical | waiting, clinicians, completed |
| emergency | Active Cases | low→critical (critical if any critical case) | active, critical |
| investigation | Turnaround Performance | excellent/good/delayed (backlog) | pending, awaiting, completed |
| radiology | Imaging Backlog | excellent/good/delayed | pending studies, scheduled, completed |
| pharmacy | Dispensing Efficiency | % + excellent/good/delayed | % dispensed, pending, low stock |
| theatre/procedure | Theatre Utilization | normal/busy/critical | in theatre, scheduled |
| ward/treatment | Capacity Status | normal/busy/critical | occupied, admissions, pending discharge |
| stores/blood bank | Stock Alerts | normal→critical | low stock, items |
| finance/administrative | Financial Health | % + excellent/good/delayed | collected, receivable, ratio |
| support/other | Operational Load | low→critical | visits, activity |

> Honest note: Turnaround/Imaging use a **backlog proxy** (pending vs completed),
> not literal average time — real TAT/oldest-age would need timestamp queries, which
> the rules exclude. All numbers reuse existing KPI values.

### Department alerts (Task 9) + priority banner (Task 10)
`DepartmentAlertService::alertsFor()` derives alerts from existing values (no
polling): critical cases, excessive waiting, stock shortage, overdue/backlog
requests, imaging backlog, dispensing backlog, discharge pressure. The
**priority-banner** partial renders *"Attention required"* + the top alerts **only
when present** (e.g. "4 items low in stock", "128 patients waiting").

### Operational status (Task 11)
`DepartmentAlertService::statusFor()` → **Stable / Busy / Critical** (Critical when
any danger-level alert, Busy when any alert, else Stable), shown as a hero badge.

### Capability awareness
Because alerts/widgets read the **capability-gated** card values, a restricted
(cross-domain) value reads as 0 and never raises an alert or inflates a widget — a
consultation user never triggers a stock alert.

## Tasks 12 — Tests

`DepartmentOperationalIntelligenceTest` (6): one widget per type with status +
metrics; alerts derived from values; status escalation Stable→Busy→Critical;
restricted values raise no alerts; restricted financial widget; excessive-waiting
raises an alert + status. The experience test was updated for the new widget keys.

## Safety

- ✅ **75 department tests pass** (549 assertions; +6 intelligence).
- ✅ **Query count unchanged: 11–13 (< 40)** — intelligence reuses already-computed
  card values; **no new queries**. Cache unaffected.
- ✅ **KPI values unchanged**; **permissions/capabilities unchanged**.
- ✅ EN/FR parity OK · audit 0 · views compile · `git diff --check` clean.

## Files

- `app/Services/Department/DepartmentIdentityWidgetBuilder.php` (enriched)
- `app/Services/Department/DepartmentAlertService.php` (new)
- `resources/views/admin/dashboards/department/partials/{priority-banner,identity-widget}.blade.php`, `hero`, `_chrome`
- `lang/{en,fr}/dashboards.php` (widget/status/metric/alerts/op_status)
- `tests/Feature/Departments/DepartmentOperationalIntelligenceTest.php`

## Remaining opportunities

- Real turnaround time / oldest-request age would need one lightweight timestamp
  aggregate per relevant widget (still well under the 40-query budget) — deferred to
  honour "reuse existing values / no new queries".
- Bed *shortage* needs a capacity denominator (total beds) that isn't currently a
  KPI; only occupancy/discharge pressure is surfaced today.
- Alert thresholds are fixed constants; a future phase could make them
  per-department configurable.
