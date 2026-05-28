```text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to refine and complete the Emergency Case Management module.

UHMS already has OPD/Visit, Admission, Billing, Pharmacy/Product Stock, Investigations, Procedures, Consultation Sessions, Clinical Record Ownership, MAR/Medication Administration planning, Visit Preview, and Patient Merge foundations.

Emergency must now be refined so it behaves like a proper Emergency Clinical Session, integrated with the existing UHMS mechanisms instead of becoming a parallel system.

Do not rebuild the whole emergency module from scratch unless a part is missing or badly implemented.

First inspect the current implementation, identify gaps, preserve what works, and implement only missing/incomplete parts.

Do not break:
- OPD / Visit workflow
- Admission workflow
- Consultation workflow
- Billing / Invoice system
- Pharmacy / Product stock system
- Investigation workflow
- Procedure workflow
- MAR / Medication Administration system
- Clinical Tasks / Reminders
- Visit Preview
- Patient Merge
- Insurance pricing / claims foundation

---

# 1. Main Objective

Refine Emergency Case Management so the Emergency page becomes a complete clinical workspace.

Emergency must support:

1. Automated emergency triage calculation.
2. Emergency vitals history and graph, mirroring Admission vitals section.
3. Medication / MAR section with proper searchable product/drug list.
4. Automatic medication quantity calculation like consultation prescriptions.
5. Emergency MAR integration.
6. Investigation section refined to mirror Consultation Investigations.
7. Procedure section refined and decoupled from Billing.
8. Billing section refined as a financial summary/action area.
9. Bay and Team section linked to wards/beds/bays.
10. Emergency treated as a clinical session with medical records.
11. Main emergency doctor, assigned nurse, and contributors.
12. All emergency records visible in Consultation/Visit Preview where appropriate.
13. Record ownership preserved across emergency notes, vitals, medications, investigations, procedures, and billing.

---

# 2. Core Concept

Emergency must be treated as a proper clinical session.

Correct model:

Visit
    ↓
Emergency Case
    ↓
Emergency Session
    ↓
Medical Records / Vitals / MAR / Investigations / Procedures / Billing / Team / Disposition

Emergency should not be a loose page with disconnected widgets.

Emergency must be integrated into the same patient, visit, invoice, medical record, stock, medication, investigation, procedure, and preview architecture.

---

# 3. Emergency Page Recommended Sections

The Emergency Case Detail page should have these sections:

1. Emergency Header / Patient Summary
2. Triage & Vitals
3. Bay / Bed & Team
4. Emergency Clinical Notes / Assessment
5. Medication / MAR
6. Investigations
7. Procedures
8. Consumables
9. Billing
10. Tasks / Monitoring
11. Disposition
12. Emergency Timeline

The top header should always clearly show:

- Patient
- Emergency number
- Visit number
- Triage category
- Current emergency status
- Bay / bed
- Main doctor
- Primary nurse
- Contributors
- Time since arrival
- Critical alerts
- Insurance/payment context if useful

---

# 4. Emergency Session Model

Create or update an Emergency Session concept.

If an existing consultation/session mechanism can support this, reuse it.

Recommended table if missing:

emergency_sessions
- id
- emergency_case_id
- visit_id
- patient_id
- department_id
- medical_record_id nullable
- main_doctor_id nullable
- primary_nurse_id nullable
- status
- started_at nullable
- ended_at nullable
- started_by nullable
- ended_by nullable
- notes nullable
- created_at
- updated_at

Statuses:

PENDING
ACTIVE
OBSERVATION
COMPLETED
CANCELLED

Rules:

- One active emergency session should exist per active emergency case.
- Emergency records should link to emergency_session_id where possible.
- Emergency records should also link to visit_id, patient_id, and emergency_case_id.
- Emergency session should be visible in Visit Preview.
- Emergency session should be visible in patient medical history/consultation history where appropriate.

---

# 5. Emergency Contributors / Team

Emergency must support a team, not only one assigned doctor.

Distinguish:

- Main emergency doctor
- Primary nurse
- Contributing doctors
- Contributing nurses
- Other contributors

Do not overwrite the main doctor when another doctor adds input.

Create or reuse contributor table.

Recommended table if missing:

emergency_session_contributors
- id
- emergency_session_id
- emergency_case_id
- user_id
- role nullable
- first_contributed_at
- last_contributed_at
- created_at
- updated_at

Rules:

- Every emergency record keeps its own creator/owner.
- A contributor is automatically added when a non-main user creates a record.
- Main doctor remains main doctor until explicitly changed.
- Primary nurse remains primary nurse until explicitly changed.
- Contributors can view but cannot edit other users’ records unless authorized.
- Emergency page must show main doctor, primary nurse, and contributors.

---

# 6. Emergency Record Ownership

All emergency records must store and display their creator.

This applies to:

- triage
- vitals
- emergency notes
- doctor assessments
- nursing notes
- medications
- MAR administrations
- investigations
- procedures
- consumable usage
- tasks
- disposition notes

Each record should have:

created_by
updated_by nullable
created_at
updated_at

Do not display every record as if it was created by the main doctor.

Preserve existing ownership logic if already implemented.

---

# 7. Automated Emergency Triage Calculation

Emergency triage should be automated.

In the Triage section, the user records vitals and danger signs, and the system automatically suggests a triage category.

The user may override the suggested category, but override must be logged with reason.

Triage should consider:

- temperature
- pulse
- respiratory rate
- systolic BP
- diastolic BP
- SpO2
- AVPU / consciousness level
- pain score
- danger signs
- trauma flag
- bleeding flag
- seizure flag
- respiratory distress
- age group if available
- pregnancy if available
- shock indicators if available

Triage categories:

RED = Immediate / Resuscitation
ORANGE = Very urgent
YELLOW = Urgent
GREEN = Less urgent
BLACK = Dead on arrival / expectant

Target times:

RED = immediately
ORANGE = within 10 minutes
YELLOW = within 30 minutes
GREEN = within 60 minutes
BLACK = special handling

Store:

auto_triage_category
final_triage_category
triage_score
override_reason nullable
triaged_by
triaged_at

If emergency_cases already has triage_category, preserve it but add auto/final logic as needed.

---

# 8. Emergency Triage Service

Create or update:

EmergencyTriageScoringService

Required methods:

calculateFromVitals(array $vitals, array $flags = []): EmergencyTriageResult

suggestCategory(EmergencyCase $case): EmergencyTriageResult

Return structured result:

[
    'score' => 0,
    'category' => 'RED',
    'reasons' => [],
    'warnings' => [],
]

Rules:

- Keep calculation configurable where possible.
- Do not hardcode everything in Vue.
- Allow future hospital-specific triage rules.
- If values are missing, return warnings instead of crashing.
- Display reasons behind suggested category.

Example:

Suggested Category: RED
Reasons:
- SpO2 below critical threshold
- Altered consciousness
- Respiratory distress

---

# 9. Emergency Triage & Vitals Section

The Emergency Triage section should include vitals history and graph, mirroring the Admission vitals section if possible.

Inspect the Admission view page vitals section and reuse/mirror its UI and logic.

Emergency Triage/Vitals section should show:

- latest vitals cards
- vitals history table
- vitals trend graph
- critical value indicators
- recorded by
- recorded at
- triage score/category
- auto-calculated category
- final category
- override reason if any

Vitals graph should include common vital trends:

- temperature
- pulse
- respiratory rate
- blood pressure
- SpO2
- pain score if available

Do not duplicate vitals logic if an existing vitals component/service exists.

Extend existing vitals system to support emergency_case_id and emergency_session_id if needed.

---

# 10. Emergency Vitals Monitoring Tasks

If Clinical Tasks/Reminders exist, use them for emergency monitoring.

Example rules:

RED case → vitals every 15 minutes
ORANGE case → vitals every 30 minutes
YELLOW case → vitals every 60 minutes

Create monitoring tasks where appropriate.

Do not force this if Clinical Tasks are not fully ready, but prepare integration points.

---

# 11. Medication / MAR Section Refinement

The current Emergency Medication/MAR section is limited by a small/static drug list and cannot search properly.

Refine it.

The medication product search must use the unified Products catalogue.

Product/drug source:

products

Do not use a separate drug stock or static list.

The medication search should support:

- product name
- generic name if available
- brand name if available
- SKU/code if available
- barcode if available
- category
- department availability

The list should show:

- product/drug name
- strength if available
- form if available
- emergency available quantity
- pharmacy available quantity if useful
- main stock quantity
- stock status
- price/insurance price if billable

---

# 12. Medication Quantity Automation

Mirror the consultation prescription quantity calculation behavior.

Medication entry should include:

- drug/product
- dose
- dose unit
- route
- frequency
- duration value
- duration unit
- start time
- expected doses
- calculated quantity
- instructions
- STAT / PRN / SOS indicator

Example:

Ceftriaxone 1g IV BD for 5 days
BD x 5 days = 10 doses
Calculated quantity = 10

If user manually changes quantity, show warning:

BD for 5 days usually requires 10 doses. You entered 8.

Do not block unless hospital policy requires blocking.

Use the same frequency table/logic as MAR/consultation prescription if available.

---

# 13. Emergency Medication Flow

Emergency medication should support:

- STAT medication
- PRN/SOS medication
- scheduled medication
- immediate administration
- pharmacy dispensing
- emergency stock administration
- billing before/after depending existing workflow
- MAR chart
- clinical tasks/reminders

Rules:

- If medication is administered from Emergency stock, create stock OUT movement from Emergency stock once.
- If medication is pharmacy-dispensed to patient, MAR administration must not deduct stock again.
- Do not deduct stock at prescription/order stage.
- Do not deduct stock twice.
- Use existing Medication Administration / MAR services where available.
- Use existing Product Stock Movement system.

---

# 14. Emergency MAR Integration

Emergency MAR should be accessible from:

- Emergency Case Detail page
- Emergency Board
- Visit Preview medication section

Medication section should show:

- active medication orders
- due now
- overdue
- upcoming
- administered today
- PRN/SOS
- MAR Chart button

Actions:

- prescribe/order
- administer STAT
- administer PRN/SOS
- open MAR
- view administration details
- hold/stop medication if authorized

---

# 15. Emergency Investigations Section Refinement

The Emergency Investigations section should mirror the Consultation Investigations section.

Inspect the Consultation Investigations implementation and reuse/mirror its behavior where possible.

Emergency Investigations should allow:

- select investigation department
- load services under selected department
- select one or more services
- mark priority: EMERGENCY / URGENT / ROUTINE
- add clinical reason
- submit request
- group requested investigations by department
- show requester/owner
- show status
- show result
- show verified result
- show billing status if applicable

Rules:

- Lab is just one investigation department.
- Use existing Investigation workflow.
- Do not create parallel emergency investigation tables unless necessary.
- Add emergency_case_id and priority fields if missing.
- Emergency requests must be visible as urgent to investigation departments.
- Result should appear on Emergency Case Detail and Visit Preview.
- Do not bypass BillingService.

Expected display:

Laboratory
    Dr. Kofi Mensah
        FBC — Emergency priority — Pending
        Malaria RDT — Result verified

X-Ray
    Dr. Ama Boateng
        Chest X-Ray — Accepted

---

# 16. Emergency Investigation Data Fields

If missing, add to investigation request tables or equivalent:

emergency_case_id nullable
emergency_session_id nullable
is_emergency boolean default false
priority nullable

Priority values:

EMERGENCY
URGENT
ROUTINE

Use existing priority/status fields if already present.

---

# 17. Emergency Procedures Section Refinement

Procedures and Billing must be decoupled.

Procedure section is clinical.

Billing section is financial.

Emergency Procedures should mirror the Consultation Investigation/request style.

Emergency Procedures should allow:

- select procedure/theatre/minor procedure department
- load procedure services
- select procedure service
- add clinical reason/notes
- mark urgency
- submit procedure request
- track procedure status
- view procedure report
- view billing status only as metadata

Types:

- emergency bedside procedure
- theatre transfer procedure
- minor procedure room procedure

Rules:

- Use existing Procedure/Theatre workflow where applicable.
- Do not bypass theatre workflow for theatre procedures.
- Do not mix procedure UI with billing UI.
- Bill procedures through BillingService where required.
- Show procedure status on emergency case detail.
- Requested procedures must show record owners/requested_by.
- Procedure records must be visible in Visit Preview.

Expected display:

Procedures

Minor Procedure Room
    Dr. Kofi Mensah
        Wound suturing — Pending

Theatre
    Dr. Ama Boateng
        Emergency laparotomy — Requested / awaiting theatre

---

# 18. Emergency Procedure Data Fields

If missing, add to procedure request tables or equivalent:

emergency_case_id nullable
emergency_session_id nullable
is_emergency boolean default false
priority nullable
performed_at nullable
performed_by nullable

Use existing fields if already present.

---

# 19. Billing Section Refinement

Emergency Billing must be separated from Procedures.

Emergency Billing section should be a financial summary and action point.

It should use the visit’s single invoice.

Emergency billing should group invoice items by source:

- Emergency Services
- Emergency Medications
- Emergency Consumables
- Emergency Investigations
- Emergency Procedures
- Emergency Admission/Observation Charges
- Other Emergency Charges

Display:

- item description
- source
- quantity
- cash price
- insurance price
- selected price
- patient payable
- paid amount
- balance
- payment status
- billing status
- created by
- created at

Rules:

- Do not create a separate emergency invoice.
- Use one visit = one invoice.
- Emergency care must not be blocked by unpaid bills.
- Emergency billable items must be added through BillingService.
- Use selected visit insurance and pricing rules.
- Fallback to Cash and Carry where needed.
- Prevent duplicate invoice items using source_type/source_id.
- Do not mix clinical procedure request UI into billing UI.

---

# 20. Emergency Billing Actions

Billing section may include actions:

- View full invoice
- Add emergency service charge if authorized
- Add emergency consumable charge if authorized
- Record payment
- Print invoice
- Show unpaid balance

Do not allow billing actions to bypass BillingService.

Do not allow payment requirement to block emergency care.

---

# 21. Bay and Team Refinement

Bay and Team section should link emergency bay assignment to wards/beds/bays.

Instead of independent emergency bays only, support this structure:

Emergency Department
    ↓
Emergency Ward / Unit
    ↓
Beds / Bays

In Bay & Team section:

1. Select emergency ward/unit.
2. Load beds/bays under that ward.
3. Assign patient to a bed/bay.
4. Mark bed/bay as occupied.
5. Release or mark cleaning when patient leaves.

This should mirror Admission bed assignment where possible.

---

# 22. Emergency Ward / Bed / Bay Data

Reuse existing ward/bed system if available.

If emergency_bays already exists, link it to ward/bed structure.

Suggested fields if using emergency_bays:

emergency_bays
- id
- ward_id nullable
- bed_id nullable
- name
- code
- bay_type
- status
- is_active
- notes nullable
- created_at
- updated_at

Suggested assignment table if missing:

emergency_bay_assignments
- id
- emergency_case_id
- emergency_session_id nullable
- ward_id nullable
- bed_id nullable
- emergency_bay_id nullable
- assigned_by
- assigned_at
- released_by nullable
- released_at nullable
- status
- notes nullable
- created_at
- updated_at

Assignment statuses:

ACTIVE
RELEASED
TRANSFERRED
CANCELLED

Rules:

- Only available beds/bays can be assigned unless override permission exists.
- Occupied bed/bay cannot be assigned twice.
- When assigned, bed/bay becomes occupied.
- When released, bed/bay becomes available/cleaning based on workflow.
- Do not break Admission bed system.

---

# 23. Emergency Team Assignment

Bay & Team section should support:

- assign main doctor
- assign primary nurse
- add contributing doctors
- add contributing nurses
- show contributors
- show who is currently responsible

Rules:

- Changing main doctor must be explicit and logged.
- Adding contributor does not change main doctor.
- Contributor entries should be attached to their own creator.
- Team assignment should appear in Emergency Timeline.

---

# 24. Emergency Clinical Notes / Assessment

Emergency notes should be refined as part of the Emergency Session.

Support note types:

- Doctor Assessment
- Nursing Note
- Resuscitation Note
- Observation Note
- General Note

Each note should show:

- note type
- content
- created by
- created at
- updated by if edited
- emergency session
- edit action if permitted

Do not overwrite notes from other users.

Use existing consultation ownership/edit rules if available.

---

# 25. Emergency Records Under Consultation / Medical History

Emergency session must be treated as part of the patient medical record history.

After emergency records are created, they should be visible under:

- Visit Preview
- Patient clinical history
- Consultation/medical record history where appropriate
- Claims mirror where relevant

Emergency session should show:

Emergency Session
    - Triage
    - Vitals
    - Doctor assessment
    - Nursing notes
    - Medications/MAR
    - Investigations
    - Procedures
    - Billing summary
    - Disposition

Do not hide emergency records from future consultations.

---

# 26. Integration With Visit Preview

Update Visit Preview to show emergency records chronologically.

Include:

- emergency case created
- arrival details
- triage calculated category
- final triage category
- vitals records and trends summary
- bay/bed assignment
- team assignment
- doctor assessments
- nursing notes
- medications ordered/administered
- MAR events
- investigations requested/results
- procedures requested/performed
- consumables used
- billing events
- tasks/reminders
- disposition
- transfers to admission/OPD/theatre
- death/DOA if applicable

Every entry must show:

- time
- user
- role
- department/session
- details

---

# 27. Tasks / Monitoring Section

Emergency should use Clinical Tasks/Reminders for:

- vitals monitoring
- medication administration
- doctor review
- nursing observation
- investigation follow-up
- procedure preparation
- disposition review
- transfer preparation

Emergency page should show:

- due tasks
- overdue tasks
- completed tasks
- task owner/assigned role
- action buttons

Do not duplicate Clinical Task engine.

Add emergency_case_id/emergency_session_id to tasks if missing.

---

# 28. Consumables Section

Emergency Consumables should use products linked to Emergency department and Emergency stock location.

Consumables section should allow:

- search product
- show Emergency available quantity
- show Main Store quantity
- enter quantity used
- reason/use case
- billable/non-billable handling
- create stock OUT from Emergency stock location
- create invoice item if billable
- link usage to emergency_case_id and emergency_session_id

Rules:

- Do not consume directly from Main Store.
- Do not create products from Emergency.
- Do not use separate emergency item table.
- Use unified products and stock movements.
- Use BillingService for billable consumables.

---

# 29. Emergency Stock Location

Emergency stock must come from Emergency department’s linked stock location.

If no emergency stock location exists, show clear error:

No Emergency stock location is configured. Please configure a stock location for Emergency department.

Do not silently use Main Store.

---

# 30. UI/UX Consistency

Emergency sections should mirror existing good UI patterns:

- Vitals → mirror Admission vitals section with graph/history
- Medication/MAR → mirror Consultation prescription behavior + MAR
- Investigations → mirror Consultation Investigations
- Procedures → mirror Consultation request pattern
- Billing → mirror Visit invoice/billing summary
- Bay assignment → mirror Admission bed assignment
- Session/contributors → mirror Consultation session ownership logic

Do not invent inconsistent UI if a working component already exists.

Reuse components where possible.

---

# 31. Data Loading / Performance

Emergency Case Detail must avoid N+1 queries.

Eager-load:

- patient
- visit
- emergency case
- emergency session
- medical record
- main doctor
- primary nurse
- contributors
- latest vitals
- vitals history
- triage
- bay/bed/ward
- notes with creators
- medication orders/products/frequencies
- MAR schedules/tasks/administrations
- investigations with departments/services/owners/results
- procedures with departments/services/owners/status
- consumables/products/stock movements
- invoice/invoice items/payments
- tasks
- timeline logs

Do not load huge historical datasets unnecessarily on the board.

Emergency Board should load summary counts only.

Emergency Detail can load full case details.

---

# 32. Backend Services to Create / Update

Create or update services as needed:

EmergencyCaseService
EmergencySessionService
EmergencyTriageScoringService
EmergencyVitalsService
EmergencyBoardService
EmergencyBayAssignmentService
EmergencyTeamService
EmergencyNoteService
EmergencyMedicationService
EmergencyInvestigationService
EmergencyProcedureService
EmergencyBillingService
EmergencyConsumableService
EmergencyTimelineService
EmergencyDispositionService
EmergencyTaskService
MarChartService
MedicationScheduleService
MedicationAdministrationService
ClinicalTaskService
BillingService
StockLocationResolver
StockMovementService
VisitPreviewService

Do not duplicate existing services. Extend existing ones where possible.

---

# 33. Routes / Controllers

Use existing route conventions.

Suggested controllers:

EmergencyCaseController
EmergencySessionController
EmergencyTriageController
EmergencyVitalsController
EmergencyBayTeamController
EmergencyNoteController
EmergencyMedicationController
EmergencyInvestigationController
EmergencyProcedureController
EmergencyBillingController
EmergencyConsumableController
EmergencyTaskController
EmergencyTimelineController

Suggested routes under existing admin prefix if applicable:

GET /admin/emergency/cases/{emergencyCase}
PATCH /admin/emergency/cases/{emergencyCase}

POST /admin/emergency/cases/{emergencyCase}/triage
POST /admin/emergency/cases/{emergencyCase}/vitals
POST /admin/emergency/cases/{emergencyCase}/bay-team
POST /admin/emergency/cases/{emergencyCase}/notes
POST /admin/emergency/cases/{emergencyCase}/medications
POST /admin/emergency/cases/{emergencyCase}/investigations
POST /admin/emergency/cases/{emergencyCase}/procedures
POST /admin/emergency/cases/{emergencyCase}/consumables
POST /admin/emergency/cases/{emergencyCase}/tasks

GET /admin/emergency/cases/{emergencyCase}/billing
GET /admin/emergency/cases/{emergencyCase}/timeline
GET /admin/emergency/cases/{emergencyCase}/mar-chart

Adapt names to existing project structure.

---

# 34. Frontend Components

If using Inertia/Vue, create or update:

Emergency/ShowCase.vue
Emergency/Components/EmergencyHeader.vue
Emergency/Components/TriageVitalsSection.vue
Emergency/Components/VitalsGraph.vue
Emergency/Components/BayTeamSection.vue
Emergency/Components/EmergencyNotesSection.vue
Emergency/Components/EmergencyMedicationSection.vue
Emergency/Components/EmergencyInvestigationSection.vue
Emergency/Components/EmergencyProcedureSection.vue
Emergency/Components/EmergencyConsumablesSection.vue
Emergency/Components/EmergencyBillingSection.vue
Emergency/Components/EmergencyTasksSection.vue
Emergency/Components/EmergencyTimeline.vue

Reuse existing components where possible:

- Admission vitals graph/table
- Consultation investigation selector
- Consultation prescription quantity calculator
- MAR chart/modal
- Visit billing summary
- Admission bed selector
- Consultation contributor display

---

# 35. Validation Rules

Emergency triage:

- emergency_case_id required
- vitals required depending policy
- auto_triage_category generated by service
- final_triage_category required
- override_reason required if final category differs from auto category
- triaged_by = current user

Emergency vitals:

- emergency_case_id required
- patient_id required
- recorded_by = current user
- recorded_at required
- at least one vital field required

Emergency medication:

- product_id required
- product must be medication/drug or allowed product type
- dose required unless PRN protocol allows free text
- route required
- frequency required
- duration or total doses required for scheduled medication
- STAT creates immediate schedule
- PRN/SOS does not create fixed schedule
- source stock validated on administration

Emergency investigation:

- department_id required
- selected services required
- services must belong to department
- priority required
- emergency_case_id required

Emergency procedure:

- procedure department required
- procedure service required
- reason/notes required if configured
- priority required
- emergency_case_id required

Bay/team:

- ward_id required if bed/bay depends on ward
- bed_id or emergency_bay_id required
- selected bed/bay must be available unless override permission
- main doctor nullable
- primary nurse nullable

Consumables:

- product_id required
- quantity > 0
- emergency stock location required
- quantity <= emergency available quantity
- billable products use BillingService

Billing:

- all invoice item creation through BillingService
- prevent duplicate source_type/source_id

---

# 36. Permissions

Add or verify permissions:

emergency.case.view
emergency.case.update
emergency.session.manage
emergency.triage.perform
emergency.triage.override
emergency.vitals.record
emergency.vitals.view_graph
emergency.bay_team.manage
emergency.notes.create
emergency.notes.edit_own
emergency.notes.edit_any
emergency.medication.order
emergency.medication.administer
emergency.mar.view
emergency.investigation.request
emergency.procedure.request
emergency.consumables.use
emergency.billing.view
emergency.billing.manage
emergency.tasks.manage
emergency.timeline.view

Use existing permission names if already defined.

---

# 37. Tests Required

Add or update tests.

## Emergency Session

1. Emergency case has emergency session.
2. Emergency session links to visit and patient.
3. Emergency session has main doctor and primary nurse.
4. Contributors can be added without changing main doctor.
5. Emergency records link to emergency session.

## Triage / Vitals

6. Triage category is auto-calculated from vitals.
7. User can override triage category with reason.
8. Override without reason fails.
9. Emergency vitals history displays records.
10. Emergency vitals graph receives correct data.
11. Emergency vitals mirror Admission vitals behavior where possible.

## Medication / MAR

12. Emergency medication search uses Products.
13. Emergency medication search is not limited to static list.
14. Medication quantity is calculated from frequency/duration.
15. STAT medication creates immediate due task.
16. PRN/SOS medication does not create recurring schedule.
17. Emergency medication appears in MAR.
18. Emergency stock administration deducts Emergency stock once.
19. Pharmacy-dispensed medication does not deduct stock again.
20. Emergency medication does not create parallel drug stock.

## Investigations

21. Emergency investigation section loads departments.
22. Selecting department loads investigation services.
23. Multiple services can be requested.
24. Requests are marked emergency/urgent.
25. Requests are grouped by department.
26. Request owners are displayed.
27. Results are visible when verified.
28. Existing Investigation workflow is used.

## Procedures

29. Procedure section is separate from Billing.
30. Selecting procedure department loads procedure services.
31. Procedure requests are created with emergency context.
32. Procedure owners are displayed.
33. Procedure status is shown.
34. Procedure billing uses BillingService but UI remains clinical.

## Billing

35. Billing section shows emergency invoice items grouped by source.
36. Emergency billing uses visit invoice.
37. Emergency care is not blocked by unpaid invoice.
38. Duplicate invoice items are prevented.
39. BillingService is used.

## Bay / Team

40. Selecting emergency ward loads beds/bays.
41. Patient can be assigned to available bed/bay.
42. Occupied bed/bay cannot be assigned without override.
43. Bay/bed status updates after assignment.
44. Team assignment logs main doctor/nurse/contributors.

## Consumables

45. Emergency consumable search uses Products.
46. Emergency consumables use Emergency stock location.
47. Emergency consumable usage creates stock OUT.
48. Billable consumable creates invoice item.
49. Emergency cannot consume directly from Main Store.

## Visit Preview / History

50. Visit Preview includes emergency session records.
51. Emergency records appear chronologically.
52. Emergency session is visible in patient clinical history.
53. Record owners are preserved in preview/history.

## Performance / UX

54. Emergency detail avoids N+1 queries.
55. Emergency sections update without full page reload where applicable.
56. Modals do not leave stuck backdrops.
57. UI mirrors existing Admission/Consultation components where applicable.

---

# 38. Deliverables

Provide:

1. Gap analysis of current Emergency module.
2. Emergency session integration.
3. Automated triage calculation.
4. Triage override logic.
5. Emergency vitals history and graph mirroring Admission.
6. Refined Medication/MAR section with searchable Product drugs.
7. Medication quantity auto-calculation.
8. Emergency MAR integration.
9. Refined Investigations section mirroring Consultation.
10. Refined Procedures section decoupled from Billing.
11. Refined Billing section grouped by source.
12. Bay/Team section linked to wards/beds/bays.
13. Emergency contributors support.
14. Emergency records available in Visit Preview/medical history.
15. Consumables integration with Emergency stock.
16. Backend services/controllers/routes updated.
17. Permissions/seeders if needed.
18. Tests or verification notes.
19. Files modified.
20. Remaining TODOs.

---

# 39. Important Rules

Do not copy OPD blindly.

Do not create parallel emergency clinical systems when existing Consultation/Admission systems can be reused.

Do not create a parallel billing system.

Do not create a parallel investigation system.

Do not create a parallel procedure system.

Do not create a parallel drug/product stock system.

Do not bypass BillingService.

Do not bypass StockMovementService.

Do not bypass Medication/MAR services.

Do not deduct stock twice.

Do not block emergency care due to unpaid invoices.

Do not overwrite main emergency doctor when contributors add records.

Do not hide emergency records from Visit Preview or patient medical history.

Now inspect the current UHMS implementation and refine Emergency Case Management according to the requirements above, reusing and mirroring existing Admission, Consultation, Billing, Investigation, Procedure, MAR, and Stock mechanisms wherever possible.
```