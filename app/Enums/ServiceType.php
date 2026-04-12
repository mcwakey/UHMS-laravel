<?php

namespace App\Enums;

enum ServiceType: string
{
    case CONSULTATION = 'consultation';
    case INVESTIGATION = 'investigation';
    case PROCEDURE = 'procedure';
    case MEDICATION = 'medication';
    case BED_CHARGE = 'bed_charge';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CONSULTATION => 'Consultation',
            self::INVESTIGATION => 'Investigation',
            self::PROCEDURE => 'Procedure',
            self::MEDICATION => 'Medication',
            self::BED_CHARGE => 'Bed Charge',
            self::OTHER => 'Other',
        };
    }
}
