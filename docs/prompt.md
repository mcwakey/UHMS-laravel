# UHMS Implementation Prompt — Payment Timing Policy Phase 6

## Visit-Level Policy Materialisation, Financial-Risk Snapshots, and Non-Operational Recommendations

You are working on **UHMS**, a Laravel-based hospital management system.

Implement **Phase 6 of the Configurable Payment Timing and Per-Visit Payment Policy system**.

Phases 1–5 are complete.

---

# 1. Existing Foundation

## Phase 1 — Payment-timing configuration

Implemented:

* `VisitPaymentTimingPolicy`
* `VisitPaymentPolicySource`
* Global and visit-type payment-timing configuration
* Database-backed payment-timing settings
* `PaymentTimingConfigurationService`
* Admin configuration, localisation, permissions, and audit

## Phase 2 — Legacy integration and observation

Implemented:

* `PaymentTimingIntegrationMode`
* `VisitPaymentTimingDecision`
* `VisitPaymentTimingResolver`
* Legacy compatibility mapping
* Typed-versus-legacy comparison
* Legacy-authoritative observation mode
* Bounded mismatch diagnostics
* `billing:payment-timing-audit`

Legacy decisions remain operationally authoritative.

## Phase 3 — Central payment façade

Implemented:

* `PaymentGateStage`
* `PaymentGateContext`
* Central production access through `PaymentGateService`
* Stage-aware observation
* `PaymentGateOperationRegistry`
* `billing:payment-gate-coverage`

Four production operations are currently wired:

```text
consultation.route.complete
consultation.next_patient.readiness
laboratory.result.enter
pharmacy.item.dispense
```

Nine operations remain intentionally unwired.

## Phase 4 — Departmental enforcement configuration

Implemented:

* `PaymentGateOperationMode`
* `MissingBillingContextPolicy`
* `PaymentGateVisitContextRule`
* `PaymentGateOverrideScopeRule`
* `PaymentGateOperationPolicy`
* `PaymentGateEnforcementEligibility`
* Per-operation configuration, diagnostics, admin interface, and audit

All 13 operations remain configuration-non-operational.

No typed enforcement mode exists.

## Phase 5 — Patient financial-risk profiles

Implemented:

* `PatientFinancialRiskLevel`
* `PatientFinancialRiskReason`
* `PatientFinancialRiskStatus`
* `PatientFinancialRiskEvent`
* Dedicated financial-risk profile and immutable history tables
* Transactional classification and lifecycle service
* Restricted permissions and privacy filtering
* Patient profile UI and finance worklist
* Expiry and audit commands
* Aggregated report and scoped export

Financial-risk data currently has no effect on:

* Visits
* Payment timing
* Payment gates
* Invoices
* Receivables
* Overrides
* Visit completion
* Discharge
* Financial closure

---

# 2. Phase 6 Goal

Create a secure and auditable **visit-level payment-policy materialisation subsystem**.

For each visit, UHMS should be able to preserve:

* The typed payment-timing policy resolved for that visit
* The source of that policy
* The global policy applicable at materialisation time
* The visit-type configuration applicable at materialisation time
* Emergency-protection context
* Compatible visit-wide legacy override context
* The patient’s financial-risk profile at that moment
* A non-operational risk-based policy recommendation
* Whether finance review is recommended
* The policy-resolution version used
* The date and time the record was materialised

This phase must provide a stable visit-specific record for later manual approvals and overrides.

It must not yet allow the visit record or risk recommendation to alter any production payment decision.

---

# 3. Core Separation

The implementation must keep these concepts separate:

```text
1. Baseline typed visit policy
2. Patient financial-risk snapshot
3. Risk-based policy recommendation
4. Operational legacy payment-gate decision
5. Future approved visit-specific policy
```

In Phase 6:

* The baseline typed policy may be materialised.
* The patient-risk state may be snapshotted.
* A candidate recommendation may be calculated.
* The recommendation may say that finance review is required.
* The recommendation must not become the operational policy.
* The payment gate must continue returning the legacy decision.
* No service may be blocked or released because of the materialised record.

Do not use one `effective_policy` field ambiguously for both observation and production enforcement.

---

# 4. Mandatory Architecture Audit

Before modifying code, inspect:

## Visit creation

* All services, controllers, jobs, imports, commands, seeders, and APIs that create visits
* Central visit-creation services
* Visit events and listeners
* Appointment-to-visit conversion
* Emergency visit creation
* Admission visit creation
* Walk-in and direct-registration workflows
* Test and fixture creation paths

Determine the safest central integration point for materialisation.

Do not add duplicate materialisation calls to many controllers if one stable visit-created event or service exists.

## Visit model and workflow

Inspect:

* `Visit`
* `VisitType`
* Visit status and lifecycle
* Visit merge or replacement behaviour
* Visit cancellation
* Visit reopening
* Visit completion
* Admission and emergency associations
* Existing activity-log behaviour
* Existing finance and billing tabs

## Payment timing

Inspect:

* `VisitPaymentTimingResolver`
* `VisitPaymentTimingDecision`
* `PaymentTimingConfigurationService`
* `PaymentTimingLegacyCompatibilityService`
* Existing compatible visit-wide overrides
* Existing emergency policy handling
* Integration-mode behaviour

## Financial risk

Inspect:

* `PatientFinancialRiskService`
* `PatientFinancialRiskProfile`
* Risk status and active-slot semantics
* Restricted visibility permissions
* Financial-risk history
* Expiry behaviour

## Existing history and snapshot patterns

Inspect whether UHMS already has:

* Visit snapshot tables
* Policy history tables
* Immutable assessment snapshots
* Versioned decision records
* Event-based visit metadata

Reuse established conventions where suitable.

Document the architecture findings in the Phase 6 report.

---

# 5. Visit Payment Policy Record

Create a dedicated table:

```text
visit_payment_policies
```

The table should contain one current materialised policy record per visit.

Suggested fields:

```text
id
visit_id

resolved_policy
resolution_source
resolution_reason_code

recommended_policy
recommendation_source
recommendation_reason_code
requires_finance_review

global_default_snapshot
visit_type_policy_snapshot
visit_type_snapshot
emergency_protection_snapshot

compatible_override_type_snapshot
compatible_override_scope_snapshot
compatible_override_id_snapshot

patient_financial_risk_profile_id
patient_risk_level_snapshot
patient_risk_status_snapshot
patient_risk_reason_snapshot
patient_risk_observed_at

resolution_version
materialized_at
last_refreshed_at

created_at
updated_at
```

Adapt fields to repository conventions.

## Mandatory rules

* `visit_id` must be unique.
* The final `resolved_policy` must never contain `inherit`.
* `recommended_policy` may be nullable.
* `recommendation_source` should normally be nullable unless a recommendation exists.
* `requires_finance_review` must default to false.
* Snapshot fields must represent point-in-time values.
* Do not store free-text financial-risk details.
* Do not store patient phone, email, address, diagnosis, or clinical notes.
* Use shortened explicit foreign-key names where MySQL identifier limits require them.
* Add useful indexes for resolved policy, source, finance-review flag, risk level, and materialisation date.
* Use enum casts.

Do not add an operational or active enforcement flag in Phase 6.

The record is observational by architecture.

---

# 6. Visit Payment Policy History

Create:

```text
visit_payment_policy_history
```

This must be append-only through normal application workflows.

Suggested fields:

```text
id
visit_payment_policy_id
visit_id
event_type
old_values
new_values
reason_code
performed_by
performed_at
created_at
```

Suggested events:

```text
materialized
refreshed
risk_snapshot_changed
baseline_policy_changed
recommendation_changed
marked_stale
```

Create an enum if consistent with existing history patterns:

```text
VisitPaymentPolicyEvent
```

Rules:

* Do not store patient snapshots.
* Store only material payment-policy and risk-snapshot fields.
* Do not store unrestricted free text.
* Do not permit UI editing or deletion of history rows.
* Do not duplicate routine read activity in `ActivityLog`.
* Avoid duplicate history entries when nothing changed.

---

# 7. Visit Payment Policy Model

Create:

```text
app/Models/VisitPaymentPolicy.php
app/Models/VisitPaymentPolicyHistory.php
```

Suggested relationships:

```php
Visit::paymentPolicy()
Visit::paymentPolicyHistory()

VisitPaymentPolicy::visit()
VisitPaymentPolicy::patientFinancialRiskProfile()
VisitPaymentPolicy::history()

VisitPaymentPolicyHistory::policy()
VisitPaymentPolicyHistory::visit()
VisitPaymentPolicyHistory::performer()
```

Use enum casts for:

```text
resolved_policy
resolution_source
recommended_policy
recommendation_source
patient_risk_level_snapshot
patient_risk_status_snapshot
event_type
```

Add scopes such as:

```php
scopeRequiringFinanceReview()
scopeForResolvedPolicy()
scopeForRecommendation()
scopeWithRiskSnapshot()
scopeMaterializedBetween()
```

Avoid hidden service calls or automatic policy refreshes inside model accessors.

---

# 8. Risk Recommendation Vocabulary

Do not treat risk recommendations as operational decisions.

Create a DTO:

```text
app/Data/Billing/PatientRiskPaymentRecommendation.php
```

Suggested structure:

```php
final readonly class PatientRiskPaymentRecommendation
{
    public function __construct(
        public ?VisitPaymentTimingPolicy $recommendedPolicy,
        public ?VisitPaymentPolicySource $source,
        public ?string $reasonCode,
        public bool $requiresFinanceReview,
        public bool $approvedForResolution,
        public array $context = [],
    ) {
    }

    public static function none(): self
    {
        // Safe no-recommendation result.
    }
}
```

Keep context bounded and non-sensitive.

Possible machine reason codes:

```text
no_active_risk_profile
risk_profile_suspended
risk_profile_under_review
watchlist_review_recommended
high_risk_prepayment_recommended
blocked_credit_prepayment_recommended
risk_rule_not_approved
risk_profile_expired
```

Do not use translated strings as canonical reason codes.

---

# 9. Risk-to-Policy Recommendation Rules

Create:

```text
app/Services/Billing/PatientRiskPaymentRecommendationService.php
```

This service should read the active patient financial-risk profile and return a recommendation only.

Suggested provisional rules:

## No active profile

```text
recommended policy: null
requires finance review: false
approved for resolution: false
```

## `normal`

```text
recommended policy: null
requires finance review: false
approved for resolution: false
```

## `watchlist`

```text
recommended policy: null
requires finance review: true
reason: watchlist_review_recommended
approved for resolution: false
```

## `high_risk`

```text
recommended policy: pay_before_service
requires finance review: true
reason: high_risk_prepayment_recommended
approved for resolution: false
```

## `blocked_credit`

```text
recommended policy: pay_before_service
requires finance review: true
reason: blocked_credit_prepayment_recommended
approved for resolution: false
```

## Suspended, cleared, or expired profile

No active recommendation.

## Under review

Preserve the snapshotted risk level, but require finance review.

Do not automatically resolve the visit to `pay_before_service`.

---

# 10. Risk-Policy Approval Configuration

Mirror the safe non-operational approach used by Phase 4.

Create a typed configuration layer describing whether a risk level has been approved to influence future policy resolution.

Suggested DTO:

```text
PatientFinancialRiskPolicyRule
```

Suggested service:

```text
PatientFinancialRiskPolicyConfigurationService
```

Possible rule fields:

```text
risk level
recommended policy
requires finance review
approved_for_resolution
```

Initial defaults:

| Risk level     | Recommendation     | Finance review | Approved for resolution |
| -------------- | ------------------ | -------------: | ----------------------: |
| Normal         | None               |             No |                      No |
| Watchlist      | None               |            Yes |                      No |
| High risk      | Pay before service |            Yes |                      No |
| Blocked credit | Pay before service |            Yes |                      No |

Rules:

* All levels must initially have `approved_for_resolution = false`.
* Do not expose an operational activation switch in Phase 6.
* Settings may be diagnostic and preparatory only.
* Invalid settings must fall back to safe defaults.
* A stored setting must never make a risk recommendation operational.
* Do not add typed enforcement mode.

Use existing settings infrastructure if database-backed storage is required.

If no admin editability is needed yet, keep the rules in typed configuration and document that choice.

---

# 11. Visit Payment Policy Materialisation Service

Create:

```text
app/Services/Billing/VisitPaymentPolicyMaterializationService.php
```

Responsibilities:

```php
public function materialize(
    Visit $visit,
    ?User $actor = null
): VisitPaymentPolicy;

public function refresh(
    Visit $visit,
    ?User $actor = null,
    ?string $reasonCode = null
): VisitPaymentPolicy;

public function preview(
    Visit $visit
): VisitPaymentPolicyPreview;
```

Create a preview DTO if useful.

## Materialisation flow

1. Load the visit and patient using bounded required relations.
2. Resolve the baseline typed policy through the existing `VisitPaymentTimingResolver`.
3. Obtain the current patient-risk recommendation.
4. Snapshot the current risk profile.
5. Capture global and visit-type policy context.
6. Capture compatible visit-wide legacy override context.
7. Create or update the unique visit payment-policy record.
8. Append history only when the record is created or materially changed.
9. Write an activity log only for material creation or refresh.
10. Return the stored record.

## Mandatory behaviour

The stored `resolved_policy` must come from the current typed baseline resolver.

The risk recommendation must be stored separately.

Do not replace:

```text
resolved_policy
```

with:

```text
recommended_policy
```

even for `high_risk` or `blocked_credit`.

---

# 12. Point-in-Time Snapshot Rules

Risk snapshots must be historically meaningful.

Capture:

```text
patient financial-risk profile ID
risk level
risk status
structured reason
observation timestamp
```

Do not capture:

```text
reason details
reference
clearance reason
patient contact information
full financial-risk history
```

## Existing visit behaviour

A later change to the patient’s financial-risk profile must not silently rewrite an existing visit snapshot.

Existing visit snapshots remain unchanged unless an authorised or system-controlled refresh explicitly occurs.

## New visit behaviour

New visits should snapshot the financial-risk profile available at visit materialisation time.

## Stale snapshot detection

Provide a diagnostic method such as:

```php
public function snapshotIsStale(
    VisitPaymentPolicy $policy
): bool;
```

A snapshot may be considered stale when:

* The patient has a newer active risk profile
* The snapshotted profile was cleared, suspended, or expired later
* The risk profile was materially updated after observation
* The visit record has no snapshot despite a current restrictive profile

Staleness must not automatically change the visit policy.

---

# 13. Materialisation Trigger

Integrate materialisation into the safest central visit-creation path discovered during the audit.

Preferred order:

1. Existing domain event and listener
2. Existing central visit-creation service
3. Existing workflow orchestration service
4. Model observer only if already consistent with project conventions

Do not add duplicated calls across many controllers.

## Transaction behaviour

* The visit must exist before materialisation.
* If the project requires the policy record to be created atomically with the visit, use the existing visit transaction.
* If materialisation occurs after commit, ensure failure cannot roll back an otherwise valid clinical visit unless the project explicitly requires it.
* Materialisation failure must not block emergency care.
* Log technical failures safely.
* Provide a repair/backfill command.

## Required coverage

Ensure materialisation works for:

```text
outpatient visits
inpatient visits
emergency visits
appointment-generated visits
direct/walk-in visits
```

Use actual existing creation paths.

---

# 14. Existing Visit Backfill

Add:

```bash
php artisan billing:visit-payment-policy-backfill
```

Suggested options:

```text
--visit=
--visit-type=
--active-only
--missing-only
--limit=
--dry-run
--commit
--json
```

Rules:

* Default to dry-run unless project conventions strongly favour explicit confirmation.
* `--commit` performs writes.
* Bound the maximum batch size.
* Use chunking.
* Avoid N+1 risk-profile and override queries.
* Do not create invoices, payments, receivables, or overrides.
* Do not change visit status.
* Append history only for committed materialisations.
* Create activity logs only for actual committed material changes.
* Repeated execution must be idempotent.
* Do not overwrite an existing record unless refresh is explicitly requested.

---

# 15. Refresh Behaviour

Do not automatically refresh every visit when:

* The global default changes
* A visit-type policy changes
* A patient-risk profile changes
* A risk profile expires
* An override is created or revoked

Phase 6 should preserve the original snapshot by default.

Provide explicit controlled refresh through:

* The materialisation service
* A maintenance command
* Future authorised UI action, if deliberately included

If adding a refresh command:

```bash
php artisan billing:visit-payment-policy-refresh
```

support:

```text
--visit=
--active-only
--stale-only
--limit=
--dry-run
--commit
--reason=
```

Every material refresh must append history.

Do not add automatic scheduled refresh in Phase 6.

---

# 16. Activity Logging

Use the existing `ActivityLog` architecture.

Suggested actions:

```text
VISIT_PAYMENT_POLICY_MATERIALIZED
VISIT_PAYMENT_POLICY_REFRESHED
VISIT_PAYMENT_POLICY_RISK_SNAPSHOT_CHANGED
VISIT_PAYMENT_POLICY_RECOMMENDATION_CHANGED
```

Capture only bounded metadata:

```text
visit identifier
policy record identifier
old resolved policy
new resolved policy
old recommendation
new recommendation
resolution source
risk level snapshot
requires finance review
actor or system source
timestamp
```

Do not include:

* Patient name
* Contact information
* Clinical details
* Free-text financial-risk details
* Insurance identifiers

Do not log routine reads or previews.

Avoid duplicate logs from observers and services.

---

# 17. Permissions

Add focused visit-policy permissions.

Suggested permissions:

```text
visits.payment_policy.view
visits.payment_policy.history
visits.payment_policy.report
visits.payment_policy.refresh
```

Suggested initial role assignment:

## Super Admin / Admin

All permissions.

## Finance Manager

View, history, report, refresh.

## Accountant

View, history, report.

## Clinical and reception roles

No detailed visit-policy snapshot permissions by default.

Rules:

* Backend routes and controllers must enforce permissions.
* Do not rely only on hidden Blade elements.
* `refresh` must not mean approve or activate.
* No permission in Phase 6 should allow a user to change the operational payment policy.

---

# 18. Restricted Visit-Level UI

Add a restricted **Payment Policy** section to the appropriate visit billing or finance interface.

Show:

```text
Observed visit policy
Policy source
Resolution reason
Global default snapshot
Visit-type policy snapshot
Emergency protection considered
Compatible visit-wide override observed
Risk level snapshot
Risk status snapshot
Risk recommendation
Finance review required
Materialised at
Last refreshed
Snapshot freshness
```

Clearly display:

> This record is observational and does not currently control service access or payment enforcement.

Do not display sensitive risk reason details to users without the appropriate financial-risk permission.

## Suggested labels

Distinguish:

```text
Observed policy
Recommended policy
Operational legacy gate
```

Do not label the recommendation as the patient’s active payment arrangement.

---

# 19. Finance Worklist

Add a restricted worklist or extend an existing billing worklist to show visits with materialised policies.

Possible route:

```text
admin/billing/visit-payment-policies
```

Filters:

```text
resolved policy
resolution source
recommended policy
finance review required
risk level snapshot
visit type
active visits only
stale snapshot
materialisation date range
```

Columns:

```text
Visit
Patient
Visit type
Observed policy
Source
Risk snapshot
Recommendation
Finance review
Snapshot status
Materialised at
```

Use existing patient privacy masking.

Do not expose confidential risk details in the broad list.

No approval or override action should exist in Phase 6.

---

# 20. Non-Sensitive Finance Review Indicator

Prepare a restricted helper or DTO for finance workflows:

```text
Financial review required
```

This indicator may be shown to:

* Finance worklists
* Billing staff with visit payment-policy permission
* Management reports

Do not expose:

```text
High-risk patient
Blocked-credit patient
Repeated abandoned invoices
```

to ordinary clinical users.

Do not add the indicator to clinical consultation, laboratory, pharmacy, nursing, or emergency workspaces in Phase 6.

---

# 21. History Interface

Add a permission-controlled visit payment-policy history panel.

Show human-readable transitions:

```text
Materialised
Baseline policy refreshed
Risk snapshot changed
Recommendation changed
Marked stale
```

Display:

```text
date/time
event
previous policy
new policy
previous recommendation
new recommendation
risk snapshot change
performed by
reason code
```

Do not render raw JSON.

Do not provide edit or delete controls.

---

# 22. Diagnostic Audit Command

Add:

```bash
php artisan billing:visit-payment-policy-audit
```

Suggested options:

```text
--visit=
--visit-type=
--missing
--stale
--finance-review
--problems-only
--limit=
--json
```

Detect:

```text
visits missing a materialised policy
duplicate visit policy records
resolved policy still set to inherit
unknown resolution source
recommendation without source
source without recommendation
restrictive risk snapshot without expected recommendation
recommendation incorrectly treated as resolved policy
stale risk snapshot
missing resolution version
invalid materialisation timestamps
unexpected sensitive fields in snapshot payload
```

Rules:

* Read-only
* No automatic repair
* No patient-sensitive free text
* No activity logs
* Findings do not fail the exit code
* Technical command failures may return non-zero

---

# 23. Resolver Guardrails

Do not modify `VisitPaymentTimingResolver` to read patient financial-risk profiles.

Do not insert patient-risk precedence into the existing resolver.

The materialisation service may call:

```text
VisitPaymentTimingResolver
```

for the baseline decision and separately call:

```text
PatientRiskPaymentRecommendationService
```

for the recommendation.

The two results must remain separate.

No production caller may replace the resolver result with the risk recommendation.

---

# 24. Payment-Gate Guardrails

Do not modify operational behaviour in:

```text
BillingPolicyService
PaymentGateService
PaymentTimingPolicyComparisonService
InvoiceItemSettlementService
PreviousBalanceOverrideService
VisitBillingOverrideService
```

Only additive diagnostic context may be introduced if absolutely required.

Explicitly prove:

* High-risk recommendation does not block service.
* Blocked-credit recommendation does not block service.
* Pay-after-services baseline remains unchanged by the recommendation.
* Emergency running-bill baseline remains unchanged.
* Pharmacy paid-only behaviour remains unchanged.
* Laboratory compatibility behaviour remains unchanged.
* Triage and consultation readiness remain unchanged.
* No unwired operation becomes wired.

---

# 25. Existing Legacy Overrides

The materialised record may snapshot compatible visit-wide override context already recognised by the Phase 2 resolver.

Capture only:

```text
override identifier
override type
override scope
whether it was considered
```

Do not capture unrestricted override reasons.

Do not broaden:

* Department-scoped overrides
* Service-scoped overrides
* Invoice-item-scoped overrides
* Previous-balance overrides
* Financial-closure overrides

Do not create a new override.

---

# 26. Resolution Versioning

Add an explicit version identifier to materialised records.

Example:

```text
payment_timing_v1
```

or a project-consistent alternative.

The version should identify the policy-materialisation algorithm.

Rules:

* Use a stable machine identifier.
* Do not use application build timestamps.
* A future algorithm change should use a new version.
* Refresh history should show version changes.
* Do not dynamically generate versions per request.

---

# 27. Seeding and Existing Data

Do not seed visit payment policies for production data through `DatabaseSeeder`.

Use the backfill command for existing visits.

Test factories may create materialised visit-policy records.

Manual-test seeders may materialise policies only in dedicated testing datasets.

Do not classify patients or create risk profiles during backfill.

Do not create overrides during backfill.

---

# 28. Performance Requirements

Normal clinical workflows must remain lightweight.

## Visit creation

Materialisation should use:

* Already-loaded visit
* Already-loaded patient where available
* Cached payment-timing settings
* A bounded active-risk-profile query
* A bounded compatible-override query

Avoid unnecessary invoice, receivable, payment, or balance queries.

## Lists

* Eager-load visit, patient, and relevant actors
* Paginate
* Avoid loading full history
* Avoid repeated risk-profile lookups
* Use indexed filters

## Payment gates

Payment-gate evaluation must not query `visit_payment_policies` in Phase 6.

Add focused query assertions where stable.

---

# 29. Failure Safety

If materialisation fails during visit creation:

* Do not alter the payment-gate decision.
* Do not silently classify the patient.
* Do not create partial history.
* Roll back only according to the chosen visit-creation integration contract.
* Emergency care must not be blocked because the observational record failed.
* Log the technical failure through existing application logging.
* Allow later repair through the backfill command.

If risk recommendation fails:

* Store or return a safe no-recommendation result.
* Preserve the baseline typed policy.
* Do not block the visit.

If history or required audit logging fails during an explicit committed refresh:

* Follow existing material-audit transaction conventions.
* Do not claim success for a partial mutation.

---

# 30. Localisation

Add complete English and French localisation.

Suggested files:

```text
lang/en/visit_payment_policy.php
lang/fr/visit_payment_policy.php
```

Required concepts:

```text
Visit Payment Policy
Observed Policy
Recommended Policy
Operational Legacy Gate
Policy Source
Resolution Reason
Finance Review Required
Risk Snapshot
Snapshot Current
Snapshot Stale
Materialised At
Last Refreshed
Global Default Snapshot
Visit-Type Policy Snapshot
Emergency Protection
Compatible Visit Override
This policy is observational
No recommendation
Prepayment recommended
Payment after services
Running bill
Visit policy history
Materialised
Refreshed
Risk snapshot changed
Recommendation changed
```

Also localise:

* Worklist labels
* Filters
* Empty states
* Permission-related error messages
* Command descriptions where project conventions require them

Machine codes and stored enum values remain untranslated.

Maintain English/French parity.

---

# 31. Tests

Do not run the full Laravel or Playwright suites during this phase.

Add focused tests.

## 31.1 Migration and model tests

Verify:

* One policy record can be created per visit.
* Duplicate visit records are prevented.
* Enum casts work.
* `resolved_policy` cannot remain `inherit`.
* Relationships work.
* History relationships work.
* Snapshot fields remain bounded.
* Sensitive free-text risk details are not stored.

## 31.2 Recommendation tests

Verify:

* No active profile produces no recommendation.
* Normal produces no recommendation.
* Watchlist requires finance review without forcing a policy.
* High risk recommends `pay_before_service`.
* Blocked credit recommends `pay_before_service`.
* Suspended, cleared, and expired profiles do not produce active recommendations.
* Under-review status requires finance review.
* All risk rules remain unapproved for operational resolution.

## 31.3 Materialisation tests

Verify:

* Outpatient materialises baseline policy correctly.
* Inpatient materialises running-bill baseline.
* Emergency materialises emergency policy correctly.
* Visit-type inheritance resolves to global default.
* Compatible visit-wide override context is snapshotted correctly.
* Narrow overrides are not broadened.
* Risk recommendation remains separate from resolved policy.
* Materialisation is idempotent.
* Material changes append history.
* Unchanged materialisation does not duplicate history or logs.
* Resolution version is stored.

## 31.4 Visit-creation integration tests

Verify:

* Direct outpatient visit creation materialises a policy.
* Appointment-generated visit materialises a policy.
* Emergency visit creation materialises a policy.
* Admission/inpatient visit creation materialises a policy.
* Failed observational materialisation follows the documented safety behaviour.
* No duplicate policy is created through repeated creation hooks.

## 31.5 Snapshot tests

Verify:

* Risk profile ID, level, status, reason code, and observed timestamp are captured.
* Free-text reason details are not captured.
* Later risk-profile updates do not silently change the visit snapshot.
* Stale snapshots are detected.
* Explicit refresh updates the snapshot and appends history.
* Refresh does not modify the patient’s risk profile.

## 31.6 Permission and confidentiality tests

Verify:

* Authorised finance users can view the visit policy.
* Unauthorised users do not receive it in page source, JSON, or props.
* History requires its own permission.
* Report requires report permission.
* Refresh requires refresh permission.
* Clinical users do not receive detailed risk snapshots.
* Patient masking remains intact.

## 31.7 Backfill tests

Verify:

* Dry-run performs no writes.
* Commit creates missing records.
* Existing records are preserved in missing-only mode.
* Limit and filters work.
* Repeated execution is idempotent.
* No invoice, payment, override, or visit-state mutation occurs.
* JSON output is valid.

## 31.8 Audit command tests

Verify:

* Missing records are detected.
* Stale snapshots are detected.
* Invalid recommendation/source combinations are detected.
* Resolved `inherit` is detected.
* Sensitive data is not emitted.
* Command performs no writes.
* Findings do not fail the exit code.

## 31.9 Activity and history tests

Verify:

* Initial materialisation creates one history entry.
* Initial materialisation creates one bounded activity event where required.
* Refresh creates one history entry.
* Unchanged refresh creates neither history nor activity log.
* Reads create no logs.
* History remains immutable.

## 31.10 Non-enforcement regression tests

Explicitly verify:

* High-risk recommendation does not change `VisitPaymentTimingResolver`.
* Blocked-credit recommendation does not change `BillingPolicyService`.
* Materialised policy does not change `PaymentGateService`.
* Materialised `pay_after_all_services` does not release pharmacy dispensing.
* Materialised `pay_before_service` does not introduce a new gate.
* Triage outcomes remain unchanged.
* Consultation readiness remains unchanged.
* Laboratory outcomes remain unchanged.
* Pharmacy outcomes remain unchanged.
* Previous-balance outcomes remain unchanged.
* Emergency and inpatient outcomes remain unchanged.
* No payment-gate operation eligibility changes.
* No visit billing override is created.

## 31.11 Localisation tests

Verify English/French parity.

---

# 32. Targeted Verification

Run focused checks only.

Suggested commands:

```bash
php artisan migrate
php artisan route:list
php artisan config:clear
php artisan view:clear
php artisan view:cache

php artisan test --filter=VisitPaymentPolicyModel
php artisan test --filter=PatientRiskPaymentRecommendation
php artisan test --filter=VisitPaymentPolicyMaterialization
php artisan test --filter=VisitPaymentPolicyCreation
php artisan test --filter=VisitPaymentPolicySnapshot
php artisan test --filter=VisitPaymentPolicyPermission
php artisan test --filter=VisitPaymentPolicyBackfill
php artisan test --filter=VisitPaymentPolicyAudit
php artisan test --filter=VisitPaymentPolicyNonEnforcement
```

Rerun existing focused regressions:

```bash
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
php artisan billing:visit-payment-policy-backfill --active-only --dry-run
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

# 33. Documentation

Create:

```text
docs/billing/PAYMENT_TIMING_POLICY_PHASE_6_REPORT.md
```

The report must include:

1. Existing visit-creation architecture audited
2. Materialisation integration point
3. Data-model decision
4. Files created
5. Files modified
6. Visit-policy record fields
7. History design
8. Risk recommendation rules
9. Risk-rule approval configuration
10. Baseline versus recommendation separation
11. Risk snapshot design
12. Snapshot staleness rules
13. Materialisation and refresh behaviour
14. Visit-creation coverage
15. Existing-visit backfill behaviour
16. Permissions and restricted visibility
17. Visit-level UI and worklist
18. Activity-log behaviour
19. Diagnostic command results
20. Query and performance impact
21. Focused tests and results
22. Confirmation that legacy payment decisions remain authoritative
23. Confirmation that risk recommendations remain non-operational
24. Deferred requirements for Phase 7

Do not claim tests passed unless they were actually executed.

---

# 34. Guardrails

Do not:

* Make risk profiles operational in payment resolution
* Add patient-risk precedence to `VisitPaymentTimingResolver`
* Replace the baseline resolved policy with the recommendation
* Create a typed enforcement mode
* Modify `BillingPolicyService` outcomes
* Modify `PaymentGateService` outcomes
* Wire any currently unwired payment operation
* Change operation eligibility
* Add manual per-visit policy selection
* Add per-visit override requests
* Add approval workflows
* Add self-approval
* Force a risk patient to prepay
* Allow a patient to pay after services through this record
* Create visit billing overrides
* Broaden existing override scopes
* Change laboratory compatibility
* Change pharmacy paid-only behaviour
* Change emergency behaviour
* Change inpatient behaviour
* Change previous-balance policy
* Change invoice calculations
* Change receivables
* Change allocations
* Change GL posting
* Change visit completion
* Change discharge
* Change financial closure
* Automatically refresh old visit snapshots
* Store free-text financial-risk details in visit snapshots
* Expose risk snapshots to ordinary clinical users
* Run broad test suites

---

# 35. Acceptance Criteria

Phase 6 is complete only when:

* A dedicated visit payment-policy record exists.
* Each visit can have only one current materialised record.
* Visit payment-policy history is append-only.
* The baseline typed policy is stored separately from the risk recommendation.
* Final stored baseline policies never contain `inherit`.
* Risk recommendations remain non-operational.
* High-risk and blocked-credit profiles may recommend prepayment without enforcing it.
* Watchlist profiles may require finance review without enforcing a policy.
* Risk rules remain unapproved for operational resolution.
* New visits materialise policy context through a central creation path.
* Existing visits can be backfilled safely.
* Backfill is dry-run capable and idempotent.
* Risk snapshots are point-in-time and confidentiality-safe.
* Existing visit snapshots are not silently rewritten.
* Snapshot staleness can be diagnosed.
* Explicit refresh appends history.
* Material mutations create bounded audit records.
* Routine reads create no activity logs.
* Permissions and backend privacy filtering are enforced.
* Finance users have a restricted visit-policy view and worklist.
* No approval or override action exists yet.
* `VisitPaymentTimingResolver` remains risk-unaware.
* `BillingPolicyService` remains unchanged operationally.
* `PaymentGateService` remains unchanged operationally.
* No new payment blocking or release behaviour is introduced.
* Existing laboratory, pharmacy, emergency, inpatient, triage, consultation, and previous-balance behaviour remains unchanged.
* English/French localisation is complete.
* Focused tests pass.
* The Phase 6 report accurately documents implementation and verification.

Proceed with **Payment Timing Policy Phase 6 only**.

After implementation, provide:

1. A concise implementation summary
2. Files created and modified
3. Visit-policy data model and history design
4. Materialisation integration point
5. Risk recommendation rules
6. Baseline-versus-recommendation separation
7. Snapshot and staleness behaviour
8. Existing-visit backfill results
9. Permission and privacy behaviour
10. UI and worklist details
11. Diagnostic command results
12. Query and performance impact
13. Focused test results
14. Confirmation that payment behaviour remains unchanged
15. The Phase 6 report path
16. Recommended requirements for Phase 7

Then stop after Phase 6.
