<?php

namespace App\Enums;

enum LaborRiskFlag: string
{
    case PREVIOUS_CAESAREAN = 'previous_caesarean';
    case MULTIPLE_PREGNANCY = 'multiple_pregnancy';
    case BREECH_PRESENTATION = 'breech_presentation';
    case PREMATURE_LABOR = 'premature_labor';
    case POST_TERM = 'post_term';
    case HYPERTENSION = 'hypertension';
    case DIABETES_RISK = 'diabetes_risk';
    case LOW_HAEMOGLOBIN = 'low_haemoglobin';
    case ABNORMAL_FETAL_HEART_RATE = 'abnormal_fetal_heart_rate';
    case MECONIUM_LIQUOR = 'meconium_liquor';

    public function label(): string
    {
        return __('maternity.labor_risk_flags.' . $this->value);
    }
}
