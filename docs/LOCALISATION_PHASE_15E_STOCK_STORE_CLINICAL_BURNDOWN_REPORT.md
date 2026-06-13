# UHMS Localisation — Phase 15E: Stock / Store / Procurement / Clinical Burn-Down Report

**Date:** 2026-06-13
**Branch:** beta-x
**Scope:** Continue the active-runtime burn-down from the Phase 15D exit state (317). Complete Batch 4 (stock / product-stock / store / suppliers / procurement) and a clinical subset of Batch 5.

---

## 1. Summary

This pass **completed Batch 4 in full** (17 stock/store/procurement views) and a **safe clinical subset of Batch 5** (6 views: prescriptions list, investigation consumables + catalogue, vitals, lab results/tests). Created two new namespaces (`store`, `prescriptions`, `vitals`) and extended `stock` and `investigations`. **Primary target reached: active runtime candidates 317 → 192 (< 200).**

All added keys have EN/FR parity, all language files lint clean, the compiled view cache builds with 0 PHP parse errors, and the scanner confirms the reduction. Permission/visibility logic on the sensitive stock and procurement pages was left untouched.

---

## 2. Active runtime candidate count

| | Active runtime candidates |
|---|---:|
| **Phase 15E start** | **317** |
| **After this pass** | **192** |
| **Net reduction** | **−125** |

**Primary target (< 200): MET.** Preferred target (< 150): not yet — see §8.

Program-to-date: **519 (15B start) → 192 (−327)**.

### Per-batch before/after

| Batch | Scope | Before | After |
|---|---|---:|---:|
| Batch 4a | product-stock (ledger, balances, receive, transfer, adjust, return) + stock-locations | 317 | 283 |
| Batch 4b + 4c | store (purchase-orders ×3, purchase-returns ×3, supplier-ledger, suppliers, stock-requisitions) + department-consumables | 283 | 220 |
| Batch 5 (subset) | prescriptions/index, investigations/items, investigation-catalogue/index, vitals/record, lab/results, lab/tests | 220 | 192 |

---

## 3. Files fixed (23 views)

**Batch 4 — Stock / Product Stock (7):** `admin/product-stock/{ledger,balances,receive,transfer,adjust,return}`, `admin/stock-locations/index`.

**Batch 4 — Store / Procurement (9):** `store/purchase-orders/{index,create,show}`, `store/purchase-returns/{index,create,show}`, `store/supplier-ledger`, `store/suppliers`, `store/stock-requisitions/index`.

**Batch 4 — Consumables (1):** `department-consumables/index`.

**Batch 5 — Clinical subset (6):** `prescriptions/index`, `investigations/items/index`, `admin/investigation-catalogue/index`, `vitals/record`, `lab/results`, `lab/tests`.

Per file: titles, headings, filters, table headers, status badges, modal titles & form labels, placeholders, helper text, empty states, `confirm()`/`alert()` JS messages, and footer totals. **Stock-cost and price values were left as data; only their labels were translated.** Product-vs-service wording kept strict (`Products = physical stock`, `Services = billable`).

---

## 4. Language files

| File | Change |
|---|---|
| `lang/{en,fr}/store.php` | **new** — 44 keys each (purchase orders, returns, supplier ledger, suppliers) |
| `lang/{en,fr}/prescriptions.php` | **new** — 5 keys each |
| `lang/{en,fr}/vitals.php` | **new** — 4 keys each |
| `lang/{en,fr}/stock.php` | +22 keys → 274 each (ledger/balances/receive headers, modal labels, product-type filters) |
| `lang/{en,fr}/investigations.php` | +11 keys → 111 each (consumables + catalogue) |
| `lang/{en,fr}/lab.php` | +1 key (`tests_title`) |

Generic strings reused `common.*` (search, status, all_statuses, filter, clear, close, actions, edit, delete, cancel, date, type, description, phone, email, name, code, department, all_departments, patient, doctor, action, view, balance, total) and existing `stock.*` / `pharmacy.*` / `theatre.*` keys instead of duplicating.

## 5. Namespaces created

- `store` — procurement vocabulary (PO #, supplier ledger debit/credit, returns) kept separate from `stock` per the brief ("create `store.php` if store/procurement vocabulary does not fit cleanly inside `stock.php`").
- `prescriptions`, `vitals` — small, did not previously exist.

---

## 6. Stock-cost / financial security

No `@can`/`@cannot`/`Gate`/policy/stock-cost-visibility/financial-visibility checks were modified. No business rules for stock receiving/transfer/adjustment/returns, purchase orders/returns, supplier ledger, department consumables, or stock requisitions were changed. No business logic moved into Blade. The `stock.restricted_cost` gate and existing visibility branches are intact. Only visible UI strings were translated.

## 7. Verification results

- **EN/FR parity:** PASS (recursive flattened-key diff across all modules — 0 gaps).
- **PHP lint:** all `lang/{en,fr}/*.php` clean; `php -l scripts/localisation-audit.php` clean.
- **View cache:** `php artisan view:cache` builds; compiled views lint with 0 parse errors.
- **`php artisan route:list`** → OK.
- **`git diff --check`** → clean for all edited files (CRLF normalisation notices only; the lone `prompt.md` trailing-whitespace notice is pre-existing, not from this pass).
- **Scanner:** Active runtime candidates **192** (was 317).

### Dynamic labels converted
- `is_active ? Active:Inactive` → `common.active/inactive` (investigation-catalogue).
- Parameterised (no concatenation): `stock.*`/`store.*` placeholders, `prescriptions.items_count` (`:count`), `wards`/`store` totals via `common.total`.
- Enum display values (`PurchaseOrderStatus`, `PurchaseReturnStatus`, `BedStatus`, `PrescriptionStatus`, product-type strings) left to their server-side `->translatedLabel()` / `->label()` methods.

### JavaScript handled
- `store/purchase-orders/create.blade.php`: the inline `select2` placeholder and the "select at least one product" `alert()` were wired through `@json(__('store.…'))` (Blade-embedded JS, not a new framework).

### JavaScript / class-A (deferred)
- `resources/js/script.js`, `resources/js/doctors.js`: not processed — `window.UHMS_I18N` wiring, scheduled separately.
- 67 class-A service candidates: not processed — confirm user-facing vs. stored/audit semantics first.

---

## 8. Remaining (51 worklist files)

| Group | Examples | Reason deferred |
|---|---|---|
| **Batch 5 remainder** | `prescriptions/show`, `investigations/.../show`, `admin/investigation-catalogue/show`, `lab/*` deeper sections | Larger clinical pages; must avoid translating medicine/test names & clinician notes — needs careful per-string review. |
| **Batch 6 — Emergency** | `emergency/show` | Emergency workflow/billing sensitivity. |
| **Batch 7 — Admin config** | icd-codes, services, permissions, modules, specialties, users, dashboards, departments, designations, complaints catalogue, notifications broadcast | Permission-slug safety; batched. |
| **Batch 8 — Billing/accounting** | billing invoice show, payables, accounting settings, accounts | Financial-visibility sensitivity. |
| **Batch 9 — Queue/notifications/service-renderings** | queue manage/board, notifications, service-renderings | Straightforward; batched. |
| **Batch 10 — HR/blood-bank/settings long tail** | hr/*, blood-bank/*, settings/*, statistics, visits/create, partials/patient-card | Long tail of 1–4 candidate files. |
| **JS + class-A services** | script.js, doctors.js, 67 service outputs | `UHMS_I18N` wiring / stored-semantics review. |

---

## 9. Manual French verification checklist (this pass)

- [ ] **Stock balances**: header buttons (Receive/Transfer/Adjust/Return/Ledger), filters, table, all four modals (labels, `(Main)` suffix, buttons).
- [ ] **Stock ledger**: filters, table headers, empty state.
- [ ] **Receive / Transfer / Adjust / Return** pages: titles, form labels, hints.
- [ ] **Purchase orders**: list filters/table/cancel-confirm; create form + JS alerts; show receiving modal.
- [ ] **Purchase returns**: list, create form, show.
- [ ] **Supplier ledger / Suppliers**: filters, table headers.
- [ ] **Department / Investigation consumables**: search, type filter, table.
- [ ] **Prescriptions list**: filters, table, badges, empty state.
- [ ] **Investigation catalogue**: filters, table.
- [ ] **Vitals record**: title, empty/placeholder/helper text.
- [ ] **Lab results / tests**: page titles.
- [ ] Confirm stock-cost columns remain hidden for users without the permission.

---

## 10. Recommendation for next phase

1. Finish **Batch 5** (prescriptions/show, investigation-catalogue/show, lab deeper sections) — clinical, careful with DB-sourced names.
2. **Batch 6** emergency/show.
3. **Batches 7–10** (admin config → billing/accounting → queue/notifications → HR/blood-bank/settings).
4. **JavaScript** via `window.UHMS_I18N`; **class-A services** (confirmed user-facing labels only).
5. Re-run the scanner after each batch; target active-runtime candidates **< 150**, then **< 100**.

> Status: **Primary target met (< 200).** Active runtime candidates reduced 317 → 192. Batch 4 complete; Batch 5 partially complete. Remaining 51 files documented above and in `LOCALISATION_COVERAGE_AUDIT_REPORT.md`.
