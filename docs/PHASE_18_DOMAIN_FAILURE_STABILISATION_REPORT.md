# UHMS Phase 18 — Domain Failure Stabilisation: MAR, Lab Billing Gate, Visit Pathway

**Date:** 2026-06-15
**Branch:** beta-x
**Scope:** Fix the 5 remaining Phase 17 domain/feature failures (MAR ×3, lab billing display gate, visit-status emergency-admission transition) without weakening permissions, hiding bugs, or touching unrelated workflows. Preserve the localisation lock.

---

## 1. Summary

All 5 targeted failures are resolved. The work split into **one genuine production bug** (MAR time columns), **one genuine state-machine gap** (visit pathway), and **three test expectations that were demonstrably outdated** because the asserted markup is delivered inside the Inertia legacy-bridge JSON envelope (where `"` → `\"` and `/` → `\/`), not because the production views were wrong.

A localisation regression (1 active runtime candidate) introduced **outside** the 5 failures — by an externally-added edit-criterion form in the investigation-catalogue view — surfaced during verification and was fixed (the localisation test was failing, so the lock rules permitted the fix).

---

## 2. Starting full-suite result

```
Phase 17 baseline:  535 passing / 5 failing
Pre-Phase-18 (after the configurable-overall-result feature): 547 passing / 5 failing
```

The 5 failing tests were the same Phase 17 set.

---

## 3. Focused failures fixed — root cause + fix

### Part A — `LabWorkflowTest › billed request opens from results route` (test expectation; production correct)

**Root cause:** The results-entry route (`LabResultController@showRequest`) already passes `$showBillingAcceptance = false`, so the "Accept & Bill" card is **not rendered** for an already-billed request (verified: `acceptSelectedForm`, `acceptSelectedBtn`, `name="item_ids[]"` all absent from the response). The old assertion `assertDontSee('Bill Selected')` failed only because the localisation i18n bundle embedded on every page serialises the `lab.bill_selected` translation value (`"Bill Selected"`) into the page JSON — a translation string, not a visible billing action.

**Fix (test):** Assert on the actual rendered control (`id="acceptSelectedForm"`, `name="item_ids[]"`) instead of the bare translated label. No production change; the billing gate was already correct.

### Part B — `MedicationAdministrationWorkflowTest › admission mar chart renders dose-cell modal trigger` (test expectation; production correct)

**Root cause:** The dose-cell modal trigger `data-bs-target="#mar-dose-{id}"` **is** rendered for actionable (DUE) cells (the `id="mar-dose-{id}"` administer-modal is also emitted, proving `is_actionable` is correctly true). On a full-page load that markup lives inside the Inertia `data-page` JSON envelope, where the quotes are escaped (`data-bs-target=\"#mar-dose-1\"`), so the literal-quote assertion failed.

**Fix (test):** Assert the trigger against the app's real raw-HTML chart render — the `X-Mar-Partial: chart` path the chart-refresh actually uses (and which an existing test already exercises) — where the markup is unescaped. Page-chrome assertions (`Print MAR`, patient header) stay on the full-page response.

### Part C — `MedicationAdministrationWorkflowTest › prn medications render PRN/SOS section` (test expectation; production correct)

**Root cause:** The PRN/SOS section renders correctly (`MarChartService` builds `prn_medications`; the view emits the heading, instructions and "Administer PRN" button). The heading "PRN / SOS Medications" contains a `/`, which `json_encode` escapes to `\/` in the full-page Inertia envelope (`PRN \/ SOS Medications`), so the literal-slash assertion failed.

**Fix (test):** Assert the PRN section against the raw-HTML `X-Mar-Partial` render, where the heading/instructions/button appear verbatim.

### Part D — `MedicationAdministrationWorkflowTest › mar chart service returns time_columns` (genuine production bug)

**Root cause:** `MarChartService::timeColumns()` derived columns **only** from generated schedule rows. An order whose schedules have not yet been generated (e.g. a freshly created order viewed on its start day) produced an empty `time_columns`, so the day grid showed "no fixed doses".

**Fix (production — `app/Services/MarChartService.php`):** `timeColumns()` now unions the actual schedule times with **planned dose times derived from each fixed-schedule order's frequency** for the selected day (new `plannedDoseTimesForDay()` honouring `is_stat`, `interval_hours`, `default_times`, and the order's `start_at`/`end_at` window). PRN orders are excluded; days outside an order's window still yield no columns. No test date hard-coding; real schedule grouping unchanged.

### Part E — `VisitStatusPatientPathwayWorkflowTest › emergency bed billing allows Admit Patient → Admitted` (genuine state-machine gap)

**Root cause:** During emergency-disposition admission the visit is in `ADMITTING` ("Admit Patient"). `VisitStatus::allowedTransitions()` mapped `ADMITTING => [CONSULTING, CANCELLED]`, omitting the natural completion `ADMITTING → ADMITTED`, so `AdmissionService::admit()` → `VisitStatusService::setAdmitted()` threw `InvalidArgumentException: Cannot transition from Admit Patient to Admitted`.

**Fix (production — `app/Enums/VisitStatus.php`):** Added `ADMITTED` to `ADMITTING`'s allowed transitions: `ADMITTING => [CONSULTING, ADMITTED, CANCELLED]`. This is the canonical happy path (in-progress admission → admitted). It does not bypass `VisitStatusService`, does not loosen any other state, and does not introduce emergency/NHIS-specific or hard-coded-ID logic.

---

## 4. Files changed

**Production (2):**
- `app/Services/MarChartService.php` — `timeColumns()` unions planned frequency-derived day columns; new private `plannedDoseTimesForDay()`. (Part D)
- `app/Enums/VisitStatus.php` — `ADMITTING` may transition to `ADMITTED`. (Part E)

**Localisation regression fix (3, lock-restoring):**
- `resources/views/admin/investigation-catalogue/show.blade.php` — both header `<option>`s use `__('investigations.no_header')` (an externally-added edit-criterion form had a hard-coded "No header" option flagged as an active runtime candidate).
- `lang/en/investigations.php`, `lang/fr/investigations.php` — added `no_header` key (EN/FR parity).

**Tests (2):**
- `tests/Feature/LabWorkflowTest.php` — Part A assertion targets the actual accept-bill control.
- `tests/Feature/MedicationAdministrationWorkflowTest.php` — Parts B & C assert MAR markup against the raw-HTML `X-Mar-Partial` chart render.

No seeders, factories, migrations, JS, or middleware changed. No `npm run build` needed (no frontend asset change).

---

## 5. Language keys added

`investigations.no_header` (EN `— No header —`, FR `— Aucun en-tête —`) — EN/FR parity maintained.

---

## 6. Verification results

| Check | Result |
|---|---|
| `LabWorkflowTest` | 7 passed |
| `MedicationAdministrationWorkflowTest` | 21 passed |
| `VisitStatusPatientPathwayWorkflowTest` | 6 passed |
| `tests/Feature/Localization` | 12 passed |
| `php scripts/localisation-audit.php` | **Active runtime candidates: 0** |
| `php artisan route:list` | OK |
| `php artisan view:cache` | Blade templates cached successfully |
| `php -l` (changed PHP + lang) | clean |
| `git diff --check` | clean (only pre-existing `prompt.md` trailing-whitespace in the user-managed spec; CRLF normalisation warnings) |
| **Full suite** | _see §7_ |

---

## 7. Full-suite final result

```
Tests: 571 passed, 0 failed (2129 assertions)
```

All 5 targeted Phase 17 failures now pass; **0 failures remain**. The total (571) is higher than Phase 17's 540 because of the 12 tests added by the prior configurable-overall-result feature plus additional test files present in the working tree this session; per the phase instruction ("If the total number of tests changes, report the exact final count"), the authoritative final count is **571 passing / 0 failing**.

---

## 8. Remaining failures

_None expected._ (Confirmed against the authoritative full run — see §7.)

---

## 9. Known risks

- **MAR planned time columns (Part D):** the grid now shows a day's planned dose slots even before schedule rows exist. This is display-only — it does not create schedules, administrations, stock movements, or alter dose semantics. Cells without a backing schedule render as empty (`-`).
- **`ADMITTING → ADMITTED` (Part E):** a deliberate, scoped state-machine addition. All other transitions are unchanged; invalid transitions remain blocked by `VisitStatusService`/`canTransitionTo()`.
- **Parts A/B/C are test refinements**, not production changes. The underlying production views were verified correct (markup present, only JSON-escaped in the full-page Inertia envelope). The refined assertions verify the same rendered elements against the app's real render paths.

---

## 10. Safety confirmations

No permissions/policies/gates/middleware weakened. No clinical/financial/stock-cost visibility changed. `ActivityLogService`, `VisitStatusService`, lab billing services and MAR services are all still used (none bypassed). No business logic moved into Blade. No NHIS-only logic. No audit events removed. Localisation lock preserved (active runtime candidates 0; 12 localisation tests pass; EN/FR parity).

---

## 11. Next recommended phase

- Build the Investigation Results Statistics report UI on top of `InvestigationResultSummaryService` (carried over from the configurable-overall-result feature).
- Consider a small test helper/assertion that decodes the Inertia `data-page` payload so future full-page view tests can assert quote/slash-bearing markup without depending on the partial path.
- Wire `tests/Feature/Localization` + the full suite into CI as required gates.
