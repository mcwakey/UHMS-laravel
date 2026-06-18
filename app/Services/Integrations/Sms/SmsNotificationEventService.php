<?php

namespace App\Services\Integrations\Sms;

use App\Enums\LogModule;
use App\Models\SmsMessage;
use App\Models\SmsNotificationEvent;
use App\Models\SmsTemplate;
use App\Services\ActivityLogService;
use App\Services\ModuleService;
use Illuminate\Support\Facades\Auth;

/**
 * Single gate for AUTOMATIC SMS events. Nothing is sent unless: the SMS module is
 * enabled, the per-event toggle is on, an active provider exists, the payer has a
 * valid phone, and no prior `sent` event exists for the same source/event (dedup).
 * Every decision (sent/skipped) is recorded in sms_notification_events + audited.
 */
class SmsNotificationEventService
{
    /** event_type => settings toggle key (manual_custom is always allowed) */
    private const TOGGLE = [
        SmsNotificationEvent::TYPE_INVOICE_PAYMENT_REQUEST => 'enable_payment_request_sms',
        SmsNotificationEvent::TYPE_PAYMENT_RECEIPT => 'enable_receipt_sms',
        SmsNotificationEvent::TYPE_APPOINTMENT_REMINDER => 'enable_appointment_reminder_sms',
        SmsNotificationEvent::TYPE_QUEUE_NOTIFICATION => 'enable_queue_sms',
    ];

    public function __construct(
        protected SmsGatewayService $gateway,
        protected SmsTemplateRenderer $renderer,
        protected SmsEventSettingsService $settings,
        protected SmsPhoneNumberNormalizer $normalizer,
        protected ModuleService $modules,
        protected ActivityLogService $logger,
    ) {}

    /**
     * @param array $args event_type, phone, source_type?, source_id?, template_id?,
     *                     body?, data?, force_resend?(bool)
     */
    public function dispatch(array $args): SmsNotificationEvent
    {
        $eventType = (string) $args['event_type'];
        $phone = trim((string) ($args['phone'] ?? ''));

        $event = SmsNotificationEvent::create([
            'event_type' => $eventType,
            'source_type' => $args['source_type'] ?? null,
            'source_id' => $args['source_id'] ?? null,
            'template_id' => $args['template_id'] ?? null,
            'recipient_phone' => $phone,
            'status' => SmsNotificationEvent::STATUS_PENDING,
            'triggered_by' => Auth::id(),
            'triggered_at' => now(),
            'metadata_snapshot' => $args['data'] ?? null,
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, 'SMS_NOTIFICATION_EVENT_CREATED', [
            'source_type' => 'sms_notification_event', 'source_id' => $event->id,
            'metadata' => ['event_type' => $eventType],
        ], $event, 'SMS notification event created');

        if ($reason = $this->skipReason($args, $eventType, $phone)) {
            return $this->skip($event, $reason);
        }

        // Render body (template or provided), service-side placeholder resolution.
        $body = $this->resolveBody($args);
        if ($body === '') {
            return $this->skip($event, 'empty_body');
        }

        try {
            $message = $this->gateway->send([
                'body' => $body,
                'recipients' => [['phone' => $phone]],
                'message_type' => $eventType,
                'template_id' => $args['template_id'] ?? null,
                'metadata' => ['notification_event_id' => $event->id],
            ]);

            $event->update([
                'sms_message_id' => $message->id,
                'status' => SmsNotificationEvent::STATUS_SENT,
                'sent_at' => now(),
            ]);

            $this->logger->log(LogModule::INTEGRATIONS, 'SMS_NOTIFICATION_EVENT_SENT', [
                'source_type' => 'sms_notification_event', 'source_id' => $event->id,
                'metadata' => ['event_type' => $eventType, 'sms_message_id' => $message->id],
            ], $event, 'SMS notification event sent');
        } catch (\Throwable $e) {
            // SMS failure must never break the source workflow.
            $event->update([
                'status' => SmsNotificationEvent::STATUS_FAILED,
                'failed_at' => now(),
                'error_message' => 'SMS could not be sent.',
            ]);
        }

        return $event->refresh();
    }

    /* ── convenience hooks ──────────────────────────────────────────── */

    public function paymentReceipt(\App\Models\Payment $payment): SmsNotificationEvent
    {
        return $this->dispatch([
            'event_type' => SmsNotificationEvent::TYPE_PAYMENT_RECEIPT,
            'source_type' => 'payment',
            'source_id' => $payment->id,
            'phone' => $payment->patient?->phone,
            'data' => [
                'patient_name' => $payment->patient?->first_name,
                'amount' => number_format((float) $payment->amount, 2),
                'currency' => 'GHS',
                'receipt_number' => $payment->payment_number,
                'hospital_name' => config('app.name', 'UHMS'),
            ],
        ]);
    }

    public function invoicePaymentRequest(\App\Models\Invoice $invoice, ?string $phone = null, ?string $paymentLink = null): SmsNotificationEvent
    {
        return $this->dispatch([
            'event_type' => SmsNotificationEvent::TYPE_INVOICE_PAYMENT_REQUEST,
            'source_type' => 'invoice',
            'source_id' => $invoice->id,
            'phone' => $phone ?: $invoice->patient?->phone,
            'data' => [
                'patient_name' => $invoice->patient?->first_name,
                'invoice_number' => $invoice->invoice_number,
                'amount' => number_format((float) $invoice->balance, 2),
                'currency' => 'GHS',
                'payment_link' => $paymentLink ?? '',
                'hospital_name' => config('app.name', 'UHMS'),
            ],
        ]);
    }

    /* ── internals ──────────────────────────────────────────────────── */

    private function skipReason(array $args, string $eventType, string $phone): ?string
    {
        if (! $this->modules->enabled('sms_gateway')) {
            return 'module_disabled';
        }
        $toggle = self::TOGGLE[$eventType] ?? null;
        if ($toggle !== null && ! $this->settings->enabled($toggle)) {
            return 'event_disabled';
        }
        if (! $this->gateway->hasActiveProvider()) {
            return 'no_active_provider';
        }
        if ($phone === '' || ! $this->normalizer->normalize($phone)['valid']) {
            return 'no_valid_phone';
        }
        if (empty($args['force_resend']) && ! empty($args['source_type']) && ! empty($args['source_id'])) {
            $exists = SmsNotificationEvent::where('event_type', $eventType)
                ->where('source_type', $args['source_type'])
                ->where('source_id', $args['source_id'])
                ->where('status', SmsNotificationEvent::STATUS_SENT)
                ->exists();
            if ($exists) {
                return 'duplicate';
            }
        }
        return null;
    }

    private function resolveBody(array $args): string
    {
        $data = (array) ($args['data'] ?? []);

        if (! empty($args['template_id']) && ($template = SmsTemplate::find($args['template_id']))) {
            return $this->renderer->render($template->body, $data, strict: false);
        }
        if (! empty($args['body'])) {
            return $this->renderer->render((string) $args['body'], $data, strict: false);
        }
        // Safe minimal default per event type.
        $default = (string) (config('integrations.sms_default_bodies.' . $args['event_type']) ?? '');
        return $default === '' ? '' : $this->renderer->render($default, $data, strict: false);
    }

    private function skip(SmsNotificationEvent $event, string $reason): SmsNotificationEvent
    {
        $event->update(['status' => SmsNotificationEvent::STATUS_SKIPPED, 'error_message' => $reason]);

        $this->logger->log(LogModule::INTEGRATIONS, 'SMS_NOTIFICATION_EVENT_SKIPPED', [
            'source_type' => 'sms_notification_event', 'source_id' => $event->id,
            'metadata' => ['event_type' => $event->event_type, 'reason' => $reason],
        ], $event, 'SMS notification event skipped');

        return $event->refresh();
    }
}
