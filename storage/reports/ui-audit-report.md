# UHMS UI Audit Report

_Generated Sun, Jul 12, 2026 2:13 PM by `php artisan ui:audit`._

## Scope
- Blade views: 1
- Vue components: 0
- CSS files: 0

## Severity summary

| Severity | Count |
|----------|------:|
| CRITICAL | 0 |
| HIGH | 0 |
| MEDIUM | 1 |
| LOW | 0 |
| INFO | 0 |

## MEDIUM (1)

- **icon-only-no-label** — `tests/fixtures/ui-audit/icon.blade.php:1`
  - Icon-only button/link without aria-label or title.
  - → Add aria-label and title describing the action (e.g. aria-label="Delete").

