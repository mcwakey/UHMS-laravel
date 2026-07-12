<?php

namespace App\Enums;

enum VisitFinancialClearanceEvent: string
{
    case ASSESSED = 'assessed';
    case CLEARED = 'cleared';
    case CONDITIONALLY_CLEARED = 'conditionally_cleared';
    case FINANCIALLY_CLOSED = 'financially_closed';
    case MARKED_STALE = 'marked_stale';
    case REOPENED = 'reopened';
    case REFRESHED = 'refreshed';
    case CONDITIONAL_CLEARANCE_REVOKED = 'conditional_clearance_revoked';
}
