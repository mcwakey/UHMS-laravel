<?php

namespace App\Enums;

enum BedReservationStatus: string
{
    case ACTIVE = 'active';
    case FULFILLED = 'fulfilled';
    case CANCELLED = 'cancelled';
    case EXPIRED = 'expired';
    case RELEASED = 'released';

    public function label(): string
    {
        return __('admissions.bed_reservation_statuses.' . $this->value);
    }
}
