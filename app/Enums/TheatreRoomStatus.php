<?php

namespace App\Enums;

enum TheatreRoomStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case OCCUPIED = 'OCCUPIED';
    case SCHEDULED = 'SCHEDULED';
    case CLEANING = 'CLEANING';
    case MAINTENANCE = 'MAINTENANCE';
    case OUT_OF_SERVICE = 'OUT_OF_SERVICE';
    case RESERVED = 'RESERVED';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::OCCUPIED => 'Occupied',
            self::SCHEDULED => 'Scheduled',
            self::CLEANING => 'Cleaning',
            self::MAINTENANCE => 'Maintenance',
            self::OUT_OF_SERVICE => 'Out of Service',
            self::RESERVED => 'Reserved',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . strtolower($this->value));
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::OCCUPIED => 'danger',
            self::SCHEDULED => 'primary',
            self::CLEANING => 'info',
            self::MAINTENANCE => 'warning',
            self::OUT_OF_SERVICE => 'dark',
            self::RESERVED => 'secondary',
        };
    }

    public function isSchedulable(): bool
    {
        return in_array($this, [self::AVAILABLE, self::SCHEDULED, self::RESERVED], true);
    }
}