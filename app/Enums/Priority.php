<?php

namespace App\Enums;

enum Priority: string
{
    case NORMAL = 'normal';
    case URGENT = 'urgent';
    case EMERGENCY = 'emergency';

    public function label(): string
    {
        return match ($this) {
            self::NORMAL => 'Normal',
            self::URGENT => 'Urgent',
            self::EMERGENCY => 'Emergency',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NORMAL => 'success',
            self::URGENT => 'warning',
            self::EMERGENCY => 'danger',
        };
    }
}
