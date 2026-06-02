```text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to implement a full Statistical Reporting / Analytics module for UHMS.

Normal reports already show tables and records, but statistical reports must go further by showing hospital trends, counts, rankings, percentages, KPIs, comparisons, charts, and drill-downs.

The goal is to help hospital management, doctors, nurses, pharmacists, lab staff, theatre staff, claims officers, store officers, and administrators understand what is happening across the hospital.

This module must use existing UHMS data.

Do not create parallel data systems.

Do not break existing workflows.

Use data from:

- patients
- visits
- consultation sessions
- complaints
- diagnoses
- prescriptions
- pharmacy dispensing
- investigations
- procedures/theatre
- emergency cases
- admissions
- MAR / medication administration
- clinical tasks
- invoices
- invoice items
- payments
- claims
- products
- stock movements
- stock balances
- blood bank
- logs where useful

---

# 1. Main Objective

Build a Statistical Reports / Analytics module that provides:

1. Hospital activity statistics.
2. Diagnosis and disease statistics.
3. Complaint statistics.
4. Consultation statistics.
5. Pharmacy statistics.
6. Investigation statistics.
7. Procedure and Theatre statistics.
8. Emergency statistics.
9. Admission statistics.
10. MAR / medication administration statistics.
11. Billing and financial statistics.
12. Claims statistics.
13. Stock / inventory statistics.
14. Blood Bank statistics.
15. Staff performance statistics.
16. Management KPI dashboard.
17. Charts and trend views.
18. Drill-down from statistics to detailed records.
19. Export/print where available.

---

# 2. Important Rules

Do not calculate statistics from the wrong source.

Do not treat prescriptions as dispensed drugs.

Do not treat invoice items as diagnoses.

Do not treat billing as proof of service rendered.

Do not treat pharmacy billing as dispensing.

Do not treat medication dispensing as administration.

Do not create parallel reporting tables unless using cache/summary tables intentionally.

Do not load all hospital records by default.

Do not expose sensitive staff performance or financial reports to unauthorized users.

Do not bypass permissions.

Do not break existing reports.

---

# 3. Suggested Menu

Add menu:

Reports
    Statistical Reports

or:

Analytics
    Dashboard
    Clinical Statistics
    Diagnosis Statistics
    Pharmacy Statistics
    Investigation Statistics
    Procedure/Theatre Statistics
    Emergency Statistics
    Admission Statistics
    Billing Statistics
    Claims Statistics
    Stock Statistics
    Blood Bank Statistics
    Staff Performance

Use existing menu/module style.

---

# 4. Statistical Dashboard

Create a main Statistical Dashboard.

It should show high-level hospital KPIs.

Summary cards:

- Total patients registered
- New patients
- Returning patients
- Total visits
- OPD visits
- Emergency cases
- Admissions
- Discharges
- Active admissions
- Consultations
- Diagnoses recorded
- Prescriptions created
- Drugs dispensed
- Investigations requested
- Procedures performed
- Invoices created
- Total billed
- Total paid
- Outstanding balance
- Claims submitted
- Claims paid
- Low stock items
- Blood units available

Default period:

- Today
- This week
- This month
- Custom date range

---

# 5. Global Filters

All statistical pages should support common filters where applicable:

- date from
- date to
- department
- doctor/user
- patient
- visit type: OPD, Emergency, Admission
- insurance provider
- insurance type
- payment type
- status
- gender
- age group
- service
- product/drug
- diagnosis
- ward
- theatre room
- blood group

Default date range should be current month or last 30 days.

Do not load all-time data by default.

---

# 6. Charts Required

Use existing chart library if already installed.

Do not add a new chart library without checking existing dependencies.

If no chart library exists, implement summary cards and tables first, then leave chart TODO.

Recommended charts:

- line chart: visits over time
- line chart: revenue over time
- bar chart: top diagnoses
- bar chart: top complaints
- bar chart: most dispensed drugs
- bar chart: most requested investigations
- bar chart: most performed procedures
- pie/donut chart: emergency triage categories
- pie/donut chart: claims by status
- bar chart: bed occupancy by ward
- bar chart: stock consumption by department
- bar chart: blood units by blood group
- trend chart: admissions and discharges

---

# 7. Drill-down Behavior

Every statistic should allow drill-down where practical.

Examples:

Click “Malaria — 320 cases” opens detailed diagnosis list filtered by Malaria.

Click “Paracetamol — 4,200 dispensed” opens pharmacy dispensing detail.

Click “Emergency RED cases — 42” opens emergency case list filtered by RED.

Click “Outstanding Balance” opens unpaid invoice report.

Click “Low Stock Items” opens stock low list.

Do not show only summary numbers without a way to inspect details.

---

# 8. Hospital Activity Statistics

Create Hospital Activity Statistics page.

Show:

- total visits
- visits by day/week/month
- OPD visits
- emergency cases
- admissions
- discharges
- deaths
- consultations
- investigations
- procedures
- prescriptions
- invoices
- payments
- new vs returning patients

Charts:

- visits trend
- OPD vs Emergency vs Admission
- new vs returning patients
- patient gender distribution
- patient age group distribution

Detailed table:

- Date
- Total Visits
- OPD
- Emergency
- Admission
- Consultations
- Investigations
- Procedures
- Revenue

---

# 9. Diagnosis Statistics

Create Diagnosis Statistics page.

Show:

- total diagnoses recorded
- most common diagnoses
- diagnoses by department
- diagnoses by doctor
- diagnoses by age group
- diagnoses by gender
- diagnoses by visit type
- primary diagnosis counts
- provisional diagnosis counts
- final diagnosis counts
- top causes of death if death diagnosis exists

Important:

Diagnosis reports must use diagnosis/clinical diagnosis records.

Do not use invoice items as diagnosis source.

Example:

Top Diagnoses:
1. Malaria — 320 cases
2. Typhoid fever — 140 cases
3. Hypertension — 90 cases
4. Diabetes mellitus — 70 cases

Detailed drill-down columns:

- Date
- Patient
- Visit No.
- Diagnosis
- ICD-10 Code if available
- Diagnosis Type
- Primary/Secondary
- Doctor
- Department
- Visit Type

---

# 10. Complaint Statistics

Create Complaint Statistics page.

Show:

- most common complaints
- complaints by department
- complaints by doctor/user
- complaints by age group
- complaints by gender
- complaints leading to emergency
- complaints linked to diagnoses
- complaint trends over time

Examples:

- Fever
- Headache
- Abdominal pain
- Cough
- Chest pain

Detailed columns:

- Date
- Patient
- Visit No.
- Complaint
- Duration
- Severity
- Doctor/User
- Department
- Diagnosis if linked/available

Use patient complaint records / complaint catalogue data.

Do not use HOPC text as primary complaint count unless no structured complaints exist.

---

# 11. Consultation Statistics

Create Consultation Statistics page.

Show:

- number of consultations
- consultations by department
- consultations by doctor
- consultations by visit type
- started consultations
- completed consultations
- pending/incomplete consultations
- average consultation time if timestamps exist
- patients seen per doctor
- most active departments
- sessions per visit

Detailed columns:

- Date
- Visit No.
- Patient
- Department / Session
- Doctor
- Status
- Started At
- Completed At
- Diagnosis Count
- Investigations Requested
- Prescriptions Created
- Procedures Requested

Emergency sessions should also appear as clinical sessions where relevant, but should be distinguishable as Emergency Session.

---

# 12. Pharmacy Statistics

Create Pharmacy Statistics page.

Must clearly separate:

- prescribed drugs
- billed drugs
- dispensed drugs
- sold/revenue drugs

Reports:

## Most Prescribed Drugs

Source: prescription items.

Show:

- drug/product
- prescription count
- prescribed quantity
- prescribing doctors
- departments
- visit type

## Most Dispensed / Sold Drugs

Source: pharmacy dispensing records and invoice items where appropriate.

Show:

- drug/product
- quantity dispensed
- number of dispenses
- total sales value
- cash/insurance split
- stock location used
- department/visit type

## Prescription Fulfilment

Show:

- prescriptions created
- fully dispensed
- partially dispensed
- not dispensed
- out-of-stock requests
- billed but not dispensed

Detailed columns:

- Prescription No.
- Patient
- Visit No.
- Doctor
- Product
- Prescribed Qty
- Billed Qty
- Dispensed Qty
- Status
- Date

Important:

Do not calculate dispensed quantity from product table quantity.

Use dispensing records / stock movement source / pharmacy workflow records.

---

# 13. Investigation Statistics

Create Investigation Statistics page.

Show:

- investigation requests count
- requests by department
- most requested investigations
- completed investigations
- pending investigations
- verified results
- rejected/corrected results
- emergency investigations
- average turnaround time
- delayed investigations
- investigation revenue if billable

Reports:

## Most Requested Investigations

- investigation service
- department
- request count
- completed count
- pending count
- revenue if billable

## Turnaround Time

Measure:

request created
accepted
result entered
verified

Detailed columns:

- Request Date/Time
- Accepted Date/Time
- Result Entered At
- Verified At
- Turnaround Time
- Department
- Staff

## Result Status

- Patient
- Visit No.
- Investigation
- Department
- Requested By
- Status
- Result Status
- Verified By

---

# 14. Procedure / Theatre Statistics

Create Procedure/Theatre Statistics page.

Show:

- procedures requested
- procedures accepted
- procedures completed
- cancelled/postponed procedures
- most performed procedures
- procedures by department
- procedures by surgeon
- procedures by theatre room
- emergency procedures
- average procedure duration
- theatre utilization
- theatre consumables usage
- procedure revenue

Reports:

## Most Performed Procedures

- procedure
- department
- request count
- completed count
- cancelled count
- revenue

## Theatre Utilization

- theatre room
- scheduled cases
- completed cases
- total scheduled hours
- total actual hours
- utilization %
- cancelled/postponed count

## Procedures by Surgeon

- surgeon
- procedure count
- completed count
- cancelled count
- average duration

---

# 15. Emergency Statistics

Create Emergency Statistics page.

Show:

- emergency cases count
- emergency cases by triage category
- emergency cases by arrival mode
- emergency waiting time
- emergency disposition statistics
- emergency admissions
- emergency deaths/DOA
- emergency investigations
- emergency medications
- emergency procedures
- emergency bed occupancy
- emergency consumable usage

Reports:

## Emergency Triage

- RED
- ORANGE
- YELLOW
- GREEN
- BLACK
- count
- average waiting time
- admissions
- deaths/DOA

## Emergency Disposition

Group by:

- Admitted
- Discharged
- Transferred to OPD
- Transferred to Theatre
- Referred Out
- Left Against Medical Advice
- Absconded
- Died
- Dead on Arrival

## Emergency Waiting Time

Measure:

arrival time
triage time
first clinical note / first doctor assessment
disposition time

---

# 16. Admission Statistics

Create Admission Statistics page.

Show:

- admissions count
- active admissions
- discharged admissions
- average length of stay
- bed occupancy
- ward occupancy
- admission diagnoses
- admission medications
- admission bed charges
- admission consumables
- discharge outcomes
- death rate if applicable
- readmission rate if data supports it

Reports:

## Bed Occupancy

- Ward
- Total Beds
- Occupied Beds
- Available Beds
- Occupancy %

## Length of Stay

- Patient
- Admission No.
- Ward
- Admission Date
- Discharge Date
- Days Admitted
- Status

Formula:

Average Length of Stay = total admission days / discharged admissions

---

# 17. MAR / Medication Administration Statistics

Create MAR Statistics page.

Show:

- scheduled doses
- given doses
- missed doses
- held doses
- refused doses
- overdue doses
- medication administration by nurse
- medication administration by ward
- adverse reactions
- top administered medications

Reports:

## Nurse Administration

- nurse
- given doses
- missed/held/refused doses
- patients covered
- ward
- date

## Missed Dose

- patient
- medication
- scheduled time
- status
- reason
- nurse
- doctor
- ward

## Medication Compliance

- medication order
- total doses
- given
- missed
- held
- refused
- completion %

---

# 18. Billing / Financial Statistics

Create Billing Statistics page.

Show:

- total billed
- total paid
- outstanding balance
- discounts
- refunds/reversals
- revenue by department
- revenue by service
- revenue by product
- revenue by payment method
- revenue by insurance provider
- unpaid invoices
- billed but not rendered services
- rendered but unpaid services
- revenue trend

Reports:

## Revenue Summary

- department
- total billed
- total paid
- outstanding
- discounts
- refunds

## Invoice Item Statistics

- item/service/product
- source
- quantity
- cash price total
- insurance price total
- selected price total
- patient payable
- paid amount
- balance

## Payment Method Statistics

- cash
- mobile money
- card
- insurance
- bank transfer
- other

Use actual payment records.

Do not assume invoice creation means payment.

---

# 19. Claims Statistics

Create Claims Statistics page.

Show:

- claims prepared
- claims ready
- claims submitted
- claims approved
- claims rejected
- claims paid
- claims outstanding
- claims by insurance type
- claims by provider
- NHIA/NHIS claims
- missing CCC/verification code
- rejected claim reasons
- average claim processing time

Example:

NHIA Claims This Month:
- Prepared: 300
- Submitted: 260
- Approved: 220
- Rejected: 25
- Pending: 15

Detailed columns:

- Claim No.
- Patient
- Visit No.
- Insurance Type
- Provider
- Claim Amount
- Approved Amount
- Paid Amount
- Status
- Submitted At
- Paid At

---

# 20. Stock / Inventory Statistics

Create Stock Statistics page.

Show:

- stock balances by location
- fast-moving products
- slow-moving products
- low stock
- out of stock
- stock consumption by department
- stock value by location
- purchase trend
- supplier performance
- stock adjustment frequency
- expired/damaged stock
- department requisitions
- transfer performance

Reports:

## Fast Moving Products

- product
- total OUT quantity
- departments
- movement count
- current stock

## Slow Moving Products

- product
- opening stock
- quantity moved
- last movement date
- current stock

## Department Consumption

- department
- product
- quantity used
- usage source
- stock location
- date

## Stock Balance Matrix

- Product
- Main Store
- Pharmacy
- Ward
- Emergency
- Lab
- Theatre
- Total
- Status

Do not calculate stock from product quantity field.

Use stock balances / stock movements.

---

# 21. Blood Bank Statistics

Create Blood Bank Statistics page.

Show:

- blood units available by blood group
- blood units by component
- donations by month
- donor eligibility statistics
- deferred donor statistics
- screening failed statistics
- blood requests by department
- urgent/emergency blood requests
- crossmatch compatibility rate
- blood issued
- blood transfused
- transfusion reactions
- expired/discarded units
- low blood stock alerts

Reports:

## Blood Inventory

- blood group
- component
- available units
- reserved units
- issued units
- expiring soon
- expired

## Donor Screening

- eligible donors
- temporarily deferred
- permanently deferred
- deferral reasons

## Compatibility / Crossmatch

- request no
- patient
- recipient group
- unit no
- donor group
- component
- compatibility status
- crossmatch result

---

# 22. Staff Performance Statistics

Create Staff Performance page.

This must be permission-protected because it is sensitive.

Show:

- patients seen per doctor
- consultations completed per doctor
- diagnoses recorded per doctor
- medication administrations per nurse
- missed/held doses per nurse
- investigations verified per lab staff
- drugs dispensed per pharmacist
- payments recorded per cashier
- procedures performed per surgeon
- theatre cases by anaesthetist
- claims processed per claims officer
- stock movements by store officer

Important:

- Do not expose staff performance to normal users.
- Use permissions.
- Allow filtering by date range and department.

Suggested permission:

reports.staff_performance

---

# 23. Statistical Formulas

Implement formulas carefully.

Examples:

## Average Length of Stay

total admission days for discharged admissions / discharged admissions count

## Emergency Waiting Time

triage_time - arrival_time

## Investigation Turnaround Time

verified_at - requested_at

## Theatre Utilization

total scheduled/actual theatre time / available theatre time

## Payment Collection Rate

total paid / total billed

## Claims Approval Rate

approved claims / submitted claims

## Medication Administration Compliance

given doses / scheduled doses

## Bed Occupancy Rate

occupied beds / total available beds

## Blood Crossmatch Compatibility Rate

compatible crossmatches / total crossmatches

Document formulas in report UI tooltips where possible.

---

# 24. Statistical Services

Create or update services:

StatisticsDashboardService
HospitalActivityStatisticsService
ClinicalStatisticsService
DiagnosisStatisticsService
ComplaintStatisticsService
ConsultationStatisticsService
PharmacyStatisticsService
InvestigationStatisticsService
ProcedureStatisticsService
TheatreStatisticsService
EmergencyStatisticsService
AdmissionStatisticsService
MarStatisticsService
BillingStatisticsService
ClaimsStatisticsService
StockStatisticsService
BloodBankStatisticsService
StaffPerformanceStatisticsService

Keep controllers thin.

Use query builders and aggregate queries.

Avoid complex logic in Vue.

---

# 25. Controllers / Routes

Create or update controllers:

StatisticsDashboardController
HospitalActivityStatisticsController
DiagnosisStatisticsController
ComplaintStatisticsController
ConsultationStatisticsController
PharmacyStatisticsController
InvestigationStatisticsController
ProcedureStatisticsController
EmergencyStatisticsController
AdmissionStatisticsController
MarStatisticsController
BillingStatisticsController
ClaimsStatisticsController
StockStatisticsController
BloodBankStatisticsController
StaffPerformanceStatisticsController

Suggested routes:

GET /admin/statistics
GET /admin/statistics/activity
GET /admin/statistics/diagnoses
GET /admin/statistics/complaints
GET /admin/statistics/consultations
GET /admin/statistics/pharmacy
GET /admin/statistics/investigations
GET /admin/statistics/procedures
GET /admin/statistics/emergency
GET /admin/statistics/admission
GET /admin/statistics/mar
GET /admin/statistics/billing
GET /admin/statistics/claims
GET /admin/statistics/stock
GET /admin/statistics/blood-bank
GET /admin/statistics/staff-performance

Add export routes where needed.

Adapt to existing route conventions.

---

# 26. Frontend Pages

If using Inertia/Vue, create:

resources/js/Pages/Statistics/Dashboard.vue
resources/js/Pages/Statistics/Activity.vue
resources/js/Pages/Statistics/Diagnoses.vue
resources/js/Pages/Statistics/Complaints.vue
resources/js/Pages/Statistics/Consultations.vue
resources/js/Pages/Statistics/Pharmacy.vue
resources/js/Pages/Statistics/Investigations.vue
resources/js/Pages/Statistics/Procedures.vue
resources/js/Pages/Statistics/Emergency.vue
resources/js/Pages/Statistics/Admission.vue
resources/js/Pages/Statistics/Mar.vue
resources/js/Pages/Statistics/Billing.vue
resources/js/Pages/Statistics/Claims.vue
resources/js/Pages/Statistics/Stock.vue
resources/js/Pages/Statistics/BloodBank.vue
resources/js/Pages/Statistics/StaffPerformance.vue

Reusable components:

StatisticsFilterBar
KpiCard
TrendChart
BarChartCard
PieChartCard
TopListCard
StatisticsTable
DrilldownTable
ExportButtons
DateRangePicker
DepartmentFilter
UserFilter
PatientFilter
ServiceFilter
ProductFilter
DiagnosisFilter

Use existing UI design patterns.

---

# 27. Export / Print

Support:

- print
- CSV export
- Excel export if project already supports it
- PDF optional only if project already supports PDF

Export must respect permissions.

Export action must be logged.

Do not create fake export buttons.

---

# 28. Permissions

Add or verify:

statistics.view
statistics.dashboard.view
statistics.activity.view
statistics.clinical.view
statistics.diagnosis.view
statistics.complaints.view
statistics.consultation.view
statistics.pharmacy.view
statistics.investigations.view
statistics.procedures.view
statistics.theatre.view
statistics.emergency.view
statistics.admission.view
statistics.mar.view
statistics.billing.view
statistics.claims.view
statistics.stock.view
statistics.blood_bank.view
statistics.staff_performance.view
statistics.export

Also keep compatibility with existing report permissions:

reports.view
reports.export
reports.clinical
reports.pharmacy
reports.billing
reports.stock
reports.blood_bank

Backend and UI must enforce permissions.

---

# 29. Module Setup

Add module if module system exists:

Statistics / Analytics

Description:

Provides hospital-wide KPI dashboards, trend analysis, clinical statistics, financial statistics, operational statistics, stock statistics, blood bank statistics, and staff performance analytics.

Dependencies:

- Patients
- Visits
- Consultation
- Billing
- Stock
- Reports

Optional dependencies:

- Emergency
- Admission
- Pharmacy
- Investigations
- Procedures/Theatre
- Claims
- Blood Bank
- MAR

If a dependent module is disabled, hide or disable that report section gracefully.

---

# 30. Performance Requirements

Statistics can be heavy, so implement carefully.

Rules:

- default date range = current month / last 30 days
- use aggregate SQL queries
- paginate drill-down details
- avoid N+1 queries
- cache expensive dashboard summaries where safe
- do not load all records into memory
- use database indexes
- group by date using DB functions carefully
- support timezone if project does

Recommended indexes:

- visits.created_at
- visits.status
- visits.patient_id
- visits.visit_type
- diagnoses.created_at
- diagnoses.patient_id
- diagnoses.visit_id
- diagnoses.created_by
- patient_complaints.created_at
- prescriptions.created_at
- prescription_items.product_id
- pharmacy_dispensings.created_at
- investigation_requests.created_at
- procedure_requests.created_at
- theatre_cases.scheduled_start_at
- emergency_cases.arrival_time
- admissions.admitted_at / created_at
- medication_administrations.administered_at
- invoice_items.created_at
- payments.created_at
- stock_movements.created_at
- blood_requests.created_at
- blood_units.status

Adapt to actual table names.

---

# 31. Data Accuracy Rules

Use correct source of truth.

## Diagnosis statistics

Use diagnosis records.

## Complaint statistics

Use patient complaint records / complaint catalogue.

## Prescribed drugs

Use prescription items.

## Dispensed drugs

Use pharmacy dispensing records.

## Sold drugs

Use invoice items and payments where applicable.

## Administered medications

Use medication_administrations.

## Investigation counts

Use investigation requests/items.

## Investigation completed

Use result verified/completed statuses.

## Procedure performed

Use procedure/theatre completed records.

## Revenue

Use invoice_items/payments.

## Stock usage

Use stock movements.

## Blood units available

Use blood_units status AVAILABLE.

---

# 32. Reports / Documentation

Create documentation file:

docs/STATISTICAL_REPORTS_IMPLEMENTATION_REPORT.md

Include:

- reports implemented
- data sources used
- formulas used
- permissions added
- charts added
- exports added
- performance notes
- known limitations
- future improvements

Also create:

docs/STATISTICAL_REPORTS_FORMULAS.md

Explain formulas:

- average length of stay
- emergency waiting time
- investigation turnaround time
- theatre utilization
- payment collection rate
- claims approval rate
- medication compliance
- bed occupancy
- blood compatibility/crossmatch rate

---

# 33. Tests Required

Add or update tests.

## Dashboard

1. Statistics dashboard loads.
2. Dashboard uses default date range.
3. Dashboard shows visit counts.
4. Dashboard shows revenue summary.
5. Dashboard respects permissions.

## Diagnosis / Complaints

6. Diagnosis statistics count diagnoses correctly.
7. Most common diagnoses are ordered correctly.
8. Complaint statistics count complaints correctly.
9. Complaint statistics use patient complaints, not HOPC text.

## Consultation

10. Consultation statistics count sessions.
11. Consultation statistics group by doctor.
12. Emergency session is distinguishable from OPD consultation.

## Pharmacy

13. Most prescribed drugs use prescription items.
14. Most dispensed drugs use dispensing records.
15. Drug revenue uses invoice/payment data.
16. Partially dispensed drugs are counted.

## Investigations

17. Investigation statistics count requests.
18. Most requested investigations are ordered correctly.
19. Turnaround time is calculated.
20. Emergency investigations can be filtered.

## Procedures / Theatre

21. Procedure statistics count completed procedures.
22. Theatre utilization is calculated.
23. Cancelled/postponed cases are counted.

## Emergency

24. Emergency statistics group by triage category.
25. Emergency disposition statistics count correctly.
26. Emergency waiting time is calculated.

## Admission

27. Admission statistics count active admissions.
28. Length of stay is calculated.
29. Bed occupancy is calculated.

## MAR

30. MAR statistics count given/missed/held/refused doses.
31. Nurse administration statistics group by nurse.
32. Medication compliance is calculated.

## Billing / Claims

33. Billing statistics calculate billed/paid/outstanding.
34. Payment collection rate is calculated.
35. Claims approval rate is calculated.

## Stock

36. Fast-moving products are calculated from stock movements.
37. Stock balance matrix uses stock balances/movements.
38. Low/out stock statistics are correct.

## Blood Bank

39. Blood inventory statistics count available units.
40. Donor deferral statistics count deferred donors.
41. Crossmatch compatibility rate is calculated.
42. Transfusion reactions are counted.

## Staff Performance

43. Staff performance requires permission.
44. Unauthorized user cannot view staff performance.
45. Staff performance counts staff actions correctly.

## Export

46. Export requires permission.
47. Export respects filters.
48. Export action is logged.

---

# 34. Deliverables

Provide:

1. Gap analysis of current statistics/reporting system.
2. Statistics / Analytics module.
3. Statistical dashboard.
4. Hospital activity statistics.
5. Diagnosis statistics.
6. Complaint statistics.
7. Consultation statistics.
8. Pharmacy statistics.
9. Investigation statistics.
10. Procedure/Theatre statistics.
11. Emergency statistics.
12. Admission statistics.
13. MAR statistics.
14. Billing/financial statistics.
15. Claims statistics.
16. Stock statistics.
17. Blood Bank statistics.
18. Staff performance statistics.
19. Charts where available.
20. Drill-down tables.
21. Export/print support.
22. Permissions/menus/module setup.
23. Documentation reports.
24. Tests or verification notes.
25. Files modified.
26. Remaining TODOs.

---

# 35. Important Rules

Do not use wrong data sources.

Do not expose sensitive staff/financial data without permission.

Do not add a heavy chart library without checking existing dependencies.

Do not load all-time hospital data by default.

Do not make statistics mutate records.

Do not break existing reports.

Do not break consultation, emergency, admission, pharmacy, investigations, procedures/theatre, billing, claims, stock, blood bank, MAR, or visit workflows.

Now inspect the current UHMS implementation and build the Statistical Reports / Analytics module according to the requirements above.
```
