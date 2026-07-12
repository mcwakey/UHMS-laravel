# UHMS Implementation Prompt — Payment Timing Policy Phase 8

## Controlled Operational Cutover, Approved-Arrangement Precedence, Typed Gate Decisions, and Instant Rollback

You are working on **UHMS**, a Laravel-based hospital management system.

Implement **Phase 8 of the Configurable Payment Timing and Per-Visit Payment Policy system**.

Phases 1–7 are complete.

---

# 1. Existing Foundation

## Phase 1 — Typed configuration

Implemented:

* `VisitPaymentTimingPolicy`
* `VisitPaymentPolicySource`
* Global payment-timing policy
* Visit-type policy configuration
* Database-backed settings
* Admin UI, localisation, permission protection, and audit

## Phase 2 — Legacy integration

Implemented:

* Legacy and observe integration modes
* `VisitPaymentTimingDecision`
* `VisitPaymentTimingResolver`
* Legacy compatibility mapping
* Typed-versus-legacy comparison
* Bounded diagnostics

Legacy decisions remain operationally authoritative.

## Phase 3 — Central payment-gate façade

Implemented:

* `PaymentGateStage`
* `PaymentGateContext`
* Centralised production payment checks through `PaymentGateService`
* Stage-aware observation
* Operation registry and coverage command

Four production hard gates are wired:

```text
consultation.route.complete
consultation.next_patient.readiness
laboratory.result.enter
pharmacy.item.dispense
```

Nine operations remain intentionally unwired.

## Phase 4 — Departmental operation policy

Implemented:

* `PaymentGateOperationMode`
* `MissingBillingContextPolicy`
* `PaymentGateVisitContextRule`
* `PaymentGateOverrideScopeRule`
* Per-operation policy DTO and configuration
* Eligibility and compatibility diagnostics
* Admin configuration and audit

Current operation modes are:

```text
disabled
observe
legacy
```

No typed mode currently exists.

## Phase 5 — Patient financial-risk profiles

Implemented:

* Restricted financial-risk classification
* History and lifecycle
* Permissions and privacy
* Finance worklist and reporting

Financial risk remains separate from live gate decisions.

## Phase 6 — Visit policy materialisation

Implemented:

* Observed baseline policy per visit
* Risk recommendation
* Risk snapshot
* Append-only policy history
* Backfill, refresh, audit, and finance worklist

The baseline and recommendation remain non-operational.

## Phase 7 — Approved visit arrangements

Implemented:

* Per-visit arrangement requests
* Approval, rejection, withdrawal, revocation, expiry, and replacement
* Maker-checker enforcement
* Risk-based approval requirements
* Current approved-arrangement link
* Arrangement history and audit
* Restricted approval worklist

Approved arrangements currently remain administrative only.

---

# 2. Phase 8 Goal

Introduce a safe and reversible mechanism through which:

1. The hospital’s typed global and visit-type payment policies can become operational for specifically approved payment-gate operations.
2. A current approved per-visit arrangement can take precedence over the typed baseline.
3. Existing legacy decisions remain the automatic fallback.
4. Each operation can be activated separately.
5. Activation can be observed before becoming active.
6. An environment-level kill switch can immediately return the entire system to legacy behaviour.
7. Laboratory and pharmacy compatibility changes require explicit acknowledgement.
8. Emergency-sensitive workflows remain on legacy behaviour until an authoritative emergency-stage boundary exists.
9. No unwired operation becomes wired.
10. Financial closure, discharge, and previous-balance enforcement remain outside this phase.

This is the first phase permitted to change production allow/block decisions, but only when administrators explicitly activate the cutover.

Deployment defaults must preserve all existing behaviour.

---

# 3. Operational Decision Hierarchy

For a payment-gate operation that is actively using typed enforcement, resolve the visit policy in this order:

```text
1. Emergency safety restriction or typed-scope exclusion
2. Current operationally eligible approved visit arrangement
3. Existing typed baseline from VisitPaymentTimingResolver
   a. compatible visit-wide legacy override
   b. explicit visit-type policy
   c. global default
4. Legacy decision fallback
```

Important:

* The Phase 6 `resolved_policy` record remains historical and observational.
* Runtime resolution should use current typed configuration rather than blindly trusting a stale snapshot.
* The approved arrangement may override the baseline.
* Risk recommendation alone must never become operational.
* An approved arrangement that is stale, expired, revoked, replaced, inconsistent, or not yet effective must be ignored.
* Any technical failure must return the existing legacy result.

---

# 4. Core Separation

Preserve these distinct concepts:

```text
Observed baseline snapshot
Current runtime typed baseline
Risk recommendation
Approved administrative arrangement
Operational typed policy
Legacy fallback decision
Invoice-item settlement state
Departmental gate operation
```

Do not overwrite the materialised baseline when the operational policy changes.

Do not mark the recommendation as approved.

Do not create a billing override merely to make the arrangement operational.

---

# 5. Mandatory Architecture Audit

Before changing code, audit:

## Runtime payment decisions

* `BillingPolicyService`
* `PaymentGateService`
* `BillingPolicyDecision`
* `BillingPolicyReason`
* `InvoiceItemSettlementService`
* Existing visit billing overrides
* Existing operation-specific compatibility paths
* Existing missing-invoice behaviour
* Existing partial-payment behaviour

## Typed policy

* `VisitPaymentTimingResolver`
* `VisitPaymentTimingDecision`
* `PaymentTimingConfigurationService`
* `PaymentTimingPolicyComparisonService`
* Existing legacy/observe integration mode

## Operation policy

* `PaymentGateOperationRegistry`
* `PaymentGateOperationConfigurationService`
* `PaymentGateEnforcementEligibilityService`
* `PaymentGateOperationCompatibilityService`
* Current admin configuration and audit

## Approved arrangement

* `VisitPaymentArrangement`
* `VisitPaymentArrangementService`
* `VisitPaymentArrangementApprovalPolicyService`
* Current-arrangement link on `VisitPaymentPolicy`
* Risk-staleness logic
* Expiry and replacement handling

## Existing production call sites

Reconfirm the exact runtime behaviour of:

```text
consultation.route.complete
consultation.next_patient.readiness
laboratory.result.enter
pharmacy.item.dispense
```

Document the audit in the Phase 8 report.

---

# 6. Master Cutover Mode

Create:

```text
app/Enums/PaymentTimingCutoverMode.php
```

Required values:

```php
<?php

namespace App\Enums;

enum PaymentTimingCutoverMode: string
{
    case DISABLED = 'disabled';
    case OBSERVE = 'observe';
    case ACTIVE = 'active';
}
```

Meaning:

## `disabled`

* Legacy decisions are returned unchanged.
* Approved arrangements are not queried during normal gate evaluation.
* Typed operational resolution is not performed.
* Existing Phase 2 diagnostics may remain independently available.

## `observe`

* Runtime typed policy is resolved.
* A would-be typed gate decision is calculated.
* Legacy remains authoritative.
* Typed-versus-legacy differences are logged through bounded diagnostics.
* No allow/block outcome changes.

## `active`

* Operations configured for typed mode may return typed decisions.
* Operations configured for legacy remain legacy-authoritative.
* Ineligible or unsupported contexts fall back to legacy.
* Unwired operations remain untouched.

Default:

```text
disabled
```

Invalid values must resolve to `disabled`.

---

# 7. Environment Kill Switch

Add a technical override such as:

```env
PAYMENT_TIMING_FORCE_LEGACY=true
```

or the project’s preferred equivalent.

Rules:

* The environment kill switch must override database settings.
* When enabled, the effective master cutover mode is `disabled`.
* It must not require a database connection to take effect.
* It must not delete or modify approved arrangements.
* It must not rewrite operation settings.
* It must be clearly visible in diagnostics and the admin UI.
* It must provide immediate rollback after configuration cache refresh or the project’s normal deployment process.

Use a safe production default according to project deployment conventions.

Document the exact precedence:

```text
environment force-legacy
→ master cutover setting
→ per-operation mode
→ runtime eligibility
```

---

# 8. Extend Operation Mode Vocabulary

Extend:

```text
PaymentGateOperationMode
```

with:

```php
case TYPED = 'typed';
```

Final values:

```text
disabled
observe
legacy
typed
```

Rules:

* `typed` is valid only for an already wired hard-gate operation.
* `typed` is valid only when the registry approves the operation for typed enforcement.
* `typed` is invalid for the nine unwired operations.
* `typed` must not be accepted when required compatibility acknowledgement is absent.
* Deployment must preserve all four currently wired operations as `legacy`.
* The seeder must never automatically switch an operation to `typed`.

---

# 9. Cutover Configuration Service

Create:

```text
app/Services/Billing/PaymentTimingCutoverConfigurationService.php
```

Suggested methods:

```php
public function configuredMode(): PaymentTimingCutoverMode;

public function effectiveMode(): PaymentTimingCutoverMode;

public function forceLegacy(): bool;

public function operationUsesTypedPolicy(string $operation): bool;

public function operationIsObserveOnly(string $operation): bool;

public function fallbackToLegacyOnFailure(): bool;
```

Rules:

* Return typed values.
* Use existing settings caching.
* Respect the environment kill switch.
* Invalid settings must safely disable cutover.
* Do not query visits, patients, arrangements, invoices, or payments.
* Do not make gate decisions.

Suggested database settings:

```text
payment_timing.cutover_mode
payment_timing.cutover_failure_fallback
payment_timing.cutover_log_decisions
payment_timing.cutover_log_fallbacks
```

Use existing naming conventions where different.

---

# 10. Typed Operation Eligibility

Update the operation registry and eligibility service.

The nine unwired operations remain ineligible.

The following four wired operations may become eligible for typed cutover after their Phase 8 requirements are satisfied:

```text
consultation.route.complete
consultation.next_patient.readiness
laboratory.result.enter
pharmacy.item.dispense
```

Registry metadata should include:

```text
approved_for_typed_enforcement
typed_supported_visit_types
requires_compatibility_acknowledgement
compatibility_change_description
emergency_supported
typed_missing_context_rule
```

Suggested initial scope:

| Operation                           |        Typed eligibility | Suggested supported visit types |
| ----------------------------------- | -----------------------: | ------------------------------- |
| Consultation route completion       |                      Yes | Outpatient                      |
| Consultation next-patient readiness |                      Yes | Outpatient                      |
| Laboratory result entry             | Yes with acknowledgement | Outpatient, inpatient           |
| Pharmacy dispensing                 | Yes with acknowledgement | Outpatient, inpatient           |

Emergency visits must initially fall back to legacy because UHMS still lacks an authoritative stabilisation-versus-post-stabilisation boundary.

Do not falsely mark emergency typed enforcement as safe.

---

# 11. Laboratory Compatibility Decision

Laboratory currently uses an intrinsic settlement compatibility rule.

When its operation remains `legacy`:

* Preserve all current behaviour.

When explicitly changed to `typed` and the master cutover is active:

## `pay_before_service`

Require settlement according to the approved typed pre-service rules.

Allow existing recognised states such as:

```text
paid
fully insured
waived
fully adjusted
approved compatible narrow override
```

according to operation configuration.

## `pay_after_all_services`

Allow result entry before payment.

Do not mark the item paid.

Do not change the invoice.

Do not clear the receivable.

## `running_bill`

Allow result entry while charges remain outstanding.

## Missing invoice item

Preserve the configured operation missing-context behaviour.

Do not silently normalise missing-item handling.

The admin must explicitly acknowledge that typed laboratory mode changes the old intrinsic paid/covered/waived-only behaviour.

---

# 12. Pharmacy Compatibility Decision

Pharmacy currently uses a stricter paid-only dispensing rule.

When its operation remains `legacy`:

* Preserve the paid-only rule exactly.

When explicitly changed to `typed` and master cutover is active:

## `pay_before_service`

Dispensing requires the relevant invoice item to meet pre-service settlement requirements.

## `pay_after_all_services`

Dispensing may proceed before payment.

The charge remains outstanding.

## `running_bill`

Dispensing may proceed while the visit bill accumulates.

## Safety boundaries

Typed payment policy must not bypass:

* Stock availability
* Prescription validity
* Quantity validation
* Clinical dispensing checks
* Controlled-drug requirements
* Cancellation rules
* Existing dispensing concurrency protection

The admin must explicitly acknowledge that typed pharmacy mode relaxes the former paid-only compatibility rule for eligible visits.

Emergency pharmacy dispensing must remain legacy in Phase 8.

---

# 13. Approved-Arrangement Operational Eligibility

Create:

```text
app/Data/Billing/ApprovedArrangementOperationalEligibility.php
```

and:

```text
app/Services/Billing/ApprovedArrangementOperationalEligibilityService.php
```

The service should determine whether the current approved arrangement may influence runtime policy.

Possible outcomes:

```text
eligible
no_current_arrangement
not_yet_effective
expired
revoked
replaced
stale_risk_context
link_mismatch
unsupported_visit_type
unsupported_operation
conflicting_legacy_visit_override
missing_materialised_policy
invalid_approved_policy
```

Eligibility requires:

* Status is `approved`.
* Arrangement is linked as the current approved arrangement.
* Approved policy is a valid operational policy.
* Effective date has been reached.
* Expiry has not passed.
* Arrangement has not been revoked, expired, or replaced.
* Risk context is not stale according to the approved operational rule.
* Visit type is supported for the operation.
* Operation is typed-eligible.
* There is no unresolved conflicting active visit-wide legacy override.

This service is read-only.

It must not update, revoke, or refresh the arrangement.

---

# 14. Stale Approved Arrangements

If the patient’s financial-risk state changes materially after approval:

* The arrangement must not silently remain operational.
* Mark the arrangement as operationally ineligible at runtime.
* Fall back according to the runtime typed baseline or legacy safety rules.
* Show it as requiring finance review in the finance worklist.
* Log a bounded diagnostic event.
* Do not automatically revoke it.
* Do not automatically modify the patient risk profile.
* Do not silently refresh its snapshot.

Provide an authorised review path through the existing arrangement workflow or a focused refresh/reapproval action only if consistent with Phase 7 semantics.

Do not approve stale context implicitly.

---

# 15. Approved-Arrangement Precedence

Create:

```text
app/Services/Billing/OperationalVisitPaymentTimingResolver.php
```

Suggested method:

```php
public function resolve(
    Visit $visit,
    PaymentGateContext $context
): OperationalVisitPaymentTimingDecision;
```

Create a dedicated DTO if appropriate.

Resolution:

1. Resolve the current typed baseline through `VisitPaymentTimingResolver`.
2. Determine whether a current approved arrangement exists.
3. Evaluate arrangement operational eligibility.
4. If eligible, return the approved arrangement policy with source:

   ```text
   approved_arrangement
   ```
5. Otherwise return the current typed baseline.
6. Include fallback and eligibility reason context.

Add to `VisitPaymentPolicySource`:

```php
case APPROVED_ARRANGEMENT = 'approved_arrangement';
```

Do not modify the Phase 6 stored baseline.

Do not make risk recommendation a resolver source.

---

# 16. Legacy Override Conflicts

The existing Phase 2 resolver recognises compatible visit-wide legacy overrides.

An approved arrangement and an active legacy visit-wide override may conflict.

Examples:

```text
Approved arrangement: pay_before_service
Legacy deferred-settlement override: allows deferred payment
```

Rules:

* Do not silently let two policies compete.
* Detect conflicts.
* A conflicting arrangement must be operationally ineligible until resolved.
* Report the conflict in the arrangement UI and audit command.
* Allow matching policies to coexist only if their semantics genuinely match.
* Do not automatically revoke or complete the legacy override.
* Do not create a replacement override.

Narrow department, service, and invoice-item overrides remain governed by the operation’s override-scope rule.

---

# 17. Typed Gate Evaluation Service

Create:

```text
app/Services/Billing/TypedPaymentGateDecisionService.php
```

Responsibilities:

```php
public function evaluate(
    BillingPolicyDecision $legacyDecision,
    Visit $visit,
    ?InvoiceItem $invoiceItem,
    VisitPaymentTimingPolicy $policy,
    PaymentGateOperationPolicy $operationPolicy,
    PaymentGateContext $context
): BillingPolicyDecision;
```

The service must reuse existing settlement and override infrastructure.

Do not create a second balance or accounting calculation.

## `pay_before_service`

Require applicable pre-service settlement.

Recognise operation-approved states such as:

```text
paid
fully insured
waived
fully adjusted
allowed partial-payment threshold
compatible narrow override
```

Do not allow an active visit-wide deferred override to defeat an approved pay-before arrangement unless the conflict was explicitly resolved.

## `pay_after_all_services`

Allow the service to proceed despite unpaid or partially paid patient responsibility.

Do not:

* Mark paid
* Change invoice status
* Remove receivable
* Create payment
* Create override

## `running_bill`

Allow the service to proceed while charges accumulate.

Do not imply financial clearance.

## Non-payment failures

Typed timing policy must not override unrelated workflow failures.

Preserve:

* Missing or invalid clinical prerequisites
* Stock failures
* Missing required bill creation where the operation currently requires billing
* Cancelled service restrictions
* Invalid entities
* Existing non-payment validation

The service changes only payment-timing permission.

---

# 18. Missing Billing Context

Use the Phase 4 operation policy.

For typed operations:

## `preserve_legacy`

Return the legacy result for missing billing context.

## `allow`

Allow only when the operation is explicitly approved for that behaviour.

## `block`

Block only where the operation is wired, typed, and explicitly approved.

## `not_applicable`

Return the appropriate non-payment decision.

Initial typed cutover should preserve existing workflow-specific missing-item behaviour.

Do not use Phase 8 to normalise inconsistencies.

---

# 19. Integrate With PaymentGateService

Integrate the cutover inside the single central façade.

Conceptual flow:

```php
$legacyDecision = $this->billingPolicyService
    ->getInvoiceItemPolicy(...);

$cutoverMode = $this->cutoverConfiguration->effectiveMode();

if ($cutoverMode === PaymentTimingCutoverMode::DISABLED) {
    return $legacyDecision;
}

$operationPolicy = $this->operationConfiguration
    ->policyFor($context->operation);

$operationalPolicy = $this->operationalResolver
    ->resolve($visit, $context);

$typedDecision = $this->typedGateDecisionService
    ->evaluate(
        legacyDecision: $legacyDecision,
        visit: $visit,
        invoiceItem: $invoiceItem,
        policy: $operationalPolicy->policy,
        operationPolicy: $operationPolicy,
        context: $context,
    );

if ($cutoverMode === PaymentTimingCutoverMode::OBSERVE
    || $operationPolicy->mode !== PaymentGateOperationMode::TYPED) {
    $this->comparison->record(...);

    return $legacyDecision;
}

return $typedDecision;
```

Adapt this to actual architecture.

Rules:

* Disabled mode must remain lightweight.
* Observe mode must return the exact legacy decision.
* Active mode only uses typed decisions for operations explicitly configured as `typed`.
* Other operations return legacy.
* Any exception returns legacy.
* Public façade method contracts remain compatible.

---

# 20. Decision Authority Metadata

Extend `BillingPolicyDecision` only if it can be done backward-compatibly.

Suggested additive metadata:

```text
decision_authority
payment_timing_policy
payment_timing_source
operation
cutover_mode
fallback_reason
approved_arrangement_id
```

Possible authority values:

```text
legacy
typed_baseline
approved_arrangement
legacy_fallback
```

Rules:

* Existing consumers must continue working.
* Do not require existing callers to read new fields.
* Do not expose sensitive arrangement or risk information.
* IDs may be included only in restricted diagnostic contexts.

---

# 21. Typed Reason Codes

Add stable machine reason codes according to existing conventions.

Possible reasons:

```text
TYPED_PREPAYMENT_REQUIRED
TYPED_PREPAYMENT_SETTLED
TYPED_PAY_AFTER_SERVICES_ALLOWED
TYPED_RUNNING_BILL_ALLOWED
APPROVED_ARRANGEMENT_PREPAYMENT_REQUIRED
APPROVED_ARRANGEMENT_DEFERRED_SETTLEMENT
APPROVED_ARRANGEMENT_RUNNING_BILL
APPROVED_ARRANGEMENT_INELIGIBLE
TYPED_OPERATION_UNSUPPORTED
TYPED_EMERGENCY_FALLBACK
TYPED_FAILURE_LEGACY_FALLBACK
TYPED_LEGACY_OVERRIDE_CONFLICT
```

Use the current `BillingPolicyReason` architecture if one exists.

User-facing messages must be localised.

---

# 22. Admin Cutover Interface

Add a restricted **Operational Payment Cutover** page or section.

Display:

```text
Configured master mode
Effective master mode
Environment force-legacy status
Legacy fallback status
Eligible operations
Operation mode
Supported visit types
Compatibility warnings
Emergency support
Current cutover blockers
```

## Master controls

Offer:

```text
Disabled
Observe Only
Active
```

Changing to `active` must require:

* Dedicated permission
* Explicit reason
* Confirmation
* Audit entry

## Operation controls

Eligible wired operations may select:

```text
Legacy
Observe
Typed
```

Unwired operations remain unable to select typed.

Laboratory and pharmacy require explicit compatibility acknowledgement.

## Rollback

Provide a prominent action to:

```text
Return all payment operations to legacy authority
```

Rollback must:

* Change the master mode to disabled or equivalent.
* Require a reason.
* Create one audit event.
* Not modify approved arrangements.
* Not delete settings history.
* Not modify invoices or payments.

---

# 23. Cutover Permissions

Add:

```text
billing.payment_timing.cutover.view
billing.payment_timing.cutover.manage
billing.payment_timing.cutover.activate
billing.payment_timing.cutover.rollback
```

Suggested grants:

## Super Admin

All.

## Admin

View, manage, activate, rollback.

## Finance Manager

View and manage operation recommendations, but not activate or rollback by default.

## Accountant

View only, if operationally useful.

## Clinical and reception roles

None.

Backend enforcement is mandatory.

Do not rely only on the UI.

---

# 24. Cutover Audit Logging

Use existing `ActivityLog`.

Suggested actions:

```text
PAYMENT_TIMING_CUTOVER_MODE_CHANGED
PAYMENT_GATE_TYPED_OPERATION_ENABLED
PAYMENT_GATE_TYPED_OPERATION_DISABLED
PAYMENT_TIMING_FORCE_LEGACY_ROLLBACK
PAYMENT_TIMING_CUTOVER_SETTINGS_UPDATED
```

Capture:

```text
old master mode
new master mode
operation
old operation mode
new operation mode
supported visit types
compatibility acknowledgement
reason
actor
timestamp
```

Do not include patient data.

Routine gate decisions must not create activity logs.

---

# 25. Runtime Diagnostics

Use bounded application diagnostics rather than activity logs.

Suggested events:

```text
payment_timing_typed_decision_observed
payment_timing_typed_decision_applied
payment_timing_legacy_fallback
payment_timing_arrangement_ineligible
payment_timing_override_conflict
payment_timing_emergency_scope_fallback
```

Rules:

* Do not log every successful decision by default.
* Log fallbacks, mismatches, and cutover anomalies.
* Deduplicate repeated events where possible.
* Exclude patient names, contacts, diagnoses, and free-text reasons.
* Logging failure must never affect the gate.

---

# 26. Cutover Status Command

Add:

```bash
php artisan billing:payment-timing-cutover-status
```

Suggested options:

```text
--operation=
--visit-type=
--typed-only
--problems-only
--json
```

Output:

```text
configured master mode
effective master mode
force-legacy status
operation
wired status
operation mode
typed eligibility
supported visit types
compatibility acknowledgement
emergency support
fallback configuration
```

Read-only.

No activity logs.

Warnings do not fail the exit code.

---

# 27. Cutover Audit Command

Add:

```bash
php artisan billing:payment-timing-cutover-audit
```

Suggested options:

```text
--operation=
--visit=
--active-only
--problems-only
--limit=
--json
```

Detect:

```text
master active while force-legacy is enabled
typed mode on an unwired operation
typed mode on an ineligible operation
missing compatibility acknowledgement
unsupported emergency typed use
unsupported visit type
approved arrangement stale
approved arrangement expired but linked
approved arrangement link mismatch
conflicting active legacy visit-wide override
resolved inherit policy
typed operation missing invoice resolution
active typed mode without rollback fallback
```

Read-only.

No automatic repair.

Findings do not fail the exit code.

---

# 28. Typed Decision Preview Command

Add a read-only preview command if it can safely use an existing invoice item:

```bash
php artisan billing:payment-timing-cutover-preview \
    --visit=123 \
    --operation=consultation.route.complete \
    --invoice-item=456
```

Output:

```text
legacy decision
typed baseline policy
current approved arrangement
arrangement eligibility
operational typed policy
would-be typed decision
comparison outcome
effective runtime authority
fallback reason
```

Rules:

* No service state mutation.
* No invoice or payment creation.
* No override creation.
* No activity log.
* No patient-sensitive output.
* Missing context should be reported, not invented.

If a safe preview cannot be implemented for every operation, support only operations with a concrete invoice-item context.

---

# 29. Existing Visit Policies and Arrangements

Do not rewrite Phase 6 or Phase 7 records during cutover activation.

Existing approved arrangements may become operational dynamically if:

* They remain current.
* They are eligible.
* Their effective date has arrived.
* Their expiry has not passed.
* Their context is not stale.
* Their operation and visit type are supported.
* The master and operation cutover settings allow it.

Do not require a new approval solely because cutover was activated unless hospital policy explicitly requires reapproval.

The cutover audit must list older arrangements that are not operationally eligible.

---

# 30. Visit Lifecycle Rules

## Active visit

Eligible for typed decisions.

## Completed visit

No new service-gate decision should normally occur unless reopened.

Do not retroactively change completed service outcomes.

## Reopened visit

Use the current approved arrangement only if it remains valid and current.

## Cancelled visit

Typed service enforcement should not run.

## Inpatient visit

Typed laboratory and pharmacy operations may use `running_bill` or an approved arrangement where the operation supports inpatient cutover.

## Emergency visit

Always fall back to legacy during Phase 8.

Do not claim stage-specific emergency typed support.

---

# 31. Risk-Patient Behaviour

Risk recommendation remains non-operational by itself.

To force prepayment for a known risk patient:

1. A financial-risk profile exists.
2. The visit records a recommendation.
3. An authorised per-visit `pay_before_service` arrangement is requested.
4. The arrangement is approved.
5. The arrangement is operationally eligible.
6. Master cutover is active.
7. The relevant operation is configured as typed.

Do not skip the approval workflow.

Do not automatically create or approve the arrangement.

A high-risk or blocked-credit patient without an approved arrangement continues to use the typed baseline or legacy fallback.

---

# 32. Pay-After-All-Services Behaviour

For an eligible visit with:

```text
pay_after_all_services
```

the typed gate may allow the currently wired service operation to proceed before payment.

Mandatory rules:

* The billable item must still be recorded according to the workflow’s missing-context rule.
* The charge remains on the existing invoice.
* Patient responsibility remains outstanding.
* The system must not mark the item paid.
* The system must not close the receivable.
* The system must not create a waiver.
* The system must not create a payment.
* The system must not create a billing override.
* Financial settlement remains required later according to future closure policy.

This phase does not implement final financial closure.

---

# 33. Pay-Before-Service Behaviour

For an eligible visit with:

```text
pay_before_service
```

the typed gate must require applicable settlement before proceeding.

It must:

* Use existing invoice-item financial state.
* Respect allowed insurance, waiver, adjustment, partial-payment, and narrow-override rules from operation policy.
* Preserve missing-context rules.
* Return a localised payment-required message.
* Provide existing cashier or invoice action metadata where available.
* Avoid duplicate invoice or balance calculations.

A restrictive arrangement may block an action that the typed baseline would otherwise allow.

---

# 34. Running-Bill Behaviour

For an eligible visit with:

```text
running_bill
```

the typed gate may allow the operation while charges accumulate.

It must not imply:

* Payment
* Financial clearance
* Discharge clearance
* Invoice closure
* Debt waiver

Inpatient running-bill support may be enabled only for operations whose registry scope includes inpatient visits.

---

# 35. Previous-Balance Policy

Previous-balance enforcement remains separate.

Rules:

* Typed service policy does not automatically bypass previous debt.
* An approved pay-after-services arrangement does not create a previous-balance override.
* An existing previous-balance override does not become an approved visit arrangement.
* Registration or OPD prior-debt checks retain existing behaviour.
* Report both decisions separately where useful.

Do not merge the two policies in Phase 8.

---

# 36. Financial Closure

Do not implement final financial closure in this phase.

An approved and operational:

```text
pay_after_all_services
```

or:

```text
running_bill
```

arrangement may allow services to proceed, but final settlement remains a separate future concern.

Do not alter:

* Visit completion
* Admission discharge
* Administrative closure
* Invoice closure
* Receivable status

Phase 9 should implement financial-clearance and closure rules.

---

# 37. Performance Requirements

## Disabled mode

Must add no arrangement, visit-policy, or typed-operation database queries to normal gate evaluation.

## Observe mode

May add only bounded queries:

* Current approved arrangement, preferably through already-loaded relations
* Existing cached settings
* Current eligibility context

Use request-scoped memoisation by visit and operation.

## Active mode

Must avoid:

* Repeated arrangement queries for several items in the same visit
* Repeated risk-staleness queries
* Repeated settings queries
* Duplicate invoice-item settlement calculation
* N+1 queries in service loops

Use:

* Eager loading
* Existing settings cache
* Resolver memoisation
* Already-loaded invoice items
* Bounded operation context

Add focused query assertions.

---

# 38. Failure Safety

Any failure in:

```text
cutover configuration
operational resolver
arrangement eligibility
typed gate evaluation
comparison logging
```

must return the legacy decision.

Do not:

* Default to allow
* Default to typed block
* Expose internal exceptions to users
* Roll back unrelated clinical work
* Modify approved arrangements
* Modify invoices

The fallback reason should be available to restricted diagnostics.

---

# 39. Admin Validation

Create or extend FormRequests.

Validate:

* Master mode is valid.
* Typed operation exists.
* Operation is wired.
* Operation is typed-eligible.
* Visit-type scope is supported.
* Required compatibility acknowledgement is present.
* Emergency unsupported operations cannot be marked emergency-active.
* Activation requires a reason.
* Rollback requires a reason.
* Unknown and duplicate operations are rejected.
* Invalid mixed updates remain transactional.
* Unauthorised endpoint calls fail.

Do not allow UI manipulation to bypass registry safety.

---

# 40. Seeding and Deployment Defaults

Update idempotent settings seeding.

Deployment defaults:

```text
Master cutover: disabled
All four wired operations: legacy
Nine unwired operations: disabled
Force-legacy fallback: enabled according to environment policy
Typed compatibility acknowledgement: false
```

Rules:

* Never overwrite administrator settings.
* Never activate typed mode during seeding.
* Never enable master active mode during seeding.
* Repeated seeding remains idempotent.

No patient, visit, arrangement, invoice, or payment data should be seeded.

---

# 41. Localisation

Add complete English and French localisation.

Suggested concepts:

```text
Operational Payment Cutover
Configured Mode
Effective Mode
Force Legacy
Disabled
Observe Only
Active
Typed Policy
Legacy Authority
Typed Baseline
Approved Arrangement
Legacy Fallback
Compatibility Acknowledgement
Emergency Not Supported
Unsupported Visit Type
Arrangement Context Stale
Legacy Override Conflict
Prepayment Required
Payment After Services Allowed
Running Bill Allowed
Immediate Rollback
Return All Operations to Legacy
Cutover Reason
Decision Authority
```

Also localise:

* Typed reason messages
* Validation
* Admin warnings
* Diagnostic labels
* Success and rollback feedback

Maintain EN/FR parity.

---

# 42. Tests

Do not run the full Laravel or Playwright suites during this phase.

## 42.1 Enum and configuration tests

Verify:

* Cutover modes exist.
* `typed` operation mode exists.
* Default master mode is disabled.
* Invalid master mode becomes disabled.
* Environment force-legacy overrides database active mode.
* Seeder does not activate cutover.
* Disabled mode avoids operational queries.

## 42.2 Registry and eligibility tests

Verify:

* Nine unwired operations remain ineligible.
* Four wired operations have explicit typed metadata.
* Laboratory and pharmacy require acknowledgement.
* Emergency is unsupported.
* Unsupported visit types fall back to legacy.
* Typed mode cannot be saved for an ineligible operation.

## 42.3 Operational resolver tests

Verify precedence:

* Eligible approved arrangement overrides typed baseline.
* Typed baseline applies without an arrangement.
* Risk recommendation alone does not override baseline.
* Expired arrangement is ignored.
* Revoked arrangement is ignored.
* Replaced arrangement is ignored.
* Future arrangement is ignored until effective.
* Stale arrangement is ignored.
* Link mismatch is ignored.
* Conflicting legacy visit-wide override prevents arrangement use.
* Source is `approved_arrangement` where applicable.
* Final policies never contain `inherit`.

## 42.4 Typed gate decision tests

### Pay before

* Unpaid item blocks.
* Paid item allows.
* Fully insured item follows operation policy.
* Waived item follows operation policy.
* Fully adjusted item follows operation policy.
* Allowed partial-payment threshold works.
* Narrow compatible override works.
* Missing billing context preserves configured behaviour.

### Pay after all services

* Unpaid item may proceed.
* Partially paid item may proceed.
* Invoice and receivable remain unchanged.
* No payment or override is created.

### Running bill

* Eligible operation may proceed unpaid.
* Invoice remains outstanding.
* No financial-clearance state is created.

## 42.5 Gate integration tests

Verify:

* Disabled mode returns the exact legacy decision.
* Observe mode returns the exact legacy decision.
* Active mode plus legacy operation returns legacy.
* Active mode plus typed operation returns typed decision.
* Kill switch returns legacy.
* Runtime exception returns legacy.
* Decision authority metadata is accurate.
* Existing façade contracts remain compatible.

## 42.6 Approved arrangement tests

Verify:

* Approved pay-after arrangement allows an eligible unpaid operation.
* Approved pay-before arrangement blocks an eligible unpaid operation.
* Approved running-bill arrangement allows an eligible operation.
* Stale arrangement falls back safely.
* Expired arrangement falls back safely.
* Arrangement approval does not itself activate cutover.
* Cutover activation does not rewrite arrangement records.

## 42.7 Laboratory tests

Verify:

* Legacy mode preserves old settlement behaviour.
* Observe mode preserves old result.
* Typed pay-before requires settlement.
* Typed pay-after permits unpaid result entry.
* Typed running-bill permits result entry.
* Missing-item handling remains configured.
* No invoice/payment mutation occurs.

## 42.8 Pharmacy tests

Verify:

* Legacy mode remains paid-only.
* Observe mode remains paid-only.
* Typed pay-before remains settlement-required.
* Typed pay-after permits unpaid dispensing after all non-payment pharmacy checks pass.
* Typed running-bill permits unpaid dispensing for supported inpatient visits.
* Stock, prescription, quantity, and clinical checks remain enforced.
* Emergency remains legacy.
* No payment, waiver, override, or receivable mutation occurs.

## 42.9 Admin and permission tests

Verify:

* Cutover page is restricted.
* Finance Manager cannot activate by default.
* Admin can activate.
* Admin can roll back.
* Activation requires reason.
* Typed lab/pharmacy require acknowledgement.
* Unwired operations reject typed mode.
* Every change creates one activity log.
* Reads create no activity logs.

## 42.10 Rollback tests

Verify:

* Master rollback immediately returns all operations to legacy.
* Per-operation rollback affects only that operation.
* Approved arrangements remain intact.
* Existing invoices and visits remain unchanged.
* Environment force-legacy overrides an active database setting.
* Rollback is auditable.

## 42.11 Command tests

Verify:

* Status command reports configured and effective mode.
* Audit command detects unsafe configurations.
* Preview command compares legacy and typed decisions safely.
* JSON outputs are valid.
* Commands perform no writes.
* Findings do not fail the exit code.

## 42.12 Performance tests

Verify:

* Disabled mode adds no arrangement query.
* Observe/active mode memoises arrangement lookup.
* Multiple invoice items for one visit do not create N+1 arrangement queries.
* Settings remain cached.
* Typed evaluation does not duplicate invoice settlement queries.

## 42.13 Regression tests

Verify:

* Nine unwired operations remain unwired.
* Previous-balance behaviour remains unchanged.
* Visit arrangement approval workflow remains unchanged.
* Patient-risk lifecycle remains unchanged.
* Materialised policy history remains unchanged.
* Emergency remains legacy.
* Financial closure remains unchanged.
* Visit completion and discharge remain unchanged.

## 42.14 Localisation tests

Verify English/French parity.

---

# 43. Targeted Verification

Run focused checks only.

Suggested commands:

```bash
php artisan migrate
php artisan route:list
php artisan config:clear
php artisan view:clear
php artisan view:cache

php artisan test --filter=PaymentTimingCutover
php artisan test --filter=OperationalVisitPaymentTimingResolver
php artisan test --filter=TypedPaymentGateDecision
php artisan test --filter=PaymentGateTypedIntegration
php artisan test --filter=PaymentTimingCutoverPermission
php artisan test --filter=PaymentTimingCutoverRollback
php artisan test --filter=PaymentTimingCutoverCommand
php artisan test --filter=PaymentTimingCutoverPerformance
```

Rerun:

```bash
php artisan test --filter=VisitPaymentArrangement
php artisan test --filter=VisitPaymentPolicy
php artisan test --filter=PatientFinancialRisk
php artisan test --filter=PaymentTiming
php artisan test --filter=PaymentGateService
php artisan test --filter=BillingPaymentPolicy
php artisan test --filter=PreviousBalancePolicy
php artisan test --filter=TriagePayment
php artisan test --filter=ConsultationPaymentReadiness
php artisan test --filter=LaboratoryPaymentGate
php artisan test --filter=Pharmacy
```

Run diagnostics:

```bash
php artisan billing:payment-timing-cutover-status
php artisan billing:payment-timing-cutover-audit
php artisan billing:payment-timing-cutover-preview \
    --visit=<visit-id> \
    --operation=<operation> \
    --invoice-item=<invoice-item-id>

php artisan billing:visit-payment-arrangement-audit
php artisan billing:visit-payment-policy-audit
php artisan billing:payment-gate-coverage
php artisan billing:payment-gate-policy-audit
```

Also run:

```text
PHP syntax checks
Blade compilation
localisation parity
permission audit
activity-log integrity check
git diff --check
```

Do not run the full Laravel suite.

Do not run the full Playwright suite.

---

# 44. Documentation

Create:

```text
docs/billing/PAYMENT_TIMING_POLICY_PHASE_8_REPORT.md
```

The report must include:

1. Existing runtime gate architecture audited
2. Cutover-mode design
3. Environment kill-switch precedence
4. Files created
5. Files modified
6. Operation eligibility and visit-type scope
7. Laboratory compatibility decision
8. Pharmacy compatibility decision
9. Approved-arrangement operational eligibility
10. Approved-arrangement precedence
11. Legacy override conflict handling
12. Typed gate decision rules
13. Missing-context handling
14. PaymentGateService integration
15. Admin activation and rollback UI
16. Permissions and role assignments
17. Activity-log behaviour
18. Runtime diagnostics
19. Status, audit, and preview commands
20. Deployment defaults
21. Query and performance impact
22. Focused tests and results
23. Exact operations activated during local verification
24. Confirmation that emergency remains legacy
25. Confirmation that unwired operations remain unwired
26. Confirmation that financial closure remains unchanged
27. Rollback verification results
28. Deferred requirements for Phase 9

Do not claim tests passed unless they were actually executed.

---

# 45. Guardrails

Do not:

* Enable cutover by default
* Seed typed mode
* Ignore the environment kill switch
* Make risk recommendations operational without an approved arrangement
* Auto-create or auto-approve arrangements
* Modify approved arrangement history
* Rewrite materialised baseline records
* Create billing overrides
* Merge previous-balance policy with payment timing
* Wire any of the nine unwired operations
* Enable emergency typed enforcement
* Invent an emergency stabilisation field
* Bypass pharmacy clinical or stock checks
* Mark unpaid services as paid
* Remove receivables
* Create payments
* Create waivers
* Change GL posting
* Change visit completion
* Change discharge
* Change financial closure
* Audit every gate read
* Expose patient-sensitive data in diagnostics
* Run broad test suites

---

# 46. Acceptance Criteria

Phase 8 is complete only when:

* A master cutover mode exists with disabled, observe, and active states.
* Deployment defaults to disabled.
* An environment force-legacy kill switch exists and wins over database settings.
* `PaymentGateOperationMode` supports typed mode.
* Typed mode is restricted to eligible wired operations.
* The nine unwired operations remain unwired and ineligible.
* Laboratory and pharmacy require compatibility acknowledgement.
* Emergency typed enforcement remains unsupported.
* A current approved arrangement can take precedence over the typed baseline.
* Risk recommendation alone remains non-operational.
* Stale, expired, revoked, replaced, future, or inconsistent arrangements are ignored.
* Legacy override conflicts are detected.
* A typed gate-decision service exists.
* Pay-before service can block an unpaid eligible operation.
* Pay-after-all-services can allow an unpaid eligible operation.
* Running bill can allow an eligible operation while charges remain outstanding.
* Missing-context behaviour remains operation-specific.
* Typed timing never marks anything paid.
* Typed timing never creates a payment, waiver, receivable change, or billing override.
* Disabled and observe modes return exact legacy outcomes.
* Active typed mode changes only explicitly activated operations.
* All failures fall back to legacy.
* Admin activation and rollback are permission-controlled and audited.
* Master rollback immediately returns the system to legacy authority.
* Approved arrangements survive rollback unchanged.
* Status, audit, and preview commands are read-only.
* Emergency behaviour remains unchanged.
* Previous-balance behaviour remains unchanged.
* Financial closure remains unchanged.
* English/French localisation is complete.
* Focused tests pass.
* The Phase 8 report accurately documents cutover and rollback verification.

Proceed with **Payment Timing Policy Phase 8 only**.

After implementation, provide:

1. A concise implementation summary
2. Files created and modified
3. Cutover-mode and kill-switch design
4. Typed operation eligibility
5. Approved-arrangement precedence
6. Laboratory and pharmacy cutover behaviour
7. Typed gate-decision rules
8. Admin activation and rollback controls
9. Permissions and audit behaviour
10. Diagnostic command results
11. Query and performance impact
12. Focused test results
13. Exact local active-cutover scenarios verified
14. Rollback verification
15. Confirmation that emergency and unwired operations remain legacy
16. Confirmation that financial closure remains unchanged
17. The Phase 8 report path
18. Recommended requirements for Phase 9

Then stop after Phase 8.
