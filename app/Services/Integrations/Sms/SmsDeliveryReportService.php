<?php

namespace App\Services\Integrations\Sms;

use App\Enums\LogModule;
use App\Models\IntegrationProvider;
use App\Models\SmsDeliveryReport;
use App\Models\SmsMessageRecipient;
use App\Services\ActivityLogService;
use App\Support\Integrations\Sms\SmsCallbackResult;

/**
 * Records inbound delivery reports against recipients. Idempotent: a repeated
 * report for the same provider_message_id + status is stored at most once and
 * never double-applies a recipient status change.
 */
class SmsDeliveryReportService
{
    public function __construct(protected ActivityLogService $logger) {}

    public function record(?IntegrationProvider $provider, SmsCallbackResult $result): ?SmsDeliveryReport
    {
        $providerMessageId = $result->providerMessageId;
        if (! $providerMessageId) {
            return null;
        }

        $recipient = SmsMessageRecipient::where('provider_message_id', $providerMessageId)->first();

        // Idempotency guard: same recipient + provider_message_id + status already stored.
        $existing = SmsDeliveryReport::query()
            ->where('provider_message_id', $providerMessageId)
            ->where('status', $result->status)
            ->when($recipient, fn ($q) => $q->where('sms_message_recipient_id', $recipient->id))
            ->first();
        if ($existing) {
            return $existing;
        }

        $report = SmsDeliveryReport::create([
            'sms_message_recipient_id' => $recipient?->id,
            'provider_id' => $provider?->id,
            'provider_message_id' => $providerMessageId,
            'provider_status' => $result->providerStatus,
            'status' => $result->status ?? 'delivered',
            'reported_at' => $result->reportedAt ? now()->parse($result->reportedAt) : now(),
            'raw_payload' => $result->raw,
        ]);

        if ($recipient) {
            $this->applyToRecipient($recipient, $result);
        }

        $this->logger->log(LogModule::INTEGRATIONS, 'SMS_DELIVERY_REPORT_RECEIVED', [
            'source_type' => 'sms_delivery_report',
            'source_id' => $report->id,
            'metadata' => ['provider_message_id' => $providerMessageId, 'status' => $result->status],
        ], $report, 'SMS delivery report received');

        return $report;
    }

    private function applyToRecipient(SmsMessageRecipient $recipient, SmsCallbackResult $result): void
    {
        $status = match ($result->status) {
            'delivered' => SmsMessageRecipient::STATUS_DELIVERED,
            'undelivered', 'failed' => SmsMessageRecipient::STATUS_UNDELIVERED,
            default => $recipient->status,
        };

        $recipient->update([
            'status' => $status,
            'provider_status' => $result->providerStatus ?? $recipient->provider_status,
            'delivered_at' => $status === SmsMessageRecipient::STATUS_DELIVERED ? now() : $recipient->delivered_at,
        ]);
    }
}
