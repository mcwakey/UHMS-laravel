# UHMS Pharmacy Department Workspace — Prescription Fulfilment and `/pharmacy/*` Route Architecture

Implement a dedicated **Pharmacy Department Workspace** for UHMS.

This workspace is for pharmacy staff managing prescriptions from receipt and clinical review through payment or insurance authorization, stock allocation, dispensing, counselling, collection, returns, cancellations, controlled overrides, and pharmacy reporting.

This implementation should follow the same department-aware workspace architecture already established for:

* Doctors and consultation departments
* Records
* Nursing OPD
* Emergency
* Inpatient
* Investigations

The configured department type is:

```php
DepartmentType::PHARMACY
```

The browser workspace must use:

```text
/pharmacy/*
```

The objective is that when a logged-in user’s active department has:

```php
DepartmentType::PHARMACY
```

the user should experience UHMS as a dedicated Pharmacy application with:

* A Pharmacy-specific sidebar menu
* A Pharmacy operations dashboard
* Pharmacy-specific breadcrumbs
* Pharmacy-specific route names
* Consistent `/pharmacy/*` URLs
* Prescription and dispensing worklists
* Medication-safety review
* Payment and insurance authorization awareness
* Stock and batch awareness
* Partial-dispensing support
* Patient collection and counselling workflows
* Returns, reversals, and cancellation controls
* Permission-controlled menu visibility
* Active-department and stock-location scoping
* Patient privacy and medication-safety enforcement
* Reuse of existing prescription, billing, inventory, consultation, claims, journey, and audit logic

Do not duplicate core prescription, dispensing, billing, payment, insurance, inventory, stock-ledger, medication-safety, patient, or reporting logic merely to create the Pharmacy workspace.

---

# 1. Core Functional Requirement

When the active department type is `pharmacy`, all supported browser pages used by pharmacy staff must appear under the `/pharmacy` URL prefix.

Examples:

```text
/pharmacy
/pharmacy/dashboard

/pharmacy/prescriptions
/pharmacy/prescriptions/pending
/pharmacy/prescriptions/authorized
/pharmacy/prescriptions/awaiting-payment
/pharmacy/prescriptions/clinical-review
/pharmacy/prescriptions/ready
/pharmacy/prescriptions/completed
/pharmacy/prescriptions/{prescription}

/pharmacy/dispensing
/pharmacy/dispensing/pending
/pharmacy/dispensing/in-progress
/pharmacy/dispensing/ready-for-collection
/pharmacy/dispensing/partially-dispensed
/pharmacy/dispensing/completed
/pharmacy/dispensing/{dispensing}

/pharmacy/collections
/pharmacy/returns
/pharmacy/reversals
/pharmacy/cancellations

/pharmacy/medications
/pharmacy/stock
/pharmacy/batches
/pharmacy/expiries
/pharmacy/low-stock
/pharmacy/stock-outs

/pharmacy/patients
/pharmacy/patients/{patient}

/pharmacy/handoffs
/pharmacy/reports
```

A Pharmacy user should not enter through:

```text
/pharmacy/prescriptions/{prescription}
```

and later be redirected to generic URLs such as:

```text
/prescriptions/{prescription}
/dispensing/{dispensing}
/patients/{patient}
/visits/{visit}
/products/{product}
/stock/{stockItem}
```

All browser navigation, forms, redirects, breadcrumbs, worklists, patient links, stock actions, dispensing actions, notifications, dashboard cards, and report drilldowns must preserve the Pharmacy workspace context.

---

# 2. Pharmacy Workspace Scope

The Pharmacy workspace is responsible for medication fulfilment workflows, including:

1. Receiving prescriptions
2. Reviewing prescription completeness
3. Reviewing medication safety alerts
4. Checking allergies
5. Detecting duplicate active medications
6. Reviewing unusual dose warnings
7. Reviewing missing-diagnosis warnings where configured
8. Payment or insurance authorization awareness
9. Sponsor authorization awareness
10. Stock availability checks
11. Batch selection
12. Expiry-aware allocation
13. Partial dispensing
14. Full dispensing
15. Medication labelling
16. Patient counselling
17. Marking medication ready for collection
18. Recording patient collection
19. Medication substitution where authorized
20. Quantity adjustment where authorized
21. Prescription cancellation
22. Dispensing reversal
23. Medication returns
24. Stock restoration after valid return or reversal
25. Controlled medication handling where supported
26. Inpatient medication supply
27. Emergency medication fulfilment
28. OPD prescription fulfilment
29. Pharmacy handoffs
30. Pharmacy reports and operational analytics

The Pharmacy workspace should remain distinct from:

* Drug prescribing in consultation
* Medication administration by nurses
* General Stores management
* Finance and cashier workflows
* Procurement
* Clinical consultation
* Inpatient nursing care

Shared services should be reused, but the Pharmacy workspace must provide the pharmacist’s operational view.

---

# Phase 1 — Inspect the Existing Pharmacy Architecture

Before implementing, inspect the current codebase and identify:

1. How department-specific menus are selected.
2. How current department workspaces are registered.
3. How active department context is resolved.
4. How multi-department users switch active departments.
5. Existing routes, controllers, models, services, policies, jobs, commands, and views for:

   * prescriptions
   * prescription items
   * medication products
   * dispensing
   * dispensing items
   * partial dispensing
   * medication collection
   * medication returns
   * reversals
   * cancellations
   * substitutions
   * stock allocation
   * stock reservations
   * stock movements
   * batches
   * expiry dates
   * stock locations
   * inventory
   * billing
   * payments
   * insurance
   * claims
   * sponsors
   * medication safety
   * allergies
   * duplicate active medication checks
   * unusual-dose warnings
   * diagnosis requirements
   * patient and visit links
   * reporting
6. Existing prescription statuses.
7. Existing dispensing statuses.
8. Existing stock-reservation behaviour.
9. Existing FEFO, FIFO, or batch-selection policy.
10. Existing return and reversal rules.
11. Existing payment-gate operations for Pharmacy.
12. Existing emergency and inpatient exceptions.
13. Existing Journey Intelligence stages for Pharmacy.
14. Existing patient privacy protections.
15. Existing audit-event infrastructure.
16. Shared views that hardcode generic routes such as:

```php
route('prescriptions.show', $prescription)
route('dispensing.show', $dispensing)
route('patients.show', $patient)
route('visits.show', $visit)
route('products.show', $product)
```

Do not create a competing prescription, dispensing, stock, menu, or routing framework where one already exists.

Extend the existing:

* Department dashboard registry
* Department menu profile service
* Active department context
* Workspace route resolver
* Workspace redirect resolver
* Prescription services
* Prescription-safety services
* Dispensing services
* Stock-location resolver
* Stock-allocation services
* Billing and payment services
* Insurance and claims services
* Journey Intelligence services
* Authorization policies
* Patient privacy services
* Activity logging

---

# Phase 2 — Pharmacy Workspace Route Group

Create a dedicated Pharmacy route group.

Use a structure equivalent to:

```php
Route::prefix('pharmacy')
    ->name('pharmacy.')
    ->middleware([
        'auth',
        'verified',
        'department.context',
        'department.type:pharmacy',
    ])
    ->group(function () {
        // Pharmacy workspace routes
    });
```

Use the project’s actual middleware names and active-department authorization architecture.

Required route names should include, where corresponding functionality already exists:

```text
pharmacy.dashboard

pharmacy.prescriptions.index
pharmacy.prescriptions.pending
pharmacy.prescriptions.awaiting_payment
pharmacy.prescriptions.authorized
pharmacy.prescriptions.clinical_review
pharmacy.prescriptions.ready
pharmacy.prescriptions.completed
pharmacy.prescriptions.cancelled
pharmacy.prescriptions.show
pharmacy.prescriptions.accept
pharmacy.prescriptions.review
pharmacy.prescriptions.cancel

pharmacy.dispensing.index
pharmacy.dispensing.pending
pharmacy.dispensing.in_progress
pharmacy.dispensing.partially_dispensed
pharmacy.dispensing.ready_for_collection
pharmacy.dispensing.completed
pharmacy.dispensing.show
pharmacy.dispensing.start
pharmacy.dispensing.store
pharmacy.dispensing.complete

pharmacy.collections.index
pharmacy.collections.show
pharmacy.collections.confirm

pharmacy.returns.index
pharmacy.returns.create
pharmacy.returns.store
pharmacy.returns.show

pharmacy.reversals.index
pharmacy.reversals.create
pharmacy.reversals.store

pharmacy.cancellations.index

pharmacy.medications.index
pharmacy.medications.show

pharmacy.stock.index
pharmacy.stock.show
pharmacy.stock.low
pharmacy.stock.out
pharmacy.stock.expiring

pharmacy.batches.index
pharmacy.batches.show

pharmacy.patients.index
pharmacy.patients.show

pharmacy.handoffs.index
pharmacy.reports.index
```

Only register routes for functionality that genuinely exists or is being implemented.

Do not create empty placeholder pages merely to populate the menu.

---

# Phase 3 — Pharmacy Operations Dashboard

Create or complete a dedicated Pharmacy dashboard.

The canonical route should be:

```text
/pharmacy
```

or:

```text
/pharmacy/dashboard
```

Choose one canonical route and redirect the other to it.

The department dashboard resolver should map:

```php
DepartmentType::PHARMACY => 'pharmacy.dashboard'
```

The dashboard should function as a Pharmacy operations command board.

Recommended metrics and widgets include, where reliable data exists:

* Prescriptions received today
* Prescriptions awaiting payment
* Prescriptions awaiting insurance authorization
* Prescriptions requiring clinical review
* Prescriptions with safety warnings
* Prescriptions ready for dispensing
* Dispensing in progress
* Partially dispensed prescriptions
* Ready for collection
* Completed dispensings today
* Emergency prescriptions
* Inpatient medication requests
* Overdue prescription fulfilments
* Prescriptions blocked by stock
* Prescriptions blocked by payment
* Prescriptions blocked by clinical safety
* Low-stock medications
* Out-of-stock medications
* Expiring batches
* Expired-stock alerts
* Pending returns
* Pending reversals
* Pending handoffs
* Average prescription-to-dispensing time
* Average ready-to-collection time

Each dashboard metric must:

* Respect permissions
* Respect the active department
* Respect stock-location scoping
* Include only prescriptions fulfilable by the active Pharmacy department
* Avoid leaking protected patient information
* Link to valid `/pharmacy/*` routes
* Use safe empty states
* Avoid expensive unbounded queries
* Reuse Journey Intelligence and department metrics where applicable

Do not introduce metrics that cannot be calculated reliably.

---

# Phase 4 — Pharmacy-Specific Menu Profile

Extend the existing department menu profile or menu registry so that:

```php
DepartmentType::PHARMACY
```

receives a dedicated Pharmacy menu.

Recommended menu structure:

## Pharmacy Command

* Dashboard
* All Prescriptions
* Emergency Prescriptions
* Inpatient Requests
* Overdue Prescriptions

## Prescription Review

* Pending Prescriptions
* Awaiting Payment
* Awaiting Insurance Authorization
* Clinical Review Required
* Safety Warnings
* Authorized Prescriptions
* Cancelled Prescriptions

## Dispensing

* Ready to Dispense
* Dispensing in Progress
* Partially Dispensed
* Ready for Collection
* Completed Dispensing

## Patient Collection

* Collection Queue
* Collected Today
* Uncollected Medications
* Counselling Pending

## Returns and Corrections

* Medication Returns
* Dispensing Reversals
* Prescription Cancellations
* Substitution Review

## Stock Awareness

* Medication Stock
* Low Stock
* Out of Stock
* Expiring Batches
* Expired Batches
* Batch Availability

## Coordination

* Pharmacy Handoffs
* Critical Alerts
* Escalations
* Pending Clinician Clarification
* Pending Finance Clarification

## Patient Access

* Patient Search
* Patient Profiles
* Medication History
* Prescription History
* Visit History

## Pharmacy Reports

* Prescription Volume Report
* Dispensing Report
* Partial Dispensing Report
* Medication Collection Report
* Medication Return Report
* Stockout Report
* Low-Stock Report
* Expiry Report
* Pharmacist Activity Report
* Prescription Turnaround-Time Report
* Medication Utilization Report
* Insurance Prescription Report
* Emergency Medication Report

## General

* Notifications
* My Profile
* Switch Department

Only show a menu item when:

1. The feature exists.
2. The required module is enabled.
3. The user possesses the required permission.
4. The active department permits access.
5. The feature belongs to Pharmacy operations.

Permissions remain authoritative.

Do not expose menu items solely because the active department type is `pharmacy`.

---

# Phase 5 — Prescription Worklists

Create or adapt prescription worklists under:

```text
/pharmacy/prescriptions
```

Recommended worklist states include:

```text
new
awaiting_payment
awaiting_insurance
awaiting_sponsor_authorization
authorized
clinical_review_required
safety_warning
accepted
ready_to_dispense
dispensing_in_progress
partially_dispensed
ready_for_collection
completed
cancelled
expired
overdue
```

Use existing prescription, payment, insurance, stock, dispensing, and journey statuses.

Do not introduce duplicate statuses where the worklist state can be derived by a resolver.

Each prescription row should display only authorized information, such as:

* Patient identifier
* Patient name according to privacy rules
* Visit number
* Prescription number
* Prescribing clinician
* Prescribing department
* Prescription date and time
* Priority
* Number of medication items
* Payment or insurance state
* Clinical-review state
* Safety-warning state
* Stock-availability state
* Dispensing state
* Assigned pharmacist
* Waiting duration
* SLA state
* Next required action

Support filters such as:

* Date
* Priority
* Prescribing department
* Prescribing clinician
* Patient category
* Prescription status
* Payment state
* Insurance state
* Safety-warning state
* Stock state
* Assigned pharmacist
* Emergency or routine
* Inpatient or outpatient
* SLA state

Use pagination and efficient queries.

---

# Phase 6 — Prescription Workspace

Create or adapt a dedicated prescription workspace.

Recommended route:

```text
/pharmacy/prescriptions/{prescription}
```

The prescription workspace should coordinate the complete fulfilment lifecycle.

Recommended sections:

1. Patient identity strip
2. Visit details
3. Prescription details
4. Prescribing clinician and department
5. Diagnosis context where authorized
6. Allergy information
7. Active medication warnings
8. Medication-safety warnings
9. Prescription items
10. Dose, route, frequency, and duration
11. Payment and insurance authorization
12. Stock availability
13. Batch availability
14. Dispensing history
15. Partial-dispensing history
16. Collection state
17. Counselling state
18. Return or reversal history
19. Clinical clarification history
20. Billing references where authorized
21. Activity timeline
22. Authorized quick actions

Do not duplicate underlying prescription, dispensing, billing, stock, or safety implementations.

The page should provide a Pharmacy-specific coordinated view of existing services.

---

# Phase 7 — Prescription Acceptance and Clinical Review

Before dispensing, the Pharmacy workflow should confirm that the prescription is appropriate for processing.

The review may validate:

* Patient identity
* Prescription ownership
* Prescribing clinician
* Medication
* Dose
* Route
* Frequency
* Duration
* Quantity
* Diagnosis context where required
* Allergy conflicts
* Duplicate active medications
* Drug interactions where supported
* Unusual-dose warnings
* Age-related warnings
* Weight-related warnings where supported
* Pregnancy-related warnings where supported
* Missing required information
* Prescription expiry
* Cancellation state

Reuse existing prescription-safety services.

Do not recreate safety checks in Pharmacy controllers.

Potential review states may include:

```text
not_reviewed
review_required
clarification_required
reviewed
approved
rejected
overridden
```

Any clinical-safety override must be:

* Explicit
* Permission-controlled
* Reasoned
* Linked to the specific warning
* Audited

Pharmacy staff should not silently alter the clinical prescription.

Where a change is required, use:

* Clinician clarification
* Authorized substitution
* Authorized quantity adjustment
* Prescription amendment workflow

according to existing policy.

---

# Phase 8 — Medication-Safety Integration

Preserve and expose the existing medication-safety checks, including:

* Allergy conflicts
* Duplicate active medication
* Missing-diagnosis policy
* Unusual-dose warnings
* Contraindication warnings where supported
* Drug-interaction warnings where supported
* Duplicate therapeutic-class warnings where supported
* Pediatric or geriatric warnings where supported
* Pregnancy or breastfeeding warnings where supported

The Pharmacy workspace should distinguish:

```text
safe
warning
requires_review
blocked
overridden
```

Warnings should display:

* Warning type
* Medication
* Clinical context
* Severity
* Recommended action
* Override eligibility
* Override status
* Responsible reviewer

Do not expose more diagnosis or clinical history than the pharmacist is authorized to view.

Safety warnings must remain visible until resolved, accepted, or overridden according to policy.

---

# Phase 9 — Payment, Insurance, and Authorization Awareness

Prescription fulfilment may depend on:

* Cash payment
* Insurance coverage
* Sponsor authorization
* Departmental payment deferral
* Emergency override
* Inpatient payment policy
* Visit-specific billing override

Display safe operational states such as:

```text
authorized_to_dispense
payment_required
payment_pending
insurance_pending
insurance_approved
insurance_partially_covered
sponsor_pending
sponsor_approved
emergency_override
authorized_override
billing_context_missing
```

Reuse existing:

* Payment-gate operation policy
* Visit billing overrides
* Insurance coverage calculation
* Sponsor authorization
* Invoice and receivable services
* Emergency exception rules
* Admission billing
* Activity logging

Do not expose unnecessary financial details without finance permissions.

Do not hardcode a universal Pharmacy payment bypass.

Use operation-specific policy for:

* Accepting prescription
* Reserving stock
* Starting dispensing
* Completing dispensing
* Releasing medication for collection

Any override must be:

* Explicit
* Permission-controlled
* Reasoned
* Visit-scoped or prescription-scoped
* Audited

Emergency and inpatient medication workflows may proceed under their configured policies without silently bypassing billing.

---

# Phase 10 — Stock Location and Department Scoping

All Pharmacy worklists and stock checks must respect:

* Active department
* Pharmacy stock location
* User assignment
* Medication-to-stock-location availability
* Authorized cross-location access

Where multiple Pharmacy departments exist, such as:

* Main Pharmacy
* OPD Pharmacy
* Inpatient Pharmacy
* Emergency Pharmacy
* Satellite Pharmacy

a user should only see prescriptions and stock relevant to their active department unless granted cross-department permission.

Reuse the existing `StockLocationResolver`.

Ensure:

```php
DepartmentType::PHARMACY
```

resolves to the correct Pharmacy stock location or configured location mapping.

Do not expose every hospital stock item merely because a user belongs to a Pharmacy department.

---

# Phase 11 — Stock Availability and Reservation

Before dispensing, the system should determine:

* Available quantity
* Reserved quantity
* Dispensed quantity
* Remaining quantity
* Batch availability
* Expiry state
* Stock-location availability
* Alternative product availability where authorized

Reuse existing stock and reservation services.

The system should support:

```text
fully_available
partially_available
out_of_stock
alternative_available
reservation_pending
reserved
```

Stock reservation must:

1. Be tied to the prescription item.
2. Be tied to the active stock location.
3. Prevent double allocation.
4. Respect batch and expiry policy.
5. Release unused reservations after cancellation or expiry.
6. Be audited where required.
7. Be transactionally safe.

Do not create a second inventory ledger for Pharmacy.

---

# Phase 12 — Batch and Expiry Selection

Dispensing should use the configured batch-selection policy.

Prefer the existing policy, such as:

* FEFO — first expiry, first out
* FIFO — first in, first out
* Explicit pharmacist selection where required

Batch selection must consider:

* Available quantity
* Expiry date
* Quarantine state
* Recall state
* Stock-location state
* Product compatibility
* Lot or batch restrictions

Do not allow:

* Expired batches
* Quarantined batches
* Recalled batches
* Inactive batches
* Zero-availability batches

to be dispensed unless an explicit exceptional workflow exists.

Batch selection and quantity deduction must be transactional.

---

# Phase 13 — Dispensing Workflow

Expose dispensing under:

```text
/pharmacy/dispensing
```

Recommended dispensing states include:

```text
not_started
in_progress
partially_dispensed
fully_dispensed
ready_for_collection
collected
cancelled
reversed
```

Authorized Pharmacy users should be able to:

* Start dispensing
* Select stock batches
* Confirm dispensed quantity
* Record partial dispensing
* Record unavailable quantity
* Record substitution where authorized
* Generate or confirm medication labels
* Record counselling instructions
* Mark medication ready for collection
* Complete dispensing

Dispensing must not:

* Exceed the prescribed quantity
* Exceed available stock
* Bypass unresolved blocking safety warnings
* Bypass required payment or authorization policy
* Modify the prescription silently
* Create duplicate stock deductions
* Mark uncollected medication as collected

All dispensing actions must remain under `/pharmacy/*`.

---

# Phase 14 — Partial Dispensing

Support partial dispensing where only part of the prescription quantity is available or authorized.

A partial dispensing record should preserve:

* Prescribed quantity
* Previously dispensed quantity
* Quantity dispensed now
* Remaining quantity
* Reason
* Batch allocation
* Responsible pharmacist
* Date and time
* Payment and insurance state
* Follow-up requirement

Potential reasons include:

```text
insufficient_stock
insurance_limit
patient_request
clinical_adjustment
package_size
authorized_split
other
```

The system should:

1. Preserve the original prescribed quantity.
2. Preserve each dispensing event.
3. Maintain the outstanding quantity.
4. Prevent over-dispensing.
5. Keep the prescription visible in the partial-dispensing worklist.
6. Allow later completion where valid.
7. Update billing and stock correctly.
8. Audit each dispensing event.

Do not mark the prescription fully completed while outstanding medication remains unless the remainder is formally cancelled or waived.

---

# Phase 15 — Medication Substitution

Where substitution is permitted, use a formal workflow.

Potential substitution types include:

* Generic equivalent
* Brand equivalent
* Strength substitution with quantity adjustment
* Formulation substitution
* Therapeutic alternative where explicit clinical approval exists

A substitution should record:

* Original medication
* Replacement medication
* Reason
* Equivalence basis
* Quantity adjustment
* Approving pharmacist
* Prescriber approval where required
* Patient notification
* Billing difference
* Stock allocation
* Date and time

Do not allow arbitrary substitution through editing the original prescription item.

Substitution must respect:

* Permission
* Formulary policy
* Insurance coverage
* Safety checks
* Prescriber-approval policy
* Audit requirements

---

# Phase 16 — Medication Labels and Counselling

Where label generation exists, expose it within `/pharmacy/*`.

Labels may include:

* Patient identifier
* Medication name
* Dose
* Route
* Frequency
* Duration
* Quantity
* Administration instructions
* Warning instructions
* Storage instructions
* Dispensing date
* Pharmacy
* Responsible pharmacist

Do not expose unnecessary sensitive information on labels.

The counselling workflow may record:

* Instructions explained
* Side effects explained
* Storage explained
* Adherence guidance
* Device-use guidance
* Patient questions
* Counselling completed by
* Counselling date and time

Counselling requirements may vary by medication or patient type.

Do not mark counselling complete automatically where acknowledgement is required.

---

# Phase 17 — Ready for Collection and Collection Workflow

Expose medication collection under:

```text
/pharmacy/collections
```

The collection queue should show:

* Patient identifier
* Prescription number
* Medication count
* Ready date and time
* Waiting duration
* Counselling requirement
* Collection status
* Authorized collector where applicable

Collection confirmation should record:

* Collector identity
* Relationship to patient where applicable
* Collection date and time
* Responsible Pharmacy user
* Counselling completion
* Signature or acknowledgement where supported
* Notes

Potential collection states include:

```text
not_ready
ready
partially_ready
collected
partially_collected
uncollected
returned_to_stock
```

Do not mark medication collected merely because dispensing is complete.

Uncollected medication should follow the configured expiry or return-to-stock policy.

---

# Phase 18 — Inpatient Pharmacy Workflow

The Pharmacy workspace must support inpatient medication supply.

Inpatient prescriptions may differ from OPD prescriptions because:

* Medication may be supplied to a ward rather than directly to the patient.
* Supply may occur in scheduled quantities.
* Administration is recorded separately by Nursing.
* Admission billing policies may apply.
* Repeat or ongoing supply may be required.
* Returned unused medication may need reconciliation.

The Pharmacy workspace should display safe inpatient context such as:

* Admission number
* Ward
* Bed
* Ordering clinician
* Medication schedule
* Quantity requested
* Quantity supplied
* Remaining quantity
* Supply cycle
* Ward receipt state

Do not treat Pharmacy dispensing as medication administration.

Medication administration remains a Nursing or authorized clinical action.

---

# Phase 19 — Emergency Pharmacy Workflow

Emergency prescriptions should remain highly visible and prioritized.

The Pharmacy workspace should show:

* Emergency priority
* Emergency department
* Emergency visit
* Stabilization requirement
* Payment override state
* Stock availability
* Required fulfilment time
* Assigned pharmacist
* Current status

Emergency medication fulfilment must use the configured emergency payment and stock-override policies.

Do not hardcode unrestricted emergency dispensing.

Any exceptional action must be:

* Operation-specific
* Permission-controlled
* Reasoned
* Audited

---

# Phase 20 — Prescription Cancellation

Provide a structured prescription-cancellation workflow.

A cancellation should record:

* Prescription or item
* Cancellation reason
* Cancelling user
* Date and time
* Whether dispensing started
* Whether stock was reserved
* Whether stock was deducted
* Whether billing was posted
* Whether refund or reversal is required
* Whether clinician notification is required

Potential cancellation reasons may include:

```text
prescriber_cancelled
duplicate_prescription
clinical_contraindication
patient_declined
medication_unavailable
insurance_denied
entered_in_error
other
```

Cancellation must:

1. Preserve the original prescription.
2. Release unused stock reservations.
3. Avoid reversing already administered medication.
4. Trigger billing correction where required.
5. Preserve audit history.
6. Avoid silently deleting dispensing records.

---

# Phase 21 — Dispensing Reversal

A completed dispensing must not be deleted directly.

Provide a formal reversal workflow.

A reversal should record:

* Dispensing record
* Prescription
* Medication item
* Quantity
* Batch
* Reason
* Responsible user
* Date and time
* Stock-restoration eligibility
* Billing-reversal requirement
* Collection state
* Patient possession state

Stock should only be restored when:

* The medication was not collected, or
* A valid return was accepted under policy, and
* The product remains suitable for return to stock.

Do not restore returned medication to saleable stock automatically where storage conditions or tamper state cannot be verified.

Reversals must be transactionally safe and audited.

---

# Phase 22 — Medication Returns

Expose returns under:

```text
/pharmacy/returns
```

A medication return may record:

* Patient
* Prescription
* Dispensing
* Medication
* Batch
* Quantity
* Return reason
* Return date
* Packaging state
* Seal or tamper state
* Storage-condition confidence
* Expiry state
* Stock-restoration decision
* Refund or billing action
* Responsible Pharmacy user

Potential return outcomes include:

```text
accepted_return_to_stock
accepted_quarantine
accepted_for_destruction
rejected
billing_only_adjustment
```

Do not assume every returned medication can be returned to available stock.

Use the existing stock, quarantine, destruction, billing, and audit services where available.

---

# Phase 23 — Stock Alerts

Expose stock-awareness pages under:

```text
/pharmacy/stock
/pharmacy/low-stock
/pharmacy/stock-outs
/pharmacy/expiries
```

The workspace may display:

* Medication
* Product code
* Stock location
* Available quantity
* Reserved quantity
* Reorder level
* Stockout state
* Earliest expiry
* Expiring quantity
* Batch count
* Last stock movement

Stock alerts should support:

* Low stock
* Out of stock
* Near expiry
* Expired
* Quarantined
* Recalled
* Negative-stock anomaly
* Reservation anomaly

Do not grant procurement, stock-adjustment, or transfer permissions merely because the user can view Pharmacy stock.

Stock corrections should remain under the existing inventory authorization model.

---

# Phase 24 — Pharmacy Handoffs and Clarifications

Expose Pharmacy handoffs under:

```text
/pharmacy/handoffs
```

Reuse the existing Journey Intelligence handoff infrastructure.

Support handoffs such as:

* Prescription clarification required
* Medication unavailable
* Alternative medication proposed
* Payment clarification required
* Insurance authorization pending
* Inpatient medication ready
* Emergency medication prepared
* Medication not collected
* Return or reversal requiring Finance action
* Safety warning requiring clinician response

The handoff worklist should show:

* Patient and visit context
* Prescription
* Sending department
* Receiving department
* Required action
* Priority
* Due time
* SLA state
* Acknowledgement
* Resolution

Authorized users may:

* Claim
* Assign
* Acknowledge
* Resolve
* Escalate
* Reassign

All Pharmacy-side links should preserve `/pharmacy/*` context.

---

# Phase 25 — Workspace-Aware URL Resolution

Extend the centralized workspace route resolver.

Do not scatter checks such as:

```php
if ($department->type === DepartmentType::PHARMACY) {
    return route('pharmacy.prescriptions.show', $prescription);
}
```

across controllers and Blade views.

Extend a service such as:

```php
DepartmentWorkspaceRouteResolver
WorkspaceUrlResolver
DepartmentRouteResolver
```

The resolver should support methods equivalent to:

```php
dashboard()

prescriptionIndex()
prescriptionShow(Prescription $prescription)

dispensingIndex()
dispensingShow(Dispensing $dispensing)

collectionIndex()
returnIndex()
reversalIndex()

medicationIndex()
medicationShow(Product $product)

stockIndex()
stockShow(StockItem $stockItem)
batchIndex()
batchShow(Batch $batch)

patientIndex()
patientShow(Patient $patient)

handoffIndex()
reportIndex()
```

For a Pharmacy user, the resolver must return `pharmacy.*` routes.

For other users, preserve the appropriate existing workspace or generic route.

Always use the active department context rather than only the user’s primary department.

---

# Phase 26 — Replace Hardcoded Shared Links

Audit all shared pages accessed by Pharmacy users.

Replace hardcoded generic links that break workspace continuity.

Review at minimum:

* Prescription worklists
* Prescription details
* Dispensing pages
* Collection queue
* Return pages
* Reversal pages
* Medication stock pages
* Batch pages
* Patient search
* Patient profiles
* Medication history
* Prescription history
* Visit history
* Journey worklists
* Dashboard cards
* Breadcrumbs
* Notifications
* Safety-alert links
* Handoff links
* Action dropdowns
* Empty-state actions
* Report drilldowns
* Flash-message action links

Avoid shared-view code such as:

```php
route('prescriptions.show', $prescription)
```

Use the centralized workspace route resolver.

Do not alter API, payment callback, insurance callback, signed, print, export, integration, or background-job URLs unless explicitly part of the Pharmacy browser workspace.

---

# Phase 27 — Workspace-Aware Redirects

All successful Pharmacy actions must redirect back into `/pharmacy/*`.

Examples:

After accepting a prescription:

```text
/pharmacy/prescriptions/{prescription}
```

After clinical review:

```text
/pharmacy/prescriptions/{prescription}
```

After starting dispensing:

```text
/pharmacy/dispensing/{dispensing}
```

After partial dispensing:

```text
/pharmacy/dispensing/{dispensing}
```

After marking ready for collection:

```text
/pharmacy/collections
```

After confirming collection:

```text
/pharmacy/prescriptions/{prescription}
```

After a medication return:

```text
/pharmacy/returns/{return}
```

After a reversal:

```text
/pharmacy/prescriptions/{prescription}
```

Avoid hardcoding Pharmacy redirects inside domain services.

Use a workspace redirect resolver such as:

```php
$workspaceRedirects->toPrescription($prescription);
$workspaceRedirects->toDispensing($dispensing);
$workspaceRedirects->toCollectionQueue();
$workspaceRedirects->toReturn($return);
$workspaceRedirects->toPharmacyDashboard();
```

Validation failures must return users to the same `/pharmacy/*` route with input preserved.

---

# Phase 28 — Login and Department Switching

When a user logs in and their active department type is `pharmacy`, redirect them to:

```text
/pharmacy
```

When a multi-department user switches to a Pharmacy department, redirect them to:

```text
/pharmacy
```

When switching away from Pharmacy, redirect to the selected department’s appropriate workspace.

The menu, dashboard, stock location, route context, and data scoping must always use the active department.

Do not rely only on:

```php
$user->department_id
```

where the application supports active department selection.

---

# Phase 29 — Pharmacy Workspace Authorization

The `/pharmacy` prefix is not authorization.

Protect the workspace so access requires:

1. An authenticated user.
2. A valid active department.
3. Active department type equal to `pharmacy`, unless authorized admin preview applies.
4. The required permission.
5. The relevant module being enabled.
6. Access to the requested patient, visit, prescription, dispensing, product, stock location, or batch.
7. Active-department or stock-location assignment where required.

A user from another department who manually enters:

```text
/pharmacy/prescriptions
```

must not receive access merely because they possess a broad prescription-view permission.

Use the project’s existing unauthorized workspace behaviour:

* `403`
* Workspace unavailable page
* Safe redirect

Do not create inconsistent authorization behaviour.

---

# Phase 30 — Permission Model

Reuse existing permissions wherever possible.

Only add permissions where the current model does not represent the required action.

Potential permissions may include:

```text
pharmacy.workspace.view

pharmacy.prescriptions.view
pharmacy.prescriptions.review
pharmacy.prescriptions.accept
pharmacy.prescriptions.cancel

pharmacy.safety_warnings.view
pharmacy.safety_warnings.manage
pharmacy.safety_overrides.manage

pharmacy.dispensing.view
pharmacy.dispensing.manage
pharmacy.dispensing.partial
pharmacy.dispensing.complete
pharmacy.dispensing.substitute

pharmacy.collections.view
pharmacy.collections.confirm

pharmacy.returns.view
pharmacy.returns.manage
pharmacy.reversals.view
pharmacy.reversals.manage

pharmacy.stock.view
pharmacy.batches.view
pharmacy.expiries.view

pharmacy.patients.view
pharmacy.handoffs.view
pharmacy.handoffs.manage
pharmacy.reports.view
pharmacy.billing_overrides.manage
```

Inspect current permission names before adding new permissions.

Avoid duplicating equivalent permissions.

Menu visibility must follow permissions, but controllers, policies, form requests, and services must independently enforce authorization.

---

# Phase 31 — Legacy Route Compatibility

Keep existing generic prescription, dispensing, stock, and patient routes operational for:

* Other departments
* Clinician prescription viewing
* Existing bookmarks
* APIs
* Print flows
* Signed URLs
* Internal notifications
* Background jobs
* Payment callbacks
* Insurance callbacks
* Export downloads
* Integrations

For interactive browser requests from an active Pharmacy department, generic routes may redirect to Pharmacy equivalents where safe.

Examples:

```text
/prescriptions/{prescription}
→ /pharmacy/prescriptions/{prescription}

/dispensing/{dispensing}
→ /pharmacy/dispensing/{dispensing}

/products/{product}
→ /pharmacy/medications/{product}
```

Do not blindly redirect:

* JSON requests
* APIs
* signed URLs
* payment callbacks
* insurance callbacks
* print routes
* exports
* background requests
* integration requests

Avoid redirect loops.

---

# Phase 32 — Breadcrumbs and Active Menu State

Pharmacy pages must display Pharmacy-specific breadcrumbs.

Examples:

```text
Pharmacy > Dashboard
Pharmacy > Prescriptions
Pharmacy > Prescriptions > Prescription Details
Pharmacy > Clinical Review
Pharmacy > Dispensing
Pharmacy > Dispensing > Dispensing Details
Pharmacy > Ready for Collection
Pharmacy > Returns
Pharmacy > Stock
Pharmacy > Expiring Batches
Pharmacy > Handoffs
Pharmacy > Reports
```

The sidebar must correctly highlight parent items for nested routes.

For example:

```text
pharmacy.prescriptions.show
pharmacy.dispensing.show
pharmacy.returns.show
```

should highlight the appropriate parent menu item.

Use active-route patterns rather than exact route-name equality only.

---

# Phase 33 — Shared View Workspace Context

Pass a clear Pharmacy workspace context to shared views.

The context may include:

```php
[
    'workspaceKey' => 'pharmacy',
    'workspaceDepartment' => $activeDepartment,
    'workspaceRoutePrefix' => 'pharmacy.',
    'workspaceTitle' => __('pharmacy.workspace.title'),
    'workspaceScope' => 'medication_fulfilment',
    'workspaceStockLocation' => $stockLocation,
]
```

Use an existing DTO or view-context object where available.

Shared views should use this context for:

* Page headings
* Breadcrumbs
* Links
* Form actions
* Back buttons
* Prescription navigation
* Dispensing navigation
* Stock navigation
* Quick actions
* Empty states
* Notifications

Do not repeatedly inspect session state or department type inside Blade templates.

---

# Phase 34 — Patient Privacy and Medication Security

The Pharmacy workspace handles sensitive patient, clinical, prescription, and medication information.

Ensure existing privacy controls remain active, including:

* Patient-name masking where applicable
* Protected phone and email fields
* Sensitive-field permission checks
* Access auditing
* Search-result masking
* Export restrictions
* Secure patient, visit, prescription, and dispensing lookup
* Privacy-aware notifications
* Activity-log sanitization

Pharmacy users should only see the clinical context necessary for medication review and fulfilment.

Do not expose full consultation notes or unrelated clinical history without permission.

Medication security must preserve:

* Prescription ownership
* Stock traceability
* Batch traceability
* Dispensing traceability
* Return traceability
* Reversal traceability
* User accountability
* Audit history

---

# Phase 35 — Clinical and Operational Safety

Preserve existing clinical and operational safeguards, including:

* Allergy detection
* Duplicate active medication checks
* Unusual-dose warnings
* Missing-diagnosis policy
* Prescription expiry checks
* Quantity validation
* Stock availability validation
* Batch expiry validation
* Quarantine and recall blocking
* Over-dispensing prevention
* Duplicate dispensing prevention
* Payment and insurance authorization
* Separation of dispensing and administration
* Controlled overrides
* Audit trails

Do not allow:

* Dispensing beyond prescribed quantity
* Dispensing unavailable stock
* Dispensing expired or quarantined stock
* Silent modification of prescriptions
* Unresolved blocking safety warnings to be ignored
* Direct deletion of completed dispensing records
* Direct editing of completed stock movements
* Invalid returns to restore saleable stock
* Collected medication to be reversed without appropriate review

Overrides must be explicit, permission-controlled, reasoned, and audited.

---

# Phase 36 — Activity Logging and Audit

Record relevant Pharmacy actions through the existing `ActivityLog` infrastructure.

Audit events should cover actions such as:

* Prescription accepted
* Prescription review completed
* Clinical clarification requested
* Safety warning acknowledged
* Safety override applied
* Payment or authorization override applied
* Stock reserved
* Stock reservation released
* Dispensing started
* Medication partially dispensed
* Medication fully dispensed
* Medication substituted
* Prescription marked ready for collection
* Medication collected
* Counselling completed
* Prescription cancelled
* Dispensing reversed
* Medication returned
* Stock returned to available inventory
* Stock moved to quarantine
* Return rejected
* Handoff acknowledged
* Handoff resolved

Do not log full sensitive clinical values where the audit policy prohibits them.

Audit records should include sufficient context such as:

* Actor
* Patient identifier
* Visit identifier
* Prescription identifier
* Dispensing identifier
* Medication or product identifier
* Batch identifier
* Department
* Stock location
* Action
* Timestamp
* Reason where required
* Previous and new state where appropriate

---

# Phase 37 — Localization

Add complete English and French localization for the Pharmacy workspace.

Prefer an existing pharmacy localization file if one exists, otherwise use:

```text
lang/en/pharmacy.php
lang/fr/pharmacy.php
```

Include keys for:

* Workspace title
* Dashboard
* Menu sections
* Prescription states
* Dispensing states
* Payment and insurance states
* Safety-warning states
* Stock-availability states
* Batch and expiry states
* Partial-dispensing reasons
* Substitution reasons
* Collection states
* Return outcomes
* Cancellation reasons
* Reversal reasons
* Handoffs
* Empty states
* Quick actions
* Reports
* Breadcrumbs
* Unauthorized workspace message
* Override reasons

Maintain complete English and French parity.

Do not hardcode visible Pharmacy labels in controllers, services, Blade templates, or JavaScript.

---

# Phase 38 — Pharmacy Reports and Statistics

Create or adapt Pharmacy reports under:

```text
/pharmacy/reports
```

Recommended reports include:

* Prescription volume by date
* Prescription volume by department
* Prescription volume by clinician
* Prescription volume by medication
* Dispensing volume
* Partial dispensing report
* Unfulfilled prescription report
* Ready-for-collection report
* Uncollected medication report
* Medication return report
* Dispensing reversal report
* Prescription cancellation report
* Stockout report
* Low-stock report
* Expiry report
* Medication utilization report
* Emergency prescription report
* Inpatient medication supply report
* Insurance prescription report
* Pharmacist activity report
* Prescription turnaround-time report
* Collection turnaround-time report

Reports must respect:

* Permissions
* Active department
* Stock location
* Patient privacy
* Aggregation rules
* Export permissions

Do not expose patient-level medication data in aggregate reports unless the user has the required detailed-report permission.

---

# Phase 39 — Menu Configuration and Future Extensibility

Implement the Pharmacy menu through the existing menu registry or department menu profile service.

Do not define it directly inside the sidebar Blade template.

The menu configuration should support:

* Section ordering
* Route name
* Icon
* Permission
* Module requirement
* Active-route patterns
* Badge counts
* Department-type availability
* Active-department scoping
* Stock-location scoping
* Feature flags
* Pending-prescription counts
* Safety-warning counts
* Ready-for-collection counts
* Low-stock counts
* Stockout counts
* Expiry-alert counts

The architecture must remain extensible for future department menu personalization, including:

```text
radiology
finance
stores
maternity
theatre
blood_bank
mortuary
ambulance
support
administrative
```

Do not implement those other workspaces in this phase.

---

# Phase 40 — Focused Automated Verification

Add focused automated tests for the Pharmacy workspace.

## Route tests

Verify:

* Pharmacy routes exist.
* Route names use `pharmacy.*`.
* URLs use `/pharmacy/*`.
* Pharmacy department middleware is attached.
* Generic routes remain available where required.

## Access tests

Verify:

* A Pharmacy department user can access authorized Pharmacy pages.
* A non-Pharmacy department user cannot access the workspace.
* Users without the required permission cannot access protected actions.
* Admin preview continues working where supported.
* Multi-department active context is respected.
* Stock-location scoping is respected.

## Dashboard tests

Verify:

* The Pharmacy dashboard loads.
* Metrics include only prescriptions assigned to the active Pharmacy department.
* Stock metrics use the active stock location.
* Safety-warning and stockout counts are accurate.
* Links point to `/pharmacy/*`.
* Sensitive information remains protected.
* Empty states render safely.

## Prescription tests

Verify:

* Prescriptions appear in the correct worklists.
* Payment and insurance states are reflected correctly.
* Emergency and inpatient prescriptions are prioritized where configured.
* Clinical-review requirements are enforced.
* Cancelled prescriptions leave active worklists.
* Multi-item prescriptions retain item-level state.

## Safety tests

Verify:

* Allergy warnings remain active.
* Duplicate active medication warnings remain active.
* Missing-diagnosis policy remains active.
* Unusual-dose warnings remain active.
* Blocking warnings prevent dispensing.
* Overrides require permission and reason.
* Overrides are audited.

## Stock tests

Verify:

* Stock availability uses the active stock location.
* Reservations prevent double allocation.
* Expired batches cannot be dispensed.
* Quarantined or recalled batches cannot be dispensed.
* Batch selection follows the configured policy.
* Failed transactions do not create partial stock deductions.
* Cancellation releases unused reservations.

## Dispensing tests

Verify:

* Dispensing cannot exceed prescribed quantity.
* Dispensing cannot exceed available stock.
* Full dispensing updates prescription state correctly.
* Partial dispensing preserves outstanding quantity.
* Later dispensing cannot exceed the remaining quantity.
* Dispensing records preserve batch traceability.
* Medication administration is not recorded by the Pharmacy dispensing action.

## Collection tests

Verify:

* Dispensed medication does not automatically become collected.
* Ready-for-collection state is separate from collected.
* Collection records the responsible user and time.
* Counselling requirements are enforced where configured.
* Uncollected medication follows the configured return-to-stock process.

## Return and reversal tests

Verify:

* Completed dispensing cannot be deleted directly.
* Reversals require a reason.
* Stock is only restored when eligible.
* Invalid returns do not restore saleable stock.
* Quarantined returns are handled correctly.
* Billing corrections are triggered where required.
* Return and reversal actions are audited.

## Redirect tests

Verify:

* Login redirects to `/pharmacy`.
* Switching to Pharmacy redirects to `/pharmacy`.
* Prescription actions remain under `/pharmacy/*`.
* Dispensing actions remain under `/pharmacy/*`.
* Collection, return, reversal, and stock links remain under `/pharmacy/*`.
* No redirect loops occur.
* JSON, API, payment, insurance, signed, print, export, and integration requests are not incorrectly redirected.

## Privacy and audit tests

Verify:

* Patient masking remains active.
* Protected fields require permission.
* Pharmacy users only see authorized clinical context.
* Pharmacy actions generate required audit records.
* Sensitive values are not exposed through alternate Pharmacy views.

Run focused Pharmacy workspace tests and essential route, view, localization, stock, billing, integration, and audit checks during implementation.

Do not run the full UHMS suite after each phase.

Run one broad relevant suite after all Pharmacy workspace phases are complete.

---

# Phase 41 — Manual Acceptance Scenarios

## Scenario A — Pharmacy login

1. Log in as a user whose active department type is `pharmacy`.
2. Confirm the landing URL is `/pharmacy`.
3. Confirm the Pharmacy-specific menu is displayed.
4. Confirm unrelated department menus are absent.

## Scenario B — Prescription receipt

1. Open the pending prescription worklist.
2. Select a new prescription.
3. Confirm payment, insurance, stock, and safety states are visible.
4. Accept the prescription.
5. Confirm the redirect remains under `/pharmacy/*`.
6. Confirm the action is audited.

## Scenario C — Clinical safety warning

1. Open a prescription with an allergy or dose warning.
2. Confirm the warning is visible.
3. Attempt to dispense without resolving it.
4. Confirm dispensing is blocked.
5. Apply an authorized override with a reason.
6. Confirm the override is audited.

## Scenario D — Full dispensing

1. Open an authorized prescription.
2. Start dispensing.
3. Select valid stock batches.
4. Dispense the full prescribed quantity.
5. Mark the medication ready for collection.
6. Confirm stock and prescription states update correctly.

## Scenario E — Partial dispensing

1. Open a prescription with insufficient stock.
2. Dispense the available quantity.
3. Confirm the prescription becomes partially dispensed.
4. Confirm the remaining quantity is preserved.
5. Complete the balance later.
6. Confirm over-dispensing is prevented.

## Scenario F — Batch expiry

1. Open a medication with several batches.
2. Confirm the configured batch-selection policy is used.
3. Attempt to select an expired batch.
4. Confirm the system blocks it.
5. Confirm available valid batches can still be dispensed.

## Scenario G — Patient collection

1. Open the ready-for-collection queue.
2. Select a prescription.
3. Complete required counselling.
4. Confirm collection.
5. Confirm the medication becomes collected.
6. Confirm the action is audited.

## Scenario H — Inpatient medication supply

1. Open an inpatient medication request.
2. Confirm ward, admission, and bed context.
3. Dispense the authorized supply.
4. Confirm the medication remains separate from Nursing administration records.
5. Confirm all URLs remain under `/pharmacy/*`.

## Scenario I — Emergency prescription

1. Open an emergency prescription without ordinary payment completion.
2. Confirm the configured emergency policy is applied.
3. Complete an authorized urgent dispensing.
4. Confirm the override or exception is visible and audited.

## Scenario J — Return

1. Open a completed dispensing.
2. Record a patient return.
3. Assess packaging and storage suitability.
4. Place the medication in available stock, quarantine, or destruction according to policy.
5. Confirm stock and billing updates are correct.
6. Confirm the return is audited.

## Scenario K — Reversal

1. Open a dispensing completed in error.
2. Start a reversal.
3. Record the reason.
4. Confirm eligible stock is restored.
5. Confirm billing correction is triggered where required.
6. Confirm the original dispensing remains in history.

## Scenario L — Active department scoping

1. Use a user assigned to multiple Pharmacy departments.
2. Switch the active department.
3. Confirm prescriptions and stock change to the selected Pharmacy.
4. Confirm unauthorized stock locations are not visible.

## Scenario M — Permission control

1. Remove dispensing-completion permission.
2. Confirm the completion action disappears.
3. Enter the route directly.
4. Confirm access is denied.

## Scenario N — Legacy compatibility

1. Enter a generic prescription or dispensing route as a Pharmacy user.
2. Confirm it safely resolves or redirects to the Pharmacy equivalent where configured.
3. Confirm APIs, payment callbacks, insurance callbacks, signed URLs, print routes, and exports remain unaffected.

---

# Acceptance Criteria

The implementation is accepted only when all the following are true:

1. Users with an active department type of `pharmacy` receive a dedicated Pharmacy menu.
2. Their default dashboard uses `/pharmacy`.
3. Supported Pharmacy pages use `/pharmacy/*` URLs.
4. Route names use the `pharmacy.*` namespace.
5. Prescription and stock worklists are scoped to the active Pharmacy department.
6. Stock availability uses the correct Pharmacy stock location.
7. Forms submit through Pharmacy routes.
8. Redirects remain inside the Pharmacy workspace.
9. Breadcrumbs and active menu states are Pharmacy-aware.
10. Permissions and enabled modules control menu visibility.
11. A non-Pharmacy department user cannot access the workspace.
12. Multi-department users are evaluated using the active department.
13. Existing prescription, dispensing, safety, billing, insurance, stock, inventory, journey, and audit logic is reused.
14. Core prescription, dispensing, and stock logic is not duplicated.
15. Allergy, duplicate-medication, diagnosis, and unusual-dose checks remain active.
16. Blocking safety warnings prevent dispensing.
17. Safety overrides are permission-controlled, reasoned, and audited.
18. Payment and insurance policies remain active.
19. Emergency and inpatient exceptions use configured policies.
20. Stock reservations prevent double allocation.
21. Expired, quarantined, recalled, or unavailable stock cannot be dispensed.
22. Dispensing cannot exceed prescribed or available quantity.
23. Partial dispensing preserves the outstanding balance.
24. Patient collection remains separate from dispensing completion.
25. Pharmacy dispensing remains separate from medication administration.
26. Returns and reversals preserve stock and billing integrity.
27. Completed dispensing records cannot be silently deleted or overwritten.
28. Generic routes remain functional for other departments and integrations.
29. APIs, payment callbacks, insurance callbacks, signed URLs, print routes, and exports are not incorrectly redirected.
30. Patient privacy and medication security remain fully active.
31. Relevant Pharmacy actions are audited.
32. English and French localization are complete and in parity.
33. Focused Pharmacy workspace tests pass.
34. One broad relevant suite passes after all phases are complete.
35. No broken links, route loops, duplicate route names, stock-location leakage, duplicate stock deductions, silent prescription modification, or Nursing-administration contamination remain.

---

# Deliverables

Provide:

1. Pharmacy workspace route group.
2. Pharmacy-specific controllers or thin adapters where required.
3. Pharmacy operations dashboard.
4. Pharmacy department menu profile.
5. Prescription worklists.
6. Pharmacy prescription workspace.
7. Clinical-review and medication-safety integration.
8. Payment, insurance, sponsor, emergency, and inpatient policy integration.
9. Active-department and stock-location scoping.
10. Stock reservation and availability integration.
11. Batch and expiry selection.
12. Full and partial dispensing workflows.
13. Medication substitution workflow.
14. Medication labels and counselling integration.
15. Ready-for-collection and patient-collection workflow.
16. Inpatient Pharmacy workflow.
17. Emergency Pharmacy workflow.
18. Prescription cancellation.
19. Dispensing reversal.
20. Medication return workflow.
21. Stock alert views.
22. Pharmacy handoff integration.
23. Workspace-aware URL resolver updates.
24. Workspace-aware redirect resolver updates.
25. Updated shared links and forms.
26. Login and department-switch integration.
27. Pharmacy breadcrumbs and active-menu handling.
28. Permission integration.
29. Patient privacy and medication-security integration.
30. English and French localization.
31. Focused feature tests.
32. A final implementation report containing:

* Files created
* Files modified
* Pharmacy route map
* Pharmacy menu map
* Prescription worklist categories
* Dashboard metrics
* Clinical-review behaviour
* Medication-safety behaviour
* Payment and insurance behaviour
* Stock-location scoping
* Reservation behaviour
* Batch-selection behaviour
* Full and partial dispensing behaviour
* Collection behaviour
* Inpatient and emergency Pharmacy behaviour
* Return and reversal behaviour
* Reused services
* Redirect behaviour
* Permissions used
* Patient privacy checks
* Medication-security checks
* Audit events
* Tests executed
* Test results
* Remaining limitations, if any

Implement the work fully.

Do not stop at planning, route registration, menu configuration, dashboard layout, prescription listing, or stock display alone. The final implementation must provide a functional, safe, stock-aware, department-specific Pharmacy workspace throughout the complete prescription-fulfilment lifecycle.
