<?php

namespace App\Enums;

enum VisitFinancialClearanceStatus: string
{
    case PENDING = 'pending';
    case CLEARED = 'cleared';
    case CONDITIONALLY_CLEARED = 'conditionally_cleared';
    case FINANCIALLY_CLOSED = 'financially_closed';
    case STALE = 'stale';
}
