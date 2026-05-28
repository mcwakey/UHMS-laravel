````text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to implement a safe Patient Folder Merge system.

UHMS already has patients, visits, emergency cases, admissions, consultation records, medical records, prescriptions, medication administration/MAR, investigations, procedures, invoices, payments, claims, documents, stock usage, clinical tasks, and visit preview.

We now need a system that allows authorized users to merge two patient folders into one without losing any records.

This feature must support two main cases:

1. Emergency temporary patient folder later identified as an existing real patient.
2. Two duplicate/parallel real patient folders that need to become one.

The user must be able to choose which patient folder remains as the main patient folder.

Do not delete patient records.

Do not lose clinical, billing, emergency, admission, pharmacy, investigation, procedure, stock, MAR, claim, or document history.

---

# 1. Core Concept

Patient merge must work like this:

```text
Main Patient Folder = patient record that remains active
Duplicate / Temporary Folder = patient record merged into the main folder
Merged Patient Folder = locked, inactive, and redirected to the main patient
````

The duplicate patient must not be deleted.

Instead:

```text
duplicate_patient.merge_status = MERGED
duplicate_patient.merged_into_patient_id = main_patient.id
duplicate_patient.is_active = false
```

The old patient folder must remain available for audit and redirect/search purposes.

---

# 2. Main Objectives

Implement a Patient Folder Merge system that can:

1. Search and select two patient folders.
2. Let user choose which one remains the main folder.
3. Compare the folders side by side.
4. Let user resolve demographic conflicts field-by-field.
5. Preview records that will be moved.
6. Merge all related records safely.
7. Preserve audit logs.
8. Lock the merged duplicate folder.
9. Keep old patient numbers as aliases.
10. Redirect old patient folder to the main patient.
11. Prevent new visits/care under merged folders.
12. Support emergency temporary patient identity confirmation.
13. Support duplicate patient folder merge.
14. Avoid duplicate child records where possible.
15. Preserve all clinical and financial history.

---

# 3. Important Rules

Do not delete the duplicate patient.

Do not delete clinical records.

Do not delete billing records.

Do not create duplicate invoices.

Do not merge without explicit confirmation.

Do not allow merging a patient into themselves.

Do not allow new visits under a merged patient.

Do not overwrite main patient demographics automatically.

Do not lose old patient number.

Do not break old references.

Do not merge financial records incorrectly.

Do not bypass audit logging.

Do not break Emergency, OPD, Admission, Billing, Pharmacy, Investigation, Procedure, MAR, Claims, Visit Preview, or Stock workflows.

---

# 4. Use Cases

## Case 1 — Emergency Temporary Patient Identified Later

Example:

```text
Temporary Patient:
TEMP-ER-2026-00012 — Unknown Male Adult

Real Patient:
PAT-2024-00077 — Kofi Mensah
```

User chooses:

```text
Main patient to keep: PAT-2024-00077
Patient to merge: TEMP-ER-2026-00012
```

Expected result:

* emergency case moves to real patient
* visit moves to real patient
* vitals move to real patient
* notes move to real patient
* medication/MAR records move to real patient
* investigations move to real patient
* procedures move to real patient
* invoices/payments move to real patient
* claims move to real patient
* documents move to real patient
* temporary patient is marked MERGED
* old temporary number becomes alias
* opening temporary folder redirects to main patient

## Case 2 — Two Duplicate Real Patient Folders

Example:

```text
PAT-2025-00021 — Ama Mensah
PAT-2026-00009 — Ama Mensah
```

User chooses which one remains main.

Expected result:

* all visits from duplicate move to main
* admissions move to main
* emergency cases move to main
* clinical records move to main
* invoices/payments move to main
* insurances and next of kin are merged intelligently
* duplicate patient is marked MERGED
* old patient number becomes alias
* search by old number still finds/redirects to main patient

---

# 5. Database Changes — Patients

Update `patients` table if missing:

```text
merge_status nullable/default ACTIVE
merged_into_patient_id nullable foreign key to patients.id
merged_at nullable
merged_by nullable foreign key to users.id
is_temporary boolean default false
temporary_reason nullable
identity_confirmed_at nullable
identity_confirmed_by nullable foreign key to users.id
is_active boolean default true
```

Suggested statuses:

```text
ACTIVE
TEMPORARY
MERGED
ARCHIVED
```

Rules:

* ACTIVE patients can receive new visits.
* TEMPORARY patients can receive emergency care.
* MERGED patients cannot receive new visits.
* MERGED patients should redirect to main patient.
* ARCHIVED patients are inactive but not necessarily merged.

---

# 6. Patient Merge Requests Table

Create:

```text
patient_merge_requests
- id
- main_patient_id
- duplicate_patient_id
- status
- reason
- requested_by
- reviewed_by nullable
- approved_by nullable
- completed_by nullable
- completed_at nullable
- field_resolution json nullable
- merge_summary json nullable
- error_message nullable
- created_at
- updated_at
```

Statuses:

```text
DRAFT
PENDING_REVIEW
APPROVED
REJECTED
COMPLETED
FAILED
CANCELLED
```

For first version, Super Admin / authorized Records Officer may execute immediately.

If approval workflow already exists, use it.

---

# 7. Patient Merge Logs Table

Create:

```text
patient_merge_logs
- id
- patient_merge_request_id
- main_patient_id
- duplicate_patient_id
- record_type
- record_id
- action
- old_patient_id nullable
- new_patient_id nullable
- details json nullable
- performed_by
- created_at
```

Actions may include:

```text
REASSIGNED
SKIPPED_DUPLICATE
MERGED_CHILD_RECORD
ALIAS_CREATED
PATIENT_MARKED_MERGED
FIELD_UPDATED
ERROR
```

This log must make the merge auditable.

---

# 8. Patient Aliases Table

Create:

```text
patient_aliases
- id
- patient_id
- alias_type
- alias_value
- source_patient_id nullable
- created_by nullable
- created_at
- updated_at
```

Alias types:

```text
OLD_PATIENT_NUMBER
OLD_TEMPORARY_NUMBER
OLD_NAME
OLD_PHONE
OLD_GHANA_CARD
OLD_INSURANCE_NUMBER
```

Examples:

```text
OLD_PATIENT_NUMBER = TEMP-ER-2026-00012
OLD_PATIENT_NUMBER = PAT-2026-00009
OLD_NAME = Unknown Male Adult
```

Search should include aliases and redirect to the main patient.

---

# 9. Merge Workflow

Implement this workflow:

```text
Search patients
↓
Select main patient to keep
↓
Select duplicate patient to merge
↓
Compare folders side by side
↓
Choose demographic field resolution
↓
Preview merge impact
↓
Confirm merge
↓
System performs transaction
↓
System logs all moved records
↓
Duplicate patient becomes MERGED
↓
Old patient number becomes alias
↓
Opening duplicate folder redirects to main folder
```

---

# 10. Merge UI Pages

Create patient merge pages.

Suggested menu:

```text
Patients
├── Patient Merge
├── Merge Requests
├── Possible Duplicates
└── Merge Logs
```

Pages:

```text
PatientMerge/Search.vue
PatientMerge/Compare.vue
PatientMerge/Preview.vue
PatientMerge/Show.vue
PatientMerge/Logs.vue
```

Or Blade equivalents depending project stack.

---

# 11. Patient Search / Selection UI

The user must be able to search patient folders by:

```text
patient number
temporary patient number
name
phone number
Ghana Card number
insurance membership number
next of kin name
next of kin phone
old patient alias
```

The UI must clearly distinguish:

```text
Main patient to keep
Duplicate patient to merge
```

Do not allow same patient to be selected for both.

If selected patient is already merged, show warning and resolve to the final main patient.

---

# 12. Side-by-Side Comparison UI

Show both patient folders side by side.

Fields:

```text
Patient number
Name
Gender
Date of birth / age
Phone
Ghana Card
Address
Occupation
Marital status
Religion
Insurance memberships
Next of kin
Visits count
Admissions count
Emergency cases count
Invoices count
Payments count
Claims count
Documents count
Last visit date
Created date
Temporary status
Merged status
```

Clearly mark:

```text
MAIN TO KEEP
DUPLICATE TO MERGE
```

---

# 13. Field Resolution UI

For demographics, the user should decide which value to keep.

Default:

```text
Main patient value wins
```

But user can choose duplicate value for selected fields.

Example:

```text
Name: keep main
Phone: use duplicate
Ghana Card: use duplicate
Address: keep main
Occupation: use duplicate
Religion: keep main
Marital status: keep main
```

Store decision in:

```text
patient_merge_requests.field_resolution
```

Rules:

* Do not overwrite main fields without user selection.
* Show empty vs non-empty clearly.
* If main field is empty and duplicate has value, suggest using duplicate value.
* If values conflict, highlight conflict.
* If values are identical, mark as matching.

---

# 14. Merge Impact Preview

Before final merge, show record counts to be moved.

Example:

```text
Records to move:
- Visits: 3
- Emergency Cases: 1
- Admissions: 1
- Medical Records: 5
- Prescriptions: 12
- Medication Orders: 4
- Medication Administration Schedules: 20
- Medication Administrations: 8
- Investigations: 6
- Procedures: 2
- Invoices: 3
- Invoice Items: 22
- Payments: 4
- Claims: 1
- Documents: 7
- Patient Insurances: 2
- Next of Kin: 1
```

Also show warnings:

```text
Duplicate insurance membership found.
Both patients have active visits.
Duplicate phone number conflict.
Merged patient will be locked after merge.
```

User must confirm:

```text
I understand this merge will move records and lock the duplicate patient folder.
```

---

# 15. Records That Must Be Reassigned

The merge must update patient references across the system.

Inspect actual table names first, then update all relevant patient foreign keys.

Likely records include:

```text
visits
emergency_cases
admissions
visit_consultation_routes
medical_records
complaints
history_of_presenting_complaints
examinations
diagnoses
clinical_notes
prescriptions
prescription_items
medication_orders
medication_administration_schedules
medication_administrations
clinical_tasks
vitals
triage_records
investigation_requests
investigation_request_items
investigation_results
procedure_requests
procedure_records
theatre_records
invoices
invoice_items
payments
claims
claim_items
claim_payments
patient_insurances
patient_next_of_kins
patient_documents
appointments
follow_ups
department_consumable_usages
stock_movements if patient_id exists
attachments/files with patient_id
audit/activity logs where patient_id exists
```

Do not assume table names. Search the codebase/migrations for `patient_id`.

---

# 16. PatientMergeService

Create service:

```text
PatientMergeService
```

Required methods:

```php
public function preview(Patient $mainPatient, Patient $duplicatePatient): array;

public function merge(
    Patient $mainPatient,
    Patient $duplicatePatient,
    User $user,
    array $fieldResolution = [],
    ?string $reason = null
): PatientMergeRequest;
```

The merge method must:

1. Validate patients.
2. Start database transaction.
3. Apply selected demographic field resolution.
4. Reassign child records from duplicate to main.
5. Merge unique patient insurances.
6. Merge unique next of kin.
7. Create aliases for old patient number/name/identifiers.
8. Mark duplicate patient as MERGED.
9. Log every major action.
10. Commit transaction.
11. Return completed merge request.

If failure occurs:

* rollback transaction
* mark request FAILED if already created
* save error message
* log error
* do not leave partial merge

---

# 17. PatientMergePreviewService

Create service:

```text
PatientMergePreviewService
```

It should return:

```text
main patient details
duplicate patient details
field conflicts
matching fields
empty-field suggestions
record counts by table/module
warnings
blocking issues
```

Blocking issues may include:

```text
same patient selected
main patient is merged into another patient
duplicate patient already merged into another active patient
patients belong to different facilities if multi-facility restrictions apply
user lacks permission
```

---

# 18. Reassignment Strategy

Use generic helper for simple tables:

```php
DB::table($table)
    ->where('patient_id', $duplicatePatient->id)
    ->update(['patient_id' => $mainPatient->id]);
```

But for sensitive records, handle carefully:

```text
patient_insurances
next_of_kin
claims
payments
active visits
temporary patient identity
```

Use model/service-level logic where business rules are needed.

Do not update blindly if a table needs deduplication.

---

# 19. Patient Insurance Merge Rules

Merge patient insurances carefully.

If duplicate patient has insurance records:

* move unique insurance records to main patient
* avoid duplicates

Duplicate definition:

```text
same insurance_provider_id
same membership_number
same policy_number if applicable
same active period if applicable
```

If same insurance already exists on main:

```text
do not create duplicate
mark duplicate insurance as merged/archived if field exists
or log skipped duplicate
```

If duplicate insurance has useful data missing on main, consider updating main insurance only if safe.

Always log action.

---

# 20. Next of Kin Merge Rules

Merge next of kin carefully.

Duplicate definition:

```text
same name + same phone
or same phone
```

Rules:

* move unique next of kin to main patient
* skip duplicate next of kin
* log skipped duplicates
* do not delete skipped records unless safe; archive/mark merged if available

---

# 21. Documents / Attachments Merge Rules

All documents/files linked to duplicate patient must move to main patient.

Rules:

* preserve file paths
* preserve uploaded_by
* preserve created_at
* update patient_id
* log action
* do not delete files

If document title conflicts, keep both.

---

# 22. Invoice / Payment / Claim Merge Rules

Financial records must be preserved.

Move:

```text
invoices
invoice_items
payments
claims
claim_items
claim_payments
```

Rules:

* do not recalculate totals
* do not duplicate invoice numbers
* do not merge two invoices into one automatically
* each invoice remains exactly as it was but linked to main patient
* claim histories remain unchanged except patient_id
* payment histories remain unchanged except patient_id
* audit logs must show reassignment

If there is one visit invoice architecture, preserve visit-invoice relationship.

---

# 23. Clinical Record Merge Rules

Move all clinical records to main patient.

Rules:

* preserve visit_id
* preserve medical_record_id
* preserve consultation_route_id
* preserve created_by
* preserve doctor ownership
* preserve timestamps
* preserve source patterns
* preserve audit logs
* do not rewrite clinical content
* do not merge separate medical records into one unless they belong to the same visit/session and existing workflow requires it

Main rule:

```text
Reassign patient_id. Preserve clinical structure.
```

---

# 24. Emergency Temporary Patient Identity Confirmation

Add a quick merge path from Emergency Case Detail page.

Button:

```text
Confirm Identity / Merge Temporary Patient
```

Workflow:

```text
Open emergency case
↓
Search real patient
↓
Select real patient as main
↓
Preview merge impact
↓
Confirm merge
↓
Temporary patient merges into real patient
↓
Emergency case now belongs to real patient
```

After merge:

```text
temporary_patient.identity_confirmed_at = now
temporary_patient.identity_confirmed_by = user
temporary_patient.merge_status = MERGED
temporary_patient.merged_into_patient_id = real_patient.id
```

On the emergency case page, show:

```text
Identity confirmed: linked to PAT-2024-00077 — Kofi Mensah
```

---

# 25. Duplicate Patient Merge Path

Add a general path from Patient module.

Button:

```text
Merge Patient Folder
```

or

```text
Mark as Duplicate / Merge
```

Workflow:

```text
Open patient folder
↓
Select another patient to merge with
↓
Choose which folder remains main
↓
Compare and resolve fields
↓
Preview
↓
Confirm merge
```

---

# 26. Redirect Behavior for Merged Patients

When opening a merged patient folder:

Show a clear page/message:

```text
This patient folder has been merged into:
PAT-2024-00077 — Kofi Mensah

Merged on: 27 May 2026
Merged by: Records Officer
Reason: Temporary emergency identity confirmed

[Open Main Patient Folder]
[View Merge Log]
```

Do not allow:

```text
new visit
new admission
new emergency case
new invoice
new prescription
new claim
new document upload
```

under merged patient.

Search results may show merged folder but must clearly mark:

```text
MERGED → Open main folder
```

---

# 27. Patient Search Alias Support

Update patient search to include aliases.

Searching old patient number should find main patient.

Example:

```text
Search: TEMP-ER-2026-00012
Result: PAT-2024-00077 — Kofi Mensah
Alias match: OLD_TEMPORARY_NUMBER TEMP-ER-2026-00012
```

Searching old duplicate patient number should also work.

Do not make old identifiers disappear.

---

# 28. Prevent New Records Under Merged Patient

Add validation/guard wherever new patient-related records are created.

If patient is MERGED:

```text
throw validation error:
This patient folder has been merged into PAT-xxxx. Please use the main patient folder.
```

Apply to:

```text
new visit
new emergency case
new admission
new invoice
new appointment
new prescription
new investigation
new procedure
new document
new claim
```

Use a reusable guard:

```text
PatientMergeGuard
```

or method:

```php
$patient->assertCanReceiveNewRecords();
```

---

# 29. Patient Model Relationships

Add relationships:

```php
public function mergedInto()
public function mergedPatients()
public function aliases()
public function mergeRequestsAsMain()
public function mergeRequestsAsDuplicate()
```

Useful accessors:

```php
public function isMerged(): bool
public function getFinalPatient(): Patient
```

`getFinalPatient()` should follow chain safely if a merged patient points to another merged patient.

Prevent infinite loops.

---

# 30. Merge Chain Handling

If patient A was merged into B, and later B is merged into C:

The system should resolve A → C.

Options:

1. Update A.merged_into_patient_id to C during second merge.
2. Keep A → B → C but `getFinalPatient()` resolves C.

Preferred:

```text
Update old merged children to point to final main patient.
```

This keeps redirect simple.

---

# 31. Approval Workflow

If the project supports approvals, implement:

```text
Draft merge request
↓
Pending review
↓
Approved
↓
Executed
```

If not, implement direct merge for users with:

```text
patients.merge.execute
```

For first version:

* Super Admin can execute immediately.
* Records Officer may request merge.
* Admin may approve.

Do not overcomplicate if approval infrastructure is missing.

---

# 32. Duplicate Detection Helper

Prepare or implement duplicate suggestions.

Possible matching criteria:

```text
same Ghana Card number
same phone number
same insurance membership number
same next of kin phone
same name + similar date of birth
similar name + same gender + same phone
```

For first implementation, manual search is enough.

But if easy, create:

```text
Possible Duplicate Patients
```

with match reason.

Do not auto-merge duplicates.

Only suggest.

---

# 33. Permissions

Add permissions:

```text
patients.merge.view
patients.merge.create
patients.merge.preview
patients.merge.approve
patients.merge.execute
patients.merge.cancel
patients.merge.view_logs
patients.merge.confirm_identity
patients.merge.suggest_duplicates
```

Suggested roles:

```text
Super Admin
Hospital Admin
Records Officer
Emergency Supervisor
```

Doctors and nurses should not merge folders by default.

They may have permission to flag possible duplicate only if implemented.

---

# 34. Routes / Controllers

Use existing route conventions.

Suggested controllers:

```text
PatientMergeController
PatientMergePreviewController
PatientMergeExecutionController
PatientMergeLogController
PatientAliasController
PatientDuplicateSuggestionController
EmergencyPatientIdentityController
```

Suggested routes:

```php
Route::prefix('admin/patients/merge')
    ->name('admin.patients.merge.')
    ->middleware(['auth'])
    ->group(function () {
        Route::get('/', [PatientMergeController::class, 'index'])->name('index');
        Route::get('/create', [PatientMergeController::class, 'create'])->name('create');
        Route::post('/preview', [PatientMergePreviewController::class, 'store'])->name('preview');
        Route::post('/execute', [PatientMergeExecutionController::class, 'store'])->name('execute');
        Route::get('/requests/{mergeRequest}', [PatientMergeController::class, 'show'])->name('show');
        Route::get('/requests/{mergeRequest}/logs', [PatientMergeLogController::class, 'index'])->name('logs');
    });

Route::post('/admin/emergency/cases/{emergencyCase}/confirm-identity', [EmergencyPatientIdentityController::class, 'store'])
    ->name('admin.emergency.cases.confirm-identity');
```

Adapt to existing project routes.

---

# 35. Services to Create / Update

Create or update:

```text
PatientMergeService
PatientMergePreviewService
PatientMergeValidationService
PatientMergeLogger
PatientAliasService
PatientSearchService
PatientDuplicateDetectionService
EmergencyPatientIdentityService
PatientMergeGuard
```

Do not put merge logic directly in controllers.

---

# 36. Transaction Safety

Merge execution must run inside a database transaction.

Pseudo-flow:

```php
DB::transaction(function () {
    // validate
    // create merge request
    // apply demographic field resolution
    // move/reassign records
    // merge insurances
    // merge next of kin
    // create aliases
    // mark duplicate patient as merged
    // write logs
});
```

If anything fails:

* rollback all changes
* show error
* do not leave partial merge

---

# 37. Record Reassignment Configuration

Create a centralized configuration listing mergeable tables and patient FK columns.

Example:

```php
return [
    'visits' => ['patient_id'],
    'emergency_cases' => ['patient_id'],
    'admissions' => ['patient_id'],
    'medical_records' => ['patient_id'],
    'invoices' => ['patient_id'],
    'invoice_items' => ['patient_id'],
    'payments' => ['patient_id'],
    'claims' => ['patient_id'],
    'claim_items' => ['patient_id'],
    'clinical_tasks' => ['patient_id'],
];
```

Use this for preview counts and merge execution.

But sensitive tables like insurances and next of kin should have custom handlers.

---

# 38. Sensitive Table Custom Handlers

Use custom handlers for:

```text
patient_insurances
patient_next_of_kins
patient_aliases
patients
claims if special status checks exist
active visits if workflow restrictions exist
```

Generic update is acceptable for simple patient_id references.

---

# 39. Authorization and Confirmation

Before merge execution:

* user must have `patients.merge.execute`
* reason is required
* main patient and duplicate patient must be confirmed
* checkbox confirmation required
* show warning that merge is not reversible from UI

Confirmation text:

```text
I confirm that I want to merge this patient folder into the selected main patient folder. I understand the duplicate folder will be locked and all records will be reassigned to the main folder.
```

---

# 40. Rollback Strategy

For first implementation:

```text
No normal UI rollback.
Merge is irreversible from UI.
Support/admin rollback may be possible using merge logs.
```

Do not build rollback UI unless explicitly required.

But logs must be detailed enough to support manual rollback.

---

# 41. Activity / Audit Integration

If the project has activity logs, record:

```text
patient_merge_requested
patient_merge_previewed
patient_merge_executed
patient_marked_merged
patient_alias_created
emergency_identity_confirmed
```

Include:

```text
main_patient_id
duplicate_patient_id
performed_by
reason
timestamp
```

---

# 42. Visit Preview Integration

Visit Preview should still work after merge.

If a visit originally belonged to duplicate patient, after merge it should show under main patient.

Visit Preview may show note:

```text
This visit was originally created under merged patient folder TEMP-ER-2026-00012.
```

This can come from merge logs/aliases if useful.

Do not lose the clinical timeline.

---

# 43. Claims / Insurance Integration

After merge:

* claims should belong to main patient
* claim items should belong to main patient
* patient insurance should merge safely
* membership numbers should remain searchable
* verification/CCC codes should remain unchanged
* no claim amount recalculation should happen

Do not change claim status during patient merge.

---

# 44. Stock / Medication / MAR Integration

After merge:

* medication orders should belong to main patient
* medication schedules should belong to main patient
* medication administrations should belong to main patient
* MAR chart should show under main patient
* stock movements remain unchanged except patient_id if such column exists
* do not duplicate stock movement
* do not change stock balances

Patient merge must not affect inventory quantities.

---

# 45. UI / UX Requirements

Use clear and safe UI.

Required screens:

1. Search/select patients.
2. Side-by-side comparison.
3. Field conflict resolution.
4. Merge impact preview.
5. Confirmation screen/modal.
6. Completed merge result.
7. Merge log view.
8. Merged patient redirect page.

Visual warnings:

```text
Temporary patient
Already merged
Active visit
Active admission
Outstanding invoice
Open claim
Different Ghana Card
Different insurance number
```

Do not make merge a one-click hidden action.

---

# 46. Performance Requirements

Preview should be efficient.

Avoid loading full records during preview.

Use counts:

```php
DB::table($table)->where('patient_id', $duplicatePatient->id)->count();
```

For comparison page, eager-load only necessary patient relationships:

```text
insurances
next of kin
latest visit
aliases
```

Do not load all clinical history in the preview page.

---

# 47. Tests Required

Add or update tests.

## Basic Merge

1. User can select main patient and duplicate patient.
2. Cannot merge patient into itself.
3. Cannot merge without permission.
4. Cannot merge without reason.
5. Merge executes inside transaction.
6. Duplicate patient is marked MERGED.
7. Duplicate patient points to main patient.
8. Main patient remains ACTIVE.

## Emergency Temporary Merge

9. Temporary emergency patient can be merged into real patient.
10. Emergency case moves to real patient.
11. Emergency visit moves to real patient.
12. Temporary patient number becomes alias.
13. Emergency case page shows identity confirmed.

## Clinical Records

14. Visits move to main patient.
15. Medical records move to main patient.
16. Consultation records move to main patient.
17. Vitals move to main patient.
18. Diagnoses move to main patient.
19. Prescriptions move to main patient.
20. Medication orders/schedules/administrations move to main patient.
21. Investigations move to main patient.
22. Procedures move to main patient.
23. Clinical tasks move to main patient.

## Billing / Claims

24. Invoices move to main patient.
25. Invoice items move to main patient.
26. Payments move to main patient.
27. Claims move to main patient.
28. Claim items move to main patient.
29. Totals/statuses are not recalculated during merge.

## Insurances / Next of Kin

30. Unique patient insurance records merge.
31. Duplicate insurance membership is not duplicated.
32. Unique next of kin records merge.
33. Duplicate next of kin is skipped/logged.

## Aliases / Search / Redirect

34. Old patient number alias is created.
35. Search by old patient number finds main patient.
36. Opening merged patient redirects/shows merged notice.
37. Cannot create new visit under merged patient.
38. Cannot create emergency case under merged patient.

## Logs / Audit

39. Merge request is created.
40. Merge logs are created for moved records.
41. Field resolution is stored.
42. Activity log records merge execution.

## Failure Safety

43. If one table update fails, entire merge rolls back.
44. Failed merge does not partially move records.
45. Error is reported clearly.

---

# 48. Deliverables

Provide:

1. Gap analysis of current patient/emergency duplicate handling.
2. Patient merge migrations.
3. Patient aliases support.
4. Patient merge request/log tables.
5. Patient model merge relationships/accessors.
6. Merge preview service.
7. Merge execution service.
8. Field conflict resolution.
9. Side-by-side comparison UI.
10. Merge impact preview UI.
11. Emergency identity confirmation merge flow.
12. Duplicate patient merge flow.
13. Merged patient redirect behavior.
14. Patient search alias support.
15. New record guard for merged patients.
16. Permissions/menus/routes.
17. Tests or verification notes.
18. Files modified.
19. Remaining TODOs.

---

# 49. Important Rules

Do not delete duplicate patient folder.

Do not lose any records.

Do not overwrite main patient data without explicit field selection.

Do not merge without audit trail.

Do not allow new care under merged patient folder.

Do not recalculate invoices/claims during merge.

Do not change stock balances during merge.

Do not break Emergency, OPD, Admission, Billing, Pharmacy, Investigation, Procedure, MAR, Claims, Visit Preview, or Stock workflows.

Now inspect the current UHMS implementation and build a safe Patient Folder Merge system that supports emergency temporary patient identity confirmation and duplicate patient folder consolidation.

```
```
