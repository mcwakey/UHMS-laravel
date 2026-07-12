# UHMS Implementation Prompt — Payment Timing Policy Phase 7

## Authorised Per-Visit Payment Arrangements, Request/Approval Workflow, and Revocation

You are working on **UHMS**, a Laravel-based hospital management system.

Implement **Phase 7 of the Configurable Payment Timing and Per-Visit Payment Policy system**.

Phases 1–6 are complete.

---

# 1. Existing Foundation

## Phase 1 — Configuration foundation

Implemented:

* `VisitPaymentTimingPolicy`
* `VisitPaymentPolicySource`
* Global and visit-type payment-timing settings
* Database-backed configuration
* Admin settings, localisation, permissions, and audit

## Phase 2 — Legacy observation integration

Implemented:

* Legacy and observe integration modes
* `VisitPaymentTimingDecision`
* `VisitPaymentTimingResolver`
* Legacy compatibility mapping
* Comparison diagnostics
* `billing:payment-timing-audit`

Legacy billing decisions remain authoritative.

## Phase 3 — Central payment façade

Implemented:

* `PaymentGateStage`
* `PaymentGateContext`
* Production payment checks centralised through `PaymentGateService`
* Maintained operation registry
* Stage-aware diagnostics and coverage reporting

Four production checks are wired:

```text
consultation.route.complete
consultation.next_patient.readiness
laboratory.result.enter
pharmacy.item.dispense
```

Nine operations remain intentionally unwired.

## Phase 4 — Departmental enforcement policy

Implemented:

* Operation modes and stage rules
* Missing-context policy
* Visit-context rules
* Override-scope rules
* Eligibility and compatibility diagnostics
* Admin configuration and audit

All operation configuration remains non-operational.

## Phase 5 — Patient financial-risk profiles

Implemented:

* Patient financial-risk classification
* Restricted permissions and privacy
* Lifecycle, review, suspension, clearance, and expiry
* Immutable history and activity logging
* Finance worklist and reporting

Financial-risk profiles do not affect visits or payment gates.

## Phase 6 — Visit policy materialisation

Implemented:

* One observational `visit_payment_policies` record per visit
* Append-only policy history
* Baseline typed policy snapshots
* Patient financial-risk snapshots
* Non-operational recommendations
* Finance-review indicators
* Backfill, refresh, and audit commands
* Restricted worklist and detail views

Important Phase 6 guarantees:

* `resolved_policy` is the baseline typed observation.
* `recommended_policy` is separate and non-operational.
* Risk recommendations never replace the baseline.
* `VisitPaymentTimingResolver` remains risk-unaware.
* `BillingPolicyService` and `PaymentGateService` do not read materialised visit policy records.
* No operational payment-policy field currently exists.

---

# 2. Phase 7 Goal

Implement an explicit and authorised **per-visit payment-arrangement workflow**.

The workflow must support authorised users requesting or directly setting, subject to permissions and approval rules:

```text
Pay Before Service
Pay After All Services
Running Bill
Return to Baseline Policy
```

The workflow must also support:

* Requests
* Approval
* Rejection
* Withdrawal
* Revocation
* Expiry
* Replacement by a newer approved arrangement
* Full history
* Separation of requester and approver
* Risk-based approval requirements
* Reason and supporting reference
* Permission-controlled visibility
* Safe handling of concurrent requests
* Clear distinction between baseline, recommendation, and approved arrangement

This phase creates an **approved visit-specific arrangement**, but it must remain separate from the live legacy payment gate.

An approved arrangement must not yet change:

* Triage blocking
* Consultation readiness
* Laboratory result entry
* Pharmacy dispensing
* Any other departmental workflow
* Invoice settlement
* Visit completion
* Discharge
* Financial closure

Operational cutover will occur in a later phase.

---

# 3. Core Domain Separation

UHMS must preserve these distinct concepts:

```text
Baseline observed policy
Risk recommendation
Requested policy
Approved visit arrangement
Legacy operational gate
Future typed operational policy
```

For example:

```text
Baseline observed policy: Pay After All Services
Risk recommendation: Pay Before Service
Requested arrangement: Pay After All Services
Approved arrangement: Pay After All Services
Legacy operational gate: Still authoritative and unchanged
```

Do not overwrite `resolved_policy` when an arrangement is approved.

Do not overwrite `recommended_policy`.

Do not make `approved_policy` synonymous with the current operational gate.

---

# 4. Mandatory Architecture Audit

Before changing code, inspect:

## Visit policy materialisation

* `VisitPaymentPolicy`
* `VisitPaymentPolicyHistory`
* `VisitPaymentPolicyMaterializationService`
* Visit-policy worklist and detail UI
* Snapshot staleness logic
* Backfill, refresh, and audit commands
* Existing permissions and role assignments

## Existing overrides

* `VisitBillingOverride`
* `VisitBillingOverrideService`
* Override types
* Override scopes
* Approval rules
* Requester and approver fields
* Expiry and revocation
* Audit logging
* Existing UI and commands

Determine whether the existing override infrastructure can safely support approved visit payment arrangements or whether a dedicated request/approval table is required.

Do not force the new workflow into an existing override type where semantics differ.

## Existing approval workflows

Inspect:

* Credit approvals
* Sponsor authorisations
* Insurance authorisations
* Management approvals
* Any maker-checker workflows
* Existing request/review models
* Existing approval history patterns
* Existing self-approval prevention conventions

## Visit lifecycle

Inspect:

* Active visit definition
* Completed visit behaviour
* Reopened visits
* Cancelled visits
* Inpatient discharge
* Emergency closure
* Same-day reopening rules
* Visit replacement or merge behaviour

Document all findings in the Phase 7 report.

---

# 5. Arrangement Status Enum

Create:

```text
app/Enums/VisitPaymentArrangementStatus.php
```

Required values:

```php
<?php

namespace App\Enums;

enum VisitPaymentArrangementStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
    case REPLACED = 'replaced';
}
```

Meaning:

## `pending`

Submitted and awaiting authorised decision.

## `approved`

Valid administrative arrangement for the visit.

Still non-operational in Phase 7.

## `rejected`

Declined by an authorised reviewer.

## `withdrawn`

Cancelled by the requester before decision.

## `revoked`

Previously approved arrangement cancelled by an authorised user.

## `expired`

Approved arrangement reached its expiry.

## `replaced`

Superseded by a newer approved arrangement.

Rules:

* Terminal statuses must be explicit.
* Do not delete rejected, withdrawn, revoked, expired, or replaced requests.
* Status values must use enum casts.
* Labels must be localised.
* Status itself must not control payment gates.

---

# 6. Arrangement Source Enum

Create:

```text
app/Enums/VisitPaymentArrangementSource.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum VisitPaymentArrangementSource: string
{
    case MANUAL_REQUEST = 'manual_request';
    case RISK_RECOMMENDATION = 'risk_recommendation';
    case FINANCE_DECISION = 'finance_decision';
    case MANAGEMENT_DECISION = 'management_decision';
    case CREDIT_APPROVAL = 'credit_approval';
    case CORPORATE_GUARANTEE = 'corporate_guarantee';
    case INSURANCE_AUTHORIZATION = 'insurance_authorization';
    case BASELINE_RESTORATION = 'baseline_restoration';
}
```

Use only sources supported by actual repository workflows.

Do not add speculative sources that cannot be justified.

The source is administrative metadata only.

---

# 7. Arrangement Event Enum

Create:

```text
app/Enums/VisitPaymentArrangementEvent.php
```

Suggested values:

```text
requested
approved
rejected
withdrawn
revoked
expired
replaced
updated_before_decision
```

Use the enum for append-only arrangement history.

---

# 8. Data Model

Create a dedicated table:

```text
visit_payment_arrangements
```

A dedicated table is preferred because:

* A visit may have multiple historical requests.
* Requests may be rejected or withdrawn.
* Approved arrangements may later be revoked or replaced.
* Requester and approver must remain historically traceable.
* The observational `visit_payment_policies` record must remain separate.

Suggested fields:

```text
id
visit_id
visit_payment_policy_id

requested_policy
approved_policy

source
status

request_reason_code
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

replaced_by_arrangement_id

requires_approval
requires_separate_approver
risk_level_snapshot
risk_status_snapshot
baseline_policy_snapshot
recommended_policy_snapshot

created_at
updated_at
```

Adapt fields to project conventions.

## Mandatory constraints

* A visit may have only one pending request at a time.
* A visit may have only one current approved arrangement.
* Historical terminal requests remain available.
* `approved_policy` must be null unless the status is approved, revoked, expired, or replaced after approval.
* `requested_policy` must never be `inherit`.
* `approved_policy` must never be `inherit`.
* Free-text fields must be length-bounded.
* Use explicit short foreign-key names where necessary.
* Add indexes for:

  * visit
  * status
  * requested policy
  * approved policy
  * expiry
  * requester
  * approver

Do not add an `is_operational` field.

---

# 9. Arrangement History

Create:

```text
visit_payment_arrangement_history
```

Suggested fields:

```text
id
visit_payment_arrangement_id
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

* Append-only through normal application workflows.
* Store only material arrangement fields.
* Do not store patient contact information.
* Do not store clinical data.
* Do not store unrestricted patient-risk details.
* Render human-readable history.
* Do not expose raw JSON.
* Do not provide edit or delete actions.

---

# 10. Models and Relationships

Create:

```text
app/Models/VisitPaymentArrangement.php
app/Models/VisitPaymentArrangementHistory.php
```

Suggested relationships:

```php
Visit::paymentArrangements()
Visit::currentApprovedPaymentArrangement()
Visit::pendingPaymentArrangement()

VisitPaymentPolicy::arrangements()

VisitPaymentArrangement::visit()
VisitPaymentArrangement::materializedPolicy()
VisitPaymentArrangement::requester()
VisitPaymentArrangement::reviewer()
VisitPaymentArrangement::approver()
VisitPaymentArrangement::withdrawer()
VisitPaymentArrangement::revoker()
VisitPaymentArrangement::replacement()
VisitPaymentArrangement::history()
```

Suggested scopes:

```php
scopePending()
scopeApproved()
scopeCurrent()
scopeExpired()
scopeForRequestedPolicy()
scopeForApprovedPolicy()
scopeRequiringReview()
```

Avoid hidden service calls inside accessors.

---

# 11. Approval Requirement Decision

Create a typed decision DTO:

```text
app/Data/Billing/VisitPaymentArrangementApprovalRequirement.php
```

Suggested shape:

```php
final readonly class VisitPaymentArrangementApprovalRequirement
{
    public function __construct(
        public bool $requiresApproval,
        public bool $requiresSeparateApprover,
        public bool $requiresFinanceManager,
        public bool $requiresManagementApproval,
        public string $reasonCode,
        public array $context = [],
    ) {
    }
}
```

Create:

```text
app/Services/Billing/VisitPaymentArrangementApprovalPolicyService.php
```

This service should determine the administrative approval requirements.

It must not make an operational payment decision.

---

# 12. Approval Rules

Use the following initial rules.

## Requesting `pay_before_service`

This is more restrictive than allowing deferred payment.

It may be directly approved by an authorised finance manager where permissions allow.

However:

* A reason is required.
* The decision must be auditable.
* It must not affect emergency stabilisation.
* It remains non-operational in Phase 7.

## Requesting `pay_after_all_services`

Requires approval when:

* The baseline is `pay_before_service`.
* The patient has a `high_risk` snapshot.
* The patient has a `blocked_credit` snapshot.
* The visit has a prepayment recommendation.
* The visit has a material previous outstanding balance above any existing policy threshold.
* The request extends beyond a configured credit limit.
* The requester lacks direct-authorisation permission.

## Requesting `running_bill`

Requires approval when:

* The visit is outpatient.
* The baseline is not already `running_bill`.
* The patient is high risk or blocked credit.
* The request is not supported by an existing admission, emergency, corporate, or credit workflow.

## Restoring baseline

May be directly executed by an authorised user where it only removes an approved arrangement and returns the visit to the observational baseline.

This still requires:

* A reason
* History
* Audit

## Risk recommendation

A risk recommendation may pre-populate a request but may not auto-submit or auto-approve it.

No recommendation is automatically approved.

---

# 13. Self-Approval Rules

By default:

```text
requester cannot approve own request
```

Implement a dedicated permission only if the project genuinely requires exceptional self-approval:

```text
visits.payment_arrangement.self_approve
```

Do not grant it to normal roles.

If introduced:

* Grant only to Super Admin by default.
* Require a reason.
* Record that self-approval occurred.
* Keep the feature explicit.

Prefer not to introduce this permission unless needed.

---

# 14. Permissions

Add granular permissions:

```text
visits.payment_arrangement.view
visits.payment_arrangement.request
visits.payment_arrangement.approve
visits.payment_arrangement.reject
visits.payment_arrangement.withdraw
visits.payment_arrangement.revoke
visits.payment_arrangement.history
visits.payment_arrangement.report
```

Optional:

```text
visits.payment_arrangement.force_pre_service
visits.payment_arrangement.allow_post_service
visits.payment_arrangement.allow_running_bill
visits.payment_arrangement.restore_baseline
```

Use these only if the project benefits from policy-specific permissions.

Suggested role assignments:

## Super Admin / Admin

All permissions.

## Finance Manager

View, request, approve, reject, withdraw, revoke, history, report, and all policy-specific permissions.

## Accountant

View, request, withdraw, history, report.

No approval or revocation by default.

## Reception

Optional request-only access if hospital workflow requires it.

No approval access.

## Clinical users

No detailed arrangement management permissions.

Backend routes, FormRequests, policies, and services must enforce permissions.

---

# 15. Arrangement Service

Create:

```text
app/Services/Billing/VisitPaymentArrangementService.php
```

Suggested responsibilities:

```php
public function request(
    Visit $visit,
    VisitPaymentArrangementData $data,
    User $actor
): VisitPaymentArrangement;

public function approve(
    VisitPaymentArrangement $arrangement,
    VisitPaymentArrangementApprovalData $data,
    User $actor
): VisitPaymentArrangement;

public function reject(...): VisitPaymentArrangement;

public function withdraw(...): VisitPaymentArrangement;

public function revoke(...): VisitPaymentArrangement;

public function expireDue(...): int;

public function restoreBaseline(
    Visit $visit,
    string $reason,
    User $actor
): VisitPaymentArrangement;
```

Use dedicated DTOs or validated arrays according to project conventions.

## Service rules

Every mutation must:

* Run inside a database transaction.
* Lock the visit’s pending and current approved arrangement rows.
* Recheck status under lock.
* Enforce requester/approver separation.
* Append immutable history.
* Create exactly one material activity log.
* Preserve terminal records.
* Avoid duplicate current arrangements.
* Avoid duplicate pending requests.
* Never call the payment gate.
* Never create or modify invoices.
* Never create or modify receivables.
* Never change visit status.

---

# 16. Request Workflow

## Creating a request

A request should snapshot:

```text
baseline observed policy
risk recommendation
risk level
risk status
finance-review requirement
current approved arrangement, if any
```

These are request-time administrative snapshots.

Do not copy:

```text
free-text financial-risk details
patient contact information
clinical information
full policy history
```

## Updating a pending request

A requester may update their own pending request where permitted.

Updating must:

* Require the request to remain pending.
* Append history when material fields change.
* Recalculate approval requirements.
* Not allow changing the visit.
* Not allow direct status manipulation.

## Duplicate request prevention

Reject or reuse a pending request where the same visit already has one.

Do not silently create multiple pending requests.

---

# 17. Approval Workflow

Approval must:

1. Re-read and lock the arrangement.
2. Confirm status is pending.
3. Confirm actor has approval permission.
4. Confirm self-approval rules.
5. Re-evaluate approval requirements.
6. Validate that the requested policy remains allowed.
7. Mark any previous approved arrangement as replaced.
8. Store the approved policy.
9. Set effective and optional expiry dates.
10. Append history.
11. Create one activity log.
12. Update the linked observational visit policy only with a reference to the approved arrangement, if needed.

Do not replace the observational baseline fields.

Do not make the arrangement operational.

---

# 18. Linking to VisitPaymentPolicy

Extend `visit_payment_policies` only where necessary.

Potential additive fields:

```text
current_approved_arrangement_id
approved_policy_snapshot
approved_arrangement_observed_at
```

Use clear naming.

Rules:

* `resolved_policy` remains the baseline.
* `recommended_policy` remains the risk recommendation.
* `approved_policy_snapshot` is the administrative arrangement.
* None of these fields controls the gate in Phase 7.
* Avoid duplicating the entire arrangement record.
* A link to the current approved arrangement is preferable where sufficient.

Update the visit-policy UI to distinguish:

```text
Observed baseline
Risk recommendation
Approved administrative arrangement
Operational legacy gate
```

Display an explicit notice:

> The approved arrangement is recorded but does not yet control service access or payment enforcement.

---

# 19. Replacement Rules

Approving a new arrangement for a visit with an existing approved arrangement must:

* Mark the old arrangement as `replaced`.
* Set `replaced_by_arrangement_id`.
* Preserve the old arrangement’s original approval data.
* Append history to both records where appropriate.
* Update the current approved-arrangement link.
* Create one bounded activity event for the new approval and one replacement event where existing audit conventions require it.

Do not delete or mutate historical approval details.

---

# 20. Revocation Rules

An approved arrangement may be revoked by an authorised user.

Revocation requires:

* A reason
* Permission
* Current approved status
* Transactional locking
* History
* Activity logging

Revocation must:

* Remove the arrangement as the current approved arrangement.
* Preserve the baseline and recommendation.
* Not automatically restore or create another arrangement.
* Not affect past services.
* Not modify invoices.
* Not change the payment gate in Phase 7.

Possible reasons:

```text
credit conditions breached
insurance or corporate guarantee cancelled
management decision
incorrect approval
patient request
visit context changed
other
```

Use structured reason codes where useful.

---

# 21. Expiry Rules

Allow an optional `expires_at`.

Add:

```bash
php artisan billing:visit-payment-arrangement-expire
```

The command should:

* Find approved arrangements whose expiry has passed.
* Lock and recheck each arrangement.
* Mark it expired.
* Remove it as the current approved arrangement.
* Append history.
* Create one activity log.
* Remain idempotent.
* Avoid touching rejected, withdrawn, revoked, replaced, or already expired records.

Register it with the scheduler only if consistent with current project conventions.

Use:

```text
withoutOverlapping
onOneServer
```

where appropriate.

Expiry must not change the payment gate in Phase 7.

---

# 22. Visit Lifecycle Handling

Define behaviour for:

## Completed visit

Requests may be disallowed unless the visit is reopened or a permission explicitly allows post-completion financial correction.

## Cancelled visit

New requests should normally be rejected.

Pending requests may be automatically withdrawn or require manual closure according to existing conventions.

## Reopened visit

Existing approved arrangements should remain historically visible.

Whether they remain current should depend on:

* Expiry
* Revocation
* Visit identity remaining the same
* Existing reopening semantics

Do not automatically create a new arrangement.

## Inpatient discharge

An approved running-bill or pay-after-services arrangement remains administrative only.

Do not connect it to discharge clearance.

## Emergency visit

Requests must not imply that emergency stabilisation can be blocked.

Any approved prepayment arrangement must display an emergency-safety warning.

---

# 23. Patient Financial-Risk Integration

Use Phase 6 snapshots and current risk state only for approval requirements.

Do not change the patient’s financial-risk profile.

## High risk / blocked credit

A request to allow deferred payment must require:

* Separate approver
* Finance Manager or higher
* Reason
* Supporting reference where configured

## Watchlist

Require finance review, but do not automatically prohibit post-service payment.

## Cleared, expired, or suspended profile

Use the request-time snapshot.

Do not silently rewrite historical requests when the patient risk profile later changes.

## Risk changed after request

At approval time:

* Detect whether the current risk profile differs materially from the request snapshot.
* Flag the request as stale.
* Require explicit confirmation or request refresh.
* Do not silently approve based on outdated risk data.

---

# 24. Previous Outstanding Balance Context

Use existing balance services only to inform approval requirements.

Possible request-time context:

```text
previous patient-responsibility balance
oldest unpaid invoice age
number of unpaid prior visits
```

Rules:

* Do not copy full invoice lists.
* Do not create a parallel balance calculation.
* Do not automatically reject requests solely because debt exists unless configured.
* Do not merge previous-balance override semantics with visit payment arrangements.
* A previous-balance override remains a separate prior-debt exception.
* Approved visit arrangements must not automatically create previous-balance overrides.

---

# 25. Form Requests

Create dedicated FormRequests.

Suggested requests:

```text
StoreVisitPaymentArrangementRequest
UpdateVisitPaymentArrangementRequest
ApproveVisitPaymentArrangementRequest
RejectVisitPaymentArrangementRequest
WithdrawVisitPaymentArrangementRequest
RevokeVisitPaymentArrangementRequest
RestoreVisitPaymentBaselineRequest
```

Validation must cover:

* Visit exists and is eligible.
* Requested policy is a valid operational policy.
* `inherit` is rejected.
* Reason is required.
* Free text is length-bounded.
* Effective date is valid.
* Expiry is after effective date.
* Supporting reference is validated.
* Pending-state requirements.
* Approval-state requirements.
* Self-approval restriction.
* Policy-specific permission.
* Unknown fields are ignored or rejected according to project conventions.
* Direct endpoint access is permission-protected.

---

# 26. Controllers and Routes

Add controllers under the existing billing/admin namespace.

Possible controller:

```text
Admin\Billing\VisitPaymentArrangementController
```

Routes may include:

```text
GET    admin/billing/visit-payment-arrangements
GET    admin/billing/visit-payment-arrangements/{arrangement}
POST   visits/{visit}/payment-arrangements
PUT    visit-payment-arrangements/{arrangement}
POST   visit-payment-arrangements/{arrangement}/approve
POST   visit-payment-arrangements/{arrangement}/reject
POST   visit-payment-arrangements/{arrangement}/withdraw
POST   visit-payment-arrangements/{arrangement}/revoke
POST   visits/{visit}/payment-arrangements/restore-baseline
```

Follow current route naming and middleware conventions.

Keep controllers thin.

Use service methods for domain transitions.

---

# 27. User Interface

## Visit policy detail page

Extend the restricted Phase 6 page.

Show four clearly separated cards:

### Observed baseline

From the typed baseline resolver.

### Risk recommendation

Non-operational recommendation.

### Approved visit arrangement

The current approved administrative policy, where present.

### Operational legacy gate

Explain that live service access still follows the existing legacy gate.

## Arrangement actions

Depending on permission and state:

```text
Request Pay Before Service
Request Pay After All Services
Request Running Bill
Request Return to Baseline
Approve
Reject
Withdraw
Revoke
View History
```

## Request form

Show:

```text
Requested policy
Reason
Supporting reference
Effective date
Optional expiry date
Current baseline
Current recommendation
Current risk snapshot
Finance review requirement
```

## Safety labels

Display:

> Approval records an administrative arrangement only. It does not yet change live service-access enforcement.

For emergency visits:

> Emergency stabilisation remains protected regardless of this administrative arrangement.

Do not expose detailed risk reasons without financial-risk permission.

---

# 28. Approval Worklist

Add:

```text
admin/billing/visit-payment-arrangements
```

Filters:

```text
status
requested policy
approved policy
source
requester
approver
requires finance review
risk level snapshot
visit type
date range
expiring soon
stale request context
```

Columns:

```text
Visit
Patient
Visit type
Baseline
Recommendation
Requested policy
Status
Risk snapshot
Requester
Approver
Requested at
Expiry
```

Respect patient masking.

Do not show unrestricted free-text reasons in the broad list.

---

# 29. Arrangement History UI

Render:

```text
event
old status
new status
old requested policy
new requested policy
approved policy
actor
timestamp
reason code
```

Do not dump raw JSON.

No edit/delete controls.

History requires its own permission.

---

# 30. Activity Logging

Use existing `ActivityLog`.

Suggested actions:

```text
VISIT_PAYMENT_ARRANGEMENT_REQUESTED
VISIT_PAYMENT_ARRANGEMENT_UPDATED
VISIT_PAYMENT_ARRANGEMENT_APPROVED
VISIT_PAYMENT_ARRANGEMENT_REJECTED
VISIT_PAYMENT_ARRANGEMENT_WITHDRAWN
VISIT_PAYMENT_ARRANGEMENT_REVOKED
VISIT_PAYMENT_ARRANGEMENT_EXPIRED
VISIT_PAYMENT_ARRANGEMENT_REPLACED
VISIT_PAYMENT_BASELINE_RESTORED
```

Capture bounded metadata:

```text
visit id
arrangement id
requested policy
approved policy
old status
new status
requester id
approver id
risk level snapshot
requires finance review
effective and expiry timestamps
```

Do not include:

* Patient name
* Contact information
* Clinical information
* Free-text risk details
* Full request reason text where current audit conventions discourage it

Reads must not create activity logs.

Avoid duplicate service and observer logs.

---

# 31. Read-Only Audit Command

Add:

```bash
php artisan billing:visit-payment-arrangement-audit
```

Suggested options:

```text
--visit=
--status=
--pending
--approved
--expired
--stale
--problems-only
--limit=
--json
```

Detect:

```text
multiple pending requests for one visit
multiple current approved arrangements
approved record without approved policy
pending record with approved policy
requester equals approver
expired arrangement still current
replaced arrangement still current
terminal status missing timestamp
invalid effective/expiry ordering
request risk snapshot stale before approval
approved arrangement linked to missing visit-policy record
approved arrangement incorrectly treated as operational
```

Rules:

* Read-only
* No automatic repair
* No activity logs
* No sensitive free text
* Findings do not fail the exit code
* Technical command failure may return non-zero

---

# 32. Reporting

Add restricted aggregate metrics:

```text
pending requests
approved arrangements
rejected requests
revoked arrangements
expired arrangements
pay-before approvals
pay-after-services approvals
running-bill approvals
high-risk deferred-payment approvals
average approval time
requests awaiting review beyond threshold
```

Patient-level export requires report permission.

Export only necessary administrative fields.

No contact, clinical, or raw history data.

---

# 33. Concurrency and Integrity

Protect against:

```text
two simultaneous requests
two simultaneous approvals
approval while withdrawal occurs
approval while revocation occurs
replacement race
expiry while approval updates
stale risk context approval
```

Use:

* Transactions
* `lockForUpdate`
* Unique constraints where possible
* Idempotent state transitions
* Status rechecks inside transactions

Do not rely only on UI disabling.

Add focused concurrency tests.

---

# 34. Performance Requirements

Normal clinical workflows must not query arrangements.

Only restricted finance/admin pages should load:

```text
pending arrangement
current approved arrangement
history
```

Use:

* Eager loading
* Pagination
* Indexed filters
* Bounded balance context
* No full history in list pages

`BillingPolicyService` and `PaymentGateService` must not query arrangement tables in Phase 7.

---

# 35. Failure Safety

If request creation fails:

* Do not create partial history.
* Do not alter the visit policy record.
* Do not affect service access.

If approval fails:

* Roll back all state changes.
* Preserve any prior approved arrangement.
* Do not partially replace arrangements.
* Do not modify the gate.

If activity logging is required inside the material transaction and fails:

* Follow existing audit-integrity conventions.
* Do not claim success for a partial approval.

If expiry processing fails:

* Roll back that arrangement transition.
* Leave payment behaviour unchanged.

---

# 36. Non-Operational Guardrails

Do not modify operational behaviour in:

```text
VisitPaymentTimingResolver
BillingPolicyService
PaymentGateService
PaymentTimingPolicyComparisonService
InvoiceItemSettlementService
PreviousBalanceOverrideService
```

Do not:

* Add patient-risk precedence to the resolver.
* Add approved-arrangement precedence to the resolver.
* Make the gate read `visit_payment_arrangements`.
* Add typed enforcement mode.
* Change operation eligibility.
* Wire any unwired operation.
* Change laboratory compatibility.
* Change pharmacy compatibility.
* Change emergency behaviour.
* Change inpatient behaviour.
* Change previous-balance behaviour.
* Change invoice or receivable calculations.
* Change payment allocation.
* Change GL posting.
* Change visit completion.
* Change discharge.
* Change financial closure.

Explicitly test that an approved arrangement remains administrative only.

---

# 37. Localisation

Add:

```text
lang/en/visit_payment_arrangement.php
lang/fr/visit_payment_arrangement.php
```

Required concepts:

```text
Visit Payment Arrangement
Request Arrangement
Requested Policy
Approved Policy
Pending
Approved
Rejected
Withdrawn
Revoked
Expired
Replaced
Pay Before Service
Pay After All Services
Running Bill
Return to Baseline
Requires Approval
Separate Approver Required
Finance Review Required
Supporting Reference
Effective Date
Expiry Date
Approve
Reject
Withdraw
Revoke
Restore Baseline
Request Context Is Stale
Administrative Arrangement Only
Does Not Control Service Access
Emergency Stabilisation Remains Protected
```

Also localise:

* Validation messages
* Status labels
* Event labels
* Reason codes where user-facing
* Worklist filters
* Empty states
* Report labels
* Success and failure feedback

Maintain English/French parity.

---

# 38. Tests

Do not run the full Laravel or Playwright suites during this phase.

## 38.1 Enum and model tests

Verify:

* Statuses, sources, and events exist.
* Enum casts work.
* One pending request per visit.
* One current approved arrangement per visit.
* Relationships work.
* Terminal records remain historical.
* Requested and approved policies reject `inherit`.

## 38.2 Approval-policy tests

Verify:

* Pay-before request requirements.
* Pay-after request requirements.
* Running-bill request requirements.
* High-risk deferred-payment request requires separate approval.
* Blocked-credit deferred-payment request requires separate approval.
* Watchlist requires finance review.
* Baseline restoration rules.
* Risk recommendation never auto-approves.
* Approval policy remains administrative only.

## 38.3 Service workflow tests

Verify:

* Request succeeds.
* Pending request can be updated.
* Duplicate pending request is rejected.
* Approval succeeds.
* Rejection succeeds.
* Withdrawal succeeds.
* Revocation succeeds.
* Expiry succeeds.
* Replacement succeeds.
* Baseline restoration succeeds.
* Invalid transitions fail.
* Terminal states cannot be reused.
* History is appended once.
* Activity log is written once.

## 38.4 Self-approval tests

Verify:

* Requester cannot approve own request.
* Separate approver requirement is enforced.
* Direct endpoint calls cannot bypass the rule.
* Any exceptional self-approval permission, if introduced, is narrowly controlled and audited.

## 38.5 Concurrency tests

Verify:

* Concurrent requests produce only one pending request.
* Concurrent approvals produce only one current approved arrangement.
* Replacement is transaction-safe.
* Approval versus withdrawal is safe.
* Approval versus expiry is safe.

## 38.6 Risk snapshot tests

Verify:

* Request-time risk snapshot is stored.
* Free-text risk details are excluded.
* Risk change after request is detected.
* Stale request cannot be approved silently.
* Request refresh or explicit confirmation is required.
* Patient risk profile itself is not changed.

## 38.7 Permission and confidentiality tests

Verify:

* View, request, approve, reject, withdraw, revoke, history, and report permissions are separate.
* Unauthorised users do not receive arrangement data in page source, JSON, or props.
* Clinical users do not see detailed reasons.
* Patient masking remains effective.
* Accountant cannot approve.
* Finance Manager can approve.
* Reception cannot approve.

## 38.8 UI tests

Verify:

* Baseline, recommendation, approved arrangement, and legacy gate are visibly distinct.
* Request form shows the correct context.
* State-dependent actions render correctly.
* Emergency warning appears where applicable.
* History is human-readable.
* Worklist filters and pagination work.
* No operational wording suggests the gate has switched.

## 38.9 Expiry and audit command tests

Verify:

* Due arrangements expire.
* Repeated expiry runs are idempotent.
* Audit command detects invalid combinations.
* JSON output is valid.
* Commands perform no unintended writes.
* Audit findings do not fail the exit code.

## 38.10 Non-enforcement regression tests

Explicitly verify:

* Approved `pay_before_service` does not introduce new blocking.
* Approved `pay_after_all_services` does not release existing hard gates.
* Approved `running_bill` does not alter OPD legacy behaviour.
* Approved arrangement does not change `VisitPaymentTimingResolver`.
* Approved arrangement does not change `BillingPolicyService`.
* Approved arrangement does not change `PaymentGateService`.
* Triage outcomes remain unchanged.
* Consultation readiness remains unchanged.
* Laboratory outcomes remain unchanged.
* Pharmacy paid-only behaviour remains unchanged.
* Previous-balance outcomes remain unchanged.
* Emergency and inpatient outcomes remain unchanged.
* No unwired operation becomes wired.
* No invoice, payment, receivable, override, or GL mutation occurs from approval alone.

## 38.11 Localisation tests

Verify English/French parity.

---

# 39. Targeted Verification

Run focused checks only.

Suggested commands:

```bash
php artisan migrate
php artisan route:list
php artisan config:clear
php artisan view:clear
php artisan view:cache

php artisan test --filter=VisitPaymentArrangementEnum
php artisan test --filter=VisitPaymentArrangementModel
php artisan test --filter=VisitPaymentArrangementApprovalPolicy
php artisan test --filter=VisitPaymentArrangementService
php artisan test --filter=VisitPaymentArrangementPermission
php artisan test --filter=VisitPaymentArrangementWorkflow
php artisan test --filter=VisitPaymentArrangementConcurrency
php artisan test --filter=VisitPaymentArrangementCommand
php artisan test --filter=VisitPaymentArrangementNonEnforcement
```

Rerun:

```bash
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

Run:

```bash
php artisan billing:visit-payment-arrangement-audit
php artisan billing:visit-payment-arrangement-expire --dry-run
php artisan billing:visit-payment-policy-audit
php artisan billing:financial-risk-audit
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

# 40. Documentation

Create:

```text
docs/billing/PAYMENT_TIMING_POLICY_PHASE_7_REPORT.md
```

The report must include:

1. Existing approval and override architecture audited
2. Data-model decision
3. Files created
4. Files modified
5. Arrangement status, source, and event vocabulary
6. Request lifecycle
7. Approval requirement rules
8. Self-approval prevention
9. Risk-based approval requirements
10. Previous-balance context handling
11. Approved-arrangement linkage to materialised visit policy
12. Replacement, revocation, and expiry behaviour
13. Permissions and role assignments
14. Privacy and restricted visibility
15. Worklist and detail UI
16. History and activity-log behaviour
17. Audit and expiry command results
18. Concurrency protection
19. Query and performance impact
20. Focused tests and results
21. Confirmation that approved arrangements remain non-operational
22. Deferred requirements for Phase 8

Do not claim tests passed unless they were executed successfully.

---

# 41. Guardrails

Do not:

* Make approved arrangements operational
* Add arrangement precedence to `VisitPaymentTimingResolver`
* Make `BillingPolicyService` read approved arrangements
* Make `PaymentGateService` read approved arrangements
* Create typed enforcement mode
* Change operation eligibility
* Wire any unwired operation
* Automatically approve recommendations
* Allow self-approval by default
* Broaden existing billing override scopes
* Create previous-balance overrides
* Modify patient financial-risk profiles
* Change laboratory compatibility
* Change pharmacy paid-only behaviour
* Change emergency behaviour
* Change inpatient behaviour
* Change invoice calculations
* Change receivables
* Change allocations
* Change GL posting
* Change visit completion
* Change discharge
* Change financial closure
* Expose sensitive risk details to clinical users
* Create activity logs for reads
* Run broad test suites

---

# 42. Acceptance Criteria

Phase 7 is complete only when:

* A dedicated per-visit arrangement model exists.
* Requests have explicit lifecycle statuses.
* A visit can have only one pending request.
* A visit can have only one current approved arrangement.
* Requested and approved policies cannot be `inherit`.
* Requester and approver are historically traceable.
* Self-approval is prevented by default.
* High-risk and blocked-credit deferred-payment requests require separate approval.
* Recommendations never auto-submit or auto-approve.
* Approval requirements are centrally resolved.
* Requests, approvals, rejection, withdrawal, revocation, replacement, expiry, and baseline restoration work transactionally.
* Historical arrangements are never deleted.
* Immutable arrangement history exists.
* Material mutations create one activity log.
* Routine reads create no activity logs.
* Risk snapshots are bounded and privacy-safe.
* Stale request context is detected before approval.
* Permissions are granular and backend-enforced.
* Finance users have a restricted worklist and detail interface.
* Baseline, recommendation, approved arrangement, and operational legacy gate are visibly distinct.
* Approved arrangements remain administrative only.
* No production payment outcome changes.
* No new operation becomes wired.
* Existing triage, consultation, laboratory, pharmacy, emergency, inpatient, and previous-balance behaviour remains unchanged.
* English/French localisation is complete.
* Focused tests pass.
* The Phase 7 report accurately documents implementation and verification.

Proceed with **Payment Timing Policy Phase 7 only**.

After implementation, provide:

1. A concise implementation summary
2. Files created and modified
3. Arrangement data model and lifecycle
4. Approval requirement rules
5. Self-approval controls
6. Risk-based approval handling
7. Replacement, revocation, and expiry behaviour
8. Permission and privacy design
9. UI and worklist details
10. Audit and history behaviour
11. Concurrency safeguards
12. Diagnostic command results
13. Query and performance impact
14. Focused test results
15. Confirmation that approved arrangements remain non-operational
16. The Phase 7 report path
17. Recommended requirements for Phase 8

Then stop after Phase 7.
