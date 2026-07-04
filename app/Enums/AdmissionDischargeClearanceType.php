<?php

namespace App\Enums;

enum AdmissionDischargeClearanceType: string
{
    case CLINICAL = 'clinical';
    case NURSING = 'nursing';
    case MEDICATION = 'medication';
    case BILLING = 'billing';
    case BED_RELEASE = 'bed_release';
    case DOCUMENTATION = 'documentation';
    case FOLLOW_UP = 'follow_up';

    public function label(): string
    {
        return __('admissions.discharge_clearance_types.' . $this->value);
    }
}
