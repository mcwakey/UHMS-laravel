# UHMS UI/UX — Remaining TODOs

What Phase 1 (this pass) delivered, and the safe follow-up work. Nothing here blocks current workflows.

## Delivered (Phase 1)
- `config/ui.php` — canonical status → colour maps + priority + tokens.
- `<x-status-badge>`, `<x-page-header>`, `<x-empty-state>` Blade components.
- Docs: gap analysis, recommendation report, theme rules, component standards, implementation checklist, this file.
- `tests/Feature/UiComponentsTest.php` (9 passing).

## Rollout (mechanical, low risk)
- [ ] Replace inline `badge bg-*` with `<x-status-badge>` across the **199** affected views — start with Blood Bank, Billing, MAR, Emergency, Stock, then the rest.
- [ ] Replace the copy-pasted page-header `<div class="d-flex … border-bottom">` with `<x-page-header>` on list/detail pages.
- [ ] Replace ad-hoc `…No records…` cells with `<x-empty-state>`.

## Components to extract (Phase 5)
- [ ] `<x-stat-card>` from the KPI card pattern.
- [ ] `<x-filter-bar>` from the GET-form filter card.
- [ ] `<x-data-table>` (responsive + pagination + empty-state wrapper).
- [ ] `<x-action-menu>` row-action dropdown (danger last).
- [ ] `<x-confirm-form>` (POST + SweetAlert2 confirm) for destructive actions.
- [ ] `<x-print-layout>` print-only chrome.

## Feedback & errors
- [ ] SweetAlert2 toast helper for async success/error (standardise over scattered usage).
- [ ] Friendly error pages: custom `resources/views/errors/{403,404,500}.blade.php` that never expose `SQLSTATE`/stack traces to end users (log the detail).
- [ ] Friendly **disabled-module** page when a module route is hit while disabled.

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
