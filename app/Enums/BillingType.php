<?php

namespace App\Enums;

enum BillingType: string
{
    case CASH = 'cash';
    case NHIS = 'nhis';
    case CORPORATE = 'corporate';
    case MIXED = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::NHIS => 'NHIS',
            self::CORPORATE => 'Corporate',
            self::MIXED => 'Mixed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CASH => 'success',
            self::NHIS => 'primary',
            self::CORPORATE => 'info',
            self::MIXED => 'warning',
        };
    }
}
