# Phase 14R.4 — Gynaecology Separation, Explicit Pregnancy Transition & Order-Set Retargeting (Report)

**Status:** ✅ Implemented. **Decision R5 is now closed.**
**Update — Phase 14R.4.1:** R1 and R2 are now **CLOSED** — the Gynaecology card is wired into the real consultation page and `maternity_context_action` has a typed clinician-facing presenter. See `OBGYN_MATERNITY_GYNAECOLOGY_PILOT_PHASE_14R_4_1_REPORT.md`. Dark by default (all four flags `false`).
**Companions:** `OBGYN_MATERNITY_OBSTETRICS_PILOT_PHASE_14R_3_1_REPORT.md`, `OBGYN_MATERNITY_SOURCE_OF_TRUTH_MATRIX.md`

---

## 1. What was implemented

- Two **independent** Gynaecology feature flags (separate from the Obstetrics pair).
- `ConsultationMaternityContextResolver::resolveExplicitOnly()` — Gynaecology never infers context.
- `GynaecologyConsultationContextService` + `GynaecologyWorkspaceViewModel` (small card, not a stage-aware panel).
- Explicit one-way **LMP adoption** with a full conflict policy.
- Gynaecology `obstetric_history` ownership when a profile is explicitly linked.
- Completed **Obstetrics `current_pregnancy` and `birth_plan`** conversion.
- **Runtime write-path hardening** — the guard moved to the service boundary, closing the order-set bypass.
- Order-set **retargeting**: read-only audit command + idempotent seeder.
- `adopt_lmp` permission, EN/FR localisation, 31 new tests.

## 2. Audit findings (before implementation)

### 2.1 Every runtime specialty-entry write path

| Call site | Before | Now |
|---|---|---|
| `ConsultationSpecialtyEntryController::store()` (form POST) | guarded (14R.3) | guarded |
| **`ConsultationSpecialtyOrderSetService::applySpecialtyEntryPatch()` → `upsertEntry()`** | **UNGUARDED — real bypass** | **guarded at service boundary** |
| `ConsultationSpecialtyEntryService::createEntry()` | unguarded | **guarded** |
| `ConsultationSpecialtyEntryService::upsertEntry()` | unguarded | **guarded** |
| Direct `ConsultationSpecialtyEntry::create()` outside the service | none found | n/a |
| Quick actions / favourites / templates | route through the same service | inherit the guard |

**Architecture chosen:** a single chokepoint — `ConsultationSpecialtyEntryService::guardMaternityOwnedFields()` — invoked by `createEntry()` and `upsertEntry()`. Every sanctioned runtime path now passes through it, so a stale or administrator-authored order set cannot write a maternity-owned field. Seed-time fixtures are unaffected (the guard is inert while flags are off).

### 2.2 Order-set inventory

Ran `php artisan consultation:obgyn-order-set-audit` (read-only). It found **exactly two** maternity-shaped patch items, both in `obstetrics_antenatal_booking`:

| Set | Item | Section | Field | Classification |
|---|---|---|---|---|
| `obstetrics_antenatal_booking` | 50 | `current_pregnancy` | `pregnancy_confirmed` | safe to retarget |
| `obstetrics_antenatal_booking` | 51 | `birth_plan` | `danger_signs_counseling` | safe to retarget |

No others exist — the assumption that "the two known items are the only bypasses" was **verified, not assumed**.

**Also confirmed:** the Gynaecology order set patches `menstrual_history.bleeding_pattern`, which is **not** maternity-owned and is correctly left alone.

**Structural finding:** order-set *items* carry no stable `code` (only the set does), so retargeting matches on **exact section + merge payload**. Anything that differs is treated as administrator-authored and left untouched.

### 2.3 `sexual_sti_history.pregnancy_test`

Free-text `text(100)`, **not** an enum — so no positive value could be assumed. Detection is tolerant (matches `pos*`, `+`, `yes`, `reactive`) and explicitly excludes `neg*` / `not …`. It only ever surfaces a non-blocking affordance.

## 3. Gynaecology feature flags

```php
'gynaecology_context_enabled'     => env('CONSULTATION_GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED', false),
'gynaecology_write_guard_enabled' => env('CONSULTATION_GYNAECOLOGY_MATERNITY_WRITE_GUARD_ENABLED', false),
```

`gynaecologyGuardEnabled()` = `gynaecologyContextEnabled() && guardFlag` — the guard **cannot** activate while the context flag is off (tested). Fully independent of the Obstetrics pair.

| Mode | Behaviour |
|---|---|
| Both off (default) | Gynaecology exactly as today. No card, no pregnancy query, no resolver call. |
| Context on, guard off | Small card for an **explicitly linked** profile. All fields stay editable. Pilot. |
| Both on | Card + `obstetric_history` maternity-owned fields become read-only projections. |

## 4. Explicit-only context resolution

`resolveExplicitOnly()` reuses the **same** explicit-link chain validation as `resolve()` — no duplicated ownership or consistency logic — and simply omits the fallbacks. Gynaecology therefore **never** acquires context from the same visit, the same admission, a single active profile, patient sex, diagnosis, complaint or a pregnancy test. Candidate profiles are queried only when the clinician opens the selector.

## 5. LMP adoption (decision R2)

One-way, explicit, clinician-confirmed. Source is read **server-side** from the persisted `menstrual_history.lmp` entry — a client-submitted value is never trusted (tested), and an unsaved form value cannot be adopted.

| Profile state | Outcome |
|---|---|
| LMP null | **Adopt** → sets `last_menstrual_period` + `dating_method = lmp`; EDD/GA recalculated by the existing maternity service |
| LMP identical | **Idempotent** success, no write |
| LMP different | **Blocked** — never overwritten; clinician directed to Maternity |
| Dated by early/late ultrasound or assisted reproduction | **Blocked** — scan/ART dating is never replaced |
| No saved consultation LMP | **Rejected** with a localised error |

**Invariants (all tested):** the consultation LMP entry is never modified; the profile LMP is never synced back; adoption never happens merely because a profile was linked; adoption requires an **editable** consultation (completed → 422) and **both** permissions.

## 6. Ownership conversions

**Gynaecology `obstetric_history`** — blocked only when linked **and** guarded: `gravida`, `para`, `abortions`, `living_children`, `previous_c_section` (→ projected from `previous_caesarean`). `previous_complications` stays writable. `menstrual_history.lmp` is **never** guarded. `contraceptive_history`, `sexual_sti_history`, `pelvic_examination`, `breast_examination` are untouched.

**Obstetrics `current_pregnancy`** — now guarded: `pregnancy_confirmed` (derived from the link; the equivalent action is create/link profile), `danger_signs` (projected from latest ANC). Still writable: `current_complaints`, `high_risk_notes`, `booking_status` (labelled encounter/booking context, not longitudinal truth).

**Obstetrics `birth_plan`** — now guarded: `danger_signs_counseling`, `next_visit_date` (both projected from latest ANC; equivalent mutation is Record ANC). Still writable: `planned_place`, `delivery_plan` (consultation intent; `labor_episodes.delivery_mode_planned` is shown separately and never auto-synced either direction).

No existing entry is deleted or rewritten in any case.

## 7. Order-set retargeting (R5)

**Audit command** — `php artisan consultation:obgyn-order-set-audit [--json]`. Read-only (tested: makes no writes). Reports set/item/profile/apply-mode/section/fields and classifies each as *safe to retarget · already retargeted · custom-needs-review · unsupported*.

**Seeder** — `ConsultationSpecialtyOrderSetMaternityReconciliationSeeder`:
- retargets `patch_specialty_entry` → `maternity_context_action` with `create_or_link_pregnancy_profile` / `record_anc_counselling`;
- **only when the payload exactly matches** the previously-seeded definition;
- an admin-modified item is **left untouched** and reported (tested);
- **idempotent** — a second run is a no-op (tested);
- retains the original definition under `payload.retargeted_from` for audit;
- **never touches historical applications** — only item definitions change.

The retargeted action creates no specialty entry, no pregnancy profile and no ANC visit; it presents an explicit clinician CTA requiring normal permissions.

**Defence in depth:** even if a stale order set survived, the service-boundary guard now returns a structured `blocked` result rather than writing.

## 8. Permissions, localisation, logging

`consultation.maternity_context.adopt_lmp` added (also requires `maternity.pregnancy.update`). Granted to Admin/Super Admin, and to clinical roles only where they already hold both the bridge link ability and the underlying maternity update permission. **Gynaecology never exposes ANC or labor actions** even to users holding those maternity permissions (tested).

EN/FR extended in strict recursive parity — **169 / 169 keys, verified programmatically**.

New identifier-only events: `GYNAECOLOGY_LMP_ADOPTED_FOR_PREGNANCY_DATING`, `OBGYN_ORDER_SET_MATERNITY_ACTION_RETARGETED`. No sexual/STI/menstrual/counselling content is logged.

## 9. Tests and baselines

| Suite | Result | Baseline | Verdict |
|---|---|---|---|
| **Gynaecology 14R.4 (new)** | **21 passed** | — | ✅ |
| **Order-set retargeting 14R.4 (new)** | **10 passed** | — | ✅ |
| Bridge + 14R.3 + 14R.3.1 + maternity group | 110 passed, **1 failed** | 1 pre-existing ANC | ✅ unchanged |
| `tests/Feature/Consultations` | 230 passed, **22 failed** | 22 pre-existing | ✅ **exactly unchanged** |

**Phase 14R.4 introduced zero new failures.** `composer test:wide` was not run.

## 10. Known risks and deferred work

| # | Item |
|---|---|
| R1 | ✅ **CLOSED in 14R.4.1.** ~~The Gynaecology context card partial and its workspace wiring are not yet included in the real Gynaecology view~~ — the service, view model, guard, actions and tests are complete and dark-by-default. Wiring should land with the pilot rollout (mirrors how 14R.3→14R.3.1 sequenced Obstetrics). |
| R2 | ✅ **CLOSED in 14R.4.1** — typed presenter with a closed action set, six display states and fail-closed behaviour. |
| R3 | LMP conflict resolution has **no override path** by design — deferred as the spec requires. |
| R4 | `booking_status`, `planned_place`, `delivery_plan` remain consultation-owned pending a separate schema decision. |
| Deferred | Summary projection + immutable completion snapshot (R6) → **14R.6**; historical reconciliation → **14R.6**; billing posting stays disabled. |

## 11. Rollout and rollback

**Rollout:** deploy with all flags false → run `consultation:obgyn-order-set-audit` → run the reconciliation seeder → enable `GYNAECOLOGY_MATERNITY_CONTEXT_ENABLED` for pilot → enable the write guard only after clinician review.

**Rollback:** disable the Gynaecology write guard, then the context flag, then `config:clear`. Obstetrics flags are unaffected. Order-set retargeting is data-level and reversible from `payload.retargeted_from`; historical applications and specialty entries are untouched throughout.

## 12. Next phase

**14R.5 — Admission, Emergency, Labor, Delivery and Postnatal handoffs**, plus the UI wiring noted in R1/R2.
