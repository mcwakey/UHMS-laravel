# Phase 14R.4.1 — Gynaecology Pilot Wiring & Maternity Action Rendering (Report)

**Status:** ✅ Implemented. **R1 and R2 are closed.** Dark by default (all four flags `false`).
**Companions:** `OBGYN_MATERNITY_GYNAECOLOGY_PHASE_14R_4_REPORT.md`, `OBGYN_MATERNITY_OBSTETRICS_PILOT_PHASE_14R_3_1_REPORT.md`

---

## 1. Summary

| Gap | Resolution |
|---|---|
| **R1** — Gynaecology card + view model not reachable on the real page | View model built in `HandlesConsultationWorkspace`; card partial created and included in `consultations/show.blade.php`. **Closed.** |
| **R2** — `maternity_context_action` had no clinician-facing rendering | Typed `ConsultationMaternityOrderSetActionPresenter` with a closed action set, six display states, and fail-closed behaviour. **Closed.** |

## 2. Include point and placement

Mirrors the Obstetrics 14R.3.1 pattern exactly — same composer, no second page, no new route:

```php
$gynaecologyContext = $selectedRoute
    ? app(GynaecologyConsultationContextService::class)->build(
        $selectedRoute, $specialtyContext->profile, $request->user(), url()->current())
    : GynaecologyWorkspaceViewModel::disabled();
```

Passed to the view as `gynaecologyContext` and shared by the card, the obstetric-history projections and the order-set CTAs. **Maternity context is never resolved in Blade.**

The card is included after the Obstetrics context panel, so it sits within the specialty area, after common session context and before the summary/completion area. It is a small card — **not** a full-width ribbon — and replaces no banner.

## 3. Rendering behaviour

| State | Card |
|---|---|
| Flag off / non-Gynaecology | **Renders nothing**, zero queries |
| Enabled, unlinked | Only the discreet, permission-gated **“Start or Link Pregnancy Workflow”**. **No GA/EDD/risk is shown** — there is no linked profile to show it from. |
| Enabled, explicitly linked | Status, LMP, EDD, GA, dating method, latest ANC, link to the maternity profile, link/relink/unlink, LMP adoption **only when eligible**, and the explicit statement **“This consultation remains Gynaecology.”** |
| Positive pregnancy test, unlinked | Non-blocking notice stating **no profile was created automatically**, plus the explicit affordance |

**Never rendered in Gynaecology:** Record ANC, Start Labor, delivery/newborn/postnatal mutations — verified by a test using a user who holds *every* ANC and labor permission.

**LMP adoption states** render their reason rather than a dead button: conflict, scan/ART dating locked, and already-matches each show a badge instead of an action.

## 4. Rollout markers

Gated by `@can('consultation.maternity_context.view')`, so they never leak to clinicians outside the pilot:

- **Pilot** — “Pregnancy Context pilot mode / The linked Pregnancy Profile is visible; legacy Gynaecology obstetric history remains editable.”
- **Guarded** — “The Pregnancy Profile is the source of truth for linked obstetric-history fields.”

## 5. Order-set action rendering (R2)

`ConsultationMaternityOrderSetActionPresenter` is **presentation-only** — it never writes, and the real action always goes back through the normal controller with its permission and mutation-boundary checks.

**Closed action set:** `create_or_link_pregnancy_profile`, `record_anc_counselling`. An unknown key renders `unsupported` and is never executed.

**States:** `action_required` · `satisfied` · `unavailable` · `blocked` · `unsupported` · `legacy_patch`.

| Situation | Result |
|---|---|
| create/link, unlinked | `action_required`, executable; Gynaecology gets *“Start or Link Pregnancy Workflow”*, Obstetrics gets *“Create or Link Pregnancy Profile”* |
| create/link, already linked | `satisfied`, **not** executable — no second profile, no `pregnancy_confirmed` write |
| record ANC in **Gynaecology** | `unavailable` — never executed, even with full ANC permissions |
| record ANC, no explicit profile | falls back to *link a profile first* |
| record ANC, completed consultation | `blocked` — *start a new active consultation or use Maternity* |
| record ANC, missing either permission | `blocked` |
| Historical `patch_specialty_entry` item | `legacy_patch` — old applications are never re-labelled as the new action |
| **Integration flag off** | `unavailable` — **does not revert to patching** and does not disappear |

That last row is the important safety property: **the feature flag controls visibility, not the safety semantics** of a retargeted action.

## 6. Query counts (measured)

| Fixture | Queries |
|---|---|
| A — Gynaecology, flags off | **0** |
| B — enabled, no explicit link | **9** |
| C — enabled, explicit link | **4** |
| D — explicit link + latest ANC | **4** (no N+1) |
| F — non-Gynaecology, flag on globally | **0** |

Context resolves **once per request** (memoised). The Blade partial is asserted to issue **zero** queries — enforced by the test harness wrapping every render in a query-log assertion.

**Optimisation applied:** the saved-LMP lookup is only meaningful for adoption, which requires an explicit link, so it is now skipped entirely when unlinked (10 → 9).

The unlinked path (9) is bounded and explicit-only — well below the six-table fallback chain the Obstetrics resolver can perform (15 before its own optimisation). Candidate profiles are still queried only when the selector is opened.

## 7. Files changed

**New**
```
resources/views/consultations/partials/maternity/gynaecology-context-card.blade.php
app/Services/Consultation/Maternity/ConsultationMaternityOrderSetActionPresenter.php
tests/Feature/ConsultationGynaecologyMaternityPilotPhase14R4_1Test.php
tests/Feature/ConsultationObgynMaternityActionRenderingPhase14R4_1Test.php
docs/maternity/OBGYN_MATERNITY_GYNAECOLOGY_PILOT_PHASE_14R_4_1_REPORT.md
```

**Modified**
```
app/Http/Controllers/.../Concerns/HandlesConsultationWorkspace.php  — build + pass gynaecologyContext
app/Services/Consultation/Maternity/GynaecologyConsultationContextService.php — skip LMP lookup when unlinked
resources/views/consultations/show.blade.php                        — include the card
lang/{en,fr}/consultation_maternity.php                             — presenter + marker keys
```

## 8. Tests and baselines

| Suite | Result | Baseline | Verdict |
|---|---|---|---|
| **Gynaecology pilot 14R.4.1 (new)** | **10 passed** | — | ✅ |
| **Action rendering 14R.4.1 (new)** | **12 passed** | — | ✅ |
| All bridge + O&G suites combined | **107 passed**, 1 skipped | — | ✅ |
| Maternity group | 25 passed, **1 failed** | 1 pre-existing ANC | ✅ unchanged |
| `tests/Feature/Consultations` | 230 passed, **22 failed** | 22 pre-existing | ✅ **exactly unchanged** |

**Phase 14R.4.1 introduced zero new failures.** EN/FR recursive parity: **182 / 182**. `composer test:wide` not run.

Two of my own test bugs surfaced and were fixed rather than worked around: Blade `@can` resolves the *authenticated* user (not the one passed to `build()`), so marker tests need `actingAs`; and the initial query budget was too tight for a legitimate cost, which led to the real optimisation above.

## 9. Existing workflows protected

Unchanged: Gynaecology readiness/completion/summary, all other Gynaecology sections, Obstetrics behaviour, the `bleeding_pattern` Gynaecology order set (correctly not maternity-owned), orders/prescriptions/procedures/tasks, and maternity billing (still preview-only; no invoice item is created by any action or render).

## 10. Known risks

| # | Risk |
|---|---|
| K1 | The card's modals (`gynaeLinkPregnancyModal`, `gynaeAdoptLmpModal`, `gynaeRelinkModal`, `gynaeUnlinkModal`) are referenced by the buttons but the modal bodies are not yet authored — the buttons are inert until they are. The underlying routes, permissions and server logic are complete and tested. This is the natural next slice of UI work. |
| K2 | Presenter output is not yet rendered by an order-set results partial — the typed presenter and its tests are complete, so this is a template-only step. |
| K3 | The unlinked path costs 9 queries. Acceptable and bounded, but worth re-measuring under pilot load. |

## 11. Rollout and rollback

**Rollout:** deploy dark → enable `CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED` for pilot → after clinician review, enable `..._WRITE_GUARD_ENABLED`.
**Rollback:** disable the guard, then the context flag, then `config:clear`. Obstetrics flags are independent and unaffected; links, historical entries and order-set applications are untouched.

## 12. Next phase

**14R.5 — Admission, Emergency, Labor, Delivery and Postnatal handoffs**, plus the modal/results-partial UI noted in K1/K2.
