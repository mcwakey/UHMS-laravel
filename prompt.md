````text
You are a senior Laravel + Inertia/Vue architect working on UHMS — Ultimate Hospital Management System.

We need to improve Pharmacy, Ward, Emergency, Consumables, Dispensing, Billing, and Stock Balance workflows.

The system already uses a unified Product-based stock system. Do not create parallel drug, lab item, procedure item, or consumable inventory systems.

Focus only on:

- Billing drugs before dispensing
- Selecting drugs/items to dispense
- Partial dispensing / quantity reduction
- Making only billed/approved items available on dispensing page
- Ward consumables from Ward stock
- Emergency consumables from Emergency stock
- Department stock quantity display
- Catalogue quantity display
- Stock Balances page redesign
- Low stock display behavior

Do not break:

- visit workflow
- admission workflow
- emergency workflow
- pharmacy workflow
- billing/invoice system
- product stock movement system
- stock locations
- purchase orders
- stock transfers
- MAR / medication administration
- investigation workflow
- procedure workflow

---

# 1. Core Stock Rule

UHMS must use one unified inventory system.

Every physical item must come from:

```text
products
````

This includes:

```text
pharmacy drugs
ward consumables
emergency consumables
investigation consumables
procedure consumables
theatre consumables
general supplies
```

Do not create or use parallel stock systems such as:

```text
drugs table as stock source
lab_items as stock source
procedure_items as stock source
department-specific item stock tables
```

Product stock must be tracked through stock locations and stock movements.

---

# 2. Billing Drugs Before Dispensing

We need to support billing selected prescribed drugs before actual dispensing.

Current expected flow:

```text
Doctor prescribes drugs
↓
Pharmacy reviews prescription
↓
Pharmacy selects drugs to bill/dispense
↓
Pharmacy can reduce quantities if needed
↓
Only selected drugs/quantities are billed
↓
Only billed/approved drugs become available on dispense page
↓
Pharmacy dispenses selected billed drugs
↓
Stock movement happens once during dispensing
```

Do not automatically bill every prescribed drug if pharmacy has not selected it for billing/dispensing.

---

# 3. Pharmacy Prescription Billing Page

On the pharmacy prescription review/billing page, user must be able to:

```text
see prescribed drugs
see prescribed quantity
see available pharmacy stock quantity
see main store stock quantity
select which drugs to bill
reduce quantity if needed
exclude unavailable drugs
bill selected drugs only
send selected billed drugs to dispense page
```

Example:

```text
Prescription:
- Paracetamol 500mg, prescribed qty 20
- Amoxicillin 500mg, prescribed qty 21
- Omeprazole 20mg, prescribed qty 7

Pharmacy selects:
- Paracetamol qty 10
- Omeprazole qty 7

System bills only:
- Paracetamol qty 10
- Omeprazole qty 7

Amoxicillin is not billed and should not appear as ready to dispense.
```

---

# 4. Partial Quantity Rule

Pharmacy user must be able to reduce quantity before billing.

Rules:

```text
selected_qty cannot be greater than prescribed_qty
selected_qty cannot be greater than available pharmacy stock unless backorder is allowed
selected_qty must be greater than 0 if selected
unselected items are not billed
```

If selected quantity is lower than prescribed quantity, show status:

```text
PARTIALLY_SELECTED
PARTIALLY_BILLED
PARTIALLY_DISPENSED
```

depending on workflow stage.

---

# 5. Invoice Item Creation Rule

When pharmacy bills selected drugs:

* create invoice items only for selected drugs
* use BillingService
* use visit insurance pricing rules
* use Product pricing rules
* preserve invoice item snapshot
* do not duplicate invoice items
* use clear source_type/source_id

Suggested source:

```text
source_type = prescription_item
source_id = prescription_item.id
```

If partial billing needs separate identity, use:

```text
source_type = pharmacy_billing_selection
source_id = pharmacy_billing_selection.id
```

Recommended table if needed:

```text
pharmacy_billing_selections
- id
- visit_id
- patient_id
- prescription_id
- prescription_item_id
- product_id
- prescribed_quantity
- selected_quantity
- billed_quantity
- dispensed_quantity
- invoice_item_id nullable
- selected_by
- billed_by nullable
- dispensed_by nullable
- status
- notes nullable
- created_at
- updated_at
```

Statuses:

```text
SELECTED
BILLED
PARTIALLY_DISPENSED
DISPENSED
CANCELLED
```

Use existing structures if already available.

---

# 6. Dispense Page Rule

The dispense page should show only drugs that are:

```text
selected
billed
available for dispensing
not fully dispensed
not cancelled
```

Do not show all prescribed drugs automatically on the dispense page.

Dispense page should display:

```text
patient
visit
prescription
product/drug
billed quantity
already dispensed quantity
remaining quantity to dispense
available pharmacy stock
main store stock
invoice item status
action
```

When dispensing:

```text
dispensed_qty <= remaining_billed_qty
dispensed_qty <= available_pharmacy_stock
```

Dispensing creates stock movement OUT from Pharmacy stock location.

Do not reduce stock during billing.

Stock reduces only when actual dispensing occurs.

---

# 7. Stock Deduction Rule for Pharmacy

Billing is financial.

Dispensing is physical stock movement.

Therefore:

```text
Billing selected drugs creates invoice items.
Dispensing selected billed drugs creates stock movement OUT.
```

Do not reduce stock at billing stage.

Do not reduce stock twice.

Do not reduce stock when medication is administered by nurse if it was already dispensed to patient.

---

# 8. Ward Consumables

Ward must have its own consumables and stock.

Ward consumables must come from products linked/available to Ward department.

Ward must consume from the Ward stock location.

Rules:

```text
Ward cannot create products.
Ward can only use products made available to Ward department.
Ward consumables stock comes from Ward stock location.
Ward stock is replenished through stock requisition/transfer from Store/Main stock.
Ward usage creates stock movement OUT from Ward stock location.
```

Examples of Ward consumables:

```text
syringes
gloves
cannulas
IV giving sets
cotton
gauze
catheters
dressings
```

Ward consumables should be usable in:

```text
admission care
nursing procedures
wound dressing
bedside care
medication administration if consumables are required
```

---

# 9. Emergency Consumables

Emergency must have its own consumables and stock.

Emergency consumables must come from products linked/available to Emergency department.

Emergency must consume from Emergency stock location.

Rules:

```text
Emergency cannot create products.
Emergency can only use products made available to Emergency department.
Emergency consumables stock comes from Emergency stock location.
Emergency stock is replenished through stock requisition/transfer from Store/Main stock.
Emergency usage creates stock movement OUT from Emergency stock location.
```

Examples:

```text
emergency drugs
IV fluids
syringes
cannulas
oxygen masks
nebulizer kits
gloves
resuscitation supplies
minor procedure supplies
```

Emergency stock usage must integrate with:

```text
Emergency Case Management
Emergency MAR
Emergency medication administration
Emergency procedures
Emergency consumables usage
```

---

# 10. Investigation Consumables

Investigation departments must use consumables from their department stock locations.

Examples:

```text
Lab stock location
X-Ray stock location
Scan stock location
CT stock location
```

Investigation consumables must come from products linked to investigation departments.

Investigation result/resource entry should allow selecting consumables used and quantity.

Usage creates stock movement OUT from the correct investigation department stock location.

Do not create lab item stock separate from products.

---

# 11. Procedure Consumables

Procedure/Theatre departments must use consumables from their department stock locations.

Examples:

```text
Theatre stock location
Minor Procedure stock location
Dental procedure stock location
Eye procedure stock location
```

Procedure consumables must come from products linked to procedure departments.

Procedure note/resource entry should allow selecting consumables used and quantity.

Usage creates stock movement OUT from the correct procedure department stock location.

Do not create procedure item stock separate from products.

---

# 12. Catalogue Quantity Display

Every department catalogue must display both:

```text
department available quantity
main stock quantity
```

This applies to:

```text
Pharmacy Drug Catalogue
Investigation Consumables Catalogue
Procedure Consumables Catalogue
Ward Consumables Catalogue
Emergency Consumables Catalogue
```

---

# 13. Pharmacy Drug Catalogue Quantity Display

On Pharmacy Drug Catalogue, always display:

```text
Pharmacy Available Qty
Main Stock Qty
Stock Status
```

Example:

```text
Paracetamol 500mg
Pharmacy Qty: 120
Main Stock Qty: 800
Status: OK
```

Do not show only one quantity without context.

Pharmacy quantity should come from Pharmacy stock location.

Main stock quantity should come from Main Store stock location.

---

# 14. Investigation Consumables Catalogue Quantity Display

On Investigation Consumables Catalogue, always display:

```text
Department Available Qty
Main Stock Qty
Stock Status
```

Example:

```text
Malaria RDT Kit
Lab Qty: 45
Main Stock Qty: 300
Status: Low
```

Quantity should be based on the selected/logged-in investigation department stock location.

---

# 15. Procedure Consumables Catalogue Quantity Display

On Procedure Consumables Catalogue, always display:

```text
Department Available Qty
Main Stock Qty
Stock Status
```

Example:

```text
Surgical Gloves
Theatre Qty: 20
Main Stock Qty: 500
Status: Low
```

Quantity should be based on the selected/logged-in procedure department stock location.

---

# 16. Ward/Emergency Catalogue Quantity Display

On Ward and Emergency consumables pages, always display:

```text
Department Available Qty
Main Stock Qty
Stock Status
```

Examples:

```text
Cannula 18G
Ward Qty: 12
Main Stock Qty: 200
Status: Low

Adrenaline Injection
Emergency Qty: 5
Main Stock Qty: 40
Status: Critical
```

---

# 17. Main Stock Qty Definition

Main Stock Qty must always come from the stock location marked as Main Store.

Rules:

```text
Main Store stock location belongs to Store department.
Main Store cannot be edited/deactivated casually.
All purchase receipts go first into Main Store.
Transfers move stock from Main Store to department stock locations.
```

Do not calculate Main Stock Qty from all locations.

Main Stock Qty means quantity in the Main Store location only.

---

# 18. Department Available Qty Definition

Department Available Qty means quantity available in the department’s linked stock location.

Examples:

```text
Pharmacy Available Qty = product balance in Pharmacy stock location
Ward Available Qty = product balance in Ward stock location
Emergency Available Qty = product balance in Emergency stock location
Lab Available Qty = product balance in Lab stock location
Theatre Available Qty = product balance in Theatre stock location
```

Use StockBalanceService or equivalent.

Do not read quantity from product table.

Do not read quantity from drug catalogue table.

---

# 19. Stock Balances Page Redesign

On the Stock Balances page, display all department quantities as columns.

Current page should be redesigned from a location-only or single-quantity view to a product-by-location matrix.

Example:

```text
Product                  Main Store   Pharmacy   Ward   Emergency   Lab   Theatre   Total   Status
Paracetamol 500mg        800          120        40     20          0     0         980     OK
Cannula 18G              200          0          12     30          0     5         247     Low in Ward
Malaria RDT Kit          300          0          0      0           45    0         345     OK
Surgical Gloves          500          0          20     10          0     25        555     Low in Theatre
```

Columns should include active stock locations or department stock locations.

At minimum include:

```text
Main Store
Pharmacy
Ward
Emergency
Investigation departments
Procedure departments
Total
Status
```

If many departments exist, allow horizontal scroll.

---

# 20. Stock Balance Matrix Rules

Each row = one product.

Each department/location column = quantity for that product in that stock location.

Total column = sum of all stock locations for that product.

Status column = overall stock status or per-location warning summary.

Rules:

```text
Do not calculate stock from product quantity field.
Calculate from stock movements / stock balances.
Do not hide zero quantities.
Use 0 for locations with no stock.
Avoid N+1 queries.
```

---

# 21. Low Stock Display Rule

Remove yellow row background for low quantity.

Do not color the entire row yellow.

Instead, show status against each quantity.

Example:

```text
Ward Qty: 3  [LOW]
Emergency Qty: 0  [OUT]
Main Store Qty: 100 [OK]
```

Per-location statuses:

```text
OK
LOW
CRITICAL
OUT
NOT STOCKED
```

Use badges or small text labels beside quantity.

Example:

```text
3 LOW
0 OUT
120 OK
```

The row itself should remain visually clean.

---

# 22. Stock Status Logic

Stock status should be calculated per product per location.

Use product/location thresholds if available.

If not available, use product default threshold.

Suggested logic:

```text
qty <= 0 = OUT
qty <= critical_threshold = CRITICAL
qty <= reorder_level / low_threshold = LOW
qty > low_threshold = OK
```

If a product is not linked to a department/location:

```text
NOT STOCKED
```

Do not show LOW just because quantity is zero in a department where product is not meant to be stocked.

---

# 23. Product Department Link Rule

Products can be linked to one or many departments.

Only departments linked to the product should normally use/consume that product.

Examples:

```text
Paracetamol → Pharmacy, Ward, Emergency
Malaria RDT Kit → Lab, Emergency
Surgical Gloves → Ward, Emergency, Theatre
```

Catalogue pages should fetch products based on department links.

Do not allow Pharmacy/Ward/Emergency/Investigation/Procedure departments to create new products.

Product creation remains Store/Procurement responsibility.

---

# 24. Requisition and Transfer Reminder

Department stock should be replenished through:

```text
Stock Requisition
Store approval
Stock Transfer
Department receipt acknowledgement
```

Departments should not directly pull from Main Store without transfer.

If a department has low stock, show action:

```text
Request Stock
```

not:

```text
Edit Product Qty
```

---

# 25. Billing Consumables

Consumables may be billable or non-billable depending on configuration.

If consumable is billable:

```text
create invoice item using BillingService
use insurance/product pricing rules
```

If non-billable:

```text
create only stock movement usage record
do not bill
```

Do not hardcode consumables as free or billable.

Use product/service configuration.

---

# 26. Ward/Emergency Consumable Usage

Ward/Emergency pages should allow authorized users to record consumables used.

Fields:

```text
patient
visit/admission/emergency case
product
quantity
usage reason
billable yes/no from product config
used_by
used_at
stock location
```

On save:

```text
validate department stock availability
create stock movement OUT from correct department stock location
if billable, create invoice item using BillingService
link stock movement and invoice item to usage record
```

Suggested table if missing:

```text
department_consumable_usages
- id
- visit_id
- admission_id nullable
- emergency_case_id nullable
- patient_id
- department_id
- stock_location_id
- product_id
- quantity
- usage_type
- notes nullable
- is_billable
- invoice_item_id nullable
- stock_movement_id nullable
- used_by
- used_at
- created_at
- updated_at
```

Usage types:

```text
WARD_CARE
EMERGENCY_CARE
INVESTIGATION
PROCEDURE
MEDICATION_ADMINISTRATION
OTHER
```

Use existing equivalent if already available.

---

# 27. Investigation/Procedure Consumable Usage

Investigation and Procedure workflows should record consumables used.

When entering investigation resources/results:

```text
select consumables used
enter quantities
deduct from investigation department stock
bill if billable
```

When entering procedure notes/resources:

```text
select consumables used
enter quantities
deduct from procedure/theatre stock
bill if billable
```

Do not let investigation/procedure create new products.

They must select from available linked products.

---

# 28. Backend Services to Use/Create

Use or create these services:

```text
StockBalanceService
StockMovementService
StockLocationResolver
BillingService
ProductPricingService
DepartmentConsumableUsageService
PharmacyBillingSelectionService
PharmacyDispensingService
StockBalanceMatrixService
```

Do not duplicate stock calculation logic in controllers.

Do not calculate stock directly in Vue/Blade.

---

# 29. Required UI Changes

## Pharmacy Prescription Billing Page

Add:

```text
checkbox/select item
prescribed qty
selected qty input
pharmacy available qty
main stock qty
stock status
bill selected button
```

## Pharmacy Dispense Page

Show only selected/billed drugs:

```text
billed qty
remaining to dispense
available pharmacy qty
main stock qty
dispense action
```

## Pharmacy Drug Catalogue

Show:

```text
Pharmacy Available Qty
Main Stock Qty
Status
```

## Ward Consumables Page

Show:

```text
Ward Available Qty
Main Stock Qty
Status
Use Consumable action
Request Stock action
```

## Emergency Consumables Page

Show:

```text
Emergency Available Qty
Main Stock Qty
Status
Use Consumable action
Request Stock action
```

## Investigation Consumables Catalogue

Show:

```text
Department Available Qty
Main Stock Qty
Status
```

## Procedure Consumables Catalogue

Show:

```text
Department Available Qty
Main Stock Qty
Status
```

## Stock Balances Page

Show product-location matrix:

```text
Product | Main Store | Pharmacy | Ward | Emergency | Lab | Theatre | Total | Status
```

Remove yellow low-stock row background.

Show status badges beside each quantity.

---

# 30. Validation Rules

Pharmacy billing selection:

```text
at least one drug selected
selected quantity required for selected item
selected quantity > 0
selected quantity <= prescribed quantity
selected quantity <= available pharmacy stock unless backorder allowed
cannot bill already fully billed item
cannot duplicate invoice item
```

Dispensing:

```text
dispense quantity > 0
dispense quantity <= remaining billed quantity
dispense quantity <= pharmacy available quantity
cannot dispense unbilled item
cannot dispense cancelled item
```

Consumable usage:

```text
product required
quantity > 0
department stock location required
quantity <= department available qty
patient/visit/admission/emergency context required where applicable
billable products must use BillingService
```

Stock balance matrix:

```text
show zero quantities
show statuses per location
do not color full row yellow
```

---

# 31. Performance Requirements

Avoid N+1 queries.

For catalogue pages, load product quantities in bulk.

For stock balance matrix:

```text
load products
load active stock locations
load balances grouped by product_id and stock_location_id
build matrix in service
```

Do not query balance per product per location in a loop.

Use indexes:

```text
stock_balances.product_id
stock_balances.stock_location_id
stock_movements.product_id
stock_movements.stock_location_id
products.department links
```

---

# 32. Tests Required

Add or update tests:

## Pharmacy Billing Before Dispense

1. Pharmacy can select prescribed drugs to bill.
2. Pharmacy can reduce selected quantity.
3. Only selected drugs are billed.
4. Unselected drugs are not billed.
5. Selected billed drugs appear on dispense page.
6. Unbilled prescribed drugs do not appear on dispense page.
7. Billing does not reduce stock.
8. Dispensing reduces pharmacy stock once.
9. Duplicate invoice items are prevented.

## Ward/Emergency Consumables

10. Ward consumable usage deducts from Ward stock location.
11. Emergency consumable usage deducts from Emergency stock location.
12. Ward cannot consume from Main Store directly.
13. Emergency cannot consume from Main Store directly.
14. Billable consumables create invoice items.
15. Non-billable consumables do not create invoice items.

## Catalogue Quantity Display

16. Pharmacy catalogue shows pharmacy qty and main stock qty.
17. Investigation catalogue shows department qty and main stock qty.
18. Procedure catalogue shows department qty and main stock qty.
19. Ward catalogue shows ward qty and main stock qty.
20. Emergency catalogue shows emergency qty and main stock qty.
21. Quantities come from stock balances, not product quantity field.

## Stock Balances Matrix

22. Stock Balances page shows departments/locations as columns.
23. Each product row shows quantities per location.
24. Total column sums all locations.
25. Zero quantities display as 0.
26. Low stock row yellow background is removed.
27. Low/out/critical status appears beside quantity.
28. NOT STOCKED appears for unlinked department/location.
29. Matrix avoids N+1 queries.

## Department Product Access

30. Pharmacy catalogue fetches products linked to Pharmacy.
31. Investigation catalogue fetches products linked to investigation department.
32. Procedure catalogue fetches products linked to procedure department.
33. Ward catalogue fetches products linked to Ward.
34. Emergency catalogue fetches products linked to Emergency.
35. Departments cannot create products from their catalogues.

---

# 33. Deliverables

Provide:

1. Gap analysis of current pharmacy billing/dispensing/stock workflow.
2. Pharmacy billing selection before dispensing.
3. Partial quantity billing support.
4. Dispense page filtered to selected/billed drugs only.
5. Ward consumables using Ward stock location.
6. Emergency consumables using Emergency stock location.
7. Investigation consumables stock display/update.
8. Procedure consumables stock display/update.
9. Catalogue pages showing department qty vs main stock qty.
10. Stock Balances matrix with department/location columns.
11. Status badge per quantity instead of yellow row background.
12. Backend services updated.
13. Validation and authorization added.
14. Tests or verification notes.
15. Files modified.
16. Remaining TODOs.

---

# 34. Important Rules

Do not create parallel inventory systems.

Do not create separate drug stock.

Do not let departments create products.

Do not reduce stock during billing.

Do not dispense unbilled drugs.

Do not deduct stock twice.

Do not consume directly from Main Store for ward/emergency/investigation/procedure usage.

Do not hide Main Stock Qty from catalogues.

Do not hide Department Available Qty from catalogues.

Do not use yellow full-row background for low stock.

Do not break purchase orders, stock transfers, stock requisitions, pharmacy dispensing, billing, emergency, ward/admission, investigation, procedure, or product management.

Now inspect the current implementation and update Pharmacy, Ward, Emergency, Investigation, Procedure, and Stock Balances workflows according to the unified product stock system described above.

```
```
