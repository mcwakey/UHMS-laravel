<?php

namespace App\Enums;

enum DeliveryMode: string
{
    case SPONTANEOUS_VAGINAL_DELIVERY = 'spontaneous_vaginal_delivery';
    case ASSISTED_DELIVERY = 'assisted_delivery';
    case CAESAREAN_SECTION = 'caesarean_section';
    case REFERRED_BEFORE_DELIVERY = 'referred_before_delivery';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.delivery_modes.' . $this->value);
    }
}
