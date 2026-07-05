<?php

namespace App\Enums;

enum MaternalCondition: string
{
    case STABLE = 'stable';
    case MONITORING_REQUIRED = 'monitoring_required';
    case CRITICAL = 'critical';
    case TRANSFERRED = 'transferred';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.maternal_conditions.' . $this->value);
    }
}
