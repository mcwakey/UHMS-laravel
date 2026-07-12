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
        // Phase 8 — additive decision-authority metadata (backward compatible;
        // existing consumers ignore these). 'legacy' by default.
        public readonly string $decisionAuthority = 'legacy',
        public readonly ?string $paymentTimingPolicy = null,
        public readonly ?string $paymentTimingSource = null,
        public readonly ?string $operation = null,
        public readonly ?string $cutoverMode = null,
        public readonly ?string $fallbackReason = null,
        public readonly ?int $approvedArrangementId = null,
    ) {}

    public static function allow(string $mode, string $reason, string $message, array $extra = []): self
    {
        return self::make(true, $mode, $reason, $message, $extra);
    }

    public static function block(string $mode, string $reason, string $message, array $extra = []): self
    {
        return self::make(false, $mode, $reason, $message, $extra + ['requiresPayment' => $extra['requiresPayment'] ?? true]);
    }

    private static function make(bool $allowed, string $mode, string $reason, string $message, array $extra): self
    {
        return new self(
            allowed: $allowed,
            mode: $mode,
            reason: $reason,
            message: $message,
            requiresPayment: $extra['requiresPayment'] ?? false,
            requiresOverride: $extra['requiresOverride'] ?? false,
            overrideUsed: $extra['overrideUsed'] ?? false,
            settlementStatus: $extra['settlementStatus'] ?? 'UNKNOWN',
            decisionAuthority: $extra['decisionAuthority'] ?? 'legacy',
            paymentTimingPolicy: $extra['paymentTimingPolicy'] ?? null,
            paymentTimingSource: $extra['paymentTimingSource'] ?? null,
            operation: $extra['operation'] ?? null,
            cutoverMode: $extra['cutoverMode'] ?? null,
            fallbackReason: $extra['fallbackReason'] ?? null,
            approvedArrangementId: $extra['approvedArrangementId'] ?? null,
        );
    }

    /** Return a copy of a decision tagged with Phase 8 authority metadata. */
    public function withAuthority(array $meta): self
    {
        return new self(
            allowed: $this->allowed,
            mode: $this->mode,
            reason: $this->reason,
            message: $this->message,
            requiresPayment: $this->requiresPayment,
            requiresOverride: $this->requiresOverride,
            overrideUsed: $this->overrideUsed,
            settlementStatus: $this->settlementStatus,
            decisionAuthority: $meta['decisionAuthority'] ?? $this->decisionAuthority,
            paymentTimingPolicy: $meta['paymentTimingPolicy'] ?? $this->paymentTimingPolicy,
            paymentTimingSource: $meta['paymentTimingSource'] ?? $this->paymentTimingSource,
            operation: $meta['operation'] ?? $this->operation,
            cutoverMode: $meta['cutoverMode'] ?? $this->cutoverMode,
            fallbackReason: $meta['fallbackReason'] ?? $this->fallbackReason,
            approvedArrangementId: $meta['approvedArrangementId'] ?? $this->approvedArrangementId,
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
            'decision_authority' => $this->decisionAuthority,
            'payment_timing_policy' => $this->paymentTimingPolicy,
            'payment_timing_source' => $this->paymentTimingSource,
            'operation' => $this->operation,
            'cutover_mode' => $this->cutoverMode,
            'fallback_reason' => $this->fallbackReason,
        ];
    }
}
