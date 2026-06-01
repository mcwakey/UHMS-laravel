You are a senior Laravel + Inertia/Vue developer working on UHMS — Ultimate Hospital Management System.

We need to build and seed a proper Complaint Catalogue and Patient Complaint recording system.

Complaints are currently needed in consultation, emergency, admission review, medical patterns, consultation summary, visit preview, claims mirror, and patient history.

The goal is to allow doctors and clinicians to select common complaints from a seeded catalogue, search complaints easily, add custom complaints when needed, and save complaints correctly under the patient’s medical record/session.

Do not break existing consultation, emergency, admission, medical records, HOPC, diagnosis, medical patterns, visit preview, or claims workflows.

---

# 1. Main Objectives

Implement:

1. Complaint Catalogue / Complaint Definitions.
2. Seed common complaints.
3. Complaint search/autocomplete.
4. Patient complaint recording.
5. Multiple complaints per medical record/session.
6. Custom complaint entry when complaint is not in catalogue.
7. Complaint duration/severity fields.
8. Link complaint to HOPC where applicable.
9. Complaint ownership/created_by tracking.
10. Complaint display in Consultation Summary.
11. Complaint display in Visit Preview.
12. Complaint support in Emergency Session.
13. Complaint support in Medical Patterns.
14. Complaint support in Claims clinical mirror.

---

# 2. Complaint Catalogue

Create or update a reusable complaint catalogue table.

Recommended table:

complaint_catalogues

Fields:

id
name
category nullable
body_system nullable
description nullable
keywords json nullable
is_active boolean default true
sort_order nullable
created_at
updated_at

Alternative names allowed if project convention differs:

complaint_definitions
complaint_master
complaint_templates

Use one consistent name across the system.

---

# 3. Complaint Categories

Seed complaints under useful categories.

Recommended categories:

General
Respiratory
Cardiovascular
Gastrointestinal
Neurological
Genitourinary
Musculoskeletal
Dermatology
ENT
Eye
Dental
Obstetrics/Gynecology
Pediatrics
Psychiatric
Emergency/Trauma

---

# 4. Patient Complaint Records

Create or update patient complaint records.

Recommended table:

patient_complaints

Fields:

id
patient_id
visit_id
medical_record_id nullable
consultation_route_id nullable
clinical_session_id nullable
emergency_case_id nullable
admission_id nullable
complaint_catalogue_id nullable
complaint_text
duration nullable
duration_unit nullable
severity nullable
notes nullable
source_pattern_id nullable
created_by
updated_by nullable
created_at
updated_at

If the project already has a complaints table, inspect it first and extend it instead of creating a duplicate table.

---

# 5. Complaint Recording Rules

Doctors/clinicians must be able to:

- search seeded complaints
- select one or more complaints
- add custom complaint if not found
- enter duration
- choose duration unit
- enter severity
- add notes
- save under the current medical record/session
- edit own complaint if permitted
- view complaints grouped by owner/user where already implemented

Duration units:

Minutes
Hours
Days
Weeks
Months
Years

Severity values:

Mild
Moderate
Severe
Critical

---

# 6. Consultation Page Integration

On the Consultation page, Complaints should appear before History of Presenting Complaint.

Required clinical order:

1. Vitals / Patient Summary
2. Complaints
3. History of Presenting Complaint
4. Examination
5. Diagnosis
6. Investigations
7. Treatments / Prescriptions
8. Procedures
9. Tasks / Follow-up / Instructions
10. Notes / Summary

Complaint section should allow:

- search complaint catalogue
- add selected complaint
- add custom complaint
- display recorded complaints
- group by owner/user if ownership grouping exists
- edit permitted complaints
- show duration/severity/notes
- show creator and timestamp

Do not merge complaints with HOPC.

---

# 7. HOPC Link

History of Presenting Complaint may optionally link to one or more complaints.

Support:

complaint_id nullable

or if multiple complaints:

hopc_complaint pivot table

Use the simplest structure consistent with current implementation.

Rules:

- HOPC can describe one complaint or multiple complaints.
- Do not force every HOPC to link to a complaint.
- Do not force one HOPC per complaint.
- User can write one general HOPC narrative covering multiple complaints.

---

# 8. Emergency Integration

Emergency Session must support complaints.

Emergency complaint workflow:

- during emergency case creation, chief complaint may be captured
- chief complaint should become a patient complaint record if appropriate
- emergency clinical session should show complaints
- emergency complaints should appear in Consultation page session view
- emergency complaints should appear in Visit Preview

Emergency complaint must link to:

patient_id
visit_id
emergency_case_id
consultation_route_id / emergency session id
created_by

---

# 9. Admission Integration

Admission review should support complaints where clinically needed.

If admission review uses the same consultation/session system, use the same complaint component.

Do not create separate admission-only complaint storage unless already required.

---

# 10. Medical Pattern Integration

Medical Patterns must support complaints.

Pattern item type:

COMPLAINT

When creating a medical pattern:

- user can add complaint catalogue items
- user can add custom complaint text
- user can set default duration/severity only if useful

When applying a pattern:

- selected complaints are created under the current medical record/session
- created_by is the current user
- source_pattern_id is set
- complaints appear in Consultation Summary and Visit Preview

Do not attribute pattern-applied complaints to the original pattern creator unless they are the current user applying it.

---

# 11. Claims Mirror Integration

Claims preparation mirror should show complaints.

Claims officer should be able to:

- view clinical complaints
- select/copy complaint text into claim-facing fields if needed
- not edit original clinical complaints

Do not allow claims officer to overwrite doctor-entered complaints.

---

# 12. Visit Preview Integration

Visit Preview must show complaints chronologically and under the correct session.

Display:

Complaint
Duration
Severity
Notes
Entered by
Session/department
Created at
Source pattern if applicable

Emergency complaints should appear under Emergency Session.

OPD complaints should appear under OPD Consultation Session.

---

# 13. Complaint Seeder

Create seeder:

ComplaintCatalogueSeeder

Seed common complaints.

Use idempotent seeding:

- updateOrCreate by name/category
- do not create duplicates
- keep existing custom records
- do not deactivate existing records unless explicitly needed

Seed at least these complaints:

General:
Fever
General weakness
Body pain
Fatigue
Loss of appetite
Weight loss
Night sweats
Malaise

Respiratory:
Cough
Shortness of breath
Chest tightness
Wheezing
Sore throat
Runny nose
Nasal congestion
Coughing blood

Cardiovascular:
Chest pain
Palpitations
Leg swelling
Fainting
Dizziness
High blood pressure complaint

Gastrointestinal:
Abdominal pain
Vomiting
Nausea
Diarrhea
Constipation
Heartburn
Blood in stool
Loss of appetite
Abdominal swelling

Neurological:
Headache
Convulsion
Loss of consciousness
Confusion
Weakness of limb
Numbness
Tremors
Dizziness

Genitourinary:
Painful urination
Frequent urination
Blood in urine
Flank pain
Urinary retention
Incontinence

Musculoskeletal:
Back pain
Joint pain
Neck pain
Limb pain
Swelling of joint
Difficulty walking
Trauma injury

Dermatology:
Skin rash
Itching
Skin wound
Burn
Swelling
Ulcer
Skin infection

ENT:
Ear pain
Ear discharge
Hearing loss
Nose bleeding
Sore throat
Difficulty swallowing

Eye:
Eye pain
Red eye
Blurred vision
Eye discharge
Loss of vision
Foreign body in eye

Dental:
Toothache
Gum bleeding
Facial swelling
Mouth ulcer
Dental trauma

Obstetrics/Gynecology:
Vaginal bleeding
Lower abdominal pain in pregnancy
Labour pains
Reduced fetal movement
Vaginal discharge
Missed period
Pregnancy-related complaint

Pediatrics:
Poor feeding
Excessive crying
Fever in child
Diarrhea in child
Vomiting in child
Convulsion in child
Difficulty breathing in child

Psychiatric:
Anxiety
Insomnia
Depressed mood
Aggression
Confusion
Substance use concern

Emergency/Trauma:
Road traffic accident
Fall injury
Assault
Burn injury
Poisoning
Snake bite
Animal bite
Severe bleeding
Unconsciousness
Seizure
Breathing difficulty
Severe pain

---

# 14. Complaint Search API

Create endpoint for complaint search/autocomplete.

Example route:

GET /admin/complaints/search?q=fever

Return:

id
name
category
body_system
description
keywords

Rules:

- only active complaints
- search by name, category, keywords
- limit results
- fast response
- usable in Consultation/Emergency/Admission pages

---

# 15. Complaint Management UI

Add optional admin page for complaint catalogue management.

Menu:

Settings or Clinical Setup
    Complaints Catalogue

Page should allow authorized users to:

- view complaints
- search/filter by category
- create complaint
- edit complaint
- activate/deactivate complaint
- manage keywords

Permissions:

complaints.catalogue.view
complaints.catalogue.create
complaints.catalogue.update
complaints.catalogue.deactivate

If time is limited, at minimum seed and search catalogue now, then add management UI later.

---

# 16. Permissions

Add or verify:

complaints.view
complaints.create
complaints.edit_own
complaints.edit_any
complaints.delete_own
complaints.delete_any
complaints.catalogue.view
complaints.catalogue.create
complaints.catalogue.update
complaints.catalogue.deactivate

Use existing consultation entry permissions if the system already uses generic permissions.

Do not allow unauthorized users to modify other clinicians’ complaints.

---

# 17. Backend Services

Create or update:

ComplaintCatalogueService
PatientComplaintService
ComplaintSearchService
MedicalPatternService
ConsultationSummaryService
VisitPreviewService
ClaimPreparationMirrorService

Do not put all logic directly in controllers.

---

# 18. Controllers / Routes

Create or update:

ComplaintCatalogueController
ComplaintSearchController
PatientComplaintController

Suggested routes:

GET /admin/complaints/catalogue
POST /admin/complaints/catalogue
PATCH /admin/complaints/catalogue/{complaint}
GET /admin/complaints/search
POST /admin/medical-records/{medicalRecord}/complaints
PATCH /admin/patient-complaints/{complaint}
DELETE /admin/patient-complaints/{complaint}

Adapt to existing route conventions.

---

# 19. Frontend Components

Create reusable component:

ComplaintSelector.vue

Features:

- search complaint catalogue
- select complaint
- add custom complaint
- duration input
- duration unit select
- severity select
- notes
- add to list
- validation errors
- loading state

Use in:

- Consultation Complaints section
- Emergency clinical session
- Admission review if needed
- Medical pattern builder

---

# 20. Validation Rules

Patient complaint save:

- patient_id required
- visit_id required
- medical_record_id or consultation_route_id required where applicable
- complaint_catalogue_id nullable exists
- complaint_text required if no complaint_catalogue_id
- duration nullable
- duration_unit nullable valid value
- severity nullable valid value
- created_by current user

Catalogue complaint:

- name required
- category nullable
- body_system nullable
- is_active boolean
- name/category should be unique where reasonable

---

# 21. Data Integrity Rules

- Do not duplicate patient complaint records on repeated save.
- Do not delete complaints with clinical history unless soft delete is already used.
- Preserve created_by.
- Preserve source_pattern_id.
- Do not overwrite complaints from other doctors unless authorized.
- Do not merge complaints with HOPC.
- Custom complaint should save complaint_text even if no catalogue item exists.

---

# 22. Tests Required

Add or update tests.

1. ComplaintCatalogueSeeder seeds complaints.
2. Seeder is idempotent.
3. Complaint search returns active complaints.
4. Complaint search finds by name.
5. Doctor can add complaint from catalogue.
6. Doctor can add custom complaint.
7. Complaint saves under visit/patient/medical record/session.
8. Complaint stores created_by.
9. Complaint can include duration and severity.
10. Complaint appears before HOPC in consultation page data.
11. Emergency chief complaint can become patient complaint.
12. Complaint appears in Consultation Summary.
13. Complaint appears in Visit Preview.
14. Complaint appears in Claims Mirror.
15. Pattern can store complaint item.
16. Applying pattern creates complaint under current doctor.
17. Unauthorized user cannot edit another doctor’s complaint.
18. Catalogue management requires permission.

---

# 23. Deliverables

Provide:

1. Gap analysis of current complaint implementation.
2. Complaint catalogue model/migration if needed.
3. Patient complaint model/migration if needed.
4. ComplaintCatalogueSeeder with common complaints.
5. Complaint search endpoint.
6. Complaint selector UI/component.
7. Consultation complaints section update.
8. Emergency complaint integration.
9. Medical pattern complaint support.
10. Consultation Summary update.
11. Visit Preview update.
12. Claims mirror update.
13. Permissions/seeders.
14. Tests or verification notes.
15. Files modified.
16. Remaining TODOs.

---

# 24. Important Rules

Do not remove existing complaint data.

Do not create duplicate complaint systems if one already exists.

Do not merge complaints into HOPC.

Do not force doctors to only use seeded complaints.

Do not allow claims officers to edit original clinical complaints.

Do not hide emergency complaints from consultation/session history.

Do not break consultation, emergency, admission, medical patterns, visit preview, or claims workflows.

Now inspect the current UHMS implementation and build/seed the Complaints Catalogue and patient complaint recording workflow as described.