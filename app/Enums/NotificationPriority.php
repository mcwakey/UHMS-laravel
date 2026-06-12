<?php

namespace App\Enums;

enum NotificationPriority: string
{
    case LOW = 'LOW';
    case NORMAL = 'NORMAL';
    case HIGH = 'HIGH';
    case URGENT = 'URGENT';
    case CRITICAL = 'CRITICAL';

    public function label(): string
    {
        return ucfirst(strtolower($this->value));
    }

    public function translatedLabel(): string
    {
        return __('statuses.priority.' . strtolower($this->value));
    }

    public function color(): string
    {
        return match ($this) {
            self::LOW => 'secondary',
            self::NORMAL => 'primary',
            self::HIGH => 'warning',
            self::URGENT => 'danger',
            self::CRITICAL => 'dark',
        };
    }
}
