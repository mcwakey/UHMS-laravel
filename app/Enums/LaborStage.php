<?php

namespace App\Enums;

enum LaborStage: string
{
    case LATENT = 'latent';
    case FIRST_STAGE = 'first_stage';
    case SECOND_STAGE = 'second_stage';
    case THIRD_STAGE = 'third_stage';
    case RECOVERY = 'recovery';
    case COMPLETED = 'completed';
    case REFERRED = 'referred';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.labor_stages.' . $this->value);
    }
}
