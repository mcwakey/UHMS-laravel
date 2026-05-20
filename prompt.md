You are a senior Laravel + Inertia/Vue architect working on **UHMS — Ultimate Hospital Management System**.

We need to fix multiple small but important bugs in Store / Products / Stock / Supplier / Purchase Order workflows, and then add **Purchase Returns** and **Department Stock Requisition** without breaking existing workflows.

Important: UHMS must use **one unified product-based stock system only**.

Do not create any parallel stock system.

Do not reintroduce `drug_id` stock logic.

Do not break the already working workflow.

---

# 1. Non-Negotiable Architecture Rule

The system must use one stock system:

```text
products
stock_locations
stock_movements
stock_balances
purchase_orders
purchase_order_items
goods_received_notes
supplier_ledger_entries
```

Every physical item is a product.

Do not use or write active workflow data to:

```text
drugs as stock source
drug_stock
stock_movements.drug_id
stock_balances.drug_id
product_stock_movements
product_stock_balances
investigation_items as stock source
procedure_items as stock source
standalone consumables table
```

If old columns/tables still exist, active workflows must not use them.

---

# 2. Current Bugs to Fix

Fix these issues carefully:

1. Product filters are not working.
2. Product pagination appears on the left; it should align correctly to the right.
3. Product list needs an insurance prices column, like Services.
4. Supplier ledger page allows wrong manual entries.
5. Supplier ledger filters are not working.
6. Supplier ledger should link directly to related source document.
7. Purchase order total value card is incorrect.
8. Purchase order filters are not working.
9. Stock location table HTML/layout is broken.
10. Stock location notes column does not display properly.
11. Main Store should exist by default and cannot be edited or deactivated.
12. Stock Balance page Receive Stock throws SQL error:

```text
SQLSTATE integrity constraint violation: column drug_id cannot be null
```

13. Receive stock, stock transfer, stock adjustment, and stock return should be handled through modals.
14. Add Purchase Returns.
15. Add Department Stock Requisition workflow.
16. Stock transfers to departments should be based on department requisitions.
17. Receiving department must acknowledge receipt before stock is updated into their location.
18. Do not break existing Store, Product, PO, Billing, Pharmacy, Investigation, Procedure, or Stock workflow.

---

# 3. Product Page Fixes

## 3.1 Product Filters Not Working

Inspect product index page, controller, route, request query, and frontend filter bindings.

Filters may include:

```text
search
product_type
department_id
status
is_billable
has_insurance_prices
supplier_id
```

Fix:

* query string binding
* controller filtering
* Inertia props
* pagination preserving query
* frontend filter submit/reset
* debounce/search if used

Pagination must preserve filters:

```php
->withQueryString()
```

or the project’s equivalent pattern.

## 3.2 Product Pagination Alignment

Pagination should not appear on the left if the design expects it on the right.

Fix CSS/layout using the project’s existing style conventions.

Expected:

```text
Pagination aligned right or consistently with other index pages.
```

Do not patch with ugly inline styles unless needed.

## 3.3 Add Insurance Prices Column

On Products index, add a column similar to Services:

```text
Insurance Prices
```

It should show whether product has insurance prices configured.

Examples:

```text
None
NHIS, Private
Provider-specific
NHIS + Provider-specific
```

Or badges:

```text
Base
NHIS
Private
Corporate
Provider
```

The column should be derived from:

```text
product_insurance_prices
product_provider_prices
```

or the existing product pricing tables.

Avoid N+1 queries.

Eager-load/count:

```php
withCount(['insurancePrices', 'providerPrices'])
```

or equivalent.

---

# 4. Supplier Ledger Fixes

## 4.1 Ledger Manual Entry Restrictions

On the Supplier Ledger page, manual entry should only allow:

```text
Payment
Credit Note
Debit Note
```

The ledger page must not allow manually entering:

```text
Purchase Order
Goods Received
Return to Supplier
Supplier Invoice
```

These must be created automatically from their own workflows.

## 4.2 Source of Ledger Entries

Enforce this:

```text
Purchase Orders / Goods Received entries come from PO / GRN receiving workflow.
Return to Supplier entries come from Purchase Return / Supplier Return workflow.
Payments come from Supplier Ledger payment form.
Credit Notes come from Supplier Ledger credit note form.
Debit Notes come from Supplier Ledger debit note form.
```

Do not let the user manually create a fake goods received or return record from the ledger page.

## 4.3 Supplier Ledger Source Links

On Supplier Ledger page, each entry with `source_type` and `source_id` should link to the concerned document.

Examples:

```text
GOODS_RECEIVED → link to GRN / Purchase Order receipt page
PAYMENT → link to supplier payment detail if exists
RETURN_TO_SUPPLIER → link to purchase return page
CREDIT_NOTE → link to credit note detail or ledger entry
DEBIT_NOTE → link to debit note detail or ledger entry
PURCHASE_ORDER → link to purchase order page if used
```

Display:

```text
View Source
```

or make the reference clickable.

## 4.4 Supplier Ledger Filters Not Working

Fix filters on ledger page.

Filters may include:

```text
supplier_id
entry_type
date_from
date_to
search
debit_credit
source_type
```

Ensure:

* backend applies filters
* frontend sends query params correctly
* pagination preserves filters
* reset works
* date filters use correct field, likely `entry_date`

---

# 5. Purchase Order Page Fixes

## 5.1 Total Value Card Incorrect

Fix the total value card.

Expected total should be based on the correct records and statuses.

Clarify and implement one of these, preferably showing both if useful:

```text
Total Ordered Value = SUM(quantity_ordered * unit_cost)
Total Received Value = SUM(quantity_received * unit_cost)
Outstanding Value = Total Ordered Value - Total Received Value
```

If the current card says “Total Value”, define it clearly.

Recommended dashboard cards on Purchase Orders page:

```text
Total Ordered Value
Total Received Value
Outstanding Value
Pending POs
Partially Received POs
```

Use products-based PO items only.

Do not calculate from drug tables.

## 5.2 Purchase Order Filters Not Working

Fix PO filters:

```text
search
supplier_id
status
date_from
date_to
product_id
```

Ensure:

* backend query applies filters
* frontend sends query params
* pagination preserves filters
* reset works
* status filter matches actual enum/status values

---

# 6. Stock Location Page Fixes

## 6.1 Broken Table HTML/Layout

Fix the stock location table display.

Inspect Blade/Vue component.

Common issues to check:

* unclosed `<td>`, `<tr>`, `<div>`
* broken slot/template
* notes column rendering raw HTML incorrectly
* long notes breaking table width
* actions column misaligned

The table should display cleanly.

## 6.2 Notes Column

Notes column should display properly:

* truncate long notes
* show tooltip or modal for full note
* do not break table layout
* preserve safe escaping
* do not render dangerous raw HTML

Example:

```text
Short note visible, long note truncated with “View”.
```

## 6.3 Main Store Protection

Main Store should exist by default.

Rules:

```text
Main Store must be created/seeded by default.
Main Store must be linked to Store / Procurement department.
Main Store cannot be edited by normal UI.
Main Store cannot be deactivated.
Main Store cannot be deleted.
Only Super Admin may rename it if absolutely necessary, but default behavior should protect it.
```

In UI:

* hide edit/deactivate/delete buttons for Main Store
* show badge:

```text
System Default
```

Backend must enforce too.

Do not rely only on frontend hiding buttons.

---

# 7. Stock Balance Receive Stock SQL Error

Current error:

```text
SQLSTATE integrity constraint violation: column drug_id cannot be null
```

This means some receive-stock path is still using old drug-based stock logic.

Fix it completely.

## Expected Behavior

Receive stock must use:

```text
product_id
stock_location_id
movement_type = PURCHASE_RECEIVED or OPENING_STOCK depending context
direction = IN
quantity
```

It must write to:

```text
stock_movements.product_id
stock_balances.product_id
```

It must not require:

```text
drug_id
```

## Required Fix

Inspect:

```text
StockBalance page receive action
Receive stock modal/form
Controller receiving request
StockMovementService
StockBalanceService
routes
validation request
migration/schema still requiring drug_id
```

Then fix:

* request must send `product_id`
* validation must require `product_id`
* movement must save `product_id`
* balance must update by `product_id`
* remove any drug_id assumptions
* update migration if unified stock table still has `drug_id NOT NULL`

Since the project is in development, prefer product-only schema and remove old drug requirement.

---

# 8. Convert Stock Actions to Modals

These stock actions should be performed in modals:

```text
Receive Stock
Stock Transfer
Stock Adjustment
Stock Return
```

Requirements:

* modal opens without page reload
* validation errors show inside modal
* modal does not leave dark backdrop stuck
* modal closes only after successful response
* data refreshes after success
* use Inertia/Vue form handling properly

Avoid the old issue where modal backdrop remains after submit.

Use:

```text
onSuccess → close modal
onError → keep modal open and show errors
preserveScroll
preserveState
```

---

# 9. Purchase Returns

Add Purchase Returns / Return to Supplier workflow.

## Purpose

When products are returned to supplier due to:

```text
damaged goods
expired goods
wrong product
excess supply
quality issue
recall
```

the system should:

1. Create a purchase return document.
2. Create stock OUT movement from the selected stock location.
3. Update stock balance.
4. Create supplier ledger entry.
5. Link return to supplier and optionally purchase order / GRN.
6. Keep full audit trail.

## Tables

Create or update:

```text
purchase_returns
- id
- return_number
- supplier_id
- purchase_order_id nullable
- goods_received_note_id nullable
- return_date
- status
- reason
- notes nullable
- created_by
- approved_by nullable
- approved_at nullable
- posted_by nullable
- posted_at nullable
- created_at
- updated_at
```

```text
purchase_return_items
- id
- purchase_return_id
- product_id
- stock_location_id
- quantity
- unit_cost
- batch_no nullable
- expiry_date nullable
- stock_movement_id nullable
- notes nullable
- created_at
- updated_at
```

## Statuses

```text
DRAFT
APPROVED
POSTED
CANCELLED
```

## Posting Return

When posted:

* create `RETURN_OUT` stock movement
* update stock balance
* create supplier ledger entry:

```text
entry_type = RETURN_TO_SUPPLIER
debit = value returned
credit = 0
```

because return reduces what facility owes supplier.

Do not manually enter supplier return from ledger page.

It must come from Purchase Return workflow.

---

# 10. Department Stock Requisition

Add stock requisition workflow.

## Purpose

Departments should not receive stock automatically by Store deciding alone.

Correct workflow:

```text
Department creates stock requisition
↓
Store reviews request
↓
Store approves quantities to supply
↓
Store issues stock from Main Store
↓
Receiving department acknowledges receipt
↓
Only after acknowledgement, stock is added to department stock location
```

This prevents stock from appearing in a department before the department confirms receipt.

## Important Rule

Before stock is transferred to a department, the department should request it.

Store can then supply full or partial quantity.

---

# 11. Stock Requisition Tables

Create:

```text
stock_requisitions
- id
- requisition_number
- requesting_department_id
- requested_by
- status
- requested_at
- reviewed_by nullable
- reviewed_at nullable
- issued_by nullable
- issued_at nullable
- acknowledged_by nullable
- acknowledged_at nullable
- notes nullable
- created_at
- updated_at
```

Create:

```text
stock_requisition_items
- id
- stock_requisition_id
- product_id
- requested_quantity
- approved_quantity nullable
- issued_quantity nullable
- acknowledged_quantity nullable
- notes nullable
- created_at
- updated_at
```

Optional transfer link:

```text
stock_transfer_id nullable
```

or create separate transfer tables if already exist.

---

# 12. Stock Requisition Statuses

Recommended statuses:

```text
DRAFT
SUBMITTED
APPROVED
PARTIALLY_APPROVED
REJECTED
ISSUED
PARTIALLY_ISSUED
AWAITING_ACKNOWLEDGEMENT
ACKNOWLEDGED
PARTIALLY_ACKNOWLEDGED
COMPLETED
CANCELLED
```

Keep it practical. If too many statuses already complicate UI, use:

```text
DRAFT
SUBMITTED
APPROVED
ISSUED
AWAITING_ACKNOWLEDGEMENT
COMPLETED
REJECTED
CANCELLED
```

---

# 13. Department Requisition Flow

## Department Creates Request

Department user selects products linked to their department.

Fields:

```text
product
requested_quantity
notes
```

Rules:

* product must be linked to requesting department
* requested quantity > 0
* department must have active stock location

## Store Reviews

Store sees submitted requisitions.

Store can:

```text
approve full quantity
approve partial quantity
reject item
reject request
```

Approved quantity cannot exceed requested quantity unless explicitly allowed.

## Store Issues Stock

Store issues approved products from Main Store.

At issue time:

* check Main Store stock
* create `TRANSFER_OUT` movement from Main Store
* do not yet create `TRANSFER_IN` into department stock if acknowledgement is required
* or create pending transfer records without updating destination balance

Recommended safe approach:

```text
On issue:
- deduct from Main Store with TRANSFER_OUT
- create pending transfer item
- status = AWAITING_ACKNOWLEDGEMENT

On department acknowledgement:
- create TRANSFER_IN into department stock location
- update department stock balance
- mark requisition completed/partially acknowledged
```

This matches the user requirement: receiving department must acknowledge before stock is updated into their location.

## Department Acknowledges

Receiving department confirms received quantities.

Rules:

* acknowledged quantity cannot exceed issued quantity
* on acknowledgement, create `TRANSFER_IN` into department stock location
* update department stock balance
* record acknowledged_by and acknowledged_at

---

# 14. Stock Transfer Compatibility

Existing stock transfer workflow must not break.

But if requisition workflow is enabled:

* transfers to departments should preferably be created from approved requisitions
* direct transfer can still exist for Store/Admin emergency correction if permitted
* direct transfer must still involve Main Store
* no department-to-department transfer unless system setting allows it

Add setting:

```text
allow_direct_store_transfers = true/false
```

Default can be true during transition, but the intended workflow is requisition-based transfer.

---

# 15. Unified Movement Logic for Requisition Transfers

Movements:

## Store issues requisition

```text
movement_type = TRANSFER_OUT
direction = OUT
stock_location = Main Store
source_type = stock_requisition_item or stock_transfer_item
source_id = item id
```

## Department acknowledges

```text
movement_type = TRANSFER_IN
direction = IN
stock_location = Department Stock Location
source_type = stock_requisition_item or stock_transfer_item
source_id = item id
```

Never update balances without movement.

---

# 16. UI for Requisitions

Add menu under Store / Procurement:

```text
Stock Requisitions
```

For department users:

```text
My Stock Requests
```

Pages:

```text
Requisitions Index
Create Requisition
Requisition Show
Review Requisition
Issue Requisition
Acknowledge Receipt
```

Store dashboard should show:

```text
Pending Requisitions
Awaiting Acknowledgement
```

Department dashboard should show:

```text
My Pending Stock Requests
Stock Awaiting My Acknowledgement
```

---

# 17. Supplier Ledger Restrictions

Update Supplier Ledger UI.

Manual actions allowed:

```text
Record Payment
Create Credit Note
Create Debit Note
```

Not allowed manually from ledger page:

```text
Goods Received
Return to Supplier
Purchase Order
```

These must come from their source workflows.

On ledger entry row, show source link:

```text
View PO
View GRN
View Return
View Payment
```

Filters must work.

---

# 18. Stock Location Main Store Rules

Backend validation:

* cannot deactivate Main Store
* cannot delete Main Store
* cannot change `is_main` to false for Main Store
* cannot assign Main Store to non-Store department
* cannot create second Main Store unless explicitly allowed

Frontend:

* disable edit/deactivate/delete actions for Main Store
* show “System Default” badge

---

# 19. Services to Create or Update

Update/create:

```text
ProductService
ProductPricingService
StockLocationService
StockMovementService
StockBalanceService
StockTransferService
StockAdjustmentService
StockReturnService
PurchaseOrderService
ProcurementService
GoodsReceivedNoteService
PurchaseReturnService
StockRequisitionService
SupplierLedgerService
```

Important:

* `StockMovementService` is the only stock movement writer.
* `StockBalanceService` is the only stock balance updater.
* `SupplierLedgerService` is the only supplier ledger writer.
* PO receiving must not write to old drug stock.
* Stock actions must use product_id.

---

# 20. Tests Required

Add or update tests for:

## Products

1. Product filters work.
2. Product pagination preserves filters.
3. Product insurance price badges/column shows correct state.

## Supplier Ledger

4. Ledger filters work.
5. Ledger manual entry only allows payment, credit note, debit note.
6. Goods received ledger entries come only from GRN workflow.
7. Return to supplier ledger entries come only from purchase return workflow.
8. Ledger row links to source document.

## Purchase Orders

9. PO filters work.
10. Total Ordered Value is correct.
11. Total Received Value is correct.
12. Outstanding Value is correct.

## Stock Locations

13. Stock location table renders correctly.
14. Notes column does not break layout.
15. Main Store cannot be edited/deactivated/deleted.

## Stock Balances / Receive Stock

16. Receive stock uses product_id, not drug_id.
17. Receive stock creates stock movement.
18. Receive stock updates stock balance.
19. Receive stock no longer throws `drug_id cannot be null`.

## Modals

20. Receive stock modal works.
21. Transfer modal works.
22. Adjustment modal works.
23. Return modal works.
24. Modals show validation errors and do not leave stuck backdrop.

## Purchase Returns

25. Purchase return can be created.
26. Posting purchase return creates RETURN_OUT movement.
27. Posting purchase return updates stock balance.
28. Posting purchase return creates supplier ledger entry.
29. Supplier return cannot be manually faked from ledger page.

## Stock Requisitions

30. Department can create requisition for products linked to department.
31. Store can approve requisition.
32. Store can partially approve requisition.
33. Store issue creates TRANSFER_OUT from Main Store.
34. Department acknowledgement creates TRANSFER_IN into department stock.
35. Department stock balance updates only after acknowledgement.
36. Acknowledged quantity cannot exceed issued quantity.
37. Requisition status updates correctly.

## Regression

38. Existing PO workflow still works.
39. Existing Product Stock Balance page still works.
40. Existing Pharmacy workflow still works.
41. Existing Investigation workflow still works.
42. Existing Procedure workflow still works.
43. No active workflow writes to legacy drug stock.

---

# 21. Deliverables

Provide:

1. Root cause analysis for each bug.
2. Files modified.
3. Product filters fixed.
4. Product pagination fixed.
5. Product insurance prices column added.
6. Supplier ledger restrictions added.
7. Supplier ledger source links added.
8. Supplier ledger filters fixed.
9. Purchase order totals fixed.
10. Purchase order filters fixed.
11. Stock location table fixed.
12. Main Store protection implemented.
13. Receive stock SQL error fixed.
14. Stock action modals implemented.
15. Purchase Returns implemented.
16. Stock Requisitions implemented.
17. Tests or verification notes.
18. Remaining TODOs.

---

# 22. Important Rules

Do not create a parallel inventory system.

Do not use drug_id for stock movement or balance.

Do not let PO receiving write to old drug stock.

Do not let departments create products from their catalogues.

Do not allow supplier returns to be manually entered from supplier ledger.

Do not allow purchase/goods received ledger entries to be manually created from supplier ledger.

Do not update department stock before acknowledgement in requisition flow.

Do not deactivate or edit Main Store through normal UI.

Do not break existing working workflows.

Do not bypass service classes.

Now inspect the current implementation and fix these bugs carefully while keeping the unified product-based inventory system intact.
