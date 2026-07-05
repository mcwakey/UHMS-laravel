<?php

namespace App\Enums;

enum AntenatalVisitStatus: string
{
    case RECORDED = 'recorded';
    case FOLLOW_UP_SCHEDULED = 'follow_up_scheduled';
    case REFERRED = 'referred';
    case HIGH_RISK = 'high_risk';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('maternity.anc_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::RECORDED => 'primary',
            self::FOLLOW_UP_SCHEDULED => 'info',
            self::REFERRED => 'warning',
            self::HIGH_RISK => 'danger',
            self::CLOSED, self::CANCELLED => 'secondary',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::CLOSED, self::CANCELLED], true);
    }
}
