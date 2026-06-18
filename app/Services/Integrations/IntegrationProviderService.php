<?php

namespace App\Services\Integrations;

use App\Enums\LogModule;
use App\Models\IntegrationProvider;
use App\Services\ActivityLogService;
use App\Support\Integrations\ProviderTestResult;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Manages integration providers (SMS + Payment): create/update, the single-
 * active-provider-per-module rule, credential updates, and connectivity tests.
 *
 * This is the funnel where ALL provider mutations are audited — controllers
 * delegate here, so audit coverage stays centralised.
 */
class IntegrationProviderService
{
    public function __construct(
        protected IntegrationCredentialService $credentials,
        protected IntegrationProviderRegistry $registry,
        protected ActivityLogService $logger,
    ) {}

    public function create(array $data, string $moduleType): IntegrationProvider
    {
        $data['module_type'] = $moduleType;
        $data['status'] = $data['status'] ?? IntegrationProvider::STATUS_DRAFT;
        $data['is_active'] = false; // activation is an explicit, audited step
        $data['created_by'] = Auth::id();
        $data['updated_by'] = Auth::id();

        $provider = IntegrationProvider::create($data);

        $this->logger->log(LogModule::INTEGRATIONS, $this->action($moduleType, 'PROVIDER_CREATED'), [
            'source_type' => 'integration_provider',
            'source_id' => $provider->id,
            'new_values' => $this->loggable($provider),
        ], $provider, "Integration provider created: {$provider->name}");

        return $provider;
    }

    public function update(IntegrationProvider $provider, array $data): IntegrationProvider
    {
        $old = $this->loggable($provider);
        $data['updated_by'] = Auth::id();
        unset($data['is_active'], $data['module_type']); // not editable here

        $provider->update($data);

        $this->logger->log(LogModule::INTEGRATIONS, $this->action($provider->module_type, 'PROVIDER_UPDATED'), [
            'source_type' => 'integration_provider',
            'source_id' => $provider->id,
            'old_values' => $old,
            'new_values' => $this->loggable($provider->refresh()),
        ], $provider, "Integration provider updated: {$provider->name}");

        return $provider;
    }

    /**
     * Activate a provider, deactivating any other active provider for the same
     * module type. Enforced in a transaction (the "single active" guarantee).
     */
    public function activate(IntegrationProvider $provider): IntegrationProvider
    {
        DB::transaction(function () use ($provider) {
            $previous = IntegrationProvider::query()
                ->where('module_type', $provider->module_type)
                ->where('id', '!=', $provider->id)
                ->where('is_active', true)
                ->lockForUpdate()
                ->get();

            foreach ($previous as $other) {
                $other->update([
                    'is_active' => false,
                    'status' => IntegrationProvider::STATUS_INACTIVE,
                    'updated_by' => Auth::id(),
                ]);
                $this->logger->log(LogModule::INTEGRATIONS, $this->action($provider->module_type, 'PROVIDER_DEACTIVATED'), [
                    'source_type' => 'integration_provider',
                    'source_id' => $other->id,
                ], $other, "Integration provider deactivated: {$other->name}");
            }

            $provider->update([
                'is_active' => true,
                'status' => IntegrationProvider::STATUS_ACTIVE,
                'updated_by' => Auth::id(),
            ]);
        });

        $this->logger->log(LogModule::INTEGRATIONS, $this->action($provider->module_type, 'PROVIDER_ACTIVATED'), [
            'source_type' => 'integration_provider',
            'source_id' => $provider->id,
            'severity' => \App\Enums\LogSeverity::WARNING,
        ], $provider, "Integration provider activated: {$provider->name}");

        return $provider->refresh();
    }

    public function deactivate(IntegrationProvider $provider): IntegrationProvider
    {
        $provider->update([
            'is_active' => false,
            'status' => IntegrationProvider::STATUS_INACTIVE,
            'updated_by' => Auth::id(),
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, $this->action($provider->module_type, 'PROVIDER_DEACTIVATED'), [
            'source_type' => 'integration_provider',
            'source_id' => $provider->id,
        ], $provider, "Integration provider deactivated: {$provider->name}");

        return $provider;
    }

    public function updateCredentials(IntegrationProvider $provider, array $values): array
    {
        $written = $this->credentials->upsert($provider, $values, Auth::id());

        if ($written !== []) {
            // Log the KEYS only — never the secret values.
            $this->logger->log(LogModule::INTEGRATIONS, $this->action($provider->module_type, 'PROVIDER_CREDENTIAL_UPDATED'), [
                'source_type' => 'integration_provider',
                'source_id' => $provider->id,
                'severity' => \App\Enums\LogSeverity::WARNING,
                'metadata' => ['credential_keys' => $written],
            ], $provider, "Integration provider credentials updated: {$provider->name}");
        }

        return $written;
    }

    public function test(IntegrationProvider $provider): ProviderTestResult
    {
        try {
            $adapter = $provider->module_type === IntegrationProvider::MODULE_SMS
                ? $this->registry->makeSms($provider)
                : $this->registry->makePayment($provider);

            $result = $adapter->testConnection();
        } catch (\Throwable $e) {
            $result = ProviderTestResult::fail('Provider could not be initialised. Check configuration.');
        }

        $provider->update([
            'last_tested_at' => now(),
            'last_test_status' => $result->status(),
            'last_test_message' => $result->message,
            'last_success_at' => $result->success ? now() : $provider->last_success_at,
            'last_failure_at' => $result->success ? $provider->last_failure_at : now(),
            'last_error_message' => $result->success ? $provider->last_error_message : $result->message,
            'updated_by' => Auth::id(),
        ]);

        $this->logger->log(LogModule::INTEGRATIONS, $this->action($provider->module_type, 'PROVIDER_TESTED'), [
            'source_type' => 'integration_provider',
            'source_id' => $provider->id,
            'metadata' => ['result' => $result->status()],
        ], $provider, "Integration provider tested: {$provider->name}");

        return $result;
    }

    /**
     * Provider-intrinsic capability flags by code. Keeps controllers from
     * hand-setting capabilities and documents what each adapter supports today.
     *
     * @return array<string,bool>
     */
    public static function capabilityFlags(string $moduleType, string $code): array
    {
        $map = [
            'nalo_sms' => ['supports_send' => true, 'supports_status_check' => false, 'supports_callback' => true],
            'fake_sms' => ['supports_send' => true, 'supports_status_check' => true, 'supports_callback' => true],
            'mtn_momo' => ['supports_collection' => true, 'supports_status_check' => true, 'supports_callback' => true, 'supports_refund' => false],
            'nalo_payment' => ['supports_collection' => true, 'supports_status_check' => false, 'supports_callback' => true, 'supports_refund' => false],
            'fake_payment' => ['supports_collection' => true, 'supports_status_check' => true, 'supports_callback' => true, 'supports_refund' => true],
        ];

        return $map[$code] ?? [];
    }

    /** Resolve the single active provider for a module type (or null). */
    public function activeProvider(string $moduleType): ?IntegrationProvider
    {
        return IntegrationProvider::query()
            ->module($moduleType)
            ->active()
            ->first();
    }

    /* ── helpers ────────────────────────────────────────────────────── */

    private function action(string $moduleType, string $suffix): string
    {
        $prefix = $moduleType === IntegrationProvider::MODULE_SMS ? 'SMS' : 'PAYMENT';
        return "{$prefix}_{$suffix}";
    }

    /** Non-secret provider attributes safe to record in the audit trail. */
    private function loggable(IntegrationProvider $provider): array
    {
        return $provider->only([
            'code', 'name', 'environment', 'base_url', 'status', 'is_active',
            'sender_id', 'callback_url',
        ]);
    }
}
