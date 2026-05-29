```text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to implement Theatre Rooms Management as part of the existing Procedure/Theatre workflow.

UHMS already has patients, visits, admission, emergency, consultation, procedures, billing, invoices, insurance pricing, product stock, stock locations, consumables, MAR/medication administration, clinical tasks, and visit preview.

Theatre Rooms Management must integrate with the existing Procedure module. Do not create a parallel procedure system.

Theatre Rooms Management should handle the physical theatre/procedure rooms, schedules, room availability, theatre team, pre-op workflow, anaesthesia note, surgeon operative note, post-op/recovery note, consumables, billing, and visit preview integration.

---

# 1. Core Concept

Current procedure workflow should evolve into:

Procedure Request
    ↓
Theatre Accepts Procedure
    ↓
Billing Item Created if required
    ↓
Theatre Case Created / Linked
    ↓
Theatre Room Scheduled
    ↓
Pre-op Checklist
    ↓
Anaesthesia Note
    ↓
Surgeon Operative Note
    ↓
Post-op / Recovery Note
    ↓
Completed

Theatre Rooms Management answers:

- Which room?
- At what time?
- For which patient?
- For which procedure?
- With which surgeon/team?
- What is the room status?
- What resources/equipment are needed?
- What is the case status?

---

# 2. Main Objectives

Implement:

1. Theatre rooms CRUD.
2. Theatre room types and statuses.
3. Theatre schedule board.
4. Theatre room calendar.
5. Theatre case management.
6. Procedure request to theatre case linking.
7. Theatre room assignment.
8. Double-booking prevention.
9. Emergency theatre case priority.
10. Theatre team assignment.
11. Pre-op checklist.
12. Anaesthesia note.
13. Surgeon operative note.
14. Post-op/recovery note.
15. Theatre consumables from Theatre stock location.
16. Theatre billing integration through existing BillingService.
17. Theatre room maintenance/blocking.
18. Theatre reports foundation.
19. Visit Preview integration.
20. Permissions and menu updates.

---

# 3. Important Rules

Do not create a parallel procedure workflow.

Do not create a parallel billing system.

Do not create a parallel stock system.

Do not create theatre consumables outside Products.

Do not bypass BillingService.

Do not bypass StockMovementService.

Do not bypass existing Procedure Request workflow.

Do not block emergency/life-saving theatre care because invoice is unpaid.

Do not allow double-booking of active theatre cases in the same room/time.

Do not delete clinical theatre notes. Use correction/audit trail if needed.

Do not overwrite the main surgeon when another theatre team member adds notes.

Do not break existing procedures, emergency, admission, billing, stock, MAR, visit preview, or consultation workflows.

---

# 4. Theatre Rooms

Create or update:

theatre_rooms

Recommended fields:

id
name
code
department_id nullable
room_type
capacity nullable
location nullable
status
notes nullable
is_active boolean default true
created_at
updated_at

Room types:

MAJOR_THEATRE
MINOR_THEATRE
EMERGENCY_THEATRE
MATERNITY_THEATRE
ENDOSCOPY_ROOM
PROCEDURE_ROOM
RECOVERY_ROOM
DENTAL_PROCEDURE_ROOM
EYE_THEATRE
OTHER

Room statuses:

AVAILABLE
OCCUPIED
SCHEDULED
CLEANING
MAINTENANCE
OUT_OF_SERVICE
RESERVED

Examples:

- Main Theatre 1
- Main Theatre 2
- Minor Procedure Room
- Maternity Theatre
- Emergency Theatre
- Endoscopy Room
- Dental Procedure Room
- Eye Theatre
- Recovery Room

---

# 5. Theatre Room CRUD UI

Create Theatre Room management page.

Fields:

- Room name
- Room code
- Department
- Room type
- Capacity
- Location
- Status
- Notes
- Active/inactive

Actions:

- Create room
- Edit room
- Deactivate room
- Set available
- Set cleaning
- Set maintenance
- Set out of service
- View schedule

Rules:

- Room code must be unique.
- Inactive/out-of-service rooms cannot be scheduled.
- Rooms with active theatre cases should not be deactivated without override permission.

---

# 6. Theatre Cases

Create or update:

theatre_cases

Recommended fields:

id
procedure_request_id nullable
visit_id
patient_id
admission_id nullable
emergency_case_id nullable
theatre_room_id nullable
scheduled_start_at nullable
scheduled_end_at nullable
actual_start_at nullable
actual_end_at nullable
expected_duration_minutes nullable
primary_surgeon_id nullable
assistant_surgeon_id nullable
anaesthetist_id nullable
scrub_nurse_id nullable
circulating_nurse_id nullable
recovery_nurse_id nullable
priority
status
accepted_by nullable
accepted_at nullable
scheduled_by nullable
completed_by nullable
completed_at nullable
cancelled_by nullable
cancelled_at nullable
cancellation_reason nullable
notes nullable
created_at
updated_at

Priority values:

ELECTIVE
URGENT
EMERGENCY
LIFE_SAVING

Theatre case statuses:

REQUESTED
ACCEPTED
BILLED
SCHEDULED
PRE_OP
ANAESTHESIA_READY
IN_THEATRE
IN_SURGERY
SURGERY_DONE
RECOVERY
POST_OP
COMPLETED
CANCELLED
POSTPONED

---

# 7. Procedure Request Integration

Theatre Case must be linked to the existing procedure request.

When a procedure request is accepted by Theatre:

- create or update theatre_case
- link procedure_request_id
- link visit_id and patient_id
- link admission_id or emergency_case_id where applicable
- preserve requested_by / created_by from procedure request
- set theatre case status = ACCEPTED
- bill procedure if workflow requires billing at acceptance
- do not duplicate billing

Procedure Request remains the clinical request entry point.

Theatre Case becomes the operational theatre execution record.

---

# 8. Theatre Room Scheduling

Theatre staff should assign:

- theatre room
- date
- start time
- expected duration
- surgeon
- anaesthetist
- scrub nurse
- circulating nurse
- assistant surgeon optional
- priority
- notes

When scheduled:

- set scheduled_start_at
- set scheduled_end_at
- set theatre_room_id
- set assigned theatre team
- set status = SCHEDULED
- mark room as SCHEDULED or keep AVAILABLE depending calendar design
- log scheduling event

The system must check room availability.

If room is unavailable, show:

Room already booked from 08:00 to 09:30. Choose another room or time.

---

# 9. Double-Booking Prevention

Prevent two active theatre cases in the same room during overlapping times.

Overlap rule:

A case conflicts if:

existing.scheduled_start_at < new.scheduled_end_at
AND
existing.scheduled_end_at > new.scheduled_start_at
AND
existing.theatre_room_id = selected room
AND
existing.status NOT IN (CANCELLED, POSTPONED, COMPLETED)

Do not allow scheduling into:

- OUT_OF_SERVICE room
- MAINTENANCE room
- inactive room

Emergency override may be allowed only with permission:

theatre.schedule.override

If override occurs:

- require reason
- log override
- optionally mark affected elective case as POSTPONED

---

# 10. Theatre Schedule Board

Create Theatre Schedule Board.

Views:

- Today’s Theatre List
- Upcoming Cases
- Emergency Cases
- By Room
- By Surgeon
- By Status
- Completed Cases
- Cancelled/Postponed Cases

Columns:

- Time
- Patient
- Visit / Admission / Emergency No.
- Procedure
- Room
- Surgeon
- Anaesthetist
- Priority
- Status
- Billing Status
- Actions

Example:

08:00 | Ama Mensah | Appendectomy | Theatre 1 | Dr. Kofi | Scheduled
10:30 | Yao Mensah | Wound Debridement | Minor Room | Dr. Ama | Pre-op
Emergency | Unknown Male | Exploratory Laparotomy | Emergency Theatre | Pending Room

Actions:

- View case
- Assign room
- Reschedule
- Start pre-op
- Record anaesthesia
- Start surgery
- Add operative note
- Send to recovery
- Complete
- Cancel/Postpone

---

# 11. Theatre Room Calendar

Create room calendar view.

For each theatre room, show scheduled blocks:

08:00 - 09:30 Appendectomy
10:00 - 11:00 Hernia Repair
11:00 - 11:30 Cleaning
12:00 - 13:00 C-section

Features:

- daily view
- weekly view optional
- filter by room
- filter by surgeon
- filter by priority
- show maintenance/cleaning blocks
- show emergency cases clearly

Rules:

- calendar data must come from theatre_cases and room blocks/maintenance
- prevent double-booking
- sort chronologically

---

# 12. Theatre Room Maintenance / Blocking

Create support for room blocking.

Recommended table:

theatre_room_blocks

Fields:

id
theatre_room_id
block_type
start_at
end_at
reason
notes nullable
created_by
created_at
updated_at

Block types:

CLEANING
MAINTENANCE
STERILIZATION
EQUIPMENT_FAILURE
RESERVED_EMERGENCY_SLOT
OTHER

Rules:

- Cannot schedule normal cases during active room block.
- Emergency override requires permission and reason.
- Blocks should appear on room calendar.

---

# 13. Emergency Theatre Cases

Emergency cases can create theatre requests.

Flow:

Emergency Case
    ↓
Procedure Request
    ↓
Theatre Case
    ↓
Emergency Theatre scheduling

Emergency theatre cases should:

- have priority EMERGENCY or LIFE_SAVING
- appear at top of Theatre Board
- suggest Emergency Theatre room first if available
- allow urgent scheduling
- not be blocked by unpaid invoice
- still create billing items through BillingService

If emergency case is linked:

theatre_cases.emergency_case_id should be set.

---

# 14. Theatre Team Management

Theatre case should track:

- primary surgeon
- assistant surgeon
- anaesthetist
- scrub nurse
- circulating nurse
- recovery nurse
- other staff/contributors

Optional table:

theatre_case_team_members

Fields:

id
theatre_case_id
user_id
role
assigned_by
assigned_at
created_at
updated_at

Roles:

PRIMARY_SURGEON
ASSISTANT_SURGEON
ANAESTHETIST
SCRUB_NURSE
CIRCULATING_NURSE
RECOVERY_NURSE
OBSERVER
OTHER

Rules:

- Do not overwrite primary surgeon when another team member adds a note.
- Each note/action must show its creator.
- Contributors should be visible on theatre case page.
- Team assignment changes should be logged.

---

# 15. Theatre Case Detail Page

Create Theatre Case Detail page.

Sections:

1. Patient / Visit Header
2. Procedure Request Summary
3. Room & Schedule
4. Theatre Team
5. Pre-op Checklist
6. Anaesthesia Note
7. Surgeon Operative Note
8. Consumables / Equipment
9. Post-op / Recovery
10. Billing Summary
11. Timeline / Audit

Header should show:

- patient
- visit/admission/emergency number
- procedure
- priority
- room
- scheduled time
- status
- primary surgeon
- anaesthetist
- billing status

---

# 16. Pre-op Checklist

Create pre-op checklist support.

Recommended table:

theatre_preop_checklists

Fields:

id
theatre_case_id
completed_by nullable
completed_at nullable
status
notes nullable
created_at
updated_at

Checklist item table:

theatre_preop_checklist_items

Fields:

id
theatre_preop_checklist_id
item_key
label
status
notes nullable
checked_by nullable
checked_at nullable
created_at
updated_at

Checklist item statuses:

PENDING
COMPLETE
INCOMPLETE
WAIVED

Default checklist items:

- Patient identity confirmed
- Consent signed
- Procedure site marked
- Allergies checked
- Fasting status confirmed
- Vitals checked
- Lab results reviewed
- Blood available if needed
- Anaesthesia assessment done
- Equipment ready
- Implants/prosthesis ready
- Antibiotic prophylaxis given

Rules:

- Required checklist items should be configurable.
- Warn before moving to IN_SURGERY if required checklist items are incomplete.
- If user proceeds despite incomplete checklist, require override permission and reason.
- Checklist actions must be logged.

---

# 17. Anaesthesia Note

Create anaesthesia note support.

Recommended table:

theatre_anaesthesia_notes

Fields:

id
theatre_case_id
anaesthetist_id nullable
anaesthesia_type
asa_class nullable
airway_assessment nullable
pre_anaesthesia_assessment nullable
drugs_given text/json nullable
induction_time nullable
monitoring_notes nullable
complications nullable
recovery_status nullable
notes nullable
created_by
updated_by nullable
created_at
updated_at

Anaesthesia types:

GENERAL
SPINAL
EPIDURAL
LOCAL
SEDATION
REGIONAL_BLOCK
NONE

Rules:

- Anaesthesia note must show creator.
- Anaesthetist can edit own note while case active.
- Other users cannot edit unless authorized.
- Completed theatre case notes require correction permission.

---

# 18. Surgeon Operative Note

Create operative note support.

Recommended table:

theatre_operative_notes

Fields:

id
theatre_case_id
surgeon_id nullable
procedure_performed
indication nullable
findings nullable
technique nullable
incision nullable
complications nullable
estimated_blood_loss nullable
specimens_taken nullable
implants_used nullable
drains_placed nullable
post_op_diagnosis nullable
post_op_plan nullable
notes nullable
created_by
updated_by nullable
created_at
updated_at

Rules:

- Operative note becomes part of patient clinical history.
- Operative note must appear in Visit Preview.
- Surgeon can edit own note while case active.
- Completed case requires correction permission.

---

# 19. Post-op / Recovery Note

Create recovery/post-op note support.

Recommended table:

theatre_recovery_notes

Fields:

id
theatre_case_id
recovery_room_id nullable
recovery_nurse_id nullable
arrival_at nullable
discharge_from_recovery_at nullable
consciousness_level nullable
pain_score nullable
nausea_vomiting nullable
bleeding_status nullable
oxygen_support nullable
vitals_summary nullable
post_op_medications nullable
notes nullable
created_by
updated_by nullable
created_at
updated_at

This section should support:

- recovery room/bed
- vitals
- pain score
- consciousness
- nausea/vomiting
- bleeding
- oxygen
- post-op medication
- recovery nurse
- discharge from recovery time

Integrate with MAR/clinical tasks where possible.

---

# 20. Theatre Consumables

Theatre uses consumables from Theatre stock location.

Examples:

- sutures
- gloves
- gauze
- drapes
- blades
- catheters
- syringes
- anaesthetic drugs
- implants
- oxygen supplies

Rules:

- Theatre cannot create products.
- Theatre selects products from Products linked to Theatre/Procedure department.
- Theatre stock comes from Theatre stock location.
- Stock deducts from Theatre stock location.
- Billable consumables create invoice items through BillingService.
- Non-billable consumables only create stock movement.
- Do not consume directly from Main Store.
- Do not create separate theatre item stock.

Recommended table if missing:

theatre_consumable_usages

Fields:

id
theatre_case_id
visit_id
patient_id
department_id nullable
stock_location_id
product_id
quantity
is_billable
invoice_item_id nullable
stock_movement_id nullable
used_by
used_at
notes nullable
created_at
updated_at

Use existing department_consumable_usages table if already available instead of creating duplicate.

---

# 21. Theatre Equipment

Prepare simple equipment tracking if not already available.

Optional table:

theatre_equipment

Fields:

id
name
code
equipment_type
theatre_room_id nullable
status
notes nullable
is_active
created_at
updated_at

Equipment statuses:

AVAILABLE
IN_USE
MAINTENANCE
OUT_OF_SERVICE

Optional theatre case equipment table:

theatre_case_equipment

Fields:

id
theatre_case_id
theatre_equipment_id
status
notes nullable
assigned_by
created_at
updated_at

This can be basic in first implementation.

Do not overbuild if asset management already exists; reuse assets if possible.

---

# 22. Billing Integration

Theatre billing must stay separate from clinical notes but integrated.

Billing items may include:

- procedure service
- theatre room charge
- anaesthesia charge
- surgeon fee
- consumables
- implants
- recovery charge
- emergency theatre surcharge

Rules:

- Use the visit invoice.
- Use BillingService.
- Use insurance pricing.
- Use selected visit insurance.
- Cash and Carry fallback still applies.
- Do not block emergency/life-saving theatre care because invoice is unpaid.
- Avoid duplicate billing using source_type/source_id.
- Do not create separate theatre invoice system.

Source examples:

source_type = theatre_case
source_type = theatre_consumable_usage
source_type = theatre_anaesthesia
source_type = theatre_room_charge

The theatre case page should show billing summary but not mix billing forms into clinical note sections.

---

# 23. Theatre Case Status Transitions

Implement status transitions:

REQUESTED
↓
ACCEPTED
↓
BILLED
↓
SCHEDULED
↓
PRE_OP
↓
ANAESTHESIA_READY
↓
IN_THEATRE
↓
IN_SURGERY
↓
SURGERY_DONE
↓
RECOVERY
↓
POST_OP
↓
COMPLETED

Also support:

CANCELLED
POSTPONED

Rules:

- Only valid transitions allowed.
- Log each transition.
- Require reason for cancellation/postponement.
- Do not complete case without required notes unless override permission.
- Do not move to IN_SURGERY if required pre-op checklist incomplete unless override permission.

---

# 24. Theatre Timeline / Audit

Create or use activity log.

Timeline events:

- procedure requested
- theatre accepted
- billed
- room scheduled
- team assigned
- pre-op checklist updated
- anaesthesia note added
- surgery started
- operative note added
- surgery completed
- recovery started
- recovery note added
- consumables used
- billing item created
- case completed
- cancelled/postponed

Each event must show:

- time
- user
- role
- action
- details

If no existing activity log supports this, create:

theatre_case_logs

Fields:

id
theatre_case_id
visit_id
patient_id
action
title
description nullable
source_type nullable
source_id nullable
performed_by
created_at

---

# 25. Visit Preview Integration

Update Visit Preview to include Theatre/Procedure timeline.

Visit Preview should show:

- procedure request
- theatre acceptance
- room schedule
- theatre team
- pre-op checklist summary
- anaesthesia note
- surgeon operative note
- consumables used
- post-op/recovery note
- theatre billing items
- completion/disposition

Every entry must show:

- time
- user
- role
- department/session
- details

The operative note and anaesthesia note must be visible in patient clinical history.

---

# 26. Procedure Catalogue Integration

Procedure catalogue should continue to load from services with department type PROCEDURE/THEATRE.

Theatre rooms management should not replace procedure catalogue.

Procedure Request uses procedure services.

Theatre Case executes/schedules the accepted procedure.

---

# 27. UI/UX Requirements

Use existing UHMS UI design patterns.

Theatre Board should be easy to scan.

Theatre Case Detail should feel like an operational clinical document.

Avoid hiding critical information.

Use badges for:

- priority
- status
- room status
- billing status
- checklist status

Use modals for:

- assign room
- assign team
- reschedule
- cancel/postpone
- add consumable
- add/edit note

Modals must:

- close only after successful save
- show validation errors inside modal
- not leave stuck backdrop
- update UI immediately without full page reload

---

# 28. Permissions

Add or verify permissions:

theatre.rooms.view
theatre.rooms.create
theatre.rooms.update
theatre.rooms.deactivate
theatre.rooms.manage_status

theatre.board.view
theatre.cases.view
theatre.cases.accept
theatre.cases.schedule
theatre.cases.reschedule
theatre.cases.cancel
theatre.cases.postpone
theatre.cases.complete

theatre.team.assign
theatre.preop.manage
theatre.anaesthesia.create
theatre.anaesthesia.edit_own
theatre.anaesthesia.edit_any
theatre.operative_note.create
theatre.operative_note.edit_own
theatre.operative_note.edit_any
theatre.recovery_note.create
theatre.recovery_note.edit_own
theatre.recovery_note.edit_any

theatre.consumables.use
theatre.billing.view
theatre.billing.manage
theatre.schedule.override
theatre.reports.view

Suggested roles:

- Theatre Nurse
- Surgeon
- Anaesthetist
- Recovery Nurse
- Theatre Manager
- Doctor
- Admin
- Super Admin

---

# 29. Routes / Controllers

Use existing route conventions.

Suggested controllers:

TheatreRoomController
TheatreBoardController
TheatreCaseController
TheatreScheduleController
TheatreTeamController
TheatrePreopChecklistController
TheatreAnaesthesiaNoteController
TheatreOperativeNoteController
TheatreRecoveryNoteController
TheatreConsumableController
TheatreBillingController
TheatreTimelineController
TheatreReportController

Suggested routes:

Route::prefix('admin/theatre')
    ->name('admin.theatre.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/rooms', [TheatreRoomController::class, 'index'])->name('rooms.index');
        Route::post('/rooms', [TheatreRoomController::class, 'store'])->name('rooms.store');
        Route::patch('/rooms/{theatreRoom}', [TheatreRoomController::class, 'update'])->name('rooms.update');

        Route::get('/board', [TheatreBoardController::class, 'index'])->name('board');
        Route::get('/calendar', [TheatreScheduleController::class, 'calendar'])->name('calendar');

        Route::get('/cases/{theatreCase}', [TheatreCaseController::class, 'show'])->name('cases.show');
        Route::post('/procedure-requests/{procedureRequest}/accept', [TheatreCaseController::class, 'accept'])->name('procedure-requests.accept');

        Route::post('/cases/{theatreCase}/schedule', [TheatreScheduleController::class, 'schedule'])->name('cases.schedule');
        Route::post('/cases/{theatreCase}/team', [TheatreTeamController::class, 'store'])->name('cases.team.store');
        Route::post('/cases/{theatreCase}/preop', [TheatrePreopChecklistController::class, 'update'])->name('cases.preop.update');
        Route::post('/cases/{theatreCase}/anaesthesia', [TheatreAnaesthesiaNoteController::class, 'store'])->name('cases.anaesthesia.store');
        Route::post('/cases/{theatreCase}/operative-note', [TheatreOperativeNoteController::class, 'store'])->name('cases.operative-note.store');
        Route::post('/cases/{theatreCase}/recovery-note', [TheatreRecoveryNoteController::class, 'store'])->name('cases.recovery-note.store');
        Route::post('/cases/{theatreCase}/consumables', [TheatreConsumableController::class, 'store'])->name('cases.consumables.store');
        Route::post('/cases/{theatreCase}/complete', [TheatreCaseController::class, 'complete'])->name('cases.complete');
        Route::post('/cases/{theatreCase}/cancel', [TheatreCaseController::class, 'cancel'])->name('cases.cancel');
    });

Adapt to existing project route naming.

---

# 30. Frontend Pages / Components

If using Inertia/Vue, create or update:

resources/js/Pages/Theatre/Rooms.vue
resources/js/Pages/Theatre/Board.vue
resources/js/Pages/Theatre/Calendar.vue
resources/js/Pages/Theatre/ShowCase.vue

Components:

TheatreRoomForm
TheatreRoomStatusBadge
TheatreScheduleBoard
TheatreRoomCalendar
TheatreCaseHeader
TheatreTeamSection
TheatrePreopChecklist
AnaesthesiaNoteSection
OperativeNoteSection
RecoveryNoteSection
TheatreConsumablesSection
TheatreBillingSummary
TheatreTimeline
ScheduleTheatreCaseModal
AssignTheatreTeamModal
CancelPostponeModal

Reuse existing components where possible.

---

# 31. Services to Create / Update

Create or update:

TheatreRoomService
TheatreScheduleService
TheatreCaseService
TheatreTeamService
TheatrePreopChecklistService
TheatreAnaesthesiaService
TheatreOperativeNoteService
TheatreRecoveryService
TheatreConsumableService
TheatreBillingService
TheatreTimelineService
TheatreReportService
BillingService
StockLocationResolver
StockMovementService
VisitPreviewService

Do not duplicate existing BillingService or StockMovementService.

---

# 32. Validation Rules

Theatre room:

- name required
- code required unique
- room_type required
- status required
- cannot deactivate active room with scheduled/in-progress case unless override

Scheduling:

- theatre_room_id required
- scheduled_start_at required
- expected_duration required
- scheduled_end_at calculated or required
- room must be active
- room must not be out of service/maintenance
- no overlapping active case unless override
- surgeon/anaesthetist optional depending procedure type
- override requires permission and reason

Pre-op:

- checklist exists for theatre case
- required items must be completed before IN_SURGERY unless override
- waiver requires note/reason

Anaesthesia note:

- anaesthesia_type required
- created_by current user
- theatre_case_id required

Operative note:

- procedure_performed required
- theatre_case_id required
- created_by current user

Recovery note:

- theatre_case_id required
- recovery nurse/current user
- notes or recovery fields required

Consumables:

- product_id required
- quantity > 0
- theatre stock location required
- quantity <= theatre available stock
- billable consumables use BillingService

Status transitions:

- only valid transitions allowed
- cancellation/postponement requires reason
- completion requires operative note or override depending configuration

---

# 33. Performance Requirements

Avoid N+1 queries.

Theatre Board should eager-load:

- patient
- visit
- procedure request
- procedure service
- theatre room
- primary surgeon
- anaesthetist
- priority
- status
- billing status

Theatre Case Detail should eager-load:

- patient
- visit
- admission/emergency
- procedure request/service
- room
- team members
- checklist/items
- anaesthesia note
- operative note
- recovery note
- consumables/products
- invoice items
- logs

Calendar should load only cases and blocks within selected date range.

Add indexes:

theatre_cases.theatre_room_id
theatre_cases.scheduled_start_at
theatre_cases.scheduled_end_at
theatre_cases.status
theatre_cases.patient_id
theatre_cases.visit_id
theatre_room_blocks.theatre_room_id
theatre_room_blocks.start_at
theatre_room_blocks.end_at

---

# 34. Reports

Prepare reports:

- Theatre utilization report
- Room occupancy report
- Procedures by surgeon
- Procedures by department
- Emergency theatre cases
- Cancelled/postponed cases
- Average start delay
- Average procedure duration
- Complication report
- Consumables usage report
- Anaesthesia report

At minimum create report foundations/routes if reporting system exists.

---

# 35. Tests Required

Add or update tests.

## Theatre Rooms

1. User can create theatre room.
2. Room code must be unique.
3. User can update room status.
4. Out-of-service room cannot be scheduled.
5. Room with active case cannot be deactivated without override.

## Scheduling

6. Procedure request can be accepted into theatre case.
7. Theatre case can be scheduled to room.
8. Double-booking same room/time is prevented.
9. Cancelled cases do not block scheduling.
10. Maintenance blocks prevent scheduling.
11. Emergency override requires permission and reason.

## Theatre Board / Calendar

12. Theatre Board shows today’s cases.
13. Theatre Board filters by room.
14. Theatre Board filters by surgeon.
15. Theatre Calendar shows scheduled cases.
16. Theatre Calendar shows room blocks.

## Team

17. Theatre team can be assigned.
18. Additional team member does not overwrite primary surgeon.
19. Team assignment is logged.

## Pre-op

20. Pre-op checklist is created.
21. Checklist items can be completed.
22. Missing required checklist warns/blocks before IN_SURGERY.
23. Waived checklist item requires note.

## Anaesthesia / Operative / Recovery Notes

24. Anaesthesia note can be recorded.
25. Operative note can be recorded.
26. Recovery note can be recorded.
27. Notes show creator.
28. Unauthorized user cannot edit another user’s note.

## Consumables / Stock

29. Theatre consumable usage deducts Theatre stock.
30. Theatre cannot consume directly from Main Store.
31. Billable consumable creates invoice item.
32. Non-billable consumable does not create invoice item.
33. Stock is not deducted twice.

## Billing

34. Theatre case billing uses visit invoice.
35. BillingService is used.
36. Duplicate billing is prevented by source_type/source_id.
37. Emergency theatre case is not blocked by unpaid invoice.

## Status Workflow

38. Theatre case follows valid status transitions.
39. Cancellation requires reason.
40. Completion requires required clinical notes or authorized override.

## Visit Preview

41. Visit Preview includes theatre timeline.
42. Operative note appears in patient clinical history.
43. Anaesthesia note appears in patient clinical history.
44. Consumables and billing references appear where appropriate.

---

# 36. Deliverables

Provide:

1. Gap analysis of current Procedure/Theatre workflow.
2. Theatre room migrations/models.
3. Theatre case model/integration.
4. Theatre room CRUD.
5. Theatre Schedule Board.
6. Theatre Room Calendar.
7. Room double-booking prevention.
8. Emergency theatre case handling.
9. Theatre team assignment.
10. Pre-op checklist.
11. Anaesthesia note.
12. Surgeon operative note.
13. Post-op/recovery note.
14. Theatre consumables from Theatre stock.
15. Theatre billing integration.
16. Room maintenance/blocking.
17. Theatre timeline/audit.
18. Visit Preview integration.
19. Permissions/menu/routes.
20. Tests or verification notes.
21. Files modified.
22. Remaining TODOs.

---

# 37. Important Rules

Do not create a parallel procedure system.

Do not replace Procedure Request with Theatre Room management.

Do not create a parallel billing system.

Do not create a parallel stock system.

Do not allow double-booking active cases.

Do not schedule inactive/out-of-service rooms.

Do not block emergency/life-saving theatre care because invoice is unpaid.

Do not deduct stock twice.

Do not bypass BillingService.

Do not bypass StockMovementService.

Do not hide theatre clinical notes from Visit Preview or patient history.

Now inspect the current UHMS implementation and implement Theatre Rooms Management as an operational layer integrated into the existing Procedure/Theatre workflow.
```
