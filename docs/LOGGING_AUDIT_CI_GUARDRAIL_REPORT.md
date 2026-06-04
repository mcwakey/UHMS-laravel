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

## 4. Is CI non-blocking?

**Yes.** The logging step uses the default (no `--fail`) command, which exits 0,
plus a defensive `|| true`. It can never fail the build. The only blocking step in
the workflow is the pre-existing **UI** critical-only gate (unrelated to logging).

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

## 8. When to make logs:audit blocking

Promote to a blocking gate (`--fail`) only after:
1. the **stock/admin** burn-down lands (cuts the 64 Admin findings), and
2. `logs:audit` gains a **baseline/allowlist** (like `ui:audit`) so the remaining
   service-funnel false positives don't block builds, **or** a per-controller
   ignore marker is added for verified-OK controllers.

Until then it stays advisory. Do not add `--fail` to CI, and do not silence
findings to force it green.
