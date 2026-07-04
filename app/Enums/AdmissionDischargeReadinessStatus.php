<?php

namespace App\Enums;

enum AdmissionDischargeReadinessStatus: string
{
    case READY = 'ready';
    case WARNING = 'warning';
    case BLOCKED = 'blocked';
    case UNAVAILABLE = 'unavailable';

    public function label(): string
    {
        return __('admissions.discharge_readiness_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::READY => 'success',
            self::WARNING => 'warning',
            self::BLOCKED => 'danger',
            self::UNAVAILABLE => 'secondary',
        };
    }
}
