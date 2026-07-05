<?php

namespace App\Enums;

enum WoundCondition: string
{
    case NOT_APPLICABLE = 'not_applicable';
    case INTACT = 'intact';
    case HEALING = 'healing';
    case DISCHARGE = 'discharge';
    case INFECTED = 'infected';
    case PAINFUL = 'painful';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.wound_conditions.' . $this->value);
    }
}
