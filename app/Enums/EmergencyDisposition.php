<?php

namespace App\Enums;

enum EmergencyDisposition: string
{
    case DISCHARGED_HOME            = 'discharged_home';
    case ADMITTED_TO_WARD           = 'admitted_to_ward';
    case REFERRED_OUT               = 'referred_out';
    case TRANSFERRED_TO_OPD         = 'transferred_to_opd';
    case TRANSFERRED_TO_THEATRE     = 'transferred_to_theatre';
    case LEFT_AGAINST_MEDICAL_ADVICE = 'left_against_medical_advice';
    case DECEASED                   = 'deceased';
    case ABSCONDED                  = 'absconded';
    case CANCELLED                  = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DISCHARGED_HOME             => 'Discharged Home',
            self::ADMITTED_TO_WARD            => 'Admitted to Ward',
            self::REFERRED_OUT                => 'Referred Out',
            self::TRANSFERRED_TO_OPD          => 'Transferred to OPD',
            self::TRANSFERRED_TO_THEATRE      => 'Transferred to Theatre',
            self::LEFT_AGAINST_MEDICAL_ADVICE => 'Left Against Medical Advice',
            self::DECEASED                    => 'Deceased',
            self::ABSCONDED                   => 'Absconded',
            self::CANCELLED                   => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DISCHARGED_HOME             => 'success',
            self::ADMITTED_TO_WARD            => 'info',
            self::REFERRED_OUT                => 'warning',
            self::TRANSFERRED_TO_OPD          => 'primary',
            self::TRANSFERRED_TO_THEATRE      => 'purple',
            self::LEFT_AGAINST_MEDICAL_ADVICE => 'warning',
            self::DECEASED                    => 'dark',
            self::ABSCONDED                   => 'secondary',
            self::CANCELLED                   => 'danger',
        };
    }
}
