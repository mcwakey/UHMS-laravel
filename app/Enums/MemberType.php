<?php

namespace App\Enums;

enum MemberType: string
{
    case HOLDER      = 'holder';
    case BENEFICIARY = 'beneficiary';

    public function label(): string
    {
        return match ($this) {
            self::HOLDER      => 'Card Holder',
            self::BENEFICIARY => 'Beneficiary',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::HOLDER      => 'primary',
            self::BENEFICIARY => 'info',
        };
    }
}
