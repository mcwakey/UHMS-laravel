<?php

namespace App\Enums;

enum JaundiceStatus: string
{
    case NONE = 'none';
    case MILD = 'mild';
    case MODERATE = 'moderate';
    case SEVERE = 'severe';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.jaundice_statuses.' . $this->value);
    }
}
