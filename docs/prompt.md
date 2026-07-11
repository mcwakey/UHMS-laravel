# UHMS Implementation Prompt — Payment Timing Policy Phase 3

## Unified Payment-Gate Workflow Wiring and Stage-Aware Policy Context

You are working on **UHMS**, a Laravel-based hospital management system.

Implement **Phase 3 of the Configurable Payment Timing and Per-Visit Payment Policy system**.

Phases 1 and 2 are complete.

Phase 1 introduced:

* `VisitPaymentTimingPolicy`
* `VisitPaymentPolicySource`
* Typed payment-timing configuration
* Database-backed admin settings
* `PaymentTimingConfigurationService`
* English and French localisation
* Settings permission protection and audit

Phase 2 introduced:

* `PaymentTimingIntegrationMode`
* `VisitPaymentTimingDecision`
* `VisitPaymentTimingResolver`
* Legacy compatibility mapping
* Typed-versus-legacy comparison
* Legacy-authoritative observation mode
* Bounded mismatch logging
* `billing:payment-timing-audit`

Phase 2 also confirmed:

* `BillingPolicyService` remains the operational decision engine.
* `PaymentGateService` exists as the intended façade.
* Production payment checks currently bypass that façade in several places.
* `TriageController` calls `BillingPolicyService` directly.
* `ConsultationNextPatientService` calls `BillingPolicyService` directly.
* `LabRequestItem::isBillSettled()` calls `InvoiceItemSettlementService` directly.
* The departmental methods on `PaymentGateService` currently have no production call sites.
* No production gate call sites were confirmed for pharmacy, radiology, theatre, treatment, nursing, blood bank, ambulance, discharge, or financial closure.
* Legacy allow/block decisions must remain authoritative for now.

The goal of Phase 3 is to establish **one operational payment-gate entry path** and introduce **stage-aware gate context**, without switching to typed enforcement and without broadening payment blocking into departments that do not currently enforce it.

---

# 1. Primary Goal

Create a coherent payment-gate architecture in which:

```text
Departmental workflow
        ↓
PaymentGateService
        ↓
BillingPolicyService
        ↓
Existing legacy BillingPolicyDecision
        ↓
Optional typed observation
```

After Phase 3:

* Existing direct production callers should use `PaymentGateService`.
* Laboratory settlement should distinguish intrinsic invoice settlement from policy permission.
* Gate decisions should identify the workflow stage being evaluated.
* Existing allow/block outcomes must remain unchanged.
* Existing exception messages and response behaviour must remain unchanged.
* Future departments will have explicit integration points.
* Typed enforcement must still remain disabled.
* No patient financial-risk implementation should be added yet.

---

# 2. Mandatory Architecture Audit

Before changing code, perform a fresh repository audit.

## 2.1 Locate every current payment-related workflow check

Search for direct use of:

```text
BillingPolicyService
PaymentGateService
InvoiceItemSettlementService
isBillSettled
canProceedWithoutCashPayment
paymentReadiness
payment_required
bill settled
payment settled
invoice paid
payment gate
billing gate
```

Inspect:

* Controllers
* Services
* Models
* Jobs
* Listeners
* Policies
* Blade components
* Inertia payload builders
* API resources
* Commands

Document all production call sites found.

## 2.2 Identify actual service lifecycle stages

For every relevant department, determine whether UHMS has a distinct action for:

```text
order
request
accept
queue
start
perform
result
verify
complete
dispense
issue
release
render
cancel
```

Do not assume all departments use the same lifecycle.

Document the exact existing action or method names for:

* Consultation and route services
* Triage
* Laboratory
* Radiology
* Pharmacy
* Procedures
* Theatre
* Treatments
* Nursing
* Admission and inpatient
* Emergency
* Blood bank
* Ambulance
* Generic service rendering

## 2.3 Identify current enforcement versus display-only checks

Classify each payment check as:

```text
hard enforcement
readiness/advisory
display status only
intrinsic settlement calculation
financial closure validation
previous-balance validation
```

Do not convert a display-only check into hard enforcement.

Do not confuse intrinsic settlement state with policy permission.

## 2.4 Record missing workflow coverage

Create a matrix:

| Department/workflow       | Existing payment check    | Current stage      | Current authority  | Phase 3 action                                |
| ------------------------- | ------------------------- | ------------------ | ------------------ | --------------------------------------------- |
| Triage consultation route | Direct billing policy     | Completion/routing | Legacy gate        | Migrate to façade                             |
| Consultation next patient | Direct billing policy     | Readiness          | Legacy gate        | Migrate to façade                             |
| Laboratory                | Intrinsic settlement only | Result entry       | Settlement service | Reconcile carefully                           |
| Pharmacy                  | None confirmed            | Dispensing         | None               | Document only unless existing policy intended |
| Procedure                 | None confirmed            | Performance        | None               | Document only unless existing policy intended |

Use actual repository findings.

---

# 3. Core Guardrail: Wiring Is Not Enforcement Expansion

This phase must distinguish:

## 3.1 Migrating an existing check

An existing production check currently calls:

```php
BillingPolicyService
```

or:

```php
InvoiceItemSettlementService
```

and Phase 3 routes the same behaviour through:

```php
PaymentGateService
```

This is allowed.

## 3.2 Introducing a new check

A department currently has no payment gate, but Phase 3 adds one.

This is a behavioural expansion and must not happen automatically.

For departments without existing enforcement:

* Add reusable façade methods where necessary.
* Add stage context.
* Add integration documentation.
* Add focused contract tests.
* Do not insert new blocking calls into production workflows unless existing configuration and behaviour already clearly require them.

The acceptance criterion is not “every department now blocks for payment.”

The acceptance criterion is:

> Every existing payment check uses the central façade, and future enforcement points are explicitly defined.

---

# 4. Stage-Aware Payment Gate Context

Create a typed representation of the workflow stage being evaluated.

Suggested enum:

```text
app/Enums/PaymentGateStage.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum PaymentGateStage: string
{
    case ORDER = 'order';
    case ACCEPT = 'accept';
    case START = 'start';
    case PERFORM = 'perform';
    case RESULT = 'result';
    case VERIFY = 'verify';
    case COMPLETE = 'complete';
    case DISPENSE = 'dispense';
    case ISSUE = 'issue';
    case RELEASE = 'release';
    case RENDER = 'render';
    case READINESS = 'readiness';
    case FINANCIAL_CLOSURE = 'financial_closure';
}
```

Adjust values to the actual UHMS workflow vocabulary.

Do not add unused speculative values merely to make the list look complete.

Create a context DTO.

Suggested location:

```text
app/Data/Billing/PaymentGateContext.php
```

Suggested shape:

```php
final readonly class PaymentGateContext
{
    public function __construct(
        public PaymentGateStage $stage,
        public ?string $operation = null,
        public ?int $departmentId = null,
        public ?string $departmentType = null,
        public ?string $serviceType = null,
        public array $metadata = [],
    ) {
    }
}
```

Rules:

* Keep metadata bounded.
* Do not include patient names or clinical details.
* Do not require every caller to construct arbitrary arrays.
* Provide named constructors or factory methods where useful.
* Reuse canonical department-type enums where available.
* Do not use translated strings as operation identifiers.

---

# 5. PaymentGateService Contract Consolidation

Audit the existing `PaymentGateService` methods and consolidate them around a common internal evaluation path.

The façade may retain convenience methods such as:

```php
policyFor(...)
canRenderInvoiceItem(...)
assertCanRenderInvoiceItem(...)
canPerformInvestigation(...)
assertCanPerformInvestigation(...)
canDispense(...)
assertCanDispense(...)
```

But internally they should delegate to one core evaluator.

Suggested core method:

```php
public function evaluate(
    Visit $visit,
    ?InvoiceItem $invoiceItem,
    PaymentGateContext $context,
): BillingPolicyDecision;
```

Or, if the existing architecture resolves the visit from the invoice item:

```php
public function evaluateInvoiceItem(
    InvoiceItem $invoiceItem,
    PaymentGateContext $context,
): BillingPolicyDecision;
```

Rules:

* Preserve all existing public methods where tests or callers depend on them.
* Preserve return types.
* Preserve `BillingGateException`.
* Preserve legacy messages.
* Preserve legacy reason codes.
* Preserve current missing-item behaviour.
* Preserve current advisory behaviour.
* Preserve override handling.
* Do not return `VisitPaymentTimingDecision` as the operational result.
* Typed observation remains internal to `BillingPolicyService`.

---

# 6. Migrate Direct BillingPolicyService Callers

## 6.1 TriageController

Replace direct calls to:

```php
BillingPolicyService::getInvoiceItemPolicy(...)
```

with the corresponding `PaymentGateService` façade method.

Use an explicit stage such as:

```text
complete
render
readiness
```

based on the real triage workflow.

Preserve:

* Current invoice-item resolution
* Current missing-invoice behaviour
* Current exception message
* Current transaction boundaries
* Current route completion behaviour
* Current override behaviour
* Current legacy decision

Do not change the user-facing error message unless required to preserve the existing message through `BillingGateException`.

If the controller currently manually converts a decision into `RuntimeException`, either:

* Preserve that exact external behaviour through a compatibility wrapper, or
* Use `BillingGateException` only if all callers and tests confirm no observable behaviour changes.

Controllers should become thinner, not more complicated.

## 6.2 ConsultationNextPatientService

Replace direct billing-policy evaluation with `PaymentGateService`.

Use:

```text
READINESS
```

as the stage where appropriate.

Preserve the existing local result shape:

```php
[
    'allowed' => bool,
    'message' => ?string,
]
```

Do not change next-patient selection behaviour.

Do not change which service is treated as the first blocker.

Do not add new invoice queries.

Do not convert readiness into hard workflow enforcement.

---

# 7. Laboratory Settlement Reconciliation

The current laboratory path uses:

```php
LabRequestItem::isBillSettled()
```

which delegates to:

```php
InvoiceItemSettlementService::canProceedWithoutCashPayment()
```

This represents intrinsic settlement state, not the complete billing policy.

Phase 3 must separate these concepts clearly.

## 7.1 Preserve intrinsic settlement helpers

Do not remove the existing settlement service.

It should continue to answer questions such as:

```text
Is the invoice item already paid?
Is patient responsibility zero?
Is it fully covered?
Is it waived?
Is it cancelled?
Is it adjusted?
```

## 7.2 Add policy-aware laboratory decision

Introduce a service-level method through `PaymentGateService`, such as:

```php
public function policyForLabResultEntry(
    LabRequestItem $requestItem
): BillingPolicyDecision;
```

or:

```php
public function canEnterLabResult(
    LabRequestItem $requestItem
): bool;
```

and:

```php
public function assertCanEnterLabResult(
    LabRequestItem $requestItem
): void;
```

Use the actual workflow stage:

```text
RESULT
```

or:

```text
VERIFY
```

depending on where the current laboratory restriction applies.

## 7.3 Avoid model service-locator patterns

Do not inject application services directly into an Eloquent model through container resolution unless that is already an established project convention.

Prefer:

* A laboratory workflow service
* A domain service
* A controller/service caller
* A model method that remains intrinsic only

A good separation is:

```text
LabRequestItem::isBillSettled()
    → intrinsic financial state only

PaymentGateService::policyForLabResultEntry()
    → policy permission
```

## 7.4 Production wiring

If the current lab result-entry workflow actively uses `isBillSettled()` as a hard gate:

* Replace that workflow gate with `PaymentGateService`.
* Preserve the existing result for all currently supported scenarios.
* Keep `isBillSettled()` available for display and intrinsic-state use.
* Add regression tests proving no changed outcome.

If `isBillSettled()` is only used for display:

* Do not introduce a new hard gate.
* Add the policy-aware façade method and document the future integration point.

---

# 8. Missing Invoice and Invoice-Item Behaviour

Phase 2 confirmed inconsistent legacy behaviour:

* Triage may throw when a priced service lacks an invoice item.
* Some departmental façade methods fail open when they cannot resolve an invoice item.
* Laboratory intrinsic settlement may return true for a missing invoice item.

Phase 3 must document and preserve these behaviours.

Do not silently normalise them yet.

Create machine-readable diagnostic reason codes where needed:

```text
invoice_item_missing_allowed
invoice_item_missing_blocked
service_not_billable
service_unpriced
invoice_not_created
policy_not_applicable
```

These reason codes may be additive internal metadata.

Do not change the current operational result.

Create a clear compatibility matrix in the report.

---

# 9. Departmental Façade Inventory

Ensure `PaymentGateService` has explicit, tested methods for the intended workflow families.

Possible families include:

```text
consultation service
investigation
laboratory
radiology
pharmacy dispensing
procedure
theatre
treatment
nursing service
blood-bank issue
ambulance rendering
generic service rendering
```

However:

* Reuse a generic method where specialised methods add no meaningful behaviour.
* Do not create dozens of near-identical methods.
* Use typed stage context instead of duplicating logic.
* Specialised methods should mainly resolve the correct visit, invoice item, and stage.

For each façade method, document:

```text
input entity
invoice-item resolution strategy
missing-item behaviour
gate stage
current production caller
future intended caller
```

---

# 10. Emergency Stage Boundary Foundation

Phase 2 resolves emergency visits to `running_bill` under emergency stabilisation protection.

Phase 3 must define the technical context needed to distinguish:

```text
emergency stabilisation
post-stabilisation routine service
non-emergency service during an emergency visit
```

Do not implement a new clinical emergency classification system.

Reuse existing fields, statuses, route types, department types, or workflow markers where available.

If UHMS currently cannot distinguish stabilisation from later service delivery:

* Document the limitation.
* Keep current emergency behaviour unchanged.
* Add an explicit context placeholder such as:

```php
$isEmergencyStabilisation
```

only where it can be populated reliably.

* Do not invent a false distinction.

The typed resolver must not become more restrictive in this phase.

---

# 11. Observation Context Improvements

Extend Phase 2 observation so mismatch logs include stage-aware context.

Add:

```text
payment gate stage
operation code
department type
service type
invoice-item presence
```

Continue excluding:

* Patient name
* Patient phone
* Patient email
* Diagnosis
* Clinical notes
* Insurance member number
* Free-text override reasons

The comparison service must continue to use the unchanged legacy decision.

Observation failures must remain non-blocking.

---

# 12. Read-Only Workflow Coverage Command

Extend or add a read-only diagnostic command.

Preferred approach: extend:

```bash
php artisan billing:payment-timing-audit
```

with options such as:

```text
--show-call-sites
--show-stages
--show-missing-coverage
```

If static repository analysis does not belong in the runtime command, add a focused command such as:

```bash
php artisan billing:payment-gate-coverage
```

The command should report:

```text
registered façade operation
gate stage
production callers discovered or registered
missing production wiring
missing invoice-item resolution
legacy behaviour
```

The command must:

* Be read-only
* Create no invoices
* Create no payments
* Create no overrides
* Create no activity logs
* Avoid patient-sensitive output
* Exit successfully when uncovered workflows exist
* Use warnings rather than treating incomplete coverage as a command failure

Prefer a maintained registry over fragile runtime filesystem scanning if the project architecture supports one.

---

# 13. Optional Payment Gate Operation Registry

If useful, create a central registry describing supported operations.

Suggested class:

```text
app/Services/Billing/PaymentGateOperationRegistry.php
```

Possible entries:

```php
[
    'consultation.route.complete' => [
        'stage' => PaymentGateStage::COMPLETE,
        'department_type' => 'consultation',
    ],
    'consultation.next_patient.readiness' => [
        'stage' => PaymentGateStage::READINESS,
        'department_type' => 'consultation',
    ],
    'laboratory.result.enter' => [
        'stage' => PaymentGateStage::RESULT,
        'department_type' => 'investigation',
    ],
]
```

The registry should:

* Use stable machine identifiers.
* Avoid hardcoded translated labels.
* Support diagnostic reporting.
* Support future per-stage configuration.
* Not itself make payment decisions.
* Not become a second policy engine.

Do not add the registry if it would duplicate an existing workflow or capability registry.

---

# 14. Permissions and Audit

This phase should not introduce new user permissions unless a new admin diagnostic screen is added.

Normal gate evaluation should not create activity logs.

Continue using `ActivityLog` only for:

* Override mutations
* Configuration changes
* Approval actions
* Other material state changes

Do not audit every allow/block read.

If the coverage command or diagnostic UI is permission-protected, reuse an existing billing/reporting/admin permission where suitable.

Do not add a duplicate permission unnecessarily.

---

# 15. Performance Requirements

Legacy payment gates can run repeatedly.

Phase 3 must ensure:

## 15.1 No extra decision queries

Migrating callers to the façade must not introduce:

* Duplicate invoice-item queries
* Duplicate visit queries
* Duplicate override queries
* Duplicate settings queries
* Duplicate settlement calculations

## 15.2 Reuse loaded data

Where the caller already has:

```text
visit
invoice item
service
request item
department
```

pass them into the façade rather than resolving them again.

## 15.3 Avoid model-triggered hidden queries

Be careful with convenience methods on models.

Do not create hidden N+1 behaviour by resolving invoice items or overrides through uncached relations inside loops.

## 15.4 Query expectations

Add focused assertions showing:

```text
migrated direct caller:
same or fewer queries than before

legacy integration mode:
no typed observation queries

observe mode:
same bounded behaviour established in Phase 2
```

Do not overfit tests to unstable exact query counts unless the project already has a reliable pattern.

---

# 16. Failure Safety

If the façade cannot resolve an entity:

* Preserve the workflow’s current result.
* Return or throw the same current decision.
* Record an internal reason code where appropriate.
* Do not expose stack traces.
* Do not default every failure to allow.
* Do not default every failure to block.

If stage context creation fails:

* Continue using legacy policy evaluation.
* Do not alter the allow/block result.

If observation logging fails:

* Preserve the current workflow outcome.

If a migration from a direct caller creates any changed message, exception, or return shape:

* Restore compatibility before completing the phase.

---

# 17. Localisation

Add complete English and French localisation for new user-facing concepts, where applicable:

```text
payment gate stage
readiness
result entry
dispensing
service performance
service completion
payment policy unavailable
invoice item missing
payment gate diagnostic coverage
```

Machine identifiers, enum values, operation codes, and log reason codes must remain untranslated.

Maintain EN/FR parity.

---

# 18. Tests

Do not run the full Laravel or Playwright suites during this phase.

Add focused tests.

## 18.1 PaymentGateStage tests

Verify:

* Required stages exist.
* Stable values are used.
* Labels resolve through localisation only where labels are user-facing.
* No stage is inferred from translated strings.

## 18.2 Core façade tests

Verify:

* Core evaluation returns the exact legacy DTO.
* `can*` methods return the DTO’s `allowed`.
* `assert*` methods throw the existing exception when blocked.
* Existing messages remain unchanged.
* Existing reason codes remain unchanged.
* Existing override behaviour remains unchanged.
* Observation remains additive only.

## 18.3 Triage regression tests

Verify:

* Existing paid route service remains allowed.
* Existing unpaid route service remains blocked where currently blocked.
* Missing invoice behaviour remains unchanged.
* Existing override remains effective.
* The controller no longer calls `BillingPolicyService` directly.
* The result and message remain unchanged.

Avoid brittle implementation assertions where behavioural verification is sufficient.

## 18.4 Consultation readiness tests

Verify:

* First blocked service remains the blocker.
* Readiness result shape remains unchanged.
* Existing message remains unchanged.
* Paid and allowed services remain ready.
* Existing override remains effective.
* The service uses the façade.

## 18.5 Laboratory tests

Verify separately:

### Intrinsic settlement

* Paid item is settled.
* Fully covered item is settled.
* Waived item is settled.
* Cancelled or adjusted item follows existing rules.
* Missing item follows existing intrinsic behaviour.

### Policy permission

* Laboratory policy method returns the legacy billing decision.
* Existing result-entry workflow outcome remains unchanged.
* Narrow overrides remain narrow.
* Visit-wide overrides remain effective where already supported.
* Missing item behaviour remains compatible.
* Intrinsic settlement and policy permission are not treated as identical concepts.

## 18.6 Stage-context observation tests

Verify:

* Stage appears in comparison context.
* Operation code appears in bounded logs.
* Sensitive data is absent.
* Stage context does not alter the legacy result.
* Logging failure remains harmless.

## 18.7 Performance tests

Verify:

* Legacy mode adds no typed resolution queries.
* Migrated callers do not duplicate invoice resolution.
* Observe-mode override lookup remains memoised.
* Looping through several services avoids obvious N+1 regressions.

## 18.8 Diagnostic command tests

Verify:

* Coverage output lists known wired operations.
* Missing production wiring is reported as warning/information.
* Command performs no writes.
* Command emits no activity logs.
* JSON output is valid if supported.
* Incomplete coverage does not create a non-zero exit code.

## 18.9 Localisation tests

Verify all new EN/FR keys are present.

---

# 19. Targeted Verification

Run focused checks only.

Suggested commands:

```bash
php artisan config:clear
php artisan view:clear
php artisan route:list

php artisan test --filter=PaymentGateStage
php artisan test --filter=PaymentGateService
php artisan test --filter=TriagePayment
php artisan test --filter=ConsultationPaymentReadiness
php artisan test --filter=LaboratoryPaymentGate
php artisan test --filter=PaymentGateCoverage
```

Also rerun:

```bash
php artisan test --filter=PaymentTiming
php artisan test --filter=BillingPaymentPolicy
php artisan test --filter=PreviousBalancePolicy
```

Run the diagnostic commands:

```bash
php artisan billing:payment-timing-audit --limit=25
```

and, if introduced:

```bash
php artisan billing:payment-gate-coverage
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

Broad verification remains deferred until the full payment-policy implementation batch is complete.

---

# 20. Documentation

Create:

```text
docs/billing/PAYMENT_TIMING_POLICY_PHASE_3_REPORT.md
```

The report must include:

1. Complete payment-check call-site audit
2. Departmental workflow-stage matrix
3. Existing enforcement versus display-only classification
4. Files created
5. Files modified
6. `PaymentGateStage` design
7. `PaymentGateContext` design
8. Payment façade consolidation
9. Triage migration
10. Consultation readiness migration
11. Laboratory settlement-versus-policy separation
12. Missing invoice compatibility matrix
13. Departmental façade inventory
14. Production-wired versus unwired operations
15. Emergency-stage limitations
16. Observation-context changes
17. Performance and query impact
18. Diagnostic command output
19. Focused tests and results
20. Confirmation that allow/block outcomes remain unchanged
21. Recommended requirements for Phase 4

Do not claim tests or checks passed unless they were actually executed.

---

# 21. Guardrails

Do not:

* Add patient financial-risk profiles
* Add risk-level enums or tables
* Add per-visit payment-policy persistence
* Add new visit override approval workflows
* Add insurer-specific timing resolution
* Add corporate timing resolution
* Add sponsor timing resolution
* Enable typed decisions as operational authority
* Add a typed enforcement mode
* Change `billing_policy.enforce`
* Change default OPD prepayment behaviour
* Change emergency running-bill behaviour
* Change admission running-bill behaviour
* Change payment calculations
* Change receivable calculations
* Change payment allocation
* Change invoice posting
* Change previous-balance behaviour
* Change visit completion
* Change discharge
* Add new departmental payment blocking where none currently exists
* Remove intrinsic settlement helpers
* Move application services into models through hidden container resolution
* Create routine activity logs for gate reads
* Run the full application test suites

---

# 22. Acceptance Criteria

Phase 3 is complete only when:

* Every current direct `BillingPolicyService` production caller has been evaluated.
* Appropriate direct callers now use `PaymentGateService`.
* Existing public gate contracts remain compatible.
* Existing allow/block/advisory outcomes remain unchanged.
* Existing exceptions and messages remain unchanged.
* A typed payment-gate stage vocabulary exists.
* A bounded stage-aware gate context exists.
* Triage payment checks use the façade.
* Consultation next-patient readiness uses the façade.
* Laboratory intrinsic settlement and policy permission are explicitly separated.
* Laboratory production behaviour remains unchanged.
* Existing missing-invoice behaviour is documented and preserved.
* Existing override scope is preserved.
* No narrow override is broadened.
* No new department begins blocking solely because of Phase 3.
* Intended departmental façade operations are inventoried.
* Production-wired and unwired operations are clearly reported.
* Observation logs include stage context without sensitive data.
* Legacy mode remains operationally lightweight.
* Focused tests pass.
* The Phase 3 report accurately documents all findings and verification.

Proceed with **Payment Timing Policy Phase 3 only**.

After implementation, provide:

1. A concise implementation summary
2. The final call-site and workflow-stage audit
3. Files created and modified
4. Triage migration details
5. Consultation readiness migration details
6. Laboratory reconciliation details
7. Production-wired and unwired payment stages
8. Query and performance impact
9. Diagnostic command results
10. Focused test results
11. Confirmation that payment outcomes remain unchanged
12. The Phase 3 report path
13. Recommended requirements for Phase 4

Then stop after Phase 3.
