<?php

namespace App\Enums;

enum InvestigationItemCategory: string
{
    case REAGENT     = 'reagent';
    case TEST_KIT    = 'test_kit';
    case CONSUMABLE  = 'consumable';
    case RADIOLOGY   = 'radiology';
    case IMAGING     = 'imaging';
    case OTHER       = 'other';

    public function label(): string
    {
        return match ($this) {
            self::REAGENT    => 'Reagent',
            self::TEST_KIT   => 'Test Kit',
            self::CONSUMABLE => 'Consumable',
            self::RADIOLOGY  => 'Radiology Supply',
            self::IMAGING    => 'Imaging Supply',
            self::OTHER      => 'Other',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::REAGENT    => 'info',
            self::TEST_KIT   => 'primary',
            self::CONSUMABLE => 'secondary',
            self::RADIOLOGY  => 'warning',
            self::IMAGING    => 'purple',
            self::OTHER      => 'dark',
        };
    }
}
