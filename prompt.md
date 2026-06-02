You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to implement a strong Reporting System across Consultation, Diagnosis, Pharmacy, Investigations, Procedures/Theatre, Emergency, Admission, Billing, Stock, and Claims.

We also need to add a Blood Bank Management module.

The goal is to help hospital administrators, doctors, pharmacists, lab staff, theatre staff, claims officers, and management understand hospital activity, workload, clinical trends, revenue, stock usage, and blood availability.

Do not break existing UHMS workflows.

Do not create parallel systems.

Use existing data from:
- patients
- visits
- consultation sessions
- diagnoses
- prescriptions
- pharmacy dispensing
- investigations
- procedures/theatre
- invoices
- invoice_items
- payments
- admissions
- emergency cases
- medication administration/MAR
- stock/products
- claims
- logs

---

# PART A — REPORTING SYSTEM

# 1. Main Objective

Build a reporting system that can generate operational, clinical, pharmacy, investigation, procedure, financial, stock, emergency, admission, and claims reports.

The system must support:

- date range filters
- department filters
- doctor/user filters
- patient filters
- visit type filters
- insurance filters
- service filters
- product/drug filters
- diagnosis filters
- export to Excel/CSV if available
- print-friendly report view
- summary cards
- tables
- charts where useful
- drill-down from summary to detailed records

Reports should not alter records.

Reports are read-only.

---

# 2. Reporting Dashboard

Create a main Reports Dashboard.

Suggested menu:

Reports
├── Clinical Reports
├── Diagnosis Reports
├── Consultation Reports
├── Pharmacy Reports
├── Investigation Reports
├── Procedure / Theatre Reports
├── Emergency Reports
├── Admission Reports
├── Billing & Payment Reports
├── Insurance / Claims Reports
├── Stock / Product Reports
├── Blood Bank Reports
└── Custom Reports

The dashboard should show summary cards such as:

- Total visits
- Total consultations
- Total emergency cases
- Total admissions
- Total diagnoses recorded
- Total prescriptions
- Total drugs dispensed
- Total investigations requested
- Total procedures performed
- Total revenue
- Outstanding balance
- Claims submitted
- Blood units available
- Low blood stock alerts

---

# 3. General Report Features

Every report should support:

- date from
- date to
- department
- staff/user
- patient
- visit type: OPD, Emergency, Admission
- insurance provider
- payment type
- status
- export
- print
- reset filters

Where applicable, also support:

- diagnosis
- drug/product
- service
- investigation department
- procedure department
- theatre room
- ward
- blood group
- donor type

---

# 4. Consultation Reports

Create consultation reports showing:

- number of consultations
- consultations by department
- consultations by doctor
- consultations by date
- consultations by visit type
- started consultations
- completed consultations
- pending/incomplete consultations
- average consultations per doctor
- consultation sessions by patient

Detailed table columns:

- Date
- Visit No.
- Patient
- Department/Session
- Doctor
- Status
- Started At
- Completed At
- Diagnosis count
- Investigations requested
- Prescriptions created
- Procedures requested

---

# 5. Diagnosis Reports

Create diagnosis reports showing:

- total diagnoses recorded
- most common diagnoses
- diagnoses by department
- diagnoses by doctor
- diagnoses by age group
- diagnoses by gender
- diagnoses by visit type
- primary diagnosis counts
- final diagnosis counts
- provisional diagnosis counts

Example summary:

Most common diagnoses:
1. Malaria — 320 cases
2. Typhoid fever — 140 cases
3. Hypertension — 90 cases
4. Diabetes mellitus — 70 cases

Detailed table columns:

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

Important:
Diagnosis reports must use existing diagnosis records.
Do not use invoice items as diagnosis source.

---

# 6. Complaints Reports

Create complaint reports showing:

- most common complaints
- complaints by department
- complaints by doctor
- complaints by age group
- complaints by gender
- complaints leading to emergency
- complaints linked to most diagnoses

Example:

Most common complaints:
- Fever
- Headache
- Abdominal pain
- Cough
- Chest pain

Detailed columns:

- Date
- Patient
- Complaint
- Duration
- Severity
- Doctor/User
- Department
- Diagnosis if linked/available

---

# 7. Pharmacy Reports

Create pharmacy reports showing:

- prescriptions created
- prescriptions billed
- prescriptions dispensed
- partially dispensed prescriptions
- not dispensed prescriptions
- most sold/dispensed drugs
- most prescribed drugs
- top revenue drugs
- drug usage by department
- drug usage by doctor
- drug usage by insurance provider
- out-of-stock requested drugs
- stock movement from dispensing

Important distinction:

Most prescribed drugs = from prescription items.
Most sold/dispensed drugs = from pharmacy dispensing and invoice items.
Most revenue drugs = from invoice_items/payment data.

Reports:

## Most Prescribed Drugs

Columns:
- Drug/Product
- Prescribed Quantity
- Prescription Count
- Doctors
- Department

## Most Sold / Dispensed Drugs

Columns:
- Drug/Product
- Quantity Dispensed
- Number of Dispenses
- Total Sales Value
- Pharmacy Stock Used
- Insurance/Cash split

## Prescription Status Report

Columns:
- Prescription No.
- Patient
- Doctor
- Date
- Items
- Billed Items
- Dispensed Items
- Status

Do not calculate pharmacy sales from product table quantity.
Use invoice_items and dispensing records.

---

# 8. Investigation Reports

Create investigation reports showing:

- investigation requests count
- requests by department
- requests by service/test
- most requested investigations
- completed investigations
- pending investigations
- verified results
- rejected/corrected results
- emergency investigations
- turnaround time

Reports:

## Most Requested Investigations

Columns:
- Investigation Service
- Department
- Request Count
- Completed Count
- Pending Count
- Revenue if billable

## Investigation Turnaround Time

Columns:
- Request Date/Time
- Accepted Date/Time
- Result Entered At
- Verified At
- Turnaround Time
- Department
- Staff

## Investigation Result Status

Columns:
- Patient
- Visit No.
- Investigation
- Department
- Requested By
- Status
- Result Status
- Verified By

---

# 9. Procedure / Theatre Reports

Create procedure and theatre reports showing:

- procedures requested
- procedures accepted
- procedures completed
- cancelled/postponed procedures
- most performed procedures
- procedures by department
- procedures by surgeon
- procedures by theatre room
- emergency procedures
- theatre utilization
- average procedure duration
- theatre consumables usage
- procedure revenue

Reports:

## Most Performed Procedures

Columns:
- Procedure
- Department
- Count
- Completed Count
- Cancelled Count
- Revenue

## Theatre Utilization Report

Columns:
- Theatre Room
- Scheduled Cases
- Completed Cases
- Total Scheduled Hours
- Total Actual Hours
- Utilization %
- Cancelled/Postponed

## Procedures by Surgeon

Columns:
- Surgeon
- Procedure Count
- Completed
- Cancelled
- Average Duration

---

# 10. Emergency Reports

Create emergency reports showing:

- emergency cases count
- emergency cases by triage category
- emergency cases by arrival mode
- emergency waiting time
- emergency dispositions
- emergency admissions
- emergency deaths/DOA
- emergency investigations
- emergency medications
- emergency procedures
- emergency bed usage
- emergency consumable usage

Reports:

## Emergency Triage Report

Columns:
- Triage Category
- Count
- Average Waiting Time
- Admissions
- Deaths/DOA

## Emergency Disposition Report

Disposition groups:
- Admitted
- Discharged
- Transferred to OPD
- Transferred to Theatre
- Referred Out
- LAMA
- Absconded
- Died
- Dead on Arrival

---

# 11. Admission Reports

Create admission reports showing:

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

Reports:

## Bed Occupancy Report

Columns:
- Ward
- Total Beds
- Occupied Beds
- Available Beds
- Occupancy %

## Length of Stay Report

Columns:
- Patient
- Admission No.
- Ward
- Admission Date
- Discharge Date
- Days Admitted
- Status

---

# 12. MAR / Medication Administration Reports

Create MAR reports showing:

- scheduled doses
- given doses
- missed doses
- held doses
- refused doses
- overdue doses
- nurse administration count
- medication administration by ward
- adverse reactions

Reports:

## Nurse Administration Report

Columns:
- Nurse
- Given Doses
- Missed/Held/Refused
- Patients Covered
- Ward
- Date

## Missed Dose Report

Columns:
- Patient
- Medication
- Scheduled Time
- Status
- Reason
- Nurse
- Doctor
- Ward

---

# 13. Billing & Payment Reports

Create billing reports showing:

- total invoice value
- total paid
- outstanding balance
- discounts
- refunds/reversals
- revenue by department
- revenue by service
- revenue by payment type
- revenue by insurance provider
- unpaid invoices
- billed but not rendered services
- rendered but unpaid services

Reports:

## Revenue Summary

Columns:
- Department
- Total Billed
- Total Paid
- Outstanding
- Discounts
- Refunds

## Invoice Item Report

Columns:
- Invoice No.
- Patient
- Item
- Source
- Cash Price
- Insurance Price
- Selected Price
- Quantity
- Patient Payable
- Paid Amount
- Balance
- Payment Status

---

# 14. Insurance / Claims Reports

Create claims reports showing:

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

Columns:
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

# 15. Stock / Product Reports

Create stock reports showing:

- stock balances by location
- product movement history
- low stock
- out of stock
- fast-moving products
- slow-moving products
- stock consumption by department
- stock adjustment report
- purchase order report
- supplier report

Reports:

## Stock Balance Matrix

Columns:
- Product
- Main Store Qty
- Pharmacy Qty
- Ward Qty
- Emergency Qty
- Lab Qty
- Theatre Qty
- Total
- Status

## Fast Moving Products

Columns:
- Product
- Total OUT Quantity
- Departments
- Movement Count
- Current Stock

## Department Consumption

Columns:
- Department
- Product
- Quantity Used
- Usage Source
- Stock Location
- Date

---

# 16. Report Charts

Add charts where useful.

Use simple chart components already available in the project or a lightweight existing chart library.

Do not add a new chart library without checking existing dependencies.

Recommended charts:

- line chart: visits/consultations over time
- bar chart: top diagnoses
- bar chart: most sold drugs
- pie/donut chart: emergency triage categories
- bar chart: investigation request count
- bar chart: procedure count
- line chart: revenue trend
- bar chart: stock consumption by department
- pie chart: claim statuses

If no chart library exists, implement tables first and leave chart TODO.

---

# 17. Report Export

Reports should support:

- print
- CSV export
- Excel export if project already supports it
- PDF optional if project already supports PDF generation

Do not implement fake exports.

If Excel package exists, use it.
If not, implement CSV export first.

Export actions must respect permissions and be logged.

---

# 18. Report Permissions

Add or verify permissions:

reports.view
reports.export
reports.clinical
reports.consultation
reports.diagnosis
reports.complaints
reports.pharmacy
reports.investigations
reports.procedures
reports.theatre
reports.emergency
reports.admission
reports.mar
reports.billing
reports.claims
reports.stock
reports.blood_bank

Permissions must be enforced in backend and UI.

---

# 19. Reporting Services

Create or update:

ReportDashboardService
ConsultationReportService
DiagnosisReportService
ComplaintReportService
PharmacyReportService
InvestigationReportService
ProcedureReportService
TheatreReportService
EmergencyReportService
AdmissionReportService
MarReportService
BillingReportService
ClaimsReportService
StockReportService
BloodBankReportService

Keep controllers thin.

Use query services for complex reports.

Avoid putting large queries inside Vue/Blade.

---

# 20. Reporting Controllers / Routes

Suggested controllers:

ReportDashboardController
ClinicalReportController
DiagnosisReportController
PharmacyReportController
InvestigationReportController
ProcedureReportController
TheatreReportController
EmergencyReportController
AdmissionReportController
BillingReportController
ClaimsReportController
StockReportController
BloodBankReportController

Suggested routes:

GET /admin/reports
GET /admin/reports/consultations
GET /admin/reports/diagnoses
GET /admin/reports/complaints
GET /admin/reports/pharmacy
GET /admin/reports/investigations
GET /admin/reports/procedures
GET /admin/reports/theatre
GET /admin/reports/emergency
GET /admin/reports/admission
GET /admin/reports/mar
GET /admin/reports/billing
GET /admin/reports/claims
GET /admin/reports/stock
GET /admin/reports/blood-bank

Add export routes where needed.

Adapt to existing route conventions.

---

# 21. Reporting UI

If using Inertia/Vue, create:

resources/js/Pages/Reports/Dashboard.vue
resources/js/Pages/Reports/Consultations.vue
resources/js/Pages/Reports/Diagnoses.vue
resources/js/Pages/Reports/Complaints.vue
resources/js/Pages/Reports/Pharmacy.vue
resources/js/Pages/Reports/Investigations.vue
resources/js/Pages/Reports/Procedures.vue
resources/js/Pages/Reports/Theatre.vue
resources/js/Pages/Reports/Emergency.vue
resources/js/Pages/Reports/Admission.vue
resources/js/Pages/Reports/Mar.vue
resources/js/Pages/Reports/Billing.vue
resources/js/Pages/Reports/Claims.vue
resources/js/Pages/Reports/Stock.vue
resources/js/Pages/Reports/BloodBank.vue

Reusable components:

ReportFilterBar
ReportSummaryCards
ReportTable
ReportChart
ReportExportButtons
DateRangeFilter
DepartmentFilter
UserFilter
PatientFilter
ServiceFilter
ProductFilter

---

# 22. Report Performance

Reports must be efficient.

Rules:

- use aggregate queries
- avoid N+1
- paginate details
- cache heavy summary reports where safe
- restrict default date range to current month or last 30 days
- do not load all hospital history by default
- use indexes on frequently filtered fields

Important indexes:

- visits.created_at
- visits.status
- visits.patient_id
- diagnoses.created_at
- diagnoses.diagnosis_id/name
- invoice_items.created_at
- invoice_items.service_id
- invoice_items.product_id
- invoice_items.source_type/source_id
- payments.created_at
- investigation_requests.created_at
- procedure_requests.created_at
- medication_administrations.administered_at
- stock_movements.created_at
- stock_movements.product_id
- stock_movements.stock_location_id

---

# PART B — BLOOD BANK MANAGEMENT

# 23. Blood Bank Main Objective

Add Blood Bank Management to UHMS.

Blood Bank should manage:

1. Blood donors.
2. Blood donation records.
3. Blood units/bags.
4. Blood grouping.
5. Screening/testing.
6. Blood inventory.
7. Blood requests.
8. Crossmatching.
9. Blood issue/transfusion.
10. Blood wastage/expiry.
11. Blood bank reports.
12. Billing integration if applicable.
13. Stock/location integration where useful.
14. Visit Preview integration.

Do not treat blood like normal product stock only.

Blood has special clinical requirements:
- blood group
- Rh factor
- donor
- donation date
- expiry date
- screening status
- crossmatch status
- unit number
- component type
- transfusion tracking

---

# 24. Blood Bank Menu

Suggested menu:

Blood Bank
├── Dashboard
├── Donors
├── Donations
├── Blood Units
├── Blood Requests
├── Crossmatching
├── Blood Issue / Transfusion
├── Wastage / Expired Units
├── Reports
└── Settings

---

# 25. Blood Groups and Components

Support blood groups:

A+
A-
B+
B-
AB+
AB-
O+
O-

Blood components:

WHOLE_BLOOD
PACKED_RED_CELLS
PLASMA
PLATELETS
CRYOPRECIPITATE

Each component may have different expiry rules.

Default expiry examples:
- Whole blood: configurable
- Packed red cells: configurable
- Platelets: configurable, often shorter
- Plasma: configurable

Make expiry configurable.

---

# 26. Blood Donors

Create:

blood_donors

Fields:

id
donor_number
patient_id nullable
first_name
last_name
gender nullable
date_of_birth nullable
phone nullable
address nullable
blood_group nullable
rh_factor nullable
donor_type
last_donation_date nullable
status
notes nullable
created_by
created_at
updated_at

Donor types:

VOLUNTARY
FAMILY_REPLACEMENT
PAID
DIRECTED
UNKNOWN

Donor statuses:

ACTIVE
DEFERRED
PERMANENTLY_DEFERRED
INACTIVE

A donor may or may not be an existing patient.

If donor is already a patient, link patient_id.

---

# 27. Blood Donations

Create:

blood_donations

Fields:

id
donation_number
blood_donor_id
donation_date
donation_type
collection_volume_ml nullable
blood_group
rh_factor
screening_status
status
collected_by
notes nullable
created_at
updated_at

Donation types:

WHOLE_BLOOD
APHERESIS
DIRECTED

Screening statuses:

PENDING
PASSED
FAILED
INCONCLUSIVE

Donation statuses:

COLLECTED
SCREENING_PENDING
APPROVED
REJECTED
PROCESSED
CANCELLED

Rules:

- donation must be screened before blood units become available
- failed screening makes units unavailable
- donation action should create one or more blood units depending component processing

---

# 28. Blood Units / Bags

Create:

blood_units

Fields:

id
unit_number
blood_donation_id nullable
blood_donor_id nullable
blood_group
rh_factor
component_type
volume_ml nullable
collection_date
expiry_date
screening_status
crossmatch_status nullable
status
storage_location_id nullable
current_location nullable
reserved_for_patient_id nullable
reserved_for_visit_id nullable
issued_to_patient_id nullable
issued_to_visit_id nullable
issued_at nullable
issued_by nullable
discarded_at nullable
discarded_by nullable
discard_reason nullable
created_at
updated_at

Blood unit statuses:

AVAILABLE
RESERVED
CROSSMATCHED
ISSUED
TRANSFUSED
EXPIRED
DISCARDED
QUARANTINED
REJECTED

Crossmatch statuses:

NOT_REQUIRED
PENDING
COMPATIBLE
INCOMPATIBLE
CANCELLED

Rules:

- only PASSED screening units can become AVAILABLE
- expired units cannot be issued
- discarded/rejected units cannot be issued
- issued unit cannot be issued again
- unit number must be unique

---

# 29. Blood Storage Locations

Create or reuse stock locations.

Blood storage may need special locations:

- Main Blood Bank Fridge
- Emergency Blood Fridge
- Theatre Blood Fridge
- Ward Blood Fridge

Recommended table if not using stock_locations:

blood_storage_locations

Fields:

id
name
code
location_type
department_id nullable
temperature_range nullable
status
is_active
notes nullable

If existing stock locations can support this, reuse them with type = BLOOD_BANK.

---

# 30. Blood Requests

Create:

blood_requests

Fields:

id
request_number
patient_id
visit_id nullable
admission_id nullable
emergency_case_id nullable
theatre_case_id nullable
requested_by
department_id nullable
blood_group_requested nullable
component_type
units_requested
priority
clinical_indication
status
approved_by nullable
approved_at nullable
rejected_by nullable
rejected_at nullable
rejection_reason nullable
created_at
updated_at

Priorities:

ROUTINE
URGENT
EMERGENCY

Statuses:

REQUESTED
APPROVED
REJECTED
CROSSMATCH_PENDING
CROSSMATCHED
PARTIALLY_ISSUED
ISSUED
CANCELLED
COMPLETED

Sources:
- Emergency
- Admission
- Theatre
- Procedure
- OPD/Consultation

---

# 31. Crossmatching

Create:

blood_crossmatches

Fields:

id
blood_request_id
blood_unit_id
patient_id
visit_id nullable
result
performed_by
performed_at
notes nullable
created_at
updated_at

Results:

COMPATIBLE
INCOMPATIBLE
PENDING
CANCELLED

Rules:

- only compatible units can be issued where crossmatch is required
- incompatible units must not be issued
- crossmatch result must be visible on request page

---

# 32. Blood Issue / Transfusion

Create:

blood_issues

Fields:

id
blood_request_id
blood_unit_id
patient_id
visit_id nullable
admission_id nullable
emergency_case_id nullable
theatre_case_id nullable
issued_by
issued_at
received_by nullable
transfused_by nullable
transfused_at nullable
status
reaction_reported boolean default false
reaction_notes nullable
notes nullable
created_at
updated_at

Statuses:

ISSUED
RECEIVED
TRANSFUSED
RETURNED
CANCELLED

Rules:

- issuing blood changes blood_unit.status = ISSUED
- transfused blood changes blood_unit.status = TRANSFUSED
- returned blood rules should be configurable
- reaction must be recorded if present
- transfusion should appear in Visit Preview

---

# 33. Blood Wastage / Expiry

Create or update handling for:

- expired blood
- discarded blood
- rejected screened blood
- damaged bags
- cold chain failure

Record:

blood_unit_id
reason
discarded_by
discarded_at
notes

Discard reasons:

EXPIRED
SCREENING_FAILED
DAMAGED_BAG
COLD_CHAIN_FAILURE
CONTAMINATION
RETURN_REJECTED
OTHER

Expired units should automatically be marked EXPIRED by scheduled command.

Command:

php artisan blood-bank:expire-units

---

# 34. Blood Bank Dashboard

Create dashboard showing:

- total available units
- available units by blood group
- available units by component
- expiring soon
- expired units
- pending requests
- urgent/emergency requests
- units issued today
- donations today
- screening pending
- low blood stock alerts

Example blood inventory card:

O+ : 12 units
O- : 3 units
A+ : 8 units
B+ : 6 units
AB- : 1 unit

---

# 35. Blood Request Workflow

Workflow:

Doctor/Emergency/Theatre requests blood
↓
Blood bank reviews request
↓
Blood bank approves or rejects
↓
Crossmatch compatible units
↓
Reserve units
↓
Issue blood
↓
Transfusion recorded
↓
Visit Preview updated
↓
Billing if applicable

Blood request can originate from:

- Emergency Case
- Admission
- Theatre Case
- Consultation
- Procedure

---

# 36. Billing Integration for Blood

Blood may be billable depending hospital policy.

If billable:

- blood component/service should create invoice item
- use BillingService
- use visit invoice
- use insurance pricing where applicable
- source_type/source_id prevents duplicate billing

Possible billable sources:

source_type = blood_request
source_type = blood_issue
source_type = blood_unit

Do not create separate blood invoice.

If blood is not billable, no invoice item should be created.

Make billing configurable.

---

# 37. Blood Bank Notifications

Use NotificationService.

Notify:

- new blood request
- urgent/emergency blood request
- blood request approved/rejected
- crossmatch completed
- blood issued
- transfusion reaction reported
- unit expiring soon
- unit expired
- low blood stock

Recipients:

- blood bank staff
- requesting doctor/team
- emergency/theatre team
- ward nurses where applicable
- administrators for low stock

---

# 38. Blood Bank Logs

Use ActivityLogService.

Log:

- donor created/updated
- donation recorded
- screening result updated
- blood unit created
- blood unit approved/rejected
- blood request created
- crossmatch performed
- blood issued
- transfusion recorded
- reaction recorded
- unit discarded/expired

---

# 39. Blood Bank Permissions

Add permissions:

blood_bank.dashboard.view
blood_bank.donors.view
blood_bank.donors.create
blood_bank.donors.update
blood_bank.donations.view
blood_bank.donations.create
blood_bank.donations.screen
blood_bank.units.view
blood_bank.units.update
blood_bank.requests.view
blood_bank.requests.create
blood_bank.requests.approve
blood_bank.requests.reject
blood_bank.crossmatch.perform
blood_bank.issue
blood_bank.transfusion.record
blood_bank.discard
blood_bank.reports.view
blood_bank.settings.manage

Also add:

reports.blood_bank

---

# 40. Blood Bank Reports

Create reports:

## Blood Inventory Report

Columns:
- Blood Group
- Component
- Available Units
- Reserved Units
- Issued Units
- Expiring Soon
- Expired

## Donation Report

Columns:
- Donor
- Donation No.
- Blood Group
- Component
- Donation Date
- Screening Status
- Status

## Blood Request Report

Columns:
- Request No.
- Patient
- Visit/Admission/Emergency/Theatre
- Component
- Units Requested
- Priority
- Status
- Requested By
- Requested At

## Blood Issue / Transfusion Report

Columns:
- Patient
- Unit No.
- Blood Group
- Component
- Issued At
- Issued By
- Transfused At
- Transfused By
- Reaction

## Expiry / Wastage Report

Columns:
- Unit No.
- Blood Group
- Component
- Expiry Date
- Discard Reason
- Discarded By
- Discarded At

---

# 41. Blood Bank Visit Preview Integration

Update Visit Preview to include:

- blood request
- approval/rejection
- crossmatch result
- unit issued
- transfusion recorded
- reaction if any

Every entry must show:
- time
- user
- patient
- unit number
- blood group/component
- status

---

# 42. Blood Bank UI Pages

If using Inertia/Vue, create:

resources/js/Pages/BloodBank/Dashboard.vue
resources/js/Pages/BloodBank/Donors.vue
resources/js/Pages/BloodBank/Donations.vue
resources/js/Pages/BloodBank/Units.vue
resources/js/Pages/BloodBank/Requests.vue
resources/js/Pages/BloodBank/Crossmatches.vue
resources/js/Pages/BloodBank/Issues.vue
resources/js/Pages/BloodBank/Reports.vue
resources/js/Pages/BloodBank/Settings.vue

Components:

BloodGroupBadge
BloodUnitStatusBadge
BloodRequestStatusBadge
BloodDonorForm
BloodDonationForm
BloodRequestForm
CrossmatchModal
BloodIssueModal
TransfusionRecordModal
BloodInventoryCards
BloodExpiryAlert

---

# 43. Blood Bank Routes / Controllers

Suggested controllers:

BloodBankDashboardController
BloodDonorController
BloodDonationController
BloodUnitController
BloodRequestController
BloodCrossmatchController
BloodIssueController
BloodWastageController
BloodBankReportController
BloodBankSettingsController

Suggested routes:

GET /admin/blood-bank
GET /admin/blood-bank/donors
POST /admin/blood-bank/donors
GET /admin/blood-bank/donations
POST /admin/blood-bank/donations
GET /admin/blood-bank/units
PATCH /admin/blood-bank/units/{bloodUnit}
GET /admin/blood-bank/requests
POST /admin/blood-bank/requests
POST /admin/blood-bank/requests/{bloodRequest}/approve
POST /admin/blood-bank/requests/{bloodRequest}/reject
POST /admin/blood-bank/requests/{bloodRequest}/crossmatch
POST /admin/blood-bank/requests/{bloodRequest}/issue
POST /admin/blood-bank/issues/{bloodIssue}/transfuse
POST /admin/blood-bank/units/{bloodUnit}/discard
GET /admin/blood-bank/reports

Adapt to existing route conventions.

---

# 44. Blood Bank Services

Create:

BloodBankDashboardService
BloodDonorService
BloodDonationService
BloodUnitService
BloodRequestService
BloodCrossmatchService
BloodIssueService
BloodInventoryService
BloodExpiryService
BloodBankBillingService
BloodBankReportService

---

# 45. Blood Bank Validation Rules

Donor:
- donor_number unique
- name required
- phone optional
- blood group optional at creation
- donor status valid

Donation:
- donor required
- donation date required
- blood group required
- component/donation type required
- screening status required

Blood Unit:
- unit number unique
- blood group required
- component type required
- expiry date required
- status valid

Blood Request:
- patient required
- visit/admission/emergency/theatre context required where applicable
- component type required
- units requested > 0
- priority required
- clinical indication required

Crossmatch:
- blood request required
- blood unit required
- compatible/incompatible result required
- only available/reserved units can be crossmatched

Issue:
- request approved
- unit compatible if crossmatch required
- unit not expired
- unit not already issued/transfused/discarded
- issued_by current user

Transfusion:
- issued unit required
- transfused_by current user
- reaction notes required if reaction reported

---

# 46. Blood Bank Scheduled Commands

Create:

php artisan blood-bank:expire-units
php artisan blood-bank:notify-expiring

Expire command:
- find units past expiry date
- mark EXPIRED
- log action
- notify blood bank users

Notify expiring command:
- find units expiring soon
- notify blood bank users
- avoid duplicate spam

---

# 47. Tests Required

## Reporting

1. Reports dashboard loads.
2. Consultation report counts consultations.
3. Diagnosis report lists most common diagnoses.
4. Pharmacy report lists most dispensed drugs.
5. Investigation report lists most requested investigations.
6. Procedure report lists most performed procedures.
7. Emergency report groups by triage category.
8. Admission report calculates length of stay.
9. Billing report calculates billed/paid/outstanding totals.
10. Stock report shows stock balance matrix.
11. Reports respect date filters.
12. Reports respect permissions.
13. Export route requires permission.

## Blood Bank

14. Blood donor can be created.
15. Blood donation can be recorded.
16. Blood unit is created with unique unit number.
17. Failed screening prevents unit availability.
18. Available unit can be requested.
19. Blood request can be approved.
20. Crossmatch compatible unit can be issued.
21. Incompatible unit cannot be issued.
22. Expired unit cannot be issued.
23. Issued unit cannot be issued again.
24. Transfusion can be recorded.
25. Reaction can be recorded.
26. Expired units command marks units expired.
27. Blood request appears in Visit Preview.
28. Blood bank reports show inventory.
29. Blood bank notifications are created.
30. Blood bank actions are logged.

---

# 48. Deliverables

Provide:

1. Gap analysis of current reporting system.
2. Reporting dashboard.
3. Consultation reports.
4. Diagnosis reports.
5. Complaint reports.
6. Pharmacy reports.
7. Investigation reports.
8. Procedure/Theatre reports.
9. Emergency reports.
10. Admission reports.
11. MAR reports.
12. Billing reports.
13. Claims reports.
14. Stock reports.
15. Report filters/exports.
16. Blood Bank migrations/models.
17. Blood Bank dashboard.
18. Donor management.
19. Donation management.
20. Blood unit inventory.
21. Blood request workflow.
22. Crossmatching.
23. Blood issue/transfusion.
24. Expiry/wastage handling.
25. Blood Bank billing integration.
26. Blood Bank notifications/logs.
27. Blood Bank reports.
28. Visit Preview integration.
29. Permissions/menus/seeders.
30. Tests or verification notes.
31. Files modified.
32. Remaining TODOs.

---

# 49. Important Rules

Do not calculate reports from the wrong source tables.

Do not treat prescriptions as dispensed drugs.

Do not treat invoice items as diagnoses.

Do not create parallel billing for blood bank.

Do not create parallel stock system for normal products.

Do not issue unscreened, incompatible, expired, discarded, or already issued blood units.

Do not bypass permissions for reports.

Do not load all hospital records by default.

Do not break existing consultation, pharmacy, investigation, procedure, emergency, admission, billing, stock, claims, MAR, visit preview, or patient workflows.

Now inspect the current UHMS implementation and build a Reporting System plus Blood Bank Management module according to the requirements above.