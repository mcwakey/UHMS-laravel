<?php

namespace App\Enums;

enum DepartmentType: string
{
    case CONSULTATION = 'consultation';
    case INVESTIGATION = 'investigation';
    case PROCEDURE = 'procedure';
    case TREATMENT = 'treatment';
    case PHARMACY = 'pharmacy';
    case RADIOLOGY = 'radiology';
    case EMERGENCY = 'emergency';
    case SUPPORT = 'support';
    case ADMINISTRATIVE = 'administrative';

    public function label(): string
    {
        return match ($this) {
            self::CONSULTATION => 'Consultation',
            self::INVESTIGATION => 'Investigation',
            self::PROCEDURE => 'Procedure',
            self::TREATMENT => 'Treatment',
            self::PHARMACY => 'Pharmacy',
            self::RADIOLOGY => 'Radiology',
            self::EMERGENCY => 'Emergency',
            self::SUPPORT => 'Support',
            self::ADMINISTRATIVE => 'Administrative',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::CONSULTATION => 'primary',
            self::INVESTIGATION => 'info',
            self::PROCEDURE => 'warning',
            self::TREATMENT => 'success',
            self::PHARMACY => 'purple',
            self::RADIOLOGY => 'secondary',
            self::EMERGENCY => 'danger',
            self::SUPPORT => 'dark',
            self::ADMINISTRATIVE => 'light',
        };
    }

    /**
     * Map department type to the corresponding VisitStatus when a patient is sent here.
     */
    public function toVisitStatus(): \App\Enums\VisitStatus
    {
        return match ($this) {
            self::CONSULTATION  => \App\Enums\VisitStatus::CONSULTING,
            self::INVESTIGATION => \App\Enums\VisitStatus::LAB,
            self::RADIOLOGY     => \App\Enums\VisitStatus::LAB,
            self::PHARMACY      => \App\Enums\VisitStatus::PHARMACY,
            self::EMERGENCY     => \App\Enums\VisitStatus::EMERGENCY,
            self::ADMINISTRATIVE => \App\Enums\VisitStatus::BILLING,
            default             => \App\Enums\VisitStatus::CONSULTING,
        };
    }
}
