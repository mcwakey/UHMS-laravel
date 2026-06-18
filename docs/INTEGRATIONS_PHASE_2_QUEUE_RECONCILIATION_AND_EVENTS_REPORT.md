# UHMS External Integrations — Phase 2: Queue Dispatch, Reconciliation, Automated Events & Hardening

Date: 2026-06-18

## Summary

Phase 2 turns the Phase 1 SMS & Payment Gateway foundation into production-grade
operational workflows, without breaking the provider architecture
(**controllers → services → provider interfaces → provider adapters**) and without
putting provider logic into billing/invoice/appointment/cashier/accounting code.

Delivered: queue-backed SMS dispatch with recipient-safe retry, scheduled/manual
SMS delivery-status reconciliation, validated SMS template placeholders, an opt-in
automatic-SMS-event dispatcher, a payment reconciliation dashboard, manual recheck +
a stale-pending recheck command, webhook signature/secret hardening, a refund-bridge
foundation (over-refund blocked, unsupported retained), payment request links, and a
provider health screen.

## Database changes (additive only — `2026_06_19_000001_extend_integrations_for_phase_2`)

- `sms_messages`: `queued_at`, `retry_count`, `max_retries`, `last_retry_at`, `next_retry_at`, `provider_status_checked_at`.
- `sms_message_recipients`: `retry_count`, `last_retry_at`, `next_retry_at`, `provider_status_checked_at`.
- `integration_providers`: `require_signature`, `allow_unsigned_sandbox_callbacks`, `signature_header`, `last_success_at`, `last_failure_at`, `last_error_message`.
- New tables: `sms_notification_events`, `payment_request_links` (MariaDB-10.1 safe — longText for JSON, short named indexes/FKs).

## Models added / changed

Added `SmsNotificationEvent`, `PaymentRequestLink`. Extended `SmsMessage`
(+`STATUS_EXPIRED`, retry/queue fields), `SmsMessageRecipient` (retry fields),
`IntegrationProvider` (signature + health fields).

## Services added

`SmsStatusReconciliationService`, `SmsTemplateRenderer`, `SmsEventSettingsService`,
`SmsNotificationEventService`, `PaymentRequestLinkService`,
`PaymentReconciliationService`, `PaymentRefundBridgeService`, `ProviderHealthService`.
`SmsGatewayService` extended with `queueOrSend`/`deliverNow`/`retry`/`deliverRecipient`;
provider adapter base extended with `verifySignature`.

## Jobs added

`SendSmsMessageJob`, `SendSmsRecipientJob`, `RetryFailedSmsMessageJob`,
`ReconcileSmsDeliveryStatusJob` (all `ShouldQueue`, ID-based payloads).

## Commands added

- `integrations:sms-reconcile-status` (`--provider --message-id --recipient-id --from --to --limit --dry-run`)
- `integrations:payments-recheck-pending` (`--provider --from --to --limit --dry-run --mark-expired`)
- `integrations:sms-send-appointment-reminders` (`--date --from --to --dry-run`)

## Provider hardening

Adapters now report `signatureValid` from `verifySignature()` (HMAC-SHA256 of the
payload against the `callback_secret` credential, keyed by the provider's
`signature_header`; honours `require_signature` and `allow_unsigned_sandbox_callbacks`).
Real adapters keep their request builders / response + error normalizers /
sandbox-live URL selector / TODO markers and still NEVER fake success.

## SMS queue workflow

`SmsGatewayService::send()` creates the message (`queued`) + recipients, sets
`queued_at`, audits `SMS_MESSAGE_QUEUED`, then `queueOrSend()`: dispatches
`SendSmsMessageJob` when a real queue is configured, else runs a synchronous
fallback. `retry()` is recipient-safe — it re-delivers only failed/queued recipients,
never the ones already sent/delivered, and honours `max_retries`. Message status is
always derived from recipient statuses; recipient statuses stay visible.

## SMS status reconciliation

`SmsStatusReconciliationService` finds `sent` recipients with no final report,
queries the provider only where `supports_status_check` is true (otherwise marks
`unsupported` and never fabricates a delivered status), updates the delivery report
idempotently via `SmsDeliveryReportService`, continues on individual failure, and
audits `SMS_STATUS_RECONCILIATION_RUN`.

## SMS template placeholders

`SmsTemplateRenderer` resolves an allow-list of safe, non-clinical placeholders in
the service layer (never Blade). Unknown placeholders are reported (and rejected in
strict mode). A preview screen renders sample data + flags unknown placeholders
(`SMS_TEMPLATE_PREVIEWED`).

## Automatic SMS events

`SmsNotificationEventService` is the single gate: nothing sends unless the SMS module
is enabled, the per-event toggle is on, an active provider exists, the payer has a
valid phone, and no prior `sent` event exists for the same source/event (dedup).
Every decision is recorded in `sms_notification_events` and audited
(`SMS_NOTIFICATION_EVENT_CREATED/SENT/SKIPPED`). Toggles live in the Setting store
(group `integrations_sms`) and **default to disabled**. A guarded `PaymentRecorded`
listener sends receipt SMS only when its toggle is on (manual/cash payments
unaffected, no event noise when off).

## Payment request links

`payment_request_links` (public handle = UUID, no internal IDs exposed). A link never
marks an invoice paid; `PaymentRequestLinkService::initiate()` re-validates link
state + live invoice balance before seeding provider initiation. Expired/used links
cannot initiate. Public patient-portal route is **deferred** (foundation only).

## Payment reconciliation dashboard

`admin/integrations/payments/reconciliation` — cards (pending, stale pending,
verified-not-linked, paid+linked, failed, amount mismatches, unknown callbacks,
callbacks awaiting verification, provider errors), filters, and actions
(manual recheck, mark expired, cancel). Recheck always goes through
`PaymentVerificationService`; no UHMS payment is created without verification and no
arbitrary invoice allocation is allowed.

## Manual recheck + stale pending command

Manual recheck (`PAYMENT_TRANSACTION_RECHECK_REQUESTED/COMPLETED`) and
`integrations:payments-recheck-pending` both verify via `PaymentVerificationService`
(idempotent — duplicate rechecks never duplicate a UHMS payment; amount/currency
mismatch stays blocked; unknown references stay retained). `--mark-expired` only
expires stale (older than `stale_pending_minutes`) still-pending transactions.

## Webhook signature behaviour

`PaymentCallbackService` blocks payment creation when a provider `require_signature`
is set and the signature is invalid (`processing_error = signature_invalid`); the
callback is still stored, the transaction left for provider-verified recheck. When a
provider supports status checks the callback is re-verified with the provider
regardless; otherwise the (signature-checked) callback drives verification, still
amount/currency-validated.

## Refund bridge behaviour

`PaymentRefundBridgeService::prepare()` only refunds a verified, UHMS-linked payment,
blocks over-refund (sum of refunds can't exceed the verified amount), and — when the
provider doesn't support refunds — retains the request as `failed` with an
`unsupported` flag + manual-refund instruction (`PAYMENT_REFUND_PROVIDER_UNSUPPORTED`).
It does not bypass the UHMS credit-note/refund approval workflow (caller passes an
approved `uhms_refund_id`).

## Billing integration

Additive only: a "Send payment request SMS" action (creates a link + dispatches the
opt-in event), payment-transaction initiation/status/verify reused from Phase 1, and
a "Payment Request Links" + "Payment API Transactions" presence under Billing &
Collections. Manual/cash entry, invoice totals and accounting posting are unchanged;
verified provider payments still create a normal UHMS payment via `PaymentService`.

## Security controls

Signature/secret verification with `require_signature` + `allow_unsigned_sandbox_callbacks`;
credentials still encrypted/masked/never logged; callback replay handled by store-then-
idempotent-process + the `uhms_payment_id` guard; reconciliation/links/refund routes
permission- and module-gated; provider test/health never expose secrets. New config:
`INTEGRATIONS_STALE_PENDING_MINUTES` (via `stale_pending_minutes`), payment link expiry
days, `sms_default_bodies`.

## Permissions

Added `integrations.sms.{queue.view,queue.retry,events.manage,status.reconcile}` and
`integrations.payments.{reconciliation.view,reconciliation.verify,reconciliation.expire,
refunds.prepare,refunds.execute,request_links.manage}`. Defaults: Super Admin/Admin all;
Finance Manager — reconciliation + refund-bridge + request links; Accountant —
reconciliation view/verify + request links; Cashier — reconciliation view + request
links; Receptionist — SMS queue view (+ existing SMS send). Provider/credential
management stays admin/IT only. Provider Health reuses `reconciliation.view`.

## Routes / controllers / views

New controllers: `SmsQueueController`, `SmsEventController`,
`PaymentReconciliationController`, `PaymentRequestLinkController`,
`ProviderHealthController` (+ preview on `SmsTemplateController`, bridge on
`PaymentRefundController`). 39 Phase 2 admin routes under
`admin/integrations/{sms,payments,health}`. New Blade views: SMS queue, SMS events,
template preview, payment reconciliation dashboard, payment request links, provider
health. Sidebar gains Payment Reconciliation, SMS Queue, SMS Events, Provider Health,
Payment Request Links.

## Audit logging

All Phase 2 actions audited via `ActivityLogService` under `LogModule::INTEGRATIONS`
(`SMS_MESSAGE_QUEUED/RETRY_REQUESTED`, `SMS_STATUS_RECONCILIATION_RUN`,
`SMS_NOTIFICATION_EVENT_CREATED/SENT/SKIPPED`, `SMS_TEMPLATE_PREVIEWED`,
`PAYMENT_RECONCILIATION_VIEWED`, `PAYMENT_TRANSACTION_RECHECK_REQUESTED/COMPLETED`,
`PAYMENT_TRANSACTION_MARKED_EXPIRED`, `PAYMENT_REQUEST_LINK_CREATED/USED`,
`PAYMENT_REQUEST_SMS_SENT`, `PAYMENT_REFUND_BRIDGE_PREPARED`,
`PAYMENT_REFUND_PROVIDER_REQUESTED/UNSUPPORTED`, `PAYMENT_PROVIDER_HEALTH_CHECKED`).

## Localisation audit result

Extended `integrations.php`, `sms.php`, `payments.php`, `menu.php`, `common.php`
(EN+FR). **Active runtime candidates: 0**; `localisation-parity-check`: **parity OK**.

## Minimal verification commands run

- `php artisan migrate --force` — Phase 2 migration applied (MariaDB).
- `php artisan route:list` — 39 Phase 2 routes registered.
- `php artisan view:cache` / `view:clear` — all Blade compiles.
- `php scripts/localisation-audit.php` — active runtime candidates = 0.
- `php scripts/localisation-parity-check.php` — EN/FR parity OK.
- `php artisan logs:audit` — MISSING_LOG = 0, NEEDS_REVIEW = 2 (unchanged baseline).
- `php artisan permissions:audit --strict` — clean (exit 0).
- `git diff --check` — clean. PHP `-l` on all changed files — clean.

## Tests added (focused; full suite intentionally deferred)

`tests/Feature/Integrations/IntegrationsPhase2Test.php` — **19 tests, all passing**:
SMS queue dispatch, queued job updates statuses, recipient-safe retry, idempotent
status reconciliation, placeholder validation, automatic-event skip-when-disabled,
payment-request SMS when enabled, reconciliation dashboard permission gate, manual
recheck via route, stale-pending command, duplicate recheck does not duplicate
payment, amount mismatch blocked, request link create, expired link cannot initiate,
refund bridge over-refund blocked, unsupported refund retained, webhook signature
failure blocks payment, module middleware blocks disabled routes, EN/FR keys exist.
Phase 1 suite (21 tests) re-run green — no regression. The wide full suite remains
**intentionally deferred**.

## Known limitations

- Live provider request/response/signature formats for Nalo SMS, MTN MoMo and Nalo
  Payment still carry `TODO`s pending sandbox-credential confirmation; adapters fail
  safe and never fake success.
- `verifySignature()` uses a generic HMAC-SHA256 scheme — confirm each provider's
  exact signing per their docs before go-live.
- Payment request links are backend-only; no public patient-portal route is exposed
  (deferred).
- Refund execution depends on provider support + the UHMS refund/credit-note approval
  workflow; MoMo disbursement remains out of scope until credentials are confirmed.
- Queue/reminder scheduling is available via commands; wiring them into the app
  scheduler is an operational step.

## Next recommended phase

Confirm live provider payloads + signatures against sandbox credentials and flip
real providers on; add the scheduler entries for reconciliation/recheck/reminders;
build the public patient payment-link portal page; connect the refund bridge to the
full credit-note/refund posting; add queue-notification SMS hooks into the live queue
module.
