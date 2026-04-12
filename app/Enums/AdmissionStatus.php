<?php

namespace App\Enums;

enum AdmissionStatus: string
{
    case ADMITTED = 'admitted';
    case ON_LEAVE = 'on_leave';
    case DISCHARGED = 'discharged';
    case TRANSFERRED = 'transferred';
    case DECEASED = 'deceased';

    public function label(): string
    {
        return match ($this) {
            self::ADMITTED => 'Admitted',
            self::ON_LEAVE => 'On Leave',
            self::DISCHARGED => 'Discharged',
            self::TRANSFERRED => 'Transferred',
            self::DECEASED => 'Deceased',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ADMITTED => 'primary',
            self::ON_LEAVE => 'warning',
            self::DISCHARGED => 'success',
            self::TRANSFERRED => 'info',
            self::DECEASED => 'dark',
        };
    }
}
