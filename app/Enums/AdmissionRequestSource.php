<?php

namespace App\Enums;

enum AdmissionRequestSource: string
{
    case CONSULTATION = 'consultation';
    case EMERGENCY = 'emergency';
    case DIRECT = 'direct';
    case MATERNITY = 'maternity';
    case TRANSFER = 'transfer';
    case THEATRE = 'theatre';

    public function label(): string
    {
        return __('admissions.request_sources.' . $this->value);
    }
}
