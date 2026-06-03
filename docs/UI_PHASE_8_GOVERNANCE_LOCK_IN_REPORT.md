# UI Phase 8 — Governance Lock-In Report

Phases 2–7 fixed the UI. Phase 8 makes the rules **enforceable** so future
developers and AI coding agents cannot easily reintroduce drift. Nothing here
changes business logic, routes, permissions, or page designs.

## 1. What governance tools were added

| Artifact | Purpose |
|----------|---------|
| `app/Console/Commands/UiAuditCommand.php` | `php artisan ui:audit` — static analysis of the Blade/Vue UI against the design law. |
| `storage/app/ui-audit-baseline.json` | Machine baseline of known debt; the `--fail` gate only blocks on **new** issues. |
| `docs/UI_AUDIT_BASELINE.md` | Human-readable baseline + burn-down order. |
| `UI_REVIEW_CHECKLIST.md` (repo root) | Short per-PR checklist for humans/Copilot. |
| `.github/copilot-instructions.md` | Binding rules for AI coding agents (auto-loaded by Copilot). |
| `docs/CODEX_UI_INSTRUCTIONS.md` | Same rules surfaced under `docs/` for Codex-style agents. |
| `composer.json` scripts | `composer ui:audit`, `composer ui:audit:fail`. |
| `tests/Feature/UiAuditCommandTest.php` | 12 tests proving the audit works and is not over-eager. |

## 2. Artisan command details

```bash
php artisan ui:audit                       # console report + storage/reports/ui-audit-report.md
php artisan ui:audit --json                # also storage/reports/ui-audit-report.json
php artisan ui:audit --path=resources/views/admin/patients   # scoped scan
php artisan ui:audit --strict              # promote softer heuristics to findings
php artisan ui:audit --fail                # non-zero exit if NEW critical findings exist
php artisan ui:audit --update-baseline     # snapshot current debt as the baseline
```

Scans `resources/views` (`*.blade.php`), `resources/js` (`*.vue`, `*.js`),
`resources/css`, and validates `config/ui.php`. Skips `vendor/` and
`node_modules/`. Blade comments are blanked (length-preserving) before scanning
so documentation/example markup is never flagged.

## 3. Patterns detected

**Forbidden patterns** — debug output (`dd/dump/var_dump/print_r`), raw technical
errors (`SQLSTATE`/`QueryException`/stack traces) in views, foreign frameworks
(Tailwind/Bulma/DaisyUI/Chakra/MUI), inline workflow badges
(`badge bg-{{ …->color() }}`), raw status echoes (`{{ $model->status }}`),
excessive inline styles.

**Component-adoption gaps** — missing `<x-page-header>`, `<x-empty-state>`,
`<x-stat-card>`, tables not in `.table-responsive`/`<x-data-table>`, destructive
actions without `<x-confirm-form>`.

**Status-domain integrity** — every required domain exists in `config/ui.php`,
every mapping uses a valid Bootstrap variant, low-contrast tints stay in
`dark_text_variants`.

**Accessibility** — icon-only buttons without `aria-label`/`title`, colour-only
status (empty badge), images without `alt`.

**Permissions** — pages with action controls but no `@can`/`@canany` guard
(flagged as a *possible* missing guard for human review — never auto-changed).

**Print** — printable views not using `<x-print-layout>`/`layouts.print`/`@media print`.

## 4. Severity rules

`CRITICAL` debug/technical-error output in views · `HIGH` inline workflow badge,
raw status echo, destructive-no-confirm, colour-only status, foreign framework,
missing status domain · `MEDIUM` missing page-header/empty-state, non-responsive
table, icon-only-no-label, invalid status variant · `LOW` missing stat-card,
inline style, possible-missing-permission, missing alt · `INFO` print-layout
adoption.

The tool is **deliberately not over-aggressive** (per the brief): literal
decorative badges, the expanded destructive verb set, and Vue inline status are
only flagged under `--strict`, so it does not block valid existing code on day one.

## 5. Files scanned

543 Blade views · 18 Vue components · 8 CSS files · `config/ui.php`.

## 6. Current audit results (default run)

| Severity | Count |
|----------|------:|
| CRITICAL | **0** |
| HIGH | 203 |
| MEDIUM | 118 |
| LOW | 206 |
| INFO | 18 |

**Zero CRITICAL** — no raw technical errors leak to users (Phase 4 holds). The
HIGH bucket is pre-existing consistency debt: 146 inline workflow badges, 45
destructive-without-confirm, 12 raw status echoes. See `docs/UI_AUDIT_BASELINE.md`.

## 7. Baseline status

Captured in strict mode (superset) → `storage/app/ui-audit-baseline.json`
(676 fingerprints, keyed `type|file`). With the baseline in place, both
`ui:audit --fail` and `ui:audit --fail --strict` exit `0` today, while any **new**
critical/high finding in a non-baselined file fails the build. The baseline is a
debt ledger to shrink — not a place to hide new criticals.

The baseline is **committed** (an explicit `!ui-audit-baseline.json` exception was
added to `storage/app/.gitignore`) so CI shares the same debt ledger; otherwise
`--fail` would treat all existing debt as new.

## 8. Developer checklist created

`UI_REVIEW_CHECKLIST.md` at the repo root — a one-screen list (approved
components, status badge, page header, empty state, confirm-form, permissions, no
raw errors, no Tailwind, responsive tables, icon labels, print layout, reason on
high-risk actions, run `ui:audit`).

## 9. Copilot / Codex instruction file created

`.github/copilot-instructions.md` (auto-loaded by GitHub Copilot) plus a
`docs/CODEX_UI_INSTRUCTIONS.md` mirror. Both tell AI tools: Bootstrap 5 + Tabler
only, reuse components, statuses from `config/ui.php`, keep permissions, no raw
errors, don't touch business logic during UI cleanup, run `ui:audit` before
finishing.

## 10. Composer / CI integration

`composer ui:audit` and `composer ui:audit:fail` added without disturbing
existing scripts. Recommended CI step (non-blocking first):

```yaml
- run: php artisan ui:audit --json   # report artifact, non-blocking
# later, once debt is burned down:
- run: php artisan ui:audit --fail   # gate on NEW criticals
```

`--fail` is intentionally **not** mandatory yet (the brief: don't block all work
immediately). Promote to a required check after the HIGH baseline is reduced;
enable `--strict` last.

## 11. Remaining risks

- The permission heuristic is file-level and advisory — it flags *possible* gaps
  (`LOW`), so the authoritative check remains `php artisan permissions:audit` +
  route middleware. Some flags are false positives (guarded at layout/route level).
- Static analysis can't see runtime/JS-built markup, so a small residue of
  dynamic icon buttons and Vue islands stays out of scope.
- The baseline is `type|file` coarse: a *new* finding of an already-baselined type
  in the *same* file won't trip `--fail`. Acceptable trade-off for low noise.

## 12. How future developers should use the governance system

1. Read `UI_REVIEW_CHECKLIST.md` before UI work.
2. Build with the approved `<x-*>` components and `config/ui.php`.
3. Run `php artisan ui:audit` (or `composer ui:audit`) before finishing — ensure
   **no new CRITICAL** versus the baseline.
4. After burning down debt, refresh the baseline:
   `php artisan ui:audit --update-baseline --strict`, and commit the smaller file.
5. AI agents follow `.github/copilot-instructions.md` automatically.

## Acceptance criteria — met

`ui:audit` exists ✔ · scans Blade + Vue (+ JS/CSS/config) ✔ · detects forbidden
patterns ✔ · detects component-adoption gaps ✔ · detects accessibility risks ✔ ·
writes MD + JSON reports ✔ · supports `--json`/`--fail`/`--strict`/`--path` ✔ ·
developer checklist ✔ · Copilot/Codex instructions ✔ · docs updated ✔ · UI debt
documented in a baseline ✔ · existing workflows untouched ✔ · 12 audit tests pass ✔.
