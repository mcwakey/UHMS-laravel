<?php

namespace App\Enums;

enum InsuranceType: string
{
    case SELF      = 'self';       // Cash & Carry — patient pays 100%
    case NHIA      = 'nhia';      // Legacy alias kept for backward compatibility
    case PRIVATE   = 'private';
    case CORPORATE = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::SELF      => 'SELF SPONSORED',
            self::NHIA      => 'NHIA',
            self::PRIVATE   => 'PRIVATE',
            self::CORPORATE => 'CORPORATE',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SELF      => 'danger',
            self::NHIA      => 'primary',
            self::PRIVATE   => 'info',
            self::CORPORATE => 'warning',
        };
    }

    /** Returns true if this type is government/insurance-covered (not self-pay). */
    public function isCovered(): bool
    {
        return $this !== self::SELF;
    }
}
