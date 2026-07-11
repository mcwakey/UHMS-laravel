# Payment Timing Policy Phase 1 Report

## Scope and outcome

Phase 1 adds a typed, database-backed, localised, auditable payment-timing configuration foundation. It does not add visit-level policy data, patient-risk data, policy-source persistence, resolution against a patient/insurer, or any new service/payment gate.

## Existing architecture reviewed

- `App\Models\Setting` and the existing `settings` table provide grouped, typed, cached values through `getValue`, `setValue`, and `getGroup`. The payment-timing settings use this infrastructure; no migration or parallel settings table was needed.
- `Admin\Settings\SettingsController`, the `resources/views/settings` pages/sidebar, and the `settings.manage` permission provide the existing administration surface and backend authorization convention.
- `App\Enums\VisitType` is the canonical visit-type vocabulary: `outpatient`, `inpatient`, and `emergency`. Those values drive configuration, validation, service access, and the UI.
- Invoice and payment state remain represented independently by `InvoiceStatus`, `PaymentStatus`, invoices, invoice items, receivables, and payments.
- Existing accounting paths include `InvoiceReceivable`, `InvoiceReceivableService`, and `PaymentService`; this phase does not alter them or their allocation/posting behavior.
- Insurance/sponsor/corporate foundations include patient insurance models/services, `Sponsor`, `SponsorAuthorization`, and `CorporateClient`. They are not inspected by the new configuration reader.
- Existing billing-policy behavior is implemented by `config/billing_policy.php`, `BillingPolicyService`, `PaymentGateService`, `VisitBillingOverrideService`, `PatientOutstandingBalanceService`, and `PreviousBalanceOverrideService`. Phase 1 deliberately does not connect the new settings to those services.
- `ActivityLogService` and `LogModule::SETTINGS` provide the shared activity-log architecture. Payment-timing updates use it rather than a separate audit store.
- English/French PHP language files and the existing localisation parity tooling are used for every new user-facing string.

## Files created

- `app/Enums/VisitPaymentTimingPolicy.php`
- `app/Enums/VisitPaymentPolicySource.php`
- `config/payment_timing.php`
- `app/Services/Billing/PaymentTimingConfigurationService.php`
- `app/Http/Requests/UpdatePaymentTimingSettingsRequest.php`
- `database/seeders/PaymentTimingSettingsSeeder.php`
- `resources/views/settings/payment-timing.blade.php`
- `lang/en/payment_timing.php`
- `lang/fr/payment_timing.php`
- `tests/Unit/PaymentTimingEnumTest.php`
- `tests/Feature/PaymentTimingConfigurationTest.php`
- `tests/Feature/PaymentTimingSettingsTest.php`
- `docs/billing/PAYMENT_TIMING_POLICY_PHASE_1_REPORT.md`

## Files modified

- `app/Http/Controllers/Admin/Settings/SettingsController.php`
- `database/seeders/DatabaseSeeder.php`
- `resources/views/settings/partials/sidebar.blade.php`
- `routes/web.php`

## Domain vocabulary

`VisitPaymentTimingPolicy` defines `inherit`, `pay_before_service`, `pay_after_all_services`, and `running_bill`. Its operational-policy helper excludes `inherit`, and labels/descriptions resolve from localisation.

`VisitPaymentPolicySource` defines `global_default`, `visit_type`, `patient_risk`, `insurance`, `corporate_account`, `manual_override`, and `emergency_policy`. It is foundational only and is not persisted or resolved in Phase 1.

## Configuration and settings integration

`config/payment_timing.php` supplies compatibility-safe defaults. The feature flag defaults to false, emergency and inpatient visits default to running bills, emergency stabilisation protection defaults to true, and closure settings are preparatory only.

The database group is `payment_timing`, with these keys:

- `enabled`
- `default_policy`
- `outpatient_policy`
- `inpatient_policy`
- `emergency_policy`
- `emergency_never_block_stabilisation`
- `require_settlement_for_pay_after_services`
- `require_settlement_for_running_bill`
- `allow_outstanding_balance_override`

`PaymentTimingConfigurationService` reads database values first and configuration defaults second. It returns enum/bool values, resolves a visit-type `inherit` to the operational global default, safely handles unknown visit types and invalid legacy policies, and never returns `inherit` as the global operational default. Database availability failures fail safely to configuration and are reported through the application logger.

## Administration, validation, permission, and audit

The existing settings sidebar now links to a Payment Timing Policies page. The page offers the feature flag, operational-only global default, enum-driven visit types, emergency protection, and preparatory financial-closure settings. It uses the existing Blade/Bootstrap design and contains no inline JavaScript.

The update form request uses enum validation, rejects `inherit` globally, accepts it per visit type, rejects unknown visit-type policy keys, and normalises booleans. The complete payload is validated before the transaction, so invalid requests preserve all current settings and create no audit event.

Both GET and PUT routes remain inside the existing `can:settings.manage` group. No duplicate permission was introduced. Successful changed values are written transactionally and logged once as `PAYMENT_TIMING_SETTINGS_UPDATED` under `SETTINGS`, including group, changed keys, old values, new values, authenticated causer, and timestamp. Unchanged values are not logged.

## Migration and seeding

No schema migration was added because the existing arbitrary grouped settings table meets the requirements. `PaymentTimingSettingsSeeder` uses `firstOrCreate`, does not overwrite administrator values, and is registered in `DatabaseSeeder`.

`php artisan migrate --force` reported `Nothing to migrate`. The focused seeder ran twice successfully, and the group remained at 9 unique rows, confirming idempotence.

## Localisation

All policy names/descriptions, source labels, page labels, help text, validation messages, and success feedback are present in English and French in focused `payment_timing.php` files. The EN/FR parity check passed.

## Focused tests and verification

Executed successfully:

- PHP syntax checks for all introduced PHP files.
- `php artisan test --filter=PaymentTiming`: 13 tests passed, 43 assertions.
- `php artisan test --filter=PaymentTimingConfiguration`: 3 tests passed during the first focused run; the final combined run includes 4 configuration tests.
- `php artisan test --filter=PaymentTimingSettings`: 6 tests passed during the first focused run; the final combined run includes 7 settings tests.
- `php artisan route:list --name=admin.settings.payment-timing`: 2 routes registered.
- `php artisan config:clear`: passed.
- `php artisan view:clear`: passed.
- `php artisan view:cache`: passed.
- `php scripts/localisation-parity-check.php`: passed.
- `php scripts/localisation-audit.php --summary`: completed; one pre-existing active runtime candidate was reported across the wider application.
- `php artisan permissions:audit`: no route-referenced permissions missing and no unprotected admin mutation routes.
- `php artisan logs:audit --only-real-gaps --min-severity=CRITICAL`: no real missing logs; the command listed the repository's existing classified backlog separately.

The full Laravel and Playwright suites were intentionally not run.

## Regression and enforcement confirmation

A focused regression test enables the new Phase 1 setting and confirms that the existing billing gate remains in its independently configured advisory behavior. No existing consultation, investigation, pharmacy, procedure, admission, emergency, completion, discharge, settlement, receivable, payment, or accounting code reads the new setting.

## Legacy constraint and Phase 2 considerations

UHMS already has a mature, separate billing-policy/gate implementation and visit billing overrides. Its current `billing_policy.enforce` default and existing behavior predate this phase. Phase 2 should explicitly map the new typed vocabulary into that existing policy service and gate behind a deliberate rollout flag, avoiding two competing sources of truth. It should also define precedence and source reporting before adding visit-level persistence, risk/insurance/corporate resolution, manual overrides, or closure enforcement.
