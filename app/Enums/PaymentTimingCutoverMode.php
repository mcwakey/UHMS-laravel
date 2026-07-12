<?php

namespace App\Enums;

/**
 * Master operational-cutover mode for typed payment-timing enforcement (Payment
 * Timing Policy Phase 8).
 *
 * DISABLED (the deployment default) returns legacy decisions unchanged and adds
 * no arrangement/typed queries. OBSERVE resolves a would-be typed decision but
 * keeps legacy authoritative. ACTIVE lets operations explicitly configured as
 * `typed` return typed decisions; everything else stays legacy. Invalid values
 * resolve to DISABLED, and an environment kill switch always wins.
 */
enum PaymentTimingCutoverMode: string
{
    case DISABLED = 'disabled';
    case OBSERVE = 'observe';
    case ACTIVE = 'active';

    public function label(): string
    {
        return __('payment_timing_cutover.modes.'.$this->value);
    }

    public static function fromStorage(mixed $value): self
    {
        return is_string($value) ? (self::tryFrom($value) ?? self::DISABLED) : self::DISABLED;
    }

    /** @return array<int, self> */
    public static function selectable(): array
    {
        return self::cases();
    }
}
