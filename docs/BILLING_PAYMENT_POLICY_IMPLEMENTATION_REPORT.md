# Billing Payment Policy — Implementation Report

Context-aware billing/payment enforcement for UHMS. This change adds a **policy
+ gate layer on top of the existing billing**; it does **not** rebuild billing,
create a parallel invoice system, or touch stock deduction. It ships the tested
decision engine and the deferred-settlement override; **controller wiring, UI,
and reports are the documented next phase** (see §16–17) and are intentionally
not yet enabled so no existing workflow is affected.

> Rollout safety: `config/billing_policy.php → enforce` is the master switch. The
> engine exists and is tested, but until the `assert*` gates are wired into the
> department controllers, behaviour is unchanged.

## 1. Current billing system found

- **One invoice per visit** (`InvoiceService::getOrCreateVisitInvoice`).
- `Invoice`: `status` (enum DRAFT/PENDING/PARTIALLY_PAID/PAID/CANCELLED/REFUNDED), `total_amount`, `amount_paid`, `balance`.
- `InvoiceItem`: `source_type` (14 canonical sources), `payment_status` (unpaid/partially_paid/paid/waived/cancelled/voided), `patient_payable`, `paid_amount`, `balance`, `insurance_covered`, `is_nhis_covered`, `department_id`, `service_catalog_id`, `product_id` — **line-level settlement already exists**, plus a `payment_allocations` table for line allocation and a `ServiceRendering` model with `rendering_status`.
- `PaymentService::recordPayment` already supports multiple/partial/periodic payments and reduces the invoice balance; it calls `VisitWorkflowService::completeAfterPayment`.

## 2. Existing behaviour preserved

No existing model, migration, service method, route, or permission was modified
destructively. Additions only: one `hasMany` relation on `Visit`, new config
files, a new table, new services, new permissions, new UI status domains. The
full suite still passes (23 adjacent tests verified; engine adds 15).

## 3. Context-aware policy added

`BillingPolicyService::careContext()` classifies each visit as **OPD**,
**EMERGENCY**, or **ADMISSION** (active admission → emergency case → visit-type
fallback) and derives an enforcement **mode**.

## 4. OPD strict pay-before-service

Default OPD mode `STRICT_PAY_BEFORE_SERVICE`: an unsettled payable line blocks
its service with a friendly, source-aware message ("Consultation fee has not been
settled.", "This drug has not been paid for yet.", …).

## 5. OPD deferred settlement override

`VisitBillingOverride` (type `DEFERRED_OPD_SETTLEMENT`, scope `VISIT`) lets an
authorised user render the whole OPD visit and settle once at the end. One active
override per visit; requires a reason; OPD-only; recorded to pathway + activity
log. Revoke re-locks unpaid services; expiry re-locks automatically.

## 6. Emergency running bill

`EMERGENCY` → `RUNNING_BILL`: the gate **always allows** rendering; charges
accumulate; periodic payments reduce the invoice balance via the existing
`PaymentService`. Care is never blocked.

## 7. Admission running bill

`ADMISSION` → `RUNNING_BILL`: same as Emergency. `require_clearance_before_discharge`
config governs optional discharge financial clearance (service stub + config in
place; discharge wiring is in §17).

## 8. Insurance / credit / waiver

- **Insurance-covered** (`patient_payable` ≤ 0 with coverage) → allowed (`INSURANCE_COVERED`).
- **Waived** (`payment_status = waived`) → allowed (`WAIVED`).
- **Credit-approved** → active `CREDIT_APPROVAL` override (visit/item/service/department scope) → allowed.
- OPD co-payment: when a balance remains it is treated as unpaid and gated (config `copayment_must_be_settled_before_opd_service`).

## 9. PaymentGateService

`app/Services/Billing/PaymentGateService.php` — façade with `canRenderInvoiceItem`/
`assertCanRenderInvoiceItem` (throws `BillingGateException` → friendly JSON 422 /
flashed web error) plus per-workflow helpers (`assertCanStartConsultation`,
`assertCanProcessInvestigation`, `assertCanDispensePrescriptionItem`,
`assertCanStartProcedure`, `assertCanMarkServiceRendered`). Department helpers
resolve the related invoice line and delegate; they are safe no-ops for settled /
running-bill / overridden contexts.

## 10. BillingPolicyService

`getVisitBillingMode`, `getInvoiceItemPolicy` (returns immutable
`BillingPolicyDecision` with `allowed/mode/reason/message/requires_payment/
requires_override/override_used/settlement_status`), `requiresPaymentBeforeService`,
`isDeferredSettlementAllowed`, `isRunningBillContext`, `careContext`,
`hasActiveOverride`. `InvoiceItemSettlementService` returns intrinsic line
settlement (`PAID/PARTIALLY_PAID/COVERED_BY_INSURANCE/WAIVED/BILLED_UNPAID/…`).

## 11. Department workflow integration

**Engine ready, wiring pending (next phase).** The exact call sites:
- Consultation start → `assertCanStartConsultation($visit, $consultationFeeItem, $user)` in `VisitWorkflowService::startConsultation` / the consultation controller.
- Lab accept/process/result → `assertCanProcessInvestigation(...)`.
- Pharmacy dispense → `assertCanDispensePrescriptionItem(...)` (gate is financial only — never deducts stock).
- Procedure/theatre start → `assertCanStartProcedure(...)`.
- Service rendering mark-rendered → `assertCanMarkServiceRendered(...)`.
- OPD visit completion under deferred settlement → balance check + `completeDeferredSettlement`.
- Admission discharge → `DischargeClearanceService` (to be added).

## 12. UI updates

`config/ui.php` gained status domains `settlement`, `service_readiness`,
`payment_gate`, `billing_policy`, `billing_override`, `discharge_clearance` so
`<x-status-badge domain="...">` renders consistent badges. Department-page badges,
the deferred-settlement approve/revoke `<x-confirm-form>` actions, and running-bill
panels are part of the wiring phase.

## 13. Permissions added (RoleSeeder)

`billing.policy.view`, `billing.view_payment_gate_status`,
`billing.view_running_balance`, `billing.payment_gate.override`,
`billing.override_opd_payment_gate`, `billing.override_opd_full_visit_settlement`,
`billing.revoke_opd_deferred_settlement`, `billing.approve_credit`,
`billing.approve_credit_balance`, `billing.waive_invoice_item`,
`billing.waive_invoice`, `billing.complete_visit_with_balance`,
`billing.discharge_clearance.override`. (Super Admin/Admin auto-receive all.)

## 14. Logs / notifications / pathway

- Pathway events: `DEFERRED_SETTLEMENT_APPROVED`, `DEFERRED_SETTLEMENT_REVOKED` (more event types reserved for the wiring phase: `SERVICE_BILLED`, `PAYMENT_SETTLED`, `RUNNING_BILL_ITEM_ADDED`, `PERIODIC_PAYMENT_RECEIVED`, `DISCHARGE_CLEARANCE_*`).
- Activity log (`ActivityLogService`, `LogModule::BILLING`): deferred approve / revoke / complete.
- Notifications: deferred-to-billing notifications are reserved for the wiring phase (kept out now to avoid spam and because `NotificationService` integration is unverified here).

## 15. Tests run

`tests/Feature/BillingPaymentPolicyTest.php` — **15 passing**: care-context
detection, settlement-status mapping, OPD block/allow (paid/covered/waived/partial),
Emergency & Admission running-bill allow, advisory mode, deferred approve
(reason + OPD guards), active-deferred allow, revoke re-lock, expiry re-lock,
complete, pathway event. Adjacent suites (UI components, billing JSON workflow,
module access) re-run green (23). Migration verified on real **MariaDB 10.1**.

## 16. Files modified / added

**Added:** `config/billing_policy.php`; `database/migrations/2026_06_03_000001_create_visit_billing_overrides_table.php`;
`app/Models/VisitBillingOverride.php`; `app/Exceptions/BillingGateException.php`;
`app/Services/Billing/{BillingPolicyDecision,InvoiceItemSettlementService,BillingPolicyService,VisitBillingOverrideService,PaymentGateService}.php`;
`tests/Feature/BillingPaymentPolicyTest.php`; this report.
**Edited (additive):** `app/Models/Visit.php` (+`billingOverrides()`), `config/ui.php` (+6 domains), `database/seeders/RoleSeeder.php` (+13 permissions).

## 17. Remaining TODOs (the wiring phase)

1. Wire `assert*` gates into consultation/lab/pharmacy/theatre/service-rendering controllers (back-end enforcement — §29 of the spec), making affected existing tests settle/override first.
2. `DischargeClearanceService` + admission discharge wiring + config.
3. Department-page UI: readiness/settlement badges, deferred-settlement approve/revoke actions, running-bill panels for Emergency/Admission, end-of-visit balance gate.
4. Waiver / credit-approval actions (permissions exist) + `INSURANCE_AUTHORIZATION_PENDING` override flow.
5. Reports/statistics for blocked/rendered/deferred/running-bill/overrides.
6. Notifications (billing on deferred approve / completion, department on settle).
7. Add the remaining 28 spec tests (workflow-level) once wiring lands.

This foundation is intentionally enforcement-inert until step 1 so it can be merged
safely and enabled per-department.
