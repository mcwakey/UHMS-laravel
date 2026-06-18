# UHMS External Integrations — Phase 1: SMS & Payment Gateways

Date: 2026-06-18

## Summary

Phase 1 delivers two clean, provider-based integration layers for UHMS:

- **SMS Gateway module** (`sms_gateway`) — send SMS through a configurable active
  provider, store messages/recipients, receive delivery reports, manage templates.
- **Payment Gateway module** (`payment_gateway`) — initiate online / mobile-money
  payments, verify them with the provider, and only then create a normal UHMS
  payment through the existing `PaymentService` (which posts to accounting).

The design is strictly layered: **controllers → services → provider adapters →
shared interfaces**. No SMS or payment-API logic is hardcoded into patient,
appointment, billing, invoice, cashier, or accounting code. Credentials are
encrypted, callbacks are idempotent, and a verified provider transaction is the
only thing that can create a UHMS payment.

## Module changes

- New optional modules seeded in `ModuleSeeder`: `sms_gateway`, `payment_gateway`
  (both default-enabled, disable-able, non-core).
- Admin/billing routes are gated with `module:sms_gateway` / `module:payment_gateway`.
  When a module is disabled the routes return the standard friendly 403
  (`errors.module-disabled`) — clinical and manual/cash billing workflows are
  unaffected.
- `LogModule::INTEGRATIONS` enum case added for audit categorisation.

## Database changes

MariaDB-10.1-safe migrations (no `->json()` — `longText`; short named indexes;
single-active-provider enforced at the service layer, not via partial unique
indexes):

- `2026_06_18_000001_create_integration_provider_tables` →
  `integration_providers`, `integration_provider_credentials`
- `2026_06_18_000002_create_sms_gateway_tables` → `sms_templates`,
  `sms_messages`, `sms_message_recipients`, `sms_delivery_reports`,
  `sms_provider_callbacks`
- `2026_06_18_000003_create_payment_gateway_tables` →
  `payment_provider_transactions`, `payment_provider_callbacks`,
  `payment_provider_attempts`, `payment_provider_refunds`

## Models added

`IntegrationProvider`, `IntegrationProviderCredential` (encrypted cast),
`SmsTemplate`, `SmsMessage`, `SmsMessageRecipient`, `SmsDeliveryReport`,
`SmsProviderCallback`, `PaymentProviderTransaction`, `PaymentProviderCallback`,
`PaymentProviderAttempt`, `PaymentProviderRefund`.

## Provider interfaces

- `App\Contracts\Integrations\SmsProviderInterface`
- `App\Contracts\Integrations\PaymentProviderInterface`

Both use DTOs/value objects (`App\Support\Integrations\…`) — `SmsSendRequest`,
`SmsSendResult`, `SmsStatusResult`, `SmsCallbackResult`, `PaymentInitiationRequest`,
`PaymentInitiationResult`, `PaymentVerificationResult`, `PaymentCallbackResult`,
`PaymentRefundRequest`, `PaymentRefundResult`, `ProviderTestResult` — instead of
passing raw arrays around.

## Provider adapters

Registered in `config/integrations.php` (`providers` registry) and instantiated by
`IntegrationProviderRegistry` with decrypted credentials injected:

- SMS: `NaloSmsProvider`, `FakeSmsProvider`
- Payment: `MtnMomoPaymentProvider`, `NaloPaymentProvider`, `FakePaymentProvider`

Real adapters call the documented provider APIs via the Laravel HTTP client
(timeout-bounded). Where a per-account contract still needs confirmation the code
is marked `TODO` and **fails safely** (returns a failed result) — it never fakes a
successful provider response. Fake providers are fully functional for
local/dev/test/demo and are blocked in production unless
`INTEGRATIONS_ALLOW_FAKE_PROVIDERS=true`.

## Initial providers

- SMS: **Nalo Solutions** (`nalo_sms`)
- Payment: **MTN MoMo** (`mtn_momo`), **Nalo Solutions** (`nalo_payment`)

Adding more providers later (Hubtel, Paystack, Flutterwave, Twilio, Arkesel, …) is a
config + adapter addition only — no controller/billing/accounting changes.

## Services added

`IntegrationProviderService` (provider CRUD + single-active rule + credentials +
test, the audit funnel), `IntegrationCredentialService` (encrypt/mask/decrypt),
`IntegrationProviderRegistry`; SMS: `SmsGatewayService`, `SmsTemplateService`,
`SmsPhoneNumberNormalizer`, `SmsDeliveryReportService`, `SmsCallbackService`;
Payment: `PaymentGatewayService`, `PaymentProviderResolver`,
`PaymentProviderTransactionService`, `PaymentVerificationService`,
`PaymentCallbackService`.

## Active-provider rule

`integration_providers.is_active` + `status='active'`. `IntegrationProviderService::
activate()` runs inside a transaction (with `lockForUpdate`), deactivating any other
active provider of the same `module_type` first. Enforced in code (MariaDB partial
unique indexes are unreliable), with supporting indexes on `(module_type, is_active)`.

## Credential encryption

Values stored only in `integration_provider_credentials.encrypted_value` using
Laravel's `encrypted` cast. Never rendered in Blade (masked as `••••••••`), never
logged (audit records credential *keys* only), never placed in exception messages.
Updates are partial — a blank field keeps the existing value.

## SMS workflow

Controller → `SmsGatewayService::send()` resolves the active provider, normalises
phone numbers (configurable country code; original + normalised retained), creates
the `SmsMessage` + recipients, calls the adapter, records per-recipient status and a
batch reference, and finalises message status (`sent` / `partially_sent` / `failed`).
SMS failures are retained and visible; they never block the calling workflow.
Delivery reports arrive via callback and update recipients idempotently. All
automatic SMS events are **disabled by default** (`config('integrations.sms_events')`);
manual test SMS is available to authorised admins.

## Payment initiation workflow

Controller → `PaymentGatewayService::initiate()` resolves the active provider,
creates a pending `payment_provider_transaction`, calls the adapter, records an
attempt, and stores the returned status / instructions / provider id. The user sees
pending/checkout instructions; nothing is marked paid.

## Callback workflow

Public, session-less, CSRF-exempt routes in `routes/api.php`
(`POST /api/integrations/{sms|payments}/{providerCode}/callback`), rate-limited
(`integration-callbacks`) and optionally IP-allow-listed. Every callback is stored
first (`*_provider_callbacks`), then processed idempotently. The endpoint always
returns a provider-friendly `{"status":"received"}` and never leaks a stack trace.

## Verification workflow

`PaymentVerificationService::verify()`:
1. Idempotency — a transaction already linked to a UHMS payment is never re-paid.
2. Re-verifies with the provider when it supports status checks; otherwise uses the
   (signature-checked) callback as the basis.
3. Validates **amount and currency** against the original request — a mismatch
   blocks payment creation and marks the transaction failed.
4. On success, creates the UHMS payment via the existing `PaymentService::recordPayment()`
   (existing receipt/invoice status + accounting posting + `PaymentRecorded`),
   links `uhms_payment_id`, and audits `PAYMENT_TRANSACTION_PAYMENT_CREATED`.

## Billing / payment integration

Verified provider payments flow through the existing payment service, so receipts,
invoice status, receivables and accounting postings all behave exactly as for a
manual payment. Manual/cash payment entry is untouched. The Payment Gateway adds a
"Payment API Transactions" entry under **Billing & Collections** (cashier/finance) and
an admin/IT-controlled provider configuration area under **Integrations**.

## Security controls

Encrypted + masked credentials; no secrets in logs/Blade/exceptions; webhook
signature/secret verification hooks in adapters (re-verify regardless); rate-limited
callback routes; optional callback IP allow-list; provider-code validation; expected
amount/currency/reference validation; unknown references retained but never posted;
permission-protected admin routes. New env knobs: `INTEGRATIONS_HTTP_TIMEOUT`,
`INTEGRATIONS_CALLBACK_IP_ALLOWLIST`, `INTEGRATIONS_ALLOW_FAKE_PROVIDERS`.

## Permissions

Added to `RoleSeeder` (Super Admin / Admin get all automatically):
`integrations.sms.{view,providers.manage,providers.activate,credentials.manage,test,send,templates.manage,reports.view}`
and `integrations.payments.{view,providers.manage,providers.activate,credentials.manage,test,
transactions.view,transactions.initiate,transactions.verify,callbacks.view,refunds.manage}`.
Role defaults: Cashier & Accountant — view/initiate/verify payment transactions
(Accountant + callbacks.view); Finance Manager inherits these; Receptionist —
`integrations.sms.{view,send,reports.view}`. Provider/credential management is **not**
granted to ordinary billing users.

## Routes / controllers / views

Admin web routes under `admin/integrations/{sms,payments}/…`
(`App\Http\Controllers\Admin\Integrations\*`); public callbacks under
`api/integrations/…` (`App\Http\Controllers\Api\Integrations\*`). Blade views under
`resources/views/admin/integrations/` use the existing Bootstrap 5 + Tabler design
system (`x-page-header`, `x-status-badge`, `x-empty-state`, `x-confirm-form`). Sidebar
gains an **Integrations** section (SMS Gateway, Payment Gateway).

## Audit logging

All provider/message/transaction/callback/refund mutations go through
`ActivityLogService` under `LogModule::INTEGRATIONS` with the action vocabulary from
the spec (`SMS_PROVIDER_*`, `SMS_MESSAGE_*`, `SMS_DELIVERY_REPORT_RECEIVED`,
`PAYMENT_PROVIDER_*`, `PAYMENT_TRANSACTION_*` incl.
`PAYMENT_TRANSACTION_DUPLICATE_CALLBACK_IGNORED`, `PAYMENT_REFUND_*`).

## Localisation audit result

`lang/{en,fr}/integrations.php`, `sms.php`, extended `payments.php` (`gateway.*`),
`statuses.php` (new badge domains), `menu.php`. **Active runtime candidates: 0**.
`localisation-parity-check.php`: **All EN/FR keys in parity**.

## Minimal verification commands run

- `php artisan migrate --force` — 3 new migrations applied (MariaDB).
- `php artisan route:list` — all SMS/payment admin routes + api callbacks registered.
- `php artisan view:cache` / `view:clear` — all Blade compiles.
- `php scripts/localisation-audit.php` — active runtime candidates = 0.
- `php scripts/localisation-parity-check.php` — EN/FR parity OK.
- `php artisan logs:audit` — MISSING_LOG = 0, NEEDS_REVIEW = 2 (unchanged baseline).
- `php artisan permissions:audit --strict` — clean (exit 0).
- `git diff --check` — clean. PHP `-l` lint on all changed files — clean.

## Tests added (focused; full suite intentionally deferred)

`tests/Feature/Integrations/IntegrationsGatewayTest.php` — **21 tests, all passing**:
single-active provider (SMS + payment), credential encryption + masking,
unauthorized credential management blocked, fake SMS send creates message/recipients,
SMS failure retained, Nalo SMS adapter normalises through the interface, idempotent
SMS delivery callback, payment initiation creates transaction, not-paid-until-verified,
verified payment creates UHMS payment via the existing service, amount-mismatch blocks
creation, duplicate callback does not duplicate payment, callback stored, unknown
reference retained-not-posted, public CSRF-free callback endpoint, MTN MoMo adapter
normalises, module middleware blocks both disabled gateways, audit events recorded,
EN/FR localisation keys exist.

The wide full application test suite is **intentionally deferred** to the end of all
current implementation phases, per the phase instruction.

## Known limitations

- Real `NaloSmsProvider`, `MtnMomoPaymentProvider`, `NaloPaymentProvider` payloads
  follow documented shapes but have `TODO`s where a live account is needed to confirm
  exact field names / signing; confirm against sandbox credentials before going live.
- Refunds are **foundation only** (intent + provider response recorded); MoMo
  disbursement and the UHMS credit-note/refund wiring are deferred.
- SMS sending is synchronous in Phase 1 (the queue path is the natural next step).
- Nalo payment verification relies on the signed callback (no status-query endpoint
  implemented yet); amount/currency are still validated before any payment is created.

## Next recommended phase

Queue-backed SMS dispatch + scheduled status reconciliation; wire confirmed live
provider payloads + webhook signature verification; connect refunds to the UHMS
refund/credit-note workflow and accounting; add patient-portal "Pay online" and
optional automatic SMS events (receipts, appointment reminders) behind their toggles.
