# O&G ↔ Maternity — Integration Plan (Phases 14R.2 → 14R.7)

**Status:** Matrix **approved**. **Phase 14R.2 is implemented** (see `OBGYN_MATERNITY_BRIDGE_PHASE_14R_2_REPORT.md`); 14R.3 onward remain proposals.
**Approved decisions:** R2 (Gynae LMP stays Consultation-owned, never auto-synced; `dating_method` → PP in 14R.3) · R4 (Gynae obstetric history RW when unlinked, RO projection when linked) · R5 (order-set retargeting in 14R.4) · R6 (immutable versioned completion snapshot in 14R.6) · explicit-FK bridge approved.
**Companion:** `OBGYN_MATERNITY_RECONCILIATION_GAP_ANALYSIS.md`

---

## 1. The three independent contexts

The architecture must never conflate these. They are resolved separately and displayed separately.

### A. Department context — *where the clinician is working*
Resolved by the existing `DepartmentType` / workspace routing (`WorkspaceRouteResolver`, `department.type:*` middleware).
Values: consultation, maternity, ward/inpatient, emergency, other operational area.
**Independent of specialty.** A maternity-department nurse and an obstetrics-department doctor are in different department contexts.

### B. Specialty workspace context — *which consultation form is presented*
Resolved by the existing `ConsultationSpecialtyProfileResolver` (route mapping → doctor preference → user department → department mapping → department-type mapping → existing entries → fallback).
Values: obstetrics, gynaecology, another specialty.
**Independent of maternity stage.** An obstetrics consultation may have no pregnancy context at all.

### C. Maternity clinical context — *the longitudinal stage*
Resolved by the **new** `ConsultationMaternityContextResolver` (§3).
Values: none, pregnancy profile, ANC, labor, delivery, newborn, postnatal.
**Independent of both A and B.** A gynaecology consultation in a consultation department may legitimately carry a linked pregnancy profile.

**Display rule:** each context is shown with its own affordance — department in the workspace chrome, specialty in the workspace band/profile chip, maternity stage in the context ribbon (§4). Never render one as if it implied another.

---

## 2. Phase 14R.2 — Bridge foundation and context resolver ✅ IMPLEMENTED

> **Delivered.** Table, enums, model, DTO, resolver, link service, permissions, EN/FR localisation and 22 passing tests. Full detail in `OBGYN_MATERNITY_BRIDGE_PHASE_14R_2_REPORT.md`. No workspace/UI behaviour was changed.

**Goal:** Create the link table and a safe resolver. **No workspace behaviour changes yet.**

**Files likely to change**
- `database/migrations/xxxx_create_consultation_maternity_links_table.php` *(new)*
- `app/Models/ConsultationMaternityLink.php` *(new)*
- `app/Services/Consultation/Maternity/ConsultationMaternityContextResolver.php` *(new)*
- `app/Services/Consultation/Maternity/ConsultationMaternityLinkService.php` *(new)*
- `database/seeders/RoleSeeder.php` — additive permissions
- `lang/{en,fr}/consultation_maternity.php` *(new)*

**Table:** as specified in the gap analysis §5 (explicit nullable FKs + `context_type` + `link_role` + link/unlink audit + partial-unique active link per context type).

**Resolver order** (`ConsultationMaternityContextResolver`):
1. Explicit link in `consultation_maternity_links` (active).
2. Maternity record on the **same visit** (`visit_id`).
3. Maternity record on the **same admission** (`admission_id`).
4. A **single** active pregnancy profile for the patient.
5. No automatic context.

**Hard rules (enforced in the resolver, tested):**
- Multiple active profiles ⇒ return an `ambiguous` result requiring explicit selection. **Never auto-pick.**
- Never create a profile from patient sex, complaint, diagnosis or a positive pregnancy test.
- Never auto-start ANC, labor, delivery or postnatal.
- All link/relink/unlink events written to `ActivityLogService`.
- Links survive consultation completion (historical role).

**UI:** none in this phase.

**Permissions (additive):** `consultation.maternity_context.view`, `.link`, `.unlink`.

**Tests:** resolver order; ambiguity returns `ambiguous`; no auto-creation; link/unlink audit rows; link survives completion.

**Risks:** low — additive table, no existing behaviour touched.
**Rollback:** drop table + delete new classes; nothing else references them.

---

## 3. Phase 14R.3 — Obstetrics workspace integration ✅ IMPLEMENTED

> **Phase 14R.3.1 follow-up delivered:** components wired into the real consultation page, clinical-mutation boundary hardened, query counts measured (K1/K2/K3 closed) — see `OBGYN_MATERNITY_OBSTETRICS_PILOT_PHASE_14R_3_1_REPORT.md`.

> **Delivered, dark by default.** Two feature flags (both `false`), dating method, context service + view model, server-side field-level write guard, 7 explicit actions, ribbon + panel, 3 dual-permission actions, EN/FR parity, 22 passing tests, zero new baseline failures. See `OBGYN_MATERNITY_OBSTETRICS_WORKSPACE_PHASE_14R_3_REPORT.md`.

**Goal:** Make Obstetrics stage-aware and stop new duplicate writes.

**Context ribbon** (read-only, top of workspace): active pregnancy profile · gestational age (with source label) · EDD · risk level · latest ANC visit · next ANC date · current maternity case · admission/ward/bed if admitted · active labor episode · delivery status · newborn records pending/complete · postnatal status.

**Context-aware panels** (Consultation · Pregnancy · ANC · Labor & Delivery · Newborn · Postnatal · Orders · Summary).
**Rule: not all panels are editable forms at once.** Default state is projection + explicit action.

| Panel | Behaviour |
|---|---|
| Pregnancy | Summary card; explicit **Create/Link profile** (`PregnancyProfileService`) |
| ANC | Latest ANC summary; **Record ANC Visit** → `AntenatalVisitService`. **ANC measurements are never saved as specialty entries.** |
| Labor & Delivery | Active episode + latest observation; **Open Labor Workspace** / **Start Labor Episode** (`LaborEpisodeService`, `LaborObservationService`); delivery record read-only + navigate |
| Newborn | Newborn summary; navigate. **No APGAR/birth-weight duplication.** |
| Postnatal | Readiness + latest observations; navigate. No observation duplication. |

**Section conversion** (per matrix): `antenatal_vitals`, `fetal_assessment`, `risk_assessment`, `lmp_edd_gestational_age`, `obstetric_history` become `RO-proj` + `ACT` when a context is linked; retain `RW-unlinked` when nothing is linked so an unlinked consultation is never blocked.

**Do not:** delete sections, delete entries, or hide a field without offering the equivalent maternity action in the same panel.

**Files:** `resources/views/consultations/partials/specialty/*`, new shared components (§6), `ObstetricConsultationContextService`, `HandlesConsultationWorkspace`, `ConsultationSpecialtyLayoutService` (panel gating).

**Permissions:** `.create_profile`, `.record_anc`, `.start_labor`.

**Risks:** clinicians perceiving read-only fields as data loss (mitigate with in-panel action + explanatory copy). Layout regressions in the specialty workspace.
**Rollback:** feature-flag the ribbon/panels; falling back restores current sections unchanged.

---

## 4. Phase 14R.4 — Gynaecology separation ✅ IMPLEMENTED

> **Delivered, dark by default.** Independent Gynaecology flags, explicit-only context resolution, one-way LMP adoption (R2), Gynaecology obstetric-history ownership (R4), completed `current_pregnancy`/`birth_plan` conversion, **order-set retargeting (R5 closed)** and runtime write-path hardening. 31 new tests. See `OBGYN_MATERNITY_GYNAECOLOGY_PHASE_14R_4_REPORT.md`.

**Goal:** Keep Gynaecology pregnancy-free by default; make any transition explicit.

- No maternity workflow by default; **small** context card only when explicitly linked.
- Explicit **Start/Link Pregnancy Workflow** action; optional referral/transition to Obstetrics/Maternity that **preserves** the original Gynaecology session and history.
- `obstetric_history` in Gynaecology → `RO-proj` when linked, `RW-unlinked` otherwise (**pending R4 sign-off**).
- `menstrual_history.lmp` never auto-syncs to `pregnancy_profiles.last_menstrual_period` (R2); one-way clinician-confirmed adoption only.
- **Audit and retarget order-set `patch_specialty_entry` actions** that currently write `current_pregnancy.pregnancy_confirmed` and `birth_plan.danger_signs_counseling` (R5).

**Files:** `GynaecologyConsultationContextService` *(new)*, gynaecology section views, `ConsultationSpecialtyOrderSetSeeder`.

**Risks:** order-set retargeting changes seeded data — ship as a new seeder revision, not an in-place mutation of applied order sets.

---

## 5. Phase 14R.5 — Admission, emergency, labor, delivery, postnatal handoffs

Target scenarios:

| # | Scenario | Behaviour |
|---|---|---|
| A | Obstetrics outpatient | Session opened → profile linked → ANC optionally recorded → orders via existing systems → normal completion |
| B | Gynaecology, no pregnancy | No maternity context required; normal specialty flow |
| C | Gynaecology discovers pregnancy | Clinician explicitly creates/links profile; original Gynae session intact; optional referral |
| D | Emergency obstetric case | Emergency owns the episode; maternity context linked; labor/admission handoff via existing services; **no duplicate emergency or labor record** |
| E | Admitted obstetric patient | Admission owns bed/nursing/discharge; Maternity owns pregnancy/labor/delivery/postnatal; consultation owns the specialist encounter, linked to both admission and maternity context |
| F | Postnatal review | Consultation note is encounter-level; mother/newborn observations stay postnatal records; follow-up linked, not duplicated |

**Routes:** preserve all current consultation routes and department-specific URLs — **no renames**. Add cross-links (consultation ↔ profile/ANC/labor/delivery/newborn/postnatal/admission) that carry the originating consultation session so "back" returns to the right workspace.

---

## 6. Shared component strategy

**Do not build duplicate maternity forms inside Consultation.** Build one set of reusable, service-backed components, usable from Obstetrics, Gynaecology (when linked), Admission, Emergency and Maternity workspaces:

`pregnancy-summary-card`, `anc-summary-card`, `labor-summary-card`, `delivery-summary-card`, `newborn-summary-card`, `postnatal-summary-card`, `maternity-context-selector`, `maternity-context-warning`, `maternity-quick-actions`.

Each consumes existing services (`MaternityOverviewService::forProfile`, `AntenatalOverviewService::forProfile`, `LaborOverviewService::forEpisode`, `NewbornOverviewService`, postnatal services). **They must not re-implement maternity logic.**

---

## 7. Orchestration services

`ConsultationMaternityContextResolver` · `ConsultationMaternityLinkService` · `ObstetricConsultationContextService` · `GynaecologyConsultationContextService` · `ConsultationMaternitySummaryService` · `ConsultationMaternityReadinessService`.

These **orchestrate only**. All writes delegate to `PregnancyProfileService`, `AntenatalVisitService`, `LaborEpisodeService`, `LaborObservationService`, `DeliveryRecordService`, `NewbornRecordService`, `PostnatalCaseService` and the existing admission-request service.

---

## 8. Readiness (advisory first)

- **Enforcement disabled by default:** `CONSULTATION_OBSTETRIC_MATERNITY_READINESS_ENFORCED=false`.
- Gynaecology keeps its existing readiness rules unchanged.
- General Obstetrics keeps normal consultation readiness.
- Advisory warnings only: ANC-mode with no ANC visit recorded; labor-review with no labor episode linked; postnatal-review with no postnatal case linked.
- **No hard blockers in the first implementation phase.** Existing consultation completion must keep working unchanged.

---

## 9. Summary integration

Add a generated **Maternity Context** section to the consultation summary when a context is linked: pregnancy summary, latest ANC, labor stage/latest observation, delivery outcome, newborn summary, postnatal readiness.

Rules: the projection **reads from maternity records**; it must not create specialty entries; it must not overwrite existing final-summary behaviour.

**Open decision (R6):** whether a frozen completion-time snapshot is required for medico-legal history. Proposal — live projection by default, plus an explicit immutable snapshot written at completion, rendered clearly labelled and visually distinct from the maternity source of truth.

---

## 10. Order integration

Investigations, prescriptions, procedures/theatre and tasks stay in their existing shared workflows.
- Orders retain consultation session/visit context (unchanged).
- Maternity context IDs may be attached as **secondary metadata** only where safe.
- **No** separate maternity investigation/prescription/procedure/pharmacy engines.
- No duplicate orders between Consultation and Maternity.
- Results visible from both contexts via shared relationships/projections.

---

## 11. Billing de-duplication

**Phase 14.1 stays preview-only. Do not enable Phase 14.2 in any phase below 14R.6.**
Verified current state: `config('billing.maternity_billing.enabled')` defaults **false**, and `MaternityBillingPostingService::postForSource()` returns `STATUS_POSTING_NOT_IMPLEMENTED`.

Double-billing surfaces to police: general consultation charge · obstetrics specialty consultation charge · ANC registration/follow-up · labor observation · delivery · postnatal care.

**Policy matrix (proposed defaults):**

| Clinical action | Policy | Default |
|---|---|---|
| Obstetrics consultation, no maternity event | `consultation_only` | ✅ |
| ANC visit recorded from Obstetrics workspace | `maternity_event_only` | ✅ (one source record: the `AntenatalVisit`) |
| Labor observation | `maternity_event_only` | ✅ |
| Delivery | `maternity_event_only` | ✅ |
| Postnatal care | `maternity_event_only` | ✅ |
| Consultation + genuinely separate maternity event same day | `both_when_configured` | ❌ off by default |
| Disputed/edge | `manual_selection` | ❌ off by default |

**Requirements:** one clinical action must never silently create two charges; billing source identity stays bound to the real source record; consultation vs. maternity-event billing remain distinguishable; **do not reintroduce a billing card into the doctor consultation workspace** (it is intentionally excluded); preview/posting stays permission-controlled outside normal clinical editing.

---

## 12. Permissions (additive)

`consultation.maternity_context.view` · `.link` · `.unlink` · `.create_profile` · `.record_anc` · `.start_labor` · `.open_postnatal` · `.summary.view`.

These gate the **bridge action**; the underlying maternity permission (`maternity.anc.record`, `maternity.labor.start`, `maternity.pregnancy.create`, …) still applies. Both must pass — the bridge never escalates privilege. Do not duplicate anything already covered by the 67 existing `maternity.*` permissions.

---

## 13. Localisation

New `lang/{en,fr}` keys, kept in strict parity: maternity context · link/create pregnancy profile · active pregnancy · no active pregnancy profile · **multiple active pregnancy profiles** · select maternity context · ANC/labor/delivery/newborn/postnatal context · open maternity workspace · record ANC visit · start labor episode · context linked/unlinked · historical context · source-of-truth warning · duplicate-data warning · advisory readiness warning.

---

## 14. Historical reconciliation (14R.6, dry-run first)

Command: `maternity:reconcile-obgyn-entries --dry-run` (default dry-run; `--apply` requires explicit confirmation).

Classifies every specialty entry in a maternity-owned section as **safe to link / safe to migrate / conflict requiring review / historical-only / insufficient context** (definitions in the matrix §7). Produces a review report. Preserves original entry values always. **No automatic backfill.**

Measured in this environment: **0 rows** to reconcile — re-measure per environment before enabling write-path changes there.

---

## 15. Phase summary

| Phase | Goal | Risk | Gate |
|---|---|---|---|
| 14R.1 | Audit, matrix, architecture *(docs only)* ✅ | none | ✅ Matrix signed off (R2/R4/R5/R6) |
| 14R.2 | Bridge table + resolver + link service ✅ | low | ✅ 22 bridge tests green |
| 14R.3 | Obstetrics stage-aware workspace ✅ | medium | ✅ 22 tests green; baselines unchanged; flags default off |
| 14R.3.1 | Pilot wiring + mutation boundary + perf ✅ | low | ✅ 10 tests green; K1/K2/K3 closed; baselines unchanged |
| 14R.4 | Gynaecology separation + explicit transition | medium | Gynae unaffected without a link |
| 14R.5 | Admission/emergency/labor/delivery/postnatal handoffs ✅ | medium | ✅ 64 tests green; scenarios A–F verified; baselines unchanged; 4 new flags default off |
| 14R.5.1 | Handoff UI completion + pilot closure ✅ | low | ✅ 44 tests green; **K2 closed**; K1 fallback usable; baselines unchanged |
| 14R.6 | Readiness, summary projection, snapshots, reconciliation, billing policy ✅ | high | ✅ 65 tests green; **R6 closed**; snapshots immutable; dry run 0 writes; baselines unchanged |
| 14R.7 | Manual test data + wider regression | medium | Full regression pass |

**14R.6 is complete (dark by default). Consultation completion now captures an immutable, versioned maternity snapshot inside its own transaction; completed summaries bind to that snapshot rather than to live data; a read-only reconciliation dry run classifies historical O&G entries; and billing de-duplication policy is declared without posting anything. R6 is closed. Next: 14R.7 — manual-test data, environment reconciliation review, pilot acceptance and wider regression.**

See `OBGYN_MATERNITY_READINESS_SUMMARY_RECONCILIATION_PHASE_14R_6_REPORT.md`.

**14R.5.1 is complete (dark by default). Every handoff trigger now has a real dialog, generated from a typed action contract and guarded by an automated modal-integrity check; K2 is closed and K1 has a usable fallback.**

**14R.5 is complete (dark by default). Three additive link tables — `emergency_maternity_links`, `admission_request_maternity_links`, `admission_maternity_links` — now carry operational maternity context, and the 14R.2 derivation logic is shared through `MaternityContextTargetService`. Next: 14R.6 — advisory readiness, summary projection, immutable completion snapshot, historical reconciliation dry run and billing de-duplication policy.**

See `OBGYN_MATERNITY_HANDOFFS_PHASE_14R_5_REPORT.md` for the ownership matrix, idempotency identities, return-context security model and measured query counts.
