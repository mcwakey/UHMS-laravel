You are a senior Laravel + Inertia/Vue developer working on UHMS — Ultimate Hospital Management System.

We have already implemented consultation record ownership correctly. Every consultation record now has its correct creator/owner.

Now we need to improve the Consultation UI and related record behavior so ownership is displayed more cleanly, investigation/procedure records show owners properly, Notes / Consultation Summary updates immediately, and all consultation records have edit actions where permitted.

Focus only on:

- Consultation page UI grouping
- Record owner display
- Investigation section grouping
- Procedure owner display
- Notes / Consultation Summary live refresh
- Unknown user issue after saving
- Edit actions for consultation records
- Backend response/eager-loading needed to support the UI

Do not change the already working ownership logic unless needed for UI data loading.

Do not break:

- consultation
- visits
- medical records
- investigations
- procedures
- prescriptions
- treatments
- billing
- visit preview
- claims mirror

---

# 1. Current Problems to Fix

Fix the following issues:

1. Consultation records currently repeat the doctor/user name on every record.
2. Instead, group records by user/doctor on the UI.
3. Under the Investigation section on the Consultation page:
   - group requests by department
   - make sure record owners are displayed clearly
4. Under the Procedures section:
   - requested procedures do not display record owners
5. Under Notes / Consultation Summary:
   - new additions do not update immediately after saving
   - some newly added records show “Unknown user” until the page is reloaded
6. Consultation records currently do not have visible edit actions/buttons.
7. After editing or adding records, the UI should update without full page reload.
8. The current tab/section should remain active after create/update.

---

# 2. Preserve Existing Ownership Logic

The ownership logic is already working.

Do not rewrite the whole ownership system.

Do not change database ownership fields unless a relationship/eager-loading issue requires it.

Current entry ownership should remain based on existing fields like:

created_by
doctor_id
updated_by
user_id

or the project’s equivalent fields.

The goal is to improve how ownership is displayed, grouped, refreshed, and edited in the frontend.

---

# 3. Group Consultation Records by User on UI

Instead of displaying this repeatedly:

Complaint A — Entered by Dr. Kofi
Complaint B — Entered by Dr. Kofi
Complaint C — Entered by Dr. Kofi
Diagnosis X — Entered by Dr. Ama
Diagnosis Y — Entered by Dr. Ama

Display grouped by user/doctor:

Dr. Kofi Mensah
    Complaint A
    Complaint B
    Complaint C

Dr. Ama Boateng
    Diagnosis X
    Diagnosis Y

Apply grouping to consultation sections where multiple records are listed:

Complaints
History of Presenting Complaint
Examination
Diagnosis
Treatments
Prescriptions
Investigations
Procedures
Tasks
Notes
Consultation Summary

If grouping per section makes more sense, group inside each section.

Example:

Complaints

Dr. Kofi Mensah
    Fever
    Headache

Dr. Ama Boateng
    Chest pain review note

Do not remove ownership visibility.

The doctor/user name should still be visible, but as a group heading instead of repeated on every item.

---

# 4. Recommended Grouping Data Structure

Backend or frontend may normalize records into this structure:

[
  {
    user_id: 5,
    user_name: "Dr. Kofi Mensah",
    user_role: "Doctor",
    entries: [...]
  },
  {
    user_id: 8,
    user_name: "Dr. Ama Boateng",
    user_role: "Specialist",
    entries: [...]
  }
]

If done in Vue, create a helper:

groupByOwner(records)

Owner resolution should use existing relationship names, such as:

record.creator
record.createdBy
record.doctor
record.user
record.requestedBy
record.enteredBy

depending on the model.

Fallback:

Unknown user

But this fallback should only happen when no creator relationship exists.

Do not show Unknown user if the record has a valid created_by, doctor_id, or ownership relationship.

---

# 5. Owner Group UI Design

For each user group, display:

Doctor/User name
Role if available
Total records count
Optional badge: Main Doctor / Contributor

Example:

Dr. Kofi Mensah
Main Doctor · 4 entries

- Fever
- Headache
- Malaria diagnosis
- Paracetamol prescription

For contributors:

Dr. Ama Boateng
Contributor · 2 entries

- Specialist review note
- ECG advised

If the group owner is the main session doctor, show a small badge:

Main Doctor

If the group owner is not the main doctor, show:

Contributor

Do not overwrite or change the main session doctor.

---

# 6. Fix Unknown User After Adding New Records

Current issue:

After adding a record, it sometimes shows Unknown user until page reload.

This usually means the frontend response after save does not include the full creator/user relationship.

Fix this properly.

When a new consultation record is created, the response must include:

record id
record content/details
created_at
updated_at
created_by
creator/user object
doctor object if used
updated_by if applicable

Example response:

{
  "id": 10,
  "content": "Patient complains of headache",
  "created_at": "2026-05-26T10:30:00Z",
  "creator": {
    "id": 3,
    "name": "Dr. Kofi Mensah"
  }
}

Do not make the frontend wait for a full reload before it can display the owner name.

If using Inertia:

- return updated props through partial reload, or
- update local state with the created record and creator object, or
- attach current user data to the optimistic/local record

The created/updated record must be rendered immediately with the correct user name.

---

# 7. Notes / Consultation Summary Must Update Immediately

Current issue:

Under Notes / Consultation Summary, new additions do not update immediately unless the page is reloaded.

Fix this.

After adding any new consultation record, the Notes / Consultation Summary section should update immediately.

Possible fixes:

emit an event after successful save
update local state
refresh summary prop using Inertia partial reload
call summary reload endpoint
recompute summary client-side from updated section data

Recommended Inertia-style approach:

router.reload({
  only: ['consultationSummary', 'sections', 'medicalRecord'],
  preserveScroll: true,
  preserveState: true,
})

or the project’s equivalent pattern.

If using local Vue state:

1. push the returned created record into the right section
2. regroup by owner
3. recompute summary
4. update the UI immediately

Do not reload the entire page.

Do not reset the user to the first tab.

Do not lose form state unnecessarily.

---

# 8. Notes / Consultation Summary Grouping

In Notes / Consultation Summary, group records by owner/user where practical.

Recommended summary layout:

Consultation Summary

Dr. Kofi Mensah
    Complaints
        Fever
        Headache

    History of Presenting Complaint
        Fever started 3 days ago...

    Diagnosis
        Malaria

    Prescriptions
        Paracetamol
        Artemether/Lumefantrine

Dr. Ama Boateng
    Additional Note
        ECG advised.

    Diagnosis
        Rule out cardiac condition.

Alternative acceptable layout:

Complaints
    Dr. Kofi Mensah
        Fever
        Headache

Diagnosis
    Dr. Kofi Mensah
        Malaria

    Dr. Ama Boateng
        Rule out cardiac condition

Use whichever fits the current design better.

Main rule:

Do not repeat the doctor/user name unnecessarily on every row.
Do not hide who created the entry.
Do not attribute all entries to the main doctor.

---

# 9. Investigation Section — Group Requests by Department

Under Consultation → Investigations, group requested investigations by department.

Instead of one flat list:

Full Blood Count
Malaria Test
Chest X-ray
Ultrasound

Display:

Laboratory
    Full Blood Count
    Malaria Test

X-Ray
    Chest X-ray

Scan / Ultrasound
    Abdominal Ultrasound

Each department group should show:

Department name
Request count
Status summary if useful

Each request/item should show:

investigation service/item
status
requested by / owner
requested at
result status
actions

---

# 10. Investigation Section — Display Record Owners

Each investigation request must show who requested it.

Preferred grouping:

Department first, then owner

Example:

Laboratory

Dr. Kofi Mensah
    Full Blood Count
    Malaria Test

Dr. Ama Boateng
    Blood Culture

Alternative acceptable display:

Laboratory
    Full Blood Count — Requested by Dr. Kofi Mensah
    Malaria Test — Requested by Dr. Kofi Mensah

Preferred final UI:

Department → Doctor/User → Requests

Do not display Unknown user if the request owner relationship exists.

Eager-load appropriate relationships such as:

requestedBy
createdBy
doctor
department
items
results

Adapt to actual model relationship names.

---

# 11. Investigation Grouping Data Structure

Recommended normalized structure:

[
  {
    department_id: 1,
    department_name: "Laboratory",
    owner_groups: [
      {
        user_id: 3,
        user_name: "Dr. Kofi Mensah",
        requests: [...]
      },
      {
        user_id: 7,
        user_name: "Dr. Ama Boateng",
        requests: [...]
      }
    ]
  },
  {
    department_id: 2,
    department_name: "X-Ray",
    owner_groups: [...]
  }
]

The backend may return this structure directly, or the frontend may build it from loaded relationships.

Avoid querying users/departments inside Vue/Blade loops.

---

# 12. Procedures Section — Display Record Owners

Requested procedures currently do not display owners.

Fix this.

Each procedure request must show:

requested by / created by doctor
department/session
requested at
procedure service
status
actions

Preferred grouping:

Procedure department first, then owner

Example:

Theatre

Dr. Kofi Mensah
    Appendectomy request

Minor Procedure Room

Dr. Ama Boateng
    Wound dressing

Alternative acceptable layout:

Procedures

Dr. Kofi Mensah
    Wound Dressing
    Suturing

Dr. Ama Boateng
    Theatre Review

Use department-first grouping if procedure departments are available.

Do not show Unknown user if the procedure request has a creator/requested-by relationship.

---

# 13. Procedure Section Data Loading

Ensure procedure records eager-load ownership and department/service relationships.

Examples:

with([
    'department',
    'requestedBy',
    'createdBy',
    'doctor',
    'service',
    'procedureService',
])

Adapt names to current implementation.

The UI must have enough data to display:

Procedure department
Procedure service
Requested by
Requested at
Status

---

# 14. Add Edit Actions for Consultation Records

All consultation record sections should have an edit point/action where permitted.

Add edit action/buttons for:

Complaints
History of Presenting Complaint
Examination
Diagnosis
Treatments
Prescriptions
Investigation requests where still editable
Procedure requests where still editable
Tasks
Notes

Button examples:

Edit
Update
Correct

Only show the edit button if the current user can edit the entry.

Use existing ownership permission rules:

creator can edit own entry while session is active
other doctors cannot edit unless consultation.entries.edit_any
completed session entries are locked unless consultation.entries.correct_completed

Do not add edit buttons that lead to 403 for normal expected users. Hide disabled actions unless needed to explain locked state.

---

# 15. Edit Modal / Inline Edit

Implement editing using modal or inline form depending on the existing UI pattern.

Recommended:

Edit modal

Requirements:

- open without full page reload
- prefill existing record
- save with validation
- show validation errors inside modal
- close only after successful update
- update UI immediately after save
- do not leave modal backdrop stuck
- do not reset active tab
- do not lose scroll position unnecessarily

After successful update:

update local record
or Inertia partial reload current section + summary

The updated record response must include:

creator
updated_by
updated_at

so the UI can display author and edit metadata immediately.

---

# 16. Backend Authorization for Editing

Frontend hiding the button is not enough.

Every update endpoint must enforce permission.

Every update endpoint must check:

user owns record
OR user has consultation.entries.edit_any
OR user has consultation.entries.correct_completed for completed sessions

If unauthorized:

return 403

Do not allow users to modify another doctor’s entries accidentally.

---

# 17. Edit Permissions by Record Type

Apply edit permission rules to all relevant record types.

## Complaints

Owner can edit if session active.

## History of Presenting Complaint

Owner can edit if session active.

## Examination

Owner can edit if session active.

## Diagnosis

Owner can edit if session active.

Be careful with final diagnosis / primary diagnosis changes.

If another user changes primary diagnosis, require correct permission.

## Treatments / Prescriptions

Owner can edit if not dispensed / not locked by pharmacy workflow.

Do not allow editing already-dispensed prescription items unless existing workflow supports correction.

## Investigation Requests

Owner can edit only before investigation is accepted/processed.

If investigation has been accepted, billed, resulted, or verified, lock editing.

## Procedure Requests

Owner can edit only before procedure is accepted/scheduled/billed/completed.

If procedure has already entered theatre workflow, lock editing.

## Tasks

Owner can edit task while active.

Assigned user may update status if allowed.

## Notes

Owner can edit own note while session active.

---

# 18. Locked Record UI

If a record cannot be edited because it is already processed, show a clear locked state if useful.

Examples:

Locked: Investigation already accepted
Locked: Procedure already scheduled
Locked: Prescription already dispensed
Locked: Session completed

Do not silently hide everything if the user needs to understand why editing is unavailable.

---

# 19. Add Audit Trail for Edits

If existing audit logging exists, use it.

If not, add or reuse a generic medical record entry log.

For each edit, track:

entry type
entry id
old value
new value
updated by
updated at
reason if override/correction

For override edits, require reason.

Suggested actions:

UPDATED
CORRECTED
OVERRIDE_UPDATED

Do not lose history of clinical changes.

---

# 20. Backend Data Loading

Ensure all consultation sections eager-load ownership relationships.

Examples:

with([
    'creator',
    'createdBy',
    'doctor',
    'updatedBy',
])

Adapt to actual relationship names.

For investigations:

with([
    'department',
    'requestedBy',
    'createdBy',
    'doctor',
    'items',
    'items.service',
    'results',
])

For procedures:

with([
    'department',
    'requestedBy',
    'createdBy',
    'doctor',
    'service',
    'procedureService',
])

Avoid N+1 queries.

Do not query users inside Vue/Blade loops.

---

# 21. API / Controller Response Fix

For all create/update endpoints, return the saved record with owner relationships loaded.

Example Laravel pattern:

$record->load(['creator', 'updatedBy']);

return response()->json([
    'record' => $record,
]);

If using Inertia redirects:

- preserve active tab
- partial reload only relevant props
- include owner relationship in returned props

Important:

After create/update, frontend must receive enough data to render owner name immediately.

---

# 22. Frontend State Fix

When a record is created:

1. receive created record with creator object
2. insert it into the correct local section list
3. regroup by owner
4. refresh or recompute consultation summary
5. keep current tab active

When a record is updated:

1. receive updated record with creator and updated_by object
2. replace the old record in local state
3. regroup by owner
4. refresh or recompute consultation summary
5. keep current tab active

Do not require manual page reload.

Do not reset to first tab.

---

# 23. Active Tab / Section Preservation

When creating or editing any consultation record, preserve the active tab/section.

This applies to:

Complaints
History of Presenting Complaint
Examination
Diagnosis
Investigations
Treatments / Prescriptions
Procedures
Tasks
Notes / Summary

If using URL query, local state, or hash for active section, preserve it.

Example:

activeSection=diagnosis

After save, remain on Diagnosis.

---

# 24. UI / UX Requirements

- Do not repeat doctor name on every record when grouped by doctor.
- Show doctor group heading once.
- Show entry timestamps under each item.
- Show “Edited by” only if edited.
- Show source pattern if available.
- Show locked status if record can no longer be edited.
- Keep UI compact and readable.
- Preserve active tab/section after save.
- No full page reload.
- No modal backdrop stuck.
- No Unknown user after save if creator exists.
- Show edit buttons only where allowed.
- Group investigation requests by department.
- Display investigation owners.
- Display procedure owners.
- Summary must update immediately.

---

# 25. Suggested UI Example — Generic Section

Example for Complaints:

Complaints

Dr. Kofi Mensah
Main Doctor · 2 entries

[Edit] Fever
Created: 26 May 2026, 10:30 AM

[Edit] Headache
Created: 26 May 2026, 10:32 AM


Dr. Ama Boateng
Contributor · 1 entry

[Edit if owner] Chest pain review
Created: 26 May 2026, 11:02 AM

---

# 26. Suggested UI Example — Investigations

Investigations

Laboratory
2 requests

Dr. Kofi Mensah
    Full Blood Count
    Status: Pending
    Requested: 26 May 2026, 10:45 AM
    [Edit if still editable]

    Malaria RDT
    Status: Result Verified
    Requested: 26 May 2026, 10:45 AM
    Locked: Result already verified

X-Ray
1 request

Dr. Ama Boateng
    Chest X-Ray
    Status: Accepted
    Requested: 26 May 2026, 11:15 AM
    Locked: Request already accepted

---

# 27. Suggested UI Example — Procedures

Procedures

Theatre
1 request

Dr. Kofi Mensah
    Appendectomy
    Status: Scheduled
    Requested: 26 May 2026, 12:05 PM
    Locked: Procedure already scheduled

Minor Procedure Room
1 request

Dr. Ama Boateng
    Wound Dressing
    Status: Pending
    Requested: 26 May 2026, 12:30 PM
    [Edit]

---

# 28. Suggested UI Example — Summary

Consultation Summary

Dr. Kofi Mensah
Main Doctor

Complaints
    Fever
    Headache

History of Presenting Complaint
    Fever started 3 days ago...

Diagnosis
    Malaria

Investigations
    Full Blood Count
    Malaria RDT

Prescriptions
    Paracetamol


Dr. Ama Boateng
Contributor

Additional Notes
    Patient reviewed and ECG advised.

Diagnosis
    Rule out cardiac condition.

---

# 29. Tests Required

Add or update tests.

## Grouping

1. Consultation records are grouped by creator in UI data.
2. Complaints group by doctor/user.
3. HOPC entries group by doctor/user.
4. Examination entries group by doctor/user.
5. Diagnosis entries group by doctor/user.
6. Treatments/prescriptions group by doctor/user where applicable.
7. Notes/Summary groups records by doctor/user.
8. Main doctor and contributor labels are correct.

## Investigations

9. Investigation requests are grouped by department.
10. Investigation requests display owner/requested by.
11. Investigation requests can be grouped by department then owner.
12. Investigation requests do not show Unknown user when owner exists.
13. Investigation edit button appears only before request is processed.
14. Processed/verified investigation request is locked.

## Procedures

15. Procedures display owner/requested by.
16. Procedures group by department and owner where possible.
17. Procedures do not show Unknown user when owner exists.
18. Procedure edit button appears only before procedure is processed.
19. Scheduled/completed procedure is locked.

## Summary Refresh

20. Newly added note appears in summary immediately.
21. Newly added complaint appears in summary immediately.
22. Newly added HOPC appears in summary immediately.
23. Newly added diagnosis appears in summary immediately.
24. Newly added record does not show Unknown user after save.
25. Created record response includes creator.
26. Updated record response includes creator/updated_by.

## Edit Actions

27. Edit button appears for record owner.
28. Edit button does not appear for unauthorized user.
29. Unauthorized user cannot update another doctor’s record.
30. Authorized edit_any user can update with audit trail if required.
31. Locked processed records cannot be edited.
32. Edit modal preloads record data.
33. Edit modal closes after successful update.
34. Modal does not leave backdrop stuck.

## UX

35. Active tab remains active after create.
36. Active tab remains active after update.
37. Page does not fully reload after create/update.
38. Grouped records are refreshed after create/update.

---

# 30. Deliverables

Provide:

1. Gap analysis of current consultation UI ownership display.
2. Grouped-by-user UI for consultation records.
3. Investigation section grouped by department.
4. Investigation owner display fixed.
5. Procedure owner display fixed.
6. Notes/Summary live update fixed.
7. Unknown user after save fixed.
8. Edit actions added where permitted.
9. Edit modals/inline edit implemented.
10. Backend authorization enforced.
11. Eager loading added.
12. Create/update responses include owner relationships.
13. Active tab preservation implemented.
14. Tests or verification notes.
15. Files modified.
16. Remaining TODOs.

---

# 31. Important Rules

Do not rewrite the ownership logic that already works.

Do not remove author attribution.

Do not repeat doctor names unnecessarily if grouping can show it once.

Do not show Unknown user after save when creator exists.

Do not require full page reload to update Notes/Summary.

Do not allow doctors to edit other doctors’ entries unless authorized.

Do not let frontend-only permission checks replace backend authorization.

Do not edit processed investigations/procedures if their workflow state should lock them.

Do not break consultation sections, medical record ownership, visit preview, claims mirror, investigations, procedures, billing, or visits.

Now inspect the current consultation page implementation and improve the UI grouping, investigation/procedure owner display, live summary updates, Unknown user issue, and edit actions as described.