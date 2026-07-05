<?php

namespace App\Enums;

enum PostnatalNewbornRiskFlag: string
{
    case LOW_BIRTH_WEIGHT = 'low_birth_weight';
    case PREMATURE = 'premature';
    case MULTIPLE_BIRTH = 'multiple_birth';
    case POOR_FEEDING = 'poor_feeding';
    case JAUNDICE = 'jaundice';
    case TEMPERATURE_INSTABILITY = 'temperature_instability';
    case CORD_CONCERN = 'cord_concern';
    case MATERNAL_HIGH_RISK = 'maternal_high_risk';
    case NEEDS_IMMUNISATION_REVIEW = 'needs_immunisation_review';
    case FOLLOW_UP_NEEDED = 'follow_up_needed';

    public function label(): string
    {
        return __('maternity.postnatal_newborn_risk_flags.' . $this->value);
    }
}
