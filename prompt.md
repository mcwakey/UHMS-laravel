Next is **Accounting Phase 6: Stock Valuation, COGS, Consumables Expense & Inventory Accounting**.

This phase connects the stock system to accounting properly.

You are working on UHMS — Ultimate Hospital Management System.

Accounting Phase 1 Foundation is complete.
Accounting Phase 2 Billing → Accounting Posting is complete.
Accounting Phase 3 Payments, Discounts, Credit Notes, Write-offs, Refunds & Reversals is complete.
Accounting Phase 4 Sponsors, Insurance, Corporate Receivables & AR Aging is complete.
Accounting Phase 5 Procurement, Supplier Ledger, Accounts Payable & AP Aging is complete.

Now proceed with Accounting Phase 6:

Stock Valuation, COGS, Consumables Expense & Inventory Accounting

Goal:
Implement full inventory accounting so UHMS can correctly value stock, account for stock received, transferred, consumed, dispensed, adjusted, damaged, expired, returned, and sold.

This phase must connect the existing product/stock movement system to accounting without replacing stock movements or stock balances.

Do not replace existing Products.
Do not replace stock movements.
Do not replace stock balances.
Do not replace pharmacy dispensing.
Do not replace procurement/goods receiving.
Do not create a parallel stock system.
Do not write the full automated test suite yet. Full tests will be written after the full accounting implementation is complete.

Important rule:

Products = physical items.
Stock movements = operational inventory movement.
Stock valuation = financial value of inventory.
Journal entries = accounting impact.

---

# 1. Main Objective

Implement accounting support for:

1. Stock valuation
2. Inventory account mapping
3. Cost of goods sold
4. Pharmacy dispensing cost
5. Department consumable usage
6. Investigation consumables expense
7. Procedure consumables expense
8. Stock adjustments
9. Damaged stock
10. Expired stock
11. Stock transfers
12. Purchase returns
13. Inventory valuation report
14. Stock movement accounting status
15. Activity logs
16. Manual verification documentation

---

# 2. Core Accounting Rules

## A. Stock received from supplier

Already handled in Phase 5 through goods receiving:

```text
Dr Inventory
Cr Supplier Payables
````

Do not duplicate this posting here.

Phase 6 may verify and improve inventory account mapping, but should not double-post goods receiving.

---

## B. Pharmacy product sold / dispensed as billable item

There are two accounting sides:

### Revenue side

Handled by billing phases:

```text
Dr Receivable / Cash
Cr Pharmacy Revenue
```

Do not duplicate revenue here.

### Cost side

When pharmacy stock is dispensed and stock leaves inventory:

```text
Dr Cost of Goods Sold
Cr Inventory
```

Example:

```text
Dr Pharmacy COGS          40
Cr Pharmacy Inventory     40
```

This records the cost of the medicine sold.

---

## C. Department consumable used but not billed separately

Example:

* gloves used in lab
* reagent consumed during test
* gauze used in theatre
* syringe used in ward

Accounting:

```text
Dr Consumables Expense
Cr Inventory
```

Example:

```text
Dr Laboratory Consumables Expense     15
Cr Laboratory Reagents Inventory      15
```

---

## D. Consumable used and billed to patient

There may be both:

Revenue side:

```text
Dr Receivable
Cr Revenue
```

Cost side:

```text
Dr Cost of Goods Sold / Consumables Expense
Cr Inventory
```

Do not skip cost just because it was billed.

Do not duplicate revenue.

---

## E. Stock transfer between locations

Normal internal transfer:

```text
No P&L impact
```

If the inventory account is the same:

```text
No journal entry required
```

If different inventory accounts are used by location/department:

```text
Dr Destination Inventory
Cr Source Inventory
```

Example:

```text
Dr Pharmacy Inventory        500
Cr Main Store Inventory      500
```

Use project accounting settings.

Do not treat transfer as expense.

---

## F. Stock adjustment increase

If stock is increased because of correction:

```text
Dr Inventory
Cr Inventory Adjustment Gain
```

or use adjustment account configured.

---

## G. Stock adjustment decrease

If stock is reduced because of correction/loss:

```text
Dr Inventory Adjustment Loss / Expense
Cr Inventory
```

---

## H. Damaged / expired stock

When stock is written off as damaged or expired:

```text
Dr Damaged / Expired Stock Expense
Cr Inventory
```

---

## I. Purchase return to supplier

Already partly handled in Phase 5:

```text
Dr Supplier Payables
Cr Inventory
```

Do not duplicate if Phase 5 already posts purchase return.

Phase 6 should ensure inventory value reduction is correct.

---

# 3. Stock Valuation Method

Implement one stock valuation method first.

Recommended default:

```text
Weighted Average Cost
```

Reason:
It is simpler and safer for hospital stock than FIFO in the first implementation.

Required behavior:

* every product/location stock balance should carry quantity and average cost where possible
* goods receiving updates average cost
* stock OUT movement uses current average cost
* stock transfer preserves cost
* stock adjustment uses configured cost or average cost
* inventory valuation report uses quantity_on_hand × average_cost

If the system already has unit_cost on stock movements, reuse it.

Do not invent cost if not available; fail clearly or use last known average cost with documentation.

---

# 4. Data Model Updates

Inspect current tables first:

```text
products
stock_movements
stock_balances
product_stock_movements
product_stock_balances
goods_receipts
goods_receipt_items
purchase_order_items
dispensing_records
consumable_usages
purchase_returns
```

Add fields only where needed.

Possible fields on stock movements:

```text
unit_cost nullable
total_cost nullable
valuation_method nullable
journal_entry_id nullable
accounting_status nullable
accounting_posted_at nullable
accounting_error nullable
reversal_journal_entry_id nullable
```

Possible fields on stock balances:

```text
quantity_on_hand
average_cost nullable
total_value nullable
last_valued_at nullable
```

Possible fields on products:

```text
inventory_account_id nullable
cogs_account_id nullable
expense_account_id nullable
valuation_method nullable
```

Possible fields on product categories/types:

```text
inventory_account_id nullable
cogs_account_id nullable
expense_account_id nullable
```

Do not add duplicate fields if equivalents already exist.

---

# 5. Required Services

Create or update:

```text
StockValuationService
InventoryAccountingPostingService
StockCostingService
InventoryAccountResolver
COGSAccountResolver
ConsumablesExpenseAccountResolver
StockAdjustmentAccountingService
InventoryValuationReportService
```

Use existing services if available.

Controllers must remain thin.

Stock accounting must be service-layer driven.

---

# 6. StockValuationService

Required methods:

```php
calculateWeightedAverageCost(Product $product, StockLocation $location, float $incomingQty, float $incomingUnitCost): float

applyIncomingStock(Product $product, StockLocation $location, float $qty, float $unitCost): void

applyOutgoingStock(Product $product, StockLocation $location, float $qty): array

getCurrentAverageCost(Product $product, StockLocation $location): float

getStockValue(Product $product, StockLocation $location): float
```

Outgoing stock should return:

```php
[
    'unit_cost' => 12.50,
    'total_cost' => 125.00,
    'valuation_method' => 'weighted_average',
]
```

---

# 7. InventoryAccountingPostingService

Required methods:

```php
postDispensingCost(DispensingRecord $record): ?JournalEntry

postConsumableUsageCost(ConsumableUsage $usage): ?JournalEntry

postStockAdjustment(StockMovement $movement): ?JournalEntry

postDamagedExpiredStock(StockMovement $movement): ?JournalEntry

postTransferIfRequired(StockTransfer $transfer): ?JournalEntry

postPurchaseReturnInventoryEffect(PurchaseReturn $return): ?JournalEntry
```

Rules:

* use existing stock movement as source
* do not post if source already has journal_entry_id
* do not duplicate Phase 5 procurement postings
* create reversal journal if stock movement is reversed
* fail clearly if account mapping missing

---

# 8. Account Mapping

Use priority order.

## Inventory account

```text
1. Product inventory_account_id
2. Product category inventory_account_id
3. Product type inventory account
4. Stock location / department inventory account
5. Accounting settings default inventory account
```

## COGS account

```text
1. Product cogs_account_id
2. Product category cogs_account_id
3. Department/service cogs account
4. Accounting settings cost_of_goods_sold_account_id
```

## Consumables expense account

```text
1. Product expense_account_id
2. Product category expense_account_id
3. Department expense account
4. Accounting settings consumables_expense_account_id
```

## Damaged/expired stock expense

```text
1. Product/category damaged stock expense account
2. Accounting settings damaged_expired_stock_expense_account_id
3. Accounting settings inventory_adjustment_loss_account_id
```

Fail clearly if required account is missing.

Do not silently post to random accounts.

---

# 9. Pharmacy Dispensing Cost Posting

When pharmacy dispensing creates a stock OUT movement:

Operational behavior remains:

* prescription is dispensed
* stock decreases from pharmacy location
* billing/revenue remains handled by billing module

Accounting cost posting:

```text
Dr Pharmacy COGS
Cr Pharmacy Inventory
```

Context:

```text
product_id
stock_location_id
stock_movement_id
dispensing_record_id
patient_id
visit_id
invoice_id if available
```

Do not duplicate pharmacy revenue posting.

Do not duplicate MAR administration logging.

---

# 10. Consumable Usage Cost Posting

When consumables are used in:

* investigation
* procedure/theatre
* emergency
* ward/admission
* general clinical consumption

Accounting:

```text
Dr Department Consumables Expense
Cr Department Inventory
```

Context:

```text
product_id
stock_location_id
stock_movement_id
consumable_usage_id
patient_id nullable
visit_id nullable
department_id
source_type
source_id
```

If the usage is patient-related, context may carry patient/visit.

If generic department usage, do not force patient context.

---

# 11. Stock Adjustment Posting

For adjustment increase:

```text
Dr Inventory
Cr Inventory Adjustment Gain
```

For adjustment decrease:

```text
Dr Inventory Adjustment Loss
Cr Inventory
```

Rules:

* require reason
* require permission
* require approval if high-value
* use average cost for valuation
* adjustment must be traceable in stock movement and journal

---

# 12. Damaged / Expired Stock

When stock is marked damaged or expired:

Operational:

* stock OUT movement
* reason: damaged/expired
* optional batch/expiry reference
* approval if needed

Accounting:

```text
Dr Damaged / Expired Stock Expense
Cr Inventory
```

This should appear in expense reports.

---

# 13. Stock Transfers

For internal transfers:

If both locations use same inventory account:

```text
No accounting journal required
```

Still keep operational stock movement.

If source and destination map to different inventory accounts:

```text
Dr Destination Inventory
Cr Source Inventory
```

Transfer should preserve stock value.

Do not create gain/loss on transfer.

---

# 14. Inventory Valuation Report

Create report:

```text
Inventory Valuation
```

Filters:

```text
Date
Location
Department
Product Type
Product Category
Product
```

Columns:

```text
Product Code
Product Name
Location
Quantity on Hand
Average Cost
Total Value
Inventory Account
Last Movement Date
```

Summary:

```text
Total Inventory Value
Value by Location
Value by Product Type
Value by Department
```

Rules:

* use stock balances and average cost
* do not calculate from all movements on every request if stock_balances has value fields
* report must reconcile with inventory accounts in General Ledger as much as possible

---

# 15. Stock Movement UI

On stock movement details, show:

```text
Unit Cost
Total Cost
Valuation Method
Accounting Status
Journal Entry
```

On product stock balance page, show:

```text
Quantity on Hand
Average Cost
Total Value
```

Only show accounting values to authorized users.

---

# 16. Permissions

Add or verify:

```text
inventory.valuation.view
inventory.cost.view
inventory.accounting.post
inventory.accounting.retry
stock.adjustment.approve
stock.writeoff.approve
reports.inventory_valuation.view
```

Do not show cost/valuation to users without permission.

Pharmacy/lab/theatre users may see quantity but not necessarily cost.

---

# 17. Activity Logs

Use ActivityLogService.

Operational logs may already exist. Do not duplicate.

Add accounting-specific logs:

```text
ACCOUNTING_POSTED_FOR_STOCK_DISPENSE
ACCOUNTING_POSTED_FOR_CONSUMABLE_USAGE
ACCOUNTING_POSTED_FOR_STOCK_ADJUSTMENT
ACCOUNTING_POSTED_FOR_DAMAGED_STOCK
ACCOUNTING_POSTED_FOR_EXPIRED_STOCK
ACCOUNTING_POSTED_FOR_STOCK_TRANSFER
ACCOUNTING_POSTING_FAILED
```

Context:

```text
product_id
stock_location_id
stock_movement_id
stock_transfer_id
dispensing_record_id
consumable_usage_id
journal_entry_id
unit_cost
total_cost
valuation_method
patient_id nullable
visit_id nullable
```

---

# 18. Idempotency

Every stock accounting source should post once.

Use:

```text
journal_entry_id
accounting_status
accounting_posted_at
accounting_error
reversal_journal_entry_id
```

Rules:

* retry failed posting must not duplicate journal entry
* stock movement reversal creates accounting reversal if original was posted
* goods receiving is not double-posted if Phase 5 already posted it
* purchase return is not double-posted if Phase 5 already posted it

---

# 19. Manual Verification Strategy

Do not write the full automated test suite yet.

Full accounting tests will be written after all accounting phases are implemented.

For this phase, provide manual verification notes.

Manual verification required:

1. Receive stock and confirm average cost updates.
2. Confirm inventory valuation shows stock value.
3. Dispense pharmacy item and confirm stock reduces.
4. Confirm dispensing cost journal debits COGS and credits inventory.
5. Use investigation consumable and confirm stock reduces.
6. Confirm consumable usage journal debits consumables expense and credits inventory.
7. Perform stock transfer between same-account locations and confirm no journal is created.
8. Perform stock transfer between different-account locations and confirm Dr destination inventory / Cr source inventory.
9. Perform stock adjustment increase and confirm Dr Inventory / Cr Adjustment Gain.
10. Perform stock adjustment decrease and confirm Dr Adjustment Loss / Cr Inventory.
11. Mark stock damaged/expired and confirm Dr Expense / Cr Inventory.
12. Confirm inventory valuation report totals match stock balances.
13. Confirm cost fields are hidden from unauthorized users.
14. Confirm duplicate accounting posting is prevented.
15. Confirm Trial Balance remains balanced.
16. Confirm General Ledger shows inventory/COGS/expense postings.
17. Confirm logs:audit Stage-2 gate remains green.
18. Confirm procurement, pharmacy, investigations, theatre, and billing workflows still work.

Do not skip validation, permissions, accounting posting, activity logs, or idempotency because tests are deferred.

---

# 20. Documentation

Create:

```text
docs/ACCOUNTING_PHASE_6_STOCK_VALUATION_INVENTORY_ACCOUNTING_REPORT.md
```

Include:

* valuation method implemented
* stock costing rules
* dispensing cost posting
* consumable usage posting
* stock adjustment posting
* damaged/expired stock posting
* transfer accounting rule
* account mappings
* inventory valuation report
* UI changes
* permissions
* activity logs
* manual verification completed
* tests deferred list
* known risks/TODOs
* next phase recommendation

---

# 21. Acceptance Criteria

Phase 6 is complete when:

* weighted average cost is implemented or existing valuation method is documented
* stock balances carry cost/value where appropriate
* dispensing cost can post Dr COGS / Cr Inventory
* consumable usage can post Dr Expense / Cr Inventory
* stock adjustments can post correctly
* damaged/expired stock can post correctly
* transfers post only when inventory accounts differ
* inventory valuation report works
* cost visibility is permission-protected
* duplicate postings are prevented
* Trial Balance remains balanced
* General Ledger shows stock valuation postings
* activity logs are written
* logs:audit Stage-2 gate remains green
* manual verification is documented
* full automated tests remain deferred until final accounting implementation pass

---

# 22. Important Rules

Do not duplicate goods receiving postings from Phase 5.
Do not duplicate purchase return postings from Phase 5.
Do not treat stock transfer as expense.
Do not treat pharmacy payment as revenue here.
Do not duplicate billing revenue postings.
Do not expose stock cost to unauthorized users.
Do not create parallel stock ledgers.
Do not bypass stock movement service.
Do not bypass ActivityLogService.
Do not enable full automated tests yet.

Proceed with Accounting Phase 6: Stock Valuation, COGS, Consumables Expense & Inventory Accounting now.
