# Phase 14R.5 — Admission, Emergency, Labor, Delivery & Postnatal Handoff Integration (Report)

**Status:** ✅ Implemented. Dark by default (all four new flags `false`).
**Update — Phase 14R.5.1:** **K2 is CLOSED** (all 19 missing modal bodies authored; an automated integrity check now fails the build on any dangling trigger) and **K1 has a real clinician-facing fallback**. See `OBGYN_MATERNITY_HANDOFF_UI_PHASE_14R_5_1_REPORT.md`.
**Companions:** `OBGYN_MATERNITY_GYNAECOLOGY_PILOT_PHASE_14R_4_1_REPORT.md`, `OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md`

---

## 1. Existing handoff architecture audited (step 1, before any code)

| Area | What already existed | What 14R.5 does with it |
|---|---|---|
| Consultation routing | `ConsultationRouteService::createRouteForDepartment()` — already idempotent per (visit, department) | Reused verbatim for the Gynaecology → Obstetrics referral |
| Consultation session | `ConsultationSessionService::resolveRouteForVisit()`, `consultationMutationContext()` | Reused as the clinical-mutation boundary |
| Admission requests | `AdmissionRequestService::create/createForVisit/accept/convertToAdmission`; `createForVisit` already de-duplicates open requests per (visit, source, source_id) | Called, never re-implemented; propagation added inside the conversion transaction |
| Admission conversion | `convertToAdmission()` → `AdmissionService::admit()` + `BedWorkflowService` | One new call inside the existing transaction |
| Emergency cases | `EmergencyCaseService::create()` — creates visit, session, billing, timeline, and **already refuses a second active case per visit** | Called, never bypassed; that duplicate rule drove the "open existing case" reuse path |
| Emergency disposition | `EmergencyDispositionService::dispose()` sets `ADMITTED` → visit `ADMITTING` | Untouched |
| Maternity → Admission | `AntenatalVisitService::createAdmissionRequest()`, `LaborEpisodeService::createAdmissionRequest()` | Untouched |
| Postnatal readiness | Phase 12 advisory area on `admission->postnatalCases` | Extended to also see explicitly linked cases |
| Orders | investigation / radiology / procedure / prescription / task creation | Untouched — no parallel engine |

**Durable handoff records found:** `admission_requests.source_type/source_id`, `admissions.admission_request_id`, `emergency_cases.visit_id/admission_id`, `delivery_records.emergency_case_id`, maternity `visit_id`/`admission_id` columns, `consultation_maternity_links` (14R.2).

**Critical audit finding — legacy source ambiguity.** `source_type = maternity` is written by BOTH `AntenatalVisitService` (source_id = AntenatalVisit id) and `LaborEpisodeService` (source_id = LaborEpisode id). The source id alone therefore cannot identify the record type. 14R.5 **does not reinterpret** those rows; it surfaces a warning and asks for an explicit link. This is precisely why clinical maternity context got its own table instead of overloading `source_id`.

**Writes during route/view loading:** `EmergencyCaseController::show()` calls `EmergencySessionService::getOrCreateForCase()` (pre-existing). No new write-on-load was introduced — both new composers are read-only.

## 2. Operational ownership matrix (ratified)

| Owner | Owns |
|---|---|
| **Consultation** | complaints, HOPC, examination, specialist narrative, diagnoses, assessment, encounter plan, orders, encounter notes, completion, summary |
| **Emergency** | arrival, triage, acuity, emergency vitals & notes, bay, emergency treatment, emergency tasks, disposition, emergency-to-admission decision |
| **Admission** | request review, bed reservation, conversion, ward/bed, nursing notes & tasks, medication/MAR, transfers, discharge planning & discharge |
| **Maternity** | Pregnancy Profile, ANC Visit, Labor Episode, Labor Observation, Delivery Record, Newborn Record, Postnatal Case, postnatal observations, maternity risk & readiness |

Enforced in code: a handoff creates a link or calls the target domain service; the source never duplicates the target's record; the source record stays intact; one operational event never creates two target records.

## 3. Flags (all default false)

`config/maternity.php` → `integration.*`

```
MATERNITY_CONSULTATION_HANDOFFS_ENABLED=false
MATERNITY_EMERGENCY_CONTEXT_ENABLED=false
MATERNITY_ADMISSION_CONTEXT_ENABLED=false
MATERNITY_EMERGENCY_HANDOFFS_ENABLED=false
```

`emergencyHandoffsEnabled()` = `emergencyContextEnabled() && handoffFlag` — an action surface can never appear where the read-only context is off (enforced in `MaternityIntegrationFlags`, not by convention). Maternity → Emergency escalation is deliberately **not** gated by the Emergency context flag: the action starts in Maternity, which has its own permissions.

The four Obstetrics/Gynaecology flags remain fully independent.

## 4. Shared target derivation

`MaternityContextTargetService` + `MaternityContextTargetDescriptor` extract the 14R.2 logic verbatim: context-type mapping, Pregnancy Profile root derivation, owner resolution (`mother_patient_id` for Newborn/Postnatal), and fail-closed rejection of unsupported/unsaved/root-less targets.

`ConsultationMaternityLinkService` now **delegates** and re-throws through `translate()`, so **every 14R.2 error code and message is byte-identical**. Visit mismatch remains allowed (longitudinal records span visits); patient mismatch remains fatal. `ConsultationMaternityBridgePhase14R2Test` — **22 passed, unchanged**.

## 5. New tables

| Table | Purpose | Uniqueness |
|---|---|---|
| `emergency_maternity_links` | Emergency case ↔ maternity record | `(emergency_case_id, context_type, active_slot)` |
| `admission_request_maternity_links` | Request ↔ maternity **clinical** context | `(admission_request_id, context_type, active_slot)` |
| `admission_maternity_links` | Admission ↔ maternity record (+ audit `admission_request_id`) | `(admission_id, context_type, active_slot)` |

Same nullable `active_slot` design as 14R.2: one ACTIVE link per (source, context type), unlimited history, MySQL/MariaDB-compatible without partial indexes. Lifecycle lives in `AbstractMaternityLinkService`, so all four bridges share one implementation.

## 6. Behaviour by bridge

**Emergency links** — idempotent same-target; different target requires relink + reason; unlink preserves history; patient ownership fail-closed; no profile auto-created; **no context inferred from complaint, diagnosis, danger signs or pregnancy test**. Same-visit records surface as `SUGGESTED`, clearly labelled and never persisted without confirmation. Multiple profiles → `AMBIGUOUS`, never "the latest". No Emergency case and no Labor episode is created during resolution.

**Admission Request context** — kept strictly separate from `source_type`/`source_id`. A request stays "raised by Emergency" while carrying "about this Labor Episode". Requests without a row remain valid; legacy ambiguous `maternity` sources warn rather than guess.

**Admission context** — resolution order: explicit links → request-carried links → records whose `admission_id` matches → single profile → none/ambiguous. Never links or creates during resolution; direct non-maternity admissions take the fast path.

**Request → Admission propagation** — runs **inside** `convertToAdmission()`'s existing transaction, so a failure rolls the conversion back. Preserves the request and its operational origin, never duplicates active links, `link_role = handoff`, records the source request for audit. A stage record's `admission_id` is populated only when null; equal is idempotent; **a different admission is never overwritten** and is reported as a conflict. `PregnancyProfile.admission_id` is deliberately excluded — one nullable column cannot represent a second admission.

**Consultation → Admission Request** — Obstetrics only. Requires an editable consultation, an explicit profile link, and both `consultation.maternity_context.create_admission_request` and `admission.requests.create`. `source_type = consultation`, `source_id = VisitConsultationRoute id`. Copies only priority, ward, provisional diagnosis and a summary truncated to 500 chars — never the full note. No auto-admit, no bed reservation, no billing, no specialty entry.

**Gynaecology → Obstetrics referral** — the current route, its specialty and every entry are untouched. Creates a separate target route through the existing `ConsultationRouteService`, links it to the same profile with `link_role = handoff`. When no Obstetrics department is mapped it returns `unavailable` and the UI points at the standard create-consultation flow (documented limitation K1). No ANC, labor or admission request is created.

**Emergency → Maternity** — link/create profile (via `PregnancyProfileService`), start/re-use labor (via `LaborEpisodeService`; reuse covers `active|monitoring|delivery_pending` only, so a delivered or closed episode is never resurrected), create/re-use admission request (source stays `emergency`). Clinical actions require an **explicit** link — a suggestion is never sufficient.

**Maternity → Emergency** — the escalation FLAG creates nothing. The explicit action goes through `EmergencyCaseService` (never a manual insert), reuses an already-linked active case *or* an active case on the same visit (Emergency's own one-active-case-per-visit rule), creates a `handoff` link, and preserves the maternity source. No admission request, no Theatre case.

**Postnatal review** — links an existing same-patient case (`link_role = reviewed`), shows readiness and observation *timestamps* read-only, never creates a second case or an observation, and does not touch consultation completion or readiness.

## 7. Idempotency identities

| Handoff | Identity | Protection |
|---|---|---|
| Consultation → Admission Request | route + profile + most-specific target + OPEN status | `lockForUpdate()` on the route before the duplicate check |
| Emergency → Labor | emergency case + profile + active episode | `lockForUpdate()` on the profile |
| Emergency → Admission Request | emergency case + OPEN status | `lockForUpdate()` on the case |
| Maternity → Emergency | source record + active linked case (or active case on the visit) | transaction + Emergency's own per-visit rule |
| Request → Admission | request + admission + context type + target id | unique index + reuse/conflict branch |
| Any link | source + context type + target | unique `(source, context_type, active_slot)` index as final race guard |

Rejected, cancelled and converted requests are never treated as open. Closed/cancelled labor episodes are never reused.

## 8. Return-context security

`MaternityReturnContext` carries a **named internal route plus scalar parameters — never a URL**. An external host is structurally inexpressible. The module set is closed, route names must match that module's allowed prefixes, anchors are slug-sanitised to 64 chars, and an invalid context simply falls back to the target module's own show page. Authorisation is re-checked by the return route's own middleware, so a link never grants access the user lacks.

## 9. Permissions

Added (all additive; nothing removed): `consultation.maternity_context.{create_admission_request, refer_obstetrics, open_postnatal}`, `emergency.maternity_context.{view, link, unlink, create_profile, start_labor, create_admission_request}`, `admission.maternity_context.{view, link, unlink}`, `maternity.emergency_handoff.create`.

Every action requires the bridge permission **and** the target module's own permission (`maternity.labor.start`, `admission.requests.create`, `emergency.case.create`, `maternity.postnatal.view`, …). The seeder grants a bridge permission only to roles that already hold both sides. Tested in both directions on all three bridges.

## 10. Localisation

`lang/{en,fr}/maternity_handoffs.php` — **112 / 112 keys, strict recursive parity, verified programmatically.** Covers ownership labels, cards, fields, risks, consultation/emergency/admission sections, handoff outcomes and postnatal wording.

## 11. Activity logging (identifier-only)

`CONSULTATION_MATERNITY_ADMISSION_REQUEST_CREATED`, `GYNAECOLOGY_OBSTETRICS_HANDOFF_CREATED`, `EMERGENCY_MATERNITY_CONTEXT_{LINKED,RELINKED,UNLINKED}`, `EMERGENCY_LABOR_EPISODE_STARTED`, `EMERGENCY_MATERNITY_ADMISSION_REQUEST_CREATED`, `MATERNITY_EMERGENCY_HANDOFF_CREATED`, `ADMISSION_MATERNITY_CONTEXT_PROPAGATED` / `_PROPAGATION_CONFLICT`, `ADMISSION_MATERNITY_CONTEXT_{LINKED,RELINKED,UNLINKED}`, `ADMISSION_REQUEST_MATERNITY_CONTEXT_*`, `POSTNATAL_CONSULTATION_REVIEW_LINKED`.

Metadata is module + record ids + profile id + actor + role only. **No clinical notes, sexual history, ANC measurements, labor observations, newborn measurements or postnatal observations are logged.**

## 12. Query counts (measured)

| Surface | Queries |
|---|---|
| Consultation — Obstetrics handoffs disabled | **0** |
| Consultation — Gynaecology handoffs disabled | **0** |
| Consultation — enabled, no explicit context | 8 |
| Consultation — explicit context, no request | **3** |
| Consultation — with open Admission Request | **3** |
| Emergency — context disabled | **0** |
| Emergency — enabled, no context | 5 |
| Emergency — suggested context | **5** (was 16) |
| Emergency — explicit Pregnancy Profile | **3** |
| Emergency — explicit Labor Episode | **4** (no N+1) |
| Admission — context disabled | **0** |
| Admission — enabled, non-maternity | **2** |
| Admission — linked Pregnancy Profile | **2** |
| Admission — linked Labor/Postnatal context | **3** |

**Two optimisations applied:**
1. `profileIdsFrom()` now issues **one UNION** instead of six table scans (`toBase()` preserves soft-delete scopes).
2. A `SUGGESTED` context skips the six-record stage fan-out — a suggestion only has to identify the pregnancy. Confirming it produces an explicit link, which then resolves the full picture.

Emergency suggested-context cost fell **16 → 5**. Every context resolves at most once per request (memoised; asserted). The shared Blade card partial performs **zero** queries — it reads only prepared arrays. Newborn summaries are built from one already-loaded collection, so multiples cost nothing extra. No persistent cross-request caching.

## 13. Files changed

**New**
```
config/maternity.php
app/Data/Maternity/{MaternityContextTargetDescriptor,OperationalMaternityContext,OperationalMaternityViewModel}.php
app/Models/{EmergencyMaternityLink,AdmissionRequestMaternityLink,AdmissionMaternityLink}.php
app/Models/Concerns/HasMaternityContextTargets.php
app/Services/Maternity/Context/{MaternityContextTargetService,MaternityContextTargetException,
    AbstractMaternityLinkService,MaternityLinkException,AbstractOperationalMaternityContextResolver,
    MaternityContextCardBuilder,MaternityIntegrationFlags}.php
app/Services/Emergency/Maternity/{EmergencyMaternityLinkService,EmergencyMaternityContextResolver,
    EmergencyMaternityHandoffService,EmergencyMaternityWorkspaceService}.php
app/Services/Admissions/Maternity/{AdmissionRequestMaternityLinkService,AdmissionMaternityLinkService,
    AdmissionMaternityContextResolver,AdmissionMaternityContextPropagationService,
    AdmissionMaternityWorkspaceService}.php
app/Services/Consultation/Maternity/{ConsultationMaternityAdmissionRequestService,
    GynaecologyObstetricsReferralService,ConsultationPostnatalReviewService,
    ConsultationMaternityHandoffPresenter}.php
app/Services/Maternity/Handoffs/MaternityEmergencyHandoffService.php
app/Support/Maternity/MaternityReturnContext.php
app/Http/Controllers/Admin/Emergency/EmergencyMaternityContextController.php
app/Http/Controllers/Admin/AdmissionsWard/AdmissionMaternityContextController.php
app/Http/Controllers/Admin/Maternity/MaternityEmergencyHandoffController.php
database/migrations/2026_07_26_00000{1,2,3}_create_*_maternity_links_table.php
database/migrations/2026_07_26_000004_seed_maternity_handoff_permissions.php
lang/{en,fr}/maternity_handoffs.php
resources/views/maternity/partials/context-cards.blade.php
resources/views/emergency/partials/maternity-context-card.blade.php
resources/views/admissions/partials/maternity-context-card.blade.php
resources/views/consultations/partials/maternity/handoff-actions.blade.php
tests/Feature/Concerns/BuildsMaternityHandoffFixtures.php
tests/Feature/{Consultation,Emergency,Admission}MaternityHandoffsPhase14R5Test.php
tests/Feature/MaternityOperationalHandoffsPhase14R5Test.php
```

**Modified**
```
app/Services/Consultation/Maternity/ConsultationMaternityLinkService.php  — delegates derivation
app/Services/Admissions/AdmissionRequestService.php                       — propagation in the conversion transaction
app/Services/Admissions/AdmissionDischargeReadinessService.php            — resolves explicitly linked postnatal cases
app/Http/Controllers/Doctor/Consultations/ConsultationMaternityContextController.php — 3 handoff actions
app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php  — handoff presenter
app/Http/Controllers/Admin/Emergency/EmergencyCaseController.php          — maternity view model
app/Http/Controllers/Admin/AdmissionsWard/AdmissionController.php         — maternity view model
routes/web.php, routes/partials/maternity.php                             — new routes
resources/views/{emergency/show,admissions/show,consultations/show}.blade.php — includes
```

## 14. Tests and baseline comparison

| Suite | Result | Baseline | Verdict |
|---|---|---|---|
| **ConsultationMaternityHandoffsPhase14R5Test** (new) | **18 passed** | — | ✅ |
| **EmergencyMaternityHandoffsPhase14R5Test** (new) | **16 passed** | — | ✅ |
| **AdmissionMaternityHandoffsPhase14R5Test** (new) | **15 passed** | — | ✅ |
| **MaternityOperationalHandoffsPhase14R5Test** (new) | **15 passed** | — | ✅ |
| 14R.2–14R.4.1 O&G bridge suites | 107 passed, 1 skipped | 107 | ✅ unchanged |
| Admission foundation/bed/nursing/discharge | 40 passed, **1 failed** | 1 pre-existing | ✅ unchanged |
| Maternity Phase 8–12 | 33 passed, **1 failed** | 1 pre-existing ANC | ✅ unchanged |
| All Emergency suites (`--filter=Emergency`) | **88 passed** | — | ✅ |
| `tests/Feature/Consultations` | 230 passed, **22 failed** | 22 pre-existing | ✅ **exactly unchanged** |

Both pre-existing failures (`AdmissionNursingCarePhase6Test > admission show renders…`, `AntenatalCarePhase9Test > anc history detail…`) were **proven pre-existing** by stashing every 14R.5 change and re-running: identical results.

**Phase 14R.5 introduced zero new failures.** `composer test:wide` was not run.

## 15. Existing workflows protected

Unchanged and verified: Emergency triage/bay/vitals/notes/treatment/tasks/disposition history; Emergency-to-admission behaviour with no maternity context; admission request accept/reject/cancel/reserve/convert; bed reservation and transfer; nursing notes/tasks and MAR; discharge planning and the final discharge flow; consultation readiness and completion; Gynaecology completion; all Maternity Phase 8–12 behaviour; orders/prescriptions/procedures/tasks. **No invoice item is created by any handoff**, and maternity billing posting remains disabled (`MATERNITY_BILLING_ENABLED=false`, `postForSource()` still non-posting).

## 16. Known risks

| # | Risk |
|---|---|
| K1 | ⚠️ **Mitigated in 14R.5.1** — still a configuration dependency, but the unavailable state now shows its reason plus a real link into the existing standard create-consultation flow. No referral subsystem was invented. |
| K2 | ✅ **CLOSED in 14R.5.1.** The audit found **19** missing dialog bodies, not 3; all are authored, every trigger is generated from a typed action model, and `MaternityHandoffModalIntegrityPhase14R5_1Test` fails the build if a trigger ever loses its target again. |
| K3 | Consultation handoff panel with no context now costs 5 queries (was 8). Re-measured in 14R.5.1; worth watching under pilot load. |
| K4 | Legacy `source_type = maternity` requests remain ambiguous by design; they surface a warning and require an explicit link. No backfill was run. |

## 17. Deferred (explicitly out of scope)

Summary projection and immutable completion snapshots; historical O&G specialty-entry reconciliation; readiness enforcement; maternity billing posting; billing cards in Consultation/Emergency/Admission; Theatre case creation; a neonatal/paediatric admission bridge.

## 18. Rollout and rollback

**Rollout:** deploy with all four flags false → enable `MATERNITY_ADMISSION_CONTEXT_ENABLED` (read-only) → enable `MATERNITY_EMERGENCY_CONTEXT_ENABLED` (read-only) → enable `MATERNITY_CONSULTATION_HANDOFFS_ENABLED` → enable `MATERNITY_EMERGENCY_HANDOFFS_ENABLED` last.

Before rollout: confirm bridge and target-module permissions per role; confirm open-request duplicate rules; confirm active-Labor and active-Emergency reuse; confirm return-context security; keep all O&G write guards in their current approved state; keep maternity billing disabled.

**Rollback:** disable Emergency handoffs → Consultation handoffs → Emergency context → Admission context → `config:clear`. Read-only cards and actions disappear; existing links, Emergency cases, Admission Requests, Admissions and Maternity records remain valid and auditable. **No destructive migration rollback is required** — the three tables are additive and historical rows are preserved.

## 19. Next phase

**14R.6 — Advisory readiness, Consultation summary projection, immutable completion snapshot, historical reconciliation dry run and billing de-duplication policy.**
