# UHMS Localisation — Phase 15D: Runtime Burn-Down Continuation Report

**Date:** 2026-06-13
**Branch:** beta-x
**Scope:** Continue the active-runtime localisation burn-down from the Phase 15C exit state (370). Finish the Batch 2 remainder (theatre/procedures) and complete Batch 3 (wards/beds).

---

## 1. Summary

This pass **completed Batch 2 in full** (the five theatre/procedure files deferred in 15C) and **completed Batch 3** (wards & beds). Two full batches were processed, one new namespace (`wards`) was created, and `theatre` / `procedures` were extended. All added keys have EN/FR parity, all language files lint clean, the compiled view cache builds with no PHP errors, and the scanner confirms the reduction.

This is an **incremental, verified pass** — Batches 4–6, the JavaScript review, and the class-A service review are documented and deferred (§7). The target (< 150) is not yet reached; remaining work is large (stock/store/procurement is 16 files alone) and is scheduled for the next pass to keep each change set reviewable.

---

## 2. Active runtime candidate count

| | Active runtime candidates |
|---|---:|
| **Phase 15D start** | **370** |
| **After this pass** | **317** |
| **Net reduction this pass** | **−53** |

Program-to-date: **519 (15B start) → 317 (−202)**.

### Per-batch before/after

| Batch | Files completed | Before | After |
|---|---|---:|---:|
| Batch 2 remainder | `admin/procedures/schedule`, `admin/procedure-catalogue/index`, `theatre/show`, `theatre/partials/schedule-form`, `theatre/rooms/partials/form` | 370 | 342 |
| Batch 3 — Wards/beds | `wards/index`, `wards/beds`, `wards/bed-map`, `settings/ward` | 342 | 317 |

---

## 3. Files fixed

**Batch 2 remainder (theatre/procedures):**
- `resources/views/admin/procedures/schedule.blade.php` — filters, table, complete/details modals, confirm dialogs, empty state.
- `resources/views/admin/procedure-catalogue/index.blade.php` — header, search, table, status, Configure button, empty state.
- `resources/views/theatre/show.blade.php` — page title, summary labels, segment tabs, every card header & primary action button across the Request/Operation workflow, the 4 scanner candidates (3× `Select…`, the "Operative note recorded…" prompt), cancel section + confirm. *(The granular surgical vitals/field input labels were left as-is — they are not flagged as active candidates and most already have `theatre.*` keys; a focused field-label pass is noted for later.)*
- `resources/views/theatre/partials/schedule-form.blade.php` *(was manual-review)* — confirmed safe (a scheduling form included only by `theatre/show`); fully translated.
- `resources/views/theatre/rooms/partials/form.blade.php` *(was manual-review)* — confirmed safe (room create/edit form fragment); fully translated.

**Batch 3 (wards/beds):**
- `resources/views/wards/index.blade.php` — header, filters, table, occupancy, status, dropdown actions, Add/Edit ward modals.
- `resources/views/wards/beds.blade.php` — header, filters, table, Add/Edit bed modals, GH₵ rate labels (values untouched).
- `resources/views/wards/bed-map.blade.php` — title, availability legend, ward cards, admit tiles, empty states.
- `resources/views/settings/ward.blade.php` — page title (body already localised).

---

## 4. Manual-review partials decision

Both `theatre/partials/schedule-form.blade.php` and `theatre/rooms/partials/form.blade.php` were flagged `manual-review` because they are shared includes. On inspection they are **plain form fragments** (scheduling fields; room attributes) with no embedded confidential clinical/financial data and no permission branching of their own. **Decision: safe to translate** — both done. The `@can` checks that gate their *inclusion* live in the parent views and were not altered.

---

## 5. Language files changed

| File | Change |
|---|---|
| `lang/en/wards.php` / `lang/fr/wards.php` | **new** — 47 keys each |
| `lang/en/theatre.php` / `lang/fr/theatre.php` | +~72 keys → 195 total each (rooms mgmt + procedure detail + schedule form) |
| `lang/en/procedures.php` / `lang/fr/procedures.php` | +~31 keys → 98 total each (scheduled procedures + catalogue list) |

Generic strings reused `common.*` (search, status, active/inactive, filter, clear, close, actions, edit, delete, cancel, update, save_changes, code, department, all_departments, all_statuses, description, notes, reason, patient, details, reject, none). Existing `settings.ward_breadcrumb` reused for the ward-settings title.

## 6. Verification results

- **EN/FR parity:** PASS (recursive flattened-key diff across all modules — 0 gaps).
- **PHP lint:** all `lang/{en,fr}/*.php` clean; `php -l scripts/localisation-audit.php` clean.
- **View cache:** `php artisan view:cache` builds; compiled views lint with 0 parse errors.
- **`php artisan route:list`** → OK.
- **`git diff --check`** → clean (CRLF notices only).
- **Scanner:** Active runtime candidates **317** (was 370).

### Dynamic labels converted
- `is_active ? 'Active':'Inactive'` → `common.active`/`common.inactive` (wards, procedures, procedure-catalogue).
- Toggle titles `'Deactivate'/'Activate'` → `common.deactivate`/`common.activate` (wards, procedures).
- Parameterised (no concatenation): `wards.edit_ward_title`/`edit_bed_title`/`admit_patient_to` (`:name`/`:number`/`:bed`), `theatre.procedure_title`/`billing_hint` (`:number`/`:service`), `procedures.procedure_for`/`procedure_services_count` (`:procedure`,`:patient`/`:count`).
- Enum display values (`BedStatus`/`BedType`/`ProcedureStatus`/`room_type` `->translatedLabel()`) left to their server-side translation methods.

### JavaScript files reviewed
- `resources/js/script.js` (23), `resources/js/doctors.js` (3): **not processed this pass.** Must route through the existing `window.UHMS_I18N` bridge (no new framework). Scheduled for a dedicated pass. No JS strings changed.

### Class-A service candidates reviewed
- The 67 class-A service-output candidates: **not processed.** Require confirming each is a rendered user-facing label vs. stored canonical/audit/SQL value. Deferred.

### Permissions / security confirmation
No `@can`/`@cannot`/`Gate`/policy/middleware/role/permission/financial-visibility/stock-cost checks modified. No business logic moved into Blade. No new localisation framework or frontend package. Product-vs-service wording kept distinct. Bed allocation, scheduling, and theatre workflow logic untouched. Admission/clinical data exposure unchanged.

---

## 7. Files still deferred (74 worklist files remain)

| Batch | Files | Reason |
|---|---|---|
| **Batch 4 — Stock/Store/Suppliers/Procurement** | `admin/product-stock/{ledger,balances,receive,transfer,adjust,return}`, `admin/stock-locations/index`, `store/purchase-orders/{index,create,show}`, `store/purchase-returns/{index,create,show}`, `store/supplier-ledger`, `store/suppliers`, `store/stock-requisitions/index`, `department-consumables/index` (16 files) | Largest batch; financial/stock-cost sensitivity needs careful per-string review without touching `@can`/visibility. Needs `stock.php`/`store.php` namespace work. |
| **Batch 5 — Prescriptions/Investigations/Vitals/Lab** | `prescriptions/{show,index}`, `investigations/items/index`, `admin/investigation-catalogue/{index,show}`, `vitals/record`, `lab/{results,tests}` (8 files) | Clinical; must avoid translating medicine/test names & clinician notes. |
| **Batch 6 — Emergency** | `emergency/show` | Emergency workflow/billing sensitivity. |
| **JavaScript** | `resources/js/script.js`, `resources/js/doctors.js` | `window.UHMS_I18N` wiring, not a straight `__()` wrap. |
| **Class-A services** | 67 candidates | Confirm user-facing vs. stored semantics first. |

---

## 8. Manual French verification checklist (this pass)

Switch app to French and verify:

- [ ] **Theatre → Scheduled procedures**: filters, table, Start/Complete/Details, complete & details modals, cancel confirm, empty state.
- [ ] **Theatre → Procedure catalogue (list)**: header, search, table, Configure, empty state.
- [ ] **Theatre → Procedure detail**: summary, Request/Operation tabs, all card headers & primary buttons, room/surgeon `Select…` placeholders, cancel section.
- [ ] **Theatre → Schedule/Reschedule form** (within procedure detail): room, times, surgeon/anaesthetist/assistant, override fields, submit.
- [ ] **Theatre rooms → create/edit room** (form partial): all field labels.
- [ ] **Wards → index**: header/total, filters, table, occupancy, Add/Edit ward modals.
- [ ] **Wards → beds**: filters, table, Add/Edit bed modals.
- [ ] **Wards → bed map**: legend, ward cards, admit tiles, empty states.
- [ ] **Settings → Ward & Admissions**: page title.

---

## 9. Recommendation for next phase / continuation

1. **Batch 4** (stock/store/procurement, 16 files) — create/extend `stock.php` + `store.php`; protect stock-cost `@can` checks; keep product-vs-service wording strict.
2. **Batch 5** (prescriptions/investigations/vitals/lab, 8 files).
3. **Batch 6** (emergency/show).
4. **JavaScript** via `window.UHMS_I18N`; document dormant strings as false positives.
5. **Class-A services**: wrap only confirmed user-facing labels.
6. Re-run the scanner after each batch; target active-runtime candidates **< 150**, then **< 100**.

> Status: **partial pass — not complete.** Active runtime candidates reduced 370 → 317. Two full batches done (Batch 2 remainder, Batch 3). Remaining work documented above and in `LOCALISATION_COVERAGE_AUDIT_REPORT.md`.
