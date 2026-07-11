<?php

namespace App\Enums;

/**
 * Configured enforcement state for a registered payment-gate operation
 * (Payment Timing Policy Phase 4).
 *
 * Deliberately has exactly three values. There is NO `typed` / `active` /
 * `enforce_typed` mode in this phase — typed enforcement is not operational and
 * will only be connected in a later, deliberate cutover phase.
 */
enum PaymentGateOperationMode: string
{
    /** No payment-policy gate is invoked for the operation. */
    case DISABLED = 'disabled';

    /** The operation may record bounded diagnostics but never changes the result. */
    case OBSERVE = 'observe';

    /** The operation uses its existing legacy gate behaviour (not typed enforcement). */
    case LEGACY = 'legacy';

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
