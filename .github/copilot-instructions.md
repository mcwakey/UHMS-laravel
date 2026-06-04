# Copilot / AI Coding Agent Instructions — UHMS

These instructions apply to **all** AI-assisted code changes in this repository
(GitHub Copilot, Codex, Claude, Cursor, etc.). They exist to stop UI drift.

## Stack (do not change)

- **Backend:** Laravel (PHP), Blade templating.
- **Frontend:** **Bootstrap 5 + Tabler Icons only.** A few isolated Vue islands exist; match the existing pattern, don't expand them.
- **Charts:** the bundled chart libraries already in `public/build/plugins`. Do **not** add a new chart library.
- **Database note:** production runs **MariaDB 10.1** — no `json` column type (use `longText`), no `Schema::hasColumn()`, no window functions. Tests run on SQLite, which hides these; verify migrations against MariaDB.

## Hard rules for UI work

1. **Do not introduce Tailwind, Bulma, DaisyUI, Material UI, Chakra, or any other CSS/JS framework.** UHMS is Bootstrap 5.
2. **Always reuse the shared components** — do not hand-roll equivalents:
   `<x-page-header>`, `<x-status-badge>`, `<x-empty-state>`, `<x-stat-card>`,
   `<x-filter-bar>`, `<x-data-table>`, `<x-action-menu>`, `<x-confirm-form>`,
   `<x-print-layout>`.
3. **Statuses:** render workflow statuses with `<x-status-badge :status="$model->status" domain="...">`. Status→colour lives in **`config/ui.php`** — add new statuses there. Never write `badge bg-{{ ...->color() }}` inline, and never echo a raw `{{ $model->status }}` to the user.
4. **Permissions:** every action control must be wrapped in `@can`/`@canany`, and the underlying route must be permission-protected. Never remove permission checks.
5. **Never expose technical errors** (`dd`, `dump`, `var_dump`, `SQLSTATE`, stack traces) in views. Use the Phase 4 friendly error pages and log the detail.
6. **Accessibility:** icon-only buttons need `aria-label` + `title`; never convey status by colour alone; images need `alt`.
7. **Responsive:** tables go in `.table-responsive`/`<x-data-table>`.
8. **Destructive actions** (Delete/Reverse/Refund/Void/Merge/Mark Deceased) use `<x-confirm-form>`; high-risk actions require a typed reason.
9. **Do not change business logic, routes, or permissions while doing UI cleanup.** Do not redesign pages. Do not hide clinical, financial, stock, or blood-bank information.
10. **Do not duplicate design patterns.** If something is repeated, extract/extend a component instead.

## Reference docs (read before large UI changes)

- `docs/UHMS_UI_THEME_RULES.md` — the design law.
- `docs/UI_COMPONENT_STANDARDS.md` — component contracts.
- `UI_REVIEW_CHECKLIST.md` — the short PR checklist.

## Before finishing any UI change

```bash
php artisan ui:audit        # must show no NEW critical findings
php artisan view:cache      # must compile
php artisan test            # relevant tests must pass
```

The audit tracks a baseline of known debt in `storage/app/ui-audit-baseline.json`.
Reduce it; do not grow it, and never add new critical findings to it to hide them.
