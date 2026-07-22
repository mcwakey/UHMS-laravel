<?php

namespace App\Services\Integrations;

use App\Enums\LogModule;
use App\Enums\LogSeverity;
use App\Exceptions\Integrations\IntegrationException;
use App\Models\IntegrationProvider;
use App\Services\ActivityLogService;
use App\Services\LegacyMigration\Foundation\Runtime\OperationalEffectGate;
use App\Services\LegacyMigration\Foundation\Runtime\ProhibitedSubsystem;
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
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);

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
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);

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
     *
     * Live activation is guarded by the go-live checklist: a live provider that
     * is not live-ready is blocked unless an elevated override (with reason) is
     * supplied. Fake providers are never selectable in production (registry).
     *
     * @throws IntegrationException when blocked.
     */
    public function activate(IntegrationProvider $provider, bool $allowOverride = false, ?string $overrideReason = null): IntegrationProvider
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);

        if ($provider->environment === IntegrationProvider::ENV_LIVE
            && ! app(ProviderGoLiveChecklistService::class)->liveActivationAllowed($provider)) {
            if (! $allowOverride || ! $overrideReason) {
                $this->logger->log(LogModule::INTEGRATIONS, 'PROVIDER_LIVE_ACTIVATION_BLOCKED', [
                    'source_type' => 'integration_provider', 'source_id' => $provider->id,
                    'severity' => LogSeverity::WARNING,
                ], $provider, "Live activation blocked (not go-live ready): {$provider->name}");

                throw new IntegrationException(
                    'Provider is not go-live ready.',
                    'integrations.errors.not_live_ready',
                );
            }

            $this->logger->log(LogModule::INTEGRATIONS, 'PROVIDER_GOLIVE_OVERRIDE_USED', [
                'source_type' => 'integration_provider', 'source_id' => $provider->id,
                'severity' => LogSeverity::SECURITY,
                'reason' => $overrideReason,
            ], $provider, "Live activation override used: {$provider->name}");
        }

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
            'severity' => LogSeverity::WARNING,
        ], $provider, "Integration provider activated: {$provider->name}");

        if ($provider->environment === IntegrationProvider::ENV_LIVE) {
            $this->logger->log(LogModule::INTEGRATIONS, 'PROVIDER_LIVE_ACTIVATION_APPROVED', [
                'source_type' => 'integration_provider', 'source_id' => $provider->id,
                'severity' => LogSeverity::WARNING,
            ], $provider, "Live provider activation approved: {$provider->name}");
        }

        return $provider->refresh();
    }

    public function deactivate(IntegrationProvider $provider): IntegrationProvider
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);

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
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);

        $written = $this->credentials->upsert($provider, $values, Auth::id());

        if ($written !== []) {
            // Log the KEYS only — never the secret values.
            $this->logger->log(LogModule::INTEGRATIONS, $this->action($provider->module_type, 'PROVIDER_CREDENTIAL_UPDATED'), [
                'source_type' => 'integration_provider',
                'source_id' => $provider->id,
                'severity' => LogSeverity::WARNING,
                'metadata' => ['credential_keys' => $written],
            ], $provider, "Integration provider credentials updated: {$provider->name}");
        }

        return $written;
    }

    public function test(IntegrationProvider $provider): ProviderTestResult
    {
        OperationalEffectGate::assertAllowed(ProhibitedSubsystem::ExternalIntegrations);

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
            'nalo_payment' => ['supports_collection' => true, 'supports_status_check' => true, 'supports_callback' => true, 'supports_refund' => false],
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
