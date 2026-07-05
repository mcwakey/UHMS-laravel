<?php

namespace App\Enums;

enum DeliveryOutcome: string
{
    case LIVE_BIRTH = 'live_birth';
    case STILLBIRTH = 'stillbirth';
    case MATERNAL_TRANSFER = 'maternal_transfer';
    case REFERRED = 'referred';
    case NOT_DELIVERED = 'not_delivered';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.delivery_outcomes.' . $this->value);
    }
}
