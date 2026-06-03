# UI Phase 10 — CI Gate, Regression & Release Readiness

Turns UHMS UI governance into enforceable development discipline: a staged CI
gate, regression checklists, and a release-readiness assessment. No business
logic, page designs, permissions, or frameworks changed.

## 1. Current UI audit counts

| Severity | Count |
|----------|------:|
| CRITICAL | **0** |
| HIGH | 114 |
| MEDIUM | 83 |
| LOW | 206 |
| INFO | 18 |
| **Total** | **421** |

By detector: `possible-missing-permission` 128 (LOW, advisory), `inline-workflow-badge`
67, `missing-page-header` 67, `inline-style` 47, `destructive-no-confirm` 45 (≈35
vendor demos), `missing-stat-card` 22, `print-no-layout` 18, `table-not-responsive` 9,
`img-no-alt` 9, `icon-only-no-label` 6, `raw-status-echo` 2, `missing-empty-state` 1.

(Down from 545 total / 203 HIGH at the start of Phase 9.)

## 2. Was the CI gate enabled?

**Yes — advisory + critical-only gate.** `.github/workflows/ui-audit.yml` runs the
audit on push/PR, uploads the report artifact, and fails the build only on **new
CRITICAL** findings outside the committed baseline.

## 3. CI mode selected

**Stage B — critical-only** (`php artisan ui:audit --fail --min-severity=CRITICAL`).
CRITICAL is 0 today and the baseline absorbs the known HIGH/MEDIUM backlog, so the
gate blocks regressions without blocking current work. The `--min-severity` option
was added to the command for this (and is unit-tested).

Staging plan: **A** advisory → **B** critical-only (now) → **C** `--min-severity=HIGH`
once the 114 HIGH is burned down → **D** `--strict`.

## 4. Composer scripts

```
composer ui:audit              # advisory report
composer ui:audit:fail         # fail on new critical (default gate) + JSON
composer ui:audit:critical     # explicit: --fail --min-severity=CRITICAL --json
```
Existing scripts (`test`, `dev`, lifecycle hooks) were left untouched.

## 5. CI files added/updated

- **`.github/workflows/ui-audit.yml`** (new) — checkout → setup PHP 8.2 → composer
  install → key:generate → `ui:audit --json` (advisory, artifact) → `ui:audit --fail
  --min-severity=CRITICAL` (gate). No prior CI existed, so nothing was broken.

> Note: the workflow does **not** run `php artisan test` to keep it fast and focused;
> the existing `composer test` remains the test entry point. Add a test job later if
> wanted.

## 6. Screenshot checklist created

**Yes** — `docs/UI_SCREENSHOT_REGRESSION_CHECKLIST.md` (high-traffic pages ×
desktop/tablet/print/empty/restricted). No screenshot library was added (none exists).

## 7. Manual regression checklist created

**Yes** — `docs/UI_MANUAL_REGRESSION_CHECKLIST.md` (every major workflow).

## 8. High-traffic pages verified

**Not visually verified in this phase** — there is no browser/screenshot harness in
this environment, so the screenshot checklist is provided for a human to execute. What
*was* verified automatically: all 543 Blade views **compile** (`view:cache`), and the
UI component/audit suites pass. (Honesty per the brief: screenshots were not run.)

## 9. Error pages verified

**Yes (automated).** `ErrorHandlingTest` asserts friendly 403, disabled-module page,
clean JSON for API, and the production query-exception shield — all passing.

## 10. Disabled module page verified

**Yes (automated)** via `ErrorHandlingTest` ("disabled module shows friendly page" /
"disabled module json returns clean message"). Note the pre-existing `ModuleAccessTest`/
`ModuleOverrideTest` still assert the *old* 404/503 contract from before Phase 4 changed
it to a friendly 403 — see §12.

## 11. Remaining UI debt

Tracked in `docs/UI_AUDIT_BASELINE.md`:
- `missing-page-header` (67) — real operational pages; per-page manual conversion.
- `inline-workflow-badge` (67) — computed/accessor/icon badges (colour-drift risk).
- `destructive-no-confirm` (45) — ≈35 vendor demos (not real workflows) + a few real.
- native `confirm()` → `<x-confirm-form>` (21 files) — already confirm; UX polish.
- `print-no-layout` (18) — already print-compliant; gradual `<x-print-layout>` adoption.
- LOW polish: `inline-style` (47), `missing-stat-card` (22), `img-no-alt` (9), `icon-only-no-label` (6).
- `possible-missing-permission` (128, advisory) — cross-check with `permissions:audit`.

## 12. Risks before release

- **Visual regression not yet screenshot-verified** — run the screenshot checklist on a
  staging build before release (badge `text-dark` additions and table wrappers are the
  most likely visual deltas, all intentional).
- **Pre-existing test failures unrelated to UI** (confirmed not caused by Phases 7–10):
  `WorkflowJsonResponsesTest` (payment→visit status = business logic), 3× MAR tests
  (time-of-day flakiness), and the disabled-module 404/503 tests that contradict Phase 4's
  intentional 403. These should be triaged separately — they are not UI regressions.
- The CI gate is critical-only; it will **not** catch new HIGH drift until Stage C.

## 13. Recommendation for next release gate

1. Run the **manual** + **screenshot** checklists on staging; fix any real visual issue.
2. Keep CI at **critical-only** for this release.
3. Reconcile the disabled-module tests to the Phase 4 contract (or revert the contract) —
   a product decision, not a UI one.
4. Burn down `missing-page-header` (67) and the remaining `inline-workflow-badge` (67),
   then move CI to **`--min-severity=HIGH`** and refresh the baseline.
5. Adopt `--strict` last, once HIGH is at/near zero.
