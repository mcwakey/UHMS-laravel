You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to improve the **Invoice Discount UI**, fix the **Purchase Order selected-items save issue**, and redesign inventory around a proper **Stock Movement Ledger** system.

Focus only on:

1. Invoice item discount entry.
2. Purchase order save issue.
3. Stock movement system.
4. Stock adjustments, transfers, returns, purchases, dispensing, reversals, and stock balance calculation.

Do not refactor unrelated modules.

---

# 1. Main Objectives

Implement the following:

1. Add a place on the Invoice View UI for authorized users to enter and apply discounts per invoice item.
2. Fix purchase order saving error: **“at least one item is required”**, even though items are selected.
3. Redesign stock quantity handling so the database does not depend on manually updating a hard `quantity` field.
4. Implement a complete stock movement system.
5. Add movement features:

   * Opening stock
   * Purchase receiving
   * Pharmacy dispensing
   * Transfers
   * Returns
   * Stock adjustments
   * Damaged stock
   * Expired stock
   * Reversals/corrections
6. Use `stock_movements` as the source of truth.
7. Use `stock_balances` as a fast cached quantity for performance.

---

# 2. Invoice Discount UI

There is currently no place on the Invoice View page for the user to manually enter discount.

Add discount functionality to the Invoice View.

## Required UI

On each invoice item row, provide one of these options:

```text id="nrz2t2"
Discount input + Apply button
```

or:

```text id="jd8sun"
Apply Discount action button that opens a modal
```

Recommended invoice columns:

```text id="i25kbh"
Service / Description
Selected Price
Discount
Patient Payable
Paid Amount
Balance
Status
Actions
```

## Discount Rules

* Discount is manually entered by an authorized user.
* Discount must be saved as `discount_amount`.
* Discount must not be automatically calculated from insurance.
* Discount must not equal `insurance_covered`.
* Discount must not exceed `selected_price * quantity`.
* Discount must not be negative.
* Discount should not be applied freely after full payment unless a reversal/refund workflow exists.

## Backend Method

Use or create:

```php id="4c1za4"
BillingService::applyDiscount(InvoiceItem $item, float $discountAmount, User $user): InvoiceItem
```

This method must:

1. Validate user permission.
2. Validate discount amount.
3. Save `discount_amount`.
4. Recalculate:

```text id="x5mril"
patient_payable = (selected_price * quantity) - discount_amount
balance = patient_payable - paid_amount
```

5. Update `payment_status`.
6. Recalculate parent invoice totals.
7. Log who applied the discount.

Do not calculate discount in the Vue component only. Backend must be authoritative.

---

# 3. Fix Purchase Order Save Error

Purchase order currently fails with:

```text id="jwdc09"
at least one item is required
```

even though items are selected.

Investigate and fix the real cause.

Check:

* frontend selected item array name
* backend validation key
* request payload
* form submit method
* route method
* controller method
* request validation
* `FormRequest` rules
* model `$fillable`
* item IDs/keys
* selected items are copied into form data before submit
* Inertia/Vue form errors are displayed
* modal/page is not dismissing before validation response
* backend expects `items`, but frontend sends `selectedItems`, `purchase_items`, or another name

## Required Behavior

* User can add multiple purchase order items.
* Submitting purchase order sends selected items correctly.
* Backend receives an array named consistently, preferably:

```text id="o9rksf"
items
```

Each item should include at minimum:

```text id="ek1nkd"
product_id / drug_id
quantity
unit_cost
```

* Validation errors must show clearly.
* No silent `302` redirect without visible errors.
* Purchase order saves successfully when items are valid.

---

# 4. Inventory Design Decision

Do not treat a product/drug `quantity` field as the main source of truth.

The correct inventory model is:

```text id="v94d1x"
Opening stock + stock movements = current stock
```

More precisely:

```text id="g8mjdn"
Current Stock =
SUM(IN movements)
-
SUM(OUT movements)
```

For performance, maintain a cached balance table:

```text id="vlz0gb"
stock_movements = source of truth
stock_balances = fast current quantity cache
```

---

# 5. Product / Drug Quantity Rule

The only initial hard quantity should be the opening stock.

After opening stock:

* Purchases add stock through stock movements.
* Dispensing reduces stock through stock movements.
* Transfers create OUT movement from source and IN movement to destination.
* Returns create return movements.
* Adjustments create adjustment movements.
* Damaged/expired stock creates OUT movements.
* Corrections are handled through reversal/adjustment movements.

Do not silently overwrite product quantity.

---

# 6. Required Tables

Use existing tables where possible. Add migrations if missing.

## products / drugs

The product/drug table stores item information.

Fields may include:

```text id="cnhoob"
id
name
code
category_id nullable
unit
reorder_level
opening_stock
opening_stock_date
is_active
created_at
updated_at
```

If using an existing `drugs` table, adapt names accordingly.

Do not use `quantity` as the authoritative current stock.

---

## stock_locations

Create if not existing.

```text id="ki1toy"
id
name
type
department_id nullable
is_active
created_at
updated_at
```

Examples:

```text id="e6yam7"
Main Store
Pharmacy
Ward Store
Theater Store
Laboratory Store
```

---

## stock_movements

Create a stock ledger table.

```text id="1cgwks"
id
product_id / drug_id
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

## Direction

```text id="mc7a9n"
IN
OUT
```

## Movement Types

Implement at least:

```text id="jo5odn"
OPENING_STOCK
PURCHASE_RECEIVED
PHARMACY_DISPENSED
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

---

## stock_balances

Create a cached current-stock table.

```text id="djnk4y"
id
product_id / drug_id
stock_location_id
quantity_on_hand
last_movement_at
created_at
updated_at
```

Add unique constraint:

```text id="7vjqe2"
product_id + stock_location_id
```

---

# 7. Stock Movement Service

Create or update:

```text id="nny48q"
StockMovementService
StockBalanceService
PurchaseOrderService
PharmacyDispensingService
StockTransferService
StockAdjustmentService
StockReturnService
```

---

## StockMovementService

Responsible for creating stock movement records.

Required method:

```php id="riknuh"
createMovement(array $data): StockMovement
```

This method must:

1. Validate movement type.
2. Validate direction.
3. Validate quantity > 0.
4. Validate stock location.
5. Validate product/drug.
6. Prevent OUT movement if insufficient stock, unless explicitly allowed by system setting.
7. Create stock movement.
8. Update stock balance.
9. Log source information.

---

## StockBalanceService

Responsible for current stock.

Required methods:

```php id="sg8fjw"
getCurrentStock($productId, $locationId): float

increase($productId, $locationId, float $quantity): void

decrease($productId, $locationId, float $quantity): void

rebuildBalance($productId, $locationId): void

rebuildAllBalances(): void
```

Balance formula:

```text id="tcc0q9"
quantity_on_hand = total IN movements - total OUT movements
```

---

# 8. Opening Stock

When a drug/product is created with opening stock:

1. Save product/drug information.
2. Create an `OPENING_STOCK` movement.
3. Direction = `IN`.
4. Update stock balance.

If opening stock is edited later:

* Do not silently change old movement.
* Create an adjustment or reversal movement.

---

# 9. Purchase Order Workflow

Purchase order creation should not automatically increase stock unless the items are actually received.

Recommended flow:

```text id="5e6h5s"
Purchase Order Created
        ↓
Pending
        ↓
Received / Partially Received
        ↓
Stock Movement Created
        ↓
Stock Balance Updated
```

## Purchase Order Save

When saving a purchase order:

* Validate at least one item exists.
* Save purchase order.
* Save purchase order items.
* Do not create stock movements yet unless the workflow marks items as received immediately.

## Purchase Receiving

When items are received:

For each received item:

```text id="rj1gam"
movement_type = PURCHASE_RECEIVED
direction = IN
source_type = purchase_order_item
source_id = purchase_order_items.id
quantity = received_quantity
unit_cost = item unit cost
stock_location_id = selected receiving location
```

Update purchase order status:

```text id="i0g1vm"
PENDING
PARTIALLY_RECEIVED
RECEIVED
CANCELLED
```

---

# 10. Pharmacy Dispensing Workflow

When pharmacy dispenses drugs:

1. Validate stock availability.
2. Create invoice item through `BillingService`.
3. Create stock movement:

```text id="sgj6je"
movement_type = PHARMACY_DISPENSED
direction = OUT
source_type = prescription_item or dispensing_item
source_id = related item id
```

4. Update stock balance.

Important:

* Billing and stock are related but separate.
* Payment does not affect stock.
* Dispensing affects stock.
* Invoice/payment affects billing.

---

# 11. Transfers

Implement stock transfer workflow.

A transfer moves stock from one location to another.

Example:

```text id="ro3w0j"
Main Store → Pharmacy
```

When transfer is completed:

Create two movements:

## Source Location

```text id="np6igd"
movement_type = TRANSFER_OUT
direction = OUT
stock_location_id = source_location_id
```

## Destination Location

```text id="6ynbwh"
movement_type = TRANSFER_IN
direction = IN
stock_location_id = destination_location_id
```

Both movements should share a common `source_type` / `source_id`, such as:

```text id="v4x8o9"
source_type = stock_transfer
source_id = stock_transfers.id
```

Rules:

* Validate source and destination are different.
* Validate quantity > 0.
* Validate source has enough stock.
* Update both balances.
* Keep transfer audit trail.

---

# 12. Returns

Implement stock returns.

Return types:

```text id="6o0f7g"
RETURN_IN
RETURN_OUT
```

Examples:

## Patient/Pharmacy Return

Drug returned to pharmacy:

```text id="l6lu42"
movement_type = RETURN_IN
direction = IN
```

## Return to Supplier

Drug returned to supplier:

```text id="qmxrno"
movement_type = RETURN_OUT
direction = OUT
```

Rules:

* Returns must reference a source when possible.
* Validate quantity.
* Update balance.
* Do not delete original dispense/purchase movement.
* Create return movement instead.

---

# 13. Stock Adjustments

Implement stock adjustment feature.

Stock adjustment is used for corrections after stock count or administrative correction.

Adjustment types:

```text id="kf1uq7"
ADJUSTMENT_IN
ADJUSTMENT_OUT
```

Examples:

* Physical count found extra stock → `ADJUSTMENT_IN`
* Physical count found missing stock → `ADJUSTMENT_OUT`
* Correction after wrong entry → adjustment movement

## Adjustment UI

Create a Stock Adjustment page or modal where authorized users can enter:

```text id="z5ys4o"
product/drug
location
adjustment_type
quantity
reason
notes
```

Rules:

* Only authorized users can adjust stock.
* Reason is required.
* Quantity must be greater than zero.
* For `ADJUSTMENT_OUT`, validate stock is enough unless negative stock is allowed.
* Create stock movement.
* Update stock balance.
* Log user and reason.

---

# 14. Damaged and Expired Stock

Implement stock removal for damaged/expired items.

## Damaged

```text id="ltl1t4"
movement_type = DAMAGED
direction = OUT
```

## Expired

```text id="4kwi4n"
movement_type = EXPIRED
direction = OUT
```

Rules:

* Must include reason/notes.
* Must update stock balance.
* Must not silently reduce product quantity.

---

# 15. Reversals and Corrections

Do not edit/delete old movements silently when correcting stock.

Use reversal movements.

Examples:

Original purchase received:

```text id="dzfwpr"
+100
```

Correction: only 80 were actually received.

Create:

```text id="dj777g"
REVERSAL_OUT 20
```

Original dispensing:

```text id="9t97l3"
-10
```

Correction: only 6 were dispensed.

Create:

```text id="7lcaj8"
REVERSAL_IN 4
```

Rules:

* Reversal must reference original source/movement where possible.
* Reversal must have notes/reason.
* Reversal updates stock balance.
* Original movement remains for audit.

---

# 16. Current Stock Display

Whenever displaying stock quantity, use:

```text id="sasz75"
stock_balances.quantity_on_hand
```

or:

```php id="2le0gk"
StockBalanceService::getCurrentStock($productId, $locationId)
```

Do not display old product/drug `quantity` field as current stock.

If old quantity field exists:

* stop using it for current stock.
* optionally rename/display as opening stock only.
* remove later if safe.

---

# 17. Stock Reports

Add or prepare for reports:

* Stock ledger by product.
* Stock ledger by location.
* Current stock by location.
* Low stock report.
* Expired stock report.
* Purchase received report.
* Dispensing stock movement report.
* Transfer report.
* Adjustment report.

Reports must read from stock movements and stock balances.

---

# 18. Validation Rules

## Movement

* product/drug required
* location required
* movement_type required
* direction required
* quantity required and greater than 0
* OUT movements must not exceed current stock unless negative stock is explicitly allowed
* movement_date required

## Transfer

* source location required
* destination location required
* source and destination must be different
* quantity required and greater than 0
* source must have enough stock

## Adjustment

* product/drug required
* location required
* adjustment type required
* quantity required
* reason required
* authorized user required

## Purchase Order

* supplier/vendor if applicable
* items required
* each item must have product/drug
* quantity required and greater than 0
* unit_cost required and >= 0

---

# 19. Data Integrity Rules

* Stock movements are the source of truth.
* Stock balances are cache only.
* Do not silently overwrite current quantity.
* Do not delete old stock movements.
* Use reversals or adjustments.
* Payment does not affect stock.
* Dispensing affects stock.
* Purchase order does not affect stock until received.
* Transfers must create both OUT and IN movements.
* Opening stock must create an opening movement.
* Every stock movement must have a user and reason/source where possible.

---

# 20. Performance Rules

* Use `stock_balances` for fast stock display.
* Use indexes:

  * stock_movements.product_id
  * stock_movements.stock_location_id
  * stock_movements.movement_type
  * stock_movements.direction
  * stock_movements.source_type
  * stock_movements.source_id
  * stock_movements.movement_date
  * stock_balances.product_id
  * stock_balances.stock_location_id
* Paginate stock movement reports.
* Do not recalculate full movement history on every page load.
* Rebuild balances only through command/manual admin action.

---

# 21. Artisan Command

Create an Artisan command:

```bash id="6n392i"
php artisan stock:rebuild-balances
```

This command must:

1. Clear/recalculate stock balances from stock movements.
2. Rebuild all balances, or optionally one product/location.
3. Log summary output.

---

# 22. Frontend Requirements

## Invoice View

* Add discount input or discount modal per invoice item.
* Apply discount without full page reload.
* Show validation errors.
* Update invoice totals after discount.

## Purchase Order Page

* Fix selected items payload.
* Show selected items before submit.
* Show validation errors clearly.
* Submit without losing selected items.

## Stock Movement Pages

Create or update UI for:

* Opening stock creation
* Purchase receiving
* Transfers
* Returns
* Adjustments
* Damaged stock
* Expired stock
* Stock ledger
* Current stock by location

Use SPA behavior where applicable.

---

# 23. Deliverables

Provide:

1. Root cause of purchase order “at least one item is required” error.
2. Files modified.
3. New migrations.
4. Updated models and relationships.
5. `StockMovementService`.
6. `StockBalanceService`.
7. `StockTransferService`.
8. `StockAdjustmentService`.
9. `StockReturnService`.
10. Updated purchase order save flow.
11. Purchase receiving flow.
12. Pharmacy dispensing stock OUT flow.
13. Transfer IN/OUT movement flow.
14. Return movement flow.
15. Adjustment movement flow.
16. Damaged/expired movement flow.
17. Stock balance rebuild command.
18. Invoice discount UI and backend.
19. Confirmation that current stock is read from `stock_balances`.
20. Confirmation that old hard quantity is no longer used as current stock.

---

# 24. Important Rules

Do not use product/drug quantity as the source of truth.

Do not silently edit stock movements.

Do not delete old movements.

Do not increase stock when purchase order is merely created unless the workflow explicitly marks it as received.

Do not reduce stock when an item is only prescribed; reduce stock only when dispensed.

Do not let payment affect stock.

Do not bypass `StockMovementService`.

Do not bypass `StockBalanceService`.

Do not apply discounts without authorization.

Do not hide purchase order validation errors.

Now inspect the current implementation and apply these changes carefully.
