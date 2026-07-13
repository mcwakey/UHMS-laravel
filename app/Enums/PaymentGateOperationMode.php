<?php

namespace App\Enums;

/**
 * Administrative mode metadata for a registered payment-gate operation.
 *
 * Runtime payment permission is now centralised by Payment Timing Policies when
 * that feature is enabled. These values remain for diagnostics, audit history
 * and compatibility screens.
 */
enum PaymentGateOperationMode: string
{
    /** No payment-policy gate is invoked for the operation. */
    case DISABLED = 'disabled';

    /** The operation may record bounded diagnostics but never changes the result. */
    case OBSERVE = 'observe';

    /** Compatibility metadata for an existing payment-gate call. */
    case LEGACY = 'legacy';

    /** Metadata indicating the operation has been reviewed for typed policy behaviour. */
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
