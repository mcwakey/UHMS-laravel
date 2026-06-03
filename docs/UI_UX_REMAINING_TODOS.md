# UHMS UI/UX — Remaining TODOs

What Phase 1 (this pass) delivered, and the safe follow-up work. Nothing here blocks current workflows.

## Delivered (Phase 1)
- `config/ui.php` — canonical status → colour maps + priority + tokens.
- `<x-status-badge>`, `<x-page-header>`, `<x-empty-state>` Blade components.
- Docs: gap analysis, recommendation report, theme rules, component standards, implementation checklist, this file.
- `tests/Feature/UiComponentsTest.php` (9 passing).

## Rollout status (Phases 2–3 + full-system readjustment)
- [x] **Phase 2** status badges — Blood Bank, Billing, MAR, Emergency, Stock, + Claims (enum-driven). (`<x-status-badge>` now in 20 files.)
- [x] **Phase 3** headers — Blood Bank, Statistics, Reports, Patients, Visits, Consultation, Pharmacy, Billing, Stock, Emergency, MAR, + Admissions, Wards, Insurance, Accounts (reconciliation), Service Renderings, Claims index. (`<x-page-header>` now in 28 files.)
- [x] **Empty states** — system-wide bulk conversion of single-line `…No records…` cells. (`<x-empty-state>` now in **71 files**; 0 single-line ad-hoc cells remain.)

## Remaining rollout (documented in UI_PHASE_2_3_FULL_SYSTEM_READJUSTMENT_REPORT.md)
- [ ] **55 page headers** still on the raw pattern — core detail/create/show pages first (claims/admissions/accounts/emergency/mar/wards/store sub-pages), then ~17 vendor-template CMS pages (blogs, faq, gallery, tickets, etc.).
- [ ] **`emergency/show`** header — needs a title-slot pattern (inline triage/status badges in the `<h4>`).
- [ ] **~42 multi-line empty states** (icon + text on separate lines) — convert to `<x-empty-state>` (already semi-compliant).
- [ ] Optionally route enum-driven `->status->color()` badges (lab, consultations, insurance, appointments, hr) through the enum-aware `<x-status-badge>` for markup consistency — cosmetic, zero colour change.
- [ ] Enrich bulk-converted empty states with contextual `icon`/`title` where they currently carry only `message`.

## Components (Phase 5 — DELIVERED)
All six created, tested (`tests/Feature/UiPhase5ComponentsTest.php`), documented (`docs/UI_COMPONENT_STANDARDS.md`), and adopted in representative pages. See `docs/UI_PHASE_5_COMPONENT_EXTRACTION_REPORT.md`.
- [x] `<x-stat-card>` — adopted in statistics dashboard/show.
- [x] `<x-filter-bar>` — adopted in statistics/show.
- [x] `<x-data-table>` — adopted in blood-bank/units.
- [x] `<x-action-menu>` — adopted in insurance/index.
- [x] `<x-confirm-form>` — adopted in insurance/index (provider toggle).
- [x] `<x-print-layout>` — created + tested; adopt gradually for print views.

### Phase 5 full-system readjustment (status) — see UI_PHASE_5_FULL_SYSTEM_READJUSTMENT_REPORT.md
- [x] `<x-confirm-form>` rolled out to **14** destructive/high-risk actions across billing, claims, accounts, modules, icd-codes, procedures, store (purchase-returns/requisitions/transfers), roles, departments, designations, MAR (16 files total). Module-disable, procedure-cancel and MAR stop-order now confirmed (last two require a reason).
- [ ] `<x-confirm-form>` — finish the **12 remaining** native `confirm()` forms (lab, patients/show, store/purchase-orders, theatre/rooms, admin/analyzers, admin catalogues). Skip `consultations/show` (chained JS).
- [ ] `<x-stat-card>` — use for **new** KPI cards; do **not** retro-fit dashboard/`reports/*` cards (₵ symbol, coloured values, inline icon-subtitles → would redesign them).
- [ ] `<x-filter-bar>` — adopt as list pages are touched (≈96 bespoke filter forms).
- [ ] `<x-data-table>` — adopt on clean list tables as touched (skip MAR grid, theatre board, timelines, charts).
- [ ] `<x-action-menu>` — adopt on row dropdowns (strip `<li>` wrappers per item).
- [ ] `<x-print-layout>` — adopt for printable documents, simplest first.
- [ ] (Backend, optional) capture the `reason` on `modules.toggle` / `roles.destroy` if reasons are wanted there.

## Feedback & errors
- [x] **Phase 4 (done):** Friendly error pages `403/404/419/500/503` + self-contained `layouts/error.blade.php`; never expose `SQLSTATE`/stack traces (logged instead). See `docs/UI_PHASE_4_ERROR_HANDLING_REPORT.md`.
- [x] **Phase 4 (done):** Friendly **disabled-module** page (HTTP 403, name + description) wired into `EnsureModuleEnabled` (clean JSON for API).
- [x] **Phase 4 (done):** Production `QueryException` shield in `bootstrap/app.php` — logs full detail, shows context-aware friendly message/flash, never raw SQL.
- [ ] SweetAlert2 toast helper for async success/error (standardise over scattered usage).

## Forms
- [ ] Field-level validation sweep (move flash `alert-danger` validation to `@error` per field).
- [ ] Confirmation + reason sweep for: delete, cancel, reverse payment, refund, stock adjustment, patient merge, mark deceased, discharge, dispose emergency case, cancel theatre case, override triage, emergency/incompatible blood release, disable module, assign critical permissions.

## Accessibility
- [ ] Add `aria-label` to icon-only buttons (audit `btn` with only `<i class="ti …">`).
- [ ] Verify contrast on `bg-warning`/`bg-info` chips (now mitigated via `text-dark`).
- [ ] Keyboard navigation pass on modals and dropdowns.

## Responsive / print
- [ ] Wrap all wide tables in `.table-responsive` (audit Stock matrix, MAR grid, theatre board).
- [ ] Print stylesheet pass for clinical documents (consultation summary, MAR chart, theatre/blood reports).

## Technical debt
- [ ] Consolidate the small Inertia/Vue footprint (18 files) — decide per-module whether to keep Vue islands or fold back into Blade for consistency.
- [ ] Remove commented-out/legacy menu items in `SidebarMenuBuilder`.
- [ ] Consider compiling `config/ui.php` colours into CSS custom properties for the Vue islands.

## Verification still useful
- [ ] Screenshot regression of high-traffic pages after the badge/header rollout.
- [ ] Manual responsive pass on clinical/financial pages.

## Suggested next prompts
1. "Roll `<x-status-badge>` into the Blood Bank, Billing and MAR views."
2. "Add friendly 403/404/500 + disabled-module pages."
3. "Extract `<x-stat-card>` and `<x-filter-bar>` and adopt them in Reports/Statistics."
