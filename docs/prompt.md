Yes. The KPIs must be different **per department type**, otherwise the dashboards will keep feeling the same.

The rule should be:

```text
Dashboard type decides the KPI family.
Department ID decides the actual records counted.
Permissions decide whether sensitive KPIs are visible.
```

So every KPI below must be scoped to the **current department_id**, not just the department type. That follows the Phase 7/8 model where department type controls layout/theme/menu, but department ID controls actual data scope.  Also, KPI cards should only be clickable when the user has the route and permission, using the drilldown URL builder pattern already added in Phase 8. 

## Recommended KPI structure

Each dashboard should have:

```text
4 primary KPIs — big cards at the top
4–8 secondary KPIs — smaller cards or side cards
2–4 operational lists — queue, alerts, recent activity
2–3 charts — trend, breakdown, workload
```

Do **not** show 20 KPI cards at the top. That makes every dashboard ugly and noisy.

---

# KPIs per Department Type

## 1. Consultation Dashboard

Best for OPD, doctors, clinics, specialist consultation rooms.

**Primary KPIs**

```text
Patients waiting
Patients in consultation
Consultations completed today
Follow-ups due
```

**Secondary KPIs**

```text
Average waiting time
Average consultation time
New vs returning patients
No-show appointments
Pending lab/radiology requests
Pending prescriptions
Consultation revenue, if permitted
```

**Charts**

```text
Consultations by day
Waiting vs consulting vs completed
Follow-up trend
Patient type breakdown
```

**Main dashboard personality**

```text
Queue-first, patient-flow dashboard.
```

---

## 2. Emergency Dashboard

Best for Emergency / Casualty.

**Primary KPIs**

```text
Active emergency cases
Critical/urgent cases
Pending triage
Emergency cases completed today
```

**Secondary KPIs**

```text
Average triage time
Average emergency stay time
Unbilled emergency services
Emergency admissions
Emergency referrals
Emergency consumables used
Emergency revenue, if permitted
```

**Charts**

```text
Cases by priority
Cases by status
Emergency volume by hour/day
Triage status breakdown
```

**Main dashboard personality**

```text
Command-center, alert-first dashboard.
```

---

## 3. Investigation Dashboard

Best for Laboratory.

**Primary KPIs**

```text
Pending lab requests
Samples awaiting acceptance
Results pending validation
Results completed today
```

**Secondary KPIs**

```text
Rejected samples
Urgent requests
Average turnaround time
Tests performed today
Laboratory services count
Laboratory consumables used
Laboratory revenue, if permitted
```

**Charts**

```text
Requests by status
Samples accepted vs rejected
Results completed trend
Top requested lab tests
```

**Main dashboard personality**

```text
Laboratory workbench, sample/results dashboard.
```

---

## 4. Radiology Dashboard

Best for X-Ray, Ultrasound, Imaging.

**Primary KPIs**

```text
Pending imaging requests
Scheduled imaging
Imaging completed today
Reports pending
```

**Secondary KPIs**

```text
Urgent imaging requests
Average imaging turnaround time
Cancelled imaging
Radiology services count
Radiology consumables used
Radiology revenue, if permitted
```

**Charts**

```text
Imaging requests by status
Scheduled vs completed imaging
Urgent imaging trend
Top imaging services
```

**Main dashboard personality**

```text
Imaging schedule/results dashboard.
```

Important: radiology should not feel like laboratory. It should show scheduling, imaging reports, scan completion, and imaging workload.

---

## 5. Procedure Dashboard

Best for minor procedure rooms.

**Primary KPIs**

```text
Pending procedures
Procedures completed today
Procedures in progress
Procedure consumables used
```

**Secondary KPIs**

```text
Cancelled procedures
Average procedure duration
Procedure services count
Unbilled procedures
Procedure revenue, if permitted
```

**Charts**

```text
Procedures by status
Procedures by day
Consumables usage trend
Top procedures
```

**Main dashboard personality**

```text
Task/procedure board.
```

---

## 6. Theatre Dashboard

Best for operating theatre / surgery.

**Primary KPIs**

```text
Scheduled surgeries today
Pre-op cases
Surgery in progress
Post-op cases
```

**Secondary KPIs**

```text
Cancelled surgeries
Completed surgeries today
Average surgery duration
Theatre occupancy/utilisation
Theatre consumables used
Unbilled theatre procedures
Theatre revenue, if permitted
```

**Charts**

```text
Surgery schedule trend
Pre-op / in-progress / post-op breakdown
Theatre utilisation
Consumables usage
```

**Main dashboard personality**

```text
Surgery board with timeline/schedule feeling.
```

---

## 7. Treatment Dashboard

Best for dressing, injections, physiotherapy, treatment rooms.

**Primary KPIs**

```text
Pending treatments
Treatments completed today
Patients waiting
Treatment tasks overdue
```

**Secondary KPIs**

```text
Treatment services count
Consumables used
Repeat treatments
Average treatment time
Treatment revenue, if permitted
```

**Charts**

```text
Treatments by status
Treatments by day
Treatment service usage
Consumables trend
```

**Main dashboard personality**

```text
Care-task dashboard.
```

---

## 8. Nursing Dashboard

Best for nursing stations and ward nursing.

**Primary KPIs**

```text
Nursing tasks pending
Vitals due
Vitals recorded today
Patients under nursing care
```

**Secondary KPIs**

```text
Overdue observations
Medication administration pending, if module exists
Shift handover notes
Ward incidents
Nursing task completion rate
```

**Charts**

```text
Vitals recorded trend
Tasks pending vs completed
Patient observation trend
Nursing workload by shift
```

**Main dashboard personality**

```text
Ward care and observation board.
```

---

## 9. Pharmacy Dashboard

Best for pharmacy/dispensary.

**Primary KPIs**

```text
Pending prescriptions
Dispensed prescriptions today
Low stock items
Near-expiry items
```

**Secondary KPIs**

```text
Out-of-stock items
Returned prescriptions
Top dispensed products
Pharmacy stock value, if permitted
Pharmacy sales/revenue, if permitted
Stock adjustments
```

**Charts**

```text
Prescription status breakdown
Dispensing trend
Low stock trend
Near-expiry trend
Top dispensed medicines
```

**Main dashboard personality**

```text
Dispensing + stock-control dashboard.
```

---

## 10. Inpatient Dashboard

Best for wards/admissions.

**Primary KPIs**

```text
Active admissions
Occupied beds
Available beds
Discharges pending
```

**Secondary KPIs**

```text
Admissions today
Discharges today
Average length of stay
Bed occupancy rate
Patients awaiting transfer
Ward revenue, if permitted
```

**Charts**

```text
Admissions vs discharges
Bed occupancy trend
Length of stay trend
Ward workload by day
```

**Main dashboard personality**

```text
Ward board, bed/admission focused.
```

---

## 11. Maternity Dashboard

Best for maternity ward, antenatal, delivery, postnatal.

**Primary KPIs**

```text
Antenatal visits today
Active maternity admissions
Deliveries today
Postnatal follow-ups
```

**Secondary KPIs**

```text
Labour cases
High-risk pregnancy cases
C-section cases, if theatre linked
Newborn cases
Maternity bed occupancy
Maternity revenue, if permitted
```

**Charts**

```text
Antenatal trend
Deliveries trend
Postnatal follow-up trend
Maternity admissions vs discharges
```

**Main dashboard personality**

```text
Maternity care board.
```

---

## 12. Blood Bank Dashboard

Best for blood bank / blood storage.

**Primary KPIs**

```text
Available blood units
Reserved blood units
Pending blood requests
Near-expiry blood units
```

**Secondary KPIs**

```text
Expired units
Crossmatch pending
Units issued today
Units received today
Blood group availability
Critical stock blood groups
```

**Charts**

```text
Blood units by status
Blood group distribution
Blood requests trend
Near-expiry trend
```

**Main dashboard personality**

```text
Blood inventory and request board.
```

---

## 13. Mortuary Dashboard

Best for mortuary.

**Primary KPIs**

```text
Active mortuary cases
Storage occupied
Pending releases
Released cases today
```

**Secondary KPIs**

```text
New admissions today
Overdue storage cases
Mortuary capacity
Documentation pending
Mortuary charges, if permitted
```

**Charts**

```text
Mortuary admissions trend
Storage occupancy trend
Cases by status
Releases by period
```

**Main dashboard personality**

```text
Controlled registry/storage dashboard.
```

If the mortuary module is not fully built yet, show generic cards plus “not configured” states.

---

## 14. Ambulance Dashboard

Best for ambulance/transport.

**Primary KPIs**

```text
Active dispatches
Pending transport requests
Available ambulances
Completed transports today
```

**Secondary KPIs**

```text
Ambulances under maintenance
Average response time
Emergency transfers
Inter-facility transfers
Fuel/usage logs, if available
Ambulance charges, if permitted
```

**Charts**

```text
Dispatches by status
Response time trend
Transport requests trend
Ambulance utilisation
```

**Main dashboard personality**

```text
Dispatch command dashboard.
```

If ambulance module is not built yet, use generic fallback with clear “module unavailable” states.

---

## 15. Records Dashboard

Best for patient records, folders, archives.

**Primary KPIs**

```text
New patient records today
Folder requests pending
Records updated today
Merge requests pending
```

**Secondary KPIs**

```text
Archived records
Duplicate patient alerts
Records awaiting verification
Folder movements
Patient search activity
```

**Charts**

```text
New records trend
Folder request trend
Merge request status
Records activity by day
```

**Main dashboard personality**

```text
Records office / patient-file dashboard.
```

---

## 16. Finance Dashboard

Best for billing, cashier, accounts, claims.

**Primary KPIs**

```text
Collections today
Unpaid invoices
Outstanding balance
Open cashier sessions
```

**Secondary KPIs**

```text
AR aging total
Pending claims
Credit notes issued
Write-offs
Discounts given
Corporate sponsor balances
Patient statement balances
Revenue by department, if permitted
```

**Charts**

```text
Collections trend
Invoice aging breakdown
Claims status breakdown
Payment method breakdown
Revenue trend
```

**Main dashboard personality**

```text
Financial control-room dashboard.
```

Important: finance KPIs are permission-sensitive. Ordinary users should not see revenue, balances, AR aging, claim values, write-offs, or stock cost unless permitted.

---

## 17. Stores Dashboard

Best for stores, inventory, procurement.

**Primary KPIs**

```text
Stock requests pending
Stock issues today
Low stock items
Purchase requests pending
```

**Secondary KPIs**

```text
Goods received today
Supplier pending deliveries
Stock transfers
Stock adjustments
Near-expiry stock
Inventory value, if permitted
Supplier payables, if permitted
```

**Charts**

```text
Stock requests trend
Stock issues trend
Low stock trend
Purchase request status
Top issued items
```

**Main dashboard personality**

```text
Inventory operations dashboard.
```

---

## 18. Support Dashboard

Best for IT, maintenance, laundry, security, housekeeping.

**Primary KPIs**

```text
Open support tasks
Tasks completed today
Overdue tasks
Assigned staff
```

**Secondary KPIs**

```text
Requests by category
Pending maintenance
Equipment issues
Housekeeping tasks
Average resolution time
```

**Charts**

```text
Support tasks by status
Requests by category
Resolution trend
Workload by staff
```

**Main dashboard personality**

```text
Support operations dashboard.
```

If the support module is not mature, show generic fallback metrics.

---

## 19. Administrative Dashboard

Best for admin/management/settings/HR-like department.

**Primary KPIs**

```text
Active users
Active departments
Pending approvals
System activity today
```

**Secondary KPIs**

```text
New users
Role/permission changes
Inactive users
Department assignments
Audit events
Configuration changes
```

**Charts**

```text
User activity trend
Department activity trend
Audit event trend
Administrative workload
```

**Main dashboard personality**

```text
Admin control dashboard.
```

---

# The 4 top cards I would use for each

This is the simplest version for your UI registry.

| Department Type | Top KPI 1             | Top KPI 2         | Top KPI 3            | Top KPI 4             |
| --------------- | --------------------- | ----------------- | -------------------- | --------------------- |
| Consultation    | Waiting patients      | Consulting now    | Completed today      | Follow-ups due        |
| Emergency       | Active cases          | Critical/urgent   | Pending triage       | Completed today       |
| Investigation   | Pending requests      | Samples awaiting  | Results pending      | Completed today       |
| Radiology       | Pending imaging       | Scheduled imaging | Reports pending      | Completed today       |
| Procedure       | Pending procedures    | In progress       | Completed today      | Consumables used      |
| Theatre         | Scheduled surgeries   | Pre-op            | In progress          | Post-op               |
| Treatment       | Pending treatments    | Completed today   | Overdue tasks        | Consumables used      |
| Nursing         | Nursing tasks         | Vitals due        | Vitals recorded      | Patients under care   |
| Pharmacy        | Pending prescriptions | Dispensed today   | Low stock            | Near expiry           |
| Inpatient       | Active admissions     | Occupied beds     | Available beds       | Pending discharges    |
| Maternity       | Antenatal visits      | Active admissions | Deliveries today     | Postnatal follow-ups  |
| Blood Bank      | Available units       | Reserved units    | Pending requests     | Near-expiry units     |
| Mortuary        | Active cases          | Storage occupied  | Pending releases     | Released today        |
| Ambulance       | Active dispatches     | Pending requests  | Available ambulances | Completed transports  |
| Records         | New records           | Folder requests   | Updated records      | Merge requests        |
| Finance         | Collections today     | Unpaid invoices   | Outstanding balance  | Open cashier sessions |
| Stores          | Stock requests        | Stock issues      | Low stock            | Purchase requests     |
| Support         | Open tasks            | Completed today   | Overdue tasks        | Assigned staff        |
| Administrative  | Active users          | Departments       | Pending approvals    | Audit activity        |

---

# Important permission-sensitive KPIs

These must **not** show for everyone:

```text
Revenue
Collections
Outstanding balance
AR aging
Claims value
Credit notes
Write-offs
Discount totals
Stock value
Supplier payables
Inventory cost
Product cost
Patient financial balances
```

Use restricted cards or hide them before querying, like Phase 7/8 already established for revenue/stock datasets and drilldowns. 

---

# How this should enter the code

I would add a KPI registry like this:

```php
DepartmentDashboardLayoutRegistry
DepartmentDashboardKpiRegistry
DepartmentDashboardDataService
```

Each type should define:

```php
[
    'dashboard_name_key' => 'departments.dashboards.pharmacy.name',
    'layout_family' => 'dispensing_stock',
    'primary_kpis' => [
        'pending_prescriptions',
        'dispensed_today',
        'low_stock',
        'near_expiry',
    ],
    'secondary_kpis' => [
        'out_of_stock',
        'top_dispensed_products',
        'stock_adjustments',
        'pharmacy_revenue',
    ],
    'charts' => [
        'prescription_status_breakdown',
        'dispensing_trend',
        'low_stock_trend',
    ],
]
```

Then the dashboard layout decides **where** to display them, while the data service decides **how** to calculate them safely.

That way Emergency, Pharmacy, Finance, Laboratory, Records, and Stores will finally stop looking like copy-paste dashboards.
