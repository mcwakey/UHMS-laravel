<?php

namespace App\Enums;

enum BleedingStatus: string
{
    case NORMAL = 'normal';
    case LIGHT = 'light';
    case MODERATE = 'moderate';
    case HEAVY = 'heavy';
    case CLOTS = 'clots';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.bleeding_statuses.' . $this->value);
    }
}
