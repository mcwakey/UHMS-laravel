````text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to implement a full Emergency Case Management module as the third patient pathway in UHMS.

UHMS already has:

- OPD / Outpatient visit workflow
- Admission / Inpatient workflow
- Billing / invoices
- Patient records
- Consultation
- Investigations
- Procedures
- Pharmacy / products / stock
- Medication Administration / MAR planning
- Visit Preview
- Insurance pricing / claims foundations

Now we need to build the Emergency workflow.

Emergency must be integrated seamlessly into the existing UHMS architecture without disturbing OPD, Admission, Billing, Pharmacy, Investigation, Procedure, Consultation, MAR, Stock, or Visit Preview workflows.

Do not copy OPD blindly.

Emergency is a fast-response clinical pathway and must support urgent care before full administrative completion where necessary.

---

# 1. Core Concept

UHMS should now support three main patient pathways:

```text
OPD / Visit = Outpatient care
Admission = Inpatient care
Emergency = Urgent / critical emergency care
````

Emergency must be its own workflow, but still connected to the same patient, visit, invoice, billing, stock, medication, investigation, procedure, and visit preview systems.

Recommended structure:

```text
Patient
    ↓
Visit
    ↓
Emergency Case
```

The `visit` remains the global encounter.

The `emergency_case` stores emergency-specific data.

---

# 2. Main Objectives

Implement Emergency Case Management with:

1. Emergency case creation.
2. Known patient and unknown patient support.
3. Emergency number generation.
4. Arrival details.
5. Emergency triage.
6. Emergency triage category/color.
7. Emergency board.
8. Emergency bay/location assignment.
9. Emergency clinical notes/timeline.
10. Emergency vitals and monitoring.
11. Emergency medications and MAR integration.
12. Emergency investigations.
13. Emergency procedures.
14. Emergency billing through the existing invoice system.
15. Emergency stock usage through the unified product stock system.
16. Emergency disposition.
17. Visit Preview integration.
18. Emergency reports foundation.
19. Permissions and role control.

---

# 3. Important Rules

Do not rebuild existing OPD or Admission workflows.

Do not create a parallel billing system.

Do not create a parallel pharmacy/drug stock system.

Do not create a parallel patient system.

Do not bypass BillingService.

Do not bypass Product Stock Movement system.

Do not bypass existing Investigation workflow.

Do not bypass existing Procedure workflow.

Do not bypass Medication Administration / MAR workflow.

Emergency care must not be blocked by unpaid invoices.

Emergency services/products/procedures/investigations should still be added to the visit invoice using existing billing rules.

---

# 4. Emergency Case Model

Create or update:

```text
emergency_cases
```

Recommended fields:

```text
id
emergency_number
visit_id
patient_id
admission_id nullable
emergency_bay_id nullable
arrival_mode
arrival_time
brought_by nullable
source nullable
referral_facility nullable
chief_complaint nullable
initial_condition nullable
triage_category nullable
triage_score nullable
triage_notes nullable
emergency_status
assigned_doctor_id nullable
assigned_nurse_id nullable
created_by
triaged_by nullable
triaged_at nullable
disposition nullable
disposition_notes nullable
disposition_time nullable
disposed_by nullable
created_at
updated_at
```

Possible emergency statuses:

```text
ARRIVED
WAITING_TRIAGE
TRIAGED
UNDER_EMERGENCY_CARE
OBSERVATION
READY_FOR_DISPOSITION
DISPOSED
CANCELLED
```

Possible dispositions:

```text
ADMITTED
DISCHARGED
TRANSFERRED_TO_OPD
TRANSFERRED_TO_THEATRE
REFERRED_OUT
LEFT_AGAINST_MEDICAL_ADVICE
ABSCONDED
DIED
DEAD_ON_ARRIVAL
```

---

# 5. Emergency Number Generation

Generate unique emergency numbers.

Example:

```text
ER-2026-000001
```

Rules:

* emergency number must be unique
* generated inside transaction
* must not be reused
* should be configurable if number generation service exists
* use existing number generation system if available

---

# 6. Emergency Arrival Modes

Emergency case creation should capture arrival mode.

Supported options:

```text
WALK_IN
AMBULANCE
POLICE
FAMILY_BROUGHT
REFERRAL
TRANSFER_FROM_OPD
TRANSFER_FROM_WARD
UNKNOWN
```

Display user-friendly labels:

```text
Walk-in
Ambulance
Police
Family brought
Referral from another facility
Transfer from OPD
Transfer from ward
Unknown / unconscious patient
```

---

# 7. Unknown Patient Support

Emergency must support unknown or unidentified patients.

Examples:

```text
Unknown Male Adult
Unknown Female Child
Accident Victim
Unconscious Patient
```

If patient identity is unknown:

* create temporary patient record
* generate temporary patient number
* allow emergency case to proceed
* allow identity update later
* allow merging/linking to an existing patient later if safe

Suggested temporary patient number:

```text
TEMP-ER-2026-000012
```

Temporary patient fields:

```text
is_temporary boolean
temporary_reason nullable
identity_confirmed_at nullable
identity_confirmed_by nullable
merged_to_patient_id nullable
```

If patient table already supports temporary patients, use existing structure.

Do not block emergency care because patient identity is incomplete.

---

# 8. Emergency Triage

Emergency triage is separate from normal OPD triage.

Emergency triage should capture:

```text
vitals
chief complaint
triage category
triage score
pain score if available
level of consciousness if available
danger signs
triage notes
triaged_by
triaged_at
```

Emergency triage categories:

```text
RED
ORANGE
YELLOW
GREEN
BLACK
```

Meanings:

```text
RED = Immediate / Resuscitation
ORANGE = Very urgent
YELLOW = Urgent
GREEN = Less urgent
BLACK = Dead on arrival / expectant
```

Target times:

```text
RED = immediately
ORANGE = within 10 minutes
YELLOW = within 30 minutes
GREEN = within 60 minutes
BLACK = special handling
```

Emergency Board must highlight triage colors clearly.

---

# 9. Emergency Vitals

Emergency vitals should support repeated monitoring.

Unlike OPD triage, emergency vitals may be recorded many times.

Support:

```text
initial vitals
repeat vitals
monitoring interval
critical value alerts
recorded_by
recorded_at
```

If the Clinical Task / Reminder engine exists, use it to create monitoring tasks.

Examples:

```text
RED cases: record vitals every 15 minutes
ORANGE cases: record vitals every 30 minutes
YELLOW cases: record vitals every 60 minutes
```

Do not duplicate the existing vitals system if it already supports visit/admission/emergency context. Extend it.

---

# 10. Emergency Bays / Locations

Create or update:

```text
emergency_bays
```

Recommended fields:

```text
id
name
code
bay_type
department_id nullable
status
notes nullable
is_active
created_at
updated_at
```

Bay types:

```text
RESUSCITATION
OBSERVATION
TREATMENT
MINOR_PROCEDURE
ISOLATION
WAITING_AREA
EMERGENCY_WARD
```

Bay statuses:

```text
AVAILABLE
OCCUPIED
CLEANING
OUT_OF_SERVICE
RESERVED
```

Emergency case can be assigned to a bay.

When emergency case is active in a bay:

```text
bay.status = OCCUPIED
```

When patient leaves bay:

```text
bay.status = AVAILABLE or CLEANING
```

Do not break existing bed management. Emergency bays are short-term emergency locations, not full admission beds.

---

# 11. Emergency Board

Create an Emergency Board.

This should be a live operational dashboard.

Columns:

```text
Emergency Number
Patient
Triage Category
Arrival Time
Waiting Time
Bay / Location
Chief Complaint
Assigned Doctor
Assigned Nurse
Status
Alerts
Actions
```

Views/filters:

```text
All Active Emergency Cases
Waiting Triage
Red / Critical Cases
Under Emergency Care
Observation
Ready for Disposition
Overdue Reviews
By Bay
By Doctor
By Nurse
```

Actions:

```text
View Case
Triage
Assign Bay
Start Emergency Care
Record Vitals
Add Note
Request Investigation
Request Procedure
Administer Medication
Open MAR
Disposition
```

The board should auto-refresh/poll periodically if the project supports it.

Suggested refresh:

```text
every 30-60 seconds
```

---

# 12. Emergency Case Detail Page

Create an Emergency Case Detail page.

It should show:

```text
patient summary
emergency number
visit number
arrival details
triage category
bay/location
current status
assigned team
vitals
emergency timeline
medications
investigations
procedures
tasks
billing/invoice summary
disposition section
```

This page should feel like an emergency control sheet.

---

# 13. Emergency Timeline

Emergency care is time-based.

Create or use an emergency timeline.

Timeline entries may include:

```text
Arrival
Triage
Bay assigned
Vitals recorded
Doctor assessment
Nursing note
Medication ordered
Medication administered
Investigation requested
Investigation result received
Procedure requested
Procedure performed
Task created
Task completed
Disposition decision
Referral
Admission
Discharge
Death / DOA
```

Each timeline entry must show:

```text
time
user
role
department
action
details
```

Use existing activity log if available.

If not, create:

```text
emergency_case_logs
- id
- emergency_case_id
- visit_id
- patient_id
- action
- title
- description nullable
- source_type nullable
- source_id nullable
- performed_by
- created_at
```

---

# 14. Emergency Clinical Notes

Emergency notes should support rapid repeated updates.

Create or reuse clinical notes with emergency context.

Fields:

```text
emergency_case_id
visit_id
patient_id
note_type
content
created_by
created_at
updated_by nullable
updated_at
```

Note types:

```text
DOCTOR_ASSESSMENT
NURSING_NOTE
RESUSCITATION_NOTE
OBSERVATION_NOTE
GENERAL_NOTE
```

Every note must show the user who made it.

Do not overwrite notes from other users.

Use the existing record ownership rules if already implemented.

---

# 15. Emergency Medication / MAR Integration

Emergency must use the Medication Administration + Clinical Tasks/MAR system.

Emergency should support:

```text
STAT medication
Immediate administration
PRN/SOS medication
Scheduled observation medication
Emergency stock source
Emergency MAR chart
```

Examples:

```text
Hydrocortisone 200mg IV STAT
Diazepam 10mg IV STAT
Adrenaline 1mg IV STAT
Ceftriaxone 1g IV BD for 2 days
```

Emergency medications must create:

```text
medication_order
medication_administration_schedule where applicable
clinical_task/reminder
medication_administration record when nurse gives dose
```

Emergency MAR must be accessible from:

```text
Emergency Board
Emergency Case Detail page
Visit Preview medication section
```

If administered from emergency stock:

```text
create stock OUT movement from Emergency stock location
```

If dispensed by pharmacy to patient:

```text
do not deduct stock again during administration
```

---

# 16. Emergency Investigations

Emergency must allow urgent investigation requests.

Departments may include:

```text
Lab
X-Ray
Scan
CT Scan
ECG
Bedside Tests
```

Rules:

* use existing Investigation workflow
* mark requests as emergency/urgent
* do not create separate parallel investigation system
* result should be visible on Emergency Case Detail and Visit Preview
* investigation departments should see priority clearly

Emergency investigation fields should include:

```text
is_emergency boolean
priority = EMERGENCY / URGENT / ROUTINE
emergency_case_id nullable
```

If fields already exist, use them.

Workflow:

```text
Emergency doctor requests urgent test
↓
Investigation department receives priority request
↓
Result entered / verified
↓
Emergency doctor sees result immediately
```

---

# 17. Emergency Procedures

Emergency should support emergency procedures.

Examples:

```text
CPR
Intubation
Wound suturing
Catheterization
Nebulization
Dressing
Splinting
Minor surgery
Resuscitation procedure
```

Two types:

```text
Emergency bedside procedure
Theatre transfer procedure
```

Rules:

* use existing Procedure workflow where applicable
* allow emergency bedside procedure records if needed
* do not bypass theatre workflow for theatre procedures
* bill procedures through BillingService
* show procedure status on emergency case detail

Emergency procedure fields should include:

```text
emergency_case_id nullable
is_emergency boolean
priority
performed_at nullable
performed_by nullable
```

Use existing procedure tables if possible.

---

# 18. Emergency Billing

Emergency care must not be blocked by payment.

Billing rule:

```text
Emergency services/products/procedures/investigations are added to the visit invoice as they happen.
Payment may be collected later.
```

Use existing invoice system:

```text
One visit = one invoice
Emergency invoice items are attached to the visit invoice
Insurance pricing still applies
Cash and Carry fallback still applies
```

Important:

* do not create separate emergency invoice system
* do not require payment before emergency care
* do not duplicate invoice items
* use BillingService for billable emergency services/products/procedures/investigations
* use source_type/source_id to prevent duplicate billing

Possible source types:

```text
emergency_case
emergency_medication
emergency_procedure
emergency_investigation
emergency_service
```

---

# 19. Emergency Stock

Emergency uses the unified Product stock system.

Emergency should have its own stock location:

```text
Emergency Stock Location
```

Emergency staff may use products/medications from that stock.

Rules:

* do not create separate drug stock table
* product is the source of all physical items
* emergency stock movement must use existing StockMovementService
* emergency stock must not consume directly from Main Store
* resolve emergency stock location by department/location mapping

Stock movement types may include:

```text
EMERGENCY_ADMINISTRATION_OUT
EMERGENCY_CONSUMABLE_OUT
EMERGENCY_RETURN
EMERGENCY_ADJUSTMENT
```

Use existing movement naming if already defined.

---

# 20. Emergency Clinical Tasks / Reminders

Emergency should use the Clinical Task system.

Task types:

```text
Medication Administration
Vitals Monitoring
Doctor Review
Nursing Observation
Investigation Follow-up
Procedure Preparation
Disposition Review
Transfer Preparation
```

Emergency board should show:

```text
due tasks
overdue tasks
critical reminders
```

Emergency RED/ORANGE cases may automatically create monitoring tasks.

Example:

```text
RED case → vitals every 15 minutes
ORANGE case → vitals every 30 minutes
```

Do not duplicate the Clinical Task engine.

Extend it with emergency context:

```text
emergency_case_id nullable
```

---

# 21. Emergency Disposition

Emergency case must end with a disposition.

Supported dispositions:

```text
ADMITTED
DISCHARGED
TRANSFERRED_TO_OPD
TRANSFERRED_TO_THEATRE
REFERRED_OUT
LEFT_AGAINST_MEDICAL_ADVICE
ABSCONDED
DIED
DEAD_ON_ARRIVAL
```

Disposition workflow:

## Admit patient

```text
Emergency Case → Admission
Emergency case status = DISPOSED
Disposition = ADMITTED
Visit continues as inpatient/admission
```

Create admission using existing Admission workflow.

Do not create duplicate admission logic.

## Discharge home

```text
Emergency Case → Discharged
Visit may be completed
Invoice remains for payment/claims
```

## Transfer to OPD

```text
Emergency Case → OPD/Consultation route
Visit continues as outpatient consultation
```

Use existing consultation route/session system.

## Transfer to Theatre

```text
Emergency Case → Procedure/Theatre workflow
```

Use existing procedure workflow.

## Refer out

Create referral note.

Fields:

```text
referral_facility
reason
condition_at_referral
referred_by
referral_time
notes
```

## Death / Dead on Arrival

Integrate with patient deceased feature.

If patient dies:

* create death/disposition record
* mark patient deceased if appropriate and authorized
* capture date/time of death
* cause of death if known
* certified_by if available

If dead on arrival:

```text
Disposition = DEAD_ON_ARRIVAL
Triage category = BLACK
```

---

# 22. Emergency Transfer to Admission

When disposition is ADMITTED:

* open admission creation flow
* pass patient, visit, emergency case
* preserve emergency timeline
* preserve medication/orders/investigations/procedures
* continue invoice
* emergency case marked disposed/admitted
* admission board now handles inpatient care

Do not create a new unrelated visit unless business rules require it.

Preferred:

```text
same visit continues into admission
```

---

# 23. Emergency Transfer to OPD / Consultation

When disposition is TRANSFERRED_TO_OPD:

* create or activate consultation route
* select consultation department
* assign doctor optional
* visit continues as OPD/consultation
* emergency case marked disposed/transferred
* invoice remains same
* Visit Preview should show emergency then OPD continuation

---

# 24. Emergency Transfer to Theatre

When disposition is TRANSFERRED_TO_THEATRE:

* create procedure request or activate existing emergency procedure request
* link to emergency_case_id
* use procedure/theatre workflow
* emergency case may remain active until accepted/transferred
* invoice remains same

---

# 25. Emergency Reports

Prepare report foundation.

Reports:

```text
Emergency Attendance Report
Emergency Cases by Triage Category
Emergency Waiting Time Report
Emergency Bay Utilization Report
Emergency Medication Report
Emergency Investigation Report
Emergency Procedure Report
Emergency Disposition Report
Emergency Referral Report
Emergency Mortality Report
Emergency Staff Activity Report
```

Filters:

```text
date range
triage category
status
disposition
doctor
nurse
bay
arrival mode
patient
```

At minimum implement basic emergency attendance and disposition report if reporting infrastructure exists.

---

# 26. Emergency Menu

Add Emergency module menu.

Suggested menu:

```text
Emergency
├── Emergency Board
├── New Emergency Case
├── Active Cases
├── Waiting Triage
├── Under Care
├── Observation
├── Ready for Disposition
├── Emergency Bays
├── Emergency MAR
├── Reports
└── Settings
```

Menu should be visible based on permissions.

---

# 27. Permissions

Add or verify permissions:

```text
emergency.board.view
emergency.case.create
emergency.case.view
emergency.case.update
emergency.case.cancel
emergency.triage.perform
emergency.bay.assign
emergency.notes.create
emergency.notes.edit_own
emergency.notes.edit_any
emergency.vitals.record
emergency.medication.administer
emergency.investigation.request
emergency.procedure.request
emergency.disposition.manage
emergency.transfer.admit
emergency.transfer.opd
emergency.transfer.theatre
emergency.refer
emergency.death.record
emergency.reports.view
emergency.settings.manage
```

Roles:

```text
Emergency Nurse
Emergency Doctor
Triage Nurse
Emergency Officer
Ward Supervisor
Matron
Pharmacist
Lab
Radiology
Cashier
Admin
Super Admin
```

---

# 28. Routes / Controllers

Use existing route conventions.

Suggested controllers:

```text
EmergencyBoardController
EmergencyCaseController
EmergencyTriageController
EmergencyBayController
EmergencyVitalsController
EmergencyNoteController
EmergencyMedicationController
EmergencyInvestigationController
EmergencyProcedureController
EmergencyDispositionController
EmergencyTransferController
EmergencyReportController
```

Suggested routes under admin prefix if used:

```php
Route::prefix('admin/emergency')
    ->name('admin.emergency.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/board', [EmergencyBoardController::class, 'index'])->name('board');
        Route::get('/cases/create', [EmergencyCaseController::class, 'create'])->name('cases.create');
        Route::post('/cases', [EmergencyCaseController::class, 'store'])->name('cases.store');
        Route::get('/cases/{emergencyCase}', [EmergencyCaseController::class, 'show'])->name('cases.show');
        Route::patch('/cases/{emergencyCase}', [EmergencyCaseController::class, 'update'])->name('cases.update');

        Route::post('/cases/{emergencyCase}/triage', [EmergencyTriageController::class, 'store'])->name('triage.store');
        Route::post('/cases/{emergencyCase}/assign-bay', [EmergencyBayController::class, 'assign'])->name('bay.assign');
        Route::post('/cases/{emergencyCase}/vitals', [EmergencyVitalsController::class, 'store'])->name('vitals.store');
        Route::post('/cases/{emergencyCase}/notes', [EmergencyNoteController::class, 'store'])->name('notes.store');

        Route::post('/cases/{emergencyCase}/investigations', [EmergencyInvestigationController::class, 'store'])->name('investigations.store');
        Route::post('/cases/{emergencyCase}/procedures', [EmergencyProcedureController::class, 'store'])->name('procedures.store');
        Route::post('/cases/{emergencyCase}/disposition', [EmergencyDispositionController::class, 'store'])->name('disposition.store');

        Route::post('/cases/{emergencyCase}/transfer/admission', [EmergencyTransferController::class, 'admit'])->name('transfer.admission');
        Route::post('/cases/{emergencyCase}/transfer/opd', [EmergencyTransferController::class, 'opd'])->name('transfer.opd');
        Route::post('/cases/{emergencyCase}/transfer/theatre', [EmergencyTransferController::class, 'theatre'])->name('transfer.theatre');
    });
```

Adapt route names to existing project.

---

# 29. Services to Create / Update

Create or update:

```text
EmergencyCaseService
EmergencyNumberService
EmergencyTriageService
EmergencyBoardService
EmergencyBayService
EmergencyVitalsService
EmergencyTimelineService
EmergencyDispositionService
EmergencyTransferService
EmergencyBillingService
EmergencyStockService
EmergencyReportService
ClinicalTaskService
MedicationAdministrationService
MarChartService
VisitPreviewService
BillingService
StockMovementService
```

Do not duplicate existing services. Extend existing ones where possible.

---

# 30. Frontend Pages / Components

If using Inertia/Vue, create:

```text
resources/js/Pages/Emergency/Board.vue
resources/js/Pages/Emergency/CreateCase.vue
resources/js/Pages/Emergency/ShowCase.vue
resources/js/Pages/Emergency/Bays.vue
resources/js/Pages/Emergency/Reports.vue
```

Components:

```text
EmergencyBoardTable
EmergencyCaseHeader
EmergencyTriagePanel
EmergencyVitalsPanel
EmergencyTimeline
EmergencyBaySelector
EmergencyNotesPanel
EmergencyMedicationPanel
EmergencyInvestigationPanel
EmergencyProcedurePanel
EmergencyDispositionPanel
EmergencyTransferModal
UnknownPatientForm
```

If using Blade, create equivalent Blade views/partials.

Follow existing UI design conventions.

---

# 31. Emergency Case Creation UI

New Emergency Case page should support:

```text
search existing patient
create emergency case for existing patient
create temporary unknown patient
arrival mode
arrival time
brought by
chief complaint
initial condition
source/referral facility
assign bay optional
triage immediately optional
```

Actions:

```text
Create Emergency Case
Create and Triage
Create Unknown Patient Case
```

---

# 32. Emergency Board UI

Emergency Board should be visual and operational.

Use triage color badges:

```text
RED
ORANGE
YELLOW
GREEN
BLACK
```

Show waiting time.

Highlight overdue triage/care.

Rows should be easy to scan.

Actions should be quick.

---

# 33. Emergency Detail UI

Emergency Case Detail should show document/timeline style.

Sections:

```text
Header
Arrival Details
Triage
Vitals
Bay / Location
Clinical Notes
Medications / MAR
Investigations
Procedures
Tasks
Billing Summary
Disposition
Timeline
```

Do not hide critical emergency information behind excessive tabs.

Tabs are okay if the page becomes too long, but critical status and triage information must always be visible.

---

# 34. Billing Integration Rules

Emergency billing must use existing BillingService.

When emergency service/product/procedure/investigation is added:

```text
create invoice item on visit invoice
use insurance pricing rules
use selected visit insurance
fallback to Cash and Carry
do not require immediate payment
do not duplicate item
```

Every emergency invoice item must have a clear source:

```text
source_type
source_id
```

---

# 35. Visit Preview Integration

Update Visit Preview to include Emergency timeline.

Visit Preview should show:

```text
Emergency case created
Arrival details
Triage category
Vitals
Bay assignment
Emergency notes
Medications administered
Investigations requested/results
Procedures performed
Clinical tasks
Disposition
Transfer/admission/discharge/referral/death
```

Every entry must show:

```text
time
user
role
department
details
```

If patient later moves to admission or OPD, Visit Preview should show the full story in chronological order.

---

# 36. Consultation / Admission Integration

Emergency must integrate with consultation and admission.

## Transfer to OPD

Use existing consultation route system.

## Transfer to Admission

Use existing admission system.

## Transfer to Theatre

Use existing procedure/theatre system.

Do not create new disconnected workflows.

---

# 37. Deceased Patient Integration

For `DIED` or `DEAD_ON_ARRIVAL` disposition:

* mark patient deceased only with proper permission
* capture date/time
* cause of death if available
* notes
* marked_by
* prevent future visits unless authorized override exists
* do not delete patient record

Use existing deceased patient logic if already implemented.

---

# 38. Notifications / Alerts

Use existing notification system if available.

Emergency alerts:

```text
New RED emergency case
Patient waiting triage
Overdue triage
Overdue vitals monitoring
Urgent investigation result ready
Emergency medication overdue
Ready for disposition
```

Recipients:

```text
Emergency nurse
Emergency doctor
Triage nurse
Supervisor
Lab/Radiology for urgent investigations
```

Do not implement SMS/WhatsApp unless infrastructure exists.

---

# 39. Performance Requirements

Emergency Board must be fast.

Avoid N+1 queries.

Eager-load:

```text
patient
visit
bay
assigned doctor
assigned nurse
latest vitals
latest triage
active tasks
medication due counts
pending investigations
pending procedures
```

Do not load entire timeline on board.

Load detailed timeline only on Emergency Case Detail page.

Index important fields:

```text
emergency_cases.emergency_number
emergency_cases.patient_id
emergency_cases.visit_id
emergency_cases.emergency_status
emergency_cases.triage_category
emergency_cases.arrival_time
emergency_cases.emergency_bay_id
emergency_case_logs.emergency_case_id
emergency_case_logs.created_at
```

---

# 40. Validation Rules

Emergency case creation:

```text
patient_id required unless creating temporary patient
arrival_mode required
arrival_time required
chief_complaint nullable but recommended
created_by required
```

Unknown patient:

```text
temporary display name required
gender nullable
estimated age nullable
temporary reason required
```

Triage:

```text
triage_category required
triage_score nullable
vitals required depending policy
triaged_by current user
triaged_at now
```

Bay assignment:

```text
bay must be active
bay must be available unless override permission
```

Disposition:

```text
disposition required
disposition_notes required for referral/LAMA/death/DOA
disposition_time required
disposed_by current user
```

---

# 41. Tests Required

Add or update tests:

## Emergency Case

1. User can create emergency case for existing patient.
2. User can create emergency case for unknown temporary patient.
3. Emergency number is generated uniquely.
4. Emergency case is linked to visit.
5. Emergency case appears on Emergency Board.

## Triage

6. Emergency triage can be recorded.
7. Triage category displays correctly.
8. RED cases are highlighted.
9. Triage updates emergency status.

## Bays

10. Emergency bay can be assigned.
11. Occupied bay cannot be assigned without override.
12. Bay status updates when assigned/released.

## Board

13. Emergency Board shows active cases.
14. Board filters by triage category.
15. Board shows waiting time.
16. Board shows assigned doctor/nurse.
17. Board does not load full timeline.

## Notes / Timeline

18. Emergency note can be added.
19. Timeline records emergency events.
20. Timeline shows user who performed action.

## Medication / MAR

21. Emergency medication creates medication order/task.
22. STAT emergency medication appears due immediately.
23. Emergency MAR opens for case.
24. Emergency stock administration creates stock OUT movement once.
25. Patient-dispensed medication does not double deduct stock.

## Investigations

26. Emergency investigation request is created using existing workflow.
27. Emergency investigation is marked urgent/emergency.
28. Investigation result appears in emergency case detail.

## Procedures

29. Emergency procedure request is created using existing workflow.
30. Emergency bedside procedure can be recorded if implemented.
31. Theatre transfer uses existing procedure workflow.

## Billing

32. Emergency service adds invoice item through BillingService.
33. Emergency billing does not require payment before care.
34. Duplicate emergency invoice items are prevented.

## Disposition

35. Emergency case can be admitted.
36. Emergency case can be discharged.
37. Emergency case can be transferred to OPD.
38. Emergency case can be transferred to Theatre.
39. Emergency case can be referred out.
40. Emergency death/DOA integrates with deceased patient logic.

## Visit Preview

41. Visit Preview includes emergency timeline.
42. Visit Preview shows emergency then admission/OPD continuation chronologically.

## Permissions

43. Unauthorized user cannot create emergency case.
44. Unauthorized user cannot triage.
45. Unauthorized user cannot dispose case.

---

# 42. Deliverables

Provide:

1. Gap analysis of existing visit/admission/emergency-related code.
2. Emergency case model/migration.
3. Emergency number generation.
4. Unknown patient support.
5. Emergency triage.
6. Emergency bay/location management.
7. Emergency Board.
8. Emergency Case Detail page.
9. Emergency notes/timeline.
10. Emergency vitals integration.
11. Emergency medication/MAR integration.
12. Emergency investigation integration.
13. Emergency procedure integration.
14. Emergency billing integration.
15. Emergency stock integration.
16. Emergency disposition workflow.
17. Admission/OPD/Theatre transfer integration.
18. Visit Preview emergency timeline.
19. Permissions/seeders/menu.
20. Tests or verification notes.
21. Files modified.
22. Remaining TODOs.

---

# 43. Important Rules

Do not copy OPD blindly.

Do not block emergency care because of unpaid invoice.

Do not create a parallel patient system.

Do not create a parallel billing system.

Do not create a parallel investigation/procedure system.

Do not create a parallel drug/product stock system.

Do not reduce stock twice.

Do not bypass existing BillingService.

Do not bypass existing StockMovementService.

Do not bypass existing MAR/Medication Administration system.

Do not break OPD, Admission, Billing, Pharmacy, Investigation, Procedure, Consultation, Visit Preview, or Claims workflows.

Now inspect the current UHMS implementation and build Emergency Case Management as a separate but fully integrated emergency pathway under the existing visit/invoice/patient architecture.

```
```
