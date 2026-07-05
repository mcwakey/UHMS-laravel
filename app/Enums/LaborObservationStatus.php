<?php

namespace App\Enums;

enum LaborObservationStatus: string
{
    case RECORDED = 'recorded';
    case REVIEWED = 'reviewed';
    case ESCALATED = 'escalated';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('maternity.labor_observation_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::RECORDED => 'primary',
            self::REVIEWED => 'success',
            self::ESCALATED => 'danger',
            self::CANCELLED => 'secondary',
        };
    }
}
