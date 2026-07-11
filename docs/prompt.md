# UHMS Implementation Prompt — Phase 1

## Payment Timing Policy Foundation and Global Configuration

You are working on **UHMS**, a Laravel-based hospital management system.

Implement **Phase 1 of the Configurable Payment Timing and Per-Visit Payment Policy system**.

This phase establishes the policy vocabulary, central configuration, administrative settings foundation, localisation, and supporting architecture.

This phase must **not yet enforce payment blocking**, modify service-delivery behaviour, or add per-visit overrides. Those will be implemented in later phases.

---

# 1. Goal

Create a central, reusable payment-timing policy foundation that allows UHMS to represent the following workflows:

1. **Pay Before Service**

   * Payment is required before applicable billable services proceed.

2. **Pay After All Services**

   * The patient may complete all services before final payment.
   * Charges continue to accumulate on the existing visit invoice.

3. **Running Bill**

   * Services continue while charges accumulate.
   * Partial or periodic payments may be recorded.
   * Intended primarily for inpatient and emergency workflows.

4. **Inherit**

   * The final policy will later be resolved from:

     * Per-visit policy
     * Patient financial-risk status
     * Insurance, sponsor, or corporate arrangement
     * Visit type
     * Global configuration

The implementation must provide a stable foundation for later per-visit resolution and enforcement.

---

# 2. Audit the Existing Architecture First

Before modifying code, inspect the current UHMS architecture for:

* Existing billing configuration
* Existing system-settings infrastructure
* Existing enums used for billing, visits, invoices, payment status, and visit types
* Existing visit-type representation
* Existing insurance, sponsor, waiver, credit, and corporate-account handling
* Existing emergency and admission billing behaviour
* Existing `InvoiceReceivable` implementation
* Existing `PaymentService`
* Existing previous-balance services
* Existing billing override services
* Existing permission registration conventions
* Existing `ActivityLog` conventions
* Existing English and French localisation structure
* Existing admin settings pages, controllers, services, and persistence patterns

Reuse the current architecture wherever possible.

Do not introduce a second settings framework, a second ledger, or a parallel billing subsystem.

If an equivalent enum, service, setting group, or configuration abstraction already exists, extend or adapt it instead of duplicating it.

Document the relevant existing architecture in the phase report.

---

# 3. Core Domain Rules

The following rules are mandatory.

## 3.1 Payment timing is not payment status

Payment timing describes **when payment is required**.

It must remain separate from:

* Invoice status
* Payment status
* Service status
* Consultation status
* Visit status
* Admission status
* Clinical completion
* Financial clearance

Do not overload existing invoice or visit status fields to represent payment timing.

## 3.2 No enforcement in this phase

This phase must not:

* Block consultation work
* Block investigations
* Block pharmacy dispensing
* Block procedures
* Block treatments
* Block admission services
* Change visit completion behaviour
* Change discharge behaviour
* Change invoice settlement behaviour
* Automatically mark anything as paid
* Add per-visit payment overrides
* Add patient financial-risk classifications

All current billing and service behaviour must remain unchanged after this phase.

## 3.3 Existing accounting integrity must remain intact

Do not bypass or duplicate:

* Existing invoice creation
* Existing invoice items
* Existing patient-responsibility calculations
* Existing insurer-responsibility calculations
* Existing receivable records
* Existing payments
* Existing journal or general-ledger entries
* Existing payment allocation behaviour

The new policy foundation is advisory and configurational only in this phase.

---

# 4. Payment Timing Enum

Create an enum following the project’s existing enum conventions.

Suggested location:

```text
app/Enums/VisitPaymentTimingPolicy.php
```

Required values:

```php
<?php

namespace App\Enums;

enum VisitPaymentTimingPolicy: string
{
    case INHERIT = 'inherit';
    case PAY_BEFORE_SERVICE = 'pay_before_service';
    case PAY_AFTER_ALL_SERVICES = 'pay_after_all_services';
    case RUNNING_BILL = 'running_bill';
}
```

Add appropriate helper methods only where consistent with the project’s enum conventions.

Possible helpers include:

```php
public function label(): string;
public function translationKey(): string;
public static function selectable(): array;
public static function operationalPolicies(): array;
```

Rules:

* `inherit` must be available for visit-type configuration.
* `inherit` should not be used as the final operational policy after resolution.
* Do not hardcode English labels inside controllers or views.
* Use localisation keys for user-facing labels.
* Do not add speculative policy values outside the four required values.

---

# 5. Payment Policy Source Enum

Create an enum representing where a resolved payment policy originates.

Suggested location:

```text
app/Enums/VisitPaymentPolicySource.php
```

Required values:

```php
<?php

namespace App\Enums;

enum VisitPaymentPolicySource: string
{
    case GLOBAL_DEFAULT = 'global_default';
    case VISIT_TYPE = 'visit_type';
    case PATIENT_RISK = 'patient_risk';
    case INSURANCE = 'insurance';
    case CORPORATE_ACCOUNT = 'corporate_account';
    case MANUAL_OVERRIDE = 'manual_override';
    case EMERGENCY_POLICY = 'emergency_policy';
}
```

This enum is foundational only in Phase 1.

Do not yet persist a source against visits or perform source resolution.

Add localisation-ready label helpers where consistent with existing enum patterns.

---

# 6. Central Payment Timing Configuration

Create or extend the appropriate configuration file.

Preferred location when no equivalent configuration exists:

```text
config/payment_timing.php
```

Suggested initial structure:

```php
<?php

use App\Enums\VisitPaymentTimingPolicy;

return [
    'enabled' => false,

    'default_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,

    'visit_types' => [
        'outpatient' => VisitPaymentTimingPolicy::INHERIT->value,
        'emergency' => VisitPaymentTimingPolicy::RUNNING_BILL->value,
        'inpatient' => VisitPaymentTimingPolicy::RUNNING_BILL->value,
    ],

    'emergency' => [
        'never_block_stabilisation' => true,
        'default_policy' => VisitPaymentTimingPolicy::RUNNING_BILL->value,
    ],

    'financial_closure' => [
        'require_settlement_for_pay_after_services' => true,
        'require_settlement_for_running_bill' => true,
        'allow_authorised_outstanding_balance_override' => true,
    ],

    'risk' => [
        'normal_policy' => VisitPaymentTimingPolicy::INHERIT->value,
        'watchlist_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
        'high_risk_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
        'blocked_credit_policy' => VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE->value,
    ],
];
```

Adjust visit-type keys to match the actual existing UHMS visit-type implementation.

Do not invent a second visit-type vocabulary when one already exists.

The configuration should support future expansion without requiring policy logic to be hardcoded inside controllers.

---

# 7. Database-Backed Global Settings

UHMS should ultimately allow administrators to configure payment timing from the system interface.

Audit the existing system-settings architecture and integrate with it.

## 7.1 Reuse existing settings infrastructure

If UHMS already has:

* A system settings table
* Typed settings
* Settings groups
* Cached settings
* A configuration service
* An admin settings controller
* Settings permissions
* Settings audit logging

then extend those existing structures.

Do not create a separate `payment_settings` system unless the existing architecture genuinely cannot support the requirements.

## 7.2 Required setting keys

Add equivalent settings for:

```text
payment_timing.enabled
payment_timing.default_policy
payment_timing.outpatient_policy
payment_timing.inpatient_policy
payment_timing.emergency_policy
payment_timing.emergency_never_block_stabilisation
payment_timing.require_settlement_for_pay_after_services
payment_timing.require_settlement_for_running_bill
payment_timing.allow_outstanding_balance_override
```

Use the project’s established naming and storage conventions where they differ.

## 7.3 Safe defaults

The feature must initially be disabled or operate in a non-enforcing compatibility mode.

Deployment of Phase 1 must not suddenly change existing billing behaviour.

Existing UHMS behaviour must remain the effective operational behaviour until later enforcement phases are explicitly enabled.

## 7.4 Typed validation

Validate policy values using the enum.

Use Laravel enum validation where compatible with the project:

```php
Rule::enum(VisitPaymentTimingPolicy::class)
```

Additional validation rules:

* The global default must be an operational policy.
* The global default must not be `inherit`.
* Visit-type defaults may use `inherit`.
* Emergency protection must not accept an invalid value.
* Boolean settings must be normalised according to existing settings conventions.

---

# 8. Configuration Access Service

Create a central service that provides typed access to payment-timing settings.

Suggested location:

```text
app/Services/Billing/PaymentTimingConfigurationService.php
```

Adapt the namespace to the existing billing service architecture.

Suggested responsibilities:

```php
final class PaymentTimingConfigurationService
{
    public function enabled(): bool;

    public function globalDefault(): VisitPaymentTimingPolicy;

    public function policyForVisitType(string|BackedEnum $visitType): VisitPaymentTimingPolicy;

    public function emergencyDefault(): VisitPaymentTimingPolicy;

    public function neverBlockEmergencyStabilisation(): bool;

    public function requiresSettlementForPayAfterServices(): bool;

    public function requiresSettlementForRunningBill(): bool;

    public function allowsOutstandingBalanceOverride(): bool;
}
```

Rules:

* Read database-backed settings through the existing settings service where available.
* Fall back safely to configuration defaults.
* Return typed enum values rather than arbitrary strings.
* Handle invalid or legacy stored values safely.
* Record or report invalid configuration according to existing project conventions.
* Do not perform visit-specific policy resolution in this service.
* Do not inspect patient risk.
* Do not inspect insurance.
* Do not inspect invoices.
* Do not enforce payment.

This is a configuration reader, not the final policy resolver.

---

# 9. Admin Configuration Interface

Extend the appropriate existing admin settings page with a section titled:

```text
Payment Timing Policies
```

The interface should support:

## 9.1 Feature status

```text
Enable configurable payment timing
```

The setting must remain non-enforcing until later phases introduce the enforcement feature flag and central payment gate.

## 9.2 Global default

Options:

```text
Pay Before Service
Pay After All Services
Running Bill
```

Do not offer `Use System Default` for the global default.

## 9.3 Visit-type defaults

At minimum, configure the actual existing equivalents of:

```text
Outpatient
Inpatient
Emergency
```

Options:

```text
Use System Default
Pay Before Service
Pay After All Services
Running Bill
```

`Use System Default` must store `inherit`.

Do not hardcode visit types in the Blade view if the project already exposes visit types through enums, configuration, or a registry.

## 9.4 Emergency protection

Show:

```text
Never block emergency stabilisation because payment is pending
```

This setting should default to enabled.

The interface should explain that final billing or financial clearance can still occur later.

## 9.5 Financial-closure preparation

Add settings for:

```text
Require settlement before financial closure for Pay After All Services
Require settlement before financial closure for Running Bill
Allow authorised outstanding-balance closure overrides
```

These settings are preparatory only in this phase.

They must not yet alter visit completion or discharge.

## 9.6 UI requirements

Follow the existing UHMS admin design system.

Do not introduce:

* A new UI framework
* A new JavaScript framework
* Unnecessary modal workflows
* Inline JavaScript handlers
* Hardcoded untranslated labels

Display concise descriptions for each policy so administrators understand the operational difference.

---

# 10. Permissions

Reuse the existing settings permission if the application already has a suitable permission such as:

```text
settings.manage
billing.settings.manage
```

If a dedicated permission is required, add:

```text
billing.payment_timing.manage
```

Optionally add a view-only permission only where the project already separates viewing and management:

```text
billing.payment_timing.view
```

Rules:

* Permission registration must follow existing seeders and conventions.
* Do not duplicate equivalent permissions.
* Existing super-admin behaviour must remain intact.
* Unauthorised users must not be able to update the settings endpoint directly.
* Protect the backend request, not only the interface.

---

# 11. Audit Logging

All database-backed payment-timing setting changes must use the existing `ActivityLog` architecture.

Record:

```text
setting group
setting key
old value
new value
changed by
date and time
reason, where the settings framework supports it
```

Avoid logging unchanged values.

Do not store sensitive patient information because this phase contains no patient-specific policy data.

Suggested audit action:

```text
payment_timing_settings_updated
```

Use the project’s existing audit event naming conventions where different.

---

# 12. Localisation

Add complete English and French localisation.

Use the existing billing or settings language files where appropriate. Create a focused language file only if consistent with project organisation.

Required concepts include:

```text
Payment Timing Policies
Enable configurable payment timing
Global default
Visit-type defaults
Use System Default
Pay Before Service
Pay After All Services
Running Bill
Emergency protection
Never block emergency stabilisation
Financial closure
Require settlement before financial closure
Allow authorised outstanding-balance overrides
Settings updated successfully
Invalid payment timing policy
```

Policy descriptions should explain:

### Pay Before Service

```text
Payment is required before applicable billable services proceed.
```

### Pay After All Services

```text
The patient may complete services before making the final payment.
```

### Running Bill

```text
Charges accumulate while services continue, and partial payments may be recorded.
```

### Use System Default

```text
Use the hospital-wide payment timing policy.
```

Ensure all newly introduced user-facing strings are covered by English and French localisation.

---

# 13. Validation and Failure Handling

The settings update must:

* Reject invalid enum values
* Reject `inherit` as the global default
* Allow `inherit` for visit-type defaults
* Reject unknown visit-type keys where applicable
* Preserve existing valid settings when validation fails
* Return the normal UHMS validation response
* Avoid partial settings updates unless the existing settings system intentionally supports them
* Audit only successful changes

The configuration access service must fall back safely when:

* A setting is absent
* A stored policy contains an invalid legacy value
* The settings table has not yet been seeded
* Cached settings are stale
* A visit type has no explicit configuration

Do not cause a fatal error because of invalid stored configuration.

---

# 14. Migration and Seeding

Where database settings require seed records:

* Use the existing settings seeder conventions.
* Make the seeding idempotent.
* Do not overwrite an administrator’s existing value during later deployments.
* Use `firstOrCreate`, equivalent upsert logic, or the established project helper.
* Do not create duplicate setting records.
* Ensure rollback behaviour is safe.

If no schema change is required because the existing settings table supports arbitrary keys, do not create an unnecessary migration.

Document the decision.

---

# 15. Tests

Do not run the complete UHMS test suite during this phase.

Add focused tests only.

## 15.1 Enum tests

Verify:

* All required policy values exist.
* All required source values exist.
* Operational policies exclude `inherit`.
* Labels resolve through localisation.

## 15.2 Configuration service tests

Verify:

* Global default resolves correctly.
* Visit type can inherit the global default.
* Explicit visit-type policies resolve correctly.
* Invalid stored values fall back safely.
* Emergency protection defaults to enabled.
* Database settings override config defaults where expected.

## 15.3 Admin settings tests

Verify:

* Authorised user can view the payment-timing settings.
* Authorised user can update valid settings.
* Unauthorised user cannot update settings.
* Global default rejects `inherit`.
* Visit-type policy accepts `inherit`.
* Invalid policy values are rejected.
* Successful updates create an audit record.
* Failed validation does not create an audit record.

## 15.4 Localisation safety

Verify that the new English and French keys are present.

## 15.5 Regression safety

Add a focused test proving that merely enabling or configuring Phase 1 does not yet block an existing billable service workflow.

Do not add broad cross-module enforcement tests yet.

---

# 16. Targeted Verification

Run only the minimum checks necessary for this phase:

```bash
php artisan migrate
php artisan route:list
php artisan config:clear
php artisan view:clear
php artisan test --filter=PaymentTiming
php artisan test --filter=PaymentTimingConfiguration
php artisan test --filter=PaymentTimingSettings
```

Adjust test class names to the actual implementation.

Also run the project’s existing:

* Permission registration check
* Localisation lock check
* Activity-log integrity check
* Blade or Inertia compilation check

Do not run the entire Laravel suite or full Playwright suite yet.

The wide Laravel and Playwright suites will run once after the complete payment-policy implementation batch.

---

# 17. Documentation

Create a phase report following the existing UHMS documentation conventions.

Suggested filename:

```text
docs/billing/PAYMENT_TIMING_POLICY_PHASE_1_REPORT.md
```

The report must include:

1. Existing architecture reviewed
2. Files created
3. Files modified
4. Enum definitions
5. Configuration keys
6. Database settings integration
7. Admin interface changes
8. Permissions
9. Audit behaviour
10. Localisation coverage
11. Focused tests added
12. Verification commands and results
13. Confirmation that service enforcement was not introduced
14. Deferred work for Phase 2

Do not claim tests passed unless they were actually executed successfully.

---

# 18. Implementation Guardrails

The implementation must be:

* Additive
* Typed
* Auditable
* Localised
* Permission-controlled
* Backward compatible
* Reusable by later phases

Do not:

* Rewrite existing billing logic
* Create a new invoice system
* Create a new payment ledger
* Duplicate the existing settings framework
* Hardcode policy decisions in controllers
* Hardcode user-facing labels
* Introduce payment blocking
* Add patient financial-risk fields
* Add per-visit payment policy records
* Add override approval workflows
* Change emergency workflows
* Change admission workflows
* Change visit completion
* Change discharge
* Change financial closure
* Run the full test suite during this phase

Controllers should remain thin.

Use services, enums, existing settings abstractions, policies, form requests, and existing audit infrastructure.

---

# 19. Acceptance Criteria

Phase 1 is complete only when:

* `VisitPaymentTimingPolicy` exists with the four required values.
* `VisitPaymentPolicySource` exists with all required source values.
* UHMS has a central payment-timing configuration.
* Database-backed settings integrate with the existing settings architecture.
* The global default cannot be `inherit`.
* Visit-type settings can inherit the global default.
* Typed access is provided through a configuration service.
* Administrators can configure payment timing through the existing settings interface.
* Settings changes are permission-protected.
* Settings changes are audited.
* All new UI text exists in English and French.
* Invalid stored settings fail safely.
* Seeding is idempotent.
* Existing billing and service workflows behave exactly as before.
* No payment enforcement has been introduced.
* Focused tests pass.
* The phase report accurately documents the implementation and verification results.

Proceed with **Payment Timing Policy Phase 1 only**.

Do not implement patient financial-risk classification, visit-level policy storage, policy resolution, overrides, or service enforcement yet.

After completing the implementation and focused verification, provide:

1. A concise implementation summary
2. Files created and modified
3. Architectural decisions made
4. Migration and seeding results
5. Focused test results
6. Any discovered legacy constraints
7. The Phase 1 report path
8. Recommended considerations for Phase 2

Then stop and wait for the next phase instruction.
