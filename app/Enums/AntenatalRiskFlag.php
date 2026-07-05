<?php

namespace App\Enums;

enum AntenatalRiskFlag: string
{
    case HIGH_BLOOD_PRESSURE = 'high_blood_pressure';
    case LOW_HAEMOGLOBIN = 'low_haemoglobin';
    case PREVIOUS_CAESAREAN = 'previous_caesarean';
    case PREVIOUS_POSTPARTUM_HAEMORRHAGE = 'previous_postpartum_haemorrhage';
    case MULTIPLE_PREGNANCY = 'multiple_pregnancy';
    case DIABETES_RISK = 'diabetes_risk';
    case HYPERTENSIVE_DISORDER_RISK = 'hypertensive_disorder_risk';
    case YOUNG_MOTHER = 'young_mother';
    case ADVANCED_MATERNAL_AGE = 'advanced_maternal_age';
    case GRAND_MULTIPARITY = 'grand_multiparity';
    case RHESUS_NEGATIVE = 'rhesus_negative';
    case BREECH_OR_ABNORMAL_PRESENTATION = 'breech_or_abnormal_presentation';

    public function label(): string
    {
        return __('maternity.anc_risk_flags.' . $this->value);
    }
}
