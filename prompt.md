You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to fix several issues around **modules loading, pharmacy drug saving, CCC code storage, insurance pricing simplification, invoice_items cleanup, invoice display, and removal of visit_services dependency**.

Focus only on these issues. Do not refactor unrelated modules.

---

# 1. Main Problems to Fix

Fix the following:

1. Modules are not loading.
2. Pharmacy cannot add more than one drug.
3. Adding drugs redirects with HTTP `302` and shows no useful error.
4. CCC code is not being saved anywhere.
5. Coverage should be removed from insurance and price calculation.
6. `invoice_items` model/table needs cleanup.
7. Remove fields no longer needed:

   * `unit_price`
   * `is_nhis_covered`
   * `nhis_approved_amount`
   * any NHIS-specific/relevance fields no longer required
8. Use `selected_price` instead of `unit_price` in views.
9. On invoice view page, remove:

   * Qty column
   * Cash Price column
10. Remove `visit_services` table usage from the project.
11. Display the visit’s invoice instead of visit services.

---

# 2. Fix Modules Not Loading

Investigate why modules are not loading.

Check:

* `modules` table
* module seeders
* module middleware
* `ModuleService`
* sidebar module filtering
* route middleware
* permission checks
* cache issues
* config cache
* database records
* frontend props/shared Inertia data

Required behavior:

* Core modules must always load.
* Enabled optional modules must load.
* Disabled modules must not load.
* Sidebar must show enabled modules according to user permission.
* Module state should not break route loading.

After fixing, run or recommend:

```bash
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

If module state is cached, refresh cache after module updates.

---

# 3. Fix Pharmacy Drug Saving Issue

Currently, the user cannot add more than one drug. The save request redirects with status `302`, no useful error appears, and the drug does not save.

Investigate and fix.

Check:

* route method
* form method
* form action
* CSRF token
* validation rules
* request payload
* controller method
* service method
* model `$fillable`
* database required fields
* unique constraints
* quantity calculation
* stock availability calculation
* duplicate drug prevention logic
* whether the system wrongly blocks adding a second drug
* whether validation errors are being lost after redirect
* whether Inertia/Vue form is not displaying errors
* whether the save button submits correctly
* whether modal/form is being dismissed too early

Required behavior:

* User can add multiple drugs where allowed.
* Each drug must save properly.
* If duplicate drug is not allowed, show clear validation error.
* If stock is insufficient, show clear validation error.
* If quantity is invalid, show clear validation error.
* No silent `302` redirect without visible errors.
* Drug list updates without full page reload.

Do not just suppress the redirect. Find and fix the real cause.

---

# 4. CCC Code Storage

There is currently nowhere saving the CCC code.

Add support for CCC code storage.

First inspect the existing domain model and determine where CCC code belongs.

Likely places:

* patient visit insurance snapshot
* claim/verification record

Preferred approach:

If CCC code is related to the patient’s insurance membership, store it on `patient_insurances`.

Add field if missing:

```text
ccc_code nullable
```

If the project already has a claim/verification table, ensure CCC code is also copied/snapshotted where needed.

Rules:

* CCC code must be saved when adding/editing patient insurance.
* CCC code must be visible where insurance details are displayed.
* CCC code must be available during visit creation if that insurance is selected.
* CCC code must not be required for Cash and Carry.
* CCC code should not be hardcoded to NHIS only unless the current business rule requires it.

---

# 6. invoice_items Model/Table Cleanup

Update `invoice_items` to match the simplified pricing model.

Remove or stop using these fields:

```text
unit_price
is_nhis_covered
nhis_approved_amount
coverage_percentage
approved_amount
```

Also remove any NHIS-specific fields or logic that no longer applies, unless still needed elsewhere for claims.

Use:

```text
selected_price
total_price
patient_payable
patient_insurance_id nullable
pricing_source
insurance_type nullable
payment_status
paid_amount
balance
```

Recommended `invoice_items` structure:

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
quantity nullable/default 1

insurance_price
cash_price
selected_price
insurance_covered
total_price
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

If quantity is no longer shown in some views, it may still remain in the database for pharmacy or future quantity-based billing. But the invoice view should hide it where requested.

---

# 7. Use selected_price Instead of unit_price

Replace `unit_price` usage in views and display logic with:

```text
selected_price
```

Rules:

* `selected_price` is the actual price applied to the invoice item.
* For Cash and Carry, `selected_price = base price`.
* For insurance, `selected_price = resolved insurance price`.
* Views must not display old `unit_price`.
* Do not show “Cash Price” column if it is no longer required.

Search and update:

```text
unit_price
is_nhis_covered
nhis_approved_amount
coverage_percentage
```

Make sure old references do not break pages.

---

# 8. Invoice View Page Changes

On the invoice view page, remove the following columns:

```text
Qty
Cash Price
```

The invoice item table should show only useful simplified columns, such as:

```text
Service / Description
Department / Source
Selected Price
Patient Payable
Paid Amount
Balance
Status
Action
```

If needed, group items by source/department:

```text
Consultation
Investigations
Pharmacy
Ward
Procedures
```

---

# 9. Remove visit_services Table Usage

Remove `visit_services` from the active project workflow.

The visit’s selected services should now be represented by invoice items under the visit’s invoice.

Required behavior:

* Do not create new `visit_services` rows.
* Do not display visit services table.
* Do not depend on visit_services for visit billing.
* Show the visit invoice instead.
* Any previous logic that reads visit_services should be updated to read invoice items.

When displaying a visit’s billable services, use:

```text
visit → invoice → invoice_items
```

not:

```text
visit → visit_services
```

If the physical table still exists temporarily for migration safety, stop using it in the app. Remove the migration/table only if safe.

---

# 10. ServicePricingService Update

Update the pricing service to return simplified values.

Required method:

```php
resolvePriceForVisitService(Service $service, ?PatientInsurance $patientInsurance = null, int $quantity = 1): array
```

Return:

```php
[
    'selected_price' => 60,
    'total_price' => 60,
    'patient_payable' => 60,
    'pricing_source' => 'provider_specific | insurance_type | cash_and_carry | base_price',
    'insurance_type' => 'cash | nhis | private | corporate | other',
]
```

Do not return or use:

```text
unit_price
coverage_percentage
insurance_covered
nhis_approved_amount
is_nhis_covered
```

unless kept only for backward compatibility internally and not saved/displayed.

---

# 11. BillingService Update

Update `BillingService::addItemToVisitInvoice(...)` to save invoice items using the simplified model.

It must:

* get or create the visit invoice.
* resolve selected price using `ServicePricingService`.
* save `selected_price`.
* save `total_price`.
* save `patient_payable`.
* save `patient_insurance_id` if insurance applies.
* save `pricing_source`.
* save `insurance_type`.
* prevent duplicate billing by `source_type + source_id`.
* update invoice totals.

Do not save `unit_price`.

---

# 12. Invoice Totals

Invoice totals should be based on `invoice_items.patient_payable` or `invoice_items.total_price` according to the system’s billing rule.

Recommended:

```text
subtotal = sum(total_price)
patient_total = sum(patient_payable)
paid_amount = sum(invoice_items.paid_amount)
outstanding_amount = sum(invoice_items.balance)
```

Each invoice item:

```text
balance = patient_payable - paid_amount
```

If no payment has been made:

```text
paid_amount = 0
balance = patient_payable
payment_status = UNPAID
```

---

# 13. Data Migration / Backward Compatibility

If existing data has `unit_price`, migrate it carefully.

Suggested migration rule:

```text
selected_price = unit_price where selected_price is null
total_price = selected_price * quantity where total_price is null
patient_payable = total_price where patient_payable is null
balance = patient_payable - paid_amount
```

Only drop old columns after confirming nothing uses them.

If immediate dropping is risky:

* keep columns temporarily
* stop writing to them
* stop displaying them
* remove in a later cleanup migration

---

# 14. Validation and Error Display

For the drug saving issue and service/module saves:

* Do not allow silent redirects.
* Show validation errors clearly.
* Use Inertia/Vue form errors properly.
* Preserve page state after validation errors.
* Do not dismiss modals before successful response.
* Log server-side errors where needed.

For any 302:

* identify whether it is validation redirect, auth redirect, middleware redirect, or route mismatch.
* fix the underlying cause.

---

# 15. Performance Rules

* Eager-load invoice with items, service, department, and patient insurance.
* Avoid N+1 queries in invoice view.
* Do not load unnecessary module data repeatedly.
* Cache enabled modules safely.
* Recalculate invoice totals efficiently.
* Keep pharmacy drug adding fast.
* Do not perform heavy calculations in Vue if they belong in backend pricing service.

---

# 16. Deliverables

Provide:

1. Root cause of modules not loading.
2. Root cause of drug save `302` redirect.
3. Confirmation that multiple drugs can now be added where allowed.
4. CCC code storage implementation.
5. Migrations added or changed.
6. Updated models and `$fillable`.
7. Updated `ServicePricingService`.
8. Updated `BillingService`.
9. Updated `invoice_items` model/table usage.
10. Confirmation that coverage is removed from pricing.
11. Confirmation that `unit_price`, NHIS-specific approval fields, and old relevance fields are removed or no longer used.
12. Updated invoice view using `selected_price`.
13. Confirmation that Qty and Cash Price columns are removed from invoice view.
14. Confirmation that `visit_services` is no longer used and visit invoice is displayed instead.
15. Files modified.

---

# 17. Important Rules

Do not hardcode NHIS-specific pricing logic.

Do not use coverage percentage in price calculation.

Do not save `unit_price` into invoice items.

Do not display `unit_price`; display `selected_price`.

Do not create or display `visit_services`.

Do not bypass `BillingService`.

Do not bypass `ServicePricingService`.

Do not allow silent `302` redirects without visible error feedback.

Do not remove old columns blindly if existing data or code still depends on them; phase the cleanup safely.

Now inspect the current implementation and apply these fixes carefully.
