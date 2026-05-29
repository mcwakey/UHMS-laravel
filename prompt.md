````text
You are a senior technical writer and Laravel/Inertia/Vue documentation assistant working on UHMS — Ultimate Hospital Management System.

We need to update the UHMS User Manual to reflect all recent workflow changes and new modules.

The current user manual already exists. Do not rewrite it blindly. First inspect the existing manual files, then update them carefully while preserving the current structure, tone, numbering, and formatting.

Update the user manual so it accurately reflects the current UHMS workflow after the latest changes.

Focus on:

- Emergency Case Management
- Emergency as a Consultation/Clinical Session
- Admission Medication Administration / MAR
- Emergency Medication / MAR
- Clinical Tasks / Reminders
- MAR Chart
- Theatre Rooms Management
- Procedure/Theatre workflow
- Patient Folder Merge
- Notifications
- Logs / Audit Trail
- Pharmacy billing before dispensing
- Ward/Emergency/Investigation/Procedure consumables
- Unified Product Stock system
- Stock Balance matrix
- Consultation ownership/grouping updates
- Consultation Summary document-style page
- Claims preparation clinical mirror
- Insurance-type-based claims workflow
- NHIA/NHIS claims workflow

Do not remove existing valid sections unless they are obsolete.

---

# 1. Main Objective

Update the UHMS User Manual so that end users understand how to use the latest implemented workflows.

The manual should be practical, clear, and role-friendly.

It should explain:

- what each module is for
- who uses it
- where to find it
- how to perform common tasks
- important rules
- workflow status changes
- warnings and restrictions
- expected outputs/results

The manual should not be written like developer documentation.

It should be written for hospital users, admins, nurses, doctors, pharmacists, records officers, cashiers, theatre staff, emergency staff, store officers, claims officers, and system administrators.

---

# 2. Existing Manual Inspection

First inspect existing manual files.

Look for files such as:

- UHMS_USER_MANUAL.md
- UHMS_Updated_User_Manual.md
- USER_MANUAL.md
- docs/user-manual/*
- docs/UHMS_USER_MANUAL.md
- README/manual files

Then decide where updates should be applied.

If there is one main user manual, update that.

If there is an updated manual and an older manual, merge the latest changes into the main manual.

Do not create duplicate competing manuals unless required.

---

# 3. Add Revision Note at Top

At the top of the manual, add a short revision note.

Example:

```text
Revision Note:
This manual has been updated to reflect the redesigned UHMS workflows, including Emergency Case Management, Medication Administration/MAR, Theatre Room Management, Patient Folder Merge, unified stock management, Notifications, Logs, and enhanced Consultation Summary workflows. Some screenshots may predate the latest interface changes and should be regenerated where required.
````

If screenshots are outdated, clearly state that screenshots may need regeneration.

Do not pretend screenshots are updated if they are not.

---

# 4. Update Table of Contents

Update the table of contents to include new/updated sections.

Suggested major sections:

1. Introduction
2. Login and Dashboard
3. User Roles and Permissions
4. Patient Registration
5. Patient Search and Patient Folder
6. Patient Folder Merge
7. Visits / OPD Workflow
8. Emergency Case Management
9. Admission / Inpatient Workflow
10. Consultation Workflow
11. Consultation Summary
12. Clinical Tasks and Reminders
13. Medication Administration Record / MAR
14. MAR Chart
15. Investigations
16. Procedures and Theatre
17. Theatre Rooms Management
18. Pharmacy
19. Billing and Payments
20. Insurance and Claims
21. NHIA / NHIS Claims
22. Store / Products / Stock
23. Ward, Emergency, Investigation and Procedure Consumables
24. Supplier Ledger and Procurement
25. Notifications
26. Logs / Audit Trail
27. Reports
28. System Settings
29. Troubleshooting
30. Appendices

Adapt numbering to the existing manual.

---

# 5. Patient Folder Merge Section

Add a user-facing section explaining Patient Folder Merge.

Explain use cases:

* temporary emergency patient later identified
* duplicate patient records created accidentally

Explain workflow:

1. Open Patient Merge.
2. Search main patient folder to keep.
3. Search duplicate/temporary folder to merge.
4. Compare records side by side.
5. Choose which demographic fields to keep.
6. Preview affected records.
7. Confirm merge.
8. Duplicate folder is locked and redirected to the main folder.

Explain important rules:

* merged folder is not deleted
* old patient number remains searchable
* all visits, emergency cases, admissions, invoices, claims, documents, and clinical records are moved to the main folder
* only authorized users can merge patient folders
* merge cannot be casually reversed from UI

Add emergency identity confirmation:

```text
From an Emergency Case, staff can confirm the identity of a temporary patient by searching the real patient and merging the temporary folder into the real patient folder.
```

---

# 6. Emergency Case Management Section

Add or update Emergency section.

Explain that UHMS has three main patient pathways:

```text
OPD / Visit = Outpatient care
Admission = Inpatient care
Emergency = Urgent or critical care
```

Explain Emergency workflow:

1. Create emergency case.
2. Use existing patient or create unknown/temporary patient.
3. Capture arrival details.
4. Record emergency triage and vitals.
5. Assign bay/bed and team.
6. Start emergency clinical session.
7. Record emergency notes/assessment.
8. Request investigations.
9. Request procedures.
10. Order/administer medication and use MAR.
11. Record consumables.
12. Review billing.
13. Complete disposition.

Emergency statuses:

* ARRIVED
* WAITING_TRIAGE
* TRIAGED
* UNDER_EMERGENCY_CARE
* OBSERVATION
* READY_FOR_DISPOSITION
* DISPOSED
* CANCELLED

Disposition options:

* Admitted
* Discharged
* Transferred to OPD
* Transferred to Theatre
* Referred out
* Left against medical advice
* Absconded
* Died
* Dead on arrival

Important rule:

```text
Emergency care must not be blocked because payment has not been made.
```

---

# 7. Emergency as Consultation Session

Add this clearly.

Emergency must be explained as a clinical session.

User-facing explanation:

```text
When a patient goes through Emergency, UHMS creates an Emergency Session under the visit. This session appears together with other consultation sessions so doctors can view the emergency history before continuing care.
```

Explain that Consultation page can show:

* Emergency Session
* OPD Session
* Dental Session
* ENT Session
* Admission Review Session

Emergency Session contains:

* triage
* vitals
* emergency notes
* medications/MAR
* investigations
* procedures
* consumables
* billing references
* disposition

Explain that this improves:

* patient history
* visit preview
* claims preparation
* continuity of care

---

# 8. Emergency Triage and Vitals

Update emergency triage documentation.

Explain that triage can be auto-calculated from:

* temperature
* pulse
* respiratory rate
* blood pressure
* oxygen saturation
* AVPU/consciousness
* pain score
* danger signs
* trauma/bleeding/seizure/respiratory distress indicators

Triage categories:

* RED: Immediate / Resuscitation
* ORANGE: Very urgent
* YELLOW: Urgent
* GREEN: Less urgent
* BLACK: Dead on arrival / expectant

Explain that users may override the auto-suggested category if authorized and must provide a reason.

Explain vitals display:

* latest vitals cards
* vitals history table
* vitals graph/trends
* recorded by and time

---

# 9. Bay / Bed and Team Assignment

Add emergency bay/team section.

Explain:

1. Select emergency ward/unit.
2. Select available bay/bed.
3. Assign patient.
4. Assign main emergency doctor.
5. Assign primary nurse.
6. Add contributors.

Explain that contributors can add records without replacing the main doctor/nurse.

Explain bed/bay statuses:

* Available
* Occupied
* Cleaning
* Out of service
* Reserved

---

# 10. Admission Medication Administration / MAR

Add or update Admission medication section.

Explain the difference:

```text
Prescription = what doctor ordered.
Dispensing = what pharmacy supplied.
Administration = what nurse actually gave.
```

Explain workflow:

1. Doctor prescribes medication.
2. Pharmacy dispenses medication.
3. System generates administration schedules.
4. Clinical tasks/reminders notify nurses.
5. Nurse records each dose.
6. System tracks progress until complete.

Medication order statuses:

* Pending dispensing
* Partially dispensed
* Dispensed
* Active administration
* Completed
* Held
* Stopped
* Cancelled
* Expired

Dose statuses:

* Scheduled
* Due
* Overdue
* Given
* Held
* Missed
* Refused
* Skipped
* Cancelled

Explain that nurse administration does not reduce stock again if pharmacy already dispensed the medication.

---

# 11. Emergency Medication / MAR

Add emergency medication workflow.

Explain emergency supports:

* STAT medication
* PRN/SOS medication
* scheduled medication
* immediate administration
* emergency stock source
* MAR chart

Explain stock rule:

```text
If medication is administered from Emergency stock, stock is deducted once from Emergency stock.
If medication was dispensed by Pharmacy to the patient, administration does not deduct stock again.
```

Explain that medication search comes from Products, not a separate drug list.

Explain automatic quantity calculation:

```text
BD for 5 days = 10 doses.
TDS for 3 days = 9 doses.
```

---

# 12. Clinical Tasks and Reminders

Add or update Clinical Tasks section.

Explain clinical tasks can remind staff for:

* medication administration
* vitals monitoring
* wound dressing
* blood sugar check
* doctor review
* investigation follow-up
* procedure preparation
* nursing observation

Explain statuses:

* Scheduled
* Due
* Overdue
* In Progress
* Completed
* Missed
* Held
* Refused
* Cancelled

Explain due/overdue alerts and escalation.

---

# 13. MAR Chart Section

Add a MAR Chart section.

Explain:

```text
The MAR Chart is a patient-specific medication administration grid. It is not a statistical chart.
```

Explain layout:

* rows = medications
* columns = scheduled times
* cells = dose status

Explain actions:

* Due/Overdue cell opens administration modal
* Given/Held/Missed/Refused cell opens details
* PRN/SOS medications appear separately
* Print MAR button prints the chart

Explain statuses and legend.

Mention where MAR can be opened:

* Admission Board
* Emergency Board
* Admission Detail
* Emergency Case Detail
* Visit Preview

---

# 14. Consultation Workflow Updates

Update Consultation section to include required clinical order:

1. Vitals / Patient Summary
2. Complaints
3. History of Presenting Complaint
4. Examination / Physical Examination
5. Diagnosis
6. Investigations
7. Treatments / Prescriptions
8. Procedures
9. Tasks / Follow-up / Instructions
10. Notes / Summary

Explain record ownership:

* records are grouped by doctor/user
* contributors are shown
* main doctor is not overwritten
* users can edit only records they are allowed to edit

Explain Emergency Session appears in consultation sessions if the patient passed through Emergency.

---

# 15. Consultation Summary Page

Update Consultation Summary section.

Describe the new document-style summary page.

It should show:

* patient and visit header
* session details
* main doctor and contributors
* complaints
* HOPC
* examination
* diagnoses
* investigations
* treatments/prescriptions
* procedures
* tasks/follow-up
* notes
* record owners
* dates/times

Mention:

```text
The Consultation Summary page is designed like a readable clinical document and should not hide important information.
```

Mention print option if implemented.

---

# 16. Investigations

Update Investigations section.

Explain both consultation and emergency investigations.

User workflow:

1. Select investigation department.
2. Select service/items.
3. Add clinical reason.
4. Submit request.
5. Department accepts/handles request.
6. Results are entered.
7. Results are verified.
8. Doctor can view verified results.

Emergency investigations can be marked:

* Emergency
* Urgent
* Routine

Explain grouping by department and owner.

Explain consumables used by investigation departments are deducted from their department stock.

---

# 17. Procedures and Theatre

Update Procedure section.

Explain procedure request workflow:

1. Doctor requests procedure.
2. Procedure/Theatre accepts.
3. Billing item may be created.
4. Theatre schedules.
5. Pre-op is completed.
6. Anaesthesia note is recorded.
7. Surgeon operative note is recorded.
8. Recovery/post-op note is recorded.
9. Case is completed.

Explain procedures can come from:

* Consultation
* Emergency
* Admission

Explain billing is separate from the clinical procedure section.

---

# 18. Theatre Rooms Management

Add new Theatre Rooms section.

Explain:

* theatre rooms CRUD
* room statuses
* theatre schedule board
* room calendar
* scheduling theatre case
* double-booking prevention
* emergency theatre cases
* theatre team
* pre-op checklist
* anaesthesia note
* operative note
* recovery note
* consumables
* billing
* visit preview

Room statuses:

* Available
* Occupied
* Scheduled
* Cleaning
* Maintenance
* Out of service
* Reserved

Theatre case statuses:

* Requested
* Accepted
* Billed
* Scheduled
* Pre-op
* Anaesthesia Ready
* In Theatre
* In Surgery
* Surgery Done
* Recovery
* Post-op
* Completed
* Cancelled
* Postponed

---

# 19. Pharmacy Updates

Update Pharmacy section.

Explain drugs can be billed before dispensing.

Workflow:

1. Doctor prescribes drugs.
2. Pharmacy reviews prescription.
3. Pharmacy selects drugs to bill/dispense.
4. Pharmacy can reduce quantities.
5. Only selected drugs are billed.
6. Only billed drugs appear on dispense page.
7. Dispensing reduces pharmacy stock.

Important:

```text
Billing is financial. Dispensing is physical stock movement.
```

Explain pharmacy catalogue displays:

* Pharmacy Available Qty
* Main Stock Qty
* Stock Status

---

# 20. Ward and Emergency Consumables

Add Ward/Emergency consumables section.

Explain:

* Ward uses Ward stock location.
* Emergency uses Emergency stock location.
* Users select products/consumables linked to their department.
* Usage deducts from department stock.
* Billable consumables create invoice items.
* Non-billable consumables only create stock movements.
* Departments cannot create products.

---

# 21. Stock / Products / Inventory

Update Store/Stock section.

Reinforce:

```text
Every physical item comes from Products.
```

Includes:

* drugs
* consumables
* investigation items
* procedure items
* theatre consumables
* emergency supplies
* ward supplies

Explain Main Store rule:

* purchase receipts go into Main Store
* departments receive stock through requisition/transfer
* departments consume only from their own stock locations

Explain Stock Balance Matrix:

```text
Product | Main Store | Pharmacy | Ward | Emergency | Lab | Theatre | Total | Status
```

Explain low stock display:

* no yellow row background
* status shown beside each location quantity
* statuses: OK, LOW, CRITICAL, OUT, NOT STOCKED

---

# 22. Insurance and Claims

Update Insurance/Claims section.

Explain insurance type based claims:

```text
Insurance Type = claim workflow
Insurance Provider = organization under that type
```

Example:

```text
Insurance Type: NHIA
Provider: NHIS
```

Explain NHIA/NHIS claims preparation:

1. Claim officer opens eligible visit.
2. System prepares claim from invoice items.
3. Claim officer reviews clinical mirror.
4. Claim officer can select doctor-entered information or enter claim-facing manual details.
5. CCC/verification code is entered if required.
6. Claim is validated.
7. Claim is marked ready.
8. Claim is exported/submitted.
9. Claim status and payment are tracked.

Explain claim clinical mirror includes:

* consultation
* complaints
* HOPC
* diagnosis
* prescriptions/drugs
* investigations
* procedures
* invoice items

Emphasize claim edits do not overwrite clinical records.

---

# 23. Billing Updates

Update Billing section.

Explain single visit invoice:

```text
One visit = one invoice.
```

Items are added from:

* visit services
* emergency services
* investigations
* pharmacy
* procedures/theatre
* consumables
* admission/ward charges

Explain invoice item logic:

* cash price
* insurance price
* selected price
* patient payable
* paid amount
* balance
* discount entered manually by user

Explain payments can cover invoice lines partially or fully.

---

# 24. Notifications Section

Add Notifications section.

Explain notifications are used for:

* emergency alerts
* medication due/overdue
* clinical tasks
* investigation results
* procedure/theatre updates
* stock alerts
* claims
* patient merge requests
* billing/payment alerts

Explain notification UI:

* bell icon
* unread count
* notification list
* mark as read
* action links

Explain users only receive notifications relevant to their role/department/assignment.

---

# 25. Logs / Audit Trail Section

Add Logs section.

Explain logs track:

* who did what
* when it happened
* which record was affected
* old and new values
* reason for correction/override
* module/source

Explain logs exist for:

* clinical actions
* financial actions
* stock actions
* emergency
* admission
* MAR
* theatre
* patient merge
* security/authentication
* user/permission changes

Explain only authorized users can view logs.

---

# 26. Reports Section

Update Reports section to include new reports:

Emergency reports:

* attendance
* triage category
* waiting time
* disposition
* mortality
* emergency medication

MAR reports:

* medication administration
* overdue medication
* missed dose
* nurse administration

Theatre reports:

* room utilization
* procedures by surgeon
* cancelled/postponed cases
* consumables usage
* anaesthesia report

Stock reports:

* stock balance matrix
* low/out stock
* department stock
* movement history
* requisitions/transfers

Claims reports:

* submitted claims
* rejected claims
* paid claims
* NHIA/NHIS claims

Logs/notification reports if implemented.

---

# 27. Screenshots

Do not generate fake screenshots.

If screenshots are outdated, add a note:

```text
Screenshot update required: this section has changed after the latest workflow redesign.
```

If screenshot placeholders exist, mark them clearly.

If the project has a screenshot capture script, update references but do not claim screenshots were regenerated unless actually done.

---

# 28. Style Requirements

The user manual should be:

* clear
* practical
* user-focused
* not too technical
* organized by module
* step-by-step where useful
* consistent in headings
* easy for hospital staff to follow

Use tables where helpful for statuses and roles.

Use warnings/notes for important rules.

Example:

```text
Important:
Billing a drug does not reduce stock. Stock is reduced only when the drug is dispensed.
```

---

# 29. Do Not Include Developer-Only Details

Avoid too much code-level explanation.

Do not include migrations, model names, route names, service class names, unless the existing manual already has a technical appendix.

This is a user manual, not an implementation prompt.

---

# 30. Deliverables

Provide:

1. Updated user manual file.
2. Any updated linked documentation files if needed.
3. A short summary of sections updated.
4. A list of screenshots that need regeneration.
5. A list of assumptions or unclear areas.
6. Files modified.

---

# 31. Important Rules

Do not remove existing useful documentation.

Do not invent screenshots.

Do not claim features are complete if the manual is only describing planned workflow. If a feature is not fully implemented, mark it as pending or planned based on current code.

Do not create duplicate manuals unless necessary.

Do not write developer implementation details inside the user manual.

Do not contradict the updated UHMS workflow.

Now inspect the current manual and update it to reflect all recent UHMS workflow changes.

```
```
