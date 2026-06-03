# UHMS UI Review Checklist

Short checklist for any PR that touches the UI. The full law lives in
[`docs/UHMS_UI_THEME_RULES.md`](docs/UHMS_UI_THEME_RULES.md) and
[`docs/UI_COMPONENT_STANDARDS.md`](docs/UI_COMPONENT_STANDARDS.md). Run the
audit before you finish:

```bash
php artisan ui:audit          # report
composer ui:audit             # same, via composer
php artisan ui:audit --json   # machine-readable report for CI
```

UHMS is **Blade + Bootstrap 5 + Tabler Icons**. No Tailwind, no other CSS
framework, no new chart library.

## Before you open the PR

- [ ] **Components, not copies.** Use the shared components instead of hand-rolling:
  - `<x-page-header>` for every major page header (title + actions).
  - `<x-status-badge :status="..." domain="...">` for **every** workflow status. Never `badge bg-{{ $x->status->color() }}`.
  - `<x-empty-state>` for empty lists (no plain "No records found").
  - `<x-stat-card>` for KPI tiles.
  - `<x-filter-bar>` for list filters.
  - `<x-data-table>` (or `.table-responsive`) for tables.
  - `<x-action-menu>` for row action dropdowns.
  - `<x-confirm-form>` for destructive actions.
  - `<x-print-layout>` for printable documents.
- [ ] **Statuses come from `config/ui.php`.** New workflow status? Add it to the right domain map there; don't invent a colour inline. One colour = one meaning.
- [ ] **Never show raw technical errors.** No `dd()`, `dump()`, `var_dump()`, `SQLSTATE`, stack traces in views. Log it; show a friendly message (Phase 4 error pages).
- [ ] **Don't rely on colour alone.** Status must have visible text, not just a coloured dot/badge.
- [ ] **Permissions in UI *and* backend.** Gate action controls with `@can`/`@canany`; the route must also be permission-protected.
- [ ] **Responsive tables.** Wide tables go in `.table-responsive` / `<x-data-table>`.
- [ ] **Accessible icons.** Icon-only buttons/links need `aria-label` + `title`. Images need `alt`.
- [ ] **Destructive actions are guarded.** Delete / Reverse / Refund / Void / Merge / Mark Deceased go through `<x-confirm-form>`; high-risk actions require a typed reason.
- [ ] **Print views use the print layout** (hospital header, patient context, black-on-white, `d-print-none` controls).
- [ ] **No business logic, route, or permission changes** sneaking in under a "UI" PR.

## Definition of done

- [ ] `php artisan ui:audit` shows **no new CRITICAL** findings (and ideally none new at all).
- [ ] `php artisan view:cache` compiles with no errors.
- [ ] Relevant feature tests pass.

> The audit has a baseline (`storage/app/ui-audit-baseline.json`) of known debt.
> Your job is to **shrink** it, never grow it. Do not add new criticals to the
> baseline to silence them.
