<?php

namespace App\Services\Billing;

/**
 * Immutable result of a billing-policy evaluation for an invoice item / action.
 * Returned by BillingPolicyService and consumed by PaymentGateService and the UI.
 */
class BillingPolicyDecision
{
    public function __construct(
        public readonly bool $allowed,
        public readonly string $mode,          // BillingPolicyService::MODE_*
        public readonly string $reason,        // machine-ish code, e.g. RUNNING_BILL, ITEM_PAID, ITEM_UNPAID
        public readonly string $message,       // friendly, user-facing
        public readonly bool $requiresPayment = false,
        public readonly bool $requiresOverride = false,
        public readonly bool $overrideUsed = false,
        public readonly string $settlementStatus = 'UNKNOWN',
    ) {}

    public static function allow(string $mode, string $reason, string $message, array $extra = []): self
    {
        return new self(
            allowed: true,
            mode: $mode,
            reason: $reason,
            message: $message,
            requiresPayment: $extra['requiresPayment'] ?? false,
            requiresOverride: $extra['requiresOverride'] ?? false,
            overrideUsed: $extra['overrideUsed'] ?? false,
            settlementStatus: $extra['settlementStatus'] ?? 'UNKNOWN',
        );
    }

    public static function block(string $mode, string $reason, string $message, array $extra = []): self
    {
        return new self(
            allowed: false,
            mode: $mode,
            reason: $reason,
            message: $message,
            requiresPayment: $extra['requiresPayment'] ?? true,
            requiresOverride: $extra['requiresOverride'] ?? false,
            overrideUsed: $extra['overrideUsed'] ?? false,
            settlementStatus: $extra['settlementStatus'] ?? 'UNKNOWN',
        );
    }

    public function toArray(): array
    {
        return [
            'allowed' => $this->allowed,
            'mode' => $this->mode,
            'reason' => $this->reason,
            'message' => $this->message,
            'requires_payment' => $this->requiresPayment,
            'requires_override' => $this->requiresOverride,
            'override_used' => $this->overrideUsed,
            'settlement_status' => $this->settlementStatus,
        ];
    }
}
