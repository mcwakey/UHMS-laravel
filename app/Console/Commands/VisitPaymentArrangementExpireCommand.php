<?php

namespace App\Console\Commands;

use App\Services\Billing\VisitPaymentArrangementService;
use Illuminate\Console\Command;
use Throwable;

/**
 * Expires approved per-visit payment arrangements past their expiry (Payment
 * Timing Policy Phase 7). Idempotent, transactional, and touches no terminal
 * records. Changes no payment gate, invoice or visit. Dry-run by default.
 */
class VisitPaymentArrangementExpireCommand extends Command
{
    protected $signature = 'billing:visit-payment-arrangement-expire
        {--dry-run : Report only (default)}
        {--commit : Perform writes}
        {--json : Emit machine-readable JSON}';

    protected $description = 'Expire due approved visit payment arrangements (idempotent; no payment behaviour changes).';

    public function handle(VisitPaymentArrangementService $service): int
    {
        try {
            $commit = (bool) $this->option('commit');
            if (! $commit) {
                $due = \App\Models\VisitPaymentArrangement::query()->expired()->count();
                $this->output($commit, $due, $due);

                return self::SUCCESS;
            }

            $expired = $service->expireDue();
            $this->output($commit, $expired, $expired);

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Arrangement expiry failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    private function output(bool $commit, int $due, int $expired): void
    {
        if ($this->option('json')) {
            $this->line((string) json_encode(['mode' => $commit ? 'commit' : 'dry-run', 'due' => $due, 'expired' => $commit ? $expired : 0], JSON_PRETTY_PRINT));

            return;
        }
        $this->info(($commit ? 'Expired' : 'Due (dry-run)').": {$due}.");
        if (! $commit) {
            $this->line('Dry-run only; re-run with --commit to expire.');
        }
    }
}
