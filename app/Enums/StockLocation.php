<?php

namespace App\Enums;

enum StockLocation: string
{
    case STORE = 'store';
    case PHARMACY = 'pharmacy';

    public function label(): string
    {
        return match ($this) {
            self::STORE => 'Store',
            self::PHARMACY => 'Pharmacy',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::STORE => 'info',
            self::PHARMACY => 'primary',
        };
    }
}
