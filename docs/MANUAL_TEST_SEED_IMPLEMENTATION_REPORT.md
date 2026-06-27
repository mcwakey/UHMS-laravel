# Manual Test Seed Implementation Report

## Summary

Implemented a separate UHMS manual testing seed profile for dashboard and workflow
validation. The default `DatabaseSeeder` was not wired to the heavy manual data.

## How To Run

```bash
UHMS_ALLOW_MANUAL_TEST_SEED=true php artisan uhms:seed-manual-test --scale=small
```

PowerShell:

```powershell
$env:UHMS_ALLOW_MANUAL_TEST_SEED='true'; php artisan uhms:seed-manual-test --scale=small
```

Supported scales:

- `small`
- `medium`
- `large`

Direct seeding is also supported:

```bash
UHMS_ALLOW_MANUAL_TEST_SEED=true php artisan db:seed --class=ManualTestingSeeder
```

PowerShell:

```powershell
$env:UHMS_ALLOW_MANUAL_TEST_SEED='true'; php artisan db:seed --class=ManualTestingSeeder
```

## How To Clean

```bash
php artisan uhms:clear-manual-test-data
php artisan uhms:clear-manual-test-data --force
```

Cleanup targets only `MT-*` records and `@uhms.test` users.

## Preserved Default Launch Seeders

`DatabaseSeeder` remains separate from `ManualTestingSeeder`. Manual test data is
not automatically run by:

```bash
php artisan db:seed
```

## Seeded Areas

- departments by department type
- manual users and roles
- manual patients and insurance policies
- appointments
- visits, queue entries, and visit status logs
- invoices, invoice items, and payments
- investigation services, requests, items, and results
- radiology and procedure services
- prescriptions and prescription items
- admissions
- emergency cases and emergency bay
- employees and attendance
- payroll records
- integration providers, SMS templates/messages, and payment transaction mocks
- audit/activity logs

## Not Fully Seeded Yet

Some requested areas depend on deeper workflow tables or mature service APIs and
are documented as pending in `docs/manual-testing-seed-data.md`:

- branch records, because no branch table exists
- full procedure lifecycle records
- full radiology reporting lifecycle
- exhaustive credit note/write-off/discount approval flows
- full claim payment lifecycle
- complete stock valuation/batch scenarios
