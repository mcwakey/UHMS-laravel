# UHMS — Configurable Investigation Overall Result Type

**Date:** 2026-06-14
**Branch:** beta-x
**Scope:** Make the investigation "overall result" configurable per investigation/test type (free text / numeric / true-false / positive-negative), respected at result entry and in reporting. No localisation regressions; no permission weakening.

---

## 1. Summary

Each investigation service (catalogue entry) can now declare **how its overall result is captured and reported**:

| Type | Entry input | Canonical storage | Display |
|---|---|---|---|
| `free_text` (default) | textarea | `overall_result_text` / legacy `result_value` | as written |
| `numeric` | number + unit | `overall_result_numeric` (+ `overall_result_unit`) | `5.6 mmol/L` |
| `boolean` | True/False select | `overall_result_boolean` | translated `True`/`False` or custom label |
| `positive_negative` | Positive/Negative select | `overall_result_outcome` | translated `Positive`/`Negative` or custom label |

Database values are always **canonical/internal** (`5.6`, `true`/`false`, `positive`/`negative`); only displayed labels are translated. The new typed columns are **additive** — legacy free-text results continue to display unchanged through a backward-compatible fallback.

The configuration is **per service** (`service_catalog`), set from the Investigation Catalogue → Configure screen. Result entry adapts the input automatically. A summary service tallies/aggregates results for reporting.

---

## 2. Database changes

One additive, idempotent migration (`2026_06_14_000001_add_overall_result_type_to_catalogue_and_results.php`). Column existence is guarded with `getColumnListing()` (no `Schema::hasColumn()`, no `->json()` — MariaDB 10.1 dev DB constraint). Verified on sqlite (`migrate`) and designed for MariaDB/MySQL.

**`service_catalog` (configuration):**
`overall_result_type` (string, default `free_text`), `overall_result_unit`, `overall_result_min_value` (decimal), `overall_result_max_value` (decimal), `overall_result_positive_label`, `overall_result_negative_label`, `overall_result_true_label`, `overall_result_false_label`.

**`lab_results` (canonical, reportable storage):**
`overall_result_type` (snapshot at entry, nullable for legacy), `overall_result_text`, `overall_result_numeric` (decimal), `overall_result_boolean`, `overall_result_outcome`, `overall_result_unit`.

Legacy `result_value` / `result_text` columns are **untouched**.

---

## 3. Model changes

- **`ServiceCatalog`** — constants `OVERALL_RESULT_FREE_TEXT|NUMERIC|BOOLEAN|POSITIVE_NEGATIVE`; helpers `overallResultTypes()`, `overallResultType()` (defaults legacy/unset → `free_text`), `overallResultTypeLabel()`, `usesNumeric/Boolean/PositiveNegative/FreeTextOverallResult()`, and label resolvers `positiveLabel()/negativeLabel()/trueLabel()/falseLabel()` (custom value or translated default). Fields added to `$fillable` + casts; `overall_result_type` added to the activity-log `logOnly` set so config changes are audited.
- **`LabResult`** — new fields in `$fillable` + casts; `hasTypedOverallResult()`; `overallResultDisplay(?ServiceCatalog)` returns the translated, human-readable overall result with a legacy fallback to `result_value`/`result_text`.

---

## 4. UI changes

- **Admin catalogue config** (`admin/investigation-catalogue/show.blade.php`): a new "Overall Result Type" card with a select + conditional fields (numeric: unit/min/max; positive_negative: labels; boolean: labels). A small vanilla-JS toggle shows only the fields for the selected type. Bootstrap 5 only; no new frontend library.
- **Result entry** (`lab/process.blade.php` parameters modal): the free-text textarea is replaced by `lab/_overall_result_input.blade.php`, which renders the input dictated by the service's configured type. The result list cell now renders `overallResultDisplay()` (translated).

---

## 5. Result entry behavior & storage strategy

`LabResultController@store` reads `overall_result_type` and, for the typed variants, validates `overall_result_value` per type and maps it into canonical columns via `mapOverallResult()` (numeric → `overall_result_numeric` + unit; boolean → `overall_result_boolean`; positive_negative → `overall_result_outcome`). It also derives a backward-compatible `result_value` text. Free-text mirrors into `overall_result_text`. `LabService@enterResult` persists the canonical columns when present (legacy callers unaffected). Numeric aggregation is never mixed with text results.

---

## 6. Backward compatibility

- New columns are additive and nullable (config default `free_text`).
- `overallResultType()` treats any legacy/unset value as `free_text`.
- `overallResultDisplay()` falls back to `result_value` / `result_text` for rows with no canonical overall value — **old free-text results still display** (covered by a test).
- No columns renamed or dropped; no existing result data deleted.

---

## 7. Reporting / tally logic

`App\Services\InvestigationResultSummaryService::summarise(ServiceCatalog, ?from, ?to)` returns a type-specific summary reading the canonical columns:

- **positive_negative** → `positive_count`, `negative_count`, `total_tested`, `positive_rate`, `negative_rate`.
- **boolean** → `true_count`, `false_count`, `total_tested`, `true_rate`, `false_rate`.
- **numeric** → `count`, `average_value`, `minimum_value`, `maximum_value`, `unit`, and `normal_count`/`abnormal_count` when a min/max range is configured.
- **free_text** → `completed_count`.

Business logic lives in the service (not in Blade/controllers).

---

## 8. Permissions / security notes

- Catalogue configuration route is gated `can:lab.tests.manage` (+ `module:investigations`); the controller also re-checks the service belongs to an investigation-type department. Verified: a viewer without the permission gets **403** (test).
- Result entry remains gated `can:lab.results.create`; verification `can:lab.results.verify`. No gate/policy/middleware weakened.
- No clinical/financial data exposure introduced. NHIS untouched (not referenced). Products vs services unchanged.

---

## 9. Tests added

`tests/Feature/InvestigationOverallResultTypeTest.php` — **12 tests / 49 assertions**, all passing:
configure each type; reject invalid type & bad range (`max < min`); unauthorized cannot configure; numeric/boolean/positive_negative canonical storage + translated display; validation per configured type; legacy free-text still displays; positive/negative tally; true/false tally; numeric average/min/max + normal/abnormal; free-text completed count.

---

## 10. Commands run

```text
php artisan migrate (sqlite file)                      → new migration DONE; columns verified
php -l (all changed PHP + lang files)                  → clean
php artisan test tests/Feature/InvestigationOverallResultTypeTest → 12 passed
php artisan view:clear / view:cache                    → Blade compiles
php artisan route:list --name=overall-result           → route registered
php artisan test tests/Feature/Localization            → see §11
php scripts/localisation-audit.php                     → active runtime candidates: 0
php artisan test (full suite)                          → 547 passed, 5 failed (1981 assertions)
```

**Full suite:** 547 passed / 5 failed. The 12 new tests all pass; the 5 failures are the **pre-existing** Phase-17 domain failures (MAR ×3, lab `Bill Selected` display-gating, visit-status state machine) — unrelated to this feature and unchanged by it. No regressions introduced.

---

## 11. Localisation

EN/FR keys added with parity to `lang/{en,fr}/investigations.php` (type labels, config labels, normal range, tally metrics) and `lang/{en,fr}/lab.php` (`overall_result_label`). All displayed strings use `__()`; stored DB values stay canonical English. Localisation lock preserved: **active runtime candidates 0**, 12 localisation QA tests green.

---

## 12. Remaining risks

- The configurable overall result input is wired into the **parameters** result modal (lab tests, positive/negative, numeric, boolean). Rich-text (radiology narrative) and file-upload result types already capture their own conclusion and are intentionally out of scope for the typed overall input; their results display via the existing narrative/file path.
- A dedicated reporting **screen** that surfaces `InvestigationResultSummaryService` output is not yet built — the tally service + tests provide the logic and are ready to bind to a report view.

---

## 13. Next recommended phase

Build the Investigation Results Statistics report UI on top of `InvestigationResultSummaryService` (per-service tallies with date-range filters, EN/FR), and optionally extend the typed overall result to the rich-text/file result modals where a structured conclusion is also desired.
