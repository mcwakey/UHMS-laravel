# UHMS Localisation Phase 2 — High-Traffic Screen Translation & UI Text Standardisation

Date: 2026-06-11 · Branch: `beta-x`

This phase extends the EN/FR localisation infrastructure introduced in Phase 1 to cover
high-traffic operational screens, all 74 admin controller flash messages, and the Report
Hub — without duplicating or replacing any Phase 1 infrastructure.

---

## 1. Audit Findings (Pre-Phase 2 State)

### What existed from Phase 1
| Piece | Location | Notes |
|---|---|---|
| Locale middleware | `app/Http/Middleware/SetLocale.php` | Wired in `bootstrap/app.php` |
| User locale column | `users.locale` | Migration already ran |
| Language switcher | `layouts/partials/header.blade.php` | EN/FR in user dropdown |
| `lang/en/common.php` + FR | Full common action/label vocabulary | 80+ keys |
| `lang/en/menu.php` + FR | 202 sidebar labels via `translateLabel()` | |
| `lang/en/statuses.php` + FR | 29 domains, all enum display labels | |
| `lang/en/reports.php` + FR | 40+ report catalogue entries | |
| `lang/en/pharmacy.php` + FR | Dispensing queue keys only | Extended in Phase 2 |
| `<x-status-badge>` | Resolves via statuses lang files | |
| `<x-empty-state>`, `<x-confirm-form>`, `<x-filter-bar>`, `<x-print-layout>` | Translated | |

### What was missing / hardcoded
- All 74 admin controllers used hardcoded English strings in `->with('success', '...')` / `->with('error', '...')`
- `admissions/` views: all 5 Blade views had hardcoded English UI text
- `emergency/` views: all 5 Blade views had hardcoded English UI text
- `pharmacy/drugs.blade.php`: hardcoded page title, filter labels, table headers
- `reports/dashboard.blade.php` and `reports/operational.blade.php`: hardcoded strings
- No `lang/en/admissions.php`, `lang/fr/admissions.php`
- No `lang/en/emergency.php`, `lang/fr/emergency.php`
- No `lang/en/messages.php`, `lang/fr/messages.php`
- No Reports Hub (single consolidated report portal)

---

## 2. Files Added

### Language files
| File | Keys | Notes |
|---|---|---|
| `lang/en/messages.php` | ~300 | Flash messages for all 74 controllers, 32 sections |
| `lang/fr/messages.php` | ~300 | French equivalents |
| `lang/en/admissions.php` | ~110 | All admissions UI text (index, create, discharge, requests, show) |
| `lang/fr/admissions.php` | ~110 | French equivalents |
| `lang/en/emergency.php` | ~90 | All emergency UI text (board, create, bays, reports, show) |
| `lang/fr/emergency.php` | ~90 | French equivalents |

### Services / Controllers
| File | Notes |
|---|---|
| `app/Services/ReportRegistryService.php` | Permission-aware report catalogue; 40+ entries, 16 sections |
| `app/Http/Controllers/Admin/ReportsHubController.php` | Thin controller feeding the hub view |

### Views
| File | Notes |
|---|---|
| `resources/views/reports/index.blade.php` | Reports Hub — permission-filtered, grouped sections |

---

## 3. Files Modified

### Language files extended
| File | Keys added | Notes |
|---|---|---|
| `lang/en/reports.php` | 65 `col_*` table header keys | Operational report column headers |
| `lang/fr/reports.php` | 65 `col_*` table header keys | French equivalents |
| `lang/en/pharmacy.php` | 12 keys | Drug catalogue page labels |
| `lang/fr/pharmacy.php` | 12 keys | French equivalents |

### Views translated
| View | Strings translated | Notes |
|---|---|---|
| `resources/views/admissions/index.blade.php` | Full | Stats, filters, table, actions |
| `resources/views/admissions/create.blade.php` | Full | All form sections, labels, billing preview |
| `resources/views/admissions/discharge.blade.php` | Full | Form, summary sidebar |
| `resources/views/admissions/requests.blade.php` | Full | Header, filters, table, actions |
| `resources/views/admissions/show.blade.php` | Key strings | Header, stat cards, allergies, med admin title |
| `resources/views/emergency/board.blade.php` | Full | Stats, filters, table, row labels, alerts |
| `resources/views/emergency/create.blade.php` | Full | Patient identity, arrival/assignment sections |
| `resources/views/emergency/bays.blade.php` | Full | Create form, bay status table |
| `resources/views/emergency/reports.blade.php` | Full | Filters, disposition cards, attendance table |
| `resources/views/emergency/show.blade.php` | Key strings | Header, action buttons |
| `resources/views/pharmacy/drugs.blade.php` | Full | Title, filter, all table headers |
| `resources/views/reports/dashboard.blade.php` | Full | All visible labels; hub link added |
| `resources/views/reports/operational.blade.php` | Full | Filter labels, column headers, hub link |

### Services
| File | Change |
|---|---|
| `app/Services/OperationalReportService.php` | `catalogue()` titles/descriptions use `__()` |
| `app/Services/OperationalReportService.php` | All 14 `table()` column arrays use `__('reports.col_*')` |
| `app/Services/SidebarMenuBuilder.php` | Added Reports Hub entry as first Reports section item |

### Routes
| File | Change |
|---|---|
| `routes/web.php` | `GET /admin/reports` → `ReportsHubController@index` (was `OperationalReportController@dashboard`) |
| `routes/web.php` | `GET /admin/reports/dashboard` → `OperationalReportController@dashboard` (preserved) |

### Controllers (via background agent)
All 74 `app/Http/Controllers/Admin/*.php` controllers had hardcoded `->with('success', '...')` /
`->with('error', '...')` strings replaced with `__('messages.SECTION.KEY')` calls.
Parameters (patient numbers, record IDs, etc.) use named placeholders:
```php
->with('success', __('messages.admissions.admitted', ['number' => $admission->admission_number]))
```

---

## 4. Modules Translated

| Module | Views | Controllers | Lang File |
|---|---|---|---|
| Admissions / Wards | ✅ All 5 views | ✅ flash messages | `admissions.php` added |
| Emergency | ✅ All 5 views | ✅ flash messages | `emergency.php` added |
| Pharmacy (drug catalogue) | ✅ `drugs.blade.php` | ✅ flash messages | `pharmacy.php` extended |
| Reports Hub | ✅ `index.blade.php` | ✅ `ReportsHubController` | `reports.php` extended |
| Reports Dashboard | ✅ `dashboard.blade.php` | — | `reports.php` extended |
| Operational Reports | ✅ `operational.blade.php` | — | `reports.php` col_* keys |
| All 74 controllers | — | ✅ 256 flash messages | `messages.php` added |

---

## 5. Architecture Compliance

| Rule | Status |
|---|---|
| No parallel localisation system created | ✅ Extended existing `lang/en/` + `lang/fr/` structure |
| No duplicate middleware | ✅ `SetLocale` middleware unchanged |
| No duplicate locale routes | ✅ `POST /locale` route unchanged |
| Bootstrap 5 + Tabler Icons only | ✅ No Tailwind introduced |
| Business logic not moved to Blade | ✅ All logic stays in services/controllers |
| `ActivityLogService` not bypassed | ✅ No controller logic changed except flash messages |
| `SidebarMenuBuilder` not broken | ✅ Reports Hub item added cleanly |
| Existing routes preserved | ✅ Old `admin.reports.dashboard` route still works |
| No hardcoded NHIS / sponsors / insurance | ✅ |
| MariaDB 10.1 compatible | ✅ No migrations in Phase 2 |

---

## 6. Translation Key Coverage

### `lang/en/messages.php` sections
```
admissions, patients, visits, appointments, triage, emergency,
billing, invoices, payments, claims, pharmacy, investigations,
theatre, admissions_clinical, stock, procurement, hr, users,
roles, departments, settings, lab, accounting, reports,
notifications, tasks, audit, sponsors, insurance, general
```

### Flash message parameter pattern
Dynamic values in messages use named placeholders — never string concatenation:
```php
__('messages.patients.registered', ['number' => $patient->patient_number])
__('messages.billing.invoice_created', ['number' => $invoice->invoice_number])
```

---

## 7. Verification Checklist

| Check | Result |
|---|---|
| `php artisan view:clear` | ✅ Clean |
| `php artisan config:clear` | ✅ Clean |
| `php artisan route:list --name=admin.reports` | ✅ `admin.reports.index` registered (ReportsHubController) |
| `php -l lang/en/admissions.php` | ✅ No syntax errors |
| `php -l lang/fr/admissions.php` | ✅ No syntax errors |
| `php -l lang/en/emergency.php` | ✅ No syntax errors |
| `php -l lang/fr/emergency.php` | ✅ No syntax errors |
| `php -l lang/en/messages.php` | ✅ No syntax errors |
| `php -l lang/fr/messages.php` | ✅ No syntax errors |
| `php -l lang/en/pharmacy.php` | ✅ No syntax errors |
| `php -l lang/fr/pharmacy.php` | ✅ No syntax errors |
| `php -l app/Services/ReportRegistryService.php` | ✅ No syntax errors |
| `php -l app/Http/Controllers/Admin/ReportsHubController.php` | ✅ No syntax errors |

---

## 8. Remaining Untranslated Areas (Known TODOs)

The following are out of scope for Phase 2 or deferred:

| Area | Notes |
|---|---|
| `patients/` views | High volume; `lang/en/patients.php` exists from Phase 1 — bulk Blade pass needed |
| `visits/` views | `lang/en/visits.php` exists — bulk Blade pass needed |
| `billing/` + `invoices/` + `payments/` views | `lang/en/billing.php` etc. exist — Blade pass needed |
| `investigations/` views | Lang file may need creation |
| `stock/` views | Lang file may need creation |
| `users/` + `settings/` views | `lang/en/users.php` / `settings.php` exist — Blade pass needed |
| `triage/` views | `lang/en/triage.php` exists — Blade pass needed |
| `emergency/show.blade.php` deep content | Only header/key strings translated; inner modals/forms have hardcoded text |
| `admissions/show.blade.php` deep content | Only header/key strings translated; inner ward round forms have hardcoded text |
| JavaScript inline strings (non-confirm) | Billing calculator labels, chart labels in dynamic JS blocks |
| Print/PDF templates (invoices, receipts) | Phase 1 translated `<x-print-layout>` chrome; content labels still hardcoded |
| Validation `attributes` array (FR) | `lang/fr/validation.php` exists but `attributes` array not populated |

---

## 9. What Was Not Changed

- No route was removed or renamed (all existing routes preserved)
- No permission was altered
- No service business logic was modified
- No migration was added
- No new UI framework was introduced
- No existing lang key was overwritten or removed
- `SetLocale` middleware priority unchanged
- `SidebarMenuBuilder` `translateLabel()` hook unchanged
- `<x-status-badge>` resolver unchanged
