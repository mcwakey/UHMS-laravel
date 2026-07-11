# UHMS Implementation Prompt — Payment Timing Policy Phase 2

## Existing Billing-Policy Integration, Precedence, Source Reporting, and Observation Mode

You are working on **UHMS**, a Laravel-based hospital management system.

Implement **Phase 2 of the Configurable Payment Timing and Per-Visit Payment Policy system**.

Phase 1 has already introduced:

* `VisitPaymentTimingPolicy`
* `VisitPaymentPolicySource`
* `config/payment_timing.php`
* Database-backed payment-timing settings
* `PaymentTimingConfigurationService`
* Admin payment-timing settings
* English and French localisation
* Settings permission protection
* Transactional settings updates
* Activity logging
* Compatibility-safe defaults

Phase 1 deliberately did not modify the existing service-payment gate.

The repository already contains a mature billing-policy implementation, including:

```text
config/billing_policy.php
BillingPolicyService
PaymentGateService
VisitBillingOverrideService
PatientOutstandingBalanceService
PreviousBalanceOverrideService
InvoiceReceivable
InvoiceReceivableService
PaymentService
```

The goal of Phase 2 is to connect the new typed payment-timing vocabulary to that existing infrastructure without creating a second billing-policy engine and without changing current production enforcement behaviour.

---

# 1. Primary Goal

Establish one coherent payment-policy architecture in which:

* The new typed payment-timing settings become the future policy vocabulary.
* Existing billing-policy and payment-gate behaviour remains operational.
* Existing service workflows continue using the current gate.
* Typed policy decisions can be resolved and explained.
* Legacy and typed decisions can be compared safely.
* Disagreements can be diagnosed before enforcement is switched over.
* The system has an explicit precedence model.
* Future patient-risk, insurance, corporate, and per-visit policy phases have a clear extension point.

This phase is an **integration and observation phase**.

It must not yet introduce new payment blocking, relax existing blocking, or alter clinical and departmental workflow outcomes.

---

# 2. Mandatory Existing-Architecture Audit

Before modifying code, inspect the current implementation in detail.

Audit:

## 2.1 `BillingPolicyService`

Determine:

* Its public methods
* Its policy vocabulary
* Whether it returns booleans, modes, DTOs, or arrays
* How it reads `config/billing_policy.php`
* Whether it distinguishes advisory and enforcing behaviour
* How it handles visit types
* How it handles emergency visits
* How it handles admission or inpatient visits
* How it handles patient responsibility
* How it handles previous balances
* How it handles insurance or sponsor responsibility
* Whether it directly queries invoices or delegates those queries

## 2.2 `PaymentGateService`

Determine:

* Every public gate method
* Every return type
* Every decision reason currently exposed
* Every place where it blocks or advises
* Whether enforcement occurs before ordering, acceptance, performance, dispensing, completion, or another stage
* How it detects fully paid, partially paid, unpaid, insured, sponsored, or zero-patient-responsibility services
* How it handles missing invoices
* How it handles unpriced services
* How it handles emergency and inpatient cases
* How it uses billing overrides
* Whether callers depend on exact message strings, codes, arrays, or DTO fields

## 2.3 Existing gate call sites

Find every call site across:

* Consultations
* Investigations
* Laboratory
* Radiology
* Pharmacy
* Procedures
* Theatre
* Treatment
* Nursing
* Admission and inpatient
* Emergency
* Blood bank
* Ambulance
* Service rendering
* Visit completion
* Discharge
* Billing and cashier flows

Create an inventory containing:

```text
caller
gate method
workflow stage
expected return shape
current blocking behaviour
current override behaviour
```

## 2.4 Existing override infrastructure

Inspect:

```text
VisitBillingOverrideService
PreviousBalanceOverrideService
related models and migrations
permissions
approval rules
expiry rules
audit logging
```

Determine whether existing overrides mean:

* Ignore a previous balance
* Allow a particular service
* Allow the entire visit to proceed
* Allow financial closure
* Allow a credit arrangement
* Something else

Do not assume all existing overrides are equivalent to a visit payment-timing override.

## 2.5 Current feature flags

Identify all existing flags related to:

```text
billing_policy.enforce
billing-policy advisory behaviour
payment gates
previous-balance blocking
emergency bypass
admission billing
service-level payment checks
```

Document which flag currently controls production behaviour.

---

# 3. Architectural Principle: One Gate, One Vocabulary

UHMS must not end up with:

```text
old billing policy
+
new payment timing policy
+
module-specific payment checks
```

as three independent sources of truth.

The intended architecture is:

```text
PaymentTimingConfigurationService
            ↓
Typed payment-timing decision layer
            ↓
Existing BillingPolicyService / PaymentGateService integration
            ↓
Existing departmental call sites
```

Existing departmental modules should continue calling the established payment gate.

Do not make every module read payment-timing settings directly.

Do not add new payment-condition checks directly inside controllers.

Do not duplicate invoice or receivable calculations.

---

# 4. Integration Rollout Mode

Add an explicit technical rollout mode.

This should preferably remain configuration- or environment-controlled rather than being exposed as an ordinary hospital administration option.

Suggested enum:

```text
app/Enums/PaymentTimingIntegrationMode.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum PaymentTimingIntegrationMode: string
{
    case LEGACY = 'legacy';
    case OBSERVE = 'observe';
}
```

## 4.1 Legacy mode

In `legacy` mode:

* Current `BillingPolicyService` and `PaymentGateService` behaviour remains authoritative.
* Typed payment-timing decisions may be available to diagnostic code.
* Typed decisions must not change allow/block outcomes.
* No comparison logging is required on every request.

## 4.2 Observe mode

In `observe` mode:

* Current legacy gate behaviour remains authoritative.
* A typed payment-timing decision is also calculated.
* The system compares the typed expectation with the legacy outcome.
* A disagreement is recorded through bounded diagnostic logging or metrics.
* The user-facing allow/block result remains the legacy result.
* No service outcome changes.

## 4.3 Configuration

Extend `config/payment_timing.php` safely:

```php
'integration' => [
    'mode' => env('PAYMENT_TIMING_INTEGRATION_MODE', 'legacy'),
    'log_mismatches' => env(
        'PAYMENT_TIMING_LOG_MISMATCHES',
        true
    ),
    'log_matches' => env(
        'PAYMENT_TIMING_LOG_MATCHES',
        false
    ),
],
```

Adjust this structure to existing project conventions where needed.

Rules:

* Default mode must be `legacy`.
* Invalid integration modes must fall back to `legacy`.
* Do not add an `active` enforcement mode in this phase unless it is represented as an unreachable placeholder with no production path.
* Do not switch the application to typed enforcement.
* Do not expose technical rollout controls to ordinary settings users.

Extend `PaymentTimingConfigurationService` with typed accessors such as:

```php
public function integrationMode(): PaymentTimingIntegrationMode;

public function logsIntegrationMismatches(): bool;

public function logsIntegrationMatches(): bool;
```

---

# 5. Typed Payment-Timing Decision DTO

Create a reusable typed decision object.

Suggested location:

```text
app/Data/Billing/VisitPaymentTimingDecision.php
```

or the project’s established DTO namespace.

Suggested shape:

```php
final readonly class VisitPaymentTimingDecision
{
    public function __construct(
        public VisitPaymentTimingPolicy $policy,
        public VisitPaymentPolicySource $source,
        public string $reasonCode,
        public array $context = [],
    ) {
    }

    public function allowsServiceBeforePayment(): bool
    {
        return in_array(
            $this->policy,
            [
                VisitPaymentTimingPolicy::PAY_AFTER_ALL_SERVICES,
                VisitPaymentTimingPolicy::RUNNING_BILL,
            ],
            true
        );
    }

    public function requiresPreServicePayment(): bool
    {
        return $this->policy
            === VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE;
    }
}
```

Adapt the method names to existing conventions.

The decision must expose:

```text
effective policy
policy source
machine-readable reason code
structured context
```

Do not store translated text as the canonical reason.

Use reason codes such as:

```text
emergency_policy
visit_type_policy
global_default
legacy_override
legacy_gate_compatibility
invalid_configuration_fallback
```

Translations may later convert reason codes into user-facing explanations.

---

# 6. Phase 2 Typed Resolver

Create a limited typed resolver.

Suggested location:

```text
app/Services/Billing/VisitPaymentTimingResolver.php
```

This resolver is not yet the final full resolver.

It should resolve only the information available in Phase 2:

1. Emergency safety policy
2. Existing compatible visit billing override, where semantically valid
3. Visit-type payment-timing configuration
4. Global payment-timing configuration

The following are explicitly deferred:

* Patient financial-risk profiles
* Risk-based policy forcing
* Insurance policy resolution
* Sponsor policy resolution
* Corporate-account policy resolution
* New visit-level policy persistence
* New manual payment-timing override requests
* Financial-closure enforcement

## 6.1 Required resolver method

Suggested signature:

```php
public function resolve(Visit $visit): VisitPaymentTimingDecision;
```

Where useful, allow a context argument without making callers construct arbitrary arrays:

```php
public function resolve(
    Visit $visit,
    ?PaymentTimingResolutionContext $context = null
): VisitPaymentTimingDecision;
```

Do not require invoice calculations merely to resolve the configured timing policy.

## 6.2 Resolution precedence in this phase

Use:

```text
1. Emergency safety policy
2. Existing compatible active visit billing override
3. Explicit visit-type setting
4. Global default
```

However, only map an existing billing override to `manual_override` if the audit proves that its semantics genuinely allow the visit or service to continue without prior payment.

Do not reinterpret a previous-balance-only override as a complete visit payment-timing override.

If an override is narrower than the new policy concept:

* Preserve its current behaviour.
* Represent it in diagnostic context.
* Do not claim that it changes the full visit timing policy.

## 6.3 Emergency policy

If:

```php
$configuration->neverBlockEmergencyStabilisation()
```

and the visit is an emergency visit, return:

```text
policy: running_bill
source: emergency_policy
reason: emergency_stabilisation_protection
```

This typed decision is advisory in Phase 2.

Do not broaden the meaning of “emergency stabilisation” beyond existing emergency workflow boundaries.

If the existing gate currently distinguishes stabilisation from later non-emergency services, preserve that distinction in the compatibility layer.

## 6.4 Visit-type configuration

Use the canonical existing `VisitType` enum.

Do not compare arbitrary strings where the model already casts visit type to an enum.

When the visit-type setting contains `inherit`, resolve it through the global operational default.

The final typed decision must never contain `inherit`.

---

# 7. Legacy-to-Typed Compatibility Mapper

Create a dedicated mapper between legacy billing concepts and typed payment-timing concepts.

Suggested location:

```text
app/Services/Billing/PaymentTimingLegacyCompatibilityService.php
```

Responsibilities:

* Interpret relevant legacy billing-policy configuration.
* Describe the existing gate’s expected timing behaviour.
* Map compatible legacy modes to `VisitPaymentTimingPolicy`.
* Preserve legacy reason codes and metadata.
* Detect concepts that cannot be mapped safely.
* Avoid changing gate results.

Possible method shapes:

```php
public function expectedPolicyForLegacyContext(
    Visit $visit,
    mixed $legacyDecision = null
): ?VisitPaymentTimingPolicy;
```

```php
public function describeLegacyDecision(
    Visit $visit,
    mixed $legacyDecision
): LegacyPaymentGateSnapshot;
```

Use a small typed snapshot DTO if needed.

The compatibility service must not become another gate.

Its purpose is mapping and diagnostics.

---

# 8. Payment Gate Integration

Integrate typed decisions with the existing `PaymentGateService` without breaking its public contract.

## 8.1 Preserve callers

Do not require every existing caller to change unless the existing gate contract is already inconsistent and a backward-compatible wrapper can be provided.

Preserve:

* Existing method names
* Existing return shapes
* Existing reason or error codes
* Existing user-facing messages
* Existing redirect or action metadata
* Existing exception behaviour

## 8.2 Integration point

The preferred integration is inside the existing gate or its existing policy dependency.

Conceptually:

```php
$legacyDecision = $this->evaluateLegacyGate(...);

if ($this->paymentTimingConfiguration->integrationMode()
    === PaymentTimingIntegrationMode::OBSERVE) {
    $typedDecision = $this->paymentTimingResolver->resolve($visit);

    $this->comparisonService->compare(
        legacyDecision: $legacyDecision,
        typedDecision: $typedDecision,
        context: $context,
    );
}

return $legacyDecision;
```

Do not return the typed decision to operational callers in this phase unless it is additive metadata that cannot alter existing consumer behaviour.

## 8.3 No behavioural changes

The following must remain unchanged:

```text
allowed remains allowed
blocked remains blocked
advisory remains advisory
override remains effective exactly as before
payment calculation remains unchanged
patient-responsibility calculation remains unchanged
emergency behaviour remains unchanged
```

Where current behaviour appears incorrect, document it as a legacy mismatch.

Do not silently fix it inside this integration phase unless it is an obvious regression caused by Phase 2 itself.

---

# 9. Typed vs Legacy Comparison Service

Create a comparison service.

Suggested location:

```text
app/Services/Billing/PaymentTimingPolicyComparisonService.php
```

Suggested responsibilities:

```php
public function compare(
    Visit $visit,
    mixed $legacyDecision,
    VisitPaymentTimingDecision $typedDecision,
    PaymentGateContext $context
): PaymentTimingPolicyComparison;
```

Possible comparison outcomes:

```text
match
legacy_more_restrictive
typed_more_restrictive
not_comparable
missing_context
```

Create a typed result DTO or enum.

Suggested enum:

```text
app/Enums/PaymentTimingComparisonOutcome.php
```

## 9.1 Comparison meaning

Examples:

### Match

Legacy gate requires payment and typed policy is `pay_before_service`.

### Legacy more restrictive

Legacy gate blocks the service, while typed policy says:

```text
pay_after_all_services
```

or:

```text
running_bill
```

### Typed more restrictive

Legacy gate allows the service, while typed policy says:

```text
pay_before_service
```

This must not cause a new block in Phase 2.

### Not comparable

The legacy gate decision is based on:

* No invoice yet
* An unpriced service
* An insurer-only amount
* A previous-balance exception
* A service-stage rule that payment timing alone cannot represent

Do not force false equivalence.

## 9.2 Comparison context

Capture only non-sensitive diagnostic context:

```text
visit id
visit type
gate operation
department type
typed policy
typed source
legacy allow/block/advisory outcome
comparison outcome
legacy reason code
typed reason code
feature mode
```

Do not log:

* Clinical notes
* Diagnosis
* Patient names
* Phone numbers
* Email addresses
* Insurance member numbers
* Free-text financial-risk reasons
* Unnecessary patient identifiers

Use visit IDs or hashed identifiers according to existing diagnostic conventions.

---

# 10. Diagnostic Logging and Noise Control

Observation mode must not flood the activity log or application logs.

## 10.1 Do not use `ActivityLog` for routine reads

Payment-policy resolution and gate comparisons are read decisions.

Do not create an `ActivityLog` row on every gate check.

Continue using `ActivityLog` only for mutations and material overrides.

## 10.2 Application diagnostic channel

Use the existing application logging architecture.

Where suitable, add a dedicated channel such as:

```text
payment_timing
```

only if the project already uses dedicated channels and adding one is justified.

Otherwise use structured application logs.

Suggested events:

```text
payment_timing_policy_mismatch
payment_timing_policy_not_comparable
payment_timing_invalid_configuration
```

## 10.3 Logging rules

* Log mismatches by default in observation mode.
* Do not log matches by default.
* Support disabling mismatch logging.
* Avoid logging the same mismatch repeatedly within a short period where existing cache or deduplication conventions permit.
* Logging failure must never block patient services.
* Logging must not trigger additional expensive invoice queries.
* Keep context structured and bounded.

---

# 11. Diagnostic Artisan Command

Add a read-only command for evaluating the integration.

Suggested command:

```bash
php artisan billing:payment-timing-audit
```

Adapt the name to existing command conventions.

Suggested options:

```text
--visit=
--visit-type=
--active-only
--limit=100
--mismatches-only
--json
```

The command should:

1. Select a bounded set of visits.
2. Resolve the typed payment-timing policy.
3. Inspect or simulate the relevant legacy policy decision using safe existing APIs.
4. Compare the outcomes.
5. Report totals:

```text
visits inspected
matching decisions
legacy more restrictive
typed more restrictive
not comparable
invalid or missing context
```

6. Display representative examples without exposing patient-sensitive data.
7. Perform no mutations.
8. Create no invoices.
9. Create no payments.
10. Create no overrides.
11. Create no activity-log rows.
12. Exit non-zero only for command failure, not merely because mismatches exist.

Do not build fake service actions that could change workflow state.

Where the existing gate cannot be evaluated safely without a concrete service or invoice item, report the case as `not_comparable`.

---

# 12. Source Reporting

Provide a stable internal explanation structure for future UI use.

The typed decision should expose:

```text
policy
source
reason code
visit type
global default
visit-type configured value
emergency protection considered
legacy override considered
integration mode
```

Do not yet add a broad patient-facing or clinical UI.

A small restricted debug representation may be added only if an existing admin diagnostic page already provides an appropriate surface.

Otherwise defer UI display and use:

* Tests
* The audit command
* Structured logs
* The phase report

Source reporting must be designed so future phases can add:

```text
patient_risk
insurance
corporate_account
manual_override
```

without changing the DTO contract fundamentally.

---

# 13. Existing Billing Override Handling

This phase must carefully classify existing overrides.

Create a documented compatibility matrix such as:

| Existing override type     | Current scope    | New source mapping      | Full timing override?   |
| -------------------------- | ---------------- | ----------------------- | ----------------------- |
| Previous-balance override  | Prior debt gate  | manual override context | No                      |
| Service-payment override   | Specific service | manual override         | Possibly service-scoped |
| Visit billing override     | Entire visit     | manual override         | Only if confirmed       |
| Financial closure override | Closure only     | manual override context | No                      |

Use actual repository findings rather than assuming these exact rows exist.

Rules:

* Preserve all current override behaviour.
* Do not broaden override scope.
* Do not convert a service-scoped override into a whole-visit override.
* Do not add new override permissions.
* Do not add new approval screens.
* Do not change override expiry.
* Do not remove current audit logging.
* Do not persist `VisitPaymentPolicySource` yet.

---

# 14. Performance Requirements

Payment gates may run frequently.

The integration must avoid:

* Repeated settings queries
* Repeated visit reloads
* N+1 invoice queries
* Duplicate patient-balance calculations
* Recomputing the same decision several times in one request
* New queries for disabled observation mode
* New queries for legacy mode where typed diagnostics are not requested

Use:

* Existing cached settings
* Already-loaded visit relations
* Request-scoped memoisation where appropriate
* Existing billing summaries
* Existing invoice context already calculated by the gate

Performance expectations:

```text
legacy mode:
no meaningful additional database queries

observe mode:
typed resolution should normally be query-free after the visit is loaded
comparison should reuse existing gate data
```

Add focused query-count assertions where the project has a stable convention for them.

---

# 15. Failure Safety

The integration must fail safely.

If typed policy resolution fails:

* Log the failure according to existing conventions.
* Continue using the legacy gate result.
* Do not allow a service merely because the typed resolver failed.
* Do not block a service merely because the typed resolver failed.
* Do not expose internal exceptions to users.

If configuration is invalid:

* Fall back through `PaymentTimingConfigurationService`.
* Use the safe operational fallback from Phase 1.
* Keep legacy behaviour authoritative.

If observation logging fails:

* Continue the current service workflow.
* Do not roll back clinical or billing operations.

---

# 16. Localisation

Add English and French translations for any new user-facing or command-facing concepts that follow the project’s localisation conventions.

Possible concepts:

```text
legacy mode
observation mode
payment policy source
global default
visit-type policy
emergency protection
legacy policy matches
legacy policy is more restrictive
typed policy is more restrictive
comparison unavailable
invalid configuration fallback
```

Do not translate:

* Enum values stored in the database
* Log event codes
* Machine-readable reason codes
* Command option names
* Permission slugs
* Configuration keys

Maintain English/French parity.

---

# 17. Tests

Do not run the complete Laravel or Playwright suites during this phase.

Add focused tests.

## 17.1 Integration mode tests

Verify:

* Default mode is `legacy`.
* Invalid mode falls back to `legacy`.
* `observe` mode resolves correctly.
* Legacy mode does not calculate or log typed comparisons unnecessarily.
* Observe mode calculates comparisons.
* Observe mode returns the unchanged legacy result.

## 17.2 Resolver tests

Verify:

* Outpatient explicit setting resolves correctly.
* Outpatient `inherit` resolves to the global default.
* Inpatient policy resolves from its visit-type setting.
* Emergency protection resolves to `running_bill`.
* Final decisions never contain `inherit`.
* Invalid configuration falls back safely.
* Source is `visit_type` when an explicit visit-type setting applies.
* Source is `global_default` when inheritance applies.
* Source is `emergency_policy` for emergency safety protection.

## 17.3 Compatibility tests

Verify:

* Legacy prepayment requirement maps to `pay_before_service` where comparable.
* Legacy advisory mode is represented correctly.
* Existing override behaviour remains unchanged.
* Previous-balance-only override is not incorrectly treated as a complete visit timing override.
* Non-comparable cases are classified safely.

## 17.4 Payment gate regression tests

For representative existing workflows, prove that Phase 2 does not alter results:

* Unpaid service currently blocked remains blocked.
* Paid service remains allowed.
* Fully insurer-covered service remains allowed where currently allowed.
* Existing advisory gate remains advisory.
* Existing emergency bypass remains unchanged.
* Existing inpatient behaviour remains unchanged.
* Existing visit billing override remains effective.
* Existing previous-balance override remains effective.
* Missing-invoice behaviour remains unchanged.
* Zero-patient-responsibility behaviour remains unchanged.

## 17.5 Observation tests

Verify:

* Matching decisions are identified.
* Legacy-more-restrictive mismatch is identified.
* Typed-more-restrictive mismatch is identified.
* Non-comparable cases are identified.
* Mismatch logging excludes sensitive patient information.
* Logging failure does not alter the gate result.
* Matches are not logged when match logging is disabled.

## 17.6 Artisan command tests

Verify:

* The command runs successfully.
* `--visit` limits evaluation correctly.
* `--limit` is respected.
* `--mismatches-only` filters output.
* JSON output is valid.
* The command performs no writes.
* The command does not create invoices, payments, overrides, or activity logs.
* Mismatches do not produce a failure exit code.

## 17.7 Localisation tests

Verify new English and French keys remain in parity.

---

# 18. Targeted Verification

Run focused checks only.

Suggested commands:

```bash
php artisan config:clear
php artisan view:clear
php artisan route:list

php artisan test --filter=PaymentTimingIntegration
php artisan test --filter=VisitPaymentTimingResolver
php artisan test --filter=PaymentTimingCompatibility
php artisan test --filter=PaymentTimingObservation
php artisan test --filter=PaymentTimingAuditCommand
```

Also run the existing focused billing-gate tests that cover the methods touched.

Run:

```bash
php artisan billing:payment-timing-audit --limit=25
```

if a safe local dataset is available.

Run the existing:

```text
localisation parity check
permission audit
activity-log integrity check
PHP syntax checks
view compilation check
git diff check
```

Do not run the full Laravel suite.

Do not run the full Playwright suite.

Broad verification remains deferred until the complete payment-policy implementation batch is finished.

---

# 19. Documentation

Create:

```text
docs/billing/PAYMENT_TIMING_POLICY_PHASE_2_REPORT.md
```

The report must include:

1. Existing billing-policy architecture audited
2. Existing gate call-site inventory
3. Existing override compatibility matrix
4. Current enforcement flags
5. Files created
6. Files modified
7. Integration-mode design
8. Typed resolver design
9. Legacy compatibility mapping
10. Precedence implemented in Phase 2
11. Source-reporting structure
12. Observation and mismatch behaviour
13. Diagnostic command usage
14. Query and performance impact
15. Failure-safety behaviour
16. Focused tests and results
17. Confirmation that operational allow/block outcomes did not change
18. Discovered mismatches between legacy and typed policy
19. Recommended requirements for Phase 3

Do not claim tests or commands passed unless they were actually executed.

---

# 20. Guardrails

Do not:

* Add patient financial-risk fields
* Add patient financial-risk tables
* Add per-visit payment-policy persistence
* Add new visit policy override screens
* Add new risk override approvals
* Add insurance policy resolution
* Add sponsor policy resolution
* Add corporate-account policy resolution
* Add financial-closure enforcement
* Change visit completion behaviour
* Change discharge behaviour
* Change invoice calculations
* Change payment allocation
* Create a parallel payment gate
* Make modules read payment-timing settings directly
* Replace current gate outcomes with typed decisions
* Enable new service blocking
* Relax existing service blocking
* Flood `ActivityLog` with read decisions
* Log patient-sensitive information
* Run the full application test suites

The existing payment gate remains authoritative throughout Phase 2.

---

# 21. Acceptance Criteria

Phase 2 is complete only when:

* Existing billing-policy and payment-gate architecture has been fully audited.
* Existing gate call sites are documented.
* Existing override semantics are documented.
* An explicit `legacy` and `observe` integration mode exists.
* Integration defaults to `legacy`.
* A typed `VisitPaymentTimingDecision` exists.
* A limited typed resolver exists.
* The resolver handles emergency, visit-type, and global configuration.
* Final typed policies never contain `inherit`.
* Existing compatible overrides are represented without broadening their scope.
* A compatibility mapper exists.
* A comparison service identifies matches and mismatches.
* Observe mode compares decisions without changing outcomes.
* Legacy mode adds no meaningful operational overhead.
* Mismatch logging is bounded and confidentiality-safe.
* A read-only diagnostic command exists.
* Existing gate public contracts remain compatible.
* Existing allow/block/advisory results remain unchanged.
* No patient-risk or per-visit persistence has been added.
* No new payment enforcement has been enabled.
* English and French localisation remain complete.
* Focused tests pass.
* The Phase 2 report accurately records the findings and verification.

Proceed with **Payment Timing Policy Phase 2 only**.

After implementation, provide:

1. A concise implementation summary
2. The audited legacy architecture
3. Files created and modified
4. The final integration and precedence design
5. Any legacy-to-typed mismatches discovered
6. Query and performance impact
7. Diagnostic command results
8. Focused test results
9. Confirmation that payment-gate outcomes remain unchanged
10. The Phase 2 report path
11. Recommended requirements for Phase 3

Then stop after Phase 2.
