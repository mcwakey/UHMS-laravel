<?php

namespace App\Enums;

enum LaborDangerSign: string
{
    case FETAL_DISTRESS = 'fetal_distress';
    case SEVERE_BLEEDING = 'severe_bleeding';
    case HIGH_BLOOD_PRESSURE = 'high_blood_pressure';
    case CONVULSIONS = 'convulsions';
    case FEVER = 'fever';
    case PROLONGED_LABOR = 'prolonged_labor';
    case OBSTRUCTED_LABOR_SUSPECTED = 'obstructed_labor_suspected';
    case SEVERE_ABDOMINAL_PAIN = 'severe_abdominal_pain';
    case RUPTURED_UTERUS_SUSPECTED = 'ruptured_uterus_suspected';
    case MATERNAL_EXHAUSTION = 'maternal_exhaustion';

    public function label(): string
    {
        return __('maternity.labor_danger_signs.' . $this->value);
    }
}
