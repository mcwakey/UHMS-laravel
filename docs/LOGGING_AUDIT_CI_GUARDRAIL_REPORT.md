# Logging Audit — CI Guardrail (Non-Blocking)

> **Update:** `logs:audit` has since been made **funnel-aware** with finding
> classification + a baseline. The flat "73 flagged" framing below is superseded
> by `docs/LOGGING_FINALIZATION_REPORT.md` (73 → 42 covered · 22 backlog · 7
> needs-review · 1 skipped · **1 real missing log**). This report still describes
> the CI wiring, which remains accurate and non-blocking.

A lightweight, **advisory-only** guardrail so future development can't silently
regress logging coverage. It does **not** fail builds and changes no business or
logging behaviour.

## 1. logs:audit command status

`php artisan logs:audit` already exists (added in the UI Phase 8 governance work,
reused for logging). It scans controllers for mutating actions (`store/update/
destroy/approve/dispense/administer/transfer/merge/cancel/reverse/...`) and flags
those whose class shows no logging marker (`ActivityLogService`, `entryLogs->`,
`MedicalRecordEntryLog`, `pathway->record`, `timeline->record`, …).

Flags / behaviour:
- `--json` → writes `storage/reports/logs-audit-report.json`.
- `--module=<path>` → scope to controllers whose path contains the string.
- `--fail` → non-zero exit when any controller is flagged. **Not used in CI yet.**
- Default (no `--fail`) → **always exits 0** (advisory).

It is a heuristic: a controller that logs via a delegated service/observer is a
false positive (logging lives at the service funnel for most modules).

## 2. Composer scripts added

```
composer logs:audit          # advisory console report
composer logs:audit:json     # also writes storage/reports/logs-audit-report.json
composer logs:audit:fail     # --fail (NOT for default use yet)
```

Existing scripts (`test`, `ui:audit*`, lifecycle hooks) were left untouched.

## 3. CI workflow updated

Yes — `.github/workflows/ui-audit.yml` (the existing governance workflow, renamed
to **“UI & Logging Audit”**) gained a non-blocking logging step that reuses the
same PHP/Composer setup:

```yaml
- name: Logging audit (advisory report)
  run: php artisan logs:audit --json || true

- name: Upload logging audit report
  if: always()
  uses: actions/upload-artifact@v4
  with:
    name: logs-audit-report
    path: |
      storage/reports/logs-audit-report.json
    if-no-files-found: warn
```

It runs **before** the UI critical-only gate, so the logging report + artifact are
always produced regardless of the UI gate outcome.

> **Important fix:** the repo's `.gitignore` ignored the **entire `/.github`**
> directory, so this workflow (and the Phase 8 `copilot-instructions.md`) would
> never be committed — meaning CI would never run. Changed to `/.github/*` +
> `!/.github/workflows` + `!/.github/copilot-instructions.md`, so the workflow and
> governance file are trackable while any other local `.github` content stays
> ignored. Commit `.github/workflows/ui-audit.yml` for CI to take effect.

## 4. Is CI blocking?

**Partly — Stage 2 is now enabled.** The workflow runs logging in two steps:

1. **Advisory report** (`logs:audit --json || true`) — always runs, never fails,
   uploads `storage/reports/logs-audit-report.json` + the baseline.
2. **Stage-2 gate** (`logs:audit --fail --only-real-gaps --min-severity=HIGH`)
   — fails the build on a **HIGH or CRITICAL** `MISSING_LOG` (an un-funnelled
   mutating action). It does **not** block on `SERVICE_FUNNEL_COVERED`,
   `KNOWN_BACKLOG`, `INTENTIONALLY_SKIPPED`, `NEEDS_REVIEW`, or MEDIUM/LOW
   findings.

Safe to enable because the eight burn-downs + role/permission + user-role
security logging + the Stage-2 prep (accounting, cashier shifts, purchase returns,
payroll, HR leave, insurance verification) brought **MISSING_LOG and NEEDS_REVIEW
both to 0**. The gate is green today and acts as a regression guard against new
HIGH/CRITICAL gaps.

## 5. Current logs:audit count

**73 controllers** flagged with likely-unlogged mutating actions, by area:

| Area | Flagged |
|------|--------:|
| Admin | 64 |
| Billing | 4 |
| Doctor | 2 |
| Theatre | 2 |
| Lab | 1 |

Many are **false positives** — `Doctor/ConsultationController`, `Theatre`, `Lab`,
and `Billing` controllers delegate to services/funnels that DO log (consultation,
procedures, investigations, claims). The genuine remaining work is concentrated in
**Admin** (stock/procurement, roles/permissions/modules/settings, catalogues).

## 6. Current known remaining areas

- **Stock / Procurement** — raw stock movements (ledger), purchase orders,
  transfers, requisitions, adjustments, returns, supplier ledger.
- **Admin / System** — users, roles, permissions, modules enable/disable, settings,
  catalogues (services/drugs/lab tests/procedures), retention.
- **Notifications** — failures / policy-relevant events.
- **Per-module polish follow-ups** (documented in each burn-down report): bed
  transfers, emergency vitals/bay, payment reversal, claim mirror/`ClaimPayment`,
  pattern-created clinical records, sample collection, print/export events.

## 7. How developers run the audit locally

```bash
php artisan logs:audit                      # console summary + flagged controllers
php artisan logs:audit --json               # + storage/reports/logs-audit-report.json
php artisan logs:audit --module=Admin       # scope to one area
composer logs:audit                         # same, via composer
```

Before finishing a feature that adds a mutating controller action, run it and
confirm the action is logged (directly or via its service funnel), with patient/
visit context where patient-related.

## 8. Blocking promotion path

The prerequisites are met (funnel-aware classification + clean baseline +
MISSING_LOG 0 + NEEDS_REVIEW 0), so **Stage 2 is enabled** in CI. The staged path:

1. **Stage 1 — done.** `--fail --only-real-gaps --min-severity=CRITICAL`. Blocked
   only CRITICAL `MISSING_LOG`.
2. **Stage 2 — ENABLED NOW.** `--fail --only-real-gaps --min-severity=HIGH`. Blocks
   HIGH + CRITICAL `MISSING_LOG`. Green today (the 7 NEEDS_REVIEW controllers were
   wired/justified in the Stage-2 prep, so no HIGH gap remains). Regression guard
   against any new un-funnelled HIGH/CRITICAL action.
3. **Stage 3 — later.** `--fail --strict`. Enable after the 22 `KNOWN_BACKLOG` items
   are burned down and the baseline reflects a clean state; then any new
   un-baselined finding fails. Re-run `composer logs:audit:baseline` after each
   backlog clear so `--strict` stays honest.

Do **not** silence findings to force the gate green, and do **not** baseline a real
HIGH/CRITICAL gap (the baseline writer already refuses to). Advance a stage only by
clearing its findings, not by loosening the rule.
