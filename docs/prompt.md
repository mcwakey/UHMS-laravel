# UHMS Implementation Prompt — Payment Timing Policy Phase 4

## Departmental Enforcement Policy Registry, Stage Rules, and Safe Cutover Controls

You are working on **UHMS**, a Laravel-based hospital management system.

Implement **Phase 4 of the Configurable Payment Timing and Per-Visit Payment Policy system**.

Phases 1–3 are complete.

---

# 1. Existing Foundation

## Phase 1

Phase 1 introduced:

* `VisitPaymentTimingPolicy`
* `VisitPaymentPolicySource`
* Typed global and visit-type configuration
* Database-backed payment-timing settings
* `PaymentTimingConfigurationService`
* Admin settings UI
* English/French localisation
* Permission-controlled configuration
* Transactional updates and activity logging

## Phase 2

Phase 2 introduced:

* `PaymentTimingIntegrationMode`
* `VisitPaymentTimingDecision`
* `VisitPaymentTimingResolver`
* Legacy compatibility mapping
* Typed-versus-legacy comparison
* Legacy-authoritative observation mode
* Bounded mismatch diagnostics
* `billing:payment-timing-audit`

## Phase 3

Phase 3 introduced:

* `PaymentGateStage`
* `PaymentGateContext`
* Centralised production access through `PaymentGateService`
* `PaymentGateOperationRegistry`
* Stage-aware observation diagnostics
* `billing:payment-gate-coverage`

The following production operations are currently wired:

```text
consultation.route.complete
consultation.next_patient.readiness
laboratory.result.enter
pharmacy.item.dispense
```

The following operations are registered but intentionally unwired:

```text
consultation.start
investigation.perform
procedure.start
service.render
theatre.perform
treatment.perform
nursing.service.render
blood_bank.unit.issue
ambulance.render
```

Phase 3 confirmed that:

* Existing payment decisions remain legacy-authoritative.
* No new department began blocking payment.
* Laboratory currently uses intrinsic settlement rules at result entry.
* Pharmacy currently has a stricter paid-only dispensing rule.
* Emergency and inpatient legacy behaviour remains unchanged.
* UHMS cannot yet reliably distinguish emergency stabilisation from post-stabilisation routine care.
* Missing invoice and invoice-item behaviour varies by workflow and must not be silently normalised.
* Previous-balance overrides remain a separate prior-debt policy.

---

# 2. Phase 4 Goal

Create a configurable and auditable **departmental payment-enforcement policy foundation** that defines:

* Which workflow operations are eligible for payment enforcement
* At which stage payment may be checked
* Whether an operation is:

  * disabled
  * observation-only
  * legacy-enforced
  * eligible for future typed enforcement
* How emergency and inpatient visits are treated
* How missing invoices and missing invoice items are handled
* Whether service-scoped or visit-scoped overrides may apply
* Whether insurer-covered, waived, adjusted, or zero-responsibility items may proceed
* How the system can safely roll back an operation to its previous behaviour

This phase establishes the policy registry, configuration model, administration foundation, validation, audit, and diagnostics.

It must **not activate new departmental payment blocking**.

It must **not switch typed payment timing into operational authority**.

---

# 3. Key Architectural Principle

UHMS must distinguish three separate concepts:

```text
1. Visit payment-timing policy
2. Departmental enforcement operation
3. Invoice-item settlement state
```

Examples:

* A visit may have `pay_before_service`.
* Laboratory may enforce at result entry rather than request creation.
* Pharmacy may enforce at dispensing.
* An invoice item may already be paid, fully insured, waived, adjusted, partially paid, or missing.
* A visit-wide override may allow deferred settlement.
* A narrow invoice-item override must not automatically apply to unrelated services.

Do not collapse these concepts into one boolean.

---

# 4. Mandatory Repository Audit

Before modifying code, inspect all relevant implementation added in Phases 1–3 and all current billing-policy configuration.

Review:

```text
VisitPaymentTimingPolicy
VisitPaymentPolicySource
PaymentTimingIntegrationMode
PaymentTimingConfigurationService
VisitPaymentTimingResolver
PaymentGateStage
PaymentGateContext
PaymentGateOperationRegistry
PaymentGateService
BillingPolicyService
InvoiceItemSettlementService
VisitBillingOverrideService
PreviousBalanceOverrideService
config/payment_timing.php
config/billing_policy.php
```

Also inspect:

* Existing `Setting` model and settings infrastructure
* Existing settings controller and views
* Existing enum-backed validation conventions
* Existing permission and activity-log conventions
* Existing feature-flag patterns
* Existing service and department registries
* Existing caching conventions
* Existing configuration-seeding conventions

Document any duplicated or overlapping setting before introducing new keys.

---

# 5. Enforcement Mode Vocabulary

Create a typed enum representing the configured enforcement state for a registered operation.

Suggested location:

```text
app/Enums/PaymentGateOperationMode.php
```

Required values:

```php
<?php

namespace App\Enums;

enum PaymentGateOperationMode: string
{
    case DISABLED = 'disabled';
    case OBSERVE = 'observe';
    case LEGACY = 'legacy';
}
```

Meaning:

## `disabled`

* No payment-policy gate should be invoked for that operation.
* Existing intrinsic settlement display calculations may still run.
* This must not remove an existing hard production check unless the operation already supports safe configuration and explicit compatibility handling.
* Existing wired operations should not default to disabled if doing so would relax current behaviour.

## `observe`

* The workflow calculates the configured operation policy.
* It records bounded diagnostics.
* It does not change the current operational result.
* For currently unwired operations, observation must not block the action.
* For existing wired hard gates, the current legacy result remains authoritative.

## `legacy`

* The operation uses the existing legacy gate behaviour.
* This does not mean typed enforcement.
* Existing wired operations should default to legacy.
* Newly registered but previously unwired operations must not default to legacy enforcement.

Do not add `typed`, `active`, or `enforce_typed` mode in this phase.

---

# 6. Missing Billing Context Policy

Create an enum for handling missing billing context at each operation.

Suggested location:

```text
app/Enums/MissingBillingContextPolicy.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum MissingBillingContextPolicy: string
{
    case PRESERVE_LEGACY = 'preserve_legacy';
    case ALLOW = 'allow';
    case BLOCK = 'block';
    case NOT_APPLICABLE = 'not_applicable';
}
```

Rules:

* Existing wired operations should initially use `preserve_legacy`.
* Newly unwired operations should not become blocked because an invoice item is missing.
* `block` must not become operational for unwired workflows in this phase.
* `not_applicable` may be used for non-billable operations.
* The enum defines configuration vocabulary only.
* It must not override existing production behaviour yet.

---

# 7. Visit Context Policy

Create a typed representation of visit-context handling for each operation.

Possible enum:

```text
app/Enums/PaymentGateVisitContextRule.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum PaymentGateVisitContextRule: string
{
    case USE_VISIT_POLICY = 'use_visit_policy';
    case ALWAYS_RUNNING_BILL = 'always_running_bill';
    case PRESERVE_LEGACY = 'preserve_legacy';
    case NOT_APPLICABLE = 'not_applicable';
}
```

Use this for configuration and diagnostics.

Do not yet use it to replace legacy operational decisions.

---

# 8. Override Scope Policy

Create an enum or typed rule describing which override scopes an operation may recognise.

Suggested enum:

```text
app/Enums/PaymentGateOverrideScopeRule.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum PaymentGateOverrideScopeRule: string
{
    case NONE = 'none';
    case INVOICE_ITEM_ONLY = 'invoice_item_only';
    case SERVICE_OR_ITEM = 'service_or_item';
    case DEPARTMENT_SERVICE_OR_ITEM = 'department_service_or_item';
    case VISIT_WIDE = 'visit_wide';
    case PRESERVE_LEGACY = 'preserve_legacy';
}
```

Rules:

* Existing wired operations should initially preserve legacy override scope.
* A narrow override must never be broadened automatically.
* A visit-wide override should apply only where the existing policy confirms that scope.
* Previous-balance overrides remain separate and are not included in this enum.
* Financial-closure overrides remain separate.
* This is preparatory configuration in Phase 4.

---

# 9. Operation Policy DTO

Create a typed DTO describing the complete configured policy for one registered payment-gate operation.

Suggested location:

```text
app/Data/Billing/PaymentGateOperationPolicy.php
```

Suggested structure:

```php
final readonly class PaymentGateOperationPolicy
{
    public function __construct(
        public string $operation,
        public PaymentGateStage $stage,
        public PaymentGateOperationMode $mode,
        public MissingBillingContextPolicy $missingBillingContext,
        public PaymentGateVisitContextRule $visitContextRule,
        public PaymentGateOverrideScopeRule $overrideScopeRule,
        public bool $allowZeroPatientResponsibility,
        public bool $allowFullyInsured,
        public bool $allowWaived,
        public bool $allowFullyAdjusted,
        public bool $allowPartialPayment,
        public bool $emergencyExempt,
        public bool $inpatientExempt,
        public bool $enabledForFutureTypedEnforcement,
        public array $metadata = [],
    ) {
    }
}
```

Adapt the shape to existing project conventions.

Rules:

* Avoid arbitrary unbounded metadata.
* Do not store translated labels.
* Do not duplicate values already available in the operation registry unless the DTO represents the resolved configuration.
* The DTO must be immutable.
* It must be safe to expose to diagnostics without patient data.

---

# 10. Extend the Operation Registry

Extend `PaymentGateOperationRegistry` so every operation declares:

```text
operation code
stage
department type
workflow family
whether currently production-wired
whether currently a hard gate
legacy behaviour
default operation mode
default missing-context policy
default visit-context rule
default override-scope rule
future typed-enforcement eligibility
```

The registry must remain descriptive.

It must not itself evaluate invoices or make payment decisions.

Suggested conceptual definition:

```php
[
    'pharmacy.item.dispense' => [
        'stage' => PaymentGateStage::DISPENSE,
        'department_type' => DepartmentType::PHARMACY,
        'wired' => true,
        'hard_gate' => true,
        'default_mode' => PaymentGateOperationMode::LEGACY,
        'missing_billing_context' => MissingBillingContextPolicy::PRESERVE_LEGACY,
        'visit_context_rule' => PaymentGateVisitContextRule::PRESERVE_LEGACY,
        'override_scope_rule' => PaymentGateOverrideScopeRule::PRESERVE_LEGACY,
        'typed_enforcement_eligible' => true,
    ],
]
```

Use actual enums and conventions.

---

# 11. Required Initial Operation Defaults

Preserve current behaviour.

## 11.1 Existing wired operations

The following should default to `legacy`:

```text
consultation.route.complete
consultation.next_patient.readiness
laboratory.result.enter
pharmacy.item.dispense
```

Their current rules must remain unchanged.

## 11.2 Existing unwired operations

The following should default to `disabled` or `observe`, whichever does not add operational checks:

```text
consultation.start
investigation.perform
procedure.start
service.render
theatre.perform
treatment.perform
nursing.service.render
blood_bank.unit.issue
ambulance.render
```

Prefer:

```text
disabled
```

unless observation can be performed safely without introducing queries, side effects, or workflow changes.

Do not add production calls merely because an operation exists in the registry.

## 11.3 Laboratory compatibility

Laboratory result entry must retain its intrinsic settlement compatibility policy:

* Paid may proceed.
* Fully insured may proceed where currently permitted.
* Waived may proceed where currently permitted.
* Fully adjusted may proceed where currently permitted.
* Missing invoice item preserves current allow behaviour.
* Visit timing overrides do not become broader than current laboratory behaviour.

## 11.4 Pharmacy compatibility

Pharmacy dispensing must retain its stricter paid-only compatibility rule.

Do not newly permit dispensing because:

* The visit is emergency
* The visit is inpatient
* The item is waived
* A credit override exists
* Deferred settlement exists
* A generic payment-gate bypass exists

unless current pharmacy behaviour already allows that exact case.

Document these as compatibility rules, not ideal future policy.

---

# 12. Configuration Storage

Use the existing grouped `Setting` infrastructure.

Do not create a separate operation-policy table unless the existing settings system cannot safely support structured operation settings.

Preferred settings group:

```text
payment_gate_operations
```

Possible keys:

```text
consultation.route.complete.mode
consultation.route.complete.missing_context
consultation.route.complete.visit_context_rule
consultation.route.complete.override_scope_rule
consultation.route.complete.emergency_exempt
consultation.route.complete.inpatient_exempt
consultation.route.complete.typed_enforcement_eligible
```

However, avoid an uncontrolled explosion of flat settings if the project settings system supports validated JSON or grouped structured payloads.

Choose one of:

## Option A — Structured JSON per operation

```text
payment_gate_operations.consultation.route.complete
```

Value:

```json
{
  "mode": "legacy",
  "missing_context": "preserve_legacy",
  "visit_context_rule": "preserve_legacy",
  "override_scope_rule": "preserve_legacy",
  "emergency_exempt": false,
  "inpatient_exempt": false,
  "typed_enforcement_eligible": true
}
```

## Option B — Flat typed keys

Use only if that matches existing settings conventions better.

Document the storage decision.

---

# 13. Configuration Service

Create:

```text
app/Services/Billing/PaymentGateOperationConfigurationService.php
```

Responsibilities:

```php
public function policyFor(string $operation): PaymentGateOperationPolicy;

public function policies(): Collection|array;

public function modeFor(string $operation): PaymentGateOperationMode;

public function registered(string $operation): bool;
```

Rules:

* Validate against `PaymentGateOperationRegistry`.
* Return typed values.
* Fall back to registry defaults.
* Handle invalid stored values safely.
* Cache through existing settings caching.
* Never return an unknown operation as an enforceable operation.
* Unknown operations should fail safely to a non-enforcing diagnostic representation.
* Do not query invoice, visit, patient, or override data.
* Do not make payment decisions.
* Do not change current workflow outcomes.

---

# 14. Administration Interface

Extend the existing Payment Timing Policies settings area with a restricted section:

```text
Departmental Payment Enforcement
```

The UI must present the registered operations grouped by department or workflow family.

For each operation, show:

```text
Operation
Workflow stage
Current production status
Current enforcement behaviour
Configured mode
Missing billing context handling
Emergency handling
Inpatient handling
Override-scope rule
Typed-enforcement eligibility
```

## 14.1 Mode options

Offer:

```text
Disabled
Observe Only
Use Existing Legacy Gate
```

Do not offer typed enforcement.

## 14.2 Safety indicators

Clearly identify:

```text
Currently wired
Currently unwired
Existing hard gate
Display/readiness only
Compatibility-specific rule
```

## 14.3 Restrictions

For unwired operations:

* Changing the mode must not silently wire the operation.
* The UI should explain that configuration alone does not activate a production gate.
* A future implementation phase is required to wire approved operations.

For existing wired hard gates:

* Do not allow an ordinary setting change to disable current protection unless a deliberate safe rollback contract exists.
* If disabling would alter current production behaviour, either:

  * make the control read-only in Phase 4, or
  * accept the setting but keep it non-operational until the cutover phase.

Prefer read-only compatibility protection for existing hard gates.

## 14.4 UX

Follow the existing Bootstrap/Blade design.

Do not introduce:

* Inline JavaScript handlers
* A new UI framework
* Untranslated labels
* Large modals
* Direct controller policy logic

Use concise help text.

---

# 15. Request Validation

Create or extend a form request for operation-policy settings.

Validate:

* Operation exists in the registry.
* Mode is a valid enum.
* Missing-context policy is valid.
* Visit-context rule is valid.
* Override-scope rule is valid.
* Boolean values are normalised.
* Existing hard-gate compatibility operations cannot be accidentally disabled where Phase 4 does not support that change.
* Typed enforcement cannot be selected.
* Unknown operation codes are rejected.
* Duplicate operations are rejected.
* Partial invalid updates do not persist.

Use database transactions for successful multi-operation updates.

Audit only changed values.

---

# 16. Audit Logging

Use the existing `ActivityLog` infrastructure for configuration mutations.

Suggested action:

```text
PAYMENT_GATE_OPERATION_SETTINGS_UPDATED
```

Use the existing naming conventions if different.

Capture:

```text
operations changed
old typed values
new typed values
changed by
timestamp
```

Do not log unchanged operations.

Do not log patient data.

Do not create activity logs during routine gate reads.

---

# 17. Enforcement Eligibility Matrix

Create a service that evaluates whether an operation is architecturally eligible for future typed enforcement.

Suggested service:

```text
app/Services/Billing/PaymentGateEnforcementEligibilityService.php
```

The service should evaluate configuration and registry metadata only.

Possible output:

```text
eligible
ineligible_unwired
ineligible_missing_stage
ineligible_missing_invoice_resolution
ineligible_emergency_boundary
ineligible_compatibility_rule
ineligible_unapproved_policy
```

Suggested DTO:

```text
PaymentGateEnforcementEligibility
```

The service must not enable enforcement.

It should help identify what remains before each operation can be cut over.

Examples:

## Pharmacy dispensing

May be ineligible for direct typed enforcement until UHMS decides whether the paid-only rule should remain or converge with visit payment timing.

## Laboratory result entry

May be ineligible until the hospital decides whether result entry, result verification, or result release is the correct enforcement stage.

## Emergency operations

Remain ineligible where stabilisation boundaries cannot be reliably identified.

---

# 18. Departmental Policy Decisions to Encode

Phase 4 should encode **provisional safe defaults**, not final hospital decisions.

## Consultation

### `consultation.route.complete`

* Existing hard gate
* Preserve legacy
* No behavioural change

### `consultation.next_patient.readiness`

* Existing readiness and activation check
* Preserve legacy
* Do not convert all readiness failures into hard enforcement

### `consultation.start`

* Unwired
* Default disabled
* Do not block consultation start in Phase 4

## Investigations and radiology

### `investigation.perform`

* Unwired
* Default disabled
* Record that the final enforcement stage is undecided:

  * acceptance
  * sample collection
  * performance
  * result entry
  * result verification
  * result release

### `laboratory.result.enter`

* Existing wired compatibility gate
* Preserve current intrinsic settlement behaviour

## Pharmacy

### `pharmacy.item.dispense`

* Existing paid-only hard gate
* Preserve legacy compatibility
* Mark as requiring explicit policy convergence decision before typed enforcement

## Procedures

### `procedure.start`

* Unwired
* Default disabled
* Do not block scheduling or performance

## Theatre

### `theatre.perform`

* Unwired
* Default disabled
* Emergency and life-saving boundaries unresolved

## Treatment

### `treatment.perform`

* Unwired
* Default disabled

## Nursing

### `nursing.service.render`

* Unwired
* Default disabled
* Medication administration and essential nursing care must not be accidentally blocked

## Blood bank

### `blood_bank.unit.issue`

* Unwired
* Default disabled
* Emergency and life-saving blood issue must remain protected

## Ambulance

### `ambulance.render`

* No dedicated operational workflow confirmed
* Default disabled
* Mark as unavailable until a concrete billable workflow exists

---

# 19. Emergency and Inpatient Safety

## 19.1 Emergency

Phase 4 must not claim that emergency stabilisation can be reliably distinguished if the repository still lacks an authoritative marker.

For operations involving emergency care:

* Mark emergency-stage enforcement eligibility as unresolved.
* Keep current legacy behaviour.
* Do not activate new gates.
* Do not add a fake `is_stabilisation` field without a broader clinical workflow design.
* Document which existing statuses or markers were reviewed.

## 19.2 Inpatient

Inpatient visits generally use running-bill behaviour.

Do not add service-level blocking to:

* Nursing tasks
* Medication administration
* Bed management
* Treatment rendering
* Discharge planning

Phase 4 may record future financial-clearance requirements separately, but must not enforce them.

---

# 20. Compatibility Evaluation

Add a compatibility evaluator that compares:

```text
registered default
stored operation policy
current production wiring
legacy behaviour
future typed-enforcement eligibility
```

Suggested service:

```text
PaymentGateOperationCompatibilityService
```

Possible statuses:

```text
compatible
configuration_non_operational
legacy_hard_gate_protected
typed_cutover_not_ready
unwired_operation
emergency_boundary_missing
invoice_resolution_missing
compatibility_rule_conflict
```

This service is diagnostic only.

---

# 21. Extend Coverage Command

Extend:

```bash
php artisan billing:payment-gate-coverage
```

Add useful options such as:

```text
--operation=
--department=
--wired-only
--unwired-only
--eligible-only
--ineligible-only
--json
```

Output should include:

```text
operation
stage
department
wired status
hard-gate status
configured mode
legacy behaviour
missing-context rule
emergency rule
inpatient rule
override-scope rule
typed-enforcement eligibility
eligibility reason
```

The command must remain read-only.

It must:

* Create no invoices
* Create no payments
* Create no overrides
* Create no settings
* Create no activity-log entries
* Avoid patient data
* Exit successfully when operations are ineligible or unwired

---

# 22. Add a Policy Audit Command

Add a read-only command:

```bash
php artisan billing:payment-gate-policy-audit
```

Suggested options:

```text
--operation=
--department=
--json
--problems-only
```

The command should identify:

```text
invalid stored enum values
unknown operation settings
missing registry defaults
wired operations configured as disabled
unwired operations configured as legacy
hard gates without compatibility protection
typed-eligible operations missing invoice resolution
emergency-sensitive operations without a reliable boundary
```

It must not modify settings automatically.

It should exit non-zero only for technical command failure, not because policy warnings exist.

---

# 23. No Production Cutover

This phase must not change `PaymentGateService` to enforce operation settings operationally.

The operation-policy configuration may be consulted for:

* Diagnostics
* Admin display
* Eligibility analysis
* Observation metadata
* Audit commands

It must not yet cause:

```text
allow → block
block → allow
advisory → hard block
unwired → wired
```

Existing wired legacy behaviour remains authoritative.

A later cutover phase will deliberately connect approved operation modes to production enforcement.

---

# 24. Performance Requirements

The configuration and diagnostics must not degrade normal workflows.

## Normal workflow

* Existing production gate paths should not add operation-settings database queries.
* Configuration should use existing cache.
* Registry metadata should be in memory.
* Legacy mode should remain lightweight.
* No operation-policy query should occur where the feature is unused.

## Admin and commands

* Load operation settings in groups.
* Avoid one settings query per operation.
* Avoid N+1 registry lookups.
* Commands should work from registry and grouped settings.

Add focused performance checks where stable.

---

# 25. Failure Safety

If stored operation configuration is invalid:

* Fall back to registry defaults.
* Record a bounded application warning.
* Preserve current legacy workflow behaviour.
* Do not enable a new gate.
* Do not disable an existing hard gate.

If operation-policy resolution throws:

* Continue with existing production behaviour.
* Do not expose internal errors to clinicians.
* Do not roll back clinical or billing actions solely because diagnostics failed.

If the registry and stored configuration disagree:

* Registry compatibility defaults win for operational safety.
* Report the disagreement through diagnostics.

---

# 26. Permissions

Reuse:

```text
settings.manage
```

for configuration unless a more appropriate existing billing-settings permission already exists.

For read-only diagnostic pages, reuse an existing billing/reporting/admin permission.

Do not create new permissions unnecessarily.

Backend routes must be protected.

Do not rely only on hidden controls.

---

# 27. Localisation

Add complete English and French translations for:

```text
Departmental Payment Enforcement
Operation
Workflow Stage
Currently Wired
Currently Unwired
Existing Hard Gate
Disabled
Observe Only
Use Existing Legacy Gate
Missing Billing Context
Preserve Existing Behaviour
Allow
Block
Not Applicable
Visit Context Rule
Override Scope
Emergency Exempt
Inpatient Exempt
Typed Enforcement Eligibility
Eligible
Not Eligible
Compatibility Rule Conflict
Emergency Boundary Missing
Invoice Resolution Missing
Configuration is not yet operational
```

Machine codes and stored enum values remain untranslated.

Maintain EN/FR parity.

---

# 28. Seeding

Add idempotent defaults using existing settings conventions.

Rules:

* Do not overwrite administrator values.
* Do not duplicate settings.
* Existing wired operations receive compatibility-safe defaults.
* Existing unwired operations remain disabled.
* Do not activate typed enforcement.
* Seeder must be safe to run repeatedly.
* No schema migration should be added if existing settings storage is sufficient.

Document the number of settings or operation records created.

---

# 29. Tests

Do not run the full Laravel or Playwright suites.

Add focused tests.

## 29.1 Enum tests

Verify:

* Operation modes contain only the required values.
* Missing-context policies are valid.
* Visit-context rules are valid.
* Override-scope rules are valid.
* No typed enforcement mode exists.

## 29.2 Registry tests

Verify:

* All 13 operations remain registered.
* Operation codes are unique.
* Every operation has a valid stage.
* Every operation has safe defaults.
* Existing wired operations default to legacy.
* Existing unwired operations default to disabled or safe observation.
* Pharmacy and laboratory retain compatibility metadata.
* Emergency-sensitive operations are marked appropriately.

## 29.3 Configuration service tests

Verify:

* Stored settings override registry defaults where permitted.
* Invalid values fall back safely.
* Unknown operations are non-enforcing.
* Existing hard gates cannot be accidentally disabled.
* Grouped settings load efficiently.
* Policies are returned as typed DTOs.

## 29.4 Admin settings tests

Verify:

* Authorised users can view operation settings.
* Unauthorised users cannot view or update them.
* Valid settings persist transactionally.
* Invalid operation codes are rejected.
* Invalid enums are rejected.
* Typed enforcement cannot be selected.
* Existing hard-gate protections are validated.
* Successful changes create one activity log.
* Unchanged updates do not create logs.
* Failed validation creates no logs.

## 29.5 Eligibility tests

Verify:

* Unwired operations are ineligible.
* Emergency-sensitive operations without a boundary are ineligible.
* Pharmacy reports its compatibility conflict.
* Laboratory reports its unresolved stage/convergence issue where applicable.
* Existing wired operations may be technically eligible only where all prerequisites are satisfied.
* Eligibility never changes workflow outcomes.

## 29.6 Compatibility tests

Verify:

* Current production wiring remains unchanged.
* Existing wired gates retain legacy outcomes.
* Unwired operations remain unwired.
* Operation settings do not produce allow/block changes.
* Existing messages and exceptions remain unchanged.
* Previous-balance overrides remain separate.

## 29.7 Command tests

Verify:

* Coverage command shows configuration and eligibility.
* Audit command detects unsafe combinations.
* JSON output is valid.
* Filters work.
* Commands perform no writes.
* Commands create no activity logs.
* Warnings do not cause failure exit codes.

## 29.8 Seeder tests

Verify:

* Seeder creates expected defaults.
* Seeder is idempotent.
* Existing administrator values are preserved.
* No duplicate operation settings are created.

## 29.9 Localisation tests

Verify English/French parity.

---

# 30. Targeted Verification

Run only focused checks.

Suggested commands:

```bash
php artisan config:clear
php artisan view:clear
php artisan view:cache

php artisan test --filter=PaymentGateOperationMode
php artisan test --filter=PaymentGateOperationRegistry
php artisan test --filter=PaymentGateOperationConfiguration
php artisan test --filter=PaymentGateOperationSettings
php artisan test --filter=PaymentGateEnforcementEligibility
php artisan test --filter=PaymentGateOperationCompatibility
php artisan test --filter=PaymentGateCoverage
php artisan test --filter=PaymentGatePolicyAudit
```

Rerun existing focused regressions:

```bash
php artisan test --filter=PaymentTiming
php artisan test --filter=PaymentGateService
php artisan test --filter=BillingPaymentPolicy
php artisan test --filter=PreviousBalancePolicy
php artisan test --filter=TriagePayment
php artisan test --filter=ConsultationPaymentReadiness
php artisan test --filter=LaboratoryPaymentGate
php artisan test --filter=Pharmacy
```

Run:

```bash
php artisan billing:payment-timing-audit --limit=25
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

# 31. Documentation

Create:

```text
docs/billing/PAYMENT_TIMING_POLICY_PHASE_4_REPORT.md
```

The report must include:

1. Existing architecture audited
2. Operation-policy vocabulary
3. Registry extensions
4. Configuration storage decision
5. Files created
6. Files modified
7. Default policies for all 13 operations
8. Existing hard-gate compatibility protections
9. Missing billing-context rules
10. Emergency and inpatient rules
11. Override-scope rules
12. Pharmacy compatibility decision
13. Laboratory compatibility decision
14. Enforcement eligibility results
15. Admin interface changes
16. Validation and permissions
17. Audit behaviour
18. Seeder behaviour and idempotence
19. Coverage command results
20. Policy-audit command results
21. Query and performance impact
22. Focused tests and results
23. Confirmation that production payment outcomes remain unchanged
24. Operations ready or not ready for future cutover
25. Recommendations for Phase 5

Do not claim tests passed unless they were executed successfully.

---

# 32. Guardrails

Do not:

* Add patient financial-risk profiles
* Add risk enums or patient-risk tables
* Persist per-visit payment timing
* Add per-visit payment-policy overrides
* Add risk override approvals
* Add insurance timing resolution
* Add sponsor timing resolution
* Add corporate timing resolution
* Add typed enforcement mode
* Make typed decisions operational
* Wire the nine unwired operations
* Add new departmental blocking
* Relax existing laboratory behaviour
* Relax existing pharmacy paid-only behaviour
* Change emergency behaviour
* Change inpatient behaviour
* Add emergency stabilisation fields without a proper clinical design
* Change invoice calculations
* Change receivable calculations
* Change payment allocations
* Change general-ledger posting
* Change previous-balance policy
* Change visit completion
* Change discharge
* Change financial closure
* Create activity logs for policy reads
* Run broad test suites

---

# 33. Acceptance Criteria

Phase 4 is complete only when:

* A typed operation-mode enum exists.
* A typed missing-billing-context policy exists.
* Typed visit-context and override-scope rules exist.
* Every registered operation has safe policy defaults.
* Existing wired operations default to legacy compatibility.
* Existing unwired operations remain non-enforcing.
* A typed operation-policy DTO exists.
* Operation settings use the existing settings infrastructure.
* A typed configuration service exists.
* Invalid settings fall back safely.
* Existing hard gates cannot be accidentally disabled.
* The admin UI exposes operation-policy configuration safely.
* Configuration changes are permission-controlled.
* Configuration changes are audited.
* Seed defaults are idempotent.
* Enforcement eligibility is calculated diagnostically.
* Pharmacy compatibility limitations are explicit.
* Laboratory compatibility limitations are explicit.
* Emergency-stage limitations are explicit.
* Coverage diagnostics include configured policy and eligibility.
* A policy-audit command identifies unsafe combinations.
* No production operation changes allow/block behaviour.
* No unwired operation becomes wired.
* Existing messages, exceptions, and override scopes remain unchanged.
* English/French localisation is complete.
* Focused tests pass.
* The Phase 4 report accurately documents implementation and verification.

Proceed with **Payment Timing Policy Phase 4 only**.

After implementation, provide:

1. A concise implementation summary
2. Files created and modified
3. The final operation-policy vocabulary
4. Configuration storage and seeding details
5. Default policies for all 13 operations
6. Pharmacy and laboratory compatibility decisions
7. Emergency and inpatient safety decisions
8. Enforcement-eligibility results
9. Coverage and policy-audit command results
10. Query and performance impact
11. Focused test results
12. Confirmation that production outcomes remain unchanged
13. The Phase 4 report path
14. Recommended requirements for Phase 5

Then stop after Phase 4.
