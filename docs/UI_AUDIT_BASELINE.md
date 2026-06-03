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

## Baseline snapshot (default, non-strict run) — post Phase 9

`CRITICAL: 0` — the system exposes no raw technical errors in views (Phase 4 holds).

| Severity | Type | Phase 8 | **Now** | Recommended fix |
|----------|------|------:|------:|-----------------|
| HIGH | `inline-workflow-badge` | 146 | **67** | Remaining are computed/accessor (`status_color`) or icon badges — convert case-by-case (colour-drift risk). |
| HIGH | `destructive-no-confirm` | 45 | **45** | ≈35 are vendor Tabler demos (fake `href="#"`); a few real ones remain. |
| HIGH | `raw-status-echo` | 12 | **2** | The 2 left are `<option>` text (cannot hold a component). |
| MEDIUM | `missing-page-header` | 67 | **67** | Adopt `<x-page-header>` per page (bespoke breadcrumbs/actions — manual). |
| MEDIUM | `icon-only-no-label` | 27 | **6** | Bespoke labels for multi-icon / icon+hidden-text buttons. |
| MEDIUM | `table-not-responsive` | 23 | **9** | Remaining are `*-pdf`/print/partials. |
| MEDIUM | `missing-empty-state` | 1 | **1** | Use `<x-empty-state>`. |
| LOW | `possible-missing-permission` | 128 | **128** | Advisory — cross-check with `permissions:audit`. |
| LOW | `inline-style` | 47 | **47** | Prefer Bootstrap utilities / design tokens. |
| LOW | `missing-stat-card` | 22 | **22** | Adopt `<x-stat-card>` for KPI tiles. |
| LOW | `img-no-alt` | 9 | **9** | Add `alt` text. |
| INFO | `print-no-layout` | 18 | **18** | Gradually adopt `<x-print-layout>`. |

_Total: **545 → 421** (HIGH 203 → 114). Strict baseline `storage/app/ui-audit-baseline.json`
shrank **676 → 610** fingerprints. See `docs/UI_PHASE_9_BASELINE_BURN_DOWN_REPORT.md`._

New status domains added in Phase 9: `consultation_route`, `consultation_session`.

## Priority burn-down order (remaining)

1. **`missing-page-header` (67)** — largest remaining bucket; convert core operational pages first (reports, settings, theatre, store, dashboards) to `<x-page-header>`.
2. **`inline-workflow-badge` (67)** — convert computed/accessor badges after verifying their colour maps match `config/ui.php`.
3. **`destructive-no-confirm`** — confirm the few real operational ones; ignore vendor demos.
4. **`print-no-layout` (18)** — gradual `<x-print-layout>` adoption.
5. **LOW polish** — `inline-style`, `missing-stat-card`, `img-no-alt`, last 6 icon labels.

After each burn-down, re-run `php artisan ui:audit --update-baseline --strict`.
