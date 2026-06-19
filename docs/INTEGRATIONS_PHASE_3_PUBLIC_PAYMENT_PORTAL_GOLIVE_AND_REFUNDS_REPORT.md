# UHMS External Integrations — Phase 3: Public Payment Portal, Go-Live, Scheduler & Refund Completion

Date: 2026-06-19

## Summary

Phase 3 completes the production-facing SMS & Payment Gateway workflow on top of the
Phase 1/2 foundation, preserving the layered architecture
(**controllers → services → provider interfaces → provider adapters**) and keeping
provider logic out of billing/invoice/patient/appointment/queue/cashier/accounting.

Delivered: a public (unauthenticated) payment-link portal, the payment-link
lifecycle, payment-request + receipt SMS operational workflows, appointment-reminder
scheduler wiring with a status screen, a queue-notification SMS hook, a provider
go-live checklist with sandbox-verification records and a live-activation guard,
the refund-bridge connection to the UHMS credit-note workflow, and a reconciliation
CSV export.

## Database changes (additive — `2026_06_19_000002_integrations_phase_3`)

- `payment_request_links`: `public_token` (unique URL handle), `token_hash`,
  `initiated_count`, `last_initiated_at`, `last_viewed_at`, `cancelled_by`,
  `cancelled_at`, `cancel_reason`, `success_redirect_url`, `failure_redirect_url`.
- `payment_provider_refunds`: `uhms_credit_note_id` (+ index) and new provider/manual
  refund status constants.
- New tables: `integration_provider_checklists`, `integration_provider_checklist_items`.
  MariaDB-10.1 safe (longText for JSON, short named FKs/indexes).

## Models added / changed

Added `IntegrationProviderChecklist`, `IntegrationProviderChecklistItem`. Extended
`PaymentRequestLink` (lifecycle fields + `INITIATED`/`FAILED` statuses; admin binding
stays on `id`, the public portal resolves by `public_token`), `PaymentProviderRefund`
(provider/credit-note statuses + `creditNote()` relation).

## Services added

`PublicPaymentLinkService`, `ProviderGoLiveChecklistService`,
`SchedulerStatusService`. Extended `IntegrationProviderService::activate()` (live
guard), `PaymentRefundBridgeService` (credit-note linkage + completion/manual
statuses), `PaymentRequestLinkService` (public token), `SmsNotificationEventService`
(`queueNotification()` hook), `PaymentReconciliationController` (CSV export).

## Routes / controllers / views added

- Public (no auth, rate-limited `public-payments`, `module:payment_gateway`):
  `GET /pay/{token}`, `POST /pay/{token}/initiate`, `POST /pay/{token}/verify`,
  `GET /pay/{token}/status`, `GET /pay/{token}/receipt` →
  `PublicPaymentController`. Views under `resources/views/payment-link/`
  (`show`, `pending`, `success`, `failed`, `expired`, `receipt`) on a minimal
  `layouts.public` (mobile-first, `noindex`, frame-breaker).
- Admin: `GoLiveChecklistController` (index/show/items/signoff/approve),
  `SchedulerStatusController`, reconciliation `export`, refund `bridge`. Views:
  `admin/integrations/golive/{index,show}`, `admin/integrations/scheduler/index`.

## Public payment-link portal workflow

`show` resolves the link by random token, records the view, and renders the derived
state (active form / pending / success / failed / expired). `initiate` revalidates
state + live invoice balance, reuses `PaymentGatewayService::initiate()`, links the
transaction and marks the link `initiated`. `verify` runs a safe recheck through
`PaymentVerificationService`. `receipt` is reachable only once a verified UHMS payment
exists. No UHMS payment is ever created directly from the portal.

## Payment link security

Resolved by random `public_token` only (validated `[A-Za-z0-9]{20,80}`) — never an
internal id. The page shows only a safe summary (facility, invoice number, amount,
currency, payer first name, expiry) — no clinical data, provider secrets, callback
payloads or accounting internals. Routes are rate-limited and module-gated; a paid
invoice shows the paid state and cannot re-initiate; expired/cancelled/used links are
blocked; amount/currency are re-validated before provider initiation; duplicate
status refreshes cannot duplicate a payment (idempotent verification + `uhms_payment_id`
guard).

## Payment request SMS workflow

"Send payment request SMS" creates a payment-request link and dispatches the opt-in
`invoice_payment_request` event whose default body now carries `{{payment_link}}`
(the public URL). Dedup by invoice/event unless `resend` is chosen (audited
`PAYMENT_REQUEST_SMS_RESENT`). Strictly gated by the toggle + active provider + valid
phone.

## Receipt SMS workflow

On `PaymentRecorded` (including a verified provider payment), the guarded listener
sends a receipt SMS only when `enable_receipt_sms` is on, deduplicated by payment +
event, with safe fields only (receipt number, amount, invoice number, facility, date)
— audited `RECEIPT_SMS_QUEUED`.

## Appointment reminder scheduler / queue SMS hook / scheduler wiring

`bootstrap/app.php` schedules `integrations:sms-reconcile-status` (hourly),
`integrations:payments-recheck-pending` (every 15 min) and
`integrations:sms-send-appointment-reminders` (daily 08:00) — all self-guarding
(no-op unless module + provider + toggle). Each records last-run status via
`SchedulerStatusService`; the Scheduler Status screen shows last run / status and the
exact crontab line. The queue-notification SMS hook
(`SmsNotificationEventService::queueNotification()`) is implemented and toggle-guarded;
wiring it into the live queue controller is left as a documented, deferred hook point
to avoid touching queue workflow.

## Provider go-live checklist + sandbox verification + live activation guard

`ProviderGoLiveChecklistService` creates a default 12-item checklist, records item
status + evidence (no secrets), finance/IT sign-offs and approval, and computes
live-readiness (all required items satisfied/waived + both sign-offs). Waivers require
a reason and the manage permission. `IntegrationProviderService::activate()` blocks a
**live** provider that is not go-live ready unless an elevated override with a reason
is supplied (audited `PROVIDER_LIVE_ACTIVATION_BLOCKED` / `PROVIDER_GOLIVE_OVERRIDE_USED`
/ `PROVIDER_LIVE_ACTIVATION_APPROVED`). Fake providers remain production-blocked by the
registry.

## Refund / credit-note workflow connection

`PaymentRefundBridgeService::prepare()` now accepts an already-approved UHMS
`credit_note_id` (the bridge never creates/approves the credit note — that stays the
existing CreditNote workflow), validates it matches the payment invoice and amount,
blocks over-refund, and links `uhms_credit_note_id` (audited
`PAYMENT_REFUND_BRIDGE_LINKED`). Provider-supported refunds complete via the gateway
(`PAYMENT_REFUND_PROVIDER_COMPLETED`); unsupported providers retain the request with
`manual_required` + a manual instruction (`PAYMENT_REFUND_MANUAL_REQUIRED`). New
refund statuses: `provider_pending`, `provider_refunded`, `provider_failed`,
`provider_unsupported`, `manual_required`.

## Payment reconciliation export

`PaymentReconciliationController::export()` streams a CSV of the filtered transactions
(reference, provider, amount, currency, status, provider status, invoice, UHMS payment
id, created_at) — audited `PAYMENT_RECONCILIATION_EXPORTED`, gated by
`reconciliation.view`.

## Security controls

Public portal: token-only resolution, rate limiting, module gate, no internal ids /
clinical data, CSRF on POST forms, `noindex`. Verified-before-paid and idempotency
preserved end to end. Credentials stay encrypted/masked; go-live evidence never stores
secrets; live activation is guarded + audited. New rate limiter `public-payments`
(30/min/IP).

## Permissions

Added `integrations.payments.public_links.{view,cancel}`,
`integrations.payments.golive.{view,manage,approve}`,
`integrations.sms.golive.{view,manage,approve}`,
`integrations.scheduler.{view,manage}`. Defaults: Super Admin/Admin all; Finance
Manager — public links, payment go-live view/manage/approve, scheduler view (plus
inherited reconciliation/refund); Accountant & Cashier — public links view (plus
existing request-links). Provider/credential management stays admin/IT only.

## Audit logging

All Phase 3 actions audited via `ActivityLogService` under `LogModule::INTEGRATIONS`
(`PAYMENT_LINK_PUBLIC_VIEWED/INITIATED/STATUS_VIEWED/RECEIPT_VIEWED`,
`PAYMENT_REQUEST_SMS_RESENT`, `RECEIPT_SMS_QUEUED`, `APPOINTMENT_REMINDER_SCHEDULER_RUN`,
`QUEUE_SMS_EVENT_TRIGGERED`, `INTEGRATION_SCHEDULER_STATUS_VIEWED`,
`PROVIDER_GOLIVE_CHECKLIST_CREATED`, `PROVIDER_GOLIVE_ITEM_UPDATED`,
`PROVIDER_GOLIVE_SIGNOFF_RECORDED`, `PROVIDER_GOLIVE_OVERRIDE_USED`,
`PROVIDER_LIVE_ACTIVATION_BLOCKED/APPROVED`, `PAYMENT_REFUND_BRIDGE_LINKED`,
`PAYMENT_REFUND_PROVIDER_COMPLETED`, `PAYMENT_REFUND_MANUAL_REQUIRED`,
`PAYMENT_RECONCILIATION_EXPORTED`).

## Localisation audit result

Extended `integrations.php`, `sms.php`, `payments.php`, `menu.php`, `common.php`
(EN+FR). **Active runtime candidates: 0**; `localisation-parity-check`: **parity OK**.

## Minimal verification commands run

- `php artisan migrate --force` — Phase 3 migration applied (MariaDB).
- `php artisan route:list` — 12 new routes (5 public + 5 go-live + scheduler + export).
- `php artisan view:cache` / `view:clear` — all Blade compiles.
- `php scripts/localisation-audit.php` — active runtime candidates = 0.
- `php scripts/localisation-parity-check.php` — EN/FR parity OK.
- `php artisan logs:audit` — MISSING_LOG = 0, NEEDS_REVIEW = 2 (unchanged baseline).
- `php artisan permissions:audit --strict` — clean (exit 0).
- `git diff --check` — clean. PHP `-l` on all changed files — clean.

## Tests added (focused; full suite intentionally deferred)

`tests/Feature/Integrations/IntegrationsPhase3Test.php` — **21 tests, all passing**:
public page uses token / hides ids, expired & cancelled links cannot initiate,
paid-invoice link shows paid state, link initiation creates a transaction but not a
UHMS payment, verified link creates exactly one UHMS payment (refresh-safe), receipt
only after verification, module middleware blocks the public portal when disabled,
payment-request SMS includes the link, receipt SMS queues after verification,
appointment & queue SMS respect toggles, scheduler page permission-gated, checklist
created, live activation blocked when incomplete, override audited, waiver requires
permission + reason, refund links to approved credit note, unsupported refund marks
manual-required, reconciliation export permission-gated, EN/FR keys exist. Phase 1
(21) + Phase 2 (19) suites re-run green — no regression. Wide full suite remains
**intentionally deferred**.

## Known limitations

- Live provider request/response/signature formats (Nalo SMS, MTN MoMo, Nalo Payment)
  still carry `TODO`s pending sandbox-credential confirmation; the go-live checklist +
  live-activation guard exist precisely to gate go-live until those are recorded.
- The public portal is a single secure-link page, not a full patient-portal account
  system (out of scope). Success/failure redirect URLs are stored but not yet consumed.
- The queue-notification SMS hook is implemented as a service method + toggle; wiring
  it into the live queue controller events is a documented deferred step.
- The refund bridge links to and is gated by an approved CreditNote but does not post
  accounting itself (existing CreditNote/refund posting remains the source of truth).
- Card PCI vaulting and MoMo disbursement remain out of scope until credentials/PCI
  scope are confirmed.

## Next recommended phase

Confirm sandbox payloads/signatures and walk providers through the go-live checklist
to flip them live; consume success/failure redirect URLs on the public pages; wire the
queue-notification hook into the live queue events; add reconciliation Excel export +
scheduled CSV email; and run the full application test suite once all implementation
phases are complete.
