<?php

namespace App\Enums;

enum VisitFinancialClearanceMode: string
{
    case DISABLED = 'disabled';
    case OBSERVE = 'observe';
    case ACTIVE = 'active';
}
