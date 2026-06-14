# UHMS Localisation — Phase 15F: Clinical / Emergency / Admin Config / Billing Burn-Down Report

**Date:** 2026-06-14
**Branch:** beta-x
**Scope:** Continue the active-runtime burn-down from the Phase 15E exit state (192). Process Batch 5 remainder, Batch 6 (emergency), Batch 7 (admin config), Batch 8 (billing/accounting), Batch 9 (queue/notifications), and Batch 10 (HR/blood-bank/settings long tail).

---

## 1. Summary

This pass processed **all remaining Blade worklist batches** — roughly **45 views** across clinical, emergency, admin-configuration, billing/accounting, queue/notifications, HR, blood-bank, settings, statistics and shared partials. Created four new namespaces (`admin`, `notifications`, `queue`, `statistics`) and extended ten existing ones.

**Both targets met:** active runtime candidates **192 → 34** — under the primary target (< 100) **and** the preferred target (< 50).

All added keys have EN/FR parity, all language files lint clean, the compiled view cache builds with 0 PHP parse errors, `route:list` is OK, and `git diff --check` is clean. Permission/financial-visibility logic on the sensitive billing, accounting, emergency and permissions pages was left untouched.

---

## 2. Active runtime candidate count

| | Active runtime candidates |
|---|---:|
| **Phase 15F start** | **192** |
| **After this pass** | **34** |
| **Net reduction** | **−158** |

**Primary target (< 100): MET. Preferred target (< 50): MET.**

Program-to-date: **519 (15B start) → 34 (−485, ~93%)**.

### Per-batch before/after

| Batch | Scope | Before | After |
|---|---|---:|---:|
| Batch 5 remainder + Batch 7 | prescriptions/show, investigation-catalogue/show + 11 admin-config views | 192 | 113 |
| Batch 6 + Batch 9 | emergency/show + notifications, queue (manage/board), service-renderings | 113 | 87 |
| Batch 8 + Batch 10 | billing/accounting/accounts + HR/blood-bank/settings/statistics/visits/patient-card | 87 | 34 |

---

## 3. Files fixed (~45 views)

**Batch 5 remainder (clinical):** `prescriptions/show`, `admin/investigation-catalogue/show`.

**Batch 6 (emergency):** `emergency/show` (urgency/priority options, Select placeholders).

**Batch 7 (admin config):** `admin/icd-codes/index`, `departments/index`, `designations/index`, `admin/permissions/index`, `admin/modules/index`, `complaints/catalogue/index`, `admin/notifications/broadcast`, `admin/specialties/index`, `admin/users/permissions`, `admin/dashboards/index`, `admin/services/index`.

**Batch 8 (billing/accounting):** `billing/invoices/show`, `accounting/payable/payables`, `accounting/settings/index`, `accounts/categories`, `accounts/entries/create`, `accounts/entries/index`.

**Batch 9 (queue/notifications):** `notifications/index`, `queue/manage`, `queue/board`, `service-renderings/index`.

**Batch 10 (long tail):** `hr/employees/{create,edit}`, `hr/attendance/summary`, `hr/leave/create`, `blood-bank/{donations,reports,requests,storage,units,donation-view,donor-profile}`, `statistics/dashboard`, `visits/create` (JS strings), `partials/patient-card`, `settings/{activity-log-show,activity-log,invoice,log-retention,notification-preferences,organization,payment-methods,profile}`.

Per file: titles, headings/breadcrumbs, filters, table headers, status options, modal titles & form labels, placeholders, helper text, empty states, `confirm()`/`alert()` JS messages. Permission slugs, route names, DB codes, medicine/test names and clinician-entered text were **not** translated.

---

## 4. Language files

| File | Change |
|---|---|
| `lang/{en,fr}/admin.php` | **new** — 36 keys each |
| `lang/{en,fr}/notifications.php` | **new** — 6 keys each |
| `lang/{en,fr}/queue.php` | **new** — 5 keys each |
| `lang/{en,fr}/statistics.php` | **new** — 1 key each |
| `lang/{en,fr}/prescriptions.php` | +6 keys (detail) |
| `lang/{en,fr}/investigations.php` | +18 keys (catalogue detail) — also fixed a pre-existing `main_stock_qty` duplicate |
| `lang/{en,fr}/emergency.php` | +3 keys (urgency) |
| `lang/{en,fr}/accounting.php` | +12 keys |
| `lang/{en,fr}/billing.php` | +4 keys |
| `lang/{en,fr}/blood_bank.php` | +5 keys |
| `lang/{en,fr}/settings.php` | +2 keys |
| `lang/{en,fr}/hr.php` | +1 key |
| `lang/{en,fr}/visits.php` | +2 keys |
| `lang/{en,fr}/patients.php` | +1 key |

Generic strings reused `common.*` (code, description, category, type, department, all_departments, all, status, all_statuses, name, actions, filter, close, select, none, required, optional, add, delete, notifications, patient, priority, reference, yes, action_cannot_be_undone) and existing module keys.

## 5. Verification results

- **EN/FR parity:** PASS (recursive flattened-key diff across all modules — 0 gaps).
- **PHP lint:** all `lang/{en,fr}/*.php` clean.
- **View cache:** `php artisan view:cache` builds; compiled views lint with 0 parse errors.
- **`php artisan route:list`** → OK.
- **`git diff --check`** → clean.
- **Scanner:** Active runtime candidates **34** (was 192).

### JavaScript handled in-Blade
- `visits/create`, `notifications/index`, `billing/invoices/show`, `admin/investigation-catalogue/show`: inline `confirm()`/`alert()`/built-HTML strings wired via `@json(__(...))` — no new framework.

### Dynamic labels converted
- Urgency/priority enum-style `<option>`s (emergency), input-type options (investigation catalogue), Active/Inactive (investigation-catalogue) → keyed.
- Parameterised (no concatenation): `prescriptions.items_count`, `admin.chapter` prefix, `investigations.no_products_alert` (`:dept`, `:link`).
- Enum display values (`BloodGroup`, `MaritalStatus`, status enums, module/priority labels) left to their server-side `->label()`/`->translatedLabel()` methods.

### Permissions / financial security
No `@can`/`@cannot`/`Gate`/policy/financial-visibility/permission checks modified. No journal/posting/invoice-total/payment/credit-note/write-off/sponsor/insurance/module-enable logic changed. No business logic moved into Blade. Permission slugs, route names and DB codes left untranslated.

---

## 6. Remaining candidates (34 — JS + manual-review partials)

| File | Count | Reason |
|---|---:|---|
| `resources/js/script.js` | 23 | Global JS — must route through `window.UHMS_I18N` (no new framework). Dedicated task; not a Blade `__()` wrap. |
| `resources/js/doctors.js` | 3 | Same as above. |
| `resources/views/patients/partials/insurance-add-modal-scripts.blade.php` | 4 | `manual-review` — embedded JS partial; needs `UHMS_I18N` bridge wiring. |
| `resources/views/admin/analyzers/index.blade.php` | 3 | Residual strings flagged after earlier pass; re-verify against current scanner. |
| `resources/views/claims/partials/clinical-mirror.blade.php` | 1 | `manual-review` — confidentiality-sensitive clinical mirror partial. |

### Class-A service candidates
The 67 class-A service-output candidates remain unprocessed — each needs confirming as a rendered user-facing label vs. a stored/audit/SQL value before wrapping. Deferred.

---

## 7. Manual French verification checklist (this pass)

- [ ] **Prescriptions → show**: drug table headers, billing table, allergies banner.
- [ ] **Investigation catalogue → configure**: input-type select, consumables form/table, no-products alert, criterion delete confirm.
- [ ] **Emergency → show**: urgency & priority selects, blood-group/marital Select placeholders.
- [ ] **Admin config** (ICD codes, departments, designations, permissions, modules, complaints, broadcast, specialties, user permissions, dashboards, services): titles, filters, table headers, delete confirms, service tracking options.
- [ ] **Billing/Accounting**: invoice payer selects + record-payment confirm; payables; accounting settings; account categories; entries create/index.
- [ ] **Queue/Notifications**: notifications filters + delete confirm; queue management table; queue board; service-renderings selects.
- [ ] **Long tail**: HR add/edit/attendance/leave; blood-bank pages; statistics dashboard; visits billing JS labels; patient-card tooltip; all settings page titles.
- [ ] Confirm financial columns/permission management remain gated for unauthorised users.

---

## 8. Recommendation for next phase

1. **JavaScript** (`script.js`, `doctors.js`, `insurance-add-modal-scripts`): expose active strings via `window.UHMS_I18N` from the layout; document any dormant strings as false positives with evidence. This is the bulk of the remaining 34.
2. **`admin/analyzers/index`** residual 3 + **`claims/clinical-mirror`** 1: re-scan and fix or document.
3. **Class-A services** (67): wrap only confirmed user-facing output labels.
4. After JS, re-run the scanner — active-runtime candidates should approach **0**.

> Status: **Both targets met (< 100 and < 50).** Active runtime candidates reduced 192 → 34. All Blade worklist batches complete; remaining 34 are JS/manual-review items documented above.
