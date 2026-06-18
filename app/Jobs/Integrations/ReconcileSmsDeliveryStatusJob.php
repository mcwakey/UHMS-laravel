<?php

namespace App\Jobs\Integrations;

use App\Services\Integrations\Sms\SmsStatusReconciliationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Queries the provider for the final delivery status of sent recipients that
 * never received a delivery report. Idempotent; continues on individual failure.
 */
class ReconcileSmsDeliveryStatusJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    /** @param array $filters provider_code?, message_id?, recipient_id?, from?, to?, limit? */
    public function __construct(public array $filters = []) {}

    public function handle(SmsStatusReconciliationService $service): void
    {
        $service->reconcile($this->filters);
    }
}
