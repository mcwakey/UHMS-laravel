<?php

namespace App\Console\Commands;

use App\Services\ProductStockMovementService;
use Illuminate\Console\Command;

class RebuildStockBalances extends Command
{
    protected $signature = 'stock:rebuild-balances
                            {--product= : Optional product_id to scope the rebuild}
                            {--location= : Optional stock_location_id to scope the rebuild}';

    protected $description = 'Recalculate stock_balances from the product movement ledger.';

    public function handle(ProductStockMovementService $productService): int
    {
        $productId  = $this->option('product')  ? (int) $this->option('product')  : null;
        $locationId = $this->option('location') ? (int) $this->option('location') : null;

        $this->info('Rebuilding product stock balances from the movement ledger...');
        $productCount = $productService->rebuildAllBalances($productId, $locationId);
        $this->info(sprintf('  Rebuilt %d product+location balance row(s).', $productCount));

        return self::SUCCESS;
    }
}
