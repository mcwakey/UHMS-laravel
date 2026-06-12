<?php

namespace App\Enums;

enum BedType: string
{
    case STANDARD = 'standard';
    case SEMI_PRIVATE = 'semi_private';
    case PRIVATE = 'private';
    case ICU = 'icu';
    case PEDIATRIC = 'pediatric';

    public function label(): string
    {
        return match ($this) {
            self::STANDARD => 'Standard',
            self::SEMI_PRIVATE => 'Semi-Private',
            self::PRIVATE => 'Private',
            self::ICU => 'ICU',
            self::PEDIATRIC => 'Pediatric',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }
}
