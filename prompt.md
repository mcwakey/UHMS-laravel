You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to fix and improve the **Visit Creation + Insurance + Service Pricing** workflow.

Focus only on these issues. Do not refactor unrelated modules.

---

# 1. Main Objectives

Fix and improve:

1. Insurance management directly from the Visit Creation page.
2. Service price setting, which currently redirects with HTTP `302` instead of saving.
3. Cash and Carry visibility and fallback behavior.
4. Selected Services table display.
5. Correct service pricing calculation.
6. Correct `visit_services` pricing snapshot persistence.
7. SPA behavior with no full page reloads.

---

# 2. Insurance Management on Visit Creation

On the Visit Creation page, the user must be able to:

* Add patient insurance.
* Edit existing patient insurance.
* Renew/update expired insurance quickly.
* Select any valid patient insurance after add/edit.
* Select **Cash and Carry** at all times.

## Cash and Carry Rules

`Cash and Carry` must always appear in the insurance/payment selector, even if the patient has valid NHIS, Private, Corporate, or provider-specific insurance.

Rules:

* Cash and Carry is the fallback.
* Cash and Carry uses the service base price.
* If selected insurance is invalid, expired, exhausted, or missing, default to Cash and Carry.
* Adding/editing/renewing insurance must update the list without full page reload.

---

# 3. Service Price Saving 302 Issue

The service price setting form/page is not saving and instead redirects with status `302`.

Find and fix the real cause. Do not suppress the redirect.

Check:

* route method mismatch
* wrong form action or named route
* missing CSRF token
* validation failure
* authorization or middleware redirect
* unauthenticated redirect
* missing controller method
* request not reaching the controller
* form sending `GET` instead of `POST/PATCH/PUT`
* request payload keys not matching validation rules
* Inertia/Vue form not handling validation errors correctly

After fixing:

* service prices must persist correctly.
* validation errors must display clearly.
* authorization errors must be handled properly.
* successful save must not cause an unwanted full page reload.

---

# 4. Selected Services Table

On Visit Creation, the **Selected Services** table must not show:

* Qty
* Unit Price

Show only:

* Service
* Price
* Action

At the bottom, show:

```text
Overall Total: [sum of all selected service total_price]
```

When insurance or selected services change, recalculate and update the table immediately without full reload.

---

# 5. Service Pricing Rules

Every service has a **base price**.

The base price is also the **Cash and Carry price**.

Example:

```text
Base / Cash and Carry = 100
NHIS price = 60
Private price = 120
Corporate price = 160
```

## Pricing Priority

Use this priority order:

```text
Provider-specific insurance service price
    ↓
General insurance type service price
    ↓
Base price / Cash and Carry price
```

Provider-specific insurance price must override the general insurance type price.

## Calculation Rules

### Cash and Carry

```text
unit_price = base_price
insurance_price = null or base_price, depending on existing schema
total_price = unit_price * quantity
insurance_covered = 0
patient_payable = total_price
pricing_source = cash_and_carry
```

### Insurance-Based Pricing

For NHIS, Private, Corporate, or any other insurance type:

```text
unit_price = base_price
insurance_price = resolved insurance price
total_price = insurance_price * quantity
insurance_covered = unit_price - insurance_price
patient_payable = total_price - (total_price * coverage_percentage)
```

Use the project’s existing coverage percentage format consistently:

```text
If coverage is decimal 1.00:
patient_payable = total_price - (total_price * 1.00)

If coverage is percentage 80:
patient_payable = total_price - (total_price * (80 / 100))
```

Example:

```text
base_price = 100
NHIS price = 60
quantity = 1
coverage_percentage = 100%

unit_price = 100
insurance_price = 60
total_price = 60
insurance_covered = 40
patient_payable = 0
```

---

# 6. Visit Service Persistence

Currently, visit creation saves only `unit_price`. This is incomplete.

When selected services are saved to `visit_services`, save a full pricing snapshot:

```text
visit_id
service_id
patient_insurance_id nullable
payment_type / insurance_type
quantity
unit_price
insurance_price
total_price
insurance_covered
patient_payable
pricing_source
```

Use the existing schema where possible. Add a migration only for missing fields.

Important:

* Old visit service records must preserve the price used at the time of visit.
* Future service price changes must not recalculate or change old visit service rows.

---

# 7. Required Backend Services

Use services. Do not put pricing logic directly in controllers.

Create or update:

```text
ServicePricingService
VisitService
InsuranceService
BillingService
```

## ServicePricingService

Implement or fix:

```php
resolvePriceForVisitService(Service $service, ?PatientInsurance $patientInsurance, int $quantity = 1): array
```

It should return:

```php
[
    'unit_price' => 100,
    'insurance_price' => 60,
    'total_price' => 60,
    'insurance_covered' => 40,
    'patient_payable' => 0,
    'pricing_source' => 'provider_specific | insurance_type | cash_and_carry | base_price',
    'insurance_type' => 'cash | nhis | private | corporate',
]
```

## VisitService

Must:

* create the visit.
* attach selected services.
* call `ServicePricingService`.
* save the full pricing snapshot into `visit_services`.

## BillingService

Must:

* create billable lines using the same resolved pricing.
* not recalculate pricing differently from `VisitService`.

---

# 8. Frontend Requirements

On the Visit Creation page:

* Always show Cash and Carry in the insurance selector.
* Show valid patient insurances.
* Show expired patient insurances clearly.
* Provide actions:

  * Add Insurance
  * Edit Insurance
  * Renew / Update Expired Insurance
* After add/edit/renew:

  * update insurance list without full page reload.
  * allow selecting the newly added/updated insurance.
* When insurance changes:

  * recalculate selected service prices.
  * update Selected Services table.
  * update Overall Total.
* When services are selected:

  * resolve price based on selected payment/insurance.
  * show the applied price.
  * do not show Qty and Unit Price columns.

Use SPA behavior. No full reloads.

---

# 9. Data Integrity and Performance Rules

## Data Integrity

* Do not save only `unit_price`.
* Do not duplicate pricing logic across controllers.
* Do not remove existing working insurance pricing logic.
* Do not hardcode NHIS. NHIS is only one insurance type.
* Provider-specific price must override general insurance type price.
* Cash and Carry must never be hidden.

## Performance

* Avoid N+1 queries when loading services, prices, departments, and insurance providers.
* Eager-load relationships needed for pricing.
* Do not repeatedly query prices for each selected service if data can be loaded once.
* Cache service pricing only where safe.
* Keep Visit Creation fast.

---

# 10. Deliverables

Provide:

1. Root cause of the `302` redirect on service price save.
2. Files modified.
3. Any new migrations.
4. Updated models and relationships.
5. `ServicePricingService` implementation.
6. Updated visit creation logic.
7. Updated Selected Services UI.
8. Add/edit/renew insurance modal or component on Visit Creation page.
9. Confirmation that Cash and Carry always appears.
10. Confirmation that `visit_services` stores:

    * `unit_price`
    * `insurance_price`
    * `total_price`
    * `insurance_covered`
    * `patient_payable`
    * `pricing_source`

Now inspect the existing implementation and fix the Visit Creation, insurance selection, service price saving, and `visit_services` pricing persistence.
