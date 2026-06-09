# UHMS — Unified Product Inventory: Implementation Plan

> Companion to [STORES_STOCK_PROCUREMENT_ANALYSIS.md](STORES_STOCK_PROCUREMENT_ANALYSIS.md) and
> the directive in `prompt.md` (top-level repo). Date: May 18 2026.

## 0. Where we are

The codebase already has the building blocks for a unified product-based ledger:

| Concern                      | Drug ledger (legacy)       | Product ledger (target)        | Status |
|------------------------------|----------------------------|--------------------------------|--------|
| Movements table              | `stock_movements`          | `product_stock_movements`      | Both exist |
| Balances table               | `stock_balances`           | `product_stock_balances`       | Both exist |
| Service                      | `StockMovementService`     | `ProductStockMovementService`  | Both exist |
| Balance service              | `StockBalanceService`      | (inline in product service)    | Asymmetric |
| Receiving writer             | `ProcurementService`       | `ProcurementService` (since GP-1) | Unified |
| Locations                    | `stock_locations`          | `stock_locations`              | Shared ✔︎ |
| PO line                      | `drug_id` / `inv_item_id`  | `product_id` (since GP-1)      | Co-exist |

The end state per `prompt.md` is: **single ledger keyed by `(product_id, stock_location_id)`**.
Reaching that requires:

- Every `Drug` becomes a `Product` (or is linked 1:1 to a product row).
- `PharmacyService::dispenseItem` writes through `ProductStockMovementService`.
- `StockTransferService` carries product line items and uses `from_location_id` / `to_location_id` FKs.
- The drug ledger tables become read-only history (or get migrated/dropped after rebuild).

That migration is large enough that an unattended one-shot is unsafe.
This plan splits the work into **additive Phase 1** (done now, no behaviour regressions) and
**Phase 2** (requires data migration + service rewrites).

---

## 1. Phase 1 — Foundations (this session)

All additive. Drug ledger still owns dispensing/consumption; product ledger now owns PO receiving
end-to-end with a GRN entity.

| # | Item                                                            | Files / artefacts                                                                                                                  |
|---|-----------------------------------------------------------------|------------------------------------------------------------------------------------------------------------------------------------|
| 1 | `is_main` column on `stock_locations` + flag the existing row   | new migration `2026_05_18_000006_add_is_main_to_stock_locations.php`                                                              |
| 2 | `goods_received_notes` + `goods_received_note_items` tables     | new migration `2026_05_18_000007_create_goods_received_notes.php`                                                                  |
| 3 | `GoodsReceivedNote` + `GoodsReceivedNoteItem` models            | `app/Models/GoodsReceivedNote.php`, `app/Models/GoodsReceivedNoteItem.php`                                                         |
| 4 | `ProcurementService::receiveItems` creates a GRN per receive    | `app/Services/ProcurementService.php`                                                                                              |
| 5 | `StockLocationService` (new) with `getMainStoreLocation` etc.   | `app/Services/StockLocationService.php`                                                                                            |
| 6 | `stock:rebuild-balances` extended to rebuild **product** balances | `app/Console/Commands/RebuildStockBalances.php`, `app/Services/ProductStockMovementService.php` (rebuild helper)                  |
| 7 | `stock:audit` command — checks invariants                       | `app/Console/Commands/StockAuditCommand.php`                                                                                       |
| 8 | Reversal helper on `ProductStockMovementService`                | `app/Services/ProductStockMovementService.php::reverseMovement`                                                                    |

### Phase 1 acceptance

After running migrations + commands:

```
php artisan migrate
php artisan stock:rebuild-balances
php artisan stock:audit
```

invariants verified:

- Exactly one `stock_locations.is_main = 1` row exists.
- For every product/location pair: `quantity_on_hand = Σ(IN) − Σ(OUT)`.
- For every PO marked received: `Σ(quantity_received)` equals `Σ(GRN item quantities)` (going forward — historical PO receives without GRNs are tagged in the audit as "pre-GRN").
- All `PURCHASE_RECEIVED` product-ledger movements have `source_type = PurchaseOrderItem::class`.

---

## 2. Phase 2 — Drug-to-Product unification (deferred)

Each item below is a self-contained PR-sized change. Do **not** ship them silently — each one
ships with a data backfill + a deprecation switch.

### 2.1 Backfill `Product` rows for every `Drug`

- New nullable `drugs.product_id` column.
- Seeder/command `inventory:link-drugs-to-products` creates a `Product` (type=`DRUG`) for every
  active Drug and stores the FK back on the drug.
- After backfill: every `Drug::product` returns a row.

### 2.2 Migrate drug ledger to product ledger

- For every row in `stock_movements`: insert a mirror row in `product_stock_movements` keyed on
  `drugs.product_id`. Run inside a transaction with an audit log.
- Run `stock:rebuild-balances` (Phase-1 command) to produce `product_stock_balances`.
- Stop writing to `stock_movements`/`stock_balances` (constants in `StockMovementService` become
  a thin wrapper around `ProductStockMovementService`).

### 2.3 Rewrite `PharmacyService::dispenseItem`

- Read `drug.product_id`, deduct from Pharmacy stock location via `ProductStockMovementService::createMovement(PHARMACY_DISPENSED)`.
- Block dispensing when Pharmacy balance < quantity (already enforced via `allow_negative=false`).
- Reversal on void: call `ProductStockMovementService::reverseMovement($originalMovement)`.

### 2.4 Rewrite `StockTransferService`

- Columns: `from_location_id`, `to_location_id` (FK to `stock_locations`).
- Validation: must involve Main Store (`stock_locations.is_main = 1` on one side).
- Items: `product_id` + `quantity`.
- Complete: emit `TRANSFER_OUT` from source + `TRANSFER_IN` to dest, both linked to the transfer row.

### 2.5 Consumable consumption (investigation, procedure, ward)

- `ConsumableUsageService::recordUsage(Service $service, Visit $visit, Department $department, array $usages)`
- Resolves `StockLocationService::getDefaultLocationForDepartment($department)`.
- Writes `INVESTIGATION_CONSUMED` / `PROCEDURE_CONSUMED` / `WARD_CONSUMED` via
  `ProductStockMovementService`.

### 2.6 Purge legacy writers

- Add `@deprecated` annotations on `DrugStock`, `InvestigationItemStock`, and the drug-flavoured
  `StockMovementService` paths.
- After two release cycles with zero hits in `storage/logs`, drop the tables in a follow-up migration.

---

## 3. Open risks & decisions

1. **Drug uniqueness vs Product uniqueness.** Drugs carry strength/formulation/brand metadata
   the generic Product schema doesn't. Two options for 2.1: (a) keep `drugs` as a metadata-only
   table that points at a Product, or (b) extend `products` with optional drug-specific columns.
   Recommended: (a) — surgical, no Product table bloat.

2. **Historical drug stock balances.** Pharmacy already has on-hand stock recorded against
   `stock_balances(drug_id, location_id)`. The 2.2 migration must port these as `OPENING_STOCK`
   IN movements so the rebuild produces the same on-hand numbers — otherwise pharmacy zeros out
   on go-live.

3. **Investigation items.** Some labs use `investigation_items` as a separate catalogue. They
   should follow the same path as drugs — a `Product` row per investigation item with type
   `REAGENT`/`CONSUMABLE`.

4. **PO bulk create form** (already drug-only). After Phase 2.1 backfill, the bulk create form
   can switch to a product picker without losing the drug dropdown UX (filter products by
   `type=DRUG`).

---

## 4. Tracking checklist

Phase 1 (this session):

- [x] Plan written
- [x] `is_main` flag + Main Store seeded
- [x] GRN tables + models
- [x] GRN integration in `ProcurementService::receiveItems`
- [x] `StockLocationService`
- [x] `stock:rebuild-balances` covers product ledger
- [x] `stock:audit`
- [x] Reversal helper

Phase 2:

- [x] 2.1 Drug → Product backfill (`inventory:link-drugs-to-products` command + Pharmacy department pivot)
- [x] 2.2 Drug ledger → product ledger migration (`product_id` FK on `stock_movements` and `stock_balances`, auto-populated by `StockMovementService` / `StockBalanceService`; uses raw DDL to sidestep MariaDB 10.1 schema-introspection bug)
- [x] 2.3 Pharmacy dispense dual-writes the product ledger (`PharmacyService::dispenseItem` calls `ProductStockMovementService` with `allow_negative=true`)
- [ ] 2.4 Stock transfer service location-FK refactor (deferred — touches transfer UI)
- [x] 2.5 Investigation/procedure/ward consumable service (already implemented pre-Phase 2 via `ConsumableUsageService` → `ProductStockMovementService`)
- [ ] 2.6 Legacy writer purge (partial — `stock:audit` now warns on unlinked drugs; full DrugStock writer removal deferred until dispense UI reads switch to product ledger)

### Phase 2 — validation results

- `php artisan migrate --force` applied `2026_05_18_000008_add_product_id_to_stock_ledger` cleanly.
- `php artisan inventory:link-drugs-to-products` linked all drugs: 3/3.
- Backfill: 3/3 `stock_movements` and 3/3 `stock_balances` rows now carry `product_id`.
- `php artisan stock:audit` reports all checks OK.

---

## Phase 3 — Convergence (this session)

Phase 3 narrows the gap between the legacy drug ledger and the unified product
ledger by ensuring every stock-affecting write (procurement receive, pharmacy
add-stock, transfers, dispensing) **also** writes the product ledger, and by
seeding opening balances for pre-Phase-2 pharmacy stock. The next phase
(Phase 4 / "deprecation cycle") will purge the legacy writers and switch reads.

- [x] 3.A Opening-balance seed command (`inventory:seed-pharmacy-opening-stock`)
- [x] 3.B `PharmacyService::addStock` posts paired drug + product movements with `PURCHASE_RECEIVED`
- [x] 3.C `StockTransferService::emitTransferMovements` dual-writes `TRANSFER_OUT`/`TRANSFER_IN` to product ledger
- [x] 3.D Product-ledger dispense path tightened to `allow_negative=false` (drug ledger stays `true` for legacy SoT compat)
- [x] 3.E `ProcurementService::receiveItems` drug-path now mirrors the receive into the product ledger (it was already doing so for `item_type=product`)

### Phase 3 — validation results

- `inventory:seed-pharmacy-opening-stock --dry-run` identified 4 DrugStock rows (total qty 400) to seed.
- `inventory:seed-pharmacy-opening-stock` posted 4 `OPENING_STOCK` product-ledger movements (Paracetamol & Quininex at Pharmacy & Main Store, 100 each).
- `product_stock_balances` snapshot: 4 rows, all positive.
- `stock:audit` reports all checks OK.

### Phase 4 — deferred (still to do)

- [ ] StockTransferService location-FK refactor: replace `from_location` / `to_location` string columns (currently cast as the `StockLocation` *enum*) with `from_location_id` / `to_location_id` FKs to the `stock_locations` table; add `product_id` to `stock_transfer_items`; enforce Main↔Department-only flows via `StockLocationService::isMainStore`. Touches the transfer UI.

### Phase 5 — Legacy drug-keyed ledger removal (2026-06-09) ✅

The drug-keyed write/read paths on the shared `stock_movements` / `stock_balances`
tables were the source of "product shows 0 stock" confusion (the cached balance rows
are all `product_id`-keyed with `drug_id = NULL`, so any `WHERE drug_id = ?` query
returned nothing). Removed entirely:

- [x] Deleted `app/Services/StockMovementService.php` (the drug-keyed writer).
- [x] Rewired its callers to the unified product ledger (`ProductStockMovementService`),
      resolving `drug.product_id` first: `StockReturnService`, `StockAdjustmentService`,
      `PharmacyService::storeDrug` opening stock.
- [x] `StockTransferService::emitTransferMovements` drug branch no longer dual-writes a
      drug-keyed row + a product-keyed row (which double-counted); it now writes the
      product ledger only.
- [x] Fixed `StockTransferService::getAvailableStock` — was `WHERE drug_id = ?`
      (always 0 post-migration); now resolves `product_id` and queries by it.
- [x] Removed the dead drug-keyed methods from `StockBalanceService`
      (`getCurrentStock`, `increase`, `decrease`, `adjust`, `rebuildBalance`,
      `rebuildAllBalances`); product-native methods remain.
- [x] `stock:audit` and `stock:rebuild-balances` no longer rebuild a drug ledger.
- Note: the `DrugStock` model + `drug_stock` table are intentionally retained as a
      guarded-empty legacy artifact (`UnifiedInventoryWorkflowTest` asserts the table
      stays empty); they hold no live stock.
- [x] Validation: `UnifiedInventoryWorkflowTest`, `StockAdminLogTest`,
      `PharmacyWorkflowTest` — 26 passed.

---

## 5. Files modified in Phase 1

- `database/migrations/2026_05_18_000006_add_is_main_to_stock_locations.php` (new)
- `database/migrations/2026_05_18_000007_create_goods_received_notes.php` (new)
- `app/Models/GoodsReceivedNote.php` (new)
- `app/Models/GoodsReceivedNoteItem.php` (new)
- `app/Services/StockLocationService.php` (new)
- `app/Services/ProcurementService.php` (modified)
- `app/Services/ProductStockMovementService.php` (modified — `rebuildBalance`, `reverseMovement`)
- `app/Console/Commands/RebuildStockBalances.php` (modified — also rebuild product ledger)
- `app/Console/Commands/StockAuditCommand.php` (new)
