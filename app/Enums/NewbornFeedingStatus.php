<?php

namespace App\Enums;

enum NewbornFeedingStatus: string
{
    case NOT_STARTED = 'not_started';
    case BREASTFEEDING = 'breastfeeding';
    case EXPRESSED_MILK = 'expressed_milk';
    case FORMULA = 'formula';
    case MIXED = 'mixed';
    case DIFFICULTY = 'difficulty';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.newborn_feeding_statuses.' . $this->value);
    }
}
