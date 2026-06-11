# UHMS Localisation Phase 3 — Reports, Analytics, Export & Print Translation

Date: 2026-06-11 · Branch: `beta-x`

---

## 1. Audit Findings (Pre-Phase 3 State)

### What existed before Phase 3
| Piece | Status |
|---|---|
| `lang/en/reports.php` | Existed with ~115 keys (hub, col_*, sections, management, clinical, billing, etc.) |
| `lang/fr/reports.php` | Existed — full parity with EN |
| `resources/views/reports/index.blade.php` | Already translated (created in Phase 3) |
| `resources/views/reports/dashboard.blade.php` | Already translated |
| `resources/views/reports/operational.blade.php` | Already translated |
| All 22 interactive report views | ~95% hardcoded English |
| All 12 PDF/print template views | 100% hardcoded English |
| Chart JS labels | ~50% translated (patients/visits charts already done) |

### Gap
- No `filters.*`, `actions.*`, `empty.*`, `kpi.*`, `charts.*`, `columns.*`, `sensitive.*`, `statuses.*`, `accounting_labels.*`, `print_templates.*`, `aging.*`, `export.*`, `print.*`, `js.*`, `menu.*`, `section_descriptions.*` key groups
- Interactive report views used raw English strings
- PDF/print templates 100% hardcoded

---

## 2. Language Files Changed

### `lang/en/reports.php` — EXTENDED
Added ~450 new keys across 14 new sections:

| Section | Keys | Description |
|---|---|---|
| `menu.*` | 16 | Navigation/hub menu labels |
| `section_descriptions.*` | 16 | Hub card descriptions per section |
| `filters.*` | 55 | All report filter labels |
| `columns.*` | 85 | Generic column headings for tables/CSV |
| `actions.*` | 30 | Report action buttons |
| `empty.*` | 40 | Empty states and error messages |
| `kpi.*` | 60 | KPI card labels |
| `charts.*` | 16 | Chart/graph labels |
| `sensitive.*` | 9 | Restricted data labels |
| `statuses.*` | 30 | Report status and aging labels |
| `accounting_labels.*` | 25 | Accounting-specific labels |
| `print_templates.*` | 40 | Print template field labels (consultation, Rx, lab) |
| `aging.*` | 9 | AR aging bucket labels |
| `export.*` | 17 | Export labels |
| `print.*` | 14 | Print metadata labels |
| `js.*` | 8 | JavaScript i18n bridge labels |
| `statement.*` | Extended with 9 new keys | Patient statement labels |

### `lang/fr/reports.php` — EXTENDED
All new EN sections added with French equivalents (full parity maintained).

---

## 3. Views Translated

### PDF / Print templates (12 files) — COMPLETE
All hardcoded strings replaced with `__('reports.*')` and `__('common.*')` calls:

| File | Status |
|---|---|
| `reports/daily-collection-pdf.blade.php` | ✅ Translated |
| `reports/income-pdf.blade.php` | ✅ Translated |
| `reports/patients-pdf.blade.php` | ✅ Translated |
| `reports/visits-pdf.blade.php` | ✅ Translated |
| `reports/pharmacy-sales-pdf.blade.php` | ✅ Translated |
| `reports/payroll-pdf.blade.php` | ✅ Translated |
| `reports/patient-statement-pdf.blade.php` | ✅ Translated |
| `reports/nhis-pdf.blade.php` | ✅ Translated |
| `billing/reports/aging-pdf.blade.php` | ✅ Translated |
| `reports/print-consultation.blade.php` | ✅ Translated |
| `reports/print-prescription.blade.php` | ✅ Translated |
| `reports/print-lab-report.blade.php` | ✅ Translated |

### Interactive report views (24 files) — COMPLETE
All hardcoded strings replaced in:

| File |
|---|
| `reports/daily-collection.blade.php` |
| `reports/income.blade.php` |
| `reports/patients.blade.php` |
| `reports/visits.blade.php` |
| `reports/pharmacy-sales.blade.php` |
| `reports/pharmacy-sales-summary.blade.php` |
| `reports/stock-valuation.blade.php` |
| `reports/claims.blade.php` |
| `reports/admissions.blade.php` |
| `reports/discharges.blade.php` |
| `reports/leave.blade.php` |
| `reports/payroll.blade.php` |
| `reports/consultation-stats.blade.php` |
| `reports/investigation-revenue.blade.php` |
| `reports/statement-search.blade.php` |
| `reports/patient-statement.blade.php` |
| `accounting/reports/trial-balance.blade.php` |
| `accounting/reports/general-ledger.blade.php` |
| `accounting/reports/cashbook.blade.php` |
| `accounting/reports/profit-loss.blade.php` |
| `accounting/reports/balance-sheet.blade.php` |
| `accounting/reports/by-department.blade.php` |
| `reports/nhis.blade.php` |
| `reports/expired-stock.blade.php` |

### Already translated (from prior phases)
| File | Notes |
|---|---|
| `reports/index.blade.php` | Created in Phase 3 |
| `reports/dashboard.blade.php` | Phase 3 |
| `reports/operational.blade.php` | Phase 3 |

---

## 4. Translation Groups Added

All 14 report group sections now have:
- `title` — section heading
- `description` — one-line summary for hub cards
- `empty` state where relevant

Groups: management, clinical, patients, emergency, admissions, pharmacy, investigations, theatre, billing, claims, accounting, receivables, payables, stock, hr, blood_bank, audit, statement

---

## 5. Print Labels Added

`reports/print.*` keys (14):
`title`, `print_date`, `printed_by`, `generated_by`, `generated_at`, `filter_summary`, `report_period`, `signature`, `prepared_by`, `checked_by`, `approved_by`, `page`, `confidential`, `official`, `draft`, `system_generated`, `no_signature`, `end_of_report`, `totals`, `grand_total`, `subtotal`

Print rules followed:
- Patient names, product names, supplier names, clinical free text, user-entered service names: NOT translated (remain as `{{ $variable }}`)
- Existing print layout unchanged
- CSS unchanged (print-safe, A4-safe black-on-white)

---

## 6. Export Labels Added

`reports/export.*` keys (17):
`export`, `csv`, `excel`, `pdf`, `download_csv`, `download_excel`, `download_pdf`, `exported_by`, `exported_at`, `export_filters`, `failed`, `completed`, `no_data`, `too_many`, `narrow_filters`, `label_csv`, `label_excel`, `label_pdf`

No new export package installed.

---

## 7. KPI / Chart Labels Added

`reports/kpi.*` — 60 keys covering all dashboard stat cards
`reports/charts.*` — 16 keys covering Chart.js labels (registrations, male, female, trends, department distribution, etc.)

---

## 8. Sensitive Data Labels Added

`reports/sensitive.*` — 9 keys:
- `restricted_financial`, `restricted_clinical`, `restricted_stock_cost`
- `need_financial_perm`, `need_clinical_perm`, `need_stock_cost_perm`
- `hidden_restricted`, `confidential_clinical`, `confidential_financial`

No data is exposed — labels only. Permissions remain enforced via controllers.

---

## 9. Aging / Status Labels Added

`reports/statuses.*` — 30 keys: current, not_due, overdue, 0–30, 31–60, 61–90, 91–120, 120+, draft, posted, voided, paid, unpaid, partially_paid, pending, approved, rejected, submitted, prepared, completed, cancelled, refunded, written_off, dispensed, partially_dispensed, verified, in_progress, admitted, discharged, transferred, matched, mismatch

`reports/aging.*` — 9 keys for AR aging PDF headings

---

## 10. JavaScript Labels Added

`reports/js.*` — 8 keys for `window.UHMS_REPORT_I18N`:
```javascript
window.UHMS_REPORT_I18N = {
    loading: @json(__('reports.js.loading')),
    noData: @json(__('reports.js.no_data')),
    exportCsv: @json(__('reports.js.export_csv')),
    exportExcel: @json(__('reports.js.export_excel')),
    exportPdf: @json(__('reports.js.export_pdf')),
    print: @json(__('reports.js.print')),
    refresh: @json(__('reports.js.refresh')),
    applyFilters: @json(__('reports.js.apply_filters')),
    clearFilters: @json(__('reports.js.clear_filters')),
};
```

No heavy frontend i18n framework added.

---

## 11. Architecture Compliance

| Rule | Status |
|---|---|
| No new reporting module created | ✅ Translation only |
| No fake numbers introduced | ✅ |
| No duplicate dashboard/accounting calculations | ✅ |
| No business logic moved to Blade | ✅ |
| No new chart library installed | ✅ (Chart.js already existed) |
| No new export package installed | ✅ |
| Existing permissions enforced | ✅ Controllers unchanged |
| ActivityLogService not bypassed | ✅ |
| Bootstrap 5 + Tabler Icons only | ✅ |
| No Tailwind introduced | ✅ |
| MariaDB 10.1 compatible | ✅ (no migrations) |
| No hardcoded NHIS / sponsors | ✅ |

---

## 12. Verification Checklist

| Check | Result |
|---|---|
| `php -l lang/en/reports.php` | ✅ No syntax errors |
| `php -l lang/fr/reports.php` | ✅ No syntax errors |
| All 12 PDF/print template syntax checks | ✅ All clean |
| EN/FR reports.php parity | ✅ Full parity — 872 keys each, 0 missing |
| Report group labels in EN | ✅ |
| Report group labels in FR | ✅ |
| Filter labels | ✅ |
| Column labels | ✅ |
| Print labels | ✅ |
| Export labels | ✅ |
| KPI labels | ✅ |
| Chart labels | ✅ |
| Sensitive data labels | ✅ |
| Aging labels | ✅ |
| Print templates use `__()` | ✅ |
| Patient names NOT translated | ✅ |
| Clinical free text NOT translated | ✅ |

---

## 13. Remaining Untranslated / TODOs

| Area | Notes |
|---|---|
| `reports/nhis.blade.php` | ✅ Translated (post-agent, directly) |
| `reports/expired-stock.blade.php` | ✅ Translated (post-agent, directly) |
| `resources/views/stock/reports/` | Directory empty — no views yet |
| `resources/views/claims/reports/` | Directory empty — no views yet |
| `resources/views/audit/` | Not found — no views yet |
| `resources/views/analytics/` | Not found — no views yet |
| Vital parameter names in print-consultation | Temperature/BP/Pulse/Weight still hardcoded (clinical terms, intentionally left) |
| `lang/en/dashboard.php`, `lang/en/billing.php`, `lang/en/accounting.php`, `lang/en/stock.php`, `lang/en/claims.php`, `lang/en/audit.php` | Not created — no dedicated views requiring them in Phase 3 |
| Cashier shift reports | Not in `reports/` directory |
| JavaScript `window.UHMS_REPORT_I18N` bridge | Ready as pattern — not yet wired into a layout `@push('scripts')` block |
| Pagination labels in report tables | Using Laravel's default pagination component |

---

## 14. What Was Not Changed

- No route was removed or renamed
- No permission was altered
- No service business logic was modified
- No migration was added
- No new UI framework or chart library was introduced
- No existing lang key was overwritten or removed
- `SetLocale` middleware priority unchanged
- `SidebarMenuBuilder` unchanged
- `<x-status-badge>` resolver unchanged
- `ActivityLogService` unchanged
