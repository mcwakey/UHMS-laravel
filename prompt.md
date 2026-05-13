You are a senior Laravel + Inertia/Vue architect, technical writer, and healthcare workflow analyst working on **UHMS — Ultimate Hospital Management System**.

We need to update the project **User Manual** to reflect the latest UHMS workflow and feature decisions.

Focus only on updating documentation/user manuals. Do not implement code unless explicitly requested.

---

# 1. Objective

Update or create the UHMS user manual with the latest workflows and features.

The manual should be saved as:

```text
docs/UHMS_Updated_User_Manual.md
docs/UHMS_Updated_User_Manual.pdf
```

The document must be clear enough for:

* hospital administrators
* front desk / records staff
* triage nurses
* doctors
* investigation staff
* pharmacists
* cashiers
* store/inventory staff
* system administrators
* developers/support staff

---

# 2. Main Areas to Document

The updated user manual must include:

1. General system navigation
2. Role-based dashboards
3. Patient registration
4. Patient insurance management
5. Visit creation
6. Triage workflow
7. Doctor consultation
8. Prescriptions
9. Investigations
10. Investigation catalogue
11. Investigation result entry, verification, viewing, and printing
12. Billing and single visit invoice
13. Payments and payment allocation
14. Manual discounts
15. Pharmacy and dispensing
16. Inventory and stock movement system
17. Purchase orders and receiving
18. Stock transfers
19. Stock returns
20. Stock adjustments
21. Damaged and expired stock
22. Module system
23. Reports
24. PWA / installable app usage
25. Important system rules

---

# 3. System-Wide Workflow Rules to Document

The manual must clearly explain these rules:

## Visit Workflow

* Every visit starts at triage.
* When triage is saved, the visit status changes from `TRIAGE` to `WAITING_CONSULTATION`.
* The doctor must click `Start Consultation` before entering clinical data.
* Clicking `Start Consultation` changes the visit status from `WAITING_CONSULTATION` to `CONSULTING`.
* If the visit is already in consultation, the doctor sees `Continue Consultation`.
* Clinical forms must remain disabled until consultation has started.

## Consultation Workflow

The consultation page must explain:

* patient header
* vitals panel
* triage score
* current visit status
* insurance summary
* previous visits panel
* consultation tabs
* diagnosis types
* primary diagnosis
* prescription entry
* investigation request entry

Mention that saving within consultation tabs should not reload the full page or reset the active tab.

---

# 4. Insurance Rules to Document

Document that:

* A patient can have multiple insurance records.
* Insurance can be added/edited from the patient profile or visit creation page.
* CCC code should be stored where applicable.
* Cash and Carry must always be available.
* Cash and Carry uses the service base price.
* If default insurance is invalid, expired, exhausted, or missing, Cash and Carry is selected.
* The user can select another valid insurance during visit creation.

Avoid treating NHIS as special. NHIS is only one insurance type.

---

# 5. Billing Rules to Document

Document the new billing model:

```text
One Visit = One Main Invoice
```

All billable items during the visit must be added to the same invoice.

Examples:

* consultation service
* investigation item
* pharmacy item
* ward charge
* procedure
* scan
* x-ray

The manual must explain that the system should not create many separate invoices for one visit.

---

# 6. Invoice Item Pricing Rules to Document

Document these invoice item fields and meanings:

* `cash_price`
* `insurance_price`
* `selected_price`
* `quantity`
* `discount_amount`
* `insurance_covered`
* `patient_payable`
* `paid_amount`
* `balance`
* `payment_status`

Explain the formulas:

```text
insurance_covered = cash_price - insurance_price
```

```text
patient_payable = (selected_price * quantity) - discount_amount
```

```text
balance = patient_payable - paid_amount
```

Important explanations:

* `insurance_covered` is not a payment.
* `discount_amount` is entered manually by authorized users.
* `paid_amount` only comes from real payments.
* Discounts must not be automatically calculated from insurance.
* Insurance covered must not be treated as paid amount.

---

# 7. Payment Rules to Document

Document that:

* Patients can pay while the visit is still ongoing.
* Payments can be allocated to selected invoice lines.
* The system must show which invoice lines are:

  * unpaid
  * partially paid
  * paid

Explain payment allocation:

```text
Payment → allocated to one or more invoice items
```

Mention that payment does not affect stock.

---

# 8. Discount Workflow to Document

Document that:

* Discounts are applied manually from the Invoice View page.
* Discounts are entered per invoice item.
* Only authorized users can apply discounts.
* Discount cannot exceed selected price × quantity.
* Applying a discount recalculates:

  * patient payable
  * balance
  * payment status
  * invoice totals

---

# 9. Investigation Workflow to Document

Document that investigations are not limited to Lab.

Investigation departments can include:

* Lab
* X-ray
* Scan
* CT-scan
* Ultrasound
* ECG
* any configured investigation-type department

## Doctor Investigation Request

Explain:

1. Doctor opens Investigation tab.
2. Doctor selects investigation department.
3. Doctor selects one or more services.
4. Request appears on the correct investigation department page.

## Investigation Request Acceptance

Document that investigation staff must select the requested items before accepting.

Only accepted selected items are billed.

Do not bill unselected items.

## Result Workflow

Document:

* result entry
* result view
* verification
* printing
* doctor result viewing from consultation page

Mention that doctors should not be able to delete an investigation item after results have been entered.

---

# 10. Investigation Catalogue to Document

The manual must rename the old concept:

```text
Lab Test Catalogue
```

to:

```text
Investigation Catalogue
```

Explain that the catalogue is based on services from investigation-type departments.

Correct structure:

```text
Investigation Service
    → Optional Headers / Categories
    → Criteria
```

Do not describe separate catalogue tests detached from services.

Explain that criteria may belong to a header/category or stand alone.

---

# 11. Pharmacy Workflow to Document

Document that:

* doctors prescribe drugs during consultation
* prescribing does not reduce stock
* pharmacy dispensing reduces stock
* only dispensed drugs are billed
* dispensed drugs are added to the same visit invoice
* dispensing creates stock movement OUT

---

# 12. Stock Movement System to Document

Document the new inventory model:

```text
stock_movements = source of truth
stock_balances = fast current stock cache
```

Explain:

```text
Current Stock = Total IN movements - Total OUT movements
```

The manual must explain that product/drug quantity should not be manually overwritten as current stock.

## Movement Types

Document these movement types:

* `OPENING_STOCK`
* `PURCHASE_RECEIVED`
* `PHARMACY_DISPENSED`
* `TRANSFER_IN`
* `TRANSFER_OUT`
* `RETURN_IN`
* `RETURN_OUT`
* `ADJUSTMENT_IN`
* `ADJUSTMENT_OUT`
* `DAMAGED`
* `EXPIRED`
* `REVERSAL_IN`
* `REVERSAL_OUT`

---

# 13. Purchase Order and Receiving to Document

Document that:

* creating a purchase order does not automatically increase stock
* stock increases when items are received
* receiving creates `PURCHASE_RECEIVED` stock movements
* purchase order can be:

  * pending
  * partially received
  * received
  * cancelled

---

# 14. Transfers, Returns, Adjustments, Damaged, Expired Stock to Document

## Transfers

Explain that a transfer creates two movements:

* `TRANSFER_OUT` from source location
* `TRANSFER_IN` into destination location

## Returns

Explain:

* `RETURN_IN` brings stock back into a location
* `RETURN_OUT` removes stock from a location, for example return to supplier

## Adjustments

Explain that adjustments are used for stock count corrections:

* `ADJUSTMENT_IN`
* `ADJUSTMENT_OUT`

Adjustment requires:

* product/drug
* location
* adjustment type
* quantity
* reason
* authorized user

## Damaged / Expired

Explain:

* damaged stock uses `DAMAGED` OUT movement
* expired stock uses `EXPIRED` OUT movement

## Reversals

Explain that corrections should use reversal movements instead of deleting old records.

---

# 15. Module System to Document

Document that UHMS supports core and optional modules.

## Core Modules

Core modules should always remain active:

* authentication
* users and roles
* patients
* visits
* triage
* consultation
* departments
* services
* billing
* settings

## Optional Modules

Optional modules can be enabled or disabled:

* insurance
* claims
* pharmacy
* inventory
* investigations
* analyzer integration
* HR
* reports
* notifications

If a module is disabled, it should disappear from the sidebar and use safe fallback behavior.

Examples:

* Insurance disabled → Cash and Carry is used.
* Pharmacy disabled → prescriptions can be recorded, but dispensing is unavailable.
* Analyzer disabled → investigation results are entered manually.

---

# 16. Reports to Document

Add report sections for:

## Billing Reports

* invoice report
* payment report
* outstanding balance report
* paid invoice items
* unpaid invoice items
* discount report

## Investigation Reports

* pending requests
* accepted items
* completed results
* verified results

## Pharmacy Reports

* pending prescriptions
* dispensed items
* drug sales
* stock movement report

## Inventory Reports

* current stock by location
* stock ledger
* low stock report
* expired stock report
* transfers report
* adjustment report
* purchase received report

---

# 17. PWA / Installable App Usage

Document that UHMS can be installed as a Progressive Web App.

Mention:

* users can install it from supported browsers like Chrome or Edge
* installed app behaves like desktop/tablet software
* live hospital operations may still require network access
* sensitive patient data should not be cached unless safely implemented
* users should log out on shared devices

---

# 18. Documentation Style

Write the manual in clear Markdown.

Use:

* headings
* subheadings
* bullet points
* short workflow steps
* examples
* important notes

Avoid overly technical code unless needed to explain system rules.

The document should be readable by hospital staff, not just developers.

---

# 19. Deliverables

Provide:

1. Updated `docs/UHMS_Updated_User_Manual.md`
2. Clear table of contents
3. Updated workflows
4. Updated billing rules
5. Updated inventory/stock movement rules
6. Updated investigation catalogue/result workflow
7. Updated module system explanation
8. Updated PWA usage notes

---

# 20. Important Rules

Do not describe the old multi-invoice model as the main billing model.

Do not describe stock quantity as manually edited current stock.

Do not treat insurance covered as payment.

Do not treat discount as automatic insurance difference.

Do not describe Lab as the only investigation department.

Do not describe tests as separate from services in the investigation catalogue.

Now inspect the existing docs if any, then update or create the new UHMS user manual at:

```text
docs/UHMS_Updated_User_Manual.md
```
