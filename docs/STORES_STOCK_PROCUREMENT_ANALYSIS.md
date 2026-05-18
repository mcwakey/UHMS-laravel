# Stores, Stock & Procurement — Full Module Analysis

> Companion document to [PRODUCTS_AND_SERVICES_ANALYSIS.md](PRODUCTS_AND_SERVICES_ANALYSIS.md).
> Scope: every file in this Laravel 12 codebase that touches store locations, on-hand
> quantities, ledger movements, supplier procurement, and goods receipt.
> Audience: maintainers + auditors. Date: May 2026.

---

## 0. TL;DR (executive)

The module **works for drugs** and **does not work for generic products**. Two parallel
inventory subsystems exist (drugs ↔ products) but only one is wired into procurement.
Specifically:

| Symptom user reports | Root cause |
|---|---|
| "PO is received but nothing shows under balances." | `ProcurementService::receiveItems()` updates `stock_movements` + `stock_balances` (DRUG ledger). `product_stock_balances` (the table the new **Product Stock → Balances** page reads) is **never touched** by a PO. If the buyer thinks of the item as a Product, the PO leaves no trace in the Product ledger. |
| "Stock has lots of inconsistencies." | Five concrete causes: (1) dual unlinked ledgers, (2) no reversal when an invoice/dispense is voided, (3) legacy `drug_stock` per-batch table is mutated in parallel to the ledger, (4) `stock_transfers` references locations by **name string** instead of FK, (5) no Requisition or GRN entity — so receipt date, delivery note number, and partial deliveries cannot be tracked properly. |

The fix path is documented in §10 (Gaps) and §11 (Recommended roadmap).

---

## 1. Why this module is vital

The hospital cannot bill what it cannot count. Every other revenue-bearing module depends
on accurate on-hand quantities:

| Dependent module | Why it needs stock |
|---|---|
| Pharmacy dispensing | Must decrement on issue + refuse below zero (unless `stock.override_negative`) |
| Lab / Investigation | Reagents and test kits are consumables priced into the test |
| Theatre / Wards | Consumables (sutures, gauze, gloves, IV sets) drive ward billing |
| Billing & Insurance | `unit_cost` from procurement is the basis for COGS, mark-up policies, NHIA tariffs |
| Finance & Audit | Supplier ledger + payment schedule, opening/closing stock valuation, expired-stock write-offs |
| Regulatory | Expiry tracking, batch traceability for recalls (FDA, Pharmacy Council Ghana) |

Without a single trusted ledger, every downstream figure (revenue, cost of sales, gross
margin, insurance claim, stock-take variance) is suspect.

---

## 2. Domain glossary

| Term | Meaning in this codebase |
|---|---|
| **Stock Location** | A physical/virtual bin: *Main Store*, *Pharmacy*, *Ward A*, *Lab*. Table `stock_locations`. |
| **Movement** | A signed ledger entry (`IN` or `OUT`) at one location for one item. Source of truth. |
| **Balance** | Cached `quantity_on_hand` per `(item, location)` — denormalised sum of movements. |
| **Drug** | Pharmacy-specific catalog (`drugs` table). Legacy primary inventory model. |
| **Product** | Generic supply catalog (`products` table). Newer, hospital-wide. |
| **DrugStock** | Legacy per-batch table that pre-dates the ledger; still mutated by `ProcurementService` and `PharmacyService`. |
| **PO** | `purchase_orders` row. Header for items being bought. |
| **GRN** | Goods Received Note. **Does not exist as its own entity** — receipt is recorded in-place on the PO. |
| **Requisition** | Internal request from a ward to the store. **Does not exist as its own entity** in this codebase. |

---

## 3. Inventory of files (where everything lives)

### 3.1 Migrations
| File | Table | Status |
|---|---|---|
| [database/migrations/2026_05_13_090000_create_stock_locations_table.php](database/migrations/2026_05_13_090000_create_stock_locations_table.php) | `stock_locations` | Canonical |
| [database/migrations/2026_05_13_090100_create_stock_movements_table.php](database/migrations/2026_05_13_090100_create_stock_movements_table.php) | `stock_movements` (drugs) | Canonical |
| [database/migrations/2026_05_13_090200_create_stock_balances_table.php](database/migrations/2026_05_13_090200_create_stock_balances_table.php) | `stock_balances` (drugs) | Canonical |
| [database/migrations/2026_05_13_101100_create_product_stock_tables.php](database/migrations/2026_05_13_101100_create_product_stock_tables.php) | `product_stock_movements`, `product_stock_balances` | Canonical (newer) |
| [database/migrations/2026_04_12_700001_create_suppliers_table.php](database/migrations/2026_04_12_700001_create_suppliers_table.php) | `suppliers` | Canonical |
| [database/migrations/2026_04_12_700002_create_purchase_orders_table.php](database/migrations/2026_04_12_700002_create_purchase_orders_table.php) | `purchase_orders` | Canonical |
| [database/migrations/2026_04_12_700003_create_purchase_order_items_table.php](database/migrations/2026_04_12_700003_create_purchase_order_items_table.php) | `purchase_order_items` | Drugs + investigation items only |
| [database/migrations/2026_04_12_700004_create_stock_transfers_table.php](database/migrations/2026_04_12_700004_create_stock_transfers_table.php) | `stock_transfers` | Uses **string** location names |
| [database/migrations/2026_04_12_700005_create_stock_transfer_items_table.php](database/migrations/2026_04_12_700005_create_stock_transfer_items_table.php) | `stock_transfer_items` | Drugs only |
| [database/migrations/2026_04_12_700006_add_location_to_drug_stock.php](database/migrations/2026_04_12_700006_add_location_to_drug_stock.php) | `drug_stock` (legacy per-batch) | **Legacy** — still updated |

### 3.2 Models
| Model | File | Role |
|---|---|---|
| `StockLocation` | [app/Models/StockLocation.php](app/Models/StockLocation.php) | Bin master |
| `StockMovement` | [app/Models/StockMovement.php](app/Models/StockMovement.php) | Drug ledger entry |
| `StockBalance` | [app/Models/StockBalance.php](app/Models/StockBalance.php) | Drug cached balance |
| `ProductStockMovement` | [app/Models/ProductStockMovement.php](app/Models/ProductStockMovement.php) | Product ledger entry |
| `ProductStockBalance` | [app/Models/ProductStockBalance.php](app/Models/ProductStockBalance.php) | Product cached balance |
| `DrugStock` | [app/Models/DrugStock.php](app/Models/DrugStock.php) | Legacy per-batch (still active) |
| `Supplier` | [app/Models/Supplier.php](app/Models/Supplier.php) | Vendor master |
| `SupplierLedgerEntry` | [app/Models/SupplierLedgerEntry.php](app/Models/SupplierLedgerEntry.php) | A/P ledger |
| `PurchaseOrder` | [app/Models/PurchaseOrder.php](app/Models/PurchaseOrder.php) | PO header |
| `PurchaseOrderItem` | [app/Models/PurchaseOrderItem.php](app/Models/PurchaseOrderItem.php) | PO line (drug_id or investigation_item_id) |
| `StockTransfer` / `StockTransferItem` | `app/Models/StockTransfer*.php` | Transfer header + line (drug-only, string locations) |
| `InvestigationItemStock` | [app/Models/InvestigationItemStock.php](app/Models/InvestigationItemStock.php) | Lab consumable per-batch (legacy pattern) |

### 3.3 Services
| Service | File | What it does |
|---|---|---|
| `StockMovementService` | [app/Services/StockMovementService.php](app/Services/StockMovementService.php) | Single writer for drug ledger; wraps in `DB::transaction`, calls `StockBalanceService` |
| `StockBalanceService` | [app/Services/StockBalanceService.php](app/Services/StockBalanceService.php) | Increase/decrease/rebuild drug balances; uses `lockForUpdate` |
| `ProductStockMovementService` | [app/Services/ProductStockMovementService.php](app/Services/ProductStockMovementService.php) | Same as above, for products |
| `ProductStockService` | [app/Services/ProductStockService.php](app/Services/ProductStockService.php) | High-level: `receive`, `adjust`, `transfer`, `balancesGroupedByLocation` |
| `ProcurementService` | [app/Services/ProcurementService.php](app/Services/ProcurementService.php) | PO lifecycle: create / submit / approve / **receiveItems** / cancel |
| `StockTransferService` | [app/Services/StockTransferService.php](app/Services/StockTransferService.php) | Transfer lifecycle for drugs |
| `StockAdjustmentService` | [app/Services/StockAdjustmentService.php](app/Services/StockAdjustmentService.php) | Manual adjust (IN/OUT/DAMAGED/EXPIRED) for drugs |
| `StockReturnService` | [app/Services/StockReturnService.php](app/Services/StockReturnService.php) | Customer/supplier returns for drugs |
| `SupplierLedgerService` | [app/Services/SupplierLedgerService.php](app/Services/SupplierLedgerService.php) | Record `goods_received` / `payment` / `return` entries |
| `PharmacyService` | [app/Services/PharmacyService.php](app/Services/PharmacyService.php) | `dispenseItem` — only place stock is decremented on a billable issue |

### 3.4 Controllers + routes (see [routes/web.php](routes/web.php))
| Controller | Prefix | Route names |
|---|---|---|
| `PurchaseOrderController` | `/admin/store/purchase-orders` | `admin.purchase-orders.*` (`index`, `create`, `store`, `show`, `submit`, `approve`, `receive`, `cancel`, `addItem`, `removeItem`) |
| `StockController` (drugs) | `/admin/store/stock` | `admin.stock.balances`, `admin.stock.ledger`, `admin.stock.locations`, `admin.stock.adjustment.*`, `admin.stock.return.*` |
| `ProductStockController` | `/admin/product-stock` | `admin.product-stock.balances`, `admin.product-stock.ledger`, `admin.product-stock.receive(.form)`, `admin.product-stock.adjust(.form)`, `admin.product-stock.transfer(.form)`, `admin.product-stock.return(.form)` |
| `StockTransferController` (drugs) | `/admin/store/transfers` | `admin.transfers.*` |
| `SupplierController` | `/admin/store/suppliers` | `admin.suppliers.*`, `admin.suppliers.ledger.*` |
| `StockLocationController` | `/admin/stock-locations` | `admin.stock-locations.*` |

### 3.5 Views
- `resources/views/admin/product-stock/{balances,ledger,receive,adjust,transfer,return}.blade.php`
- `resources/views/admin/stock-locations/index.blade.php`
- Drug-side: `resources/views/admin/store/{purchase-orders,stock,transfers,suppliers}/*.blade.php`

---

## 4. Data model — entity diagram (text)

```
                    ┌──────────────────┐
                    │ stock_locations  │  (Main Store, Pharmacy, Ward A, Lab, …)
                    └────────┬─────────┘
            ┌────────────────┼─────────────────┐
            │                │                 │
 (drug ledger)         (product ledger)   (legacy per-batch)
            │                │                 │
  ┌─────────▼────────┐ ┌────▼──────────────┐ ┌─▼─────────┐
  │ stock_movements  │ │product_stock_     │ │ drug_stock │  ← still mutated by
  │  (drug_id, qty,  │ │movements          │ │  (per batch)│   ProcurementService
  │   dir, source)   │ │ (product_id, …)   │ └────────────┘   + PharmacyService
  └─────────┬────────┘ └─────────┬─────────┘
            │                    │
  ┌─────────▼────────┐ ┌─────────▼─────────┐
  │ stock_balances   │ │product_stock_     │
  │ (cached qty)     │ │balances           │
  └──────────────────┘ └───────────────────┘

  ┌──────────────┐    ┌──────────────────┐    ┌──────────────────────┐
  │  suppliers   │───<│ purchase_orders  │───<│ purchase_order_items │
  └──────┬───────┘    └────────┬─────────┘    │ (drug_id OR          │
         │                     │              │  investigation_item) │
         │                     │              └──────────────────────┘
         ▼                     ▼
  supplier_ledger_   ── feeds ── on PO receipt (goods_received entry)
  entries

  ┌────────────────────┐    ┌─────────────────────┐
  │ stock_transfers    │───<│ stock_transfer_items│   (drug-only,
  │  (from/to as TEXT) │    └─────────────────────┘    string locations ❌)
  └────────────────────┘
```

The two ledgers (`stock_*` vs `product_stock_*`) are **structurally identical** but **never
joined**. There is no foreign key between `drugs` and `products`. A drug is **not** a product
and vice-versa in the current schema.

---

## 5. Workflows (end-to-end)

### 5.1 Drug procurement (works correctly)

```mermaid
flowchart LR
    A[Create PO<br/>status=draft] --> B[Add Items<br/>drug_id, qty, unit_cost]
    B --> C[Submit<br/>status=submitted]
    C --> D[Approve<br/>status=approved]
    D --> E[Receive Items]
    E --> F[ProcurementService.receiveItems]
    F --> G1[insert drug_stock<br/>per-batch row]
    F --> G2[StockMovementService.createMovement<br/>PURCHASE_RECEIVED]
    G2 --> H[stock_movements row]
    G2 --> I[StockBalanceService.increase<br/>→ stock_balances.quantity_on_hand]
    F --> J[SupplierLedgerEntry<br/>type=goods_received]
    F --> K[PO status<br/>partially_received | received]
```

Files:
- [PurchaseOrderController::receive()](app/Http/Controllers/Admin/PurchaseOrderController.php) → validation
- [ProcurementService::receiveItems()](app/Services/ProcurementService.php) → orchestration
- [StockMovementService::createMovement()](app/Services/StockMovementService.php) → wraps in `DB::transaction`, writes movement + calls `StockBalanceService`
- [StockBalanceService::increase()](app/Services/StockBalanceService.php) → `lockForUpdate`, upsert into `stock_balances`

### 5.2 Product procurement (**broken — gap G-A**)

There is **no PO path for products**. `purchase_order_items` only stores `drug_id` or
`investigation_item_id`. Users wanting to bring product stock in must use the **Receive**
form under *Product Stock → Receive* ([ProductStockController::receive()](app/Http/Controllers/Admin/ProductStockController.php))
which writes directly to `product_stock_movements` (movement_type = `PURCHASE_RECEIVED`)
**without any PO, supplier ledger entry, or audit link to a vendor**.

This is the precise reason behind the user report: *"PO received, nothing under balances."*
If the item is a Product (not a Drug), the PO cannot reference it, and the Product Stock
**Balances** page (which reads `product_stock_balances`) shows nothing.

### 5.3 Pharmacy dispensing → invoicing

```
Prescription created (no stock change)
    └─> Pharmacist dispenses → PharmacyService::dispenseItem
            ├─> DrugStock.quantity decremented per batch (FEFO)
            ├─> StockMovementService.createMovement(PHARMACY_DISPENSED)
            │       └─> stock_balances.quantity_on_hand decreased
            └─> InvoiceItem appended via BillingService.addItemToVisitInvoice
```

### 5.4 Invoice cancellation — **no reversal (gap G-B)**

When an invoice or a single invoice line is voided/deleted, nothing emits a `RETURN_IN` /
reversal movement. The stock count remains decremented but the revenue line is gone →
positive variance on the next stock-take with no audit trail.

### 5.5 Direct adjustment & transfer

Adjustment: `ProductStockController::adjust` → `ProductStockService::adjust` → ledger + balance.
Transfer: validated source ≠ destination, paired `TRANSFER_OUT` + `TRANSFER_IN` movements
linked by `source_id`. The **drug** variant additionally rewrites legacy `drug_stock` rows
(extra surface area for bugs).

### 5.6 Requisition — **does not exist (gap G-C)**

The user-manual references "Requisition → PO" but there is no `requisitions` table,
model, controller, or service. Ward → Store internal transfers are ad-hoc.

---

## 6. How "balance" is computed today

Two parallel doctrines exist:

| Place | Method | Risk |
|---|---|---|
| Drug ledger | `stock_balances.quantity_on_hand` (cached). Rebuildable via [StockBalanceService::rebuildAllBalances()](app/Services/StockBalanceService.php) | Drift if a writer skips the service |
| Drug per-batch | `SUM(drug_stock.quantity)` per `(drug_id, location)` | Mutated in 3+ places independently |
| Drug accessor | `Drug::total_stock` accessor sums **balances** | OK if ledger is honored |
| Product ledger | `product_stock_balances.quantity_on_hand` | Same model, fewer writers (lower risk) |

When the three sources disagree (and they do, in practice), no automatic reconciler runs.

---

## 7. Permissions

Granted by [database/seeders/RoleSeeder.php](database/seeders/RoleSeeder.php):

| Domain | Permissions |
|---|---|
| Procurement | `store.purchase.view`, `store.purchase.create`, `store.purchase.approve` |
| Transfers | `store.transfer.view`, `store.transfer.create`, `stock.transfer` |
| Stock (generic) | `stock.view`, `stock.view_balance`, `stock.adjust`, `stock.receive`, `stock.return`, `stock.override_negative` |
| Locations | `stock.location.manage` (canonical) + `stock_location.manage` (legacy alias) |
| Supplier | `supplier.manage`, `supplier.ledger.view`, `supplier.payment.create`, `supplier.return.create` |
| Pharmacy stock | `pharmacy.stock.manage`, `pharmacy.dispensing.create`, `pharmacy.drugs.manage` |

**Issue**: Two permissions for the same action (`stock_location.manage` vs `stock.location.manage`)
is a maintenance hazard — a role granted one will not get the other.

---

## 8. Test coverage

Only 3 tests touch this domain, and they only assert that pages render:

- `tests/Feature/ReportTest.php::test_stock_valuation_report_loads`
- `tests/Feature/ReportTest.php::test_expired_stock_report_loads`
- `tests/Feature/PharmacyWorkflowTest.php::test_drug_stock_index_loads`

No test verifies that **after** a PO receipt, the balance actually moves. No test
exercises reversal, transfer, or adjustment.

---

## 9. The user's two questions answered concretely

### Q1 — "When you do PO and it's received, nothing is registered under balances."

**Code analysis**: `ProcurementService::receiveItems()` does write to `stock_movements` and
calls `StockBalanceService::increase()` — but **only for drugs** (table `stock_balances`).

**What the UI shows you**:
- **Admin → Store → Stock → Balances** reads `stock_balances` (drugs). After receiving a
  PO of a drug, the row **does** update here.
- **Admin → Product Stock → Balances** reads `product_stock_balances`. POs do **not** write
  to this table. If you receive a PO and look here, you will see zero — which matches the
  report.

**Compounding problem**: legacy `drug_stock` (per-batch) is also updated, so the same
quantity can appear inflated when a report sums across both `stock_balances` and
`drug_stock`.

**Verification SQL** (run in your DB to confirm the diagnosis on a single recent PO):
```sql
SELECT po.po_number, poi.drug_id, poi.quantity_received,
       (SELECT SUM(ds.quantity)  FROM drug_stock      ds WHERE ds.drug_id = poi.drug_id) AS legacy_qty,
       (SELECT SUM(sb.quantity_on_hand) FROM stock_balances sb WHERE sb.drug_id = poi.drug_id) AS ledger_qty,
       (SELECT COUNT(*) FROM stock_movements sm
         WHERE sm.source_type='App\\Models\\PurchaseOrderItem' AND sm.source_id = poi.id) AS movements
FROM purchase_orders po
JOIN purchase_order_items poi ON poi.purchase_order_id = po.id
WHERE po.status IN ('received','partially_received')
ORDER BY po.id DESC LIMIT 20;
```
- If `movements = 0` for received items → `receiveItems()` is failing silently (check `storage/logs/laravel.log`).
- If `movements > 0` but `ledger_qty = 0` → `StockBalanceService` failed to upsert; run `php artisan tinker` and call `app(\App\Services\StockBalanceService::class)->rebuildAllBalances();`.
- If both match but the **Product Stock Balances** page is empty → you are looking at the
  wrong page (this is the most common cause).

### Q2 — "Stock has a lot of inconsistencies."

Five root causes, in priority order:

1. **Dual ledgers, no bridge.** A purchase of "Surgical Gloves" might be entered today as a
   Drug, tomorrow as a Product. Two ledgers, two balance tables, no reconciliation.
2. **No reversal on invoice / dispense void.** Quantity decremented at dispense remains
   decremented if the visit is later cancelled. Manual `stock.adjust` is required and is
   often forgotten.
3. **Legacy `drug_stock` per-batch table is mutated in parallel** to the ledger by
   `ProcurementService::receiveItems`, `PharmacyService::dispenseItem`, and
   `StockTransferService::complete`. Any one of these three writers can fail mid-way without
   the other two rolling back consistently (they each open their own transaction).
4. **`stock_transfers.from_location` / `to_location` are TEXT** (location names), not FK to
   `stock_locations.id`. Renaming a location silently breaks historical transfer rows.
5. **No partial-receipt audit (no GRN entity).** `quantity_received` is updated on the PO
   line in place; if Receipt A and Receipt B both land partially, the second overwrites the
   first's notes/batch/expiry fields.

Secondary issues (medium severity):
- `StockBalanceService::adjust()` allows direct balance writes without a corresponding
  movement (used internally for rebuilds; can be misused).
- `Drug::opening_stock` (column) **and** `StockMovement::OPENING_STOCK` (ledger row) both
  exist — two sources of truth.
- `ProductStockMovement` lacks `unit_cost` on the receive form (cost basis lost).
- No pessimistic lock in `PharmacyService::dispenseItem` (concurrent dispense could
  over-issue if two workers click simultaneously).

---

## 10. Gap analysis (numbered for tracking)

Severity legend: **🔴 Critical** | **🟠 High** | **🟡 Medium** | **🟢 Polish**

| ID | Gap | Severity | Where |
|---|---|---|---|
| **GP-1** | POs cannot reference Products — only Drugs / Investigation items. | 🔴 | `purchase_order_items` schema; `ProcurementService::receiveItems` |
| **GP-2** | No reversal of stock movements when an invoice line / dispense is voided. | 🔴 | `BillingService`, `PharmacyService` |
| **GP-3** | Legacy `drug_stock` table is updated alongside the ledger in three places — drift. | 🔴 | `ProcurementService`, `PharmacyService`, `StockTransferService` |
| **GP-4** | `stock_transfers.from_location` / `to_location` are strings, not FK. | 🟠 | `stock_transfers` migration |
| **GP-5** | No GRN entity — receipts overwrite `quantity_received` on the PO line. | 🟠 | Schema |
| **GP-6** | No Requisition entity — wards request stock ad-hoc. | 🟠 | Missing |
| **GP-7** | `ProductStockMovement` has no `unit_cost` capture on receive (no COGS basis for products). | 🟠 | `ProductStockController::receive`, `product_stock_movements` |
| **GP-8** | Duplicate permission strings `stock.location.manage` and `stock_location.manage`. | 🟡 | `RoleSeeder`, route middleware |
| **GP-9** | `Drug::opening_stock` column duplicates `OPENING_STOCK` ledger row. | 🟡 | `Drug` model + migration |
| **GP-10** | `StockBalanceService::adjust()` allows bare balance writes (no movement). | 🟡 | `StockBalanceService` |
| **GP-11** | No `lockForUpdate` in `PharmacyService::dispenseItem` against the source batch row. | 🟡 | `PharmacyService` |
| **GP-12** | No automatic expired-stock job (manual EXPIRED movements only). | 🟡 | Console/Kernel |
| **GP-13** | No low-stock notifications (data exists, no channel). | 🟢 | Notifications |
| **GP-14** | Test coverage = 3 page-load tests; no workflow assertions. | 🟠 | `tests/Feature` |
| **GP-15** | Stock-take / physical count workflow missing (no `stock_counts` entity). | 🟠 | Missing |
| **GP-16** | Supplier returns recorded only as ledger entries; no `purchase_returns` doc. | 🟡 | Missing |
| **GP-17** | No COGS report; `unit_cost` captured but never summarised. | 🟡 | Reports |
| **GP-18** | No stock valuation method choice (FIFO/Weighted Average); FEFO is used for dispense only. | 🟡 | Reports + accounting |
| **GP-19** | `StockTransferService::complete` does not wrap its drug_stock mutations in the same `DB::transaction` as the movements (verify in code). | 🟠 | `StockTransferService` |
| **GP-20** | Receive form for products has no supplier picker — provenance lost. | 🟠 | `ProductStockController::receive` view |

---

## 11. Recommended roadmap (sequenced)

### Tier 1 — make the existing system honest (do first)

1. **GP-1 + GP-7 + GP-20 (unify procurement around Products)**
   - Migration: add `product_id` (nullable) to `purchase_order_items`; keep `drug_id` /
     `investigation_item_id` for backward-compat; add CHECK constraint "exactly one of the
     three is set."
   - Extend `ProcurementService::receiveItems` with a `product` branch that calls
     `ProductStockMovementService::createMovement(PURCHASE_RECEIVED, …)` plus a
     `SupplierLedgerEntry`.
   - Add supplier picker and unit-cost field to the Product Stock receive form.
2. **GP-2 (reversal on void)**
   - New method `BillingService::reverseInvoiceItem(InvoiceItem $i)` that emits
     `RETURN_IN` (drug) or `ADJUSTMENT_IN` (product) for the same quantity, batch, location.
   - Call it from invoice/visit cancellation paths.
3. **GP-5 (introduce GRN entity)**
   - `goods_received_notes` table: id, grn_number, purchase_order_id, received_date,
     received_by, supplier_delivery_no, notes.
   - `grn_items`: id, grn_id, purchase_order_item_id, quantity, unit_cost, batch_no,
     expiry_date.
   - `ProcurementService::receiveItems` becomes `ProcurementService::recordGrn(po, payload)`
     which writes the GRN, then the movements. Multiple GRNs can attach to one PO; sum of
     `grn_items.quantity` = `purchase_order_items.quantity_received`.
4. **GP-3 (retire legacy `drug_stock` writers)**
   - Stop writing to `drug_stock` from `receiveItems`, `dispenseItem`, transfer complete.
   - Replace reads with `stock_movements` joined to `stock_balances`, batch FEFO logic
     queried from `stock_movements WHERE remaining_quantity > 0`.
   - Drop the table in a later migration, after a downtime window.

### Tier 2 — structural cleanup

5. **GP-4** Migrate `stock_transfers` to use `from_location_id` / `to_location_id` FK.
6. **GP-6** Add `requisitions` + `requisition_items` (ward → store internal request) feeding
   into transfers.
7. **GP-19** Audit `StockTransferService::complete` transaction scope; nest properly.
8. **GP-11** `lockForUpdate()` on `DrugStock`/`StockBalance` rows during dispense.
9. **GP-8** Pick one permission string; alias the other; update `RoleSeeder` and middleware.
10. **GP-9** Deprecate `Drug::opening_stock`; backfill an `OPENING_STOCK` movement and drop the column.

### Tier 3 — reporting & polish

11. **GP-15** Stock-take (physical count) entity + variance posting.
12. **GP-16** Supplier-return document (debit note).
13. **GP-12, GP-13** Console jobs for expiry detection + low-stock notifications.
14. **GP-14** Workflow tests:
    - PO create → submit → approve → receive → assert `stock_balances` + ledger row.
    - Dispense → assert decrement; void invoice → assert reversal.
    - Transfer → assert paired movements + both balances.
15. **GP-17, GP-18** COGS report + valuation method config (FIFO / weighted avg).
16. **GP-10** Lock `StockBalanceService::adjust()` behind a flag and emit a synthetic
    `RECONCILIATION` movement so the ledger is always self-explanatory.

---

## 12. Acceptance criteria for "balances always match"

When Tier 1 is complete, the following invariants must hold (assert in tests):

1. For every drug at every location: `stock_balances.quantity_on_hand = SUM(stock_movements signed)`.
2. For every product at every location: `product_stock_balances.quantity_on_hand = SUM(product_stock_movements signed)`.
3. For every received PO line: `SUM(grn_items.quantity) = purchase_order_items.quantity_received`, and a movement exists with `source_type=GrnItem`, `source_id=grn_items.id`.
4. For every voided invoice item: an opposite-signed movement exists with `source_type=InvoiceItem`, `source_id=invoice_items.id`, `reason='reversal'`.
5. `drug_stock` is read-only (or dropped) after Tier 1 step 4.

A nightly console command should fail loudly when any invariant is violated.

---

## 13. File reference index

| Concern | Primary file |
|---|---|
| PO receipt logic | [app/Services/ProcurementService.php](app/Services/ProcurementService.php) |
| Drug ledger writer | [app/Services/StockMovementService.php](app/Services/StockMovementService.php) |
| Drug balance cache | [app/Services/StockBalanceService.php](app/Services/StockBalanceService.php) |
| Product ledger writer | [app/Services/ProductStockMovementService.php](app/Services/ProductStockMovementService.php) |
| Product high-level | [app/Services/ProductStockService.php](app/Services/ProductStockService.php) |
| Dispense | [app/Services/PharmacyService.php](app/Services/PharmacyService.php) |
| Billing (no stock writes) | [app/Services/BillingService.php](app/Services/BillingService.php) |
| Supplier A/P | [app/Services/SupplierLedgerService.php](app/Services/SupplierLedgerService.php) |
| PO controller | [app/Http/Controllers/Admin/PurchaseOrderController.php](app/Http/Controllers/Admin/PurchaseOrderController.php) |
| Product Stock controller | [app/Http/Controllers/Admin/ProductStockController.php](app/Http/Controllers/Admin/ProductStockController.php) |
| Routes | [routes/web.php](routes/web.php) |
| Permissions | [database/seeders/RoleSeeder.php](database/seeders/RoleSeeder.php) |
| User manual (existing prose) | [UHMS_Updated_User_Manual.md](UHMS_Updated_User_Manual.md) §§17–18 |

---

*End of document. Companion to PRODUCTS_AND_SERVICES_ANALYSIS.md. Submit issues against the
GP-* IDs above so progress can be tracked.*
