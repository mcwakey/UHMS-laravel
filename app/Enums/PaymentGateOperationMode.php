<?php

namespace App\Enums;

/**
 * Configured enforcement state for a registered payment-gate operation
 * (Payment Timing Policy Phase 4, extended in Phase 8).
 *
 * `typed` (Phase 8) makes the operation use operational typed enforcement — but
 * ONLY when the master cutover is active, the operation is registry-approved and
 * wired, any required compatibility acknowledgement is present, and the runtime
 * context is eligible. Otherwise the operation stays legacy/observe. The seeder
 * never selects `typed`, and it is invalid for the nine unwired operations.
 */
enum PaymentGateOperationMode: string
{
    /** No payment-policy gate is invoked for the operation. */
    case DISABLED = 'disabled';

    /** The operation may record bounded diagnostics but never changes the result. */
    case OBSERVE = 'observe';

    /** The operation uses its existing legacy gate behaviour (not typed enforcement). */
    case LEGACY = 'legacy';

    /** The operation uses operational typed enforcement (Phase 8; guarded by cutover). */
    case TYPED = 'typed';

    public function label(): string
    {
        return __('payment_gate.modes.'.$this->value);
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
