# UI Phase 5 — Reusable Component Extraction & Rollout

Additive, safe extraction of six repeated UI patterns into shared Blade components, plus a representative adoption. No business logic, routes, permissions, or CSS framework changed.

## 1. Components created
| Component | File |
|---|---|
| `<x-stat-card>` | `resources/views/components/stat-card.blade.php` |
| `<x-filter-bar>` | `resources/views/components/filter-bar.blade.php` |
| `<x-data-table>` | `resources/views/components/data-table.blade.php` |
| `<x-action-menu>` | `resources/views/components/action-menu.blade.php` |
| `<x-confirm-form>` | `resources/views/components/confirm-form.blade.php` |
| `<x-print-layout>` | `resources/views/components/print-layout.blade.php` |

All are pure Bootstrap 5 + Tabler, accessible, and merge extra attributes via `$attributes->merge()`. Full usage docs in `docs/UI_COMPONENT_STANDARDS.md`.

## 2. Props supported
- **stat-card:** title, value, subtitle, icon, variant(`primary`), route, trend, format(`number|currency|percent`). Clickable via Bootstrap `.stretched-link` (accessible aria-label).
- **filter-bar:** action(current URL), method(`GET`), resetUrl, title, icon, collapsible, applyLabel, resetLabel. Apply + Reset auto-added; query string preserved.
- **data-table:** paginator, striped, hover(true), alignMiddle(true), responsive(true), tableClass, pagination(true). `head` slot for `<thead>`, default slot for rows. Pagination bottom-right with `withQueryString()`.
- **action-menu:** label(aria), icon(`ti-dots-vertical`), align(`end`), size(`sm`).
- **confirm-form:** action, method(`POST`+spoofing), buttonLabel, buttonClass(`btn btn-danger`), icon, confirmTitle, confirmText, confirmButton, cancelButton, requireReason, reasonName, reasonPlaceholder, disabled, disabledReason. CSRF + SweetAlert2 (native fallback) + reason capture + double-submit guard.
- **print-layout:** title, patient, visit, subtitle, generatedAt, signatures. Standalone print-friendly document shell.

## 3. Pages updated (representative adoption)
| Component | Page | What changed |
|---|---|---|
| `<x-stat-card>` | `statistics/dashboard`, `statistics/show` | KPI grids now use the component (formatting + clickable route preserved) |
| `<x-filter-bar>` | `statistics/show` | Date filter form → filter-bar (Apply/Reset auto) |
| `<x-empty-state>` | `statistics/show` | Remaining `No data.` cell (dynamic colspan, missed by the bulk sweep) |
| `<x-data-table>` | `blood-bank/units` | Card + `.table-responsive` + table + pagination → data-table |
| `<x-action-menu>` | `insurance/index` | Row action dropdown → action-menu |
| `<x-confirm-form>` | `insurance/index` | Provider activate/deactivate toggle now **confirmed** before submit |
| `<x-print-layout>` | — | Component created + tested; adoption recommended gradually for new/simple print views |

## 4. Pages intentionally skipped (this pass)
- **Existing standalone print views** (`reports/*-pdf`, `print-consultation`, `print-prescription`, `print-lab-report`) are full bespoke documents; converting them risks breaking working print routes. `<x-print-layout>` is ready and tested — adopt gradually, simplest documents first.
- **Statistics list cards** keep their custom header (drilldown button) rather than `<x-data-table>` (which has no header slot) — only their empty cell was standardised.
- **Complex clinical tables** (MAR grid, theatre board, consultation show) were not converted — out of a safe first-pass scope.

## 5. Issues found
- `<x-data-table>` initial table-class order put `mb-0` before `table-hover`; reordered to the theme-canonical `table table-hover align-middle mb-0`.
- Blade escapes apostrophes/HTML in props (expected) and `text-uppercase` is CSS — test assertions adjusted accordingly (no component change needed).
- No broken markup or duplicated components found.

## 6. Tests added/run
- **`tests/Feature/UiPhase5ComponentsTest.php`** (9 tests): stat-card (title/value/icon/variant + currency + clickable route), filter-bar (form/apply/reset/slot), data-table (responsive wrapper + head/body + pagination), action-menu (accessible dropdown), confirm-form (method spoofing + CSRF + confirm attributes + disabled reason), print-layout (document shell).
- **Existing `tests/Feature/UiComponentsTest.php`** (12) still pass.
- Regression: **45 tests pass** (UI ×21, Statistics, Blood Bank); **all 534 views compile**; `StatisticsTest` confirms the stat-card/filter-bar/empty-state pages render at runtime.

## 7. Remaining component rollout TODOs
- Roll `<x-stat-card>` into the remaining dashboards (Blood Bank, Emergency, Admission, Stock, Billing, Reports dashboard).
- Roll `<x-filter-bar>` into the high-traffic list filters (Patients, Visits, Billing, Stock, Logs, Notifications, Roles).
- Roll `<x-data-table>` into clean list tables (Patients, Visits, Billing, Pharmacy, Stock, Logs, Notifications).
- Roll `<x-action-menu>` into list tables that have several row actions.
- Roll `<x-confirm-form>` into the high-risk actions list (reverse payment, refund, stock adjustment, patient merge execute, mark deceased, discharge, dispose emergency case, cancel theatre case, override triage, emergency/incompatible blood release, disable module) — replacing native `onsubmit="return confirm()"`.
- Adopt `<x-print-layout>` for printable documents, simplest first.
