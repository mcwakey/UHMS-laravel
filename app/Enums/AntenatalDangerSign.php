<?php

namespace App\Enums;

enum AntenatalDangerSign: string
{
    case SEVERE_HEADACHE = 'severe_headache';
    case BLURRED_VISION = 'blurred_vision';
    case VAGINAL_BLEEDING = 'vaginal_bleeding';
    case SEVERE_ABDOMINAL_PAIN = 'severe_abdominal_pain';
    case REDUCED_FETAL_MOVEMENT = 'reduced_fetal_movement';
    case CONVULSIONS = 'convulsions';
    case FEVER = 'fever';
    case SWOLLEN_FACE_HANDS = 'swollen_face_hands';
    case LEAKING_LIQUOR = 'leaking_liquor';
    case BREATHLESSNESS = 'breathlessness';
    case SEVERE_VOMITING = 'severe_vomiting';

    public function label(): string
    {
        return __('maternity.anc_danger_signs.' . $this->value);
    }
}
