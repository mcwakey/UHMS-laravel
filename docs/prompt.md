# UHMS Administrative Department Workspace — Hospital Operations, Governance, Oversight, and `/administrative/*` Route Architecture

Implement a dedicated **Administrative Department Workspace** for UHMS.

This workspace is for hospital administrators, management personnel, departmental coordinators, executive officers, compliance staff, and other authorized administrative users responsible for coordinating hospital operations and overseeing departments.

This implementation should follow the same department-aware workspace architecture already established for:

* Doctors and consultation departments
* Records
* Nursing OPD
* Emergency
* Inpatient
* Investigations
* Pharmacy
* Stores
* Maternity
* Finance

The configured department type is:

```php
DepartmentType::ADMINISTRATIVE
```

The browser workspace must use:

```text
/administrative/*
```

The objective is that when a logged-in user’s active department has:

```php
DepartmentType::ADMINISTRATIVE
```

the user should experience UHMS as a dedicated Administrative application with:

* An Administrative-specific sidebar menu
* A hospital operations dashboard
* Administrative-specific breadcrumbs
* Administrative-specific route names
* Consistent `/administrative/*` URLs
* Departmental oversight
* Operational approval worklists
* Hospital activity summaries
* Staff and department coordination
* Policy and procedure management
* Compliance and audit follow-up
* Incident and complaint management
* Meeting, committee, and action tracking
* Announcements and internal communication
* Document and correspondence tracking
* Facility and service availability awareness
* Executive and operational reporting
* Permission-controlled menu visibility
* Active-department and facility scoping
* Separation between administrative oversight and specialist department operations
* Reuse of existing user, department, workflow, reporting, audit, HR, Finance, Stores, and clinical services

Do not duplicate core clinical, Finance, HR, Records, Stores, billing, admission, patient, user-management, reporting, or audit logic merely to create the Administrative workspace.

---

# 1. Core Functional Requirement

When the active department type is `administrative`, all supported browser pages used by administrative staff must appear under the `/administrative` URL prefix.

Examples:

```text
/administrative
/administrative/dashboard

/administrative/departments
/administrative/departments/{department}
/administrative/departments/{department}/performance

/administrative/operations
/administrative/operations/worklist
/administrative/operations/exceptions
/administrative/operations/escalations

/administrative/approvals
/administrative/approvals/pending
/administrative/approvals/completed
/administrative/approvals/{approval}

/administrative/users
/administrative/users/{user}
/administrative/staff
/administrative/staff/{staff}

/administrative/schedules
/administrative/duty-rosters
/administrative/coverage

/administrative/policies
/administrative/policies/{policy}
/administrative/procedures
/administrative/guidelines

/administrative/documents
/administrative/correspondence
/administrative/memos
/administrative/announcements

/administrative/incidents
/administrative/incidents/{incident}
/administrative/complaints
/administrative/complaints/{complaint}

/administrative/compliance
/administrative/audits
/administrative/audits/{audit}
/administrative/findings
/administrative/risk-register

/administrative/meetings
/administrative/committees
/administrative/action-items

/administrative/facilities
/administrative/service-availability
/administrative/department-status

/administrative/handoffs
/administrative/reports
```

An Administrative user should not enter through:

```text
/administrative/departments/{department}
```

and later be redirected to generic URLs such as:

```text
/departments/{department}
/admin/users/{user}
/approvals/{approval}
/incidents/{incident}
/reports/operations
```

All browser navigation, forms, redirects, breadcrumbs, dashboard links, worklists, approval actions, notifications, report drilldowns, and administrative records must preserve the Administrative workspace context.

---

# 2. Administrative Workspace Scope

The Administrative workspace is responsible for hospital administration and operational oversight, including:

1. Departmental oversight
2. Hospital operations monitoring
3. Service availability monitoring
4. Department performance summaries
5. Operational exceptions
6. Administrative escalation
7. Approval worklists
8. Cross-department coordination
9. Department creation and configuration where authorized
10. Staff and departmental assignment awareness
11. Duty-roster and coverage awareness
12. Policy management
13. Standard operating procedure management
14. Administrative guidelines
15. Internal memos
16. Announcements
17. Correspondence tracking
18. Document registers
19. Committee management
20. Meeting management
21. Action-item tracking
22. Complaint management
23. Incident management
24. Administrative investigations
25. Compliance monitoring
26. Internal audit follow-up
27. Audit findings
28. Corrective-action tracking
29. Risk-register management
30. Facility and operational issue tracking
31. Executive reporting
32. Departmental comparison
33. Operational trends
34. Management summaries
35. System-configuration awareness where authorized
36. Administrative activity auditing

The Administrative workspace must remain distinct from:

* Clinical consultation
* Patient registration and Records operations
* Finance transactions
* Pharmacy dispensing
* Stores inventory operations
* HR payroll and attendance processing
* Investigation result processing
* Inpatient ward care
* Emergency care
* Maternity care
* Theatre operations
* Technical system administration

Administrative users may oversee and review those areas, but specialist workspaces and domain services remain authoritative for performing specialist actions.

---

# Phase 1 — Inspect the Existing Administrative Architecture

Before implementing, inspect the current codebase and identify:

1. How department-specific menus are selected.
2. How existing departmental workspaces are registered.
3. How active department context is resolved.
4. How multi-department users switch active departments.
5. How `DepartmentType::ADMINISTRATIVE` is currently mapped by:

   * department dashboard resolver
   * department dashboard registry
   * department menu profile service
   * capability resolver
   * department metrics registry
6. Whether the current internal dashboard key is:

```text
management
administrative
admin
operations
```

Preserve established internal keys where required while exposing the browser workspace under `/administrative/*`.

7. Existing routes, controllers, services, policies, models, and views for:

   * departments
   * users
   * roles
   * permissions
   * department assignments
   * facility configuration
   * operational dashboards
   * approval workflows
   * notifications
   * announcements
   * documents
   * reports
   * activity logs
   * audit findings
   * incidents
   * complaints
   * schedules
   * duty rosters
   * committees
   * meetings
   * tasks
   * risk registers
8. Existing management and department dashboard widgets.
9. Existing departmental analytics.
10. Existing cross-department comparison services.
11. Existing approval infrastructure.
12. Existing Journey Intelligence oversight functionality.
13. Existing audit and ActivityLog infrastructure.
14. Existing file and document-storage architecture.
15. Existing permission naming conventions.
16. Existing admin-preview behaviour.
17. Existing views that hardcode generic routes such as:

```php
route('admin.departments.show', $department)
route('admin.users.show', $user)
route('approvals.show', $approval)
route('reports.department-metrics')
```

Do not create a competing administrative menu, dashboard, approval, department, user-management, or reporting system where one already exists.

Extend the existing:

* Department dashboard registry
* Department menu profile service
* Department capability resolver
* Active department context
* Workspace route resolver
* Workspace redirect resolver
* Department management services
* User and assignment services
* Approval infrastructure
* Journey Intelligence oversight services
* Reporting services
* Notification services
* Document services
* Authorization policies
* Activity logging

---

# Phase 2 — Administrative Workspace Route Group

Create a dedicated Administrative route group.

Use a structure equivalent to:

```php
Route::prefix('administrative')
    ->name('administrative.')
    ->middleware([
        'auth',
        'verified',
        'department.context',
        'department.type:administrative',
    ])
    ->group(function () {
        // Administrative workspace routes
    });
```

Use the project’s actual middleware names and active-department authorization architecture.

Required route names should include, where the corresponding functionality exists:

```text
administrative.dashboard

administrative.departments.index
administrative.departments.show
administrative.departments.performance
administrative.departments.status
administrative.departments.services
administrative.departments.staff

administrative.operations.index
administrative.operations.worklist
administrative.operations.exceptions
administrative.operations.escalations
administrative.operations.show

administrative.approvals.index
administrative.approvals.pending
administrative.approvals.completed
administrative.approvals.show
administrative.approvals.approve
administrative.approvals.reject
administrative.approvals.return

administrative.users.index
administrative.users.show
administrative.users.departments
administrative.users.permissions

administrative.staff.index
administrative.staff.show
administrative.staff.coverage

administrative.schedules.index
administrative.duty_rosters.index
administrative.coverage.index

administrative.policies.index
administrative.policies.show
administrative.policies.create
administrative.policies.store
administrative.policies.update
administrative.policies.publish
administrative.policies.archive

administrative.procedures.index
administrative.procedures.show
administrative.guidelines.index
administrative.guidelines.show

administrative.documents.index
administrative.documents.show
administrative.documents.create
administrative.documents.store
administrative.documents.update
administrative.documents.archive

administrative.correspondence.index
administrative.correspondence.show
administrative.correspondence.create
administrative.correspondence.store

administrative.memos.index
administrative.memos.show
administrative.memos.create
administrative.memos.store
administrative.memos.publish

administrative.announcements.index
administrative.announcements.show
administrative.announcements.create
administrative.announcements.store
administrative.announcements.publish

administrative.incidents.index
administrative.incidents.show
administrative.incidents.create
administrative.incidents.store
administrative.incidents.update
administrative.incidents.assign
administrative.incidents.resolve

administrative.complaints.index
administrative.complaints.show
administrative.complaints.create
administrative.complaints.store
administrative.complaints.assign
administrative.complaints.resolve

administrative.compliance.index
administrative.audits.index
administrative.audits.show
administrative.findings.index
administrative.findings.show
administrative.findings.assign
administrative.findings.resolve

administrative.risks.index
administrative.risks.show
administrative.risks.create
administrative.risks.store
administrative.risks.update

administrative.meetings.index
administrative.meetings.show
administrative.meetings.create
administrative.meetings.store

administrative.committees.index
administrative.committees.show

administrative.action_items.index
administrative.action_items.show
administrative.action_items.assign
administrative.action_items.complete

administrative.facilities.index
administrative.facilities.show
administrative.service_availability.index
administrative.department_status.index

administrative.handoffs.index
administrative.reports.index
```

Only register routes for functionality that exists or is being implemented.

Do not create empty placeholder pages merely to populate the menu.

---

# Phase 3 — Administrative Operations Dashboard

Create or complete a dedicated Administrative dashboard.

The canonical route should be:

```text
/administrative
```

or:

```text
/administrative/dashboard
```

Choose one canonical route and redirect the other to it.

The department dashboard resolver should map:

```php
DepartmentType::ADMINISTRATIVE => 'administrative.dashboard'
```

If the existing registry uses an internal dashboard key such as `management`, preserve compatibility while exposing `/administrative`.

The dashboard should function as a hospital operations command board.

Recommended metrics and widgets include, where reliable data exists:

* Active departments
* Departments currently operational
* Departments with service interruptions
* Departments with critical alerts
* Active staff today
* Departments with inadequate coverage
* Pending administrative approvals
* Overdue approvals
* Open incidents
* Critical incidents
* Open complaints
* Complaints awaiting response
* Outstanding audit findings
* Overdue corrective actions
* High-risk items
* Pending policy reviews
* Policies awaiting approval
* Unpublished administrative documents
* Open meeting action items
* Overdue action items
* Journey Intelligence critical cases
* Departments with SLA breaches
* Departments with supervisor configuration missing
* System-wide patient volume today
* Current active visits
* Current admissions
* Emergency activity
* Discharges today
* Service bottlenecks
* Department performance ranking
* Operational escalations
* Notifications requiring management attention

Each dashboard metric must:

* Respect permissions
* Respect the active Administrative department
* Respect facility or branch scoping
* Avoid exposing patient-identifiable information unnecessarily
* Avoid exposing financial, HR, or clinical details without permission
* Link to valid `/administrative/*` routes
* Use safe empty states
* Avoid expensive unbounded queries
* Reuse existing department metrics, Journey Intelligence, analytics, and reporting services

Do not introduce metrics that cannot be calculated reliably.

---

# Phase 4 — Administrative-Specific Menu Profile

Extend the existing department menu profile or menu registry so that:

```php
DepartmentType::ADMINISTRATIVE
```

receives a dedicated Administrative menu.

Recommended menu structure:

## Administrative Command

* Dashboard
* Operational Overview
* Pending Approvals
* Critical Escalations
* Department Status
* Executive Summary

## Departments

* All Departments
* Department Performance
* Department Services
* Department Staffing
* Department Configuration
* Department Comparison

## Operations

* Operations Worklist
* Service Availability
* Operational Exceptions
* SLA Breaches
* Bottlenecks
* Critical Alerts
* Escalations

## People and Coverage

* Staff Directory
* User Directory
* Department Assignments
* Duty Rosters
* Staff Coverage
* Supervisors
* Unassigned Responsibilities

Do not expose HR payroll, attendance modification, or confidential employee records unless the user possesses the relevant HR permissions.

## Approvals

* Pending Approvals
* My Approval Queue
* Overdue Approvals
* Completed Approvals
* Rejected Requests
* Returned for Correction

## Policies and Governance

* Policies
* Standard Operating Procedures
* Guidelines
* Policy Review Calendar
* Archived Policies

## Communication and Documents

* Announcements
* Memos
* Correspondence
* Administrative Documents
* Circulars
* Document Register

## Incidents and Complaints

* Incidents
* Critical Incidents
* Complaints
* Pending Investigations
* Corrective Actions
* Resolved Cases

## Compliance and Risk

* Compliance Dashboard
* Internal Audits
* Audit Findings
* Corrective Actions
* Risk Register
* High-Risk Items
* Compliance Deadlines

## Meetings and Committees

* Meetings
* Committees
* Meeting Minutes
* Action Items
* Overdue Actions

## Facilities and Availability

* Facility Overview
* Department Availability
* Service Interruptions
* Facility Issues
* Operational Notices

Only expose maintenance or technical actions where supported and authorized.

## Reports

* Department Performance Report
* Operational Activity Report
* Service Availability Report
* Approval Report
* Incident Report
* Complaint Report
* Audit Findings Report
* Risk Report
* Staff Coverage Report
* Executive Summary Report
* Department Comparison Report
* Management Activity Report

## General

* Notifications
* My Profile
* Switch Department

Only show a menu item when:

1. The feature exists.
2. The corresponding module is enabled.
3. The user possesses the required permission.
4. The active department and facility permit access.
5. The action belongs to administrative operations.

Permissions remain authoritative.

Do not expose menu items solely because the active department type is `administrative`.

---

# Phase 5 — Department Oversight

Expose departmental oversight under:

```text
/administrative/departments
```

Reuse the existing department management and metrics services.

Each department overview may display:

* Department name
* Department type
* Department status
* Facility
* Department head
* Supervisor
* Active staff
* Available services
* Current patient volume
* Active work queue
* Critical alerts
* SLA breaches
* Operational state
* Latest activity
* Outstanding approvals
* Outstanding risks
* Quick links

Administrative users must not automatically gain permission to edit every department.

Separate:

```text
view
manage
configure
activate
deactivate
assign_staff
manage_services
```

through permissions and policies.

---

# Phase 6 — Department Performance and Comparison

Reuse the existing Department Metrics Registry and comparison services.

Expose department performance under:

```text
/administrative/departments/{department}/performance
```

and comparison under an authorized Administrative report or dashboard.

Potential metrics include:

* Patient volume
* Active visits
* Completed visits
* Revenue where authorized
* Average waiting time
* SLA compliance
* Worklist volume
* Critical alerts
* Service completion rate
* Pending handoffs
* Discharge rate
* Investigation turnaround time
* Prescription turnaround time
* Bed occupancy
* Department staffing coverage

Only show metrics appropriate to each department type.

Do not compare unrelated departments using meaningless metrics.

Use department-type-specific metric definitions and clearly label shared versus type-specific KPIs.

---

# Phase 7 — Administrative Operations Worklist

Create or adapt an operations worklist under:

```text
/administrative/operations/worklist
```

Recommended worklist categories include:

```text
approval_required
department_exception
service_interruption
staffing_gap
sla_breach
critical_escalation
policy_review_due
audit_action_due
complaint_action_due
incident_action_due
meeting_action_due
configuration_issue
```

Each item should display:

* Type
* Title
* Department
* Facility
* Priority
* Owner
* Due date
* Current status
* Age
* Next required action
* Escalation state

Support filters such as:

* Department
* Facility
* Priority
* Owner
* Status
* Due date
* Category
* Escalation state

Reuse existing Journey Intelligence coordination, task, notification, and approval services where appropriate.

---

# Phase 8 — Service Availability and Operational Status

Expose service availability under:

```text
/administrative/service-availability
```

The page may show:

* Department
* Service
* Availability state
* Operating hours
* Staff coverage
* Equipment availability where supported
* Stock dependency where supported
* Current interruption
* Expected restoration
* Responsible department
* Operational notice

Potential availability states may include:

```text
available
limited
temporarily_unavailable
scheduled_downtime
emergency_only
closed
unknown
```

Do not automatically mark services unavailable based only on one missing input unless the existing service-availability resolver supports it.

Availability changes must be permission-controlled and audited.

---

# Phase 9 — Approval Worklists

Expose Administrative approvals under:

```text
/administrative/approvals
```

Reuse existing module-specific approval services.

Approval requests may originate from:

* Finance
* Discounts
* Refunds
* Credit notes
* Write-offs
* Stores adjustments
* Stock disposals
* Department configuration
* User assignment
* Policy publication
* Incident closure
* Audit finding closure
* Other configured workflows

The Administrative workspace should aggregate approvals but must not replace the originating domain’s validation and approval services.

Each approval item should display:

* Request type
* Reference
* Requesting department
* Requesting user
* Requested action
* Amount or impact where authorized
* Priority
* Current approval level
* Required decision
* Supporting details
* Due date

Administrative approval actions should call the authoritative domain service.

Do not update source records directly from a generic approval controller.

---

# Phase 10 — Approval Decisions and Separation of Duties

Approval actions may include:

```text
approve
reject
return_for_correction
request_information
escalate
```

Each decision should record:

* Approval request
* Decision
* Approving user
* Approval level
* Reason
* Date and time
* Source module
* Source record
* Next state

Where configured:

* A requester must not approve their own request.
* One approval level must not impersonate another.
* High-impact requests must require multiple approvals.
* Final approval must remain distinct from execution.
* Emergency approval exceptions must be reasoned and audited.

Do not bypass source-module permissions or business rules.

---

# Phase 11 — User and Staff Oversight

Expose authorized staff and user visibility under:

```text
/administrative/users
/administrative/staff
```

Administrative users may be allowed to view:

* Name
* Staff identifier
* Job title
* Department assignments
* Primary department
* Active department
* Role
* Account status
* Supervisor
* Schedule coverage
* Last activity where authorized
* Required training or compliance state where supported

Do not expose:

* Payroll
* salary
* bank details
* medical information
* disciplinary information
* private contact details

without the relevant permission.

Administrative user-management actions should reuse the existing user, role, permission, and department-assignment services.

Do not create a second user-management system.

---

# Phase 12 — Department Assignments

Where authorized, expose department assignment management.

The workflow may support:

* Assign user to department
* Remove user from department
* Set primary department
* Set supervisor
* Set department-specific role
* Configure active/inactive assignment
* Set start and end dates
* Record reason

Reuse the existing `department_user` pivot and primary-department synchronization.

Changes must:

* Preserve valid active-department context
* Prevent removal of required primary assignments without replacement
* Avoid unauthorized privilege escalation
* Be audited

Do not grant permissions solely through department assignment unless the existing role and permission model explicitly does so.

---

# Phase 13 — Staff Coverage and Duty Rosters

Expose schedule and coverage awareness under:

```text
/administrative/duty-rosters
/administrative/coverage
```

Reuse existing HR, scheduling, attendance, and department services where available.

The Administrative workspace may show:

* Department
* Shift
* Scheduled staff
* Present staff where authorized
* Required minimum coverage
* Coverage gap
* Supervisor
* On-call staff
* Escalation state

Administrative users should not modify attendance or payroll records through this workspace unless they possess the appropriate HR permissions.

Coverage should remain an operational view, not an alternative HR system.

---

# Phase 14 — Policy Management

Expose policies under:

```text
/administrative/policies
```

A policy record may include:

* Title
* Policy code
* Category
* Owning department
* Version
* Effective date
* Review date
* Status
* Approving authority
* Document
* Summary
* Superseded policy
* Distribution list

Potential policy states may include:

```text
draft
under_review
approved
published
superseded
archived
```

Policy publication should:

1. Preserve version history.
2. Record approval.
3. Record publication date.
4. Preserve previous versions.
5. Notify relevant users where supported.
6. Be audited.

Do not overwrite published policy content silently.

---

# Phase 15 — Standard Operating Procedures and Guidelines

Expose procedures and guidelines under:

```text
/administrative/procedures
/administrative/guidelines
```

Reuse the document and policy architecture where possible.

Each document should support:

* Department ownership
* Version
* Review date
* Approval
* Publication
* Archive
* Related policy
* Related forms
* Distribution

Do not store separate uncontrolled copies of the same official procedure across multiple modules.

Use one authoritative published version with controlled links.

---

# Phase 16 — Administrative Documents and Correspondence

Expose document and correspondence tracking under:

```text
/administrative/documents
/administrative/correspondence
```

Document records may include:

* Reference number
* Title
* Category
* Sender
* Recipient
* Department
* Date
* Confidentiality level
* File attachment
* Status
* Assigned officer
* Required response
* Due date

Potential correspondence states may include:

```text
received
registered
assigned
in_review
response_required
responded
closed
archived
```

Document access must respect:

* Confidentiality
* Department
* role
* explicit permission
* facility scope

Do not expose confidential correspondence through broad administrative access.

---

# Phase 17 — Memos and Announcements

Expose internal memos and announcements under:

```text
/administrative/memos
/administrative/announcements
```

A memo or announcement may include:

* Title
* Message
* Audience
* Departments
* Roles
* Facility
* Effective date
* Expiry date
* Priority
* Attachment
* Publishing user
* Publication state

Potential states may include:

```text
draft
scheduled
published
expired
archived
```

Announcements should use the existing notification infrastructure where available.

Do not send confidential administrative information to broad audiences without explicit targeting.

---

# Phase 18 — Incident Management

Expose administrative incidents under:

```text
/administrative/incidents
```

Incidents may include:

* Operational incidents
* Facility incidents
* Administrative errors
* Security incidents
* Service interruptions
* Documentation incidents
* Compliance incidents
* Patient-safety incidents where the user is authorized
* Data-quality incidents

An incident record may include:

* Incident number
* Category
* Severity
* Department
* Facility
* Date and time
* Reported by
* Description
* Immediate action
* Assigned owner
* Investigation state
* Corrective action
* Resolution
* Closure approval

Potential incident states may include:

```text
reported
triaged
assigned
under_investigation
corrective_action_required
resolved
closed
reopened
```

Do not expose clinical incident details beyond the user’s permission.

Incident management must preserve history and audit all changes.

---

# Phase 19 — Complaint Management

Expose complaints under:

```text
/administrative/complaints
```

A complaint may originate from:

* Patient
* Relative
* Staff member
* Department
* External stakeholder
* Anonymous source where allowed

Complaint records may include:

* Complaint number
* Complainant type
* Department
* Category
* Priority
* Date
* Summary
* Assigned officer
* Response deadline
* Investigation
* Resolution
* Feedback
* Closure state

Potential states may include:

```text
received
acknowledged
assigned
under_review
response_pending
resolved
closed
reopened
```

Patient identity and contact information must remain protected.

Do not expose complaint records across departments without authorization.

---

# Phase 20 — Compliance Monitoring

Expose compliance oversight under:

```text
/administrative/compliance
```

Compliance may include:

* Policy review deadlines
* License or certification expiry
* Departmental compliance checks
* Mandatory reporting deadlines
* Training compliance where supported
* Audit action deadlines
* Documentation compliance
* Service standards
* Data-completeness checks

The workspace should distinguish:

```text
compliant
attention_required
non_compliant
overdue
under_review
exempted
```

Exemptions must be reasoned, time-scoped, approved, and audited.

Do not fabricate compliance checks where the system has no reliable source data.

---

# Phase 21 — Internal Audits and Findings

Expose internal audits under:

```text
/administrative/audits
/administrative/findings
```

An audit may include:

* Audit title
* Scope
* Department
* Period
* Lead auditor
* Audit team
* Start date
* Completion date
* Findings
* Recommendations
* Corrective actions
* Follow-up date
* Status

An audit finding may include:

* Finding reference
* Severity
* Description
* Requirement
* Evidence
* Responsible department
* Assigned owner
* Corrective action
* Due date
* Verification
* Closure state

Potential finding states may include:

```text
open
assigned
action_in_progress
verification_pending
resolved
closed
overdue
```

Do not allow findings to be silently deleted or closed without the required verification.

---

# Phase 22 — Risk Register

Expose an Administrative risk register under:

```text
/administrative/risk-register
```

A risk record may include:

* Risk title
* Category
* Department
* Description
* Likelihood
* Impact
* Risk score
* Existing controls
* Mitigation plan
* Owner
* Review date
* Residual risk
* Status

Potential states may include:

```text
identified
assessed
mitigation_planned
mitigation_in_progress
accepted
closed
overdue
```

Risk-scoring rules should be centralized and configurable.

Do not hardcode risk calculations across views and controllers.

Risk acceptance must require permission and be audited.

---

# Phase 23 — Meetings and Committees

Expose meetings and committees under:

```text
/administrative/meetings
/administrative/committees
```

A meeting may include:

* Title
* Committee
* Date and time
* Location
* Chairperson
* Attendees
* Agenda
* Minutes
* Decisions
* Action items
* Attachments
* Status

A committee may include:

* Name
* Mandate
* Members
* Chairperson
* Secretary
* Meeting schedule
* Active state

Do not automatically expose confidential committee records to all Administrative users.

Use committee membership and permissions.

---

# Phase 24 — Action Item Tracking

Expose administrative action items under:

```text
/administrative/action-items
```

Action items may originate from:

* Meetings
* Audits
* Complaints
* Incidents
* Approvals
* Risk mitigation
* Management decisions

Each action item should include:

* Source
* Description
* Owner
* Department
* Priority
* Due date
* Status
* Completion note
* Verification
* Escalation state

Potential states may include:

```text
pending
assigned
acknowledged
in_progress
completed
verified
cancelled
overdue
```

Reuse existing task or coordination infrastructure where appropriate.

Do not create duplicate task records when the existing task model can represent administrative actions safely.

---

# Phase 25 — Journey Intelligence and Operational Oversight

Reuse the existing Journey Intelligence infrastructure to provide management oversight.

Administrative users with appropriate permission may view:

* Department bottlenecks
* SLA breaches
* Unassigned actions
* Supervisor escalations
* High-priority handoffs
* Department queues
* Journey analytics
* Prediction summaries
* Prediction accuracy summaries

Administrative users should not automatically receive patient-identifiable details.

Use aggregate or masked views unless detailed clinical oversight permission exists.

Do not allow Administrative users to claim or complete clinical actions unless they possess the appropriate clinical permission and department context.

---

# Phase 26 — Facility and Branch Scoping

Where the hospital system supports multiple facilities or branches, all Administrative data must respect:

* Active facility
* Active Administrative department
* User facility assignments
* Authorized cross-facility access
* Department-facility mapping

A user should not see all facilities merely because their department type is `administrative`.

Support explicit permissions for:

```text
facility_local
facility_group
organization_wide
```

where the existing authorization model supports such scopes.

---

# Phase 27 — System Configuration Awareness

Administrative users may need visibility into operational configuration such as:

* Enabled modules
* Department status
* Service status
* Missing supervisors
* Missing department assignments
* Incomplete configuration
* Policy-audit findings
* Missing billing context
* Localization parity warnings
* Integration status

Visibility does not automatically grant edit permission.

System configuration changes must remain under the existing settings and system-administration permissions.

Do not turn the Administrative workspace into unrestricted technical administration.

---

# Phase 28 — Workspace-Aware URL Resolution

Extend the centralized workspace route resolver.

Do not scatter checks such as:

```php
if ($department->type === DepartmentType::ADMINISTRATIVE) {
    return route('administrative.departments.show', $department);
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

departmentIndex()
departmentShow(Department $department)
departmentPerformance(Department $department)

operationsWorklist()
operationsExceptions()
operationsEscalations()

approvalIndex()
approvalShow(Approval $approval)

userIndex()
userShow(User $user)

staffIndex()
coverageIndex()

policyIndex()
policyShow(Policy $policy)

documentIndex()
documentShow(Document $document)

announcementIndex()
memoIndex()
correspondenceIndex()

incidentIndex()
incidentShow(Incident $incident)

complaintIndex()
complaintShow(Complaint $complaint)

auditIndex()
auditShow(Audit $audit)

findingIndex()
findingShow(AuditFinding $finding)

riskIndex()
riskShow(Risk $risk)

meetingIndex()
meetingShow(Meeting $meeting)

actionItemIndex()
actionItemShow(ActionItem $actionItem)

serviceAvailability()
handoffIndex()
reportIndex()
```

For an Administrative user, the resolver must return `administrative.*` routes.

For other users, preserve the appropriate existing workspace or generic route.

Always use the active department and facility context rather than only the user’s primary department.

---

# Phase 29 — Replace Hardcoded Shared Links

Audit all shared pages accessed by Administrative users.

Replace hardcoded generic links that break workspace continuity.

Review at minimum:

* Department lists
* Department dashboards
* User and staff lists
* Approval worklists
* Operations worklists
* Policy pages
* Document pages
* Announcement pages
* Incident pages
* Complaint pages
* Audit pages
* Finding pages
* Risk pages
* Meeting pages
* Committee pages
* Action-item pages
* Journey oversight pages
* Dashboard cards
* Breadcrumbs
* Notifications
* Escalation links
* Action dropdowns
* Empty-state actions
* Report drilldowns
* Flash-message links

Avoid shared-view code such as:

```php
route('admin.departments.show', $department)
```

Use the centralized workspace route resolver.

Do not alter API, integration, signed, print, export, webhook, or background-job URLs unless explicitly part of the Administrative browser workspace.

---

# Phase 30 — Workspace-Aware Redirects

All successful Administrative actions must redirect back into `/administrative/*`.

Examples:

After approving a request:

```text
/administrative/approvals/{approval}
```

After publishing a policy:

```text
/administrative/policies/{policy}
```

After publishing an announcement:

```text
/administrative/announcements/{announcement}
```

After assigning an incident:

```text
/administrative/incidents/{incident}
```

After resolving a complaint:

```text
/administrative/complaints/{complaint}
```

After updating an audit finding:

```text
/administrative/findings/{finding}
```

After completing an action item:

```text
/administrative/action-items/{actionItem}
```

Avoid hardcoding Administrative redirects inside domain services.

Use a workspace redirect resolver such as:

```php
$workspaceRedirects->toDepartment($department);
$workspaceRedirects->toApproval($approval);
$workspaceRedirects->toPolicy($policy);
$workspaceRedirects->toIncident($incident);
$workspaceRedirects->toComplaint($complaint);
$workspaceRedirects->toAuditFinding($finding);
$workspaceRedirects->toAdministrativeDashboard();
```

Validation failures must return users to the same `/administrative/*` route with input preserved.

---

# Phase 31 — Login and Department Switching

When a user logs in and their active department type is `administrative`, redirect them to:

```text
/administrative
```

When a multi-department user switches to an Administrative department, redirect them to:

```text
/administrative
```

When switching away from Administrative, redirect to the selected department’s appropriate workspace.

The menu, dashboard, route context, facility, and data scoping must always use the active department.

Do not rely only on:

```php
$user->department_id
```

where the application supports active department selection.

---

# Phase 32 — Administrative Workspace Authorization

The `/administrative` prefix is not authorization.

Protect the workspace so access requires:

1. An authenticated user.
2. A valid active department.
3. Active department type equal to `administrative`, unless authorized admin preview applies.
4. The required permission.
5. The relevant module being enabled.
6. Access to the requested facility, department, user, document, approval, incident, complaint, audit, risk, or meeting.
7. Appropriate organization, facility, branch, department, or committee scope.

A user from another department who manually enters:

```text
/administrative/departments
```

must not receive access merely because they possess a broad department-view permission.

Use the project’s existing unauthorized workspace behaviour:

* `403`
* Workspace unavailable page
* Safe redirect

Do not create inconsistent authorization behaviour.

---

# Phase 33 — Permission Model

Reuse existing permissions wherever possible.

Only add permissions where the current permission model does not represent the action.

Potential permissions may include:

```text
administrative.workspace.view

administrative.dashboard.view
administrative.operations.view
administrative.operations.manage
administrative.escalations.view
administrative.escalations.manage

administrative.departments.view
administrative.departments.manage
administrative.departments.configure
administrative.departments.compare

administrative.users.view
administrative.users.manage
administrative.user_departments.manage
administrative.staff.view
administrative.coverage.view

administrative.approvals.view
administrative.approvals.manage

administrative.policies.view
administrative.policies.create
administrative.policies.manage
administrative.policies.approve
administrative.policies.publish

administrative.documents.view
administrative.documents.manage
administrative.correspondence.view
administrative.correspondence.manage

administrative.announcements.view
administrative.announcements.manage
administrative.memos.view
administrative.memos.manage

administrative.incidents.view
administrative.incidents.manage
administrative.incidents.close

administrative.complaints.view
administrative.complaints.manage
administrative.complaints.close

administrative.compliance.view
administrative.compliance.manage
administrative.audits.view
administrative.audits.manage
administrative.findings.view
administrative.findings.manage

administrative.risks.view
administrative.risks.manage
administrative.risks.accept

administrative.meetings.view
administrative.meetings.manage
administrative.committees.view
administrative.committees.manage
administrative.action_items.view
administrative.action_items.manage

administrative.service_availability.view
administrative.service_availability.manage

administrative.handoffs.view
administrative.handoffs.manage

administrative.reports.view
administrative.reports.export
administrative.system_status.view
```

Inspect existing permission names before adding new ones.

Avoid duplicating equivalent permissions.

Menu visibility must follow permissions, but controllers, policies, form requests, approval services, and domain services must independently enforce authorization.

---

# Phase 34 — Separation of Duties

Enforce separation of duties where configured.

Examples:

* A policy author should not be the only policy approver.
* A complaint investigator should not approve final closure where independent review is required.
* An incident reporter should not close a critical incident without verification.
* An audit finding owner should not independently verify their own corrective action.
* A department-change requester should not approve the same change.
* A risk owner should not approve risk acceptance above configured thresholds.
* An approval requester should not approve their own request.

Use configurable rules rather than hardcoding one universal workflow.

Exceptions must be:

* Explicit
* Permission-controlled
* Reasoned
* Time-stamped
* Audited

---

# Phase 35 — Privacy and Confidentiality

The Administrative workspace handles sensitive operational, staff, patient, complaint, audit, and governance information.

Ensure existing privacy controls remain active, including:

* Patient masking where patient context appears
* Protected phone and email fields
* Staff contact protection
* Complaint confidentiality
* Incident confidentiality
* Committee confidentiality
* Document access classification
* Audit access control
* Export restrictions
* Privacy-aware notifications
* Activity-log sanitization

Administrative users should only see the minimum information required for their role.

Do not expose:

* Full clinical notes
* Payroll
* Bank information
* confidential HR cases
* protected complaint identities
* security-sensitive system details
* patient-identifiable analytics

without explicit permission.

---

# Phase 36 — Administrative Integrity and Operational Safety

Preserve existing operational safeguards, including:

* Department authorization
* User and role authorization
* Approval traceability
* Policy version history
* Document confidentiality
* Incident-history preservation
* Complaint-history preservation
* Audit finding traceability
* Risk acceptance control
* Corrective-action verification
* Meeting-action accountability
* Notification targeting
* Activity logging

Do not allow:

* Published policies to be silently overwritten
* Closed complaints to be edited without reopening
* Critical incidents to be closed without required verification
* Audit findings to disappear without resolution
* Risks to be deleted merely because they are accepted
* Approval decisions to be edited silently
* Department access to be expanded without authorization
* Confidential documents to become globally visible
* Administrative oversight to bypass specialist-domain rules
* Management users to perform clinical or financial transactions without corresponding permissions

Overrides must be explicit, permission-controlled, reasoned, scoped, and audited.

---

# Phase 37 — Activity Logging and Audit

Record relevant Administrative actions through the existing `ActivityLog` infrastructure.

Audit events should cover actions such as:

* Administrative department opened
* Department status changed
* Department configuration updated
* Department assignment changed
* Approval approved
* Approval rejected
* Approval returned for correction
* Policy created
* Policy approved
* Policy published
* Policy superseded
* Document registered
* Document archived
* Announcement published
* Memo published
* Incident created
* Incident assigned
* Incident severity changed
* Incident resolved
* Incident closed
* Complaint created
* Complaint assigned
* Complaint resolved
* Complaint closed
* Audit created
* Audit finding created
* Audit finding assigned
* Corrective action recorded
* Audit finding closed
* Risk created
* Risk score changed
* Risk accepted
* Meeting created
* Meeting minutes published
* Action item assigned
* Action item completed
* Service availability changed
* Operational escalation created
* Operational escalation resolved

Do not log full confidential document content, complaint details, clinical notes, authentication credentials, or security-sensitive values.

Audit records should include sufficient context such as:

* Actor
* Department
* Facility
* Source record
* Action
* Timestamp
* Reason where required
* Previous and new state where appropriate

---

# Phase 38 — Localization

Add complete English and French localization for the Administrative workspace.

Prefer existing Administration, Management, Departments, Reports, and Settings localization files where appropriate.

Otherwise, use or extend:

```text
lang/en/administrative.php
lang/fr/administrative.php
```

Include keys for:

* Workspace title
* Dashboard
* Menu sections
* Department states
* Operational states
* Approval states
* Policy states
* Document states
* Correspondence states
* Announcement states
* Incident states and severities
* Complaint states
* Compliance states
* Audit states
* Finding states
* Risk states
* Meeting states
* Action-item states
* Service-availability states
* Empty states
* Quick actions
* Reports
* Breadcrumbs
* Unauthorized workspace message
* Override and exception reasons

Maintain complete English and French parity.

Do not hardcode visible Administrative labels in controllers, services, Blade templates, or JavaScript.

---

# Phase 39 — Administrative Reports and Analytics

Create or adapt Administrative reports under:

```text
/administrative/reports
```

Recommended reports include:

* Department performance report
* Department comparison report
* Operational status report
* Service availability report
* SLA breach report
* Bottleneck report
* Approval turnaround report
* Staff coverage report
* Department assignment report
* Policy review report
* Document register report
* Correspondence report
* Announcement report
* Incident report
* Critical-incident report
* Complaint report
* Complaint-resolution report
* Compliance report
* Internal-audit report
* Audit-findings report
* Corrective-action report
* Risk-register report
* High-risk-item report
* Meeting activity report
* Action-item report
* Executive summary
* Management activity report

Reports must respect:

* Permissions
* Active department
* Facility and branch scope
* Department scope
* Confidentiality level
* Patient privacy
* Staff privacy
* Minimum aggregation safeguards
* Export permissions

Do not expose identifiable patient, complaint, incident, or staff details in aggregate reports without detailed-report permission.

---

# Phase 40 — Menu Configuration and Future Extensibility

Implement the Administrative menu through the existing menu registry or department menu profile service.

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
* Facility scoping
* Feature flags
* Pending-approval counts
* Critical-incident counts
* Open-complaint counts
* Overdue-audit-finding counts
* High-risk-item counts
* Service-interruption counts
* Overdue-action-item counts
* Coverage-gap counts

The architecture must remain extensible for remaining department menu personalization, including:

```text
radiology
theatre
blood_bank
mortuary
ambulance
support
```

Do not implement those workspaces in this phase.

---

# Phase 41 — Focused Automated Verification

Add focused automated tests for the Administrative workspace.

## Route tests

Verify:

* Administrative routes exist.
* Route names use `administrative.*`.
* URLs use `/administrative/*`.
* Administrative department middleware is attached.
* Generic routes remain available where required.

## Access tests

Verify:

* An Administrative department user can access authorized Administrative pages.
* A non-Administrative department user cannot access the workspace.
* Users without the required permission cannot access protected actions.
* Admin preview continues working where supported.
* Multi-department active context is respected.
* Facility and branch scope are respected.

## Dashboard tests

Verify:

* The Administrative dashboard loads.
* Department, approval, incident, complaint, audit, and risk metrics are accurate.
* Patient-identifiable information is masked or omitted.
* Confidential metrics are hidden without permission.
* Links point to `/administrative/*`.
* Empty states render safely.

## Department oversight tests

Verify:

* Department lists respect facility scope.
* Department performance uses existing metrics.
* Department-type-specific KPIs are displayed correctly.
* Administrative users without management permission cannot modify departments.
* Department changes are audited.

## Approval tests

Verify:

* Approval worklists aggregate supported source workflows.
* Approval actions call the source domain service.
* Requesters cannot approve their own requests where separation is enabled.
* Rejection and return require reasons.
* Approval history remains preserved.
* Source records update correctly.

## Policy tests

Verify:

* Policies preserve version history.
* Published policies cannot be silently overwritten.
* Policy publication requires approval where configured.
* Superseded policies remain available in history.
* Policy actions are audited.

## Document tests

Verify:

* Documents respect confidentiality classifications.
* Unauthorized departments cannot access restricted documents.
* Archived documents remain traceable.
* Attachments follow existing security controls.

## Incident and complaint tests

Verify:

* Incidents and complaints preserve full state history.
* Critical incidents require the configured closure checks.
* Complaints respect identity protection.
* Closed records require formal reopening before modification.
* Assignment and resolution actions are audited.

## Audit and compliance tests

Verify:

* Audit findings cannot be deleted silently.
* Corrective actions require verification where configured.
* Overdue findings remain visible.
* Compliance exemptions require reason and approval.
* Audit closure is permission-controlled.

## Risk tests

Verify:

* Risk scoring uses the centralized service.
* Risk acceptance requires permission.
* High-risk items remain visible.
* Accepted risks remain in history.
* Risk changes are audited.

## Meeting and action-item tests

Verify:

* Meetings preserve minutes and decisions.
* Committee access is restricted appropriately.
* Action items remain linked to their source.
* Overdue actions remain visible.
* Completion and verification are distinct where configured.

## Redirect tests

Verify:

* Login redirects to `/administrative`.
* Switching to Administrative redirects to `/administrative`.
* Department, approval, policy, incident, complaint, audit, risk, and meeting actions remain under `/administrative/*`.
* No redirect loops occur.
* JSON, API, signed, print, export, webhook, and integration requests are not incorrectly redirected.

## Privacy and audit tests

Verify:

* Patient masking remains active.
* Staff and complaint confidentiality are enforced.
* Confidential documents require permission.
* Administrative actions generate required audit records.
* Sensitive content is not exposed through alternate Administrative views.

Run focused Administrative workspace tests and essential route, view, localization, permission, privacy, and audit checks during implementation.

Do not run the full UHMS suite after each phase.

Run one broad relevant suite after all Administrative workspace phases are complete.

---

# Phase 42 — Manual Acceptance Scenarios

## Scenario A — Administrative login

1. Log in as a user whose active department type is `administrative`.
2. Confirm the landing URL is `/administrative`.
3. Confirm the Administrative-specific menu is displayed.
4. Confirm unrelated specialist menus are absent.

## Scenario B — Department oversight

1. Open the department list.
2. Select a department.
3. Confirm operational status, staffing, services, metrics, and alerts.
4. Confirm links remain under `/administrative/*`.
5. Confirm unauthorized configuration actions are hidden.

## Scenario C — Department comparison

1. Open department comparison.
2. Compare two departments with compatible metrics.
3. Confirm shared and type-specific KPIs are clearly distinguished.
4. Confirm unrelated metrics are not used misleadingly.

## Scenario D — Approval workflow

1. Open pending approvals.
2. Select a request from another module.
3. Review supporting information.
4. Approve or reject it.
5. Confirm the originating module updates through its domain service.
6. Confirm the decision is audited.

## Scenario E — Self-approval restriction

1. Submit an approval request as one user.
2. Attempt to approve it using the same user.
3. Confirm the system blocks the action where separation is configured.
4. Approve using another authorized user.

## Scenario F — Policy publication

1. Create or update a draft policy.
2. Submit it for review.
3. Approve and publish it.
4. Confirm the previous version remains preserved.
5. Confirm relevant users are notified where supported.

## Scenario G — Confidential document

1. Register a confidential document.
2. Assign it to specific departments or roles.
3. Confirm unauthorized users cannot access it.
4. Archive it.
5. Confirm it remains traceable.

## Scenario H — Incident management

1. Record an operational incident.
2. Assign an owner.
3. Record immediate and corrective actions.
4. Resolve the incident.
5. Complete required verification.
6. Confirm the full history remains visible.

## Scenario I — Complaint management

1. Record a complaint.
2. Assign it to an authorized officer.
3. Record investigation and response.
4. Resolve and close the complaint.
5. Confirm complainant information remains protected.

## Scenario J — Audit finding

1. Create an audit finding.
2. Assign a corrective action.
3. Mark the action complete.
4. Verify the correction with an authorized reviewer.
5. Close the finding.
6. Confirm it cannot be silently deleted.

## Scenario K — Risk register

1. Create a risk.
2. Record likelihood, impact, controls, and mitigation.
3. Update the risk score.
4. Accept or close it with the required authority.
5. Confirm the complete risk history remains visible.

## Scenario L — Meeting action items

1. Create a meeting.
2. Record minutes and decisions.
3. Assign action items.
4. Complete one action.
5. Confirm overdue actions remain visible.
6. Confirm source linkage is preserved.

## Scenario M — Service availability

1. Mark a service as temporarily unavailable.
2. Record the reason and expected restoration.
3. Confirm the status appears on the Administrative dashboard.
4. Restore availability.
5. Confirm both actions are audited.

## Scenario N — Active department and facility scoping

1. Use a user assigned to multiple Administrative departments or facilities.
2. Switch the active department.
3. Confirm departments, approvals, reports, incidents, and documents change to the selected context.
4. Confirm unauthorized facilities are not visible.

## Scenario O — Permission control

1. Remove policy-publishing permission.
2. Confirm the Publish action disappears.
3. Enter the route directly.
4. Confirm access is denied.

## Scenario P — Legacy compatibility

1. Enter a generic department, user, approval, or incident route as an Administrative user.
2. Confirm it safely resolves or redirects to the Administrative equivalent where configured.
3. Confirm APIs, signed URLs, print routes, exports, webhooks, and integrations remain unaffected.

---

# Acceptance Criteria

The implementation is accepted only when all the following are true:

1. Users with an active department type of `administrative` receive a dedicated Administrative menu.
2. Their default dashboard uses `/administrative`.
3. Supported Administrative pages use `/administrative/*` URLs.
4. Route names use the `administrative.*` namespace.
5. Administrative links, forms, breadcrumbs, and redirects preserve the workspace context.
6. Permissions and enabled modules control menu visibility.
7. A non-Administrative department user cannot access the workspace.
8. Multi-department users are evaluated using the active department.
9. Facility, branch, department, committee, and confidentiality scopes are respected.
10. Existing department, user, approval, reporting, notification, document, task, and audit services are reused.
11. Core clinical, Finance, HR, Stores, Records, and specialist logic is not duplicated.
12. Administrative oversight does not bypass specialist-domain authorization.
13. Department performance uses reliable shared and type-specific KPIs.
14. Approval actions call the authoritative source-domain service.
15. Separation of duties is enforced where configured.
16. Published policies preserve version history.
17. Confidential documents remain access-controlled.
18. Incident and complaint histories cannot be silently overwritten.
19. Audit findings remain visible until properly verified and closed.
20. Risk acceptance remains permission-controlled and traceable.
21. Meeting decisions and action items remain linked.
22. Service-availability changes are permission-controlled and audited.
23. Administrative users do not automatically gain unrestricted system-admin access.
24. Administrative users do not automatically gain clinical, Finance, HR, or inventory transaction permissions.
25. Patient, staff, complaint, document, and audit confidentiality remain fully active.
26. Generic routes remain functional for other departments and integrations.
27. APIs, signed URLs, print routes, exports, webhooks, and integrations are not incorrectly redirected.
28. Relevant Administrative actions are audited.
29. English and French localization are complete and in parity.
30. Focused Administrative workspace tests pass.
31. One broad relevant suite passes after all phases are complete.
32. No broken links, route loops, duplicate route names, facility leakage, confidentiality leakage, self-approval bypass, policy-history loss, or specialist-workflow contamination remains.

---

# Deliverables

Provide:

1. Administrative workspace route group.
2. Administrative-specific controllers or thin adapters where required.
3. Administrative operations dashboard.
4. Administrative department menu profile.
5. Departmental oversight pages.
6. Department performance and comparison integration.
7. Administrative operations worklist.
8. Service-availability monitoring.
9. Aggregated approval worklists.
10. Separation-of-duty integration.
11. User and staff oversight.
12. Department-assignment integration.
13. Duty-roster and coverage awareness.
14. Policy-management workflow.
15. Procedure and guideline management.
16. Document and correspondence tracking.
17. Memo and announcement management.
18. Incident-management workflow.
19. Complaint-management workflow.
20. Compliance monitoring.
21. Internal audit and finding management.
22. Risk-register management.
23. Meeting and committee management.
24. Administrative action-item tracking.
25. Journey Intelligence management oversight.
26. Facility and branch scoping.
27. System-configuration awareness without unrestricted administration.
28. Workspace-aware URL resolver updates.
29. Workspace-aware redirect resolver updates.
30. Updated shared links and forms.
31. Login and department-switch integration.
32. Administrative breadcrumbs and active-menu handling.
33. Permission and confidentiality integration.
34. English and French localization.
35. Focused feature tests.
36. A final implementation report containing:

* Files created
* Files modified
* Administrative route map
* Administrative menu map
* Dashboard metrics
* Department oversight behaviour
* Department comparison behaviour
* Approval aggregation behaviour
* Separation-of-duty rules
* Policy and document behaviour
* Incident and complaint behaviour
* Audit and compliance behaviour
* Risk-register behaviour
* Meeting and action-item behaviour
* Service-availability behaviour
* Active-department and facility scoping
* Reused services
* Redirect behaviour
* Permissions used
* Confidentiality checks
* Operational-integrity checks
* Audit events
* Tests executed
* Test results
* Remaining limitations, if any

Implement the work fully.

Do not stop at planning, route registration, menu configuration, dashboard layout, department listing, approval aggregation, policy management, or report creation alone. The final implementation must provide a functional, secure, permission-aware, department-specific Administrative workspace for hospital governance and operational oversight without bypassing specialist department boundaries.
