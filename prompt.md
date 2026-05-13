You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to design and implement a complete **Theatre / Procedure Workflow Module**.

Focus only on theatre procedures, procedure requests, theatre scheduling, procedure billing, pre-op records, anaesthesia notes, surgeon operative notes, post-op notes, procedure timeline, and consultation integration.

Do not refactor unrelated modules.

---

# 1. Main Objective

Implement a full theatre procedure workflow:

```text
Doctor requests procedure
    ↓
Procedure status = REQUESTED

Theatre accepts procedure
    ↓
Procedure status = ACCEPTED

System creates billing item
    ↓
Procedure status = BILLED

Theatre schedules procedure
    ↓
Procedure status = SCHEDULED

Theatre records pre-op vitals
    ↓
Procedure status = PRE_OP

Anaesthetist records anaesthesia note
    ↓
Procedure status = ANAESTHESIA

Surgeon records operative note
    ↓
Procedure status = IN_SURGERY or SURGERY_DONE

Recovery/post-op note entered
    ↓
Procedure status = POST_OP

Procedure finalized
    ↓
Procedure status = COMPLETED
```

This workflow must be linked to the patient visit and visible in the patient’s visit history.

---

# 2. Core Business Rules

1. A doctor requests a procedure from the Consultation page.
2. The procedure request must be linked to:

   * patient
   * visit
   * requesting doctor
   * selected procedure service
   * theatre/procedure department
3. Theatre must accept the procedure before billing.
4. Billing happens after theatre acceptance.
5. Billing must create a billable item on the **same visit invoice**.
6. Do not create a separate invoice for theatre.
7. Billing must go through `BillingService`.
8. Theatre billing must respect the patient’s selected visit insurance or Cash and Carry fallback.
9. Scheduling happens after billing.
10. Pre-op must not start before billing, unless emergency override is explicitly allowed.
11. Anaesthesia note and surgeon operative note must be separate.
12. Vitals must be recordable at multiple procedure stages.
13. Completed procedure must be visible from the Consultation page and Patient Visit History.
14. Cancelled/rejected procedures must require a reason.
15. If a billed procedure is cancelled, do not delete the invoice item silently. Void/cancel/reverse it with reason.

---

# 3. Required Procedure Statuses

Create or update a procedure status enum.

Recommended statuses:

```text
REQUESTED
ACCEPTED
BILLED
SCHEDULED
PRE_OP
ANAESTHESIA
IN_SURGERY
SURGERY_DONE
POST_OP
COMPLETED
REJECTED
CANCELLED
ON_HOLD
RESCHEDULED
```

Use existing project enum conventions if available.

---

# 4. Procedure Request from Consultation

On the Doctor Consultation page, add a **Procedure / Theatre Request** section or tab.

The doctor should be able to select:

* procedure department / theatre department
* procedure service
* priority
* indication / reason for procedure
* provisional diagnosis or linked diagnosis if available
* notes
* preferred date/time optional

When submitted:

* create a procedure request
* status = `REQUESTED`
* do not create billing yet
* show success without full page reload
* keep doctor on the same consultation page/tab
* show the request under the patient’s procedure list/timeline

Use:

```php
ProcedureRequestService::requestProcedure(...)
```

---

# 5. Theatre Dashboard / Queue

Create or update a Theatre dashboard.

It should have tabs or filters:

```text
Pending Requests
Accepted
Billed
Scheduled Today
In Theatre
Recovery / Post-op
Completed
Cancelled / Rejected
```

Each row should display:

* patient name
* visit number
* age/gender
* requested procedure service
* requesting doctor
* priority
* request date/time
* current status
* billing status
* insurance/payment type
* scheduled time if available
* surgeon if assigned
* anaesthetist if assigned
* action button

Action buttons should depend on status:

```text
REQUESTED → Accept / Reject
ACCEPTED → Generate Billing
BILLED → Schedule
SCHEDULED → Record Pre-op
PRE_OP → Record Anaesthesia
ANAESTHESIA → Start Surgery / Record Surgeon Note
IN_SURGERY → Complete Surgery
SURGERY_DONE → Record Post-op
POST_OP → Finalize Procedure
COMPLETED → View / Print Report
```

---

# 6. Theatre Acceptance

When theatre accepts the procedure:

* validate the procedure is in `REQUESTED`
* set status to `ACCEPTED`
* save accepted_by
* save accepted_at
* optionally add acceptance notes

Do not create billing before acceptance.

Use:

```php
ProcedureWorkflowService::acceptProcedure(...)
```

If rejected:

* require rejection reason
* set status to `REJECTED`
* save rejected_by
* save rejected_at
* do not create billing

---

# 7. Procedure Billing

After acceptance, create billing item.

When theatre generates billing:

* validate status is `ACCEPTED`
* get the visit’s main invoice
* add procedure service as an invoice item through `BillingService`
* apply patient’s selected visit insurance or Cash and Carry fallback
* save invoice_item_id / billing_item_id on the procedure request
* set procedure status to `BILLED`

Use:

```php
BillingService::addItemToVisitInvoice(...)
ProcedureWorkflowService::markBilled(...)
```

Required billing source:

```text
source_type = procedure_request
source_id = procedure_requests.id
```

Rules:

* do not create a separate theatre invoice
* prevent duplicate billing for same procedure request
* if already billed, return clear error
* if billing fails, do not move status to `BILLED`
* if procedure is cancelled after billing, void/cancel invoice item with reason; do not delete silently

---

# 8. Theatre Scheduling

After billing, theatre can schedule the procedure.

Scheduling should capture:

* theatre room
* scheduled start date/time
* scheduled end date/time optional
* surgeon
* anaesthetist
* assistant surgeon optional
* theatre nurses optional
* required equipment optional
* notes

When scheduling:

* validate procedure status is `BILLED`
* create or update procedure schedule
* set status = `SCHEDULED`
* save scheduled_by
* save scheduled_at

Use:

```php
ProcedureScheduleService::scheduleProcedure(...)
```

If rescheduled:

* keep previous schedule history if possible
* set status = `RESCHEDULED` or keep `SCHEDULED` with reschedule log
* require reason

---

# 9. Pre-op Vitals and Checklist

After scheduling, theatre records pre-op vitals and checklist.

Pre-op vitals may include:

* temperature
* blood pressure
* pulse
* respiratory rate
* oxygen saturation
* weight
* pain score
* notes

Pre-op checklist may include:

* consent signed
* fasting confirmed
* allergies checked
* blood available if needed
* site marked if applicable
* pre-op diagnosis
* equipment ready
* anaesthesia review done

When pre-op is saved:

* validate procedure status is `SCHEDULED`
* create procedure vitals with stage `PRE_OP`
* save checklist details
* set status = `PRE_OP`
* save recorded_by and recorded_at

Use:

```php
ProcedureClinicalService::recordPreOp(...)
```

---

# 10. Anaesthesia Note

Anaesthetist records anaesthesia note after pre-op.

Anaesthesia note fields:

* anaesthetist
* anaesthesia type:

  * local
  * regional
  * spinal
  * general
  * sedation
  * other
* pre-anaesthesia assessment
* drugs used
* dosage / medication notes
* airway management
* monitoring notes
* complications
* start time
* end time
* notes

When anaesthesia note is saved:

* validate procedure status is `PRE_OP`
* create anaesthesia note
* set status = `ANAESTHESIA`
* save anaesthetist_id
* save timestamp

Use:

```php
ProcedureClinicalService::recordAnaesthesiaNote(...)
```

---

# 11. Surgeon Operative Note

Surgeon records operative note.

Operative note fields:

* surgeon
* assistant surgeon optional
* procedure performed
* pre-op diagnosis
* post-op diagnosis
* findings
* incision
* technique
* blood loss
* complications
* specimens taken
* implants/materials used
* start time
* end time
* outcome
* notes

When surgeon note is started/saved:

* validate procedure status is `ANAESTHESIA`
* set status = `IN_SURGERY` when surgery begins
* save operative note
* set status = `SURGERY_DONE` when operative note is completed

Use:

```php
ProcedureClinicalService::startSurgery(...)
ProcedureClinicalService::recordOperativeNote(...)
ProcedureClinicalService::completeSurgery(...)
```

---

# 12. Post-op / Recovery Note

After surgery, recovery/post-op note is entered.

Post-op fields:

* recorded_by
* recovery status
* post-op vitals
* pain score
* consciousness level
* post-op instructions
* medications
* complications
* transfer destination:

  * ward
  * ICU
  * outpatient discharge
  * emergency observation
  * recovery room
* notes

When post-op note is saved:

* validate procedure status is `SURGERY_DONE`
* create post-op note
* optionally create procedure vitals with stage `POST_OP` or `RECOVERY`
* set status = `POST_OP`

Use:

```php
ProcedureClinicalService::recordPostOp(...)
```

---

# 13. Finalize Procedure

When all required notes are completed:

* validate status is `POST_OP`
* set status = `COMPLETED`
* save completed_by
* save completed_at
* make full procedure report available

Use:

```php
ProcedureWorkflowService::completeProcedure(...)
```

---

# 14. Procedure Timeline Feature

Implement a Procedure Timeline view.

Timeline should show each step:

```text
Requested
Accepted
Billed
Scheduled
Pre-op Vitals
Anaesthesia
Surgery
Post-op
Completed
```

Each timeline item should show:

* status
* timestamp
* responsible user
* notes summary
* action button if action is pending
* view details button if completed

Example:

```text
✓ Requested by Dr. Mensah
✓ Accepted by Theatre Nurse
✓ Billed on Visit Invoice #INV-00034
✓ Scheduled for 10:30 AM in Theatre Room 1
✓ Pre-op vitals recorded
✓ Anaesthesia note entered
✓ Surgeon operative note entered
✓ Post-op note entered
✓ Completed
```

This timeline should be visible:

* on Theatre procedure detail page
* in Patient Visit History
* from Consultation page under Procedures/Theatre section

---

# 15. Consultation Page Integration

On the consultation page:

* doctors can request procedures
* doctors can see procedure requests grouped/listed by status
* doctors can see current theatre status
* doctors can view completed procedure reports
* doctors cannot delete/cancel a procedure after theatre has accepted it unless permitted
* if procedure is billed or completed, cancellation must follow backend rules

The doctor should see:

```text
Procedure
Status
Billing Status
Scheduled Date
Surgeon
Anaesthetist
Actions: View Timeline / View Report
```

---

# 16. Patient Visit History Integration

The procedure must be visible in patient visit history.

Visit history should show:

* procedure requested
* theatre acceptance
* billing item
* schedule
* pre-op vitals
* anaesthesia note
* operative note
* post-op note
* completion status
* printable report

---

# 17. Printing / Reports

Create printable reports:

1. Procedure request form
2. Theatre schedule
3. Pre-op checklist
4. Anaesthesia report
5. Operative note
6. Post-op report
7. Full procedure report

Full procedure report should include:

* hospital information
* patient details
* visit details
* procedure service
* requesting doctor
* priority
* indication
* billing/invoice reference
* schedule details
* pre-op vitals/checklist
* anaesthesia note
* operative note
* post-op note
* surgeon
* anaesthetist
* theatre room
* timestamps
* completion status

Only completed procedures should show final full procedure report unless preview/draft mode is explicitly allowed.

---

# 18. Suggested Database Tables

Use existing tables if available. Otherwise create clean migrations.

## procedure_requests

```text
id
visit_id
patient_id
requested_by
department_id
service_id
priority
indication
notes
status
billing_item_id nullable
accepted_by nullable
accepted_at nullable
rejected_by nullable
rejected_at nullable
rejection_reason nullable
cancelled_by nullable
cancelled_at nullable
cancellation_reason nullable
completed_by nullable
completed_at nullable
requested_at
created_at
updated_at
```

## procedure_schedules

```text
id
procedure_request_id
theatre_room_id nullable
scheduled_start
scheduled_end nullable
surgeon_id nullable
anaesthetist_id nullable
assistant_surgeon_id nullable
status
notes
scheduled_by
scheduled_at
created_at
updated_at
```

## procedure_vitals

```text
id
procedure_request_id
stage
temperature nullable
blood_pressure nullable
pulse nullable
respiratory_rate nullable
oxygen_saturation nullable
weight nullable
pain_score nullable
recorded_by
recorded_at
notes nullable
created_at
updated_at
```

Stages:

```text
PRE_OP
INTRA_OP
POST_OP
RECOVERY
```

## procedure_checklists

```text
id
procedure_request_id
consent_signed
fasting_confirmed
allergies_checked
blood_available
site_marked
equipment_ready
anaesthesia_review_done
pre_op_diagnosis nullable
completed_by
completed_at
notes nullable
created_at
updated_at
```

## anaesthesia_notes

```text
id
procedure_request_id
anaesthetist_id
anaesthesia_type
pre_assessment nullable
drugs_used nullable
dosage_notes nullable
airway_management nullable
monitoring_notes nullable
complications nullable
start_time nullable
end_time nullable
notes nullable
created_at
updated_at
```

## operative_notes

```text
id
procedure_request_id
surgeon_id
assistant_surgeon_id nullable
procedure_performed
pre_op_diagnosis nullable
post_op_diagnosis nullable
findings nullable
incision nullable
technique nullable
blood_loss nullable
complications nullable
specimens nullable
implants nullable
start_time nullable
end_time nullable
outcome nullable
notes nullable
created_at
updated_at
```

## post_op_notes

```text
id
procedure_request_id
recorded_by
recovery_status nullable
pain_score nullable
consciousness_level nullable
post_op_instructions nullable
medications nullable
complications nullable
transfer_destination nullable
notes nullable
created_at
updated_at
```

## theatre_rooms

```text
id
name
location nullable
is_active
created_at
updated_at
```

## procedure_status_logs

```text
id
procedure_request_id
from_status nullable
to_status
changed_by
reason nullable
notes nullable
created_at
updated_at
```

---

# 19. Models and Relationships

Create/update models:

```text
ProcedureRequest
ProcedureSchedule
ProcedureVital
ProcedureChecklist
AnaesthesiaNote
OperativeNote
PostOpNote
TheatreRoom
ProcedureStatusLog
```

Relationships:

## ProcedureRequest

```php
visit()
patient()
requestingDoctor()
department()
service()
billingItem()
schedule()
vitals()
checklist()
anaesthesiaNote()
operativeNote()
postOpNote()
statusLogs()
```

## ProcedureSchedule

```php
procedureRequest()
theatreRoom()
surgeon()
anaesthetist()
assistantSurgeon()
scheduledBy()
```

---

# 20. Services

Create or update:

```text
ProcedureRequestService
ProcedureWorkflowService
ProcedureScheduleService
ProcedureClinicalService
ProcedureReportService
BillingService
InvoiceService
ServicePricingService
InsuranceService
```

## ProcedureRequestService

Handles:

* doctor procedure request
* validation
* listing requests
* consultation page integration

## ProcedureWorkflowService

Handles:

* accept
* reject
* mark billed
* cancel
* complete
* status transitions
* status logs

## ProcedureScheduleService

Handles:

* schedule
* reschedule
* room/doctor assignment

## ProcedureClinicalService

Handles:

* pre-op vitals
* checklist
* anaesthesia note
* surgery start
* operative note
* post-op note

## ProcedureReportService

Handles:

* timeline data
* printable reports
* visit history summary

---

# 21. Required Methods

Implement methods like:

```php
ProcedureRequestService::requestProcedure(array $data, User $doctor): ProcedureRequest

ProcedureWorkflowService::acceptProcedure(ProcedureRequest $procedure, User $user, ?string $notes = null): ProcedureRequest

ProcedureWorkflowService::rejectProcedure(ProcedureRequest $procedure, User $user, string $reason): ProcedureRequest

ProcedureWorkflowService::generateBilling(ProcedureRequest $procedure, User $user): ProcedureRequest

ProcedureScheduleService::scheduleProcedure(ProcedureRequest $procedure, array $data, User $user): ProcedureSchedule

ProcedureClinicalService::recordPreOp(ProcedureRequest $procedure, array $data, User $user): ProcedureRequest

ProcedureClinicalService::recordAnaesthesiaNote(ProcedureRequest $procedure, array $data, User $user): AnaesthesiaNote

ProcedureClinicalService::startSurgery(ProcedureRequest $procedure, User $user): ProcedureRequest

ProcedureClinicalService::recordOperativeNote(ProcedureRequest $procedure, array $data, User $user): OperativeNote

ProcedureClinicalService::recordPostOp(ProcedureRequest $procedure, array $data, User $user): PostOpNote

ProcedureWorkflowService::completeProcedure(ProcedureRequest $procedure, User $user): ProcedureRequest

ProcedureReportService::getTimeline(ProcedureRequest $procedure): array
```

---

# 22. Validation Rules

## Request Procedure

* visit_id required
* patient_id must match visit patient
* department_id required
* department must be theatre/procedure department
* service_id required
* service must belong to selected department
* priority required
* indication required
* requested_by required

## Accept Procedure

* status must be `REQUESTED`
* user must have permission to accept procedure

## Generate Billing

* status must be `ACCEPTED`
* procedure must not already be billed
* service must be billable
* visit must have invoice or be able to create one
* billing must use patient insurance/cash fallback

## Schedule Procedure

* status must be `BILLED`
* scheduled_start required
* theatre room required if system requires it
* surgeon required if system requires it
* anaesthetist required if system requires it

## Pre-op

* status must be `SCHEDULED`
* vitals required according to system rules
* checklist required according to system rules

## Anaesthesia

* status must be `PRE_OP`
* anaesthesia type required
* anaesthetist required

## Operative Note

* status must be `ANAESTHESIA` or `IN_SURGERY`
* surgeon required
* procedure performed required
* start/end time validation

## Post-op

* status must be `SURGERY_DONE`
* recovery status or notes required

## Complete Procedure

* status must be `POST_OP`
* required notes must exist

---

# 23. Permissions

Add or verify permissions:

```text
procedure.request
procedure.view
procedure.accept
procedure.reject
procedure.bill
procedure.schedule
procedure.reschedule
procedure.record_preop
procedure.record_anaesthesia
procedure.record_surgery
procedure.record_postop
procedure.complete
procedure.cancel
procedure.print
procedure.view_report
```

Roles that may use them:

* doctor
* theatre nurse
* anaesthetist
* surgeon
* cashier/admin
* superadmin

---

# 24. Inertia/Vue Pages and Components

Create or update:

```text
resources/js/Pages/Procedures/Index.vue
resources/js/Pages/Procedures/Show.vue
resources/js/Pages/Procedures/Schedule.vue
resources/js/Pages/Procedures/Reports/FullReport.vue
resources/js/Components/Procedures/ProcedureTimeline.vue
resources/js/Components/Procedures/ProcedureRequestForm.vue
resources/js/Components/Procedures/PreOpForm.vue
resources/js/Components/Procedures/AnaesthesiaNoteForm.vue
resources/js/Components/Procedures/OperativeNoteForm.vue
resources/js/Components/Procedures/PostOpNoteForm.vue
```

On Consultation page, add or update:

```text
ProcedureRequestForm
ProcedureList
ProcedureTimelineModal
ProcedureReportModal
```

Use SPA behavior:

* no full page reloads
* preserve active tab
* show validation errors inline
* do not dismiss modals before successful response
* show loading states

---

# 25. Theatre Module Sidebar / Routes

Add module navigation if procedure/theatre module is enabled:

```text
Theatre / Procedures
    - Requests
    - Schedule
    - In Theatre
    - Completed
    - Rooms
    - Reports
```

If module system exists:

* register theatre/procedure module
* hide menu if disabled
* protect routes with module middleware

---

# 26. Data Integrity Rules

* Do not bill before theatre accepts the request.
* Do not schedule before billing.
* Do not start pre-op before scheduling.
* Do not record anaesthesia before pre-op.
* Do not record surgeon note before anaesthesia.
* Do not complete procedure before post-op note.
* Do not create separate invoice for theatre.
* Do not duplicate billing for the same procedure request.
* Do not delete billed procedures silently.
* Do not cancel without reason.
* Do not reject without reason.
* Do not bypass `BillingService`.
* Do not bypass procedure workflow status transitions.
* Do not let frontend update status directly.

---

# 27. Performance Rules

* Eager-load procedure requests with patient, visit, service, department, schedule, billing item, and status logs where needed.
* Paginate theatre queues.
* Do not load full procedure report until requested.
* Load timeline summary efficiently.
* Avoid N+1 queries on theatre dashboard.
* Cache static theatre room/service lists where safe.

---

# 28. Testing / Verification

Add or update tests for:

1. Doctor can request procedure.
2. Procedure starts as `REQUESTED`.
3. Theatre can accept procedure.
4. Accepted procedure can be billed.
5. Billing creates invoice item on same visit invoice.
6. Duplicate billing is prevented.
7. Billed procedure can be scheduled.
8. Scheduled procedure can record pre-op.
9. Pre-op procedure can record anaesthesia.
10. Anaesthesia procedure can record operative note.
11. Surgery can be marked done.
12. Post-op can be recorded.
13. Procedure can be completed.
14. Rejected procedure requires reason.
15. Cancelled billed procedure does not delete invoice item silently.
16. Procedure timeline shows all completed steps.
17. Doctor can view procedure report from consultation page.

---

# 29. Deliverables

Provide:

1. New/updated migrations.
2. New/updated models and relationships.
3. Procedure status enum.
4. Services implementation.
5. Controllers and Form Requests.
6. Inertia/Vue pages and components.
7. Consultation page integration.
8. Theatre dashboard.
9. Procedure timeline.
10. Billing integration through `BillingService`.
11. Report/print views.
12. Permissions.
13. Tests or verification notes.
14. List of files modified.
15. Remaining TODOs if any.

---

# 30. Important Rules

Do not hardcode theatre as a single room or single service.

Do not create separate invoices.

Do not bill before acceptance.

Do not schedule before billing.

Do not allow status jumps outside the allowed workflow.

Do not put procedure workflow logic in controllers.

Do not update status from Vue directly.

Do not remove existing working consultation features.

Do not break patient visit history.

Do not ignore insurance pricing.

Do not silently delete billed/cancelled procedure records.

Now inspect the existing UHMS implementation and build the Theatre / Procedure Workflow module carefully according to this specification.
