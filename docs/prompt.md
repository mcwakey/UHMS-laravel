# UHMS Implementation Prompt — Payment Timing Policy Phase 5

## Patient Financial-Risk Profiles, Restricted Visibility, Review Workflow, History, and Audit

You are working on **UHMS**, a Laravel-based hospital management system.

Implement **Phase 5 of the Configurable Payment Timing and Per-Visit Payment Policy system**.

Phases 1–4 are complete.

---

# 1. Existing Foundation

## Phase 1 — Payment-timing configuration

Implemented:

* `VisitPaymentTimingPolicy`
* `VisitPaymentPolicySource`
* Global and visit-type payment-timing settings
* `PaymentTimingConfigurationService`
* Admin configuration
* Permission protection
* English/French localisation
* Transactional updates and activity logging

## Phase 2 — Legacy integration and observation

Implemented:

* Legacy/observe integration modes
* `VisitPaymentTimingDecision`
* Limited typed resolver
* Legacy compatibility mapping
* Decision comparison
* Bounded mismatch diagnostics
* `billing:payment-timing-audit`

Legacy payment decisions remain authoritative.

## Phase 3 — Central payment façade

Implemented:

* `PaymentGateStage`
* `PaymentGateContext`
* Production payment checks centralised through `PaymentGateService`
* Maintained operation registry
* Stage-aware observation
* Payment-gate coverage command

Four production operations are currently wired:

```text
consultation.route.complete
consultation.next_patient.readiness
laboratory.result.enter
pharmacy.item.dispense
```

Nine operations remain intentionally unwired.

## Phase 4 — Departmental enforcement-policy foundation

Implemented:

* `PaymentGateOperationMode`
* `MissingBillingContextPolicy`
* `PaymentGateVisitContextRule`
* `PaymentGateOverrideScopeRule`
* `PaymentGateOperationPolicy`
* `PaymentGateEnforcementEligibility`
* Operation configuration, eligibility, and compatibility services
* Admin operation-policy interface
* Idempotent settings seeding
* Coverage and policy-audit commands

All operation configuration remains non-operational.

No typed enforcement mode exists.

No current production payment outcome is controlled by the Phase 4 operation settings.

---

# 2. Phase 5 Goal

Create a secure and auditable **patient financial-risk profile subsystem** that allows authorised finance and management users to:

* Classify a patient’s financial-risk level
* Record the reason for the classification
* Add structured supporting context
* Define an effective date
* Define an optional review or expiry date
* Suspend, expire, clear, or reactivate a risk profile
* Review the complete change history
* Restrict sensitive financial-risk information from unrelated users
* Search and report on active risk profiles
* Audit every material mutation

This phase must establish patient-level financial-risk data only.

It must **not**:

* Force a visit to pay before service
* Change a visit payment-timing decision
* Modify `VisitPaymentTimingResolver`
* Modify `PaymentGateService` operational outcomes
* Change existing previous-balance behaviour
* Automatically create billing overrides
* Automatically deny deferred settlement
* Automatically classify patients from outstanding balances
* Persist visit-level risk snapshots
* Add risk-based visit-policy overrides

Risk-based policy resolution will be implemented in a later phase.

---

# 3. Core Domain Principle

Financial risk is not the same as:

```text
invoice unpaid
previous visit balance
active receivable
insurance pending
payment gate blocked
credit approved
billing override
patient medically high risk
```

Patient financial risk is a controlled administrative classification representing the hospital’s willingness to extend payment flexibility to the patient.

Do not reuse clinical risk fields.

Do not overload patient status, invoice status, payment status, or visit status.

---

# 4. Mandatory Existing-Architecture Audit

Before modifying code, inspect:

## Patient architecture

* `Patient` model
* Patient profile controllers and services
* Patient administration pages
* Patient search and list pages
* Existing patient tabs or profile sections
* Patient merge behaviour
* Patient privacy and field-authorisation services
* Sensitive-field masking conventions
* Existing patient history/audit displays

## Billing architecture

* `PatientOutstandingBalanceService`
* `PreviousBalanceOverrideService`
* `VisitBillingOverrideService`
* `InvoiceReceivable`
* `InvoiceReceivableService`
* Existing credit, sponsor, corporate, and insurance models
* Existing balance reports
* Existing patient statements

## Security architecture

* Permission registration
* Policies and gates
* Role seeding
* Existing sensitive finance permissions
* Existing `PatientFieldAuthorizationService`
* Existing `PatientPrivacyService`
* Existing audit-log conventions
* Existing export permissions

## Settings and enums

* Existing enum conventions
* Existing reason-code conventions
* Existing status-history patterns
* Existing model event patterns
* Existing active/expired scopes

Reuse existing architecture wherever possible.

Do not create a separate patient-profile framework or duplicate the privacy subsystem.

Document the findings in the Phase 5 report.

---

# 5. Financial-Risk Level Enum

Create:

```text
app/Enums/PatientFinancialRiskLevel.php
```

Required values:

```php
<?php

namespace App\Enums;

enum PatientFinancialRiskLevel: string
{
    case NORMAL = 'normal';
    case WATCHLIST = 'watchlist';
    case HIGH_RISK = 'high_risk';
    case BLOCKED_CREDIT = 'blocked_credit';
}
```

Meaning:

## `normal`

No active financial restriction is recorded.

This should normally be represented by the absence of an active restrictive profile or by a formally cleared profile according to the final data model.

## `watchlist`

The patient requires additional financial review but is not automatically denied payment flexibility.

## `high_risk`

The patient presents a material financial exposure requiring controlled approval before extending credit or deferred payment in a future phase.

## `blocked_credit`

The hospital has formally restricted new credit or deferred-settlement arrangements, subject to authorised override in a future phase.

Rules:

* Use localisation for labels and descriptions.
* Do not hardcode labels in views.
* Do not create numeric severity assumptions unless explicitly defined.
* Add helpers only where consistent with current enum conventions.
* The enum must not make payment decisions.

---

# 6. Risk Reason Enum

Create:

```text
app/Enums/PatientFinancialRiskReason.php
```

Required structured reasons:

```php
<?php

namespace App\Enums;

enum PatientFinancialRiskReason: string
{
    case PREVIOUS_UNPAID_VISITS = 'previous_unpaid_visits';
    case REPEATED_ABANDONED_INVOICES = 'repeated_abandoned_invoices';
    case CREDIT_LIMIT_EXCEEDED = 'credit_limit_exceeded';
    case INVALID_CORPORATE_GUARANTEE = 'invalid_corporate_guarantee';
    case INSURANCE_ELIGIBILITY_UNRESOLVED = 'insurance_eligibility_unresolved';
    case PAYMENT_COMMITMENT_BREACHED = 'payment_commitment_breached';
    case MANAGEMENT_DECISION = 'management_decision';
    case OTHER = 'other';
}
```

Adjust names only where existing UHMS terminology provides a better canonical equivalent.

Rules:

* `OTHER` requires explanatory text.
* `MANAGEMENT_DECISION` should normally require explanatory text or a reference.
* Structured reasons must be stored as enum values.
* Free-text details must remain supplementary.
* Do not infer a reason automatically in this phase.

---

# 7. Risk Profile Status Enum

Create:

```text
app/Enums/PatientFinancialRiskStatus.php
```

Suggested values:

```php
<?php

namespace App\Enums;

enum PatientFinancialRiskStatus: string
{
    case ACTIVE = 'active';
    case UNDER_REVIEW = 'under_review';
    case SUSPENDED = 'suspended';
    case CLEARED = 'cleared';
    case EXPIRED = 'expired';
}
```

Meaning:

## `active`

The classification is currently valid.

## `under_review`

The profile is being reviewed and remains visible, but operational behaviour must not change in this phase.

## `suspended`

The classification is temporarily inactive without being deleted.

## `cleared`

An authorised reviewer has formally removed the restriction.

## `expired`

The effective period ended.

Rules:

* Do not delete historical profiles when cleared.
* Do not use soft deletion as the normal clearance workflow.
* Preserve the difference between suspension, clearance, and expiry.
* No status should affect live payment gates in Phase 5.

---

# 8. Data Model

Use a dedicated patient financial-risk profile and immutable history.

Preferred tables:

```text
patient_financial_risk_profiles
patient_financial_risk_history
```

Adapt names to existing project conventions if required.

## 8.1 `patient_financial_risk_profiles`

Suggested fields:

```text
id
patient_id
risk_level
primary_reason
reason_details
status
credit_limit
effective_from
review_due_at
expires_at
reference
set_by
reviewed_by
reviewed_at
suspended_by
suspended_at
cleared_by
cleared_at
clearance_reason
created_at
updated_at
```

Potential additional fields are allowed only when justified by current UHMS architecture.

Rules:

* Use foreign keys where consistent with existing migrations.
* Use explicit, safely shortened foreign-key names where necessary for MySQL identifier limits.
* Monetary fields must follow the existing UHMS currency/decimal conventions.
* `credit_limit` is optional and informational in Phase 5.
* Do not enforce the credit limit against invoices or visits yet.
* Free-text fields must be length-bounded.
* Use appropriate indexes for patient, status, risk level, review date, and expiry date.

## 8.2 `patient_financial_risk_history`

Suggested fields:

```text
id
patient_financial_risk_profile_id
patient_id
event_type
old_values
new_values
reason
performed_by
performed_at
created_at
```

Use the existing JSON-casting and activity-history conventions.

History should record material transitions such as:

```text
created
updated
submitted_for_review
review_completed
suspended
reactivated
cleared
expired
```

Do not store full patient snapshots.

Do not duplicate unnecessary personal information in history JSON.

---

# 9. Single Active Restrictive Profile Rule

A patient must not have multiple conflicting active restrictive profiles.

Define the rule clearly:

* A patient may have only one current profile in `active` or `under_review` state.
* Suspended, cleared, and expired historical profiles may remain.
* Updating the current classification should normally mutate the current profile and append history, unless the existing project convention favours versioned profile records.
* Clearing and later reclassifying a patient must remain historically traceable.

Enforce this through:

* Service-layer transactions
* Database constraints where safely possible
* Row locking during mutation
* Focused concurrency tests

Do not rely only on UI validation.

---

# 10. Models and Relationships

Create:

```text
app/Models/PatientFinancialRiskProfile.php
app/Models/PatientFinancialRiskHistory.php
```

Suggested relationships:

```php
Patient::financialRiskProfiles()
Patient::activeFinancialRiskProfile()
Patient::financialRiskHistory()

PatientFinancialRiskProfile::patient()
PatientFinancialRiskProfile::setter()
PatientFinancialRiskProfile::reviewer()
PatientFinancialRiskProfile::suspender()
PatientFinancialRiskProfile::clearer()
PatientFinancialRiskProfile::history()
```

Use established user-relation naming conventions.

Add scopes such as:

```php
scopeActive()
scopeRestrictive()
scopeDueForReview()
scopeExpired()
scopeForRiskLevel()
```

Rules:

* Avoid hidden service calls inside model accessors.
* Do not make `Patient::financialRiskProfile` trigger expensive or repeated queries in lists.
* Eager-load in report and index workflows.
* Use enum casts.
* Use decimal or money casts consistent with the project.

---

# 11. Central Financial-Risk Service

Create:

```text
app/Services/Billing/PatientFinancialRiskService.php
```

or an equivalent finance-oriented namespace consistent with UHMS.

Responsibilities:

```php
public function currentFor(Patient $patient): ?PatientFinancialRiskProfile;

public function createOrClassify(
    Patient $patient,
    PatientFinancialRiskData $data,
    User $actor
): PatientFinancialRiskProfile;

public function update(
    PatientFinancialRiskProfile $profile,
    PatientFinancialRiskData $data,
    User $actor
): PatientFinancialRiskProfile;

public function submitForReview(...): PatientFinancialRiskProfile;

public function completeReview(...): PatientFinancialRiskProfile;

public function suspend(...): PatientFinancialRiskProfile;

public function reactivate(...): PatientFinancialRiskProfile;

public function clear(...): PatientFinancialRiskProfile;

public function expireDueProfiles(...): int;
```

Use dedicated DTOs or validated arrays according to existing project conventions.

Rules:

* All mutations must run inside database transactions.
* Lock the current patient profile where necessary.
* Append immutable history for every material mutation.
* Use `ActivityLog` for material mutations.
* Do not call `PaymentGateService`.
* Do not call `VisitPaymentTimingResolver`.
* Do not create billing overrides.
* Do not modify visit records.
* Do not modify invoices.
* Do not automatically create a profile from outstanding balances.

---

# 12. State-Transition Rules

Define explicit allowed transitions.

Suggested transitions:

```text
active → under_review
active → suspended
active → cleared
active → expired

under_review → active
under_review → suspended
under_review → cleared
under_review → expired

suspended → active
suspended → under_review
suspended → cleared
suspended → expired
```

Rules:

* `cleared` and `expired` are terminal for that profile.
* A new classification after clearance should create or formally reactivate according to the documented data strategy.
* Reactivation must require a reason.
* Clearance must require a clearance reason.
* Suspended profiles must not be treated as active.
* Invalid transitions must be rejected in the backend.
* No transition should affect a live visit in Phase 5.

Create a transition service or model helper only if it keeps the domain logic clear.

---

# 13. Review and Expiry Workflow

## 13.1 Review date

Allow an optional:

```text
review_due_at
```

This means the profile should be reviewed; it does not automatically clear or expire the profile.

## 13.2 Expiry date

Allow an optional:

```text
expires_at
```

When reached, the profile should transition to `expired`.

## 13.3 Scheduled command

Add a command such as:

```bash
php artisan billing:financial-risk-expire
```

The command should:

* Find active, under-review, or suspended profiles whose `expires_at` has passed
* Transition them to `expired`
* Append history
* Create appropriate activity logs
* Be idempotent
* Avoid touching already cleared or expired profiles
* Avoid changing payment gates or visits

Register it with the scheduler only if consistent with existing project scheduling conventions.

Use:

```text
withoutOverlapping
```

where appropriate.

## 13.4 Review reminder diagnostics

Optionally add a read-only command:

```bash
php artisan billing:financial-risk-review-due
```

or include due-review reporting in the admin index.

Do not create notification automation unless a suitable existing notification framework and explicit scope exist.

---

# 14. Existing Balance Context

The profile UI may show authorised users useful financial context from existing services:

```text
current patient-responsibility receivables
previous outstanding balance
oldest unpaid invoice
age of oldest debt
number of unpaid visits
```

Use existing:

```text
PatientOutstandingBalanceService
InvoiceReceivable
existing patient balance summaries
```

Rules:

* Display context only.
* Do not copy balance values into the risk profile unless explicitly captured as an immutable assessment snapshot.
* If an assessment snapshot is added, it must be clearly marked as historical and must not replace live receivable data.
* Do not automatically determine risk level.
* The user must make and confirm the classification.
* No risk profile should be created merely because a patient has an outstanding balance.

---

# 15. Permissions

Add only the permissions needed for this subsystem.

Suggested permissions:

```text
patients.financial_risk.view
patients.financial_risk.manage
patients.financial_risk.review
patients.financial_risk.clear
patients.financial_risk.history
patients.financial_risk.report
```

Evaluate whether existing finance or patient-sensitive permissions can be reused without reducing confidentiality.

Suggested intent:

## `view`

View the current restricted profile.

## `manage`

Create and update classifications.

## `review`

Submit and complete reviews, suspend, or reactivate.

## `clear`

Clear or remove an active restriction.

## `history`

View detailed mutation history.

## `report`

Access cross-patient risk reports and exports.

Rules:

* Do not grant these broadly to clinical roles.
* Existing super-admin behaviour should remain intact.
* Suggested initial role assignment may include authorised:

  * finance managers
  * billing supervisors
  * selected administrators
  * hospital management
* Ordinary clinicians should not see detailed risk reasons.
* Reception access should be deliberately limited.
* Backend controllers, services, routes, and exports must enforce permissions.

---

# 16. Restricted Visibility and Privacy

Integrate with the existing patient privacy architecture.

Financial-risk data is sensitive administrative information.

## 16.1 Full visibility

Authorised users may see:

```text
risk level
reason
reason details
credit limit
effective date
review date
expiry date
references
set/review/clear actors
history
```

## 16.2 Limited operational visibility

Where a future workflow needs a non-sensitive indicator, prepare a method that can eventually expose:

```text
financial review required
payment arrangement requires finance review
```

Do not expose detailed reasons.

In Phase 5, do not add this indicator broadly to clinical workspaces unless an existing business requirement already demands it.

## 16.3 No visibility

Unauthorised users must not receive financial-risk data in:

* Patient JSON
* Search responses
* Data tables
* Inertia props
* API resources
* Print views
* Exports
* Activity-log previews
* Browser page source
* Hidden form fields

Do not merely hide the Blade section.

Filter the data at the backend.

---

# 17. Patient Profile UI

Add a restricted **Financial Risk** section to the appropriate patient finance or administration profile.

Avoid placing it prominently in unrelated clinical tabs.

The section should show:

```text
Current status
Risk level
Primary reason
Effective date
Review due date
Expiry date
Credit limit
Reference
Set by
Last reviewed by
Last updated
```

Actions according to permission:

```text
Classify patient
Edit classification
Submit for review
Complete review
Suspend
Reactivate
Clear restriction
View history
```

## Form requirements

The classification form should include:

```text
Risk level
Primary reason
Reason details
Optional credit limit
Effective date
Optional review date
Optional expiry date
Optional reference
```

Validation messages must be localised.

For a normal patient with no current profile, show a neutral empty state rather than a warning badge.

---

# 18. Admin Financial-Risk Worklist

Add a restricted finance/admin page listing patient financial-risk profiles.

Suggested route area:

```text
admin/billing/financial-risk
```

or the closest existing billing administration namespace.

Features:

* Search by patient identifier or name using existing privacy-aware patient search
* Filter by risk level
* Filter by status
* Filter by review due
* Filter by expired
* Filter by active restriction
* Sort by risk level, effective date, review date, or expiry date
* Pagination
* Permission-safe links to patient profiles

Columns:

```text
Patient
Risk level
Status
Primary reason
Effective date
Review due
Expiry date
Credit limit
Last updated
```

Respect patient masking and privacy rules.

Do not expose detailed free-text reasons in broad list views unless necessary.

---

# 19. History Interface

Add a restricted history panel showing:

```text
date/time
event type
previous level/status
new level/status
performed by
reason
reference
```

Do not dump raw JSON directly into the UI.

Render a human-readable, localised summary.

History must be immutable through normal application workflows.

Do not provide delete or edit actions for history entries.

---

# 20. Activity Logging

Use the existing `ActivityLog` architecture.

Suggested actions:

```text
PATIENT_FINANCIAL_RISK_CREATED
PATIENT_FINANCIAL_RISK_UPDATED
PATIENT_FINANCIAL_RISK_SUBMITTED_FOR_REVIEW
PATIENT_FINANCIAL_RISK_REVIEW_COMPLETED
PATIENT_FINANCIAL_RISK_SUSPENDED
PATIENT_FINANCIAL_RISK_REACTIVATED
PATIENT_FINANCIAL_RISK_CLEARED
PATIENT_FINANCIAL_RISK_EXPIRED
```

Capture:

```text
patient identifier
profile identifier
old level/status
new level/status
structured reason
actor
timestamp
```

Do not include unnecessary patient contact information.

Do not include unrestricted free-text details in broad audit metadata where the existing logging standards discourage it.

Avoid duplicate logging from both model observers and services.

Choose one authoritative mutation-logging path.

---

# 21. Validation

Create dedicated FormRequests.

Suggested requests:

```text
StorePatientFinancialRiskRequest
UpdatePatientFinancialRiskRequest
ReviewPatientFinancialRiskRequest
SuspendPatientFinancialRiskRequest
ReactivatePatientFinancialRiskRequest
ClearPatientFinancialRiskRequest
```

Consolidate where appropriate without making one request ambiguous.

Required validation:

* Risk level must be a valid enum.
* Reason must be a valid enum.
* `OTHER` requires `reason_details`.
* Management decisions require details or a reference.
* Credit limit must be non-negative.
* Effective date must be valid.
* Review date cannot precede the effective date.
* Expiry date cannot precede the effective date.
* Clearance requires a reason.
* Suspension requires a reason.
* Reactivation requires a reason.
* Unknown fields should not be trusted.
* Invalid state transitions must be rejected.
* Unauthorised users cannot invoke endpoints directly.

---

# 22. Reporting and Export

Add a restricted read-only report for authorised users.

Metrics may include:

```text
active watchlist patients
active high-risk patients
active blocked-credit patients
profiles due for review
profiles expiring soon
profiles cleared during period
profiles created during period
```

Patient-level export should require:

```text
patients.financial_risk.report
```

Export only necessary fields.

Respect patient-sensitive export permissions already present in UHMS.

Do not include:

* Clinical details
* Insurance member numbers
* Unnecessary contact fields
* Raw audit JSON
* Internal free-text notes unless explicitly authorised

If introducing export would significantly broaden the phase, implement the query service and restricted HTML report first, and document CSV export as deferred.

---

# 23. Read-Only Diagnostic Command

Add:

```bash
php artisan billing:financial-risk-audit
```

Suggested options:

```text
--patient=
--status=
--level=
--review-due
--expired
--problems-only
--json
```

The command should detect:

```text
multiple active profiles for one patient
active profile already beyond expiry
invalid date ordering
missing actor references
missing required reason details
terminal profiles incorrectly active
profiles due for review
```

Rules:

* Read-only
* No automatic fixes
* No patient-sensitive free text in normal output
* No activity logs
* Findings should not cause a non-zero exit code unless the command itself fails

---

# 24. Seeding and Existing Data

Do not seed actual patients as financial risks in production/default seeders.

Permission seeding is allowed.

For tests and manual-testing datasets:

* Add dedicated factories
* Add test-only seeders if consistent with UHMS conventions
* Keep these separate from default launch data
* Do not classify real production patients automatically

No backfill should infer financial risk from old debt.

Existing patients should simply have no active risk profile.

---

# 25. Performance Requirements

Avoid:

* N+1 active-profile lookups in patient lists
* N+1 user lookups in history
* Repeated balance-summary calculations
* Loading full history when only current status is required
* Including risk data in every patient query

Use:

* Explicit eager loading
* Restricted query services
* Pagination
* Indexed status/date columns
* Permission checks before loading sensitive relations

The normal clinical patient workflow should incur no risk-profile query unless the data is actually required.

Add focused query assertions where stable.

---

# 26. Failure Safety

If financial-risk data cannot be loaded:

* Do not alter payment-gate behaviour.
* Do not block clinical care.
* Do not silently classify the patient.
* Show an authorised administrative error where appropriate.
* Log the technical failure through existing application logging.

If expiry processing fails:

* Do not partially transition a profile.
* Roll back the transaction.
* Leave payment behaviour unchanged.

If audit logging fails inside a mutation transaction, follow the project’s existing material-audit integrity convention.

Do not claim a mutation succeeded if required audit logging did not complete.

---

# 27. Localisation

Add full English and French localisation.

Suggested file:

```text
lang/en/patient_financial_risk.php
lang/fr/patient_financial_risk.php
```

Required concepts include:

```text
Financial Risk
Risk Level
Normal
Watchlist
High Risk
Blocked Credit
Primary Reason
Reason Details
Credit Limit
Effective Date
Review Due
Expiry Date
Reference
Active
Under Review
Suspended
Cleared
Expired
Classify Patient
Submit for Review
Complete Review
Suspend
Reactivate
Clear Restriction
View History
Review Overdue
Expires Soon
Financial review required
```

Also localise:

* Reason labels
* Event labels
* Validation messages
* Empty states
* Success and failure messages
* Report labels
* Command-facing descriptions where project conventions require them

Maintain English/French parity.

---

# 28. Tests

Do not run the full Laravel or Playwright suites during this phase.

Add focused tests.

## 28.1 Enum tests

Verify:

* All required levels exist.
* All required reasons exist.
* All statuses exist.
* Labels resolve through localisation.
* No enum makes operational payment decisions.

## 28.2 Migration and model tests

Verify:

* Profile can be created.
* Enum casts work.
* Monetary fields follow project conventions.
* Relationships work.
* Current-profile scope works.
* Due-for-review scope works.
* Expired scope works.
* History is related correctly.

## 28.3 Service tests

Verify:

* Authorised classification succeeds.
* Only one active restrictive profile exists.
* Update appends history.
* Review transitions work.
* Suspension works.
* Reactivation works.
* Clearance works.
* Expiry works.
* Invalid transitions fail.
* Transactions prevent partial updates.
* Concurrent classification does not create duplicate active profiles.

## 28.4 Permission and confidentiality tests

Verify:

* Authorised user can view the profile.
* Unauthorised user cannot view the profile.
* Unauthorised user cannot infer the profile from JSON or page source.
* Manage, review, clear, history, and report permissions are enforced separately.
* Clinical users do not receive detailed reason data.
* Patient privacy masking remains effective.

## 28.5 UI tests

Verify:

* Current profile is displayed correctly.
* Empty state displays for patients without a profile.
* Forms show correct fields.
* State-dependent actions are available only when valid.
* History is human-readable.
* Filters and pagination work.
* Wired payment-gate settings remain unrelated.

## 28.6 Audit tests

Verify:

* Creation logs one activity event.
* Update logs one event.
* Review logs one event.
* Suspension logs one event.
* Reactivation logs one event.
* Clearance logs one event.
* Expiry logs one event.
* Failed validation creates no event.
* Read-only viewing creates no activity log.
* History and ActivityLog do not conflict or duplicate incorrectly.

## 28.7 Expiry command tests

Verify:

* Due profiles expire.
* Future profiles remain unchanged.
* Cleared profiles remain unchanged.
* Repeated execution is idempotent.
* History is added once.
* Activity log is added once.
* Payment gates remain unchanged.

## 28.8 Diagnostic command tests

Verify:

* Duplicate-active-profile anomalies are reported.
* Expired active records are reported.
* Invalid date ordering is reported.
* Filters work.
* JSON output is valid.
* Command performs no writes.
* Findings do not create a failure exit code.

## 28.9 Non-enforcement regression tests

Explicitly verify:

* Creating a high-risk profile does not change `VisitPaymentTimingResolver`.
* Creating a blocked-credit profile does not change `BillingPolicyService`.
* Creating a risk profile does not change `PaymentGateService`.
* Existing triage payment outcomes remain unchanged.
* Existing consultation readiness remains unchanged.
* Existing laboratory behaviour remains unchanged.
* Existing pharmacy behaviour remains unchanged.
* Existing previous-balance behaviour remains unchanged.
* No visit policy or override is created.

## 28.10 Localisation tests

Verify English/French parity.

---

# 29. Targeted Verification

Run focused checks only.

Suggested commands:

```bash
php artisan migrate
php artisan route:list
php artisan config:clear
php artisan view:clear
php artisan view:cache

php artisan test --filter=PatientFinancialRiskEnum
php artisan test --filter=PatientFinancialRiskModel
php artisan test --filter=PatientFinancialRiskService
php artisan test --filter=PatientFinancialRiskPermission
php artisan test --filter=PatientFinancialRiskWorkflow
php artisan test --filter=PatientFinancialRiskAudit
php artisan test --filter=PatientFinancialRiskExpiry
php artisan test --filter=PatientFinancialRiskCommand
php artisan test --filter=PatientFinancialRiskNonEnforcement
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

# 30. Documentation

Create:

```text
docs/billing/PAYMENT_TIMING_POLICY_PHASE_5_REPORT.md
```

The report must include:

1. Existing patient, billing, privacy, and audit architecture reviewed
2. Data-model decision
3. Files created
4. Files modified
5. Enum vocabulary
6. Active-profile uniqueness strategy
7. State-transition rules
8. Review and expiry workflow
9. Permissions and initial role assignment
10. Privacy and restricted-visibility behaviour
11. Patient profile UI
12. Financial-risk worklist
13. History design
14. Activity-log behaviour
15. Balance-context integration
16. Reporting and export scope
17. Expiry command and scheduler behaviour
18. Diagnostic command results
19. Seeder and existing-data decisions
20. Performance and query impact
21. Focused tests and results
22. Confirmation that payment gates and visit policies remain unchanged
23. Deferred work for Phase 6

Do not claim tests passed unless they were executed successfully.

---

# 31. Guardrails

Do not:

* Modify `VisitPaymentTimingResolver` to inspect risk profiles
* Modify typed payment precedence
* Force `pay_before_service`
* Add per-visit payment-policy persistence
* Add visit risk snapshots
* Add per-visit payment overrides
* Add risk override approvals
* Modify operation eligibility
* Add typed enforcement mode
* Wire unwired payment-gate operations
* Change laboratory compatibility
* Change pharmacy compatibility
* Change emergency behaviour
* Change inpatient behaviour
* Change previous-balance policy
* Automatically classify patients from debt
* Automatically clear profiles after payment
* Automatically create profiles from failed payment
* Change invoice calculations
* Change receivable calculations
* Change payment allocation
* Change GL posting
* Change visit completion
* Change discharge
* Change financial closure
* Expose detailed risk information to ordinary clinical users
* Create activity logs for profile reads
* Run broad test suites

---

# 32. Acceptance Criteria

Phase 5 is complete only when:

* Financial-risk level, reason, and status enums exist.
* A dedicated patient financial-risk data model exists.
* Financial-risk history is immutable.
* Only one active restrictive profile can exist per patient.
* Classification is transaction-safe.
* Review, suspension, reactivation, clearance, and expiry workflows exist.
* Invalid state transitions are rejected.
* Review and expiry dates are supported.
* Due expiry can be processed idempotently.
* Permissions are granular and backend-enforced.
* Sensitive risk data is excluded from unauthorised responses.
* Patient privacy and masking remain intact.
* Authorised users have a usable patient-profile interface.
* Authorised users have a financial-risk worklist.
* History is human-readable and permission-controlled.
* Material mutations create activity logs.
* Routine reads do not create activity logs.
* Existing balance services may provide context but do not classify patients.
* No existing patient is automatically classified.
* No visit policy is created or modified.
* No payment gate reads financial-risk profiles.
* No production payment outcome changes.
* English/French localisation is complete.
* Focused tests pass.
* The Phase 5 report accurately documents implementation and verification.

Proceed with **Payment Timing Policy Phase 5 only**.

After implementation, provide:

1. A concise implementation summary
2. Files created and modified
3. Data-model and active-profile strategy
4. Risk levels, reasons, and statuses
5. State-transition workflow
6. Permission and privacy design
7. UI and worklist details
8. Audit and history behaviour
9. Expiry and diagnostic command results
10. Performance and query impact
11. Focused test results
12. Confirmation that payment behaviour remains unchanged
13. The Phase 5 report path
14. Recommended requirements for Phase 6

Then stop after Phase 5.
