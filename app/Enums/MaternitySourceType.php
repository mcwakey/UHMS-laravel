<?php

namespace App\Enums;

enum MaternitySourceType: string
{
    case PREGNANCY_PROFILE = 'pregnancy_profile';
    case MATERNITY_CASE = 'maternity_case';
    case VISIT = 'visit';
    case ADMISSION = 'admission';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return __('maternity.source_types.' . $this->value);
    }
}
