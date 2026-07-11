# Payment Timing Policy — Phase 4 Report

**Departmental Enforcement Policy Registry, Stage Rules, and Safe Cutover Controls**

Date: 2026-07-11 · Branch: `beta-x` · Status: Complete (diagnostics/configuration only)

---

## 1. Scope & Intent

Phase 4 builds a **configurable, auditable departmental payment-enforcement
policy foundation**. It records, per workflow operation, how that operation
*would* relate to payment enforcement, and diagnoses how far each operation is
from a safe typed cutover. It is **foundation only**.

This phase deliberately changes **no production payment behaviour**.

## 2. Non-Goals (Hard Guardrails Honoured)

The following were explicitly **NOT** done:

- ❌ No new departmental payment blocking activated.
- ❌ No typed payment timing promoted to operational authority.
- ❌ No production allow→block / block→allow / advisory→hard-block /
  unwired→wired behaviour change.
- ❌ No `typed` / `active` / `enforce_typed` mode added (mode enum is
  `disabled` / `observe` / `legacy` only).
- ❌ The 9 unwired operations remain **unwired**.
- ❌ Existing laboratory and pharmacy paid-only behaviour unchanged.
- ❌ Emergency / inpatient runtime behaviour unchanged; no emergency
  stabilisation fields invented without clinical design.
- ❌ No activity logs created for policy reads.
- ❌ No change to invoices, receivables, payment allocation, GL, previous-balance
  policy, visit completion, discharge, or financial closure.

## 3. Mandatory Pre-Work Audit

Before writing code, the existing payment-gate surface was audited: the
`PaymentGateOperationRegistry`, `PaymentGateService`, the four production-wired
hard gates (2× consultation, laboratory result entry, pharmacy dispense), and
the nine unwired stages. The audit confirmed the three concepts that must never
be collapsed: **visit payment-timing policy**, **departmental enforcement
operation**, and **invoice-item settlement state**.

## 4. What Was Built

### Enums (`app/Enums/`)
- `PaymentGateOperationMode` — `disabled` / `observe` / `legacy`. **No typed
  mode.**
- `MissingBillingContextPolicy` — `preserve_legacy` / `allow` / `block` /
  `not_applicable`.
- `PaymentGateVisitContextRule` — `use_visit_policy` / `always_running_bill` /
  `preserve_legacy` / `not_applicable`.
- `PaymentGateOverrideScopeRule` — `none` / `invoice_item_only` /
  `service_or_item` / `department_service_or_item` / `visit_wide` /
  `preserve_legacy`.

All labels resolve through `lang/*/payment_gate.php`.

### DTOs (`app/Data/Billing/`)
- `PaymentGateOperationPolicy` — final readonly resolved policy for one
  operation, plus `::unknown()` safe (DISABLED) fallback.
- `PaymentGateEnforcementEligibility` — eligibility verdict + status + reasons.

### Registry (`app/Services/Billing/PaymentGateOperationRegistry.php`)
13 operations keyed by code. Original six keys retained for backward
compatibility; added department/workflow/mode/context/override/eligibility
metadata. Every operation carries `approved_for_typed_enforcement = false`
(provisional). Wired hard gates default to `legacy`; unwired default to
`disabled`.

### Services
- `PaymentGateOperationConfigurationService` — merges stored settings over
  registry defaults (group `payment_gate_operations`, JSON per operation).
  **Refuses to relax a wired hard gate to `disabled`** — clamps back to the
  legacy default and logs a warning. Invalid stored values fall back to
  defaults. Reads settings + registry only.
- `PaymentGateEnforcementEligibilityService` — diagnostic. In Phase 4 **no
  operation is eligible** (none approved for cutover).
- `PaymentGateOperationCompatibilityService` — diagnostic. `operational` is
  always `false`.

### Console commands
- `billing:payment-gate-coverage` (extended) — filters + config mode +
  eligibility columns. Read-only.
- `billing:payment-gate-policy-audit` (new) — flags unsafe/inconsistent
  combinations. Warnings **do not** fail the exit code; it fails only on a
  technical error. Read-only.

### Admin UI
- `SettingsController@paymentGateOperations` / `@updatePaymentGateOperations`.
- `resources/views/settings/payment-gate-operations.blade.php` — grouped by
  workflow family; per-operation diagnostics + editable form for unwired
  operations only. Wired hard gates render **read-only**. No inline JS.
- Routes `admin.settings.payment-gate-operations[.update]` under
  `can:settings.manage` (reuses the existing permission — none added).
- Sidebar link added.
- `UpdatePaymentGateOperationSettingsRequest` — validates enums/booleans;
  rejects duplicates, unknown operations, and any wired/hard-gate operation.

### Seeder
- `PaymentGateOperationSettingsSeeder` — idempotent (`firstOrCreate`); never
  overwrites administrator values; always seeds `typed_enforcement_eligible =
  false`. Registered in `DatabaseSeeder`.

### Localisation
- `lang/en/payment_gate.php` and `lang/fr/payment_gate.php` — full parity
  (verified by test + the project `LanguageParityTest` gate).

## 5. Audit Behaviour

Admin updates run inside a `DB::transaction`, diff each submitted operation's
resolved JSON against the current stored value, `Setting::setValue` **only
changed** operations, and log a single
`PAYMENT_GATE_OPERATION_SETTINGS_UPDATED` activity event (module `SETTINGS`)
with `setting_group`, changed `setting_keys`, `old_values`, `new_values` — only
when a change occurred. Reads are never audited.

## 6. Verification Performed (focused only — full suites not run)

| Check | Result |
|---|---|
| `php -l` on new/changed PHP | ✅ no syntax errors |
| `route:list` payment-gate-operations | ✅ GET + PUT registered |
| `view:cache` (blade compile) | ✅ compiled |
| `PaymentGateOperationPolicyTest` (16) + `PaymentGateCoverageTest` (1) | ✅ 17 passed |
| Regression: PaymentTiming / PaymentGateService / LaboratoryPaymentGate / Pharmacy / PreviousBalance / BillingPaymentPolicy | ✅ passed |
| `LanguageParityTest` gate | ✅ passed (86 total in batch) |
| `billing:payment-gate-coverage` | ✅ 13 registered · 4 wired · 9 unwired · 0 eligible |
| `billing:payment-gate-policy-audit` | ✅ read-only, advisory findings only, exit 0 |
| `git diff --check` | ✅ whitespace-clean |

The advisory audit findings (5) are the *expected* provisional state:
emergency-sensitive operations without a clinically designed stabilisation
boundary, and the compatibility-protected lab/pharmacy hard gates — precisely
the conditions that keep them **ineligible** for cutover.

## 7. Current State Snapshot

- Registered operations: **13** (4 production-wired hard gates, 9 unwired).
- Eligible for typed enforcement: **0** (by design this phase).
- All operations: **configuration-non-operational**.
- Existing wired gates: protected and read-only; cannot be relaxed via config.

## 8. What A Future Phase Would Need

Before any operation can be cut over: hospital approval per operation
(`approved_for_typed_enforcement`), confirmed invoice-item resolution, a
clinically designed emergency stabilisation boundary, and an operational typed
mode — none of which exist yet and none of which this phase introduces.
