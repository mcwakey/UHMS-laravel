<?php

namespace App\Enums;

enum PostnatalMotherDangerSign: string
{
    case HEAVY_BLEEDING = 'heavy_bleeding';
    case SEVERE_HEADACHE = 'severe_headache';
    case BLURRED_VISION = 'blurred_vision';
    case FEVER = 'fever';
    case SEVERE_ABDOMINAL_PAIN = 'severe_abdominal_pain';
    case CONVULSIONS = 'convulsions';
    case FOUL_DISCHARGE = 'foul_discharge';
    case BREATHLESSNESS = 'breathlessness';
    case CHEST_PAIN = 'chest_pain';
    case SEVERE_WEAKNESS = 'severe_weakness';
    case WOUND_INFECTION = 'wound_infection';
    case MENTAL_HEALTH_CONCERN = 'mental_health_concern';

    public function label(): string
    {
        return __('maternity.postnatal_mother_danger_signs.' . $this->value);
    }
}
