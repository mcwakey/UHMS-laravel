<?php

namespace App\Enums;

enum PaymentTimingIntegrationMode: string
{
    case LEGACY = 'legacy';
    case OBSERVE = 'observe';
}
