<?php

namespace App\Enums;

enum BillingType: string
{
    case CASH = 'cash';
    case INSURANCE = 'insurance';
    case CORPORATE = 'corporate';
    case MIXED = 'mixed';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::INSURANCE => 'Insurance',
            self::CORPORATE => 'Corporate',
            self::MIXED => 'Mixed',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CASH => 'success',
            self::INSURANCE => 'primary',
            self::CORPORATE => 'info',
            self::MIXED => 'warning',
        };
    }
}
