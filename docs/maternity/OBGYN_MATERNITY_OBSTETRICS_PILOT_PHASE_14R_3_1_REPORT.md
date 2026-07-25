# Phase 14R.3.1 — Pilot Wiring & Clinical Mutation Boundary Hardening (Report)

**Status:** ✅ Implemented. **K1, K2 and K3 are closed.** Still dark by default (both flags `false`).
**Companions:** `OBGYN_MATERNITY_OBSTETRICS_WORKSPACE_PHASE_14R_3_REPORT.md`, `OBGYN_MATERNITY_BRIDGE_PHASE_14R_2_REPORT.md`

---

## 1. Summary

A narrow closure/hardening phase that made the Obstetrics maternity integration **actually reachable**, put the **correct mutation boundary** around clinical record creation, and **measured** the real query cost.

| Gap | Resolution |
|---|---|
| **K1** — ribbon/panel existed but were not on the real page | Wired into `HandlesConsultationWorkspace` + included in `consultations/show.blade.php`. **Closed.** |
| **K2** — all seven actions bypassed the consultation mutation guard | Split into **context-link** (allowed on completed) vs **clinical-mutation** (requires an active/editable consultation). **Closed.** |
| **K3** — query delta unmeasured | Measured across all five required fixtures; found and fixed a 15-query worst case. **Closed.** |

## 2. Include point and UI placement

**Data assembly:** `HandlesConsultationWorkspace` (the real consultation show-data composer, same place `specialtyLayout` / `specialtyContext` are built). The view model is built **once** and passed to the view as `maternityContext`; the ribbon and panel share that single instance. **Maternity context is never resolved inside Blade.**

```php
$maternityContext = $selectedRoute
    ? app(ObstetricConsultationContextService::class)->build($selectedRoute, $specialtyContext->profile, $request->user(), url()->current())
    : ObstetricWorkspaceViewModel::disabled();
```

**Placement in `consultations/show.blade.php`:**
- **Ribbon** — immediately after `session-context` (the patient/visit/specialty header), before the main specialty content.
- **Panel** — after `consultation-gating`, within the established workspace flow, before the summary/completion area.

It does **not** replace the patient banner, specialty banner, admission context, visit status or session controls; it does not create a second full-width page; no existing section was moved.

## 3. Mutation boundary (K2)

Two distinct guards in `ConsultationMaternityContextController`:

**`route()` — context-link actions** (`link`, `confirm`, `relink`, `unlink`)
Resolves the session **without** requiring editability. These maintain or correct the encounter's relationship to an existing longitudinal record, create no clinical data, and Phase 14R.2 requires bridge links to remain manageable after completion.

**`mutableRoute()` — clinical-mutation actions** (`create profile`, `record ANC`, `start labor`)
Delegates to the existing `consultationMutationContext()` — **the project's existing guard, reused rather than reimplemented, and not weakened.** It runs *before* any write, so a blocked attempt leaves no maternity record, no bridge link and no partial transaction.

### Completed-consultation action matrix

| Action | Active | Paused | Completed |
|---|---|---|---|
| View maternity context | allowed | allowed | allowed |
| Link existing profile | allowed | allowed | **allowed** |
| Confirm inferred profile | allowed | allowed | **allowed** |
| Relink profile | allowed *(reason)* | allowed *(reason)* | **allowed** *(reason)* |
| Unlink profile | allowed *(reason)* | allowed *(reason)* | **allowed** *(reason)* |
| Create pregnancy profile | allowed *(dual perms)* | per existing mutation policy | **blocked (422)** |
| Record ANC | allowed *(dual perms)* | per existing mutation policy | **blocked (422)** |
| Start labor | allowed *(dual perms)* | per existing mutation policy | **blocked (422)** |
| Open existing maternity record | allowed | allowed | allowed |

Paused/cancelled follow the project's existing consultation mutation policy — no separate rule was invented, and **no historical clinical-mutation override was added**.

> **Behavioural note discovered during testing:** an `ACTIVE` route with no `started_at` is treated as *not started* by the existing guard, so clinical mutations are correctly refused. Three Phase 14R.3 fixtures modelled exactly that and began failing when the boundary tightened — the **fixtures** were corrected (adding `started_at`), not the guard. This is the hardening working as intended.

## 4. Query counts (K3) — measured, not estimated

Measured in the test harness (`test_query_counts_across_all_measured_fixtures`, emitted to STDERR), one stable fixture per case:

| Fixture | Before | **After** |
|---|---|---|
| 1. Obstetrics, workspace flag **off** | 0 | **0** |
| 2. Obstetrics, enabled, **no context** | 15 | **9** |
| 3. Obstetrics, enabled, **inferred** profile | 14 | 15 |
| 4. Obstetrics, **explicit** link + ANC + labor | 2 | **2** |
| 5. **Non-Obstetrics**, flag enabled globally | 0 | **0** |

**Resolver invocations:** 0 when the flag is off · 0 for non-Obstetrics · exactly **1** per request when enabled (memoised; a second `build()` in the same request reuses it). Ribbon and panel issue **no** queries of their own.

**Optimisation applied.** The visit and admission fallbacks each scan six maternity tables. Every inferable context ultimately roots in a pregnancy profile belonging to *this* patient, so the resolver now short-circuits with one `exists()` check when the patient has **no** pregnancy profile — the overwhelmingly common case. That took the no-context path from **15 → 9** queries. The inferred path pays one extra query (14 → 15), which is the correct trade.

The explicit path is the cheapest (**2**) because an active link short-circuits every fallback — so the guarded production mode is also the fastest. No persistent cross-request caching was added.

## 5. Files changed

**New**
```
tests/Feature/ConsultationObstetricsMaternityPilotPhase14R3_1Test.php
docs/maternity/OBGYN_MATERNITY_OBSTETRICS_PILOT_PHASE_14R_3_1_REPORT.md
```

**Modified**
```
app/Http/Controllers/Doctor/Consultations/Concerns/HandlesConsultationWorkspace.php  — build + pass maternityContext
app/Http/Controllers/Doctor/Consultations/ConsultationMaternityContextController.php — route() vs mutableRoute()
app/Services/Consultation/Maternity/ConsultationMaternityContextResolver.php         — no-profile fast path
resources/views/consultations/show.blade.php                                          — include ribbon + panel
resources/views/consultations/partials/maternity/context-ribbon.blade.php             — rollout-mode marker
lang/{en,fr}/consultation_maternity.php                                               — rollout.* keys
tests/Feature/ConsultationObstetricsMaternityWorkspacePhase14R3Test.php               — fixture started_at
```

## 6. Rollout-mode marker

Rendered inside the ribbon and gated by `@can('consultation.maternity_context.view')`, so it is never shown to users who cannot see maternity context:

- **Pilot** (workspace on, guard off) — *“Maternity Context pilot mode — Source projections are visible; legacy Obstetrics fields remain editable.”*
- **Guarded** (both on) — *“Maternity is the source of truth for linked pregnancy fields.”*

Both localised EN/FR.

## 7. Localisation

`rollout.*` added in strict parity: pilot/guarded titles + hints, completed-consultation review, context-linking-available, open existing record, start new consultation, return to consultation, unsaved changes, blocked-completed, blocked-paused. **Recursive parity verified programmatically: EN 125 / FR 125, no mismatches.**

## 8. Tests and baseline comparison

| Suite | Result | Documented baseline | Verdict |
|---|---|---|---|
| **14R.3.1 (new)** | **10 passed** | — | ✅ |
| 14R.3 workspace | 22 passed, 1 skipped | 22 + 1 skip | ✅ unchanged |
| 14R.2 bridge | 22 passed | 22 | ✅ unchanged |
| Bridge + 14R.3 + 14R.3.1 combined | **54 passed**, 1 skipped | — | ✅ |
| Maternity group (Foundation/ANC/Labor/Postnatal + bridge + both O&G suites) | 78 passed, **1 failed** | 1 pre-existing ANC | ✅ unchanged |
| `tests/Feature/Consultations` | 230 passed, **22 failed** | 22 pre-existing | ✅ **exactly unchanged** |

**Phase 14R.3.1 introduced zero new failures.**

Also run: `php -l` on all changed PHP/Blade/lang files (clean) · EN/FR parity script · `route:list --name=consultation` / `--name=maternity` · `view:clear` · `config:clear` · `git diff --check -- . ':!docs/prompt.md'` (clean). **`composer test:wide` was not run.**

## 9. Existing workflows protected

Unchanged: complaints, HOPC, examination, diagnoses, plan, orders, prescriptions, procedures, tasks, notes, consultation completion, readiness, final summary, consultation billing, **Gynaecology**, all non-Obstetrics profiles, maternity pages. No route renamed. Billing remains preview-only (`enabled` defaults false, `postForSource` non-posting); no invoice item is created by any bridge action; no billing card added.

## 10. Known risks

| # | Risk | Mitigation |
|---|---|---|
| R1 | Ribbon/panel placement is now live in pilot mode and may crowd the workspace for some layouts. | Reversible by flag; manual pilot check B is required before wider rollout. |
| R2 | Inferred-context path costs 15 queries. | Acceptable: it only occurs for patients who *have* a pregnancy profile but no explicit link — and confirming the link drops it to 2. |
| R3 | Unsaved-data protection relies on the workspace's existing dirty-state behaviour; the maternity actions are separate POST forms. | No second JS framework was introduced. Actions preserve the originating consultation URL and return to it. Worth an explicit manual check during pilot. |
| R4 | Paused-consultation behaviour is inherited from the existing mutation policy rather than specified here. | Intentional — the brief requires following existing policy, not inventing one. |

## 11. Rollout and rollback

**Rollout**
1. Deploy with both flags `false` (no behaviour change).
2. Pilot: `WORKSPACE_ENABLED=true`, `WRITE_GUARD_ENABLED=false` → projections visible, fields still editable.
3. After clinician review **and** measuring existing maternity-owned specialty entries per environment: `WRITE_GUARD_ENABLED=true`.

**Rollback**
1. Disable the write guard. 2. Disable the workspace. 3. `php artisan config:clear`. 4. Confirm fields are editable again.
No destructive migration rollback required; links and historical entries remain intact and auditable.

## 12. Next phase

**14R.4 — Gynaecology Separation, Explicit Pregnancy Transition and Order-Set Retargeting (R5)**, which unblocks converting `current_pregnancy` and `birth_plan`.
