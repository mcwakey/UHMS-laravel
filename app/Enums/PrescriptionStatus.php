<?php

namespace App\Enums;

enum PrescriptionStatus: string
{
    case PENDING = 'pending';
    case PARTIALLY_SELECTED = 'partially_selected';
    case PARTIALLY_BILLED = 'partially_billed';
    case BILLED = 'billed';
    case DISPENSED = 'dispensed';
    case PARTIALLY_DISPENSED = 'partially_dispensed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::PARTIALLY_SELECTED => 'Partially Selected',
            self::PARTIALLY_BILLED => 'Partially Billed',
            self::BILLED => 'Billed',
            self::DISPENSED => 'Dispensed',
            self::PARTIALLY_DISPENSED => 'Partially Dispensed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PARTIALLY_SELECTED => 'secondary',
            self::PARTIALLY_BILLED => 'info',
            self::BILLED => 'primary',
            self::DISPENSED => 'success',
            self::PARTIALLY_DISPENSED => 'info',
            self::CANCELLED => 'danger',
        };
    }
}
