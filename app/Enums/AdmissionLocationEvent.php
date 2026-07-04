<?php

namespace App\Enums;

enum AdmissionLocationEvent: string
{
    case ADMITTED = 'admitted';
    case BED_RESERVED = 'bed_reserved';
    case BED_RELEASED = 'bed_released';
    case BED_TRANSFERRED = 'bed_transferred';
    case WARD_TRANSFERRED = 'ward_transferred';
    case DISCHARGED = 'discharged';

    public function label(): string
    {
        return __('admissions.location_events.' . $this->value);
    }
}
