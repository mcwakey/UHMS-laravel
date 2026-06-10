# Accounting Phase 6 — Stock Valuation, COGS, Consumables Expense & Inventory Accounting

## Summary
Connects the existing product/stock-movement system to the GL. Stock is now valued at
**weighted-average cost**; costed OUT movements post their inventory effect
(COGS / consumables expense / adjustments / damaged-expired) automatically, and a new
**Inventory Valuation** report reads the costed balances. Nothing in stock movements,
balances, dispensing or procurement was replaced — a single posting hook sits inside the
canonical mover (`ProductStockMovementService::createMovement`).

## Valuation method — Weighted Average Cost
- `stock_balances` gained `average_cost`, `total_value`, `last_valued_at`.
- Incoming stock recomputes average cost: `((oldQty·oldAvg)+(inQty·inCost)) / newQty`.
- Outgoing stock is costed at the current average cost.
- `StockValuationService` (`quoteMovementCost`, `applyBalance`, `getCurrentAverageCost`,
  `getStockValue`) is called from `createMovement`, which now stores `unit_cost`,
  `total_cost` and `valuation_method` on every movement.
- Legacy stock received before Phase 6 has no cost basis → its OUT movements cost 0 and
  post nothing (documented). Costs accrue as new stock is received/adjusted-in with a cost.

## Posting rules (`InventoryAccountingPostingService::postForMovement`, dispatched by type)
| Movement | Journal |
|---|---|
| Pharmacy dispense | **Dr COGS / Cr Inventory** |
| Investigation / procedure / ward / emergency consumption | **Dr Consumables Expense / Cr Inventory** |
| Adjustment increase | **Dr Inventory / Cr Inventory Adjustment Gain** |
| Adjustment decrease | **Dr Inventory Adjustment Loss / Cr Inventory** |
| Damaged / Expired | **Dr Damaged/Expired Stock Expense / Cr Inventory** |
| Transfer (in/out) | none — both locations map to the same inventory account → `not_applicable` |
| Goods receipt / purchase return | **owned by Phase 5** — movement marked `not_applicable` (no double-post) |
| Opening stock / reversals | `not_applicable` |

Inventory accounts are resolved by product type (drug→Pharmacy, reagent→Lab Reagents,
consumable/supply→Consumables, else default). Since accounts are keyed by product type (not
location), a transfer's source and destination resolve to the **same** account, so
`postTransferIfRequired` correctly creates no journal — exactly per the spec.

## Idempotency & reversal
Every movement posts at most once (guarded on `journal_entry_id` and
`accounting_status ∈ {posted, reversed, not_applicable}`). Failures set `failed` +
`accounting_error` (operational movement still succeeds). `reverseForMovement` creates a
controlled reversal entry; posted entries are never edited.

## Account mapping (settings — added this phase)
`inventory_adjustment_gain_account_id` (4970), `inventory_adjustment_loss_account_id` (5210),
`damaged_expired_stock_expense_account_id` (5220). COGS (5100) and Consumables Expense (5200)
already existed. Seeded by `AccountingChartSeeder`; **missing accounts fail loudly**.

## Inventory Valuation report
`InventoryValuationReportService` reads `stock_balances` (qty × avg = value) — a fast read,
no ledger replay. Filters: search, location, product type. Columns: code, product, type,
location, qty, **avg cost, total value, inventory account** (cost columns gated by
`inventory.cost.view`), last movement. Summary: total value + by location/type.
At `Store → Inventory Valuation` (`admin.store.stock.valuation`).

## UI
Stock movement detail now shows **Total Cost, Valuation Method, Accounting Status, Journal
Entry** (cost gated by `inventory.cost.view`). Valuation report cost columns are
permission-gated. Sidebar: "Inventory Valuation" under Store & Procurement.

## Permissions (RoleSeeder)
`inventory.valuation.view`, `inventory.cost.view`, `inventory.accounting.post`,
`inventory.accounting.retry`, `stock.adjustment.approve`, `stock.writeoff.approve`,
`reports.inventory_valuation.view`.

## Activity logs (ActivityLogService — facility level)
`ACCOUNTING_POSTED_FOR_STOCK_DISPENSE`, `..._CONSUMABLE_USAGE`, `..._STOCK_ADJUSTMENT`,
`..._DAMAGED_STOCK`, `..._EXPIRED_STOCK`, `ACCOUNTING_POSTING_FAILED`,
`ACCOUNTING_REVERSAL_CREATED` — with product/location/movement/cost context.

## Manual verification completed (tinker, rolled back)
- Adjustment-in 20 → WAC average cost + total value updated; **Dr Inventory / Cr Adjustment Gain**.
- Dispense → **Dr COGS / Cr Pharmacy Inventory** (`posted`); consumption → **Dr Consumables Expense / Cr Inventory**; damaged → **Dr Damaged/Expired / Cr Inventory**; adjustment-out → **Dr Adjustment Loss / Cr Inventory**.
- **GL inventory account net change reconciles with stock total-value change**; **Trial Balance balanced** throughout.
- Goods receipt + purchase return movements marked `not_applicable` (no double-post over Phase 5).
- Valuation report renders and totals from `stock_balances`.

## Tests deferred
Full automated inventory-accounting tests deferred to the final accounting pass (per spec).
Validation, permissions, posting, idempotency and logs are implemented now.

## Known risks / TODOs
- **Legacy stock** (pre-Phase-6) has `average_cost = 0`; run a one-off costing/backfill or
  re-receive to establish a cost basis. Until then those items value at 0 and their OUT
  movements post no COGS.
- **Opening stock** and direct non-GRN receipts are `not_applicable` (no Dr Inventory); use the
  Phase-5 goods-receipt path or adjustment-in (which posts Dr Inventory) to establish GL value.
- Location/department-specific inventory accounts are not modelled (mapping is by product type),
  so transfers never post — consistent with current chart but a future option.
- Finer cost-gating on the pre-existing `unit_cost` line of the movement detail is a minor TODO.

## Next phase recommendation
Phase 7: period-end inventory reconciliation (GL inventory vs valuation report), stock
write-off approval workflow for high-value adjustments, and a legacy-stock cost backfill command.
