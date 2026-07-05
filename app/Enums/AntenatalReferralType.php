<?php

namespace App\Enums;

enum AntenatalReferralType: string
{
    case NONE = 'none';
    case CONSULTATION = 'consultation';
    case EMERGENCY = 'emergency';
    case ADMISSION = 'admission';
    case MATERNITY_ADMISSION = 'maternity_admission';
    case EXTERNAL_REFERRAL = 'external_referral';

    public function label(): string
    {
        return __('maternity.anc_referral_types.' . $this->value);
    }
}
