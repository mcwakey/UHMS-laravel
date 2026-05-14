You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to resolve a major inventory/catalogue architecture conflict by making **Product** the single source of truth for every physical item in the facility, while **Service** remains the source of truth for billable hospital activities.

Focus only on:

1. Unified product architecture
2. Product-department availability
3. Department stock locations
4. Pharmacy product/drug catalogue
5. Investigation consumables/items
6. Procedure consumables/items
7. Investigation Catalogue based on services
8. Procedure Catalogue based on services
9. Stock movements and stock balances
10. Supplier ledger

Do not refactor unrelated modules.

---

# 1. Core Architecture Decision

The system must follow this rule:

```text
If it is physically stocked, purchased, transferred, consumed, dispensed, returned, damaged, or expired, it is a Product.
```

And:

```text
If it is billed as a hospital activity/service, it is a Service.
```

Therefore:

* Products = physical items
* Services = billable hospital activities

---

# 2. Remove Separate Physical Item Concepts

Do not create or use separate item tables such as:

```text
drugs
lab_items
procedure_items
consumables
```

All physical items must be stored in:

```text
products
```

Examples:

```text
Paracetamol tablet        → Product, type = DRUG
Ceftriaxone injection     → Product, type = DRUG
Gloves                    → Product, type = CONSUMABLE
Malaria RDT kit           → Product, type = REAGENT / CONSUMABLE
EDTA tube                 → Product, type = CONSUMABLE
X-ray film                → Product, type = CONSUMABLE
Sutures                   → Product, type = SURGICAL_SUPPLY
Gauze                     → Product, type = CONSUMABLE
Syringe                   → Product, type = CONSUMABLE
Oxygen mask               → Product, type = MEDICAL_SUPPLY
```

Since this project is still in development, we do not need legacy compatibility for old `drugs`, `lab_items`, or `procedure_items` tables. Avoid creating them.

---

# 3. Product Types

Create or update product type enum/constants.

Recommended product types:

```text
DRUG
CONSUMABLE
REAGENT
SURGICAL_SUPPLY
MEDICAL_SUPPLY
EQUIPMENT
GENERAL_ITEM
```

A product type describes what the item is.

A product type does not determine department access by itself.

Department access must be controlled through product-department linking.

---

# 4. Products Table

Create or update `products` table.

Recommended fields:

```text
id
name
code
product_type
unit
description nullable
reorder_level nullable
is_active
created_by nullable
created_at
updated_at
```

Important:

* Do not store current stock quantity as the main source of truth.
* Current stock must come from stock balances/movements.
* Product is the facility-wide item definition.
* Same product can exist in different stock locations with different balances.

---

# 5. Product-Department Linking

A product can be linked to one or more departments.

Create or update pivot table:

```text
product_department
- id
- product_id
- department_id
- is_active
- created_at
- updated_at
```

Examples:

```text
Paracetamol       → Pharmacy
Gloves            → Pharmacy, Lab, Theatre, Ward
Malaria RDT Kit   → Lab
X-ray Film        → X-ray
Sutures           → Theatre
```

Rules:

* A department can only see/use products linked to it.
* Product availability is controlled by Store/Procurement or Admin.
* Department users cannot create products directly.
* Department users cannot link products to their department unless they have Store/Admin permission.
* Product-department links should be respected everywhere in the system.

---

# 6. User Department Access

Department access should follow the logged-in user’s department.

Example:

```text
user.department_id = Pharmacy
```

Then Pharmacy pages should load:

```text
products linked to Pharmacy department
stock location linked to Pharmacy department
```

Example:

```text
user.department_id = Laboratory
```

Then Lab pages should load:

```text
products linked to Laboratory department
stock location linked to Laboratory department
```

Do not allow a department user to consume stock from another department’s location unless explicitly authorized.

---

# 7. Stock Locations Linked to Departments

Stock locations represent where physical quantities are stored.

Create or update `stock_locations`:

```text
id
name
department_id
is_main
is_active
created_at
updated_at
```

Examples:

```text
Main Store Location → Store Department
Pharmacy Stock Location → Pharmacy Department
Laboratory Stock Location → Laboratory Department
Theatre Stock Location → Theatre Department
Ward Stock Location → Ward Department
X-ray Stock Location → X-ray Department
```

Rules:

* Main Store location must be linked to the Store department.
* Department stock usage must deduct from the stock location linked to that department.
* Each department should have a default stock location.
* Same product can have different balances across different locations.

---

# 8. Product vs Stock Location Meaning

Product-department linking answers:

```text
Which department is allowed to use this product?
```

Stock location answers:

```text
Where is the physical quantity stored?
```

Example:

```text
Product: Gloves
Linked departments: Pharmacy, Lab, Theatre

Stock balances:
- Pharmacy Stock Location: 50
- Lab Stock Location: 100
- Theatre Stock Location: 200
```

When Lab uses gloves, deduct from Lab stock.

When Theatre uses gloves, deduct from Theatre stock.

Do not deduct from Main Store unless the user/action belongs to the Store location.

---

# 9. Stock Movement System

Stock movements remain the source of truth.

```text
stock_movements = source of truth
stock_balances = fast current stock cache
```

Current stock formula:

```text
Current Stock = Total IN movements - Total OUT movements
```

Do not use product quantity as current stock.

---

# 10. Stock Movement Tables

Create or update:

## stock_movements

```text
id
product_id
stock_location_id
movement_type
direction
quantity
unit_cost nullable
batch_no nullable
expiry_date nullable
source_type nullable
source_id nullable
performed_by nullable
movement_date
notes nullable
created_at
updated_at
```

Direction:

```text
IN
OUT
```

Movement types:

```text
OPENING_STOCK
PURCHASE_RECEIVED
PHARMACY_DISPENSED
INVESTIGATION_CONSUMED
PROCEDURE_CONSUMED
WARD_CONSUMED
TRANSFER_IN
TRANSFER_OUT
RETURN_IN
RETURN_OUT
ADJUSTMENT_IN
ADJUSTMENT_OUT
DAMAGED
EXPIRED
REVERSAL_IN
REVERSAL_OUT
```

## stock_balances

```text
id
product_id
stock_location_id
quantity_on_hand
last_movement_at
created_at
updated_at
```

Add unique constraint:

```text
product_id + stock_location_id
```

---

# 11. Stock Movement Services

Create or update:

```text
StockMovementService
StockBalanceService
StockLocationService
StockTransferService
StockAdjustmentService
StockReturnService
ConsumableUsageService
```

## StockLocationService

Required method:

```php
getDefaultLocationForDepartment(Department $department): StockLocation
```

## StockMovementService

Must:

* validate product
* validate stock location
* validate direction
* validate movement type
* validate quantity > 0
* block OUT movement if insufficient stock unless override is explicitly allowed
* create stock movement
* update stock balance
* preserve source_type/source_id
* preserve performed_by

## StockBalanceService

Must:

* get current stock from stock_balances
* update stock balance after each movement
* rebuild balances from stock_movements if needed

Required methods:

```php
getCurrentStock($productId, $locationId): float
increase($productId, $locationId, float $quantity): void
decrease($productId, $locationId, float $quantity): void
rebuildBalance($productId, $locationId): void
rebuildAllBalances(): void
```

---

# 12. Store / Procurement Responsibilities

Only Store/Procurement or Admin can:

* create products
* edit products
* link products to departments
* receive stock from suppliers
* transfer stock to department locations
* process returns to supplier
* perform authorized stock adjustments
* manage damaged/expired stock
* view supplier ledger

Department users should not create products.

---

# 13. Pharmacy Catalogue

Pharmacy should no longer have its own `drugs` table.

The Pharmacy Drugs Catalogue should load from products:

```text
products
WHERE product_department.department_id = Pharmacy department
AND product_type = DRUG
```

Rules:

* Pharmacy cannot create drugs directly.
* Pharmacy can only view/use drug products linked to Pharmacy.
* Pharmacy dispensing deducts stock from Pharmacy stock location.
* Dispensing creates stock movement:

```text
movement_type = PHARMACY_DISPENSED
direction = OUT
stock_location = Pharmacy Stock Location
source_type = dispensing_item / prescription_item
```

Prescribing does not reduce stock.

Only dispensing reduces stock.

---

# 14. Investigation Items / Consumables

Investigation/Lab item lists should load from products, not a separate lab item table.

For investigation departments:

```text
products
WHERE product_department.department_id = current investigation department
AND product_type IN (CONSUMABLE, REAGENT, MEDICAL_SUPPLY, GENERAL_ITEM)
```

Rules:

* Lab/Investigation users cannot create products.
* Investigation consumables must be linked to the investigation department.
* Investigation result entry consumes stock from the investigation department’s stock location.
* Result entry creates stock movement:

```text
movement_type = INVESTIGATION_CONSUMED
direction = OUT
source_type = investigation_result
```

---

# 15. Procedure / Theatre Consumables

Procedure/Theatre consumables should load from products, not separate procedure item tables.

For procedure departments:

```text
products
WHERE product_department.department_id = procedure/theatre department
AND product_type IN (CONSUMABLE, SURGICAL_SUPPLY, MEDICAL_SUPPLY, GENERAL_ITEM)
```

Rules:

* Theatre users cannot create products.
* Procedure consumables must be linked to the procedure/theatre department.
* Procedure consumable usage deducts from Theatre/Procedure stock location.
* Procedure usage creates stock movement:

```text
movement_type = PROCEDURE_CONSUMED
direction = OUT
source_type = procedure_request / procedure_record
```

---

# 16. Services vs Products

Keep this separation strict.

## Services

Billable hospital activities:

```text
General Consultation
Full Blood Count
Malaria Test
Appendectomy
Chest X-Ray
Caesarean Section
```

## Products

Physical stock items:

```text
Paracetamol
Gloves
EDTA Tube
Malaria RDT Kit
Sutures
Gauze
X-ray Film
```

Rules:

* Investigation Catalogue is based on services.
* Procedure Catalogue is based on services.
* Products are only linked as default consumables or physical stock items.
* Do not duplicate services as products.
* Do not duplicate products as services unless they are intentionally billable as a separate service/product sale through billing logic.

---

# 17. Investigation Catalogue

Investigation Catalogue must load:

```text
services where department.type = investigation
```

Example services:

```text
Full Blood Count
Malaria Test
Liver Function Test
Chest X-Ray
Abdominal Scan
```

For each investigation service, allow configuration of:

* headers/categories
* criteria
* default consumables/products

Correct structure:

```text
Investigation Service
    → Headers / Categories
    → Criteria
    → Default Products / Consumables
```

Default consumables must be selected from `products` linked to that investigation department.

Do not create separate catalogue tests detached from services.

---

# 18. Procedure Catalogue

Procedure Catalogue must load:

```text
services where department.type = procedure
```

or if the project uses theatre terminology:

```text
services where department.type = theatre
```

Use the existing department type naming convention.

Example services:

```text
Appendectomy
Caesarean Section
Wound Debridement
Suturing
Hernia Repair
```

For each procedure service, allow configuration of:

* report templates
* template sections
* template fields
* default consumables/products

Correct structure:

```text
Procedure Service
    → Template Sections
    → Template Fields
    → Default Products / Consumables
```

Do not create separate procedure catalogue items detached from services.

---

# 19. Procedure Templates

For each procedure service, configure templates for:

```text
PRE_OP
ANAESTHESIA
OPERATIVE_NOTE
POST_OP
FULL_REPORT
```

Suggested tables:

## procedure_template_sections

```text
id
service_id
template_type
name
description nullable
sort_order
is_active
created_at
updated_at
```

## procedure_template_fields

```text
id
service_id
section_id nullable
template_type
label
field_key
input_type
options nullable
default_value nullable
is_required
sort_order
is_active
created_at
updated_at
```

Input types:

```text
text
textarea
number
select
checkbox
date
time
datetime
file
```

## procedure_template_values

```text
id
procedure_request_id
service_id
template_field_id
template_type
value nullable
recorded_by
recorded_at
created_at
updated_at
```

Rules:

* Templates are configured per procedure service.
* Values are saved against procedure request.
* Required fields must be completed before moving to the next relevant procedure stage.
* Future template changes must not corrupt old procedure records.

---

# 20. Service Default Consumables

Both Investigation services and Procedure services can have default consumables.

Create or update:

```text
service_consumables
- id
- service_id
- product_id
- default_quantity
- is_required
- notes nullable
- created_at
- updated_at
```

Rules:

* Product must be linked to the department that owns the service.
* Default consumables do not deduct stock.
* Actual consumable usage deducts stock.
* Default consumables are preloaded during result/procedure entry.
* Users may confirm, adjust, remove, or add actual usage if allowed.

---

# 21. Actual Consumable Usage

Create or update:

```text
consumable_usages
- id
- visit_id
- patient_id
- service_id
- source_type
- source_id
- product_id
- stock_location_id
- quantity_used
- stock_movement_id nullable
- used_by
- used_at
- notes nullable
- created_at
- updated_at
```

Examples:

```text
source_type = investigation_result
source_id = investigation_results.id

source_type = procedure_request
source_id = procedure_requests.id

source_type = ward_care
source_id = ward_care_records.id
```

Rules:

* Actual usage creates stock OUT movement.
* Usage must link to created stock movement.
* Product must be available to the department.
* Stock location must match the service department.
* If stock is insufficient, block unless authorized override exists.

---

# 22. Investigation Result Consumable Workflow

When entering investigation results:

1. Identify the investigation service.
2. Identify the service department.
3. Get the department stock location.
4. Load default consumables from `service_consumables`.
5. Allow user to confirm/edit actual quantities.
6. Save result values.
7. Save consumable usages.
8. Create `INVESTIGATION_CONSUMED` OUT movements.
9. Update stock balances.

Do not deduct from Main Store unless the investigation department actually uses Main Store, which should not normally happen.

---

# 23. Procedure Consumable Workflow

When recording procedure/theatre stages:

1. Identify procedure service.
2. Identify procedure/theatre department.
3. Get the department stock location.
4. Load default consumables from `service_consumables`.
5. Allow user to confirm/edit actual quantities.
6. Save procedure template/clinical values.
7. Save consumable usages.
8. Create `PROCEDURE_CONSUMED` OUT movements.
9. Update stock balances.

Do not deduct from Pharmacy stock or Main Store unless that is the configured department stock location.

---

# 24. Supplier Ledger

Build a supplier ledger to track everything happening with suppliers.

## suppliers

```text
id
name
phone nullable
email nullable
address nullable
contact_person nullable
tax_id nullable
status
created_at
updated_at
```

## supplier_ledger_entries

```text
id
supplier_id
entry_date
entry_type
source_type nullable
source_id nullable
description
debit
credit
balance_after nullable
created_by
created_at
updated_at
```

Entry types:

```text
PURCHASE_ORDER
GOODS_RECEIVED
SUPPLIER_INVOICE
PAYMENT
RETURN_TO_SUPPLIER
CREDIT_NOTE
DEBIT_NOTE
ADJUSTMENT
```

Recommended simple convention:

```text
credit = amount facility owes supplier
debit = amount paid/reduced
```

Examples:

Goods received worth 5,000:

```text
credit = 5000
```

Payment to supplier of 2,000:

```text
debit = 2000
```

Return to supplier worth 500:

```text
debit = 500
```

Outstanding supplier balance:

```text
credits - debits
```

---

# 25. Supplier Ledger Workflow

## Purchase Order Created

Purchase order creation may optionally create a commitment record, but should not necessarily affect supplier balance unless the system requires commitments.

## Goods Received

When purchase items are received:

1. Create `PURCHASE_RECEIVED` stock movement.
2. Update stock balance.
3. Create supplier ledger credit entry.
4. Update supplier balance.
5. Update purchase order received status.

## Supplier Payment

When supplier is paid:

1. Create supplier ledger debit entry.
2. Link to payment record if payment table exists.
3. Update supplier balance.

## Return to Supplier

When stock is returned to supplier:

1. Create `RETURN_OUT` stock movement.
2. Reduce stock balance.
3. Create supplier ledger debit or credit note entry.
4. Link to supplier.
5. Record reason.

---

# 26. Supplier Ledger UI

Create supplier pages:

```text
Suppliers
Supplier Details
Supplier Ledger
Supplier Payments
Supplier Returns
```

Supplier ledger view should show:

```text
Date
Type
Description
Debit
Credit
Balance
Source
Created By
```

Example:

```text
Supplier: MedSupply Ltd

Date        Type              Description              Debit    Credit    Balance
01/02/26    Goods Received     PO-0001 received          0        5000      5000
05/02/26    Payment            Bank payment              2000     0         3000
07/02/26    Return             Expired stock returned    500      0         2500
```

---

# 27. Services to Create or Update

Create/update:

```text
ProductService
ProductDepartmentService
StockLocationService
StockMovementService
StockBalanceService
StockTransferService
StockAdjustmentService
StockReturnService
ConsumableUsageService
ServiceConsumableService
InvestigationCatalogueService
ProcedureCatalogueService
ProcedureTemplateService
SupplierService
SupplierLedgerService
PurchaseOrderService
```

## ProductService

Handles product creation and updates.

## ProductDepartmentService

Handles product availability per department.

## StockLocationService

Handles department stock location resolution.

## ConsumableUsageService

Handles actual product usage and stock OUT movement.

## SupplierLedgerService

Handles supplier ledger entries and balance calculations.

Required method examples:

```php
StockLocationService::getDefaultLocationForDepartment(Department $department): StockLocation

ConsumableUsageService::recordUsageForSource(
    Visit $visit,
    Service $service,
    string $sourceType,
    int $sourceId,
    array $items,
    User $user
): void

SupplierLedgerService::recordEntry(
    Supplier $supplier,
    string $entryType,
    float $debit,
    float $credit,
    string $description,
    ?string $sourceType = null,
    ?int $sourceId = null,
    ?User $user = null
): SupplierLedgerEntry
```

---

# 28. Validation Rules

## Product

* name required
* code unique if used
* product_type required
* unit required
* only Store/Admin can create or edit products
* department links must reference valid departments

## Product Department Link

* product required
* department required
* duplicate active links should be prevented

## Stock Location

* name required
* department required
* main stock location must belong to Store department
* location must be active before use

## Consumable Usage

* product required
* product must be linked to service department
* stock location must match service department
* quantity_used > 0
* sufficient stock required unless override allowed
* required consumables must be confirmed

## Procedure Catalogue

* service must belong to procedure/theatre-type department
* template section name required
* template field label required
* input_type required
* required fields must have values before completing relevant stage

## Supplier Ledger

* supplier required
* entry_type required
* debit and credit must be >= 0
* debit and credit cannot both be zero unless allowed for memo entries
* source_type/source_id should be stored when entry comes from purchase, receipt, return, or payment

---

# 29. Permissions

Add or verify permissions:

```text
product.create
product.edit
product.view
product.link_departments
stock_location.manage
stock.view
stock.transfer
stock.adjust
stock.return
stock.override_negative
service_consumable.manage
consumable_usage.record
procedure_catalogue.view
procedure_catalogue.manage
procedure_template.manage
supplier.view
supplier.create
supplier.edit
supplier.ledger.view
supplier.payment.create
supplier.return.create
```

Rules:

* Pharmacy users cannot create products.
* Lab users cannot create products.
* Theatre users cannot create products.
* Only Store/Admin can create products and link them to departments.
* Department users can consume only products assigned to their department.

---

# 30. Frontend / Inertia Pages

Create or update:

## Store / Products

```text
Products/Index.vue
Products/Create.vue
Products/Edit.vue
Products/Show.vue
Products/DepartmentLinks.vue
```

## Stock

```text
StockLocations/Index.vue
StockMovements/Index.vue
StockBalances/Index.vue
StockTransfers/Index.vue
StockAdjustments/Index.vue
StockReturns/Index.vue
```

## Department Catalogues

```text
Pharmacy/ProductCatalogue.vue
Investigations/Consumables.vue
Procedures/Consumables.vue
```

These should read from `products`, filtered by department and product type.

## Investigation Catalogue

Update to support:

* services from investigation departments
* headers/categories
* criteria
* default consumables from products

## Procedure Catalogue

Create/update:

```text
ProcedureCatalogue/Index.vue
ProcedureCatalogue/Show.vue
ProcedureCatalogue/Templates.vue
ProcedureCatalogue/Consumables.vue
```

## Supplier Ledger

```text
Suppliers/Index.vue
Suppliers/Show.vue
Suppliers/Ledger.vue
Suppliers/Payments.vue
Suppliers/Returns.vue
```

---

# 31. Data Integrity Rules

* Product is the only table for physical items.
* Do not create `drugs`, `lab_items`, `procedure_items`, or standalone `consumables` tables.
* Services are billable hospital activities.
* Products are stock items.
* Department access to products must use product_department.
* Department stock usage must deduct from that department’s stock location.
* Stock movements are the source of truth.
* Stock balances are cache only.
* Purchase receiving affects stock.
* Purchase order creation alone does not affect stock unless explicitly received.
* Default consumables do not affect stock.
* Actual consumable usage affects stock.
* Investigation Catalogue is service-based.
* Procedure Catalogue is service-based.
* Supplier ledger records supplier-related financial/stock events.

---

# 32. Performance Rules

* Use stock_balances for current stock display.
* Do not calculate current stock from all movements on every page.
* Eager-load product departments where needed.
* Load default consumables only for selected services.
* Paginate products, movements, suppliers, and ledger entries.
* Avoid N+1 queries in catalogues and stock pages.
* Cache product availability per department where safe.

---

# 33. Tests / Verification

Add or update tests for:

1. Store/Admin can create product.
2. Pharmacy cannot create product.
3. Lab cannot create product.
4. Theatre cannot create product.
5. Product can be linked to multiple departments.
6. Department only sees linked products.
7. Pharmacy Drugs Catalogue loads products linked to Pharmacy with type DRUG.
8. Investigation consumables load products linked to investigation department.
9. Procedure consumables load products linked to procedure department.
10. Stock location belongs to department.
11. Pharmacy dispensing deducts from Pharmacy stock location.
12. Investigation result consumables deduct from investigation department stock location.
13. Procedure consumables deduct from procedure department stock location.
14. Investigation Catalogue loads services from investigation-type departments.
15. Procedure Catalogue loads services from procedure/theatre-type departments.
16. Procedure template fields save and load correctly.
17. Goods received creates supplier ledger credit.
18. Supplier payment creates supplier ledger debit.
19. Return to supplier creates stock OUT and supplier ledger entry.
20. Supplier balance is calculated correctly.

---

# 34. Deliverables

Provide:

1. New/updated migrations.
2. New/updated models and relationships.
3. Product architecture implementation.
4. Product-department linking.
5. Department stock location implementation.
6. Stock movement/balance updates.
7. Pharmacy catalogue reading from products.
8. Investigation consumables reading from products.
9. Procedure consumables reading from products.
10. Investigation Catalogue service-based update.
11. Procedure Catalogue service-based implementation.
12. Procedure templates implementation.
13. Service default consumables implementation.
14. Actual consumable usage implementation.
15. Supplier ledger implementation.
16. Updated Vue/Inertia pages.
17. Updated validation requests.
18. Updated permissions.
19. Tests or verification notes.
20. List of modified files.
21. Remaining TODOs if any.

---

# 35. Important Rules

Do not create a drugs table.

Do not create lab_items table.

Do not create procedure_items table.

Do not create standalone consumables table for physical items.

Do not let departments create products.

Do not use product quantity as current stock.

Do not deduct stock from wrong department location.

Do not create Procedure Catalogue items detached from services.

Do not create Investigation Catalogue tests detached from services.

Do not deduct default consumables until actual usage is saved.

Do not bypass StockMovementService.

Do not bypass StockBalanceService.

Do not bypass SupplierLedgerService for supplier events.

Now inspect the current UHMS implementation and apply this architecture carefully.
