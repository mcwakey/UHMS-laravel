# Permission Gap Burn-Down Report

Date: 2026-06-03

Scope: full-system UHMS permission gap burn-down, with billing discount treated as the confirmed missing feature and high-risk financial, clinical, stock, blood bank, emergency, theatre, and admin actions checked for route/backend/UI drift.

## Audit Summary

Commands run:

- `php artisan permissions:audit`
- `php artisan permissions:audit --json`
- High-risk keyword sweep across routes, controllers, services, views, seeders, and config.

Initial audit at the start of this pass showed 399 DB permissions, 199 route-referenced permissions, no route permissions missing from the DB, and 6 unprotected admin mutation routes: notification read/delete routes and self-service profile routes.

After the discount routes were moved to new permission names, the audit correctly flagged the new discount route permissions as missing from the local DB until the updated permissions were synced.

Final audit:

- Permissions in DB: 408
- Permissions referenced in routes: 206
- Admin mutation routes scanned: 347
- Route permissions missing in DB: 0
- Unprotected admin mutation routes: 0
- Possible duplicate permission-name candidates remaining: 8 pairs

The JSON report was regenerated at `storage/reports/permissions_audit.json`.

## Permissions Added

Billing discount permissions added as the canonical naming convention:

- `billing.discount.view`
- `billing.discount.apply`
- `billing.discount.approve`
- `billing.discount.remove`
- `billing.discount.override_limit`
- `billing.discount.report`

Admin/system permissions added:

- `modules.enable`
- `modules.disable`
- `permissions.assign_critical`

Compatibility note: existing project-native names such as `invoices.view`, `invoices.edit`, `invoices.void`, `payments.refund`, `stock.adjust`, `store.purchase.approve`, `blood_bank.units.issue`, and `procedure.*` were not renamed. This avoids duplicate meanings and preserves existing roles. `BillingService::applyDiscount()` still accepts the legacy `invoices.discount` permission for apply compatibility if an installation already used it.

## Discount Controls

Backend enforcement:

- Apply discount route now requires `billing.discount.apply`.
- Remove discount route now requires `billing.discount.remove`.
- Discount report route requires `billing.discount.report`.
- Service-level apply requires `billing.discount.apply` or legacy `invoices.discount`.
- Service-level remove requires `billing.discount.remove`.
- Exceeding the configured discount limit requires `billing.discount.override_limit`.
- Paid, cancelled, voided, waived, or refunded invoice items reject discount changes.
- Header-level invoice creation discounts are blocked so discounts must go through the audited item-level flow.

Business safety:

- Discount reason is required.
- User and timestamp are recorded.
- Old amount and new amount are recorded.
- Invoice and invoice item are recorded.
- Override events are flagged as high risk.
- Discount apply/remove/override events are written to activity logs.
- Discount history appears on invoice detail when the user has `billing.discount.view`.
- Discount report appears under Accounts & Finance when the user has `billing.discount.report`.

UI enforcement:

- Apply Discount button is hidden without `billing.discount.apply`.
- Remove Discount uses `<x-confirm-form>` and is hidden without `billing.discount.remove`.
- Discount History is hidden without `billing.discount.view`.
- Discount modal validates amount and reason.
- Cancel invoice UI now uses `invoices.void`, matching backend intent.

## Other Gaps Closed

- Notification mutation routes are now protected by `notifications.view` or `notifications.delete`.
- User activation/deactivation route and menu action now use `users.disable`.
- Module enable/disable now checks `modules.enable` or `modules.disable` at controller level and in the module UI.
- Critical role permission changes now require `permissions.assign_critical`.
- Critical direct user permission changes now require `permissions.assign_critical`.
- Role and user permission UIs preserve existing critical grants but lock critical checkbox changes for users without `permissions.assign_critical`.
- `permissions:audit` no longer reports self-service profile routes as admin mutation gaps.

## Role Defaults

Updated defaults:

- Super Admin/Admin receive all new permissions through the existing all-permissions sync behavior.
- Cashier receives `billing.discount.view` and `billing.discount.apply`.
- Accountant receives `billing.discount.view`, `billing.discount.apply`, `billing.discount.approve`, and `billing.discount.report`.
- Doctor, Nurse, Pharmacy, Lab, Claims Officer, and other low-trust clinical/operational roles do not receive discount override/remove permissions by default.
- `billing.discount.remove`, `billing.discount.override_limit`, and `permissions.assign_critical` are critical-risk permissions and are not granted to low-trust roles by default.

For the local workspace DB, the new migration was applied and the new permission rows/grants were inserted narrowly through query-builder updates to avoid running the broad role `syncPermissions()` seeder against existing local role customizations.

## Existing High-Risk Coverage Verified

The audit and keyword sweep confirmed existing permission coverage in the main high-risk areas using UHMS's current canonical names:

- Billing/payment: invoice view/create/edit/void, payment view/create/refund/void, credit notes, waivers, credit approval, payment gate override, deferred OPD settlement, discharge override.
- Patients: mark deceased, merge request/approval/execute/identity confirmation, insurance management.
- Visits/consultation/emergency: visit transitions, preview, emergency case, triage, notes, medication, consumables, disposition, billing.
- Admission/MAR: medication boards, MAR chart, medication administration, hold/stop/correct.
- Pharmacy/investigations/procedures/theatre: dispensing, lab request/result/verify, procedure accept/reject/schedule/cancel/report, theatre team/consumables/case controls.
- Stock/procurement: receive, adjust, transfer, return, requisitions, purchase orders, purchase returns.
- Blood bank: donor/screening, request approval, crossmatch/verify, issue, compatibility override, emergency release, transfusion, discard.
- Admin/system: users, roles, direct permissions, modules, settings, logs, exports.

## Remaining TODOs

- `billing.discount.approve` is seeded, described, and visible for role assignment, but no separate discount approval queue exists yet. Current over-limit behavior is direct block/unblock through `billing.discount.override_limit`.
- The audit still reports 8 possible duplicate permission-name pairs. These were not renamed in this pass because renaming requires compatibility mapping and role-assignment migration.
- Some permissions are intentionally `unused_in_routes` because they are enforced in services, views, reports, or future workflow surfaces rather than as direct route middleware.
- Consider adding a read-route audit mode for auth-only GET/index routes. The current strict audit focuses on mutation routes and route-referenced permission drift.

## Tests Run

- `php artisan test tests/Feature/BillingDiscountPermissionTest.php` - 8 passed, 36 assertions.
- `php artisan test tests/Feature/BillingTest.php tests/Feature/BillingEnhancementsTest.php tests/Feature/Permissions/PermissionsAuditCommandTest.php tests/Feature/Permissions/PermissionsAuditStrictTest.php` - 19 passed, 54 assertions.
- `php artisan permissions:audit --json` - 0 route permissions missing, 0 unprotected admin mutation routes.
- `git diff --check` - no whitespace errors; only line-ending normalization warnings.

