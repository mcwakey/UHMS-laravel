You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We must eliminate the current two-parallel-inventory problem completely.

The project must have **one unified inventory system**, not separate drug stock and product stock systems.

Focus only on inventory, stock locations, product purchase orders, receiving, transfers, department stock usage, pharmacy dispensing, investigation consumables, procedure consumables, stock movements, and stock balances.

Do not refactor unrelated modules.

---

# 1. Core Decision

UHMS must use **one inventory system only**.

The single inventory source is:

```text
products
```

All physical items in the hospital must be products.

Examples:

```text
Paracetamol
Ceftriaxone
Gloves
Syringe
EDTA Tube
Malaria RDT Kit
Sutures
Gauze
X-ray Film
Oxygen Mask
```

Do not maintain separate active inventory systems for:

```text
drugs
product_stock
drug_stock
lab_items
procedure_items
standalone consumables
```

---

# 2. Hard Rule

There must not be two parallel ledgers.

Remove or stop using parallel systems like:

```text
stock_movements for drugs
product_stock_movements for products
stock_balances for drugs
product_stock_balances for products
drug_stock legacy batch quantity
```

Replace them with one product-based stock system:

```text
stock_movements
stock_balances
```

where each movement and balance references:

```text
product_id
stock_location_id
```

---

# 3. Product Is the Only Inventory Item

Products represent all physical stock items.

Recommended `products` fields:

```text
id
name
code
product_type
unit
description nullable
reorder_level nullable
base_price nullable
is_billable
is_active
created_by nullable
created_at
updated_at
```

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

Rules:

* Pharmacy drugs are products.
* Investigation/lab consumables are products.
* Theatre/procedure consumables are products.
* Ward consumables are products.
* Store/Admin creates products.
* Departments use only products linked to their departments.

---

# 4. Main Store Receiving Rule

All product purchase orders, when received, must first enter the **Main Store**.

Purchase receiving must always create stock IN movements into:

```text
Main Store stock location
```

not directly into Pharmacy, Lab, Theatre, Ward, or any other department location.

Correct flow:

```text
Purchase Order
↓
Goods Received
↓
Stock IN to Main Store
↓
Transfer from Main Store to department stock location
↓
Department consumes from its own stock location
```

Do not allow purchase receiving directly into departmental stock locations unless a future explicit setting is added. For now, enforce Main Store only.

---

# 5. Department Stock Usage Rule

All departments must interact only with their own stock location.

Examples:

```text
Pharmacy users consume from Pharmacy stock location only.
Lab users consume from Lab stock location only.
Theatre users consume from Theatre/Procedure stock location only.
Ward users consume from Ward stock location only.
```

Departments must not consume from Main Store.

If Pharmacy needs stock:

```text
Main Store → Pharmacy Stock Location
```

If Lab needs stock:

```text
Main Store → Laboratory Stock Location
```

If Theatre needs stock:

```text
Main Store → Theatre Stock Location
```

Only after transfer can the department use the stock.

---

# 6. Stock Locations

Use one `stock_locations` table:

```text
id
name
department_id
is_main
is_active
created_at
updated_at
```

Rules:

* There must be one Main Store stock location.
* Main Store must be linked to the Store/Procurement department.
* Department stock locations must be linked to their departments.
* Department users should only see/use their department’s active stock location.
* Store/Admin can manage locations.
* Departments cannot use Main Store directly.

---

# 7. Unified Stock Movements

Use one `stock_movements` table for all products.

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

Rules:

* Every stock change must create a stock movement.
* Do not update balances directly without movement.
* Do not use drug-specific movement tables.
* Do not use product-specific parallel movement tables.
* One movement table only.

---

# 8. Unified Stock Balances

Use one `stock_balances` table:

```text
id
product_id
stock_location_id
quantity_on_hand
last_movement_at
created_at
updated_at
```

Unique constraint:

```text
product_id + stock_location_id
```

Current stock formula:

```text
quantity_on_hand = SUM(IN movements) - SUM(OUT movements)
```

Rules:

* `stock_movements` is the source of truth.
* `stock_balances` is a fast cache.
* Do not use product quantity as stock.
* Do not use drug_stock as stock.
* Do not use product_stock_balances separately.

---

# 9. Purchase Orders Must Use Products Only

Purchase order items must reference products.

Use:

```text
purchase_order_items
- id
- purchase_order_id
- product_id
- quantity_ordered
- quantity_received
- unit_cost
- created_at
- updated_at
```

Rules:

* Do not use `drug_id`.
* Do not use `investigation_item_id`.
* Do not use `procedure_item_id`.
* Purchase orders are for products only.
* Since the system is still in development, remove or stop using legacy item references.

---

# 10. Goods Receiving / GRN

Create proper Goods Received Notes.

```text
goods_received_notes
- id
- grn_number
- purchase_order_id
- supplier_id
- received_date
- supplier_delivery_no nullable
- received_by
- notes nullable
- created_at
- updated_at
```

```text
goods_received_note_items
- id
- goods_received_note_id
- purchase_order_item_id
- product_id
- stock_location_id
- quantity_received
- unit_cost
- batch_no nullable
- expiry_date nullable
- stock_movement_id nullable
- created_at
- updated_at
```

Important:

* `stock_location_id` for received goods must be Main Store.
* A PO can have multiple GRNs.
* Partial receipts must be supported.
* Each GRN item must create one `PURCHASE_RECEIVED` IN movement.
* Each GRN item must update Main Store stock balance.
* PO item `quantity_received` must equal sum of its GRN item quantities.

---

# 11. Receiving Workflow

When Store receives a PO:

1. Validate purchase order is approved/receivable.
2. Resolve Main Store stock location.
3. For each received product:

   * validate product_id
   * validate received quantity
   * validate remaining quantity
   * validate unit cost
4. Create GRN.
5. Create GRN items.
6. Create `PURCHASE_RECEIVED` IN movement into Main Store.
7. Update Main Store stock balance.
8. Create supplier ledger credit entry.
9. Update PO item quantity_received.
10. Update PO status:

    * pending
    * partially_received
    * received

Do not receive PO stock into Pharmacy/Lab/Theatre/Ward.

---

# 12. Transfers from Main Store to Departments

Departments get stock only by transfer from Main Store.

Transfer rules:

Allowed:

```text
Main Store → Department Stock Location
Department Stock Location → Main Store
```

Blocked:

```text
Pharmacy → Lab
Lab → Theatre
Ward → Pharmacy
Department → Department
```

unless a future setting explicitly enables interdepartment transfers.

Transfer must use location IDs:

```text
from_location_id
to_location_id
```

not text location names.

A completed transfer creates:

```text
TRANSFER_OUT from source location
TRANSFER_IN into destination location
```

Both movements must link to the same transfer record.

---

# 13. Department Consumption

## Pharmacy

Pharmacy dispensing must:

* use products linked to the pharmacy department
* use only products linked to Pharmacy department
* check Pharmacy stock balance only
* deduct from Pharmacy stock location only
* create `PHARMACY_DISPENSED` OUT movement
* not touch Main Store stock directly

If Pharmacy stock is zero but Main Store has stock, dispensing must fail until transfer is done.

## Investigation / Lab

Investigation result consumables must:

* use products linked to the investigation department
* deduct from that department’s stock location
* create `INVESTIGATION_CONSUMED` OUT movement
* not touch Main Store stock directly

## Theatre / Procedure

Procedure consumables must:

* use products linked to Theatre/Procedure department
* deduct from Theatre/Procedure stock location
* create `PROCEDURE_CONSUMED` OUT movement
* not touch Main Store stock directly

## Ward

Ward consumables must:

* use products linked to Ward department
* deduct from Ward stock location
* create `WARD_CONSUMED` OUT movement
* not touch Main Store stock directly

---

# 14. Services to Update

Create/update these services:

```text
ProductService
StockLocationService
StockMovementService
StockBalanceService
ProcurementService
GoodsReceivedNoteService
StockTransferService
StockAdjustmentService
StockReturnService
PharmacyService
ConsumableUsageService
SupplierLedgerService
BillingService
```

## StockLocationService

Must provide:

```php
getMainStoreLocation(): StockLocation
getDefaultLocationForDepartment(Department $department): StockLocation
```

Rules:

* `getMainStoreLocation()` is used for PO receiving.
* `getDefaultLocationForDepartment()` is used for department consumption.
* They must not be mixed.

## ProcurementService / GoodsReceivedNoteService

Must receive all PO products into Main Store only.

## PharmacyService

Must dispense from Pharmacy stock location only.

## ConsumableUsageService

Must consume from the service department’s stock location only.

## StockTransferService

Must move stock between Main Store and department locations.

---

# 15. Product Pricing and Billing

If a product is billable:

* use ProductPricingService to resolve price
* use BillingService to add product to visit invoice
* do not bypass BillingService
* do not bill non-billable products

Billing does not change stock.

Stock changes happen only when physical stock is dispensed or consumed.

---

# 16. Remove / Disable Legacy Parallel Inventory

Find and remove/disable active use of:

```text
drugs as inventory item source
drug_stock
product_stock_movements
product_stock_balances
ProductStockMovementService
ProductStockService
drug-specific StockMovementService logic
drug-specific StockBalanceService logic
stock transfers using string location names
purchase_order_items.drug_id
purchase_order_items.investigation_item_id
```

Since the project is still in development, prefer direct migration to product-only inventory instead of maintaining backward compatibility.

If some old tables still physically exist temporarily, they must not be used by active workflows.

---

# 17. UI Changes

## Store / Procurement

Menu should include:

```text
Products
Purchase Orders
Goods Receiving
Stock Locations
Stock Balances
Stock Ledger
Stock Transfers
Stock Adjustments
Stock Returns
Suppliers
Supplier Ledger
```

## Purchase Order UI

* Select products only.
* No drug selector.
* No investigation item selector.
* Receiving should automatically use Main Store location.
* Show clear message:

```text
All received stock enters Main Store. Transfer stock to departments before they can use it.
```

## Stock Balance UI

Show unified product balances:

```text
Product
Type
Location
Department
Quantity on Hand
```

## Department Screens

Pharmacy, Lab, Theatre, Ward should show only their own stock location balances.

---

# 18. Supplier Ledger

Receiving goods creates supplier ledger credit.

Supplier payment creates supplier ledger debit.

Supplier return creates supplier ledger debit/credit note as appropriate.

Supplier balance:

```text
credits - debits
```

Supplier ledger must link entries to source records where possible:

```text
source_type = goods_received_note
source_id = goods_received_notes.id
```

---

# 19. Reversal Rules

When a stock-affecting transaction is cancelled or voided:

* do not delete original movement
* create opposite reversal movement
* update stock balance
* link reversal to source record
* record reason and user

Examples:

Dispensed product voided:

```text
REVERSAL_IN to Pharmacy stock location
```

Investigation consumable usage cancelled:

```text
REVERSAL_IN to Lab stock location
```

Procedure consumable usage cancelled:

```text
REVERSAL_IN to Theatre stock location
```

---

# 20. Validation Rules

## Receiving

* PO must be approved/receivable.
* Product is required.
* Received quantity > 0.
* Received quantity cannot exceed remaining ordered quantity unless over-receiving is enabled.
* Receiving location must be Main Store.
* Unit cost >= 0.

## Department Consumption

* Department must have active stock location.
* Product must be linked to department.
* Quantity > 0.
* Stock must be available in department location.
* Main Store stock must not be used for department consumption.

## Transfer

* Source and destination required.
* Source and destination must be different.
* Transfer must involve Main Store.
* Source must have enough stock.
* Product must exist.
* Quantity > 0.

---

# 21. Data Integrity Invariants

These must always be true:

```text
One active inventory item source = products
```

```text
stock_balances.quantity_on_hand =
SUM(IN stock_movements) - SUM(OUT stock_movements)
for each product/location
```

```text
All purchase receipts go to Main Store
```

```text
Departments consume only from their department stock location
```

```text
purchase_order_items.quantity_received =
SUM(goods_received_note_items.quantity_received)
```

```text
Every GRN item has a PURCHASE_RECEIVED stock movement
```

```text
Every stock-affecting cancellation has a reversal movement
```

---

# 22. Artisan Commands

Create/update:

```bash
php artisan stock:rebuild-balances
php artisan stock:audit
```

## stock:rebuild-balances

* rebuild all stock balances from stock movements
* optionally rebuild one product/location
* report summary

## stock:audit

Check:

* movement totals vs balances
* PO received quantities vs GRN totals
* GRN items without stock movement
* department consumption from Main Store
* duplicate active ledgers
* missing reversals for voided stock-affecting records

Audit command should fail loudly if inconsistencies exist.

---

# 23. Testing Requirements

Add or update tests:

1. Product PO receiving stores stock in Main Store.
2. Receiving does not store directly in Pharmacy/Lab/Theatre.
3. Product Stock Balances show received stock in Main Store.
4. Pharmacy cannot dispense from Main Store.
5. Pharmacy can dispense after Main Store → Pharmacy transfer.
6. Lab cannot consume from Main Store.
7. Lab can consume after Main Store → Lab transfer.
8. Theatre cannot consume from Main Store.
9. Theatre can consume after Main Store → Theatre transfer.
10. Transfer creates paired OUT/IN movements.
11. Stock balance equals signed stock movements.
12. PO item quantity_received equals GRN totals.
13. Voided dispensing creates reversal IN movement.
14. No active workflow writes to drug_stock/product_stock_balances/product_stock_movements.
15. Purchase order items use product_id only.

---

# 24. Deliverables

Provide:

1. Gap analysis of current inventory implementation.
2. Migrations to unify inventory around products.
3. Updated Product model.
4. Updated PurchaseOrderItem model.
5. Updated StockMovement model.
6. Updated StockBalance model.
7. Updated StockLocation model.
8. Updated Procurement / GRN flow.
9. Updated StockTransfer flow.
10. Updated Pharmacy dispensing flow.
11. Updated Investigation/Procedure/Ward consumable usage flow.
12. Legacy inventory usage removed/disabled.
13. Updated Store/Procurement UI.
14. Updated Department stock usage UI.
15. Supplier ledger integration.
16. Stock audit/rebuild commands.
17. Tests or verification notes.
18. Files modified.
19. Remaining TODOs if any.

---

# 25. Important Rules

Do not maintain two parallel inventory systems.

Do not use drugs as separate inventory source.

Do not use product_stock_movements/product_stock_balances as a separate ledger.

Do not write to drug_stock.

Do not let purchase receiving go directly to departments.

Do not let departments consume from Main Store.

Do not use text location names for transfers.

Do not update stock balance without stock movement.

Do not delete stock movements.

Do not bypass StockMovementService.

Do not bypass StockBalanceService.

Do not bypass SupplierLedgerService.

Do not break billing while fixing stock.

Now inspect the current implementation and force the system into one unified product-based inventory architecture where all PO receipts enter Main Store first and all departments consume only from their own department stock locations.


a lot of these can be automated though out the system. get me an .md analysis document to improve on this