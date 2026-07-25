# O&G ↔ Maternity — Source-of-Truth Matrix (field level)

**Status:** **Approved.** Ownership decisions below are ratified; field-conversion work lands in 14R.3/14R.4.
**Companion:** `OBGYN_MATERNITY_RECONCILIATION_GAP_ANALYSIS.md`, `OBGYN_MATERNITY_BRIDGE_PHASE_14R_2_REPORT.md`

## Approved decisions (ratified before Phase 14R.2)

| Ref | Decision |
|---|---|
| **14R.3** | `dating_method` is now Pregnancy-Profile-owned (column added, enum `PregnancyDatingMethod`). The Obstetrics field-level write guard is **implemented and enforced server-side**, gated behind two default-off flags. |
| **R2** | Gynaecology `menstrual_history.lmp` stays **Consultation-owned** and is **never auto-synced** to `pregnancy_profiles.last_menstrual_period`. An explicit **one-way** "Use this LMP for pregnancy dating" action is approved for a later phase. `dating_method` becomes **Pregnancy-Profile-owned in 14R.3**. |
| **R4** | Gynaecology `obstetric_history` is **RW (editable encounter history) when no pregnancy profile is linked**, and a **read-only Maternity projection when a profile is linked**. Existing consultation entries are preserved. |
| **R5** | Order-set automatic specialty-entry patches are **retargeted in 14R.4**. |
| **R6** | An **immutable, versioned completion-time Maternity Context snapshot** is approved, **deferred to 14R.6**. |
| **Bridge** | The **explicit-foreign-key** bridge design (not polymorphic) is **approved and implemented** in 14R.2. |

---

## 0. How to read this

**Classification**

| Code | Meaning |
|---|---|
| `C` | Consultation-owned |
| `PP` | Pregnancy-profile-owned |
| `ANC` | ANC-visit-owned |
| `LAB` | Labor-owned |
| `DEL` | Delivery-owned |
| `NB` | Newborn-owned |
| `PN` | Postnatal-owned |
| `ADM` | Admission-owned |
| `ORD` | Shared order/workflow-owned |
| `?` | Ambiguous — decision required |

**Consultation read/write behaviour (target)**

| Code | Meaning |
|---|---|
| `RW` | Consultation reads and writes (true consultation data) |
| `RO-proj` | Read-only projection of the maternity record |
| `ACT` | Not a stored consultation field; an **action** that calls a maternity service |
| `RW-unlinked` | Consultation-owned only while no maternity context is linked; becomes `RO-proj` once linked |

**Default ownership direction** (per phase brief): Consultation owns the encounter (complaints, HOPC, examination narrative, assessment, diagnoses, plan, orders, prescriptions, procedures, notes, final summary). Maternity owns the longitudinal record (pregnancy profile, ANC, labor, delivery, newborn, postnatal). Admission owns ward/bed/nursing/discharge. Existing order systems own investigations, radiology, theatre, prescriptions, pharmacy, tasks.

---

## 1. Obstetrics — `obstetric_history`

Also seeded into **Gynaecology** (see §5).

| Field | Current owner | Target owner | Consultation R/W | Maternity R/W | Historical treatment | Summary | Reporting | Billing |
|---|---|---|---|---|---|---|---|---|
| `gravida` | Both (competing) | **PP** | `RO-proj` (`RW-unlinked`) | RW via `PregnancyProfileService` | Preserve entry; link, do not overwrite | From PP | Maternity | none |
| `para` | Both (competing) | **PP** | `RO-proj` (`RW-unlinked`) | RW | Preserve; link | From PP | Maternity | none |
| `abortions` | Both (competing) | **PP** | `RO-proj` (`RW-unlinked`) | RW | Preserve; link | From PP | Maternity | none |
| `living_children` | Both (competing) | **PP** | `RO-proj` (`RW-unlinked`) | RW | Preserve; link | From PP | Maternity | none |
| `previous_c_section` | Both (competing; PP calls it `previous_caesarean`) | **PP** | `RO-proj` (`RW-unlinked`) | RW | Preserve; **name mismatch — map explicitly** | From PP | Maternity risk | none |
| `previous_complications` | Consultation | **C** | `RW` | — | Keep | Consultation | Consultation | none |

> `previous_complications` is free narrative with no PP counterpart (`previous_postpartum_haemorrhage` is a distinct boolean on PP). It stays consultation-owned.

---

## 2. Obstetrics — pregnancy dating and status

### 2.1 `lmp_edd_gestational_age`

| Field | Current owner | Target owner | Consultation R/W | Maternity R/W | Historical | Summary | Reporting | Billing |
|---|---|---|---|---|---|---|---|---|
| `lmp` | Both | **PP** (`last_menstrual_period`) | `RO-proj` | RW | Preserve; explicit clinician confirm to adopt | From PP | Maternity | none |
| `edd` | Both | **PP** (`estimated_due_date`) | `RO-proj` | RW | Preserve; explicit confirm | From PP | Maternity | none |
| `gestational_age_weeks` | **Three-way** (C, PP, ANC) | **PP** baseline, **ANC** per-visit | `RO-proj` (derived) | RW | Preserve | Derived, labelled with source | Maternity | none |
| `gestational_age_days` | **Three-way** | **PP** / **ANC** | `RO-proj` | RW | Preserve | Derived | Maternity | none |
| `dating_method` | Consultation | **PP** *(approved, R2)* | `RO-proj` once adopted | RW | Converts in **14R.3**, not before | From PP | Maternity | none |

> GA is *computed* clinically (from LMP or scan). Consultation must never hold an independent GA. Show PP baseline + latest ANC GA, each labelled.

### 2.2 `current_pregnancy`

| Field | Current owner | Target owner | Consultation R/W | Maternity R/W | Historical | Summary | Reporting | Billing |
|---|---|---|---|---|---|---|---|---|
| `pregnancy_confirmed` | Consultation | **PP** (existence of profile) | `ACT` — "Create/Link pregnancy profile" | RW | Preserve; **never auto-create a profile** | Profile presence | Maternity | none |
| `booking_status` | Consultation | **PP** `?` | `RO-proj` if adopted | RW | Decision | From PP | ANC coverage | may drive ANC registration charge |
| `current_complaints` | Consultation | **C** | `RW` | — | Keep | Consultation | Consultation | consultation charge |
| `danger_signs` | Both (ANC has `danger_signs` json) | **ANC** when recorded in ANC context; **C** otherwise | `RW-unlinked` → `ACT` | RW | Preserve | Both, labelled | Maternity | none |
| `high_risk_notes` | Consultation | **C** narrative; risk *level* → PP | `RW` | — | Keep | Consultation | — | none |

> `pregnancy_confirmed = true` must **not** imply profile creation. Boundary: "Do not auto-create pregnancy profiles."

---

## 3. Obstetrics — ANC measurement sections

**These are the highest-severity duplicates.** Every field below is a per-visit clinical measurement that `AntenatalVisit` already owns. Target for the whole group: the consultation panel becomes an **`ACT`** — "Record ANC Visit" → `AntenatalVisitService` — plus a `RO-proj` of the latest visit. **No ANC measurement should be stored as a specialty entry.**

### 3.1 `antenatal_vitals`

| Field | Current owner | Target owner | Consultation R/W | Notes |
|---|---|---|---|---|
| `blood_pressure` (string) | Both | **ANC** | `ACT` + `RO-proj` | **Lossy shape mismatch:** ANC stores `blood_pressure_systolic` / `_diastolic` as integers. Migration must parse `"120/80"`; unparseable values → conflict queue. |
| `weight` | Both | **ANC** (`weight_kg`) | `ACT` + `RO-proj` | unit assumed kg both sides — confirm |
| `temperature` | Both | **ANC** | `ACT` + `RO-proj` | |
| `pulse` | Both | **ANC** | `ACT` + `RO-proj` | |
| `urine_protein` | Both | **ANC** | `ACT` + `RO-proj` | |
| `urine_glucose` | Both | **ANC** | `ACT` + `RO-proj` | |

### 3.2 `fetal_assessment`

| Field | Current owner | Target owner | Consultation R/W | Notes |
|---|---|---|---|---|
| `fundal_height` (string) | Both | **ANC** (`fundal_height_cm` decimal) | `ACT` + `RO-proj` | **Lossy:** string → decimal; "32cm"/"32 weeks size" must be parsed or queued |
| `fetal_heart_rate` | Both | **ANC** | `ACT` + `RO-proj` | Also present on `labor_observations` for labor context |
| `fetal_movement` | Both | **ANC** | `ACT` + `RO-proj` | |
| `presentation` | Both | **ANC**; **LAB** in labor | `ACT` + `RO-proj` | Context decides which record; `labor_episodes.presentation` also exists |
| `lie` | Consultation | **ANC** `?` | `RO-proj` if adopted | No ANC column today — **decision:** extend ANC or keep consultation-owned |

### 3.3 `risk_assessment`

| Field | Current owner | Target owner | Consultation R/W | Notes |
|---|---|---|---|---|
| `risk_level` | Both | **PP** (`risk` via `markHighRisk`) | `RO-proj` + `ACT` | Use `PregnancyProfileService::markHighRisk()`; do not store a second level |
| `risk_factors` | Both (PP `known_risks`, ANC `risk_flags`) | **PP** baseline, **ANC** per-visit | `RO-proj` + `ACT` | |
| `action_plan` | Consultation | **C** | `RW` | Encounter plan is consultation-owned |

### 3.4 `birth_plan`

| Field | Current owner | Target owner | Consultation R/W | Notes |
|---|---|---|---|---|
| `planned_place` | Consultation | **PP** `?` | `RW-unlinked` | Longitudinal intent — decision |
| `delivery_plan` | Consultation | **PP** `?` / **LAB** (`delivery_mode_planned`) | `RW-unlinked` | Ambiguous; overlaps labor planning |
| `danger_signs_counseling` | Consultation | **ANC** (`counselling`) | `ACT` | Order-set currently patches this — retarget (R5) |
| `next_visit_date` | Both | **ANC** (`next_visit_date`) | `RO-proj` + `ACT` | ANC drives ANC scheduling/reporting |

### 3.5 Legacy sections (hidden, data preserved)

| Section.field | Current owner | Target owner | Consultation R/W | Notes |
|---|---|---|---|---|
| `lab_screening.hb` | Both | **ANC** (`haemoglobin`) | `RO-proj` | Legacy; aliased → `investigations` |
| `lab_screening.blood_group` | Both | **PP** (`blood_group`) | `RO-proj` | |
| `lab_screening.rhesus` | Both | **PP** (`rhesus_status`) | `RO-proj` | |
| `lab_screening.hiv_status / hepatitis_b / syphilis / urinalysis` | Consultation | **ORD** (lab results) | `RO-proj` from lab | Should read from investigation results, not be typed |
| `ultrasound_findings.*` | Consultation | **ORD** (radiology result) | `RO-proj` from lab/radiology | Aliased → `investigations`; keep read-only |

> Legacy sections are already hidden by `ConsultationSpecialtySectionAliasService`. **Do not delete them** (phase boundary); their rows remain for audit.

---

## 4. Labor / Delivery / Newborn / Postnatal

No consultation section currently writes these. Target: **projection + navigation only**.

| Concept | Owner | Consultation behaviour |
|---|---|---|
| Labor stage, membranes, liquor, presentation, contractions | **LAB** (`labor_episodes`, `labor_observations`) | `RO-proj` card + "Open Labor Workspace" / "Start Labor Episode" (`ACT` → `LaborEpisodeService`) |
| Cervical dilation, FHR in labor, descent, maternal vitals in labor | **LAB** (`labor_observations`) | `RO-proj` (latest observation); record via `LaborObservationService` |
| Delivery mode, outcome, placenta, blood loss, maternal condition, complications | **DEL** (`delivery_records`) | `RO-proj` + navigate; **never duplicate delivery fields** |
| APGAR 1/5/10, birth weight, length, head circumference, sex, birth time, resuscitation | **NB** (`newborn_records`) | `RO-proj` + navigate; **never duplicate APGAR/birth weight** |
| Postnatal mother/newborn observations, breastfeeding, discharge readiness | **PN** (`postnatal_cases`, `postnatal_*_observations`) | `RO-proj` + navigate |
| Ward, bed, nursing workflow, discharge summary | **ADM** | `RO-proj` from admission |

**Paediatrics adjacency (flagged, out of scope):** `birth_history.delivery_mode / gestational_age_birth / birth_weight / neonatal_complications` overlaps `DEL`/`NB`. Same bridge should later support a newborn→paediatrics link.

---

## 5. Gynaecology

Gynaecology must remain a **non-pregnancy** reproductive-health workspace.

### 5.1 Correctly consultation-owned — no change

| Section | Fields | Owner |
|---|---|---|
| `gyne_complaint` | `complaint_text`, `duration`, `associated_symptoms` | **C** (aliased → `complaints`) |
| `menstrual_history` | `cycle_length`, `cycle_regularity`, `bleeding_pattern`, `dysmenorrhea`, `menopause_status` | **C** |
| `contraceptive_history` | `current_method`, `past_methods`, `side_effects`, `family_planning_goal` | **C** |
| `sexual_sti_history` | `sti_symptoms`, `discharge`, `pelvic_pain`, `pregnancy_test`, `screening_notes` | **C** |
| `pelvic_examination` | `external_findings`, `speculum_findings`, `bimanual_findings`, `cervix`, `uterus`, `adnexa`, `exam_notes` | **C** |
| `breast_examination` | `breast_symptoms`, `inspection`, `palpation`, `lumps`, `nipple_discharge`, `axillary_nodes` | **C** |

### 5.2 Special cases

| Field | Issue | Decision |
|---|---|---|
| `menstrual_history.lmp` | Same concept as `pregnancy_profiles.last_menstrual_period`, different intent (gynaecological cycle vs. pregnancy dating) | **✅ Approved (R2):** stays `C`, **never auto-synced**. Explicit one-way "use this LMP to date pregnancy" offered only when a profile is being created/linked. |
| `sexual_sti_history.pregnancy_test` | A positive result must **not** auto-create a profile or switch specialty | Stays `C`. May *surface* the "Create/Link pregnancy profile" action; clinician acts explicitly. **Enforced and tested in 14R.2.** |
| `obstetric_history.*` (seeded into Gynaecology) | Three-way duplication | **✅ Approved (R4): `RW-unlinked` → `RO-proj` when a profile is linked.** Existing entries preserved. |

### 5.3 Gynaecology rules

- Do not display the maternity workflow by default.
- Show a **small** maternity context card only when a profile is explicitly linked, or when the clinician chooses to create/link one.
- Never auto-switch a Gynaecology consultation to Obstetrics.
- Provide an explicit "Start/Link Pregnancy Workflow" action.
- Preserve the original Gynaecology session and its history after any transition.

---

## 6. Consultation-owned, never migrated

Unchanged and explicitly protected: complaints, HOPC, examination narrative (general and specialty), clinical assessment, diagnoses, consultation plan, orders (investigations/prescriptions/procedures/tasks — owned by `ORD`), clinician notes, encounter-level final summary, readiness/completion state.

---

## 7. Historical-data treatment (summary)

| Class | Definition | Action |
|---|---|---|
| **safe to link** | Specialty entry matches an existing maternity record (same patient/visit/date, no value conflict) | Create bridge link; keep entry as audit |
| **safe to migrate** | No maternity record exists; values parse cleanly | Create maternity record via service (dry-run first); keep entry as audit |
| **conflict requiring review** | Both exist and values disagree (e.g. GA 28w vs 30w; unparseable `blood_pressure`) | Queue for clinician review. **Never auto-overwrite either side.** |
| **historical-only** | Consultation completed long ago; no active profile | Leave as-is; mark link role `historical` if later linked |
| **insufficient context** | Cannot identify patient/pregnancy/date reliably | Leave untouched; report |

Rules: preserve original specialty-entry values in all cases; dry-run before any backfill; produce a review report; **no automatic backfill in this phase or in 14R.2–14R.5**.
