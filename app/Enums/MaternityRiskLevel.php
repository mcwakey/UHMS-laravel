<?php

namespace App\Enums;

enum MaternityRiskLevel: string
{
    case LOW = 'low';
    case MODERATE = 'moderate';
    case HIGH = 'high';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return __('maternity.risk_levels.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'success',
            self::MODERATE => 'warning',
            self::HIGH, self::EMERGENCY => 'danger',
        };
    }
}
