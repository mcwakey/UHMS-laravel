# Consultation Workspace Section De-duplication Audit

Phase 16.5 — Consultation Workspace UX De-duplication and Billing Card Removal.

This audit classifies every seeded specialty section for all 11 personalised
consultation workspace profiles, and records the mapping decision applied by
`ConsultationSpecialtySectionAliasService` and `ConsultationSpecialtySeeder`.

## Classification vocabulary

| Classification | Meaning |
| --- | --- |
| `shared_core` | Shared clinical function; rendered by the shared consultation panes (complaints, diagnosis, investigations, prescription, procedures, tasks, notes, summary, readiness). |
| `specialty_structured` | Clinically unique structured capture. Kept as its own structured section. |
| `duplicate_of_shared_core` | Duplicates a shared core function under a specialty name. Hidden from the doctor workspace; aliased to the canonical shared section; legacy saved entries preserved and surfaced through summary/readiness. |
| `hybrid_review` | Mixes ordering with narrative review of external results. Hidden from the main layout; data preserved and shown under the canonical Investigations heading in the summary. |
| `deprecated_hidden` | Kept in the database for compatibility, no longer part of the doctor-facing layout. All `duplicate_of_shared_core` and `hybrid_review` rows end up in this state (`is_visible = false`). |

## Decision rules applied

1. If a section means "request/order this test or procedure" → use the shared
   `investigations`/`procedures` section, with specialty favorites/order sets
   supplying suggestions.
2. If a section means "doctor reviewed an external result and typed findings"
   → data stays stored under the legacy key and is surfaced in the summary
   under the canonical heading (`Investigations`), merged with core data.
3. If uncertain → keep the data stored, hide the section from the main layout,
   expose it through summary/history only.
4. No DB rows are deleted. Legacy section rows are kept with
   `is_visible = false`; legacy `consultation_specialty_entries` rows remain
   untouched and readable.

## Canonical alias map

| Legacy section key | Profile(s) | Classification | Canonical section |
| --- | --- | --- | --- |
| `lab_screening` | obstetrics | hybrid_review (result values: Hb, blood group, HIV…) | `investigations` |
| `ultrasound_findings` | obstetrics | hybrid_review (order flag + findings) | `investigations` |
| `urgent_investigations` | emergency | duplicate_of_shared_core | `investigations` |
| `imaging` | orthopedics | duplicate_of_shared_core (order flags + findings) | `investigations` |
| `dental_xray` | dental | duplicate_of_shared_core (order flag + findings) | `investigations` |
| `urgent_procedures` | emergency | duplicate_of_shared_core | `procedures` |
| `dental_procedures` | dental | duplicate_of_shared_core | `procedures` |
| `procedure_plan` | orthopedics, surgery | duplicate_of_shared_core (planned/done narrative) | `procedures` |
| `dental_diagnosis` | dental | duplicate_of_shared_core | `diagnosis` |
| `medications_given` | emergency | duplicate_of_shared_core (administered drugs duplicate prescription and risk pharmacy/billing confusion) | `prescription` |

Notes on the judgement calls:

- `medications_given` captured administered emergency drugs as free text. It
  duplicates the shared prescription workflow and bypasses pharmacy. It is
  merged under the canonical Prescription/Treatment heading in the summary;
  structured resuscitation drugs belong in `emergency_interventions`
  (which stays).
- `procedure_plan` narrative (orthopedic/surgical plan) is preserved: legacy
  entries appear under the canonical Procedures summary heading, and the
  shared Procedures pane is now part of both layouts. A dedicated
  `orthopedic_plan`/`surgical_plan` rename was considered and rejected — the
  existing `cast_splint_plan` (orthopedics) and `theatre_referral` +
  `post_op_instructions` (surgery) already capture the specialty-specific
  plan; a further plan panel would re-introduce the duplication.
- `ultrasound_findings`/`lab_screening` hold reviewed result values, so their
  saved entries render in the summary under `Investigations` merged with core
  investigation orders (decision rule 2).

## Per-profile audit

### general_medicine

| Section | Classification |
| --- | --- |
| patient_summary, complaints, hopc, examination, diagnosis, investigations, prescription, procedures, tasks, notes, summary, completion_readiness | shared_core |

No changes. This is the canonical baseline.

### physiotherapy

| Section | Classification |
| --- | --- |
| patient_summary, tasks, summary, completion_readiness | shared_core |
| presenting_problem, pain_assessment, functional_limitation, physical_assessment, treatment_plan, therapy_session, home_exercise_plan, progress_notes | specialty_structured |

No duplicates. Treatment/therapy plans do not duplicate the shared
procedures/tasks flow (they capture rehab-specific structure); order sets and
favorites already push tasks and follow-up instructions into shared sections.

### ophthalmology

| Section | Classification |
| --- | --- |
| patient_summary, diagnosis, investigations, procedures, prescription, summary, completion_readiness | shared_core |
| eye_complaint (aliased to complaints), visual_acuity, refraction, iop, eye_examination, follow_up | specialty_structured |

No duplicates. Already normalized.

### dental

| Section | Classification | Decision |
| --- | --- | --- |
| patient_summary, prescription, summary, completion_readiness | shared_core | keep |
| dental_complaint (aliased to complaints), tooth_chart, oral_examination, consent, follow_up | specialty_structured | keep |
| dental_diagnosis | duplicate_of_shared_core | → `diagnosis`, hidden |
| dental_xray | duplicate_of_shared_core | → `investigations`, hidden |
| dental_procedures | duplicate_of_shared_core | → `procedures`, hidden |

Normalized layout adds the shared `diagnosis`, `investigations`, `procedures`
sections. Dental X-ray / OPG requests come from investigation favorites;
extraction/filling procedures from procedure favorites and order sets.

### obstetrics

| Section | Classification | Decision |
| --- | --- | --- |
| patient_summary, diagnosis, prescription, summary, completion_readiness | shared_core | keep |
| obstetric_history, current_pregnancy, lmp_edd_gestational_age, antenatal_vitals, fetal_assessment, risk_assessment, birth_plan, follow_up | specialty_structured | keep |
| ultrasound_findings | hybrid_review | → `investigations`, hidden |
| lab_screening | hybrid_review | → `investigations`, hidden |

Normalized layout adds the shared `investigations` section. Antenatal
screening orders (FBC, blood group/rhesus, urinalysis, HIV, HBsAg, syphilis,
malaria, obstetric ultrasound) are seeded as investigation favorites.
`birth_plan` stays: it captures delivery planning, not follow-up scheduling,
and must not replace `follow_up`.

### gynecology

| Section | Classification |
| --- | --- |
| patient_summary, diagnosis, investigations, procedures, prescription, summary, completion_readiness | shared_core |
| gyne_complaint, menstrual_history, obstetric_history, contraceptive_history, sexual_sti_history, pelvic_examination, breast_examination, follow_up | specialty_structured |

No duplicates. Already normalized.

### ent

| Section | Classification |
| --- | --- |
| patient_summary, diagnosis, investigations, procedures, prescription, summary, completion_readiness | shared_core |
| ent_complaint, ear_assessment, nose_assessment, throat_assessment, hearing_balance_assessment, neck_assessment, follow_up | specialty_structured |

No duplicates. Audiometry/ear swab/sinus imaging arrive as investigation
favorites, not sections.

### pediatrics

| Section | Classification |
| --- | --- |
| patient_summary, diagnosis, investigations, prescription, summary, completion_readiness | shared_core |
| pediatric_complaint, birth_history, feeding_history, growth_assessment, immunization_status, developmental_assessment, pediatric_examination, caregiver_instructions, follow_up | specialty_structured |

No duplicates. `caregiver_instructions` captures counselling given to the
caregiver; it complements, and must not replace, `follow_up` or the final
note.

### emergency

| Section | Classification | Decision |
| --- | --- | --- |
| patient_summary, diagnosis, summary, completion_readiness | shared_core | keep |
| triage_summary, emergency_complaint, primary_survey, vitals_monitoring, trauma_assessment, emergency_interventions, disposition, handover | specialty_structured | keep |
| urgent_investigations | duplicate_of_shared_core | → `investigations`, hidden |
| urgent_procedures | duplicate_of_shared_core | → `procedures`, hidden |
| medications_given | duplicate_of_shared_core | → `prescription`, hidden |

Normalized layout adds the shared `investigations`, `procedures`, and
`prescription` sections. Urgency is expressed on the shared investigation
request (urgency field) and via emergency favorites (urgent FBC, malaria,
blood glucose, ECG, X-ray), not via parallel panels. Administered
resuscitation drugs/fluids remain part of `emergency_interventions`.

### orthopedics

| Section | Classification | Decision |
| --- | --- | --- |
| patient_summary, diagnosis, prescription, summary, completion_readiness | shared_core | keep |
| ortho_complaint, injury_history, pain_mobility_assessment, joint_limb_examination, neurovascular_status, cast_splint_plan, follow_up | specialty_structured | keep |
| imaging | duplicate_of_shared_core | → `investigations`, hidden |
| procedure_plan | duplicate_of_shared_core | → `procedures`, hidden |

Normalized layout adds the shared `investigations` and `procedures`
sections. X-ray/CT/MRI requests come from investigation favorites;
cast application/splinting from procedure favorites. `cast_splint_plan`
remains the specialty plan panel.

### surgery

| Section | Classification | Decision |
| --- | --- | --- |
| patient_summary, diagnosis, investigations, prescription*, summary, completion_readiness | shared_core | keep |
| surgical_complaint, surgical_history, wound_assessment, local_or_abdominal_exam, consent, theatre_referral, post_op_instructions, follow_up | specialty_structured | keep |
| procedure_plan | duplicate_of_shared_core | → `procedures`, hidden |

Normalized layout adds the shared `procedures` section (and `prescription`,
which the phase target layout lists via the shared prescription pane).
`consent`, `theatre_referral`, and `post_op_instructions` stay as structured
surgical capture; `post_op_instructions` must not replace `follow_up`.

## Readiness impact

| Rule | Profile | Before | After |
| --- | --- | --- | --- |
| `procedure_plan_recorded` (blocking) | orthopedics, surgery | Only satisfied by a `procedure_plan` entry — impossible once hidden | Satisfied by a legacy `procedure_plan` entry OR core treatments OR a procedure request OR session notes; anchored to the shared Procedures section |
| `dental_diagnosis_recorded` (blocking) | dental | dental_diagnosis entry OR core diagnoses | unchanged logic; anchored to the shared Diagnosis section |
| `procedure_or_plan_recorded` (blocking) | dental | dental_procedures entry OR treatments OR procedure request OR notes | unchanged logic; anchored to the shared Procedures section |
| `xray_missing_if_extraction_planned` (warning) | dental | dental_xray entry only | ALSO satisfied by core investigation orders; extraction detection also scans core treatments; anchored to the shared Investigations section |

No readiness rules referenced `lab_screening`, `ultrasound_findings`,
`urgent_investigations`, `urgent_procedures`, `medications_given`, or
`imaging`, so hiding them creates no blockers.

## Summary builder impact

Duplicate headings are replaced by canonical headings that merge legacy saved
entries with core data:

| Profile | Before | After |
| --- | --- | --- |
| obstetrics | `Lab screening` (entry only) | `Investigations` = legacy lab_screening + ultrasound_findings entries + core investigation orders |
| emergency | `Medications given` (entry only) | `Investigations`, `Procedures`, `Treatment / prescription` canonical headings merging legacy urgent_investigations / urgent_procedures / medications_given entries with core data |
| orthopedics | `Imaging`, `Procedure plan` (entries only) | `Diagnosis`, `Investigations`, `Procedures` canonical headings merging legacy entries with core data |
| surgery | `Procedure plan` (entry only) | `Diagnosis`, `Investigations`, `Procedures` canonical headings merging the legacy entry with core data |
| dental | `Dental diagnosis`, `X-ray / investigation`, `Procedure plan / performed procedure` | `Diagnosis`, `Investigations`, `Procedures` canonical headings (same merge formatters, canonical labels) |

## Quick action impact

| Profile | Before | After |
| --- | --- | --- |
| dental | `dental_diagnosis` → `#dental_diagnosis-section`, `dental_procedure` → `#dental_procedures-section` | `diagnosis` → `#diagnoses-section`, `procedures` → `#procedures-section` |
| surgery | `procedure_plan` → `#procedure_plan-section` | `procedures` → `#procedures-section` |

All other profiles' quick actions already target visible sections.

## Favorites / order sets

- Favorites carry no section keys; they already surface inside the shared
  panes ("Specialty suggestions"). Seeded investigation favorites were
  extended (ENT: ear swab, sinus X-ray, CT sinuses; emergency: blood glucose,
  ECG; orthopedics: CT scan, MRI; dental: OPG) so each specialty's common
  orders are one click away inside the shared Investigations pane.
- No seeded order-set item targets a duplicate section key (verified across
  `ConsultationSpecialtyOrderSetSeeder`). Admin-created items that target
  legacy keys keep validating and keep writing to the legacy entry, which the
  summary/readiness alias layer continues to honor.

## Billing card removal (related UX noise)

The doctor consultation workspace no longer renders the billing/service
mapping card (mapped service, billed badge, preview/apply actions, mapping
warnings), and `specialtyBillingContext` is no longer computed for or exposed
to the doctor page. Billing mapping resolution, apply/preview endpoints,
admin service-mapping configuration, reporting/dashboard billing health,
CSV export, and audit logging are unchanged.
