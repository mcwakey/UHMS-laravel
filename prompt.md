You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to implement **Product Pricing** using the same insurance-aware pricing logic already used for Services.

Focus only on product pricing, insurance-aware billing, product price configuration UI, and integration with BillingService.

Do not refactor unrelated modules.

---

# 1. Main Objective

Products must support pricing the same way Services support pricing.

Products are physical items such as:

```text
Paracetamol
Ceftriaxone
Gloves
Syringe
Malaria RDT Kit
Sutures
Gauze
X-ray Film
```

Some products can be billable, especially:

* pharmacy drugs
* billable consumables
* procedure consumables
* ward consumables
* investigation consumables if configured as billable

When a product is billed, its price must be selected based on the patient’s insurance/payment type.

---

# 2. Core Rule

Product pricing should follow this priority:

```text
Insurance provider-specific product price
    ↓
General insurance type product price
    ↓
Base price / Cash and Carry price
```

This is the same idea as service pricing.

---

# 3. Product Base Price

Every product should have a base price.

The base price is also the Cash and Carry price.

Example:

```text
Product: Paracetamol 500mg

Base / Cash and Carry price = 5
NHIS price = 3
Private price = 6
Corporate price = 4
Provider-specific price for ABC Insurance = 2.5
```

If patient is Cash and Carry:

```text
selected_price = base_price
```

If patient has NHIS:

```text
selected_price = NHIS product price
```

If patient has ABC Insurance and ABC has a specific product price:

```text
selected_price = ABC provider-specific product price
```

---

# 4. Product Price Types

Create product pricing support for:

```text
cash / base price
nhis price
private insurance price
corporate insurance price
other insurance type price
insurance provider-specific price
```

Do not hardcode NHIS only.

NHIS is only one insurance type.

---

# 5. Suggested Database Design

Use existing tables if available. Otherwise create clean tables.

## products

Add or confirm field:

```text
base_price nullable
```

This is the Cash and Carry price.

If the system already has `selling_price`, decide whether to rename/use it as `base_price`.

---

## product_insurance_prices

Create a table for general insurance type product prices:

```text
id
product_id
insurance_type
price
is_active
created_at
updated_at
```

Example records:

```text
product_id = 1
insurance_type = nhis
price = 3

product_id = 1
insurance_type = private
price = 6

product_id = 1
insurance_type = corporate
price = 4
```

---

## product_provider_prices

Create a table for provider-specific product prices:

```text
id
product_id
insurance_provider_id
price
is_active
created_at
updated_at
```

Example:

```text
product_id = 1
insurance_provider_id = ABC Insurance
price = 2.5
```

Provider-specific prices must override general insurance type prices.

---

# 6. Pricing Resolution Logic

Create or update:

```text
ProductPricingService
```

Required method:

```php
resolvePriceForProduct(
    Product $product,
    ?PatientInsurance $patientInsurance = null,
    int|float $quantity = 1
): array
```

Return:

```php
[
    'cash_price' => 5,
    'insurance_price' => 3,
    'selected_price' => 3,
    'quantity' => 2,
    'total_price' => 6,
    'patient_payable' => 6,
    'pricing_source' => 'provider_specific | insurance_type | cash_and_carry | base_price',
    'insurance_type' => 'cash | nhis | private | corporate | other',
    'insurance_provider_id' => null,
]
```

Rules:

## Cash and Carry

```text
cash_price = product base price
insurance_price = null
selected_price = cash_price
total_price = selected_price * quantity
patient_payable = total_price
pricing_source = cash_and_carry
```

## Insurance

```text
cash_price = product base price
insurance_price = resolved insurance product price
selected_price = insurance_price
total_price = selected_price * quantity
patient_payable = total_price
pricing_source = provider_specific or insurance_type
```

## Fallback

If no insurance price exists:

```text
selected_price = cash_price
pricing_source = base_price
```

---

# 7. BillingService Integration

Update `BillingService` so it can bill both services and products.

Current billing likely supports:

```php
BillingService::addItemToVisitInvoice(Visit $visit, Service $service, ...)
```

Add support for products, for example:

```php
BillingService::addProductToVisitInvoice(
    Visit $visit,
    Product $product,
    string $sourceType,
    ?int $sourceId = null,
    int|float $quantity = 1,
    ?Department $department = null,
    ?User $createdBy = null
): InvoiceItem
```

This method must:

1. Get or create the visit invoice.
2. Resolve patient insurance from the visit.
3. Call `ProductPricingService`.
4. Create invoice item.
5. Save pricing snapshot.
6. Prevent duplicate billing by `source_type + source_id` where applicable.
7. Recalculate invoice totals.

---

# 8. Invoice Item Fields for Product Billing

When a product is billed, save the same invoice item fields:

```text
invoice_id
visit_id
patient_id
department_id nullable
service_id nullable
product_id nullable
source_type
source_id nullable
description
quantity
cash_price
insurance_price
selected_price
insurance_covered
discount_amount
patient_payable
paid_amount
balance
payment_status
patient_insurance_id nullable
pricing_source
insurance_type nullable
created_by nullable
```

Important:

* Product bill lines should use `product_id`.
* Service bill lines should use `service_id`.
* An invoice item can be service-based or product-based.
* Do not force every invoice item to have `service_id`.

---

# 9. Calculation Rules

Use the same billing formulas everywhere.

```text
insurance_covered = cash_price - insurance_price
```

If no insurance applies:

```text
insurance_covered = 0
```

```text
patient_payable = (selected_price * quantity) - discount_amount
```

```text
balance = patient_payable - paid_amount
```

Important:

* insurance_covered is not payment
* discount is manual
* paid_amount only comes from real payments
* product pricing must not use coverage percentage

---

# 10. Product Pricing UI

Under Store / Procurement → Products, add a pricing configuration section.

On Product Details page, include tabs:

```text
Details
Department Availability
Stock Balances
Pricing
Supplier History
```

## Pricing Tab

Show:

```text
Base / Cash and Carry Price
Insurance Type Prices
Provider-Specific Prices
```

### Base Price

Allow Store/Admin to set:

```text
base_price
```

### Insurance Type Prices

Allow setting prices by insurance type:

```text
NHIS
Private
Corporate
Other configured insurance types
```

### Provider-Specific Prices

Allow setting prices for a specific insurance provider:

```text
Insurance Provider
Price
Status
```

Provider-specific price overrides general insurance type price.

---

# 11. Pharmacy Dispensing Integration

When pharmacy dispenses a drug:

1. Confirm stock from Pharmacy stock location.
2. Create stock movement OUT.
3. Add dispensed product to visit invoice through `BillingService::addProductToVisitInvoice`.
4. Resolve product price based on patient insurance.
5. Save product invoice item with pricing snapshot.

Important:

* Pharmacy must not manually decide product price.
* Price must come from `ProductPricingService`.
* Cash and Carry uses product base price.
* Insurance uses product insurance price.
* Provider-specific insurance price overrides type price.

---

# 12. Consumable Billing Rule

Some consumables may be stock-only, while others may be billable.

Add or confirm product field:

```text
is_billable
```

Rules:

* If `is_billable = false`, product consumption only affects stock.
* If `is_billable = true`, product consumption can create an invoice item.
* Billing must still go through `BillingService`.
* Pricing must go through `ProductPricingService`.

Examples:

```text
Gloves used internally may be non-billable.
Sutures used in theatre may be billable.
Medication dispensed by pharmacy is billable.
```

---

# 13. Investigation Consumable Billing

When investigation result entry consumes products:

* deduct stock from investigation department stock location
* if consumed product is billable, add product invoice item
* price product according to patient insurance
* do not bill non-billable consumables

---

# 14. Procedure Consumable Billing

When procedure/theatre consumes products:

* deduct stock from procedure/theatre stock location
* if consumed product is billable, add product invoice item
* price product according to patient insurance
* do not bill non-billable consumables

---

# 15. Validation Rules

## Product Pricing

* base_price must be nullable or numeric >= 0
* insurance price must be numeric >= 0
* product_id required
* insurance_type required for type price
* insurance_provider_id required for provider price
* prevent duplicate active price for same product + insurance_type
* prevent duplicate active price for same product + insurance_provider_id

## Product Billing

* product must exist
* product must be active
* product must be billable if creating invoice item
* quantity must be greater than zero
* patient/visit must exist
* pricing must resolve successfully
* selected_price must be >= 0

---

# 16. Permissions

Add or verify permissions:

```text
product.pricing.view
product.pricing.manage
product.provider-pricing.manage
product.insurance-pricing.manage
```

Only Store/Admin or authorized billing/product managers should set product prices.

Pharmacy can view prices where needed but should not manage global product pricing unless permitted.

---

# 17. Data Integrity Rules

* Product base price is Cash and Carry price.
* Product insurance type price overrides base price.
* Product provider-specific price overrides insurance type price.
* Product invoice item must store pricing snapshot.
* Do not recalculate old invoice items when product price changes.
* Do not hardcode NHIS.
* Do not use coverage percentage.
* Do not treat insurance_covered as payment.
* Do not allow pharmacy/lab/theatre to bypass pricing service.
* Do not create product invoice items manually from controllers.

---

# 18. Performance Rules

* Eager-load product insurance prices where needed.
* Avoid N+1 queries during dispensing.
* Cache insurance price lookup where safe.
* Do not load all provider-specific prices unless required.
* Product pricing lookup should be fast during billing.

---

# 19. Tests / Verification

Add or update tests for:

1. Product base price is used for Cash and Carry.
2. Product insurance type price is used for matching insurance type.
3. Provider-specific product price overrides insurance type price.
4. Product falls back to base price if no insurance price exists.
5. Pharmacy dispensing bills product with correct insurance-aware price.
6. Billable consumable creates invoice item.
7. Non-billable consumable does not create invoice item.
8. Product invoice item stores pricing snapshot.
9. Changing product price does not change old invoice items.
10. Duplicate active insurance price is prevented.
11. Duplicate active provider price is prevented.

---

# 20. Deliverables

Provide:

1. New/updated migrations.
2. Updated Product model.
3. Updated InvoiceItem model if needed.
4. ProductPricingService implementation.
5. Updated BillingService product billing method.
6. Product pricing UI.
7. Product insurance type pricing.
8. Product provider-specific pricing.
9. Pharmacy dispensing integration.
10. Investigation/procedure consumable billing integration where applicable.
11. Permissions added.
12. Tests or verification notes.
13. Files modified.
14. Remaining TODOs if any.

---

# 21. Important Rules

Do not hardcode NHIS only.

Do not use coverage percentage.

Do not bypass ProductPricingService.

Do not bypass BillingService.

Do not let old invoice items recalculate from new product prices.

Do not force product invoice items to have service_id.

Do not bill non-billable consumables.

Do not allow unauthorized users to manage product prices.

Now inspect the current UHMS implementation and add insurance-aware Product Pricing similar to Service Pricing.
