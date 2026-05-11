You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to redesign the billing system around a **single invoice per visit** model with centralized, insurance-aware pricing.

Focus only on billing, invoice, payments, insurance pricing consistency, and related department billing flows. Do not refactor unrelated modules.

---

# 1. Main Objective

Implement this billing model:

```text
One Visit
    ↓
One Main Invoice
    ↓
Many Invoice Items
    ↓
Many Payments
    ↓
Payments allocated to specific invoice items
```

The goal is to avoid creating many separate invoices for one patient visit.

Every billable action during a visit must add items to the same visit invoice.

---

# 2. Core Billing Rule

For every visit:

```text
1 Visit = 1 Invoice
```

The invoice should be created automatically when the visit starts or when the first billable item is added.

There must never be multiple active invoices for the same visit.

Enforce this with logic and, if possible, a unique database constraint on:

```text
invoices.visit_id
```

---

# 3. Department Billing Behavior

All departments must add billable items to the same visit invoice.

Examples:

```text
Visit Invoice
├── Consultation service
├── Investigation service
├── Pharmacy item
├── Procedure
├── Ward charge
├── Scan
├── X-ray
└── Other billable services
```

Do not create separate invoices for:

* consultation
* lab
* scan
* x-ray
* pharmacy
* ward
* procedure

Instead, create invoice items under the visit’s main invoice.

---

# 4. Centralized Billing Service Rule

No module, controller, or Vue component should create invoice items directly.

Every billable action must go through:

```php
BillingService::addItemToVisitInvoice(...)
```

`BillingService` must call:

```php
ServicePricingService::resolvePriceForVisitService(...)
```

and, where relevant:

```php
InsuranceService::evaluateCoverage(...)
```

This ensures consistent pricing across:

* visit creation
* triage/consultation billing
* investigation acceptance
* pharmacy dispensing
* ward charges
* procedures
* scans
* x-rays

---

# 5. Insurance Pricing Consistency

Currently, visit creation respects insurance pricing, but investigation/pharmacy billing does not always pick the patient’s insurance price.

Fix this.

All invoice items, regardless of source department, must use the same insurance-aware pricing logic.

Pricing priority:

```text
Provider-specific insurance service price
    ↓
General insurance type service price
    ↓
Base price / Cash and Carry price
```

Cash and Carry must use the service base price.

Provider-specific price must override general insurance type price.

Do not hardcode NHIS. NHIS is only one insurance type.

---

# 6. Invoice Table Design

Use existing tables if already present, but adjust them if needed.

Recommended `invoices` fields:

```text
id
invoice_number
visit_id
patient_id
patient_insurance_id nullable
status
subtotal
insurance_total
patient_total
paid_amount
outstanding_amount
created_by nullable
created_at
updated_at
```

Rules:

* `visit_id` must be unique for active visit invoice.
* invoice totals should be recalculated whenever invoice items or payments change.
* invoice status should reflect item/payment status.

Recommended invoice statuses:

```text
DRAFT
OPEN
PARTIALLY_PAID
PAID
CANCELLED
VOIDED
```

---

# 7. Invoice Item Table Design

Recommended `invoice_items` fields:

```text
id
invoice_id
visit_id
patient_id
department_id nullable
service_id nullable

source_type
source_id nullable

description
quantity

unit_price
insurance_price
total_price
insurance_covered
patient_payable

paid_amount
balance
payment_status

patient_insurance_id nullable
pricing_source
insurance_type nullable

created_by nullable
created_at
updated_at
```

## Important Field Meaning

```text
unit_price = base price / Cash and Carry price
insurance_price = applied insurance price, if insurance applies
total_price = insurance_price * quantity for insured patients, or unit_price * quantity for cash patients
insurance_covered = difference or covered portion depending on existing system rules
patient_payable = amount patient must pay personally
paid_amount = amount paid against this item
balance = patient_payable - paid_amount
```

---

# 8. Source Tracking and Duplicate Prevention

Every invoice item must know where it came from.

Use:

```text
source_type
source_id
```

Examples:

```text
source_type = visit_service
source_id = visit_services.id

source_type = investigation_request_item
source_id = investigation_request_items.id

source_type = prescription_item
source_id = prescription_items.id

source_type = ward_charge
source_id = ward_charges.id
```

Rules:

* Prevent duplicate billing for the same `source_type + source_id`.
* If an investigation item has already been billed, do not bill it again.
* If a pharmacy item has already been billed, do not bill it again.
* If duplicate billing is attempted, return a clear error.

---

# 9. Payment Model

Patients must be able to make payments while the visit is still ongoing.

Payments can clear:

* full invoice
* selected invoice items
* part of an invoice item
* multiple invoice items at once

Use two levels:

## payments

```text
id
invoice_id
visit_id
patient_id
amount
payment_method
reference nullable
received_by
paid_at
notes nullable
created_at
updated_at
```

## payment_allocations

```text
id
payment_id
invoice_item_id
amount
created_at
updated_at
```

`payment_allocations` is required so the system can know exactly which bill lines are paid, partially paid, or still owed.

---

# 10. Line-Level Payment Status

Each invoice item must have its own payment status.

Recommended statuses:

```text
UNPAID
PARTIALLY_PAID
PAID
WAIVED
CANCELLED
VOIDED
```

When a payment is made:

* allocate payment to selected invoice items.
* update each item’s `paid_amount`.
* update each item’s `balance`.
* update each item’s `payment_status`.
* recalculate invoice totals.
* update invoice status.

Example:

```text
Consultation fee      PAID
Full Blood Count      PAID
Malaria Test          UNPAID
Pharmacy Drugs        PARTIALLY_PAID
X-ray                 UNPAID
```

---

# 11. Payment Allocation Rules

When cashier receives payment:

User should be able to:

1. Pay selected invoice lines.
2. Pay full outstanding balance.
3. Pay partial amount against one or more lines.

Rules:

* Do not allocate more than the balance of a line.
* Do not accept negative or zero payments.
* If payment amount exceeds selected line balances, reject or handle as advance only if existing system supports advances.
* If no advance system exists, reject excess payment.
* Paid invoice items should not be casually deleted.
* Cancelling or reversing paid lines should require a reversal/refund workflow later.

---

# 12. Insurance and Patient Payable Formula

Use existing project coverage format consistently.

For insurance-based pricing:

```text
unit_price = service base price
insurance_price = resolved insurance price
total_price = insurance_price * quantity
insurance_covered = unit_price - insurance_price
patient_payable = total_price - (total_price * coverage_percentage)
```

Examples:

```text
Base price = 100
NHIS price = 60
Quantity = 1
Coverage = 100%

unit_price = 100
insurance_price = 60
total_price = 60
insurance_covered = 40
patient_payable = 0
```

```text
Base price = 100
NHIS price = 60
Quantity = 1
Coverage = 80%

unit_price = 100
insurance_price = 60
total_price = 60
insurance_covered = 40
patient_payable = 12
```

For Cash and Carry:

```text
unit_price = base_price
insurance_price = null or base_price depending on existing schema
total_price = unit_price * quantity
insurance_covered = 0
patient_payable = total_price
```

---

# 13. Insurance Limits and Fallback

When adding an invoice item:

1. Resolve the patient’s selected visit insurance.
2. Check whether insurance is valid.
3. Check per-visit, monthly, yearly, and monthly visit-count limits.
4. If insurance can cover the item, apply insurance pricing.
5. If insurance is exhausted or invalid, fallback to Cash and Carry for this and future billable items.

Rules:

* Do not recalculate old invoice items.
* Do not change previous invoice items when insurance becomes exhausted.
* Only new/future invoice items should fallback to Cash and Carry.
* Store pricing snapshot on every invoice item.

---

# 14. Visit Creation Flow

When a visit is created:

1. Create visit.
2. Determine selected insurance or fallback to Cash and Carry.
3. Create or get the visit invoice.
4. Add selected visit services as invoice items through `BillingService`.
5. Save pricing snapshot on invoice items.
6. Do not create another invoice for the same visit.

---

# 15. Investigation Acceptance Flow

When investigation staff accepts selected requested test/service items:

1. Select items from request data table.
2. Accept only selected items.
3. Call `BillingService::addItemToVisitInvoice(...)` for each selected item.
4. Add items to the same visit invoice.
5. Use insurance-aware pricing.
6. Prevent duplicate billing.
7. Do not bill unselected items.

---

# 16. Pharmacy Dispensing Flow

When pharmacy dispenses drugs/items:

1. Bill only dispensed items.
2. Add items to the same visit invoice.
3. Use insurance-aware pricing or pharmacy-specific pricing rules through the same pricing service.
4. Prevent duplicate billing for already billed prescription/dispensed items.

---

# 17. Cashier Invoice View

The cashier invoice screen must show one invoice per visit.

Display:

* invoice number
* patient
* visit number
* visit status
* active insurance/payment type
* invoice total
* paid amount
* outstanding amount

Show invoice items grouped by source/department:

```text
Consultation
- General Consultation — PAID

Investigations
- Full Blood Count — UNPAID
- Malaria Test — PAID

Pharmacy
- Paracetamol — PARTIALLY_PAID
```

Each line must show:

* description/service
* department/source
* total price
* patient payable
* paid amount
* balance
* payment status

---

# 18. Cashier Payment UI

The cashier must be able to:

* select invoice items to pay.
* enter payment amount.
* choose payment method.
* allocate payment to selected lines.
* see updated paid/unpaid status immediately.
* print receipt for payment.

Do not require the visit to be completed before payment.

---

# 19. Data Integrity Rules

* One active invoice per visit.
* All billable departments add to the same invoice.
* All invoice items store pricing snapshots.
* Do not duplicate invoice items for the same source.
* Do not delete paid invoice items directly.
* Do not recalculate old invoice items after price changes.
* Do not bypass `BillingService`.
* Do not bypass `ServicePricingService`.
* Do not bypass `InsuranceService` where insurance applies.
* Do not create invoices directly in controllers.

---

# 20. Backend Services

Create or update:

```text
InvoiceService
BillingService
ServicePricingService
InsuranceService
PaymentService
```

## InvoiceService

Responsible for:

* creating/getting invoice for visit.
* enforcing one invoice per visit.
* recalculating totals.
* updating invoice status.

Required methods:

```php
getOrCreateVisitInvoice(Visit $visit): Invoice
recalculateTotals(Invoice $invoice): Invoice
updateStatus(Invoice $invoice): Invoice
```

## BillingService

Responsible for:

* adding billable items to visit invoice.
* calling pricing service.
* preventing duplicates.
* updating invoice totals.

Required method:

```php
addItemToVisitInvoice(
    Visit $visit,
    Service $service,
    string $sourceType,
    ?int $sourceId = null,
    int $quantity = 1,
    ?Department $department = null,
    ?User $createdBy = null
): InvoiceItem
```

## ServicePricingService

Responsible for:

* resolving base price.
* resolving provider-specific price.
* resolving insurance type price.
* calculating total and patient payable.

## InsuranceService

Responsible for:

* checking validity.
* checking limits.
* applying fallback to Cash and Carry.
* returning active pricing context.

## PaymentService

Responsible for:

* receiving payments.
* allocating payments to invoice items.
* updating line statuses.
* updating invoice totals/status.

Required method:

```php
recordPayment(
    Invoice $invoice,
    array $allocations,
    string $paymentMethod,
    ?string $reference,
    User $receivedBy
): Payment
```

---

# 21. Validation Rules

## Add Invoice Item

* visit must exist.
* service must exist.
* source_type required.
* source_id required where applicable.
* duplicate source billing must be prevented.
* quantity must be greater than zero.

## Record Payment

* invoice must exist.
* allocations required.
* each invoice item must belong to invoice.
* amount must be greater than zero.
* allocation amount must not exceed item balance.
* total allocation must equal payment amount.
* paid/voided/cancelled items cannot receive payment.

---

# 22. Performance Rules

* Eager-load invoice items with service, department, source where needed.
* Avoid N+1 queries in cashier invoice view.
* Use database indexes:

  * invoices.visit_id
  * invoices.patient_id
  * invoice_items.invoice_id
  * invoice_items.visit_id
  * invoice_items.patient_id
  * invoice_items.source_type
  * invoice_items.source_id
  * payments.invoice_id
  * payment_allocations.payment_id
  * payment_allocations.invoice_item_id
* Recalculate totals efficiently.
* Do not load all historical invoices unnecessarily.
* Paginate invoice/payment history where needed.

---

# 23. Deliverables

Provide:

1. Root cause of inconsistent pricing across investigation/pharmacy if found.
2. Files modified.
3. New migrations if needed.
4. Updated models and relationships.
5. `InvoiceService` implementation.
6. `BillingService` implementation/update.
7. `PaymentService` implementation.
8. Updated visit creation billing flow.
9. Updated investigation acceptance billing flow.
10. Updated pharmacy dispensing billing flow.
11. Cashier invoice view showing line payment statuses.
12. Payment allocation UI/backend.
13. Confirmation that there is only one invoice per visit.
14. Confirmation that insurance-aware pricing is used across all departments.
15. Confirmation that selected/accepted investigation items only are billed.
16. Confirmation that invoice items distinguish paid, partially paid, and unpaid lines.

---

# 24. Important Rules

Do not generate multiple invoices for the same visit.

Do not bill all investigation request items unless all were selected and accepted.

Do not bill undispensed pharmacy items.

Do not create invoice items directly from controllers.

Do not bypass insurance pricing in investigation or pharmacy.

Do not recalculate old invoice items when prices change.

Do not delete paid invoice items directly.

Do not allow overpayment unless an advance payment system already exists.

Now inspect the existing billing, visit, investigation, pharmacy, insurance, and payment implementation and redesign it carefully around the single visit invoice model.
