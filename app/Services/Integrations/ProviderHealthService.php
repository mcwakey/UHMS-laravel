<?php

namespace App\Services\Integrations;

use App\Enums\LogModule;
use App\Models\IntegrationProvider;
use App\Models\PaymentProviderCallback;
use App\Models\PaymentProviderTransaction;
use App\Models\SmsMessageRecipient;
use App\Models\SmsProviderCallback;
use App\Services\ActivityLogService;

/**
 * Aggregates provider operational health for the health screen. Never exposes
 * secrets — only timestamps, statuses and counts.
 */
class ProviderHealthService
{
    public function __construct(protected ActivityLogService $logger) {}

    /** @return array<int,array<string,mixed>> health rows for all providers */
    public function snapshot(): array
    {
        return IntegrationProvider::query()
            ->orderBy('module_type')->orderByDesc('is_active')->orderBy('name')
            ->get()
            ->map(fn (IntegrationProvider $p) => $this->forProvider($p))
            ->all();
    }

    public function forProvider(IntegrationProvider $provider): array
    {
        $isSms = $provider->module_type === IntegrationProvider::MODULE_SMS;

        return [
            'provider' => $provider,
            'last_tested_at' => $provider->last_tested_at,
            'last_test_status' => $provider->last_test_status,
            'last_success_at' => $provider->last_success_at,
            'last_failure_at' => $provider->last_failure_at,
            'last_error_message' => $provider->last_error_message,
            'pending_transactions' => $isSms ? null : PaymentProviderTransaction::where('provider_id', $provider->id)
                ->whereIn('status', [
                    PaymentProviderTransaction::STATUS_INITIATED,
                    PaymentProviderTransaction::STATUS_PENDING,
                    PaymentProviderTransaction::STATUS_REQUIRES_CUSTOMER_ACTION,
                ])->count(),
            'failed_transactions' => $isSms ? null : PaymentProviderTransaction::where('provider_id', $provider->id)
                ->where('status', PaymentProviderTransaction::STATUS_FAILED)->count(),
            'undelivered_sms_count' => $isSms ? SmsMessageRecipient::whereHas('message', fn ($q) => $q->where('provider_id', $provider->id))
                ->where('status', SmsMessageRecipient::STATUS_UNDELIVERED)->count() : null,
            'callback_failures' => $isSms
                ? SmsProviderCallback::where('provider_id', $provider->id)->whereNotNull('processing_error')->count()
                : PaymentProviderCallback::where('provider_id', $provider->id)->whereNotNull('processing_error')->count(),
        ];
    }

    public function logHealthChecked(?IntegrationProvider $provider = null): void
    {
        $this->logger->log(LogModule::INTEGRATIONS, 'PAYMENT_PROVIDER_HEALTH_CHECKED', [
            'source_type' => $provider ? 'integration_provider' : null,
            'source_id' => $provider?->id,
        ], $provider, 'Provider health checked');
    }
}
