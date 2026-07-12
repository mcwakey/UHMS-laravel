# Payment Timing Policy — Phase 8 Report

**Controlled Operational Cutover, Approved-Arrangement Precedence, Typed Gate Decisions, and Instant Rollback**

Date: 2026-07-12 · Branch: `beta-x` · Status: Complete (default-off; cutover opt-in)

---

## 1. Existing runtime gate architecture audited

- **`BillingPolicyService`** exposes three decision primitives, each receiving a `PaymentGateContext` (carrying the operation code) and returning an immutable `BillingPolicyDecision`:
  - `getInvoiceItemPolicy` — consultation route/readiness + investigation/procedure/service.
  - `getIntrinsicSettlementPolicy` — `laboratory.result.enter` (paid/covered/waived/adjusted-only).
  - `getPaidOnlyInvoiceItemPolicy` — `pharmacy.item.dispense` (strict paid-only).
- **`PaymentGateService`** is a thin façade over those three.
- **`BillingPolicyDecision`** — `allowed/mode/reason/message/requiresPayment/requiresOverride/overrideUsed/settlementStatus`.
- **`InvoiceItemSettlementService`** — statuses PAID / BILLED_UNPAID / PARTIALLY_PAID / COVERED_BY_INSURANCE / WAIVED / ADJUSTED / CANCELLED / UNBILLED.
- **Registry (Phase 4)** — 13 ops; 4 wired hard gates; lab/pharmacy carry `compatibility_requires_decision`.
- **Arrangement (Phase 7)** — `VisitPaymentPolicy.current_approved_arrangement_id` link + `VisitPaymentArrangementService::riskIsStale()`.

**Integration decision:** a single injected `PaymentTimingCutoverGateService::apply()` called at the end of the three primitives. Default (disabled) returns the *exact* legacy object with zero arrangement/visit-policy/typed queries.

## 2. Cutover-mode design

`PaymentTimingCutoverMode` = `disabled` (default; legacy unchanged, no extra queries) · `observe` (computes a would-be typed decision, records a bounded diff, returns exact legacy) · `active` (operations set to `typed` may return typed decisions; everything else legacy). Invalid values resolve to `disabled`.

## 3. Environment kill-switch precedence

`PAYMENT_TIMING_FORCE_LEGACY` (config `payment_timing_cutover.force_legacy`) forces the effective mode to `disabled`, DB-independently, without modifying arrangements or settings. Documented precedence: **environment force-legacy → master cutover setting → per-operation mode → runtime eligibility.**

## 4. Files created

Enums: `PaymentTimingCutoverMode`; `PaymentGateOperationMode::TYPED`; `VisitPaymentPolicySource::APPROVED_ARRANGEMENT`. Reason codes: `TypedPaymentGateReason`. Config `config/payment_timing_cutover.php`. Services: `PaymentTimingCutoverConfigurationService`, `ApprovedArrangementOperationalEligibilityService`, `OperationalVisitPaymentTimingResolver`, `TypedPaymentGateDecisionService`, `PaymentTimingCutoverGateService`, `PaymentTimingCutoverDiagnostics`. DTOs: `ApprovedArrangementOperationalEligibility`, `OperationalVisitPaymentTimingDecision`. Controller `Admin\Billing\PaymentTimingCutoverController` + 3 FormRequests. Commands: `…cutover-status`, `…cutover-audit`, `…cutover-preview`. View `admin/billing/payment-timing-cutover/index`. Migration (permissions). Localisation `lang/{en,fr}/payment_timing_cutover.php`. 7 test files (34 tests).

## 5. Files modified

`BillingPolicyService` (inject cutover + wrap 3 returns + public `legacyInvoiceItemPolicy`), `BillingPolicyDecision` (additive authority metadata + `withAuthority()`), `PaymentGateOperationRegistry` (typed metadata; approve 4 wired), `PaymentGateOperationConfigurationService` (typed clamp + `operationTypedEligible`/`compatibilityAcknowledged`/`storedForPublic`), `PaymentGateOperationMode`, `VisitPaymentPolicySource`, `AppServiceProvider` (singletons), `RoleSeeder`, `PaymentTimingSettingsSeeder` (cutover defaults), `routes/web.php`, `lang/*/payment_gate.php`, `lang/*/payment_timing.php`.

## 6. Operation eligibility and visit-type scope

| Operation | Typed | Supported visit types | Ack |
|---|---|---|---|
| consultation.route.complete | Yes | outpatient | — |
| consultation.next_patient.readiness | Yes | outpatient | — |
| laboratory.result.enter | Yes | outpatient, inpatient | Required |
| pharmacy.item.dispense | Yes | outpatient, inpatient | Required |

The nine unwired operations remain ineligible; `typed` is clamped to `legacy` for any non-eligible operation (verified). Emergency visits are excluded from typed support.

## 7. Laboratory compatibility decision

Legacy: intrinsic paid/covered/waived/adjusted-only preserved exactly. Typed (active + acknowledged): pay-before requires settlement per operation-allowed states (paid/insured/waived/adjusted/narrow override); pay-after and running-bill permit result entry before payment. Missing-item behaviour follows the configured missing-context rule. The admin must acknowledge the change.

## 8. Pharmacy compatibility decision

Legacy: strict paid-only preserved. Typed (active + acknowledged): pay-before requires settlement; pay-after/running-bill permit dispensing before payment. Stock/prescription/quantity/clinical/controlled-drug/cancellation/concurrency checks are untouched (typed changes only payment-timing permission). Emergency pharmacy stays legacy. The admin must acknowledge the change.

## 9. Approved-arrangement operational eligibility

`ApprovedArrangementOperationalEligibilityService` (read-only) requires: status approved, VPP link matches, valid non-inherit approved policy, effective reached, not expired, not stale-risk, supported visit type, typed-eligible operation, no conflicting active visit-wide legacy override, materialised policy present. Any failure → ineligible (with a machine reason).

## 10. Approved-arrangement precedence

`OperationalVisitPaymentTimingResolver` resolves the typed baseline via `VisitPaymentTimingResolver`, then overrides it with an eligible current approved arrangement (source `approved_arrangement`). It never mutates the Phase 6 baseline and never treats a risk recommendation as a source. Memoised per visit+operation.

## 11. Legacy override conflict handling

A restrictive (pay-before) arrangement conflicts with an active visit-wide legacy deferral override (`DEFERRED_OPD_SETTLEMENT` / `CREDIT_APPROVAL` / `PAYMENT_GATE_BYPASS`, scope VISIT) → the arrangement becomes operationally ineligible (reason `conflicting_legacy_visit_override`, also surfaced by the audit command). Matching deferral semantics coexist. No override is auto-revoked or created.

## 12. Typed gate decision rules

`TypedPaymentGateDecisionService` reuses the settlement service (no second balance calc): pay-before → require settlement (block `TYPED_PREPAYMENT_REQUIRED` / allow `TYPED_PREPAYMENT_SETTLED` per operation allow-flags + narrow override); pay-after → allow (`TYPED_PAY_AFTER_SERVICES_ALLOWED`); running-bill → allow (`TYPED_RUNNING_BILL_ALLOWED`). It **never** marks paid, creates a payment/waiver/override, or changes an invoice/receivable, and preserves every non-payment failure.

## 13. Missing-context handling

Operation's Phase 4 missing-billing-context rule: `preserve_legacy`/`not_applicable` → legacy; `allow` → allow; `block` → block. Initial cutover preserves existing workflow-specific missing-item behaviour (no normalisation).

## 14. PaymentGateService integration

The cutover is applied inside `BillingPolicyService` (the single primitive layer under the façade). Disabled → exact legacy (no queries); observe → exact legacy (records diff); active + typed op → typed decision tagged with authority; active + non-typed op → legacy; **any exception → legacy** (fail-safe). Façade method contracts are unchanged.

## 15. Admin activation and rollback UI

Restricted `admin/billing/payment-timing-cutover` shows configured/effective mode, force-legacy status, failure-fallback, and eligible operations with mode, supported visit types, compatibility warnings, emergency support and current blockers. Activating to `active` requires the activate permission + explicit confirmation + reason. A prominent **Return All Operations to Legacy** rollback requires the rollback permission + reason. Rollback modifies no arrangements/invoices/history.

## 16. Permissions and role assignments

`billing.payment_timing.cutover.{view,manage,activate,rollback}`. Super Admin/Admin: all; Finance Manager: view + manage (not activate/rollback); Accountant: view; clinical/reception: none. Enforced on routes, FormRequests and controller.

## 17. Activity-log behaviour

`PAYMENT_TIMING_CUTOVER_MODE_CHANGED`, `PAYMENT_GATE_TYPED_OPERATION_ENABLED`/`_DISABLED`, `PAYMENT_TIMING_FORCE_LEGACY_ROLLBACK` — bounded metadata (old/new mode, operation, acknowledgement, reason, actor), no patient data. Routine gate decisions create **no** activity logs.

## 18. Runtime diagnostics

`PaymentTimingCutoverDiagnostics` writes bounded, de-duplicated, non-sensitive application-log events (observed diff / applied / legacy fallback / arrangement ineligible / override conflict / emergency fallback). Successful routine decisions are not logged; logging failure never affects the gate.

## 19. Status, audit, and preview commands

`billing:payment-timing-cutover-status` (configured/effective mode + per-op typed eligibility), `…cutover-audit` (unsafe-config detection incl. active-while-force-legacy, typed-on-unwired/ineligible, missing acknowledgement, stale/expired/link-mismatch/conflict, active-without-fallback), `…cutover-preview` (legacy vs typed for a visit/operation/item; reports missing context). All read-only, no activity logs, findings never fail the exit code.

## 20. Deployment defaults

Master cutover `disabled`; all four wired ops `legacy`; nine unwired `disabled`; typed compatibility acknowledgement `false`; failure fallback `enabled`. Seeding is idempotent and never activates cutover or selects typed. (Settings defaults live in `PaymentTimingSettingsSeeder`; the migration seeds only permissions, leaving a fresh DB's `payment_timing` group untouched — disabled at runtime via the config fallback.)

## 21. Query and performance impact

Disabled adds no arrangement/visit-policy/typed-operation query (verified — the existing legacy-mode zero/one-query invariant is preserved). The cutover config memoises the master mode per request; the operational resolver memoises per visit+operation; diagnostics de-duplicate per request. No duplicate settlement calculation (typed reuses the settlement service).

## 22. Focused tests and results

All executed and passing — **34 Phase 8 tests**: `TypedPaymentGateDecisionServiceTest` (6), `PaymentGateTypedIntegrationTest` (7), `OperationalVisitPaymentTimingResolverTest` (5), `PaymentTimingCutoverConfigTest` (4), `PaymentTimingCutoverPermissionTest` (6), `PaymentTimingCutoverCommandTest` (4), `PaymentTimingCutoverLocalizationTest` (2). Full Phase 1–8 payment regression + `LanguageParityTest`: **226 passed**. Broad clinical regression (Visit/Consultation/Emergency/Admission/Lab/Invoice): **713 passed** (3 pre-existing `NhisClaimWorkflowTest` failures — confirmed failing identically with Phase 8 changes stashed, i.e. unrelated to this phase). Migrations clean; blade compiles; localisation parity holds; `git diff --check` clean for all Phase 8 files. Full Laravel/Playwright suites **not** run, per spec.

## 23. Exact operations activated during local verification

Under test only: `consultation.route.complete` set to `typed` with master `active` (E2E integration) — a pay-after arrangement allowed an unpaid OPD item (authority `approved_arrangement`), a pay-before arrangement blocked it, and observe/legacy/kill-switch/emergency all returned legacy. Lab/pharmacy typed decisions verified at the service level with acknowledgement. No operation is left activated (settings default disabled/legacy).

## 24. Confirmation: emergency remains legacy

Emergency visits always fall back to legacy (`PaymentTimingCutoverGateService::isEmergency` → legacy; registry `emergency_supported=false`; eligibility excludes emergency visit type). Verified by `test_emergency_visit_falls_back_to_legacy`.

## 25. Confirmation: unwired operations remain unwired

The nine unwired operations remain ineligible; typed is clamped to legacy for them; the audit command flags typed-on-unwired. Verified.

## 26. Confirmation: financial closure remains unchanged

Typed timing never marks paid, creates payments/waivers/overrides, or changes invoices/receivables/GL/visit-completion/discharge/closure. Financial closure is deferred to Phase 9. Verified (`test_pay_after_allows_unpaid_without_touching_finances`, integration no-write assertions).

## 27. Rollback verification results

Master rollback returns `cutover_mode` to `disabled` immediately (one audit event), approved arrangements untouched; the environment kill switch overrides an active DB setting at runtime. Verified by `test_rollback_requires_permission_and_returns_to_legacy` and `test_environment_kill_switch_forces_legacy`.

## 28. Deferred requirements for Phase 9

- **Financial clearance and closure** rules (visit completion / discharge clearance / receivable settlement) building on the now-operational pay-after/running-bill outcomes.
- Wiring additional (currently unwired) operations behind their own eligibility + compatibility decisions.
- An authoritative emergency stabilisation-vs-post-stabilisation boundary to allow safe emergency typed support.
- Optional expansion of typed missing-context handling once workflow inconsistencies are resolved.
