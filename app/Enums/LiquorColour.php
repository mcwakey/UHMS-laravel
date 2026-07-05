<?php

namespace App\Enums;

enum LiquorColour: string
{
    case CLEAR = 'clear';
    case MECONIUM_STAINED = 'meconium_stained';
    case BLOOD_STAINED = 'blood_stained';
    case FOUL_SMELLING = 'foul_smelling';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.liquor_colours.' . $this->value);
    }
}
