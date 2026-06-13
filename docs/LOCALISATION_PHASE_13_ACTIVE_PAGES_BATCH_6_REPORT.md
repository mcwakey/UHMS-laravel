# UHMS Localisation Phase 13 - Active Pages Translation Batch 6

## Scope

Phase 13 covered deeper active pages across claims, insurance provider setup, cash office/accounting flows, and the localisation audit classifier. The implementation stayed inside presentation/localisation surfaces: Blade labels, EN/FR language keys, status label mappings, and audit reporting.

## Implemented

- Added EN/FR claim keys for claim show/review actions, insurance provider configuration, verification help text, membership fields, eligible visit filtering, claim item/payment labels, and modal actions.
- Localised active claims pages:
  - `resources/views/claims/show.blade.php`
  - `resources/views/claims/review.blade.php`
  - `resources/views/claims/eligible-visits.blade.php`
- Localised insurance provider and verification UI:
  - `resources/views/insurance/index.blade.php`
  - `resources/views/insurance/_verification_fields.blade.php`
- Added EN/FR accounting keys for cashier handover, daily collection, reconciliation, income/expense summaries, payment method tables, daily trend labels, and accounting report labels.
- Localised cash-office pages:
  - `resources/views/accounts/handover.blade.php`
  - `resources/views/accounts/daily-collection.blade.php`
  - `resources/views/accounts/reconciliation.blade.php`
- Replaced raw accounting enum/status label rendering with translated labels in:
  - `resources/views/accounting/accounts/_form.blade.php`
  - `resources/views/accounting/accounts/index.blade.php`
  - `resources/views/accounting/dashboard.blade.php`
  - `resources/views/accounting/fiscal-years/index.blade.php`
  - `resources/views/accounting/journals/index.blade.php`
  - `resources/views/accounting/journals/show.blade.php`
  - `resources/views/accounting/payable/payables.blade.php`
  - `resources/views/accounting/payable/payments.blade.php`
  - `resources/views/accounting/payable/statement.blade.php`
  - `resources/views/accounting/periods/index.blade.php`
  - `resources/views/accounting/reports/trial-balance.blade.php`
- Added default EN/FR status labels for accounting/account-payable enum values such as asset, liability, equity, debit, credit, posted, invoice, payment, payable, purchase, and credit note.
- Extended `scripts/localisation-audit.php` with candidate buckets:
  - active runtime
  - demo/template
  - backup-only
  - language-file
  - known false positive
  - service-title manual review
- Regenerated `docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md` with the new candidate classification section.

## Audit Output

`php scripts/localisation-audit.php` completed successfully.

- Files scanned: 1268
- Files with candidates: 510
- Candidates: 18567
- Active runtime candidates: 4340
- Demo/template candidates: 208
- Backup-only candidates: 0
- Language-file candidates: 133
- Known false positives: 13492
- Service-title manual-review candidates: 394

## Verification

Passed:

- `php artisan view:clear`
- `php artisan config:clear`
- `php artisan cache:clear`
- `php artisan route:list` - 714 routes listed
- `php artisan view:cache`
- `php artisan view:clear`
- PHP lint for 72 EN/FR language files
- PHP lint for updated localisation audit script and touched language files
- Nested EN/FR parity for 36 language files
- Targeted scans for Phase 13 dynamic label fallbacks and eligible-visit raw strings
- `php scripts/localisation-audit.php`

## Notes

- No billing, accounting, claims, payment, permission, or restricted-data logic was changed.
- No new packages or Tailwind changes were introduced.
- Remaining localisation backlog is still expected in wider Billing Inertia pages, settings/users/roles/departments/modules, broader report hubs, and manual service-title review candidates surfaced by the audit classifier.
