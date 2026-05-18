You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

The current stock implementation is still wrong and inconsistent.

We already agreed on this rule:

```text
Every physical item in the hospital is a Product.
There must be only one inventory system.
```

But the system is still behaving like it has parallel inventory systems:

```text
Drug catalogue shows quantity
Product Stock Balances does not show the same quantity

Investigation Items page shows nothing
Products linked to Investigation department already exist

Investigation Items page still has Add New Item button
Departments are still able to create items outside Products
```

This must be fixed completely.

Focus only on fixing the unified product-based inventory system and department product catalogues. Do not refactor unrelated modules.

---

# 1. Non-Negotiable Rule

There must be only one inventory system.

Use only:

```text
products
stock_movements
stock_balances
stock_locations
product_department
```

Do not use active workflows based on:

```text
drugs
drug_stock
stock_movements.drug_id
stock_balances.drug_id
product_stock_movements
product_stock_balances
investigation_items
procedure_items
lab_items
standalone consumables table
```

If these old tables or models still physically exist during development, stop using them in active workflows.

---

# 2. Product Is the Only Physical Item

Every physical item must be stored in `products`.

Examples:

```text
Paracetamol
Ceftriaxone
Gloves
Malaria RDT Kit
EDTA Tube
Sutures
Gauze
Syringe
X-ray Film
Oxygen Mask
```

Product types:

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

* Pharmacy drugs are products with `product_type = DRUG`.
* Lab/investigation consumables are products linked to investigation department.
* Theatre/procedure consumables are products linked to procedure/theatre department.
* Ward consumables are products linked to ward department.
* Store/Admin creates products.
* Departments only consume/view products linked to them.

---

# 3. Fix Product Stock Balances

Product Stock Balances must show all product quantities from the unified stock balance table.

The page must read from:

```text
stock_balances
```

joined with:

```text
products
stock_locations
departments
```

Each balance row must be based on:

```text
stock_balances.product_id
stock_balances.stock_location_id
stock_balances.quantity_on_hand
```

Do not read from:

```text
drug_stock
product_stock_balances
stock_balances.drug_id
products.quantity
drugs.quantity
```

The Product Stock Balances page must show:

```text
Product
Product Type
Location
Department
Quantity on Hand
Reorder Level
Status
```

Example expected result after PO receiving:

```text
Paracetamol    DRUG       Main Store    Store       100
Gloves         CONSUMABLE Main Store    Store       500
Malaria Kit    REAGENT    Main Store    Store       50
```

---

# 4. Fix Purchase Order Receiving

When a purchase order is received, the received product quantity must be posted into the unified inventory system.

Receiving must:

1. Resolve Main Store stock location.
2. Create `PURCHASE_RECEIVED` movement in `stock_movements`.
3. Use `product_id`.
4. Use `stock_location_id = Main Store`.
5. Update or create matching `stock_balances` row.
6. Update purchase order received quantity.
7. Update supplier ledger if supplier exists.

Correct movement:

```text
stock_movements.product_id = received product
stock_movements.stock_location_id = Main Store location
stock_movements.movement_type = PURCHASE_RECEIVED
stock_movements.direction = IN
stock_movements.quantity = received quantity
```

Correct balance:

```text
stock_balances.product_id = received product
stock_balances.stock_location_id = Main Store location
quantity_on_hand increases by received quantity
```

Do not write received PO quantities only to drug stock.

Do not write to product_stock_balances.

Do not create stock movements using drug_id.

---

# 5. Fix Drug Catalogue

Pharmacy Drug Catalogue must be only a filtered product catalogue.

It must load:

```text
products
WHERE product_type = DRUG
AND product is linked to Pharmacy department
```

It must not use:

```text
drugs table
drug_stock
legacy drug quantity
```

The quantity shown in Drug Catalogue must come from:

```text
stock_balances.quantity_on_hand
WHERE stock_balances.product_id = products.id
AND stock_balances.stock_location_id = Pharmacy stock location
```

Important behavior:

If a drug has been received into Main Store but not transferred to Pharmacy:

```text
Product Stock Balances:
Paracetamol — Main Store — 100

Pharmacy Drug Catalogue:
Paracetamol — Pharmacy Available Qty — 0
```

After transfer:

```text
Main Store → Pharmacy Qty 20

Product Stock Balances:
Paracetamol — Main Store — 80
Paracetamol — Pharmacy — 20

Pharmacy Drug Catalogue:
Paracetamol — Pharmacy Available Qty — 20
```

This must be consistent.

---

# 6. Fix Investigation Items Page

The current Investigation Items page is wrong.

It must no longer be a creation page.

Rename/rework it as:

```text
Investigation Consumables
```

It must load products from:

```text
products
WHERE product is linked to the current Investigation/Lab department
AND product_type IN (CONSUMABLE, REAGENT, MEDICAL_SUPPLY, GENERAL_ITEM)
```

Remove or hide:

```text
Add New Item
Create Investigation Item
Edit Investigation Item as separate entity
```

There should be no separate investigation item creation.

If an investigation department needs an item:

```text
Store/Admin creates Product
Store/Admin links Product to Investigation department
Investigation Consumables page displays it
```

---

# 7. Fix Procedure Consumables Page

Procedure/Theatre consumables must also be a filtered product catalogue.

It must load:

```text
products
WHERE product is linked to Procedure/Theatre department
AND product_type IN (CONSUMABLE, SURGICAL_SUPPLY, MEDICAL_SUPPLY, GENERAL_ITEM)
```

Do not create separate procedure items.

Do not allow Theatre users to create products.

---

# 8. Fix Department Catalogue Rules

All department item/catalogue pages must become product views.

## Pharmacy

```text
Products linked to Pharmacy
product_type = DRUG
quantity from Pharmacy stock location
```

## Investigation

```text
Products linked to Investigation/Lab department
product_type IN consumable/reagent/supply types
quantity from Investigation department stock location
```

## Theatre/Procedure

```text
Products linked to Theatre/Procedure department
product_type IN surgical/consumable/supply types
quantity from Theatre stock location
```

## Ward

```text
Products linked to Ward department
quantity from Ward stock location
```

Departments must not create products.

---

# 9. Fix Transfer Flow

Transfers must move stock from Main Store to department stock locations.

When a product is transferred:

```text
TRANSFER_OUT from Main Store
TRANSFER_IN into department stock location
```

Update `stock_balances` for both locations.

After transfer, the department catalogue must show the updated quantity.

Example:

```text
Main Store → Lab
Malaria RDT Kit Qty 10
```

Expected balances:

```text
Malaria RDT Kit — Main Store — reduced by 10
Malaria RDT Kit — Lab — increased by 10
```

Expected Investigation Consumables:

```text
Malaria RDT Kit — Available in Lab — 10
```

---

# 10. Department Consumption Rule

Departments consume only from their own stock location.

Pharmacy dispensing must deduct from Pharmacy location.

Investigation result consumables must deduct from Investigation/Lab location.

Procedure consumables must deduct from Theatre/Procedure location.

Ward consumables must deduct from Ward location.

Departments must never consume directly from Main Store.

---

# 11. Remove Add Buttons from Department Item Pages

Remove or hide product creation buttons from:

```text
Pharmacy Drug Catalogue
Investigation Consumables
Procedure Consumables
Ward Consumables
```

Only Store/Admin product pages may have:

```text
Add Product
Create Product
Edit Product
Department Availability
```

If a department user lacks a product, they should not create it there.

Optional future feature:

```text
Request Product from Store
```

But do not implement product creation in department catalogues.

---

# 12. UI Error and Consistency Fixes

Fix any UI that still says:

```text
Add Drug
Add Investigation Item
Add Lab Item
Add Procedure Item
Drug Quantity
Lab Item Quantity
```

Replace with:

```text
Product
Department Product
Investigation Consumable
Procedure Consumable
Stock Balance
Quantity on Hand
```

---

# 13. Backend Query Requirements

Create reusable query methods.

## ProductService

```php
getProductsForDepartment(Department $department, ?array $types = null)
```

Must return products linked to department and optionally filtered by product_type.

## StockBalanceService

```php
getQuantityForProductAtLocation(Product $product, StockLocation $location): float
```

## StockLocationService

```php
getDefaultLocationForDepartment(Department $department): StockLocation
getMainStoreLocation(): StockLocation
```

Use these methods instead of duplicating queries in controllers.

---

# 14. Required Verification Scenario

After fixing, this exact scenario must work:

## Scenario A — Pharmacy Drug

1. Store creates Product:

   * name = Paracetamol
   * product_type = DRUG
   * linked department = Pharmacy

2. Store creates PO for Paracetamol Qty 100.

3. Store approves and receives PO.

4. Product Stock Balances must show:

```text
Paracetamol — Main Store — 100
```

5. Pharmacy Drug Catalogue must show Paracetamol but quantity:

```text
Available in Pharmacy = 0
```

6. Store transfers Paracetamol Qty 20 to Pharmacy.

7. Product Stock Balances must show:

```text
Paracetamol — Main Store — 80
Paracetamol — Pharmacy — 20
```

8. Pharmacy Drug Catalogue must show:

```text
Paracetamol — Available in Pharmacy = 20
```

## Scenario B — Investigation Consumable

1. Store creates Product:

   * name = Malaria RDT Kit
   * product_type = REAGENT
   * linked department = Laboratory

2. Store creates PO Qty 50.

3. Store receives PO.

4. Product Stock Balances must show:

```text
Malaria RDT Kit — Main Store — 50
```

5. Investigation Consumables page must show Malaria RDT Kit but quantity:

```text
Available in Laboratory = 0
```

6. Store transfers Qty 10 to Laboratory.

7. Product Stock Balances must show:

```text
Malaria RDT Kit — Main Store — 40
Malaria RDT Kit — Laboratory — 10
```

8. Investigation Consumables must show:

```text
Malaria RDT Kit — Available in Laboratory = 10
```

---

# 15. Data Integrity Rules

* One inventory system only.
* Products are the only physical items.
* Purchase receiving posts to Main Store.
* Product Stock Balances must show received stock.
* Department catalogues are filtered product views.
* Department quantities come from department stock location.
* Departments do not create products.
* Departments do not consume from Main Store.
* Stock balances update from stock movements.
* No active workflow should write to old drug/investigation/procedure item stock systems.

---

# 16. Tests Required

Add or update tests for:

1. PO receiving product creates Main Store stock balance.
2. Product Stock Balances page shows received product.
3. Pharmacy Drug Catalogue loads product linked to Pharmacy.
4. Pharmacy Drug Catalogue quantity comes from Pharmacy stock location.
5. Investigation Consumables loads product linked to Laboratory.
6. Investigation Consumables quantity comes from Laboratory stock location.
7. Department catalogue shows zero if product exists but has not been transferred to that department.
8. Transfer Main Store to department updates both balances.
9. Department catalogue shows updated quantity after transfer.
10. Department item pages do not show Add New Item button.
11. No active workflow writes to `drug_stock`.
12. No active workflow writes to `product_stock_balances`.
13. No active workflow reads old `investigation_items` as physical items.

---

# 17. Deliverables

Provide:

1. Root cause of inconsistent stock quantities.
2. Files modified.
3. Updated purchase receiving logic.
4. Updated Product Stock Balances query.
5. Updated Pharmacy Drug Catalogue query.
6. Updated Investigation Consumables query.
7. Updated Procedure Consumables query.
8. Removed Add New Item buttons from department item pages.
9. Updated transfer/balance update behavior.
10. Tests or verification notes.
11. Confirmation that the two scenarios above pass.

---

# 18. Important Rules

Do not maintain two inventory systems.

Do not keep showing quantity from drug catalogue if Product Stock Balances does not match.

Do not let departments create items.

Do not let Investigation Items be separate from Products.

Do not read quantity from old drug tables.

Do not write stock to old product_stock_balances.

Do not receive directly into Pharmacy/Lab/Theatre.

Do not let department catalogues use Main Store quantity as their available stock.

Fix this properly and aggressively.
