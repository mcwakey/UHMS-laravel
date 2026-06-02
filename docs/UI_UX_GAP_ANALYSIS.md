# UHMS UI/UX Gap Analysis

_Audit of the current UHMS frontend, grounded in the actual codebase (not assumptions)._

## 0. Stack reality (important)

UHMS is a **server-rendered Blade application** styled with **Bootstrap 5**, **Tabler Icons** (`ti ti-*`) and FontAwesome, with jQuery, DataTables, Select2, SweetAlert2, daterangepicker and Chart.js loaded globally in `resources/views/layouts/app.blade.php`.

- **531** Blade views vs **18** Inertia/Vue single-file components — the system is **Blade-dominant**, with a small Inertia/Vue footprint in a few modules (`Admissions`, `Billing`, `Lab`, `Pharmacy`, `Visits`, `Legacy`).
- There is **no Tailwind** (`tailwind.config.js` absent). Do **not** introduce Tailwind or a second CSS framework.
- A partial design system already exists: `resources/css/uhms-design-system.css` defines `:root` tokens (`--uhms-radius-*`, `--uhms-content-gap`, `--uhms-muted-bg`, `--uhms-table-header-bg`, `--uhms-focus-ring`) and base rules for `.uhms-page-header`, `.card`, etc.
- Existing reusable pieces: Blade `components/{footer,modal-popup,settings-sidebar}`, partials `{patient-card,patient-visit-header,consultation-clinical-sections}`; Vue `Components/{Can,UhmsConfirmDialog,ComplaintSelector}`.

> The original brief assumed Inertia/Vue + Tailwind. This audit corrects that: the governance layer is delivered as **Blade components + a PHP config + the existing CSS tokens**, which is what 96% of the views can actually consume.

## 1. Cross-cutting findings (by element)

| Element | Current state | Gap | Severity |
|---|---|---|---|
| **Status badges** | **199 views** hand-roll `badge bg-*`; the same status is coloured differently across modules (e.g. blood-bank used `bg-warning text-dark` in one place, `bg-warning` in another, and printed the raw `UPPER_SNAKE` value). | No single source of truth → colour drift + raw enum text shown to users. | **HIGH** |
| **Page headers** | A de-facto pattern is copy-pasted everywhere: `<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 pb-3 mb-3 border-bottom">…<h4 class="fw-bold mb-1">`. Good, but duplicated ~hundreds of times with small variations. | Not componentised → inconsistent title sizes/spacing/action placement. | MEDIUM |
| **Buttons** | Bootstrap `btn btn-*` used consistently in intent, but action ordering and colour for destructive actions varies; some destructive actions are `btn-outline-danger`, some `btn-danger`, some plain links. | No documented button hierarchy / confirmation rule. | MEDIUM |
| **Forms** | Bootstrap `.row .g-2 .col-md-*` grid forms; labels present; validation surfaced via flash `alert-danger` rather than per-field. | Field-level validation errors are inconsistent; long forms not always sectioned. | MEDIUM |
| **Tables** | `table table-hover align-middle`; pagination via `{{ $x->links() }}` mostly bottom; empty states are ad-hoc `<td colspan>…No records</td>`. | Empty/loading states inconsistent; some action columns not right-aligned. | MEDIUM |
| **Modals** | Bootstrap modals; **stuck-backdrop is already solved** centrally in `app.blade.php` (delegated dismiss + Inertia hooks). | Large clinical content sometimes shoved into small modals instead of drawer/page. | MEDIUM |
| **Filters/search** | Most list pages use a `<form method="GET">` card with date/status/department; state preserved via query string + `withQueryString()`. | No shared FilterBar component → markup differs per module. | MEDIUM |
| **Feedback** | Flash messages render as dismissible **Bootstrap alerts** (top of content), not toasts; SweetAlert2 is available but used unevenly. | "success toast" expectation unmet; some actions give no feedback. | MEDIUM |
| **Cards / KPIs** | `card h-100 border-start border-{color} border-3` KPI pattern (reports/statistics) is clean and reused; good baseline. | Not yet a `<x-stat-card>` component. | LOW |
| **Empty / loading / error** | Empty states ad-hoc; loading states largely absent (server-rendered); errors can surface as raw `QueryException` 500 pages in dev. | Need standard EmptyState + user-safe error pages. | MEDIUM |
| **Sidebar** | `SidebarMenuBuilder` is permission-aware (`permission`/`module` per item) and hides disabled modules — **already strong**. | Some commented-out/legacy items; depth is large. | LOW |
| **Accessibility** | Status communicated by colour; icon-only buttons sometimes lack `aria-label`; focus ring token exists but not universally applied. | Colour-only status; missing aria labels. | MEDIUM |
| **Terminology** | Mostly consistent (Visit, Invoice, Dispensed, Administered, Rendered, Verified). Occasional drift (drug/item/product). | Minor. | LOW |

## 2. Module-by-module notes

- **Dashboard / Reports / Statistics** — strongest, most consistent layout (KPI cards + filter + Chart.js + drill-down tables). Use as the **reference pattern** for the rest. The new Statistics module already follows the recommended `FilterBar → KPI cards → charts → tables` shape.
- **Blood Bank** — recently hardened; uses raw status strings in several spots (`{{ $unit->status }}`), ideal first adopter of `<x-status-badge domain="blood_unit">`.
- **Billing** — financial colours mostly correct; ensure `UNPAID=danger`, `PARTIALLY_PAID=warning`, `PAID=success` everywhere via `domain="invoice"`.
- **Emergency / Admission / MAR** — clinically dense; triage colours must be **fixed** (`RED/ORANGE/YELLOW/GREEN/BLACK`) — now centralised under `domain="triage"`. Large case views correctly use full pages (not modals).
- **Pharmacy** — billing vs dispensing actions can look similar; recommend distinct button variants + confirmation on dispense.
- **Stock** — matrix/movement tables are wide; ensure horizontal scroll wrappers (`.table-responsive`) everywhere.
- **Reports operational** — recently fixed (status-column bug); status breakdown now safe.
- **Settings / Roles / Modules** — admin config layout; critical permission assignment should warn/confirm.

## 3. Priority ranking

| Priority | Issue | Action |
|---|---|---|
| **HIGH** | Inconsistent / raw status badges across 199 views | Adopt `<x-status-badge>` + `config/ui.php` (delivered) |
| **HIGH** | No documented design law → every new module drifts | `UHMS_UI_THEME_RULES.md` + checklist (delivered) |
| MEDIUM | Page header duplication | Adopt `<x-page-header>` (delivered) |
| MEDIUM | Ad-hoc empty states / no feedback on some actions | `<x-empty-state>` + toast standard (component delivered; rollout pending) |
| MEDIUM | Field-level validation not standardised | Document form rules; roll out incrementally |
| MEDIUM | User-safe error pages (no raw SQL) | Add friendly 500/permission views (TODO) |
| LOW | Card/KPI, terminology, sidebar polish | Incremental |

## 4. What already works (preserve)

- Permission- and module-aware sidebar (`SidebarMenuBuilder`).
- Centralised modal-backdrop cleanup in the layout.
- CSS design tokens in `uhms-design-system.css`.
- Reports/Statistics layout pattern (the reference).
- Consistent Bootstrap + Tabler icon usage.

These are the foundation the new governance layer formalises rather than replaces.
