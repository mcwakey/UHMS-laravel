<?php

namespace App\Console\Commands;

use App\Services\BloodExpiryService;
use Illuminate\Console\Command;

class ExpireBloodUnitsCommand extends Command
{
    protected $signature = 'blood-bank:expire-units';

    protected $description = 'Mark expired, unissued blood units as EXPIRED.';

    public function handle(BloodExpiryService $expiry): int
    {
        $count = $expiry->expireDueUnits();
        $this->info("Expired {$count} blood unit(s).");

        return self::SUCCESS;
    }
}
