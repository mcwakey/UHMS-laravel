# Capabilities & Visibility (RBAC)

A dashboard shows **what the user is responsible for**, not "what data exists". A
single permission (`visits.view`) can't represent every clinical user, so visibility
is modelled as **capabilities**.

## A capability is satisfied two ways

`DepartmentDashboardCapabilityService::can($user, $capability)` returns true if
**either**:

1. the user holds **any** of the capability's module permissions, **or**
2. the user is a member of a department whose **type** owns the capability
   (workflow access — you can see your own department's board because you work there).

Super Admins see everything.

### Profiles (`DepartmentDashboardCapabilityService::PROFILES`)

| Capability | Permissions (any) | Department types (workflow) |
|---|---|---|
| `consultation_access` | visits.view, consultations.view, consultation.access, consultation.queue, queue.view, queue.manage, appointments.view | consultation, treatment, procedure, theatre, emergency, ambulance, nursing, records |
| `investigation_access` | lab.requests.view, lab.results.view, consultation.request_lab | investigation, radiology, blood_bank |
| `pharmacy_access` | prescriptions.view, pharmacy.dispensing.view, pharmacy.stock.manage | pharmacy |
| `stock_access` | store.purchase.view, pharmacy.stock.manage, store.requisition.view | pharmacy, stores, blood_bank |
| `ward_access` | ward.view, beds.view, ward.admit | inpatient, maternity, nursing, treatment |
| `emergency_access` | emergency.case.view, emergency.board.view | emergency, ambulance |
| `financial_access` | reports.financial_values.view, invoices.view, payments.view, billing.view | finance, administrative |

## Metric → capability (`DepartmentMetricDefinitionService`)

The metric→capability map is the **single source of truth**: the card value, its
drilldown, the matching quick action and the identity widget all gate on the same
capability. Metrics not listed (services/staff/activity counts) are always visible.

```
visits_today, waiting_queue, completed_today, appointments_today, scheduled, in_theatre  → consultation_access
pending_requests, pending_imaging, scheduled_imaging, samples_awaiting_acceptance,
        completed_results_today, completed_imaging_today                                 → investigation_access
pending_prescriptions, dispensed_today                                                   → pharmacy_access
low_stock, stock_items, stock_issues, stock_requests                                     → stock_access
active_cases, critical_cases                                                             → emergency_access
active_admissions, beds_occupied, vitals_due, discharges_pending                         → ward_access
department_revenue_today, revenue_today, invoices_today, payments_today, receivables     → financial_access
```

## Unified visibility (the key rule)

In `DepartmentDashboardDataService::metricCard()`:

```php
$visible = capabilities->can(user, metricDefinitions->capabilityFor($key));
$route   = $visible ? drilldowns->build(context, $key) : null;   // drilldown follows the card
$value   = $visible ? <compute> : ['restricted' => true, 'value' => null];
```

A restricted card keeps its place and shows **"Access restricted"** (never `0`, never
hidden). Because alerts/widgets read these capability-gated values, a restricted value
reads as `0` and never raises a false alert.

## Why values aren't gated on a single permission

An earlier attempt gated each value on one permission name and blanked legitimate
boards (doctors lack `visits.view`; pharmacists may have `pharmacy.stock.manage` but
not `store.purchase.view`). The department-type clause fixes this: you always see your
own department's domain, while cross-domain (revenue on a clinical board) stays
restricted.

## Caveat

Route middleware can be stricter than a dashboard capability, so a workflow-granted
drilldown could still 403 on the target page. Aligning route gates with these
capabilities is a future enhancement, not a dashboard concern.
