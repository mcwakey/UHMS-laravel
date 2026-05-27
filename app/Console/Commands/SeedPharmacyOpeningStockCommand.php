<?php

namespace App\Console\Commands;

use App\Enums\StockMovementType;
use App\Models\DrugStock;
use App\Models\ProductStockBalance;
use App\Models\StockLocation;
use App\Services\ProductStockMovementService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * inventory:seed-pharmacy-opening-stock
 *
 * One-shot Phase 3 backfill: for every DrugStock row with positive quantity,
 * post an OPENING_STOCK movement against the matching stock_locations row so
 * the unified product ledger (`product_stock_balances`) reflects the existing
 * pharmacy on-hand quantities. After this command runs, `allow_negative=true`
 * on the dispense path can be flipped to `false`.
 *
 * Idempotent: a product/location pair that already has a positive
 * ProductStockBalance row is skipped (its opening stock has already been
 * seeded — usually by a previous run of this command or by Procurement).
 */
class SeedPharmacyOpeningStockCommand extends Command
{
    protected $signature = 'inventory:seed-pharmacy-opening-stock
                            {--dry-run : Show what would happen without writing}
                            {--location= : Only seed a single legacy location string (e.g. pharmacy)}';

    protected $description = 'Post OPENING_STOCK movements for pre-existing DrugStock rows so product ledger matches on-hand (Phase 3).';

    public function handle(ProductStockMovementService $productMovements): int
    {
        $this->warn('This command is no longer functional — the legacy drug_stock table has been removed.');
        $this->info('Opening stock is now managed via stock_movements (OPENING_STOCK type) on the canonical stock_balances ledger.');
        return self::SUCCESS;
    }

    public function _legacyHandle(ProductStockMovementService $productMovements): int
    {
        $dry = (bool) $this->option('dry-run');
        $onlyLocation = $this->option('location') ? (string) $this->option('location') : null;

        // Map legacy DrugStock.location strings → stock_locations row.
        $locationCache = [];
        $resolveLocation = function (string $name) use (&$locationCache): ?StockLocation {
            if (array_key_exists($name, $locationCache)) {
                return $locationCache[$name];
            }
            return $locationCache[$name] = StockLocation::query()
                ->where('name', $name)
                ->orWhere('type', $name)
                ->orderBy('id')
                ->first();
        };

        $seeded   = 0;
        $skipped  = 0;
        $failed   = 0;
        $totalQty = 0;

        $query = DrugStock::query()
            ->with('drug')
            ->where('quantity', '>', 0);

        if ($onlyLocation) {
            $query->where('location', $onlyLocation);
        }

        $rows = $query->get();

        if ($rows->isEmpty()) {
            $this->info('No DrugStock rows with positive quantity were found.');
            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d DrugStock rows with positive quantity.', $rows->count()));
        if ($dry) {
            $this->warn('Dry-run: no movements will be posted.');
        }

        foreach ($rows as $stock) {
            $drug = $stock->drug;
            if (! $drug) {
                $this->warn(sprintf('[SKIP] DrugStock #%d has no drug (drug_id=%d).', $stock->id, $stock->drug_id));
                $skipped++;
                continue;
            }
            if (! $drug->product_id) {
                $this->warn(sprintf('[SKIP] Drug #%d (%s) is not linked to a product — run `php artisan inventory:link-drugs-to-products` first.', $drug->id, $drug->name));
                $skipped++;
                continue;
            }

            $location = $resolveLocation((string) $stock->location);
            if (! $location) {
                $this->warn(sprintf('[SKIP] DrugStock #%d location "%s" does not map to a stock_locations row.', $stock->id, $stock->location));
                $skipped++;
                continue;
            }

            // Idempotency guard: if this product/location already has a positive
            // balance row, the opening stock was already seeded.
            $existingBalance = ProductStockBalance::query()
                ->where('product_id', $drug->product_id)
                ->where('stock_location_id', $location->id)
                ->value('quantity_on_hand');

            if ($existingBalance !== null && (float) $existingBalance > 0) {
                $this->line(sprintf(
                    '[SKIP] Drug #%d (%s) at %s already has product balance %s — opening stock not needed.',
                    $drug->id,
                    $drug->name,
                    $location->name,
                    rtrim(rtrim(number_format((float) $existingBalance, 4, '.', ''), '0'), '.')
                ));
                $skipped++;
                continue;
            }

            $totalQty += (int) $stock->quantity;

            if ($dry) {
                $this->line(sprintf(
                    '[DRY] Would seed %d %s of %s at %s (batch=%s).',
                    $stock->quantity,
                    $drug->unit ?? 'unit',
                    $drug->name,
                    $location->name,
                    $stock->batch_number ?? '—'
                ));
                $seeded++;
                continue;
            }

            try {
                DB::transaction(function () use ($productMovements, $stock, $drug, $location) {
                    $productMovements->createMovement([
                        'product_id'        => $drug->product_id,
                        'stock_location_id' => $location->id,
                        'movement_type'     => StockMovementType::OPENING_STOCK,
                        'quantity'          => $stock->quantity,
                        'unit_cost'         => $stock->unit_cost,
                        'batch_no'          => $stock->batch_number,
                        'expiry_date'       => $stock->expiry_date,
                        'source_type'       => DrugStock::class,
                        'source_id'         => $stock->id,
                        'notes'             => 'Opening stock seeded from legacy DrugStock #' . $stock->id,
                    ]);
                });
                $seeded++;
                $this->line(sprintf('[OK] Seeded %d of %s at %s.', $stock->quantity, $drug->name, $location->name));
            } catch (\Throwable $e) {
                $failed++;
                $this->error(sprintf('[FAIL] DrugStock #%d: %s', $stock->id, $e->getMessage()));
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'Done. Seeded: %d, Skipped: %d, Failed: %d, Total qty posted: %d.',
            $seeded,
            $skipped,
            $failed,
            $totalQty
        ));

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
