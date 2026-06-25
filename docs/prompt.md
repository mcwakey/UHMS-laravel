You are working on the UHMS Laravel/Inertia healthcare management system.

Goal:
Implement a dynamic Visit Status Flow that supports proper statistical reporting without hardcoding every possible visit scenario into one status field.

Important principle:
Do NOT mix workflow status, source of visit, and attendance classification into one field.
'waiting' status should be changed to 'queued'
'waiting_consultation' status should be changed to 'waiting'
'visit_status_logs' should be changed to 'visit_status'

Use separate concepts:

1. visit_status
This represents where the visit is in the workflow.

2. visit_source
This represents how the visit started.

3. attendance_class
This represents the statistical category of the attendance.

The system must support dynamic configuration, future extension, and clean statistics.

Required visit_status values:
- created
- registered
- scheduled
- confirmed
- checked_in
- walked_in
- queued
- triage
- waiting
- consultation
- admitted
- discharged
- completed
- cancelled
- no_show
- abandoned

Required visit_source values:
- direct
- appointment
- emergency
- referral
- follow_up
- review
- online_booking
- walk_in

Required attendance_class values:
- first_ever
- first_attendance_of_year
- subsequent_attendance
- emergency_attendance
- referral_attendance

Core workflow rules:

Direct visit flow:

1. Direct first time ever at the hospital
null -> created
created -> checked_in
checked_in -> queued

visit_source: direct
attendance_class: first_ever

2. Direct first attendance in the current year
null -> registered
registered -> checked_in
checked_in -> queued

visit_source: direct
attendance_class: first_attendance_of_year

3. Direct subsequent attendance in the current year
null -> checked_in
created -> checked_in

visit_source: direct
attendance_class: subsequent_attendance

4. When an appointment patient arrives:
null -> scheduled
scheduled -> checked_in
checked_in -> queued

visit_source: appointment
attendance_class: subsequent_attendance

Statistical logic:

The system must be able to report:
- Total direct visits
- Total appointment visits
- Total first-ever attendances
- Total first attendance of the year
- Total subsequent attendances
- Total checked-in patients
- Appointment patients who came
- Appointment no-shows
- Cancelled appointments
- Patients who attended more than once in the year
- Attendance counts by date range, department, doctor, branch, visit source, and attendance class

Attendance classification rules:

When creating a visit, determine attendance_class automatically:

1. first_ever:
Patient has no previous visits/attendances in the system.

2. first_attendance_of_year:
Patient has previous visits historically, but no completed/active attendance in the current calendar year.

3. subsequent_attendance:
Patient already has at least one attendance in the current calendar year.

Use the visit date/year, not only the current server date, when determining yearly attendance.

Dynamic configuration:

Implement visit statuses, visit sources, attendance classes, and transition reasons in a way that can be extended later.

Services/classes:

Create or update a VisitStatusFlowService responsible for:
- determining attendance_class
- resolving initial visit status
- validating allowed transitions
- applying transitions
- writing transition history
- updating timestamp fields such as checked_in_at, arrived_at, completed_at, cancelled_at, no_show_at
- preventing invalid transitions unless the user has a privileged permission

Initial visit creation behavior:

When creating a direct visit:
- determine attendance_class automatically
- assign visit_source = direct
- assign initial visit_status according to the direct visit flow

When creating a visit from an appointment:
- determine attendance_class automatically
- assign visit_source = appointment
- assign initial visit_status:
    - null -> scheduled
    - scheduled -> checked_in
    - checked_in -> queued

Permissions:
Only privileged users should configure status flows or override invalid transitions.

UI requirements:

1. Visit create page:
- Do not ask the user to manually select attendance_class.
- The system should calculate it automatically.
- Show a small label/badge after patient selection if possible:
    - First ever
    - First attendance this year
    - Subsequent attendance

2. Appointment check in page:
- Show a small label/badge after patient selection if possible:
    - First ever
    - First attendance this year
    - Subsequent attendance
- Appointment check-in should move the visit to initial visit_status workflow.
    - null -> scheduled
    - scheduled -> checked_in
    - checked_in -> queued

3. Visit list:
Show badges for:
- visit_status
- visit_source
- attendance_class

4. Visit details:
Dinamicaly display the Visit Status Flow according to the patient parcour
Show status history.

Statistics/reporting:

Add or prepare query scopes so reports can filter by:
- visit_status
- visit_source
- attendance_class
- date range
- branch
- department
- doctor/user

Add model scopes if appropriate:
- scopeStatus($query, string $code)
- scopeSource($query, string $code)
- scopeAttendanceClass($query, string $code)
- scopeBetweenVisitDates($query, $from, $to)

Important:
Do not break existing visits, appointments, emergency visits, billing, queues, triage, or consultation flows.

Audit/localisation:

Use translation files for labels; do not hardcode UI text.

Add English and French translations for:
- visit statuses
- visit sources
- attendance classes
- UI labels
- validation messages
- success/error flash messages

Use the existing localisation structure of the project.

Testing:

Do not run the broad full-suite immediately during implementation.

During implementation, run only minimal safety checks where necessary:
- PHP syntax/lint for changed files
- migration status or migrate on test DB if available
- route/view compilation checks if the project supports them
- targeted tests for visit creation, appointment check-in, and status transition service

At the end of the whole implementation, run the wider relevant suite.

Minimum targeted tests to add/update:

1. Direct first-ever visit:
Expected:
visit_source = direct / appointment
attendance_class = first_ever
visit_status = created

2. Direct first attendance of year:
Expected:
visit_source = direct / appointment
attendance_class = first_attendance_of_year
visit_status = registered

3. Direct subsequent attendance:
Expected:
visit_source = direct / appointment
attendance_class = subsequent_attendance
visit_status = checked_in

4. Appointment check-in:
Expected:
null -> scheduled
scheduled -> checked_in
checked_in -> queued

5. Invalid transition:
Expected:
transition is rejected unless user has override permission

6. Statistics:
Expected:
reports can count visits by source, attendance_class, and status.

Deliverables:
- migrations
- models/relationships
- seeders
- VisitStatusFlowService
- controller updates
- UI updates where needed
- translations EN/FR
- targeted tests
- brief implementation report explaining changed files, new logic, and how to extend the flow later

Be careful:
- Do not hardcode NHIS or any insurance-specific behavior here.
- Do not break emergency visit behavior.
- Do not break appointment flow.
- Do not break billing/queue generation.
- Do not make attendance_class manually editable by normal users.
- Keep the flow dynamic and configurable.
