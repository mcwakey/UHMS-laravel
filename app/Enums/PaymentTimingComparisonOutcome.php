<?php

namespace App\Enums;

enum PaymentTimingComparisonOutcome: string
{
    case MATCH = 'match';
    case LEGACY_MORE_RESTRICTIVE = 'legacy_more_restrictive';
    case TYPED_MORE_RESTRICTIVE = 'typed_more_restrictive';
    case NOT_COMPARABLE = 'not_comparable';
    case MISSING_CONTEXT = 'missing_context';
}
