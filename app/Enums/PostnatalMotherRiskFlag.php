<?php

namespace App\Enums;

enum PostnatalMotherRiskFlag: string
{
    case PREVIOUS_PPH = 'previous_pph';
    case CAESAREAN_BIRTH = 'caesarean_birth';
    case HYPERTENSIVE_DISORDER = 'hypertensive_disorder';
    case ANAEMIA = 'anaemia';
    case INFECTION_RISK = 'infection_risk';
    case BREASTFEEDING_SUPPORT_NEEDED = 'breastfeeding_support_needed';
    case PAIN_UNCONTROLLED = 'pain_uncontrolled';
    case MOBILITY_LIMITATION = 'mobility_limitation';
    case PSYCHOSOCIAL_SUPPORT = 'psychosocial_support';

    public function label(): string
    {
        return __('maternity.postnatal_mother_risk_flags.' . $this->value);
    }
}
