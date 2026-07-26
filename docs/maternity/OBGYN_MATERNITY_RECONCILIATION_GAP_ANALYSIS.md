# O&G ↔ Maternity Reconciliation — Gap Analysis (Phase 14R.1)

**Status:** Audit only. No runtime behaviour was changed by this phase.
**Update (Phase 14R.2 approved & implemented):** the explicit-FK bridge design in §5 was **approved and built** — see `OBGYN_MATERNITY_BRIDGE_PHASE_14R_2_REPORT.md`. Open decisions R2/R4/R5/R6 have been resolved; see §6 below.
**Scope:** Reconcile the Consultation Specialty Engine (Obstetrics, Gynaecology) with the Maternity longitudinal domain.
**Related:** `OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md`, `OBGYN_MATERNITY_INTEGRATION_PLAN.md`, `docs/manual-testing/OBGYN_MATERNITY_RECONCILIATION_TEST_PLAN.md`

---

## 1. Executive summary

Three findings drive every recommendation in this phase.

**Finding 1 — There is no link between a consultation and a maternity record.**
Maternity tables carry `visit_id` and `admission_id`, but **no maternity table carries `consultation_route_id`**, and no consultation table carries `pregnancy_profile_id`. A grep for `consultation_maternity`, `clinical_context_link` and `consultation_clinical_context` across `database/migrations`, `app/Models` and `app/Services` returns nothing. There is therefore **no existing generic clinical-context link to reuse** — a bridge must be designed (§5).

**Finding 2 — Obstetrics duplicates the Maternity domain at field level, not merely at module level.**
The Obstetrics specialty profile owns nine structured sections whose fields are a near 1:1 restatement of `pregnancy_profiles` and `antenatal_visits`. This is a *write-capable* duplicate: `ConsultationSpecialtyEntryService::createEntry()` persists them into `consultation_specialty_entries.entry` (JSON) with no reference to any maternity record. Recording an ANC visit from the Obstetrics workspace today produces a consultation JSON blob, **not** an `AntenatalVisit`.

**Finding 3 — The duplication is structural but not yet materialised.**
A live count of `consultation_specialty_entries` in the nine maternity-owned section keys returns **0 rows** in this environment (`pregnancy_profiles` = 1, `antenatal_visits` = 0, patients with >1 active pregnancy profile = 0). The corrective backfill problem is currently empty here, which makes this a **low-risk window to act preventively**. This must be re-measured per environment before any migration — production may differ.

> **Consequence:** the reconciliation should prioritise *preventing* divergent writes (redirect the write path to Maternity services) over *repairing* existing divergence. A dry-run reconciliation command is still required for other environments.

---

## 2. What was audited

### 2.1 Consultation Specialty Engine

| Concern | Location |
|---|---|
| Profile resolution | `app/Services/Consultation/Specialty/ConsultationSpecialtyProfileResolver.php` |
| Layout / section ordering | `ConsultationSpecialtyLayoutService.php` |
| Field schemas (structured sections) | `ConsultationSpecialtySectionSchema.php` (364 lines) |
| Canonical aliases + presentation labels | `ConsultationSpecialtySectionAliasService.php` |
| Entry persistence | `ConsultationSpecialtyEntryService.php` |
| Summary generation | `ConsultationSpecialtySummaryBuilder.php`, `…SummarySourceCollector.php`, `…SummaryTemplateRegistry.php` |
| Readiness | `ConsultationSpecialtyReadinessService.php`, `…ReadinessRuleRegistry.php` |
| Quick actions / favourites / order sets | `ConsultationSpecialtyQuickActionRegistry.php`, `…FavoriteService.php`, `…OrderSetService.php` |
| Billing mapping | `ConsultationSpecialtyBillingMappingService.php`, `…BillingApplicationService.php` |
| Doctor workspace | `DoctorSpecialtyWorkspaceService.php` |
| Seeded profiles/sections | `database/seeders/ConsultationSpecialtySeeder.php` |

**Tables** (`database/migrations/2026_07_05_*`):
`consultation_specialty_profiles`, `consultation_specialty_sections`, `consultation_specialty_entries`,
`consultation_specialty_profile_mappings`, `consultation_specialty_favorites`,
`consultation_specialty_order_sets` (+ items/applications), `consultation_specialty_service_mappings`.

**Models:** `ConsultationSpecialtyProfile`, `ConsultationSpecialtySection`, `ConsultationSpecialtyEntry`,
`ConsultationSpecialtyProfileMapping`, `ConsultationSpecialtyFavorite`, `ConsultationSpecialtyOrderSet(+Item/Application/ApplicationItem)`,
`ConsultationSpecialtyServiceMapping`, `ConsultationSpecialtyBillingApplication`, `ConsultationSpecialtyTemplate`.

**Ownership of specialty consultation data:**
`consultation_specialty_entries` — keyed by `consultation_id` (a `VisitConsultationRoute` id), `consultation_specialty_profile_id`, `section_key`, with an untyped JSON `entry` column (`ConsultationSpecialtyEntry::$casts` → `'entry' => 'array'`). **There is no schema-level constraint on what goes into `entry`**, which is precisely why maternity-owned fields can be written there silently.

### 2.2 Maternity domain

**Models:** `PregnancyProfile`, `MaternityCase`, `AntenatalVisit`, `LaborEpisode`, `LaborObservation`, `DeliveryRecord`, `NewbornRecord`, `PostnatalCase`, `PostnatalMotherObservation`, `PostnatalNewbornObservation`, `MaternityServiceMapping`, `MaternityBillingEvent`.

**Services:** `PregnancyProfileService`, `MaternityCaseService`, `AntenatalVisitService`, `LaborEpisodeService`, `LaborObservationService`, `DeliveryRecordService`, `NewbornRecordService`, `PostnatalCaseService`, `PostnatalMotherObservationService`, plus overview/risk/report/billing services.

**Key structural fact:** every maternity record roots to `pregnancy_profile_id`, and most also carry `maternity_case_id`, `patient_id`, `visit_id`, `admission_id`, `department_id`. **None carry `consultation_route_id`.**

### 2.3 Billing posture (verified, unchanged)

- `config('billing.maternity_billing.enabled', false)` — **default false**.
- `MaternityBillingPostingService::postForSource()` returns `STATUS_POSTING_NOT_IMPLEMENTED`.
- Conclusion: **Phase 14.2 posting is genuinely not implemented; the system is preview-only.** This phase does not change it.

### 2.4 Permissions

67 `maternity.*` permissions exist and are granular (`maternity.anc.record`, `maternity.labor.start`, `maternity.pregnancy.create`, …). 37 `consultation.*` permissions exist. **Zero `consultation.maternity_context.*` permissions exist** — bridge permissions are additive and net-new (§18 of the integration plan).

### 2.5 Localisation

`lang/en` and `lang/fr` both contain `consultation_specialties.php`, `maternity.php`, `maternity_phase13.php`, `maternity_phase14.php` — parity holds at file level. New bridge keys must be added to both.

---

## 3. Field-level duplication discovered

Full detail in the source-of-truth matrix. Summary of the collisions:

### 3.1 Obstetrics profile sections (seeded)

`patient_summary, complaints, obstetric_history, current_pregnancy, lmp_edd_gestational_age, antenatal_vitals, fetal_assessment, risk_assessment, investigations, diagnosis, prescription, birth_plan, follow_up, summary, completion_readiness`
Legacy (hidden, data preserved): `ultrasound_findings`, `lab_screening`.

### 3.2 Direct collisions

| Consultation section.field | Maternity owner | Severity |
|---|---|---|
| `obstetric_history.gravida / para / abortions / living_children` | `pregnancy_profiles.gravida / para / abortions / living_children` | **Competing copy** |
| `obstetric_history.previous_c_section` | `pregnancy_profiles.previous_caesarean` | **Competing copy** |
| `lmp_edd_gestational_age.lmp / edd` | `pregnancy_profiles.last_menstrual_period / estimated_due_date` | **Competing copy** |
| `lmp_edd_gestational_age.gestational_age_weeks / _days` | `pregnancy_profiles.*` **and** `antenatal_visits.*` | **Three-way** |
| `antenatal_vitals.blood_pressure` | `antenatal_visits.blood_pressure_systolic/_diastolic` | Competing + lossy (string vs. split ints) |
| `antenatal_vitals.weight / temperature / pulse` | `antenatal_visits.weight_kg / temperature / pulse` | Competing copy |
| `antenatal_vitals.urine_protein / urine_glucose` | `antenatal_visits.urine_protein / urine_glucose` | Competing copy |
| `fetal_assessment.fundal_height` | `antenatal_visits.fundal_height_cm` | Competing + lossy (string vs. decimal) |
| `fetal_assessment.fetal_heart_rate / fetal_movement / presentation` | `antenatal_visits.*` | Competing copy |
| `risk_assessment.risk_factors` | `antenatal_visits.risk_flags`, `pregnancy_profiles.known_risks` | Competing copy |
| `current_pregnancy.danger_signs` | `antenatal_visits.danger_signs` | Competing copy |
| `lab_screening.hb` | `antenatal_visits.haemoglobin` | Competing copy (legacy section) |
| `lab_screening.blood_group / rhesus` | `pregnancy_profiles.blood_group / rhesus_status` | Competing copy (legacy section) |
| `birth_plan.next_visit_date` | `antenatal_visits.next_visit_date` | Competing copy |
| `birth_plan.delivery_plan / planned_place` | `labor_episodes.delivery_mode_planned` (partial) | Ambiguous |

### 3.3 Cross-specialty collision (Gynaecology)

**`obstetric_history` is seeded into the Gynaecology profile as well as Obstetrics.**
So gravida/para/abortions/living_children/previous_c_section exist in **three** places: the Obstetrics workspace, the Gynaecology workspace, and `pregnancy_profiles`.

This is defensible clinically (a gynaecology consultation legitimately takes an obstetric history) but is a genuine source-of-truth hazard. Recommendation in the matrix: keep the section visible in Gynaecology but make it a **read-only projection when a pregnancy profile is linked**, and a consultation-owned historical note when not.

### 3.4 Adjacent collision (Paediatrics)

`birth_history.delivery_mode / gestational_age_birth / birth_weight / neonatal_complications` overlaps `newborn_records.birth_weight_kg`, `delivery_records.delivery_mode`, and newborn APGAR/complication fields. Out of scope for this phase, but flagged: the same bridge should later serve a newborn→paediatrics link.

### 3.5 Not duplicated (safe)

Gynaecology's `menstrual_history`, `contraceptive_history`, `sexual_sti_history`, `pelvic_examination`, `breast_examination` have **no maternity counterpart** and are correctly consultation-owned. `menstrual_history.lmp` is a partial exception — see the matrix (it is a gynaecological LMP, not necessarily a pregnancy-dating LMP; the two must not be auto-synced).

---

## 4. Behavioural gaps

1. **ANC recorded from Obstetrics does not create an `AntenatalVisit`.** It writes `antenatal_vitals` / `fetal_assessment` specialty entries. Maternity reporting, ANC schedules, risk assessment and billing readiness never see it.
2. **No stage awareness.** The Obstetrics workspace cannot show gestational age, EDD, active labor episode, delivery status or postnatal state, because it has no route to a pregnancy profile.
3. **Order sets can write maternity-shaped data.** `ConsultationSpecialtyOrderSetSeeder` contains `patch_specialty_entry` actions targeting `current_pregnancy.pregnancy_confirmed` and `birth_plan.danger_signs_counseling` — an automation path that deepens duplication.
4. **No context disambiguation.** With multiple active pregnancy profiles there is no selection UI and no rule; the resolver does not exist.
5. **Summary divergence.** `ConsultationSpecialtySummaryBuilder` summarises specialty entries only; a linked pregnancy/ANC/labor record contributes nothing to the consultation summary.
6. **Billing identity risk (latent).** Both `ConsultationSpecialtyServiceMapping` and `MaternityServiceMapping` exist. With posting disabled this is inert, but enabling Phase 14.2 without a de-duplication policy risks two charges for one clinical act (§14 of the plan).

---

## 5. Bridge: explicit FKs vs. polymorphic

**Recommendation: explicit nullable foreign keys, one row per link, with a `context_type` discriminator retained for query ergonomics.**

Rationale specific to this codebase:

- Maternity tables already model relationships with **explicit constrained FKs** (`pregnancy_profile_id`, `maternity_case_id`, `labor_episode_id`, `delivery_record_id`). A polymorphic `context_id` would be the only untyped relationship in the domain and would lose `cascadeOnDelete`/`nullOnDelete` integrity that the rest of maternity relies on.
- The candidate targets are a **closed, known set of seven** (pregnancy profile, maternity case, ANC visit, labor episode, delivery record, newborn record, postnatal case). Polymorphism buys nothing for a closed set.
- Reporting will need joins across these; explicit FKs keep that indexable and analysable.
- The project has an existing precedent for a nullable-FK link row (`visit_department_history`-style linking rows).

**Proposed table** (name: `consultation_maternity_links` — domain-specific, honest about scope; a generic `consultation_clinical_context_links` is deferred until a second domain needs it):

| Column | Notes |
|---|---|
| `id` | |
| `consultation_route_id` | FK → `visit_consultation_routes`. **This is the project's real consultation encounter key** (`ConsultationSpecialtyEntry.consultation_id` uses it). |
| `pregnancy_profile_id` | FK, nullable. Longitudinal root where applicable. |
| `maternity_case_id`, `antenatal_visit_id`, `labor_episode_id`, `delivery_record_id`, `newborn_record_id`, `postnatal_case_id` | FK, nullable |
| `context_type` | enum-ish string: `pregnancy_profile\|maternity_case\|anc_visit\|labor\|delivery\|newborn\|postnatal` |
| `link_role` | `primary\|reviewed\|created\|handoff\|historical` |
| `linked_by`, `linked_at` | |
| `unlinked_by`, `unlinked_at`, `reason` | soft-unlink preserves history |
| `metadata` | json, nullable |
| timestamps | |

Constraints: partial-unique on (`consultation_route_id`, `context_type`) where `unlinked_at is null` — one active link per context type per session; history retained.

> Per the phase boundary ("do not implement a new table in this audit phase unless the choice is unambiguous and the change is very small"), **the table is specified here but not created.** It lands in Phase 14R.2.

---

## 6. Risks and unresolved decisions

| # | Risk / open question | Recommendation |
|---|---|---|
| R1 | Live data measured in **this** environment only (0 maternity-shaped specialty entries). Production may hold real duplicates. | Ship the dry-run reconciliation command in 14R.6 and run per environment **before** any write-path change is enabled there. |
| R2 | `menstrual_history.lmp` vs `pregnancy_profiles.last_menstrual_period` — same clinical concept, different intent. | **✅ RESOLVED (approved).** Gynaecology LMP remains Consultation-owned; **never auto-synced**. An explicit one-way "Use this LMP for pregnancy dating" action is approved for a later phase. `dating_method` becomes Pregnancy-Profile-owned in **14R.3**, not earlier. |
| R3 | Removing write capability from Obstetrics sections could be read as data loss by clinicians mid-consultation. | Convert to read-only projection **with** an explicit "Record ANC Visit" action in the same panel; never silently drop a field. (Applies in 14R.3.) |
| R4 | `obstetric_history` in Gynaecology — is it history-taking or pregnancy truth? | **✅ RESOLVED (approved).** Remains **editable encounter history when no pregnancy profile is linked**; when a profile **is** linked, gravida/para/abortions/living children/previous caesarean become **read-only Maternity projections**. Existing consultation entries are preserved. |
| R5 | Order-set `patch_specialty_entry` actions targeting maternity-shaped sections. | ✅ **IMPLEMENTED in 14R.4.** Audit confirmed exactly two seeded items; both retargeted idempotently; admin-modified items untouched; guard moved to the service boundary so the bypass is closed. |
| R6 | Whether the consultation summary needs a **frozen** completion-time maternity snapshot for medico-legal purposes. | **✅ RESOLVED (approved).** An **immutable versioned** completion-time Maternity Context snapshot is approved, **deferred to 14R.6**. 14R.2 must not implement summary snapshots. |
| R7 | Enabling Phase 14.2 without a billing policy matrix. | Blocked by design: keep `billing.maternity_billing.enabled=false` until 14R.6 policy is approved. |

---

## 7. Checks run in this phase

Per the phase testing boundary (no full suite, no `composer test:wide`):

- Read-only code inspection (grep/read) across specialty + maternity modules.
- Read-only data counts via `tinker` (no writes, no migrations).
- `git diff --check -- . ':!docs/prompt.md'` — see final report.
- No PHP source files were modified, so no syntax checks were applicable beyond the new Markdown deliverables.

**No runtime changes were made in this phase.**

---

## Phase 14R.6 — historical reconciliation dry run

`php artisan maternity:reconcile-obgyn-entries` is **read-only** and classifies every in-scope historical O&G specialty entry.

| Classification | Meaning | Recommended future action |
|---|---|---|
| `safe_to_link` | A correct Maternity record already exists for the same patient and compatible pregnancy, and the values match after safe normalisation | create a bridge link only; preserve the entry |
| `safe_to_migrate` | No target exists, but patient, pregnancy and required values are identifiable and parse safely | create the target through the Maternity service, then link; preserve the entry |
| `conflict_requires_review` | Values disagree (gravida/para, LMP/EDD, material GA difference) or a value is unparseable | a human decides; nothing is chosen automatically |
| `historical_only` | Completed consultation with no identifiable active profile | leave as encounter history |
| `insufficient_context` | Patient/pregnancy/date cannot be identified, or the entry is empty | leave untouched; report |

**In scope:** Obstetrics `obstetric_history`, `lmp_edd_gestational_age`, `current_pregnancy`, `antenatal_vitals`, `fetal_assessment`, `risk_assessment`, `birth_plan`, plus hidden legacy `lab_screening` / `ultrasound_findings`; Gynaecology `obstetric_history` only.

**Out of scope (Consultation-owned):** `menstrual_history` (including `menstrual_history.lmp`), `previous_complications`, fetal lie, `action_plan`, `current_complaints`, `high_risk_notes`, `booking_status`, `planned_place`, `delivery_plan`, and all ordinary Gynaecology sections.

**Parser rules.** `120/80` and `120 / 80` parse; free text does not. `32`, `32 cm`, `32.5 cm` parse; `32 weeks size` does not. Locale-ambiguous dates are refused rather than reinterpreted, and a missing year is never inferred. **A scan-dated profile is never overridden by an LMP-derived gestational age.**

**Measured in this environment: 0 rows in every classification.** Re-run and review per environment before enabling any O&G write guard there.

`--apply` is unavailable in Phase 14R.6: it exits non-zero and performs zero writes.

---

## Phase 14R.7 — environment review vs synthetic validation

These two things are **never** reported as one number.

### A. Real environment reconciliation

Captured before any pilot seeding, to
`storage/app/manual-testing/obgyn-maternity/environment-reconciliation-14R7.json`.

| Classification | Count |
|---|---|
| safe_to_link | 0 |
| safe_to_migrate | 0 |
| conflict_requires_review | 0 |
| historical_only | 0 |
| insufficient_context | 0 |
| **Total inspected** | **0** |

Mode `dry_run`, writes performed `0`, no patient names in the artefact, `--apply` not run.

**This environment holds no historical O&G specialty entries.** The result proves the command
executes; it does not validate classification against real data at scale. Each environment must
run and review its own report **before** any write guard is enabled there.

### B. Synthetic classifier validation

> **SYNTHETIC PILOT DATA — NOT ENVIRONMENT RECONCILIATION COUNTS.**

Isolated `MT-OBGYN-14R7-` pilot records `R1`–`R5` produce **all five classifications**, at least one
each, deterministically across repeated runs. The command still performs zero writes and `--apply`
still exits non-zero.

Synthetic counts must never be added to, or substituted for, the counts in section A.
