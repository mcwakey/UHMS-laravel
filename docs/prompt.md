# UHMS Inpatient Department Workspace — Normal Admission and `/inpatient/*` Route Architecture

Implement a dedicated **Inpatient Department Workspace** for UHMS.

This workspace is for clinical and operational staff managing normal hospital admissions from admission request and bed assignment through inpatient nursing care, clinical rounds, treatment, medication administration, investigations, procedures, monitoring, transfer, discharge, and readmission.

This implementation should follow the same department-aware workspace architecture already established for:

* Doctors and consultation departments
* Records
* Nursing OPD
* Emergency

The objective is that when a logged-in user’s active department has:

```php
DepartmentType::INPATIENT
```

the user should experience UHMS as a dedicated Inpatient application with:

* An Inpatient-specific sidebar menu
* An Inpatient ward dashboard
* Inpatient-specific breadcrumbs
* Inpatient-specific route names
* Consistent `/inpatient/*` URLs
* Workspace-aware links and redirects
* Admission and ward worklists
* Bed and ward visibility
* Nursing session and clinical-round workflows
* Medication, treatment, investigation, and procedure coordination
* Discharge-readiness and clearance workflows
* Transfer and readmission support
* Permission-controlled menu visibility
* Active-department scoping
* Patient privacy and clinical-safety enforcement
* Reuse of existing admission, patient, visit, consultation, nursing, billing, journey, and audit logic

Do not duplicate core admission, ward, patient, consultation, nursing, medication, treatment, investigation, discharge, or billing business logic merely to create the Inpatient workspace.

---

# 1. Core Functional Requirement

When the active department type is `inpatient`, all supported browser pages used for normal admitted-patient care must appear under the `/inpatient` URL prefix.

Examples:

```text
/inpatient
/inpatient/dashboard

/inpatient/admissions
/inpatient/admissions/pending
/inpatient/admissions/active
/inpatient/admissions/discharged
/inpatient/admissions/{admission}

/inpatient/wards
/inpatient/wards/{ward}
/inpatient/beds
/inpatient/beds/availability

/inpatient/patients
/inpatient/patients/{patient}

/inpatient/visits
/inpatient/visits/{visit}

/inpatient/rounds
/inpatient/rounds/{admission}

/inpatient/sessions
/inpatient/sessions/{session}

/inpatient/vitals
/inpatient/assessments
/inpatient/care-plans
/inpatient/tasks
/inpatient/medications
/inpatient/treatments
/inpatient/procedures
/inpatient/investigations
/inpatient/observations
/inpatient/intake-output

/inpatient/handoffs
/inpatient/transfers
/inpatient/discharges
/inpatient/readmissions
/inpatient/reports
```

An Inpatient user should not enter through:

```text
/inpatient/admissions/{admission}
```

and later be redirected to generic URLs such as:

```text
/admissions/{admission}
/patients/{patient}
/visits/{visit}
/consultations/{consultation}
/nursing-sessions/{session}
/discharges/{discharge}
```

All browser navigation, forms, dashboard links, ward lists, patient cards, session actions, breadcrumbs, notifications, worklists, and redirects must preserve the Inpatient workspace context.

---

# 2. Inpatient Workspace Scope

The Inpatient workspace is responsible for normal admission and ward-care workflows, including:

1. Admission requests
2. Admission acceptance
3. Ward selection
4. Bed allocation
5. Active admissions
6. Inpatient clinical sessions
7. Nursing assessments
8. Nursing care plans
9. Vital-sign monitoring
10. Intake and output monitoring
11. Clinical rounds
12. Doctor reviews
13. Medication administration
14. Treatments
15. Procedures
16. Investigation requests and follow-up
17. Nursing tasks
18. Patient observation
19. Diet and nutrition instructions where supported
20. Mobility and fall-risk monitoring
21. Pressure-injury monitoring where supported
22. Handoffs between shifts and departments
23. Internal ward transfers
24. External transfers
25. Discharge readiness
26. Departmental discharge clearances
27. Final discharge
28. Same-admission session reopening
29. Readmission
30. Inpatient reports and operational analytics

This workspace is for **normal inpatient admissions**.

It must remain distinct from:

* Emergency care
* Routine OPD care
* Maternity-specific admission
* Theatre operations
* Intensive-care workflows unless the current system models them through normal inpatient departments
* Mortuary workflows

Shared services and components may be reused, but the Inpatient workspace must present a ward-oriented and admission-oriented experience.

---

# Phase 1 — Inspect the Existing Admission Architecture

Before implementing, inspect the current codebase and identify:

1. How department-specific menus are selected.
2. How the Records, Nursing, Emergency, and doctor workspaces are registered.
3. How active department context is resolved.
4. How multi-department users switch active departments.
5. Existing routes, controllers, models, services, policies, and views for:

   * admissions
   * admission requests
   * wards
   * beds
   * bed allocation
   * patients
   * visits
   * consultations
   * inpatient sessions
   * nursing sessions
   * clinical rounds
   * nursing assessments
   * care plans
   * vital signs
   * intake and output
   * medication administration
   * treatments
   * procedures
   * investigations
   * observation
   * nursing tasks
   * handoffs
   * transfers
   * discharge clearances
   * discharge
   * readmission
6. Existing admission statuses.
7. Existing bed and ward availability logic.
8. Existing inpatient consultation-completion rules.
9. Existing session reopen rules.
10. Existing discharge and readmission services.
11. Existing billing behaviour for active admissions.
12. Existing Journey Intelligence stages for admitted patients.
13. Existing patient privacy protections.
14. Existing clinical-safety services.
15. Existing activity-log events.
16. Shared views that hardcode generic routes such as:

```php
route('admissions.show', $admission)
route('patients.show', $patient)
route('visits.show', $visit)
route('consultations.show', $consultation)
route('discharges.show', $discharge)
```

Do not create a competing admission, menu, dashboard, session, or routing architecture where one already exists.

Extend the existing:

* Department dashboard registry
* Department menu profile service
* Active department context
* Workspace route resolver
* Workspace redirect resolver
* Admission services
* Bed-allocation services
* Consultation workflow services
* Nursing workflow services
* Journey Intelligence services
* Billing and receivable services
* Authorization policies
* Patient privacy services
* Activity logging

---

# Phase 2 — Inpatient Workspace Route Group

Create a dedicated Inpatient route group.

Use a structure equivalent to:

```php
Route::prefix('inpatient')
    ->name('inpatient.')
    ->middleware([
        'auth',
        'verified',
        'department.context',
        'department.type:inpatient',
    ])
    ->group(function () {
        // Inpatient workspace routes
    });
```

Use the project’s actual middleware names and active-department authorization architecture.

Required route names should include, where the corresponding functionality already exists:

```text
inpatient.dashboard

inpatient.admissions.index
inpatient.admissions.pending
inpatient.admissions.active
inpatient.admissions.discharged
inpatient.admissions.show
inpatient.admissions.accept
inpatient.admissions.assign_bed

inpatient.wards.index
inpatient.wards.show

inpatient.beds.index
inpatient.beds.availability
inpatient.beds.assign
inpatient.beds.release

inpatient.patients.index
inpatient.patients.show

inpatient.visits.index
inpatient.visits.show

inpatient.rounds.index
inpatient.rounds.show
inpatient.rounds.create
inpatient.rounds.store
inpatient.rounds.update

inpatient.sessions.index
inpatient.sessions.show
inpatient.sessions.create
inpatient.sessions.store
inpatient.sessions.reopen

inpatient.vitals.index
inpatient.vitals.show
inpatient.vitals.store
inpatient.vitals.update

inpatient.assessments.index
inpatient.assessments.show
inpatient.assessments.store
inpatient.assessments.update

inpatient.care_plans.index
inpatient.care_plans.show
inpatient.care_plans.store
inpatient.care_plans.update

inpatient.tasks.index
inpatient.tasks.show
inpatient.tasks.update

inpatient.medications.index
inpatient.medications.show
inpatient.medications.administer

inpatient.treatments.index
inpatient.treatments.show
inpatient.treatments.execute

inpatient.procedures.index
inpatient.procedures.show

inpatient.investigations.index
inpatient.investigations.show

inpatient.observations.index
inpatient.observations.show

inpatient.intake_output.index
inpatient.intake_output.show
inpatient.intake_output.store

inpatient.handoffs.index
inpatient.handoffs.show
inpatient.handoffs.update

inpatient.transfers.index
inpatient.transfers.create
inpatient.transfers.store
inpatient.transfers.show

inpatient.discharges.index
inpatient.discharges.readiness
inpatient.discharges.create
inpatient.discharges.store
inpatient.discharges.show

inpatient.readmissions.create
inpatient.readmissions.store

inpatient.reports.index
```

Only register routes for real functionality.

Do not add empty placeholder pages merely to populate the menu.

---

# Phase 3 — Inpatient Ward Dashboard

Create or complete a dedicated Inpatient dashboard.

The canonical destination should be:

```text
/inpatient
```

or:

```text
/inpatient/dashboard
```

Choose one canonical route and redirect the other to it.

The department dashboard resolver should map:

```php
DepartmentType::INPATIENT => 'inpatient.dashboard'
```

The dashboard should function as an inpatient ward command board.

Recommended metrics and widgets include, where reliable data exists:

* Active admissions
* Admissions today
* Pending admission requests
* Patients awaiting bed allocation
* Occupied beds
* Available beds
* Bed occupancy rate
* Patients without assigned beds
* Patients requiring clinical review
* Patients with overdue vital signs
* Patients with incomplete nursing assessments
* Patients with overdue nursing tasks
* Medications due
* Medications overdue
* Treatments pending
* Procedures pending
* Investigations pending
* Critical investigation results awaiting review
* Patients with discharge planned today
* Patients awaiting discharge clearances
* Patients ready for discharge
* Patients awaiting transfer
* Handoffs awaiting acknowledgement
* High-risk patients
* Long-stay patients
* Readmissions today
* Discharges today

Each dashboard metric must:

* Respect permissions
* Respect the active department
* Scope data to normal inpatient admissions
* Avoid maternity and Emergency cases unless explicitly assigned to the Inpatient department
* Avoid leaking protected patient information
* Link to `/inpatient/*` routes
* Use safe empty states
* Avoid expensive unbounded queries
* Reuse Journey Intelligence and department metrics where applicable

Do not introduce misleading metrics that cannot be accurately calculated.

---

# Phase 4 — Inpatient-Specific Menu Profile

Extend the existing department menu profile or menu registry so that:

```php
DepartmentType::INPATIENT
```

receives a dedicated Inpatient menu.

Recommended menu structure:

## Inpatient Command

* Dashboard
* Active Admissions
* Pending Admissions
* Discharged Today
* Long-Stay Patients
* High-Risk Patients

## Wards and Beds

* Ward Overview
* Bed Availability
* Bed Occupancy
* Patients Without Beds
* Bed Transfers

## Patient Care

* Admitted Patients
* Clinical Rounds
* Inpatient Sessions
* Nursing Assessments
* Care Plans
* Vital Signs
* Intake and Output
* Patient Observations
* Nursing Notes

## Medication and Services

* Medication Administration
* Treatments
* Procedures
* Pending Investigations
* Laboratory Follow-Up
* Radiology Follow-Up
* Pending Services

## Nursing Work

* Nursing Tasks
* Tasks Due
* Overdue Tasks
* Assigned Tasks
* Unassigned Tasks

## Coordination

* Shift Handoffs
* Department Handoffs
* Escalations
* Transfers
* Admission Requests

## Discharge

* Discharge Readiness
* Pending Clearances
* Planned Discharges
* Completed Discharges
* Readmissions

## Patient Access

* Patient Search
* Patient Profiles
* Admission History
* Visit History

## Inpatient Reports

* Admission Report
* Bed Occupancy Report
* Ward Census Report
* Length-of-Stay Report
* Nursing Activity Report
* Medication Administration Report
* Treatment Report
* Investigation Follow-Up Report
* Discharge Report
* Readmission Report
* Mortality Report where authorized

## General

* Notifications
* My Profile
* Switch Department

Only show a menu item when:

1. The feature exists.
2. The relevant module is enabled.
3. The user possesses the required permission.
4. The active department permits access.
5. The functionality belongs to normal inpatient care.

Permissions remain authoritative.

Do not expose actions solely because the user belongs to an Inpatient department.

---

# Phase 5 — Admission Worklists

Create or adapt Inpatient admission worklists under:

```text
/inpatient/admissions
```

Recommended admission categories include:

```text
pending_acceptance
awaiting_bed
admitted
active
clinical_review_due
nursing_action_due
transfer_pending
discharge_planned
clearance_pending
ready_for_discharge
discharged_today
readmitted
```

Use existing admission statuses, bed-allocation state, journey stage, clinical-task state, and discharge-readiness services.

Do not introduce duplicate statuses where the worklist state can be derived by a resolver.

Each worklist item should display only authorized information, such as:

* Patient identifier
* Patient name according to privacy rules
* Admission number
* Visit number
* Admission date and time
* Admission reason
* Admitting clinician
* Ward
* Bed
* Length of stay
* Current clinical stage
* Responsible doctor
* Assigned nurse where applicable
* Pending tasks
* Pending medications
* Pending investigations
* Discharge-readiness state
* Next recommended action

Support filters such as:

* Ward
* Bed status
* Admission date
* Length of stay
* Responsible clinician
* Assigned nurse
* Admission status
* Clinical-risk level
* Discharge-readiness state
* Pending task type
* Payment or billing state where operationally relevant

Use pagination and efficient queries.

---

# Phase 6 — Inpatient Admission Workspace

Create or adapt a dedicated Inpatient admission workspace.

Recommended route:

```text
/inpatient/admissions/{admission}
```

The admission workspace should provide a coordinated view of the entire admission.

Recommended sections:

1. Patient identity strip
2. Admission details
3. Ward and bed
4. Admission date and length of stay
5. Responsible clinician
6. Assigned nursing team
7. Diagnosis and problem list
8. Allergies and clinical alerts
9. Current admission status
10. Current Journey Intelligence stage
11. Vital-sign timeline
12. Nursing assessment
13. Care plan
14. Clinical rounds
15. Active clinical sessions
16. Medication administration
17. Treatments
18. Procedures
19. Investigations and results
20. Intake and output
21. Patient observations
22. Nursing tasks
23. Handoffs
24. Transfer history
25. Billing and service-access state
26. Discharge readiness
27. Clearances
28. Activity timeline
29. Authorized quick actions

Do not duplicate underlying module implementations.

The Inpatient admission page should coordinate existing workflows through one ward-oriented interface.

---

# Phase 7 — Ward and Bed Management Integration

Expose ward and bed information within the Inpatient workspace.

Examples:

```text
/inpatient/wards
/inpatient/wards/{ward}
/inpatient/beds
/inpatient/beds/availability
```

Reuse existing:

* Ward models
* Bed models
* Bed-allocation services
* Admission services
* Transfer services
* Occupancy calculations
* Activity logging
* Authorization policies

The ward overview may display:

* Ward capacity
* Occupied beds
* Available beds
* Reserved beds
* Out-of-service beds
* Occupancy percentage
* Patients awaiting beds
* Planned discharges
* Expected transfers

Authorized users may:

* Assign a bed
* Transfer a patient to another bed
* Transfer a patient to another ward
* Release a bed after discharge
* Mark a bed unavailable where supported
* View bed history

Do not allow a bed to be assigned concurrently to multiple active admissions.

Bed assignment and release must be transactional and audited.

Do not automatically release a bed before the configured discharge or transfer workflow considers the patient moved.

---

# Phase 8 — Inpatient Clinical Sessions

Expose inpatient clinical sessions under:

```text
/inpatient/sessions
```

A clinical session should represent an authorized episode of inpatient clinical work during an active admission.

The session workflow must respect the established Inpatient rules:

1. An active admission must remain workable across multiple calendar days.
2. Do not apply the OPD next-day locking rule to active inpatient admissions.
3. An inpatient admission must remain open for new authorized clinical work until the patient is discharged.
4. Completing one session must not block work in another active session.
5. Only the completed session itself should become read-only unless reopened.
6. Any active session must continue allowing authorized users to add new clinical items.
7. A completed session may be reopened by an authorized user.
8. Reopening must require a reason where configured.
9. Reopening must be audited.
10. Discharge must not destroy or detach existing clinical sessions.
11. Authorized users should be able to reopen a session after discharge where the established workflow permits clinical correction or completion.
12. Reopening a session must not silently reactivate the entire admission unless that is explicitly requested.
13. Readmission must use the formal readmission workflow.
14. Manual consultation completion must not be reintroduced where consultation completion is already automatic.

Session states may include:

```text
active
completed
reopened
cancelled
locked
```

Use the current consultation and session architecture.

Do not create a parallel inpatient consultation implementation.

---

# Phase 9 — Clinical Rounds

Expose clinical rounds under:

```text
/inpatient/rounds
```

A clinical round may include:

* Date and time
* Responsible clinician
* Participating staff
* Current condition
* New complaints
* Examination findings
* Diagnosis review
* Medication review
* Investigation review
* Treatment response
* Updated plan
* Procedures required
* Discharge planning
* Follow-up interval
* Clinical summary

Rounds should be admission-scoped and linked to the active clinical session where applicable.

Authorized users should be able to:

* Start a round
* Record findings
* Update the treatment plan
* Request investigations
* Request procedures
* Update medication
* Create tasks
* Mark review due
* Complete the round

All redirects and links must remain inside `/inpatient/*`.

---

# Phase 10 — Nursing Assessment and Care Plans

Expose inpatient nursing assessments and care plans under:

```text
/inpatient/assessments
/inpatient/care-plans
```

The nursing assessment may include existing fields such as:

* General condition
* Consciousness
* Mobility
* Fall risk
* Pressure-injury risk
* Pain
* Nutrition risk
* Hydration
* Elimination
* Skin condition
* Infection risk
* Mental state
* Communication needs
* Personal-care needs
* Immediate nursing concerns
* Escalation requirement

The care plan may include:

* Nursing problem
* Goal
* Planned intervention
* Frequency
* Responsible nurse
* Start date
* Review date
* Status
* Outcome
* Completion note

Assessments and care plans must be:

* Admission-scoped
* Time-stamped
* Linked to the responsible user
* Permission-controlled
* Audited
* Visible to authorized clinical staff

Do not introduce duplicate care-plan models where existing nursing-task or care-plan infrastructure already exists.

---

# Phase 11 — Vital Signs and Ongoing Monitoring

Expose inpatient vital signs under:

```text
/inpatient/vitals
```

Support:

* Initial inpatient vital signs
* Repeated observations
* Time-stamped entries
* Responsible staff
* Abnormal-value indicators
* Critical-value alerts
* Trend display
* Repeat-measurement schedules
* Monitoring-frequency requirements
* Escalation
* Correction audit

The workspace should distinguish:

```text
missing
complete
abnormal
critical
repeat_due
monitoring_active
overdue
```

Do not assume that one completed vital-sign entry satisfies the entire admission.

Vital monitoring must support repeated observations over the admission duration.

Critical values must use the existing clinical-alert and escalation architecture.

---

# Phase 12 — Intake and Output Monitoring

Where supported, expose intake and output monitoring under:

```text
/inpatient/intake-output
```

Support records such as:

* Oral fluids
* IV fluids
* Enteral feeds
* Urine output
* Drain output
* Vomiting
* Stool
* Blood loss
* Other measurable output
* Running fluid balance
* Shift totals
* Daily totals

Each entry should include:

* Date and time
* Quantity
* Unit
* Route or source
* Responsible staff
* Notes

Use centralized unit and calculation handling.

Do not duplicate medication-infusion or treatment records where those already generate intake records.

---

# Phase 13 — Nursing Tasks

Expose inpatient nursing tasks under:

```text
/inpatient/tasks
```

Reuse existing clinical-task and Journey Intelligence infrastructure.

Task categories may include:

* Repeat vital signs
* Medication administration
* Patient repositioning
* Wound care
* Intake and output recording
* Sample collection
* Treatment administration
* Preparation for procedure
* Mobility assistance
* Clinical review request
* Discharge preparation
* Handoff
* Transfer preparation

Task states may include:

```text
pending
assigned
acknowledged
in_progress
completed
cancelled
overdue
escalated
```

Where frequency-based task generation already exists, repeated occurrences must be displayed and completed correctly.

Authorized users may:

* Claim
* Assign
* Acknowledge
* Start
* Complete
* Add a completion note
* Escalate
* Reassign
* View task history

All task links and redirects must remain inside `/inpatient/*`.

---

# Phase 14 — Medication Administration

Expose inpatient medication administration under:

```text
/inpatient/medications
```

Display:

* Medication
* Dose
* Route
* Frequency
* Scheduled time
* Prescribing clinician
* Dispensing or availability state
* Administration status
* Last administered time
* Next due time
* Allergy warning
* Duplicate-medication warning
* Dose warning
* Omitted or refused reason

Authorized users may record:

```text
administered
delayed
withheld
refused
not_available
cancelled
missed
```

Do not bypass:

* Prescription safety
* Pharmacy dispensing
* Stock control
* Payment policy
* Allergy checks
* Duplicate-medication checks
* Audit logging

Medication administration must not falsely imply that medication was dispensed, billed, or available where those steps are incomplete.

Any urgent override must be explicit, permission-controlled, reasoned, and audited.

---

# Phase 15 — Treatments and Procedures

Expose inpatient treatment and procedure worklists under:

```text
/inpatient/treatments
/inpatient/procedures
```

Display:

* Patient
* Admission
* Ward and bed
* Ordered treatment or procedure
* Requesting clinician
* Priority
* Scheduled time
* Assigned staff
* Required consumables
* Payment or authorization state where relevant
* Safety warnings
* Status
* Completion time

Reuse existing:

* Treatment services
* Procedure services
* Theatre services where appropriate
* Stock and consumable logic
* Billing mapping
* Payment-gate policy
* Activity logging

Do not allow nursing or ward staff to perform doctor-only ordering actions unless explicitly permitted by the existing permission model.

---

# Phase 16 — Investigations and Result Follow-Up

Expose inpatient investigation tracking under:

```text
/inpatient/investigations
```

Support:

* Requested
* Awaiting sample collection
* Sample collected
* In progress
* Result available
* Critical result
* Result reviewed
* Cancelled

The worklist should help ward staff identify:

* Pending investigations
* Overdue sample collection
* Results awaiting review
* Critical results
* Investigations blocking discharge
* Investigations requiring repeat collection

Reuse existing laboratory, radiology, billing, consultation, and journey services.

Do not create duplicate investigation records for the Inpatient workspace.

---

# Phase 17 — Patient Observation and Risk Monitoring

Expose patient observation under:

```text
/inpatient/observations
```

Observation may include:

* General clinical observations
* Neurological monitoring
* Pain monitoring
* Wound monitoring
* Mobility monitoring
* Fall-risk monitoring
* Pressure-injury monitoring
* Treatment response
* Fluid-balance concerns
* Behavioural observation where supported
* Escalation status

High-risk patients should be visible on:

* The dashboard
* Ward overview
* Active admissions list
* Nursing worklists
* Handoffs

Do not duplicate structured vital signs or medication records inside free-text observations.

---

# Phase 18 — Shift and Department Handoffs

Expose inpatient handoffs under:

```text
/inpatient/handoffs
```

Reuse the existing Journey Intelligence handoff and coordination services.

Support:

* Shift handoff
* Nurse-to-nurse handoff
* Doctor-to-doctor handoff
* Department handoff
* Transfer handoff
* Discharge handoff
* Outstanding-action handoff

The worklist should display:

* Patient and admission context
* Sending user or department
* Receiving user or department
* Expected action
* Priority
* Due time
* SLA status
* Acknowledgement state
* Resolution state

Authorized users may:

* Claim
* Assign
* Acknowledge
* Resolve
* Escalate
* Reassign

All handoff links should use `/inpatient/*` routes where an Inpatient equivalent exists.

---

# Phase 19 — Inpatient Transfer Workflow

Expose inpatient transfers under:

```text
/inpatient/transfers
```

Support:

* Bed-to-bed transfer
* Ward-to-ward transfer
* Department transfer
* Internal specialty transfer
* External-facility transfer where supported

A transfer may include:

* Source ward and bed
* Destination ward and bed
* Reason
* Priority
* Responsible clinician
* Accepting department
* Acceptance status
* Handoff
* Transport status
* Transfer date and time

Reuse existing bed, ward, admission, ambulance, referral, and handoff services where applicable.

A transfer must not create a duplicate active admission unless the established workflow explicitly requires it.

Bed release and destination assignment must be transactional.

---

# Phase 20 — Discharge Readiness

Create or extend a centralized inpatient discharge-readiness process.

Recommended readiness areas include:

* Clinical stability
* Final diagnosis
* Clinical summary
* Medication reconciliation
* Discharge prescription
* Investigation review
* Pending-result plan
* Treatment completion
* Procedure follow-up
* Nursing clearance
* Pharmacy clearance
* Finance or billing clearance
* Insurance or claims clearance
* Ward clearance
* Follow-up appointment
* Referral instructions
* Patient education
* Warning signs
* Responsible clinician approval

Reuse the existing admission discharge-clearance architecture.

Do not create duplicate clearance tables or services where existing ones already exist.

The readiness screen should clearly distinguish:

```text
not_started
in_progress
blocked
ready
overridden
completed
```

Overrides must be:

* Permission-controlled
* Reasoned
* Audited
* Scoped to the admission and clearance type

---

# Phase 21 — Inpatient Discharge

Expose inpatient discharge under:

```text
/inpatient/discharges
```

The discharge process should:

1. Validate discharge readiness.
2. Record final diagnosis.
3. Record discharge summary.
4. Record discharge destination.
5. Record medication and follow-up instructions.
6. Resolve or safely carry forward outstanding actions.
7. Complete required clearances.
8. Update the admission status.
9. Update the visit status.
10. Release the bed at the correct workflow point.
11. Update Journey Intelligence state.
12. Preserve audit history.
13. Preserve billing and receivable history.
14. Preserve all clinical sessions and records.

Do not silently cancel pending investigations, treatments, medications, or tasks.

Outstanding items should be:

* Completed
* Cancelled with reason
* Carried forward
* Marked for outpatient follow-up
* Explicitly overridden

according to the existing workflow.

---

# Phase 22 — Session Reopening After Discharge

Preserve the established session-reopening behaviour for inpatient cases.

After discharge:

1. Existing sessions must remain visible.
2. Authorized users may reopen a completed session where permitted.
3. Reopening must require the appropriate permission.
4. Reopening must require a reason where configured.
5. Reopening must be audited.
6. Adding a clinical item after discharge must not silently remove the discharge.
7. Reopening one session must not automatically reopen every session.
8. Reopening a session must not silently reactivate bed occupancy.
9. Reopening a session must not silently restart billing periods.
10. Clinical corrections and late documentation must remain distinguishable from readmission.

The UI should clearly indicate:

```text
discharged
session_reopened_after_discharge
late_entry
clinical_correction
```

where the current data model supports these distinctions.

---

# Phase 23 — Readmission Workflow

Expose readmission under:

```text
/inpatient/readmissions
```

The system should provide a formal **Readmit** action for eligible discharged patients.

Readmission should:

1. Use the existing patient record.
2. Link to the previous admission.
3. Preserve the previous discharge.
4. Create or extend the appropriate admission episode according to the established domain model.
5. Create a new active care period.
6. Re-establish ward and bed allocation.
7. Reopen or extend billing according to the configured admission and billing architecture.
8. Preserve previous invoices, receivables, payments, and audit history.
9. Record the readmission reason.
10. Record the responsible clinician.
11. Record the readmission date and time.
12. Update Journey Intelligence.
13. Create required handoffs.
14. Audit the readmission.

Do not accomplish readmission by simply changing a discharged admission back to `active` unless that is explicitly the established business model.

Where the intended model is to extend the same admission and its billing period, use the existing extension service and preserve a clear readmission event.

Where the intended model requires a linked new admission, use a parent or previous-admission relationship.

Do not duplicate billing or clinical records.

---

# Phase 24 — Billing and Service-Access Awareness

Display billing and service-access status where operationally relevant.

The Inpatient workspace may show safe states such as:

```text
cleared_to_proceed
payment_deferred
deposit_required
insurance_pending
billing_context_missing
authorized_override
previous_balance_present
```

Reuse existing:

* Payment-gate operation policies
* Per-visit billing overrides
* Admission billing
* Invoice receivables
* Previous-balance policy
* Payment allocation services
* Insurance coverage
* Claims workflow
* Discharge clearances

Do not expose unnecessary financial details to users without finance permissions.

Do not block urgent inpatient clinical care merely because billing is incomplete.

However, elective or non-urgent services must follow the configured payment rules.

Any override must be explicit, permission-controlled, reasoned, and audited.

Readmission must extend or restart billing according to the current admission billing model without losing previous financial history.

---

# Phase 25 — Workspace-Aware URL Resolution

Extend the centralized workspace route resolver.

Do not scatter checks such as:

```php
if ($department->type === DepartmentType::INPATIENT) {
    return route('inpatient.admissions.show', $admission);
}
```

across controllers and Blade views.

Extend a service such as:

```php
DepartmentWorkspaceRouteResolver
WorkspaceUrlResolver
DepartmentRouteResolver
```

The resolver should support methods equivalent to:

```php
dashboard()

patientIndex()
patientShow(Patient $patient)

admissionIndex()
admissionShow(Admission $admission)

wardIndex()
wardShow(Ward $ward)

bedAvailability()

visitShow(Visit $visit)

roundIndex()
roundShow(Admission $admission)

sessionIndex()
sessionShow(ConsultationSession $session)

vitalsShow(Admission $admission)
assessmentShow(Admission $admission)
carePlanShow(Admission $admission)

taskIndex()
taskShow(Task $task)

medicationIndex()
treatmentIndex()
procedureIndex()
investigationIndex()
observationIndex()
handoffIndex()

transferIndex()
dischargeReadiness(Admission $admission)
dischargeShow(Discharge $discharge)
readmissionCreate(Admission $admission)
```

For an Inpatient user, the resolver must return `inpatient.*` routes.

For other users, preserve the appropriate existing workspace or generic route.

Always use the currently active department context rather than only the user’s primary department.

---

# Phase 26 — Replace Hardcoded Shared Links

Audit all shared pages accessed by Inpatient users.

Replace hardcoded generic links that break workspace continuity.

Review at minimum:

* Admission lists
* Ward dashboards
* Bed lists
* Patient search
* Patient profiles
* Visit history
* Admission history
* Clinical rounds
* Session pages
* Vital-sign pages
* Nursing assessments
* Care plans
* Task lists
* Medication worklists
* Treatment worklists
* Procedure worklists
* Investigation worklists
* Observation pages
* Handoff pages
* Transfer pages
* Discharge pages
* Readmission actions
* Journey worklists
* Dashboard cards
* Breadcrumbs
* Notifications
* Escalation links
* Action dropdowns
* Empty-state actions
* Flash-message action links

Avoid shared-view code such as:

```php
route('admissions.show', $admission)
```

Use the centralized workspace route resolver.

Do not change API, callback, signed, payment, print, export, or background-job URLs unless they are explicitly part of the Inpatient browser workspace.

---

# Phase 27 — Workspace-Aware Redirects

All successful Inpatient actions must redirect back into `/inpatient/*`.

Examples:

After accepting an admission:

```text
/inpatient/admissions/{admission}
```

After assigning a bed:

```text
/inpatient/admissions/{admission}
```

After recording a clinical round:

```text
/inpatient/admissions/{admission}
```

After recording vital signs:

```text
/inpatient/admissions/{admission}
```

After completing a nursing task:

```text
/inpatient/tasks
```

or the admission workspace.

After starting discharge:

```text
/inpatient/discharges/{admission}/readiness
```

After completing discharge:

```text
/inpatient/admissions/discharged
```

After readmission:

```text
/inpatient/admissions/{activeAdmission}
```

Avoid hardcoding Inpatient redirects inside domain services.

Use a workspace redirect resolver such as:

```php
$workspaceRedirects->toAdmission($admission);
$workspaceRedirects->toWard($ward);
$workspaceRedirects->toTaskList();
$workspaceRedirects->toDischargeReadiness($admission);
$workspaceRedirects->toActiveAdmissions();
```

Validation failures must return users to the same `/inpatient/*` route with input preserved.

---

# Phase 28 — Login and Department Switching

When a user logs in and their active department type is `inpatient`, redirect them to:

```text
/inpatient
```

When a multi-department user switches to an Inpatient department, redirect them to:

```text
/inpatient
```

When switching away from Inpatient, redirect to the selected department’s corresponding workspace.

The menu, dashboard, route context, and data scoping must always use the active department.

Do not rely only on:

```php
$user->department_id
```

where the application supports multiple departments and an active department context.

---

# Phase 29 — Inpatient Workspace Authorization

The `/inpatient` prefix is not authorization.

Protect the workspace so access requires:

1. An authenticated user.
2. A valid active department.
3. Active department type equal to `inpatient`, unless an authorized admin-preview mode applies.
4. The required permission.
5. The relevant module being enabled.
6. Access to the requested patient, visit, admission, ward, or bed.
7. Appropriate ward or department assignment where required.

A user from another department who enters:

```text
/inpatient/admissions
```

must not receive access merely because they possess a broad admission-view permission.

Use the project’s existing unauthorized workspace behaviour:

* `403`
* workspace unavailable page
* safe redirect

Do not create inconsistent authorization behaviour.

---

# Phase 30 — Permission Model

Reuse existing permissions wherever possible.

Only add permissions where the current permission model does not correctly represent the action.

Potential permissions may include:

```text
inpatient.workspace.view
inpatient.admissions.view
inpatient.admissions.manage
inpatient.admissions.accept
inpatient.beds.view
inpatient.beds.assign
inpatient.wards.view
inpatient.rounds.view
inpatient.rounds.manage
inpatient.sessions.view
inpatient.sessions.manage
inpatient.sessions.reopen
inpatient.vitals.view
inpatient.vitals.manage
inpatient.assessments.view
inpatient.assessments.manage
inpatient.care_plans.view
inpatient.care_plans.manage
inpatient.tasks.view
inpatient.tasks.manage
inpatient.medications.view
inpatient.medications.administer
inpatient.treatments.view
inpatient.treatments.execute
inpatient.procedures.view
inpatient.investigations.view
inpatient.observations.view
inpatient.observations.manage
inpatient.handoffs.view
inpatient.handoffs.manage
inpatient.transfers.manage
inpatient.discharges.view
inpatient.discharges.manage
inpatient.discharge_overrides.manage
inpatient.readmissions.manage
inpatient.reports.view
```

Inspect current permission names before adding new ones.

Avoid duplicating equivalent permissions.

Menu visibility must follow permissions, but controllers, policies, form requests, and services must independently enforce authorization.

---

# Phase 31 — Legacy Route Compatibility

Keep existing generic routes operational for:

* Other departments
* Existing bookmarks
* APIs
* Print flows
* Signed URLs
* Internal notifications
* Background jobs
* Integrations
* Payment callbacks
* Export downloads

For interactive browser requests from an active Inpatient department, generic routes may redirect to Inpatient equivalents where safe.

Examples:

```text
/admissions/{admission}
→ /inpatient/admissions/{admission}

/wards/{ward}
→ /inpatient/wards/{ward}

/visits/{visit}
→ /inpatient/visits/{visit}
```

Do not blindly redirect:

* JSON requests
* APIs
* signed URLs
* callbacks
* print routes
* exports
* payment endpoints
* background requests
* integration requests

Avoid redirect loops.

---

# Phase 32 — Breadcrumbs and Active Menu State

Inpatient pages must display Inpatient-specific breadcrumbs.

Examples:

```text
Inpatient > Dashboard
Inpatient > Active Admissions
Inpatient > Admissions > Patient Admission
Inpatient > Wards
Inpatient > Wards > Ward Details
Inpatient > Beds
Inpatient > Clinical Rounds
Inpatient > Nursing Tasks
Inpatient > Medications
Inpatient > Investigations
Inpatient > Discharge Readiness
Inpatient > Discharges
Inpatient > Readmission
Inpatient > Reports
```

The sidebar must correctly highlight parent items for nested routes.

For example:

```text
inpatient.admissions.show
inpatient.rounds.show
inpatient.sessions.show
```

should highlight the appropriate admission or patient-care section.

Use route patterns rather than exact route-name equality only.

---

# Phase 33 — Shared View Workspace Context

Pass a clear Inpatient workspace context to shared views.

The context may include:

```php
[
    'workspaceKey' => 'inpatient',
    'workspaceDepartment' => $activeDepartment,
    'workspaceRoutePrefix' => 'inpatient.',
    'workspaceTitle' => __('inpatient.workspace.title'),
    'workspaceScope' => 'normal_admission',
]
```

Use an existing DTO or view-context object where available.

Shared views should use this context for:

* Page headings
* Breadcrumbs
* Links
* Form actions
* Back buttons
* Admission navigation
* Ward navigation
* Quick actions
* Empty states
* Notifications
* Discharge actions

Do not repeatedly inspect session state or department type inside Blade templates.

---

# Phase 34 — Patient Privacy and Clinical Safety

The Inpatient workspace handles highly sensitive patient and admission information.

Ensure all existing privacy controls remain active, including:

* Patient-name masking where applicable
* Protected phone and email fields
* Sensitive-field permission checks
* Access auditing
* Search-result masking
* Export restrictions
* Secure patient, visit, and admission lookup
* Privacy-aware notifications
* Activity-log sanitization

Do not bypass privacy controls because the patient is admitted.

Clinical-safety protections must remain active, including:

* Allergy warnings
* Critical vital-sign alerts
* Medication conflicts
* Duplicate active medication warnings
* Unusual dose warnings
* Missing diagnosis policy where applicable
* Treatment-readiness checks
* Procedure-readiness checks
* Investigation-result alerts
* Discharge-readiness checks
* Admission and transfer readiness
* Clinical escalation

Overrides must be explicit, permission-controlled, reasoned, scoped, and audited.

---

# Phase 35 — Activity Logging and Audit

Record relevant Inpatient actions through the existing `ActivityLog` infrastructure.

Audit events should cover existing actions such as:

* Admission accepted
* Admission rejected
* Bed assigned
* Bed changed
* Ward transfer initiated
* Ward transfer completed
* Clinical round created
* Clinical session created
* Clinical session completed
* Clinical session reopened
* Vital signs recorded
* Vital signs corrected
* Nursing assessment created
* Care plan created
* Care plan updated
* Nursing task claimed
* Nursing task completed
* Medication administered
* Medication withheld
* Treatment completed
* Procedure completed
* Investigation reviewed
* Observation recorded
* Handoff acknowledged
* Handoff resolved
* Discharge clearance updated
* Discharge override applied
* Patient discharged
* Session reopened after discharge
* Readmission created
* Admission billing period extended

Do not log full sensitive values.

Audit records should include sufficient context such as:

* Actor
* Patient identifier
* Visit identifier
* Admission identifier
* Department
* Ward
* Action
* Timestamp
* Reason where required
* Previous and new state where appropriate

---

# Phase 36 — Localization

Add complete English and French localization for the Inpatient workspace.

Prefer an existing inpatient localization file if one exists, otherwise use:

```text
lang/en/inpatient.php
lang/fr/inpatient.php
```

Include keys for:

* Workspace title
* Dashboard
* Menu sections
* Admission states
* Ward and bed states
* Clinical rounds
* Sessions
* Vital signs
* Nursing assessments
* Care plans
* Nursing tasks
* Medication administration
* Treatments
* Procedures
* Investigations
* Observations
* Intake and output
* Handoffs
* Transfers
* Discharge readiness
* Clearances
* Discharge
* Session reopening
* Readmission
* Billing and service-access states
* Empty states
* Quick actions
* Critical alerts
* Reports
* Breadcrumbs
* Unauthorized workspace message
* Override reasons

Maintain complete English and French parity.

Do not hardcode visible Inpatient labels in controllers, services, Blade templates, or JavaScript.

---

# Phase 37 — Menu Configuration and Future Extensibility

Implement the Inpatient menu through the existing menu registry or department menu profile service.

Do not define it directly inside the sidebar Blade template.

The menu configuration should support:

* Section ordering
* Route name
* Icon
* Permission
* Module requirement
* Active-route patterns
* Badge counts
* Department-type availability
* Ward scoping
* Feature flags
* Overdue counts
* Medication-due counts
* Discharge-readiness counts

The architecture must remain extensible for future department menu personalization, including:

```text
pharmacy
investigation
radiology
finance
stores
maternity
theatre
blood_bank
mortuary
ambulance
support
administrative
```

Do not implement those other workspaces in this phase.

---

# Phase 38 — Focused Automated Verification

Add focused automated tests for the Inpatient workspace.

## Route tests

Verify:

* Inpatient routes exist.
* Route names use `inpatient.*`.
* URLs use `/inpatient/*`.
* Inpatient department middleware is attached.
* Generic routes remain available where required.

## Access tests

Verify:

* An Inpatient department user can access authorized Inpatient pages.
* A non-Inpatient department user cannot access the workspace.
* Users without the required permission cannot access protected actions.
* Admin preview continues working where supported.
* Multi-department active context is respected.

## Dashboard tests

Verify:

* The Inpatient dashboard loads.
* Metrics include only normal inpatient admissions.
* Maternity and Emergency cases do not leak into the workspace incorrectly.
* Ward and bed metrics are accurate.
* Links point to `/inpatient/*`.
* Sensitive information remains protected.
* Empty states render safely.

## Admission tests

Verify:

* Admission acceptance uses the existing admission service.
* Bed assignment is transactional.
* A bed cannot be assigned to multiple active admissions.
* Active admissions remain visible.
* Discharged admissions leave the active-admission worklist.
* Readmitted patients return through the correct workflow.

## Session-rule tests

Verify:

* Active inpatient admissions remain workable across multiple days.
* OPD next-day locking is not applied.
* Completing one session does not block another active session.
* A completed session becomes read-only until reopened.
* Authorized users can reopen a completed session.
* Reopening requires a reason where configured.
* Reopening is audited.
* Reopening after discharge does not silently reactivate the admission.
* Reopening after discharge does not silently restore bed occupancy.
* Readmission uses the formal readmission workflow.

## Ward and bed tests

Verify:

* Ward occupancy counts are accurate.
* Bed availability updates after assignment.
* Transfers release and assign beds correctly.
* Discharge releases the bed at the correct point.
* Failed transactions do not leave inconsistent occupancy.

## Medication and task tests

Verify:

* Medication-due worklists are admission-scoped.
* Administration actions remain under `/inpatient/*`.
* Allergy and dose warnings remain active.
* Frequency-based nursing tasks display correctly.
* Completed tasks are audited.

## Discharge tests

Verify:

* Discharge readiness uses the existing clearance architecture.
* Missing required clearances block discharge.
* Authorized overrides require a reason.
* Overrides are audited.
* Discharge preserves clinical and billing history.
* Outstanding items are not silently discarded.
* Bed release occurs correctly.

## Readmission tests

Verify:

* Readmission links to the previous admission.
* Previous discharge remains preserved.
* The correct billing period or new billing episode is created.
* Previous invoices and payments are not modified incorrectly.
* Ward and bed allocation is re-established.
* Journey Intelligence updates correctly.
* Readmission is audited.

## Redirect tests

Verify:

* Login redirects to `/inpatient`.
* Switching to Inpatient redirects to `/inpatient`.
* Admission actions remain under `/inpatient/*`.
* Clinical round actions remain under `/inpatient/*`.
* Session actions remain under `/inpatient/*`.
* Medication, treatment, investigation, transfer, discharge, and readmission actions remain under `/inpatient/*`.
* No redirect loops occur.
* JSON and API requests are not incorrectly redirected.

## Privacy and audit tests

Verify:

* Patient masking remains active.
* Protected fields require permission.
* Clinical, admission, transfer, discharge, and readmission actions generate audit records.
* Sensitive values are not exposed through alternate Inpatient views.

Run focused Inpatient workspace tests and essential route, view, localization, migration, billing, and audit checks during implementation.

Do not run the full UHMS suite after each phase.

Run one broad relevant suite after all Inpatient workspace phases are complete.

---

# Phase 39 — Manual Acceptance Scenarios

## Scenario A — Inpatient login

1. Log in as a user whose active department type is `inpatient`.
2. Confirm the landing URL is `/inpatient`.
3. Confirm the Inpatient menu is displayed.
4. Confirm unrelated department menus are absent.

## Scenario B — Admission acceptance

1. Open pending admissions.
2. Accept an admission.
3. Assign a ward and bed.
4. Confirm the patient appears in active admissions.
5. Confirm the URL remains under `/inpatient/*`.
6. Confirm the actions are audited.

## Scenario C — Multi-day active admission

1. Open an admission created on a previous day.
2. Confirm the admission remains active.
3. Start a new clinical session.
4. Add clinical items.
5. Confirm the OPD next-day restriction is not applied.

## Scenario D — Multiple clinical sessions

1. Complete one inpatient session.
2. Open another active session.
3. Confirm authorized users can continue adding clinical items.
4. Confirm the completed session remains read-only.
5. Reopen the completed session with permission.
6. Confirm the reopening is audited.

## Scenario E — Nursing care

1. Record a nursing assessment.
2. Create or update a care plan.
3. Record vital signs.
4. Complete a nursing task.
5. Confirm the admission workspace updates.
6. Confirm all redirects remain under `/inpatient/*`.

## Scenario F — Medication administration

1. Open medications due.
2. Administer an authorized medication.
3. Record a withheld or refused medication.
4. Confirm safety warnings remain active.
5. Confirm all actions are audited.

## Scenario G — Investigation follow-up

1. Open pending investigations.
2. Confirm overdue samples and unreviewed results are visible.
3. Review a result.
4. Confirm the admission timeline updates.

## Scenario H — Ward transfer

1. Transfer a patient to another bed.
2. Transfer the patient to another ward.
3. Confirm source-bed release and destination assignment.
4. Confirm the admission remains the same.
5. Confirm the transfer is audited.

## Scenario I — Discharge readiness

1. Open discharge readiness.
2. Confirm all required clearance areas appear.
3. Leave one required clearance incomplete.
4. Confirm discharge remains blocked.
5. Complete the clearance.
6. Confirm the patient becomes ready for discharge.

## Scenario J — Discharge

1. Complete discharge.
2. Confirm the admission status changes.
3. Confirm the patient leaves the active-admission list.
4. Confirm the bed is released.
5. Confirm clinical and billing history remain intact.

## Scenario K — Session reopening after discharge

1. Open a discharged admission.
2. Reopen an eligible session with permission.
3. Add a permitted late entry or correction.
4. Confirm the admission does not silently become active.
5. Confirm the bed does not become occupied.
6. Confirm the action is audited.

## Scenario L — Readmission

1. Open a discharged patient.
2. Select Readmit.
3. Record the readmission reason.
4. Assign a ward and bed.
5. Confirm the previous admission remains preserved.
6. Confirm the new or extended billing period is correct.
7. Confirm the patient appears in active admissions.
8. Confirm the readmission is audited.

## Scenario M — Department switching

1. Use a multi-department user.
2. Switch to an Inpatient department.
3. Confirm redirect to `/inpatient`.
4. Switch to another department.
5. Confirm the correct menu and routes load.

## Scenario N — Permission control

1. Remove an Inpatient permission.
2. Confirm the related menu item disappears.
3. Enter the route directly.
4. Confirm access is denied.

## Scenario O — Legacy compatibility

1. Enter a generic admission or ward route as an Inpatient user.
2. Confirm it safely resolves or redirects to the Inpatient equivalent where configured.
3. Confirm APIs, signed URLs, callbacks, print routes, and exports remain unaffected.

---

# Acceptance Criteria

The implementation is accepted only when all the following are true:

1. Users with an active department type of `inpatient` receive a dedicated Inpatient menu.
2. Their default dashboard uses `/inpatient`.
3. Supported Inpatient pages use `/inpatient/*` URLs.
4. Route names use the `inpatient.*` namespace.
5. The workspace includes only normal inpatient admissions.
6. Maternity, Emergency, and OPD cases do not incorrectly appear.
7. Forms submit through Inpatient routes.
8. Redirects remain inside the Inpatient workspace.
9. Breadcrumbs and active menu states are Inpatient-aware.
10. Permissions and enabled modules control menu visibility.
11. A non-Inpatient department user cannot access the workspace.
12. Multi-department users are evaluated using the active department.
13. Existing admission, ward, bed, consultation, nursing, medication, treatment, investigation, billing, journey, discharge, and audit logic is reused.
14. Core clinical, admission, and billing logic is not duplicated.
15. Active inpatient admissions remain workable across multiple days.
16. OPD next-day locking is not applied to active admissions.
17. Completing one session does not block another active session.
18. Completed sessions can be reopened by authorized users.
19. Reopening is reasoned and audited.
20. Reopening after discharge does not silently reactivate the admission or bed occupancy.
21. Bed assignment and transfer remain transactionally consistent.
22. Medication and clinical-safety protections remain active.
23. Discharge uses the existing readiness and clearance architecture.
24. Discharge preserves clinical and billing history.
25. Readmission preserves the previous admission and discharge.
26. Readmission correctly creates or extends the billing period.
27. Generic routes remain functional for other departments and integrations.
28. APIs, signed URLs, callbacks, print routes, and exports are not incorrectly redirected.
29. Patient privacy protections remain fully active.
30. Relevant Inpatient actions are audited.
31. English and French localization are complete and in parity.
32. Focused Inpatient workspace tests pass.
33. One broad relevant suite passes after all phases are complete.
34. No broken links, route loops, duplicate route names, generic URL leaks, incorrect bed occupancy, or OPD/maternity/Emergency workflow contamination remain.

---

# Deliverables

Provide:

1. Inpatient workspace route group.
2. Inpatient-specific controllers or thin adapters where required.
3. Inpatient ward dashboard.
4. Inpatient department menu profile.
5. Admission worklists.
6. Inpatient admission workspace.
7. Ward and bed-management integration.
8. Clinical-round integration.
9. Inpatient-session integration.
10. Nursing assessments and care plans.
11. Vital-sign and intake/output monitoring.
12. Nursing-task worklists.
13. Medication, treatment, procedure, and investigation worklists.
14. Observation and handoff integration.
15. Transfer workflow integration.
16. Discharge-readiness and clearance integration.
17. Discharge workflow integration.
18. Session reopening after discharge.
19. Readmission workflow.
20. Workspace-aware URL resolver updates.
21. Workspace-aware redirect resolver updates.
22. Updated shared links and forms.
23. Login and department-switch integration.
24. Inpatient breadcrumbs and active-menu handling.
25. Permission integration.
26. Patient privacy and clinical-safety integration.
27. Billing and service-access integration.
28. English and French localization.
29. Focused feature tests.
30. A final implementation report containing:

* Files created
* Files modified
* Inpatient route map
* Inpatient menu map
* Admission worklist categories
* Dashboard metrics
* Ward and bed behaviour
* Clinical-session behaviour
* Session reopening behaviour
* Discharge-readiness workflow
* Discharge behaviour
* Readmission behaviour
* Billing-period behaviour
* Reused services
* Redirect behaviour
* Permissions used
* Patient privacy checks
* Clinical-safety checks
* Audit events
* Tests executed
* Test results
* Remaining limitations, if any

Implement the work fully.

Do not stop at planning, route registration, menu configuration, dashboard layout, or admission listing alone. The final implementation must provide a functional, safe, department-specific Inpatient workspace throughout the full normal-admission lifecycle.
