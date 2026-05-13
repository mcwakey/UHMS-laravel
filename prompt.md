You are a senior Laravel architect working on **UHMS — Ultimate Hospital Management System**.

We need to fix the **BillingService** starting with the `invoice_items` model/table and make billing calculations consistent everywhere in the system.

Focus only on billing calculation, `invoice_items`, invoice totals, and removal of legacy/unused billing fields. Do not refactor unrelated modules.

---

# 1. Main Objective

Update the billing system so that every billable item in UHMS follows one consistent calculation rule.

This applies everywhere:

* Visit creation
* Consultation services
* Investigation acceptance
* Pharmacy dispensing
* Ward charges
* Procedures
* Scan/X-ray/Lab
* Any other billable service

All billing must go through `BillingService`.

---

# 2. Required Invoice Item Fields

The `invoice_items` model/table should use these fields:

```text
id
invoice_id
visit_id
patient_id
department_id nullable
service_id nullable

source_type nullable
source_id nullable

description
quantity

cash_price
insurance_price nullable
selected_price

insurance_covered
discount_amount

patient_payable
paid_amount
balance

payment_status

patient_insurance_id nullable
pricing_source nullable
insurance_type nullable

created_by nullable
created_at
updated_at
```

---

# 3. Meaning of Each Price Field

## `cash_price`

This is the normal service base price.

For Cash and Carry:

```text
cash_price = service base price
```

For insurance patients:

```text
cash_price = service base price
```

So `cash_price` must always represent the original/base/cash price of the item.

---

## `insurance_price`

This is the price selected based on the patient’s insurance.

If the patient is on NHIS, Private, Corporate, or provider-specific insurance:

```text
insurance_price = resolved insurance price
```

If patient is Cash and Carry:

```text
insurance_price = null
```

or keep it equal to `cash_price` only if the current database structure requires it.

---

## `selected_price`

This is the actual price being used for billing before discounts.

Rules:

```text
If Cash and Carry:
selected_price = cash_price

If insurance applies:
selected_price = insurance_price
```

If insurance price does not exist:

```text
selected_price = cash_price
pricing_source = base_price
```

---

# 4. Correct Billing Calculations

Use these formulas everywhere.

## Insurance Covered

```text
insurance_covered = cash_price - insurance_price
```

Important:

* `insurance_covered` is not paid amount.
* Do not assume insurance coverage is a payment.
* It is only the difference between the cash/base price and the insurance price.
* If Cash and Carry:

```text
insurance_covered = 0
```

* If insurance price is null:

```text
insurance_covered = 0
```

Example:

```text
cash_price = 100
insurance_price = 60

insurance_covered = 100 - 60 = 40
```

---

## Patient Payable

```text
patient_payable = (selected_price * quantity) - discount_amount
```

If no discount:

```text
patient_payable = selected_price * quantity
```

Important:

* `patient_payable` should NOT be reduced by `insurance_covered`.
* `patient_payable` should NOT treat `insurance_covered` as paid.
* `patient_payable` is the amount the patient is expected to pay after manual discount.

Example without discount:

```text
selected_price = 60
quantity = 2

patient_payable = 60 * 2 = 120
```

Example with discount:

```text
selected_price = 60
quantity = 2
discount_amount = 20

patient_payable = (60 * 2) - 20 = 100
```

---

## Paid Amount

```text
paid_amount = sum(payment allocations for this invoice item)
```

Important:

* `paid_amount` only comes from actual payment.
* Do not set `paid_amount = insurance_covered`.
* Do not set `paid_amount = discount_amount`.
* Do not set `paid_amount = selected_price`.

---

## Balance

```text
balance = patient_payable - paid_amount
```

Example:

```text
patient_payable = 120
paid_amount = 50

balance = 120 - 50 = 70
```

---

## Discount Amount

`discount_amount` must be entered manually by an authorized user.

Rules:

* Do not auto-set discount amount from insurance.
* Do not calculate discount as `insurance_covered`.
* Do not calculate discount as `cash_price - selected_price`.
* Discount must be stored separately in:

```text
discount_amount
```

When discount changes, recalculate:

```text
patient_payable = (selected_price * quantity) - discount_amount
balance = patient_payable - paid_amount
```

Validation:

* discount_amount must be >= 0
* discount_amount must not exceed `selected_price * quantity`
* only authorized users can apply discounts

---

# 5. Payment Status Calculation

Update `payment_status` based on `balance` and `paid_amount`.

Rules:

```text
If patient_payable <= 0:
payment_status = PAID

If paid_amount <= 0 and balance > 0:
payment_status = UNPAID

If paid_amount > 0 and balance > 0:
payment_status = PARTIALLY_PAID

If balance <= 0:
payment_status = PAID
```

Supported statuses:

```text
UNPAID
PARTIALLY_PAID
PAID
WAIVED
CANCELLED
VOIDED
```

---

# 6. BillingService Requirements

Update `BillingService` so every invoice item is created using the correct formulas.

When creating an invoice item:

1. Resolve `cash_price`.
2. Resolve `insurance_price` if insurance applies.
3. Resolve `selected_price`.
4. Set `quantity`.
5. Set `discount_amount = 0` by default unless provided manually.
6. Calculate:

```text
insurance_covered = cash_price - insurance_price
patient_payable = (selected_price * quantity) - discount_amount
paid_amount = 0
balance = patient_payable
payment_status = UNPAID or PAID if patient_payable is 0
```

Do not set `paid_amount` from insurance.

Do not set `discount_amount` from insurance.

---

# 7. ServicePricingService Requirements

Update `ServicePricingService` to return pricing values clearly.

Required response:

```php
[
    'cash_price' => 100,
    'insurance_price' => 60,
    'selected_price' => 60,
    'pricing_source' => 'provider_specific | insurance_type | cash_and_carry | base_price',
    'insurance_type' => 'cash | nhis | private | corporate | other',
]
```

It should not calculate paid amount.

It should not calculate discount.

It should not calculate balance.

Those are billing/payment responsibilities.

---

# 8. Discount Workflow

Add or fix a way to manually apply discount on invoice items.

Discount should be applied through a controlled backend method, not directly from frontend calculation.

Suggested method:

```php
BillingService::applyDiscount(InvoiceItem $item, float $discountAmount, User $user): InvoiceItem
```

This method must:

1. Validate user permission.
2. Validate discount amount.
3. Save `discount_amount`.
4. Recalculate `patient_payable`.
5. Recalculate `balance`.
6. Update `payment_status`.
7. Recalculate parent invoice totals.
8. Log who applied the discount.

---

# 9. Invoice Totals

Update invoice totals based on invoice items.

Recommended calculations:

```text
subtotal = sum(selected_price * quantity)
total_discount = sum(discount_amount)
insurance_total = sum(insurance_covered)
patient_total = sum(patient_payable)
paid_amount = sum(invoice_items.paid_amount)
outstanding_amount = sum(invoice_items.balance)
```

Important:

* `insurance_total` is informational.
* It is not paid amount.
* Actual payment comes only from `payments` and `payment_allocations`.

---

# 10. Remove Legacy or Unused Fields

Remove or stop using legacy fields that conflict with the new billing logic.

Search and remove/replace usage of:

```text
unit_price
is_nhis_covered
nhis_approved_amount
approved_amount
insurance_paid
insurance_payment
relevance
```

Do not drop columns blindly if existing code still depends on them.

Use safe cleanup:

1. Stop writing to legacy fields.
2. Stop reading from legacy fields.
3. Replace views/controllers/services with new fields.
4. Add migration to drop legacy columns only when safe.
5. If dropping now, ensure migrations and code are consistent.

---

# 11. Invoice Item Model

Update `InvoiceItem` model:

* `$fillable`
* casts for money fields
* relationships
* helper methods if useful

Suggested casts:

```php
protected $casts = [
    'quantity' => 'integer',
    'cash_price' => 'decimal:2',
    'insurance_price' => 'decimal:2',
    'selected_price' => 'decimal:2',
    'insurance_covered' => 'decimal:2',
    'discount_amount' => 'decimal:2',
    'patient_payable' => 'decimal:2',
    'paid_amount' => 'decimal:2',
    'balance' => 'decimal:2',
];
```

Relationships:

```php
invoice()
visit()
patient()
department()
service()
patientInsurance()
creator()
paymentAllocations()
```

---

# 13. Data Integrity Rules

* `insurance_covered` must never be treated as payment.
* `discount_amount` must never be auto-filled from insurance.
* `paid_amount` must only come from payment allocations.
* `balance` must always equal `patient_payable - paid_amount`.
* `patient_payable` must always equal `(selected_price * quantity) - discount_amount`.
* Old invoice items must preserve their historical pricing.
* New billing logic must be consistent everywhere.

---

# 14. Validation Rules

When creating or updating invoice items:

* quantity must be greater than 0
* selected_price must be >= 0
* cash_price must be >= 0
* insurance_price must be nullable and >= 0
* discount_amount must be >= 0
* discount_amount must not exceed `selected_price * quantity`
* paid_amount must not be manually edited except through payment allocation logic

---

# 15. Tests / Verification

Add or update tests for these scenarios:

## Cash and Carry

```text
cash_price = 100
selected_price = 100
quantity = 1
discount = 0
patient_payable = 100
paid_amount = 0
balance = 100
insurance_covered = 0
```

## Insurance

```text
cash_price = 100
insurance_price = 60
selected_price = 60
quantity = 1
discount = 0
insurance_covered = 40
patient_payable = 60
paid_amount = 0
balance = 60
```

## Insurance with Discount

```text
cash_price = 100
insurance_price = 60
selected_price = 60
quantity = 1
discount = 10
insurance_covered = 40
patient_payable = 50
paid_amount = 0
balance = 50
```

## Partial Payment

```text
patient_payable = 60
paid_amount = 20
balance = 40
payment_status = PARTIALLY_PAID
```

## Full Payment

```text
patient_payable = 60
paid_amount = 60
balance = 0
payment_status = PAID
```

---

# 16. Deliverables

Provide:

1. Updated `invoice_items` migration or cleanup migration.
2. Updated `InvoiceItem` model.
3. Updated `ServicePricingService`.
4. Updated `BillingService`.
5. Updated `InvoiceService` totals recalculation.
6. Updated `PaymentService` if needed.
7. Updated invoice views.
8. Discount application logic.
9. Removal or deactivation of legacy/unused fields.
10. Tests or verification notes proving calculations are correct.

---

# 17. Important Rules

Do not assume insurance coverage is paid amount.

Do not make discount equal to insurance covered.

Do not calculate patient payable from cash price when insurance selected.

Do not use coverage percentage.

Do not use NHIS-specific legacy fields.

Do not manually edit paid amount except through payments.

Do not bypass `BillingService`.

Do not bypass `ServicePricingService`.

Do not break historical invoice data.

Now inspect the current billing implementation and update `invoice_items`, pricing, discount, payment, and invoice total calculations to follow this model everywhere in UHMS.
