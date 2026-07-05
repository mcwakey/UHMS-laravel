<?php

namespace App\Enums;

enum NewbornCondition: string
{
    case WELL = 'well';
    case STABLE = 'stable';
    case OBSERVE = 'observe';
    case AT_RISK = 'at_risk';
    case CRITICAL = 'critical';
    case DECEASED = 'deceased';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.newborn_conditions.' . $this->value);
    }
}
