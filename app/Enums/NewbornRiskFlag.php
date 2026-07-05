<?php

namespace App\Enums;

enum NewbornRiskFlag: string
{
    case LOW_BIRTH_WEIGHT = 'low_birth_weight';
    case PREMATURE = 'premature';
    case RESUSCITATION_REQUIRED = 'resuscitation_required';
    case CONGENITAL_CONCERN = 'congenital_concern';
    case MATERNAL_HIGH_RISK = 'maternal_high_risk';
    case MECONIUM_EXPOSURE = 'meconium_exposure';
    case MULTIPLE_BIRTH = 'multiple_birth';
    case POOR_APGAR = 'poor_apgar';
    case TEMPERATURE_INSTABILITY = 'temperature_instability';
    case FEEDING_DIFFICULTY = 'feeding_difficulty';

    public function label(): string
    {
        return __('maternity.newborn_risk_flags.' . $this->value);
    }
}
