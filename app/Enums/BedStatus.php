<?php

namespace App\Enums;

enum BedStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case MAINTENANCE = 'maintenance';
    case RESERVED = 'reserved';
    case CLEANING = 'cleaning';
    case BLOCKED = 'blocked';
    case ISOLATION = 'isolation';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::OCCUPIED => 'Occupied',
            self::MAINTENANCE => 'Maintenance',
            self::RESERVED => 'Reserved',
            self::CLEANING => 'Cleaning',
            self::BLOCKED => 'Blocked',
            self::ISOLATION => 'Isolation',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::AVAILABLE => 'success',
            self::OCCUPIED => 'danger',
            self::MAINTENANCE => 'warning',
            self::RESERVED => 'info',
            self::CLEANING => 'cyan',
            self::BLOCKED => 'dark',
            self::ISOLATION => 'purple',
        };
    }
}
