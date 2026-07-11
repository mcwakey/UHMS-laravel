# Payment Timing Policy Phase 2 Report

## Outcome

Phase 2 connects the typed payment-timing vocabulary to the existing `BillingPolicyService` in legacy-authoritative observation mode. It adds typed resolution, compatibility mapping, comparison, bounded diagnostic logging, and a read-only audit command. No typed decision can change an operational allow, block, advisory, exception, message, or return shape.

## Existing billing-policy architecture audited

### BillingPolicyService

`BillingPolicyService` is the current decision engine. Its public API includes care-context detection, running-bill/deferred-settlement checks, visit billing mode, pre-service payment requirement, invoice-item policy evaluation, and active-override checks. `getInvoiceItemPolicy` returns the immutable `BillingPolicyDecision` DTO with `allowed`, mode, reason, message, payment/override flags, and settlement status.

The engine reads `config/billing_policy.php`. `billing_policy.enforce=false` returns an allowed `ADVISORY` decision. Otherwise it delegates intrinsic invoice-line state to `InvoiceItemSettlementService`, which reads maintained invoice-item responsibility, paid, balance, insurance-covered, waiver, cancellation, and invoice-adjustment values. It does not use receivables or recalculate allocations. Emergency and admission contexts are always running bills; OPD defaults to prepayment. Existing active overrides are checked only after intrinsic settlement.

Previous balances are not part of `BillingPolicyService`; `PreviousBalanceOverrideService` applies a separate OPD prior-debt gate. `InvoiceReceivableService`, `PaymentService`, allocations, and accounting remain untouched.

### PaymentGateService

`PaymentGateService` is a façade over `BillingPolicyService` with these public contracts:

| Method family | Return | Behavior |
| --- | --- | --- |
| `policyFor` | `BillingPolicyDecision` | Returns the exact legacy DTO. |
| `canRenderInvoiceItem` | `bool` | Returns legacy `allowed`. |
| `assertCanRenderInvoiceItem` | `void` | Throws the existing `BillingGateException` with the legacy DTO/message when blocked. |
| consultation `can*` / `assert*` | `bool` / `void` | Missing fee is allowed; otherwise delegates to invoice-item gate. |
| investigation, dispensing, procedure, service-rendering `can*` / `assert*` | `bool` / `void` | Best-effort invoice-item resolution; missing item fails open exactly as before. |

Phase 2 adds only an optional internal operation label. Existing method names, existing positional arguments, return types, exception behavior, reasons, messages, and response metadata remain compatible.

### Gate call-site inventory

The repository audit found that the façade's departmental methods currently have no production call sites; they are exercised by billing-policy tests. The actual production reads found are:

| Caller | Gate/read | Workflow stage | Expected shape | Current behavior and overrides |
| --- | --- | --- | --- | --- |
| `TriageController::assertRouteServicesSettled` | `BillingPolicyService::getInvoiceItemPolicy` | Completing triage / routing consultation services | DTO, converted to `RuntimeException` message | Billable priced service without an invoice throws; legacy item policy blocks/allows and honors its existing overrides. |
| `ConsultationNextPatientService::paymentReadiness` | `BillingPolicyService::getInvoiceItemPolicy` | Next-patient preview/readiness | Local `{allowed,message}` array derived from DTO | First blocked route service prevents readiness; existing legacy override behavior applies. |
| `LabRequestItem::isBillSettled` | `InvoiceItemSettlementService::canProceedWithoutCashPayment` | Laboratory result-entry readiness | `bool` | Missing invoice item returns true; paid/covered/waived/adjusted returns true; billing overrides and visit timing are not consulted. |
| `PaymentGateService` departmental methods | façade methods listed above | Intended consultation/lab/pharmacy/procedure/render stages | bool/void/DTO | No production caller found. Missing resolved items fail open. |

No direct `PaymentGateService` call was found in pharmacy, radiology, theatre, treatment, nursing, admission, emergency, blood bank, ambulance, discharge, visit completion, billing, or cashier production paths. This is an important legacy wiring constraint for Phase 3, not something broadened in this integration phase.

## Existing override compatibility matrix

| Existing override | Actual scope/behavior | Phase 2 source mapping | Full timing override? |
| --- | --- | --- | --- |
| `DEFERRED_OPD_SETTLEMENT` | Created as VISIT scope; permits the OPD visit to continue and settle later. Reason required; optional expiry; active/revoked/completed lifecycle is audited. | `manual_override` when active and VISIT-scoped | Yes. |
| `PAYMENT_GATE_BYPASS` | Legacy engine accepts VISIT, DEPARTMENT, SERVICE, or INVOICE_ITEM scope for the matching invoice line. | `manual_override` only when active and VISIT-scoped; narrow scopes are not elevated by the visit resolver | Only at VISIT scope. |
| `CREDIT_APPROVAL` | Same scoped matching behavior for a legacy invoice item. | `manual_override` only when active and VISIT-scoped | Only at VISIT scope. |
| `PREVIOUS_BALANCE_OVERRIDE` | Permits OPD care despite prior-visit debt; evaluated by the separate previous-balance service. | Diagnostic context only | No. |
| `MANAGEMENT_APPROVAL` | Defined in the model but not consumed by the current item policy. | None | No confirmed timing semantics. |
| `INSURANCE_AUTHORIZATION_PENDING` | Defined in the model but not consumed by the current item policy. | None | No confirmed timing semantics. |

The resolver never broadens a department, service, invoice-item, prior-debt, closure, or unconsumed override into a visit-wide policy.

## Current enforcement and rollout flags

- `BILLING_GATE_ENFORCE` / `billing_policy.enforce` is the current operational authority and defaults to true.
- `billing_policy.opd.payment_required_before_service`, partial-payment threshold settings, emergency running-bill settings, admission running-bill/clearance settings, and insurance/credit/waiver behavior shape the legacy decision.
- `billing.previous_balance_policy.enabled` and its OPD threshold/any-balance flags control the separate prior-debt gate. Emergency/admission previous-balance bypass remains unchanged.
- Phase 1 `payment_timing.enabled` remains non-enforcing.
- `PAYMENT_TIMING_INTEGRATION_MODE` defaults to `legacy`; the only other valid value is `observe`. Invalid values fall back to `legacy`.
- `PAYMENT_TIMING_LOG_MISMATCHES`, `PAYMENT_TIMING_LOG_MATCHES`, and `PAYMENT_TIMING_LOG_DEDUPLICATION_SECONDS` control observation noise. These are environment controls and are not exposed in hospital settings UI.

## Integration and precedence design

`BillingPolicyService::getInvoiceItemPolicy` first evaluates the unchanged legacy path and stores its exact DTO. In `legacy` mode it immediately returns that DTO. In `observe` mode it safely resolves and compares the typed expectation, then still returns the same DTO. Any resolver, comparison, cache, or logging exception is caught and cannot alter the result.

The limited `VisitPaymentTimingResolver` precedence is:

1. Emergency visit-type stabilisation protection -> `running_bill`, source `emergency_policy`.
2. Confirmed active VISIT-scoped deferred/credit/bypass override -> `pay_after_all_services`, source `manual_override`.
3. Explicit visit-type configuration -> source `visit_type`.
4. Inherited or missing visit-type configuration -> operational global default, source `global_default`.

Final decisions never contain `inherit`. The DTO reports policy, source, machine reason, visit type, global default, configured visit-type value, emergency consideration, override consideration, prior-balance override presence, and integration mode. It contains no translated canonical reason or patient-sensitive data.

## Legacy mapping and observation

`PaymentTimingLegacyCompatibilityService` maps only timing-comparable legacy reasons:

- `ITEM_UNPAID` -> `pay_before_service`
- `RUNNING_BILL` / `OPD_RUNNING_BILL` -> `running_bill`
- `DEFERRED_APPROVED` / `CREDIT_APPROVED` / `GATE_BYPASS` -> `pay_after_all_services`

Advisory, already-paid, insurer-covered, waived, adjusted, cancelled, partial-threshold, missing-visit, and other service-state decisions are retained as advisory/not-comparable/missing context instead of forcing false timing equivalence.

Comparison outcomes are `match`, `legacy_more_restrictive`, `typed_more_restrictive`, `not_comparable`, and `missing_context`. Mismatches are logged by default only in observe mode. Matches are off by default. Logs contain bounded IDs/enums/reason codes only, are deduplicated for five minutes by default, never use ActivityLog, and never perform invoice queries.

## Diagnostic command

Use:

```bash
php artisan billing:payment-timing-audit \
  --visit=123 --visit-type=outpatient --active-only \
  --limit=100 --mismatches-only --json
```

The command eagerly reads a bounded visit set plus its existing overrides. It compares safe visit-context expectations and does not simulate a billable action, create an invoice, create a payment, create an override, or add an ActivityLog row. Mismatches do not cause a failure exit.

Local execution with `--limit=25` inspected the six available visits: 6 matches, 0 legacy-more-restrictive, 0 typed-more-restrictive, 0 not-comparable, and 0 missing-context. Five outpatient visits matched prepayment/global-default; one inpatient visit matched running-bill/visit-type policy. No local outcome mismatch was discovered.

## Query and performance impact

- Legacy mode performs one in-memory config read and no typed resolution, comparison, setting lookup, override lookup, cache write, or diagnostic log. A focused assertion observed zero queries on the advisory legacy path.
- Observe mode reuses the visit already resolved by the legacy item decision. Settings use the existing settings cache. Active overrides are reused when eager loaded; otherwise the resolver performs at most one memoised override query per visit/resolver instance.
- Comparison uses the existing legacy DTO and performs no payment, invoice, receivable, balance, insurer, or patient queries.
- The command eager loads overrides to avoid N+1 access and applies a hard 1-1000 bound.

## Failure safety

Invalid integration modes fall back to legacy. Invalid policies use Phase 1 operational fallbacks. Typed observation exceptions emit a bounded application warning and return the original legacy DTO. Diagnostic-log failures are swallowed after a debug diagnostic. No observation error can allow a blocked service, block an allowed service, change advisory mode, or roll back a clinical/billing action.

## Files created

- `app/Enums/PaymentTimingIntegrationMode.php`
- `app/Enums/PaymentTimingComparisonOutcome.php`
- `app/Data/Billing/VisitPaymentTimingDecision.php`
- `app/Data/Billing/LegacyPaymentGateSnapshot.php`
- `app/Data/Billing/PaymentTimingPolicyComparison.php`
- `app/Services/Billing/VisitPaymentTimingResolver.php`
- `app/Services/Billing/PaymentTimingLegacyCompatibilityService.php`
- `app/Services/Billing/PaymentTimingPolicyComparisonService.php`
- `app/Console/Commands/PaymentTimingAuditCommand.php`
- `tests/Feature/PaymentTimingIntegrationTest.php`
- `tests/Feature/VisitPaymentTimingResolverTest.php`
- `tests/Feature/PaymentTimingCompatibilityTest.php`
- `tests/Feature/PaymentTimingObservationTest.php`
- `tests/Feature/PaymentTimingAuditCommandTest.php`
- `docs/billing/PAYMENT_TIMING_POLICY_PHASE_2_REPORT.md`

## Files modified

- `.env.example`
- `config/payment_timing.php`
- `app/Services/Billing/PaymentTimingConfigurationService.php`
- `app/Services/Billing/BillingPolicyService.php`
- `app/Services/Billing/PaymentGateService.php`
- `lang/en/payment_timing.php`
- `lang/fr/payment_timing.php`
- `tests/Feature/PaymentTimingSettingsTest.php`

## Verification performed

- Phase 1 + Phase 2 payment-timing focus: 29 tests passed, 102 assertions.
- Existing `BillingPaymentPolicyTest`: 17 tests passed, 45 assertions.
- Existing `PreviousBalancePolicyTest`: 16 tests passed, 50 assertions.
- PHP syntax checks for every new integration class passed.
- Config and compiled views cleared; Blade view cache compiled successfully.
- Payment-timing routes remained registered.
- English/French localisation parity passed.
- Permission audit: zero route permissions missing and zero unprotected admin mutation routes.
- Logging audit: zero real missing logs; its existing classified backlog remains unrelated.
- Read-only audit command completed successfully in table and JSON modes.
- `git diff --check` passed.

The full Laravel and Playwright suites were intentionally not run.

## Phase 3 recommendations

Before typed enforcement, Phase 3 should decide and test the actual workflow stages that must call the single façade. It should migrate the two direct `BillingPolicyService` callers and explicitly reconcile the lab model's intrinsic settlement check with the policy gate. Pharmacy, procedures, service rendering, radiology, theatre, blood bank, and other intended stages need audited wiring rather than assumed coverage.

Phase 3 must also define stage-aware emergency stabilisation boundaries, service-scoped resolution context, precedence for future persisted visit policy/risk/insurance/corporate sources, and a deliberate cutover flag with rollback. Previous-balance and financial-closure overrides must remain separate unless their semantics are explicitly unified.
