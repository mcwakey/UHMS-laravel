You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to implement a Service Rendering / Service Fulfilment page for all visit services that are not category/type = CONSULTATION.

Currently, consultation services are handled through consultation sessions, but other service types also need a dedicated page where staff can see pending services, work on them, and mark whether they have been rendered or not.

This is important because a patient may be billed for services such as wound dressing, injection, nebulization, ECG, nursing care, emergency service, ward service, minor service, administrative service, or other non-consultation services, and the hospital must know whether the service was actually rendered.

Do not create a parallel billing system.

Do not create a parallel consultation system.

Do not break existing investigation, pharmacy, procedure, theatre, emergency, admission, billing, visit, or invoice workflows.

---

# 1. Main Objective

Create a page/module where all non-consultation services selected for a visit can be displayed, tracked, worked on, and marked as rendered.

The page should answer:

- Which non-consultation services are pending?
- Which department is responsible?
- Which patient/visit does it belong to?
- Has the service been rendered?
- Who rendered it?
- When was it rendered?
- Was there a note/result/proof?
- Was it billed?
- Was it paid?
- Is it cancelled or not rendered?

---

# 2. Services Included

This page should include services where category/type is NOT CONSULTATION.

Examples:

- Nursing services
- Wound dressing
- Injection administration
- Nebulization
- ECG
- Observation service
- Emergency service
- Ward service
- Admission service
- Minor procedure service if not handled by theatre workflow
- Administrative service
- Bedside care service
- Other general hospital services

Do not include:

- Consultation services handled by consultation sessions
- Investigation services handled by investigation workflow
- Pharmacy/drug dispensing handled by pharmacy workflow
- Procedure/theatre services already handled by procedure/theatre workflow

However, if a non-consultation service does not have a specialized workflow, it must appear on this Service Rendering page.

---

# 3. Service Rendering Statuses

Each service item should have a rendering status.

Recommended statuses:

PENDING
IN_PROGRESS
RENDERED
NOT_RENDERED
CANCELLED
ON_HOLD

Meaning:

PENDING = service selected/billed but not yet worked on  
IN_PROGRESS = staff started working on it  
RENDERED = service has been completed  
NOT_RENDERED = service could not be rendered, with reason  
CANCELLED = service was cancelled  
ON_HOLD = service temporarily paused  

---

# 4. Data Model

Inspect the current invoice_items / visit services / selected services implementation first.

Do not recreate visit_services if the project already moved to invoice_items.

Preferred approach:

Use invoice_items as the source of billable services.

For each invoice item linked to a service, track rendering status either directly on invoice_items or in a separate service_renderings table.

Recommended table if missing:

service_renderings
- id
- visit_id
- patient_id
- invoice_item_id nullable
- service_id
- department_id nullable
- emergency_case_id nullable
- admission_id nullable
- consultation_route_id nullable
- rendered_by nullable
- started_by nullable
- started_at nullable
- rendered_at nullable
- status
- notes nullable
- result_summary nullable
- reason_not_rendered nullable
- created_by
- updated_by nullable
- created_at
- updated_at

Rules:

- One invoice item/service should not create duplicate active rendering records.
- Service rendering must preserve original billing/invoice link.
- Rendering a service must not duplicate billing.
- Cancelling/not-rendering should not automatically delete invoice item.
- Billing correction/cancellation should follow BillingService rules.

---

# 5. Automatic Creation of Service Rendering Records

When a non-consultation service is added to a visit invoice, the system should create a service rendering record automatically if the service requires rendering tracking.

Rules:

If service.category/type = CONSULTATION:
    create/route consultation session, not service rendering.

If service.category/type = INVESTIGATION:
    use investigation workflow.

If service.category/type = PHARMACY/PRODUCT/DRUG:
    use pharmacy workflow.

If service.category/type = PROCEDURE/THEATRE:
    use procedure/theatre workflow.

Else:
    create service_rendering record with status PENDING.

Use service configuration if available:

requires_rendering_tracking = true/false

If missing, infer from category/type.

---

# 6. Service Rendering Page

Create a page:

Service Rendering / Service Fulfilment

Suggested menu:

Clinical Services
    Service Rendering

or:

Operations
    Service Rendering

The page should show a table of pending and active non-consultation services.

Columns:

- Patient
- Visit No.
- Service
- Department
- Source
- Invoice No.
- Billing Status
- Payment Status
- Rendering Status
- Requested/Created At
- Rendered By
- Rendered At
- Actions

Filters:

- date range
- department
- service
- status
- patient
- visit number
- billing status
- payment status
- source: OPD / Emergency / Admission
- rendered by

---

# 7. Actions

Users should be able to:

- View service details
- Start service
- Mark as rendered
- Mark as not rendered
- Put on hold
- Cancel service if authorized
- Add notes/result summary
- View related invoice item
- View visit preview
- Open patient folder

---

# 8. Start Service

When user clicks Start:

- status becomes IN_PROGRESS
- started_by = current user
- started_at = now
- log action
- update UI immediately

Do not create new billing item.

---

# 9. Mark as Rendered

When user marks service as rendered:

Required fields:

- rendered_at
- rendered_by current user
- notes optional
- result_summary optional depending service

Actions:

- status = RENDERED
- rendered_at = now unless user enters allowed time
- rendered_by = current user
- log action
- update visit pathway
- update visit preview
- notify relevant user if needed

Do not duplicate invoice item.

Do not change payment status.

---

# 10. Mark as Not Rendered

When marking as not rendered:

Require:

- reason_not_rendered

Actions:

- status = NOT_RENDERED
- reason_not_rendered saved
- log action
- update visit pathway
- optionally notify billing/cashier if invoice correction may be needed

Important:

Not rendered does not automatically remove invoice item.

If refund/cancellation is needed, user must use BillingService/billing correction workflow.

---

# 11. Billing Relationship

Service Rendering and Billing must be connected but not mixed.

Billing tells:

- service was charged
- patient payable
- payment status

Rendering tells:

- service was actually done

The page should display billing/payment status but rendering actions should not directly modify billing except through authorized workflows.

If a service is not rendered but already billed, show warning:

This service was billed but marked as not rendered. Billing review may be required.

---

# 12. Department Responsibility

Each service should be linked to a department or department type.

The rendering page should allow staff to see services assigned to their department.

Examples:

- Nursing department sees nursing care, injections, wound dressing
- Emergency sees emergency services
- Ward sees ward services
- ECG/Cardiology sees ECG services
- Physiotherapy sees physiotherapy services

Users should only see/render services they are authorized for, unless they have admin permission.

---

# 13. Visit Pathway Integration

Every service rendering action should create pathway events.

Examples:

SERVICE_RENDERING_CREATED
SERVICE_RENDERING_STARTED
SERVICE_RENDERED
SERVICE_NOT_RENDERED
SERVICE_RENDERING_CANCELLED

Visit Preview should show:

10:20 Wound dressing service started by Nurse Ama  
10:35 Wound dressing rendered by Nurse Ama  
Notes: Dressing changed, wound clean.

---

# 14. Visit Preview Integration

Update Visit Preview to include service renderings.

Show under chronological timeline and/or service section:

- service name
- department
- status
- rendered by
- rendered at
- notes/result summary
- billing/payment status
- reason if not rendered

---

# 15. Consultation Page Integration

If the patient is in consultation and doctor sends the patient for a non-consultation service, the visit status should remain CONSULTING.

The service rendering should appear as a pathway event and on the Service Rendering page.

When the service is completed, doctor can see it from Visit Preview / session summary.

Do not change visit.status to service department names.

---

# 16. Emergency / Admission Integration

Emergency and Admission may generate non-consultation services.

Examples:

- emergency observation
- emergency nursing care
- ward nursing service
- injection service
- bedside service

These should appear on the Service Rendering page if they do not have their own specialized workflow.

They should also link to:

- emergency_case_id where applicable
- admission_id where applicable

---

# 17. Permissions

Add or verify permissions:

service_rendering.view
service_rendering.view_all
service_rendering.start
service_rendering.mark_rendered
service_rendering.mark_not_rendered
service_rendering.cancel
service_rendering.edit_notes
service_rendering.reports

Rules:

- Department users can work on services assigned to their department.
- Admin/Super Admin can view all.
- Only authorized users can cancel.
- Only authorized users can edit rendered records.
- Completed rendered records should require correction permission if edited.

Optional correction permission:

service_rendering.correct_completed

---

# 18. UI Permission Rules

Frontend must hide/show buttons based on permissions and service status.

Examples:

- Start button only if PENDING and user can start
- Mark Rendered only if PENDING or IN_PROGRESS and user can mark_rendered
- Mark Not Rendered only if PENDING or IN_PROGRESS and user can mark_not_rendered
- Cancel only if user can cancel
- Edit notes only if user can edit_notes
- Correct completed only if user can correct_completed

Backend must enforce the same permissions.

---

# 19. Notifications

Send notifications where useful:

- new service rendering assigned to department
- service rendered
- service marked not rendered
- service overdue if expected time exists
- billing review needed for billed but not rendered service

Do not spam.

Use existing NotificationService.

---

# 20. Logs / Audit

Every action must be logged:

- service rendering created
- service started
- service rendered
- service not rendered
- service cancelled
- notes edited
- correction made

Logs should include:

- user
- patient
- visit
- service
- old status
- new status
- reason if applicable
- timestamp

Use ActivityLogService.

---

# 21. Reports

Prepare report foundation.

Reports:

- rendered services report
- pending services report
- not rendered services report
- services by department
- services by staff
- billed but not rendered report
- rendered but unpaid report

Filters:

- date range
- department
- service
- staff
- status
- patient
- visit
- billing status

---

# 22. Backend Services

Create or update:

ServiceRenderingService
ServiceRenderingQueryService
VisitPathwayService
VisitPreviewService
BillingService
NotificationService
ActivityLogService

Do not put workflow logic directly in controllers.

---

# 23. Routes / Controllers

Create or update:

ServiceRenderingController
ServiceRenderingActionController
ServiceRenderingReportController

Suggested routes:

GET /admin/service-renderings
GET /admin/service-renderings/{serviceRendering}
POST /admin/service-renderings/{serviceRendering}/start
POST /admin/service-renderings/{serviceRendering}/mark-rendered
POST /admin/service-renderings/{serviceRendering}/mark-not-rendered
POST /admin/service-renderings/{serviceRendering}/cancel
PATCH /admin/service-renderings/{serviceRendering}/notes

Adapt to existing route conventions.

---

# 24. Frontend Components

If using Inertia/Vue, create:

resources/js/Pages/ServiceRenderings/Index.vue
resources/js/Pages/ServiceRenderings/Show.vue

Components:

ServiceRenderingTable
ServiceRenderingFilters
ServiceRenderingStatusBadge
ServiceRenderingActionModal
MarkRenderedModal
MarkNotRenderedModal
ServiceRenderingDetailPanel

Use existing UI style.

Modals must:

- close only after successful save
- show validation errors inside modal
- not leave stuck backdrop
- update table immediately
- preserve filters/page state

---

# 25. Validation Rules

Start service:

- status must be PENDING
- user must have permission
- service must not be cancelled
- service must not already be rendered

Mark rendered:

- status must be PENDING or IN_PROGRESS
- rendered_at required
- rendered_by current user
- notes optional
- user must have permission

Mark not rendered:

- status must be PENDING or IN_PROGRESS
- reason_not_rendered required
- user must have permission

Cancel:

- status must not be RENDERED unless correction permission
- cancellation reason required
- user must have permission

---

# 26. Data Integrity Rules

- Do not create duplicate service rendering for same invoice item/service.
- Do not delete service rendering records.
- Do not duplicate invoice items.
- Do not change payment status from rendering page.
- Do not remove charges automatically when not rendered.
- Do not bypass BillingService for financial corrections.
- Do not allow unauthorized department users to render services outside their department.
- Preserve rendered_by and rendered_at.
- Completed records require correction permission for changes.

---

# 27. Tests Required

Add or update tests.

1. Non-consultation service creates service rendering record.
2. Consultation service does not create service rendering record.
3. Investigation service uses investigation workflow, not generic service rendering.
4. Procedure/theatre service uses procedure workflow, not generic rendering if specialized workflow exists.
5. Pending service appears on Service Rendering page.
6. Department user sees services for their department.
7. Unauthorized user cannot start service.
8. Authorized user can start service.
9. Authorized user can mark service rendered.
10. Mark rendered saves rendered_by and rendered_at.
11. Authorized user can mark service not rendered with reason.
12. Not rendered without reason fails.
13. Rendering does not create duplicate invoice item.
14. Rendering does not change payment status.
15. Billed but not rendered warning appears.
16. Visit pathway records service rendering events.
17. Visit Preview shows rendered service.
18. Completed rendered service cannot be edited without correction permission.
19. Service rendering actions are logged.
20. Notifications are sent where configured.

---

# 28. Deliverables

Provide:

1. Gap analysis of current non-consultation service handling.
2. Service rendering model/migration if needed.
3. Automatic creation of rendering records for non-consultation services.
4. Service Rendering page.
5. Filters and status badges.
6. Start/Render/Not Rendered/Cancel actions.
7. Billing relationship display.
8. Visit Pathway integration.
9. Visit Preview integration.
10. Emergency/Admission integration.
11. Permissions/seeders/menu.
12. Notifications/logs integration.
13. Reports foundation.
14. Tests or verification notes.
15. Files modified.
16. Remaining TODOs.

---

# 29. Important Rules

Do not use visit.status to represent laboratory/pharmacy/billing/service departments.

Do not create a parallel billing system.

Do not duplicate invoice items.

Do not mark service as rendered just because it was billed.

Do not remove billing automatically just because service was not rendered.

Do not mix specialized workflows into generic service rendering.

Do not break consultation, investigation, pharmacy, procedure/theatre, emergency, admission, billing, visit preview, or patient pathway workflows.

Now inspect the current UHMS implementation and build a Service Rendering / Service Fulfilment page for all non-consultation services that do not already have a specialized workflow.