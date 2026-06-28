# Department Dashboard — Phase 8.4: RBAC & Metric Visibility

Goal: a dashboard shows **"what does this user need to know right now?"**, not
"what data exists in the hospital." No KPI/perf/layout/theme changes — only *who
may see what*.

## Why a single permission failed

`visits.view` alone can't represent every consultation user — a doctor reads visits
through **consultation** permissions (and through simply *working in* the
consultation department), not one flag. Gating values on a single permission
over-restricted real users (an earlier attempt blanked the consultation board).

## 1. Capability profiles (Task 4)

A **capability** is satisfied by EITHER (a) any one of a set of module permissions,
OR (b) **membership of a department whose type owns the capability** — the
department you work in already grants you visibility of its board. Super Admins see
everything. (`app/Services/Department/DepartmentDashboardCapabilityService.php`)

| Capability | Permissions (any) | Department types (workflow) |
|---|---|---|
| `consultation_access` | visits.view, consultations.view, consultation.access, consultation.queue, queue.view, queue.manage, appointments.view | consultation, treatment, procedure, theatre, emergency, ambulance, nursing, records |
| `investigation_access` | lab.requests.view, lab.results.view, consultation.request_lab | investigation, radiology, blood_bank |
| `pharmacy_access` | prescriptions.view, pharmacy.dispensing.view, pharmacy.stock.manage | pharmacy |
| `stock_access` | store.purchase.view, pharmacy.stock.manage, store.requisition.view | pharmacy, stores, blood_bank |
| `ward_access` | ward.view, beds.view, ward.admit | inpatient, maternity, nursing, treatment |
| `emergency_access` | emergency.case.view, emergency.board.view | emergency, ambulance |
| `financial_access` | reports.financial_values.view, invoices.view, payments.view, billing.view | finance, administrative |

## 2. Metric → capability (Task 3)

One source of truth (`DepartmentMetricDefinitionService`) governs the **card, its
drilldown, the quick action and the widget** together (Task 5 — unified).

| Capability | Metrics |
|---|---|
| consultation_access | visits_today, waiting_queue, completed_today, appointments_today, scheduled, in_theatre |
| investigation_access | pending_requests, pending_imaging, scheduled_imaging, samples_awaiting_acceptance, completed_results_today, completed_imaging_today |
| pharmacy_access | pending_prescriptions, dispensed_today |
| stock_access | low_stock, stock_items, stock_issues, stock_requests |
| emergency_access | active_cases, critical_cases |
| ward_access | active_admissions, beds_occupied, vitals_due, discharges_pending |
| financial_access | department_revenue_today, revenue_today, invoices_today, payments_today, receivables |
| _(none — always visible)_ | services_count, staff_count, activity_today |

## 3. Department role matrix (Task 8)

Capabilities granted by **department type** (verified from the running app). A user
also gains a capability via an explicit role permission, and admins/Super Admins
see all.

| Department type | Capabilities (workflow) | Sees (examples) | Restricted |
|---|---|---|---|
| consultation | consultation_access | visits, waiting queue, completed | revenue, stock, lab, ward |
| emergency | consultation_access, emergency_access | active/critical cases, visits | revenue, stock |
| investigation / radiology / blood_bank | investigation_access (+ stock for blood_bank) | requests, samples, results | revenue, ward |
| pharmacy | pharmacy_access, stock_access | prescriptions, dispensed, low stock | revenue, lab, ward |
| stores | stock_access | stock items, low stock, requests | revenue, clinical |
| inpatient / maternity / nursing | ward_access (+ consultation for nursing) | admissions, beds, vitals due | revenue, stock |
| finance / administrative | financial_access | revenue, invoices, payments, receivables | clinical, stock |

> Example resolutions: **Pharmacist** → pharmacy_access + stock_access → sees stock
> (fixes the earlier over-restriction where a pharmacist with `pharmacy.stock.manage`
> but not `store.purchase.view` lost `low_stock`). **Doctor** → consultation_access →
> sees visits + a *working* drilldown, revenue restricted. **Finance staff** → sees
> revenue even without `invoices.view`, via the finance department workflow.

## 4. Restricted metrics (Task 6)

When a capability is absent, the card keeps its place and shows **"Access
restricted"** (not `0`, not hidden), and the drilldown/quick action for that domain
are withheld too — never card-visible/drilldown-hidden. Cross-domain examples: a
clinical user's `department_revenue_today` → restricted; a finance user's clinical
counts → restricted.

## 5. Tests (Task 7)

`DepartmentDashboardCapabilityTest` — workflow grants per type, cross-domain
restriction, explicit-permission grant without workflow, metric visibility in the
payload (doctor vs finance vs pharmacist), **card+drilldown unified**, quick actions
follow capability. Two Phase-6/8 tests updated to the new unified contract.

## 6. Safety (Task 9)

- ✅ **69 department tests pass** (519 assertions; +6 capability tests).
- ✅ **KPI values unchanged** — when a metric is visible the computation is identical;
  only *visibility* changes (the point of this phase).
- ✅ **Performance unchanged/better** — gating uses cached permissions + already-loaded
  relations (no new queries); restricted metrics now *skip* their query, so counts
  dropped slightly (e.g. consultation 29→11, pharmacy 24→13). The cache is unaffected.
- ✅ EN/FR parity OK · audit 0 · views compile.

## 7. Remaining security gaps

- **Route-level gates** can still be stricter than the dashboard capability — a
  drilldown shown by workflow access may 403 if the target page requires a narrower
  permission. Aligning route middleware with these capabilities is a follow-up.
- **Identity widget** for a (rare) cross-domain restricted source falls back to `0`
  rather than an explicit "restricted" badge (only reachable in admin preview of a
  foreign domain; own-dashboard widgets are always in-domain).
- **Per-metric granularity within a domain** isn't modelled (e.g. a user who may see
  pending requests but not results) — capabilities are domain-level by design.
- The drilldown builder's old `permissionFor()` map is now unused (kept for
  reference); a later cleanup can remove it.
