# UHMS Implementation Prompt — Payment Timing Policy Phase 9

## Financial Clearance, Conditional Closure, Outstanding-Balance Approval, and Receivable Preservation

You are working on **UHMS**, a Laravel-based hospital management system.

Implement **Phase 9 of the Configurable Payment Timing and Per-Visit Payment Policy system**.

Phases 1–8 are complete.

Phase 9 completes the payment-timing implementation batch by introducing financial clearance and administrative financial closure for visits whose services may be completed before final payment.

---

# 1. Existing Foundation

## Phase 1 — Payment-timing configuration

Implemented:

* `VisitPaymentTimingPolicy`
* `VisitPaymentPolicySource`
* Global payment-timing policy
* Visit-type payment-timing configuration
* Database-backed settings
* Admin configuration
* Permission protection
* English/French localisation
* Audit logging

## Phase 2 — Legacy integration

Implemented:

* Legacy and observation integration modes
* `VisitPaymentTimingDecision`
* `VisitPaymentTimingResolver`
* Legacy compatibility mapping
* Decision comparison and diagnostics

## Phase 3 — Central payment façade

Implemented:

* `PaymentGateStage`
* `PaymentGateContext`
* Production payment checks centralised through `PaymentGateService`
* Maintained operation registry and coverage diagnostics

Currently wired operations:

```text
consultation.route.complete
consultation.next_patient.readiness
laboratory.result.enter
pharmacy.item.dispense
```

## Phase 4 — Departmental policy registry

Implemented:

* Per-operation payment policy
* Missing-billing-context rules
* Visit-context rules
* Override-scope rules
* Eligibility and compatibility services
* Configuration UI and audit

## Phase 5 — Patient financial-risk profiles

Implemented:

* Financial-risk classification
* Restricted visibility
* Review, suspension, clearance, and expiry
* Immutable history
* Audit and reporting

## Phase 6 — Visit-level materialisation

Implemented:

* One observational payment-policy record per visit
* Baseline typed policy
* Risk recommendation
* Risk snapshot
* Append-only policy history
* Backfill, refresh, and diagnostics

## Phase 7 — Approved visit arrangements

Implemented:

* Per-visit arrangement requests
* Approval, rejection, withdrawal, replacement, revocation, and expiry
* Maker-checker workflow
* Risk-based approval requirements
* Current approved-arrangement linkage
* Administrative-only arrangement history

## Phase 8 — Operational typed cutover

Implemented:

* Master cutover mode:

  * `disabled`
  * `observe`
  * `active`
* Environment force-legacy kill switch
* Per-operation typed mode
* Approved-arrangement precedence
* Typed gate decisions
* Automatic legacy fallback
* Admin activation and rollback
* Typed laboratory and pharmacy compatibility handling
* Emergency visits remaining legacy
* Nine unwired operations remaining unwired

Typed policy can now allow services to proceed under:

```text
pay_after_all_services
running_bill
```

without marking the relevant invoice item as paid.

---

# 2. Phase 9 Goal

Implement a secure and auditable **visit financial-clearance and administrative financial-closure subsystem**.

The subsystem must:

* Assess whether the visit’s patient-responsibility balance is settled
* Distinguish clinical completion from financial clearance
* Distinguish discharge from financial closure
* Allow services and clinical sessions to complete independently of settlement
* Support visits operating under:

  * `pay_before_service`
  * `pay_after_all_services`
  * `running_bill`
* Allow authorised conditional clearance with an outstanding balance
* Preserve all outstanding debt in the existing receivable ledger
* Support payment-plan, approved outstanding-balance, insurance-pending, and corporate-guarantee clearance bases where appropriate
* Maintain immutable clearance and approval history
* Reopen or mark clearance stale when new financial activity occurs
* Provide finance worklists, reports, commands, and audit trails
* Remain independently reversible
* Never falsely mark an invoice or receivable as paid

The new financial-clearance subsystem may control only the new **administrative financial-close action**.

It must not block:

* Clinical consultation completion
* Session completion
* Clinical reopening
* Same-day outpatient reopening
* Active inpatient sessions
* Clinical discharge
* Emergency care
* Re-admission workflows
* New clinical work permitted by existing visit/session rules

---

# 3. Mandatory Domain Separation

UHMS must keep these concepts separate:

```text
Clinical work completed
Consultation/session completed
Patient clinically discharged
Visit administratively active/completed
Invoice settled
Patient responsibility cleared
Financial clearance conditionally approved
Visit financially closed
Outstanding receivable still collectible
```

Important examples:

## Example 1 — Pay after all services

* The patient completes consultation, laboratory, pharmacy, and procedure work.
* The clinical workflow is complete.
* The invoice remains unpaid.
* Financial clearance remains pending.
* The visit is not financially closed.
* Finance later receives payment or approves a conditional clearance.

## Example 2 — Inpatient running bill

* The patient receives services while admitted.
* Partial payments may be recorded.
* The clinician may discharge the patient.
* Discharge does not mean the invoice is settled.
* Finance clearance and financial closure remain separate.

## Example 3 — Approved outstanding balance

* Finance approves closure with GHS 500 outstanding.
* The visit becomes conditionally cleared.
* The GHS 500 receivable remains open.
* The system does not create a payment, waiver, credit note, or adjustment.
* Collection may continue after financial closure.

## Example 4 — Reopened consultation

* An outpatient visit is clinically reopened within the existing allowed period.
* New billable items are added.
* The previous financial clearance becomes stale or reopened.
* Clinical reopening is not blocked by the financial-close record.

---

# 4. Mandatory Architecture Audit

Before modifying code, inspect the following.

## 4.1 Visit lifecycle

Audit:

* `Visit`
* Visit statuses
* Consultation completion
* Session completion
* Automatic consultation completion
* Same-day outpatient reopening
* Next-day outpatient restrictions
* Inpatient active-session behaviour
* Inpatient discharge
* Discharge reversal or readmission
* Visit cancellation
* Visit reopening
* Existing administrative completion
* Existing financial or billing completion fields

Identify which actions are:

```text
clinical
administrative
billing-related
financial
```

Do not assume the existing generic “complete visit” action is suitable for financial closure.

## 4.2 Invoice and receivable architecture

Audit:

* Invoice model and service
* Invoice items
* `InvoiceReceivable`
* `InvoiceReceivableService`
* `PaymentService`
* Payment allocation
* Invoice adjustment
* Waivers
* Insurance responsibility
* Corporate responsibility
* Sponsor responsibility
* Patient responsibility
* Partial payment behaviour
* Overpayment handling
* Payment reversal or refund handling
* Existing statement and ageing reports

The current ledger and receivable data must remain authoritative.

## 4.3 Current payment policy

Audit:

* `OperationalVisitPaymentTimingResolver`
* `VisitPaymentTimingResolver`
* Current approved arrangement
* Master cutover mode
* Per-operation typed modes
* Environment kill switch
* Legacy visit-wide overrides
* Previous-balance policy

## 4.4 Existing clearance concepts

Search for:

```text
financial clearance
billing clearance
discharge clearance
patient cleared
invoice clearance
settlement clearance
account clearance
credit approval
payment plan
corporate guarantee
insurance pending
management approval
```

Determine whether existing fields, models, services, permissions, or approval workflows can be extended safely.

Do not create duplicate clearance systems.

## 4.5 Financial mutation events

Audit the existing observer/event architecture for:

* Invoice creation
* Invoice-item creation
* Invoice-item cancellation
* Payment posting
* Payment reversal
* Credit note
* Waiver
* Adjustment
* Insurance responsibility change
* Sponsor or corporate authorisation change

Determine the safest point for marking an existing clearance stale or refreshing it.

Document all findings in the Phase 9 report.

---

# 5. Financial-Clearance Mode

Create:

```text
app/Enums/VisitFinancialClearanceMode.php
```

Required values:

```php
<?php

namespace App\Enums;

enum VisitFinancialClearanceMode: string
{
    case DISABLED = 'disabled';
    case OBSERVE = 'observe';
    case ACTIVE = 'active';
}
```

Meaning:

## `disabled`

* Financial-clearance assessment may be manually previewed.
* Existing clinical, visit, discharge, and billing behaviour remains unchanged.
* No financial-close requirement is operational.
* The financial-close action is unavailable or non-enforcing.

## `observe`

* Clearance decisions are calculated and recorded.
* The system reports whether financial closure would be permitted.
* Existing behaviour remains unchanged.
* No administrative financial-close action is blocked or completed automatically.

## `active`

* The dedicated financial-close action requires a valid clearance decision.
* Fully or conditionally cleared visits may be financially closed.
* Visits that remain financially pending cannot be financially closed.
* Clinical completion and discharge remain unaffected.

Default:

```text
disabled
```

Invalid values must fall back to `disabled`.

---

# 6. Financial-Clearance Status

Create:

```text
app/Enums/VisitFinancialClearanceStatus.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum VisitFinancialClearanceStatus: string
{
    case PENDING = 'pending';
    case CLEARED = 'cleared';
    case CONDITIONALLY_CLEARED = 'conditionally_cleared';
    case FINANCIALLY_CLOSED = 'financially_closed';
    case STALE = 'stale';
}
```

Meaning:

## `pending`

The visit has patient-responsibility amounts that are not settled and no valid conditional-clearance approval exists.

## `cleared`

The visit is currently financially clear through full settlement, zero patient responsibility, or another non-debt basis.

## `conditionally_cleared`

An authorised approval permits financial closure while an outstanding balance remains.

## `financially_closed`

The visit has been administratively closed from a financial perspective.

This must not mean:

* Clinically complete
* Discharged
* Invoice paid
* Receivable closed

## `stale`

The previous assessment or closure no longer reflects current financial data.

Examples:

* A new invoice item was added
* A payment was reversed
* Responsibility changed
* An approval expired or was revoked
* A new charge was created after closure

---

# 7. Financial-Clearance Basis

Create:

```text
app/Enums/VisitFinancialClearanceBasis.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum VisitFinancialClearanceBasis: string
{
    case FULLY_SETTLED = 'fully_settled';
    case ZERO_PATIENT_RESPONSIBILITY = 'zero_patient_responsibility';
    case FULLY_INSURED = 'fully_insured';
    case FULLY_SPONSORED = 'fully_sponsored';
    case CORPORATE_GUARANTEE = 'corporate_guarantee';
    case INSURANCE_PENDING_APPROVED = 'insurance_pending_approved';
    case APPROVED_OUTSTANDING_BALANCE = 'approved_outstanding_balance';
    case APPROVED_PAYMENT_PLAN = 'approved_payment_plan';
    case MANAGEMENT_APPROVAL = 'management_approval';
}
```

Use only bases supported by actual repository workflows.

Rules:

* Fully insured means the patient-responsibility portion is zero according to the existing invoice ledger.
* Conditional bases must require an active approved clearance exception.
* A basis must not change invoice accounting.
* A basis must not create a payment.
* A basis must not erase debt.

---

# 8. Financial-Clearance Event Enum

Create:

```text
app/Enums/VisitFinancialClearanceEvent.php
```

Suggested values:

```text
assessed
cleared
conditionally_cleared
financially_closed
marked_stale
reopened
refreshed
conditional_clearance_revoked
```

Use it for immutable history.

---

# 9. Financial-Clearance Data Model

Create:

```text
visit_financial_clearances
```

One current financial-clearance record per visit.

Suggested fields:

```text
id
visit_id

status
basis

operational_policy_snapshot
policy_source_snapshot
approved_arrangement_id_snapshot

patient_responsibility_snapshot
patient_paid_snapshot
patient_outstanding_snapshot
insurance_responsibility_snapshot
sponsor_responsibility_snapshot
corporate_responsibility_snapshot

invoice_count_snapshot
invoice_item_count_snapshot
receivable_count_snapshot

current_exception_id
requires_finance_action

assessment_version
assessed_at
cleared_at
conditionally_cleared_at
financially_closed_at
stale_at
reopened_at
last_refreshed_at

created_by
last_refreshed_by
financially_closed_by

created_at
updated_at
```

Adapt names to project conventions.

Mandatory rules:

* `visit_id` is unique.
* Amount fields are snapshots only.
* Existing invoice and receivable tables remain authoritative.
* Amounts follow existing money precision.
* `current_exception_id` is nullable.
* No field should imply that an outstanding receivable was paid.
* Add indexes for:

  * status
  * basis
  * requires finance action
  * assessed date
  * closed date
  * stale date
* Use short explicit foreign-key names where required.

---

# 10. Financial-Clearance History

Create:

```text
visit_financial_clearance_history
```

Suggested fields:

```text
id
visit_financial_clearance_id
visit_id
event_type
old_values
new_values
reason_code
performed_by
performed_at
created_at
```

Rules:

* Append-only through normal workflows.
* Store material status, basis, and amount snapshots.
* Do not store patient contact data.
* Do not store clinical details.
* Do not expose raw JSON.
* Do not permit history deletion or editing.
* Do not duplicate unchanged refreshes.

---

# 11. Models and Relationships

Create:

```text
app/Models/VisitFinancialClearance.php
app/Models/VisitFinancialClearanceHistory.php
```

Add relationships:

```php
Visit::financialClearance()
Visit::financialClearanceHistory()
Visit::financialClearanceExceptions()

VisitFinancialClearance::visit()
VisitFinancialClearance::currentException()
VisitFinancialClearance::history()
VisitFinancialClearance::creator()
VisitFinancialClearance::refresher()
VisitFinancialClearance::financialCloser()
```

Suggested scopes:

```php
scopePending()
scopeCleared()
scopeConditionallyCleared()
scopeFinanciallyClosed()
scopeStale()
scopeRequiringFinanceAction()
scopeAssessedBetween()
```

Avoid service calls inside model accessors.

---

# 12. Live Financial Summary DTO

Create:

```text
app/Data/Billing/VisitFinancialSummary.php
```

Suggested structure:

```php
final readonly class VisitFinancialSummary
{
    public function __construct(
        public string $currency,
        public string $patientResponsibility,
        public string $patientPaid,
        public string $patientOutstanding,
        public string $insuranceResponsibility,
        public string $sponsorResponsibility,
        public string $corporateResponsibility,
        public int $invoiceCount,
        public int $invoiceItemCount,
        public int $receivableCount,
        public bool $hasUnbilledBillableItems,
        public bool $hasPendingFinancialAdjustments,
        public array $context = [],
    ) {
    }
}
```

Use the project’s money conventions rather than raw floating-point values.

Context must be bounded.

---

# 13. Financial Summary Service

Create:

```text
app/Services/Billing/VisitFinancialSummaryService.php
```

Responsibilities:

```php
public function summarize(Visit $visit): VisitFinancialSummary;
```

Mandatory rules:

* Use existing invoices and receivables.
* Reuse existing responsibility calculations.
* Reuse current payment allocation.
* Do not independently reconstruct the ledger.
* Do not count cancelled invoice items as payable.
* Respect adjustments and waivers.
* Do not treat insurer responsibility as patient responsibility.
* Do not treat sponsor responsibility as patient responsibility.
* Do not count previous-visit debt as part of the current visit’s clearance.
* Previous balance may be displayed separately.
* Avoid duplicate invoice and payment queries.

---

# 14. Financial-Clearance Decision DTO

Create:

```text
app/Data/Billing/VisitFinancialClearanceDecision.php
```

Suggested structure:

```php
final readonly class VisitFinancialClearanceDecision
{
    public function __construct(
        public VisitFinancialClearanceStatus $status,
        public ?VisitFinancialClearanceBasis $basis,
        public bool $mayFinanciallyClose,
        public bool $requiresFinanceAction,
        public string $reasonCode,
        public VisitFinancialSummary $summary,
        public ?int $conditionalApprovalId = null,
        public array $context = [],
    ) {
    }
}
```

The decision must not modify data.

---

# 15. Financial-Clearance Decision Service

Create:

```text
app/Services/Billing/VisitFinancialClearanceDecisionService.php
```

Suggested method:

```php
public function decide(Visit $visit): VisitFinancialClearanceDecision;
```

Resolution rules:

## 15.1 Unbilled billable items

If the visit contains known billable services that should have been billed but have no valid invoice item:

* Do not financially close.
* Return a machine-readable reason.
* Preserve existing service-specific missing-billing behaviour.
* Do not automatically create an invoice.

## 15.2 Zero patient responsibility

If patient responsibility is zero:

* Clear using an appropriate basis:

  * zero patient responsibility
  * fully insured
  * fully sponsored
  * corporate guarantee
* Do not require patient payment.

## 15.3 Fully settled patient responsibility

If current patient-responsibility outstanding is within the existing currency tolerance:

* Return `cleared`
* Basis: `fully_settled`
* Allow financial close

## 15.4 Outstanding patient responsibility

If patient responsibility remains outstanding:

* Check for a valid current conditional-clearance approval.
* If valid:

  * Return `conditionally_cleared`
  * Preserve the outstanding amount
  * Allow financial close
* Otherwise:

  * Return `pending`
  * Require finance action
  * Do not allow financial close

## 15.5 Payment-timing policy

The operational visit policy explains when payment was required during service delivery.

It must not erase the final outstanding balance.

For:

```text
pay_after_all_services
running_bill
```

the visit may complete services before payment, but final financial clearance still requires:

* Settlement, or
* Valid conditional-clearance approval

For:

```text
pay_before_service
```

perform a final residual-balance assessment as a safety check.

## 15.6 Previous balance

Previous-visit balance remains separate.

Do not block current-visit financial clearance based solely on previous debt unless an existing hospital policy explicitly requires it.

Report it separately where authorised.

---

# 16. Financial-Clearance Exception Type

Create:

```text
app/Enums/VisitFinancialClearanceExceptionType.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum VisitFinancialClearanceExceptionType: string
{
    case OUTSTANDING_BALANCE_APPROVAL = 'outstanding_balance_approval';
    case PAYMENT_PLAN = 'payment_plan';
    case INSURANCE_PENDING = 'insurance_pending';
    case CORPORATE_GUARANTEE = 'corporate_guarantee';
    case MANAGEMENT_APPROVAL = 'management_approval';
}
```

Use only supported workflows.

This is a financial-closure exception, not a service-payment arrangement.

---

# 17. Financial-Clearance Exception Status

Create:

```text
app/Enums/VisitFinancialClearanceExceptionStatus.php
```

Required values:

```text
pending
approved
rejected
withdrawn
revoked
expired
replaced
```

Use the same clear lifecycle discipline established in Phase 7.

---

# 18. Conditional-Clearance Data Model

Create:

```text
visit_financial_clearance_exceptions
visit_financial_clearance_exception_history
```

Suggested exception fields:

```text
id
visit_id
visit_financial_clearance_id

type
status

requested_amount
approved_amount

reason_code
request_reason
supporting_reference

requested_by
requested_at

reviewed_by
reviewed_at
review_decision_reason

approved_by
approved_at

effective_from
expires_at

withdrawn_by
withdrawn_at
withdrawal_reason

revoked_by
revoked_at
revocation_reason

replaced_by_exception_id

financial_summary_snapshot
payment_policy_snapshot
arrangement_id_snapshot
risk_level_snapshot

created_at
updated_at
```

Rules:

* One pending exception request per visit.
* One current approved exception per visit.
* Approved amount cannot exceed the current outstanding amount unless the workflow explicitly supports a higher credit ceiling.
* `inherit` and payment-policy mutation do not belong here.
* Free text must be bounded.
* Snapshot JSON must exclude patient contact and clinical information.
* Use database transactions and row locking.
* Preserve terminal records.

---

# 19. Conditional-Clearance Approval Rules

Create:

```text
app/Services/Billing/VisitFinancialClearanceApprovalPolicyService.php
```

and a typed approval-requirement DTO.

Suggested rules:

## Fully settled

No exception approval required.

## Outstanding-balance approval

Requires:

* Finance Manager or higher
* Reason
* Supporting reference where configured
* Approved amount
* Separate approver by default

## Payment plan

Requires:

* Existing or new structured payment-plan details
* Finance Manager approval
* Effective date
* Optional due schedule or external reference
* Separate approver

Do not build a full instalment collection subsystem unless one already exists.

## Insurance pending

Requires:

* Confirmed insurance relationship
* Authorisation or claim reference
* Finance or insurance-authorisation permission
* Explicit reason

## Corporate guarantee

Requires:

* Valid corporate relationship
* Guarantee reference
* Credit-limit validation where an existing corporate credit limit exists

## Management approval

Requires:

* High-level permission
* Reason
* Supporting reference
* Separate approver

Self-approval must be prohibited by default.

---

# 20. Conditional-Clearance Service

Create:

```text
app/Services/Billing/VisitFinancialClearanceExceptionService.php
```

Suggested methods:

```php
public function request(...): VisitFinancialClearanceException;

public function approve(...): VisitFinancialClearanceException;

public function reject(...): VisitFinancialClearanceException;

public function withdraw(...): VisitFinancialClearanceException;

public function revoke(...): VisitFinancialClearanceException;

public function expireDue(...): int;
```

Every mutation must:

* Run in a transaction
* Lock current pending and approved records
* Recalculate live outstanding patient responsibility
* Recheck requester/approver separation
* Recheck approval permissions
* Append immutable history
* Create one activity log
* Preserve the receivable
* Avoid invoice or payment mutation

Approving an exception must not:

* Create a payment
* Create a waiver
* Create a credit note
* Adjust the invoice
* Close the receivable
* Mark the invoice paid
* Create a Phase 7 payment arrangement
* Create a previous-balance override

---

# 21. Financial-Clearance Service

Create:

```text
app/Services/Billing/VisitFinancialClearanceService.php
```

Suggested methods:

```php
public function assess(
    Visit $visit,
    ?User $actor = null,
    bool $logActivity = false
): VisitFinancialClearance;

public function refresh(
    Visit $visit,
    ?User $actor = null,
    ?string $reasonCode = null
): VisitFinancialClearance;

public function financiallyClose(
    Visit $visit,
    User $actor,
    string $reason
): VisitFinancialClearance;

public function markStale(
    Visit $visit,
    string $reasonCode,
    ?User $actor = null
): ?VisitFinancialClearance;

public function reopenFinancialClearance(
    Visit $visit,
    string $reasonCode,
    ?User $actor = null
): VisitFinancialClearance;
```

## Assess

* Calculate the live decision.
* Create or update the current clearance record.
* Append history only on material change.
* Do not close the visit automatically.

## Financially close

* Available only in `active` mode.
* Recalculate the live decision inside the transaction.
* Require `mayFinanciallyClose = true`.
* Set status to `financially_closed`.
* Preserve the clearance basis.
* Record actor and timestamp.
* Do not change clinical visit status.

## Mark stale

Used after relevant financial changes.

* Do not block the financial mutation.
* Mark existing clearance stale.
* Append bounded history.
* Do not modify the invoice or payment.

## Reopen financial clearance

If a financially closed visit receives new billable activity:

* Move financial status to stale or pending according to the final design.
* Preserve the previous close event in history.
* Do not block clinical reopening.
* Do not delete the previous close timestamp from history.

---

# 22. Operational Financial-Close Action

Add a dedicated action:

```text
Financially Close Visit
```

Do not reuse a clinical “Complete Visit” button.

Suggested route:

```text
POST visits/{visit}/financial-clearance/close
```

The action must:

1. Require financial-close permission.
2. Require Phase 9 mode `active`.
3. Recalculate the live decision.
4. Require cleared or conditionally cleared status.
5. Require a reason.
6. Run transactionally.
7. Append history.
8. Create one activity log.
9. Leave clinical and discharge statuses unchanged.

If pending:

* Reject the financial-close action.
* Return a localised reason.
* Provide links to:

  * invoice
  * payment collection
  * exception request
  * finance review

---

# 23. Clinical Completion and Discharge Safety

Phase 9 must explicitly prove:

* Consultation completion is not blocked by financial clearance.
* Session completion is not blocked by financial clearance.
* Same-day outpatient reopening remains allowed according to current rules.
* Existing next-day outpatient restrictions remain unchanged.
* Active inpatient sessions remain editable according to current rules.
* Inpatient discharge is not blocked by financial clearance.
* Emergency disposition and discharge are not blocked by financial clearance.
* Readmission is not blocked by prior financial closure.
* New clinical items may be added after clinical reopening.
* New billable items mark financial clearance stale rather than being rejected.

Do not add financial-clearance checks to clinical completion or discharge controllers.

---

# 24. Automatic Assessment and Staleness Integration

Audit existing observers and events.

Where safe, integrate after successful financial mutations.

Potential triggers:

```text
invoice created
invoice item created
invoice item cancelled
payment posted
payment reversed
waiver applied
adjustment applied
responsibility changed
insurance coverage changed
corporate responsibility changed
```

Preferred behaviour:

## No existing clearance record

* Do nothing automatically.
* Assessment may occur when finance opens the visit or through backfill.

## Existing pending, cleared, conditionally cleared, or closed record

* Mark stale or queue a failure-safe refresh after commit.
* Do not block the original financial mutation.
* Do not create duplicate history for repeated equivalent events.

## Payment posted

A failure-safe after-commit refresh may automatically move:

```text
pending → cleared
stale → cleared
conditionally_cleared → cleared
```

when the outstanding patient balance reaches zero.

Do not automatically financially close the visit.

## New charge after financial close

Mark the record stale or reopened.

Do not reject the charge merely because the visit was financially closed.

---

# 25. Clearance Snapshot Staleness

Create:

```php
public function snapshotIsStale(
    VisitFinancialClearance $clearance
): bool;
```

Possible stale conditions:

* Current patient responsibility differs from snapshot
* Current paid amount differs
* Current outstanding amount differs
* Invoice count changed
* Active conditional exception changed
* Approved exception expired or was revoked
* Operational payment policy changed
* Current approved visit arrangement changed
* New unbilled billable item exists
* Payment reversal occurred after assessment

Staleness is diagnostic until refresh or close.

A stale record cannot be financially closed without re-assessment.

---

# 26. Current Visit Versus Previous Balance

Show both values separately:

```text
Current visit outstanding
Previous visit outstanding
Total patient exposure
```

Financial clearance for the current visit should normally use:

```text
Current visit outstanding
```

Previous-balance policy remains independently enforced at its existing workflow stage.

Do not:

* Merge previous balance into the current visit invoice
* Move previous debt to the current visit
* Create a previous-balance override from a clearance approval
* Treat financial closure as settlement of old debt

---

# 27. Patient Financial-Risk Integration

Financial risk may inform approval requirements but must not alter ledger calculations.

Suggested behaviour:

## Normal

Standard approval requirements.

## Watchlist

Finance review required for conditional clearance.

## High risk

Separate approver required.

## Blocked credit

Conditional clearance requires Finance Manager or higher and explicit supporting reference.

Risk profile changes after exception request must trigger stale-context detection.

Do not modify the patient financial-risk profile when approving or revoking financial clearance.

---

# 28. Payment Arrangement Integration

Use the current operational payment policy only as context.

Examples:

## Approved pay after all services

* Explains why services proceeded before settlement.
* Does not itself approve financial closure with debt.

## Approved running bill

* Explains why charges accumulated.
* Does not itself clear the balance.

## Approved pay before service

* A residual outstanding balance should still prevent financial closure unless an exception is approved.

Do not automatically create a clearance exception from a Phase 7 arrangement.

---

# 29. Exception Expiry and Revocation

Add:

```text
php artisan billing:visit-financial-clearance-exception-expire
```

Rules:

* Dry-run by default
* `--commit` performs mutations
* Idempotent
* Transactional
* Mark due approved exceptions expired
* Mark related conditional clearance stale
* Do not automatically reopen clinical work
* Do not modify receivables
* Create history and activity logs only on commit

Register daily scheduling only if consistent with project conventions.

Use:

```text
withoutOverlapping
onOneServer
```

---

# 30. Configuration

Create:

```text
config/visit_financial_clearance.php
```

Suggested defaults:

```php
return [
    'mode' => 'disabled',

    'financial_close' => [
        'require_reason' => true,
        'fallback_to_no_enforcement_on_failure' => true,
    ],

    'settlement' => [
        'currency_tolerance' => '0.01',
        'use_patient_responsibility_only' => true,
        'require_no_unbilled_billable_items' => true,
    ],

    'conditional_clearance' => [
        'enabled' => true,
        'require_separate_approver' => true,
        'allow_outstanding_balance' => true,
        'allow_payment_plan' => true,
        'allow_insurance_pending' => true,
        'allow_corporate_guarantee' => true,
    ],

    'automatic_refresh' => [
        'after_payment' => true,
        'mark_stale_after_new_charge' => true,
        'financially_close_automatically' => false,
    ],
];
```

Adapt to existing settings conventions.

Database-backed admin settings should include only operationally appropriate options.

Deployment defaults must not activate financial-close enforcement.

---

# 31. Environment Safety Override

Add an environment control such as:

```env
VISIT_FINANCIAL_CLEARANCE_FORCE_DISABLED=true
```

Rules:

* Overrides database active mode
* Does not modify clearance records
* Does not modify exception approvals
* Does not modify invoices
* Visible in diagnostics and admin UI
* Allows immediate rollback of financial-close enforcement

Precedence:

```text
environment force-disabled
→ configured financial-clearance mode
→ live clearance decision
```

---

# 32. Permissions

Add:

```text
visits.financial_clearance.view
visits.financial_clearance.assess
visits.financial_clearance.close
visits.financial_clearance.history
visits.financial_clearance.report

visits.financial_clearance_exception.request
visits.financial_clearance_exception.approve
visits.financial_clearance_exception.reject
visits.financial_clearance_exception.withdraw
visits.financial_clearance_exception.revoke
visits.financial_clearance_exception.history

billing.financial_clearance.settings.view
billing.financial_clearance.settings.manage
billing.financial_clearance.settings.activate
billing.financial_clearance.settings.rollback
```

Suggested role assignments:

## Super Admin / Admin

All permissions.

## Finance Manager

View, assess, close, history, report, request, approve, reject, revoke.

Do not grant activation or rollback unless hospital policy permits it.

## Accountant

View, assess, request, withdraw, history, report.

No exception approval or financial close by default.

## Reception and clinical roles

No financial-close or exception-management permissions.

They must retain normal clinical workflow permissions.

---

# 33. Administration Interface

Add a restricted **Financial Clearance Settings** page.

Display:

```text
Configured mode
Effective mode
Environment force-disabled status
Settlement basis
Patient-responsibility tolerance
Unbilled-item requirement
Conditional-clearance options
Separate-approver requirement
Automatic refresh behaviour
```

Controls:

```text
Disabled
Observe Only
Active
```

Activation requires:

* Activation permission
* Confirmation
* Reason
* Activity log

Provide prominent rollback:

```text
Disable Financial-Close Enforcement
```

Rollback must:

* Change configured mode to disabled
* Require reason
* Create one audit event
* Leave existing clearances and approvals intact
* Leave accounting records untouched

---

# 34. Visit Financial-Clearance UI

Add a restricted card to the visit billing/finance area.

Show:

```text
Operational payment policy
Current visit patient responsibility
Amount paid
Current outstanding balance
Previous outstanding balance
Clearance status
Clearance basis
Conditional approval
Assessment freshness
Assessed at
Financially closed at
```

Clearly label:

> Financial closure is separate from clinical completion and discharge.

Actions according to permission:

```text
Assess Financial Clearance
Refresh Assessment
Collect Payment
Request Conditional Clearance
Approve or Reject Exception
Revoke Exception
Financially Close Visit
View History
```

Do not expose unrestricted financial-risk details.

---

# 35. Finance Worklist

Add:

```text
admin/billing/visit-financial-clearances
```

Filters:

```text
status
basis
financially closed
requires finance action
stale
visit type
operational payment policy
conditional exception type
risk level
date range
active visits only
discharged but not financially closed
clinically completed but not financially closed
```

Columns:

```text
Visit
Patient
Visit type
Clinical status
Discharge status
Payment policy
Patient responsibility
Paid
Outstanding
Clearance status
Basis
Exception
Stale
Last assessed
```

Respect patient masking and permission boundaries.

---

# 36. Reporting

Add aggregate metrics:

```text
pending financial clearances
cleared visits
conditionally cleared visits
financially closed visits
stale clearances
discharged but financially open
clinically completed but financially open
outstanding amount conditionally closed
payment-plan amount
insurance-pending amount
corporate-guaranteed amount
average time from clinical completion to financial closure
```

Patient-level export requires report permission.

Export only necessary financial and visit identifiers.

Do not include clinical notes, diagnosis, contact data, or raw history JSON.

---

# 37. Activity Logging

Suggested actions:

```text
VISIT_FINANCIAL_CLEARANCE_ASSESSED
VISIT_FINANCIAL_CLEARANCE_REFRESHED
VISIT_FINANCIAL_CLEARANCE_CLEARED
VISIT_FINANCIAL_CLEARANCE_CONDITIONALLY_CLEARED
VISIT_FINANCIALLY_CLOSED
VISIT_FINANCIAL_CLEARANCE_MARKED_STALE
VISIT_FINANCIAL_CLEARANCE_REOPENED

VISIT_FINANCIAL_CLEARANCE_EXCEPTION_REQUESTED
VISIT_FINANCIAL_CLEARANCE_EXCEPTION_APPROVED
VISIT_FINANCIAL_CLEARANCE_EXCEPTION_REJECTED
VISIT_FINANCIAL_CLEARANCE_EXCEPTION_WITHDRAWN
VISIT_FINANCIAL_CLEARANCE_EXCEPTION_REVOKED
VISIT_FINANCIAL_CLEARANCE_EXCEPTION_EXPIRED

VISIT_FINANCIAL_CLEARANCE_MODE_CHANGED
VISIT_FINANCIAL_CLEARANCE_ROLLBACK
```

Use bounded metadata:

```text
visit id
clearance id
exception id
old/new status
basis
patient responsibility snapshot
paid snapshot
outstanding snapshot
approved amount
actor
timestamp
```

Do not include:

* Patient name
* Contact data
* Clinical notes
* Free-text financial-risk details
* Full invoice contents

Routine decision previews and reads must not create activity logs.

---

# 38. Read-Only Diagnostic Commands

Add:

```text
php artisan billing:visit-financial-clearance-status
php artisan billing:visit-financial-clearance-audit
```

Suggested status options:

```text
--visit=
--status=
--stale
--financially-open
--clinically-complete
--discharged
--problems-only
--limit=
--json
```

Audit should detect:

```text
visit missing clearance record
financially closed with outstanding debt and no approved exception
conditionally cleared without current approved exception
expired or revoked exception still linked
cleared snapshot differs from live balance
financially closed record is stale
new charges after closure
payment reversal after closure
multiple current approved exceptions
pending record with closed timestamp
closed record missing actor
approved amount below current outstanding
approved amount above allowed limit
current receivable incorrectly marked settled
patient responsibility incorrectly includes insurer amount
clinical status modified by financial close
```

Rules:

* Read-only
* No automatic fixes
* No activity logs
* No sensitive output
* Findings do not fail the exit code
* Technical failure may return non-zero

---

# 39. Backfill and Refresh Commands

Add:

```text
php artisan billing:visit-financial-clearance-backfill
php artisan billing:visit-financial-clearance-refresh
```

Suggested options:

```text
--visit=
--visit-type=
--active-only
--completed-only
--discharged-only
--missing-only
--stale-only
--limit=
--dry-run
--commit
--json
```

Rules:

* Dry-run by default
* Bounded chunking
* No N+1 financial queries
* No invoice/payment/receivable mutation
* No automatic financial closure
* Idempotent
* Existing records preserved unless refresh requested
* History and activity logs only for committed material changes

---

# 40. Performance Requirements

## Clinical workflows

No financial-clearance query should be added to ordinary clinical actions.

Do not load financial-clearance relations in:

* Consultation workspace
* Laboratory result workflow
* Pharmacy dispensing workflow
* Nursing workflows
* Emergency workflows

unless the user has a finance-specific need and permission.

## Finance workflows

Use:

* Eager loading
* Pagination
* Indexed filters
* Existing invoice summaries
* Request-scoped memoisation
* Bounded live balance calculations

## Automatic refresh

* Use after-commit behaviour
* Avoid recalculating the same visit several times in one transaction
* Deduplicate staleness events
* Do not block successful payment posting because refresh failed

Add focused query assertions where stable.

---

# 41. Failure Safety

If assessment fails:

* Do not alter clinical or discharge state.
* Do not mark the visit cleared.
* Log the technical failure.
* Allow later repair through refresh or backfill.

If financial close fails:

* Roll back the close transaction.
* Preserve the previous clearance status.
* Do not modify invoices or receivables.
* Do not change clinical status.

If automatic refresh fails:

* Preserve the successful invoice or payment mutation.
* Log the error.
* Mark repair as available through diagnostic commands.

If conditional approval fails:

* Preserve any previous valid approval.
* Do not partially link the exception.
* Do not alter accounting records.

---

# 42. Form Requests

Create dedicated requests for:

```text
AssessVisitFinancialClearanceRequest
CloseVisitFinanciallyRequest
StoreVisitFinancialClearanceExceptionRequest
ApproveVisitFinancialClearanceExceptionRequest
RejectVisitFinancialClearanceExceptionRequest
WithdrawVisitFinancialClearanceExceptionRequest
RevokeVisitFinancialClearanceExceptionRequest
UpdateVisitFinancialClearanceSettingsRequest
RollbackVisitFinancialClearanceRequest
```

Validation must cover:

* Permission
* Visit eligibility
* Current state
* Exception type
* Requested and approved amount
* Non-negative values
* Effective and expiry dates
* Reason requirements
* Supporting references
* Requester/approver separation
* Stale financial summary
* Stale risk context
* Invalid direct status manipulation
* Unknown fields
* Activation confirmation

---

# 43. Localisation

Create:

```text
lang/en/visit_financial_clearance.php
lang/fr/visit_financial_clearance.php
```

Required concepts:

```text
Financial Clearance
Financially Close Visit
Pending
Cleared
Conditionally Cleared
Financially Closed
Stale
Fully Settled
Zero Patient Responsibility
Fully Insured
Outstanding Balance Approved
Payment Plan Approved
Insurance Pending Approved
Corporate Guarantee
Management Approval
Current Visit Outstanding
Previous Visit Outstanding
Requires Finance Action
Assess Clearance
Refresh Assessment
Request Conditional Clearance
Approve
Reject
Withdraw
Revoke
New Charges Added After Closure
Payment Reversal After Closure
Financial Closure Is Separate From Clinical Completion
Clinical Discharge Remains Allowed
Outstanding Receivable Remains Collectible
Disable Financial-Close Enforcement
```

Also localise:

* Validation
* Exception types and statuses
* History events
* Worklist filters
* Empty states
* Reports
* Audit command labels
* Success, rejection, and rollback feedback

Maintain full English/French parity.

---

# 44. Tests

Add focused tests first.

## 44.1 Enum and model tests

Verify:

* Clearance modes exist.
* Statuses and bases exist.
* Exception types and statuses exist.
* Enum casts work.
* One clearance per visit.
* One pending and one current approved exception per visit.
* History relationships work.
* Terminal records remain historical.

## 44.2 Financial summary tests

Verify:

* Patient responsibility is calculated from existing ledger data.
* Patient payments are reflected correctly.
* Partial payment is reflected correctly.
* Insurance responsibility is not counted as patient debt.
* Sponsor/corporate responsibility is separate.
* Waivers and adjustments follow current accounting behaviour.
* Cancelled items are excluded.
* Previous balance is not merged into the current visit.
* No duplicate accounting calculation is introduced.

## 44.3 Decision tests

Verify:

* Zero patient responsibility clears.
* Fully settled patient responsibility clears.
* Outstanding balance remains pending.
* Valid approved exception conditionally clears.
* Expired or revoked exception does not clear.
* Unbilled billable items prevent financial close where configured.
* Stale assessment cannot close.
* Pay-after-services still requires final settlement or exception.
* Running bill still requires final settlement or exception.
* Pay-before still performs residual-balance validation.

## 44.4 Exception workflow tests

Verify:

* Request succeeds.
* Duplicate pending request is rejected.
* Approval succeeds.
* Rejection succeeds.
* Withdrawal succeeds.
* Revocation succeeds.
* Expiry succeeds.
* Replacement succeeds.
* Self-approval is blocked.
* Stale balance blocks silent approval.
* High-risk/blocked-credit requirements are enforced.
* History and activity logs are created exactly once.

## 44.5 Financial-close tests

Verify:

* Disabled mode does not enforce closure.
* Observe mode reports but does not operationally close.
* Active mode allows cleared financial close.
* Active mode allows conditionally cleared financial close.
* Active mode rejects pending close.
* Financial close does not change clinical visit status.
* Financial close does not discharge the patient.
* Financial close does not mark invoice paid.
* Financial close does not close the receivable.
* Financial close does not create payment, waiver, adjustment, or override.

## 44.6 Clinical safety regression tests

Explicitly verify:

* Outpatient consultation can be reopened within the current allowed period.
* Existing next-day outpatient rules remain unchanged.
* Inpatient active sessions remain editable.
* Inpatient clinical discharge remains allowed.
* Emergency discharge or disposition remains allowed.
* Clinical completion does not require financial clearance.
* New clinical work after reopening remains allowed.
* New billable work marks clearance stale rather than blocking clinical work.
* Readmission behaviour remains unchanged.

## 44.7 Automatic staleness tests

Verify:

* New invoice item marks clearance stale.
* Payment posting refreshes or marks stale according to configuration.
* Full payment may refresh pending to cleared.
* Payment reversal marks clearance stale.
* Adjustment changes mark clearance stale.
* New charge after financial close reopens or stales financial clearance.
* Original financial mutation succeeds even if refresh fails.
* Repeated equivalent events do not duplicate history.

## 44.8 Permission and privacy tests

Verify:

* View, assess, close, exception, history, report, settings, activate, and rollback permissions are distinct.
* Accountant cannot approve or financially close by default.
* Finance Manager can perform authorised finance actions.
* Clinical users do not receive clearance details in page source, props, or JSON.
* Patient masking remains intact.
* Reads create no activity logs.

## 44.9 Worklist and report tests

Verify:

* Filters work.
* Pagination works.
* Discharged-but-financially-open visits are reported.
* Clinically-complete-but-financially-open visits are reported.
* Conditional outstanding totals are correct.
* Export omits clinical and contact details.

## 44.10 Command tests

Verify:

* Backfill dry-run performs no writes.
* Backfill commit is idempotent.
* Refresh affects only selected records.
* Exception expiry is idempotent.
* Status and audit commands are read-only.
* JSON output is valid.
* Findings do not fail the exit code.

## 44.11 Rollback tests

Verify:

* Environment force-disabled overrides active DB mode.
* Admin rollback disables enforcement immediately.
* Existing clearance and exception records remain intact.
* Clinical workflows remain unchanged.
* Accounting records remain unchanged.
* Rollback creates one audit event.

## 44.12 Payment-timing regression tests

Verify:

* Phase 8 typed cutover still works.
* Approved pay-after arrangements still permit eligible services.
* Approved pay-before arrangements still block eligible unpaid services.
* Running-bill service behaviour remains unchanged.
* Emergency remains legacy.
* Nine unwired operations remain unwired.
* Previous-balance policy remains separate.
* Phase 7 arrangement workflow remains intact.
* Phase 6 materialisation remains intact.
* Phase 5 financial-risk lifecycle remains intact.

## 44.13 Localisation tests

Verify English/French parity.

---

# 45. Targeted Verification

Run focused checks first:

```bash
php artisan migrate
php artisan route:list
php artisan config:clear
php artisan view:clear
php artisan view:cache

php artisan test --filter=VisitFinancialClearanceEnum
php artisan test --filter=VisitFinancialSummary
php artisan test --filter=VisitFinancialClearanceDecision
php artisan test --filter=VisitFinancialClearanceService
php artisan test --filter=VisitFinancialClearanceException
php artisan test --filter=VisitFinancialClose
php artisan test --filter=VisitFinancialClearancePermission
php artisan test --filter=VisitFinancialClearanceCommand
php artisan test --filter=VisitFinancialClearanceClinicalSafety
php artisan test --filter=VisitFinancialClearanceStaleness
php artisan test --filter=VisitFinancialClearanceRollback
```

Rerun payment-policy regressions:

```bash
php artisan test --filter=PaymentTimingCutover
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
php artisan billing:visit-financial-clearance-backfill --active-only --dry-run
php artisan billing:visit-financial-clearance-status
php artisan billing:visit-financial-clearance-audit
php artisan billing:visit-financial-clearance-exception-expire --dry-run
php artisan billing:payment-timing-cutover-status
php artisan billing:payment-timing-cutover-audit
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

---

# 46. Final Wide Verification

Phase 9 completes this payment-timing implementation batch.

After all focused Phase 9 tests pass, run one final wide verification.

## Laravel suite

```bash
php artisan test
```

Rules:

* Do not dismiss new failures as unrelated without verification.
* Compare any known `NhisClaimWorkflowTest` failures against the confirmed pre-Phase-9 baseline.
* Fix failures caused by Phases 1–9.
* Clearly document genuinely pre-existing failures with evidence.
* Report total tests, assertions, passed, failed, skipped, and duration.

## Playwright suite

Run the complete existing Playwright suite after the Laravel suite.

Add focused browser coverage for:

```text
finance assesses a pay-after visit
unpaid visit shows pending clearance
finance requests conditional clearance
separate approver approves it
visit becomes conditionally cleared
finance financially closes the visit
invoice and receivable remain outstanding
new billable item marks financial closure stale
clinical completion and discharge remain unaffected
admin rollback disables financial-close enforcement
```

Document browser, viewport, and result summary.

Do not claim the full suite passed unless it actually passed.

---

# 47. Documentation

Create:

```text
docs/billing/PAYMENT_TIMING_POLICY_PHASE_9_REPORT.md
```

The report must include:

1. Existing visit and financial architecture audited
2. Clinical-versus-financial lifecycle separation
3. Files created
4. Files modified
5. Clearance-mode design
6. Environment rollback design
7. Clearance status and basis vocabulary
8. Financial summary design
9. Ledger and receivable source-of-truth decision
10. Clearance decision rules
11. Conditional-clearance exception workflow
12. Approval and self-approval controls
13. Financial-close action
14. Clinical completion and discharge safety
15. Reopening and new-charge behaviour
16. Automatic staleness/refresh integration
17. Current versus previous-balance handling
18. Payment-arrangement integration
19. Financial-risk integration
20. Permissions and role assignments
21. Admin settings and rollback
22. Visit UI and finance worklist
23. Reporting and exports
24. Activity-log behaviour
25. Diagnostic and maintenance commands
26. Query and performance impact
27. Focused tests and results
28. Full Laravel-suite results
29. Full Playwright-suite results
30. Any confirmed pre-existing failures
31. Confirmation that outstanding receivables remain collectible
32. Confirmation that clinical completion/discharge remain independent
33. Confirmation that financial close does not mark invoices paid
34. Final implementation-batch closure summary
35. Deferred future work

Do not claim tests or commands passed unless they were executed successfully.

---

# 48. Guardrails

Do not:

* Block clinical completion because financial clearance is pending
* Block session completion because financial clearance is pending
* Block inpatient discharge because financial clearance is pending
* Block emergency care or disposition
* Remove same-day outpatient reopening
* Change existing next-day outpatient restrictions
* Prevent inpatient active sessions from being edited
* Treat financial closure as clinical completion
* Treat clinical discharge as financial clearance
* Mark outstanding invoices paid
* Close receivables with unpaid balances
* Create payments
* Create waivers
* Create credit notes
* Create invoice adjustments
* Merge previous debt into the current visit invoice
* Create previous-balance overrides
* Create Phase 7 payment arrangements automatically
* Modify patient financial-risk profiles
* Change GL entries
* Wire the nine unwired operations
* Enable emergency typed enforcement
* Invent an emergency stabilisation field
* Automatically financially close visits after payment
* Hide outstanding debt after conditional closure
* Audit routine reads
* Expose sensitive patient data
* Skip final wide verification after the phase is complete

---

# 49. Acceptance Criteria

Phase 9 is complete only when:

* Financial-clearance mode supports disabled, observe, and active states.
* Deployment defaults to disabled.
* Environment force-disabled rollback exists.
* One current financial-clearance record exists per visit.
* Financial-clearance history is append-only.
* Live financial summaries use existing ledger and receivable data.
* Patient responsibility is separated from insurer, sponsor, and corporate responsibility.
* Previous balance remains separate from current-visit clearance.
* Fully settled visits clear.
* Zero-patient-responsibility visits clear.
* Outstanding visits remain pending without approval.
* Valid conditional approvals allow conditional clearance.
* Outstanding receivables remain open after conditional clearance.
* A dedicated financial-close action exists.
* Financial close requires a current valid assessment in active mode.
* Financial close does not change clinical visit status.
* Financial close does not discharge the patient.
* Financial close does not mark invoices paid.
* Financial close does not close receivables.
* New charges after closure mark the clearance stale or reopened.
* Payment reversals mark clearance stale.
* Clinical reopening remains allowed.
* Same-day outpatient reopening remains unchanged.
* Inpatient active-session behaviour remains unchanged.
* Clinical discharge remains independent.
* Emergency workflows remain unaffected.
* Conditional-clearance approvals use maker-checker controls.
* Self-approval is blocked by default.
* Exception expiry and revocation work.
* Financial risk affects approval requirements only.
* Payment arrangements explain timing but do not approve debt closure.
* Finance users have a restricted worklist and history.
* Admin activation and rollback are permission-controlled and audited.
* Maintenance and audit commands are safe and idempotent.
* English/French localisation is complete.
* Focused tests pass.
* One final full Laravel suite is executed.
* One final full Playwright suite is executed.
* All new regressions caused by the payment-timing batch are fixed.
* The Phase 9 report accurately documents the complete implementation and verification.

Proceed with **Payment Timing Policy Phase 9 only**.

After implementation, provide:

1. A concise implementation summary
2. Files created and modified
3. Financial-clearance data model
4. Clearance statuses and bases
5. Financial summary and ledger integration
6. Conditional-clearance approval workflow
7. Financial-close behaviour
8. Clinical completion, reopening, and discharge safety
9. New-charge and payment-reversal behaviour
10. Permission and privacy design
11. Admin activation and rollback
12. UI, worklist, and reporting details
13. Diagnostic and maintenance command results
14. Query and performance impact
15. Focused test results
16. Full Laravel-suite results
17. Full Playwright-suite results
18. Any confirmed pre-existing failures
19. Confirmation that outstanding debt remains collectible
20. Confirmation that invoices are not falsely marked paid
21. Confirmation that clinical workflows remain independent
22. The Phase 9 report path
23. Final payment-timing implementation-batch closure summary

Then stop after Phase 9.
