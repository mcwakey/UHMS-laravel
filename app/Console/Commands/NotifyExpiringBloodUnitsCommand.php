<?php

namespace App\Console\Commands;

use App\Services\BloodExpiryService;
use Illuminate\Console\Command;

class NotifyExpiringBloodUnitsCommand extends Command
{
    protected $signature = 'blood-bank:notify-expiring {--days=7}';

    protected $description = 'List blood units expiring soon. Dashboard notifications can hook into this command later.';

    public function handle(BloodExpiryService $expiry): int
    {
        $days = (int) $this->option('days');
        $units = $expiry->expiringSoon($days);

        if ($units->isEmpty()) {
            $this->info("No blood units expiring within {$days} day(s).");

            return self::SUCCESS;
        }

        $this->warn($units->count()." blood unit(s) expiring within {$days} day(s):");
        foreach ($units as $unit) {
            $this->line("{$unit->unit_number} {$unit->blood_group} {$unit->component_type} expires {$unit->expiry_date?->format('Y-m-d')}");
        }

        return self::SUCCESS;
    }
}
