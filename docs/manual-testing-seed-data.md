# UHMS Manual Testing Seed Data

This seed profile is for local/manual/stress testing only. It is deliberately
separate from the default launch seed flow.

Do not use this profile for production launch data.

## Safety

Default launch seeding remains:

```bash
php artisan db:seed
```

Manual testing data is never called from `DatabaseSeeder`.

Manual test seeding is blocked in production and requires:

```env
UHMS_ALLOW_MANUAL_TEST_SEED=true
```

Manual records use clear markers:

- `MT-*` reference prefixes
- `@uhms.test` emails
- metadata snapshots with `seed_profile = manual_test` where supported

## Run

Preferred:

```powershell
$env:UHMS_ALLOW_MANUAL_TEST_SEED='true'; php artisan uhms:seed-manual-test --scale=small
$env:UHMS_ALLOW_MANUAL_TEST_SEED='true'; php artisan uhms:seed-manual-test --scale=medium
$env:UHMS_ALLOW_MANUAL_TEST_SEED='true'; php artisan uhms:seed-manual-test --scale=large
```

Bash:

```bash
UHMS_ALLOW_MANUAL_TEST_SEED=true php artisan uhms:seed-manual-test --scale=small
UHMS_ALLOW_MANUAL_TEST_SEED=true php artisan uhms:seed-manual-test --scale=medium
UHMS_ALLOW_MANUAL_TEST_SEED=true php artisan uhms:seed-manual-test --scale=large
```

If `UHMS_ALLOW_MANUAL_TEST_SEED=true` is already set in `.env`, you can run:

```bash
php artisan uhms:seed-manual-test --scale=small
php artisan uhms:seed-manual-test --scale=medium
php artisan uhms:seed-manual-test --scale=large
```

Direct seeder:

```powershell
$env:UHMS_ALLOW_MANUAL_TEST_SEED='true'; php artisan db:seed --class=ManualTestingSeeder
```

or:

```bash
UHMS_ALLOW_MANUAL_TEST_SEED=true php artisan db:seed --class=ManualTestingSeeder
```

For direct seeding, set the scale with:

```env
UHMS_MANUAL_TEST_SCALE=small
```

## Clear

Preview the cleanup summary and confirm interactively:

```bash
php artisan uhms:clear-manual-test-data
```

Non-interactive cleanup:

```bash
php artisan uhms:clear-manual-test-data --force
```

Cleanup deletes only manual-test records identified by `MT-*` prefixes and
`@uhms.test` users.

## Scale Targets

| Scale | Patients | Visits | Invoices | Lab Requests | Prescriptions | Admissions | Emergency Cases | Users |
| --- | ---: | ---: | ---: | ---: | ---: | ---: | ---: | ---: |
| small | 200 | 300 | 150 | 100 | 60 | 20 | 20 | 30 |
| medium | 1,000 | 2,500 | 1,500 | 600 | 400 | 150 | 150 | 80 |
| large | 5,000 | 15,000 | 8,000 | 3,000 | 2,000 | 600 | 700 | 200 |

## Test Accounts

All manual test accounts use password:

```text
password
```

| Area | Test Account | Purpose |
| --- | --- | --- |
| Admin | admin.manual@uhms.test | Full system testing |
| Doctor | doctor.opd@uhms.test | Consultation workflow |
| Ward | nurse.ward@uhms.test | Admission and ward workflow |
| Emergency | emergency.user@uhms.test | Emergency workflow |
| Lab | lab.tech@uhms.test | Investigation workflow |
| Radiology | radiology.user@uhms.test | Imaging workflow |
| Pharmacy | pharmacy.user@uhms.test | Prescription workflow |
| Accounts | accounts.user@uhms.test | Accounting workflow |
| Billing | billing.user@uhms.test | Billing and payment workflow |
| Claims | claims.user@uhms.test | Claims workflow |
| HR | hr.user@uhms.test | HR and payroll workflow |
| Reception | reception.user@uhms.test | Registration and appointments |
| Stores | store.user@uhms.test | Inventory workflow |
| Theatre | theatre.user@uhms.test | Procedure workflow |

## Seeded Departments

Manual departments are created with `MT-DEP-*` codes across:

- consultation
- emergency
- investigation
- radiology
- procedure
- theatre
- inpatient/treatment
- nursing
- maternity
- pharmacy
- stores
- support
- records
- finance
- administrative

Dashboard titles and descriptions include the department name so dashboard
personalization can be verified with realistic labels.

## Seeded Workflow Scenarios

The manual profile seeds source records for:

- direct visits
- appointment visits
- checked-in visits
- no-show and cancelled appointments
- queue entries
- visit status logs
- emergency visits and emergency cases
- lab/investigation requests
- completed and abnormal lab results
- prescriptions and prescription items
- admissions and discharge/death/transfer status samples
- cash, insurance, corporate, partially paid, paid, refunded, and unpaid invoices
- payments and payment provider transaction mocks
- SMS provider templates and message mocks
- HR employees and attendance logs
- payroll records across multiple periods
- audit/activity log samples

## Billing and Accounting Coverage

Seeded billing records include:

- cash invoices
- insurance invoices
- corporate invoices
- unpaid invoices
- partially paid invoices
- paid invoices
- cancelled/refunded invoices
- invoice items from consultation, lab, pharmacy, and procedure source types
- payment records

Accounting journal samples are seeded only when fiscal year and accounting
period records already exist.

## Pending Coverage

The current project has no `branches` table/model, so branch-count scale targets
are documented but not physically seeded.

The first manual profile seeds real source data for dashboards and workflows. It
does not yet fully synthesize every deep sub-workflow record for:

- full procedure request lifecycle stages
- detailed radiology report validation records
- stock batches and valuation lines for every product
- credit-note/write-off/discount approval workflows beyond invoice fields
- full claims payment lifecycle for every provider

Those should be expanded as the related workflow tables stabilize.

## Implementation Files

- `database/seeders/ManualTestingSeeder.php`
- `database/seeders/ManualTesting/*`
- `php artisan uhms:seed-manual-test`
- `php artisan uhms:clear-manual-test-data`
