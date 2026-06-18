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
 * Delivers all still-sendable recipients of an SMS message through the active
 * provider. SMS failures are retained on the message/recipients, never thrown.
 */
class SendSmsMessageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(public int $messageId) {}

    public function handle(SmsGatewayService $gateway): void
    {
        $message = SmsMessage::with('recipients')->find($this->messageId);
        if ($message) {
            $gateway->deliverNow($message);
        }
    }
}
