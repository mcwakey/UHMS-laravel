Use this full prompt for Codex/Copilot:

````text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

The Notifications system is not working correctly, and many important workflows now depend on notifications.

We need a full technical analysis, gap report, repair implementation, and solution report for the notification system.

Notifications are critical for:

- Emergency alerts
- Medication/MAR due and overdue reminders
- Clinical tasks/reminders
- Admission tasks
- Investigation requests/results
- Procedure/theatre requests
- Pharmacy dispensing alerts
- Stock requisitions/transfers
- Low stock alerts
- Billing/payment alerts
- Claim preparation/submission alerts
- Patient merge alerts/approval
- Appointment/follow-up reminders
- System/admin alerts

Your task is to inspect the current UHMS notification implementation, identify why it is not working, fix it properly, and produce reports of what you found and what you changed.

Do not patch only one notification screen. Build a reliable notification foundation for the whole system.

Do not break existing UHMS workflows.

---

# 1. Main Objective

Perform a full notification system audit and repair.

You must:

1. Inspect the current notification implementation.
2. Identify all gaps, bugs, missing pieces, and broken flows.
3. Fix the notification backend.
4. Fix the notification frontend/UI.
5. Fix notification triggers from dependent modules.
6. Ensure notifications are stored, delivered, displayed, counted, read/unread, and actionable.
7. Add reports explaining the issues found and the solutions implemented.
8. Identify remaining improvements that need separate future reports/prompts.

---

# 2. Expected Deliverables

At the end, provide these markdown reports in the project documentation folder, for example:

```text
docs/NOTIFICATIONS_GAP_ANALYSIS.md
docs/NOTIFICATIONS_SOLUTION_REPORT.md
docs/NOTIFICATIONS_REMAINING_RECOMMENDATIONS.md
````

If the project already has a documentation folder, use the existing convention.

The reports must include:

## NOTIFICATIONS_GAP_ANALYSIS.md

Explain:

* Current notification architecture found
* Existing notification tables/models/classes
* Existing notification UI/components
* Existing notification channels
* Existing notification triggers
* What is broken
* What is missing
* What is duplicated
* What is unused/dead code
* Which workflows depend on notifications
* Root causes of notification failure

## NOTIFICATIONS_SOLUTION_REPORT.md

Explain:

* Files changed
* Migrations added/updated
* Models updated
* Services created/updated
* Controllers/routes created/updated
* Frontend components updated
* Notification triggers fixed
* Queue/scheduler changes
* Tests added
* How to verify the fix
* Known limitations

## NOTIFICATIONS_REMAINING_RECOMMENDATIONS.md

Explain:

* Remaining notification improvements
* Advanced notification features to build later
* External SMS/WhatsApp/email push recommendations
* Real-time broadcasting recommendations
* Escalation workflow improvements
* Risk areas that need more testing

---

# 3. Notification System Must Support

UHMS notifications must support:

```text
database/in-app notifications
read/unread status
notification dropdown/header count
notification list page
notification detail/action links
priority levels
module/source tracking
recipient targeting
role/department targeting
task/reminder notifications
due/overdue alerts
escalation alerts
auditability
```

Optional but prepare for later:

```text
email notifications
SMS notifications
WhatsApp notifications
browser push notifications
real-time WebSocket/broadcast notifications
sound alerts
```

Do not implement external SMS/WhatsApp unless infrastructure already exists.

---

# 4. Inspect Current Implementation

First inspect the project for:

```text
Notification models
Laravel notification classes
database notifications table
custom notifications table if any
notification services
notification controllers
notification routes
notification Vue/Inertia components
notification Blade partials
notification dropdown/header component
notification bell/count
notification read/unread logic
notification mark-as-read actions
notification queue configuration
scheduler/cron configuration
broadcasting configuration
event/listener setup
jobs
policies/permissions
seeders
```

Search for:

```text
Notification
notifications
notify
notifiable
database_notifications
markAsRead
unreadNotifications
read_at
broadcast
event
listener
job
queue
scheduler
reminder
alert
task
overdue
```

---

# 5. Decide Notification Architecture

If the project already uses Laravel’s built-in notifications, use and fix that.

Laravel default structure:

```text
notifications table
notifiable_type
notifiable_id
type
data
read_at
created_at
updated_at
```

If the project has a custom notification table, inspect whether it is better to keep or migrate.

Preferred approach:

Use Laravel database notifications unless the existing custom system is already deeply integrated.

Do not run two parallel notification systems unless unavoidable.

If both exist, consolidate or create a bridge service so the UI reads from one consistent source.

---

# 6. Notification Data Requirements

Each notification should include enough structured data.

Recommended data payload:

```json
{
  "title": "Medication overdue",
  "message": "Ceftriaxone 1g IV for Ama Mensah is overdue by 25 minutes.",
  "module": "MAR",
  "source_type": "medication_administration_schedule",
  "source_id": 12,
  "priority": "HIGH",
  "action_url": "/admin/admissions/5/mar-chart",
  "patient_id": 10,
  "visit_id": 22,
  "admission_id": 5,
  "emergency_case_id": null,
  "department_id": 3,
  "metadata": {}
}
```

Supported priority levels:

```text
LOW
NORMAL
HIGH
URGENT
CRITICAL
```

Supported modules:

```text
EMERGENCY
ADMISSION
CONSULTATION
MAR
CLINICAL_TASKS
INVESTIGATION
PROCEDURE
THEATRE
PHARMACY
BILLING
CLAIMS
STOCK
PATIENTS
SYSTEM
```

---

# 7. Notification Service

Create or update a central service:

```text
NotificationService
```

Responsibilities:

```text
send notification to one user
send notification to multiple users
send notification to role
send notification to department users
send notification to permission holders
create database notification
avoid duplicate active notifications where needed
mark notification read
mark all read
fetch unread count
fetch latest notifications
build action URL
```

Suggested methods:

```php
notifyUser(User $user, string $type, array $data): void;

notifyUsers(Collection|array $users, string $type, array $data): void;

notifyRole(string $role, string $type, array $data): void;

notifyDepartment(int $departmentId, string $type, array $data): void;

notifyPermission(string $permission, string $type, array $data): void;

unreadCount(User $user): int;

latest(User $user, int $limit = 10);

markAsRead(User $user, string $notificationId): void;

markAllAsRead(User $user): void;
```

Do not duplicate notification-sending code inside many controllers.

---

# 8. Notification Types / Classes

Create/update Laravel notification classes as needed.

At minimum support generic notification:

```text
GenericSystemNotification
```

Better: create specific notifications for important modules:

```text
EmergencyAlertNotification
MedicationDueNotification
MedicationOverdueNotification
ClinicalTaskNotification
InvestigationRequestNotification
InvestigationResultNotification
ProcedureRequestNotification
TheatreCaseNotification
PharmacyDispensingNotification
StockAlertNotification
BillingNotification
ClaimNotification
PatientMergeNotification
```

If too many classes are excessive, use one generic database notification with structured type/module data.

---

# 9. Notification UI

Fix or create notification UI.

Must include:

## Header/Bell Component

Show:

```text
notification bell icon
unread count badge
latest unread notifications
priority indicator
time ago
mark as read
view all
```

## Notification List Page

Show:

```text
all notifications
filter by read/unread
filter by module
filter by priority
search
pagination
mark selected as read
mark all as read
open action link
```

## Notification Detail / Action

Clicking notification should:

```text
mark as read
redirect to action_url
```

If action_url is missing, open notification detail or list.

---

# 10. Notification Read/Unread Logic

Fix read/unread behavior.

Requirements:

```text
Unread count must be accurate.
Clicking notification marks it as read.
Mark as read works for one notification.
Mark all as read works.
Read notifications should not appear as unread.
Unread count should update without full page reload where possible.
```

If using Inertia:

```text
share unread notification count globally through HandleInertiaRequests
partial reload notifications after mark read
```

Do not calculate unread count with expensive query on every request if performance is bad. Cache if needed later.

---

# 11. Inertia Shared Props

If using Inertia, add shared props:

```php
'notifications' => [
    'unread_count' => ...,
    'latest' => ...,
]
```

Only load a small number of latest notifications globally.

Example:

```text
latest limit = 5 or 10
```

Do not load all notifications into every page.

---

# 12. Routes / Controllers

Create or update notification routes.

Suggested:

```php
Route::prefix('admin/notifications')
    ->name('admin.notifications.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('index');
        Route::get('/latest', [NotificationController::class, 'latest'])->name('latest');
        Route::post('/{notification}/read', [NotificationController::class, 'markAsRead'])->name('read');
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead'])->name('read-all');
        Route::delete('/{notification}', [NotificationController::class, 'destroy'])->name('destroy');
    });
```

Adapt route names to existing project conventions.

---

# 13. Queue / Scheduler Audit

Notifications may fail because queue/scheduler is not working.

Inspect:

```text
.env queue connection
QUEUE_CONNECTION
failed_jobs table
jobs table
queue worker setup
scheduled tasks
app/Console/Kernel.php or routes/console.php
Laravel version scheduler format
notification jobs
event listeners queued or sync
```

If notifications are queued:

* ensure jobs table exists
* ensure failed_jobs table exists
* ensure queue worker instructions are documented
* ensure local/dev can use sync queue if needed

For dev reliability, if no worker is running, important database notifications may need to be created synchronously.

Do not silently queue critical database notification if queue worker is absent.

---

# 14. Scheduler for Reminders

For reminders/due/overdue tasks, implement or fix scheduled command.

Examples:

```text
clinical-tasks:check-due
medications:check-overdue
notifications:dispatch-reminders
```

The command should:

* find due tasks
* find overdue tasks
* create notifications
* avoid duplicate notifications
* escalate where needed
* update last_notified_at / escalation_level if available

If scheduler is not configured, document commands in solution report.

---

# 15. Duplicate Notification Prevention

Avoid spamming duplicate notifications.

For recurring checks, implement deduplication.

Example:

```text
Medication overdue for same schedule should not notify every minute.
```

Use:

```text
source_type
source_id
type
recipient
last_notified_at
deduplication window
```

If Laravel default notifications are used, dedupe through service query before sending.

Suggested dedupe window:

```text
15 minutes for due reminders
30 minutes for overdue reminders
```

Make configurable.

---

# 16. Notification Triggers to Fix

Audit and fix notification triggers for these modules.

## Emergency

Notify:

```text
new RED emergency case
patient waiting triage
triage overdue
emergency medication overdue
urgent investigation requested/result ready
ready for disposition
```

Recipients:

```text
Emergency nurses
Emergency doctors
Triage nurses
Emergency supervisor
```

## Admission

Notify:

```text
new admission
bed assigned
patient transferred
vitals monitoring due
clinical task overdue
```

Recipients:

```text
ward nurses
assigned doctor
ward supervisor
```

## MAR / Medication Administration

Notify:

```text
medication due soon
medication due now
medication overdue
medication held
medication missed
reaction recorded
```

Recipients:

```text
assigned nurse
ward nurses
emergency nurses
ward supervisor
doctor if escalated
```

## Clinical Tasks

Notify:

```text
task assigned
task due
task overdue
task completed
task escalated
```

## Investigations

Notify:

```text
new investigation request
emergency/urgent investigation request
result entered
result verified
result rejected/correction required
```

Recipients:

```text
investigation department users
requesting doctor
emergency/consultation team
```

## Procedures / Theatre

Notify:

```text
procedure requested
procedure accepted
theatre case scheduled
theatre case rescheduled
pre-op incomplete
procedure completed
procedure cancelled/postponed
```

## Pharmacy

Notify:

```text
new prescription awaiting pharmacy review
drug billed and ready to dispense
partial dispensing
out of stock drug
dispensing completed
```

## Stock

Notify:

```text
low stock
critical stock
out of stock
stock requisition submitted
stock requisition approved/rejected
stock transfer sent
stock transfer received/acknowledged
purchase order received
```

## Billing

Notify:

```text
invoice created
payment received
invoice overdue
large outstanding balance
```

## Claims

Notify:

```text
claim prepared
claim missing CCC/verification code
claim ready for submission
claim submitted
claim rejected
claim paid
```

## Patient Merge

Notify:

```text
possible duplicate found
merge requested
merge approved
merge completed
merge failed
emergency identity confirmed
```

---

# 17. Notification Recipients

Recipient targeting must be reliable.

Implement helper methods to get recipients by:

```text
user
role
permission
department
assigned doctor
assigned nurse
patient care team
stock location department
investigation department
procedure department
ward
emergency unit
```

Do not hardcode only Super Admin as recipient.

Do not notify every user for every event.

---

# 18. Notification Permissions

Add or verify permissions:

```text
notifications.view
notifications.mark_read
notifications.delete
notifications.manage

notifications.emergency.receive
notifications.admission.receive
notifications.mar.receive
notifications.investigation.receive
notifications.procedure.receive
notifications.pharmacy.receive
notifications.stock.receive
notifications.billing.receive
notifications.claims.receive
notifications.patient_merge.receive
```

Use existing permission style if already defined.

---

# 19. Notification Settings

Prepare notification preferences/settings if not already available.

For first implementation, global settings are enough.

Suggested config:

```text
notifications.enabled
notifications.due_reminder_minutes
notifications.overdue_reminder_minutes
notifications.dedupe_minutes
notifications.latest_limit
notifications.poll_interval_seconds
```

Optional later:

```text
per-user notification preferences
per-role preferences
mute module notifications
external channel preferences
```

---

# 20. Frontend Polling / Refresh

If real-time broadcasting is not implemented, use polling.

Recommended:

```text
poll latest notifications every 30-60 seconds
poll urgent board counts separately if needed
```

Do not overload server.

Only fetch:

```text
unread count
latest notifications
```

Full notification list should paginate.

If broadcasting exists, fix/implement Laravel Echo events carefully.

---

# 21. Notification Actions

Every actionable notification should have an action_url.

Examples:

```text
Emergency RED case → /admin/emergency/cases/{id}
Medication overdue → /admin/admissions/{admission}/mar-chart
Investigation result verified → /admin/investigations/requests/{id}
Procedure scheduled → /admin/theatre/cases/{id}
Stock out → /admin/stock/balances
Claim rejected → /admin/claims/{id}
Patient merge requested → /admin/patients/merge/requests/{id}
```

Do not create dead notifications with no way to act unless they are purely informational.

---

# 22. Audit / Logging

Notification failures should be traceable.

If a notification cannot be sent:

* log error
* do not crash critical workflow unless notification is mandatory
* record in solution report

Consider adding:

```text
notification_logs
```

only if existing logs are insufficient.

Recommended fields:

```text
id
type
module
recipient_id nullable
source_type nullable
source_id nullable
status
error_message nullable
created_at
```

But avoid overbuilding if Laravel notifications already suffice.

---

# 23. Notification Testing Page / Debug Tool

Create a dev/admin-only notification test page or command.

Suggested command:

```bash
php artisan notifications:test --user=1
```

It should send a test database notification to a user.

Also create a simple route/page if useful:

```text
/admin/notifications/debug
```

Only accessible to Super Admin/dev.

Purpose:

* verify database notifications
* verify unread count
* verify UI dropdown
* verify mark as read
* verify action links

Do not expose debug tools to normal users.

---

# 24. Common Root Causes to Check

Specifically check for:

```text
notifications table missing
wrong notifiable model
User model missing Notifiable trait
queue worker not running
notifications queued but never processed
failed jobs
wrong route/action_url
frontend not reading latest notifications
unread count not shared through Inertia
markAsRead route broken
CSRF/method mismatch
notification dropdown component not mounted
layout not receiving props
permissions hiding notifications
recipient query returning no users
department/role relationships broken
broadcasting configured but not connected
scheduler not running
duplicate custom notification tables causing confusion
```

Fix actual root causes, not symptoms.

---

# 25. Database / Model Checks

Verify:

## User model

Must include if using Laravel notifications:

```php
use Illuminate\Notifications\Notifiable;
```

## notifications table

Must exist if database channel is used.

If missing:

```bash
php artisan notifications:table
php artisan migrate
```

or create migration manually.

## Notifiable relationships

Ensure current authenticated user can access:

```php
$user->notifications()
$user->unreadNotifications()
$user->readNotifications()
```

---

# 26. UI Failure Checks

Verify:

* layout renders notification dropdown
* notification prop exists on every authenticated page
* unread count is not null
* latest notifications list displays
* mark read action updates UI
* action links work
* mobile/responsive layout works
* empty state displays properly
* priority/status badges show correctly

---

# 27. Implementation Reports

After fixing, create:

## docs/NOTIFICATIONS_GAP_ANALYSIS.md

Include:

```text
Overview
Current implementation found
Broken areas
Root causes
Affected modules
Risk level
Recommended fix plan
```

## docs/NOTIFICATIONS_SOLUTION_REPORT.md

Include:

```text
Summary of fixes
Files changed
Migrations
Services
Controllers
Frontend components
Triggers restored
Queue/scheduler changes
Testing performed
How to verify
```

## docs/NOTIFICATIONS_REMAINING_RECOMMENDATIONS.md

Include:

```text
Real-time broadcasting plan
External SMS/WhatsApp/email plan
Per-user preferences
Advanced escalation plan
Notification analytics
Unresolved risks
Future implementation prompts needed
```

If another module needs separate deep analysis, mention it clearly in the remaining recommendations report.

---

# 28. Tests Required

Add or update tests.

## Core Notifications

1. User can receive database notification.
2. User unread count increases after notification.
3. User can list latest notifications.
4. User can mark one notification as read.
5. User can mark all notifications as read.
6. Read notifications do not count as unread.
7. Notification action URL is stored and returned.
8. Notification priority/module is stored and displayed.

## Service

9. NotificationService can notify one user.
10. NotificationService can notify multiple users.
11. NotificationService can notify users by role.
12. NotificationService can notify users by department.
13. Duplicate prevention works for same source/type/user.
14. Missing recipient does not crash workflow.

## UI

15. Header bell shows unread count.
16. Dropdown shows latest notifications.
17. Clicking notification marks it as read.
18. View all opens notification list page.
19. Notification list paginates.
20. Filters by module/read status work if implemented.

## Workflow Triggers

21. Emergency RED case creates notification.
22. Medication overdue creates notification.
23. Clinical task assigned creates notification.
24. Investigation result verified notifies requesting doctor.
25. Procedure/theatre request notifies relevant users.
26. Stock low/out creates stock notification.
27. Claim rejected creates claim notification.
28. Patient merge request creates notification.

## Scheduler / Queue

29. Due task command creates due notifications.
30. Overdue task command creates overdue notifications.
31. Duplicate due reminders are not created inside dedupe window.
32. Failed notification jobs are handled/logged.

---

# 29. Verification Checklist

After implementation, verify manually:

```text
Login as admin
Send test notification
Unread count appears
Dropdown shows notification
Click notification opens target page
Notification becomes read
Mark all as read works

Create emergency RED case
Emergency notification appears for emergency users

Create medication overdue/due task
MAR notification appears for nurses

Verify notification reports exist in docs folder
```

---

# 30. Deliverables

Provide:

1. Full notification gap analysis report.
2. Notification solution report.
3. Remaining recommendations report.
4. NotificationService or equivalent central service.
5. Fixed notification table/model setup.
6. Fixed User Notifiable setup.
7. Fixed notification routes/controllers.
8. Fixed notification dropdown/list UI.
9. Fixed read/unread logic.
10. Fixed Inertia shared props or layout data.
11. Fixed queue/scheduler issues.
12. Fixed notification triggers in critical modules.
13. Duplicate notification prevention.
14. Tests or verification notes.
15. Files modified.
16. Remaining TODOs.

---

# 31. Important Rules

Do not leave notifications half-working.

Do not create multiple competing notification systems.

Do not notify only Super Admin for everything.

Do not rely on queue if queue worker is not configured for critical database notifications.

Do not create notification spam.

Do not hide notification errors.

Do not break dependent modules.

Do not implement external SMS/WhatsApp unless infrastructure already exists.

Do not remove existing notifications without replacing them.

Now inspect the UHMS notification implementation, identify all gaps, fix the notification system, restore dependent workflow triggers, and generate the required reports.

```
```
