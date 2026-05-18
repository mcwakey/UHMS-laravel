<?php

namespace App\Console\Commands;

use App\Models\GoodsReceivedNoteItem;
use App\Models\ProductStockBalance;
use App\Models\ProductStockMovement;
use App\Models\StockBalance;
use App\Models\StockLocation;
use App\Services\ProductStockMovementService;
use App\Services\StockBalanceService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * stock:audit — sanity-check the unified inventory model.
 *
 * Checks per docs/UNIFIED_INVENTORY_IMPLEMENTATION_PLAN.md §3:
 *   (a) Drug + product cached balances match the SUM of their movements.
 *   (b) purchase_order_items.quantity_received matches SUM(GRN item quantities).
 *   (c) GRN items have at least one ledger movement linked (drug or product).
 *   (d) At most one Main Store stock_location exists.
 *
 * Returns a non-zero exit code when any inconsistency is detected
 * (unless --fix made everything consistent).
 */
class StockAuditCommand extends Command
{
    protected $signature = 'stock:audit
                            {--fix : Auto-fix safe inconsistencies (rebuild cached balances)}';

    protected $description = 'Audit the unified inventory ledger and report inconsistencies.';

    public function handle(StockBalanceService $drugSvc, ProductStockMovementService $productSvc): int
    {
        $issues = 0;

        // ----- (d) Main Store uniqueness ----------------------------------------------
        $mainCount = StockLocation::query()->where('is_main', true)->count();
        if ($mainCount === 0) {
            $this->error('[FAIL] No stock_locations row has is_main = 1.');
            $issues++;
        } elseif ($mainCount > 1) {
            $this->error(sprintf('[FAIL] %d stock_locations rows have is_main = 1 (expected exactly 1).', $mainCount));
            $issues++;
        } else {
            $this->info('[OK] Exactly one Main Store stock location.');
        }

        // ----- (a) Drug balance vs movements -------------------------------------------
        $drugMismatches = DB::table('stock_movements as sm')
            ->selectRaw('sm.drug_id, sm.stock_location_id,
                SUM(CASE WHEN sm.direction = "in"  THEN sm.quantity ELSE 0 END) as total_in,
                SUM(CASE WHEN sm.direction = "out" THEN sm.quantity ELSE 0 END) as total_out')
            ->groupBy('sm.drug_id', 'sm.stock_location_id')
            ->get()
            ->filter(function ($row) {
                $expected = (float) $row->total_in - (float) $row->total_out;
                $cached = (float) (StockBalance::query()
                    ->where('drug_id', $row->drug_id)
                    ->where('stock_location_id', $row->stock_location_id)
                    ->value('quantity_on_hand') ?? 0);
                return abs($expected - $cached) > 0.0001;
            });

        if ($drugMismatches->isNotEmpty()) {
            $this->error(sprintf('[FAIL] %d drug+location cached balances do not match the ledger.', $drugMismatches->count()));
            $issues++;
            if ($this->option('fix')) {
                foreach ($drugMismatches as $row) {
                    $drugSvc->rebuildBalance((int) $row->drug_id, (int) $row->stock_location_id);
                }
                $this->warn('  --fix: rebuilt mismatched drug balances.');
            }
        } else {
            $this->info('[OK] Drug stock balances match ledger.');
        }

        // ----- (a) Product balance vs movements ----------------------------------------
        $productMismatches = ProductStockMovement::query()
            ->selectRaw('product_id, stock_location_id,
                SUM(CASE WHEN direction = "in"  THEN quantity ELSE 0 END) as total_in,
                SUM(CASE WHEN direction = "out" THEN quantity ELSE 0 END) as total_out')
            ->groupBy('product_id', 'stock_location_id')
            ->get()
            ->filter(function ($row) {
                $expected = (float) $row->total_in - (float) $row->total_out;
                $cached = (float) (ProductStockBalance::query()
                    ->where('product_id', $row->product_id)
                    ->where('stock_location_id', $row->stock_location_id)
                    ->value('quantity_on_hand') ?? 0);
                return abs($expected - $cached) > 0.0001;
            });

        if ($productMismatches->isNotEmpty()) {
            $this->error(sprintf('[FAIL] %d product+location cached balances do not match the ledger.', $productMismatches->count()));
            $issues++;
            if ($this->option('fix')) {
                foreach ($productMismatches as $row) {
                    $productSvc->rebuildBalance((int) $row->product_id, (int) $row->stock_location_id);
                }
                $this->warn('  --fix: rebuilt mismatched product balances.');
            }
        } else {
            $this->info('[OK] Product stock balances match ledger.');
        }

        // ----- (b) PO item quantity_received vs SUM(GRN item quantities) ---------------
        // Only checks PO items that have GRN coverage; PO items received before GRN
        // wiring (pre Phase 1) are exempt.
        $poDiscrepancies = DB::table('goods_received_note_items as gri')
            ->join('purchase_order_items as poi', 'poi.id', '=', 'gri.purchase_order_item_id')
            ->selectRaw('poi.id as po_item_id, poi.quantity_received as po_qty, SUM(gri.quantity_received) as grn_qty')
            ->whereNotNull('gri.purchase_order_item_id')
            ->groupBy('poi.id', 'poi.quantity_received')
            ->get()
            ->filter(fn ($r) => abs((float) $r->po_qty - (float) $r->grn_qty) > 0.0001);

        if ($poDiscrepancies->isNotEmpty()) {
            $this->error(sprintf('[FAIL] %d PO items have quantity_received != SUM(GRN item quantities).', $poDiscrepancies->count()));
            $issues++;
            foreach ($poDiscrepancies->take(5) as $r) {
                $this->line(sprintf('       po_item=%d po_qty=%s grn_qty=%s', $r->po_item_id, $r->po_qty, $r->grn_qty));
            }
        } else {
            $this->info('[OK] PO items reconciled against GRN items.');
        }

        // ----- (c) GRN items with no ledger movement linked ----------------------------
        $orphanGrn = GoodsReceivedNoteItem::query()
            ->whereNull('product_stock_movement_id')
            ->whereNull('stock_movement_id')
            ->count();

        if ($orphanGrn > 0) {
            $this->error(sprintf('[FAIL] %d GRN items have no linked ledger movement.', $orphanGrn));
            $issues++;
        } else {
            $this->info('[OK] Every GRN item is linked to a ledger movement.');
        }

        // ----- (e) Drugs without product link (blocks unified ledger) ------------------
        $unlinkedDrugs = \App\Models\Drug::query()->whereNull('product_id')->count();
        if ($unlinkedDrugs > 0) {
            $this->warn(sprintf('[WARN] %d drugs have no product_id. Run `php artisan inventory:link-drugs-to-products`.', $unlinkedDrugs));
            // Treated as warning, not failure — Phase 2 transitional state.
        } else {
            $this->info('[OK] Every drug is linked to a product.');
        }

        if ($issues === 0) {
            $this->info('Stock audit: all checks passed.');
            return self::SUCCESS;
        }

        $this->error(sprintf('Stock audit: %d check(s) failed.', $issues));
        return self::FAILURE;
    }
}
