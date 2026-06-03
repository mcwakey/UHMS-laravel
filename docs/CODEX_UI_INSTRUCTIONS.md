# Codex / AI UI Instructions — UHMS

This is the canonical AI-agent guidance for UI work. It mirrors
[`.github/copilot-instructions.md`](../.github/copilot-instructions.md) so that
tools which look in `docs/` (Codex, some IDE agents) also find the rules.

**Read [`.github/copilot-instructions.md`](../.github/copilot-instructions.md) — it is the source of truth.**

Quick summary:

- UHMS = **Blade + Bootstrap 5 + Tabler Icons**. No Tailwind / other frameworks / new chart libs.
- Reuse the shared `<x-*>` components; never hand-roll equivalents.
- Workflow statuses → `<x-status-badge>` + `config/ui.php`. No inline `badge bg-...->color()`, no raw `{{ $model->status }}`.
- Permissions in UI **and** backend. Never remove them.
- No raw technical errors in views.
- Accessible icons, responsive tables, confirmed destructive actions, print layout for printables.
- Don't change business logic during UI cleanup.

Before finishing UI work, run:

```bash
php artisan ui:audit
```

and ensure there are **no new critical findings** versus
`storage/app/ui-audit-baseline.json`.
