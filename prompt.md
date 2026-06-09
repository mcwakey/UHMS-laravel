Yes — this is the right direction. Instead of one generic dashboard for everybody, UHMS should generate dashboards based on department type, because Pharmacy, Lab, Emergency, Theatre, Ward, Billing, Consultation, and Admin each need different KPIs and actions.

Also, the dashboard rollout must respect the existing UI rules: UHMS is Blade + Bootstrap 5 + Tabler Icons, not Tailwind; new pages should reuse shared components like <x-page-header>, <x-status-badge>, <x-empty-state>, and follow the standard layout order: header → KPIs → filters → main content → pagination.  

Copy this prompt:

You are working on UHMS — Ultimate Hospital Management System.
We now want to build dashboards based on Department Types.
The system should not rely on only one generic dashboard for all users.
Each department type should have its own dashboard experience, KPIs, queues, pending work, quick actions, alerts, and reports.
Examples of department types:
- Consultation / OPD
- Emergency / Casualty
- Admission / Ward
- Pharmacy
- Investigations / Laboratory
- Imaging / Radiology
- Procedures / Theatre
- Billing / Cashier
- Insurance / Claims
- Stock / Store / Procurement
- Blood Bank
- Administration / Management
- Accounting / Finance
- HR / Payroll
- Reception / Front Desk
Do not rebuild the whole dashboard system blindly.
Do not create random dashboard pages without checking existing modules.
Do not break existing dashboards.
Do not introduce a second UI framework.
Do not use Tailwind.
Use Blade, Bootstrap 5, Tabler Icons, and existing UHMS UI components.
Follow existing UHMS UI standards:
- use `<x-page-header>`
- use `<x-stat-card>` if available
- use `<x-filter-bar>` where filters exist
- use `<x-status-badge>` for statuses
- use `<x-empty-state>` for empty dashboard sections
- use Bootstrap cards/tables
- use semantic colors from `config/ui.php`
- avoid hidden critical clinical/financial/stock information
---
# 1. Main Objective
Build a department-type dashboard system.
When a user logs in or opens the dashboard, UHMS should show the dashboard relevant to their department type, role, and permissions.
The dashboard must answer:
- What is happening in this department now?
- What requires attention?
- What is pending?
- What has been completed today?
- What are the financial/clinical/stock risks?
- What quick actions should this user take?
---
# 2. Department Type Model / Configuration
Inspect the current department model first.
Search for:
```text
Department
department_type
type
category
service_type
department_id
current_department_id

If department types already exist, reuse them.

If not, add a clean enum/config for department types.

Recommended department type keys:

consultation
emergency
admission
pharmacy
investigation
imaging
procedure_theatre
billing
insurance_claims
stock_store
blood_bank
admin
accounting
hr
reception

Do not hardcode dashboard logic only by department name.

Use department type or department category.

⸻

3. Dashboard Routing

Create a dashboard resolver.

Suggested service:

DepartmentDashboardResolver

Responsibilities:

* detect authenticated user
* detect user role
* detect user primary department
* detect department type
* detect enabled modules
* detect permissions
* return the dashboard route/view/component to show

Suggested logic:

1. If user has global admin/management role → management dashboard
2. Else if user has assigned department → department-type dashboard
3. Else if user has role-specific dashboard → role dashboard
4. Else fallback to general staff dashboard

Do not break existing:

dashboard
admin.dashboard
doctor.dashboard
staff.dashboard

If those routes exist, keep them and route internally to the correct department dashboard.

⸻

4. Dashboard Layout Standard

Every department dashboard should follow this structure:

<x-page-header>
KPI cards
Alerts / attention cards
Queue / worklist
Recent activity
Quick actions
Reports / analytics links

Use this order:

Header
KPIs
Critical alerts
Main work queue
Secondary lists
Recent activity
Reports

This matches the UHMS UI governance and keeps dashboards consistent. The UI checklist also requires permissions on actions, status badges through <x-status-badge>, empty states through <x-empty-state>, and no hidden critical clinical/financial/stock data.  

⸻

5. Common Dashboard Data Contract

Each dashboard should return a common structure:

[
    'title' => 'Pharmacy Dashboard',
    'department' => $department,
    'kpis' => [],
    'alerts' => [],
    'queues' => [],
    'quickActions' => [],
    'recentActivity' => [],
    'reports' => [],
]

This allows consistent rendering while each department type supplies its own metrics.

⸻

6. Consultation / OPD Dashboard

KPIs:

* patients waiting
* consultations in progress
* completed consultations today
* referred to investigations
* referred to pharmacy
* follow-up appointments set
* average waiting time

Main queue:

* next patient in line
* waiting consultation patients
* patients currently being consulted
* patients returned from investigations/pharmacy
* high-priority patients

Quick actions:

* open consultation queue
* start next consultation
* view patient history
* create follow-up appointment

Alerts:

* long waiting patients
* patients returned with completed investigation results
* unpaid OPD patients if payment gate applies

⸻

7. Emergency / Casualty Dashboard

KPIs:

* active emergency cases
* red/orange triage cases
* waiting triage cases
* cases awaiting disposition
* emergency admissions pending
* emergency bed/bay occupancy
* emergency deaths/DOA today if permitted

Main queue:

* active emergency cases
* triage priority board
* pending disposition
* cases awaiting admission/theatre/transfer

Quick actions:

* create emergency case
* open triage
* assign bay/bed
* dispose to admission/theatre/OPD
* view emergency board

Alerts:

* critical triage
* long-stay emergency cases
* no bay/bed available
* pending emergency billing/consumables

Emergency should continue to behave as a visit/consultation-based workflow, not a parallel patient lifecycle. Existing emergency audit notes say emergency should be implemented as an overlay on the Visit workflow and not as a separate lifecycle.  

⸻

8. Admission / Ward Dashboard

KPIs:

* admitted patients
* beds occupied
* beds available
* discharges pending
* MAR overdue doses
* patients pending billing clearance
* deaths/discharges today

Main lists:

* current admissions
* pending discharges
* medication administration schedule
* overdue nursing tasks
* patients needing vitals

Quick actions:

* admit patient
* transfer bed
* open MAR
* discharge patient
* view bed map

Alerts:

* overdue MAR doses
* patients not discharged but clinically completed
* bed occupancy threshold
* unpaid discharge clearance

⸻

9. Pharmacy Dashboard

KPIs:

* prescriptions waiting
* prescriptions billed
* prescriptions dispensed
* partial dispensing
* out-of-stock drugs
* low-stock drugs
* revenue today if permitted

Main queue:

* prescriptions pending billing
* prescriptions pending dispensing
* partial dispensing follow-ups
* stock alerts

Quick actions:

* open dispensing
* bill selected drugs
* view drug catalogue
* receive stock
* request stock transfer

Alerts:

* out-of-stock drugs
* low-stock drugs
* urgent/emergency prescriptions

⸻

10. Investigations / Laboratory Dashboard

KPIs:

* requests waiting
* samples pending
* results pending
* verified results today
* rejected/cancelled tests
* urgent investigations
* consumables low stock

Main queue:

* pending requests
* accepted/in-progress requests
* results awaiting verification
* urgent/emergency investigations

Quick actions:

* accept request
* enter result
* verify result
* print result
* use consumables

Alerts:

* urgent requests
* delayed results
* low reagents/consumables

⸻

11. Imaging / Radiology Dashboard

If imaging is separate from investigations:

KPIs:

* imaging requests pending
* imaging completed today
* reports pending
* reports verified
* urgent imaging

Main queue:

* X-ray requests
* scan requests
* pending report entry
* pending verification

Quick actions:

* accept imaging request
* upload/enter report
* verify report
* print result

If imaging uses the investigation workflow, reuse investigation dashboard logic with imaging-specific filters.

⸻

12. Procedures / Theatre Dashboard

KPIs:

* pending procedure requests
* scheduled procedures
* in-theatre cases
* recovery cases
* completed procedures today
* cancelled/postponed cases

Main board:

* pending requests
* scheduled
* in theatre
* recovery
* completed

Quick actions:

* schedule procedure
* assign theatre room
* assign theatre team
* start procedure
* enter operative note
* complete procedure

Alerts:

* overdue scheduled procedures
* room conflicts
* missing team assignment
* pending pre-op checklist

⸻

13. Billing / Cashier Dashboard

KPIs:

* invoices pending payment
* payments received today
* unpaid OPD services
* partial payments
* refunds
* discounts/credit notes/write-offs pending approval
* cashier shift status

Main lists:

* unpaid invoices
* payments today
* failed accounting postings
* pending approval adjustments
* cashier shift summary

Quick actions:

* record payment
* open invoice
* issue receipt
* apply discount if permitted
* close cashier shift

Alerts:

* unpaid OPD service blocks
* failed accounting postings
* high-risk discounts/write-offs

⸻

14. Insurance / Claims Dashboard

KPIs:

* claims prepared
* claims submitted
* claims approved
* claims rejected
* claim payments received
* pending CCC/verification
* aging insurance receivables

Main lists:

* claims pending preparation
* claims ready for submission
* rejected claims needing correction
* unpaid approved claims

Quick actions:

* prepare claim
* submit claim
* update CCC/verification code
* record claim payment

Alerts:

* rejected claims
* missing CCC/reference
* aging insurance receivables

Do not hardcode NHIS. NHIS is only an insurance provider under an insurance type.

⸻

15. Stock / Store / Procurement Dashboard

KPIs:

* low-stock products
* out-of-stock products
* pending requisitions
* pending stock transfers
* pending purchase orders
* goods received today
* purchase returns

Main lists:

* low stock by department/location
* requisitions awaiting approval
* stock transfers awaiting receipt
* purchase orders awaiting receipt
* expired/damaged stock

Quick actions:

* create requisition
* approve requisition
* receive stock
* transfer stock
* stock adjustment
* create purchase order

Alerts:

* low stock
* expired stock
* stock inconsistencies
* PO received but stock not reflected

Stock/procurement has known historical complexity with drug/product ledgers and stock balances, so dashboard metrics must use the current canonical stock balance services and not mix incompatible ledgers blindly.  

⸻

16. Blood Bank Dashboard

KPIs:

* available blood units
* units expiring soon
* pending screening
* pending compatibility/crossmatch
* blood requests pending
* blood issued today
* transfusion reactions

Main lists:

* blood units by group
* pending donor screening
* pending recipient compatibility
* pending issue requests
* expiring units

Quick actions:

* register donor
* screen donation
* perform compatibility
* issue blood
* record transfusion reaction

Alerts:

* low blood group stock
* expiring units
* incompatible/emergency release
* pending WHO screening

⸻

17. Accounting / Finance Dashboard

KPIs:

* cash/bank balance if available
* receivables total
* payables total
* AR aging total
* AP aging total
* revenue today/month
* expenses today/month
* failed accounting postings

Main lists:

* failed postings
* journal entries pending posting
* receivables aging
* payables aging
* trial balance warning if unbalanced

Quick actions:

* create journal entry
* open trial balance
* open general ledger
* open AR aging
* open AP aging
* retry failed posting

Alerts:

* failed accounting posting
* closed period posting attempt
* unbalanced draft journal
* old receivables

⸻

18. HR / Payroll Dashboard

KPIs:

* staff active
* leave requests pending
* payroll batches pending
* payroll approved/paid
* attendance issues if available

Main lists:

* leave requests
* payroll batches
* staff onboarding/exit if available

Quick actions:

* approve leave
* process payroll
* view staff

Alerts:

* pending payroll approval
* unpaid payroll
* leave conflicts

⸻

19. Administration / Management Dashboard

KPIs:

* visits today
* admissions
* emergency cases
* revenue today
* receivables
* payables
* claims pending
* low stock
* bed occupancy
* department performance

Main panels:

* hospital overview
* department summaries
* financial overview
* operational alerts
* system alerts

Quick actions:

* manage users
* manage roles
* manage modules
* view logs
* open reports

Alerts:

* failed accounting postings
* critical permission changes
* modules disabled
* stockout
* unverified claims
* overdue receivables

⸻

20. Permissions

Every dashboard section must respect permissions.

Examples:

* billing KPIs require billing permissions
* accounting KPIs require accounting permissions
* clinical queues require clinical permissions
* stock cost values require inventory cost permissions
* admin/system cards require admin permissions

Do not show dashboard actions that always 403.

Backend must still enforce permissions.

⸻

21. Module Enable/Disable

Dashboard cards should respect module availability.

If module is disabled:

* hide that dashboard section
* or show disabled-module friendly state for admins only

Do not break dashboard rendering if a module is disabled.

⸻

22. Services to Create / Update

Create:

DepartmentDashboardResolver
DepartmentDashboardService
DashboardMetricService
DashboardQueueService
DashboardAlertService

Optional per-department services:

ConsultationDashboardService
EmergencyDashboardService
AdmissionDashboardService
PharmacyDashboardService
InvestigationDashboardService
TheatreDashboardService
BillingDashboardService
StockDashboardService
AccountingDashboardService
ClaimsDashboardService
BloodBankDashboardService

Prefer small focused services rather than one giant controller.

⸻

23. Views

Recommended structure:

resources/views/admin/dashboards/
├── index.blade.php
├── partials/
│   ├── kpi-card.blade.php
│   ├── alert-list.blade.php
│   ├── work-queue.blade.php
│   └── quick-actions.blade.php
├── department-types/
│   ├── consultation.blade.php
│   ├── emergency.blade.php
│   ├── admission.blade.php
│   ├── pharmacy.blade.php
│   ├── investigation.blade.php
│   ├── theatre.blade.php
│   ├── billing.blade.php
│   ├── stock.blade.php
│   ├── blood-bank.blade.php
│   ├── accounting.blade.php
│   ├── hr.blade.php
│   └── management.blade.php

Use shared components. Do not duplicate large blocks unnecessarily.

⸻

24. Sidebar / Navigation

Update sidebar/dashboard navigation carefully.

The existing sidebar is built through SidebarMenuBuilder, so do not hardcode menu links directly in Blade if the project uses the builder.  

Add either:

* one Dashboard route that resolves automatically
* or a dashboard submenu by department type for admins

Recommended:

Dashboard → My Dashboard
Dashboard → Management Dashboard
Dashboard → Department Dashboards

Only show department dashboards the user can access.

⸻

25. Activity Logs / Notifications

Do not log dashboard views unless policy requires it.

Do log dashboard-triggered actions, such as:

* approve requisition
* record payment
* assign bed
* verify result
* approve claim
* retry accounting posting

Dashboard is a view layer; action services own logs.

⸻

26. Performance

Dashboards must be fast.

Rules:

* avoid N+1 queries
* use aggregate queries
* cache expensive metrics if needed
* do not load thousands of records
* limit queues to top 10–20 items
* use date filters: today, this week, this month
* paginate or link to full list

Suggested:

Today by default
Refresh button
Last updated timestamp

⸻

27. UI Requirements

Each dashboard must include:

* <x-page-header>
* KPI cards
* alert cards
* work queue table/list
* quick action buttons
* empty states
* status badges
* permission-aware actions
* module-aware sections
* responsive layout

Use dashboard cards like:

<x-stat-card title="Waiting Patients" :value="$waiting" icon="ti-users" variant="warning" />

If <x-stat-card> is not available, create or reuse the existing KPI card pattern.

⸻

28. Manual Verification Strategy

Do not write the full automated test suite yet if the current implementation phase is still ongoing.

Manual verification required:

1. Login as doctor and confirm consultation dashboard appears.
2. Login as emergency staff and confirm emergency dashboard appears.
3. Login as pharmacist and confirm pharmacy dashboard appears.
4. Login as lab user and confirm investigation dashboard appears.
5. Login as theatre user and confirm theatre dashboard appears.
6. Login as billing/cashier and confirm billing dashboard appears.
7. Login as stock/store user and confirm stock dashboard appears.
8. Login as accountant and confirm accounting dashboard appears.
9. Login as admin and confirm management dashboard appears.
10. Confirm disabled modules do not break dashboard.
11. Confirm unauthorized dashboard actions are hidden and backend protected.
12. Confirm KPIs match existing module counts.
13. Confirm queues link to correct pages.
14. Confirm empty states display correctly.
15. Confirm dashboard performance is acceptable.
16. Confirm logs:audit Stage-2 gate remains green.

⸻

29. Documentation

Create:

docs/DEPARTMENT_TYPE_DASHBOARDS_REPORT.md

Include:

* department types implemented
* dashboard resolver behavior
* KPI definitions
* queue definitions
* permission rules
* module enable/disable rules
* UI components used
* manual verification completed
* known TODOs
* next recommendations

⸻

30. Acceptance Criteria

This task is complete when:

* department-type dashboard resolver exists
* each major department type has dashboard data/view
* users are routed to the correct dashboard
* KPIs are permission-aware
* queues are permission-aware
* disabled modules do not break dashboards
* dashboards follow UHMS UI standards
* dashboard actions route to existing workflows
* no duplicate business logic is created
* logs:audit Stage-2 gate remains green
* documentation is updated

⸻

31. Important Rules

Do not introduce Tailwind.
Do not introduce a second dashboard framework.
Do not create dashboard actions that bypass existing service workflows.
Do not duplicate module business logic inside dashboard controllers.
Do not show unauthorized financial/clinical/stock data.
Do not expose stock cost to unauthorized users.
Do not log dashboard views unless policy requires it.
Do not break existing dashboard routes.
Do not break SidebarMenuBuilder.
Do not ignore module enable/disable state.

Proceed with Department-Type Dashboards implementation now.