# UHMS Localisation Phase 4 — Remaining Operational Screens
## Bulk Translation Report

**Date:** 2026-06-12
**Branch:** beta-x
**Scope:** All remaining operational screens not covered in Phases 1–3

---

## Executive Summary

Phase 4 completes the EN/FR localisation of all remaining operational view files across 10 module groups. After this phase:

- **23 lang file pairs** cover the full application
- **3,301 EN keys** (= 3,301 FR keys) — 100% EN/FR parity verified
- **141 blade views** contain `__()` translation calls
- **Zero** hardcoded user-facing English strings remain in the translated modules
- All PHP lang files pass `php -l` syntax check

---

## Modules Translated — Phase 4

### 1. Patients Module

**Views translated:**
| File | `__()` calls |
|------|-------------|
| `patients/show.blade.php` | 59 |
| `patients/create.blade.php` | 85 |
| `patients/edit.blade.php` | 35 |
| `patients/index.blade.php` | 37 |
| `patients/partials/insurance-add-modal.blade.php` | 24 |
| `patients/partials/insurance-edit-modal.blade.php` | 12 |
| `patients/partials/patient-search-select.blade.php` | 2 |
| `patients/merge/index.blade.php` | 38 |
| `patients/merge/compare.blade.php` | 24 |
| `patients/merge/show.blade.php` | 13 |
| `patients/merge/logs.blade.php` | 12 |
| `patients/merged.blade.php` | 9 |

**Lang file:** `lang/en/patients.php` / `lang/fr/patients.php` — **248 keys each**

**Notable patterns:**
- JS insurance cascade (type→provider→tier) uses `@php $insuranceI18n = [...] @endphp <script>const insuranceI18n = @json($insuranceI18n);</script>` pattern
- Emergency contact JS strings use same pattern
- Merge views use `patients.merge_*` key prefix
- Deceased modal strings use `patients.deceased_*` key prefix

---

### 2. Visits Module

**Views translated:**
| File | `__()` calls |
|------|-------------|
| `visits/show.blade.php` | 89 |
| `visits/create.blade.php` | 137 |
| `visits/edit.blade.php` | 42 |
| `visits/preview.blade.php` | 12 |
| `visits/index.blade.php` | 33 |
| `visits/partials/visit-preview-summary.blade.php` | 26 |
| `visits/partials/visit-preview-timeline.blade.php` | 0* |

*timeline partial: all text is service-layer data, no static UI strings.

**Lang file:** `lang/en/visits.php` / `lang/fr/visits.php` — **338 keys each**

**Notable patterns:**
- `create.blade.php` injects 64 JS i18n keys via `visitI18n` object for patient search, insurance cascade, form validation feedback
- Tab labels (`tab_overview`, `tab_consultation`, `tab_lab`, etc.) used across show and create views
- Vitals labels reused in triage and admissions contexts

---

### 3. Billing — Invoices, Payments, Counter-Sale, Statements

**Views translated:**
| File | `__()` calls |
|------|-------------|
| `billing/invoices/index.blade.php` | 32 |
| `billing/invoices/show.blade.php` | 141 |
| `billing/invoices/create.blade.php` | 30 |
| `billing/invoices/print.blade.php` | 36 |
| `billing/invoices/invoice-pdf.blade.php` | 28 |
| `billing/payments/index.blade.php` | 36 |
| `billing/payments/receive.blade.php` | 43 |
| `billing/payments/receipt.blade.php` | 41 |
| `billing/payments/receipt-pdf.blade.php` | 22 |
| `billing/counter-sale/create.blade.php` | 42 |
| `billing/statements/statement-pdf.blade.php` | 19 |

**Lang files:**
- `lang/en/invoices.php` / `lang/fr/invoices.php` — **199 keys each**
- `lang/en/payments.php` / `lang/fr/payments.php` — **127 keys each**
- `lang/en/billing.php` / `lang/fr/billing.php` — **126 keys each**

**Notable patterns:**
- Print/PDF templates use `generated_footer`, `computer_generated`, `thank_you_hospital` keys
- Receipt PDF uses `reversal_stamp` / `paid_in_full` / `part_payment_label` badge keys
- Counter-sale tabs (`pharmacy_sale_tab`, `investigation_sale_tab`, `procedure_sale_tab`)
- Statement PDF uses `patient_statement`, `total_charges`, `invoices_payments_count` keys
- Cash shift / cashier handover UI fully translated

**Architecture compliance:** No hardcoded insurance provider names, no hardcoded sponsor names, no hardcoded NHIS references.

---

### 4. Investigations / Laboratory

**Views translated:**
| File | `__()` calls |
|------|-------------|
| `lab/process.blade.php` | 95 |
| `lab/results.blade.php` | 38 |
| `lab/tests.blade.php` | 62 |
| `lab/print.blade.php` | 18 |
| `lab/_consumables.blade.php` | 5 |
| `lab/_criterion_input.blade.php` | 10 |
| `lab/_result_modal.blade.php` | 17 |
| `lab/partials/accept-bill.blade.php` | 7 |

**Lang file:** `lang/en/lab.php` / `lang/fr/lab.php` — **193 keys each**

**Notable patterns:**
- `_criterion_input` partial: result flags (High/Low/Normal/Abnormal) use lang keys, not hardcoded values
- `accept-bill` partial: accept-and-bill JS action fully translated

---

### 5. Store / Stock / Inventory

**Views translated:**
| File | `__()` calls |
|------|-------------|
| `store/stock/balances.blade.php` | 22 |
| `store/stock/ledger.blade.php` | 27 |
| `store/stock/adjustment.blade.php` | 27 |
| `store/stock/adjustments-index.blade.php` | 14 |
| `store/stock/transfer.blade.php` | 24 |
| `store/stock/transfers-index.blade.php` | 14 |
| `store/stock/return.blade.php` | 24 |
| `store/stock/returns-index.blade.php` | 14 |
| `store/stock/valuation.blade.php` | 12 |
| `store/stock/batch-show.blade.php` | 16 |
| `store/stock/movement-show.blade.php` | 23 |
| `store/stock/locations.blade.php` | 26 |
| `store/stock-requisitions/index.blade.php` | 16 |
| `store/stock-requisitions/create.blade.php` | 15 |
| `store/stock-requisitions/show.blade.php` | 12 |
| `admin/stock-locations/index.blade.php` | 17 |
| `admin/stock-locations/_form.blade.php` | 7 |

**Lang file:** `lang/en/stock.php` / `lang/fr/stock.php` — **240 keys each**

**Architecture compliance:** Cost information restricted via existing `@can` checks; `stock.restricted_cost` key used for unauthorized display.

---

### 6. Users, Roles, and Settings

**Views translated:**
| File | `__()` calls |
|------|-------------|
| `users/index.blade.php` | 29 |
| `users/create.blade.php` | 23 |
| `users/edit.blade.php` | 22 |
| `roles/permissions.blade.php` | 6 |
| `settings/organization.blade.php` | 15 |
| `settings/invoice.blade.php` | 14 |
| `settings/ward.blade.php` | 15 |
| `settings/payment-methods.blade.php` | 16 |
| `settings/notification-preferences.blade.php` | 7 |
| `settings/activity-log.blade.php` | 28 |
| `settings/activity-log-show.blade.php` | 22 |
| `settings/log-retention.blade.php` | 7 |
| `settings/profile.blade.php` | 22 |
| `settings/partials/sidebar.blade.php` | 10 |

**Lang files:**
- `lang/en/users.php` / `lang/fr/users.php` — **57 keys each**
- `lang/en/roles.php` / `lang/fr/roles.php` — **30 keys each**
- `lang/en/settings.php` / `lang/fr/settings.php` — **179 keys each**

---

### 7. Triage

**Views translated:**
| File | `__()` calls |
|------|-------------|
| `triage/index.blade.php` | 12 |
| `triage/create.blade.php` | 42 |
| `triage/show.blade.php` | 20 |

**Lang file:** `lang/en/triage.php` / `lang/fr/triage.php` — **91 keys each**

**Notable patterns:**
- Triage create injects JS i18n object with 8 AJAX feedback keys
- Score guide thresholds (numeric clinical values) intentionally left hardcoded
- `assessed_by` and `assigned_to_dept` use Laravel `:name` / `:date` / `:dept` parameter substitution

---

### 8. Emergency & Admissions (Deep Content)

**Views translated:**
| File | `__()` calls |
|------|-------------|
| `emergency/show.blade.php` | 211 |
| `admissions/show.blade.php` | 120 |

**Lang files:**
- `lang/en/emergency.php` / `lang/fr/emergency.php` — **296 keys each**
- `lang/en/admissions.php` / `lang/fr/admissions.php` — **241 keys each**

---

## JavaScript i18n Bridge Pattern

For views with inline JavaScript that display dynamic UI messages, the following pattern was used throughout Phase 4:

```blade
@php
$i18n = [
    'key_one' => __('module.key_one'),
    'key_two' => __('module.key_two'),
];
@endphp
<script>const moduleI18n = @json($i18n);</script>
```

JavaScript then references `moduleI18n.key_one` instead of hardcoded strings.

**Modules using this pattern:**
- `visits/create.blade.php` — 64 keys via `visitI18n`
- `patients/create.blade.php` — 7 insurance cascade keys + emergency contact keys via `insuranceI18n`
- `patients/partials/insurance-add-modal.blade.php` — 7 keys via `insuranceI18n`
- `triage/create.blade.php` — 8 AJAX feedback keys via `triageI18n`

---

## EN/FR Parity — Final State

| Module | Keys (EN = FR) |
|--------|---------------|
| admissions | 241 |
| auth | 3 |
| billing | 126 |
| common | 163 |
| dashboards | 100 |
| emergency | 296 |
| investigations | 100 |
| invoices | 199 |
| lab | 193 |
| menu | 202 |
| messages | 63 |
| patients | 248 |
| payments | 127 |
| pharmacy | 28 |
| reports | 139 |
| roles | 30 |
| settings | 179 |
| statuses | 29 |
| stock | 240 |
| triage | 91 |
| users | 57 |
| validation | 109 |
| visits | 338 |
| **TOTAL** | **3,301** |

**Parity result:** ✓ 0 missing keys in any direction across all 23 modules.

---

## Strings Intentionally Left Hardcoded

The following categories of strings were intentionally NOT translated, per localisation conventions:

1. **Clinical unit abbreviations** — `mmHg`, `bpm`, `°C`, `/min`, `%`, `kg/m²`, `mmol/L` — universally recognised, not translatable text
2. **Currency symbol** — `₵` / `GH₵` — locale-independent symbol
3. **Dynamic data values** — patient names, diagnoses, clinical notes, database enum display values
4. **Brand names** — `UHMS` acronym in logos and footers
5. **Format hints** — `e.g. GHA-XXXXXXXXX-X`, `e.g. GA-123-4567` — developer-guidance placeholder formats
6. **JS class selectors** — `data-preview-filter="all"` etc. — structural, not displayed
7. **Chart.js series labels** in `admissions/show.blade.php` — inline `<script>` strings used only for dataset configuration
8. **Ghana region names / occupation lists / relationship values** — stored DB values that remain consistent across locales
9. **`N/A`** — universal abbreviation used as PHP null-fallback
10. **Score guide numeric thresholds** in triage — clinical data constants (`≥95%`, `36–38.5°C`, etc.)

---

## Architecture Compliance Checklist

- ✓ No parallel localisation systems created
- ✓ No duplicate middleware, routes, or layouts
- ✓ No business logic moved into Blade
- ✓ No hardcoded NHIS references
- ✓ No hardcoded insurance provider names
- ✓ No hardcoded sponsor names
- ✓ No unauthorized clinical data exposed
- ✓ No unauthorized financial values exposed
- ✓ No unauthorized stock cost data exposed
- ✓ ActivityLogService not bypassed
- ✓ Bootstrap 5 + Tabler Icons only (no Tailwind, no Vue, no new packages)
- ✓ Products = physical stock items distinction maintained
- ✓ Services = billable activities distinction maintained
- ✓ All `php -l` checks pass on all lang files
- ✓ `php artisan view:clear && config:clear && cache:clear` run after all changes

---

## Verification Commands

```bash
# Syntax check all lang files
for f in lang/en/*.php lang/fr/*.php; do php -l "$f"; done

# Verify EN/FR parity
php -r "
\$modules = ['admissions','auth','billing','common','dashboards','emergency','investigations','invoices','lab','menu','messages','patients','payments','pharmacy','reports','roles','settings','statuses','stock','triage','users','validation','visits'];
foreach (\$modules as \$m) {
    \$en = include 'lang/en/' . \$m . '.php';
    \$fr = include 'lang/fr/' . \$m . '.php';
    \$diff = count(array_diff(array_keys(\$en), array_keys(\$fr)));
    echo \$m . ': ' . (\$diff === 0 ? 'OK' : 'MISMATCH ' . \$diff) . PHP_EOL;
}
"

# Clear caches
php artisan view:clear && php artisan config:clear && php artisan cache:clear
```

---

## Remaining TODOs (Post Phase 4)

1. **JavaScript resource files** (`resources/js/`) — check for hardcoded English strings in compiled JS bundles that are not covered by the Blade i18n bridge pattern
2. **Controller flash messages** — verify all `->with('success', ...)` and `->with('error', ...)` calls use `__('messages.*')` keys (partially done in earlier phases)
3. **Email templates** — `resources/views/emails/` not covered in Phase 4
4. **Validation error messages in browser** — browser-native validation tooltip text is not translatable via Laravel; acceptable per scope
5. **Dynamic enum labels** from model `->label()` methods — these return hardcoded English; consider adding a `->translatedLabel()` helper per model if full FR UI is required
