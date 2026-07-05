<?php

namespace App\Enums;

enum BreastfeedingStatus: string
{
    case EFFECTIVE = 'effective';
    case NEEDS_SUPPORT = 'needs_support';
    case NOT_ESTABLISHED = 'not_established';
    case CONTRAINDICATED = 'contraindicated';
    case NOT_APPLICABLE = 'not_applicable';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.breastfeeding_statuses.' . $this->value);
    }
}
