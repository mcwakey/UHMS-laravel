<?php

namespace App\Enums;

enum NewbornOutcome: string
{
    case LIVE_BIRTH = 'live_birth';
    case STILLBIRTH = 'stillbirth';
    case NEONATAL_DEATH = 'neonatal_death';
    case TRANSFERRED = 'transferred';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.newborn_outcomes.' . $this->value);
    }
}
