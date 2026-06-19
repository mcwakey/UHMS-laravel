<?php

namespace App\Listeners\Integrations;

use App\Enums\LogModule;
use App\Events\PaymentRecorded;
use App\Models\SmsNotificationEvent;
use App\Services\ActivityLogService;
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
        protected ActivityLogService $logger,
    ) {}

    public function handle(PaymentRecorded $event): void
    {
        if (! $this->modules->enabled('sms_gateway') || ! $this->settings->enabled('enable_receipt_sms')) {
            return;
        }

        try {
            $notification = $this->events->paymentReceipt($event->payment);

            if ($notification->status === SmsNotificationEvent::STATUS_SENT) {
                $this->logger->log(LogModule::INTEGRATIONS, 'RECEIPT_SMS_QUEUED', [
                    'source_type' => 'payment', 'source_id' => $event->payment->id,
                    'payment_id' => $event->payment->id,
                ], $event->payment, 'Receipt SMS queued');
            }
        } catch (\Throwable $e) {
            // SMS must never affect payment recording.
        }
    }
}
