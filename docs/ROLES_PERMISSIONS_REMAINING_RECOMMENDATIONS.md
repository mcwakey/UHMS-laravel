# Roles, Permissions & Modules - Remaining Recommendations

_Last updated: 2026-06-03_

This document captures **future work** that was deliberately excluded from the current implementation slice (see [SOLUTION_REPORT](ROLES_PERMISSIONS_SOLUTION_REPORT.md)). Each item has a clear scope and a rough effort estimate so the team can prioritise.

---

## June 2026 Burn-Down Update

The full-system permission burn-down closed the confirmed billing discount gap and several adjacent admin gaps. See [PERMISSION_GAP_BURN_DOWN_REPORT](PERMISSION_GAP_BURN_DOWN_REPORT.md).

Current `php artisan permissions:audit --json` state:

- Route permissions missing in DB: 0
- Admin mutation routes without `can:` or `role:` middleware: 0
- Remaining duplicate-name candidates: 8 pairs

Added or tightened:

- `billing.discount.view/apply/approve/remove/override_limit/report`
- `modules.enable`
- `modules.disable`
- `permissions.assign_critical`
- Notification mutation route guards
- User activation/deactivation guard
- Critical role/direct-permission assignment lock

---

## R1 - Add Read-Route Strictness

**Status:** advisory.
**Severity:** low/medium.

The current `permissions:audit` strictness focuses on mutation routes and route permissions referenced in code. Add an optional read-route mode for sensitive index/detail GET routes so direct URL access is checked as strongly as mutation access.

Suggested command option:

```bash
php artisan permissions:audit --include-read-routes
```

Flag auth-only GET routes in sensitive admin modules, especially billing, patients, clinical records, logs, reports, settings, roles, users, stock, and blood bank.

**Effort:** ~1-2 hours.

---

## R2 - Resolve Duplicate Permission Names

The audit still reports these possible duplicate pairs:

| Pair | Recommendation |
|---|---|
| `consultation.create` / `consultations.create` | Pick one canonical consultation namespace, alias for one release, then migrate role assignments. |
| `patients.create` / `payments.create` | Likely false positive by edit distance; keep documented unless audit scoring is refined. |
| `patients.view` / `payments.view` | Likely false positive by edit distance; keep documented unless audit scoring is refined. |
| `procedure.reschedule` / `procedure.schedule` | Keep both if they map to distinct workflow actions; otherwise migrate to schedule plus workflow state. |
| `procedure.view` / `procedures.view` | Pick singular or plural procedure namespace and alias the other. |
| `reports.consultation` / `reports.consultations` | Pick plural to match existing report grouping, then migrate. |
| `reports.diagnoses` / `reports.diagnosis` | Pick one report namespace, then migrate. |
| `theatre.cases.reschedule` / `theatre.cases.schedule` | Keep both if reschedule is a distinct high-risk workflow action; otherwise migrate to schedule plus workflow state. |

The `permissions:audit` command surfaces these on every run.

**Effort:** ~2-4 hours including a data migration that copies role assignments from old to new and preserves aliases for one release.

---

## R3 — Surface metadata in `/admin/roles` UI

`config/permissions.php` now exposes a description and risk colour for every permission, but the role-edit screen still shows raw dot-names. Update `resources/views/admin/roles/edit.blade.php` (or the Vue equivalent if migrated) to read `App\Support\PermissionMeta::for(...)` and render:

- group-by module (collapsible)
- description tooltip
- coloured risk badge (`config('permissions.risk_levels')`)

**Effort:** ~half a day.

---

## R4 — Surface module description on the admin module index

The `description` column is populated by `ModuleSeeder` but `resources/views/admin/modules/index.blade.php` shows only the module name. Add a one-line description under each card.

**Effort:** ~15 minutes.

---

## R5 — Front-end `<Can>` component / `usePermissions` composable

`auth.permissions` is now shared. Add a thin Vue helper to make consumption uniform:

```vue
<Can permission="patients.delete">
  <button class="btn btn-danger">Delete</button>
</Can>
```

backed by `resources/js/Composables/usePermissions.js`. Today every Vue page either re-implements the check or hides UI server-side via the sidebar.

**Effort:** ~1 hour.

---

## R6 — Permissions admin dashboard

Once R3 + R5 are done, build a read-only admin dashboard at `/admin/permissions` that consumes `storage/reports/permissions_audit.json` (regenerated nightly via the scheduler) and shows:

- total count by risk
- routes missing a `can:` guard
- DB permissions never referenced in code

This turns the audit command into a continuous monitoring tool.

**Effort:** ~1 day (largely UI).

---

## R7 — `--strict` flag on `permissions:audit`

Add a `--strict` flag that exits with code `1` when any of the following are non-empty:

- `referenced_not_in_db`
- `unprotected_routes`

Wire into CI so PRs that introduce drift fail.

**Effort:** ~30 minutes.

---

## R8 — Per-user permission overrides UI

Spatie supports direct `givePermissionTo($user, $perm)` independent of roles. The model layer is ready (`User` uses `HasRoles`); a UI on the user-edit page would let admins grant emergency one-off permissions without minting a new role. The `permissions.assign` permission already gates this.

**Effort:** ~half a day.

---

## R9 — Periodic permission attestation

For compliance: schedule a quarterly export of `(user, role, permissions)` to a CSV and email it to the compliance officer. Implement as a scheduled `php artisan permissions:export` command. Store CSVs under `storage/reports/attestation/`.

**Effort:** ~2 hours.

---

## R10 — Documentation maintenance

Re-run the gap analysis whenever a major feature lands. The `permissions:audit --json` output is the source of truth — wire it into the existing `docs/` pipeline so drift is automatically reported in PR reviews.

**Effort:** ongoing.
