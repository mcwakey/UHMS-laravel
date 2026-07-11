<?php

namespace App\Services\Billing;

use App\Enums\VisitPaymentTimingPolicy;
use App\Enums\VisitType;
use App\Models\Setting;
use BackedEnum;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PaymentTimingConfigurationService
{
    public const GROUP = 'payment_timing';

    public function enabled(): bool
    {
        return $this->boolean('enabled', (bool) config('payment_timing.enabled', false));
    }

    public function globalDefault(): VisitPaymentTimingPolicy
    {
        $configured = $this->policyFrom(config('payment_timing.default_policy'));
        $fallback = $configured && $configured !== VisitPaymentTimingPolicy::INHERIT
            ? $configured
            : VisitPaymentTimingPolicy::PAY_BEFORE_SERVICE;

        $policy = $this->storedPolicy('default_policy', $fallback);

        return $policy === VisitPaymentTimingPolicy::INHERIT ? $fallback : $policy;
    }

    public function policyForVisitType(string|BackedEnum $visitType): VisitPaymentTimingPolicy
    {
        $value = $visitType instanceof BackedEnum ? (string) $visitType->value : $visitType;
        $type = VisitType::tryFrom($value);
        if (! $type) {
            return $this->globalDefault();
        }

        $fallback = $this->policyFrom(config("payment_timing.visit_types.{$type->value}"))
            ?? VisitPaymentTimingPolicy::INHERIT;
        $policy = $this->storedPolicy("{$type->value}_policy", $fallback);

        return $policy === VisitPaymentTimingPolicy::INHERIT ? $this->globalDefault() : $policy;
    }

    public function configuredPolicyForVisitType(VisitType $visitType): VisitPaymentTimingPolicy
    {
        $fallback = $this->policyFrom(config("payment_timing.visit_types.{$visitType->value}"))
            ?? VisitPaymentTimingPolicy::INHERIT;

        return $this->storedPolicy("{$visitType->value}_policy", $fallback);
    }

    public function emergencyDefault(): VisitPaymentTimingPolicy
    {
        return $this->policyForVisitType(VisitType::EMERGENCY);
    }

    public function neverBlockEmergencyStabilisation(): bool
    {
        return $this->boolean('emergency_never_block_stabilisation', (bool) config('payment_timing.emergency.never_block_stabilisation', true));
    }

    public function requiresSettlementForPayAfterServices(): bool
    {
        return $this->boolean('require_settlement_for_pay_after_services', (bool) config('payment_timing.financial_closure.require_settlement_for_pay_after_services', true));
    }

    public function requiresSettlementForRunningBill(): bool
    {
        return $this->boolean('require_settlement_for_running_bill', (bool) config('payment_timing.financial_closure.require_settlement_for_running_bill', true));
    }

    public function allowsOutstandingBalanceOverride(): bool
    {
        return $this->boolean('allow_outstanding_balance_override', (bool) config('payment_timing.financial_closure.allow_authorised_outstanding_balance_override', true));
    }

    private function storedPolicy(string $key, VisitPaymentTimingPolicy $fallback): VisitPaymentTimingPolicy
    {
        $raw = $this->setting($key, $fallback->value);
        $policy = $this->policyFrom($raw);
        if ($policy) {
            return $policy;
        }

        Log::warning('Invalid payment timing setting; using safe fallback.', ['group' => self::GROUP, 'key' => $key]);

        return $fallback;
    }

    private function policyFrom(mixed $value): ?VisitPaymentTimingPolicy
    {
        return is_string($value) ? VisitPaymentTimingPolicy::tryFrom($value) : null;
    }

    private function boolean(string $key, bool $fallback): bool
    {
        return (bool) $this->setting($key, $fallback);
    }

    private function setting(string $key, mixed $fallback): mixed
    {
        try {
            return Setting::getValue(self::GROUP, $key, $fallback);
        } catch (Throwable $exception) {
            Log::warning('Payment timing settings are unavailable; using configuration fallback.', [
                'key' => $key,
                'exception' => $exception::class,
            ]);

            return $fallback;
        }
    }
}
