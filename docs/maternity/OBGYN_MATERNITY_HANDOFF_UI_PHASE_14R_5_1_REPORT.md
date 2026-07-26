# Phase 14R.5.1 — Handoff UI Completion & Pilot Closure (Report)

**Status:** ✅ Implemented. **K2 is CLOSED.** K1 has a real, usable clinician-facing fallback. Dark by default (all four flags `false`).
**Companion:** `OBGYN_MATERNITY_HANDOFFS_PHASE_14R_5_REPORT.md`

---

## 1. Trigger / modal audit inventory (step 1, before any code)

Scanned every O&G/Maternity handoff view for `data-bs-toggle="modal"`, `data-bs-target`, `href="#…"`, JS modal-open calls, presenter action keys and forms without a route.

**The three documented IDs were not the whole story — 19 triggers had no dialog body.**

| # | Trigger label | Modal target ID | Workspace | Route | Method | Required fields | Bridge permission | Target permission | Lifecycle | State found |
|---|---|---|---|---|---|---|---|---|---|---|
| 1 | Create Admission Request | `consultationMaternityAdmissionRequestModal` | Consultation (Obs) | `…maternity-context.admission-request` | POST | priority, ward, diagnosis, summary | `consultation.maternity_context.create_admission_request` | `admission.requests.create` | editable route | **missing modal** |
| 2 | Refer to Obstetrics/Maternity | `consultationReferObstetricsModal` | Consultation (Gyn) | `…refer-obstetrics` | POST | notes | `…refer_obstetrics` | `consultations.create` | editable route | **missing modal** |
| 3 | Link Postnatal Case | `consultationPostnatalReviewModal` | Consultation | `…postnatal-review` | POST | `postnatal_case_id` | `…open_postnatal` | `maternity.postnatal.view` | context action | **missing modal** |
| 4 | Link Pregnancy Profile | `emergencyLinkPregnancyModal` | Emergency | `…maternity-context.link` | POST | `pregnancy_profile_id` | `emergency.maternity_context.link` | `maternity.pregnancy.view` | case open | **missing modal** |
| 5 | Create Pregnancy Profile | `emergencyCreatePregnancyModal` | Emergency | `…create-profile` | POST | — | `…create_profile` | `maternity.pregnancy.create` | case open | **missing modal** |
| 6 | Start/Open Labor | `emergencyStartLaborModal` | Emergency | `…start-labor` | POST | `pregnancy_profile_id` | `…start_labor` | `maternity.labor.start` | case open + explicit link | **missing modal** |
| 7 | Create/Open Admission Request | `emergencyMaternityAdmissionRequestModal` | Emergency | `…admission-request` | POST | priority, ward, diagnosis, summary | `…create_admission_request` | `admission.requests.create` | case open | **missing modal** |
| 8 | Unlink Pregnancy Profile | `emergencyUnlinkPregnancyModal` | Emergency | `…unlink` | POST | reason | `…unlink` | `maternity.pregnancy.view` | case open | **missing modal** |
| 9 | Relink Pregnancy Profile | `emergencyRelinkPregnancyModal` | Emergency | `…relink` | POST | profile, reason | `…link` | `maternity.pregnancy.view` | case open | **missing trigger + modal** |
| 10 | Link Admission Context | `admissionLinkPregnancyModal` | Admission | `…maternity-context.link` | POST | `pregnancy_profile_id` | `admission.maternity_context.link` | `maternity.pregnancy.view` | not discharged | **missing modal** |
| 11 | Correct context | `admissionRelinkPregnancyModal` | Admission | `…relink` | POST | profile, reason | `…link` | `maternity.pregnancy.view` | not discharged | **missing modal** |
| 12 | Unlink context | `admissionUnlinkPregnancyModal` | Admission | `…unlink` | POST | reason | `…unlink` | `maternity.pregnancy.view` | not discharged | **missing modal** |
| 13 | Link/Create Profile | `maternityLinkProfileModal` | Consultation (Obs panel, 14R.3.1) | `…link` | POST | `pregnancy_profile_id` | `consultation.maternity_context.link` | `maternity.pregnancy.view` | context action | **missing modal** |
| 14 | Relink Profile | `maternityRelinkModal` | Consultation (Obs) | `…relink` | POST | profile, reason | `…link` | `maternity.pregnancy.view` | context action | **missing modal** |
| 15 | Unlink Profile | `maternityUnlinkModal` | Consultation (Obs) | `…unlink` | POST | reason | `…unlink` | `maternity.pregnancy.view` | context action | **missing modal** |
| 16 | Record ANC | `maternityRecordAncModal` | Consultation (Obs) | `…record-anc` | POST | visit_date | `…record_anc` | `maternity.anc.record` | editable route | **missing modal** |
| 17 | Start or Link | `gynaeLinkPregnancyModal` | Consultation (Gyn, 14R.4.1) | `…link` | POST | `pregnancy_profile_id` | `…link` | `maternity.pregnancy.view` | context action | **missing modal** |
| 18 | Adopt LMP | `gynaeAdoptLmpModal` | Consultation (Gyn) | `…adopt-lmp` | POST | — | `…adopt_lmp` | `maternity.pregnancy.update` | editable route | **missing modal** |
| 19 | Relink / Unlink | `gynaeRelinkModal`, `gynaeUnlinkModal` | Consultation (Gyn) | `…relink` / `…unlink` | POST | profile, reason / reason | `…link` / `…unlink` | `maternity.pregnancy.view` | context action | **missing modal** |
| — | Create Emergency Handoff | `maternityEmergencyHandoffModal` | Maternity (labor/delivery/postnatal) | `…emergency-handoff` | POST | arrival_mode | `maternity.emergency_handoff.create` | `emergency.case.create` | supported source | **missing trigger + modal** |

Also confirmed **not** broken: the `mc-pregnancy` / `mc-anc` / `mc-labor` / `mc-newborn` / `mc-postnatal` targets in the Obstetrics panel are `data-bs-toggle="tab"` panes, correctly defined. They are out of scope.

**Every one of these is now complete.** The maternity handoff views contain **zero** hand-written `data-bs-target` — triggers are generated from typed actions, so a trigger cannot outlive its dialog by construction.

## 2. Modal architecture used

The project's existing convention, unchanged: Bootstrap 5 `modal > modal-dialog > form.modal-content`, `@csrf`, `old()` for input preservation, `@selected()`, `@error` + `is-invalid` / `invalid-feedback`, flash `success` / `error`, and the Blood-Bank select2-AJAX pattern for searchable pickers.

No second modal framework, no new frontend framework, no SPA layer, no inline business logic, and no state-changing GET route. The only GET added is the read-only candidate lookup.

**Reopen after validation failure** uses the framework's own old-input rather than a bespoke session key: each form emits `_handoff_modal`, and the page re-opens exactly that dialog from `old('_handoff_modal')`. No controller change was needed.

## 3. Typed presentation contract

`MaternityHandoffActionViewModel` carries action key, label, description, visible, state, disabled reason, route name + parameters, method, modal id, confirmation level, required fields, source/target module, target context type, existing-record details, return context and read-only display context.

`MaternityHandoffActionFactory` resolves one closed state ladder for every module:

```
feature flag → permissions → explicit unavailability → lifecycle → ambiguity → context → existing record → enabled
```

Order matters: a missing permission says so rather than "link a profile first", and a completed consultation says so rather than offering a form the server will reject.

Blade renders from this and **never** rediscovers permissions, queries for profiles/requests/admissions/emergency cases, infers reuse, builds a return URL, or interprets an ambiguous legacy `source_id`.

**Bug found and fixed while wiring this:** the factory used `new Action(...$base + [...])`. PHP's `+` keeps the **left** operand for duplicate keys, so the "Open Existing …" label override silently never applied. Switched to `array_merge`. It was caught by the UI suite, not by inspection.

## 4. UI by module

**Consultation → Admission Request** — Obstetrics only. Read-only context summary, priority, bounded active-ward list (no beds loaded), provisional diagnosis, and a 500-char handover summary with the server-side truncation preserved. States `No admission is created` / `No bed is reserved`. An open request flips the action to *Open Existing Admission Request* with id, status and link.

**Gynaecology → Obstetrics referral** — notes only; explicitly states the consultation remains Gynaecology and that no maternity record is created.

**Postnatal review** — same-patient case selector, `link_role = reviewed`, read-only readiness projection, and explicit "observations remain in Maternity" wording.

**Emergency link/profile** — confirm a suggested profile, select an existing one, relink or unlink with a mandatory reason, or create explicitly. The selector is lazy and patient-scoped; no profile is pre-selected, so an ambiguous context always requires a human choice.

**Emergency Start/Open Labor** — explicit link required (a suggestion is refused). With an active episode the form is replaced by *Open Existing Labor*. Only the inputs the existing `LaborEpisodeService` route accepts are offered; the full Labor form is not duplicated.

**Emergency Admission Request** — operational source (`Emergency`) and clinical maternity context are labelled **separately**; an open request renders *Open Existing Admission Request* rather than a misleading "create another".

**Admission context** — link / correct (relink, reason required) / unlink (reason required). The summary distinguishes *carried from Admission Request*, *linked directly* and *inferred from a matching admission record*, and the card keeps the legacy-ambiguous-source warning. No ANC/Labor/Delivery/Newborn/Postnatal record is creatable from here.

**Maternity → Emergency** — a new panel on Labor, Delivery and Postnatal. States plainly that the escalation flag created nothing, that this action will create or open a case, and that no Admission Request and no Theatre case will be created. An active linked or same-visit case renders *Open Existing Emergency Case*.

## 5. K1 — Gynaecology referral fallback (no new subsystem)

With no active `ConsultationSpecialtyProfileMapping` for an Obstetrics consultation department the action resolves to `unavailable`, shows the reason, and renders a **real link into the existing standard consultation-creation flow** carrying patient and visit. No department is preselected (preselecting an invalid one is the failure being avoided), no route is created until the clinician completes the standard flow, the Gynaecology specialty is unchanged and no ANC/Labor/Admission Request is created.

With a valid mapping the explicit confirmation renders, `ConsultationRouteService` is reused, a repeat reuses the existing open route, and the target route is linked to the same Pregnancy Profile. **K1 remains a configuration dependency; the clinician-facing path is now fully usable either way.**

## 6. Honest disabled states

Every action renders exactly one of `enabled · existing_record · blocked · unavailable · ambiguous · permission_missing · feature_disabled · invalid_context`.

A clickable button appears **only** for `enabled` and `existing_record`. `permission_missing` and `feature_disabled` are hidden entirely (the UI does not advertise capabilities a role lacks); the rest render their reason as text. The server remains authoritative regardless — the UI disabling an action never replaces a check.

## 7. Lifecycle boundaries

Context actions (link / relink / unlink / review) remain available after consultation completion, per the approved 14R.2 policy. Clinical/operational creation actions use the **same** `ConsultationSessionEligibilityService::addItemDecision()` the server-side mutation guard uses, so the UI can never be more permissive than the boundary. Emergency actions respect case state (disposed/cancelled → blocked); Admission actions respect discharge; Maternity escalation respects source-record and patient validity.

## 8. Idempotency made visible

Repeated actions surface the server's own result: "Existing record reused", the record id and status, and a link to it — never wording implying a duplicate was created. Covered for Consultation → Admission Request, Emergency → Labor, Emergency → Admission Request, Maternity → Emergency Case, Gynaecology → Obstetrics referral and request → Admission propagation. **No client-side duplicate logic was added**; the transactional identity remains server-side.

## 9. Return-context security

Every modal form carries `MaternityReturnContext` hidden fields — a **named internal route plus scalar parameters**, never a URL. There is no `return_url` field anywhere. An external value is rejected and the action falls back to the target module's own show page (tested against `https://evil.example.com/steal`). Authorisation is rechecked at the destination by its own middleware.

## 10. Candidate selectors

`GET …/maternity-context/candidates` for Emergency, Admission and Consultation. Patient scope is derived from the **route-bound record server-side**; a request may only supply a search term. Attempts to pass `patient_id`, `pregnancy_profile_id` or `all=1` do not widen it (tested). Results are capped at 20, ordered active-first, and contain identifiers/dating only — no clinical narrative. Both bridge and `maternity.pregnancy.view` are required, and the endpoint 403s while its flag is off.

## 11. Permissions and flags

**No new permissions were added** — the audit found every action already covered by a 14R.5 permission. Every action still requires the target-domain permission, and a user holding only one half sees no form (tested in both directions).

Flag behaviour: off → no trigger, no modal body, no candidate query, zero queries. Context on / handoffs off → context renders, **no mutation form at all** (tested). Handoffs on → only actions the user may execute. The four Obstetrics/Gynaecology flags remain independent.

## 12. Query counts (measured)

| Surface | 14R.5.1 | 14R.5 |
|---|---|---|
| Emergency page, flags off | **0** | 0 |
| Emergency, context on, selector closed | **4** | 5 |
| Emergency, handoffs on, selector closed | **6** | — |
| Emergency, explicit profile | **6** | 3 † |
| Emergency card **render** | **0** | 0 |
| Emergency selector **opened** | **4** | n/a (new) |
| Admission page, flags off | **0** | 0 |
| Admission, context on, unlinked | **10** | — |
| Admission, explicit profile | **3** | 2 † |
| Admission card **render** | **0** | 0 |
| Admission selector **opened** | **2** | n/a (new) |
| Consultation, handoffs off | **0** | 0 |
| Consultation, handoffs on, unlinked | **5** | 8 |
| Consultation, handoffs on, linked | **9** | 3 † |
| Consultation modal **render** | **0** | 0 |
| Maternity Labor escalation, off | **0** | n/a (new) |
| Maternity Labor escalation, on | **2** | n/a (new) |
| Maternity escalation panel **render** | **0** | n/a (new) |
| Maternity Postnatal escalation, on | **4** | n/a (new) |

† The 14R.5 figures were taken before the action layer existed. The increase is the *existing-record* lookups the UI now needs to render "Open Existing …" honestly — the open Admission Request, the active Labor Episode, the linked Emergency Case — plus the bounded ward list, and only when the corresponding action could actually be shown. Each is one bounded query, none is an N+1, and none runs on a flag-off surface.

**Every modal partial and every card render costs 0 queries.** Candidates are never loaded at page render (asserted). Each workspace builds one view model per request and reuses it for triggers, modals and cards — there is no per-button lookup. No persistent cross-request caching.

## 13. Tests and baseline comparison

| Suite | Result | Baseline | Verdict |
|---|---|---|---|
| **MaternityHandoffUiPhase14R5_1Test** (new) | **26 passed** | — | ✅ |
| **MaternityHandoffModalIntegrityPhase14R5_1Test** (new) | **9 passed** | — | ✅ |
| **MaternityHandoffSelectorsPhase14R5_1Test** (new) | **9 passed** | — | ✅ |
| 14R.5 handoff suites (4) + 14R.2–14R.4.1 suites (7) | **215 passed**, 1 skipped | 171 + 44 | ✅ |
| Admission foundation/bed/nursing/discharge + Maternity 8–12 | 73 passed, **2 failed** | 2 pre-existing | ✅ unchanged |
| All Emergency suites | **91 passed** | — | ✅ |
| `tests/Feature/Consultations` | 230 passed, **22 failed** | 22 pre-existing | ✅ **exactly unchanged** |

The two failures are the documented pre-existing `AdmissionNursingCarePhase6Test > admission show renders…` and `AntenatalCarePhase9Test > anc history detail…`, proven pre-existing in 14R.5 by stashing every change.

**Phase 14R.5.1 introduced zero new failures.** `composer test:wide` was not run.

**One existing test was updated, not weakened:** `ConsultationGynaecologyMaternityPilotPhase14R4_1Test::renderCard()` now passes `gynaecologyMaternityActions`, because the card's contract changed — its triggers come from typed actions, exactly as the real workspace supplies them. The alternative (falling back to a hard-coded trigger when no action is passed) would have reintroduced the dangling-target defect this phase exists to remove.

## 14. Files changed

**New**
```
app/Data/Maternity/MaternityHandoffActionViewModel.php
app/Services/Maternity/Context/MaternityHandoffActionFactory.php
app/Services/Consultation/Maternity/ConsultationMaternityModalPresenter.php
app/Services/Maternity/Handoffs/MaternityEmergencyHandoffPresenter.php
app/Http/Controllers/Admin/Maternity/MaternityContextCandidateController.php
resources/views/maternity/partials/handoff-modal.blade.php
resources/views/maternity/partials/handoff-triggers.blade.php
resources/views/maternity/partials/handoff-scripts.blade.php
resources/views/maternity/partials/emergency-handoff-panel.blade.php
resources/views/maternity/partials/handoff-fields/*.blade.php   (13 partials)
tests/Feature/MaternityHandoffUiPhase14R5_1Test.php
tests/Feature/MaternityHandoffModalIntegrityPhase14R5_1Test.php
tests/Feature/MaternityHandoffSelectorsPhase14R5_1Test.php
docs/maternity/OBGYN_MATERNITY_HANDOFF_UI_PHASE_14R_5_1_REPORT.md
```

**Modified**
```
app/Data/Maternity/OperationalMaternityViewModel.php            — typed handoffActions
app/Services/Consultation/Maternity/ConsultationMaternityHandoffPresenter.php
app/Services/Emergency/Maternity/EmergencyMaternityWorkspaceService.php
app/Services/Admissions/Maternity/AdmissionMaternityWorkspaceService.php
app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php
app/Http/Controllers/Admin/Maternity/{LaborEpisode,DeliveryRecord,PostnatalCase}Controller.php
routes/web.php                                                   — 5 candidate routes
resources/views/{emergency,admissions}/partials/maternity-context-card.blade.php
resources/views/consultations/partials/maternity/{handoff-actions,context-panel,gynaecology-context-card}.blade.php
resources/views/consultations/show.blade.php
resources/views/maternity/{labor/show,labor/deliveries/show,postnatal/show}.blade.php
lang/{en,fr}/maternity_handoffs.php                              — 112 → 197 keys
tests/Feature/ConsultationGynaecologyMaternityPilotPhase14R4_1Test.php
```

## 15. Localisation

EN/FR extended in strict recursive parity: **197 / 197 keys, verified programmatically.** Covers states, modal chrome, priorities, arrival modes, escalation, the Gynaecology fallback and every button label.

## 16. Activity logging

No new events. Modal opens and profile searches are **not** logged. The 14R.5 identifier-only events are unchanged, and nothing added here logs a clinical summary, diagnosis narrative, ANC measurement, labor observation, newborn measurement, postnatal observation, or sexual/menstrual history.

## 17. Existing workflows protected

Unchanged and verified green: all Emergency suites (91), admission request/bed/nursing/discharge, Maternity phases 8–12, the full Consultation suite at its exact baseline, the 14R.2 bridge, the 14R.3/14R.3.1 Obstetrics workspace and the 14R.4/14R.4.1 Gynaecology work. No invoice item is created by any handoff and maternity billing posting remains disabled.

## 18. Known risks

| # | Risk |
|---|---|
| R1 | Selectors need select2 + jQuery on the page. Both are bundled globally, and the initialiser no-ops when absent — the form still submits, it just falls back to a plain `<select>` with no remote search. |
| R2 | The Admission "context on, unlinked" path costs 10 queries — the resolver's fallback chain, not new UI work. Worth re-measuring under pilot load. |
| R3 | The K1 fallback links to the visit page as the entry point to standard consultation creation. If an installation renames that route the presenter degrades to no link (it never throws), and the reason text still shows. |
| R4 | Modal bodies are rendered inline with the page. For a workspace with many actions this adds markup weight; a lazy-load endpoint is a possible future optimisation, not a correctness issue. |

## 19. Rollout and rollback

Unchanged from 14R.5. All four flags stay `false` after deployment; pilot order remains Admission read-only context → Emergency read-only context → Consultation handoff actions → Emergency/Maternity handoffs last. **Mutation modals are not exposed in read-only context mode** — enforced in code and tested.

Before enabling mutation actions: verify permissions, open/active duplicate rules, modal validation and return context, absence of billing posting, that no modal target is missing (the integrity suite proves this), and the manual scenarios.

Rollback: disable Emergency handoffs → Consultation handoffs → Emergency context → Admission context → `config:clear`. Action controls and modal bodies disappear; links, Admission Requests, Admissions, Emergency Cases and Maternity records remain valid; no destructive migration rollback; no historical link deleted.

## 20. K2 closure

**K2 is CLOSED.** Every criterion is met and enforced by an automated check:

- every visible trigger has a real target — `MaternityHandoffModalIntegrityPhase14R5_1Test` fails the build if not;
- every target form posts to its real server action — asserted per module;
- permission- and lifecycle-disabled actions never render as executable — asserted in both permission directions and for completed consultations, disposed emergency cases and discharged admissions;
- candidate queries are lazy and bounded — asserted;
- scenarios A–F are covered by the automated suites above.

## 21. Next phase

**14R.6 — Advisory Readiness, Consultation Summary Projection, Immutable Completion Snapshot, Historical Reconciliation Dry Run and Billing De-duplication Policy.**
