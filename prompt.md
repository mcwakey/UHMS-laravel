You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS External Integrations Phase 1 — SMS Gateway Module & Payment Gateway Module

## Goal

Implement two separate, provider-based integration modules for UHMS:

```text
SMS Gateway Module
Payment Gateway Module
```

Each module must support:

```text
multiple providers configured in the system
only one active provider at a time
sandbox/live mode
encrypted credentials
provider adapters
request/response logging
callback/webhook handling
retry-safe processing
permissions
audit logging
localisation
documentation
```

Initial providers:

```text
SMS providers:
- Nalo Solutions

Payment providers:
- MTN MoMo
- Nalo Solutions
```

Design must allow adding more providers later, such as:

```text
PayGate
FedaPay
QOSIC
Hubtel
Flutterwave
Paystack
AirtelTigo Money
Telecel Cash
Twilio
MNotify
Arkesel
other local or international providers
```

Do not hardcode provider-specific behavior into controllers, billing, invoice, patient, appointment, or accounting modules.

---

# 1. Required Context

Read existing project structure and relevant reports if present:

```text
docs/ACCOUNTING_GAP_EXECUTION_MASTER_PLAN.md
docs/ACCOUNTING_PHASE_0_SHARED_CONTROLS_AND_READINESS_REPORT.md
docs/ACCOUNTING_PHASE_A_BASIC_TO_ADVANCED_POSTING_BRIDGE_REPORT.md
docs/ACCOUNTING_PHASE_B_BANK_ACCOUNTS_AND_RECONCILIATION_REPORT.md
docs/ACCOUNTING_PHASE_C_FAILED_POSTING_WORKBENCH_REPORT.md
docs/ACCOUNTING_PHASE_D_SUBLEDGER_RECONCILIATION_WORKBENCH_REPORT.md
docs/ACCOUNTING_PHASE_E_PAYROLL_ACCOUNTING_POSTING_REPORT.md
docs/ACCOUNTING_PHASE_E2_STATUTORY_PAYROLL_SETTLEMENT_REPORT.md
docs/ACCOUNTING_PHASE_F_CASH_FLOW_AND_EXPORTS_REPORT.md
docs/ACCOUNTING_PHASE_G_BUDGETS_AND_COMMITMENTS_REPORT.md
docs/ACCOUNTING_PHASE_H_FIXED_ASSETS_AND_DEPRECIATION_REPORT.md
docs/ACCOUNTING_PHASE_I_STATUTORY_TAX_ACCOUNTING_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Also inspect existing modules, payments, invoices, notifications, settings, permissions, audit logging, queue/jobs, and localisation structure.

Important testing instruction:

```text
Do not run the wide full application test suite after this phase.
The wide full-suite test is deferred until all current implementation phases are complete.
For this phase, run only necessary safety checks: migrations, route list, view cache, localisation audit, permission audit, logs:audit, PHP lint where practical, focused tests where needed, and git diff check.
```

---

# 2. Core Principles

Implement this as a clean integration layer.

Rules:

```text
SMS logic must not be hardcoded in patient, appointment, billing, or queue controllers.
Payment API logic must not be hardcoded in invoice, visit, cashier, billing, or accounting controllers.
Controllers must call services.
Services must call provider adapters.
Provider adapters must implement shared interfaces.
Provider credentials must be encrypted.
Provider callbacks must be idempotent.
Payment success must be verified before marking an invoice or transaction as paid.
Duplicate callbacks must not duplicate payments.
Failed provider calls must be retained for troubleshooting.
Only one active SMS provider is allowed at a time.
Only one active Payment provider is allowed at a time.
```

Do not introduce a new frontend framework.

Use existing Laravel, Bootstrap 5, Tabler Icons, permissions, audit logging, module middleware, and localisation structure.

Do not add a new HTTP library unless the project already uses it.

Use Laravel HTTP client if available.

---

# 3. Modules

Add two optional modules:

```text
sms_gateway
payment_gateway
```

Suggested module names:

```text
SMS Gateway
Payment Gateway
```

Module behavior:

```text
SMS Gateway disabled:
- SMS settings hidden/inaccessible
- SMS sending disabled safely
- no crash when notifications request SMS
- show "SMS gateway not configured" or equivalent controlled message

Payment Gateway disabled:
- online/mobile money payment initiation hidden/inaccessible
- manual/cash payment workflows still work
- invoices and billing remain usable
- no callback should post payment if module disabled unless explicitly allowed for pending verification
```

Module dependencies:

```text
sms_gateway:
- no dependency on accounting
- can be used by appointments, billing notifications, patient communication, reminders

payment_gateway:
- depends on billing/collections module if such module exists
- must integrate with accounting only through existing payment/accounting services
- must not require accounting_advanced for basic online payment collection
```

Do not make clinical workflows dependent on SMS or payment APIs.

---

# 4. Provider Model

Create a shared provider registry or separate provider tables.

Recommended tables:

```text
integration_providers
integration_provider_credentials
integration_provider_events
```

Or use separate tables if cleaner:

```text
sms_providers
payment_providers
```

The design must clearly separate SMS providers from Payment providers.

## integration_providers

Fields:

```text
id
module_type
code
name
description
environment
base_url
status
is_active
supports_send
supports_status_check
supports_callback
supports_collection
supports_disbursement
supports_refund
supports_balance_check
sender_id
callback_url
webhook_secret_hint
last_tested_at
last_test_status
last_test_message
created_by
updated_by
timestamps
```

module_type:

```text
sms
payment
```

environment:

```text
sandbox
live
```

provider codes:

```text
nalo_sms
mtn_momo
nalo_payment
```

statuses:

```text
draft
active
inactive
suspended
failed
```

Rules:

```text
Only one active provider where module_type = sms.
Only one active provider where module_type = payment.
Activating one provider must deactivate the previously active provider for that module type.
Provider code must be unique per module type.
Provider credentials must not be displayed after saving.
```

Because MariaDB partial unique indexes may be limited, enforce “one active provider” through service-level transaction and validation. Add helpful database indexes but do not rely only on unsupported partial unique constraints.

---

# 5. Credentials

Create secure credential storage.

Recommended table:

```text
integration_provider_credentials
```

Fields:

```text
id
integration_provider_id
credential_key
encrypted_value
is_sensitive
created_by
updated_by
timestamps
```

Credential examples:

For Nalo SMS:

```text
api_key
username
password
sender_id
client_id
client_secret
```

For MTN MoMo:

```text
subscription_key
api_user
api_key
target_environment
collection_primary_key
callback_secret
merchant_account_reference
```

For Nalo Payment:

```text
api_key
merchant_id
client_id
client_secret
callback_secret
```

Do not assume exact credential names are final. Make credentials flexible per provider.

Rules:

```text
Encrypt all credential values.
Do not log secrets.
Do not expose secrets in Blade.
Do not expose secrets in exception messages.
Allow credential update without showing current value.
Mask credential values in UI.
```

Use Laravel encryption helpers, encrypted casts, or existing project secret storage pattern.

---

# 6. SMS Tables

Create:

```text
sms_messages
sms_message_recipients
sms_delivery_reports
sms_templates
sms_provider_callbacks
```

## sms_messages

Fields:

```text
id
message_uuid
provider_id
template_id nullable
sender_id
message_body
message_type
status
scheduled_at nullable
sent_at nullable
completed_at nullable
failed_at nullable
provider_batch_reference nullable
error_code nullable
error_message nullable
metadata_snapshot
created_by nullable
timestamps
```

message_type:

```text
manual
appointment_reminder
invoice_notification
payment_receipt
lab_result_ready
queue_notification
admission_notice
discharge_notice
custom
```

statuses:

```text
draft
queued
sending
sent
partially_sent
failed
cancelled
delivered
undelivered
```

## sms_message_recipients

Fields:

```text
id
sms_message_id
recipient_type nullable
recipient_id nullable
phone_number
normalized_phone_number
recipient_name nullable
status
provider_message_id nullable
provider_status nullable
sent_at nullable
delivered_at nullable
failed_at nullable
error_code nullable
error_message nullable
metadata_snapshot
timestamps
```

## sms_delivery_reports

Fields:

```text
id
sms_message_recipient_id
provider_id
provider_message_id nullable
provider_status
status
reported_at nullable
raw_payload
timestamps
```

## sms_templates

Fields:

```text
id
code
name
description
language
body
is_active
created_by
updated_by
timestamps
```

Rules:

```text
Templates are optional in Phase 1.
SMS can be sent manually without template.
Templates must support placeholders later.
Do not allow unrestricted PHI/clinical data in SMS templates by default.
```

---

# 7. Payment Tables

Create:

```text
payment_provider_transactions
payment_provider_callbacks
payment_provider_attempts
payment_provider_refunds
```

## payment_provider_transactions

Purpose:

```text
Represent an online/mobile-money payment request before it becomes a confirmed UHMS payment.
```

Fields:

```text
id
transaction_uuid
provider_id
provider_code
payment_reference
provider_transaction_id nullable
external_reference nullable
invoice_id nullable
visit_id nullable
patient_id nullable
payer_name nullable
payer_phone nullable
payer_email nullable
amount
currency
payment_method
status
provider_status nullable
initiated_at nullable
authorized_at nullable
paid_at nullable
failed_at nullable
cancelled_at nullable
expired_at nullable
verified_at nullable
uhms_payment_id nullable
accounting_posting_attempt_id nullable
error_code nullable
error_message nullable
metadata_snapshot
created_by nullable
updated_by nullable
timestamps
```

payment_method examples:

```text
mtn_momo
nalo_payment
mobile_money
card
bank_transfer
wallet
```

statuses:

```text
draft
initiated
pending
requires_customer_action
authorized
paid
failed
cancelled
expired
verified
reconciled
refunded
partially_refunded
```

Rules:

```text
Provider transaction does not equal UHMS payment until verified.
Only verified successful provider transaction can create/attach a UHMS payment record.
Do not duplicate UHMS payment when provider sends duplicate callback.
Amount must match expected invoice/payment request amount.
Currency must match expected invoice/payment request currency.
```

## payment_provider_callbacks

Fields:

```text
id
provider_id
provider_code
event_type
provider_transaction_id nullable
payment_reference nullable
signature_valid
processed
processed_at nullable
processing_error nullable
raw_payload
headers_snapshot
ip_address nullable
timestamps
```

Rules:

```text
Store every callback.
Verify signature where provider supports it.
Callback processing must be idempotent.
Do not trust callback alone if provider supports status verification.
```

## payment_provider_attempts

Fields:

```text
id
payment_provider_transaction_id
provider_id
attempt_type
status
request_payload_snapshot
response_payload_snapshot
http_status nullable
error_code nullable
error_message nullable
started_at
completed_at nullable
timestamps
```

attempt_type:

```text
initiate
verify
status_check
callback_process
refund
cancel
```

## payment_provider_refunds

Fields:

```text
id
payment_provider_transaction_id
provider_id
refund_reference
provider_refund_id nullable
amount
currency
status
reason
requested_by
requested_at
processed_at nullable
failed_at nullable
uhms_refund_id nullable
metadata_snapshot
timestamps
```

Refunds can be mostly foundation in Phase 1 unless existing refund workflow is ready.

---

# 8. Provider Interfaces

Create provider interfaces.

## SMS Interface

```php
interface SmsProviderInterface
{
    public function code(): string;

    public function send(SmsSendRequest $request): SmsSendResult;

    public function queryStatus(string $providerMessageId): SmsStatusResult;

    public function handleCallback(array $payload, array $headers = []): SmsCallbackResult;

    public function testConnection(): ProviderTestResult;
}
```

## Payment Interface

```php
interface PaymentProviderInterface
{
    public function code(): string;

    public function initiate(PaymentInitiationRequest $request): PaymentInitiationResult;

    public function verify(string $providerTransactionId, ?string $paymentReference = null): PaymentVerificationResult;

    public function handleCallback(array $payload, array $headers = []): PaymentCallbackResult;

    public function refund(PaymentRefundRequest $request): PaymentRefundResult;

    public function testConnection(): ProviderTestResult;
}
```

Use DTOs/value objects where project style allows.

Do not pass raw request arrays everywhere.

---

# 9. Provider Adapters

Create provider adapters:

```text
NaloSmsProvider
MtnMomoPaymentProvider
NaloPaymentProvider
```

Adapters must:

```text
load credentials from active provider config
build provider-specific payload
send HTTP request
handle provider response
normalize provider response into shared result objects
never expose raw credentials
record request/response safely
support sandbox/live base URLs
handle provider errors gracefully
```

Important:

```text
If exact provider API payloads are not available in the project yet, implement adapter skeletons with clearly isolated TODO sections and configuration placeholders.
Do not fake successful provider responses in production code.
Provide test/fake providers for local testing.
```

Add fake providers:

```text
FakeSmsProvider
FakePaymentProvider
```

Purpose:

```text
local testing
demo environment
automated tests
development without real credentials
```

Fake providers must be disabled in production unless explicitly allowed by config.

---

# 10. SMS Gateway Service

Create:

```text
SmsGatewayService
SmsTemplateService
SmsPhoneNumberNormalizer
SmsDeliveryReportService
```

Responsibilities:

```text
resolve active SMS provider
validate active provider exists
normalize phone numbers
create message record
create recipient records
send SMS via provider
record provider attempts
update statuses
handle delivery callbacks
query delivery status
support manual resend where safe
```

Phone rules:

```text
support Ghana/Togo style phone normalisation where project already has phone rules
do not assume every number is Ghanaian
store original and normalized phone number
validate minimum safe format before sending
```

Sending rules:

```text
SMS send should be queued if queue exists.
If queue is not configured, provide synchronous fallback with timeout.
Do not block clinical workflow because SMS failed.
SMS failures should be visible in logs/workbench.
```

---

# 11. Payment Gateway Service

Create:

```text
PaymentGatewayService
PaymentProviderTransactionService
PaymentVerificationService
PaymentCallbackService
PaymentProviderResolver
```

Responsibilities:

```text
resolve active payment provider
initiate payment request
record pending provider transaction
handle callback
verify provider transaction
create/attach UHMS payment after verified success
allocate payment to invoice using existing payment services
trigger accounting posting through existing payment/accounting flow
record failed/expired/cancelled payments
support manual verify/recheck
prevent duplicate payment creation
```

Payment initiation flow:

```text
User chooses online/mobile-money payment
System creates provider transaction
System calls active provider
Provider returns pending/success/customer-action response
System shows pending/checkout instructions
Provider sends callback or user clicks verify
System verifies transaction
If success and amount/currency/reference match:
    create UHMS payment using existing payment service
    link provider transaction to UHMS payment
    update invoice/payment status through existing workflow
If failed:
    retain failure and allow retry/new transaction
```

Rules:

```text
Never mark invoice paid before verification.
Never create duplicate payment for same provider transaction.
Never trust amount from callback without checking expected amount.
Do not allow callback to allocate to arbitrary invoice without matching internal reference.
Callbacks must be idempotent.
```

---

# 12. Payment Integration Points

Integrate payment gateway into existing billing/payment areas.

Add buttons/actions where safe:

```text
Invoice show: Pay Online / Mobile Money
Billing payment screen: Initiate Payment API
Patient statement: Pay selected invoice if public portal exists
Cashier dashboard: Payment API transactions
```

Do not replace manual payment entry.

Manual payments must continue to work.

When provider payment is verified:

```text
create normal UHMS payment record
use existing receipt/invoice status workflow
use existing accounting/payment posting service
use existing ActivityLogService
```

---

# 13. SMS Integration Points

Initial safe SMS use cases:

```text
manual SMS test/send from SMS Gateway workbench
payment receipt notification
invoice payment request notification
appointment reminder if appointment module is available
queue notification if queue module is available
```

Do not automatically enable all SMS events.

Add settings:

```text
enable payment request SMS
enable receipt SMS
enable appointment reminder SMS
enable queue SMS
enable low-balance/admin alert SMS
```

Default all automatic SMS events to disabled.

Manual test SMS should be available to authorized admins.

---

# 14. Admin UI

Add settings/workbench screens.

## SMS Gateway

Routes under:

```text
admin/integrations/sms
```

Screens:

```text
SMS Providers
Create/Edit SMS Provider
Activate SMS Provider
Provider Credentials
Test SMS Provider
Manual SMS Send
SMS Messages
SMS Message Detail
Delivery Reports
SMS Templates
```

## Payment Gateway

Routes under:

```text
admin/integrations/payments
```

Screens:

```text
Payment Providers
Create/Edit Payment Provider
Activate Payment Provider
Provider Credentials
Test Payment Provider
Payment Transactions
Payment Transaction Detail
Manual Verify/Recheck
Callbacks
Refund Foundation
```

Provider activation screen must show warning:

```text
Activating this provider will deactivate the currently active provider for this module.
```

---

# 15. Callback Routes

Add public callback routes with strict processing.

Suggested routes:

```text
POST /api/integrations/sms/{providerCode}/callback
POST /api/integrations/payments/{providerCode}/callback
```

Rules:

```text
Do not require normal browser auth for provider callbacks.
Do require provider verification/signature/secret where available.
Store raw callback.
Process idempotently.
Return provider-friendly response.
Never expose internal stack traces.
```

Also add internal admin route to view callbacks.

---

# 16. Security

Security requirements:

```text
encrypt credentials
mask credentials in UI
never log secrets
verify webhook signatures/secrets where provider supports it
rate-limit callback routes where possible
validate provider code
validate expected amount
validate currency
validate internal reference
reject unknown transaction references
protect admin routes with permissions
```

Add config:

```text
INTEGRATIONS_HTTP_TIMEOUT
INTEGRATIONS_CALLBACK_IP_ALLOWLIST optional
INTEGRATIONS_ALLOW_FAKE_PROVIDERS
```

Do not hardcode production credentials in code or seeders.

---

# 17. Permissions

Add permissions.

## SMS

```text
integrations.sms.view
integrations.sms.providers.manage
integrations.sms.providers.activate
integrations.sms.credentials.manage
integrations.sms.test
integrations.sms.send
integrations.sms.templates.manage
integrations.sms.reports.view
```

## Payment

```text
integrations.payments.view
integrations.payments.providers.manage
integrations.payments.providers.activate
integrations.payments.credentials.manage
integrations.payments.test
integrations.payments.transactions.view
integrations.payments.transactions.initiate
integrations.payments.transactions.verify
integrations.payments.callbacks.view
integrations.payments.refunds.manage
```

Suggested role defaults:

```text
Administrator / Super Admin:
- all integration permissions

Finance Manager:
- view payment providers
- view payment transactions
- initiate/verify payments
- view callbacks

Accountant / Cashier:
- initiate payment transactions
- verify payment transactions
- view own/related payment transactions

IT Admin if role exists:
- manage providers
- credentials
- activate providers
- test providers

Receptionist:
- send approved SMS templates if desired
- view SMS delivery status for patient communication only
```

Do not grant provider credential management to ordinary billing users.

---

# 18. Audit Logging

Use `ActivityLogService`.

Audit events:

```text
SMS_PROVIDER_CREATED
SMS_PROVIDER_UPDATED
SMS_PROVIDER_ACTIVATED
SMS_PROVIDER_DEACTIVATED
SMS_PROVIDER_CREDENTIAL_UPDATED
SMS_PROVIDER_TESTED
SMS_MESSAGE_CREATED
SMS_MESSAGE_SENT
SMS_MESSAGE_FAILED
SMS_DELIVERY_REPORT_RECEIVED
SMS_TEMPLATE_CREATED
SMS_TEMPLATE_UPDATED

PAYMENT_PROVIDER_CREATED
PAYMENT_PROVIDER_UPDATED
PAYMENT_PROVIDER_ACTIVATED
PAYMENT_PROVIDER_DEACTIVATED
PAYMENT_PROVIDER_CREDENTIAL_UPDATED
PAYMENT_PROVIDER_TESTED
PAYMENT_TRANSACTION_INITIATED
PAYMENT_TRANSACTION_VERIFIED
PAYMENT_TRANSACTION_FAILED
PAYMENT_TRANSACTION_CALLBACK_RECEIVED
PAYMENT_TRANSACTION_PAYMENT_CREATED
PAYMENT_TRANSACTION_DUPLICATE_CALLBACK_IGNORED
PAYMENT_REFUND_REQUESTED
PAYMENT_REFUND_COMPLETED
PAYMENT_REFUND_FAILED
```

Run:

```bash
php artisan logs:audit --json
```

Fix new missing/needs-review audit gaps.

---

# 19. Localisation

All labels must be localised EN/FR.

Create or extend:

```text
lang/en/integrations.php
lang/fr/integrations.php
lang/en/sms.php
lang/fr/sms.php
lang/en/payments.php
lang/fr/payments.php
```

Required keys include:

```text
integrations
provider
providers
active_provider
activate_provider
deactivate_provider
credentials
masked_credentials
sandbox
live
test_connection
callback_url
webhook_secret
request_log
response_log

sms_gateway
sms_provider
sms_providers
sms_message
sms_messages
sms_template
sms_templates
sender_id
delivery_report
manual_sms
send_test_sms
message_body
recipient
recipients
delivered
undelivered

payment_gateway
payment_provider
payment_providers
payment_transaction
payment_transactions
initiate_payment
verify_payment
manual_verify
provider_reference
provider_transaction_id
payment_callback
payment_callbacks
callback_received
callback_processed
payment_pending
payment_verified
payment_failed
payment_cancelled
payment_expired
duplicate_callback_ignored
```

Maintain EN/FR parity.

Run:

```bash
php scripts/localisation-audit.php
```

Active runtime candidates must remain:

```text
0
```

---

# 20. Navigation

Add navigation:

```text
Administration / Integrations > SMS Gateway
Administration / Integrations > Payment Gateway
Billing & Collections > Payment API Transactions
```

Payment provider transaction pages may also appear under Billing & Collections for cashier/finance users.

Provider configuration pages should remain admin/IT controlled.

---

# 21. Focused Tests To Add

Add focused tests but do not run the wide full suite.

Required coverage:

```text
only one SMS provider can be active at a time
only one payment provider can be active at a time
provider credentials are encrypted and masked
unauthorized user cannot manage provider credentials
SMS provider can be configured
Nalo SMS adapter normalizes request/response through interface
fake SMS provider sends test message
SMS send creates message and recipient records
SMS failure is retained and visible
SMS callback/delivery report is idempotent

payment provider can be configured
MTN MoMo adapter normalizes request/response through interface
Nalo payment adapter normalizes request/response through interface
fake payment provider initiates payment
payment initiation creates provider transaction
payment callback is stored
duplicate payment callback does not duplicate UHMS payment
payment is not marked paid until verified
verified payment creates UHMS payment through existing payment service
amount mismatch blocks payment creation
unknown reference callback is retained but not posted
payment manual verify updates transaction safely
module middleware blocks disabled SMS gateway routes
module middleware blocks disabled payment gateway routes
audit events are recorded
localisation keys exist
```

Do not run:

```bash
php artisan test
```

during this phase unless explicitly instructed.

The wide full-suite test will be run after all current implementation phases are complete.

---

# 22. Minimal Verification Commands

Run only necessary safety checks:

```bash
php artisan migrate --force
php artisan route:list
php artisan view:cache
php artisan view:clear
php scripts/localisation-audit.php
php artisan logs:audit --json
php artisan permissions:audit --strict
git diff --check
```

Also run PHP lint on changed files if practical:

```bash
find app database routes lang resources/views -name "*.php" -print0 | xargs -0 -n1 php -l
```

Do not run the full application test suite yet.

---

# 23. Documentation

Create:

```text
docs/INTEGRATIONS_PHASE_1_SMS_AND_PAYMENT_GATEWAYS_REPORT.md
```

Include:

```text
summary
module changes
database changes
models added
services added
provider interfaces
provider adapters
initial SMS providers
initial payment providers
active-provider rule
credential encryption
SMS workflow
payment initiation workflow
callback workflow
verification workflow
billing/payment integration
security controls
permissions
routes/controllers/views
audit logging
localisation audit result
minimal verification commands run
tests added but not fully executed
known limitations
next recommended phase
```

---

# 24. Acceptance Criteria

Phase 1 is complete only when:

```text
sms_gateway module exists
payment_gateway module exists
Nalo SMS provider can be configured
MTN MoMo payment provider can be configured
Nalo payment provider can be configured
only one SMS provider can be active at a time
only one payment provider can be active at a time
credentials are encrypted and masked
SMS messages can be sent through active provider or fake provider
SMS delivery callbacks can be stored idempotently
payment transactions can be initiated through active provider or fake provider
payment callbacks can be stored idempotently
payments are verified before invoice/payment status changes
duplicate callbacks do not duplicate UHMS payments
verified provider payment creates normal UHMS payment through existing service
manual/cash payments continue to work
provider admin routes are permission protected
callback routes are safe and idempotent
ActivityLogService is used
EN/FR localisation parity is maintained
active runtime candidates remain 0
route list works
view cache compiles
logs:audit has no new missing/needs-review gaps
permissions audit is clean
documentation report is created
full test suite is intentionally deferred
```

Proceed with External Integrations Phase 1 now.
