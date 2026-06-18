<?php

namespace App\Listeners\Integrations;

use App\Events\PaymentRecorded;
use App\Services\Integrations\Sms\SmsEventSettingsService;
use App\Services\Integrations\Sms\SmsNotificationEventService;
use App\Services\ModuleService;

/**
 * Optionally sends a payment-receipt SMS when a UHMS payment is recorded.
 *
 * Strictly opt-in: returns immediately unless the SMS module is enabled AND the
 * `enable_receipt_sms` toggle is on — so the default behaviour (and every manual
 * cash payment) is completely unaffected and produces no event noise.
 */
class SendPaymentReceiptSms
{
    public function __construct(
        protected ModuleService $modules,
        protected SmsEventSettingsService $settings,
        protected SmsNotificationEventService $events,
    ) {}

    public function handle(PaymentRecorded $event): void
    {
        if (! $this->modules->enabled('sms_gateway') || ! $this->settings->enabled('enable_receipt_sms')) {
            return;
        }

        try {
            $this->events->paymentReceipt($event->payment);
        } catch (\Throwable $e) {
            // SMS must never affect payment recording.
        }
    }
}
