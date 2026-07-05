<?php

namespace App\Enums;

enum PostnatalNewbornDangerSign: string
{
    case DIFFICULTY_BREATHING = 'difficulty_breathing';
    case FEVER = 'fever';
    case HYPOTHERMIA = 'hypothermia';
    case POOR_FEEDING = 'poor_feeding';
    case CONVULSIONS = 'convulsions';
    case LETHARGY = 'lethargy';
    case JAUNDICE = 'jaundice';
    case BLEEDING = 'bleeding';
    case CORD_INFECTION = 'cord_infection';
    case CYANOSIS = 'cyanosis';
    case VOMITING = 'vomiting';

    public function label(): string
    {
        return __('maternity.postnatal_newborn_danger_signs.' . $this->value);
    }
}
