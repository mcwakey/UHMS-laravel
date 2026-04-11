<?php

namespace App\Enums;

enum PrescriptionStatus: string
{
    case PENDING = 'pending';
    case DISPENSED = 'dispensed';
    case PARTIALLY_DISPENSED = 'partially_dispensed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::DISPENSED => 'Dispensed',
            self::PARTIALLY_DISPENSED => 'Partially Dispensed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::DISPENSED => 'success',
            self::PARTIALLY_DISPENSED => 'info',
            self::CANCELLED => 'danger',
        };
    }
}
