<?php

namespace App\Enums;

enum TheatreRoomBlockType: string
{
    case CLEANING = 'CLEANING';
    case MAINTENANCE = 'MAINTENANCE';
    case STERILIZATION = 'STERILIZATION';
    case EQUIPMENT_FAILURE = 'EQUIPMENT_FAILURE';
    case RESERVED_EMERGENCY_SLOT = 'RESERVED_EMERGENCY_SLOT';
    case OTHER = 'OTHER';

    public function label(): string
    {
        return match ($this) {
            self::CLEANING => 'Cleaning',
            self::MAINTENANCE => 'Maintenance',
            self::STERILIZATION => 'Sterilization',
            self::EQUIPMENT_FAILURE => 'Equipment Failure',
            self::RESERVED_EMERGENCY_SLOT => 'Reserved Emergency Slot',
            self::OTHER => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CLEANING => 'info',
            self::MAINTENANCE => 'warning',
            self::STERILIZATION => 'primary',
            self::EQUIPMENT_FAILURE => 'danger',
            self::RESERVED_EMERGENCY_SLOT => 'secondary',
            self::OTHER => 'dark',
        };
    }
}