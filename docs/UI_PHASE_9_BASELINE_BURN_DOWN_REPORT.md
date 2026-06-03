# UI Phase 9 — Audit Baseline Burn-Down

Full-system reduction of the `ui:audit` baseline in priority order. No business
logic, routes, permissions, or page designs were changed; statuses are still
sourced from `config/ui.php` (no inline colours introduced).

## 1–2. Starting vs ending counts

| Severity | Start | End |
|----------|------:|----:|
| CRITICAL | 0 | 0 |
| HIGH | 203 | **114** |
| MEDIUM | 118 | **83** |
| LOW | 206 | 206 |
| INFO | 18 | 18 |
| **Total** | **545** | **421** |

## 3. Findings reduced by detector

| Detector | Priority | Start | End | Δ |
|----------|:--------:|------:|----:|----:|
| `raw-status-echo` | 1 | 12 | **2** | −83% |
| `inline-workflow-badge` | 2 | 146 | **67** | −54% |
| `icon-only-no-label` | 4 | 27 | **6** | −78% |
| `table-not-responsive` | 5 | 23 | **9** | −61% |
| `destructive-no-confirm` | 3 | 45 | 45 | documented |
| `print-no-layout` | 6 | 18 | 18 | documented |
| `missing-page-header` | 7 | 67 | 67 | deferred |

## 4. Files changed (high level)

- **config/ui.php** — added `consultation_route` and `consultation_session` domains.
- **~60 Blade views** — inline enum badges → `<x-status-badge>` (backreference-safe transform: only where `->color()` **and** `->label()` are on the same expression, so colour/label are provably identical).
- **9 Blade views** — raw `{{ $x->status }}` echoes → `<x-status-badge>` (blood-bank reports, consultations index/show/history, visits/show, emergency/show).
- **~12 Blade views** — remaining icon-only buttons labelled (the Phase 7 transform missed buttons whose `<i>` was multi-line or whose attributes contained `->` inside Blade expressions; a quote-aware matcher fixed this).
- **11 Blade views** — screen tables wrapped in `.table-responsive`.

## 5. Status domains added/updated in config/ui.php

- `consultation_route`: PENDING→warning, ACTIVE→success, PAUSED→info, COMPLETED→secondary, CANCELLED→danger (mirrors `VisitConsultationRoute::STATUS_*` colours).
- `consultation_session`: PENDING→warning, ACTIVE→success, PAUSED→warning, COMPLETED→secondary, CANCELLED→danger.
- Covered by a new test in `tests/Feature/UiComponentsTest.php`.

## 6. Destructive actions

The 45 `destructive-no-confirm` findings are **35 vendor Tabler demo pages**
(`email`, `chat`, `messages`, `tables-basic`, `file-manager`, `social-feed`) with
placeholder `href="#"` Delete buttons (no real action), plus a handful of real
operational ones (billing/payments, blood-bank/units discard with a required
reason, emergency/show). The demo pages are not UHMS workflows; converting their
fake buttons adds no safety. Real high-risk operational actions were already
wrapped in `<x-confirm-form>` with reason enforcement during Phases 5–6. The 21
remaining native `confirm()` forms still **do** confirm (so they are correctly
*not* flagged); upgrading them to `<x-confirm-form>` is tracked as polish.

## 7. Icon-only buttons labelled

27 → 6. The transform adds `aria-label` + `title` derived from the Tabler icon,
skipping anything already labelled. The 6 remaining are multi-icon or icon+hidden-text
buttons that need bespoke labels.

## 8. Tables made responsive

17 tables across 11 screen views wrapped in `.table-responsive` (double-wrap-safe).
`*-pdf` (DomPDF) and the vendor `tables-basic` demo were intentionally skipped.

## 9. Print views

Not converted (see §11). Already print-compliant from Phase 7.

## 10. Page headers

Deferred (see §11).

## 11. Items intentionally skipped and why

- **`missing-page-header` (67)** — real operational pages (`reports/*`, `settings/*`,
  `theatre/*`, `store/*`, dashboards, `patients/merge/*`). Each has a bespoke header
  (varied breadcrumbs/action buttons); a safe conversion is per-page manual work. The
  pages already have functional headers, so this is the lowest-leverage bucket and is
  tracked as debt rather than risked with a fragile bulk transform.
- **`print-no-layout` (18)** — already have hospital headers + `@media print` rules
  (Phase 7). `<x-print-layout>` adoption is gradual to avoid breaking print routes.
- **`destructive-no-confirm` vendor demos (35)** — not real workflows (see §6).
- **`possible-missing-permission` (128, LOW)** — advisory only; authoritative check is
  `permissions:audit` + route middleware.
- **`inline-style` (47) / `missing-stat-card` (22) / `img-no-alt` (9)** — LOW polish.
- **2 `raw-status-echo`** — `<option>` text (`{{ $bed->status }}` / `{{ $bay->status }}`)
  inside `<select>`; a component can't render inside an `<option>`, so left as plain text.
- **2 `inline-workflow-badge`** — model-accessor (`status_color`) / computed
  `['class','label']` stock badges; converting risks colour drift.

## 12. Baseline updated

**Yes** — real fixes were made, so the strict baseline was regenerated:
`storage/app/ui-audit-baseline.json` shrank 676 → **610** fingerprints. No new
critical findings were hidden; CRITICAL remains 0.

## 13. Tests run

`php artisan view:cache` compiles all 543 views. UI/audit suites green (35 tests),
including the new `consultation_route`/`consultation_session` domain test and the
`--min-severity` gate test.

## 14. Remaining TODOs

`missing-page-header` (67), `destructive-no-confirm` real-vs-demo split, native
`confirm()` → `<x-confirm-form>` (21), `print-no-layout` (18), and the LOW polish
buckets. Tracked in `docs/UI_UX_REMAINING_TODOS.md` and `docs/UI_AUDIT_BASELINE.md`.

## 15. When to enable `ui:audit --fail` in CI

Now, in **critical-only** mode (`--fail --min-severity=CRITICAL`) — CRITICAL is 0 and
the baseline absorbs known debt, so it blocks regressions without blocking current
work. Promote to `--min-severity=HIGH` once the 114 HIGH (mostly page-header +
remaining inline badges) is burned down; `--strict` last. See Phase 10.
