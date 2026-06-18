You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS External Integrations Phase 2 — Queue Dispatch, Live Provider Hardening, Payment Reconciliation & Automated SMS Events

## Goal

Extend the Phase 1 SMS and Payment Gateway foundation into production-ready operational workflows.

Phase 1 delivered:

```text
sms_gateway module
payment_gateway module
provider-based architecture
Nalo SMS provider
MTN MoMo payment provider
Nalo payment provider
fake providers
encrypted credentials
single-active-provider rule
callback storage
idempotent payment verification
UHMS payment creation only after verified provider success
```

Phase 2 must now add:

```text
queue-backed SMS sending
scheduled SMS delivery-status reconciliation
live-provider payload hardening
webhook signature/secret verification hardening
payment reconciliation dashboard
manual provider recheck tools
refund / credit-note workflow foundation
patient invoice payment link foundation
automatic SMS event toggles
SMS templates and placeholders
payment request SMS
receipt SMS
appointment reminder SMS
queue notification SMS
provider health monitoring
documentation
```

Do not break the Phase 1 provider architecture.

Do not hardcode provider logic into billing, invoices, appointments, patients, cashier, or accounting controllers.

---

# 1. Required Context

Read:

```text
docs/INTEGRATIONS_PHASE_1_SMS_AND_PAYMENT_GATEWAYS_REPORT.md
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

Current integration baseline:

```text
Phase 1 complete.
SMS and Payment Gateway modules exist.
Nalo SMS, MTN MoMo, Nalo Payment and fake providers exist.
Credentials are encrypted and masked.
Callbacks are stored idempotently.
Verified provider payments create normal UHMS payments through existing PaymentService.
Manual/cash payments remain untouched.
Full suite is intentionally deferred.
```

Important testing instruction:

```text
Do not run the wide full application test suite after this phase.
The wide full-suite test is deferred until all current implementation phases are complete.
For this phase, run only necessary safety checks: migrations, route list, view cache, localisation audit, permission audit, logs:audit, PHP lint where practical, focused integration checks where needed, and git diff check.
```

---

# 2. Scope of This Phase

Implement:

```text
SMS queue dispatch
SMS retry and status reconciliation
SMS template placeholders
automatic SMS event dispatcher
provider health checks
provider status test logs
provider webhook signature/secret validation hardening
payment reconciliation dashboard
payment manual verify/recheck
payment stale pending detection
payment failed/cancelled/expired handling
payment request link foundation
invoice payment request SMS
payment receipt SMS
appointment reminder SMS where appointment module exists
queue notification SMS where queue module exists
refund / credit-note workflow bridge foundation
provider callback replay protection
permissions
audit logging
localisation
documentation
```

Do not implement yet:

```text
direct provider contract-specific go-live without real sandbox credential confirmation
full public patient portal if it does not already exist
full card-payment PCI flow
MoMo disbursement if provider credentials are not confirmed
bulk marketing SMS
external debt collection SMS automation
automatic SMS sending without explicit settings
```

---

# 3. Core Rules

Rules:

```text
Automatic SMS events must remain disabled by default.
No clinical workflow should fail because SMS failed.
No invoice should be marked paid before provider verification.
No duplicate callback should duplicate a UHMS payment.
No refund should bypass credit-note/refund approval workflow.
No provider credential should be logged or displayed.
No provider adapter should fake success in production.
```

Architecture must remain:

```text
controllers → services → provider interfaces → provider adapters
```

Do not put provider-specific logic in controllers.

Do not put business workflow logic in provider adapters.

---

# 4. Queue-backed SMS Sending

Add queue-backed SMS dispatch.

Create jobs:

```text
SendSmsMessageJob
SendSmsRecipientJob
ReconcileSmsDeliveryStatusJob
RetryFailedSmsMessageJob
```

Rules:

```text
If queue is configured, SMS should dispatch through queue.
If queue is not configured, allow controlled synchronous fallback.
Failed SMS attempts must be retained.
Retry must not duplicate successfully sent recipients.
Message-level status must derive from recipient statuses.
Recipient-level statuses must remain visible.
```

SMS statuses should support:

```text
queued
sending
sent
partially_sent
failed
delivered
undelivered
expired
cancelled
```

Add fields if missing:

```text
queued_at
retry_count
last_retry_at
next_retry_at
max_retries
provider_status_checked_at
```

Use additive migrations only.

---

# 5. SMS Delivery Status Reconciliation

Implement scheduled/manual reconciliation.

Create command:

```bash
php artisan integrations:sms-reconcile-status
```

Options:

```text
--provider=
--message-id=
--recipient-id=
--from=
--to=
--limit=
--dry-run
```

Behavior:

```text
find sent recipients without final delivery status
query provider status where supported
update delivery reports
mark delivered/undelivered/expired
retain raw provider result
continue on individual failure
audit summary
```

If provider does not support status query:

```text
show controlled unsupported message
do not mark as delivered
keep status as sent/pending
```

---

# 6. SMS Templates and Placeholders

Improve SMS templates.

Support placeholders:

```text
{{patient_name}}
{{invoice_number}}
{{amount}}
{{currency}}
{{payment_link}}
{{appointment_date}}
{{appointment_time}}
{{queue_number}}
{{hospital_name}}
{{receipt_number}}
```

Rules:

```text
Placeholders must be resolved by service, not Blade.
Unknown placeholders should fail preview or show clear warning.
Templates must support EN/FR.
Clinical-sensitive placeholders must be restricted.
Default templates should be safe and minimal.
```

Create service:

```text
SmsTemplateRenderer
```

Add preview screen:

```text
template preview with sample data
placeholder validation
```

---

# 7. Automatic SMS Event Dispatcher

Create:

```text
SmsNotificationEventService
```

Supported initial events:

```text
invoice_payment_request
payment_receipt
appointment_reminder
queue_notification
manual_custom
```

Settings:

```text
enable_payment_request_sms
enable_receipt_sms
enable_appointment_reminder_sms
enable_queue_sms
```

Default:

```text
all automatic SMS events disabled
```

Rules:

```text
No automatic SMS is sent unless the event toggle is enabled.
Do not send SMS if patient/payer has no valid phone number.
Do not send duplicate event SMS for same source/event/template unless explicitly resent.
Do not expose sensitive diagnosis/lab details in SMS.
```

Create table if needed:

```text
sms_notification_events
```

Fields:

```text
id
event_type
source_type
source_id
template_id nullable
sms_message_id nullable
recipient_phone
status
triggered_by nullable
triggered_at
sent_at nullable
failed_at nullable
error_message nullable
metadata_snapshot
timestamps
```

Use this to prevent duplicate automatic SMS.

---

# 8. Payment Request Link Foundation

Implement payment request link foundation.

Purpose:

```text
Create secure payment request references that can be sent by SMS or displayed on invoice pages.
```

Create table if needed:

```text
payment_request_links
```

Fields:

```text
id
link_uuid
invoice_id nullable
visit_id nullable
patient_id nullable
payer_type nullable
payer_id nullable
amount
currency
status
expires_at nullable
used_at nullable
payment_provider_transaction_id nullable
created_by nullable
metadata_snapshot
timestamps
```

Statuses:

```text
active
used
expired
cancelled
```

Rules:

```text
A payment link does not mark invoice paid.
A payment link starts provider payment initiation.
Amount and invoice reference must be validated again before provider initiation.
Expired link cannot initiate payment.
Used link cannot be reused unless explicitly configured.
Do not expose internal numeric IDs in public link.
```

If public patient portal is not available:

```text
create backend-generated link/reference foundation
do not expose a public route unless the app already supports safe public pages
document patient portal route as deferred
```

---

# 9. Payment Reconciliation Dashboard

Add dashboard for provider payment operations.

Route area:

```text
admin/integrations/payments/reconciliation
```

Dashboard cards:

```text
pending transactions
stale pending transactions
verified but not linked
paid and linked to UHMS payment
failed transactions
amount mismatches
unknown callbacks
duplicate callbacks ignored
callbacks awaiting verification
provider errors
```

Filters:

```text
provider
status
date range
invoice
patient
payer phone
amount
reference
callback status
UHMS payment linked/unlinked
```

Actions:

```text
manual verify/recheck
view callback payload
view provider attempts
link to invoice
link to UHMS payment
mark expired where safe
cancel pending transaction where safe
```

Rules:

```text
Manual verify must use PaymentVerificationService.
Do not create UHMS payment without verification.
Do not allow arbitrary invoice allocation.
Unknown callbacks remain retained but not posted.
```

---

# 10. Stale Pending Payment Recheck

Create command:

```bash
php artisan integrations:payments-recheck-pending
```

Options:

```text
--provider=
--from=
--to=
--limit=
--dry-run
--mark-expired
```

Behavior:

```text
find pending/stale provider transactions
verify with provider where possible
update status
create UHMS payment only when verified success and amount/currency/reference match
mark expired only when configured/confirmed
audit summary
continue on individual failure
```

---

# 11. Provider Webhook Verification Hardening

Review and strengthen:

```text
Nalo SMS callback verification
MTN MoMo callback verification
Nalo Payment callback verification
```

Rules:

```text
If provider supports signature header, verify it.
If provider supports callback secret, verify it.
If provider supports status recheck, recheck before payment creation.
If exact live signature format is unknown, isolate TODO inside adapter and fail safely in live mode unless configured to accept sandbox callbacks.
Do not allow unsigned live payment callback to create UHMS payment unless provider verification is performed.
```

Add provider settings:

```text
require_signature
allow_unsigned_sandbox_callbacks
callback_secret
signature_header
```

---

# 12. Live Provider Payload Hardening

Confirm adapter payload structure areas.

For each real provider adapter:

```text
NaloSmsProvider
MtnMomoPaymentProvider
NaloPaymentProvider
```

Add:

```text
clear request builder method
clear response normalizer
clear error normalizer
sandbox/live URL selector
provider-specific required credential list
provider capability flags
safe TODO comments where live credentials are required
```

Provider test must show:

```text
missing credential errors
network error
authentication failure
successful sandbox/fake test
```

Do not fake success for real providers.

---

# 13. Refund / Credit-note Workflow Bridge Foundation

Build foundation but do not force full refund automation if provider support is not confirmed.

Add service:

```text
PaymentRefundBridgeService
```

Responsibilities:

```text
prepare provider refund request from approved UHMS refund or credit-note workflow
validate original provider transaction
validate refundable amount
create provider refund record
call provider refund if supported
record provider response
link refund to UHMS refund/credit note where available
```

Rules:

```text
Refund cannot exceed verified provider payment amount minus previous refunds.
Refund must require permission.
Refund must not bypass UHMS credit-note/refund approval workflow.
If provider refund is unsupported, mark as unsupported and retain manual-refund instruction.
```

---

# 14. Billing Integration

Enhance existing billing/payment screens safely.

Add:

```text
Initiate mobile money / online payment
View provider transaction status
Manual verify/recheck
Send payment request SMS
View payment callback history
```

Do not remove manual payment entry.

Do not change invoice total logic.

Do not change existing accounting posting flow.

Verified provider payment must still create a normal UHMS payment through existing `PaymentService`.

---

# 15. SMS Integration Points

Add optional event hooks:

```text
invoice payment request SMS
payment receipt SMS
appointment reminder SMS
queue notification SMS
```

Rules:

```text
Event hooks must check module enabled.
Event hooks must check active provider exists.
Event hooks must check automatic event setting enabled.
Event hooks must avoid duplicate event messages.
Failure must be logged but not block source workflow.
```

Appointment reminder should be foundation only if scheduling command is not already present.

Create command if safe:

```bash
php artisan integrations:sms-send-appointment-reminders
```

Options:

```text
--date=
--from=
--to=
--dry-run
```

---

# 16. Provider Health Monitoring

Add provider health screen.

Track:

```text
last_tested_at
last_test_status
last_success_at
last_failure_at
last_error_message
pending_transactions
failed_transactions
undelivered_sms_count
callback_failures
```

Provider test should not expose secrets.

---

# 17. Permissions

Reuse Phase 1 permissions where possible.

Add only if missing:

```text
integrations.sms.queue.view
integrations.sms.queue.retry
integrations.sms.events.manage
integrations.sms.status.reconcile

integrations.payments.reconciliation.view
integrations.payments.reconciliation.verify
integrations.payments.reconciliation.expire
integrations.payments.refunds.prepare
integrations.payments.refunds.execute
integrations.payments.request_links.manage
```

Suggested defaults:

```text
Administrator / Super Admin:
- all

IT Admin:
- provider health
- queues
- status reconciliation
- provider testing

Finance Manager:
- payment reconciliation
- manual verify
- refunds bridge
- payment request links

Cashier / Accountant:
- initiate payments
- verify related payment transactions
- send payment request SMS where allowed

Receptionist:
- appointment reminder / queue SMS where allowed
```

Do not grant credential management to ordinary billing users.

---

# 18. Audit Logging

Use `ActivityLogService`.

Audit:

```text
SMS_MESSAGE_QUEUED
SMS_MESSAGE_RETRY_REQUESTED
SMS_STATUS_RECONCILIATION_RUN
SMS_NOTIFICATION_EVENT_CREATED
SMS_NOTIFICATION_EVENT_SKIPPED
SMS_NOTIFICATION_EVENT_SENT
SMS_TEMPLATE_PREVIEWED

PAYMENT_RECONCILIATION_VIEWED
PAYMENT_TRANSACTION_RECHECK_REQUESTED
PAYMENT_TRANSACTION_RECHECK_COMPLETED
PAYMENT_TRANSACTION_MARKED_EXPIRED
PAYMENT_REQUEST_LINK_CREATED
PAYMENT_REQUEST_LINK_USED
PAYMENT_REQUEST_SMS_SENT
PAYMENT_REFUND_BRIDGE_PREPARED
PAYMENT_REFUND_PROVIDER_REQUESTED
PAYMENT_REFUND_PROVIDER_UNSUPPORTED
PAYMENT_PROVIDER_HEALTH_CHECKED
```

Run:

```bash
php artisan logs:audit --json
```

Fix new missing/needs-review audit gaps.

---

# 19. Localisation

All labels must be localised EN/FR.

Extend:

```text
lang/en/integrations.php
lang/fr/integrations.php
lang/en/sms.php
lang/fr/sms.php
lang/en/payments.php
lang/fr/payments.php
lang/en/menu.php
lang/fr/menu.php
```

Required keys:

```text
sms_queue
retry_sms
sms_status_reconciliation
sms_notification_event
sms_notification_events
template_placeholders
preview_template
payment_request_sms
receipt_sms
appointment_reminder_sms
queue_notification_sms
automatic_sms_events
enable_payment_request_sms
enable_receipt_sms
enable_appointment_reminder_sms
enable_queue_sms

payment_reconciliation
stale_pending_transactions
manual_recheck
recheck_pending_payments
verified_not_linked
amount_mismatch
unknown_callback
duplicate_callback
payment_request_link
payment_request_links
create_payment_link
expire_payment_link
provider_health
provider_health_check
refund_bridge
provider_refund
refund_unsupported
```

Maintain EN/FR parity.

Run:

```bash
php scripts/localisation-audit.php
php scripts/localisation-parity-check.php
```

Active runtime candidates must remain:

```text
0
```

---

# 20. Navigation

Add or extend navigation:

```text
Administration / Integrations > Provider Health
Administration / Integrations > SMS Queue
Administration / Integrations > SMS Events
Administration / Integrations > Payment Reconciliation
Billing & Collections > Payment API Transactions
Billing & Collections > Payment Request Links
```

Route access must be permission and module protected.

---

# 21. Focused Tests To Add

Add focused tests but do not run the wide full suite.

Required coverage:

```text
SMS dispatch can be queued
queued SMS updates recipient statuses
SMS retry does not duplicate delivered recipients
SMS status reconciliation updates delivery report idempotently
SMS template placeholder validation works
automatic SMS event is skipped when toggle disabled
payment request SMS creates notification event when enabled

payment reconciliation dashboard is permission protected
pending transaction can be manually rechecked
stale pending command verifies transactions safely
verified recheck creates UHMS payment once
duplicate recheck does not duplicate UHMS payment
amount mismatch remains blocked
unknown callback remains retained
payment request link can be created
expired payment request link cannot initiate payment
refund bridge blocks refund above original payment
unsupported provider refund is retained visibly
webhook signature failure does not create payment
module middleware blocks disabled routes
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
php scripts/localisation-parity-check.php
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
docs/INTEGRATIONS_PHASE_2_QUEUE_RECONCILIATION_AND_EVENTS_REPORT.md
```

Include:

```text
summary
database changes
models added/changed
services added
jobs added
commands added
provider hardening
SMS queue workflow
SMS status reconciliation
SMS template placeholders
automatic SMS events
payment request links
payment reconciliation dashboard
manual recheck workflow
stale pending recheck command
webhook signature behavior
refund bridge behavior
billing integration
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

Phase 2 is complete only when:

```text
SMS dispatch can run through queue
SMS retry is recipient-safe
SMS delivery status reconciliation exists
SMS templates support validated placeholders
automatic SMS event toggles exist and default disabled
payment request SMS can be generated when enabled
payment request link foundation exists
payment reconciliation dashboard exists
manual payment recheck works through verification service
stale pending payment recheck command exists
duplicate rechecks/callbacks do not duplicate UHMS payment
amount mismatch remains blocked
unknown callbacks remain retained but not posted
webhook signature/secret failure blocks payment creation
provider health screen exists
refund bridge prevents over-refund and unsupported refunds are visible
manual/cash payment workflows remain unchanged
permissions are enforced
module middleware protects routes
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

Proceed with External Integrations Phase 2 now.
