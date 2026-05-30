# Roles, Permissions & Modules — Remaining Recommendations

_Last updated: November 2025_

This document captures **future work** that was deliberately excluded from the current implementation slice (see [SOLUTION_REPORT](ROLES_PERMISSIONS_SOLUTION_REPORT.md)). Each item has a clear scope and a rough effort estimate so the team can prioritise.

---

## R1 — Tighten remaining admin index routes

**Status:** advisory.
**Severity:** low (sidebar already hides them).

A handful of admin index routes still gate only on `auth` rather than on `can:`. The `permissions:audit` command lists them on every run — the current set includes:

- `admin.visits.index`
- `admin.appointments.index`
- `admin.wards.index`
- `admin.admissions.index`
- `admin.claims.index`
- `admin.suppliers.index`

Add the corresponding `can:<perm>.view` middleware so direct URL access is also blocked, not just sidebar discovery.

**Effort:** ~30 minutes.

---

## R2 — Resolve duplicate permission names

The seeder contains a few near-duplicates that diverged across feature work:

| Pair | Recommendation |
|---|---|
| `product.link_department` ↔ `product.link_departments` | Keep the plural; alias the singular for one release; remove. |
| `stock.location.manage` ↔ `stock_location.manage` | Keep the dot-form (`stock.location.manage`); migrate users via a one-time `php artisan` script. |
| `procedure.catalogue.view` ↔ `procedure_catalogue.view` | Keep the dot-form; migrate. |

The `permissions:audit` command surfaces these on every run.

**Effort:** ~1–2 hours including a data migration that copies role assignments from old to new and deletes the orphan.

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
