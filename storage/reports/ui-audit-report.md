# UHMS UI Audit Report

_Generated Sat, Jun 6, 2026 12:54 PM by `php artisan ui:audit`._

## Scope
- Blade views: 1
- Vue components: 0
- CSS files: 0

## Severity summary

| Severity | Count |
|----------|------:|
| CRITICAL | 0 |
| HIGH | 1 |
| MEDIUM | 0 |
| LOW | 0 |
| INFO | 0 |

## HIGH (1)

- **inline-workflow-badge** — `tests/fixtures/ui-audit/badge.blade.php:1`
  - Workflow status rendered with an inline Bootstrap colour.
  - → Use <x-status-badge :status="$model->status" domain="..."/> so the colour is centralised in config/ui.php.

