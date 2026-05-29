<?php

namespace App\Console\Commands;

use App\Enums\NotificationModule;
use App\Enums\NotificationPriority;
use App\Models\Claim;
use App\Services\NotificationService;
use Illuminate\Console\Command;

class ClaimsCheckStaleCommand extends Command
{
    protected $signature = 'claims:check-stale {--days=7 : Days a claim may sit in submitted before alerting}';
    protected $description = 'Notify claims managers when a claim has been in submitted state too long.';

    public function handle(NotificationService $notifier): int
    {
        $days = (int) $this->option('days');
        $cutoff = now()->subDays($days);

        $claims = Claim::query()
            ->where(function ($q) {
                $q->where('status', 'submitted')
                  ->orWhere('status', 'SUBMITTED');
            })
            ->where(function ($q) use ($cutoff) {
                $q->where('submitted_at', '<', $cutoff)
                  ->orWhereNull('submitted_at');
            })
            ->limit(500)
            ->get();

        $count = 0;
        foreach ($claims as $claim) {
            $payload = [
                'module' => NotificationModule::CLAIMS,
                'priority' => NotificationPriority::HIGH,
                'title' => 'Claim stale',
                'message' => sprintf('Claim %s has been in submitted state > %d days', $claim->claim_number, $days),
                'source_type' => 'claim',
                'source_id' => $claim->id,
                'url' => url("/admin/claims/{$claim->id}"),
            ];

            $count += $notifier->notifyRole(['Claims Manager', 'Insurance Officer'], $payload, 1440);
        }

        $this->info("Stale claim notifications dispatched: {$count}");
        return self::SUCCESS;
    }
}
