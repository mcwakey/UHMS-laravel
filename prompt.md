I’ll treat **“ogs” as “logs”** — system/activity/audit logs. Here’s the full prompt:

```text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to perform a full analysis, repair, and standardization of the Logs system across UHMS.

Logs are critical because many modules now require traceability, auditability, accountability, correction history, and workflow tracking.

The system must properly log actions from:

- Emergency
- Admission
- Consultation
- Medication Administration / MAR
- Clinical Tasks / Reminders
- Investigations
- Procedures / Theatre
- Pharmacy
- Billing / Payments
- Claims
- Stock / Inventory
- Purchase Orders
- Supplier Ledger
- Patient Merge
- Insurance
- User / Role / Permission changes
- System settings
- Notifications
- Login / Authentication
- Critical data edits and corrections

Your task is to inspect the current UHMS logs implementation, identify gaps, fix or standardize the logging system, and generate proper reports.

Do not patch only one module. Build a reliable logging foundation for the whole system.

Do not break existing UHMS workflows.

---

# 1. Main Objective

Perform a full logging/audit system audit and repair.

You must:

1. Inspect the current logging implementation.
2. Identify all existing logs, activity logs, audit logs, event logs, and module-specific logs.
3. Identify what is broken, duplicated, missing, or inconsistent.
4. Standardize logging across UHMS.
5. Ensure important actions are logged.
6. Ensure logs capture who did what, when, where, and why.
7. Ensure logs are searchable and filterable.
8. Ensure logs are visible to authorized users.
9. Ensure sensitive logs are protected.
10. Generate reports explaining gaps, fixes, and remaining recommendations.

---

# 2. Expected Documentation Reports

Create these markdown reports in the project documentation folder:

docs/LOGS_GAP_ANALYSIS.md
docs/LOGS_SOLUTION_REPORT.md
docs/LOGS_REMAINING_RECOMMENDATIONS.md

If the project already has a documentation folder convention, follow it.

---

# 3. LOGS_GAP_ANALYSIS.md

This report must explain:

- Current logging architecture found
- Existing log tables
- Existing models/services/classes
- Existing module-specific logs
- Existing activity/audit log package if any
- What actions are currently logged
- What important actions are not logged
- What logs are duplicated or inconsistent
- What logs are unused/dead code
- What frontend log views exist
- What log permissions exist
- Root causes of logging problems
- Modules depending on logs
- Risk level of current logging gaps

---

# 4. LOGS_SOLUTION_REPORT.md

This report must explain:

- Summary of fixes made
- Files changed
- Migrations added/updated
- Models updated
- Services created/updated
- Controllers/routes created/updated
- Frontend pages/components updated
- Log triggers restored/added
- Permissions added/updated
- Tests added
- How to verify logs are working
- Known limitations

---

# 5. LOGS_REMAINING_RECOMMENDATIONS.md

This report must explain:

- Remaining improvements
- Advanced audit features to implement later
- Security/event monitoring recommendations
- Log retention and archiving recommendations
- Export/reporting recommendations
- Risk areas needing deeper testing
- Future implementation prompts needed

---

# 6. Core Logging Requirements

UHMS logs must answer:

Who did it?
What did they do?
When did they do it?
Where/module did it happen?
Which record was affected?
What changed?
Why was it changed, if reason is required?
Was it normal action, correction, override, or system event?

Each log should support:

- actor/user
- action
- module
- source/subject type
- source/subject ID
- old values
- new values
- description
- reason
- IP address if available
- user agent if useful
- severity/level
- created_at

---

# 7. Recommended Central Log Table

If no strong logging system exists, create a central table:

activity_logs
- id
- user_id nullable
- module nullable
- action
- description nullable
- subject_type nullable
- subject_id nullable
- causer_type nullable
- causer_id nullable
- patient_id nullable
- visit_id nullable
- admission_id nullable
- emergency_case_id nullable
- department_id nullable
- severity default INFO
- old_values json nullable
- new_values json nullable
- metadata json nullable
- reason nullable
- ip_address nullable
- user_agent nullable
- created_at
- updated_at

Severity levels:

DEBUG
INFO
NOTICE
WARNING
ERROR
CRITICAL
SECURITY

If the project already uses Spatie Activitylog or another audit package, inspect it and decide whether to extend it instead of creating a parallel system.

Do not create competing log systems if a good one already exists.

---

# 8. Log Service

Create or update a central service:

ActivityLogService

Responsibilities:

- log normal actions
- log clinical actions
- log financial actions
- log stock actions
- log corrections
- log overrides
- log security events
- log system events
- attach patient/visit/admission/emergency context
- capture old/new values
- capture request metadata
- support module filtering

Suggested methods:

log(string $module, string $action, array $data = []): void;

logCreated(Model $model, string $module, ?string $description = null): void;

logUpdated(Model $model, string $module, array $oldValues, array $newValues, ?string $reason = null): void;

logDeleted(Model $model, string $module, ?string $reason = null): void;

logCorrection(Model $model, string $module, array $oldValues, array $newValues, string $reason): void;

logOverride(Model $model, string $module, array $data, string $reason): void;

logSecurity(string $action, array $data = []): void;

Do not scatter logging logic randomly across controllers.

---

# 9. Module Names

Use consistent module names:

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
PAYMENTS
CLAIMS
STOCK
PURCHASE_ORDERS
SUPPLIER_LEDGER
PATIENTS
PATIENT_MERGE
INSURANCE
USERS
ROLES
PERMISSIONS
SETTINGS
NOTIFICATIONS
AUTH
SYSTEM

---

# 10. Action Names

Use consistent action names:

CREATED
UPDATED
DELETED
VIEWED
APPROVED
REJECTED
CANCELLED
COMPLETED
SUBMITTED
VERIFIED
REOPENED
ASSIGNED
TRANSFERRED
DISPENSED
ADMINISTERED
BILLED
PAID
REFUNDED
CORRECTED
OVERRIDE_UPDATED
OVERRIDE_DELETED
STATUS_CHANGED
LOGIN
LOGOUT
FAILED_LOGIN
PASSWORD_CHANGED
PERMISSION_CHANGED
ROLE_CHANGED
MERGED
EXPORTED
PRINTED

---

# 11. Clinical Logging Requirements

Clinical logs are critical.

Log actions for:

- consultation started
- consultation completed
- complaint added/edited
- HOPC added/edited
- examination added/edited
- diagnosis added/edited
- prescription added/edited
- investigation requested
- investigation result entered
- investigation result verified
- procedure requested
- theatre case updated
- emergency triage recorded
- emergency vitals recorded
- admission vitals recorded
- clinical task created/completed
- medication administered
- medication held/missed/refused
- MAR correction
- patient deceased marking
- patient merge

Every clinical correction must capture:

- old value
- new value
- corrected by
- correction reason
- correction time

Do not delete clinical history silently.

---

# 12. Financial Logging Requirements

Billing and finance actions must be logged.

Log:

- invoice created
- invoice item added
- invoice item updated
- invoice item cancelled
- discount applied
- payment recorded
- payment reversed
- refund recorded
- claim prepared
- claim submitted
- claim approved
- claim rejected
- claim paid
- supplier payment recorded
- supplier credit note
- supplier debit note
- purchase order approved
- purchase order received

Financial logs must preserve:

- old amount
- new amount
- user
- reason if correction
- payment reference
- invoice/claim/payment IDs

Do not recalculate or silently mutate financial data without logs.

---

# 13. Stock / Inventory Logging Requirements

Stock logs are critical.

Log:

- product created/updated
- product department changed
- purchase order created/approved/received
- stock received
- stock transfer requested
- stock transfer approved
- stock transfer dispatched
- stock transfer received/acknowledged
- stock adjustment
- stock return
- purchase return
- pharmacy dispensing
- ward consumable usage
- emergency consumable usage
- investigation consumable usage
- procedure/theatre consumable usage
- stock correction
- low stock alert generated

Stock logs must capture:

- product
- stock location
- quantity before
- quantity changed
- quantity after
- movement type
- source document
- performed by
- reason

Do not change stock balances without stock movement and log.

---

# 14. Emergency Logging Requirements

Log:

- emergency case created
- unknown patient created
- emergency identity confirmed
- emergency triage recorded
- triage auto-category calculated
- triage category overridden
- bay assigned/released
- main doctor assigned
- nurse assigned
- contributor added
- emergency note added/edited
- emergency medication ordered/administered
- emergency investigation requested/result received
- emergency procedure requested/performed
- emergency consumable used
- emergency disposition completed
- emergency transferred to admission/OPD/theatre
- emergency death/DOA recorded

Logs must appear in Emergency Timeline and Visit Preview where appropriate.

---

# 15. Admission Logging Requirements

Log:

- admission created
- bed assigned
- bed transferred
- admission status changed
- admission vitals recorded
- admission notes added
- clinical task created/completed
- medication administered
- patient discharged
- admission converted/transferred if applicable

---

# 16. MAR / Medication Administration Logging

Log:

- medication order created
- medication schedule generated
- clinical task created for dose
- dose administered
- dose held
- dose missed
- dose refused
- dose skipped
- adverse reaction recorded
- medication stopped
- medication held
- schedule cancelled
- MAR correction

MAR logs must capture:

- medication
- dose
- scheduled time
- actual time
- administered by
- reason not given
- reaction
- correction reason if changed

---

# 17. Procedure / Theatre Logging

Log:

- procedure requested
- procedure accepted
- theatre case created
- theatre room assigned
- theatre rescheduled
- theatre team assigned
- pre-op checklist updated
- anaesthesia note added/edited
- operative note added/edited
- recovery note added/edited
- theatre consumables used
- case completed
- case cancelled/postponed
- room maintenance/block created

---

# 18. Patient Merge Logging

Patient merge must be fully logged.

Log:

- merge request created
- merge preview generated
- merge approved/rejected
- merge executed
- each major table reassignment
- patient aliases created
- duplicate patient marked merged
- identity confirmed
- merge failed

Logs must include:

- main_patient_id
- duplicate_patient_id
- records moved summary
- field resolution
- performed_by
- reason

---

# 19. Authentication / Security Logging

Log security events:

- login
- logout
- failed login
- password changed
- password reset requested
- user created
- user disabled
- role changed
- permission changed
- unauthorized access attempt if detectable
- sensitive export
- settings changed

Do not expose sensitive data like passwords/tokens in logs.

---

# 20. Log UI

Create or repair a Log Viewer page.

Suggested menu:

System
├── Activity Logs
├── Clinical Logs
├── Financial Logs
├── Stock Logs
├── Security Logs

Or one Activity Logs page with filters.

Log viewer should support:

- search
- filter by module
- filter by action
- filter by severity
- filter by user
- filter by patient
- filter by visit
- filter by date range
- filter by subject type
- pagination
- view details
- export if authorized

Do not load all logs at once.

---

# 21. Log Detail View

Each log detail should show:

- action
- module
- description
- user/actor
- subject
- patient/visit context
- old values
- new values
- metadata
- reason
- IP/user agent if available
- timestamp

For old/new values, show readable diff if possible.

---

# 22. Permissions

Add or verify permissions:

logs.view
logs.view_clinical
logs.view_financial
logs.view_stock
logs.view_security
logs.export
logs.delete
logs.manage_retention

Most users should not see all logs.

Suggested access:

- Super Admin: all logs
- Admin: most logs
- Records Officer: patient/merge logs
- Finance: billing/payment/claims logs
- Pharmacist/Store: stock/pharmacy logs
- Doctor/Nurse: clinical logs related to their patients if policy allows
- Security/Admin: auth/security logs

Do not expose sensitive financial/security logs to normal users.

---

# 23. Log Retention

Prepare retention policy.

Do not delete logs by default.

Create recommendation in report for:

- clinical logs retained long-term
- financial logs retained long-term
- security logs retained according to policy
- old debug logs archived
- exports restricted

Do not implement auto-delete unless explicitly configured.

---

# 24. Log Export

If export infrastructure exists, allow authorized export.

Formats:

- CSV
- Excel
- PDF optional

Exports must be logged:

action = EXPORTED
module = SYSTEM or LOGS

Do not allow unrestricted export of sensitive logs.

---

# 25. Frontend / Inertia Requirements

If using Inertia/Vue:

Create or update:

resources/js/Pages/Logs/Index.vue
resources/js/Pages/Logs/Show.vue

Components:

LogFilterPanel
LogTable
LogSeverityBadge
LogActionBadge
LogDetailPanel
JsonDiffViewer

Use pagination.

Do not load huge JSON blobs in table rows. Show them in detail modal/page.

---

# 26. Routes / Controllers

Use existing route conventions.

Suggested routes:

Route::prefix('admin/logs')
    ->name('admin.logs.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/', [ActivityLogController::class, 'index'])->name('index');
        Route::get('/{activityLog}', [ActivityLogController::class, 'show'])->name('show');
        Route::get('/export', [ActivityLogExportController::class, 'export'])->name('export');
    });

Suggested controllers:

ActivityLogController
ActivityLogExportController
ClinicalLogController optional
FinancialLogController optional
StockLogController optional
SecurityLogController optional

Prefer one flexible ActivityLogController first.

---

# 27. Logging Middleware / Helpers

Consider middleware/helper to capture request context:

- IP address
- user agent
- authenticated user
- route name
- request ID if available

Do not log sensitive request data.

Mask sensitive fields:

password
password_confirmation
token
api_key
secret
remember_token
card_number
pin

---

# 28. Model Observers

For critical models, create observers if useful:

PatientObserver
VisitObserver
InvoiceObserver
PaymentObserver
StockMovementObserver
MedicationAdministrationObserver
EmergencyCaseObserver
TheatreCaseObserver
ClaimObserver
UserObserver

Observers should log important lifecycle actions.

Do not over-log every tiny update if it causes noise.

Use explicit service logs for workflow actions.

---

# 29. Avoid Log Noise

Not every minor frontend save should create useless noise.

Prioritize:

- clinical changes
- financial changes
- stock changes
- status changes
- assignments
- corrections
- overrides
- security events
- merges
- approvals
- submissions
- cancellations

Avoid logging harmless page views unless needed.

---

# 30. Tests Required

Add or update tests.

## Core Logs

1. ActivityLogService creates log.
2. Log stores user, module, action, subject.
3. Log stores old/new values.
4. Log stores reason.
5. Log stores patient/visit context.
6. Log viewer lists logs.
7. Log filters by module.
8. Log filters by user.
9. Log filters by patient.
10. Log detail shows old/new values.

## Clinical Logs

11. Consultation record update creates log.
12. Emergency triage override creates log.
13. Medication administration creates log.
14. MAR correction creates log.
15. Investigation result verification creates log.
16. Theatre operative note update creates log.

## Financial Logs

17. Invoice item creation creates log.
18. Payment recording creates log.
19. Payment correction/reversal creates log.
20. Claim submission/rejection creates log.

## Stock Logs

21. Stock movement creates log.
22. Pharmacy dispensing creates log.
23. Emergency consumable usage creates log.
24. Theatre consumable usage creates log.
25. Stock adjustment creates log with reason.

## Patient Merge Logs

26. Patient merge creates merge logs.
27. Patient alias creation creates log.
28. Merged patient redirect/action is logged if needed.

## Security Logs

29. Login event creates security log if implemented.
30. Failed login creates security log if implemented.
31. Role/permission change creates log.

## Permissions

32. Unauthorized user cannot view logs.
33. Finance user can view financial logs if permitted.
34. Normal user cannot view security logs.
35. Export requires permission.

---

# 31. Verification Checklist

After implementation, verify manually:

- Create consultation note and check log.
- Edit diagnosis and check old/new values.
- Record emergency triage override and check reason.
- Administer medication and check MAR log.
- Create stock movement and check stock log.
- Record payment and check finance log.
- Merge patients and check merge logs.
- Open log viewer and filter by patient.
- Open log detail and inspect metadata.
- Confirm unauthorized user cannot access logs.

---

# 32. Deliverables

Provide:

1. LOGS_GAP_ANALYSIS.md
2. LOGS_SOLUTION_REPORT.md
3. LOGS_REMAINING_RECOMMENDATIONS.md
4. Central ActivityLogService or equivalent
5. Log table/model updates
6. Log viewer UI
7. Log detail UI
8. Permissions/routes/controllers
9. Module log triggers
10. Tests or verification notes
11. Files modified
12. Remaining TODOs

---

# 33. Important Rules

Do not create multiple competing log systems.

Do not lose old logs.

Do not log passwords, tokens, secrets, or sensitive credentials.

Do not expose logs to unauthorized users.

Do not silently change clinical, financial, or stock records without logs.

Do not delete logs by default.

Do not over-log meaningless noise.

Do not break existing UHMS workflows.

Now inspect the UHMS logging implementation, produce the required gap analysis, fix and standardize the logging system, and generate the solution and recommendation reports.
```
