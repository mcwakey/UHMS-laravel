````text
You are a senior Laravel + Inertia/Vue developer and UI/UX designer working on UHMS — Ultimate Hospital Management System.

The previous consultation UI ownership/grouping prompt has already been implemented.

Now update the **Consultation Summary page** so it fully accommodates those changes.

The Consultation Summary page must now feel like a clean, modern, stylish, readable **clinical document**, not just a basic list/table. It must show all available consultation information clearly, without hiding important information behind collapsed sections, tabs, hover-only content, or modals.

Focus only on the Consultation Summary page/view and the data needed to render it properly.

Do not break:
- consultation records
- record ownership
- medical records
- visit preview
- investigations
- procedures
- prescriptions
- billing
- claims mirror
- visit workflow

---

# 1. Main Objective

Update the Consultation Summary page into a full clinical document-style page.

The page should clearly display:

- patient information
- visit information
- consultation session information
- main doctor
- contributing doctors
- complaints
- history of presenting complaint
- examination
- diagnoses
- investigations
- treatments
- prescriptions
- procedures
- tasks/follow-up/instructions
- clinical notes
- record owners/authors
- dates/times
- source pattern information if available
- edit/locked status where applicable

The page must reflect the ownership/grouping changes already implemented.

---

# 2. Document Feel Requirement

The page should look and read like a proper medical consultation document.

Style goal:

Modern, clean, professional, stylish, readable, printable.

It should feel like:

```text
Clinical Consultation Summary
Patient Medical Record Document
Visit Consultation Report
````

Not like:

```text
Random admin table
Raw database dump
Crowded dashboard widget
```

Use a clear document layout with:

* page header
* patient/visit information block
* consultation session block
* section headings
* grouped clinical content
* author attribution
* clean spacing
* readable typography
* subtle borders
* cards or paper-like container
* print-friendly layout

---

# 3. No Hidden Information

Very important:

Do not hide important information.

Avoid:

* collapsed accordions for clinical sections
* tabs that hide sections
* hover-only details
* modals for viewing main content
* “view more” hiding key clinical data
* summary-only cards that omit details

The Consultation Summary page should show all available clinical information directly on the page.

It is okay to use visual grouping, but not hidden sections.

If content is long, let the page scroll.

---

# 4. Page Layout

Recommended document layout:

```text
Consultation Summary

Patient & Visit Header
Consultation Session Details
Clinical Summary Sections
Author / Contributor Summary
Timeline / Audit Metadata if useful
Print / Export Actions
```

Use a document-like centered container:

```text
max-width: readable document width
white / soft background
border or shadow
rounded corners if consistent with UI
good spacing
print-friendly
```

---

# 5. Header Section

At the top, show:

```text
Consultation Summary
Visit Number
Patient Name
Patient Number
Visit Date
Visit Type
Visit Status
Department Session
Main Doctor
Contributors
Generated Date/Time
```

Example:

```text
CONSULTATION SUMMARY

Patient: Ama Mensah
Patient No: PAT-2026-000012
Visit No: VST-2026-000045
Visit Type: OPD
Visit Date: 26 May 2026
Department Session: OPD Consultation
Main Doctor: Dr. Kofi Mensah
Contributors: Dr. Ama Boateng, Dr. Yao Mensah
Status: CONSULTING
```

---

# 6. Patient Information Block

Show:

```text
Patient name
Patient number
Age
Gender
Phone number
Ghana Card number if available
Insurance provider used for visit
Membership number if available
Occupation if available
Marital status if available
Religion if available
Next of kin if available
```

Do not hide these details if they exist.

Use a clean two-column or three-column document grid.

---

# 7. Visit Information Block

Show:

```text
Visit number
Visit type
Visit date/time
Visit status
Department/session
Services linked to this session
Main doctor
Contributors
Triage score if available
Latest vitals if available
```

If the visit has multiple consultation sessions, show the current session being summarized and list other sessions briefly.

Example:

```text
Other Consultation Sessions:
- Dental Department — Completed
- ENT Department — Pending
```

---

# 8. Ownership / Contributor Summary

Because records are now grouped by owner, show a contributor summary near the top.

Example:

```text
Contributors

Dr. Kofi Mensah — Main Doctor — 8 entries
Dr. Ama Boateng — Contributor — 2 entries
Dr. Yao Mensah — Contributor — 1 entry
```

This should help the reader understand who contributed to the document.

---

# 9. Clinical Section Order

The Consultation Summary page must show sections in this order:

```text
1. Complaints
2. History of Presenting Complaint
3. Examination / Physical Examination
4. Diagnosis
5. Investigations
6. Treatments / Prescriptions
7. Procedures
8. Tasks / Follow-up / Instructions
9. Notes / Clinical Summary
```

If a section has no records, show a soft empty state:

```text
No examination findings recorded.
```

Do not completely omit important clinical sections unless the project’s UI convention prefers hiding empty sections. Preferred: show section title with empty state.

---

# 10. Group Records by Owner Inside Sections

Within each clinical section, group entries by doctor/user.

Example:

```text
Complaints

Dr. Kofi Mensah
Main Doctor · 2 entries

- Fever
  Created: 26 May 2026, 10:30 AM

- Headache
  Created: 26 May 2026, 10:32 AM


Dr. Ama Boateng
Contributor · 1 entry

- Chest discomfort
  Created: 26 May 2026, 11:02 AM
```

Do not repeat:

```text
Entered by Dr. Kofi
Entered by Dr. Kofi
Entered by Dr. Kofi
```

on every row if the entries are already under Dr. Kofi’s group.

But each entry should still show enough metadata:

```text
created time
updated time if edited
source pattern if available
locked/edit status if useful
```

---

# 11. Section: Complaints

Display all complaints grouped by owner.

Each complaint should show:

```text
complaint text
severity if available
duration if available
created at
updated at if edited
source pattern if available
edit action if permitted
```

---

# 12. Section: History of Presenting Complaint

Display HOPC entries grouped by owner.

Each entry should show:

```text
full narrative/content
onset if available
duration if available
location if available
character if available
radiation if available
associated symptoms if available
aggravating factors if available
relieving factors if available
severity if available
timing if available
notes if available
created at
updated at if edited
source pattern if available
edit action if permitted
```

This section must not be summarized so aggressively that clinical meaning is lost.

---

# 13. Section: Examination / Physical Examination

Display examination findings grouped by owner.

Show:

```text
general examination
systemic examination
local examination
specialty-specific examination
findings
notes
created at
updated at if edited
source pattern if available
edit action if permitted
```

If examination data is structured, display it neatly with labels.

If free-text, show readable paragraph formatting.

---

# 14. Section: Diagnosis

Display diagnosis entries grouped by owner.

Each diagnosis should show:

```text
diagnosis name
ICD-10 code if available
provisional/final
primary diagnosis badge if applicable
created at
updated at if edited
source pattern if available
edit action if permitted
```

Example:

```text
Malaria
ICD-10: B54
Final Diagnosis · Primary
Created: 26 May 2026, 10:45 AM
```

---

# 15. Section: Investigations

Investigations should be grouped by department, then by owner.

Example:

```text
Investigations

Laboratory

Dr. Kofi Mensah
- Full Blood Count
  Status: Result Verified
  Requested: 26 May 2026, 10:45 AM
  Result: [summary if available]

- Malaria RDT
  Status: Pending
  Requested: 26 May 2026, 10:45 AM

X-Ray

Dr. Ama Boateng
- Chest X-Ray
  Status: Accepted
  Requested: 26 May 2026, 11:15 AM
```

Each investigation should show:

```text
department
requested service/item
requested by
requested at
status
accepted/billed/result status if available
result summary if available
verified by if available
edit/locked action where applicable
```

Do not hide result summaries if available.

---

# 16. Section: Treatments / Prescriptions

Show treatments and prescriptions grouped by owner.

For treatment plans:

```text
treatment description
instructions
created at
created by
```

For prescriptions:

```text
product/drug
dosage
frequency
duration
quantity
instructions
prescription status
dispensing status if available
created at
created by
```

If pharmacy has dispensed, show dispensing status clearly.

Do not imply prescribing reduced stock.

---

# 17. Section: Procedures

Procedures should be grouped by department, then by owner where possible.

Each procedure should show:

```text
procedure department
procedure service
requested by
requested at
status
scheduled date if available
surgeon note if available
anaesthesia note if available
post-op note if available
completed status if available
edit/locked state where applicable
```

Requested procedures must display owners.

Do not show Unknown user if owner exists.

---

# 18. Section: Tasks / Follow-up / Instructions

Show tasks grouped by owner or assigned person if more useful.

Each task should show:

```text
title
description
priority
status
assigned to
due date
created by
completed by if completed
completed at if completed
source pattern if available
edit action if permitted
```

Follow-up instructions should be visible directly.

---

# 19. Section: Notes / Clinical Summary

Show notes grouped by owner.

The Notes / Clinical Summary section should include:

```text
general clinical notes
additional doctor inputs
summary notes
assessment notes
follow-up notes
```

Each note should show:

```text
content
created by
created at
updated by if edited
updated at if edited
source pattern if available
```

This section must update immediately after new additions without requiring page reload.

---



# 22. Unknown User Fix

If any record appears as Unknown user until reload, fix the response/eager-loading.

All records rendered in Consultation Summary must include owner relationship immediately.

For each record type, eager-load:

```text
creator
createdBy
doctor
requestedBy
enteredBy
updatedBy
```

depending on the actual relationship names.

For create/update responses, return the record with owner relationships loaded.

Do not query users inside Vue/Blade loops.

---

# 23. Print-Friendly Design

The Consultation Summary page should be print-friendly.

Add a button:

```text
Print Summary
```

It may call:

```js
window.print()
```

For print:

* hide navigation/sidebar/buttons
* keep document content readable
* show patient/visit header
* show all clinical sections
* show author/contributor information
* avoid dark backgrounds
* avoid tiny text

Optional future button:

```text
Export PDF
```

but do not implement PDF unless the project already has PDF infrastructure.

---

# 24. Visual Style

Make it modern but readable.

Use:

```text
clear headings
subheadings
soft borders
paper-like background
section dividers
badges for status
small metadata text
good spacing
readable font sizes
print-friendly colors
```

Avoid:

```text
overly dense tables
hidden accordions
too many loud colors
small unreadable text
excessive icons
repeating doctor names on every row
```

Suggested style:

```text
Document container
Section cards or section blocks
Owner group blocks
Clean badges
Muted metadata
```

---

# 25. Data Loading Requirements

The backend should provide all data needed to render the summary without N+1 queries.

Load:

```text
patient
visit
insurance
consultation route/session
main doctor
contributors
complaints with owners
HOPC with owners
examinations with owners
diagnoses with owners
investigations with department + owners + results
treatments with owners
prescriptions with owners + dispensing status
procedures with department + owners
tasks with owners/assigned users
notes with owners
source patterns where available
updated_by users where available
```

Use a dedicated service if needed:

```text
ConsultationSummaryService
```

This service should normalize the data for display.

---

# 26. Recommended Normalized Structure

The Consultation Summary page can receive data like:

```js
{
  patient: {},
  visit: {},
  session: {},
  contributors: [],
  sections: [
    {
      key: "complaints",
      title: "Complaints",
      owner_groups: [
        {
          user_id: 1,
          user_name: "Dr. Kofi Mensah",
          role_label: "Main Doctor",
          entries: [...]
        }
      ]
    },
    {
      key: "investigations",
      title: "Investigations",
      department_groups: [
        {
          department_id: 2,
          department_name: "Laboratory",
          owner_groups: [...]
        }
      ]
    }
  ]
}
```

Use this or a similar structure that keeps the frontend simple and avoids repeated grouping logic everywhere.

---

# 27. Authorization

The summary page must respect record-level edit permissions.

Backend must enforce permissions on update endpoints.

Frontend should only show edit buttons when the user can edit.

Permission rules remain:

```text
creator can edit own entry while session active
other users cannot edit unless edit_any
completed session entries locked unless correction permission
processed investigations/procedures/prescriptions locked according to workflow
```

---

# 28. Tests Required

Add or update tests:

1. Consultation Summary page loads successfully.
2. Summary page has document-style sections.
3. Summary page shows patient header.
4. Summary page shows visit/session header.
5. Summary page shows contributors.
6. Summary sections appear in correct order.
7. Complaints are grouped by owner.
8. HOPC entries are grouped by owner.
9. Examination entries are grouped by owner.
10. Diagnoses are grouped by owner.
11. Investigations are grouped by department then owner.
12. Procedures are grouped by department then owner where available.
13. Prescriptions show owner and dispensing status.
14. Tasks show owner/assigned user.
15. Notes show owner.
16. Summary does not repeat owner name unnecessarily on every row.
17. Summary does not falsely attribute all records to main doctor.
18. Records do not show Unknown user when owner exists.
19. New additions appear immediately without full reload.
20. Updated records appear immediately without full reload.
21. Edit button appears for owner.
22. Edit button does not appear for unauthorized user.
23. Locked processed records show locked state.
24. Print button exists.
25. Print layout hides unnecessary navigation/actions.

---

# 29. Deliverables

Provide:

1. Gap analysis of current Consultation Summary page.
2. Updated document-style Consultation Summary page.
3. Patient/visit/session header.
4. Contributor summary.
5. Clinical sections in correct order.
6. Grouped-by-owner display.
7. Investigation grouping by department then owner.
8. Procedure owner display/grouping.
9. No hidden clinical information.
10. Live update behavior.
11. Unknown user fix.
12. Edit actions where permitted.
13. Print-friendly layout.
14. Eager loading / summary service updates.
15. Tests or verification notes.
16. Files modified.
17. Remaining TODOs.

---

# 30. Important Rules

Do not hide important clinical information behind collapsed UI.

Do not repeat doctor names unnecessarily.

Do not show all entries as if they belong to the main doctor.

Do not show Unknown user when creator exists.

Do not require a full reload for new or edited records to appear.

Do not break existing consultation ownership logic.

Do not break edit permissions.

Do not break investigations, procedures, prescriptions, tasks, visit preview, or claims mirror.

Now inspect the current Consultation Summary page and update it into a modern, stylish, readable, document-like clinical summary page that displays all information clearly and supports the new grouped ownership UI.

```
```
