````text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to fix and refine the Visit, Emergency, Admission, Billing, Bed Count, Consumables, and Patient Pathway workflow.

UHMS already has OPD/Visits, Admission, Emergency, Consultation Sessions, Investigations, Pharmacy, Procedures/Theatre, Billing, Stock/Consumables, Beds, MAR, and Visit Preview.

The current problem is that the Visit Status Flow and patient movement logic are too rigid and do not properly reflect the real path/parcours a patient follows in the hospital.

We need to redesign the logic carefully without breaking existing workflows.

Do not rebuild the whole system blindly. First inspect the current implementation, identify gaps, then fix only what is wrong or incomplete.

Do not break:
- OPD visit creation
- consultation sessions
- emergency cases
- admission
- billing
- pharmacy
- investigations
- procedures/theatre
- stock/consumables
- bed management
- visit preview
- patient history
- claims

---

# 1. Main Problems to Fix

Fix the following issues:

1. If a patient is currently admitted and not yet discharged, the system must not allow creating another normal OPD visit for that patient.
2. When creating an emergency case from a visit, the system currently does not create the Emergency Session. This must be fixed.
3. Re-examine the Visit Status Flow.
4. Remove misleading visit statuses such as laboratory, pharmacy, billing from the main visit status flow.
5. Visit status should reflect the patient’s global care state, not every department they pass through.
6. A patient may go directly to investigation, pharmacy, or procedure without consultation.
7. A patient may be in consultation, then sent to investigation/pharmacy/procedure, then return to consultation without changing the main visit status away from CONSULTING.
8. Visit Preview must reflect the real pathway/parcours of the patient through departments.
9. All outpatient sessions should be automatically locked/completed after midnight / next day.
10. Emergency beds and emergency consumables should be billed per day like normal admission.
11. When disposing emergency patient to admission:
    - emergency bed count must end
    - admission bed count must start
    - emergency consumable daily billing must end
    - admission consumable/daily billing must start

---

# 2. Important Conceptual Correction

Visit status should not be used as a replacement for department movement history.

Wrong approach:

```text
visit.status = LABORATORY
visit.status = PHARMACY
visit.status = BILLING
visit.status = XRAY
````

Correct approach:

```text
visit.status = ACTIVE / WAITING_CONSULTATION / CONSULTING / COMPLETED / ADMITTED / EMERGENCY / CANCELLED
```

Then the patient’s actual hospital pathway should be tracked separately using sessions, routes, tasks, requests, invoice items, and visit timeline records.

The Visit Status answers:

```text
What is the global state of this visit?
```

The Visit Pathway / Timeline answers:

```text
Where did the patient go?
What services were requested?
Which departments handled the patient?
What happened chronologically?
```

---

# 3. Patient Cannot Create New OPD Visit While Admitted

If a patient has an active admission that is not discharged, the system must prevent creating another normal OPD visit.

Definition of active admission:

```text
admission.status NOT IN (DISCHARGED, CANCELLED, TRANSFERRED_OUT, DECEASED)
```

or use the project’s existing completed/discharged statuses.

When creating a new OPD visit:

1. Check whether the patient has an active admission.
2. If yes, block visit creation.
3. Show clear message:

```text
This patient is currently admitted and has not yet been discharged. A new OPD visit cannot be created until the admission is completed.
```

Allow exceptions only if the system has an authorized override permission.

Suggested permission:

```text
visits.create_while_admitted
```

If override is used:

* require reason
* log the action
* show warning
* do not silently allow it

This guard should apply to:

* Visit creation page
* API/controller store method
* Any quick-create visit flow
* Emergency-to-OPD transfer if patient is actively admitted unless clinically allowed

---

# 4. Emergency Case Created From Visit Must Create Emergency Session

When an emergency case is created from a visit, the system must also create or link an Emergency Session.

Emergency must be treated like a consultation/clinical session.

Correct flow:

```text
Visit created or selected
↓
Emergency Case created
↓
Emergency Session created
↓
Emergency records attach to that session
↓
Emergency Session appears on Consultation page/session list
↓
Visit Preview includes Emergency Session chronologically
```

Emergency Session must link to:

```text
visit_id
patient_id
emergency_case_id
department_id = Emergency Department
medical_record_id if used
main_doctor_id nullable
primary_nurse_id nullable
status
started_at
created_by
```

Use the existing consultation session / visit consultation route mechanism if possible.

Do not create a completely separate emergency-only session system if the existing session table can support:

```text
session_type = EMERGENCY
emergency_case_id
```

If the current session table is `visit_consultation_routes` or similar, extend it.

Emergency records must link to:

```text
visit_id
patient_id
emergency_case_id
consultation_route_id / clinical_session_id
medical_record_id if used
created_by
updated_by nullable
```

---

# 5. Emergency Session Must Appear on Consultation Page

If a patient went through Emergency, the Consultation page must show the Emergency Session in the session list.

Example:

```text
Consultation Sessions for This Visit

Emergency Department Session
Status: Completed
Triage: RED
Main Doctor: Dr. Kofi
Primary Nurse: Nurse Ama
Contributors: Dr. Mensah, Nurse Yaa
Records: Triage, vitals, notes, medications, investigations, procedures, disposition
[View Session]

OPD Consultation Session
Status: Active
Main Doctor: Dr. Yao
[Continue Consultation]
```

Rules:

* Emergency Session should be viewable from Consultation page.
* If emergency case is still active, authorized emergency staff may edit.
* If emergency case is completed/disposed, show read-only unless user has correction permission.
* Consultation doctors should be able to view emergency history before continuing care.
* Emergency records must preserve creators/contributors.
* Emergency Session must also appear in Visit Preview and patient clinical history.

---

# 6. Rebuild Visit Status Flow Correctly

Re-examine all visit statuses currently used.

Remove or stop using statuses like:

```text
LABORATORY
PHARMACY
BILLING
XRAY
SCAN
PROCEDURE
```

as global visit statuses.

These should be represented as department requests/tasks/timeline events, not visit.status.

Recommended Visit Statuses:

```text
REGISTERED
WAITING_TRIAGE
TRIAGE
WAITING_CONSULTATION
CONSULTING
ACTIVE
EMERGENCY
ADMITTED
COMPLETED
CANCELLED
NO_SHOW
DECEASED
```

Use the project’s existing status names if already established, but clean the meaning.

Suggested meanings:

## REGISTERED

Visit has been created but no clinical workflow started.

## WAITING_TRIAGE

Patient is waiting for triage.

## TRIAGE

Patient is currently undergoing triage.

## WAITING_CONSULTATION

Triage is done or visit is ready for doctor/session.

## CONSULTING

Patient is actively under one or more consultation/clinical sessions.

Important:

A patient should remain CONSULTING even if the doctor sends them to investigation, pharmacy, or procedure and expects them to return.

## ACTIVE

Generic active status if visit is ongoing but not strictly consulting.

## EMERGENCY

Visit is currently under emergency care.

## ADMITTED

Visit has moved into admission/inpatient workflow.

## COMPLETED

Visit is completed/closed.

## CANCELLED

Visit was cancelled.

## DECEASED

Visit ended due to death if applicable.

---

# 7. Patient Pathway / Parcours Must Be Separate From Visit Status

Create or update a patient pathway tracking mechanism.

Recommended table:

```text
visit_pathway_events
- id
- visit_id
- patient_id
- event_type
- department_id nullable
- source_type nullable
- source_id nullable
- status nullable
- title
- description nullable
- started_at nullable
- completed_at nullable
- created_by nullable
- created_at
- updated_at
```

Possible event types:

```text
VISIT_CREATED
TRIAGE_STARTED
TRIAGE_COMPLETED
CONSULTATION_STARTED
CONSULTATION_COMPLETED
SENT_TO_INVESTIGATION
INVESTIGATION_REQUESTED
INVESTIGATION_ACCEPTED
INVESTIGATION_RESULT_READY
INVESTIGATION_VERIFIED
PRESCRIPTION_CREATED
SENT_TO_PHARMACY
PHARMACY_BILLED
PHARMACY_DISPENSED
PROCEDURE_REQUESTED
THEATRE_SCHEDULED
PROCEDURE_COMPLETED
EMERGENCY_STARTED
EMERGENCY_DISPOSED
ADMISSION_STARTED
BED_ASSIGNED
ADMISSION_DISCHARGED
BILLING_ITEM_ADDED
PAYMENT_RECEIVED
VISIT_COMPLETED
```

If existing activity logs or visit timeline exists, extend it instead of creating a duplicate.

The goal is:

```text
Visit status stays clinically meaningful.
Visit pathway records every department/service movement.
Visit Preview uses pathway events to show the real patient journey.
```

---

# 8. Handling Different Patient Routes

The system must support multiple patient routes.

## Route A — Direct Investigation

Example:

```text
Visit created
↓
Investigation service selected/requested
↓
Patient goes to investigation
↓
Result entered/verified
↓
Visit completed or sent to consultation if needed
```

Visit status can remain ACTIVE or COMPLETED depending flow.

Do not force CONSULTING if no consultation session exists.

## Route B — Direct Pharmacy

Example:

```text
Visit created
↓
Drug/product service selected or prescription exists
↓
Pharmacy bills/dispenses
↓
Visit completed
```

Do not set visit.status = PHARMACY.

Use pathway event:

```text
SENT_TO_PHARMACY / PHARMACY_DISPENSED
```

## Route C — Direct Procedure

Example:

```text
Visit created
↓
Procedure requested
↓
Procedure/theatre handles it
↓
Visit completed or admitted if needed
```

Do not set visit.status = PROCEDURE.

## Route D — Consultation With Investigation/Pharmacy Return

Example:

```text
Visit created
↓
Triage
↓
Consulting
↓
Doctor sends patient to investigation
↓
Patient returns to consulting
↓
Doctor reviews result
↓
Doctor prescribes drugs
↓
Patient goes to pharmacy
↓
Patient may return to consulting or complete visit
```

In this case:

```text
visit.status should remain CONSULTING
```

while investigation/pharmacy/procedure activities are tracked as pathway events and request statuses.

---

# 9. Consultation Session Lock After Midnight

All outpatient sessions should be automatically locked/completed after midnight or the next day.

Requirement:

```text
Outpatient sessions from yesterday that are still active should be automatically completed/locked.
```

This applies to:

```text
OPD consultation sessions
outpatient visit sessions
non-admission, non-emergency active clinical sessions
```

Do not automatically close:

```text
active admissions
active emergency cases
active inpatient sessions
theatre cases still in progress
```

Suggested scheduled command:

```bash
php artisan visits:close-outpatient-sessions
```

or:

```bash
php artisan outpatient-sessions:auto-complete
```

Logic:

1. Find outpatient visits/sessions where date < today.
2. Status is still active/consulting/waiting.
3. Not admitted.
4. Not active emergency.
5. Not already completed/cancelled.
6. Mark sessions as completed/locked.
7. Mark visit completed if no active pending workflow remains.
8. Log action.
9. Notify relevant users if needed.

Session fields:

```text
locked_at
locked_by nullable
lock_reason
completed_at
completed_by nullable
```

Lock reason:

```text
Automatically completed after end of outpatient day.
```

This should run automatically through scheduler.

If scheduler is not configured, document it.

---

# 10. Manual Override for Locked Sessions

After outpatient session is auto-locked, users should not freely edit it.

Allow corrections only with permission.

Suggested permission:

```text
consultation.entries.correct_completed
visits.reopen_locked_session
```

If reopening:

* require reason
* log action
* show warning
* preserve audit trail

---

# 11. Emergency Beds Billed Per Day

Emergency beds/bays should be billable per day like normal admission.

When a patient is assigned to an emergency bed/bay:

```text
start emergency bed count
```

When patient leaves emergency bed/bay:

```text
end emergency bed count
```

Billing should calculate based on emergency bed occupancy days or configured billing unit.

Supported billing units:

```text
PER_DAY
PER_HOUR
PER_SHIFT
FLAT
```

For now, implement per-day if that is the existing admission pattern.

Emergency bed billing must use:

```text
BillingService
visit invoice
insurance pricing rules
cash and carry fallback
source_type/source_id
```

Do not create a separate emergency invoice.

Recommended table if missing:

```text
emergency_bed_charges
- id
- emergency_case_id
- visit_id
- patient_id
- bed_id nullable
- emergency_bay_id nullable
- ward_id nullable
- started_at
- ended_at nullable
- billing_unit
- quantity
- service_id nullable
- invoice_item_id nullable
- status
- created_by
- ended_by nullable
- created_at
- updated_at
```

Statuses:

```text
ACTIVE
BILLED
ENDED
CANCELLED
```

If existing bed assignment table can handle charges, extend it instead.

---

# 12. Emergency Consumables Billed Per Day

Emergency consumables or emergency daily care consumable packages should be billable per day like admission if configured.

Examples:

```text
Emergency observation consumables
Emergency nursing care consumables
Emergency bed consumable package
```

Important distinction:

## Direct consumable usage

Example:

```text
1 cannula used
2 syringes used
```

This should be billed/stock-deducted immediately based on actual usage.

## Daily emergency consumable charge

Example:

```text
Emergency care consumables package per day
```

This should be billed per day while patient occupies emergency bed/observation.

Implement both if needed, but do not duplicate billing.

Use existing BillingService and Product/Stock system.

Emergency daily consumables should stop when emergency bed count stops.

---

# 13. Disposing Emergency Patient to Admission

When emergency disposition is ADMITTED:

The system must transition occupancy and daily billing correctly.

Flow:

```text
Emergency case active
↓
Emergency bed/bay assigned
↓
Emergency bed count active
↓
Emergency daily consumables active
↓
Disposition = ADMITTED
↓
End emergency bed count
↓
End emergency daily consumable count
↓
Create/start admission
↓
Assign admission bed
↓
Start admission bed count
↓
Start admission daily consumables if configured
↓
Visit status = ADMITTED
```

Important:

* Do not continue emergency bed billing after admission starts.
* Do not continue emergency consumable daily billing after admission starts.
* Do not start admission bed count before emergency bed count ends unless overlap is intentionally allowed.
* Preserve emergency timeline.
* Preserve same visit/invoice.
* Do not create duplicate visit unless project explicitly requires it.
* Admission should continue the same patient journey.

---

# 14. Emergency Bed Count End Rules

Emergency bed count should end when:

```text
Emergency case disposed to admission
Emergency case discharged
Emergency case transferred to OPD
Emergency case transferred to theatre if emergency bed released
Emergency case referred out
Emergency case marked death/DOA
Emergency bed manually released
```

When ending emergency bed count:

* set ended_at
* calculate quantity/day count
* finalize invoice item if needed
* release bed/bay status to AVAILABLE or CLEANING
* log action

---

# 15. Admission Bed Count Start Rules

When emergency disposes to admission:

* use existing admission creation workflow
* assign admission ward/bed
* create admission bed assignment
* start admission bed count
* start daily admission billing if existing system supports it
* do not duplicate emergency bed charge

If no admission bed is assigned immediately:

* admission can be created as waiting bed
* emergency bed may remain active until physical transfer
* system must clearly show patient still occupying emergency bed
* emergency bed billing continues until actual release

This is important.

Add two possible workflows:

## Immediate transfer to admission bed

Emergency bed ends immediately.

Admission bed starts immediately.

## Admission accepted but waiting bed

Emergency disposition may be ADMISSION_PENDING_BED.

Emergency bed remains active until bed transfer is completed.

Use whichever matches existing admission workflow, but support the logic safely.

---

# 16. Visit Preview Must Show Pathway

Visit Preview must now show the real patient parcours.

Example:

```text
08:00 Visit created
08:05 Triage completed
08:10 Consultation started
08:25 Investigation requested: FBC, Malaria RDT
08:50 Lab result verified
09:00 Consultation continued
09:15 Prescription created
09:20 Pharmacy billed selected drugs
09:35 Pharmacy dispensed drugs
09:45 Visit completed
```

For Emergency to Admission:

```text
10:00 Emergency case created
10:05 RED triage calculated
10:08 Emergency bed assigned: Resus Bay 1
10:15 Emergency medication administered
10:40 Emergency investigation requested
11:30 Decision: Admit patient
11:45 Emergency bed count ended
11:50 Admission created
12:00 Admission bed assigned
12:00 Admission bed count started
```

This is more accurate than changing visit.status to LAB/PHARMACY.

---

# 17. Billing Integration

All emergency/admission bed and consumable charges must use the existing BillingService.

Rules:

```text
One visit = one invoice.
Emergency bed charge goes to visit invoice.
Emergency consumable charge goes to visit invoice.
Admission bed charge goes to visit invoice.
Admission consumable charge goes to visit invoice.
Insurance pricing applies.
Cash and Carry fallback applies.
Prevent duplicate charges using source_type/source_id.
```

Source examples:

```text
source_type = emergency_bed_charge
source_id = emergency_bed_charge.id

source_type = emergency_daily_consumable_charge
source_id = charge.id

source_type = admission_bed_charge
source_id = admission_bed_charge.id
```

Do not create separate emergency/admission invoice.

---

# 18. Services to Create or Update

Create/update services as needed:

```text
VisitStatusService
VisitPathwayService
VisitGuardService
OutpatientSessionAutoCloseService
EmergencySessionService
EmergencyBedBillingService
EmergencyConsumableBillingService
EmergencyDispositionService
AdmissionBedBillingService
AdmissionTransferService
BillingService
VisitPreviewService
ActivityLogService
NotificationService
```

Do not duplicate existing services if already available.

---

# 19. VisitGuardService

Create or update:

```text
VisitGuardService
```

Responsibilities:

```text
prevent OPD visit creation while active admission exists
prevent new records under merged patient
validate visit status transitions
validate outpatient session locking rules
```

Suggested method:

```php
public function assertCanCreateVisit(Patient $patient, ?User $user = null): void
```

This should check:

* patient is not merged
* patient is not actively admitted
* other business rules

---

# 20. VisitStatusService

Create/update service to centralize status transitions.

Responsibilities:

```text
set visit waiting triage
set visit triage
set visit waiting consultation
set visit consulting
set visit emergency
set visit admitted
set visit completed
set visit cancelled
```

Do not allow controllers to randomly set visit.status to laboratory/pharmacy/billing.

Deprecate or remove usage of old statuses.

---

# 21. VisitPathwayService

Create/update service to record patient parcours.

Responsibilities:

```text
record pathway event
record department movement
record request creation
record completion
build pathway timeline for Visit Preview
```

Example method:

```php
record(Visit $visit, string $eventType, array $data = []): VisitPathwayEvent
```

Use this whenever:

* investigation requested
* pharmacy billed/dispensed
* procedure requested/completed
* emergency started/disposed
* admission started/discharged
* billing item added
* payment received

---

# 22. Scheduler Command

Create scheduled command:

```bash
php artisan outpatient-sessions:auto-complete
```

or use existing naming convention.

The command should:

* find outpatient sessions from previous days still active
* complete/lock them
* update visit status if appropriate
* log all changes
* optionally notify responsible staff/admin

Add scheduler entry.

Document in code/report that server cron must run Laravel scheduler.

---

# 23. UI Changes

## Visit Creation UI

If patient has active admission, show blocking warning.

Example:

```text
This patient is currently admitted in Ward A / Bed 3 since 26 May 2026.
You cannot create a new OPD visit until the patient is discharged.
```

Show link:

```text
Open Active Admission
```

If user has override permission, show override option with reason.

## Emergency Creation UI

When creating emergency case from visit, ensure UI shows:

```text
Emergency Session will be created for this visit.
```

After creation, redirect to Emergency Case page and show session.

## Consultation Page

Show Emergency Session in session list.

## Visit Preview

Show pathway timeline/parcours.

## Emergency Disposition UI

When disposing to Admission, show bed count transition:

```text
Emergency bed billing will end.
Admission bed billing will start when admission bed is assigned.
```

If admission bed not assigned:

```text
Patient remains in Emergency bed until admission bed transfer is completed.
Emergency bed billing continues.
```

---

# 24. Data / Migration Updates

Add fields where missing.

Possible visit fields:

```text
status
completed_at
completed_by
locked_at
locked_by
lock_reason
```

Possible session fields:

```text
session_type
emergency_case_id nullable
locked_at
locked_by
lock_reason
completed_at
completed_by
```

Possible pathway table:

```text
visit_pathway_events
```

Possible emergency/admission charge tracking tables if missing:

```text
emergency_bed_charges
emergency_daily_consumable_charges
admission_bed_charges
admission_daily_consumable_charges
```

Use existing tables if similar structures already exist.

---

# 25. Validation Rules

Visit creation:

```text
patient_id required
patient must not be merged
patient must not have active admission unless override permission and reason
```

Emergency case creation from visit:

```text
visit_id required
patient_id required
emergency case must create/link emergency session
```

Visit status transition:

```text
cannot set visit.status to LABORATORY/PHARMACY/BILLING
cannot complete visit with active admission/emergency unless allowed
cannot admit without admission workflow
```

Outpatient auto-lock:

```text
only outpatient sessions
not active emergency
not active admission
session date before today
```

Emergency-to-admission disposition:

```text
emergency_case_id required
admission creation required
emergency bed count end required when bed released
admission bed count start required when admission bed assigned
```

---

# 26. Tests Required

Add or update tests.

## Visit Creation Guard

1. Cannot create OPD visit when patient has active admission.
2. Can create OPD visit after admission is discharged.
3. Override requires permission.
4. Override requires reason.
5. Attempt is logged.

## Emergency Session

6. Creating emergency case from visit creates emergency session.
7. Emergency session links to visit/patient/emergency case.
8. Emergency session appears on Consultation page.
9. Emergency records link to emergency session.
10. Emergency session appears in Visit Preview.

## Visit Status Flow

11. Visit status is not set to LABORATORY.
12. Visit status is not set to PHARMACY.
13. Visit status is not set to BILLING.
14. Patient can go to investigation while visit remains CONSULTING.
15. Patient can go to pharmacy while visit remains CONSULTING.
16. Patient can go to procedure while visit remains CONSULTING.
17. Direct investigation route records pathway event.
18. Direct pharmacy route records pathway event.
19. Direct procedure route records pathway event.

## Pathway / Preview

20. Investigation request creates pathway event.
21. Pharmacy billing creates pathway event.
22. Pharmacy dispensing creates pathway event.
23. Procedure request creates pathway event.
24. Emergency disposition creates pathway event.
25. Visit Preview shows chronological pathway.

## Outpatient Auto-Lock

26. Previous-day outpatient sessions auto-complete.
27. Active admission sessions are not auto-closed.
28. Active emergency cases are not auto-closed.
29. Locked outpatient sessions cannot be edited without correction permission.
30. Auto-lock action is logged.

## Emergency Bed / Consumable Billing

31. Emergency bed assignment starts bed count.
32. Emergency bed release ends bed count.
33. Emergency bed charge is billed per day.
34. Emergency daily consumable charge is billed per day if configured.
35. Duplicate emergency bed charges are prevented.
36. Billing uses visit invoice.

## Emergency to Admission

37. Disposing to admission ends emergency bed count when bed released.
38. Disposing to admission starts admission bed count when admission bed assigned.
39. If admission bed is not assigned, emergency bed remains active.
40. Emergency consumable daily billing ends when emergency bed/care ends.
41. Admission daily billing starts when admission bed/care starts.
42. Same visit/invoice is preserved.

---

# 27. Reports / Documentation

Create or update an implementation report:

```text
docs/VISIT_STATUS_AND_PATIENT_PATHWAY_REPORT.md
```

Include:

* previous problems found
* old statuses removed/deprecated
* new visit status flow
* pathway/timeline mechanism
* emergency session creation fix
* admission active visit guard
* outpatient auto-lock logic
* emergency-to-admission bed billing transition
* files modified
* remaining TODOs

---

# 28. Deliverables

Provide:

1. Gap analysis of current Visit Status Flow.
2. Guard preventing new OPD visit during active admission.
3. Emergency case creation now creates Emergency Session.
4. Emergency Session appears on Consultation page.
5. Visit status flow cleaned.
6. Laboratory/Pharmacy/Billing removed from main visit statuses.
7. Patient pathway/parcours tracking implemented.
8. Visit Preview updated to show pathway.
9. Outpatient sessions auto-complete/lock after midnight.
10. Emergency bed billing per day.
11. Emergency consumables per day where configured.
12. Emergency-to-admission transition handles bed count correctly.
13. Admission bed count starts correctly.
14. Tests or verification notes.
15. Documentation/report file.
16. Files modified.
17. Remaining TODOs.

---

# 29. Important Rules

Do not create a new OPD visit for actively admitted patients.

Do not create emergency case without emergency session.

Do not isolate Emergency from consultation/session history.

Do not use LABORATORY, PHARMACY, BILLING as main visit statuses.

Do not change visit status away from CONSULTING just because patient goes to investigation/pharmacy/procedure.

Do not auto-close active admission or active emergency cases.

Do not continue emergency bed billing after patient is physically transferred to admission bed.

Do not start admission bed billing before admission bed assignment unless configured.

Do not duplicate emergency/admission bed charges.

Do not create separate invoices.

Do not break existing consultation, emergency, admission, billing, pharmacy, investigation, procedure, stock, MAR, visit preview, or claims workflows.

Now inspect the current UHMS implementation and correct the Visit Status Flow, Emergency Session creation, active admission visit guard, outpatient auto-lock, and emergency-to-admission billing/bed transition logic as described above.

```
```
