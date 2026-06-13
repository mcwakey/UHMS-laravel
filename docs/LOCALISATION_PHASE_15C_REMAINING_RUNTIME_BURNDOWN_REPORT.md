# UHMS Localisation — Phase 15C: Remaining Runtime Pages Burn-Down Report

**Date:** 2026-06-13
**Branch:** beta-x
**Scope:** Continue the active-runtime localisation burn-down from the Phase 15B exit state (437), processing the remaining route-linked worklist in batches.

---

## 1. Summary

Phase 15C resumed the batch burn-down. This pass completed **Batch 1 (pharmacy)** in full and a **high-value portion of Batch 2 (theatre/procedures)**, creating one new namespace (`procedures`) and extending `pharmacy` and `theatre`. All added keys have full EN/FR parity, all language files lint clean, the compiled view cache builds with no PHP errors, and the scanner confirms a measured reduction.

This is an **incremental, verified pass** — not a completion of all ten batches. Batches 3–10, the remaining Batch 2 files, the JavaScript review, and the class-A service review are **documented and deferred** (per the phase rule "Do not claim 'all pages translated' unless the scanner and manual French checks support it"). The remaining worklist and a continuation plan are in §8.

---

## 2. Active runtime candidate count

| | Active runtime candidates |
|---|---:|
| **Phase 15C start** | **437** |
| **After this pass** | **370** |
| **Net reduction this pass** | **−67** |

Combined with Phase 15B (519 → 437), the program-to-date reduction is **519 → 370 (−149)**.

### Per-batch before/after

| Batch | Files completed | Before | After |
|---|---|---:|---:|
| Batch 1 — Pharmacy | `pharmacy/drug-history`, `pharmacy/history` | 437 | 411 |
| Batch 2 — Theatre/procedures (partial) | `theatre/rooms/index`, `admin/procedures/index`, `admin/procedure-catalogue/show` | 411 | 370 |

---

## 3. Files fixed

| File | Batch | Namespace used |
|---|---|---|
| `resources/views/pharmacy/drug-history.blade.php` | 1 | `pharmacy` + `common` |
| `resources/views/pharmacy/history.blade.php` | 1 | `pharmacy` + `common` |
| `resources/views/theatre/rooms/index.blade.php` | 2 | `theatre` + `common` |
| `resources/views/admin/procedures/index.blade.php` | 2 | `procedures` (new) + `common` |
| `resources/views/admin/procedure-catalogue/show.blade.php` | 2 | `procedures` (new) + `theatre` + `common` |

What was translated per file: page titles, headings, breadcrumbs, filters, table headers, status/active badges, action buttons & icon `title`/`aria-label`s, modal titles & form labels, placeholders, helper text, empty states, `confirm()` messages, and footers/totals. Stock-cost and revenue **values** were left as data; only their **labels** were translated.

---

## 4. Files still deferred

**83 worklist files remain** (per the regenerated `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md`). Notable deferrals:

### Batch 2 remainder (deferred this pass)
- `theatre/show.blade.php`
- `theatre/partials/schedule-form.blade.php` *(manual-review)*
- `theatre/rooms/partials/form.blade.php` *(manual-review)*
- `admin/procedures/schedule.blade.php` (14 candidates)
- `admin/procedure-catalogue/index.blade.php`

### Batches 3–10 (deferred this pass)
Wards/beds; product-stock/store/suppliers/procurement; prescriptions/investigations/catalogue/vitals/lab; emergency/show; admin config pages (icd-codes, services, permissions, modules, specialties, users, dashboards, departments, designations, complaints catalogue, notifications broadcast); billing/accounting/accounts; queue/notifications/service-renderings; HR/blood-bank/settings long tail.

### Reason for deferral
- **Volume & reviewability:** ~83 files is too large for a single reviewable change set; batching keeps diffs auditable and clinical pages low-risk.
- **`manual-review` partials** (`schedule-form`, `rooms/partials/form`): embedded shared form fragments — needs confirmation they aren't reused in a confidentiality-sensitive context before wrapping.
- **Financial/clinical sensitivity** (billing, accounting, emergency, stock-cost): require careful per-string review to avoid touching `@can`/visibility logic; scheduled for dedicated batches.

---

## 5. Language files changed

| File | Change |
|---|---|
| `lang/en/procedures.php` | **new** — 67 keys |
| `lang/fr/procedures.php` | **new** — 67 keys |
| `lang/en/pharmacy.php` | +40 keys (drug-history + history) → 106 total |
| `lang/fr/pharmacy.php` | +40 keys → 106 total |
| `lang/en/theatre.php` | +24 keys (rooms management) → 147 total |
| `lang/fr/theatre.php` | +24 keys → 147 total |

Generic strings reused `lang/{en,fr}/common.php` (search, type, status, active/inactive, filter, reset, close, actions, edit, delete, cancel, update, save_changes, category, code, department, all_departments, all_statuses, description, required, notes, reason, patient, date, drug, total, from, to, yes, no, deactivate/activate) instead of duplicating.

## 6. Namespaces created

- `procedures` (EN + FR) — procedure catalogue index, edit/add modals, and the catalogue "configure" page (templates + default consumables). Chosen over overloading `theatre.php` to keep the procedure-catalogue vocabulary self-contained (the phase brief allows `theatre.php` *or* `procedures.php` consistently).

## 7. Verification results

- **EN/FR parity:** PASS (recursive flattened-key diff across all modules — 0 gaps).
- **PHP lint:** `for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done` → all clean.
- **`php -l scripts/localisation-audit.php`** → clean.
- **View cache:** `php artisan view:cache` builds successfully; compiled views lint with 0 parse errors.
- **`php artisan route:list`** → OK (no broken references from the edits).
- **`git diff --check`** → clean (CRLF normalisation notices only).
- **Scanner:** Active runtime candidates **370** (was 437).

### JavaScript files reviewed
- `resources/js/script.js` (23), `resources/js/doctors.js` (3): **not yet processed** — flagged `manual-review`. They must route through the existing `window.UHMS_I18N` bridge (no new frontend framework), which is a dedicated task scheduled for the next pass. No JS strings were changed in this pass.

### Class-A service candidates reviewed
- The 67 class-A service-output candidates (`ConsultationNextPatientService`, `FinancialReportService`, `PatientMergePreviewService`, `ProcedureReportService`, `StatisticsService`, …): **not yet processed.** These require confirming each string is a rendered user-facing label vs. a stored canonical/audit/SQL value before wrapping; deferred to avoid altering stored semantics.

### Permissions / security confirmation
No `@can`/`@cannot`/`Gate`/policy/middleware/role/permission/financial-visibility/stock-cost checks were modified. No business logic moved into Blade. No new localisation framework or frontend package introduced. Product-vs-service wording kept distinct.

### Dynamic labels converted
- `is_active ? 'Active':'Inactive'` → `common.active`/`common.inactive` (theatre rooms, procedures, drug-history).
- Toggle titles `'Deactivate'/'Activate'` → `common.deactivate`/`common.activate` (procedures).
- Parameterised strings (no concatenation): `pharmacy.drug_history_title` (`:name`), `pharmacy.locations_count`/`records_count` (`:count`), `theatre.edit_room_title`/`block_room_title` (`:name`), `procedures.configure_procedure_title` (`:name`).
- Enum-backed display values (`room_type->translatedLabel()`, `status->translatedLabel()`, procedure `category`) left to their existing server-side translation methods — not wrapped at the view layer.

---

## 8. Manual French verification checklist (this pass)

Switch app to French and verify:

- [ ] **Pharmacy → Drug history**: title, drug-details list, stat cards, stock-by-location table, receipt-history table (Expired/Soon badges), dispensing-history table + totals, empty states.
- [ ] **Pharmacy → Dispensing history**: filters (search/from/to), table headers, empty state.
- [ ] **Theatre → Rooms**: heading/hint, Calendar/Board/New Room buttons, filters, table, Recent Room Blocks, create/edit/block modals.
- [ ] **Admin → Procedure catalog (index)**: stat cards, filters, table, Add/Edit procedure modals, consent labels.
- [ ] **Admin → Procedure catalogue → Configure**: Templates/Consumables tabs, section & field forms, tables, consumables form, all confirm() dialogs.

---

## 9. Recommendation for next phase / continuation

1. **Finish Batch 2** (`procedures/schedule` — 14, `theatre/show`, `procedure-catalogue/index`, the two `manual-review` partials).
2. Proceed Batch 3 → Batch 10 in worklist order, running the scanner after each batch.
3. **JavaScript** (`script.js`, `doctors.js`): expose active strings via `window.UHMS_I18N` from the layout; document any dormant/demo strings as false positives with evidence.
4. **Class-A services**: wrap only confirmed user-facing output labels in `__()`; leave stored/audit/SQL semantics untouched.
5. Target: active-runtime candidates **< 100** (ideally 0) by the end of the continuation, with manual French verification per batch.

> Status: **partial pass — not complete.** Active runtime candidates reduced 437 → 370. Remaining work is documented above and in `LOCALISATION_COVERAGE_AUDIT_REPORT.md`.
