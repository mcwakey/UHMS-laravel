<?php

namespace App\Enums;

enum NewbornBreathingStatus: string
{
    case NORMAL = 'normal';
    case FAST_BREATHING = 'fast_breathing';
    case DIFFICULTY = 'difficulty';
    case APNOEA = 'apnoea';
    case SUPPORTED = 'supported';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.newborn_breathing_statuses.' . $this->value);
    }
}
