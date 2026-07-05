<?php

namespace App\Enums;

enum PostnatalCaseStatus: string
{
    case OPEN = 'open';
    case UNDER_OBSERVATION = 'under_observation';
    case MOTHER_READY = 'mother_ready';
    case NEWBORN_READY = 'newborn_ready';
    case READY_FOR_DISCHARGE = 'ready_for_discharge';
    case REFERRAL_REQUIRED = 'referral_required';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('maternity.postnatal_case_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN, self::UNDER_OBSERVATION => 'primary',
            self::MOTHER_READY, self::NEWBORN_READY => 'info',
            self::READY_FOR_DISCHARGE, self::CLOSED => 'success',
            self::REFERRAL_REQUIRED => 'warning',
            self::CANCELLED => 'secondary',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::CLOSED, self::CANCELLED], true);
    }
}
