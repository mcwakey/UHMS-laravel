<?php

namespace App\Enums;

enum StockLocation: string
{
    case STORE      = 'store';
    case PHARMACY   = 'pharmacy';
    case LABORATORY = 'laboratory';

    public function label(): string
    {
        return match ($this) {
            self::STORE      => 'Store',
            self::PHARMACY   => 'Pharmacy',
            self::LABORATORY => 'Laboratory',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::STORE      => 'info',
            self::PHARMACY   => 'primary',
            self::LABORATORY => 'success',
        };
    }
}
