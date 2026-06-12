<?php

namespace App\Enums;

enum BedStatus: string
{
    case AVAILABLE = 'available';
    case OCCUPIED = 'occupied';
    case MAINTENANCE = 'maintenance';
    case RESERVED = 'reserved';

    public function label(): string
    {
        return match ($this) {
            self::AVAILABLE => 'Available',
            self::OCCUPIED => 'Occupied',
            self::MAINTENANCE => 'Maintenance',
            self::RESERVED => 'Reserved',
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
        };
    }
}
