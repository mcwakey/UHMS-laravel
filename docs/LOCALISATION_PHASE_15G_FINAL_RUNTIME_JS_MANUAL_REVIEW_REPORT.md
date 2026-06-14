# UHMS Localisation — Phase 15G: Final Runtime JS / Manual-Review Burn-Down Report

**Date:** 2026-06-14
**Branch:** beta-x
**Scope:** Drive the remaining **34** active runtime localisation candidates to **0** — by translating the genuinely user-facing strings and proving (with evidence) that the rest are non-user-facing / dormant template / protocol identifiers, reclassifying them accurately in the scanner.

---

## 1. Summary

**Active runtime candidates: 34 → 0.**

Of the 34 remaining candidates:
- **5 were genuinely active and user-facing** → translated (insurance add-modal script via a page-level i18n map; claims clinical-mirror static labels).
- **29 were provably false positives** → translated where trivially safe is moot; instead proven non-active with hard evidence (`php artisan route:list`) and reclassified into the correct scanner buckets:
  - **26** = dormant purchased-template demo-widget HTML in `resources/js/script.js` (23) and `resources/js/doctors.js` (3).
  - **3** = `HL7 v2.x` protocol-standard identifier (×3, analyzers index) — a non-translatable technical name.

Additionally, **class-A service outputs were reviewed**; the two clearest user-facing cases were translated (the prompt's own example, plus the financial P&L / balance-sheet labels), and the rest documented with classification.

All language files lint clean, EN/FR parity passes, the compiled view cache builds with 0 PHP errors, `route:list` is OK, and `git diff --check` is clean. No clinical/financial data exposed; no business logic, permission, or workflow change.

---

## 2. Active runtime candidate count

| | Active runtime candidates |
|---|---:|
| **Phase 15G start** | **34** |
| **After this pass** | **0** |

**Program-to-date: 519 (15B start) → 0 (−519, 100% of active runtime candidates resolved).**

---

## 3. Per-item disposition

### 3.1 `patients/partials/insurance-add-modal-scripts.blade.php` — TRANSLATED (active)
Blade JS partial for the patient insurance add-modal (active, route-linked). Added a page-level `I18N` map (`@json(__('patients.*'))`) at the top of the IIFE and replaced **all 10** English literals (the 4 flagged + 6 siblings): Select Provider/Tier, Loading providers/tiers, No providers/tiers, Failed to load providers/tiers, Select type/provider first, default-tier hint. All keys already existed in `patients.php` (from Phase 15B) except `default_tier_used` (added). Insurance/sponsor logic untouched; provider/tier/policy/member values not translated.

### 3.2 `claims/partials/clinical-mirror.blade.php` — TRANSLATED (active, static labels only)
Confidentiality-sensitive claim-preparation mirror. The flagged `Department:` and surrounding strings are **static UI labels**, not clinical data — the clinical content lives in `$recordMirror[...]` / `$entry['content']` / `$entry['entered_by']` variables, which were **left untouched**. Translated: card title, snapshot description, Department/Main Doctor/Contributors labels, the 10 section labels (Complaints…Notes/Summary), and the Original author / Source Pattern meta labels. New keys added to `claims.php`; reused `common.department`, `consultations.main_doctor_label`, `consultations.unassigned`. No additional clinical data exposed; claim/NHIS logic unchanged (NHIS not hardcoded).

### 3.3 `resources/js/script.js` (23) + `resources/js/doctors.js` (3) — FALSE POSITIVE (dormant template), reclassified
**Evidence:** these candidates are HTML-fragment strings (`<option>Fever</option>`, `<option>Welcome Email</option>`, `<option>General Consultation</option>`, `<option>Moring</option>` [vendor typo], schedule/education demo labels) built inside jQuery handlers (`.add-diagnosis`, `.add-advices`, `.add-invest`, `.add-reminder`, `.add-schedule-btn`, `.add-education-btn`). Those trigger classes exist **only** in dormant purchased-admin-template demo views — `appointment-consultations`, `online-consultations`, `appointment-settings`, `email-templates-settings`, `add-doctor`, `edit-doctor`, `doctors-schedules`. **Those views are defined only in `routes/web.php.bak`; `php artisan route:list` returns no active route for any of them**, so the markup is never produced on a live route. Per the phase rule, these are documented demo/template false positives. The scanner was updated to bucket inline HTML-fragment strings in these two vendor-bundle files as `demo_template_candidates` (see §5). No JS behaviour changed.

### 3.4 `admin/analyzers/index.blade.php` (3) — FALSE POSITIVE (protocol identifier), reclassified
The 3 residual candidates are all `HL7 v2.x` — the name of an international healthcare-messaging **standard** (HL7 version 2.x), shown in the analyzer protocol dropdown alongside `ASTM E1394`. Per UHMS rules ("do not translate protocol names"; cf. `TCP/IP`, `ASTM E1394` kept in Phase 15B), this is a non-translatable technical identifier. Added `HL7 v2.x` and `ASTM E1394` to the scanner's exact-match known-false-positive list (alongside `UHMS`, `N/A`, `GHS`, clinical units). Analyzer connection/mapping/diagnostics logic untouched.

---

## 4. Class-A service candidate review (§9)

Reviewed the priority services. The class-A candidates sit in the **`service_title_manual_review`** bucket — **not** active runtime — so they did not block the 0 target.

| Service | Disposition |
|---|---|
| `ConsultationNextPatientService` | **A — translated.** `'message' => __('consultations.payment_ready')` (the prompt's exact example). Confirmed user-facing gate message. |
| `FinancialReportService` | **A — translated.** P&L section labels (Revenue, COGS, Operating/Administrative Expenses, Finance Costs) and balance-sheet groups (Assets, Liabilities, Equity) wrapped `'label' => __('accounting.*')`. Pure display labels; array keys (`revenue`, `cogs`, …) unchanged, so no lookup/semantic change. |
| `StatisticsService` | **A (safe) — documented, deferred.** Catalogue `'title'` labels (Hospital Activity, Diagnosis Statistics, …) are display-only and safe to wrap `__('statistics.*')`; deferred to keep this final change set reviewable. Recommended next. |
| `ProcedureReportService` | **B/E — left unchanged.** Strings like `Requested`/`Accepted`/`Billed` mirror procedure **status** values derived from enums/stored workflow state; translating risks divergence from canonical status. Status display is already localised at the enum/`x-status-badge` layer. |
| `PatientMergePreviewService` | **A (safe) but bulk — documented, deferred.** 35 entity-count labels (Visits, Medical records, Vitals, …); safe but large, deferred to avoid a sprawling final change. |
| `ReportService` | **D — false positive.** Candidates are **SQL expressions** (`AVG(julianday(actual_discharge_date) - julianday(admission_date))`, `AVG(DATEDIFF(...))`). Must not be translated. |

Stored event titles, audit/journal descriptions, SQL, and canonical workflow titles were **not** altered.

---

## 5. Scanner change (accurate reclassification, with evidence)

`scripts/localisation-audit.php` was updated so the audit accurately reflects the proven dispositions (the scanner is our own tool and already maintains `known_false_positive` and `demo_template` buckets):

1. **Vendor-bundle demo markup:** string candidates in `resources/js/script.js` / `resources/js/doctors.js` whose context is an inline HTML fragment (`<option>`, `<label>`, `<h6>`, `<th>`, `<span>`, `<div>`) are bucketed as `demo_template_candidates`. Justified + evidenced in code comments (dormant demo widgets; trigger views unrouted — only in `web.php.bak`).
2. **Protocol identifiers:** `HL7 v2.x` and `ASTM E1394` added to the exact-match known-false-positive list.

Both rules are narrow (two specific files / two specific strings) and do not affect any active Blade view. The scanner still lints clean (`php -l`).

---

## 6. Language files changed

| File | Change |
|---|---|
| `lang/{en,fr}/patients.php` | +1 (`default_tier_used`) |
| `lang/{en,fr}/claims.php` | +16 (clinical-mirror static labels) |
| `lang/{en,fr}/accounting.php` | +7 (financial-report P&L/balance labels); removed 2 self-introduced duplicate keys, renamed `select_type`→`select_type_ph` to resolve a Phase-15F collision |
| `lang/{en,fr}/consultations.php` | +1 (`payment_ready`) |

## 7. Code files changed (services)

- `app/Services/ConsultationNextPatientService.php` — `'message' => __('consultations.payment_ready')`.
- `app/Services/FinancialReportService.php` — 8 `'label' => __('accounting.*')` (P&L + balance sheet).

## 8. Verification results

- **Active runtime candidates: 0** (scanner).
- **EN/FR parity:** PASS (recursive flattened-key diff — 0 gaps). The Phase-15F accounting duplicate keys were resolved.
- **PHP lint:** all `lang/{en,fr}/*.php` clean; `scripts/localisation-audit.php` clean; edited services clean.
- **View cache:** `php artisan view:cache` builds; compiled views lint clean.
- **`php artisan route:list`** → OK.
- **`git diff --check`** → clean.

### Permissions / confidentiality / financial security
No `@can`/policy/permission changes. No clinical free-text, diagnosis, medicine/test names, patient values, insurance/sponsor/policy/member numbers translated or exposed. No claim-generation, insurance/NHIS, journal/posting, or analyzer-integration logic changed. No business logic moved into Blade. No new localisation framework or frontend package introduced.

---

## 9. Manual French verification checklist

- [ ] **Patient insurance add modal**: type→provider→tier cascade — placeholders, loading, error, no-providers/tiers, default-tier hint all in French; provider/tier names (data) unchanged.
- [ ] **Claims clinical mirror**: card title, description, Department/Main Doctor/Contributors, section headings, author/source-pattern meta in French; clinical content unchanged.
- [ ] **Consultation next-patient**: "Payment ready" gate message in French.
- [ ] **Financial reports**: income statement & balance sheet section labels in French; figures unchanged.
- [ ] **Analyzers index**: `HL7 v2.x` / `ASTM E1394` remain as-is (protocol names).
- [ ] **script.js / doctors.js**: confirm no regression in active modals, DataTables, Select2, date pickers, charts, forms, dashboard widgets (the touched demo widgets are not on active routes).

---

## 10. Status & recommendation

**Acceptance criteria met:** active runtime candidate count is **0**; every previously-remaining candidate is either translated or proven non-user-facing/false-positive with evidence; active JS strings use a Blade i18n map; insurance modal, analyzer residuals, and clinical mirror handled; class-A services reviewed with confirmed user-facing labels translated and stored/SQL semantics preserved.

**Optional follow-ups (not active-runtime debt):**
1. Translate `StatisticsService` catalogue titles and `PatientMergePreviewService` entity labels (safe display labels) for fuller report localisation.
2. The `service_title_manual_review` bucket (385) and `demo_template` (15,827) are non-active; periodically re-confirm none become route-linked.

> The UHMS active-runtime localisation burn-down (Phases 15B → 15G) is complete: **519 → 0**.
