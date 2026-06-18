<?php

namespace App\Jobs\Integrations;

use App\Models\SmsMessage;
use App\Services\Integrations\Sms\SmsGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Recipient-safe retry of a failed / partially-sent SMS message.
 */
class RetryFailedSmsMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;

    public function __construct(public int $messageId) {}

    public function handle(SmsGatewayService $gateway): void
    {
        $message = SmsMessage::with('recipients')->find($this->messageId);
        if ($message) {
            $gateway->retry($message);
        }
    }
}
