<?php

namespace App\Services\Billing;

use App\Enums\PaymentGateOperationMode;
use App\Enums\PaymentTimingCutoverMode;
use App\Models\Setting;
use Throwable;

/**
 * Resolves the master cutover configuration (Payment Timing Policy Phase 8).
 *
 * Reads settings + the environment kill switch ONLY — it queries no visits,
 * patients, arrangements, invoices or payments, and makes no gate decision.
 * Invalid settings safely disable cutover; the environment force-legacy flag
 * always wins over the database.
 */
class PaymentTimingCutoverConfigurationService
{
    /** Reuses the existing payment-timing settings group. */
    public const GROUP = 'payment_timing';

    private ?PaymentTimingCutoverMode $configuredModeMemo = null;

    public function __construct(private readonly PaymentGateOperationConfigurationService $operationConfiguration) {}

    /** The environment kill switch — DB-independent, always wins. */
    public function forceLegacy(): bool
    {
        return (bool) config('payment_timing_cutover.force_legacy', false);
    }

    /** The master mode stored in settings (ignores the kill switch). */
    public function configuredMode(): PaymentTimingCutoverMode
    {
        if ($this->configuredModeMemo !== null) {
            return $this->configuredModeMemo;
        }

        try {
            return $this->configuredModeMemo = PaymentTimingCutoverMode::fromStorage(Setting::getValue(self::GROUP, 'cutover_mode'));
        } catch (Throwable) {
            return $this->configuredModeMemo = PaymentTimingCutoverMode::DISABLED;
        }
    }

    /** The mode that actually applies at runtime (kill switch forces DISABLED). */
    public function effectiveMode(): PaymentTimingCutoverMode
    {
        return $this->forceLegacy() ? PaymentTimingCutoverMode::DISABLED : $this->configuredMode();
    }

    /** True only when master is ACTIVE and the operation is configured `typed`. */
    public function operationUsesTypedPolicy(string $operation): bool
    {
        if ($this->effectiveMode() !== PaymentTimingCutoverMode::ACTIVE) {
            return false;
        }

        return $this->operationMode($operation) === PaymentGateOperationMode::TYPED;
    }

    /**
     * True when the cutover should COMPUTE a typed decision but keep legacy
     * authoritative (master OBSERVE, or an operation set to observe under ACTIVE).
     */
    public function operationIsObserveOnly(string $operation): bool
    {
        $mode = $this->effectiveMode();
        if ($mode === PaymentTimingCutoverMode::DISABLED) {
            return false;
        }
        if ($mode === PaymentTimingCutoverMode::OBSERVE) {
            return true;
        }

        // ACTIVE: observe-only when the operation itself is not typed.
        return $this->operationMode($operation) !== PaymentGateOperationMode::TYPED;
    }

    public function fallbackToLegacyOnFailure(): bool
    {
        return $this->boolSetting('cutover_failure_fallback', true);
    }

    public function logDecisions(): bool
    {
        return $this->boolSetting('cutover_log_decisions', false);
    }

    public function logFallbacks(): bool
    {
        return $this->boolSetting('cutover_log_fallbacks', true);
    }

    private function operationMode(string $operation): PaymentGateOperationMode
    {
        try {
            return $this->operationConfiguration->modeFor($operation);
        } catch (Throwable) {
            return PaymentGateOperationMode::LEGACY;
        }
    }

    private function boolSetting(string $key, bool $default): bool
    {
        try {
            $value = Setting::getValue(self::GROUP, $key);

            return $value === null ? $default : (bool) $value;
        } catch (Throwable) {
            return $default;
        }
    }
}
