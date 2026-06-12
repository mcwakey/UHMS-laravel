# UHMS Localisation Coverage Cleanup Report

Date: 2026-06-12

## Scope

Implemented a focused cleanup from `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md` for real runtime pages and response payloads. This pass prioritised controller JSON/redirect messages, report labels, chart labels, consumables page copy, and service-generated labels that are returned to UI/report consumers.

## Audit Delta

| Metric | Before | After |
| --- | ---: | ---: |
| Files scanned | 1248 | 1248 |
| Files with candidates | 551 | 536 |
| Hardcoded candidates | 19758 | 19723 |

The audit was rerun with:

```bash
php scripts/localisation-audit.php
```

## Implemented Runtime Cleanup

- Localised controller JSON/response messages for appointments, medication administration, patient insurance, service catalogue pricing, triage, visits, consultations, medical patterns, statistics exports, and activity-log exports.
- Localised accounting, billing aging, AR/AP aging, emergency consumables, emergency billing groups, and statistics report/chart labels.
- Added matching EN/FR keys in `messages.php`, `accounting.php`, `reports.php`, `stock.php`, and `emergency.php`.
- Kept locale key parity for all touched translation files.

## Classification Notes

### Real App Strings Fixed

- Controller/API messages that are visible to users or frontend clients.
- Report titles, chart labels, table column labels, and filter option labels.
- Service labels returned in report payloads or validation errors.

### False Positives / Deferred

- `app/Services/SidebarMenuBuilder.php` still appears in the audit because it stores raw source labels before the menu translation layer resolves them.
- Large high-priority Blade findings such as `resources/views/components/modal-popup.blade.php`, `widgets.blade.php`, `ui-dropdowns.blade.php`, `tables-basic.blade.php`, and other dashboard template files appear to be demo/template UI assets or bulk legacy sample pages. They were not translated in this pass.
- Enum/model labels such as `InvoiceStatus`, `ClaimStatus`, `BillingType`, and model fallback labels should be handled in a dedicated enum/status-label pass to avoid changing canonical domain behaviour unexpectedly.
- Remaining service event titles in admission, blood bank, procedure workflow, lab workflow, and merge-preview services need manual confirmation of whether they are user-facing timeline/notification titles or internal audit/event labels.

## Verification

Passed:

- `php artisan view:clear`
- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan route:list` (714 routes)
- PHP lint for all touched app and language files
- EN/FR nested key parity for touched translation files
- `php scripts/localisation-audit.php`

Not fully completed:

- Full recursive PHP lint over `app,database,routes,config` was attempted but timed out after 300 seconds. Touched-file lint passed cleanly.

## Follow-Up Recommendations

1. Confirm whether remaining service `title` / `message` audit findings are user-facing timeline or notification copy, then localise them in a workflow-specific translation group.
2. Run a separate cleanup for real Blade screens, excluding demo/template pages from the audit input if possible.
3. Add an audit allowlist for language files, source-label arrays translated downstream, and vendor/demo Blade templates to make future localisation reports more actionable.
