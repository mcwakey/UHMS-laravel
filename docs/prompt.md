# UHMS Records Department Workspace — Personalized Menu and `/records/*` Route Architecture

Implement a dedicated **Records Department Workspace** for UHMS.

This work should follow the same department-aware navigation and workspace-personalisation approach already implemented for doctors and consultation departments.

The objective is that when a logged-in user’s active department has:

```php
DepartmentType::RECORDS
```

the user should experience UHMS as a dedicated Records workspace with:

* A Records-specific sidebar menu
* A Records dashboard
* Records-specific breadcrumbs
* Records-specific route names
* Consistent `/records/*` URLs
* Records-aware redirects after actions
* Permission-controlled menu visibility
* Active-department scoping
* Reuse of existing patient, visit, appointment, document, and reporting logic

Do not duplicate core patient, visit, appointment, or medical-record business logic merely to create the new URL structure.

---

## 1. Core Functional Requirement

When the active department type is `records`, all supported pages used by the Records officer must appear under the `/records` URL prefix.

Examples:

```text
/records
/records/dashboard
/records/patients
/records/patients/create
/records/patients/{patient}
/records/patients/{patient}/edit
/records/visits
/records/visits/create
/records/visits/{visit}
/records/appointments
/records/appointments/{appointment}
/records/documents
/records/reports
```

A Records user should not enter through `/records/patients` and then be sent back to a generic URL such as:

```text
/patients/{patient}
/visits/{visit}
/appointments/{appointment}
```

All navigation, form redirects, table actions, breadcrumbs, search results, dashboard links, pagination links, and related actions must preserve the Records workspace context.

---

# Phase 1 — Inspect the Existing Workspace Architecture

Before implementing, inspect the current codebase and identify:

1. How doctor-specific menus are selected.
2. How department type determines dashboard and navigation profiles.
3. How the active department is resolved.
4. How multi-department users switch active departments.
5. How existing patient, visit, appointment, consultation, and records routes are currently registered.
6. How menu visibility is filtered by:

   * permissions
   * module availability
   * active department
   * role
7. How dashboard redirection after login is currently resolved.
8. Whether an existing workspace URL resolver, route resolver, menu registry, or department menu profile service can be extended.
9. Which existing controllers and services can be reused.
10. Which views currently hardcode route names such as:

```php
route('patients.show', $patient)
route('visits.show', $visit)
route('appointments.show', $appointment)
```

Do not create a competing workspace system where one already exists. Extend the existing department dashboard, menu profile, department-context, and route-resolution infrastructure.

---

# Phase 2 — Records Workspace Route Group

Create a dedicated Records route group.

Use a structure equivalent to:

```php
Route::prefix('records')
    ->name('records.')
    ->middleware([
        'auth',
        'verified',
        'department.context',
        'department.type:records',
    ])
    ->group(function () {
        // Records workspace routes
    });
```

Use the project’s actual middleware names and department-type validation architecture.

Required route naming should include, where the corresponding feature already exists:

```text
records.dashboard

records.patients.index
records.patients.create
records.patients.store
records.patients.show
records.patients.edit
records.patients.update

records.visits.index
records.visits.create
records.visits.store
records.visits.show
records.visits.edit
records.visits.update

records.appointments.index
records.appointments.create
records.appointments.store
records.appointments.show
records.appointments.edit
records.appointments.update

records.documents.index
records.documents.show

records.reports.index
records.reports.patients
records.reports.visits
records.reports.attendance
```

Only add routes for functionality that genuinely exists or is being implemented in this phase.

Do not create empty placeholder pages simply to fill the menu.

---

# Phase 3 — Reuse Existing Business Logic

The `/records/*` routes must reuse the current application services and domain logic.

Preferred approaches include:

* Thin Records workspace controllers
* Shared application services
* Shared query services
* Shared form requests
* Shared authorization policies
* Shared Blade components
* Shared patient and visit workflow services
* Shared action classes
* Shared controller traits where appropriate

Avoid copying full generic controllers into a new `Records` namespace unless separation is genuinely required.

For example, a Records patient controller may delegate to existing patient services while rendering the shared patient views with a Records workspace context.

The Records route layer is primarily responsible for:

* Workspace authorization
* Route context
* Menu context
* Breadcrumb context
* Redirect context
* Records-specific presentation decisions

It must not create a second patient-management implementation.

---

# Phase 4 — Records Dashboard

Create or complete a dedicated Records dashboard.

The route should resolve to:

```text
/records
```

or:

```text
/records/dashboard
```

Use one canonical route and redirect the other to it if both are present.

The department dashboard resolver should map:

```php
DepartmentType::RECORDS => 'records.dashboard'
```

The dashboard should be personalized for Records operations.

Include useful Records metrics where the necessary data already exists, such as:

* Patients registered today
* Patients registered this week
* Visits created today
* Patients checked in today
* Active visits
* Appointments today
* Pending patient-record corrections
* Possible duplicate patients
* Recently updated patient records
* Records activity today

Each metric must:

* Respect permissions
* Use the active department where department scoping applies
* Avoid leaking patient-sensitive information
* Link to a valid `/records/*` destination
* Display a safe empty state where no data exists

Do not add misleading metrics that cannot be reliably calculated from current data.

---

# Phase 5 — Records-Specific Menu Profile

Extend the existing department menu profile or menu registry so that:

```php
DepartmentType::RECORDS
```

receives a dedicated Records menu.

Recommended menu structure:

## Records Dashboard

* Dashboard

## Patient Management

* Patient List
* Register New Patient
* Patient Search
* Recently Registered Patients

Where supported:

* Duplicate Patient Review
* Patient Merge Requests
* Pending Record Corrections

## Visits and Attendance

* Visit List
* Create Visit
* Patient Check-In
* Today’s Attendance
* Active Visits
* Completed Visits
* Appointments

## Patient Records

* Patient Profiles
* Visit History
* Consultation History
* Investigation History
* Admission History
* Uploaded Documents

## Records Reports

* Patient Registration Report
* Attendance Report
* Visit Report
* Appointment Report
* Records Activity Report

## General

* Notifications
* My Profile
* Switch Department

Only show menu items where:

1. The feature exists.
2. The module is enabled.
3. The user possesses the required permission.
4. The active department context permits access.

Do not expose menu items merely because the user belongs to a Records department.

Permissions remain authoritative.

---

# Phase 6 — Workspace-Aware URL Resolution

Introduce or extend a centralized workspace route resolver.

The application must not scatter logic such as:

```php
if ($department->type === DepartmentType::RECORDS) {
    return route('records.patients.show', $patient);
}
```

across multiple views and controllers.

Create or extend a service such as:

```php
DepartmentWorkspaceRouteResolver
WorkspaceUrlResolver
DepartmentRouteResolver
```

Use the naming convention already established in the project.

The resolver should support methods equivalent to:

```php
patientIndex()
patientCreate()
patientShow(Patient $patient)
patientEdit(Patient $patient)

visitIndex()
visitCreate()
visitShow(Visit $visit)
visitEdit(Visit $visit)

appointmentIndex()
appointmentShow(Appointment $appointment)

dashboard()
```

For a Records user, it should return route names under:

```text
records.*
```

For other users, it should preserve their existing workspace or generic route behaviour.

Example:

```php
$workspaceRoutes->patientShow($patient);
```

For an active Records department:

```text
/records/patients/{patient}
```

For another workspace:

```text
The appropriate existing route for that workspace
```

Do not base this solely on the user’s primary department. Use the currently active department context.

---

# Phase 7 — Replace Hardcoded Shared Links

Audit all shared pages used by Records staff.

Replace hardcoded generic links where they break workspace continuity.

Review at minimum:

* Patient index tables
* Patient search results
* Global search results
* Patient cards
* Patient show page
* Patient edit page
* Visit list
* Visit history
* Appointment list
* Dashboard quick actions
* Breadcrumbs
* Action dropdowns
* Empty-state actions
* Pagination tables
* Flash-message action links
* Recently viewed records
* Duplicate-patient results
* Patient merge screens
* Document links
* Report drilldowns

Example of what should be avoided in a shared view:

```php
route('patients.show', $patient)
```

Use the centralized route resolver or a workspace-aware component instead.

Do not change API URLs, integration endpoints, callback URLs, signed URLs, download URLs, or print URLs unless they are explicitly part of the Records browser workspace.

---

# Phase 8 — Workspace-Aware Redirects

All successful Records actions must redirect back into the `/records/*` workspace.

Examples:

After registering a patient:

```text
/records/patients/{patient}
```

After editing a patient:

```text
/records/patients/{patient}
```

After creating a visit:

```text
/records/visits/{visit}
```

After updating an appointment:

```text
/records/appointments/{appointment}
```

After deleting, archiving, merging, correcting, or cancelling an item, return to the appropriate Records list or detail route.

Avoid hardcoding Records redirects inside shared business services.

Use a workspace redirect resolver such as:

```php
$workspaceRedirects->toPatient($patient);
$workspaceRedirects->toVisit($visit);
$workspaceRedirects->toAppointment($appointment);
$workspaceRedirects->patientIndex();
```

Business services should remain unaware of the browser workspace where possible.

---

# Phase 9 — Login and Department Switching

When a user logs in and their active department type is `records`, redirect them to:

```text
/records
```

When a multi-department user switches their active department to a Records department, redirect them to:

```text
/records
```

When switching away from Records, redirect to the corresponding target department workspace.

The Records menu and route context must be determined from the active department selected in the session or existing department-context mechanism.

Do not use only:

```php
$user->department_id
```

when the application already supports multiple departments and an active department context.

---

# Phase 10 — Records Workspace Authorization

The URL prefix is not authorization.

Protect the Records workspace so that access requires:

1. An authenticated user.
2. A valid active department context.
3. Active department type equal to `records`, unless the project already supports an authorized admin-preview mode.
4. The required feature permission.
5. Any relevant module being enabled.
6. Access to the requested patient, visit, appointment, or document under existing policies.

A user from another department who manually enters:

```text
/records/patients
```

must not receive access merely because they possess a broad patient permission.

Use the project’s existing behaviour for unauthorized department workspace access:

* `403`
* workspace unavailable page
* safe redirect

Do not create inconsistent behaviour.

Administrators who are allowed to preview department dashboards may retain that capability through the existing preview mechanism.

---

# Phase 11 — Legacy Route Compatibility

Keep existing generic patient, visit, and appointment routes working for:

* Other departments
* Existing bookmarks
* Integrations
* API consumers
* Print flows
* Signed URLs
* Background jobs
* Internal notifications

For interactive browser requests from an active Records department, generic routes may redirect to their Records equivalents where safe.

Examples:

```text
/patients
→ /records/patients

/patients/{patient}
→ /records/patients/{patient}

/visits
→ /records/visits
```

Apply such redirects carefully.

Do not blindly redirect:

* JSON requests
* API requests
* signed routes
* callbacks
* payment endpoints
* export downloads
* print requests
* AJAX endpoints
* background or system requests

Avoid redirect loops.

Where generic routes are still required internally, allow them to render correctly while ensuring links generated inside the Records workspace remain Records-aware.

---

# Phase 12 — Breadcrumbs and Active Menu State

Records pages must display Records-specific breadcrumbs.

Examples:

```text
Records > Patients
Records > Patients > Register Patient
Records > Patients > Patient Profile
Records > Visits
Records > Visits > Visit Details
Records > Appointments
Records > Reports
```

The sidebar must correctly highlight the active item for nested Records routes.

Examples:

```text
records.patients.show
```

should highlight:

```text
Patient List
```

or the relevant Patient Management parent section.

Do not depend only on exact route-name equality where nested routes are involved.

---

# Phase 13 — Shared View Workspace Context

Pass a clear workspace context to shared views.

The context may include:

```php
[
    'workspaceKey' => 'records',
    'workspaceDepartment' => $activeDepartment,
    'workspaceRoutePrefix' => 'records.',
    'workspaceTitle' => __('records.workspace.title'),
]
```

Use the project’s existing DTO or view-context architecture where available.

Shared views should use this context for:

* Page headings
* Breadcrumbs
* Action URLs
* Back links
* Form actions
* Workspace-specific quick actions
* Empty states

Do not implement workspace detection through repeated direct session reads inside Blade templates.

---

# Phase 14 — Forms and Validation

Records forms must submit to `/records/*` routes.

Examples:

```text
POST /records/patients
PUT /records/patients/{patient}
POST /records/visits
PUT /records/appointments/{appointment}
```

Reuse existing:

* Form requests
* Validation rules
* policies
* DTOs
* action services
* activity logging
* patient privacy controls
* duplicate detection
* visit creation rules

Validation errors must return the user to the same Records URL with their input preserved.

Authorization failures must not leak patient information.

---

# Phase 15 — Patient Privacy and Audit Integrity

The Records workspace handles highly sensitive patient information.

Ensure all existing patient privacy protections remain active.

This includes:

* Masking protected fields
* Permission checks for phone and email
* Sensitive field access audit
* Export restrictions
* Search-result masking
* Patient merge audit
* Record correction audit
* Patient update audit
* Visit creation audit
* Document access audit where already supported

Do not bypass privacy services because the user works in Records.

Where new Records-specific actions are added, record activity using the existing `ActivityLog` infrastructure.

Avoid logging full sensitive values.

---

# Phase 16 — Localization

Add complete English and French localisation for the Records workspace.

Prefer an existing domain file if one exists, otherwise use:

```text
lang/en/records.php
lang/fr/records.php
```

Include keys for:

* Workspace title
* Dashboard title
* Menu sections
* Menu items
* Dashboard metrics
* Empty states
* Unauthorized workspace message
* Breadcrumb labels
* Quick actions
* Reports
* Record corrections
* Duplicate-patient review
* Recently registered patients
* Today’s attendance
* Active visits

Maintain full EN/FR parity.

Do not hardcode visible Records labels in controllers, services, Blade templates, or JavaScript.

---

# Phase 17 — Records Menu Configuration and Extensibility

Implement the Records menu through the existing menu registry or department menu profile service.

Do not build the Records menu directly inside the sidebar Blade template.

The menu definition should be data-driven and support:

* Section ordering
* Route name
* Icon
* Permission
* Module requirement
* Active route patterns
* Badge or count where supported
* Department-type availability

The architecture should make it straightforward to personalize menus later for:

```text
pharmacy
investigation
radiology
finance
stores
nursing
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

Do not implement those other department menus in this phase unless required for safe shared refactoring.

---

# Phase 18 — Automated Verification

Add focused automated tests for the Records workspace.

## Route tests

Verify:

* Records routes exist.
* Route names use `records.*`.
* URLs use `/records/*`.
* Records middleware is attached.
* Generic routes remain available where required.

## Access tests

Verify:

* A Records department user can access authorized Records pages.
* A non-Records department user cannot access the Records workspace.
* A Records user without the necessary permission cannot access protected actions.
* An authorized administrator preview still works if supported.
* Multi-department active context is respected.

## Menu tests

Verify:

* Records users see the Records menu profile.
* Non-Records users do not receive the Records menu profile.
* Unauthorized items are hidden.
* Disabled-module items are hidden.
* Active route highlighting works.

## Redirect tests

Verify:

* Patient creation redirects to `records.patients.show`.
* Patient update remains inside `/records`.
* Visit creation redirects to `records.visits.show`.
* Appointment actions remain inside `/records`.
* Department switching to Records redirects to `/records`.
* Login with an active Records department redirects to `/records`.
* No redirect loops occur.
* JSON and API requests are not incorrectly redirected.

## Shared-view tests

Verify links generated from:

* Patient tables
* Patient profile
* Visit tables
* Search results
* Dashboard cards
* Breadcrumbs
* Action dropdowns

all remain inside `/records/*` for Records users.

## Privacy and audit tests

Verify:

* Existing patient masking remains active.
* Sensitive-field authorization still applies.
* Required activity-log entries are created.
* Records routes do not expose unmasked fields through alternate views.

Run only the focused Records workspace tests and essential route/view/localisation safety checks during implementation.

Do not run the entire UHMS test suite after every phase.

Run one broad relevant suite after all implementation phases are complete.

---

# Phase 19 — Manual Acceptance Scenarios

Confirm the following manually:

## Scenario A — Records login

1. Log in as a user whose active department type is `records`.
2. Confirm the landing URL is `/records`.
3. Confirm the Records-specific menu is visible.
4. Confirm unrelated department-specific menu sections are absent.

## Scenario B — Patient workflow

1. Open `/records/patients`.
2. Register a new patient.
3. Confirm the form submits through `/records/patients`.
4. Confirm the success redirect is `/records/patients/{patient}`.
5. Edit the patient.
6. Confirm the user remains inside `/records/*`.
7. Confirm breadcrumbs and sidebar highlighting remain correct.

## Scenario C — Visit workflow

1. Open a patient through `/records/patients/{patient}`.
2. Create a visit.
3. Confirm the visit URL becomes `/records/visits/{visit}`.
4. Navigate back to the patient.
5. Confirm no generic `/patients/*` or `/visits/*` URLs unexpectedly appear.

## Scenario D — Appointment workflow

1. Open `/records/appointments`.
2. Create or update an appointment.
3. Confirm all redirects remain under `/records/appointments/*`.

## Scenario E — Permissions

1. Remove a Records permission.
2. Confirm the related menu item disappears.
3. Confirm directly entering the route remains forbidden.

## Scenario F — Department switching

1. Log in as a multi-department user.
2. Switch to a Records department.
3. Confirm redirect to `/records`.
4. Switch to another department.
5. Confirm the other department’s dashboard and menu load.

## Scenario G — Legacy compatibility

1. While operating under Records, enter a generic interactive patient route.
2. Confirm it safely resolves or redirects to the Records equivalent.
3. Confirm API, print, export, and signed URLs are unaffected.

---

# Acceptance Criteria

The implementation is accepted only when all the following are true:

1. Users with an active department type of `records` receive a dedicated Records menu.
2. Their default dashboard uses `/records`.
3. Patient, visit, appointment, document, and report pages available to Records staff use `/records/*` URLs.
4. Route names use the `records.*` namespace.
5. Forms submit through Records routes.
6. Redirects remain inside the Records workspace.
7. Breadcrumbs and active menu states are Records-aware.
8. Permissions and enabled modules control menu visibility.
9. A non-Records department user cannot directly access the Records workspace.
10. Multi-department users are evaluated using the active department context.
11. Existing patient and visit business logic is reused rather than duplicated.
12. Generic routes remain functional for other departments and integrations.
13. API, signed, print, callback, and export routes are not incorrectly redirected.
14. Patient privacy protections remain fully active.
15. Relevant actions remain audited.
16. English and French localisation are complete and in parity.
17. Focused Records workspace tests pass.
18. One broad relevant suite passes after the implementation is complete.
19. No route loops, duplicate route names, broken links, or generic URL leaks remain in the Records browser workflow.

---

# Deliverables

Provide:

1. Records workspace route group.
2. Records-specific controllers or thin adapters where required.
3. Records department dashboard.
4. Records menu profile configuration.
5. Workspace-aware URL resolver.
6. Workspace-aware redirect resolver.
7. Updated shared links and forms.
8. Login and department-switch integration.
9. Records breadcrumbs and active-menu handling.
10. EN/FR localisation.
11. Focused feature tests.
12. A final implementation report containing:

* Files created
* Files modified
* Route map
* Records menu map
* Reused services
* Redirect behaviour
* Permissions used
* Privacy and audit checks
* Tests executed
* Test results
* Remaining limitations, if any

Implement the work fully. Do not stop at planning or architecture documentation.
