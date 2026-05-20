<?php

namespace App\Enums;

enum EmergencyArrivalMode: string
{
    case WALK_IN               = 'walk_in';
    case AMBULANCE             = 'ambulance';
    case POLICE                = 'police';
    case REFERRAL              = 'referral';
    case BROUGHT_BY_RELATIVE   = 'brought_by_relative';
    case TRANSFER_FROM_OPD     = 'transfer_from_opd';
    case TRANSFER_FROM_WARD    = 'transfer_from_ward';
    case OTHER                 = 'other';

    public function label(): string
    {
        return match ($this) {
            self::WALK_IN             => 'Walk-in',
            self::AMBULANCE           => 'Ambulance',
            self::POLICE              => 'Police',
            self::REFERRAL            => 'Referral',
            self::BROUGHT_BY_RELATIVE => 'Brought by relative',
            self::TRANSFER_FROM_OPD   => 'Transfer from OPD',
            self::TRANSFER_FROM_WARD  => 'Transfer from Ward',
            self::OTHER               => 'Other',
        };
    }
}
