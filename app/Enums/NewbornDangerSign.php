<?php

namespace App\Enums;

enum NewbornDangerSign: string
{
    case DIFFICULTY_BREATHING = 'difficulty_breathing';
    case FEVER = 'fever';
    case HYPOTHERMIA = 'hypothermia';
    case POOR_FEEDING = 'poor_feeding';
    case CONVULSIONS = 'convulsions';
    case LETHARGY = 'lethargy';
    case JAUNDICE = 'jaundice';
    case BLEEDING = 'bleeding';
    case CYANOSIS = 'cyanosis';
    case INFECTION_SIGNS = 'infection_signs';

    public function label(): string
    {
        return __('maternity.newborn_danger_signs.' . $this->value);
    }
}
