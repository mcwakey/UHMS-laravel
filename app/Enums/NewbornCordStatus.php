<?php

namespace App\Enums;

enum NewbornCordStatus: string
{
    case CLEAN = 'clean';
    case BLEEDING = 'bleeding';
    case INFECTED = 'infected';
    case CLAMPED = 'clamped';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.newborn_cord_statuses.' . $this->value);
    }
}
