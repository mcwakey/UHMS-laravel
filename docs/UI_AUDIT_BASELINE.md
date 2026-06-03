# UHMS UI Audit Baseline

This file records the **known UI debt** at the time `php artisan ui:audit` was
introduced (Phase 8). The machine-readable companion lives at
`storage/app/ui-audit-baseline.json` and is what the `--fail` gate consults.

> **Rule:** future work must **shrink** this list, never grow it. Do **not** add
> new CRITICAL findings to the baseline to silence them — fix them.

## How the baseline is used

- `php artisan ui:audit --fail` exits non-zero only when a **new CRITICAL**
  finding appears that is **not** in the baseline. Existing debt does not block.
- `php artisan ui:audit --fail --strict` does the same for CRITICAL **and HIGH**.
- Regenerate after you burn down debt:
  `php artisan ui:audit --update-baseline --strict`.

The baseline is keyed at **`type|file`** granularity, so fixing all findings of a
type in a file removes that fingerprint.

## Baseline snapshot (default, non-strict run)

`CRITICAL: 0` — the system exposes no raw technical errors in views (Phase 4 holds).

| Severity | Type | Count | Recommended fix |
|----------|------|------:|-----------------|
| HIGH | `inline-workflow-badge` | 146 | Replace `badge bg-{{ $x->status->color() }}` with `<x-status-badge :status="..." domain="...">`. |
| HIGH | `destructive-no-confirm` | 45 | Wrap Delete/Reverse/Refund/Void/Merge actions in `<x-confirm-form>`. |
| HIGH | `raw-status-echo` | 12 | Replace raw `{{ $model->status }}` with `<x-status-badge>` or `->label()`. |
| MEDIUM | `missing-page-header` | 67 | Adopt `<x-page-header>` for the page header bar. |
| MEDIUM | `icon-only-no-label` | 27 | Add `aria-label` + `title` (the 47 edge cases Phase 7 left, plus a few). |
| MEDIUM | `table-not-responsive` | 23 | Wrap in `.table-responsive` / `<x-data-table>` (mostly inside partials/modals). |
| MEDIUM | `missing-empty-state` | 1 | Use `<x-empty-state>`. |
| LOW | `possible-missing-permission` | 128 | Human review — confirm action controls are permission-gated (many are already gated at the route/layout level). |
| LOW | `inline-style` | 47 | Prefer Bootstrap utilities / design tokens. |
| LOW | `missing-stat-card` | 22 | Adopt `<x-stat-card>` for KPI tiles. |
| LOW | `img-no-alt` | 9 | Add `alt` text. |
| INFO | `print-no-layout` | 18 | Gradually adopt `<x-print-layout>` for printable views. |

_Total: 545 findings (non-strict). Strict mode adds literal-badge, expanded
destructive verbs, lower inline-style threshold, and Vue inline-status — captured
in the strict `storage/app/ui-audit-baseline.json` (676 fingerprints)._

## Priority burn-down order

1. **`raw-status-echo` (HIGH, 12)** — smallest HIGH bucket, clear user-facing wins.
2. **`inline-workflow-badge` (HIGH, 146)** — largest consistency gap; mechanical swap to `<x-status-badge>`.
3. **`destructive-no-confirm` (HIGH, 45)** — safety; wrap in `<x-confirm-form>`.
4. **`missing-page-header` / `table-not-responsive` (MEDIUM)** — polish.
5. **LOW/INFO** — opportunistic.

After each burn-down, re-run `php artisan ui:audit --update-baseline --strict`.
