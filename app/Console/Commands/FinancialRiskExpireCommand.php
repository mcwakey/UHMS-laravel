<?php

namespace App\Console\Commands;

use App\Services\Billing\PatientFinancialRiskService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Transitions due patient financial-risk profiles to EXPIRED (Payment Timing
 * Policy Phase 5).
 *
 * Idempotent: already cleared/expired profiles are untouched, each transition is
 * transactional, and history/activity are appended exactly once. It changes NO
 * payment gate, visit or invoice.
 */
class FinancialRiskExpireCommand extends Command
{
    protected $signature = 'billing:financial-risk-expire {--json : Emit machine-readable JSON}';

    protected $description = 'Expire due patient financial-risk profiles (idempotent; no payment behaviour changes).';

    public function handle(PatientFinancialRiskService $service): int
    {
        try {
            $count = $service->expireDueProfiles();
        } catch (Throwable $e) {
            $this->error('Financial-risk expiry failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if ($this->option('json')) {
            $this->line((string) json_encode(['expired' => $count], JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->info("Financial-risk profiles expired: {$count}.");

        return self::SUCCESS;
    }
}
