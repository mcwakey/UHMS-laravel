<?php

namespace App\Enums;

enum MaternityCaseType: string
{
    case PREGNANCY_PROFILE = 'pregnancy_profile';
    case ANTENATAL = 'antenatal';
    case MATERNITY_ADMISSION = 'maternity_admission';
    case LABOR_OBSERVATION = 'labor_observation';
    case POSTNATAL_OBSERVATION = 'postnatal_observation';
    case EMERGENCY_REFERRAL = 'emergency_referral';

    public function label(): string
    {
        return __('maternity.case_types.' . $this->value);
    }
}
