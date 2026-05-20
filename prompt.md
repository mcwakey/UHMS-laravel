You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to implement a full **Emergency Unit / Emergency Department module**.

This must be integrated seamlessly into the existing working UHMS system. Do not disturb the already implemented OPD, Admission, Triage, Consultation, Billing, Pharmacy, Investigation, Theatre/Procedure, Product Stock, Inventory, Roles, Dashboard, or Menu workflows.

Use the audit report as source of truth:

```text
docs/EMERGENCY_INTEGRATION_AUDIT.md
```

---

# 1. Main Objective

Implement Emergency as the third major patient workflow beside OPD and Admission:

```text
Visit / OPD  = Outpatient care
Admission    = Inpatient care
Emergency    = Emergency care
```

Emergency must be a full clinical management unit, not just a priority flag.

However, Emergency must **reuse existing UHMS primitives** wherever possible.

The audit confirms that UHMS already has:

```text
VisitType::EMERGENCY
VisitStatus::EMERGENCY
Priority::EMERGENCY
TriageScore::EMERGENCY
VisitWorkflowService
BillingService
LabRequest / Investigation workflow
Prescription / Pharmacy workflow
Procedure workflow
Product stock movement system
ModuleService
SidebarMenuBuilder
Spatie permissions
```

So Emergency should be implemented as:

```text
Visit.visit_type = EMERGENCY
+
Emergency-specific dashboard / queue / triage / treatment / disposition UI
+
Optional emergency_cases table for ER-only fields
```

Do not create a separate emergency patient lifecycle that duplicates Visit.

---

# 2. Non-Negotiable Integration Rules

1. Do not break OPD workflow.
2. Do not break Admission workflow.
3. Do not break existing Triage workflow.
4. Do not break Consultation workflow.
5. Do not create separate Emergency invoices.
6. Do not create separate Emergency patient records.
7. Do not bypass `VisitWorkflowService`.
8. Do not bypass `BillingService`.
9. Do not bypass existing Investigation request workflow.
10. Do not bypass existing Procedure request workflow.
11. Do not bypass Product Stock / Consumable usage services.
12. Emergency must use `visit_type = EMERGENCY`.
13. Emergency status changes must use `VisitWorkflowService::transition()`.
14. Emergency billing must use the visit’s single invoice.
15. Emergency product consumption must use the Emergency stock location.
16. OPD queues must not be polluted with Emergency cases.
17. Emergency queues must not show OPD cases.

---

# 3. Recommended Emergency Workflow

Implement this workflow:

```text
Emergency Registration / Arrival
    ↓
Emergency Visit Created
    visit_type = EMERGENCY
    status = TRIAGE or EMERGENCY depending on severity
    priority = EMERGENCY / URGENT / NORMAL
    ↓
Emergency Triage
    ↓
Emergency Queue
    ↓
Emergency Assessment / Treatment
    ↓
Emergency Orders:
    - investigations
    - prescriptions / medication
    - minor procedures
    - theatre procedures
    - consumables
    ↓
Emergency Observation / Resuscitation if needed
    ↓
Disposition / Outcome:
    - Discharged Home
    - Admitted to Ward
    - Referred Out
    - Transferred to OPD / Specialist
    - Transferred to Theatre
    - Left Against Medical Advice
    - Deceased
    - Absconded
```

---

# 4. Current System Findings to Respect

The audit found:

* `VisitType::EMERGENCY` already exists.
* `VisitStatus::EMERGENCY` already exists.
* `Priority::EMERGENCY` already exists.
* `TriageScore::EMERGENCY` already exists.
* `VisitWorkflowService::transition()` is the canonical status mutator and writes `VisitStatusLog`.
* Consultation data is visit-scoped, not OPD-scoped.
* Billing already uses one invoice per visit.
* Investigation requests are visit-scoped.
* Procedure requests are visit-scoped.
* Emergency needs its own stock location.
* Emergency roles, permissions, dashboard, and menu do not exist yet.

Use these existing foundations instead of duplicating them.

---

# 5. Emergency Module

Add an optional module:

```text
slug = emergency
name = Emergency
depends_on = visits, triage, consultation, billing
is_core = false
is_enabled = true by default if suitable
```

Update module seeding.

The Emergency menu must be hidden if the module is disabled.

Do not affect existing modules.

---

# 6. Emergency Roles and Permissions

Add permissions:

```text
emergency.access
emergency.dashboard.view
emergency.case.create
emergency.case.view
emergency.case.update
emergency.queue.view
emergency.queue.manage
emergency.triage.create
emergency.triage.update
emergency.treatment.record
emergency.orders.create
emergency.medication.administer
emergency.consumables.consume
emergency.observation.manage
emergency.disposition.set
emergency.discharge
emergency.admit
emergency.refer
emergency.death.record
emergency.report.view
emergency.stock.consume
```

Recommended roles:

```text
Emergency Doctor
Emergency Nurse
Emergency Officer
Emergency Receptionist
```

Assign permissions carefully.

Do not automatically give Emergency staff admin, billing management, stock management, HR, procurement, or settings permissions.

---

# 7. Emergency Sidebar Menu

Add a new Emergency section to `SidebarMenuBuilder`, gated by:

```text
module = emergency
permission = emergency.access
```

Recommended menu:

```text
Emergency Unit
├── Dashboard
├── Register Emergency Case
├── Emergency Queue
├── Emergency Triage
├── In Treatment
├── Resuscitation / Critical
├── Observation
├── Awaiting Admission
├── Disposition / Outcomes
└── Emergency Reports
```

Suggested menu item routes:

```text
admin.emergency.dashboard
admin.emergency.create
admin.emergency.queue
admin.emergency.triage.index
admin.emergency.treatment.index
admin.emergency.resuscitation
admin.emergency.observation
admin.emergency.awaiting-admission
admin.emergency.dispositions
admin.emergency.reports.index
```

Do not expose Store, HR, Accounts, Settings, Admin, or Procurement menus to Emergency-only users.

---

# 8. Dashboard Routing

Update dashboard routing so Emergency users can land on Emergency dashboard.

If user has Emergency role/permission and is not Admin/Super Admin:

```text
redirect to admin.emergency.dashboard
```

Do not break existing dashboard redirects:

```text
Super Admin / Admin → admin.dashboard
Doctor → doctor.dashboard
Staff → staff.dashboard
```

Emergency users should get Emergency dashboard only if they are primarily Emergency staff or have `emergency.dashboard.view`.

---

# 9. Optional Emergency Case Table

Create `emergency_cases` as a 1:1 overlay on visits.

Do not duplicate patient or visit fields.

Recommended table:

```text
emergency_cases
- id
- visit_id
- patient_id
- emergency_number
- registered_by
- arrival_mode
- arrival_time
- brought_by nullable
- accompanied_by nullable
- referral_source nullable
- chief_complaint
- triage_category nullable
- treatment_area_id nullable
- assigned_doctor_id nullable
- assigned_nurse_id nullable
- status
- disposition nullable
- disposition_at nullable
- disposition_by nullable
- discharge_summary nullable
- referral_facility nullable
- referral_reason nullable
- death_time nullable
- death_cause nullable
- certified_by nullable
- notes nullable
- created_at
- updated_at
```

Important:

* `visit_id` must be unique.
* `patient_id` must match `visit.patient_id`.
* Use this table for Emergency-only operational fields.
* The canonical clinical episode is still the visit.

---

# 10. Emergency Number

Generate an emergency number such as:

```text
ER-2026-000001
```

Do not reuse OPD visit number as the displayed emergency number, although both may be shown.

---

# 11. Emergency Registration

Create Emergency registration page.

Support three modes:

## Existing Patient

* search patient
* select patient
* create emergency visit/case

## New Patient

Quick registration with:

```text
name
gender
approximate age or DOB
phone nullable
address nullable
next of kin nullable
```

## Unknown Patient

Allow temporary patient identity:

```text
Unknown Male ER-0001
Unknown Female ER-0002
Unknown Patient ER-0003
```

Later staff can update the patient profile when identity is confirmed.

When Emergency case is registered:

1. Create or use patient.
2. Create Visit:

   * `visit_type = EMERGENCY`
   * `status = TRIAGE` or `EMERGENCY`
   * `priority = EMERGENCY` if critical
   * `chief_complaint`
   * `checked_in_at = now`
3. Create EmergencyCase.
4. Initialize queue/status using `VisitWorkflowService`.
5. Create/get visit invoice if the system normally does that at visit creation.

Do not create separate Emergency invoice.

---

# 12. Emergency Arrival Fields

Capture:

```text
arrival_mode
arrival_time
brought_by
accompanied_by
referral_source
chief_complaint
priority
notes
```

Arrival mode options:

```text
WALK_IN
AMBULANCE
POLICE
REFERRAL
BROUGHT_BY_RELATIVE
TRANSFER_FROM_OPD
TRANSFER_FROM_WARD
OTHER
```

---

# 13. Emergency Triage

Emergency triage must reuse existing `Triage` model/table where possible.

Add emergency-specific UI around it.

Record:

```text
Airway
Breathing
Circulation
Disability / consciousness
Exposure
Temperature
Blood pressure
Pulse
Respiratory rate
Oxygen saturation
Pain score
Blood glucose
AVPU or GCS
Bleeding
Trauma status
Pregnancy status if applicable
```

If the existing Triage table does not have these fields, add only what is necessary or store Emergency-specific triage details on `emergency_cases` or a related emergency triage details table.

Keep existing OPD triage working.

---

# 14. Emergency Triage Categories

Support categories:

```text
RED      = Immediate / Resuscitation
ORANGE   = Very urgent
YELLOW   = Urgent
GREEN    = Less urgent
BLACK    = Dead / Expectant
```

Store this as:

```text
triage_category
```

Recommended values:

```text
RED
ORANGE
YELLOW
GREEN
BLACK
```

Map to:

```text
Priority::EMERGENCY
Priority::URGENT
Priority::NORMAL
TriageScore::EMERGENCY
TriageScore::URGENT
TriageScore::ROUTINE
```

Do not remove existing `TriageScore`.

---

# 15. Emergency Triage Behavior

When Emergency triage is saved:

* Save triage/vitals.
* Set emergency case triage category.
* Set visit priority.
* Set visit triage score.
* Transition visit status safely.

Possible transitions:

```text
TRIAGE → EMERGENCY
EMERGENCY → CONSULTING
EMERGENCY → ADMITTED
EMERGENCY → COMPLETED
EMERGENCY → CANCELLED
```

Use existing `VisitStatus` transitions if already allowed.

If transition is not allowed but clinically needed, update enum transitions carefully and add tests.

Do not write directly:

```php
$visit->status = ...
```

Always use:

```php
VisitWorkflowService::transition(...)
```

---

# 16. Emergency Queue

Create Emergency queue page.

It must only show:

```text
visit_type = EMERGENCY
```

Active statuses:

```text
TRIAGE
EMERGENCY
CONSULTING
WAITING_INVESTIGATION
WAITING_PHARMACY
OBSERVATION
AWAITING_ADMISSION
REFERRED_CONSULTATION
```

Use existing statuses where possible.

Emergency queue columns:

```text
Emergency No.
Patient
Age / Gender
Arrival Time
Waiting Time
Triage Category
Priority
Chief Complaint
Status
Assigned Doctor
Treatment Area
Action
```

Color-code rows by triage category:

```text
RED
ORANGE
YELLOW
GREEN
BLACK
```

Actions:

```text
Triage
Start Treatment
Continue Treatment
View Case
Admit
Discharge
Refer
Record Death
```

Do not show OPD patients in Emergency queue.

Do not show Emergency patients in OPD consultation queue unless explicitly filtered.

---

# 17. Protect OPD / Consultation Queue

The audit warns about visit status pollution.

Update OPD/consultation queues if needed so they do not accidentally show Emergency cases.

OPD consultation queue should filter:

```text
visit_type = OUTPATIENT
status IN WAITING_CONSULTATION, CONSULTING
```

Emergency consultation/treatment queue should filter:

```text
visit_type = EMERGENCY
```

Do not mix the two unless the user explicitly selects a cross-workflow filter.

---

# 18. Emergency Dashboard

Create Emergency dashboard.

Cards:

```text
Emergency cases today
Waiting triage
Red / critical cases
Orange / urgent cases
In treatment
Under observation
Awaiting admission
Awaiting transfer/referral
Discharged today
Deaths today
STAT labs pending
```

Main dashboard list:

```text
Active emergency queue
```

Dashboard should be fast and use eager loading.

Do not load full patient history on dashboard.

---

# 19. Emergency Treatment Workspace

Create Emergency case/treatment page.

Sections:

```text
Patient Summary
Arrival Details
Triage / Vitals
Primary Survey
Secondary Survey
Clinical Notes
Diagnosis
Orders
Medications Given
Investigations
Procedures
Consumables Used
Observation Notes
Disposition / Outcome
Timeline
```

The page should reuse existing consultation/medical record functionality where possible.

Emergency clinical data should be tied to:

```text
visit_id
patient_id
medical_record_id where applicable
emergency_case_id where applicable
```

Do not create duplicate diagnosis/prescription/investigation systems.

---

# 20. Emergency Primary Survey

Support ABCDE assessment:

```text
Airway
Breathing
Circulation
Disability
Exposure
```

This can be implemented as emergency clinical notes or as structured fields if simple.

Do not overcomplicate if existing notes system can support it.

---

# 21. Emergency Vitals Timeline

Use existing `Vital` timeline for repeated vitals after triage.

Emergency staff should be able to record repeated observations:

```text
time
blood pressure
pulse
temperature
respiratory rate
spo2
pain score
blood glucose
notes
recorded_by
```

Do not create duplicate vitals records if existing `Vital` model is suitable.

---

# 22. Emergency Investigations

Emergency can request investigations using existing LabRequest/LabRequestItem workflow.

When Emergency requests investigation:

* Link to the Emergency visit.
* Set urgency to `STAT` or emergency-equivalent.
* Show STAT badge in investigation queue.
* Sort STAT requests above normal requests.
* Billing still occurs through the existing investigation acceptance workflow unless system policy says otherwise.

Do not create separate emergency investigation tables.

---

# 23. Emergency Pharmacy / Medication

Support emergency medication through existing prescription/dispensing where possible.

Two modes may exist:

## Prescription/Pharmacy mode

```text
Emergency doctor prescribes → Pharmacy dispenses → Stock deducted → Invoice item added
```

## Emergency administration mode

```text
Emergency staff administers medication from Emergency stock location
```

For this implementation, if adding direct Emergency medication administration:

* Use Products.
* Use Emergency stock location.
* Create product stock OUT movement.
* Use `EMERGENCY_CONSUMED` or appropriate product movement type.
* Add invoice item if the product is billable.
* Use `ProductPricingService` and `BillingService`.

Do not deduct Emergency medication from Main Store.

---

# 24. Emergency Stock Location

Create or seed Emergency stock location:

```text
Emergency Store / Emergency Stock
department_id = Emergency department
is_main = false
is_active = true
```

Add or verify Emergency department exists.

Add `DepartmentType::EMERGENCY` if missing.

Update stock location resolver:

```php
StockLocationResolver::getDefaultLocationForDepartment($emergencyDepartment)
```

Emergency consumes only from Emergency stock location.

If Emergency needs stock:

```text
Main Store → Emergency Stock Location
```

Do not consume directly from Main Store.

---

# 25. Emergency Stock Movement Type

Add movement type:

```text
EMERGENCY_CONSUMED
```

Use for emergency product/consumable usage.

If the project prefers source-based movement using existing types, document the decision. But recommended is:

```text
movement_type = EMERGENCY_CONSUMED
direction = OUT
stock_location = Emergency stock location
source_type = emergency_case or emergency_medication
```

---

# 26. Emergency Consumables

Emergency treatment page should allow consumables used.

Example:

```text
Cannula × 1
Syringe × 2
Gloves × 2
IV Fluid × 1
Oxygen mask × 1
```

Rules:

* Load products linked to Emergency department.
* Deduct only from Emergency stock location.
* Use `ConsumableUsageService`.
* If billable, add product invoice item.
* If non-billable, only deduct stock.
* Block if insufficient stock unless override permission exists.

---

# 27. Emergency Procedures

Emergency can perform minor procedures or request theatre procedures.

## Minor emergency procedures

Examples:

```text
Suturing
Wound dressing
Nebulization
CPR
Intubation
Catheterization
Splinting
Incision and drainage
```

These may be recorded as clinical notes or services billed to the visit invoice.

## Theatre procedures

Use existing Procedure Request workflow:

```text
Emergency treatment page → Request Procedure
```

Set priority:

```text
emergency
```

Do not create separate emergency procedure system.

---

# 28. Emergency Observation

Implement Emergency observation if not already available.

Suggested table:

```text
emergency_observations
- id
- emergency_case_id
- visit_id
- patient_id
- started_at
- ended_at nullable
- reason
- status
- notes nullable
- created_by
- created_at
- updated_at
```

Observation notes:

```text
emergency_observation_notes
- id
- emergency_observation_id
- recorded_at
- recorded_by
- vitals_snapshot nullable/json
- note
- created_at
- updated_at
```

If existing notes/vitals timeline can support this without new tables, reuse it.

Observation outcomes:

```text
DISCHARGE
ADMISSION
REFERRAL
DEATH
CONTINUE_OBSERVATION
```

---

# 29. Emergency Disposition / Outcome

Every emergency case must eventually have an outcome.

Supported outcomes:

```text
DISCHARGED_HOME
ADMITTED_TO_WARD
REFERRED_OUT
TRANSFERRED_TO_OPD
TRANSFERRED_TO_THEATRE
LEFT_AGAINST_MEDICAL_ADVICE
DECEASED
ABSCONDED
CANCELLED
```

Disposition should record:

```text
outcome
outcome_at
outcome_by
summary
instructions
```

---

# 30. Admit From Emergency

Wire Emergency → Admission.

When Emergency case is admitted:

1. Validate visit has `visit_type = EMERGENCY`.
2. Validate status is suitable, usually `EMERGENCY` or `CONSULTING`.
3. Call `AdmissionService::admit()`.
4. Allow admission from Emergency if currently blocked.
5. Transition visit safely:

   * `EMERGENCY → ADMITTED`
6. Link Admission to visit.
7. Keep Emergency case reference.

If necessary, add:

```text
admissions.emergency_case_id nullable
```

or rely on `visit_id` if enough.

Do not create a new visit when admitting from Emergency unless hospital policy requires it.

---

# 31. Discharge From Emergency

Discharge should produce Emergency discharge summary.

Capture:

```text
diagnosis
treatment given
medications given
investigations done
condition at discharge
follow-up instructions
doctor
date/time
```

Transition visit to:

```text
COMPLETED
```

or another existing suitable terminal status.

Do not break normal OPD discharge.

---

# 32. Referral Out

Capture:

```text
facility referred to
reason for referral
condition on referral
treatment already given
transport mode
doctor
notes
```

If structured referral model exists, use it.

If not, store on Emergency disposition fields for now.

---

# 33. Death Record

If outcome is death, capture:

```text
time of death
cause of death
certifying doctor
body released to
mortuary transfer status
notes
```

Do not force admission before recording an Emergency death unless hospital policy requires it.

---

# 34. Insurance / Billing Behavior in Emergency

Emergency must use the existing visit invoice.

Rules:

* One Emergency Visit = One Main Invoice.
* Emergency services/products/investigations/procedures add to the same invoice.
* Use selected visit insurance if valid.
* If insurance is missing/unverified, use Cash and Carry fallback unless policy says provisional insurance.
* Do not block lifesaving emergency care because of insurance verification.
* Add audit notes if billing is provisional.

Use:

```text
BillingService
InvoiceService
ServicePricingService
ProductPricingService
InsuranceService
```

Do not create `emergency_invoices`.

---

# 35. Emergency Reports

Create Emergency reports:

```text
Emergency Attendance Report
Emergency Triage Category Report
Emergency Response Time Report
Emergency Outcomes Report
Emergency Admissions from Emergency
Emergency Deaths Report
Emergency Referrals Report
Emergency Medication / Consumable Usage Report
Emergency Revenue Report
```

Reports should be gated by:

```text
emergency.report.view
```

---

# 36. Emergency Dashboard Metrics

Implement metrics:

```text
active emergency cases
waiting triage
critical RED cases
in treatment
under observation
awaiting admission
STAT labs pending
discharged today
deaths today
average waiting time
```

Do not calculate heavy reports directly on every dashboard load if it will hurt performance.

---

# 37. Routes

Add routes under:

```php
Route::prefix('admin/emergency')
    ->name('admin.emergency.')
    ->middleware(['auth', 'verified', 'can:emergency.access'])
    ->group(function () {
        // routes here
    });
```

Suggested routes:

```text
GET    /dashboard
GET    /
GET    /create
POST   /
GET    /queue
GET    /triage
GET    /{emergencyCase}
GET    /{emergencyCase}/treatment
POST   /{emergencyCase}/triage
POST   /{emergencyCase}/start-treatment
POST   /{emergencyCase}/vitals
POST   /{emergencyCase}/notes
POST   /{emergencyCase}/orders/investigation
POST   /{emergencyCase}/orders/procedure
POST   /{emergencyCase}/medications
POST   /{emergencyCase}/consumables
POST   /{emergencyCase}/observation/start
POST   /{emergencyCase}/observation/notes
POST   /{emergencyCase}/disposition
POST   /{emergencyCase}/admit
POST   /{emergencyCase}/discharge
POST   /{emergencyCase}/refer
POST   /{emergencyCase}/death
GET    /reports
```

Use existing route naming conventions if different.

---

# 38. Controllers

Create:

```text
app/Http/Controllers/Admin/Emergency/DashboardController.php
app/Http/Controllers/Admin/Emergency/CaseController.php
app/Http/Controllers/Admin/Emergency/QueueController.php
app/Http/Controllers/Admin/Emergency/TriageController.php
app/Http/Controllers/Admin/Emergency/TreatmentController.php
app/Http/Controllers/Admin/Emergency/ObservationController.php
app/Http/Controllers/Admin/Emergency/DispositionController.php
app/Http/Controllers/Admin/Emergency/ReportController.php
```

Keep controllers thin.

Business logic goes into services.

---

# 39. Services

Create:

```text
EmergencyService
EmergencyDashboardService
EmergencyQueueService
EmergencyTriageService
EmergencyTreatmentService
EmergencyDispositionService
EmergencyObservationService
EmergencyReportService
```

These services should orchestrate existing services:

```text
VisitService
VisitWorkflowService
TriageService or TriageController logic if no service exists
ConsultationService
BillingService
InvestigationRequestService
ProcedureRequestService
PrescriptionService
ConsumableUsageService
AdmissionService
StockLocationResolver
```

Do not duplicate existing billing, visit, investigation, pharmacy, or procedure logic.

---

# 40. Inertia / Vue Pages

Create pages:

```text
resources/js/Pages/Emergency/Dashboard.vue
resources/js/Pages/Emergency/Create.vue
resources/js/Pages/Emergency/Queue.vue
resources/js/Pages/Emergency/Show.vue
resources/js/Pages/Emergency/Triage.vue
resources/js/Pages/Emergency/Treatment.vue
resources/js/Pages/Emergency/Observation.vue
resources/js/Pages/Emergency/Disposition.vue
resources/js/Pages/Emergency/Reports/Index.vue
```

Components:

```text
EmergencyDashboardCards.vue
EmergencyQueueTable.vue
EmergencyPatientHeader.vue
EmergencyTriageForm.vue
EmergencyVitalsTimeline.vue
EmergencyPrimarySurvey.vue
EmergencyOrdersPanel.vue
EmergencyConsumablesPanel.vue
EmergencyObservationPanel.vue
EmergencyDispositionForm.vue
EmergencyTimeline.vue
```

---

# 41. SPA Behavior

Emergency pages must behave like SPA pages:

* no unnecessary full reloads
* forms preserve state on validation errors
* modals do not hang
* validation errors display clearly
* queue filters update smoothly
* case status updates after action
* action buttons show loading states

Use Inertia properly.

---

# 42. Queue Filtering Safety

Update existing queue services if needed.

OPD triage queue:

```text
visit_type = OUTPATIENT
status = TRIAGE
```

Emergency triage queue:

```text
visit_type = EMERGENCY
status IN TRIAGE, EMERGENCY
```

Consultation OPD queue:

```text
visit_type = OUTPATIENT
status IN WAITING_CONSULTATION, CONSULTING
```

Emergency treatment queue:

```text
visit_type = EMERGENCY
active emergency statuses
```

Do not mix OPD and Emergency unless the UI explicitly allows it.

---

# 43. Data Integrity Rules

* Emergency case must have visit.
* Emergency visit must have `visit_type = EMERGENCY`.
* Emergency status changes must use `VisitWorkflowService`.
* Emergency billing must use visit invoice.
* Emergency investigations must use existing investigation request system.
* Emergency procedures must use existing procedure request system.
* Emergency consumables must use product stock movement system.
* Emergency stock must use Emergency stock location.
* Emergency admission must use `AdmissionService`.
* Emergency outcome must be recorded before closing the case.
* Do not create duplicate patient lifecycle tables.

---

# 44. Performance Rules

* Paginate emergency queue.
* Eager-load patient, visit, triage, assigned doctor, assigned nurse, treatment area.
* Do not load full clinical history on dashboard.
* Load timeline/details only on case page.
* Index:

  * visits.visit_type
  * visits.status
  * visits.priority
  * emergency_cases.status
  * emergency_cases.triage_category
  * emergency_cases.arrival_time
  * emergency_cases.assigned_doctor_id
  * emergency_cases.assigned_nurse_id

---

# 45. Tests Required

Add or update feature tests:

1. Emergency module can be enabled.
2. Emergency permissions are seeded.
3. Emergency user sees Emergency menu.
4. Emergency registration creates visit with `visit_type = EMERGENCY`.
5. Emergency case links to visit and patient.
6. Emergency triage saves vitals.
7. Emergency triage sets triage category.
8. Emergency queue shows only emergency visits.
9. OPD queue does not show emergency visits.
10. Emergency treatment can start using `VisitWorkflowService`.
11. Emergency can request investigation with STAT urgency.
12. Emergency can request theatre procedure.
13. Emergency consumable usage deducts from Emergency stock location.
14. Emergency billing uses same visit invoice.
15. Emergency can admit patient through `AdmissionService`.
16. Emergency can discharge patient.
17. Emergency can refer patient.
18. Emergency can record death.
19. Emergency dashboard loads.
20. Disabling emergency module hides menu/routes.

Regression tests:

1. OPD visit still works.
2. Normal triage still works.
3. Consultation queue still works.
4. Admission still works.
5. Investigation flow still works.
6. Pharmacy dispensing still works.
7. Procedure workflow still works.
8. Billing still follows one invoice per visit.

---

# 46. Safe Implementation Phases

Implement in phases.

## Phase 1 — Foundations

* Emergency module seed
* Emergency permissions/roles
* Emergency department if missing
* Emergency stock location
* `DepartmentType::EMERGENCY` if missing
* `StockMovementType::EMERGENCY_CONSUMED`
* Status transition tests
* Menu entry hidden behind module/permission

## Phase 2 — Registration, Queue, Dashboard

* Emergency registration
* Emergency case creation
* Emergency queue
* Emergency dashboard
* OPD/Emergency queue separation

## Phase 3 — Emergency Triage and Treatment

* Emergency triage UI
* Emergency treatment workspace
* Emergency vitals timeline
* Emergency clinical notes
* Start/continue treatment

## Phase 4 — Orders and Stock

* Emergency investigations with STAT urgency
* Emergency procedure requests
* Emergency consumables
* Emergency product usage
* Emergency stock deduction

## Phase 5 — Disposition

* Discharge
* Admit to ward
* Refer out
* Transfer to OPD/theatre if needed
* Death record

## Phase 6 — Reports and Polish

* Reports
* KPIs
* Notifications
* UAT fixes

Do not implement all at once if it risks breaking existing workflows.

---

# 47. Deliverables

Provide:

1. Gap analysis before implementation.
2. Migrations added/updated.
3. Models added/updated.
4. Enums updated.
5. Services added/updated.
6. Controllers added.
7. Routes added.
8. Vue/Inertia pages added.
9. Sidebar menu update.
10. Permissions and roles seeded.
11. Emergency stock location seeded.
12. Emergency dashboard.
13. Emergency queue.
14. Emergency registration.
15. Emergency triage.
16. Emergency treatment page.
17. Emergency orders integration.
18. Emergency stock/consumable integration.
19. Emergency disposition handling.
20. Tests or verification notes.
21. Files modified.
22. Remaining TODOs.

---

# 48. Important Rules

Do not create a parallel emergency patient lifecycle.

Do not create emergency invoices.

Do not bypass VisitWorkflowService.

Do not bypass BillingService.

Do not bypass existing Investigation workflow.

Do not bypass existing Procedure workflow.

Do not bypass stock/consumable services.

Do not mix OPD and Emergency queues.

Do not consume Emergency stock from Main Store.

Do not expose Emergency menus to unauthorized users.

Do not break existing OPD, Admission, Triage, Consultation, Billing, Pharmacy, Investigation, Procedure, or Inventory workflows.

Now inspect the current implementation and build the Emergency Unit module as a seamless overlay on the existing UHMS Visit workflow.
