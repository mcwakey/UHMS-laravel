You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to fix and redesign parts of the **Doctor Consultation, Prescription, Investigation Request, and Investigation Catalogue** workflow.

Focus only on these issues. Do not refactor unrelated modules.

---

# 1. Main Problems to Fix

## Problem A — Prescription Not Saving

On the doctor consultation page, when the doctor creates a prescription, it does not save.

Investigate and fix the prescription save flow.

Check:

* route method
* form submission method
* controller method
* request validation
* missing required fields
* medication/drug relationship
* visit/consultation relationship
* Inertia/Vue form handling
* database table fields
* model `$fillable`
* authorization/middleware
* server-side validation errors not displayed
* JavaScript errors preventing submit
* modal/form button not submitting properly

After fixing:

* prescription must save successfully.
* validation errors must display properly.
* the prescription list must update without full page reload.
* the doctor must remain on the same consultation tab after saving.

---

## Problem B — Investigation Request Not Showing on Investigation Side

On the doctor consultation page, when a doctor requests an investigation, the request does not appear on the investigation request page.

Investigate the full flow:

```text
Doctor Consultation → Investigation Request → Investigation Department Queue
```

Check:

* whether the investigation request is actually saved
* whether it is linked to the correct visit
* whether it is linked to the correct patient
* whether it is linked to the selected investigation department
* whether it is linked to the selected investigation service
* whether status is set correctly
* whether the investigation request page filters by wrong department/status/date
* whether the logged-in investigation user has permission to see it
* whether the query is using the wrong relationship
* whether the request is created but hidden by filters

After fixing:

* investigation requests created by doctors must appear on the correct investigation department request page.
* investigation request status must be clear.
* the request must show patient, visit, service, department, request date, requested by doctor, and current status.

---

# 2. Investigation Catalogue Revamp

The current “Lab Test Catalogue” must be redesigned into a general **Investigation Catalogue**.

The system should not assume everything is Lab.

Investigations can include:

* Lab
* X-ray
* Scan
* CT-scan
* Ultrasound
* ECG
* Other investigation-type departments

---

# 3. Rename Concept

Rename or redesign:

```text
Lab Test Catalogue
```

to:

```text
Investigation Catalogue
```

Do not keep the catalogue limited to lab tests.

---

# 4. Catalogue Structure

The Investigation Catalogue must be based on **services**.

The first thing shown in the Investigation Catalogue should be:

```text
List of services that belong to investigation-type departments
```

Example:

```text
Full Blood Count
Malaria Test
Chest X-Ray
Abdominal Scan
CT Brain
ECG
```

These are services from departments marked as investigation type.

---

# 5. Department and Service Rules

A department can have a type.

Example:

```text
department.type = consultation | investigation | pharmacy | ward | admin | accounts
```

Only services from departments where:

```text
department.type = investigation
```

should appear in the Investigation Catalogue.

Do not create separate “tests” that are detached from services.

The service is the investigation item.

---

# 6. Categories / Headers / Criteria

When a service is selected in the Investigation Catalogue, the user must be able to configure:

1. Categories / Headers
2. Criteria

A criterion can belong to a category/header or can stand alone.

## Example

Service:

```text
Full Blood Count
```

Headers/Categories:

```text
Red Cell Indices
White Cell Count
Platelets
```

Criteria:

```text
Hemoglobin
WBC
RBC
HCT
MCV
MCH
MCHC
Platelets
```

Some criteria may belong to a header, while others may not.

---

# 7. Important Catalogue Rule

We should not have a separate “test” under the catalogue.

Wrong:

```text
Investigation Catalogue
    → Test
        → Criteria
```

Correct:

```text
Investigation Catalogue
    → Investigation Service
        → Optional Headers/Categories
        → Criteria
```

The selected service itself is the investigation test/item.

---

# 8. Suggested Database Design

Use existing tables where possible, but if missing, create clean tables.

## investigation_catalogue_services

Optional table only if needed. Prefer using existing `services` table filtered by investigation department.

## investigation_headers

```text
id
service_id
name
description nullable
sort_order
is_active
created_at
updated_at
```

## investigation_criteria

```text
id
service_id
header_id nullable
name
unit nullable
reference_range nullable
default_value nullable
input_type
options nullable
sort_order
is_required
is_active
created_at
updated_at
```

Criteria must belong to a service.

Criteria may optionally belong to a header/category.

---

# 9. Investigation Request Flow

When a doctor requests an investigation:

1. Doctor selects an investigation department.
2. System loads services under that investigation department.
3. Doctor selects one or more services.
4. System creates investigation request/order records.
5. Billing lines are created through `BillingService`.
6. Investigation department sees the request in their queue.

Required records:

```text
investigation_requests
investigation_request_items
```

Suggested structure:

## investigation_requests

```text
id
visit_id
patient_id
requested_by
requesting_department_id nullable
status
notes nullable
created_at
updated_at
```

## investigation_request_items

```text
id
investigation_request_id
service_id
department_id
status
billing_item_id nullable
created_at
updated_at
```

---

# 10. Investigation Result Entry

On the investigation result/resource entry page:

When opening a requested investigation service, the system must load the configured:

* headers/categories
* criteria

from the Investigation Catalogue.

The result entry form must display criteria grouped by header where available.

If a criterion has no header, show it under an “Ungrouped” or “General” section.

Example:

```text
Full Blood Count

[Red Cell Indices]
- Hemoglobin: ______ g/dL
- RBC: ______
- HCT: ______

[White Cell Count]
- WBC: ______

[General]
- ESR: ______
```

The investigation staff should enter results against the configured criteria.

---

# 11. Investigation Result Tables

Use or create tables like:

## investigation_results

```text
id
investigation_request_item_id
visit_id
patient_id
service_id
performed_by nullable
status
notes nullable
created_at
updated_at
```

## investigation_result_values

```text
id
investigation_result_id
criteria_id
value
unit nullable
flag nullable
reference_range nullable
created_at
updated_at
```

Important:

* Store result values against criteria.
* Preserve unit and reference range snapshot at time of result entry.
* Do not depend on future catalogue changes to alter old results.

---

# 12. Backend Services

Create or update these services:

```text
PrescriptionService
InvestigationRequestService
InvestigationCatalogueService
InvestigationResultService
BillingService
```

## PrescriptionService

Must:

* validate medication/drug exists.
* validate visit exists.
* validate doctor/user can prescribe.
* save prescription.
* return updated prescription list or Inertia response.

## InvestigationRequestService

Must:

* create investigation request from consultation.
* attach selected services.
* route request to correct investigation department.
* create billing through `BillingService`.
* set correct statuses.
* ensure request appears in investigation queue.

## InvestigationCatalogueService

Must:

* load only services under investigation-type departments.
* create/update headers for selected service.
* create/update criteria for selected service.
* order headers and criteria correctly.
* prevent duplicate criteria names under same service/header where appropriate.

## InvestigationResultService

Must:

* load configured headers and criteria for requested service.
* save result values against criteria.
* preserve unit/reference range snapshots.
* update investigation request item status.

---

# 13. Frontend Requirements

## Doctor Consultation Page

Fix:

* Prescription creation.
* Investigation request creation.
* SPA behavior.

Requirements:

* Save prescription without full reload.
* Update prescription list after save.
* Show validation errors if prescription fails.
* Doctor remains on current tab.
* Request investigation without full reload.
* Investigation request should appear immediately or show success feedback.

## Investigation Request Page

Must show requests from doctors.

Display:

* patient name
* visit number
* requested service
* investigation department
* requested by
* request date
* status
* action button

## Investigation Catalogue Page

Rename from Lab Test Catalogue to Investigation Catalogue.

First screen:

```text
List of investigation services
```

When selecting a service:

* show existing headers/categories
* show existing criteria
* allow creating/editing/deleting headers
* allow creating/editing/deleting criteria
* allow criteria to be linked to a header or remain ungrouped

## Investigation Result Entry Page

When opening a request item:

* load headers and criteria configured for the selected service.
* display grouped result fields.
* save result values.
* update status.

---

# 14. Validation Rules

## Prescription

* visit_id required
* medication/drug required
* quantity required
* dosage/instruction required where applicable
* prescribed_by required

## Investigation Request

* visit_id required
* at least one service required
* selected services must belong to investigation-type departments
* department must match service department

## Investigation Catalogue

* service must belong to investigation-type department
* header name required
* criteria name required
* criteria service_id required
* criteria header_id nullable
* input_type required
* sort_order optional

## Investigation Result

* request item must exist
* criteria must belong to the request item’s service
* required criteria must have values
* result values must be saved safely

---

# 15. Data Integrity Rules

* Do not create catalogue tests detached from services.
* Services are the investigation items.
* Criteria belong to services.
* Headers belong to services.
* Criteria may optionally belong to headers.
* Old result records must not change if catalogue configuration changes later.
* Investigation requests must be linked to visit and patient.
* Billing must be created through `BillingService`.
* Do not duplicate investigation request logic in controllers.

---

# 16. Performance Rules

* Avoid N+1 queries when loading investigation requests.
* Eager-load patient, visit, service, department, requester.
* Paginate request queues.
* Load catalogue criteria only when a service is selected.
* Cache investigation catalogue configuration where safe.
* Keep doctor consultation page fast.
* Do not load all service criteria for all services upfront.

---

# 17. Deliverables

Provide:

1. Root cause of prescription not saving.
2. Root cause of investigation requests not appearing.
3. Files modified.
4. Any new migrations.
5. Updated models and relationships.
6. `PrescriptionService` fix or implementation.
7. `InvestigationRequestService` implementation.
8. `InvestigationCatalogueService` implementation.
9. `InvestigationResultService` implementation.
10. Updated doctor consultation prescription flow.
11. Updated doctor consultation investigation request flow.
12. Renamed/revamped Investigation Catalogue UI.
13. Result entry page using configured criteria and headers.
14. Confirmation that only investigation-type department services appear in the catalogue.
15. Confirmation that there is no separate “test” entity under catalogue.

---

# 18. Important Rules

Do not hardcode Lab only.

Do not keep “Lab Test Catalogue” as the main concept.

Do not create tests separate from services.

Do not save investigation results as unstructured text only.

Do not create billing directly from the controller.

Do not break existing consultation workflow.

Do not reload the whole consultation page after prescription or investigation request save.

Do not load unnecessary criteria data upfront.

remove anything tha is Lab related specifically.

Now inspect the existing implementation and fix the prescription save issue, investigation request visibility issue, and redesign the catalogue into a proper Investigation Catalogue based on investigation-type department services.
