<?php

namespace App\Services\Integrations\Sms;

use App\Enums\LogModule;
use App\Models\IntegrationProvider;
use App\Models\SmsMessageRecipient;
use App\Services\ActivityLogService;
use App\Services\Integrations\IntegrationProviderRegistry;
use App\Support\Integrations\Sms\SmsCallbackResult;
use Illuminate\Support\Facades\Log;

/**
 * Queries providers for the final delivery status of recipients that were sent
 * but never received a delivery report. Idempotent (delegates the recipient
 * update to SmsDeliveryReportService), continues on individual failure, and
 * never marks a recipient delivered when the provider can't be queried.
 */
class SmsStatusReconciliationService
{
    public function __construct(
        protected IntegrationProviderRegistry $registry,
        protected SmsDeliveryReportService $deliveryReports,
        protected ActivityLogService $logger,
    ) {}

    /**
     * @param array $filters provider_code?, message_id?, recipient_id?, from?, to?, limit?
     * @return array{checked:int,delivered:int,undelivered:int,unsupported:int,errors:int,skipped:int}
     */
    public function reconcile(array $filters = [], bool $dryRun = false): array
    {
        $summary = ['checked' => 0, 'delivered' => 0, 'undelivered' => 0, 'unsupported' => 0, 'errors' => 0, 'skipped' => 0];

        $query = SmsMessageRecipient::query()
            ->where('status', SmsMessageRecipient::STATUS_SENT)
            ->whereNotNull('provider_message_id')
            ->with('message.provider');

        if (! empty($filters['recipient_id'])) {
            $query->where('id', $filters['recipient_id']);
        }
        if (! empty($filters['message_id'])) {
            $query->where('sms_message_id', $filters['message_id']);
        }
        if (! empty($filters['from'])) {
            $query->whereDate('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->whereDate('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['provider_code'])) {
            $query->whereHas('message.provider', fn ($q) => $q->where('code', $filters['provider_code']));
        }

        $recipients = $query->limit((int) ($filters['limit'] ?? 200))->get();

        $adapters = [];
        foreach ($recipients as $recipient) {
            $provider = $recipient->message?->provider;
            if (! $provider) {
                $summary['skipped']++;
                continue;
            }

            if (! $provider->supports_status_check) {
                $summary['unsupported']++;
                continue; // never fabricate a delivered status
            }

            try {
                $adapters[$provider->id] ??= $this->registry->makeSms($provider);
                $result = $adapters[$provider->id]->queryStatus((string) $recipient->provider_message_id);
                $summary['checked']++;

                if (! $dryRun) {
                    $recipient->forceFill(['provider_status_checked_at' => now()])->save();
                }

                if (! $result->success || ! $result->status) {
                    continue;
                }

                $normalized = in_array($result->status, ['delivered'], true) ? 'delivered'
                    : (in_array($result->status, ['undelivered', 'failed', 'expired'], true) ? 'undelivered' : null);

                if ($normalized === null) {
                    continue;
                }

                $summary[$normalized]++;

                if (! $dryRun) {
                    $this->deliveryReports->record($provider, new SmsCallbackResult(
                        providerMessageId: (string) $recipient->provider_message_id,
                        status: $normalized,
                        providerStatus: $result->providerStatus,
                        reportedAt: $result->deliveredAt,
                        eventType: 'status_reconciliation',
                        signatureValid: true,
                        raw: $result->raw,
                    ));
                }
            } catch (\Throwable $e) {
                $summary['errors']++;
                Log::warning('SMS status reconciliation error', ['recipient_id' => $recipient->id, 'error' => $e->getMessage()]);
            }
        }

        $this->logger->log(LogModule::INTEGRATIONS, 'SMS_STATUS_RECONCILIATION_RUN', [
            'metadata' => array_merge($summary, ['dry_run' => $dryRun, 'filters' => array_filter($filters)]),
        ], null, 'SMS status reconciliation run');

        return $summary;
    }
}
