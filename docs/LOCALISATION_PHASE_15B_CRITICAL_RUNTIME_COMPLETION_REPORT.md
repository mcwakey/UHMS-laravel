# UHMS Localisation — Phase 15B: Critical Runtime Completion Report

**Date:** 2026-06-13
**Branch:** beta-x
**Scope:** Active-runtime candidate burn-down (consultations, auth/validation, analyzers, pharmacy) + critical Blade parse-error fix.

---

## 1. Summary

Phase 15B began with a **production-down parse error**: `admin/consultations/{id}/routes/{id}` returned HTTP 500 (`ParseError: Unclosed '[' on line 2891 does not match ')'`). Root cause was **Blade silently truncating a large inline `@json([...])` array literal** (PCRE recursion mis-match on a 2 900-line view), which dropped 7 of 10 array keys and emitted malformed PHP. This was fixed and six other views carrying the same latent pattern were hardened.

After restoring the page, the phase continued the active-runtime localisation burn-down per the Phase 15 worklist, prioritising consultations, auth validation, and the two highest-candidate clinical modules (lab analyzers, pharmacy dispensing). All added keys have full EN/FR parity, all language files lint clean, and all compiled views lint clean (0 PHP parse errors across the compiled view cache).

Per the phase instruction ("Do not declare complete based only on automated checks"), the remaining active-runtime worklist is **documented, not closed** — see §11.

---

## 2. Manually confirmed problem pages

- **`admin/consultations/{id}/routes/{id}` (consultations/show.blade.php)** — confirmed HTTP 500 before fix (reproduced via `php -l` on the compiled view), confirmed compiling cleanly after fix.
- **consultations/history.blade.php** — clinical visit-summary print document; section headers and labels verified rendering through `__()` keys.
- **admin/analyzers/{index,show,diagnostics}** — lab analyzer management pages; full UI surface translated.
- **pharmacy/dispense.blade.php** — pharmacy dispensing workflow; full UI surface translated.

---

## 3. Active runtime candidate count

| | Active runtime candidates |
|---|---:|
| **Before (Phase 15B baseline)** | **519** |
| **After** | **437** |
| **Net reduction** | **−82** |

Scanner: `php scripts/localisation-audit.php` (report regenerated at `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`).

---

## 4. Consultation pages fixed

- `resources/views/consultations/show.blade.php` — **critical parse-error fix** (converted the inline `@json([...])` i18n map to a `@php $consultationI18nData = [...] @endphp` variable + `@json($consultationI18nData)`). Restores the page and matches the project's documented JS-i18n convention.
- `resources/views/consultations/history.blade.php` — translated all 8 active-runtime candidates (section headers `Patient Information`, `Visit Information`, `Care Team & Contributors`, `Investigations`, `Procedures`; the `Main Doctor:` / `Result:` labels) plus the surrounding visible structural labels and empty-states so the French print is coherent.

---

## 5. Auth pages fixed

The active-runtime scanner reports **no remaining auth-page candidates** — `resources/views/auth/**` and the profile/settings pages were already covered in earlier phases (keys live in `lang/{en,fr}/common.php`, `auth.php`, `passwords.php`). Phase 15B's auth work was therefore on **validation attributes** (§9) rather than view markup.

---

## 6. Other modules fixed

- **Lab analyzers** (new `lang/{en,fr}/analyzers.php` namespace):
  - `resources/views/admin/analyzers/diagnostics.blade.php`
  - `resources/views/admin/analyzers/index.blade.php`
  - `resources/views/admin/analyzers/show.blade.php`
- **Pharmacy**:
  - `resources/views/pharmacy/dispense.blade.php`

Plus six views hardened against the `@json` truncation footgun (see §7).

---

## 7. Files changed

### Critical parse-error fix (inline `@json([...])` → `@php` variable + `@json($var)`)
- `resources/views/consultations/show.blade.php`
- `resources/views/admin/products/show.blade.php`
- `resources/views/admin/products/index.blade.php`
- `resources/views/appointments/create.blade.php`
- `resources/views/appointments/edit.blade.php`
- `resources/views/appointments/index.blade.php`
- `resources/views/patients/show.blade.php`

> All seven held large multi-line `@json([...])` literals at risk of the same PCRE-truncation defect. `consultations/show` was actively broken (500); the other six were latent.

### Localisation (view markup → `__()` keys)
- `resources/views/admin/analyzers/diagnostics.blade.php`
- `resources/views/admin/analyzers/index.blade.php`
- `resources/views/admin/analyzers/show.blade.php`
- `resources/views/pharmacy/dispense.blade.php`
- `resources/views/consultations/history.blade.php`

---

## 8. Language files changed

| File | Change |
|---|---|
| `lang/en/analyzers.php` | **new** — 82 keys |
| `lang/fr/analyzers.php` | **new** — 82 keys |
| `lang/en/pharmacy.php` | +34 dispense keys (30 → 64) |
| `lang/fr/pharmacy.php` | +34 dispense keys (30 → 64) |
| `lang/en/consultations.php` | +25 visit-history-print keys (99 → 124) |
| `lang/fr/consultations.php` | +25 visit-history-print keys (99 → 124) |
| `lang/en/validation.php` | +26 `attributes` (auth + consultation) |
| `lang/fr/validation.php` | +26 `attributes` (auth + consultation) |

Generic strings reused existing `lang/{en,fr}/common.php` keys (filter, clear, close, actions, status, view, edit, delete, activate/deactivate, cancel, patient, date, notes, name, phone, blood_group, department, visit_date, drug, optional) rather than duplicating them.

---

## 9. Validation attributes added

Added to the `attributes` array of `lang/{en,fr}/validation.php` (French errors now show French field names):

- **Auth/profile:** `password_confirmation`, `current_password`, `new_password`, `remember`, `avatar`, `profile_photo` (others — `name`, `first_name`, `last_name`, `email`, `password`, `phone`, `locale` — already present).
- **Consultation:** `complaint`, `complaints`, `history`, `history_of_presenting_complaint`, `physical_examination`, `diagnoses`, `investigations`, `prescriptions`, `treatment`, `treatment_plan`, `follow_up_date`, `clinical_notes`, `vitals`, `temperature`, `blood_pressure`, `pulse`, `respiratory_rate`, `spo2`, `weight`, `height`, `bmi` (`diagnosis` already present).

---

## 10. Dynamic labels converted

- `is_active ? 'Active' : 'Inactive'` → `common.active` / `common.inactive` (analyzers index/show).
- Toggle title `'Deactivate' / 'Activate'` → `common.deactivate` / `common.activate`.
- Pluralised/parameterised strings converted to placeholder form (no fragment concatenation): `pharmacy.billed_dispensed_of` (`:billed/:dispensed/:total`), `pharmacy.qty_dispensed` (`:qty`), `pharmacy.remaining_qty` (`:qty`), `pharmacy.items_count` (`:count`), `consultations.member_short`.
- Model-derived display methods (`status_label`, `protocol_label`, `x-status-badge`) were **left untouched** — they are resolved server-side and out of scope for view-layer wrapping.

---

## 11. Scanner result

```
Files scanned: 1268
Active runtime candidates: 437   (was 519)
Demo/template candidates: 15788
Language-file candidates: 133
Known false positives: 1297
Service-title manual-review candidates: 394
```

## 12. Parity result

- **EN/FR recursive key parity: PASS** across every module (automated diff of flattened keys, including nested arrays — 0 gaps).
- `for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done` → all clean.
- `php -l scripts/localisation-audit.php` → clean.
- `git diff --check` → clean (CRLF normalisation notices only).
- Compiled view cache: **0 PHP parse errors** (`php -l` over all of `storage/framework/views/*.php`).

---

## 13. Manual French verification checklist

Switch app locale to French and confirm:

- [ ] Consultation page (`/admin/consultations/{id}/routes/{id}`) loads (no 500) and JS labels (loading/error/delete) render in French.
- [ ] Consultation history print: section headers, patient/visit info labels, Care Team, Investigations, Procedures, empty-states are French.
- [ ] Lab analyzers: index stats/filters/table/modals; show device-info/mappings/recent-messages/modals; diagnostics stats/filters/table/view-message modal.
- [ ] Pharmacy dispense: prescription details, progress, batch table, item table, status badges, individual dispense modal.
- [ ] Auth/profile form validation errors show French field names (e.g. *confirmation du mot de passe*, *mot de passe actuel*).

---

## 14. Remaining candidates (deferred to Phase 15C)

**437 active-runtime candidates remain across ~86 files** (see the worklist table in `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`). They were **not** closed in 15B because each requires careful per-string judgement (clinical/financial confidentiality, dynamic-vs-static labels) and dedicated lang namespaces; rushing them risks regressions in clinical views. Highest-value next batch (by candidate count), all `priority: high`, `active route-linked`:

| Module | File | Candidates |
|---|---|---:|
| javascript | `resources/js/script.js` | 23 |
| pharmacy | `pharmacy/drug-history.blade.php` | 17 |
| theatre | `theatre/rooms/index.blade.php` | 16 |
| admin | `admin/procedures/index.blade.php`, `admin/procedures/schedule.blade.php` | 14 each |
| admin | `admin/product-stock/ledger.blade.php` | 12 |
| prescriptions | `prescriptions/show.blade.php` | 12 |
| wards | `wards/index.blade.php` | 12 |
| admin | `admin/investigation-catalogue/show.blade.php`, `admin/procedure-catalogue/show.blade.php` | 11 each |
| wards | `wards/beds.blade.php` | 11 |
| store | `store/purchase-orders/*`, `store/purchase-returns/*` | 8–10 |

### Reason for deferral (per category)
- **`resources/js/script.js` / `doctors.js`** — global JS files, not Blade; must route through the existing `window.UHMS_I18N` bridge (no new framework). Flagged `manual-review`, not a straight `__()` wrap.
- **Theatre / procedures / wards / stock / store / prescriptions views** — straightforward `fix` candidates but each needs its own domain lang namespace and FR clinical terminology review; batched for 15C to keep this change set reviewable.
- **`*/partials/*`, `claims/clinical-mirror`** — shared/embedded partials and confidentiality-sensitive clinical mirror; flagged `manual-review` to avoid altering data exposure.

---

## 15. Permissions / security

No `@can`/`@cannot`/`Gate`/policy/middleware/role/financial-visibility/stock-cost checks were modified. No business logic moved into Blade. No new localisation framework, frontend package, or i18n library introduced.

---

## 16. Recommendation for next phase (15C)

1. Wire `resources/js/script.js` + `doctors.js` strings through `window.UHMS_I18N` (highest single-file count).
2. Burn down the theatre → wards → stock/store → prescriptions cluster (≈140 candidates) with per-domain lang files.
3. Complete the remaining pharmacy pages (`drug-history`, `history`).
4. Then sweep the long tail of 1-candidate settings/blood-bank/hr views.
5. Re-run the scanner after each cluster; target active-runtime candidates < 200 by end of 15C.
