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

## Forms (Phase 6 — see UI_PHASE_6_FORMS_CONFIRMATIONS_VALIDATION_REPORT.md)
- [x] **Global double-submit guard** in `layouts/app.blade.php` (disables submit + spinner; opt-out `data-no-loading`).
- [x] **Reason enforcement** — patient merge (required), triage override (conditional). Already enforced before: payment reversal, stock adjustment/return, incompatible/emergency blood release, procedure cancel, MAR stop-order.
- [x] **Field-level validation** — major create/edit forms already use `@error`/`is-invalid`/`old()` (e.g. `patients/create`); merge reason added.
- [x] **Confirmation sweep** — 17 destructive actions on `<x-confirm-form>` (native `confirm()` reduced 26 → 10).
- [ ] Convert the **10 remaining** native `confirm()` forms (store/purchase-orders, theatre/rooms, admin/analyzers, admin catalogues, lab/tests). Skip `consultations/show` (chained JS).
- [ ] Field-level validation on the remaining simple modal/inline forms (low risk).
- [ ] (Backend, optional) persist `reason` on `modules.toggle`/`roles.destroy` if reasons wanted there.

## Accessibility (Phase 7 — see UI_PHASE_7_RESPONSIVE_ACCESSIBILITY_PRINT_REPORT.md)
- [x] **~385 icon-only buttons** across **226 files** given `aria-label` + `title` (432 → 47; 89% closed).
- [x] Contrast on `bg-warning`/`bg-info` chips mitigated via `text-dark` (config `dark_text_variants`).
- [ ] Label the **47 remaining** icon-only buttons (unusual/dynamic markup) — tracked by `ui:audit` (`icon-only-no-label`).
- [ ] Keyboard navigation pass on modals and dropdowns.

## Responsive / print (Phase 7)
- [x] **16 data tables** in 9 files wrapped in `.table-responsive` (the real gaps; rest already wrapped).
- [ ] Remaining `table-not-responsive` findings (partials/modals) — tracked by `ui:audit`.
- [ ] Gradual `<x-print-layout>` adoption for the 18 `print-no-layout` views (already print-CSS compliant).

## UI governance (Phase 8 — DELIVERED — see UI_PHASE_8_GOVERNANCE_LOCK_IN_REPORT.md)
- [x] `php artisan ui:audit` command (`--json`, `--fail`, `--strict`, `--path`, `--update-baseline`); composer `ui:audit` / `ui:audit:fail` scripts.
- [x] Detectors: forbidden patterns, component gaps, status-domain integrity, accessibility, permission-guard heuristic, print layout.
- [x] Reports: `storage/reports/ui-audit-report.md` + `.json`; severity counts (CRITICAL→INFO).
- [x] Baseline: `storage/app/ui-audit-baseline.json` + `docs/UI_AUDIT_BASELINE.md` (0 CRITICAL today).
- [x] Guardrails: `UI_REVIEW_CHECKLIST.md`, `.github/copilot-instructions.md`, `docs/CODEX_UI_INSTRUCTIONS.md`.
- [x] Tests: `tests/Feature/UiAuditCommandTest.php` (12 passing).
- [ ] **Burn down the baseline** (priority: `raw-status-echo` 12 → `inline-workflow-badge` 146 → `destructive-no-confirm` 45), then make `--fail` a CI gate and later enable `--strict`.

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
