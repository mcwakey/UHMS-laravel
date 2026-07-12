<?php

namespace App\Enums;

enum VisitFinancialClearanceExceptionStatus: string
{
    case PENDING = 'pending';
    case APPROVED = 'approved';
    case REJECTED = 'rejected';
    case WITHDRAWN = 'withdrawn';
    case REVOKED = 'revoked';
    case EXPIRED = 'expired';
    case REPLACED = 'replaced';
}
