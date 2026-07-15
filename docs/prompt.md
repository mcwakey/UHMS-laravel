# UHMS Nursing Department Workspace — OPD Nursing Menu and `/nursing/*` Route Architecture

Implement a dedicated **Nursing Department Workspace** for UHMS.

This workspace is specifically for nursing staff who manage **outpatient department cases**, including patient arrival, triage, vital signs, nursing assessments, consultation preparation, treatment support, nursing tasks, observation, patient flow, and OPD discharge preparation.

This implementation should follow the same department-aware workspace approach already used for doctors, consultation departments, and the Records department.

The objective is that when a logged-in user’s active department has:

```php
DepartmentType::NURSING
```

the user should experience UHMS as a dedicated OPD Nursing application with:

* A Nursing-specific sidebar menu
* A Nursing OPD dashboard
* Nursing-specific breadcrumbs
* Nursing-specific route names
* Consistent `/nursing/*` URLs
* Workspace-aware redirects
* OPD patient queues and worklists
* Permission-controlled menu visibility
* Active-department scoping
* Reuse of existing patient, visit, triage, vital-sign, consultation, treatment, nursing-task, medication, and billing logic

Do not duplicate core patient, visit, consultation, triage, treatment, or billing business logic merely to create the Nursing workspace.

---

# 1. Core Functional Requirement

When the active department type is `nursing`, all supported pages used by OPD nursing staff must appear under the `/nursing` URL prefix.

Examples:

```text
/nursing
/nursing/dashboard

/nursing/patients
/nursing/patients/{patient}

/nursing/opd
/nursing/opd/queue
/nursing/opd/active
/nursing/opd/completed

/nursing/visits
/nursing/visits/{visit}

/nursing/triage
/nursing/triage/{visit}
/nursing/triage/{visit}/edit

/nursing/vitals
/nursing/vitals/{visit}

/nursing/assessments
/nursing/assessments/{visit}

/nursing/tasks
/nursing/tasks/{task}

/nursing/treatments
/nursing/treatments/{visit}

/nursing/observations
/nursing/observations/{visit}

/nursing/handoffs
/nursing/reports
```

A Nursing user should not enter through:

```text
/nursing/opd/queue
```

and later be redirected to generic URLs such as:

```text
/visits/{visit}
/patients/{patient}
/triage/{visit}
/consultations/{consultation}
```

All browser navigation, forms, redirects, breadcrumbs, tables, worklists, dashboard links, patient-flow actions, and related actions must preserve the Nursing workspace context.

---

# 2. Nursing Workspace Scope

The Nursing workspace is primarily responsible for **OPD nursing operations**.

The workspace should support existing functionality related to:

1. OPD patient arrival and nursing queues
2. Triage
3. Vital signs
4. Nursing assessment
5. Consultation preparation
6. Patient movement through the OPD workflow
7. Nursing tasks
8. Treatment and procedure support
9. Medication administration where already supported
10. Patient observation
11. Clinical handoffs
12. Monitoring pending services
13. Consultation support
14. OPD discharge preparation
15. Same-day completed OPD case review or reopening where authorized
16. Nursing reports and activity monitoring

This phase should not convert the Nursing workspace into an inpatient ward-management workspace.

Inpatient nursing workflows should remain under the appropriate inpatient, ward, maternity, or admission context unless the current architecture intentionally shares components.

---

# Phase 1 — Inspect Existing Nursing and OPD Architecture

Before implementing, inspect the current codebase and identify:

1. How department-specific menus are selected.
2. How doctor and Records workspaces are currently registered.
3. How the active department context is resolved.
4. How multi-department users switch active departments.
5. Existing routes and controllers for:

   * patients
   * visits
   * triage
   * vital signs
   * consultations
   * nursing assessments
   * nursing notes
   * treatments
   * procedures
   * medication administration
   * clinical tasks
   * observations
   * handoffs
   * discharge
6. Existing OPD visit classifications and statuses.
7. How outpatient and inpatient cases are distinguished.
8. How active, completed, reopened, and closed consultations are handled.
9. How patient queues and journey worklists are generated.
10. Existing dashboard and menu profile services.
11. Existing workspace URL and redirect resolvers.
12. Existing permission, module, and department filtering.
13. Existing patient privacy and audit protections.
14. Views that hardcode generic route names such as:

```php
route('patients.show', $patient)
route('visits.show', $visit)
route('triage.edit', $visit)
route('consultations.show', $consultation)
```

Do not create a competing workspace or menu system where one already exists.

Extend the existing:

* Department dashboard registry
* Department menu profile service
* Active department context
* Workspace route resolver
* Workspace redirect resolver
* Permission filtering
* Patient journey services
* Consultation workflow services

---

# Phase 2 — Nursing Workspace Route Group

Create a dedicated Nursing route group.

Use a structure equivalent to:

```php
Route::prefix('nursing')
    ->name('nursing.')
    ->middleware([
        'auth',
        'verified',
        'department.context',
        'department.type:nursing',
    ])
    ->group(function () {
        // Nursing workspace routes
    });
```

Use the project’s actual middleware names and department-type authorization architecture.

Required route naming should include, where the corresponding feature already exists:

```text
nursing.dashboard

nursing.patients.index
nursing.patients.show

nursing.opd.index
nursing.opd.queue
nursing.opd.active
nursing.opd.completed

nursing.visits.index
nursing.visits.show

nursing.triage.index
nursing.triage.show
nursing.triage.create
nursing.triage.store
nursing.triage.edit
nursing.triage.update

nursing.vitals.index
nursing.vitals.show
nursing.vitals.create
nursing.vitals.store
nursing.vitals.edit
nursing.vitals.update

nursing.assessments.index
nursing.assessments.show
nursing.assessments.create
nursing.assessments.store
nursing.assessments.edit
nursing.assessments.update

nursing.tasks.index
nursing.tasks.show
nursing.tasks.update

nursing.treatments.index
nursing.treatments.show

nursing.observations.index
nursing.observations.show

nursing.handoffs.index
nursing.reports.index
```

Only register routes for functionality that exists or is being implemented.

Do not add non-functional placeholder pages.

---

# Phase 3 — Nursing OPD Dashboard

Create or complete a dedicated Nursing dashboard.

The canonical route should be:

```text
/nursing
```

or:

```text
/nursing/dashboard
```

Choose one canonical destination and redirect the other to it.

The department dashboard resolver should map:

```php
DepartmentType::NURSING => 'nursing.dashboard'
```

The dashboard should be personalized for OPD nursing operations.

Recommended metrics and widgets include, where reliable data exists:

* Patients waiting for triage
* Patients currently in triage
* Patients with incomplete vital signs
* Patients waiting for consultation
* Active OPD cases
* High-risk or critical patients
* Patients with overdue nursing tasks
* Patients awaiting treatment
* Patients under observation
* Patients awaiting investigation
* Patients awaiting medication
* Patients ready for discharge
* Completed OPD cases today
* Reopened OPD cases today
* Average triage waiting time
* Average nursing processing time
* Pending handoffs
* Unacknowledged handoffs
* Recently completed nursing actions

Each dashboard widget must:

* Respect permissions
* Respect the active department
* Include only appropriate OPD visits
* Avoid leaking protected patient information
* Link to `/nursing/*` routes
* Use safe empty states
* Avoid expensive unbounded queries
* Reuse Journey Intelligence data where applicable

Do not introduce metrics that cannot be calculated accurately from current data.

---

# Phase 4 — Nursing-Specific Menu Profile

Extend the existing department menu profile or menu registry so that:

```php
DepartmentType::NURSING
```

receives a dedicated OPD Nursing menu.

Recommended menu structure:

## Nursing Dashboard

* Dashboard

## OPD Patient Flow

* OPD Queue
* Waiting for Triage
* In Triage
* Waiting for Consultation
* Active OPD Cases
* Completed Today

## Triage and Assessment

* Triage Worklist
* Vital Signs
* Nursing Assessments
* High-Risk Patients
* Critical Cases

## Nursing Care

* Nursing Tasks
* Treatments
* Procedures Support
* Medication Administration
* Patient Observations
* Nursing Notes

## Coordination

* Pending Services
* Investigation Follow-Up
* Consultation Follow-Up
* Handoffs
* Escalations
* Discharge Preparation

## Patient Access

* Patient Search
* Patient Profiles
* Visit History

## Nursing Reports

* OPD Attendance Report
* Triage Report
* Vital Signs Report
* Nursing Activity Report
* Treatment Report
* Task Completion Report
* Patient Flow Report

## General

* Notifications
* My Profile
* Switch Department

Only show a menu item when:

1. The feature exists.
2. The required module is enabled.
3. The user possesses the required permission.
4. The active department permits access.
5. The feature is appropriate for OPD nursing.

Do not expose menu items based only on the department type.

Permissions remain authoritative.

---

# Phase 5 — OPD Patient Queue

Create or adapt an OPD nursing queue under:

```text
/nursing/opd/queue
```

The queue should include outpatient visits requiring nursing attention.

Recommended queue categories:

```text
waiting_for_triage
triage_in_progress
vitals_incomplete
waiting_for_consultation
consultation_in_progress
nursing_action_required
treatment_pending
observation
service_follow_up
ready_for_discharge
completed_today
```

Use the existing visit statuses, journey stages, consultation state, nursing tasks, and service statuses.

Do not introduce duplicate visit statuses where existing statuses can be interpreted through a queue resolver.

Each queue item should display only authorized information, such as:

* Patient identifier
* Masked patient name where required
* Visit number
* Arrival time
* Current OPD stage
* Waiting duration
* Priority or acuity
* Triage status
* Vital signs status
* Consultation status
* Pending nursing actions
* Assigned nurse where applicable
* Next recommended action

Each queue row should link to a `/nursing/*` destination.

The queue should support useful filters such as:

* Department
* Date
* Status
* Priority
* Assigned nurse
* Waiting duration
* Triage completion
* Consultation state
* Pending task type

Use pagination and efficient queries.

---

# Phase 6 — OPD Case Workspace

Create or adapt a Nursing OPD case workspace for each visit.

Recommended route:

```text
/nursing/opd/{visit}
```

or:

```text
/nursing/visits/{visit}
```

Choose the route that best fits the existing architecture.

The OPD case page should provide a coordinated nursing view of the patient’s outpatient visit.

Recommended sections:

1. Patient identity strip
2. Visit details
3. Current journey stage
4. Arrival and waiting-time information
5. Triage summary
6. Vital signs
7. Nursing assessment
8. Allergies and alerts
9. Current complaints
10. Consultation status
11. Pending investigations
12. Pending treatments
13. Pending procedures
14. Medication administration
15. Nursing tasks
16. Observations
17. Handoffs
18. Discharge readiness
19. Activity timeline
20. Authorized quick actions

Do not duplicate the doctor consultation workspace.

The Nursing workspace should present the information and actions needed for OPD nursing care while respecting clinical ownership and permissions.

---

# Phase 7 — Triage Workflow

Ensure triage functionality is fully available inside the Nursing workspace.

Examples:

```text
/nursing/triage
/nursing/triage/{visit}
/nursing/triage/{visit}/edit
```

The triage workflow should reuse existing:

* Triage models
* Form requests
* Validation
* Acuity rules
* Clinical alerts
* Vital sign services
* Audit logging
* Patient privacy protections

Triage should support existing fields such as:

* Chief complaint
* Arrival mode
* Acuity or priority
* Temperature
* Pulse
* Respiratory rate
* Blood pressure
* Oxygen saturation
* Weight
* Height
* BMI
* Pain score
* Consciousness level
* Clinical notes
* Emergency indicators
* Pregnancy-related information where already supported
* Pediatric measurements where applicable

Do not create parallel triage records for the same visit.

When triage is completed, the patient should move to the appropriate next OPD queue state using the existing journey and visit-state architecture.

---

# Phase 8 — Vital Signs Workflow

Expose vital-sign recording and review under `/nursing/*`.

The implementation must support:

* Initial vital signs
* Repeat vital signs
* Time-stamped observations
* Nurse identity
* Abnormal-value indicators
* Critical-value alerts
* Trend display where supported
* Patient age and context-aware ranges where already implemented
* Audit logging
* Authorization

The page should distinguish between:

* Missing vital signs
* Complete vital signs
* Abnormal vital signs
* Critical vital signs
* Repeat measurement due

All form submissions and redirects must remain inside `/nursing/*`.

---

# Phase 9 — Nursing Assessment

Create or adapt the nursing assessment workflow for OPD cases.

The assessment may include existing fields such as:

* General condition
* Pain assessment
* Mobility
* Fall risk
* Allergies
* Infection risk
* Mental status
* Hydration
* Nutrition concerns
* Skin condition
* Immediate nursing needs
* Clinical observations
* Escalation requirement
* Nursing plan
* Free-text nursing notes

Do not add clinical assessment fields that do not fit the current data model unless required by existing project specifications.

The assessment should:

* Be visit-scoped
* Be time-stamped
* Record the responsible nurse
* Respect privacy controls
* Generate audit events
* Integrate with nursing tasks where appropriate
* Remain visible to authorized clinicians

---

# Phase 10 — Nursing Tasks

Expose nursing tasks under:

```text
/nursing/tasks
```

Reuse the existing consultation and nursing task infrastructure.

The Nursing workspace should show tasks assigned to:

* The active nurse
* The active Nursing department
* Unassigned nursing staff
* The current OPD visit

Task states may include:

```text
pending
assigned
acknowledged
in_progress
completed
cancelled
overdue
```

Where the existing task model supports frequency-based task generation, display generated occurrences correctly.

For example, a task scheduled three times should produce or represent three actionable occurrences according to the current task architecture.

The Nursing workspace should allow authorized users to:

* Claim a task
* Assign a task
* Acknowledge a task
* Start a task
* Record completion
* Add a completion note
* Escalate a task
* View related patient and visit
* View task history

All task routes and redirects should remain under `/nursing/*`.

---

# Phase 11 — Treatment and Procedure Support

Expose nursing-relevant treatment and procedure worklists where those workflows already exist.

Examples:

```text
/nursing/treatments
/nursing/treatments/{visit}
```

The worklist may include:

* Ordered treatment
* Ordered procedure
* Prescribing or requesting clinician
* Scheduled time
* Status
* Assigned nurse
* Required consumables
* Completion status
* Pending billing or payment status where relevant
* Clinical instructions
* Safety warnings

Do not allow nursing users to perform doctor-only ordering actions unless they possess a specific permission and the existing workflow supports it.

The Nursing workspace may support execution, acknowledgement, administration, observation, and completion of authorized nursing actions.

---

# Phase 12 — Medication Administration

Where medication administration functionality already exists, expose it in the Nursing workspace.

The workspace may display:

* Medication
* Dose
* Route
* Frequency
* Scheduled time
* Prescribing clinician
* Administration status
* Last administered time
* Next due time
* Omitted or refused reason
* Allergy warnings
* Duplicate-medication warnings
* Patient-specific safety alerts

Authorized nursing users may record:

* Administered
* Delayed
* Withheld
* Refused
* Not available
* Cancelled

Do not bypass the existing prescription, pharmacy, inventory, payment, or medication-safety workflows.

Medication administration must not imply that the medication has been dispensed or paid for unless those workflows are complete.

---

# Phase 13 — Patient Observation

Provide an OPD observation worklist where applicable.

Examples:

```text
/nursing/observations
/nursing/observations/{visit}
```

Observation functionality may include:

* Observation start time
* Observation reason
* Assigned nurse
* Repeat vital schedule
* Clinical notes
* Pain monitoring
* Response to treatment
* Escalation status
* Observation duration
* Readiness for consultation or discharge

Observation is not an inpatient admission.

Do not convert an OPD observation case into an admission unless the authorized admission workflow is explicitly completed.

---

# Phase 14 — Nursing Handoffs

Expose Nursing handoffs under:

```text
/nursing/handoffs
```

Reuse the existing Journey Intelligence handoff and coordination infrastructure.

Nursing staff should be able to see:

* Handoffs owed by Nursing
* Handoffs owed to Nursing
* Unacknowledged handoffs
* Overdue handoffs
* Escalated handoffs
* Patient and visit context
* Responsible department
* Expected action
* SLA status

Use the existing handoff actions where supported:

* Claim
* Assign
* Acknowledge
* Resolve
* Dismiss
* Escalate

All handoff links should resolve through `/nursing/*` where a Nursing workspace equivalent exists.

---

# Phase 15 — OPD Session and Completion Rules

Preserve the established outpatient consultation rules.

For outpatient visits:

1. Nursing staff should be able to continue working on an active same-day OPD visit.
2. Completion of a consultation session must not immediately make the visit inaccessible on the same day.
3. Authorized users should still be able to reopen a completed same-day session.
4. A completed OPD consultation may be automatically finalized by the system according to the existing workflow.
5. Manual consultation completion should not be reintroduced where the system has moved to automatic completion.
6. New clinical items should be blocked on a later day unless the visit or session is formally reopened.
7. Reopening must be permission-controlled and audited.
8. The Nursing workspace should clearly show whether a case is:

   * active
   * completed today
   * reopened
   * closed from a previous day
   * awaiting discharge
9. Nursing actions must not reopen a visit silently.
10. Existing visit-completion and consultation-completion services must remain authoritative.

Do not apply inpatient session rules to OPD cases.

---

# Phase 16 — Payment and Service Access Awareness

Nursing staff may need to know whether a patient is permitted to proceed with services.

Display payment or service-access status where relevant, but do not expose unnecessary financial detail.

The workspace may show safe operational states such as:

```text
cleared_to_proceed
payment_required
payment_deferred
insurance_pending
authorized_override
billing_context_missing
```

Reuse the existing payment-gate operation policy and visit billing override architecture.

Do not allow the Nursing workspace to bypass payment enforcement.

Respect:

* Global payment policy
* Per-visit payment permission
* Risk-patient enforcement
* Departmental operation configuration
* Authorized billing overrides
* Emergency exceptions
* Audit logging

Nursing staff should see only what is needed to know whether an action can proceed.

---

# Phase 17 — Workspace-Aware URL Resolution

Introduce or extend the centralized workspace route resolver.

Do not scatter checks such as:

```php
if ($department->type === DepartmentType::NURSING) {
    return route('nursing.visits.show', $visit);
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

opdQueue()
opdVisitShow(Visit $visit)

visitIndex()
visitShow(Visit $visit)

triageIndex()
triageShow(Visit $visit)
triageEdit(Visit $visit)

vitalsShow(Visit $visit)
vitalsEdit(Visit $visit)

assessmentShow(Visit $visit)
taskIndex()
taskShow(Task $task)

treatmentIndex()
observationIndex()
handoffIndex()
```

For a Nursing user, it should return `nursing.*` routes.

For other users, it should preserve their existing workspace or generic routes.

The resolver must use the currently active department context rather than only the user’s primary department.

---

# Phase 18 — Replace Hardcoded Shared Links

Audit all shared pages accessed by Nursing staff.

Replace hardcoded generic links that break Nursing workspace continuity.

Review at minimum:

* OPD queues
* Patient search
* Patient profile
* Visit lists
* Visit history
* Triage pages
* Vital-sign pages
* Consultation summary pages
* Nursing-task lists
* Treatment worklists
* Observation worklists
* Handoff pages
* Journey worklists
* Dashboard cards
* Breadcrumbs
* Action dropdowns
* Flash-message actions
* Empty states
* Notifications
* Escalation links
* Recently viewed patients
* Pending-service links

Avoid shared-view code such as:

```php
route('visits.show', $visit)
```

Use the centralized workspace route resolver.

Do not alter API, integration, signed, payment-callback, print, export, or background-job URLs unless they are explicitly part of the Nursing browser workspace.

---

# Phase 19 — Workspace-Aware Redirects

All successful Nursing actions must redirect back into `/nursing/*`.

Examples:

After triage:

```text
/nursing/opd/{visit}
```

After recording vital signs:

```text
/nursing/opd/{visit}
```

After completing a nursing task:

```text
/nursing/tasks
```

or:

```text
/nursing/opd/{visit}
```

After recording an observation:

```text
/nursing/observations/{visit}
```

After acknowledging a handoff:

```text
/nursing/handoffs
```

Avoid hardcoding Nursing redirects inside clinical business services.

Use a workspace redirect resolver such as:

```php
$workspaceRedirects->toOpdVisit($visit);
$workspaceRedirects->toTriage($visit);
$workspaceRedirects->toTaskList();
$workspaceRedirects->toHandoffList();
```

Validation failures must return the user to the same Nursing route with input preserved.

---

# Phase 20 — Login and Department Switching

When a user logs in and their active department type is `nursing`, redirect them to:

```text
/nursing
```

When a multi-department user switches their active department to a Nursing department, redirect them to:

```text
/nursing
```

When switching away from Nursing, redirect to the destination workspace associated with the newly active department.

The Nursing menu and URLs must always be based on the active department context.

Do not use only:

```php
$user->department_id
```

where the application supports multiple departments.

---

# Phase 21 — Nursing Workspace Authorization

The `/nursing` prefix is not authorization.

Protect the Nursing workspace so access requires:

1. An authenticated user.
2. A valid active department.
3. Active department type equal to `nursing`, unless an authorized admin-preview mode applies.
4. The permission required by the requested action.
5. The relevant module being enabled.
6. Access to the patient and visit under existing policies.
7. Appropriate clinical ownership or assignment where required.

A user from another department who enters:

```text
/nursing/opd/queue
```

must not receive access merely because they possess a broad visit-view permission.

Use the project’s existing unauthorized workspace behaviour:

* `403`
* workspace unavailable page
* safe redirect

Do not create inconsistent authorization behaviour.

---

# Phase 22 — Permission Model

Reuse existing permissions wherever they already correctly represent the action.

Add new permissions only where necessary.

Potential permissions may include:

```text
nursing.workspace.view
nursing.opd_queue.view
nursing.triage.view
nursing.triage.manage
nursing.vitals.view
nursing.vitals.manage
nursing.assessments.view
nursing.assessments.manage
nursing.tasks.view
nursing.tasks.manage
nursing.treatments.view
nursing.treatments.execute
nursing.medications.view
nursing.medications.administer
nursing.observations.view
nursing.observations.manage
nursing.handoffs.view
nursing.handoffs.manage
nursing.reports.view
nursing.sessions.reopen
```

Before adding permissions, inspect current permission names and avoid duplication.

Menu visibility must follow permission checks, but controllers, policies, and services must independently enforce authorization.

---

# Phase 23 — Legacy Route Compatibility

Keep existing generic routes operational for:

* Other departments
* Existing bookmarks
* Internal notifications
* Print flows
* API consumers
* Background jobs
* Integrations
* Signed URLs

For interactive browser requests from an active Nursing department, generic routes may redirect to Nursing equivalents where safe.

Examples:

```text
/triage
→ /nursing/triage

/visits/{visit}
→ /nursing/opd/{visit}

/nursing-tasks
→ /nursing/tasks
```

Apply redirects carefully.

Do not blindly redirect:

* JSON requests
* API requests
* signed URLs
* payment callbacks
* export downloads
* print routes
* AJAX endpoints
* background requests
* integration requests

Avoid redirect loops.

---

# Phase 24 — Breadcrumbs and Active Menu State

Nursing pages must display Nursing-specific breadcrumbs.

Examples:

```text
Nursing > Dashboard
Nursing > OPD Queue
Nursing > OPD Queue > Patient Visit
Nursing > Triage
Nursing > Triage > Record Triage
Nursing > Vital Signs
Nursing > Nursing Tasks
Nursing > Observations
Nursing > Handoffs
Nursing > Reports
```

The sidebar must correctly highlight menu items for nested routes.

For example:

```text
nursing.triage.edit
nursing.triage.update
```

should highlight:

```text
Triage Worklist
```

Use active route patterns rather than exact route-name comparisons only.

---

# Phase 25 — Shared View Workspace Context

Pass a clear Nursing workspace context to shared views.

The context may include:

```php
[
    'workspaceKey' => 'nursing',
    'workspaceDepartment' => $activeDepartment,
    'workspaceRoutePrefix' => 'nursing.',
    'workspaceTitle' => __('nursing.workspace.title'),
    'workspaceScope' => 'opd',
]
```

Use an existing DTO or view-context object where available.

Shared views should use this context for:

* Page headings
* Breadcrumbs
* Links
* Form actions
* Back buttons
* Quick actions
* Empty states
* Queue navigation
* Visit navigation

Do not repeatedly read session state or detect the department type inside Blade templates.

---

# Phase 26 — Patient Privacy and Clinical Safety

The Nursing workspace handles protected clinical information.

Ensure all existing privacy controls remain active, including:

* Patient-name masking where applicable
* Protected phone and email fields
* Sensitive field permissions
* Access auditing
* Search-result masking
* Export restrictions
* Clinical note authorization
* Activity-log sanitization
* Privacy-aware notifications
* Secure patient and visit lookups

Do not bypass privacy services because a user belongs to Nursing.

Clinical safety protections must remain active, including:

* Allergy warnings
* Critical vital alerts
* Medication conflicts
* Duplicate active medication warnings
* Missing diagnosis policy where relevant
* Unusual dose warnings
* Prescription override rules
* Treatment readiness checks
* Completion readiness checks

Nursing users should not be allowed to override clinical safety restrictions unless an explicit permission and override workflow exist.

---

# Phase 27 — Activity Logging and Audit

Record relevant Nursing actions using the existing `ActivityLog` infrastructure.

Audit events should cover existing actions such as:

* Triage created
* Triage updated
* Vital signs recorded
* Vital signs corrected
* Nursing assessment created
* Nursing assessment updated
* Nursing task claimed
* Nursing task assigned
* Nursing task completed
* Medication administered
* Medication withheld
* Treatment completed
* Observation started
* Observation updated
* Observation completed
* Handoff acknowledged
* Handoff resolved
* OPD session reopened
* Clinical escalation created

Do not log full sensitive field values.

Audit records should contain sufficient context such as:

* Actor
* Patient identifier
* Visit identifier
* Department
* Action
* Timestamp
* Reason where required

---

# Phase 28 — Localization

Add complete English and French localisation for the Nursing workspace.

Prefer an existing nursing localisation file if one exists, otherwise use:

```text
lang/en/nursing.php
lang/fr/nursing.php
```

Include keys for:

* Workspace title
* Dashboard
* Menu sections
* OPD queue states
* Triage
* Vital signs
* Nursing assessments
* Nursing tasks
* Treatments
* Medication administration
* Observations
* Handoffs
* Patient-flow states
* Payment-access states
* Empty states
* Quick actions
* Reports
* Breadcrumbs
* Unauthorized workspace message
* Session reopening
* Critical alerts
* Waiting-time labels

Maintain complete EN/FR parity.

Do not hardcode visible Nursing labels in controllers, services, Blade templates, or JavaScript.

---

# Phase 29 — Menu Configuration and Future Extensibility

Implement the Nursing menu through the existing menu registry or department menu profile service.

Do not build it directly inside the sidebar Blade template.

The menu definition should support:

* Section ordering
* Route name
* Icon
* Permission
* Module requirement
* Active route patterns
* Badge counts
* Department-type availability
* OPD-specific visibility
* Feature flags

The architecture should remain extensible for future department menu personalisation, including:

```text
pharmacy
investigation
radiology
finance
stores
inpatient
maternity
theatre
emergency
blood_bank
mortuary
ambulance
support
administrative
```

Do not implement those other workspaces in this phase.

---

# Phase 30 — Focused Automated Verification

Add focused automated tests for the Nursing workspace.

## Route tests

Verify:

* Nursing routes exist.
* Route names use `nursing.*`.
* URLs use `/nursing/*`.
* Nursing department middleware is attached.
* Generic routes remain available where required.

## Access tests

Verify:

* A Nursing department user can access authorized Nursing pages.
* A non-Nursing department user cannot access the Nursing workspace.
* A Nursing user without the required permission cannot access protected actions.
* Admin preview continues working where supported.
* Multi-department active context is respected.

## Dashboard tests

Verify:

* Nursing dashboard loads.
* OPD metrics include only appropriate visits.
* Links point to `/nursing/*`.
* Sensitive information remains protected.
* Empty states render safely.

## Menu tests

Verify:

* Nursing users receive the Nursing menu.
* Non-Nursing users do not receive it.
* Unauthorized items are hidden.
* Disabled-module items are hidden.
* Active-route highlighting works.
* Badge counts use OPD-scoped data.

## Queue tests

Verify:

* Only OPD visits appear.
* Inpatient visits do not incorrectly appear.
* Queue categorization is correct.
* Triage and vital status are reflected.
* Completed-today cases remain accessible.
* Previous-day closed cases are not editable without reopening.

## Redirect tests

Verify:

* Login redirects to `/nursing`.
* Switching to Nursing redirects to `/nursing`.
* Triage actions remain inside `/nursing/*`.
* Vital-sign actions remain inside `/nursing/*`.
* Task actions remain inside `/nursing/*`.
* Handoff actions remain inside `/nursing/*`.
* No redirect loops occur.
* JSON and API requests are not incorrectly redirected.

## Session-rule tests

Verify:

* Active same-day OPD visits remain editable.
* Completed same-day OPD sessions can be reopened with permission.
* Previous-day sessions reject new clinical entries until reopened.
* Reopening is audited.
* Inpatient rules are not accidentally applied to OPD cases.

## Privacy and audit tests

Verify:

* Patient masking remains active.
* Protected fields require permission.
* Clinical actions generate required audit records.
* Sensitive values are not exposed through alternate Nursing views.

Run focused Nursing workspace tests and essential route, view, localisation, migration, and audit checks during implementation.

Do not run the entire UHMS suite after each phase.

Run one broad relevant suite after all Nursing workspace phases are complete.

---

# Phase 31 — Manual Acceptance Scenarios

## Scenario A — Nursing login

1. Log in as a user whose active department type is `nursing`.
2. Confirm the landing URL is `/nursing`.
3. Confirm the Nursing OPD menu is displayed.
4. Confirm unrelated department menus are absent.

## Scenario B — OPD queue

1. Open `/nursing/opd/queue`.
2. Confirm only OPD cases are shown.
3. Confirm patient states and waiting times are correct.
4. Open a patient.
5. Confirm the URL remains under `/nursing/*`.

## Scenario C — Triage

1. Open a patient waiting for triage.
2. Record triage information.
3. Submit the form.
4. Confirm the redirect remains under `/nursing/*`.
5. Confirm the queue status changes correctly.
6. Confirm the activity is audited.

## Scenario D — Vital signs

1. Record initial vital signs.
2. Record a repeat vital.
3. Confirm both readings appear in the timeline.
4. Confirm abnormal readings are highlighted.
5. Confirm critical values trigger the existing safety behaviour.

## Scenario E — Nursing tasks

1. Open `/nursing/tasks`.
2. Claim a pending task.
3. Complete the task.
4. Confirm the patient’s case page updates.
5. Confirm the redirect remains under `/nursing/*`.

## Scenario F — Same-day completed OPD case

1. Complete an OPD consultation.
2. Open the case again on the same day.
3. Confirm authorized users can reopen the session.
4. Add a permitted nursing item.
5. Confirm the reopen action and new item are audited.

## Scenario G — Previous-day completed case

1. Open an OPD visit completed on a previous day.
2. Attempt to add a new nursing item.
3. Confirm the system blocks the action.
4. Reopen the visit with an authorized user.
5. Confirm the action becomes available.

## Scenario H — Department switching

1. Use a multi-department user.
2. Switch to a Nursing department.
3. Confirm redirect to `/nursing`.
4. Switch to another department.
5. Confirm that department’s menu and routes load.

## Scenario I — Permission control

1. Remove a Nursing permission.
2. Confirm the relevant menu item disappears.
3. Directly enter the route.
4. Confirm access is denied.

## Scenario J — Legacy compatibility

1. Enter a generic visit or triage browser route as a Nursing user.
2. Confirm it safely resolves or redirects to the Nursing equivalent where configured.
3. Confirm API, export, signed, callback, and print routes remain unaffected.

---

# Acceptance Criteria

The implementation is accepted only when all the following are true:

1. Users with an active department type of `nursing` receive a dedicated Nursing OPD menu.
2. Their default dashboard uses `/nursing`.
3. Supported Nursing pages use `/nursing/*` URLs.
4. Route names use the `nursing.*` namespace.
5. The Nursing queue includes only appropriate OPD cases.
6. Forms submit through Nursing routes.
7. Redirects remain inside the Nursing workspace.
8. Breadcrumbs and active menu states are Nursing-aware.
9. Permissions and enabled modules control menu visibility.
10. A non-Nursing department user cannot access the Nursing workspace.
11. Multi-department users are evaluated using the active department.
12. Existing patient, visit, triage, consultation, treatment, task, and journey logic is reused.
13. Core clinical business logic is not duplicated.
14. Generic routes remain functional for other departments and integrations.
15. API, callback, signed, print, and export routes are not incorrectly redirected.
16. Active same-day OPD cases remain workable.
17. Completed same-day OPD sessions can be reopened by authorized users.
18. Previous-day OPD sessions require formal reopening before new clinical entries.
19. Patient privacy protections remain fully active.
20. Clinical safety restrictions remain fully active.
21. Relevant Nursing actions are audited.
22. English and French localisation are complete and in parity.
23. Focused Nursing workspace tests pass.
24. One broad relevant suite passes after all implementation phases are complete.
25. No broken links, route loops, duplicate route names, generic URL leaks, or inpatient workflow contamination remain.

---

# Deliverables

Provide:

1. Nursing workspace route group.
2. Nursing-specific controllers or thin adapters where required.
3. Nursing OPD dashboard.
4. Nursing department menu profile.
5. OPD nursing queue and worklists.
6. Nursing OPD case workspace.
7. Workspace-aware URL resolver updates.
8. Workspace-aware redirect resolver updates.
9. Updated shared links and forms.
10. Login and department-switch integration.
11. Nursing breadcrumbs and active-menu handling.
12. Nursing permission integration.
13. EN/FR localisation.
14. Focused feature tests.
15. A final implementation report containing:

* Files created
* Files modified
* Nursing route map
* Nursing menu map
* OPD queue categories
* Dashboard metrics
* Reused services
* Redirect behaviour
* Permissions used
* Session reopening behaviour
* Patient privacy checks
* Clinical safety checks
* Audit events
* Tests executed
* Test results
* Remaining limitations, if any

Implement the work fully.

Do not stop at planning, architecture documentation, route registration, or menu configuration alone. The final implementation must provide a functional and consistent Nursing OPD workspace throughout the supported patient workflow. Make sure to take into considaration other changes needed
