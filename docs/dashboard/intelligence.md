# Operational Intelligence

The dashboard helps staff **prioritize** — not just display numbers. Three pieces, all
derived from values **already computed** for the KPI cards (zero extra queries, and
they respect capability gating, so a restricted value reads as 0).

## 1. Operational widget — `DepartmentIdentityWidgetBuilder`

One signature widget per department type: a status + a coloured level bar + 2–3
supporting metric lines. Built from a `cardValue(key)` closure that reads the
assembled card values.

| Type | Widget (`key`) | Status scale |
|---|---|---|
| consultation | Waiting Pressure (`waiting_pressure`) | low / moderate / high / critical |
| emergency | Active Cases (`active_load`) | low → critical (critical if any critical case) |
| investigation | Turnaround Performance (`turnaround`) | excellent / good / delayed (backlog proxy) |
| radiology | Imaging Backlog (`imaging_backlog`) | excellent / good / delayed |
| pharmacy | Dispensing Efficiency (`dispensing_efficiency`) | % + excellent/good/delayed |
| theatre/procedure | Theatre Utilization (`theatre_utilization`) | normal / busy / critical |
| ward/treatment | Capacity Status (`capacity_status`) | normal / busy / critical |
| stores/blood bank | Stock Alerts (`stock_alerts`) | normal → critical |
| finance/admin | Financial Health (`financial_health`) | % (collected ÷ (collected+receivable)) |
| support/other | Operational Load (`operational_load`) | low → critical |

> Turnaround/Imaging use a **backlog proxy** (pending vs completed), not literal
> average time — real time-in-queue needs timestamp queries, deliberately excluded.

## 2. Alerts — `DepartmentAlertService::alertsFor()`

Threshold rules over existing values → a list of `{key, message, variant, icon}`:
critical cases, excessive waiting (≥15; danger ≥30), stock shortage (≥1 low),
overdue/backlog requests (≥20), imaging backlog (≥12), dispensing backlog (≥15),
discharge pressure (≥5). Rendered by the **priority-banner** ("Attention required")
only when non-empty.

## 3. Operational status — `DepartmentAlertService::statusFor()`

`Stable / Busy / Critical`, shown as a hero badge:
- **Critical** — any danger-level alert,
- **Busy** — any alert,
- **Stable** — none.

## Payload keys

`identity_widget`, `alerts`, `operational_status` (added in
`DepartmentDashboardDataService::build()` from the shared `cardValue` closure).

## Tests

`DepartmentOperationalIntelligenceTest`: one widget per type with status + metrics;
alerts derived from values; status escalation; restricted values raise no alerts;
restricted financial widget; excessive-waiting raises an alert + status.
