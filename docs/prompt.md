# UHMS Investigations Department Workspace — Diagnostic Workflow and `/investigations/*` Route Architecture

Implement a dedicated **Investigations Department Workspace** for UHMS.

This workspace is for staff managing laboratory and other non-radiology diagnostic investigations from request receipt through payment or authorization checks, specimen collection, accessioning, processing, result entry, verification, release, clinician review, critical-result escalation, and reporting.

This implementation should follow the same department-aware workspace architecture already established for:

* Doctors and consultation departments
* Records
* Nursing OPD
* Emergency
* Inpatient

The configured department type is:

```php
DepartmentType::INVESTIGATION
```

The browser workspace should use the plural URL prefix:

```text
/investigations/*
```

The objective is that when a logged-in user’s active department has:

```php
DepartmentType::INVESTIGATION
```

the user should experience UHMS as a dedicated Investigations application with:

* An Investigations-specific sidebar menu
* A diagnostic operations dashboard
* Investigation-specific breadcrumbs
* Investigation-specific route names
* Consistent `/investigations/*` URLs
* Request, specimen, processing, and result worklists
* Department and service-specific filtering
* Critical-result management
* Quality-control visibility where supported
* Payment and insurance authorization awareness
* Permission-controlled menu visibility
* Active-department scoping
* Patient privacy and result-security enforcement
* Reuse of existing consultation, investigation, billing, inventory, reporting, journey, and audit logic

Do not duplicate core investigation, billing, patient, consultation, specimen, inventory, result, or reporting logic merely to create the new workspace.

---

# 1. Core Functional Requirement

When the active department type is `investigation`, all supported browser pages used by investigation staff must appear under the `/investigations` URL prefix.

Examples:

```text
/investigations
/investigations/dashboard

/investigations/requests
/investigations/requests/pending
/investigations/requests/authorized
/investigations/requests/awaiting-payment
/investigations/requests/{request}

/investigations/specimens
/investigations/specimens/collection
/investigations/specimens/received
/investigations/specimens/rejected
/investigations/specimens/{specimen}

/investigations/worklist
/investigations/worklist/pending
/investigations/worklist/in-progress
/investigations/worklist/overdue

/investigations/results
/investigations/results/pending
/investigations/results/entered
/investigations/results/verification
/investigations/results/released
/investigations/results/critical
/investigations/results/{result}

/investigations/patients
/investigations/patients/{patient}

/investigations/services
/investigations/equipment
/investigations/quality-control
/investigations/consumables
/investigations/handoffs
/investigations/reports
```

An Investigations user should not enter through:

```text
/investigations/requests/{request}
```

and later be redirected to generic URLs such as:

```text
/investigation-requests/{request}
/lab/requests/{request}
/patients/{patient}
/visits/{visit}
/consultations/{consultation}
```

All browser navigation, forms, redirects, breadcrumbs, worklists, patient links, result actions, notifications, dashboard cards, and report drilldowns must preserve the Investigations workspace context.

---

# 2. Investigations Workspace Scope

The Investigations workspace is responsible for non-radiology diagnostic investigation workflows, including:

1. Receiving investigation requests
2. Validating requested services
3. Payment or insurance authorization awareness
4. Emergency and urgent request prioritization
5. Specimen collection
6. Specimen labeling
7. Specimen accessioning
8. Specimen receipt
9. Specimen rejection
10. Specimen recollection
11. Test worklist generation
12. Manual result entry
13. Numeric result entry
14. Free-text result entry
15. Boolean result entry
16. Positive/negative result entry
17. Instrument or analyzer result import where supported
18. Result verification
19. Result approval
20. Result release
21. Critical-result identification
22. Critical-result acknowledgement
23. Clinician notification
24. Result correction and amendment
25. Investigation cancellation
26. Repeat testing
27. Quality-control tracking where supported
28. Equipment and analyzer awareness
29. Consumable and reagent awareness
30. Investigation reports and operational analytics

This workspace should primarily cover laboratory and similar diagnostic investigations.

It must remain distinct from:

* Radiology
* Pharmacy
* Theatre
* General consultation
* Inpatient ward care
* Blood-bank operations where blood-bank workflows have their own dedicated department type

Shared services may be reused, but Radiology should retain its own future `/radiology/*` workspace.

---

# Phase 1 — Inspect the Existing Investigation Architecture

Before implementing, inspect the current codebase and identify:

1. How department-specific menus are selected.
2. How the existing department workspaces are registered.
3. How active department context is resolved.
4. How multi-department users switch active departments.
5. Existing routes, controllers, models, services, policies, jobs, commands, and views for:

   * investigation requests
   * investigation request items
   * services
   * service categories
   * laboratory departments or benches
   * specimen collection
   * specimen types
   * specimen accessioning
   * specimen receipt
   * specimen rejection
   * test processing
   * result entry
   * result verification
   * result release
   * result amendments
   * analyzer integration
   * patient and visit links
   * billing
   * insurance
   * stock and consumables
   * critical-result alerts
   * notifications
   * reporting
6. Existing investigation statuses.
7. Existing specimen statuses.
8. Existing result types and formats.
9. Existing result-validation rules.
10. Existing positive and negative result aggregation.
11. Existing critical-value configuration.
12. Existing service-to-department mapping.
13. Existing billing and payment-gate operations.
14. Existing Journey Intelligence stages for investigations.
15. Existing patient privacy and audit protections.
16. Existing views that hardcode generic routes such as:

```php
route('investigations.show', $request)
route('patients.show', $patient)
route('visits.show', $visit)
route('consultations.show', $consultation)
```

Do not create a competing investigation, specimen, result, menu, or routing framework where one already exists.

Extend the existing:

* Department dashboard registry
* Department menu profile service
* Active department context
* Workspace route resolver
* Workspace redirect resolver
* Investigation request services
* Specimen services
* Result-entry services
* Result-verification services
* Billing services
* Journey Intelligence services
* Notification infrastructure
* Authorization policies
* Patient privacy services
* Activity logging

---

# Phase 2 — Investigations Workspace Route Group

Create a dedicated Investigations route group.

Use a structure equivalent to:

```php
Route::prefix('investigations')
    ->name('investigations.')
    ->middleware([
        'auth',
        'verified',
        'department.context',
        'department.type:investigation',
    ])
    ->group(function () {
        // Investigations workspace routes
    });
```

Use the project’s actual middleware names and active-department authorization architecture.

Required route names should include, where the corresponding functionality already exists:

```text
investigations.dashboard

investigations.requests.index
investigations.requests.pending
investigations.requests.authorized
investigations.requests.awaiting_payment
investigations.requests.urgent
investigations.requests.overdue
investigations.requests.show
investigations.requests.accept
investigations.requests.cancel

investigations.specimens.index
investigations.specimens.collection
investigations.specimens.received
investigations.specimens.rejected
investigations.specimens.show
investigations.specimens.collect
investigations.specimens.receive
investigations.specimens.reject
investigations.specimens.recollect

investigations.worklist.index
investigations.worklist.pending
investigations.worklist.in_progress
investigations.worklist.overdue
investigations.worklist.completed

investigations.results.index
investigations.results.pending
investigations.results.entry
investigations.results.verification
investigations.results.released
investigations.results.critical
investigations.results.show
investigations.results.store
investigations.results.verify
investigations.results.release
investigations.results.amend

investigations.patients.index
investigations.patients.show

investigations.services.index
investigations.services.show

investigations.equipment.index
investigations.quality_control.index
investigations.consumables.index

investigations.handoffs.index
investigations.reports.index
```

Only register routes for functionality that exists or is implemented in this phase.

Do not create empty placeholder pages merely to populate the menu.

---

# Phase 3 — Investigations Operations Dashboard

Create or complete a dedicated Investigations dashboard.

The canonical destination should be:

```text
/investigations
```

or:

```text
/investigations/dashboard
```

Choose one canonical route and redirect the other to it.

The department dashboard resolver should map:

```php
DepartmentType::INVESTIGATION => 'investigations.dashboard'
```

The dashboard should function as an operational command board for diagnostic investigations.

Recommended metrics and widgets include, where reliable data exists:

* Requests received today
* Requests awaiting authorization
* Requests awaiting payment
* Emergency requests
* Urgent requests
* Specimens awaiting collection
* Specimens collected today
* Specimens awaiting receipt
* Rejected specimens
* Recollection required
* Tests pending
* Tests in progress
* Overdue investigations
* Results awaiting entry
* Results awaiting verification
* Results awaiting release
* Critical results
* Critical results awaiting acknowledgement
* Amended results today
* Completed investigations today
* Average request-to-collection time
* Average collection-to-result time
* Average turnaround time
* Tests breaching SLA
* Analyzer or equipment alerts where supported
* Low-stock investigation consumables where supported

Each dashboard metric must:

* Respect permissions
* Respect the active department
* Scope data to investigation services assigned to the active department
* Exclude Radiology services unless explicitly mapped to the Investigation department
* Avoid leaking protected patient information
* Link to `/investigations/*` routes
* Use safe empty states
* Avoid expensive unbounded queries
* Reuse Journey Intelligence and department metrics where applicable

Do not introduce metrics that cannot be calculated reliably.

---

# Phase 4 — Investigations-Specific Menu Profile

Extend the existing department menu profile or menu registry so that:

```php
DepartmentType::INVESTIGATION
```

receives a dedicated Investigations menu.

Recommended menu structure:

## Investigations Command

* Dashboard
* All Requests
* Emergency Requests
* Urgent Requests
* Overdue Investigations

## Request Management

* Pending Requests
* Authorized Requests
* Awaiting Payment
* Accepted Requests
* Cancelled Requests

## Specimen Management

* Collection Worklist
* Collected Specimens
* Awaiting Receipt
* Received Specimens
* Rejected Specimens
* Recollection Required

## Testing Worklist

* Pending Tests
* Tests in Progress
* Overdue Tests
* Completed Tests
* Assigned to Me
* Unassigned Tests

## Results

* Result Entry
* Awaiting Verification
* Awaiting Release
* Released Results
* Critical Results
* Amended Results

## Operations

* Investigation Services
* Equipment and Analyzers
* Quality Control
* Consumables and Reagents

## Coordination

* Handoffs
* Critical Alerts
* Escalations
* Pending Clinician Review

## Patient Access

* Patient Search
* Patient Profiles
* Investigation History
* Visit History

## Investigation Reports

* Request Volume Report
* Specimen Collection Report
* Rejected Specimen Report
* Turnaround-Time Report
* Positive and Negative Result Report
* Critical-Result Report
* Staff Activity Report
* Service Performance Report
* Analyzer Performance Report where supported
* Consumable Usage Report where supported

## General

* Notifications
* My Profile
* Switch Department

Only show a menu item when:

1. The feature exists.
2. The required module is enabled.
3. The user possesses the required permission.
4. The active department permits access.
5. The functionality belongs to the Investigation department.

Permissions remain authoritative.

Do not expose menu items solely because the active department type is `investigation`.

---

# Phase 5 — Investigation Request Worklists

Create or adapt investigation request worklists under:

```text
/investigations/requests
```

Recommended request categories include:

```text
new
awaiting_authorization
awaiting_payment
authorized
accepted
urgent
emergency
awaiting_specimen
in_progress
completed
cancelled
overdue
```

Use existing request, billing, insurance, specimen, result, and journey statuses.

Do not introduce duplicate statuses where the worklist state can be derived through a resolver.

Each worklist row should display only authorized information, such as:

* Patient identifier
* Patient name according to privacy rules
* Visit number
* Request number
* Requesting department
* Requesting clinician
* Requested service
* Priority
* Request date and time
* Payment or authorization state
* Specimen requirement
* Current investigation stage
* Assigned staff or bench
* SLA status
* Waiting duration
* Next required action

Support filters such as:

* Date
* Priority
* Requesting department
* Requesting clinician
* Investigation service
* Service category
* Specimen type
* Payment state
* Insurance state
* Request status
* Assigned staff
* SLA state

Use pagination and efficient queries.

---

# Phase 6 — Investigation Request Workspace

Create or adapt a dedicated investigation request workspace.

Recommended route:

```text
/investigations/requests/{request}
```

The request workspace should coordinate the full investigation lifecycle.

Recommended sections:

1. Patient identity strip
2. Visit details
3. Request details
4. Requesting clinician and department
5. Investigation services requested
6. Priority and SLA
7. Payment or insurance authorization status
8. Specimen requirements
9. Specimen collection details
10. Specimen acceptance or rejection history
11. Current processing stage
12. Assigned staff or laboratory section
13. Result-entry status
14. Verification status
15. Release status
16. Critical-result alerts
17. Clinician acknowledgement
18. Result amendment history
19. Billing references where authorized
20. Activity timeline
21. Authorized quick actions

Do not duplicate underlying request, specimen, result, billing, or notification implementations.

The workspace should act as a coordinated department-specific view of existing services.

---

# Phase 7 — Payment and Authorization Awareness

Investigation services may require payment, insurance approval, sponsorship, or an authorized exception.

Display safe operational states such as:

```text
authorized_to_proceed
payment_required
payment_pending
insurance_pending
insurance_approved
sponsor_approved
emergency_override
authorized_override
billing_context_missing
```

Reuse existing:

* Payment-gate operation policy
* Visit billing overrides
* Insurance coverage services
* Sponsor authorization
* Invoice receivables
* Emergency exceptions
* Activity logging

Do not expose unnecessary financial information to Investigation users without finance permissions.

Do not hardcode a universal payment bypass.

Use operation-specific policy for actions such as:

* Accept request
* Collect specimen
* Start processing
* Release result

Emergency stabilization-related investigations may proceed through the configured emergency policy.

Any override must be:

* Explicit
* Permission-controlled
* Reasoned
* Visit-scoped or request-scoped
* Audited

---

# Phase 8 — Specimen Collection Workflow

Expose specimen collection under:

```text
/investigations/specimens/collection
```

Reuse existing specimen and request services.

The collection worklist should support:

* Patient identification
* Request identification
* Required specimen type
* Collection container
* Collection instructions
* Fasting or preparation requirements
* Priority
* Collection status
* Assigned collector
* Collection time
* Barcode or accession number where supported
* Special precautions

Authorized users may:

* Confirm patient identity
* Record specimen collection
* Record collection date and time
* Record collector
* Print or confirm labels
* Record collection notes
* Mark collection unsuccessful
* Request recollection
* Record refusal where appropriate

Specimen collection must not create duplicate request items.

Collection actions must remain under `/investigations/*`.

---

# Phase 9 — Specimen Accessioning and Receipt

Expose specimen receipt and accessioning inside the Investigations workspace.

Recommended routes:

```text
/investigations/specimens/received
/investigations/specimens/{specimen}
```

The workflow should support:

* Accession number
* Barcode
* Collection time
* Receipt time
* Transport duration
* Specimen condition
* Specimen quantity
* Container type
* Temperature requirement where supported
* Receiving staff
* Laboratory section
* Acceptance status
* Rejection reason
* Recollection requirement

A specimen should not silently move into processing without the required acceptance state.

Receipt and accessioning must be transactional and audited.

---

# Phase 10 — Specimen Rejection and Recollection

Support structured specimen rejection.

Potential rejection reasons may include:

```text
wrong_patient
unlabelled
mislabelled
insufficient_quantity
wrong_container
haemolysed
clotted
leaking
contaminated
delayed_transport
temperature_breach
duplicate_specimen
invalid_collection
other
```

Use existing configured reasons where available.

A rejection should record:

* Reason
* Notes
* Rejecting staff
* Date and time
* Whether recollection is required
* Notification destination
* Request and patient context

The system should:

1. Preserve the rejected specimen record.
2. Mark the affected test as blocked or recollection required.
3. Create or expose a recollection action.
4. Notify the requesting clinical area where supported.
5. Audit the rejection.
6. Avoid silently cancelling the entire request if unaffected items can continue.

Do not overwrite the original specimen state.

---

# Phase 11 — Testing Worklist

Expose the testing worklist under:

```text
/investigations/worklist
```

The worklist should group tests by their operational area where supported, such as:

* Haematology
* Chemistry
* Microbiology
* Serology
* Parasitology
* Histology
* Immunology
* Molecular diagnostics
* Other configured investigation sections

Use existing department, service-category, bench, or laboratory-section mappings.

Recommended worklist states include:

```text
pending
assigned
in_progress
paused
awaiting_repeat
awaiting_quality_control
completed
cancelled
overdue
```

Each worklist item may display:

* Request number
* Patient identifier
* Service
* Specimen
* Priority
* Received time
* Assigned staff
* Equipment or analyzer
* Current state
* SLA status
* Result-entry status
* Quality-control blocker
* Next action

Authorized users may:

* Claim
* Assign
* Start processing
* Pause with reason
* Mark processing complete
* Request repeat testing
* Escalate delay
* View test history

Do not treat result release as equivalent to processing completion.

---

# Phase 12 — Result Format Support

Preserve and extend the existing investigation result formats.

Each investigation service should use its configured result type.

Supported formats may include:

```text
free_text
numeric
boolean
positive_negative
structured
```

Where existing functionality supports them, also preserve:

```text
select
multi_select
range
ratio
date
time
organism_sensitivity
```

Do not force all investigation results into a free-text field.

## Numeric Results

Support:

* Numeric value
* Unit
* Reference range
* High or low indicator
* Critical indicator
* Decimal precision
* Age-specific range where supported
* Sex-specific range where supported

## Boolean Results

Support:

```text
true
false
```

Display localized clinical labels configured for the service.

## Positive/Negative Results

Support:

```text
positive
negative
indeterminate
equivocal
```

where configured.

Positive and negative values must remain structured so they can be used for monthly and service-level statistics.

## Free-Text Results

Support free-text findings while preserving:

* Responsible staff
* Entry time
* Verification state
* Amendment history

## Structured Results

Reuse existing result schemas where a test has multiple analytes or fields.

Do not duplicate investigation definitions inside result records.

---

# Phase 13 — Result Entry

Expose result entry under:

```text
/investigations/results/entry
```

Result entry should:

1. Use the configured result format.
2. Validate required fields.
3. Validate numeric ranges and units.
4. Detect abnormal and critical values.
5. Record the responsible user.
6. Record the entry time.
7. Preserve analyzer origin where applicable.
8. Support draft state where currently allowed.
9. Prevent unauthorized release.
10. Preserve amendment history.

Potential result states include:

```text
not_entered
draft
entered
awaiting_verification
verified
released
amended
cancelled
```

Do not allow released results to be overwritten directly.

Corrections must use the result-amendment workflow.

---

# Phase 14 — Result Verification and Approval

Expose result verification under:

```text
/investigations/results/verification
```

Verification should confirm:

* Patient and request
* Investigation service
* Specimen suitability
* Result completeness
* Units
* Reference ranges
* Abnormal indicators
* Critical indicators
* Quality-control status where relevant
* Analyzer flags where relevant
* Previous related results where permitted

Use the existing authorization and professional-role model.

The same user should not perform entry and verification where the current configuration requires separation of duties.

Support configuration for:

* Entry-only users
* Verifiers
* Approvers
* Auto-verification for eligible analyzer results
* Mandatory manual verification for critical results

Verification must be audited.

---

# Phase 15 — Result Release

Expose releasable results under:

```text
/investigations/results
```

Result release should:

1. Require verification where configured.
2. Check critical-result handling requirements.
3. Record the releasing user.
4. Record release date and time.
5. Make results visible to authorized clinicians.
6. Update request and journey state.
7. Trigger configured notifications.
8. Preserve patient privacy.
9. Preserve audit history.

Do not mark the entire investigation request complete if some request items remain incomplete.

Use item-level completion where the existing architecture supports multi-item requests.

---

# Phase 16 — Critical Results

Expose critical results under:

```text
/investigations/results/critical
```

Critical-result logic should use a centralized configuration or service.

A critical result should record:

* Investigation service
* Result
* Critical rule triggered
* Patient and visit
* Requesting clinician
* Requesting department
* Detection time
* Responsible Investigation staff
* Notification attempt
* Recipient
* Acknowledgement
* Acknowledgement time
* Escalation state
* Resolution state

The workflow should support:

* Identify
* Confirm
* Notify
* Escalate
* Acknowledge
* Resolve

Critical results must remain highly visible until acknowledged or resolved according to the configured workflow.

Do not expose full result details in notifications where privacy controls prohibit it.

Reuse the existing Journey Intelligence and notification infrastructure where appropriate.

---

# Phase 17 — Result Amendments and Corrections

Released results must not be edited directly.

Provide a formal amendment workflow.

An amendment should record:

* Original result
* Corrected result
* Amendment reason
* Amending user
* Amendment date and time
* Verification
* Release
* Notification status
* Whether clinical acknowledgement is required

The system must:

1. Preserve the original released result.
2. Display the amendment history.
3. Mark the current result as amended.
4. Notify authorized clinical recipients where configured.
5. Audit the amendment.
6. Avoid altering historical reports silently.

An amendment should not create an unrelated duplicate request.

---

# Phase 18 — Analyzer and Equipment Integration

Where analyzer integration already exists, expose operational visibility in the Investigations workspace.

Support existing HL7, ASTM, file-import, serial, network, or middleware integrations where applicable.

The workspace may show:

* Connected analyzers
* Connection state
* Last successful communication
* Pending result imports
* Import failures
* Unmatched results
* Analyzer flags
* Equipment maintenance state
* Quality-control blockers

Do not rewrite established analyzer communication services solely for the workspace.

Analyzer results must pass through:

* Patient and request matching
* Service matching
* Validation
* Critical-value detection
* Verification policy
* Audit logging

Do not auto-release unmatched or invalid results.

---

# Phase 19 — Quality Control

Where quality-control functionality already exists, expose it under:

```text
/investigations/quality-control
```

Potential visibility may include:

* Control run status
* Control material
* Lot number
* Expected range
* Recorded value
* Pass or fail
* Equipment
* Investigation service
* Responsible user
* Date and time
* Corrective action

If a failed quality-control state should block patient-result processing or release, enforce that rule centrally.

Do not create fake quality-control functionality merely to populate the menu.

Where no quality-control model exists, omit the menu item and document it as a future limitation.

---

# Phase 20 — Consumables and Reagents

Where stock integration already exists, expose investigation-relevant consumable awareness.

Recommended route:

```text
/investigations/consumables
```

The workspace may show:

* Reagents
* Test kits
* Collection tubes
* Slides
* Containers
* Controls
* Consumables
* Available quantity
* Reorder level
* Expiry date
* Lot number
* Storage location
* Stockout state

Reuse the existing Stores and inventory services.

Do not create a second stock ledger for Investigations.

Investigation staff should only have access to stock actions allowed by their permissions.

---

# Phase 21 — Investigation Service Configuration Awareness

Expose an investigation service list under:

```text
/investigations/services
```

The operational view may show:

* Service name
* Service code
* Category
* Department
* Result format
* Specimen requirement
* Normal turnaround time
* Emergency turnaround time
* Reference range availability
* Critical-value configuration
* Billing mapping
* Active or inactive state

This operational view should not automatically grant permission to edit service definitions.

Service configuration should remain under the existing administrative permission model.

---

# Phase 22 — Department and Section Scoping

All Investigations worklists must respect the active department.

Where a hospital has multiple Investigation departments or sections, such as:

* Main laboratory
* Emergency laboratory
* Haematology
* Chemistry
* Microbiology
* Satellite laboratory

the workspace must scope data through:

* Active department
* Service-to-department mapping
* Laboratory section
* User assignment
* Authorized cross-department access

Do not rely only on a broad service type of `investigation`.

A user should not see every hospital investigation merely because their active department type is `investigation`.

Use active `department_id` scoping where applicable.

---

# Phase 23 — Handoffs and Coordination

Expose Investigation handoffs under:

```text
/investigations/handoffs
```

Reuse the existing Journey Intelligence handoff infrastructure.

Support handoffs such as:

* Specimen collection requested
* Recollection required
* Critical result notification
* Result awaiting clinician review
* Investigation delayed
* Equipment failure escalation
* Test referred externally
* Investigation blocking discharge
* Investigation blocking procedure

The worklist should display:

* Patient and visit context
* Request
* Sending department
* Receiving department
* Required action
* Priority
* Due time
* SLA state
* Acknowledgement
* Resolution

Authorized users may:

* Claim
* Assign
* Acknowledge
* Resolve
* Escalate
* Reassign

All links should preserve `/investigations/*` context where an Investigation workspace destination exists.

---

# Phase 24 — Workspace-Aware URL Resolution

Extend the centralized workspace route resolver.

Do not scatter checks such as:

```php
if ($department->type === DepartmentType::INVESTIGATION) {
    return route('investigations.requests.show', $request);
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

requestIndex()
requestShow(InvestigationRequest $request)

specimenCollection()
specimenShow(Specimen $specimen)

worklist()
resultEntry()
resultShow(InvestigationResult $result)
criticalResultList()

patientIndex()
patientShow(Patient $patient)

serviceIndex()
equipmentIndex()
qualityControlIndex()
consumableIndex()
handoffIndex()
reportIndex()
```

For an Investigations user, the resolver must return `investigations.*` routes.

For other users, preserve the appropriate existing workspace or generic route.

Always use the active department context rather than only the user’s primary department.

---

# Phase 25 — Replace Hardcoded Shared Links

Audit all shared pages accessed by Investigation users.

Replace hardcoded generic links that break workspace continuity.

Review at minimum:

* Investigation request lists
* Specimen worklists
* Result-entry pages
* Verification pages
* Released-result pages
* Critical-result pages
* Patient search
* Patient profiles
* Visit history
* Consultation investigation sections
* Journey worklists
* Dashboard cards
* Breadcrumbs
* Notifications
* Escalation links
* Action dropdowns
* Empty-state actions
* Report drilldowns
* Flash-message action links

Avoid shared-view code such as:

```php
route('investigations.show', $request)
```

Use the centralized workspace route resolver.

Do not alter API, analyzer, callback, signed, payment, print, export, or background-job URLs unless they are explicitly part of the Investigations browser workspace.

---

# Phase 26 — Workspace-Aware Redirects

All successful Investigations actions must redirect back into `/investigations/*`.

Examples:

After accepting a request:

```text
/investigations/requests/{request}
```

After collecting a specimen:

```text
/investigations/specimens/{specimen}
```

After rejecting a specimen:

```text
/investigations/specimens/rejected
```

After entering a result:

```text
/investigations/results/verification
```

After verifying a result:

```text
/investigations/results/{result}
```

After releasing a result:

```text
/investigations/requests/{request}
```

After amending a result:

```text
/investigations/results/{result}
```

Avoid hardcoding Investigation redirects inside domain services.

Use a workspace redirect resolver such as:

```php
$workspaceRedirects->toInvestigationRequest($request);
$workspaceRedirects->toSpecimen($specimen);
$workspaceRedirects->toResult($result);
$workspaceRedirects->toVerificationQueue();
$workspaceRedirects->toInvestigationDashboard();
```

Validation failures must return the user to the same `/investigations/*` route with input preserved.

---

# Phase 27 — Login and Department Switching

When a user logs in and their active department type is `investigation`, redirect them to:

```text
/investigations
```

When a multi-department user switches to an Investigation department, redirect them to:

```text
/investigations
```

When switching away from Investigations, redirect to the selected department’s appropriate workspace.

The menu, dashboard, route context, and data scoping must always use the active department.

Do not rely only on:

```php
$user->department_id
```

where the application supports multiple departments.

---

# Phase 28 — Investigations Workspace Authorization

The `/investigations` prefix is not authorization.

Protect the workspace so access requires:

1. An authenticated user.
2. A valid active department.
3. Active department type equal to `investigation`, unless authorized admin preview applies.
4. The required permission.
5. The relevant module being enabled.
6. Access to the requested patient, visit, request, specimen, or result.
7. Active-department or laboratory-section assignment where required.

A user from another department who manually enters:

```text
/investigations/requests
```

must not receive access merely because they possess a broad investigation-view permission.

Use the project’s existing unauthorized workspace behaviour:

* `403`
* workspace unavailable page
* safe redirect

Do not create inconsistent authorization behaviour.

---

# Phase 29 — Permission Model

Reuse existing permissions wherever possible.

Only add permissions where the current model does not represent the required action.

Potential permissions may include:

```text
investigations.workspace.view
investigations.requests.view
investigations.requests.manage
investigations.requests.accept
investigations.requests.cancel

investigations.specimens.view
investigations.specimens.collect
investigations.specimens.receive
investigations.specimens.reject
investigations.specimens.recollect

investigations.worklist.view
investigations.worklist.manage

investigations.results.view
investigations.results.enter
investigations.results.verify
investigations.results.release
investigations.results.amend

investigations.critical_results.view
investigations.critical_results.manage

investigations.services.view
investigations.equipment.view
investigations.quality_control.view
investigations.quality_control.manage
investigations.consumables.view

investigations.handoffs.view
investigations.handoffs.manage
investigations.reports.view
investigations.overrides.manage
```

Inspect current permission names before adding new permissions.

Avoid duplicating equivalent permissions.

Menu visibility must follow permissions, but controllers, policies, form requests, and services must independently enforce authorization.

---

# Phase 30 — Legacy Route Compatibility

Keep existing generic investigation routes operational for:

* Other departments
* Clinician result viewing
* Existing bookmarks
* APIs
* Analyzer integrations
* Print flows
* Signed URLs
* Internal notifications
* Background jobs
* Payment callbacks
* Export downloads

For interactive browser requests from an active Investigation department, generic routes may redirect to Investigation equivalents where safe.

Examples:

```text
/investigation-requests/{request}
→ /investigations/requests/{request}

/lab/results/{result}
→ /investigations/results/{result}

/specimens/{specimen}
→ /investigations/specimens/{specimen}
```

Do not blindly redirect:

* JSON requests
* APIs
* analyzer callbacks
* signed URLs
* payment callbacks
* print routes
* exports
* background requests
* integration requests

Avoid redirect loops.

---

# Phase 31 — Breadcrumbs and Active Menu State

Investigations pages must display Investigation-specific breadcrumbs.

Examples:

```text
Investigations > Dashboard
Investigations > Requests
Investigations > Requests > Request Details
Investigations > Specimen Collection
Investigations > Specimens > Specimen Details
Investigations > Testing Worklist
Investigations > Result Entry
Investigations > Verification
Investigations > Critical Results
Investigations > Quality Control
Investigations > Reports
```

The sidebar must correctly highlight parent items for nested routes.

For example:

```text
investigations.requests.show
investigations.specimens.show
investigations.results.show
```

should highlight the appropriate parent menu item.

Use active-route patterns rather than exact route-name equality only.

---

# Phase 32 — Shared View Workspace Context

Pass a clear Investigations workspace context to shared views.

The context may include:

```php
[
    'workspaceKey' => 'investigations',
    'workspaceDepartment' => $activeDepartment,
    'workspaceRoutePrefix' => 'investigations.',
    'workspaceTitle' => __('investigations.workspace.title'),
    'workspaceScope' => 'diagnostic_investigation',
]
```

Use an existing DTO or view-context object where available.

Shared views should use this context for:

* Page headings
* Breadcrumbs
* Links
* Form actions
* Back buttons
* Request navigation
* Specimen navigation
* Result navigation
* Quick actions
* Empty states
* Notifications

Do not repeatedly inspect session state or department type inside Blade templates.

---

# Phase 33 — Patient Privacy and Result Security

The Investigations workspace handles highly sensitive patient and diagnostic information.

Ensure all existing privacy controls remain active, including:

* Patient-name masking where applicable
* Protected phone and email fields
* Sensitive-field permission checks
* Access auditing
* Search-result masking
* Export restrictions
* Secure patient, visit, request, specimen, and result lookup
* Privacy-aware notifications
* Activity-log sanitization

Result visibility must remain permission-controlled.

Do not expose unreleased results to users who only possess released-result access.

Do not expose:

* Draft results
* Unverified results
* Internal laboratory comments
* Quality-control notes
* Analyzer raw messages

to unauthorized clinical users.

Critical-result notifications must avoid unnecessary sensitive details.

---

# Phase 34 — Clinical and Operational Safety

Preserve existing clinical and operational safeguards, including:

* Correct patient identification
* Correct specimen identification
* Duplicate-request checks
* Specimen suitability rules
* Critical-result detection
* Result-range validation
* Unit validation
* Result verification
* Amendment history
* Analyzer matching
* Quality-control blocking
* Permission-controlled release
* Audit trails

Do not allow:

* Result release without required verification
* Direct editing of released results
* Silent replacement of rejected specimens
* Silent cancellation of pending request items
* Unmatched analyzer results to be released
* Critical results to disappear without acknowledgement
* Unauthorized users to amend results

Overrides must be explicit, permission-controlled, reasoned, and audited.

---

# Phase 35 — Activity Logging and Audit

Record relevant Investigation actions through the existing `ActivityLog` infrastructure.

Audit events should cover actions such as:

* Request accepted
* Request cancelled
* Payment or authorization override applied
* Specimen collected
* Specimen received
* Specimen rejected
* Recollection requested
* Test assigned
* Test processing started
* Test processing completed
* Result entered
* Result corrected before release
* Result verified
* Result released
* Critical result detected
* Critical result notification recorded
* Critical result acknowledged
* Result amended
* Analyzer result imported
* Analyzer result unmatched
* Quality-control failure recorded
* Investigation handoff acknowledged
* Investigation handoff resolved

Do not log full sensitive result values where the audit policy prohibits them.

Audit records should include sufficient context such as:

* Actor
* Patient identifier
* Visit identifier
* Request identifier
* Specimen identifier
* Result identifier
* Department
* Action
* Timestamp
* Reason where required
* Previous and new state where appropriate

---

# Phase 36 — Localization

Add complete English and French localization for the Investigations workspace.

Prefer an existing investigation localization file if one exists, otherwise use:

```text
lang/en/investigations.php
lang/fr/investigations.php
```

Include keys for:

* Workspace title
* Dashboard
* Menu sections
* Request states
* Payment and authorization states
* Specimen states
* Specimen rejection reasons
* Testing worklist states
* Result formats
* Result states
* Verification
* Release
* Critical results
* Amendments
* Analyzer and equipment states
* Quality-control states
* Consumables
* Handoffs
* Empty states
* Quick actions
* Reports
* Breadcrumbs
* Unauthorized workspace message
* Override reasons

Maintain complete English and French parity.

Do not hardcode visible Investigation labels in controllers, services, Blade templates, or JavaScript.

---

# Phase 37 — Investigation Reports and Statistics

Create or adapt Investigation reports under:

```text
/investigations/reports
```

Recommended reports include:

* Request volume by date
* Request volume by service
* Request volume by department
* Request volume by clinician
* Emergency and urgent request report
* Specimen collection report
* Rejected specimen report
* Recollection report
* Test turnaround-time report
* SLA breach report
* Result completion report
* Critical-result report
* Result amendment report
* Staff activity report
* Equipment performance report where supported
* Consumable usage report where supported

Preserve structured statistics for positive and negative results.

Monthly and period-based reporting should be able to calculate:

* Total tests
* Positive results
* Negative results
* Indeterminate results
* Positivity rate
* Result counts by service
* Result counts by department
* Result counts by patient category where authorized

Do not attempt to derive positive or negative statistics from unstructured free text.

Only configured positive/negative result fields should contribute to those statistics.

Reports must respect:

* Permissions
* Active department
* Patient privacy
* Aggregation thresholds where applicable
* Export permissions

---

# Phase 38 — Menu Configuration and Future Extensibility

Implement the Investigations menu through the existing menu registry or department menu profile service.

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
* Active department scoping
* Laboratory-section scoping
* Feature flags
* Emergency-request counts
* Critical-result counts
* Overdue-test counts
* Specimen-rejection counts

The architecture must remain extensible for future department menu personalization, including:

```text
radiology
pharmacy
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

# Phase 39 — Focused Automated Verification

Add focused automated tests for the Investigations workspace.

## Route tests

Verify:

* Investigation routes exist.
* Route names use `investigations.*`.
* URLs use `/investigations/*`.
* Investigation department middleware is attached.
* Generic routes remain available where required.

## Access tests

Verify:

* An Investigation department user can access authorized Investigation pages.
* A non-Investigation department user cannot access the workspace.
* Users without the required permission cannot access protected actions.
* Admin preview continues working where supported.
* Multi-department active context is respected.
* Active `department_id` scoping is respected.

## Dashboard tests

Verify:

* The Investigations dashboard loads.
* Metrics include only services mapped to the active Investigation department.
* Radiology services do not leak into the workspace incorrectly.
* Critical-result and overdue counts are accurate.
* Links point to `/investigations/*`.
* Sensitive information remains protected.
* Empty states render safely.

## Request tests

Verify:

* Requests appear in the correct worklist.
* Payment and authorization states are reflected correctly.
* Emergency and urgent requests are prioritized.
* Multi-item requests retain item-level states.
* Cancelling one item does not incorrectly cancel unrelated items.

## Specimen tests

Verify:

* Specimen collection uses the existing request.
* Duplicate specimen records are not created incorrectly.
* Receipt and accessioning are audited.
* Rejection preserves the original specimen.
* Recollection is created or exposed correctly.
* Unaffected request items remain processable.

## Result-format tests

Verify:

* Free-text results save correctly.
* Numeric results validate values, units, and ranges.
* Boolean results remain structured.
* Positive and negative results remain structured.
* Positive and negative statistics calculate correctly.
* Structured result schemas remain supported.

## Verification and release tests

Verify:

* Results requiring verification cannot be released early.
* Unauthorized users cannot verify or release results.
* Critical results follow the required workflow.
* Multi-item requests complete only when all required items are complete.
* Released results become visible to authorized clinicians.

## Amendment tests

Verify:

* Released results cannot be overwritten directly.
* Amendments preserve the original result.
* Amendments require a reason.
* Amendments are verified and released correctly.
* Amendment actions are audited.

## Analyzer tests

Where analyzer integration exists, verify:

* Matched results import correctly.
* Unmatched results do not auto-release.
* Invalid results are blocked.
* Critical-result detection still runs.
* Analyzer imports are audited.

## Redirect tests

Verify:

* Login redirects to `/investigations`.
* Switching to an Investigation department redirects to `/investigations`.
* Request actions remain under `/investigations/*`.
* Specimen actions remain under `/investigations/*`.
* Result-entry, verification, release, and amendment actions remain under `/investigations/*`.
* No redirect loops occur.
* JSON, analyzer, API, export, print, signed, and callback requests are not incorrectly redirected.

## Privacy and audit tests

Verify:

* Patient masking remains active.
* Protected fields require permission.
* Draft and unverified results are not exposed to unauthorized users.
* Investigation actions generate required audit records.
* Sensitive result values are not exposed through alternate views.

Run focused Investigations workspace tests and essential route, view, localization, billing, integration, and audit checks during implementation.

Do not run the full UHMS suite after each phase.

Run one broad relevant suite after all Investigations workspace phases are complete.

---

# Phase 40 — Manual Acceptance Scenarios

## Scenario A — Investigation login

1. Log in as a user whose active department type is `investigation`.
2. Confirm the landing URL is `/investigations`.
3. Confirm the Investigations menu is displayed.
4. Confirm unrelated department menus are absent.

## Scenario B — Request receipt

1. Open the pending request worklist.
2. Select a new request.
3. Confirm payment or authorization state is visible.
4. Accept the request.
5. Confirm the redirect remains under `/investigations/*`.
6. Confirm the action is audited.

## Scenario C — Specimen collection

1. Open the collection worklist.
2. Select a patient.
3. Record specimen collection.
4. Confirm labeling and specimen type.
5. Confirm the specimen appears in the receipt worklist.
6. Confirm all URLs remain under `/investigations/*`.

## Scenario D — Specimen rejection

1. Open a received specimen.
2. Reject it with a structured reason.
3. Confirm the original specimen remains preserved.
4. Confirm recollection is required.
5. Confirm the requesting department is notified where supported.
6. Confirm the action is audited.

## Scenario E — Numeric result

1. Open a numeric test.
2. Enter a result and unit.
3. Confirm reference range and abnormal indicators.
4. Submit for verification.
5. Verify and release the result.
6. Confirm the result appears in the clinician-facing workflow.

## Scenario F — Positive/negative result

1. Open a positive/negative investigation.
2. Record a positive result.
3. Verify and release it.
4. Confirm the structured value is preserved.
5. Confirm the report statistics count it as positive.

## Scenario G — Critical result

1. Enter a value that triggers a critical rule.
2. Confirm the result appears in the critical worklist.
3. Record notification to the clinician.
4. Confirm acknowledgement and escalation states.
5. Confirm the result remains visible until appropriately resolved.

## Scenario H — Result amendment

1. Open a released result.
2. Attempt direct editing.
3. Confirm direct editing is blocked.
4. Start an amendment.
5. Enter the reason and corrected result.
6. Verify and release the amendment.
7. Confirm the original result remains visible in history.

## Scenario I — Multi-item request

1. Open a request containing several investigation items.
2. Complete only one item.
3. Confirm the request does not become fully completed.
4. Complete the remaining items.
5. Confirm the request then reaches its final state.

## Scenario J — Active department scoping

1. Use a user assigned to multiple Investigation departments.
2. Switch the active department.
3. Confirm the worklists change to the selected department.
4. Confirm requests from unauthorized sections are not displayed.

## Scenario K — Permission control

1. Remove result-verification permission.
2. Confirm the verification menu item disappears.
3. Enter the verification route directly.
4. Confirm access is denied.

## Scenario L — Legacy compatibility

1. Enter a generic investigation route as an Investigation user.
2. Confirm it safely resolves or redirects to the Investigations equivalent where configured.
3. Confirm APIs, analyzer integrations, signed URLs, callbacks, print routes, and exports remain unaffected.

---

# Acceptance Criteria

The implementation is accepted only when all the following are true:

1. Users with an active department type of `investigation` receive a dedicated Investigations menu.
2. Their default dashboard uses `/investigations`.
3. Supported investigation pages use `/investigations/*` URLs.
4. Route names use the `investigations.*` namespace.
5. Worklists are scoped to the active Investigation department.
6. Radiology requests do not incorrectly appear unless explicitly mapped.
7. Forms submit through Investigation routes.
8. Redirects remain inside the Investigations workspace.
9. Breadcrumbs and active menu states are Investigation-aware.
10. Permissions and enabled modules control menu visibility.
11. A non-Investigation department user cannot access the workspace.
12. Multi-department users are evaluated using the active department.
13. Existing request, specimen, result, billing, insurance, inventory, journey, integration, reporting, and audit logic is reused.
14. Core investigation and billing logic is not duplicated.
15. Specimen collection, receipt, rejection, and recollection are fully tracked.
16. Result formats remain structured and service-configurable.
17. Numeric results preserve units and reference ranges.
18. Positive and negative results remain usable for monthly statistics.
19. Results requiring verification cannot be released early.
20. Critical results use a visible acknowledgement and escalation workflow.
21. Released results cannot be overwritten directly.
22. Result amendments preserve the original result and audit history.
23. Analyzer results cannot bypass matching, validation, verification, or critical-result checks.
24. Generic routes remain functional for other departments and integrations.
25. APIs, analyzer callbacks, signed URLs, print routes, exports, and payment callbacks are not incorrectly redirected.
26. Patient privacy and result-security protections remain fully active.
27. Relevant Investigation actions are audited.
28. English and French localization are complete and in parity.
29. Focused Investigations workspace tests pass.
30. One broad relevant suite passes after all phases are complete.
31. No broken links, route loops, duplicate route names, incorrect department leakage, silent result replacement, or Radiology workflow contamination remain.

---

# Deliverables

Provide:

1. Investigations workspace route group.
2. Investigation-specific controllers or thin adapters where required.
3. Investigations operations dashboard.
4. Investigation department menu profile.
5. Request worklists.
6. Investigation request workspace.
7. Payment and authorization integration.
8. Specimen collection workflow.
9. Specimen receipt and accessioning.
10. Specimen rejection and recollection.
11. Testing worklists.
12. Result-format integration.
13. Result-entry workflow.
14. Verification and release workflow.
15. Critical-result workflow.
16. Result-amendment workflow.
17. Analyzer and equipment integration where supported.
18. Quality-control integration where supported.
19. Consumable and reagent visibility.
20. Positive and negative result reporting.
21. Workspace-aware URL resolver updates.
22. Workspace-aware redirect resolver updates.
23. Updated shared links and forms.
24. Login and department-switch integration.
25. Investigation breadcrumbs and active-menu handling.
26. Permission integration.
27. Patient privacy and result-security integration.
28. English and French localization.
29. Focused feature tests.
30. A final implementation report containing:

* Files created
* Files modified
* Investigation route map
* Investigation menu map
* Request worklist categories
* Dashboard metrics
* Specimen workflow
* Result formats
* Verification and release behaviour
* Critical-result behaviour
* Result-amendment behaviour
* Analyzer integration behaviour
* Positive and negative reporting behaviour
* Active department scoping
* Reused services
* Redirect behaviour
* Permissions used
* Patient privacy checks
* Result-security checks
* Audit events
* Tests executed
* Test results
* Remaining limitations, if any

Implement the work fully.

Do not stop at planning, route registration, menu configuration, dashboard layout, or request listing alone. The final implementation must provide a functional, secure, department-specific Investigations workspace throughout the complete diagnostic investigation lifecycle.
