# UHMS Localisation — Phase 16: QA Gates & Regression Lock Report

**Date:** 2026-06-14
**Branch:** beta-x
**Scope:** Lock the completed localisation (Phases 15B→15G, active runtime candidates **0**) behind automated QA gates so future development cannot silently reintroduce untranslated active-runtime UI strings. No broad re-translation — tests + verification only.

---

## 1. Summary

Added a dedicated, fast-running localisation regression suite under `tests/Feature/Localization/` (5 files, **12 tests / 60 assertions, all passing**) plus the standard verification gates. The suite enforces EN/FR key parity, a zero-active-runtime scanner gate, French render smoke across 31 critical module pages, French validation attributes, and the JavaScript-i18n / false-positive-rule invariants from Phase 15G. No application/business logic, permissions, or Blade content were changed (one test-only change seeds roles).

**Starting active runtime candidates: 0 — confirmed and now gated.**

---

## 2. Tests added (`tests/Feature/Localization/`)

| File | What it guards | Result |
|---|---|---|
| `LanguageParityTest.php` | Recursive EN/FR key parity per file (keys only, not values) + identical file set per locale. Prints `missing key / locale / file`. | **PASS** (2 tests) |
| `ActiveRuntimeLocalizationAuditTest.php` | Runs `scripts/localisation-audit.php` via `Symfony\Process`; **fails if `Active runtime candidates > 0`**; surfaces demo/known-FP/service-review/lang-file counts for awareness. | **PASS** (1 test) |
| `FrenchRouteSmokeTest.php` | Seeds app roles, logs in a **Super Admin** (Gate::before bypass so permissions can't mask failures), forces French, GETs 31 curated module index pages + `/login`; asserts no `5xx` and that `200` responses carry `lang="fr"`. | **PASS** (2 tests) |
| `ValidationLocalizationTest.php` | French `validation.attributes` exist with expected French names; required-rule messages interpolate the French attribute (and don't leak the raw snake_case field). Covers auth/clinical/stock fields. | **PASS** (2 tests) |
| `JavaScriptLocalizationBridgeTest.php` | `window.UHMS_I18N` exposed in the global layout; insurance-modal script uses `@json(__())` and contains no raw English literals; `visits/create` JS strings translated; **Phase 15G scanner false-positive rules stay narrow** (HL7 v2.x / ASTM E1394 exact-match; the script.js/doctors.js rule is gated on inline HTML-fragment context, not the whole file). | **PASS** (5 tests) |

`php artisan test tests/Feature/Localization` → **Tests: 12 passed (60 assertions)**.

---

## 3. Commands run

```text
php artisan view:clear / config:clear / cache:clear      → OK
php artisan route:list                                   → OK
php artisan view:cache                                   → Blade templates cached (0 errors); then view:clear
for f in lang/{en,fr}/*.php; do php -l "$f"; done         → all clean
php -l scripts/localisation-audit.php                    → clean
php scripts/localisation-audit.php                        → Active runtime candidates: 0
git diff --check                                         → clean
php artisan test tests/Feature/Localization              → 12 passed (60 assertions)
```

### 3.1 Language parity result
**PASS** — EN/FR file sets identical; key structures match per file (0 gaps). (The Phase-15F accounting duplicate keys resolved in 15G stay resolved.)

### 3.2 Active runtime audit result
**Active runtime candidates: 0.** Non-blocking buckets (surfaced for awareness): Demo/template = 15,827 · Known false positives = 931 · Service-title manual-review = 385 · Language-file = 136.

### 3.3 French route smoke result
**PASS.** `/login` + 31 admin index pages (dashboard, patients, visits, consultations, appointments, billing invoices & payments, pharmacy dispensing & history, lab results & tests, investigations, wards, theatre & rooms, product-stock balances, store purchase-orders & suppliers, accounting & settings, accounts categories, reports, notifications, queue board, service-renderings, HR employees, blood bank, ICD codes, departments, analyzers, designations) all render `lang="fr"` with no `5xx`. Roles are seeded via `RoleSeeder` so controllers that reference `role('Doctor')` resolve on the empty test DB (this was the cause of two initial `500`s — an empty-data issue, **not** localisation).

### 3.4 Validation localisation result
**PASS.** `lang/fr/validation.php` `attributes` resolve to French names (e.g. `first_name`→*prénom*, `email`→*adresse e-mail*, `blood_pressure`→*tension artérielle*, `quantity`→*quantité*) and are interpolated into required-rule messages.

### 3.5 JavaScript bridge result
**PASS.** Global `window.UHMS_I18N` present before `script.js`; active Blade-embedded JS uses `@json(__())` / page-level I18N maps; the two vendor demo bundles (`script.js`, `doctors.js`) remain present and the narrow demo-fragment scanner rule is asserted to stay scoped.

---

## 4. False-positive protection (Phase 15G rules)

`JavaScriptLocalizationBridgeTest::test_scanner_false_positive_rules_remain_narrow` locks the 15G reclassifications so they can't be silently widened:
- exact-match known-FP list must still contain `'HL7 v2.x'` and `'ASTM E1394'` (protocol identifiers);
- the dormant-demo rule must stay scoped to `'resources/js/script.js', 'resources/js/doctors.js'` **and** gated on inline HTML-fragment context (`'<option'`, …) — never a blanket "all JS is demo".

If a rule is widened or these files become route-linked, this test (and the audit test) will force the change to be justified/wired through `window.UHMS_I18N` (documented in the 15G report §3.3).

---

## 5. Optional service-output review (§10) — DEFERRED (non-blocking)

The `service_title_manual_review` bucket (385) is **not** active-runtime debt. The two clearest user-facing services were already translated in Phase 15G (`ConsultationNextPatientService` message, `FinancialReportService` P&L/balance labels). The remaining priority optional candidates are **documented as deferred** to keep this QA phase free of behavioural changes:
- `StatisticsService` catalogue `'title'` labels — safe display-only; `'statistics.*'` keys can be added next.
- `PatientMergePreviewService` entity labels (≈35) — safe display-only; bulk, deferred.
- `ProcedureReportService` — status strings derived from enums/stored workflow; **leave** (localise at the enum/badge layer).
- `ReportService` — SQL expressions; **false positive, never translate**.

No stored event titles, audit/journal descriptions, SQL, or canonical workflow values were altered.

---

## 6. Files changed

- **Added:** `tests/Feature/Localization/{LanguageParity,ActiveRuntimeLocalizationAudit,FrenchRouteSmoke,ValidationLocalization,JavaScriptLocalizationBridge}Test.php`.
- **Added:** this report.
- No Blade, lang, service, route, or permission files changed in Phase 16. (The smoke test seeds `RoleSeeder` in-memory only.)

---

## 7. Manual French QA checklist (release-critical sample)

Switch app to French and confirm — title, breadcrumbs, headings, forms, placeholders, buttons, modals, alerts, validation errors, JS confirms/alerts, empty states, print/PDF labels; no clinical/financial data exposure; no permission regression:

- [ ] login · dashboard · patients · visits/create · consultation show
- [ ] pharmacy dispense · billing invoice show · payment page
- [ ] emergency show · wards/beds · theatre show
- [ ] stock balances · purchase orders · accounting settings
- [ ] reports hub · settings/profile · notifications · queue board

(The automated smoke test covers index-page render + `lang="fr"`; the above covers show/form/modal/PDF surfaces a smoke test can't safely assert on empty data.)

---

## 8. Known non-blocking localisation debt

1. `service_title_manual_review` (385) and `demo_template` (15,827) buckets — non-active; the audit test surfaces their counts so regressions stay visible.
2. Optional `StatisticsService` / `PatientMergePreviewService` display labels — safe to localise later.
3. Manual French show/PDF QA (§7) requires a human/browser pass — not automatable on empty data.

---

## 9. Acceptance criteria — status

| Criterion | Status |
|---|---|
| Active runtime candidates remain 0 | ✅ (audit test gates it) |
| EN/FR parity test passes | ✅ |
| Localisation audit test passes | ✅ |
| French route smoke passes / documents redirects | ✅ (31 pages + login) |
| Validation localisation tests pass | ✅ |
| JS localisation bridge checks pass | ✅ |
| False-positive rules documented & narrow | ✅ (test-locked) |
| PHP lint passes · view cache compiles | ✅ |
| No permission weakening · no clinical/financial exposure · no business logic in Blade | ✅ |
| Documentation report created | ✅ (this file) |

---

## 10. Recommendation for next phase

1. Wire the new `tests/Feature/Localization` suite into CI as a required gate (it runs in ~2 min standalone).
2. Optionally localise `StatisticsService` / `PatientMergePreviewService` display labels and extend `ValidationLocalizationTest`.
3. Perform the manual French show/PDF QA pass (§7) before release.

> The UHMS localisation program (Phases 15B→16) is complete and **locked**: active runtime candidates **519 → 0**, with automated regression gates preventing reintroduction.
