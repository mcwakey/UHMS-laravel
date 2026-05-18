<?php

namespace App\Console\Commands;

use App\Services\ProductStockMovementService;
use App\Services\StockBalanceService;
use Illuminate\Console\Command;

class RebuildStockBalances extends Command
{
    protected $signature = 'stock:rebuild-balances
                            {--drug= : Optional drug_id to scope the drug-ledger rebuild}
                            {--product= : Optional product_id to scope the product-ledger rebuild}
                            {--location= : Optional stock_location_id to scope both rebuilds}
                            {--skip-drugs : Skip the legacy drug-ledger rebuild}
                            {--skip-products : Skip the unified product-ledger rebuild}';

    protected $description = 'Recalculate stock_balances and product_stock_balances from their respective movement ledgers.';

    public function handle(StockBalanceService $drugService, ProductStockMovementService $productService): int
    {
        $drugId     = $this->option('drug')     ? (int) $this->option('drug')     : null;
        $productId  = $this->option('product')  ? (int) $this->option('product')  : null;
        $locationId = $this->option('location') ? (int) $this->option('location') : null;

        if (! $this->option('skip-drugs')) {
            $this->info('Rebuilding drug stock balances from movements ledger...');
            $drugCount = $drugService->rebuildAllBalances($drugId, $locationId);
            $this->info(sprintf('  Rebuilt %d drug+location balance row(s).', $drugCount));
        }

        if (! $this->option('skip-products')) {
            $this->info('Rebuilding product stock balances from product movements ledger...');
            $productCount = $productService->rebuildAllBalances($productId, $locationId);
            $this->info(sprintf('  Rebuilt %d product+location balance row(s).', $productCount));
        }

        return self::SUCCESS;
    }
}
