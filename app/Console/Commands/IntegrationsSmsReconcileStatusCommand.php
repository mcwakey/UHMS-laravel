<?php

namespace App\Console\Commands;

use App\Services\Integrations\Sms\SmsStatusReconciliationService;
use Illuminate\Console\Command;

class IntegrationsSmsReconcileStatusCommand extends Command
{
    protected $signature = 'integrations:sms-reconcile-status
        {--provider= : Limit to an SMS provider code}
        {--message-id= : Limit to one SMS message id}
        {--recipient-id= : Limit to one recipient id}
        {--from= : Created from date (Y-m-d)}
        {--to= : Created to date (Y-m-d)}
        {--limit=200 : Max recipients to check}
        {--dry-run : Query but do not persist updates}';

    protected $description = 'Reconcile SMS delivery status with providers for sent recipients lacking a final report.';

    public function handle(SmsStatusReconciliationService $service): int
    {
        $summary = $service->reconcile([
            'provider_code' => $this->option('provider'),
            'message_id' => $this->option('message-id'),
            'recipient_id' => $this->option('recipient-id'),
            'from' => $this->option('from'),
            'to' => $this->option('to'),
            'limit' => (int) $this->option('limit'),
        ], (bool) $this->option('dry-run'));

        $this->info('SMS delivery status reconciliation complete' . ($this->option('dry-run') ? ' (dry-run)' : ''));
        foreach ($summary as $key => $value) {
            $this->line(sprintf('  %-13s %d', $key, $value));
        }

        return self::SUCCESS;
    }
}
