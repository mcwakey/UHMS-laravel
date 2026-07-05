<?php

namespace App\Enums;

enum FetalPresentation: string
{
    case UNKNOWN = 'unknown';
    case CEPHALIC = 'cephalic';
    case BREECH = 'breech';
    case TRANSVERSE = 'transverse';
    case OBLIQUE = 'oblique';
    case OTHER = 'other';

    public function label(): string
    {
        return __('maternity.fetal_presentations.' . $this->value);
    }
}
