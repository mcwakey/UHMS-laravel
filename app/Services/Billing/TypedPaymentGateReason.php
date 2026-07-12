<?php

namespace App\Services\Billing;

/**
 * Stable machine reason codes for typed payment-gate decisions (Payment Timing
 * Policy Phase 8). The codes are stored on BillingPolicyDecision::$reason; the
 * user-facing message is localised via {@see message()}.
 */
final class TypedPaymentGateReason
{
    public const TYPED_PREPAYMENT_REQUIRED = 'TYPED_PREPAYMENT_REQUIRED';
    public const TYPED_PREPAYMENT_SETTLED = 'TYPED_PREPAYMENT_SETTLED';
    public const TYPED_PAY_AFTER_SERVICES_ALLOWED = 'TYPED_PAY_AFTER_SERVICES_ALLOWED';
    public const TYPED_RUNNING_BILL_ALLOWED = 'TYPED_RUNNING_BILL_ALLOWED';
    public const APPROVED_ARRANGEMENT_PREPAYMENT_REQUIRED = 'APPROVED_ARRANGEMENT_PREPAYMENT_REQUIRED';
    public const APPROVED_ARRANGEMENT_DEFERRED_SETTLEMENT = 'APPROVED_ARRANGEMENT_DEFERRED_SETTLEMENT';
    public const APPROVED_ARRANGEMENT_RUNNING_BILL = 'APPROVED_ARRANGEMENT_RUNNING_BILL';
    public const APPROVED_ARRANGEMENT_INELIGIBLE = 'APPROVED_ARRANGEMENT_INELIGIBLE';
    public const TYPED_OPERATION_UNSUPPORTED = 'TYPED_OPERATION_UNSUPPORTED';
    public const TYPED_EMERGENCY_FALLBACK = 'TYPED_EMERGENCY_FALLBACK';
    public const TYPED_FAILURE_LEGACY_FALLBACK = 'TYPED_FAILURE_LEGACY_FALLBACK';
    public const TYPED_LEGACY_OVERRIDE_CONFLICT = 'TYPED_LEGACY_OVERRIDE_CONFLICT';

    /** Localised, user-facing message for a reason code (falls back to the code). */
    public static function message(string $code): string
    {
        $key = 'payment_timing_cutover.reasons.'.$code;
        $message = __($key);

        return $message === $key ? $code : $message;
    }
}
