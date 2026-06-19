You are working on UHMS — Ultimate Hospital Management System.

Important:
There is currently no `docs/UHMS_IMPLEMENTATION_SKILL.md` file in this project.
Do not try to read it.
Follow this prompt directly.

# UHMS External Integrations Phase 3 — Patient Payment Portal, Scheduler Wiring, Provider Go-Live & Refund/Credit-Note Completion

## Goal

Complete the production-facing SMS and Payment Gateway workflow.

Phase 1 created the provider-based SMS and Payment Gateway foundation.

Phase 2 added queue-backed SMS, status reconciliation, SMS events, payment request links, payment reconciliation, stale pending recheck, webhook hardening, provider health, and refund bridge foundation.

Phase 3 must now add:

```text
public patient payment-link portal
secure invoice payment page
payment request link usage flow
provider go-live checklist
scheduler wiring for SMS/payment commands
live provider payload verification records
provider signature verification readiness
refund bridge connection to UHMS credit-note/refund workflow
queue notification SMS hook
appointment reminder scheduler activation
receipt/payment-request SMS operational workflow
admin go-live dashboard
documentation
```

Do not break the existing provider architecture:

```text
controllers → services → provider interfaces → provider adapters
```

Do not put provider-specific logic into billing, invoice, patient, appointment, queue, cashier, or accounting controllers.

---

# 1. Required Context

Read:

```text
docs/INTEGRATIONS_PHASE_1_SMS_AND_PAYMENT_GATEWAYS_REPORT.md
docs/INTEGRATIONS_PHASE_2_QUEUE_RECONCILIATION_AND_EVENTS_REPORT.md
docs/ACCOUNTING_PHASE_0_SHARED_CONTROLS_AND_READINESS_REPORT.md
docs/ACCOUNTING_PHASE_A_BASIC_TO_ADVANCED_POSTING_BRIDGE_REPORT.md
docs/ACCOUNTING_PHASE_B_BANK_ACCOUNTS_AND_RECONCILIATION_REPORT.md
docs/ACCOUNTING_PHASE_C_FAILED_POSTING_WORKBENCH_REPORT.md
docs/ACCOUNTING_PHASE_D_SUBLEDGER_RECONCILIATION_WORKBENCH_REPORT.md
docs/ACCOUNTING_PHASE_E_PAYROLL_ACCOUNTING_POSTING_REPORT.md
docs/ACCOUNTING_PHASE_E2_STATUTORY_PAYROLL_SETTLEMENT_REPORT.md
docs/ACCOUNTING_PHASE_F_CASH_FLOW_AND_EXPORTS_REPORT.md
docs/LOCALISATION_COVERAGE_AUDIT_REPORT.md
```

Current integration baseline:

```text
Phase 1 complete.
Phase 2 complete.
sms_gateway module exists.
payment_gateway module exists.
Nalo SMS, MTN MoMo, Nalo Payment, and fake providers exist.
Credentials are encrypted and masked.
Callbacks are stored idempotently.
Verified provider payments create normal UHMS payments through existing PaymentService.
SMS queue/retry/status reconciliation exists.
Payment reconciliation dashboard exists.
Payment request links exist but no public patient payment portal is exposed yet.
Refund bridge exists but is not fully connected to UHMS credit-note/refund posting.
Provider live payload/signature formats still need sandbox confirmation before go-live.
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
public payment-link portal
secure invoice payment page
payment link validation and expiry handling
payer confirmation screen
provider payment initiation from payment link
payment success / failed / pending page
receipt lookup after verified payment
scheduler command wiring documentation and optional Kernel schedule entries
provider go-live checklist records
provider sandbox verification records
provider signature verification readiness records
refund bridge connection to approved UHMS refund / credit-note workflow
queue notification SMS event hook
appointment reminder scheduler activation
payment request SMS operational workflow
receipt SMS operational workflow
admin go-live dashboard
permissions
audit logging
localisation
documentation
```

Do not implement yet:

```text
full patient portal account system
card PCI vaulting
provider disbursement without confirmed credentials
bulk marketing SMS
automatic debt collection SMS campaigns
provider-specific legal/compliance advice
```

---

# 3. Core Rules

Rules:

```text
Public payment page must never expose internal numeric IDs.
Public payment page must not expose clinical details.
Payment link must not mark invoice paid.
Provider payment must be verified before UHMS payment creation.
Duplicate callback or duplicate return-page refresh must not duplicate UHMS payment.
Expired or cancelled payment link must not initiate payment.
Amount and currency must be revalidated before provider initiation.
Manual/cash payments must remain unchanged.
SMS failure must not block billing, appointments, queue, or payment workflows.
Refund must not bypass approved UHMS refund/credit-note workflow.
Live providers must fail safe until sandbox payload/signature confirmation is recorded.
```

---

# 4. Public Payment Link Portal

Add public routes, if the project safely supports public routes:

```text
GET  /pay/{linkUuid}
POST /pay/{linkUuid}/initiate
GET  /pay/{linkUuid}/status
GET  /pay/{linkUuid}/receipt
```

Route names:

```text
public.payments.show
public.payments.initiate
public.payments.status
public.payments.receipt
```

Rules:

```text
No auth required.
No internal IDs in URL.
Use UUID / random public token only.
Rate-limit public payment routes.
Do not expose diagnosis, service details, clinical notes, or restricted patient data.
Show only safe invoice summary:
- hospital/facility name
- invoice number
- amount due
- currency
- payer/patient display name if safe
- expiry status
- payment instructions
```

If public routes are not safe in this app, implement the portal foundation behind a feature flag and document what remains.

---

# 5. Payment Link Lifecycle

Extend `payment_request_links` if needed:

```text
public_token
token_hash
initiated_count
last_initiated_at
last_viewed_at
cancelled_by
cancelled_at
cancel_reason
success_redirect_url nullable
failure_redirect_url nullable
```

Statuses:

```text
active
initiated
used
expired
cancelled
failed
```

Rules:

```text
Active link can start provider payment.
Initiated link can show pending status.
Used link cannot initiate another payment unless reissue is configured.
Expired link blocks initiation.
Cancelled link blocks initiation.
If invoice is already paid, show paid state and do not initiate.
If invoice balance changed, recalculate amount before initiation.
```

---

# 6. Payment Page UX

Create public views:

```text
payment-link/show.blade.php
payment-link/pending.blade.php
payment-link/success.blade.php
payment-link/failed.blade.php
payment-link/expired.blade.php
payment-link/receipt.blade.php
```

The page should support:

```text
mobile-first layout
clear amount due
provider selection hidden because only active provider is used
phone number input if provider requires payer phone
payment method instructions
pending status message
manual refresh / verify button
safe receipt after verified payment
```

Use Bootstrap 5 and existing UI components.

Do not introduce a frontend framework.

---

# 7. Payment Initiation From Link

Add service:

```text
PublicPaymentLinkService
```

Responsibilities:

```text
validate public token
validate link status
validate invoice balance
validate amount/currency
collect payer phone if required
call PaymentGatewayService
link payment_provider_transaction_id to payment_request_link
mark link initiated
show provider instructions
```

Do not duplicate payment initiation logic.

Do not create UHMS payment directly.

---

# 8. Payment Status and Receipt

Payment status page must:

```text
load payment request link
load linked provider transaction
show pending / paid / failed / expired
allow manual recheck through safe service method
show receipt only after verified UHMS payment exists
```

Rules:

```text
Do not reveal provider secrets.
Do not show raw callback payload publicly.
Do not expose accounting posting details publicly.
```

---

# 9. SMS Payment Request Workflow

Improve payment request SMS.

When billing user clicks "Send payment request SMS":

```text
create or reuse active payment request link
render SMS template with {{payment_link}}
queue SMS through SmsGatewayService
record sms_notification_event
audit action
```

Rules:

```text
Do not send if SMS event toggle is disabled unless user explicitly chooses manual send with permission.
Do not send duplicate payment request SMS for same invoice/link unless resend is confirmed.
Payment link expiry should be shown to user before sending.
```

---

# 10. Receipt SMS Workflow

When provider payment is verified and UHMS payment is created:

```text
if receipt SMS toggle enabled:
    render receipt template
    queue SMS
    deduplicate by payment id + event type
```

Receipt SMS must include safe fields only:

```text
receipt number
amount paid
invoice number
facility name
payment date
```

Do not include diagnoses, clinical notes, lab details, or restricted patient details.

---

# 11. Appointment Reminder Scheduler

Phase 2 added command:

```bash
php artisan integrations:sms-send-appointment-reminders
```

Now wire it operationally.

If Laravel console scheduler is present, add schedule entry:

```php
$schedule->command('integrations:sms-send-appointment-reminders --date=tomorrow')->dailyAt('08:00');
```

But only activate sending if:

```text
sms_gateway module enabled
active SMS provider exists
enable_appointment_reminder_sms = true
appointment module exists
```

If scheduler structure is not ready, document exact cron entry:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

and document the command to schedule later.

---

# 12. Queue Notification SMS Hook

If the queue module exists, add hook:

```text
queue ticket created
patient called
patient moved to consultation
```

Rules:

```text
Do not automatically send unless enable_queue_sms is true.
Deduplicate by queue entry + event type.
Failure must not block queue workflow.
SMS should be short and safe.
```

If queue workflow is not cleanly hookable, document deferred hook points.

---

# 13. Scheduler Wiring

Add or document scheduled jobs for:

```text
SMS status reconciliation
pending payment recheck
appointment reminders
```

Recommended schedule:

```text
integrations:sms-reconcile-status every 15 minutes or hourly
integrations:payments-recheck-pending every 10-15 minutes
integrations:sms-send-appointment-reminders daily morning
```

Use project scheduler conventions.

If scheduler is not used in the project, create documentation instead of forcing it.

Add admin screen to show:

```text
last command run
last success/failure
recommended cron status if detectable
```

---

# 14. Provider Go-Live Checklist

Create tables:

```text
integration_provider_checklists
integration_provider_checklist_items
```

Or store checklist as structured provider metadata if simpler.

Checklist items:

```text
sandbox credentials configured
test connection passed
test SMS/payment sent
callback URL configured at provider
callback signature/secret confirmed
sandbox callback received
amount/currency validation tested
duplicate callback tested
failed transaction tested
live credentials configured
live callback URL configured
live provider status verified
finance sign-off
IT sign-off
go-live approved
```

Statuses:

```text
pending
passed
failed
not_applicable
waived
```

Rules:

```text
Provider cannot be marked live-ready until required checklist items pass or are waived with reason.
Waiver requires elevated permission.
Checklist does not activate provider automatically unless user explicitly activates provider.
```

---

# 15. Provider Sandbox Verification Records

Add service:

```text
ProviderGoLiveChecklistService
```

Responsibilities:

```text
create default checklist for provider
record checklist item status
record evidence/reference
record sandbox test results
record callback verification result
record sign-off
determine live readiness
audit all changes
```

Evidence may include:

```text
reference number
provider ticket id
test transaction id
callback sample hash
notes
timestamp
actor
```

Do not store secrets in evidence.

---

# 16. Live Provider Activation Guard

When activating a real provider in live mode:

```text
check required go-live checklist
warn or block if not live-ready
allow elevated override only with reason
audit override
```

Fake providers must remain blocked in production unless explicitly allowed by config.

---

# 17. Refund / Credit-note Completion

Phase 2 created refund bridge foundation.

Now connect it to existing UHMS credit-note/refund workflow.

Inspect existing:

```text
credit notes
refunds
write-offs
payments
invoice adjustments
cash/bank refunds
```

Implement only where existing workflow is clear.

Rules:

```text
Provider refund requires approved UHMS refund or credit note.
Refund cannot exceed verified provider payment remaining refundable amount.
Provider refund result must link back to UHMS refund/credit note.
If provider refund unsupported, retain manual refund instruction.
If provider refund succeeds, update UHMS refund status using existing refund workflow.
Do not reverse invoice/payment incorrectly.
Do not bypass accounting posting rules.
```

Add refund statuses if missing:

```text
provider_pending
provider_refunded
provider_failed
provider_unsupported
manual_required
```

---

# 18. Payment Reconciliation Enhancement

Add reconciliation improvements:

```text
provider totals by date
UHMS payments by date
provider-paid not linked
UHMS-linked but provider status unknown
amount mismatch report
currency mismatch report
duplicate callback report
manual refund required report
```

Export:

```text
CSV export for payment reconciliation dashboard
```

Use existing export tooling.

---

# 19. Permissions

Reuse Phase 1 and 2 permissions where possible.

Add only if missing:

```text
integrations.payments.public_links.view
integrations.payments.public_links.cancel
integrations.payments.golive.view
integrations.payments.golive.manage
integrations.payments.golive.approve
integrations.sms.golive.view
integrations.sms.golive.manage
integrations.sms.golive.approve
integrations.scheduler.view
integrations.scheduler.manage
```

Suggested defaults:

```text
Administrator / Super Admin:
- all

IT Admin:
- provider go-live checklist
- scheduler
- provider activation

Finance Manager:
- payment reconciliation
- go-live approval for payment providers
- refund bridge

Cashier / Accountant:
- create/send payment request links
- view transaction status
- manual verify if already permitted

Receptionist:
- appointment/queue SMS actions only where permitted
```

---

# 20. Audit Logging

Use `ActivityLogService`.

Audit:

```text
PAYMENT_LINK_PUBLIC_VIEWED
PAYMENT_LINK_PUBLIC_INITIATED
PAYMENT_LINK_PUBLIC_STATUS_VIEWED
PAYMENT_LINK_PUBLIC_RECEIPT_VIEWED
PAYMENT_REQUEST_SMS_RESENT
RECEIPT_SMS_QUEUED
APPOINTMENT_REMINDER_SCHEDULER_RUN
QUEUE_SMS_EVENT_TRIGGERED
INTEGRATION_SCHEDULER_STATUS_VIEWED
PROVIDER_GOLIVE_CHECKLIST_CREATED
PROVIDER_GOLIVE_ITEM_UPDATED
PROVIDER_GOLIVE_SIGNOFF_RECORDED
PROVIDER_GOLIVE_OVERRIDE_USED
PROVIDER_LIVE_ACTIVATION_BLOCKED
PROVIDER_LIVE_ACTIVATION_APPROVED
PAYMENT_REFUND_BRIDGE_LINKED
PAYMENT_REFUND_PROVIDER_COMPLETED
PAYMENT_REFUND_MANUAL_REQUIRED
PAYMENT_RECONCILIATION_EXPORTED
```

Run:

```bash
php artisan logs:audit --json
```

Fix new missing/needs-review audit gaps.

---

# 21. Localisation

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
public_payment
payment_link
payment_link_expired
payment_link_cancelled
payment_link_used
pay_invoice
amount_due
payer_phone
payment_pending
payment_successful
payment_unsuccessful
view_receipt
download_receipt
send_payment_request_sms
resend_payment_request_sms
receipt_sms
go_live_checklist
go_live_ready
not_live_ready
sandbox_verification
callback_signature_confirmed
provider_signoff
finance_signoff
it_signoff
activation_override
scheduler_status
scheduled_commands
last_command_run
refund_manual_required
provider_refund_completed
payment_reconciliation_export
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

# 22. Navigation

Add or extend navigation:

```text
Administration / Integrations > Go-Live Checklists
Administration / Integrations > Scheduler Status
Billing & Collections > Payment Request Links
Billing & Collections > Payment Reconciliation
```

Public payment pages must not appear in authenticated sidebar navigation.

---

# 23. Focused Tests To Add

Add focused tests but do not run the wide full suite.

Required coverage:

```text
public payment link page does not expose internal IDs
expired payment link cannot initiate payment
cancelled payment link cannot initiate payment
already-paid invoice link shows paid state
payment link initiation creates provider transaction
payment link does not create UHMS payment before verification
verified payment link creates one UHMS payment
duplicate return/status refresh does not duplicate payment
receipt page only works after verified payment
payment request SMS includes payment link when enabled
receipt SMS queues after verified payment when enabled
appointment reminder command respects toggle
queue SMS hook respects toggle
scheduler status page is permission protected
go-live checklist is created for provider
live provider activation blocked when checklist incomplete
go-live waiver requires permission and reason
provider activation override is audited
provider refund links to approved UHMS refund/credit note
unsupported provider refund marks manual required
payment reconciliation export requires permission
module middleware protects integration routes
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

# 24. Minimal Verification Commands

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

# 25. Documentation

Create:

```text
docs/INTEGRATIONS_PHASE_3_PUBLIC_PAYMENT_PORTAL_GOLIVE_AND_REFUNDS_REPORT.md
```

Include:

```text
summary
database changes
models added/changed
services added
routes/controllers/views added
public payment-link portal workflow
payment link security
payment request SMS workflow
receipt SMS workflow
appointment reminder scheduler
queue SMS hook
scheduler wiring
provider go-live checklist
sandbox verification behavior
live activation guard
refund/credit-note workflow connection
payment reconciliation export
security controls
permissions
audit logging
localisation audit result
minimal verification commands run
tests added but not fully executed
known limitations
next recommended phase
```

---

# 26. Acceptance Criteria

Phase 3 is complete only when:

```text
public payment link page exists or is explicitly feature-flagged with foundation complete
payment link does not expose internal IDs
expired/cancelled/used links are handled safely
payment link initiation creates provider transaction but not UHMS payment
verified payment creates exactly one UHMS payment
receipt page only appears after verified payment
payment request SMS can include payment link
receipt SMS can queue after verified payment
appointment reminder scheduler is wired or documented
queue SMS hook is implemented or exact hook points are documented
scheduler status page exists
go-live checklist exists
live provider activation guard exists
sandbox verification records exist
provider activation override requires permission and reason
refund bridge connects to approved UHMS refund/credit-note workflow where existing workflow is available
unsupported provider refunds remain visible as manual-required
payment reconciliation CSV export exists
manual/cash payment workflows remain unchanged
provider credentials remain encrypted and masked
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

Proceed with External Integrations Phase 3 now.
