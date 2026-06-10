# App Responsiveness + Localisation — Implementation Report

Date: 2026-06-10 · Branch: `beta-x`

This phase adds English/French localisation infrastructure and a responsive
hardening pass across the UHMS Blade + Bootstrap 5 UI, per `prompt.md`.

---

## 1. Localisation

### 1.1 Infrastructure

| Piece | Location | Notes |
|---|---|---|
| Language files | `lang/en/`, `lang/fr/` | `common`, `menu`, `statuses`, `dashboards` + full FR `auth`, `validation`, `passwords`, `pagination` |
| Locale middleware | `app/Http/Middleware/SetLocale.php` | Appended to the `web` group (before the Inertia middlewares) in `bootstrap/app.php` |
| User preference | `users.locale` (nullable `varchar(5)`) | Migration `2026_06_10_000001_add_locale_to_users_table.php`, ran clean on MariaDB 10.1 |
| Switch route | `POST /locale` → `locale.switch` | Validates against `SetLocale::SUPPORTED`; stores in session and (if logged in) on the user profile |
| Switcher UI | `layouts/partials/header.blade.php` | EN/FR buttons in the user dropdown, active state + checkmark, `data-spa-ignore` |

**Locale resolution order** (`SetLocale::handle`):
`user->locale` → `session('locale')` → browser `Accept-Language` → `config('app.locale')`.
`Carbon::setLocale()` is set alongside, so date diffs/format names follow the locale.
The switch route calls `app()->setLocale()` *before* flashing so the
"Language updated" message renders in the newly chosen language.

### 1.2 Menu translation (SidebarMenuBuilder)

Rather than editing ~200 label lines, a single `translateLabel()` step in
`app/Services/SidebarMenuBuilder.php` translates section titles
(`filterSection`) and item/child labels (`filterItem`). Keys are derived from
the English label — lowercase, non-alphanumeric runs collapsed to `_`
(e.g. `Visits / OPD` → `menu.visits_opd`, `Profit & Loss` → `menu.profit_loss`,
`ICD-10 Codes` → `menu.icd_10_codes`) — and applied only when `Lang::has()`
confirms the key, so an untranslated new menu entry falls back to its English
label instead of breaking. All 202 current labels resolve in both `en` and `fr`
(verified by script). Both the admin and consultation sidebars flow through
`finaliseSections`, so one hook covers both.

### 1.3 Status badges

`<x-status-badge>` now resolves the display text as:
explicit `label` prop → `statuses.{domain}.{status}` → `statuses.default.{status}`
→ enum `label()` → title-cased raw value. Colour resolution is unchanged.
The `statuses.php` files cover all 29 domains from `config/ui.php` with full
en/fr parity (verified by script). **Stored enum/DB values are never translated
— only the rendered label.**

### 1.4 Chrome, auth and shared components

- `layouts/app.blade.php` — `<html lang>` from `app()->getLocale()`; footer tagline,
  flash labels and the double-submit "Please wait…" text via `__()` (`@json` for the JS string).
- `layouts/auth.blade.php` — dynamic `<html lang>`.
- `auth/login`, `auth/forgot-password`, `auth/reset-password` — all visible strings translated.
- `layouts/partials/header.blade.php` — search placeholders, notifications block,
  profile dropdown, Staff fallback, language switcher.
- `<x-empty-state>`, `<x-confirm-form>`, `<x-filter-bar>` — translated defaults
  (callers passing explicit props are unaffected).
- `<x-print-layout>` — header tagline, Generated/Patient/Visit labels, Print/Back buttons.

### 1.5 Deliberately NOT translated

Per spec §"do not translate": route names, permission names, enum **stored**
values, clinical free-text (notes/diagnoses), patient/doctor/supplier names,
currency formatting (kept as-is). PDF/print money formats unchanged.

### 1.6 Remaining work (follow-up TODOs)

Per the prompt, legacy/vendor per-module pages were not bulk-translated. The
infrastructure makes them incremental: wrap strings in `__('module.key')` and
add keys. Highest-traffic candidates:

- Page-level headings/labels in `patients/`, `visits/`, `billing/`, `pharmacy/`,
  `laboratory/`, `dashboard/` views (page headers already pick up translated
  menu terms where they use shared components).
- Controller flash messages (currently English literals in many controllers).
- Form Request `attributes()`/custom messages (FR `validation.php` already
  covers the framework rules).
- DataTables/Select2 plugin UI strings (would need locale JSON files).
- PDF/print templates (kept English by design for now; decide per document).

## 2. Responsiveness

### 2.1 CSS layer (`public/build/css/uhms-design-system.css`)

Appended a responsive layer (Bootstrap utilities/breakpoints only, no new
frameworks):

- `≤991px` — large dropdowns (notifications/profile) clamped to viewport width;
  payment-queue scroll containment.
- `≤767px` — tighter card/content padding; stat-card min-height released;
  filter-bar fields and Apply/Reset stack full-width; pagination wraps;
  modal dialogs fit the viewport; ≥36px icon-button touch targets;
  breadcrumbs scroll horizontally instead of wrapping.
- `≤575px` — page-header action buttons stack; slightly smaller table font;
  nav-tabs become horizontally scrollable.
- `@media print` — topbar/sidebar/footer/action buttons hidden, content
  full-width, card shadows flattened.
- `.table-responsive` gets `-webkit-overflow-scrolling: touch`.
- Both layouts now cache-bust the stylesheet with `?v={filemtime}`.

### 2.2 Table audit

App-wide: 297 view files contain 437 `<table>`s; all interactive screens are
wrapped (`.table-responsive` or `<x-data-table>`) after this pass. Fixed in
this phase:

- `billing/counter-sale/create.blade.php` — 3 line-item tables wrapped.
- `accounting/reports/balance-sheet.blade.php` — Assets/Liabilities/Equity wrapped.
- `accounting/reports/profit-loss.blade.php` — main statement wrapped.

The remaining 19 unwrapped files are **PDF/print templates**
(`*-pdf.blade.php`, `print*.blade.php`, `payslip`, `receipt`) which are
intentionally fixed-width for paper output.

### 2.3 Existing behaviour preserved

Sidebar/topbar mobile behaviour (`#mobile_btn`, SimpleBar, scroll persistence),
the double-submit guard, modal cleanup and notification polling are untouched
apart from string translation.

## 3. Verification performed

All checks against the real dev stack (MariaDB 10.1, `php artisan serve`):

| Check | Result |
|---|---|
| `users.locale` migration on MariaDB 10.1 | ✅ ran clean |
| All 202 sidebar labels resolve in en+fr | ✅ script-verified |
| 29 status domains en/fr key parity | ✅ script-verified |
| `php artisan view:cache` (all Blade compiles) | ✅ clean |
| Guest: `Accept-Language: fr` → login in French | ✅ `lang="fr"`, «Connexion» |
| Guest: `POST /locale` (fr) persists via session | ✅ subsequent pages French |
| Authenticated: switch updates `users.locale` + UI | ✅ DB + render + flash in new language |
| Sidebar build in FR via tinker | ✅ «Menu principal / Tableau de bord», 17 sections |

**Manual browser checks still recommended** (no automated suite, and HTTP
checks can't see layout): 375/414/768/1024/1366/1920 widths on Dashboard,
Patients list, Visit detail, Invoice create/show, POS dispense, Lab results,
print preview of invoice/receipt — plus the FR↔EN switcher from the topbar
dropdown.

## 4. Conventions for future work

- New menu items: add the derived key to `lang/{en,fr}/menu.php` (lowercase,
  `_` for symbol runs). Missing keys fall back to the English label.
- New statuses: add to `config/ui.php` (colour) and `lang/{en,fr}/statuses.php`
  under the domain (lowercase key). Fallback chain keeps old behaviour.
- Page strings: `__('common.*')` for shared actions/labels; create
  `lang/{en,fr}/{module}.php` when a module gets a dedicated pass.
- Never translate stored values; translate only at render time.
