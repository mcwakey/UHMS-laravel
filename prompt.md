You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to modify the **Investigation Request, Lab/Investigation Acceptance, Billing, Results, Printing, and Consultation Status Workflow**.

Focus only on the requirements below. Do not refactor unrelated modules.

---

# 1. Main Objectives

Implement and fix the following workflows:

1. Investigation request acceptance must allow selecting individual requested test items before billing.
2. Invoice must be generated only for accepted investigation items.
3. Result viewing, verification, and printing must be supported.
4. Doctors must be able to view investigation results from the consultation page.
5. Doctors must not be able to delete investigation items after results have been entered.
6. Consultation investigation requests must be grouped by department.
7. Doctors must explicitly start consultation before editing/entering clinical data.
8. Triage completion must move visit status from `TRIAGE` to `WAITING_CONSULTATION`.
9. Consultation list must only show visits with `WAITING_CONSULTATION` or `CONSULTING`.

---

# 2. Investigation Request Acceptance Workflow

On the Investigation/Lab Request View page, modify the flow so that before accepting a request, the investigation staff must select the requested test/service items from the data table.

## Required Behavior

When the investigation staff opens a request:

* Show all requested investigation items in a data table.
* Each item must have a checkbox/select option.
* Staff must select the items they want to accept.
* Only selected items are accepted.
* Only selected items are billed.
* Unselected items remain pending or rejected depending on existing system logic.

## Accept Request Button

When the user clicks:

```text
Accept Request
```

The system must:

1. Validate that at least one test item is selected.
2. Mark only selected request items as accepted.
3. Generate billing/invoice only for selected accepted items.
4. Leave unselected items unchanged.
5. Update the request status based on item statuses.

---

# 3. Automatic Invoice Generation

When selected test items are accepted:

* An invoice must be generated automatically.
* Invoice must include only accepted test items.
* Billing must go through `BillingService`.
* Do not create billing lines directly in the controller.

## Invoice Rules

For each selected item:

* Create billing item linked to:

  * visit
  * patient
  * investigation request
  * investigation request item
  * service
  * department

The invoice total must equal the sum of selected accepted items only.

If insurance applies:

* Use existing insurance/service pricing logic.
* Respect Cash and Carry fallback.
* Do not duplicate pricing logic.

---

# 4. Result Entry, View, Verification, and Printing

After investigation results are entered:

## Result View

There must be a `View Result` button.

This button should:

* Open a modal or page showing the result.
* Display the investigation service/test item.
* Display configured headers/categories.
* Display criteria and entered values.
* Display reference ranges and units where available.
* Display result status.

## Result Verification

There must be a verification step.

After result entry:

* Result status should be something like `PENDING_VERIFICATION`.
* Authorized staff can verify the result.
* Once verified, result status becomes `VERIFIED`.

## Print Button

After verification:

* Show a `Print` button.
* Print only verified test item results.
* Print result in a clean report format.
* Include:

  * hospital info
  * patient info
  * visit info
  * investigation service
  * result values
  * units
  * reference ranges
  * performed by
  * verified by
  * date/time

Do not allow printing unverified results unless explicitly configured.

---

# 5. Doctor Result Viewing from Consultation Page

On the Consultation page, under the Investigation tab:

* Doctors must be able to view results for requested investigations.
* Add a `View Result` button for completed or verified investigation items.
* Open result in a modal or dedicated result preview page.
* Doctors should not need to leave the consultation page.

## Visibility Rules

Doctor can view result when:

* result has been entered, or
* result has been verified, depending on existing system policy.

Recommended:

* Show `View Result` when result status is `RESULT_ENTERED`, `PENDING_VERIFICATION`, or `VERIFIED`.
* Show `Print` only when status is `VERIFIED`.

---

# 6. Prevent Deleting Investigation Items After Result Entry

On the Consultation page, under the Investigation tab:

* If a requested investigation item has no result yet, doctor may delete/cancel it if allowed.
* Once result has been entered, doctor must not be able to delete that item.
* Hide or disable the delete button for items with results.

## Rule

Do not allow deletion if:

```text
result exists
OR status is RESULT_ENTERED
OR status is PENDING_VERIFICATION
OR status is VERIFIED
OR status is DONE
```

Return a clear error message if deletion is attempted from backend.

---

# 7. Investigation Status Update

When result is entered for an investigation item:

* Update the item status from `PENDING` or `ACCEPTED` to `DONE` or `RESULT_ENTERED`.
* If all items in the request are done, update the parent request status to `DONE`.
* If some are done and others are pending, use `PARTIALLY_DONE`.

Recommended statuses:

```text
PENDING
ACCEPTED
IN_PROGRESS
RESULT_ENTERED
PENDING_VERIFICATION
VERIFIED
DONE
CANCELLED
REJECTED
```

Use existing status names if already defined, but keep behavior consistent.

---

# 8. Group Requested Investigations by Department on Consultation Page

When displaying requested investigations under the Consultation page:

* Group the investigation request items by department.
* Each department group should show its requested services/items.

Example:

```text
LAB
- Full Blood Count — Pending
- Malaria Test — Done

X-RAY
- Chest X-Ray — Accepted

SCAN
- Abdominal Scan — Verified
```

Each item should show:

* service name
* status
* request date
* accepted date if available
* result status if available
* action buttons:

  * View Result
  * Print
  * Delete/Cancel if allowed

---

# 9. Start Consultation Workflow

On the Consultation page, before the doctor starts working:

* If visit status is `WAITING_CONSULTATION`, show a button:

```text
Start Consultation
```

* Doctor must click this button before entering complaints, diagnosis, prescriptions, investigations, notes, or treatment.
* Clicking the button changes visit status from:

```text
WAITING_CONSULTATION → CONSULTING
```

or:

```text
WAITING_CONSULTATION → IN_CONSULTATION
```

Use the project’s existing status naming convention.

## Continue Consultation

If visit status is already `CONSULTING` or `IN_CONSULTATION`, show:

```text
Continue Consultation
```

The doctor can continue working.

## Lock Clinical Actions Before Start

Before consultation is started:

* Disable clinical forms.
* Disable save buttons.
* Disable prescription creation.
* Disable investigation request creation.
* Disable diagnosis entry.
* Show clear message:

```text
Click Start Consultation to begin entering clinical information.
```

---

# 10. Triage Completion Workflow

When triage is saved successfully:

* Visit status must change from:

```text
TRIAGE → WAITING_CONSULTATION
```

This must happen through `VisitWorkflowService`.

Do not update visit status directly from controller or frontend component.

## Required Method

Create or update:

```php
VisitWorkflowService::completeTriage($visit, $triageData)
```

This method should:

1. Save vitals.
2. Calculate triage score.
3. Save triage score.
4. Assign chosen consultation department/service if applicable.
5. Create billing where required.
6. Change status to `WAITING_CONSULTATION`.
7. Log the status transition.

---

# 11. Consultation List Filtering

The consultation list must only load patients whose visit status is:

```text
WAITING_CONSULTATION
CONSULTING
```

or existing equivalent statuses.

Do not show:

* TRIAGE patients
* completed visits
* discharged visits
* billing-only visits
* cancelled visits
* investigation-only visits

## Action Button Logic

In the consultation list:

If status is `WAITING_CONSULTATION`, show:

```text
Start Consultation
```

If status is `CONSULTING`, show:

```text
Continue Consultation
```

The button should take the doctor to the consultation page and apply the correct behavior.

---

# 12. Backend Services to Use

Use services. Do not put workflow logic directly in controllers.

Create or update:

```text
VisitWorkflowService
InvestigationRequestService
InvestigationResultService
BillingService
InvoiceService
ConsultationService
```

## InvestigationRequestService

Must handle:

* accepting selected request items.
* validating selected items.
* updating item statuses.
* generating invoice through billing/invoice services.
* updating parent request status.

## InvestigationResultService

Must handle:

* saving result values.
* updating result status.
* verifying result.
* checking whether result exists.
* preventing deletion after result entry.
* loading printable result data.

## VisitWorkflowService

Must handle:

* triage completion.
* start consultation.
* continue consultation.
* status transitions.
* status logs.

## BillingService / InvoiceService

Must handle:

* billing only selected accepted test items.
* automatic invoice generation.
* insurance pricing logic.
* Cash and Carry fallback.

---

# 13. Suggested Methods

Implement or update methods like:

```php
InvestigationRequestService::acceptSelectedItems($requestId, array $itemIds, User $user)

InvestigationResultService::saveResult($requestItemId, array $values, User $user)

InvestigationResultService::verifyResult($resultId, User $user)

InvestigationResultService::canDeleteRequestItem($requestItemId): bool

VisitWorkflowService::completeTriage(Visit $visit, array $triageData): Visit

VisitWorkflowService::startConsultation(Visit $visit, User $doctor): Visit

ConsultationService::getConsultationQueue(User $doctor)
```

---

# 14. Validation Rules

## Accept Investigation Request

* request_id required
* selected item IDs required
* selected items must belong to the request
* selected items must not already be billed/accepted if duplicate billing is not allowed
* selected items must be pending

## Result Entry

* request item must exist
* request item must be accepted or in progress
* criteria values must match configured criteria
* required criteria must have values

## Result Verification

* result must exist
* result must not already be verified
* user must have permission to verify

## Start Consultation

* visit must exist
* visit status must be `WAITING_CONSULTATION`
* doctor must have permission to consult
* doctor must belong to or be allowed for the current department/service

---

# 15. Frontend Requirements

## Investigation Request View Page

* Add checkboxes in request item data table.
* Add selected item count.
* Disable Accept button until at least one item is selected.
* On Accept:

  * submit selected item IDs.
  * show loading state.
  * generate invoice automatically.
  * update item statuses without full reload.
  * show success or validation errors.

## Investigation Result Page

* Add View button after result entry.
* Add Verify button for authorized users.
* Add Print button only after verification.
* Display result in clean modal/page.

## Consultation Page

* Add Start Consultation / Continue Consultation behavior.
* Disable clinical actions until consultation has started.
* Group investigations by department.
* Add View Result button for result-ready items.
* Add Print button for verified items.
* Hide/disable delete button after result entry.

## Triage Page

* On save, update visit status to `WAITING_CONSULTATION`.
* Redirect or update queue without full reload.
* Show confirmation.

## Consultation Queue Page

* Load only `WAITING_CONSULTATION` and `CONSULTING` visits.
* Show action button based on status:

  * `Start Consultation`
  * `Continue Consultation`

---

# 16. Data Integrity Rules

* Do not bill unaccepted investigation items.
* Do not invoice the full request unless all items were selected.
* Do not allow duplicate billing for the same accepted item.
* Do not allow deleting investigation items after results have been entered.
* Do not allow clinical entries before consultation is started.
* Do not update visit statuses directly outside `VisitWorkflowService`.
* Do not print unverified results unless the system setting explicitly allows it.
* Preserve old result values and printed report data.

---

# 17. Performance Rules

* Eager-load patient, visit, service, department, requester, result status.
* Paginate investigation request queues.
* Do not load full result details until View Result is clicked.
* Group investigations by department efficiently on backend or frontend.
* Avoid N+1 queries on consultation page.
* Keep consultation queue fast.

---

# 18. Deliverables

Provide:

1. Root cause of investigation request billing issue if existing.
2. Files modified.
3. New migrations if needed.
4. Updated models and relationships.
5. Updated investigation request acceptance logic.
6. Automatic invoice generation for selected accepted items.
7. Result view, verification, and print workflow.
8. Doctor-side result viewing under Consultation → Investigation tab.
9. Delete prevention after result entry.
10. Grouped investigation display by department.
11. Start Consultation / Continue Consultation workflow.
12. Triage status transition fix.
13. Consultation queue filtering fix.
14. Confirmation that no unaccepted item is billed.
15. Confirmation that clinical actions are disabled until consultation starts.

---

# 19. Important Rules

Do not hardcode Lab only. This applies to all investigation-type departments.

Do not bill all request items automatically unless all were selected.

Do not create invoices directly in controllers.

Do not allow result printing before verification.

Do not allow doctors to delete investigation items after results exist.

Do not allow doctors to enter consultation data before clicking Start Consultation.

Do not show triage patients in consultation queue.

Do not reload the whole page for these actions.

Now inspect the current implementation and apply these workflow fixes carefully without breaking existing investigation, billing, consultation, or triage behavior.
