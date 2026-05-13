# UHMS — Ultimate Hospital Management System
## Updated User Manual

**Version:** Updated Workflow Edition  
**Generated:** 2026-05-13  
**System Type:** Laravel + Inertia/Vue SPA + Installable PWA  

---

# Table of Contents

1. Introduction
2. General System Navigation
3. User Roles and Dashboards
4. Patient Registration
5. Patient Insurance Management
6. Visit Creation
7. Triage Workflow
8. Doctor Consultation
9. Prescriptions
10. Investigations
11. Investigation Catalogue
12. Investigation Results, Verification, and Printing
13. Billing and Single Visit Invoice
14. Payments and Payment Allocation
15. Discounts
16. Pharmacy and Dispensing
17. Inventory and Stock Movement System
18. Purchase Orders and Receiving
19. Stock Transfers
20. Stock Returns
21. Stock Adjustments, Damaged, and Expired Stock
22. Module System
23. Reports
24. PWA / Installable App Usage
25. Important System Rules

---

# 1. Introduction

UHMS is a hospital management system designed to manage the complete patient journey from registration to triage, consultation, investigations, pharmacy, billing, payment, and discharge.

The updated system follows these principles:

- One visit has one main invoice.
- All billable items are added to the visit invoice.
- Insurance pricing is applied consistently across all departments.
- Stock is controlled through stock movements instead of manually editing product quantity.
- Most actions behave like an SPA and do not reload the full page.
- Optional modules can be enabled or disabled without breaking the main workflow.

---

# 2. General System Navigation

After login, users are directed to the dashboard assigned to their role.

The sidebar displays only:

- enabled modules
- menus allowed for the user role
- actions allowed by user permissions

If a module is disabled, it should not appear in the sidebar.

---

# 3. User Roles and Dashboards

## Admin Dashboard

Used for system overview and administration.

Typical information:

- total patients
- visits today
- active users
- enabled modules
- billing summary
- system configuration status

## Triage / Nurse Dashboard

Used to manage patients waiting for triage.

Shows:

- new visits awaiting triage
- emergency cases
- patients with completed vitals
- patients waiting for consultation

## Doctor Dashboard

Used by doctors to manage consultations.

Shows:

- patients waiting for consultation
- patients currently in consultation
- referred patients
- recent consultation records

## Cashier Dashboard

Used for billing and payments.

Shows:

- open invoices
- paid invoices
- unpaid invoice lines
- partially paid invoices
- outstanding balances

## Investigation Dashboard

Used by investigation departments such as Lab, X-ray, Scan, CT-scan, etc.

Shows:

- pending investigation requests
- accepted investigation items
- results pending entry
- results pending verification
- verified results

## Pharmacy Dashboard

Used by pharmacy staff.

Shows:

- pending prescriptions
- dispensed drugs
- stock alerts
- pharmacy invoice items

## Store / Inventory Dashboard

Used for stock management.

Shows:

- current stock
- low stock
- pending purchase orders
- stock movements
- transfers
- adjustments

---

# 4. Patient Registration

The Records or Front Desk user can register new patients.

Patient information may include:

- full name
- gender
- date of birth
- phone number
- address
- occupation
- religion
- marital status
- next of kin
- insurance information

After registration, the patient can be checked in for a hospital visit.

---

# 5. Patient Insurance Management

A patient can have multiple insurance records.

Insurance information can be added or edited from:

- patient profile
- visit creation page

Each patient insurance may include:

- insurance provider
- insurance type
- member number
- CCC code where applicable
- validity start date
- validity end date
- default insurance indicator

## Cash and Carry

Cash and Carry is always available, even if the patient has insurance.

Cash and Carry uses the service base price.

## Insurance Selection Rules

When creating a visit:

1. The system checks the patient’s default insurance.
2. If valid, it is selected automatically.
3. If invalid, expired, exhausted, or missing, Cash and Carry is selected.
4. The user can choose another valid insurance.
5. The user can add, edit, or renew insurance directly from the visit creation page.

---

# 6. Visit Creation

When a patient visits the hospital, a new visit is created.

During visit creation:

1. Select the patient.
2. Select insurance or Cash and Carry.
3. Select the consultation department/service if required.
4. The system creates the visit.
5. The system creates one main invoice for the visit.
6. Selected services are added as invoice items.
7. The patient is automatically sent to triage.

## Selected Services

The selected services table shows simplified billing information:

- service
- price
- action

The table also shows the overall total.

The system no longer depends on a separate `visit_services` display. The visit invoice and invoice items represent the billable services for the visit.

---

# 7. Triage Workflow

Every visit starts at triage.

The triage user records patient vitals, such as:

- temperature
- blood pressure
- pulse
- respiratory rate
- oxygen saturation
- weight
- height

After vitals are saved:

1. The system calculates the triage score.
2. The visit status changes from `TRIAGE` to `WAITING_CONSULTATION`.
3. The patient appears in the consultation queue.

The triage score may help identify routine, urgent, emergency, or inpatient cases.

---

# 8. Doctor Consultation

The doctor consultation page is designed as an SPA-like clinical workspace.

It includes:

- patient header
- vitals panel
- triage score
- current visit status
- insurance summary
- previous visits panel
- consultation tabs

## Starting Consultation

Before entering clinical information, the doctor must click:

```text
Start Consultation
```

This changes the visit status from:

```text
WAITING_CONSULTATION → CONSULTING
```

If the visit is already in consultation, the button shows:

```text
Continue Consultation
```

Before consultation is started, clinical forms are disabled.

## Consultation Tabs

The consultation workspace may include:

- complaints
- history
- diagnosis
- investigations
- prescriptions
- treatment
- notes

Saving within a tab should not reload the whole page or reset the active tab.

## Diagnosis

Diagnosis supports:

- provisional diagnosis
- final diagnosis
- primary diagnosis

The first diagnosis is set as primary by default. The doctor may later set another diagnosis as primary.

---

# 9. Prescriptions

Doctors can prescribe drugs during consultation.

When saving a prescription:

- the prescription must be linked to the patient visit
- the prescription must be linked to the doctor
- the prescription list updates without full page reload
- validation errors are shown clearly

Prescribing a drug does not reduce stock. Stock is reduced only when pharmacy dispenses the drug.

---

# 10. Investigations

Investigations include all investigation-type departments, not only Lab.

Examples:

- Lab
- X-ray
- Scan
- CT-scan
- Ultrasound
- ECG

## Requesting Investigation from Consultation

On the consultation page:

1. Open the Investigations tab.
2. Select an investigation department.
3. Select one or more services under that department.
4. Submit the request.

The request appears on the correct investigation department request page.

## Grouping by Department

Requested investigations under consultation are grouped by department.

Example:

```text
LAB
- Full Blood Count — Pending
- Malaria Test — Done

X-RAY
- Chest X-Ray — Accepted
```

Doctors can view available results from the consultation page.

---

# 11. Investigation Catalogue

The old “Lab Test Catalogue” concept is replaced with:

```text
Investigation Catalogue
```

The catalogue is based on services from investigation-type departments.

The system should not create separate catalogue tests detached from services.

Correct structure:

```text
Investigation Service
    → Optional Headers / Categories
    → Criteria
```

## Configuring a Service

When an investigation service is selected, authorized users can configure:

- headers/categories
- criteria

A criterion can belong to a header or stand alone.

Example:

```text
Service: Full Blood Count

Header: Red Cell Indices
- Hemoglobin
- RBC
- HCT

Header: White Cell Count
- WBC

General
- ESR
```

---

# 12. Investigation Results, Verification, and Printing

Investigation staff can enter results for accepted investigation items.

The result form loads the configured headers and criteria from the Investigation Catalogue.

## Result Entry

When result values are entered:

- values are saved against the configured criteria
- result status is updated
- the investigation item status changes from pending/accepted to result-entered/done depending on workflow

## View Result

After results are entered, a View Result button appears.

The result view shows:

- patient information
- visit information
- investigation service
- result values
- units
- reference ranges
- status

## Verification

Authorized users verify results.

After verification:

- result status becomes verified
- Print button becomes available

## Printing

Only verified results should be printed unless system settings allow otherwise.

The printed result should include:

- hospital information
- patient details
- visit details
- investigation service
- result values
- units
- reference ranges
- performed by
- verified by
- date/time

Doctors can view results directly from the Consultation → Investigations tab.

---

# 13. Billing and Single Visit Invoice

UHMS follows this billing model:

```text
One Visit = One Main Invoice
```

All billable activities during the visit add items to that invoice.

Examples:

- consultation service
- investigation item
- pharmacy item
- ward charge
- procedure
- scan
- x-ray

The system must not create multiple active invoices for the same visit.

## Invoice Items

Each invoice item stores a pricing snapshot.

Important fields:

- service/description
- cash price
- insurance price
- selected price
- quantity
- discount amount
- patient payable
- paid amount
- balance
- payment status

## Pricing Rules

Cash and Carry:

```text
selected_price = cash_price
patient_payable = selected_price * quantity - discount_amount
```

Insurance:

```text
selected_price = resolved insurance price
insurance_covered = cash_price - insurance_price
patient_payable = selected_price * quantity - discount_amount
```

Important:

- insurance covered is not a payment
- discount is manually entered
- paid amount only comes from actual payments

---

# 14. Payments and Payment Allocation

Patients can make payment while the visit is still ongoing.

A payment can clear:

- full invoice
- selected invoice items
- part of selected invoice items

The system tracks which invoice lines are unpaid, partially paid, or paid.

Payment allocation allows UHMS to know exactly which lines have been cleared.

Example:

```text
Consultation Fee — PAID
Full Blood Count — PAID
Pharmacy Drugs — PARTIALLY PAID
X-Ray — UNPAID
```

Payments do not affect stock.

---

# 15. Discounts

Discounts are entered manually on the Invoice View page.

Authorized users can apply discount per invoice item.

Discount rules:

- discount cannot be negative
- discount cannot exceed selected price × quantity
- discount is not the same as insurance covered
- discount is not automatically calculated
- applying discount recalculates patient payable, balance, payment status, and invoice totals

Discounts should be applied through the Apply Discount action or discount input on the invoice item row.

---

# 16. Pharmacy and Dispensing

Pharmacy sees prescriptions created by doctors.

When pharmacy dispenses a drug:

1. The dispensed item is billed through the main visit invoice.
2. Stock is reduced through a stock movement.
3. The invoice item is added to the visit invoice.
4. The prescription/dispensing status is updated.

Important:

- Prescribing does not reduce stock.
- Dispensing reduces stock.
- Payment does not reduce stock.
- Only dispensed drugs should be billed.

---

# 17. Inventory and Stock Movement System

UHMS uses a stock movement ledger.

The system should not depend on manually updating a product quantity field.

Current stock is calculated as:

```text
Total IN movements - Total OUT movements
```

For performance, the system may use `stock_balances` as a cached current stock table.

## Main Principle

```text
stock_movements = source of truth
stock_balances = fast current stock cache
```

Stock movement types include:

- opening stock
- purchase received
- pharmacy dispensed
- transfer in
- transfer out
- return in
- return out
- adjustment in
- adjustment out
- damaged
- expired
- reversal in
- reversal out

---

# 18. Purchase Orders and Receiving

Creating a purchase order does not automatically increase stock unless the items are marked as received.

Recommended workflow:

```text
Purchase Order Created
        ↓
Pending
        ↓
Partially Received / Received
        ↓
Stock Movement Created
        ↓
Stock Balance Updated
```

When receiving purchase order items:

- stock movement type is `PURCHASE_RECEIVED`
- direction is `IN`
- stock balance is increased
- purchase order status is updated

If purchase order saving fails with “at least one item is required,” check that selected items are being properly sent to the backend.

---

# 19. Stock Transfers

Transfers move stock from one location to another.

Example:

```text
Main Store → Pharmacy
```

A completed transfer creates two stock movements:

1. `TRANSFER_OUT` from source location
2. `TRANSFER_IN` into destination location

Rules:

- source and destination must be different
- quantity must be greater than zero
- source must have enough stock
- both movements must be linked to the same transfer record

---

# 20. Stock Returns

Returns are handled through stock movements.

## Return In

Used when stock comes back into a location.

```text
movement_type = RETURN_IN
direction = IN
```

## Return Out

Used when stock leaves a location, for example return to supplier.

```text
movement_type = RETURN_OUT
direction = OUT
```

Original stock movements should not be deleted. Returns must create new movements.

---

# 21. Stock Adjustments, Damaged, and Expired Stock

## Stock Adjustments

Stock adjustment is used when physical stock differs from system stock.

Adjustment types:

- `ADJUSTMENT_IN`
- `ADJUSTMENT_OUT`

Users must provide:

- product/drug
- location
- adjustment type
- quantity
- reason
- notes if needed

Only authorized users should perform stock adjustments.

## Damaged Stock

Damaged stock is removed with:

```text
movement_type = DAMAGED
direction = OUT
```

## Expired Stock

Expired stock is removed with:

```text
movement_type = EXPIRED
direction = OUT
```

## Reversals

Corrections should be handled with reversal movements instead of deleting old records.

Example:

Original purchase received:

```text
+100
```

Correction:

```text
REVERSAL_OUT 20
```

Original dispensing:

```text
-10
```

Correction:

```text
REVERSAL_IN 4
```

---

# 22. Module System

UHMS supports core and optional modules.

## Core Modules

Core modules should always remain active:

- authentication
- users and roles
- patients
- visits
- triage
- consultation
- departments
- services
- billing
- settings

## Optional Modules

Optional modules can be enabled or disabled:

- insurance
- claims
- pharmacy
- inventory
- investigations
- analyzer integration
- HR
- reports
- notifications

If a module is disabled, it should disappear from the sidebar and the system should use safe fallback behavior.

Examples:

- Insurance disabled → Cash and Carry is used.
- Pharmacy disabled → prescriptions can be recorded, but dispensing is unavailable.
- Analyzer disabled → investigation results are entered manually.

---

# 23. Reports

Reports should be generated from the correct source records.

## Billing Reports

- invoice report
- payment report
- outstanding balance report
- paid invoice items
- unpaid invoice items
- discount report

## Investigation Reports

- pending requests
- accepted items
- completed results
- verified results

## Pharmacy Reports

- pending prescriptions
- dispensed items
- drug sales
- stock movement report

## Inventory Reports

- current stock by location
- stock ledger
- low stock report
- expired stock report
- transfers report
- adjustment report
- purchase received report

---

# 24. PWA / Installable App Usage

UHMS can be installed as a Progressive Web App.

Users can install it from supported browsers such as Chrome or Edge.

The installed app behaves like a desktop/tablet application.

Important:

- internet or local network access may still be required for live hospital operations
- sensitive patient data should not be cached for offline use unless specifically implemented safely
- users should log out when not using shared devices

---

# 25. Important System Rules

1. Every visit starts at triage.
2. Triage completion sends patient to waiting consultation.
3. Doctors must start consultation before entering clinical data.
4. One visit has one main invoice.
5. Every department adds billable items to the visit invoice.
6. Billing must pass through the Billing Service.
7. Insurance pricing must be applied consistently everywhere.
8. Insurance covered is not payment.
9. Discount is entered manually by authorized users.
10. Paid amount comes only from payments.
11. Prescribing does not reduce stock.
12. Dispensing reduces stock.
13. Stock movements are the source of truth.
14. Stock balances are only cached current quantity.
15. Do not delete old stock movements; use reversals or adjustments.
16. Optional modules must not break the core visit workflow.

---

# End of User Manual
