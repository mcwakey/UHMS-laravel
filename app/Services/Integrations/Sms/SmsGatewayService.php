<?php

namespace App\Services\Integrations\Sms;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Exceptions\Integrations\IntegrationException;
use App\Models\IntegrationProvider;
use App\Models\SmsMessage;
use App\Models\SmsMessageRecipient;
use App\Services\ActivityLogService;
use App\Services\Integrations\IntegrationProviderRegistry;
use App\Services\Integrations\IntegrationProviderService;
use App\Support\Integrations\Sms\SmsCallbackResult;
use App\Support\Integrations\Sms\SmsSendRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Orchestrates SMS sending through the active provider. Controllers/clinical
 * code never touch a provider directly — they call send() and SMS failures
 * never block the calling workflow (failures are recorded + visible).
 */
class SmsGatewayService
{
    public function __construct(
        protected IntegrationProviderService $providers,
        protected IntegrationProviderRegistry $registry,
        protected SmsPhoneNumberNormalizer $normalizer,
        protected SmsDeliveryReportService $deliveryReports,
        protected ActivityLogService $logger,
    ) {}

    public function hasActiveProvider(): bool
    {
        return $this->providers->activeProvider(IntegrationProvider::MODULE_SMS) !== null;
    }

    /**
     * Send an SMS to one or more recipients through the active provider.
     *
     * @param array $data ['body' => string, 'recipients' => [['phone','name'?,'recipient_type'?,'recipient_id'?], ...],
     *                      'sender_id'? , 'message_type'?, 'template_id'?, 'metadata'?]
     * @throws IntegrationException when no active SMS provider exists.
     */
    public function send(array $data): SmsMessage
    {
        $provider = $this->providers->activeProvider(IntegrationProvider::MODULE_SMS);
        if (! $provider) {
            throw IntegrationException::notConfigured('sms');
        }

        $body = trim((string) ($data['body'] ?? ''));
        if ($body === '') {
            throw new IntegrationException('SMS body is empty.', 'integrations.errors.sms_body_required');
        }

        $message = DB::transaction(function () use ($data, $provider, $body) {
            $message = SmsMessage::create([
                'message_uuid' => (string) Str::uuid(),
                'provider_id' => $provider->id,
                'template_id' => $data['template_id'] ?? null,
                'sender_id' => $data['sender_id'] ?? $provider->sender_id,
                'message_body' => $body,
                'message_type' => $data['message_type'] ?? 'manual',
                'status' => SmsMessage::STATUS_QUEUED,
                'metadata_snapshot' => $data['metadata'] ?? null,
                'created_by' => Auth::id(),
            ]);

            foreach (($data['recipients'] ?? []) as $row) {
                $norm = $this->normalizer->normalize((string) ($row['phone'] ?? ''));
                $message->recipients()->create([
                    'recipient_type' => $row['recipient_type'] ?? null,
                    'recipient_id' => $row['recipient_id'] ?? null,
                    'phone_number' => $norm['original'],
                    'normalized_phone_number' => $norm['normalized'],
                    'recipient_name' => $row['name'] ?? null,
                    'status' => $norm['valid']
                        ? SmsMessageRecipient::STATUS_QUEUED
                        : SmsMessageRecipient::STATUS_FAILED,
                    'error_code' => $norm['valid'] ? null : 'invalid_number',
                    'error_message' => $norm['valid'] ? null : 'Phone number failed validation.',
                ]);
            }

            return $message->load('recipients');
        });

        $this->logger->log(LogModule::INTEGRATIONS, 'SMS_MESSAGE_CREATED', [
            'source_type' => 'sms_message',
            'source_id' => $message->id,
            'metadata' => ['type' => $message->message_type, 'recipients' => $message->recipients->count()],
        ], $message, 'SMS message created');

        $this->dispatchToProvider($provider, $message);

        return $message->refresh()->load('recipients');
    }

    /** Resend the still-unsent recipients of an existing message. */
    public function resend(SmsMessage $message): SmsMessage
    {
        $provider = $this->providers->activeProvider(IntegrationProvider::MODULE_SMS);
        if (! $provider) {
            throw IntegrationException::notConfigured('sms');
        }

        $this->dispatchToProvider($provider, $message, resend: true);

        return $message->refresh()->load('recipients');
    }

    public function handleDeliveryCallback(IntegrationProvider $provider, array $payload, array $headers = []): SmsCallbackResult
    {
        $adapter = $this->registry->makeSms($provider);
        $result = $adapter->handleCallback($payload, $headers);
        $this->deliveryReports->record($provider, $result);

        return $result;
    }

    /* ── internals ──────────────────────────────────────────────────── */

    private function dispatchToProvider(IntegrationProvider $provider, SmsMessage $message, bool $resend = false): void
    {
        $sendable = $message->recipients
            ->filter(fn ($r) => in_array($r->status, [
                SmsMessageRecipient::STATUS_QUEUED,
                $resend ? SmsMessageRecipient::STATUS_FAILED : SmsMessageRecipient::STATUS_QUEUED,
            ], true))
            ->filter(fn ($r) => $r->error_code !== 'invalid_number');

        if ($sendable->isEmpty()) {
            $this->finaliseMessageStatus($message);
            return;
        }

        $request = new SmsSendRequest(
            body: $message->message_body,
            recipients: $sendable->map(fn ($r) => [
                'recipient_id' => $r->id,
                'phone' => $r->normalized_phone_number,
                'name' => $r->recipient_name,
            ])->values()->all(),
            senderId: $message->sender_id,
            reference: $message->message_uuid,
            messageType: $message->message_type,
        );

        $message->update(['status' => SmsMessage::STATUS_SENDING]);

        try {
            $adapter = $this->registry->makeSms($provider);
            $result = $adapter->send($request);
        } catch (\Throwable $e) {
            // Never let an SMS transport failure bubble into the caller's workflow.
            $this->markAllFailed($message, $sendable, 'send_exception', 'Provider call failed.');
            $this->logger->log(LogModule::INTEGRATIONS, 'SMS_MESSAGE_FAILED', [
                'source_type' => 'sms_message',
                'source_id' => $message->id,
                'severity' => LogSeverity::WARNING,
            ], $message, 'SMS message failed to send');
            return;
        }

        foreach ($sendable as $recipient) {
            $info = $result->perRecipient[$recipient->normalized_phone_number] ?? null;
            if ($info && ($info['status'] ?? null) === 'sent') {
                $recipient->update([
                    'status' => SmsMessageRecipient::STATUS_SENT,
                    'provider_message_id' => $info['provider_message_id'] ?? null,
                    'provider_status' => $info['provider_status'] ?? null,
                    'sent_at' => now(),
                ]);
            } else {
                $recipient->update([
                    'status' => SmsMessageRecipient::STATUS_FAILED,
                    'provider_status' => $info['provider_status'] ?? null,
                    'failed_at' => now(),
                    'error_code' => $info['error_code'] ?? 'send_failed',
                    'error_message' => $info['error_message'] ?? 'Provider did not accept the message.',
                ]);
            }
        }

        $message->update(['provider_batch_reference' => $result->batchReference]);
        $this->finaliseMessageStatus($message->refresh()->load('recipients'));

        $action = $message->status === SmsMessage::STATUS_FAILED ? 'SMS_MESSAGE_FAILED' : 'SMS_MESSAGE_SENT';
        $this->logger->log(LogModule::INTEGRATIONS, $action, [
            'source_type' => 'sms_message',
            'source_id' => $message->id,
            'metadata' => ['status' => $message->status, 'batch' => $result->batchReference],
        ], $message, "SMS message {$message->status}");
    }

    private function finaliseMessageStatus(SmsMessage $message): void
    {
        $recipients = $message->recipients;
        $total = $recipients->count();
        $sent = $recipients->whereIn('status', [
            SmsMessageRecipient::STATUS_SENT,
            SmsMessageRecipient::STATUS_DELIVERED,
        ])->count();
        $failed = $recipients->whereIn('status', [
            SmsMessageRecipient::STATUS_FAILED,
            SmsMessageRecipient::STATUS_UNDELIVERED,
        ])->count();

        $status = match (true) {
            $total === 0 => SmsMessage::STATUS_FAILED,
            $sent === 0 => SmsMessage::STATUS_FAILED,
            $failed === 0 => SmsMessage::STATUS_SENT,
            default => SmsMessage::STATUS_PARTIALLY_SENT,
        };

        $message->update([
            'status' => $status,
            'sent_at' => $message->sent_at ?? ($sent > 0 ? now() : null),
            'failed_at' => $status === SmsMessage::STATUS_FAILED ? now() : $message->failed_at,
            'completed_at' => now(),
        ]);
    }

    private function markAllFailed(SmsMessage $message, $recipients, string $code, string $reason): void
    {
        foreach ($recipients as $recipient) {
            $recipient->update([
                'status' => SmsMessageRecipient::STATUS_FAILED,
                'failed_at' => now(),
                'error_code' => $code,
                'error_message' => $reason,
            ]);
        }
        $message->update(['status' => SmsMessage::STATUS_FAILED, 'failed_at' => now(), 'completed_at' => now()]);
    }
}
