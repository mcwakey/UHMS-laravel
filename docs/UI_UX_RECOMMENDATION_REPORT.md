# UHMS UI/UX Recommendation Report

This report turns the [gap analysis](UI_UX_GAP_ANALYSIS.md) into concrete, **non-breaking** recommendations. The binding rules live in [UHMS_UI_THEME_RULES.md](UHMS_UI_THEME_RULES.md); component usage in [UI_COMPONENT_STANDARDS.md](UI_COMPONENT_STANDARDS.md).

## Guiding principle

UHMS is clinical software: **clarity beats decoration**. Standardise on what already works (Bootstrap 5 + Tabler + the Reports/Statistics layout) and remove drift — do **not** redesign or re-skin.

## 1. Global layout standard

Every page:

```
<x-page-header>           ← title, description, primary actions (top-right)
[ KPI / summary cards ]   ← optional
[ FilterBar (GET form) ]  ← list pages
Main content              ← table / form / clinical document / board / timeline
[ secondary content ]     ← notes, logs
Pagination (bottom-right)
```

## 2. Colour system

Use Bootstrap contextual variants with **one meaning each** (see `config/ui.php`):
success=done/paid/available, info=active/due, warning=pending/low/held, danger=critical/overdue/out, secondary=inactive/cancelled, dark=death/black-triage, primary=brand/admitted. Never give one colour two meanings (e.g. red must not also mean "completed").

## 3. Status badges → adopt `<x-status-badge>`

Replace inline `badge bg-*` with `<x-status-badge :status="$x->status" domain="invoice"/>`. Colours come from `config/ui.php`, text is humanised automatically, and contrast (`text-dark` on tints) is handled. Roll out per module starting with **Blood Bank, Billing, MAR, Emergency** (highest-risk statuses).

## 4. Typography & spacing

Reuse existing tokens: page title `h4.fw-bold`, section title `h5/h6.card-title`, body default, small `.fs-12/.fs-13`. Page gap = `--uhms-content-gap` (1rem); card padding 1rem; grids use `g-2`/`g-3`.

## 5. Buttons (hierarchy)

| Role | Class |
|---|---|
| Primary (one per area) | `btn btn-primary` |
| Secondary | `btn btn-outline-secondary` |
| Destructive | `btn btn-danger` (or `btn-outline-danger`) **+ confirmation** |
| Ghost/link | `btn btn-link` |
| Icon-only | `btn` + **`aria-label`** + Tabler icon |

Action order in tables/menus: **View → Edit → Print → Cancel/Delete** (danger last).

## 6. Forms

Group into `<x-form-section>`-style fieldsets; required `*`; per-field validation (`@error('field')`); searchable Select2 for long lists; consistent save/cancel; disable submit while saving; success/error feedback.

## 7. Tables

`table table-hover align-middle` inside `.table-responsive`; right-aligned action column; `{{ $x->links() }}` bottom-right; `<x-empty-state>` for no-data.

## 8. Modals & drawers

Small create/edit/confirm → modal. Large clinical content (consultation summary, MAR chart, theatre/emergency case, visit preview) → **full page or drawer**, never a cramped modal. Backdrop cleanup is already centralised — keep using the layout's helpers. Destructive modals require reason text (reuse `UhmsConfirmDialog.vue` in Vue screens; SweetAlert2 confirm in Blade).

## 9. Filters/search

Standard GET-form card: search, date range, department, status, **Apply** + **Reset**; preserve state via query string; never break pagination.

## 10. Feedback

Keep server flash → Bootstrap alerts for page reloads; use **SweetAlert2 toast** for async actions; always confirm destructive actions; never fail silently; never show raw SQL.

## 11. Clinical documents & print

Document-style container, clear patient header, section headings, chronological order, authorship visible, print-friendly (hide chrome, black-on-white, signatures). Applies to invoice, receipt, consultation summary, visit preview, MAR chart, lab result, theatre report, blood issue/transfusion report, claims.

## 12. Dashboards

KPI cards + trend charts + priority lists + filter. Each dashboard answers one question; don't overload.

## 13. Accessibility

Status badges carry **text** (delivered); add `aria-label` to icon-only buttons; keep the focus-ring token; ensure contrast; never rely on colour alone.

## 14. Permission / module UI

Hide actions the user lacks permission for (`@can`), and keep backend enforcement. Hide disabled-module menu items (already done by `SidebarMenuBuilder`); block direct URLs (module middleware already does this) and show a friendly disabled-module page (TODO).

## 15. Implementation roadmap

| Phase | Scope | Risk |
|---|---|---|
| **1 (done)** | `config/ui.php`, `<x-status-badge>`, `<x-page-header>`, `<x-empty-state>`, docs, tests | none (additive) |
| **2** | Roll `<x-status-badge>` into Blood Bank, Billing, MAR, Emergency, Stock | low |
| **3** | Roll `<x-page-header>` + `<x-empty-state>` across list pages | low |
| **4** | Friendly error/permission/disabled-module pages; SweetAlert2 toast helper | low/med |
| **5** | Extract `<x-stat-card>`, `<x-filter-bar>`, `<x-data-table>` from repeated markup | med |
| **6** | Field-level validation + confirmation sweep on destructive actions | med |

## 16. Refactor first (highest leverage)

1. Blood Bank views (raw status strings) → `<x-status-badge>`.
2. Billing/invoice status colours → `domain="invoice"`.
3. MAR & Emergency triage → `domain="mar"`/`domain="triage"`.
4. List-page headers → `<x-page-header>`.
