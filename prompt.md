````text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to implement a proper Medication Administration + Clinical Task/Reminder system for Admission and Emergency patients.

This system must help nurses track, receive alerts, and record medication dosages that must be administered to admitted or emergency patients.

This is not just a reminder system. It must be a clinical, auditable Medication Administration Record system supported by scheduled clinical tasks.

Focus on:

- Admission Board medication tracking
- Emergency medication tracking
- Medication administration schedules
- Clinical tasks/reminders for nurses
- Due / overdue medication alerts
- Nurse administration recording
- Medication progress tracking
- Avoiding double stock deduction
- Audit trail
- Reusable clinical task engine

Do not break existing:

- consultation
- prescriptions
- pharmacy dispensing
- admission
- emergency
- billing
- product stock
- stock movements
- nursing/vitals
- visit workflow

---

# 1. Core Concept

The system must clearly separate these three concepts:

```text
Prescription = what the doctor ordered
Dispensing = what pharmacy supplied
Administration = what nurse actually gave to the patient
````

Do not treat prescription, dispensing, and administration as the same thing.

Correct workflow:

```text
Doctor Prescription
    ↓
Pharmacy Dispensing
    ↓
Medication Administration Schedule
    ↓
Clinical Task / Nurse Reminder
    ↓
Nurse Administration Record
    ↓
Progress / Audit / Reports
```

---

# 2. Main Objective

When a doctor prescribes a medication for an admitted or emergency patient, the system should be able to generate administration schedules and nursing tasks.

Example:

```text
Drug: Ceftriaxone
Dose: 1g
Route: IV
Frequency: BD
Duration: 5 days
Quantity: 10 doses
```

The system should generate scheduled dose tasks:

```text
Dose 1/10 — Day 1 — 08:00
Dose 2/10 — Day 1 — 20:00
Dose 3/10 — Day 2 — 08:00
Dose 4/10 — Day 2 — 20:00
...
Dose 10/10 — Day 5 — 20:00
```

Nurses should then be able to record each dose as:

```text
Given
Missed
Held
Skipped
Refused
Cancelled
Not given
```

The system must track progress:

```text
Ceftriaxone 1g IV BD — 4/10 doses given
Next dose: Today 20:00
Status: Active
```

---

# 3. Build It as Clinical Tasks / Reminders

Medication administration should be implemented using a reusable clinical task/reminder engine.

Create a general concept:

```text
Clinical Tasks / Care Tasks / Nursing Tasks
```

Medication administration is one type of clinical task.

Future task types can include:

```text
Medication Administration
Vitals Monitoring
Wound Dressing
IV Fluid Check
Blood Sugar Check
Doctor Review
Follow-up Instruction
Investigation Follow-up
Procedure Preparation
Nursing Observation
```

This allows the same task/reminder engine to later support many hospital workflows.

---

# 4. Medication Administration Is Not Just a Reminder

A normal reminder says:

```text
Remember to give Ceftriaxone
```

UHMS needs a clinical task with proof:

```text
Scheduled dose
Due time
Actual administration time
Administered by nurse
Dose given
Route
Status
Reason if not given
Patient reaction
Witness if needed
Audit trail
```

So every medication task must result in a proper administration record or a documented reason why it was not administered.

---

# 5. Required Workflow for Admission

For admitted patients:

```text
Doctor prescribes medication
    ↓
Pharmacy dispenses medication to patient/admission
    ↓
System creates or activates medication administration schedule
    ↓
System creates clinical tasks/reminders for scheduled doses
    ↓
Admission Board shows due/upcoming/overdue medications
    ↓
Nurse records each dose administration
    ↓
Task is completed or marked missed/held/refused
    ↓
Medication order progress updates automatically
```

---

# 6. Required Workflow for Emergency

For Emergency patients:

```text
Doctor orders emergency medication
    ↓
Medication may be administered immediately or scheduled
    ↓
If from Emergency stock, stock is deducted once
    ↓
System records administration
    ↓
Emergency Board shows STAT/due/overdue meds
```

Emergency must support:

```text
STAT medication
Immediate administration
PRN/SOS medication
Scheduled observation medication
Emergency stock source
```

---

# 7. Admission Board Requirements

The Admission Board must show medication tracking per admitted patient.

Patient row should show:

```text
Patient
Bed / Ward
Active medication orders
Due now count
Overdue count
Upcoming count
Completed count
Missed dose warning
```

Example:

```text
Ama Mensah | Ward A Bed 4 | 3 Active Meds | 1 Due Now | 0 Overdue
```

Clicking should open a medication administration panel/modal/page.

Medication panel should show:

```text
Active Medications
Due Now
Overdue
Upcoming
Completed
PRN/SOS
Held/Stopped
```

Example:

```text
Active Medications:
1. Ceftriaxone 1g IV BD — 6/10 doses given
2. Paracetamol 1g PO TDS — 3/9 doses given
3. Omeprazole 20mg PO OD — 1/5 doses given
```

---

# 8. Emergency Board Requirements

The Emergency Board should show:

```text
STAT medications due
Emergency medications administered
Observation medications
Overdue critical medications
PRN/SOS medications
```

Emergency medication board columns:

```text
Patient
Emergency Number
Location / Bay
Medication
Dose
Route
Scheduled Time
Status
Prescribed By
Administered By
Action
```

---

# 9. Clinical Task Statuses

Clinical tasks should support statuses:

```text
SCHEDULED
DUE
OVERDUE
IN_PROGRESS
COMPLETED
MISSED
HELD
REFUSED
SKIPPED
CANCELLED
```

The system can calculate `DUE` and `OVERDUE` dynamically from `due_at`, but it is also acceptable to persist status if the project already uses persisted task statuses.

---

# 10. Medication Order Statuses

Medication orders should support statuses:

```text
PENDING_DISPENSING
PARTIALLY_DISPENSED
DISPENSED
ACTIVE_ADMINISTRATION
COMPLETED
HELD
STOPPED
CANCELLED
EXPIRED
```

Important:

```text
A medication is not completed just because it was dispensed.
It is completed when all required doses are administered, cancelled, stopped, or otherwise clinically closed.
```

---

# 11. Medication Dose / Schedule Statuses

Each scheduled dose should support:

```text
SCHEDULED
DUE
OVERDUE
GIVEN
PARTIALLY_GIVEN
MISSED
SKIPPED
REFUSED
HELD
CANCELLED
VOIDED
CORRECTED
```

---

# 12. Frequency System

Implement or reuse a medication frequency system.

Create or update:

```text
medication_frequencies
- id
- code
- name
- times_per_day nullable
- interval_hours nullable
- default_times json nullable
- requires_schedule boolean default true
- is_prn boolean default false
- is_stat boolean default false
- is_active boolean default true
- created_at
- updated_at
```

Seed common frequencies:

```text
OD   = once daily, default 08:00
BD   = twice daily, default 08:00 and 20:00
TDS  = three times daily, default 08:00, 14:00, 20:00
QID  = four times daily, default 06:00, 12:00, 18:00, 22:00
Q6H  = every 6 hours
Q8H  = every 8 hours
Q12H = every 12 hours
STAT = immediately once
PRN  = as needed
SOS  = as needed / emergency
```

Do not hardcode frequency logic only in frontend.

---

# 13. Quantity / Duration Logic

The system should calculate expected doses from frequency and duration.

Example:

```text
BD for 5 days = 10 doses
TDS for 3 days = 9 doses
OD for 7 days = 7 doses
```

When doctor enters:

```text
Frequency = BD
Duration = 5 days
```

System calculates:

```text
Expected doses = 10
```

If quantity is entered manually and does not match expected doses, show warning:

```text
BD for 5 days usually requires 10 doses. You entered 8.
```

Do not block unless hospital policy requires blocking.

---

# 14. Medication Orders

Use the existing prescription model if it already contains enough information.

If not, create a medication order layer linked to prescriptions.

Recommended table:

```text
medication_orders
- id
- visit_id
- admission_id nullable
- emergency_case_id nullable
- patient_id
- medical_record_id nullable
- consultation_route_id nullable
- prescription_id nullable
- prescription_item_id nullable
- prescribed_by
- product_id
- dose
- dose_unit
- route
- frequency_id
- duration_value nullable
- duration_unit nullable
- total_doses
- quantity_ordered
- quantity_dispensed default 0
- start_at
- end_at nullable
- instructions nullable
- status
- source_type nullable
- source_id nullable
- created_at
- updated_at
```

Rules:

* Do not duplicate prescription data unnecessarily.
* If prescriptions already work, create medication_orders from prescription items.
* Medication orders must be linked to visit, patient, and admission/emergency when applicable.

---

# 15. Medication Administration Schedules

Create scheduled dose records.

Recommended table:

```text
medication_administration_schedules
- id
- medication_order_id
- visit_id
- admission_id nullable
- emergency_case_id nullable
- patient_id
- scheduled_at
- dose
- dose_unit
- route
- status
- sequence_number
- clinical_task_id nullable
- created_at
- updated_at
```

Example:

```text
Medication: Ceftriaxone 1g IV BD
Dose 1/10 — scheduled_at = 2026-05-26 08:00
Dose 2/10 — scheduled_at = 2026-05-26 20:00
```

Each scheduled dose should have a matching clinical task/reminder.

---

# 16. Medication Administration Records

Create actual nurse administration records.

Recommended table:

```text
medication_administrations
- id
- medication_order_id
- schedule_id nullable
- clinical_task_id nullable
- visit_id
- admission_id nullable
- emergency_case_id nullable
- patient_id
- administered_by
- administered_at
- scheduled_at nullable
- dose_given
- dose_unit
- route
- status
- reason_not_given nullable
- notes nullable
- reaction nullable
- source_stock_type nullable
- stock_location_id nullable
- stock_movement_id nullable
- witnessed_by nullable
- corrected_by nullable
- corrected_at nullable
- correction_reason nullable
- created_at
- updated_at
```

Do not delete administration records. If correction is needed, void/correct with audit trail.

---

# 17. Clinical Tasks Table

Create or update reusable clinical task table.

Recommended table:

```text
clinical_tasks
- id
- visit_id
- admission_id nullable
- emergency_case_id nullable
- patient_id
- task_type
- title
- description nullable
- scheduled_at nullable
- due_at
- status
- priority
- assigned_to nullable
- assigned_role nullable
- assigned_department_id nullable
- source_type nullable
- source_id nullable
- completed_by nullable
- completed_at nullable
- notes nullable
- escalation_level default 0
- last_reminded_at nullable
- created_at
- updated_at
```

For medication administration:

```text
task_type = MEDICATION_ADMINISTRATION
source_type = medication_administration_schedule
source_id = schedule.id
```

---

# 18. Clinical Task Types

Support at least:

```text
MEDICATION_ADMINISTRATION
VITALS_MONITORING
WOUND_DRESSING
IV_FLUID_CHECK
BLOOD_SUGAR_CHECK
DOCTOR_REVIEW
FOLLOW_UP
INVESTIGATION_FOLLOW_UP
PROCEDURE_PREPARATION
NURSING_OBSERVATION
OTHER
```

Only medication administration must be fully implemented now.

Other task types can be prepared structurally for later.

---

# 19. Schedule Generation Service

Create service:

```text
MedicationScheduleService
```

Required methods:

```php
generateForOrder(MedicationOrder $order): Collection

regenerateFutureSchedules(MedicationOrder $order, User $user, ?string $reason = null): Collection

cancelFutureSchedules(MedicationOrder $order, User $user, ?string $reason = null): void
```

Generation rules:

* Use frequency default times or interval hours.
* Use start_at as schedule anchor.
* Generate total_doses.
* Create one schedule per dose.
* Create one clinical task per schedule.
* Do not duplicate schedules if they already exist.
* If order is stopped, cancel future schedules only.
* Past given doses must remain unchanged.

---

# 20. Clinical Task Reminder Service

Create service:

```text
ClinicalTaskReminderService
```

Responsibilities:

```text
Find due medication tasks
Find overdue medication tasks
Update task status where needed
Return dashboard counts
Trigger in-app notifications if notification system exists
Escalate overdue tasks if configured
```

Reminder windows:

```text
Upcoming = due within next 30 minutes
Due Now = due within configured due window
Overdue = due time passed and not completed
```

Make reminder windows configurable.

Suggested settings:

```text
medication_task_upcoming_minutes = 30
medication_task_overdue_after_minutes = 15
medication_task_escalate_after_minutes = 30
medication_task_second_escalation_after_minutes = 60
```

---

# 21. Nurse Alerts / Keeping Nurses Awake

The system should actively alert nurses.

First implementation should include:

```text
Admission Board due/overdue badges
Emergency Board due/overdue badges
Medication Task Board
In-app notifications if existing notification system supports it
Auto-refresh or polling for due/overdue tasks
Escalation list for supervisors
```

Optional later:

```text
sound alert
browser push notification
SMS
WhatsApp
email
```

For now, implement in-app alerting and board badges.

---

# 22. Escalation Rules

If a medication task is overdue:

```text
Overdue by 15 minutes → show red overdue badge
Overdue by 30 minutes → notify ward supervisor / charge nurse
Overdue by 60 minutes → escalate to matron / doctor if configured
```

Escalation should not spam repeatedly.

Track:

```text
last_reminded_at
escalation_level
```

Use existing notification system if available.

If not available, create dashboard-level escalation list first.

---

# 23. Nurse Administration Workflow

Nurse opens Admission Board or Emergency Board.

For each due medication task, nurse can:

```text
Administer
Hold
Skip
Mark Refused
Mark Missed
Record Reaction
```

Administration modal fields:

```text
Medication
Scheduled time
Dose
Route
Actual administration time
Dose given
Status
Reason if not given
Notes
Patient reaction
Witness optional
```

When nurse saves:

* create medication_administration record
* update schedule status
* update clinical task status
* update medication order progress
* do not reload whole page
* update board counts immediately

---

# 24. Administration Status Behavior

If nurse marks `GIVEN`:

```text
schedule.status = GIVEN
task.status = COMPLETED
administration.status = GIVEN
```

If nurse marks `REFUSED`:

```text
schedule.status = REFUSED
task.status = COMPLETED or REFUSED depending existing task model
reason_not_given required
```

If nurse marks `HELD`:

```text
schedule.status = HELD
reason_not_given required
doctor/nurse note required if configured
```

If nurse marks `MISSED`:

```text
schedule.status = MISSED
reason_not_given required
```

If nurse marks `SKIPPED`:

```text
schedule.status = SKIPPED
reason_not_given required
```

Do not leave due tasks open after nurse has documented an acceptable non-given reason.

---

# 25. Pharmacy Dispensing Integration

When pharmacy dispenses medication for an admitted or emergency patient:

* update quantity_dispensed on medication order if applicable
* activate or generate administration schedule if not already generated
* show available quantity for administration
* do not mark medication as administered
* do not mark medication as completed

If pharmacy dispenses less than ordered:

Example:

```text
Ordered: 10 doses
Dispensed: 6 doses
```

Admission Board should show:

```text
10 scheduled doses
6 available doses
4 pending supply
```

Nurse should not administer more doses than available patient-dispensed stock unless using ward/emergency stock.

---

# 26. Stock Deduction Rule

Very important:

Do not reduce stock twice.

Normal admission flow:

```text
Pharmacy dispensing reduces pharmacy stock.
Nurse administration does not reduce stock again.
```

Emergency or ward stock flow:

```text
If medication is administered directly from Emergency/Ward stock:
    administration creates stock OUT movement from that department stock location.
```

Each administration should know source:

```text
PATIENT_DISPENSED_STOCK
WARD_STOCK
EMERGENCY_STOCK
OTHER_DEPARTMENT_STOCK
```

If source is `PATIENT_DISPENSED_STOCK`:

```text
do not create stock movement
```

If source is department stock:

```text
create stock movement OUT once
link stock_movement_id to medication_administrations
```

Use existing Product Stock Movement system.

Do not reintroduce drug stock parallel system.

---

# 27. Department Stock Location

For ward/emergency stock administration:

* resolve stock location from department
* Emergency uses Emergency stock location
* Ward uses Ward stock location if available
* do not consume directly from Main Store
* if no stock location exists, show clear error

Use existing:

```text
StockLocationResolver
StockMovementService
StockBalanceService
```

or project equivalents.

---

# 28. PRN / SOS Medication

PRN/SOS medications do not generate fixed recurring schedules.

They should appear under:

```text
PRN / SOS Available Medications
```

Nurse can administer when needed.

PRN administration must require:

```text
reason / symptom
dose
time
response
notes
```

Optional safety rule:

```text
respect maximum daily dose if configured
```

If maximum daily dose is not implemented, leave TODO but structure data for it.

---

# 29. STAT Medication

STAT medication should create one immediate task.

Rules:

```text
scheduled_at = now
due_at = now
status = DUE
```

Once given:

```text
schedule.status = GIVEN
task.status = COMPLETED
medication_order.status = COMPLETED
```

If not given:

```text
record reason
close/cancel appropriately based on clinical decision
```

---

# 30. Holding, Stopping, and Changing Medication

Doctors may hold, stop, or change medications.

If medication is held:

```text
future tasks may be HELD or paused
reason required
```

If medication is stopped:

```text
future scheduled doses = CANCELLED
future clinical tasks = CANCELLED
medication_order.status = STOPPED
past given doses remain unchanged
```

If medication is changed:

Recommended safe behavior:

```text
stop old medication order
create new medication order
generate new schedule
```

Do not overwrite the old order history.

---

# 31. Corrections / Audit Trail

Do not delete administration records.

If an administration was recorded wrongly:

* allow correction only with permission
* require correction reason
* keep old values in audit log
* mark old record corrected/voided if needed

Create or reuse:

```text
medication_administration_logs
- id
- medication_administration_id nullable
- medication_order_id nullable
- schedule_id nullable
- action
- old_value json nullable
- new_value json nullable
- reason nullable
- performed_by
- created_at
```

Actions:

```text
GIVEN
HELD
MISSED
REFUSED
SKIPPED
CORRECTED
VOIDED
ORDER_STOPPED
ORDER_HELD
SCHEDULE_GENERATED
TASK_CREATED
TASK_ESCALATED
```

---

# 32. Admission Medication Board UI

Create or update Admission Board medication area.

Views:

```text
By Patient
Due Now
Overdue
Upcoming
Completed Today
Missed / Held / Refused
PRN / SOS
```

Columns:

```text
Patient
Ward / Bed
Medication
Dose
Route
Frequency
Scheduled Time
Status
Prescribed By
Administered By
Action
```

Color coding:

```text
Upcoming = gray
Due Now = blue
Overdue = red
Given = green
Held = orange
Missed = red/dark
Refused = warning
Cancelled = muted
```

---

# 33. Emergency Medication Board UI

Create or update Emergency Board medication area.

Views:

```text
STAT Due
Due Now
Overdue
Observation Medications
Administered Today
PRN / SOS
```

Columns:

```text
Patient
Emergency Number
Bay / Location
Medication
Dose
Route
Scheduled Time
Status
Prescribed By
Administered By
Action
```

Emergency should highlight STAT meds strongly.

---

# 34. Medication Administration Modal

Create reusable modal/component:

```text
MedicationAdministrationModal
```

Fields:

```text
Patient
Medication
Scheduled Time
Dose Ordered
Route
Frequency
Dose Given
Actual Administration Time
Administration Status
Reason Not Given
Patient Reaction
Notes
Witness
Source Stock Type
Stock Location if applicable
```

Rules:

* actual administration time defaults to now
* dose_given defaults to scheduled dose
* reason required for non-given statuses
* source stock required if not patient-dispensed
* witness optional for now
* modal closes only after successful save
* validation errors show inside modal
* no backdrop stuck

---

# 35. Medication Order Progress

For each medication order, calculate:

```text
total_doses
given_doses
remaining_doses
missed_doses
held_doses
refused_doses
cancelled_doses
next_due_at
progress_percentage
```

Show:

```text
Ceftriaxone 1g IV BD — 4/10 doses given
Next dose: Today 20:00
Status: Active
```

Medication order should become completed when:

```text
all scheduled doses are GIVEN, CANCELLED, MISSED, REFUSED, or otherwise clinically closed
```

Prefer:

```text
COMPLETED only when all required doses are given
```

and use another status like `CLOSED` if non-given statuses complete the schedule. If existing statuses do not include CLOSED, document the chosen approach.

---

# 36. Reports

Prepare or implement basic reports:

```text
Medication Administration Report
Due Medication Report
Overdue Medication Report
Missed Dose Report
Held / Refused Dose Report
Nurse Administration Report
Drug Utilization Report
Adverse Reaction Report
Admission Medication Progress Report
Emergency Medication Report
```

At minimum, implement:

```text
Medication Administration Report
Overdue Medication Report
Missed Dose Report
Nurse Administration Report
```

Filters:

```text
date range
ward
emergency unit
patient
medication/product
nurse
status
doctor/prescriber
```

---

# 37. Permissions

Add or verify permissions:

```text
medication_orders.view
medication_orders.manage
medication_orders.stop
medication_orders.hold

medication_administration.view
medication_administration.administer
medication_administration.hold
medication_administration.mark_missed
medication_administration.correct
medication_administration.view_reports

clinical_tasks.view
clinical_tasks.manage
clinical_tasks.complete
clinical_tasks.escalate
clinical_tasks.view_overdue

admission.medication_board.view
emergency.medication_board.view
```

Roles likely involved:

```text
Nurse
Ward Nurse
Emergency Nurse
Doctor
Pharmacist
Ward Supervisor
Matron
Admin
Super Admin
```

---

# 38. Routes / Controllers

Use existing route conventions.

Possible controllers:

```text
MedicationOrderController
MedicationScheduleController
MedicationAdministrationController
MedicationAdministrationBoardController
ClinicalTaskController
ClinicalTaskReminderController
AdmissionMedicationBoardController
EmergencyMedicationBoardController
MedicationAdministrationReportController
```

Keep controllers thin.

Business logic belongs in services.

---

# 39. Services to Create / Update

Create or update:

```text
MedicationOrderService
MedicationScheduleService
MedicationAdministrationService
ClinicalTaskService
ClinicalTaskReminderService
MedicationFrequencyService
MedicationProgressService
AdmissionMedicationBoardService
EmergencyMedicationBoardService
MedicationAdministrationReportService
StockLocationResolver
StockMovementService
```

Do not duplicate existing services if they already exist.

Extend existing prescription/pharmacy services carefully.

---

# 40. Integration with Existing Prescriptions

Inspect existing prescription flow first.

Determine:

```text
where doctor prescription is stored
where prescription items are stored
how pharmacy dispensing works
how dispensed quantities are stored
how admission/emergency prescriptions are identified
```

Then map prescription items into medication orders.

Do not rebuild prescription module.

Do not break pharmacy dispensing.

---

# 41. Integration with Visit Preview

Update Visit Preview to include medication administration timeline.

Show:

```text
Medication ordered
Medication dispensed
Dose scheduled
Dose given / missed / held / refused
Administered by nurse
Reaction/notes
```

Example:

```text
Ceftriaxone 1g IV — Dose 4/10 given by Nurse Ama at 08:05.
Patient tolerated medication well.
```

---

# 42. Integration with Consultation Summary

Consultation Summary may show medication orders/prescriptions, but administration details are more relevant to Admission/Emergency and Visit Preview.

If shown in summary, display progress:

```text
Paracetamol 1g TDS — 3/9 doses given
```

Do not overcrowd consultation summary with every dose unless requested.

---

# 43. Notifications

Use existing notification system if available.

Create notifications for:

```text
Medication due now
Medication overdue
Medication task escalated
Medication held
Medication missed
Medication reaction recorded
```

Recipients:

```text
assigned nurse
ward nurses
emergency nurses
ward supervisor
doctor if escalated
```

Do not send external SMS/WhatsApp yet unless existing infrastructure exists.

---

# 44. Auto-Refresh / Polling

Medication boards should stay current.

Implement one of:

```text
Inertia partial reload polling
Vue polling every configured interval
WebSocket/event broadcasting if existing
manual refresh button plus auto-refresh
```

Recommended initial approach:

```text
poll every 60 seconds for due/overdue counts
```

Make interval configurable.

---

# 45. Validation Rules

Administration validation:

```text
schedule_id required unless PRN/SOS
medication_order_id required
patient_id required
status required
administered_at required for GIVEN
dose_given required for GIVEN
reason_not_given required for HELD/MISSED/REFUSED/SKIPPED
administered_by = current user
source_stock_type required
stock_location_id required if source_stock_type is WARD_STOCK or EMERGENCY_STOCK
cannot administer cancelled/stopped order
cannot administer already completed schedule unless correction flow
cannot administer more doses than available patient-dispensed stock unless using ward/emergency stock
```

Schedule generation validation:

```text
frequency required
dose required
route required
start_at required
total_doses or duration required
```

---

# 46. Data Integrity Rules

* Do not duplicate schedules for same order.
* Do not create duplicate clinical tasks for same schedule.
* Do not administer same schedule twice unless correction/repeat is explicitly allowed.
* Do not reduce stock twice.
* Do not delete administration history.
* Do not edit completed administration without audit trail.
* Do not continue future schedules after order is stopped.
* Do not administer medication for discharged/completed visit unless authorized correction.

---

# 47. Performance Rules

Avoid N+1 queries.

Medication boards should eager-load:

```text
patient
admission
emergency case
bed/location
medication order
product
frequency
prescriber
schedule
clinical task
administration
administered_by
```

Use indexed columns:

```text
clinical_tasks.due_at
clinical_tasks.status
clinical_tasks.task_type
clinical_tasks.assigned_department_id
medication_administration_schedules.scheduled_at
medication_administration_schedules.status
medication_orders.patient_id
medication_orders.admission_id
medication_orders.emergency_case_id
```

Do not load all historical administrations on the board. Load active/due/current-day data.

---

# 48. Tests Required

Add or update tests:

## Frequency / Scheduling

1. Frequency seed creates OD, BD, TDS, QID, STAT, PRN.
2. BD for 5 days generates 10 schedules.
3. TDS for 3 days generates 9 schedules.
4. STAT generates one due task.
5. PRN does not generate fixed schedule.
6. Schedule generation does not duplicate schedules.

## Clinical Tasks

7. Each scheduled dose creates a clinical task.
8. Due medication task appears on Admission Board.
9. Overdue task appears as overdue.
10. Completed task disappears from Due Now.
11. Escalation level updates after configured delay.

## Administration

12. Nurse can mark dose GIVEN.
13. GIVEN creates medication administration record.
14. GIVEN updates schedule and task status.
15. Nurse can mark dose HELD with reason.
16. Nurse can mark dose REFUSED with reason.
17. Nurse cannot mark non-given status without reason.
18. Nurse cannot administer cancelled schedule.
19. Nurse cannot administer stopped order.
20. Duplicate administration is prevented.

## Admission Board

21. Admission Board shows active medication count.
22. Admission Board shows due now count.
23. Admission Board shows overdue count.
24. Patient medication progress shows 4/10 doses given.

## Emergency Board

25. Emergency Board shows STAT medication.
26. Emergency stock administration creates stock OUT movement.
27. Patient-dispensed stock administration does not create stock movement.

## Stock

28. Pharmacy dispensing reduces stock once.
29. Nurse administration from patient-dispensed stock does not reduce stock again.
30. Nurse administration from ward/emergency stock reduces correct department stock once.
31. Main Store is not consumed directly.

## Corrections / Audit

32. Administration correction requires permission.
33. Correction requires reason.
34. Correction creates audit log.
35. Administration records are not deleted.

## Integration

36. Pharmacy dispensing activates/generates medication administration schedule.
37. Stopping medication cancels future schedules.
38. Past given doses remain unchanged after stop.
39. Visit Preview shows medication administration timeline.
40. Notifications/board badges appear for due/overdue medications.

---

# 49. Deliverables

Provide:

1. Gap analysis of existing prescription/pharmacy/admission/emergency medication flow.
2. Medication frequency setup.
3. Medication order mapping from prescriptions.
4. Medication schedule generation.
5. Clinical task/reminder engine support.
6. Admission Medication Board.
7. Emergency Medication Board.
8. Medication administration modal.
9. Nurse administration recording.
10. Due/overdue alerts.
11. Escalation handling.
12. Stock deduction safety.
13. PRN/SOS support.
14. STAT support.
15. Stop/hold medication support.
16. Audit/correction support.
17. Reports or report foundation.
18. Permissions/seeders.
19. Visit Preview integration.
20. Tests or verification notes.
21. Files modified.
22. Remaining TODOs.

---

# 50. Important Rules

Do not treat prescription as proof of administration.

Do not treat pharmacy dispensing as proof of administration.

Do not reduce stock twice.

Do not create a parallel drug inventory system.

Do not bypass Product stock movement system.

Do not delete administration records.

Do not allow medication administration without auditability.

Do not continue schedules after medication is stopped.

Do not require nurses to remember doses manually; create tasks/reminders.

Do not break existing consultation, prescription, pharmacy, admission, emergency, billing, stock, or visit workflows.

Now inspect the current implementation and build a Medication Administration Record system powered by Clinical Tasks/Reminders, integrated into Admission Board and Emergency Board.

```
```
