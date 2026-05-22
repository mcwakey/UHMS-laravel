You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to fix **double billing during visit creation** and redesign the **consultation queue / consultation routing logic**.

Focus only on visit creation billing, consultation queue eligibility, consultation start logic, and consultation department routing.

Do not refactor unrelated modules.

---

# 1. Current Problems

## Problem A — Double Billing on Visit Creation

When creating a visit, the patient is being billed twice.

This must be fixed.

Investigate all places where visit creation adds invoice items, especially:

```text
VisitController
VisitService
VisitWorkflowService
BillingService
InvoiceService
VisitServiceItem
visit_services if still present
invoice_items
selected services handling
```

Find the exact duplicate billing source.

Possible causes:

```text
Visit creation adds selected services to invoice
AND another workflow also generates invoice items from visit services

BillingService::addItemToVisitInvoice called twice

VisitServiceItem created then later converted immediately again

Frontend submits selected services twice

Controller and service both add billing lines

Duplicate guard missing or using wrong source_type/source_id

Old visit_services logic still active alongside invoice_items
```

---

# 2. Required Billing Rule

When a visit is created:

* selected services should be billed **once only**
* all billable services must go to the visit’s single invoice
* invoice item must have a unique source reference
* old duplicate paths must be removed or disabled

Use only:

```text
BillingService
InvoiceService
invoice_items
```

Do not use `visit_services` as a billing source if the system has already moved to one visit invoice.

---

# 3. Duplicate Protection

`BillingService::addItemToVisitInvoice()` must prevent duplicates.

For each invoice item, use a stable unique identity such as:

```text
invoice_id
source_type
source_id
service_id
product_id
```

Recommended for visit creation selected services:

```text
source_type = visit_selected_service
source_id = selected visit service item id
```

or, if there is no staging table:

```text
source_type = visit_creation
source_id = visit.id + service.id
```

Better approach:

Create a unique key or duplicate check:

```php
InvoiceItem::where('invoice_id', $invoice->id)
    ->where('source_type', $sourceType)
    ->where('source_id', $sourceId)
    ->exists();
```

If the same selected service can appear once per visit, also prevent:

```php
InvoiceItem::where('invoice_id', $invoice->id)
    ->where('service_id', $service->id)
    ->where('source_type', 'visit_creation')
    ->exists();
```

Adapt to existing schema.

Do not silently create duplicate invoice lines.

---

# 4. Consultation Queue Rule

Only queue visits that have at least one selected/billed service from a **consultation-type department**.

A visit should appear in the triage/consultation queue only if:

```text
visit has invoice item / selected service
AND service.department.type = consultation
AND visit status is WAITING_CONSULTATION or CONSULTING
```

Do not queue visits for triage/consultation if they only have:

```text
lab service
pharmacy product
procedure service
ward service
emergency-only consumable
billing-only item
```

---

# 5. Consultation Service Link Rule

When starting a consultation, the consultation session must be linked to one specific consultation service selected for that patient.

Example:

A patient visit has selected services:

```text
General Consultation — OPD Department
Dental Consultation — Dental Department
Lab Malaria Test — Lab Department
```

The consultation queue should only care about:

```text
General Consultation
Dental Consultation
```

When the doctor starts consultation, they must start it for one of those consultation services.

---

# 6. One Consultation Department at a Time

A patient can be directed to one consultation department at a time.

When transitioning/directing a patient to a consultation department:

* select one consultation department
* select one consultation service under that department
* assign doctor optionally
* update current consultation target
* keep visit status as `CONSULTING`

Important:

```text
Changing consultation department should not change visit status away from CONSULTING.
```

The visit remains clinically active while moving between consultation departments.

---

# 7. Visit Status Rule During Department Transition

When a patient is already in consultation and is referred/transitioned to another consultation department:

```text
visit.status = CONSULTING
```

must remain unchanged.

Only these fields should change:

```text
current_department_id
current_consultation_service_id or equivalent
assigned_doctor_id if selected
consultation routing/status record
```

Do not set status to:

```text
WAITING_CONSULTATION
REFERRED_CONSULTATION
LAB
PHARMACY
BILLING
```

when moving between consultation departments unless there is a clear separate workflow requirement.

For consultation-to-consultation routing, status remains:

```text
CONSULTING
```

---

# 8. Required Data Model Review

Inspect current models/tables and decide the cleanest implementation.

Possible existing/needed structures:

```text
visits.current_department_id
visits.assigned_doctor_id
visits.current_consultation_service_id nullable
medical_records.service_id nullable
consultation_sessions
consultation_tasks
invoice_items.service_id
service_catalog.department_id
departments.type
```

If `current_consultation_service_id` does not exist and there is no equivalent field, add it:

```text
visits.current_consultation_service_id nullable foreign key to services/service_catalog
```

or create a better table:

```text
visit_consultation_routes
- id
- visit_id
- department_id
- service_id
- doctor_id nullable
- status
- started_at nullable
- completed_at nullable
- routed_by
- notes nullable
```

Recommended if multiple consultation departments can happen during one visit:

```text
visit_consultation_routes
```

This avoids overloading `visits`.

---

# 9. Recommended Consultation Route Table

Create if missing:

```text
visit_consultation_routes
- id
- visit_id
- patient_id
- department_id
- service_id
- doctor_id nullable
- status
- routed_by nullable
- started_by nullable
- started_at nullable
- completed_by nullable
- completed_at nullable
- notes nullable
- created_at
- updated_at
```

Statuses:

```text
PENDING
ACTIVE
COMPLETED
CANCELLED
```

Rules:

* a visit can have many consultation routes
* only one route can be ACTIVE at a time
* the currently active route determines the current consultation department/service
* starting consultation activates one route
* switching/referring to another consultation department completes or suspends the current route and creates/activates another route
* visit status remains `CONSULTING`

If current system already has a similar table, use it instead of creating a duplicate.

---

# 10. Visit Creation Consultation Route

When creating a visit with selected services:

1. Create visit.
2. Create/get invoice.
3. Bill selected services once.
4. Identify selected services whose department type is `consultation`.
5. Create pending consultation route records for those consultation services.
6. If only one consultation service exists:

   * set it as first/current route if appropriate.
7. If multiple consultation services exist:

   * user must choose initial consultation service/department after triage.

Do not queue visit for triage or consultation unless at least one pending consultation route exists.

---

# 11. Triage to Consultation Rule

After triage is completed:

* triage personnel must direct patient to one consultation department/service
* the selected department/service must come from the patient’s selected consultation services
* activate that consultation route
* set:

```text
visit.status = WAITING_CONSULTATION
visit.current_department_id = selected consultation department
visit.current_consultation_service_id = selected consultation service
```

When doctor starts consultation:

```text
WAITING_CONSULTATION → CONSULTING
```

through `VisitWorkflowService`.

---

# 12. Start Consultation Logic

When doctor clicks Start Consultation:

1. Validate visit has status `WAITING_CONSULTATION`.
2. Validate visit has current consultation service selected.
3. Validate service belongs to consultation-type department.
4. Validate current department matches service department.
5. Assign current doctor if selected/allowed.
6. Link medical record / consultation session to the selected consultation service.
7. Transition visit:

```text
WAITING_CONSULTATION → CONSULTING
```

8. Mark consultation route as `ACTIVE`.
9. Open consultation page.

Do not start consultation without a consultation service.

---

# 13. Continue Consultation Logic

When continuing consultation:

1. Validate visit status is `CONSULTING`.
2. Load active consultation route.
3. Load service and department linked to the route.
4. Load or create medical record tied to visit and consultation route/service.
5. Open consultation page.

---

# 14. Medical Record / Consultation Link

Clinical entries should be traceable to the consultation service/department where they were made.

If possible, link medical records or consultation sections to:

```text
visit_id
consultation_route_id
service_id
department_id
doctor_id
```

At minimum:

```text
medical_records.visit_id
medical_records.doctor_id
medical_records.service_id
medical_records.department_id
```

This helps visit preview show:

```text
Dental consultation by Dr. X
General consultation by Dr. Y
```

---

# 15. Consultation-to-Consultation Referral

When a doctor sends patient to another consultation department:

1. Select target consultation department.
2. Load consultation services under that department.
3. Select one service.
4. Optionally select doctor.
5. Validate selected service belongs to consultation-type department.
6. Create or activate target consultation route.
7. Keep visit status as:

```text
CONSULTING
```

8. Update current department/service.
9. Log the route transition.
10. Do not create duplicate billing unless the selected service has not already been billed.

If the new consultation service is billable and not already billed:

* add invoice item once through `BillingService`
* use insurance pricing
* avoid duplicate invoice item

---

# 16. Consultation Queue Query

Update consultation queue query.

It should load visits where:

```text
visit.status IN (WAITING_CONSULTATION, CONSULTING)
AND visit has pending/active consultation route
AND route.department.type = consultation
```

Or if no route table exists:

```text
visit.status IN (WAITING_CONSULTATION, CONSULTING)
AND visit.current_department_id is consultation-type
AND visit.current_consultation_service_id is not null
```

Do not show visits with no consultation service.

---

# 17. UI Requirements

## Visit Creation Page

* selected services should clearly show department type
* consultation services should be identifiable
* avoid duplicate service submission
* avoid duplicate billing
* if no consultation service is selected, do not send patient to consultation queue
* if selected service is not consultation type, do not queue consultation

## Triage Page

After vitals, user must choose one consultation destination from the patient’s selected consultation services.

Show:

```text
Available consultation services for this visit
```

Example:

```text
General Consultation — OPD
Dental Consultation — Dental
ENT Consultation — ENT
```

## Consultation Queue

Show:

```text
Patient
Visit Number
Current Consultation Department
Current Consultation Service
Assigned Doctor
Status
Action
```

Action:

```text
WAITING_CONSULTATION → Start Consultation
CONSULTING → Continue Consultation
```

## Consultation Page Header

Show:

```text
Current Department
Current Consultation Service
Doctor
Visit Status
```

## Refer / Transition to Another Consultation Department

Add modal:

```text
Select Consultation Department
Select Consultation Service
Select Doctor optional
Notes
```

When submitted:

* visit remains `CONSULTING`
* route changes
* current department/service changes
* billing happens only if needed and once

---

# 18. Fix Double Billing Specifically

Add debug/logging temporarily if needed.

Check these areas:

```text
VisitController@store
VisitService::create
BillingService::addItemToVisitInvoice
InvoiceService::getOrCreateVisitInvoice
generateItemsFromVisit
VisitServiceItem
frontend selected services array
```

Find whether both of these happen:

```text
selected services billed during create
selected services billed again after visit creation
```

Then remove one path.

Final rule:

```text
One selected service = one invoice item only.
```

Add tests to prove it.

---

# 19. Tests Required

Add or update tests:

## Double Billing

1. Creating visit with one selected service creates exactly one invoice item.
2. Creating visit with two selected services creates exactly two invoice items.
3. Refreshing/retrying visit creation does not duplicate invoice items.
4. BillingService prevents duplicate source billing.
5. Old visit_services logic does not create duplicate invoice items.

## Consultation Queue

6. Visit with no consultation service does not appear in consultation queue.
7. Visit with consultation service appears in consultation queue after triage directs it.
8. Visit with only lab service does not appear in consultation queue.
9. Visit with only procedure service does not appear in consultation queue.
10. Visit with consultation + lab service appears only for consultation service.

## Start Consultation

11. Cannot start consultation without selected consultation service.
12. Start consultation links consultation to selected service.
13. Start consultation changes status to `CONSULTING`.
14. Active consultation route is created/updated.

## Department Transition

15. Transition to another consultation department keeps visit status `CONSULTING`.
16. Transition updates current department.
17. Transition updates current consultation service.
18. Transition does not duplicate billing if service already billed.
19. Transition bills new consultation service once if not yet billed.
20. Consultation queue shows current department/service correctly.

---

# 20. Deliverables

Provide:

1. Root cause of double billing.
2. Files modified.
3. Billing duplication fix.
4. Duplicate guard in BillingService.
5. Consultation queue eligibility fix.
6. Consultation route/service linking implementation.
7. Triage-to-consultation destination logic.
8. Start consultation service link.
9. Consultation department transition logic.
10. UI updates.
11. Tests or verification notes.
12. Remaining TODOs.

---

# 21. Important Rules

Do not create duplicate invoice items.

Do not queue visits without consultation service.

Do not start consultation without selected consultation service.

Do not transition patient to multiple consultation departments at once.

Do not change visit status away from `CONSULTING` when moving between consultation departments.

Do not bypass `VisitWorkflowService`.

Do not bypass `BillingService`.

Do not break OPD, Emergency, Admission, Investigation, Pharmacy, Procedure, or Billing workflows.

Now inspect the current implementation and fix the double billing plus consultation routing logic carefully.
