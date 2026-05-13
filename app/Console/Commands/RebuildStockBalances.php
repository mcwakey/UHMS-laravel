<?php

namespace App\Console\Commands;

use App\Services\StockBalanceService;
use Illuminate\Console\Command;

class RebuildStockBalances extends Command
{
    protected $signature = 'stock:rebuild-balances
                            {--drug= : Optional drug_id to scope the rebuild}
                            {--location= : Optional stock_location_id to scope the rebuild}';

    protected $description = 'Recalculate stock_balances rows from the stock_movements ledger.';

    public function handle(StockBalanceService $service): int
    {
        $drugId     = $this->option('drug')     ? (int) $this->option('drug')     : null;
        $locationId = $this->option('location') ? (int) $this->option('location') : null;

        $this->info('Rebuilding stock balances from movements ledger...');

        $count = $service->rebuildAllBalances($drugId, $locationId);

        $this->info(sprintf('Rebuilt %d drug+location balance row(s).', $count));

        return self::SUCCESS;
    }
}
