<?php

namespace App\Enums;

enum UterusCondition: string
{
    case FIRM = 'firm';
    case BOGGY = 'boggy';
    case TENDER = 'tender';
    case ENLARGED = 'enlarged';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.uterus_conditions.' . $this->value);
    }
}
