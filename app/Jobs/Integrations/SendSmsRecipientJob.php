<?php

namespace App\Jobs\Integrations;

use App\Models\SmsMessageRecipient;
use App\Services\Integrations\Sms\SmsGatewayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * Delivers a single SMS recipient (targeted retry). Never re-sends a recipient
 * already marked sent/delivered.
 */
class SendSmsRecipientJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $recipientId) {}

    public function handle(SmsGatewayService $gateway): void
    {
        $recipient = SmsMessageRecipient::with('message')->find($this->recipientId);
        if ($recipient) {
            $gateway->deliverRecipient($recipient);
        }
    }
}
