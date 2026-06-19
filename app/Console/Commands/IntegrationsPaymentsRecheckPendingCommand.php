<?php

namespace App\Console\Commands;

use App\Enums\LogModule;
use App\Models\PaymentProviderTransaction;
use App\Services\ActivityLogService;
use App\Services\Integrations\Payment\PaymentVerificationService;
use Illuminate\Console\Command;

class IntegrationsPaymentsRecheckPendingCommand extends Command
{
    protected $signature = 'integrations:payments-recheck-pending
        {--provider= : Limit to a payment provider code}
        {--from= : Created from date (Y-m-d)}
        {--to= : Created to date (Y-m-d)}
        {--limit=100 : Max transactions to recheck}
        {--dry-run : Verify but do not persist}
        {--mark-expired : Mark still-pending stale transactions as expired}';

    protected $description = 'Recheck pending/stale provider transactions and verify them safely (creates a UHMS payment only on verified, matched success).';

    public function handle(PaymentVerificationService $verification, ActivityLogService $logger): int
    {
        $pending = [
            PaymentProviderTransaction::STATUS_INITIATED,
            PaymentProviderTransaction::STATUS_PENDING,
            PaymentProviderTransaction::STATUS_REQUIRES_CUSTOMER_ACTION,
        ];
        $staleCutoff = now()->subMinutes((int) config('integrations.stale_pending_minutes', 30));

        $query = PaymentProviderTransaction::query()
            ->whereIn('status', $pending)
            ->when($this->option('provider'), fn ($q) => $q->where('provider_code', $this->option('provider')))
            ->when($this->option('from'), fn ($q) => $q->whereDate('created_at', '>=', $this->option('from')))
            ->when($this->option('to'), fn ($q) => $q->whereDate('created_at', '<=', $this->option('to')))
            ->latest()
            ->limit((int) $this->option('limit'));

        $dryRun = (bool) $this->option('dry-run');
        $summary = ['checked' => 0, 'verified' => 0, 'still_pending' => 0, 'expired' => 0, 'errors' => 0];

        foreach ($query->get() as $txn) {
            $summary['checked']++;
            try {
                if (! $dryRun) {
                    $txn = $verification->verify($txn);
                }

                if ($txn->isSuccessful()) {
                    $summary['verified']++;
                    continue;
                }

                if ($this->option('mark-expired') && $txn->created_at && $txn->created_at->lt($staleCutoff)) {
                    if (! $dryRun) {
                        $txn->update(['status' => PaymentProviderTransaction::STATUS_EXPIRED, 'expired_at' => now()]);
                        $logger->log(LogModule::INTEGRATIONS, 'PAYMENT_TRANSACTION_MARKED_EXPIRED', [
                            'source_type' => 'payment_provider_transaction', 'source_id' => $txn->id,
                        ], $txn, 'Stale provider transaction marked expired');
                    }
                    $summary['expired']++;
                } else {
                    $summary['still_pending']++;
                }
            } catch (\Throwable $e) {
                $summary['errors']++;
            }
        }

        $this->info('Pending payment recheck complete' . ($dryRun ? ' (dry-run)' : ''));
        foreach ($summary as $key => $value) {
            $this->line(sprintf('  %-13s %d', $key, $value));
        }

        app(\App\Services\Integrations\SchedulerStatusService::class)
            ->recordRun('integrations:payments-recheck-pending', 'success', $summary);

        return self::SUCCESS;
    }
}
