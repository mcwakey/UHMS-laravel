You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to make broad system and UI changes so UHMS properly supports the new **Product-as-single-source** architecture, department stock locations, service-based Investigation/Procedure catalogues, consumable usage, and supplier ledger.

Focus on aligning UI, workflows, permissions, validation, and backend logic with this architecture.

Do not refactor unrelated modules.

---

# 1. Core Architecture Rule

Apply this rule everywhere:

```text
Products = physical items
Services = billable hospital activities
```

## Products

A product is anything physically stocked, purchased, transferred, dispensed, consumed, returned, damaged, or expired.

Examples:

```text
Paracetamol
Ceftriaxone
Gloves
EDTA Tube
Malaria RDT Kit
Sutures
Gauze
Syringe
X-ray Film
Oxygen Mask
```

## Services

A service is a billable hospital activity.

Examples:

```text
General Consultation
Full Blood Count
Malaria Test
Appendectomy
Chest X-Ray
Caesarean Section
Wound Dressing
```

Important:

* Do not create `drugs`, `lab_items`, `procedure_items`, or standalone `consumables` tables.
* Products must handle all physical items.
* Services must handle all billable hospital activities.
* Investigation Catalogue is service-based.
* Procedure Catalogue is service-based.

---

# 2. Main Objective

Update the system so that:

1. Store/Procurement is the only place that creates products.
2. Products can be linked to one or more departments.
3. Department users can only see/use products linked to their department.
4. Stock locations are linked to departments.
5. Departments consume stock from their own stock location only.
6. Pharmacy catalogue loads drugs from `products`.
7. Investigation consumables load from `products`.
8. Procedure consumables load from `products`.
9. Investigation Catalogue loads services from investigation-type departments.
10. Procedure Catalogue loads services from procedure/theatre-type departments.
11. Default consumables are configured per service using products.
12. Actual consumable usage creates stock movements.
13. Supplier ledger tracks supplier-related purchases, payments, returns, and balances.
14. UI menus and labels must reflect this new architecture clearly.

---

# 3. Menu and UI Restructure

## Store / Procurement Menu

Update Store/Procurement menu to be the master place for products and stock:

```text
Store / Procurement
├── Dashboard
├── Products
│   ├── All Products
│   ├── Create Product
│   ├── Product Types / Categories
│   └── Department Availability
│
├── Stock Locations
├── Stock Balances
├── Stock Movements
├── Purchase Orders
├── Goods Receiving
├── Stock Transfers
├── Stock Adjustments
├── Stock Returns
├── Damaged / Expired Stock
├── Suppliers
└── Supplier Ledger
```

## Pharmacy Menu

Pharmacy must not create drugs.

Recommended Pharmacy menu:

```text
Pharmacy
├── Dashboard
├── Drug Catalogue
├── Pending Prescriptions
├── Dispensing
├── Dispensing History
└── Stock Balance
```

`Drug Catalogue` must read from:

```text
products linked to Pharmacy department
AND product_type = DRUG
```

## Investigation Menu

Recommended Investigation menu:

```text
Investigations
├── Dashboard
├── Requests
├── Result Entry
├── Results
├── Investigation Catalogue
├── Department Consumables
└── Stock Balance
```

`Department Consumables` must read from products linked to the investigation department.

`Investigation Catalogue` must read from services under investigation-type departments.

## Theatre / Procedure Menu

Recommended Theatre/Procedure menu:

```text
Theatre / Procedures
├── Dashboard
├── Procedure Requests
├── Theatre Schedule
├── In Theatre
├── Procedure Catalogue
├── Procedure Consumables
├── Theatre Rooms
├── Completed Procedures
└── Stock Balance
```

`Procedure Consumables` must read from products linked to the procedure/theatre department.

`Procedure Catalogue` must read from services under procedure/theatre-type departments.

---

# 4. Rename Confusing Labels

Update UI labels to avoid confusion.

| Old / Confusing Label | New Label                                |
| --------------------- | ---------------------------------------- |
| Add Drug              | Add Product                              |
| Drugs Table           | Pharmacy Drug Catalogue                  |
| Lab Items             | Investigation Consumables                |
| Procedure Items       | Procedure Consumables                    |
| Theatre Items         | Procedure Consumables                    |
| Test Catalogue        | Investigation Catalogue                  |
| Lab Test Catalogue    | Investigation Catalogue                  |
| Quantity              | Stock Balance / Quantity on Hand         |
| Product Quantity      | Current Stock by Location                |
| Add Lab Item          | Link Product to Investigation Department |
| Add Procedure Item    | Link Product to Procedure Department     |

Important:

* Product creation must only appear under Store/Procurement.
* Department catalogues should be read-only views of products assigned to that department unless the user has Store/Admin permission.

---

# 5. Product Form Requirements

Under Store/Procurement, Product form should include:

```text
Product Name
Product Code
Product Type
Unit
Description
Reorder Level
Status
Departments where product is available
Opening Stock optional
Opening Stock Location
Supplier optional
```

Product type options:

```text
DRUG
CONSUMABLE
REAGENT
SURGICAL_SUPPLY
MEDICAL_SUPPLY
EQUIPMENT
GENERAL_ITEM
```

Rules:

* If opening stock is entered, create an `OPENING_STOCK` stock movement.
* Do not save opening stock as current stock.
* Do not use product quantity as current stock.
* Current stock must come from `stock_balances`.

---

# 6. Product Department Availability UI

Create a clear UI for linking products to departments.

On Product Details page, show:

```text
Available Departments
[✓] Pharmacy
[✓] Laboratory
[ ] Theatre
[✓] Ward
[ ] X-ray
```

Or provide a Department Availability page:

```text
Products Available to Laboratory
- Gloves
- Malaria RDT Kit
- EDTA Tube
```

Rules:

* Store/Admin can link products to departments.
* Department users cannot create/link products unless authorized.
* Department screens must only load linked products.

---

# 7. Stock Location UI

Each stock location must show:

```text
Name
Department
Is Main Store?
Is Active?
Current products/balances
```

Rules:

* Main Store must be linked to Store department.
* Only one Main Store should exist unless system settings allow multiple.
* Department users should not change stock locations.
* Store/Admin manages stock locations.

---

# 8. Stock Balance UI

Stock balance must be shown by product and location.

Example:

```text
Product       Location       Quantity on Hand
Gloves        Main Store     1000
Gloves        Pharmacy       50
Gloves        Lab            120
Gloves        Theatre        300
```

Do not display old product quantity as current stock.

Current stock must come from:

```text
stock_balances.quantity_on_hand
```

or:

```php
StockBalanceService::getCurrentStock(...)
```

---

# 9. Stock Transfer UI

Transfer page must enforce source and destination rules.

Allowed initially:

```text
Main Store → Department Location
Department Location → Main Store
```

Blocked initially:

```text
Pharmacy → Lab
Lab → Theatre
Ward → Pharmacy
Department → Department
```

unless inter-department transfer is explicitly enabled.

Transfer form should include:

```text
Source Location
Destination Location
Product
Available Quantity
Transfer Quantity
Notes
```

When source location and product are selected, display current stock.

Transfer must create:

```text
TRANSFER_OUT from source
TRANSFER_IN into destination
```

Both movements must link to the same transfer record.

---

# 10. Purchase Order and Receiving UI

Creating a purchase order must not automatically increase stock.

Flow:

```text
Purchase Order Created
↓
Pending
↓
Goods Receiving
↓
Stock Movement Created
↓
Stock Balance Updated
↓
Supplier Ledger Updated
```

## Purchase Order Form

Show:

```text
Supplier
Order Date
Items
Expected Quantity
Unit Cost
Status
```

## Goods Receiving Form

Show:

```text
Purchase Order
Items Ordered
Quantity Already Received
Quantity to Receive
Receiving Location, usually Main Store
Batch Number
Expiry Date
Unit Cost
```

When received:

* create `PURCHASE_RECEIVED` movement
* update stock balance
* create supplier ledger entry
* update purchase order status

---

# 11. Supplier Ledger UI

Create/update supplier pages:

```text
Suppliers
Supplier Details
Supplier Ledger
Supplier Payments
Supplier Returns
```

Supplier detail page should show:

```text
Supplier Profile
Purchase Orders
Goods Received
Payments
Returns
Outstanding Balance
Ledger
```

Ledger columns:

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

Use this convention unless project already defines another:

```text
credit = amount facility owes supplier
debit = amount paid/reduced
```

Examples:

Goods received worth 5,000:

```text
credit = 5000
```

Supplier payment of 2,000:

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

# 12. Pharmacy UI Changes

Pharmacy must use products, not a drugs table.

## Drug Catalogue

Load:

```text
products linked to Pharmacy department
AND product_type = DRUG
```

Pharmacy users should not see Create Product button unless they have Store/Admin permission.

## Dispensing Page

When dispensing, show stock from Pharmacy stock location only:

```text
Drug: Paracetamol
Available in Pharmacy: 50
Quantity to dispense: 10
```

Rules:

* If Pharmacy has 0 but Main Store has 100, pharmacy cannot dispense.
* Stock must be transferred to Pharmacy first.
* Dispensing creates `PHARMACY_DISPENSED` OUT movement from Pharmacy stock location.
* Prescribing does not affect stock.
* Payment does not affect stock.

---

# 13. Investigation Result Entry UI

When entering results, add a Consumables Used section.

Example:

```text
Consumables Used
[✓] Malaria RDT Kit    Qty: 1
[✓] Gloves             Qty: 1
[✓] Lancet             Qty: 1
[+] Add Consumable
```

Rules:

* Preload default consumables from the investigation service.
* Consumable list must load products linked to that investigation department.
* Saving result should also save actual consumable usage.
* Actual usage creates `INVESTIGATION_CONSUMED` OUT movements.
* Deduct from the investigation department stock location.
* Do not deduct from Main Store.

If stock is insufficient:

* block save unless authorized override exists
* show clear error message

---

# 14. Procedure / Theatre UI Changes

On procedure workflow pages, add a Consumables Used section.

Example:

```text
Procedure Consumables Used
[✓] Surgical Gloves    Qty: 4
[✓] Sutures            Qty: 2
[✓] Gauze              Qty: 10
[+] Add Consumable
```

Rules:

* Preload default consumables from the procedure service.
* Consumables list must load products linked to procedure/theatre department.
* Saving usage creates `PROCEDURE_CONSUMED` OUT movements.
* Deduct from procedure/theatre stock location.
* Do not deduct from Pharmacy or Main Store unless configured as that department’s stock location.

---

# 15. Investigation Catalogue UI

Investigation Catalogue should show:

```text
Investigation Services
```

It must load:

```text
services where department.type = investigation
```

When a service is selected, show sections:

```text
Service Details
Headers / Categories
Criteria
Default Consumables
Preview
```

Default Consumables must load products linked to the selected service’s department.

Do not create detached tests.

---

# 16. Procedure Catalogue UI

Procedure Catalogue should mirror Investigation Catalogue.

First page:

```text
Procedure Services
```

It must load:

```text
services where department.type = procedure
```

or:

```text
services where department.type = theatre
```

depending on the existing department type naming.

When selected, show sections:

```text
Service Details
Templates
Template Sections
Template Fields
Default Consumables
Preview Report
```

Default Consumables must load products linked to the selected service’s department.

Do not create detached procedure items.

---

# 17. Billing UI Changes

Billing must clearly distinguish service billing and product billing.

Invoice item source may include:

```text
consultation_service
investigation_service
procedure_service
pharmacy_product
ward_consumable
```

Invoice item descriptions should be clear:

```text
General Consultation
Full Blood Count
Appendectomy
Paracetamol 500mg
Surgical Gloves
```

Rules:

* Services are billed as services.
* Dispensed products can create invoice items when billable.
* Consumables may or may not be billable depending on configuration.
* Backend must know source type and source ID.
* Billing must still use the visit’s single invoice.

---

# 18. Permissions

Add or verify permissions:

```text
product.create
product.edit
product.link_department
product.view

stock.location.manage
stock.transfer
stock.adjust
stock.receive
stock.return
stock.view_balance

supplier.manage
supplier.ledger.view
supplier.payment.create
supplier.return.create

consumable.use

procedure.catalogue.manage
investigation.catalogue.manage
```

Permission rules:

* Store/Admin can create products.
* Store/Admin can link products to departments.
* Pharmacy can view pharmacy products and dispense.
* Lab can view linked consumables and consume during results.
* Theatre can view linked consumables and consume during procedures.
* Departments cannot create products unless explicitly authorized.

---

# 19. Dashboard Changes

## Store Dashboard

Show:

```text
Total products
Low stock items
Pending purchase orders
Recent stock movements
Supplier balances
Pending transfers
```

## Pharmacy Dashboard

Show:

```text
Pending prescriptions
Low pharmacy stock
Dispensed today
Pharmacy stock value
```

## Lab / Investigation Dashboard

Show:

```text
Pending investigations
Results pending
Low consumables
Consumables used today
```

## Theatre Dashboard

Show:

```text
Pending procedures
Scheduled procedures
Low theatre supplies
Procedures completed today
```

---

# 20. Validation Rules

Enforce these validations:

## Product

* product name required
* product type required
* unit required
* only Store/Admin can create product

## Product Department Link

* product required
* department required
* duplicate active links should be prevented

## Department Usage

* product must be linked to the user/service department
* department must have active stock location
* cannot consume more than available stock unless override enabled

## Transfer

* source required
* destination required
* source and destination must be different
* transfer must involve Main Store unless inter-department transfer is enabled
* quantity must be greater than zero
* source must have enough stock

## Service Consumables

* product must be linked to the service department
* default quantity must be greater than zero
* required consumables must be confirmed before finalization if configured

## Purchase Receiving

* purchase order required
* receiving location required
* received quantity must be greater than zero
* received quantity must not exceed remaining ordered quantity unless over-receiving is allowed

## Supplier Ledger

* supplier required
* entry type required
* debit/credit must be valid
* source should be linked where possible

---

# 21. Backend Services to Use

Create or update these services:

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

Important:

* Product creation must go through `ProductService`.
* Product-department linking must go through `ProductDepartmentService`.
* Stock usage must go through `StockMovementService`.
* Current stock must use `StockBalanceService`.
* Consumable usage must go through `ConsumableUsageService`.
* Supplier events must go through `SupplierLedgerService`.

---

# 22. Required Service Behavior

## StockLocationService

Must resolve the correct stock location for a department:

```php
getDefaultLocationForDepartment(Department $department): StockLocation
```

## ConsumableUsageService

Must record actual consumable use:

```php
recordUsageForSource(
    Visit $visit,
    Service $service,
    string $sourceType,
    int $sourceId,
    array $items,
    User $user
): void
```

This method must:

* validate department access
* validate product availability
* validate stock location
* validate quantity
* create consumable usage records
* create stock OUT movements
* update stock balances

## SupplierLedgerService

Must record supplier events:

```php
recordEntry(
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

# 23. Frontend / Inertia Pages

Create or update these page groups.

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
GoodsReceiving/Index.vue
```

## Supplier Ledger

```text
Suppliers/Index.vue
Suppliers/Show.vue
Suppliers/Ledger.vue
Suppliers/Payments.vue
Suppliers/Returns.vue
```

## Pharmacy

```text
Pharmacy/DrugCatalogue.vue
Pharmacy/Dispensing.vue
Pharmacy/StockBalance.vue
```

## Investigations

```text
Investigations/Consumables.vue
Investigations/ResultEntry.vue
InvestigationCatalogue/Index.vue
InvestigationCatalogue/Show.vue
```

## Procedures

```text
Procedures/Consumables.vue
ProcedureCatalogue/Index.vue
ProcedureCatalogue/Show.vue
ProcedureCatalogue/Templates.vue
ProcedureCatalogue/Consumables.vue
```

---

# 24. Data Integrity Rules

* Product is the only source for physical items.
* Services are the only source for billable hospital activities.
* Product-department links control product availability.
* Stock locations control physical quantity by department.
* Department consumption must use department stock location.
* Stock movements are the source of truth.
* Stock balances are cache only.
* Default consumables do not deduct stock.
* Actual usage deducts stock.
* Purchase order creation does not increase stock.
* Goods receiving increases stock.
* Supplier ledger tracks supplier events.
* Investigation Catalogue must be service-based.
* Procedure Catalogue must be service-based.
* Do not create detached tests/procedure items.

---

# 25. Performance Rules

* Use `stock_balances` for current stock display.
* Do not calculate stock from all movements on every page.
* Eager-load product departments where needed.
* Load consumables only for selected service.
* Paginate product, movement, supplier, and ledger lists.
* Avoid N+1 queries in catalogues and stock pages.
* Cache product availability per department where safe.

---

# 26. Testing / Verification

Add or update tests for:

1. Store/Admin can create product.
2. Pharmacy cannot create product.
3. Lab cannot create product.
4. Theatre cannot create product.
5. Product can be linked to departments.
6. Department sees only linked products.
7. Pharmacy Drug Catalogue loads products linked to Pharmacy with type DRUG.
8. Investigation consumables load products linked to investigation department.
9. Procedure consumables load products linked to procedure department.
10. Stock location belongs to department.
11. Pharmacy dispensing deducts from Pharmacy stock location.
12. Investigation result consumables deduct from investigation department stock location.
13. Procedure consumables deduct from procedure department stock location.
14. Main Store can transfer to department.
15. Department can return stock to Main Store.
16. Purchase receiving creates stock IN movement.
17. Goods received creates supplier ledger credit.
18. Supplier payment creates supplier ledger debit.
19. Return to supplier creates stock OUT and supplier ledger entry.
20. Investigation Catalogue loads services from investigation departments.
21. Procedure Catalogue loads services from procedure/theatre departments.
22. Default consumables only load linked products.
23. Actual consumable usage creates stock movement.
24. Stock balance updates correctly after usage.

---

# 27. Deliverables

Provide:

1. Gap analysis of current implementation.
2. Updated menus/navigation.
3. Updated labels.
4. New/updated migrations.
5. Updated models and relationships.
6. Updated services.
7. Updated permissions.
8. Updated Vue/Inertia pages.
9. Updated validation requests.
10. Updated Store/Product UI.
11. Updated Pharmacy product catalogue.
12. Updated Investigation consumable flow.
13. Updated Procedure consumable flow.
14. Updated Investigation Catalogue.
15. Updated Procedure Catalogue.
16. Updated Supplier Ledger.
17. Tests or verification notes.
18. List of modified files.
19. Remaining TODOs if any.

---

# 28. Important Rules

Do not create a drugs table.

Do not create lab_items table.

Do not create procedure_items table.

Do not create standalone consumables table.

Do not allow Pharmacy/Lab/Theatre to create products.

Do not use product quantity as current stock.

Do not deduct stock from Main Store when department stock should be used.

Do not allow direct department-to-department transfer unless explicitly enabled.

Do not create Investigation Catalogue tests detached from services.

Do not create Procedure Catalogue items detached from services.

Do not deduct default consumables until actual usage is saved.

Do not bypass StockMovementService.

Do not bypass StockBalanceService.

Do not bypass SupplierLedgerService.

Do not break existing billing, pharmacy, investigation, and procedure workflows.

Now inspect the current UHMS implementation and apply these changes carefully, module by module.

Make all the relevace changes