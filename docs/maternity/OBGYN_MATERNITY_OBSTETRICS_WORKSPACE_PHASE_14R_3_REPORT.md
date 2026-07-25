# Phase 14R.3 — Obstetrics Stage-Aware Workspace (Implementation Report)

**Status:** ✅ Implemented, **dark by default** (both feature flags default `false`).
**Update — Phase 14R.3.1:** K1, K2 and K3 are now **CLOSED**. The ribbon/panel are wired into the real consultation page, clinical mutations now require an editable consultation, and query counts were measured. See `OBGYN_MATERNITY_OBSTETRICS_PILOT_PHASE_14R_3_1_REPORT.md`.
**Companions:** `OBGYN_MATERNITY_BRIDGE_PHASE_14R_2_REPORT.md`, `OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md`, `OBGYN_MATERNITY_INTEGRATION_PLAN.md`

---

## 1. What was implemented

The first clinician-facing reconciliation phase: the Obstetrics consultation workspace can now see the longitudinal maternity record, act on it through the existing maternity services, and — when explicitly enabled — stop new duplicate maternity writes.

- Two independent, reversible feature flags (both default off).
- `dating_method` on `pregnancy_profiles` + `PregnancyDatingMethod` enum.
- `ObstetricConsultationContextService` + `ObstetricWorkspaceViewModel` (one memoised resolution per request; components never query).
- `ConsultationMaternitySpecialtyWriteGuard` — **server-side**, field-level, wired into the real specialty-entry write path.
- `ConsultationMaternityContextController` + 7 routes: link, confirm, relink, unlink, create-profile, record-ANC, start-labor.
- Maternity context ribbon + stage-aware Maternity Context panel (projection + explicit action).
- 3 additive action permissions, each requiring the underlying maternity permission.
- EN/FR localisation (parity verified 113/113 keys).
- 22 passing feature tests.

## 2. Pre-change audit (required by the phase brief)

| Question | Finding |
|---|---|
| Specialty-entry write path | `ConsultationSpecialtyEntryController::store()` → validates via `ConsultationSpecialtySectionSchema::rulesFor()` → `sanitizedEntry()` → `ConsultationSpecialtyEntryService::create/updateEntry()`. **The guard hooks in immediately after `sanitizedEntry()`**, before persistence. |
| Active specialty profile | `ConsultationSpecialtyProfileResolver::resolve()` (route mapping → doctor preference → user dept → dept mapping → dept-type → existing entries → fallback). |
| Section ordering/presentation | `ConsultationSpecialtyLayoutService::buildLayout()`, labels via `ConsultationSpecialtySectionAliasService`. |
| Encounter key | `visit_consultation_routes.id` (`VisitConsultationRoute`) — unchanged from 14R.2. |
| Route/URL prefix | Existing `admin.consultations.*` convention reused; **no route renamed, no second Obstetrics page created**. |

## 3. Files changed

**New**
```
app/Enums/PregnancyDatingMethod.php
app/Data/Consultation/Maternity/ObstetricWorkspaceViewModel.php
app/Services/Consultation/Maternity/ObstetricConsultationContextService.php
app/Services/Consultation/Maternity/ConsultationMaternitySpecialtyWriteGuard.php
app/Http/Controllers/Doctor/Consultations/ConsultationMaternityContextController.php
database/migrations/2026_07_25_000003_add_dating_method_to_pregnancy_profiles_table.php
database/migrations/2026_07_25_000004_seed_consultation_maternity_action_permissions.php
resources/views/consultations/partials/maternity/context-ribbon.blade.php
resources/views/consultations/partials/maternity/context-panel.blade.php
tests/Feature/ConsultationObstetricsMaternityWorkspacePhase14R3Test.php
docs/maternity/OBGYN_MATERNITY_OBSTETRICS_WORKSPACE_PHASE_14R_3_REPORT.md
```

**Modified (additive)**
```
config/consultation.php                     — maternity_context flags
app/Models/PregnancyProfile.php             — dating_method fillable + cast
app/Http/Controllers/.../ConsultationSpecialtyEntryController.php — guard call
routes/web.php                              — 7 maternity-context routes + import
database/seeders/RoleSeeder.php             — 3 action permissions
lang/{en,fr}/consultation_maternity.php     — 14R.3 keys
```

## 4. Feature flags and rollout modes

```php
'maternity_context' => [
    'obstetrics_workspace_enabled'  => env('CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED', false),
    'obstetrics_write_guard_enabled'=> env('CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED', false),
],
```

| Mode | Workspace | Guard | Behaviour |
|---|---|---|---|
| **Disabled** (default) | false | false | Identical to today. Ribbon/panel render nothing; **the resolver is never called**, so there is no hot-path cost. |
| **Pilot** | true | false | Ribbon + projections render. **All existing specialty fields stay editable.** Safe clinician-review mode. |
| **Guarded** | true | true | Ribbon + projections, and maternity-owned fields are blocked server-side **only when an explicit valid profile link exists**. |

`guardEnabled()` returns `workspaceEnabled() && guardFlag` — **the guard cannot activate without the workspace flag**, enforced in code and covered by a test.

## 5. Pregnancy dating method

Additive nullable `pregnancy_profiles.dating_method`, cast to `PregnancyDatingMethod` (`lmp`, `early_ultrasound`, `late_ultrasound`, `assisted_reproduction`, `clinical_estimate`, `unknown`). Existing profiles remain valid and are **not backfilled**. `PregnancyProfileService::normalise()` passes the value through untouched, so create/update accept it with no service change. LMP/EDD/GA calculation is unchanged.

## 6. Context service and view model

`ObstetricConsultationContextService::build()` returns an `ObstetricWorkspaceViewModel` carrying flag state, resolver status/source, explicit-vs-inferred, pregnancy/ANC/labor/delivery/newborn/postnatal/admission projections, available actions, the write policy, warnings, candidate profiles and the return URL.

**Performance contract:**
- `context()` memoises per consultation id → **one resolution per request** (tested).
- When the workspace flag is off, `build()` returns early **without calling the resolver at all**.
- All projection values are assembled here; **Blade components perform no queries** (verified by inspection — the components only read the view model).

## 7. Resolver-state UI behaviour

| State | Ribbon | Actions | Guard |
|---|---|---|---|
| **Explicit** | Full ribbon, "Explicitly linked" | relink / unlink / ANC / labor per permission | may apply |
| **Inferred** | "Suggested Maternity Context" + source badge + **Confirm and Link** | ANC/labor **blocked** until confirmed | **never** |
| **Ambiguous** | Warning only | Candidate selector (status, LMP, EDD, GA, created) | **never** |
| **None** | Nothing | Discreet, permission-gated "Link or Create Pregnancy Profile" | n/a |
| **Invalid** | Red warning | **All mutations disabled**; no silent fallthrough | **never** |

## 8. Actions (all explicit, all dual-permission)

| Action | Bridge permission | Also requires | Notes |
|---|---|---|---|
| Link | `…link` | — | same-patient profiles only |
| Confirm | `…link` | — | link_role `reviewed` |
| Relink | `…link` | — | **reason required**, old row preserved |
| Unlink | `…unlink` | — | **reason required**, never deletes |
| Create profile | `…create_profile` | `maternity.pregnancy.create` | `PregnancyProfileService` + link as `created`, **transactional** |
| Record ANC | `…record_anc` | `maternity.anc.record` | `AntenatalVisitService`, **exactly one** `AntenatalVisit`, linked as `created` |
| Start labor | `…start_labor` | `maternity.labor.start` | reuses an active episode; otherwise `LaborEpisodeService` + link as `created` |

ANC and labor **require a confirmed explicit profile link** — inferred context returns 422.

> **Design note:** these actions deliberately do **not** use `consultationMutationContext()`. That guard requires an *editable* (started/unpaused/uncompleted) session because it protects clinical entry writes. A maternity link is a bridge record, and 14R.2 requires links to remain valid on completed consultations — requiring an editable session would break review/historical linking. Bridge + maternity permissions still gate every action.

## 9. Field-level write-guard matrix

Active only when: Obstetrics profile **and** workspace flag **and** guard flag **and** an explicit valid pregnancy-profile link.

| Section | Blocked (maternity-owned) | Still writable (consultation-owned) |
|---|---|---|
| `obstetric_history` | gravida, para, abortions, living_children, previous_c_section → maps to `previous_caesarean` | **previous_complications** |
| `lmp_edd_gestational_age` | lmp, edd, gestational_age_weeks, gestational_age_days, dating_method | — |
| `antenatal_vitals` | blood_pressure, weight, temperature, pulse, urine_protein, urine_glucose | — |
| `fetal_assessment` | fundal_height, fetal_heart_rate, fetal_movement, presentation | **lie** (ANC has no `lie` column; ownership unresolved) |
| `risk_assessment` | risk_level, risk_factors | **action_plan** |
| `current_pregnancy`, `birth_plan` | **not converted** — order sets still patch them (14R.4) | all |

Blocked fields are **rejected with a localised validation error**, never silently dropped; consultation-owned siblings in a mixed section still save. Historical entries are never modified or deleted (tested).

## 10. Permissions

Added: `consultation.maternity_context.create_profile`, `.record_anc`, `.start_labor` (retaining 14R.2's view/link/unlink). Admin/Super Admin get all. Other roles receive an action **only** if they already hold both `consultation.maternity_context.link` and the specific maternity permission it wraps. **The bridge never grants a maternity operation the user otherwise lacks** — verified by two tests that assert 403 when only the bridge half is held.

## 11. Localisation & activity logging

EN/FR extended in strict parity (**113 keys each, verified programmatically**): ribbon, panel, dating methods, actions, messages, write-guard messages, warnings.

Orchestration logs added: `PREGNANCY_PROFILE_CREATED_FROM_CONSULTATION`, `ANC_VISIT_RECORDED_FROM_CONSULTATION`, `LABOR_EPISODE_STARTED_FROM_CONSULTATION` — metadata is **identifiers only** (route/profile/ANC/labor/visit ids, actor). No clinical measurements or notes are logged.

## 12. Tests and checks

**New suite: 22 passed, 1 skipped** (the skip is `gynecology` profile absent in the test env — guarded by `markTestSkipped`).

Covers: all three flag modes · guard-never-without-workspace · none/inferred/ambiguous/invalid states · confirm creates explicit link · link/patient-mismatch/unlink-reason · dual-permission enforcement (both directions) · profile created + linked as `created` · **ANC creates exactly one `AntenatalVisit` and zero `antenatal_vitals`/`fetal_assessment`/`risk_assessment` specialty entries** · inferred cannot record ANC · labor creates one episode and reuses an active one · full field-level guard matrix incl. mixed sections · guard inert without explicit link / for non-Obstetrics / when disabled · historical entries preserved · context resolved once per request.

**Regression vs. documented baselines:**

| Suite | Result | Baseline | Verdict |
|---|---|---|---|
| 14R.3 (new) | 22 passed, 1 skipped | — | ✅ |
| 14R.2 bridge + Maternity Foundation + ANC + Labor/Delivery + Postnatal | 47 passed, **1 failed** | 1 pre-existing (`AntenatalCarePhase9Test`) | ✅ unchanged |
| `tests/Feature/Consultations` | 230 passed, **22 failed** | 22 pre-existing | ✅ **exactly unchanged** |

**Phase 14R.3 introduced zero new failures.**

Also run: `php -l` on all changed PHP/lang files (clean) · EN/FR parity script · `php artisan route:list --name=consultation` / `--name=maternity` · `view:clear` · `config:clear` · `git diff --check -- . ':!docs/prompt.md'` (clean). **`composer test:wide` was not run.**

## 13. Existing workflows protected

Unchanged: complaints, HOPC, examination, diagnoses, plan, investigations, prescriptions, procedures, tasks, notes, consultation completion, readiness rules, final summary, consultation billing, **the Gynaecology workspace**, and every non-Obstetrics specialty profile. No readiness blocker, no summary projection, no completion snapshot was added.

**Billing untouched:** `billing.maternity_billing.enabled` still defaults false; `MaternityBillingPostingService::postForSource()` still returns `STATUS_POSTING_NOT_IMPLEMENTED`; linking/creating/recording-ANC/starting-labor create **no invoice item**; no billing card was added to the doctor workspace.

## 14. Known risks

| # | Risk | Mitigation |
|---|---|---|
| K1 | ✅ **CLOSED in 14R.3.1** — wired into `HandlesConsultationWorkspace` + included in `consultations/show.blade.php`. ~~Ribbon/panel are built but not yet included.~~ | Deliberate: the components are dark-by-default and verified to render empty when disabled. Wiring them into `consultations/show.blade.php` should happen with the pilot rollout so the include point can be reviewed alongside real clinician feedback. |
| K2 | ✅ **MEASURED in 14R.3.1** — off/non-Obstetrics = 0 queries; explicit = 2; no-context reduced 15 → 9 via a no-profile fast path. |
| K3 | Guard relies on the resolver reporting `explicit`. | Covered by tests for inferred/ambiguous/invalid; all three leave the guard inert. |
| K4 | `fetal_assessment.lie` stays consultation-owned. | Intentional — ANC has no `lie` column; ownership deferred rather than silently adding it. |
| K5 | ✅ **CLOSED in 14R.3.1** — split into context-link actions (skip the guard, by design) vs clinical-mutation actions (now require an editable consultation). |

## 15. Intentionally deferred

Gynaecology workspace changes and explicit LMP adoption (**14R.4**) · order-set retargeting R5 (**14R.4**) · `current_pregnancy` / `birth_plan` conversion (**14R.4**) · admission/emergency/labor/delivery/postnatal handoffs (**14R.5**) · readiness warnings, summary projection, immutable completion snapshot R6, historical reconciliation, billing de-duplication (**14R.6**) · manual test data + wide regression (**14R.7**).

## 16. Rollback

1. Set `CONSULTATION_OBSTETRIC_MATERNITY_WRITE_GUARD_ENABLED=false` — maternity fields become editable again immediately.
2. If needed, set `CONSULTATION_OBSTETRIC_MATERNITY_WORKSPACE_ENABLED=false` — ribbon/panel disappear; the resolver stops being called.

No destructive migration rollback is required. Consultation fields and specialty entries remain intact, maternity records remain intact, and bridge links remain as auditable history. The `dating_method` column is additive and nullable.

**Before enabling the guard in any environment:** measure existing maternity-owned specialty entries, review/accept them as preserved legacy data, have clinicians review pilot mode, assign the maternity permissions, and manually test the ANC + labor actions.

## 17. Next phase

**14R.4 — Gynaecology separation and explicit pregnancy transition**, which also retargets the order-set `patch_specialty_entry` actions (R5) that currently block converting `current_pregnancy` and `birth_plan`.
