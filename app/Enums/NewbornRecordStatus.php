<?php

namespace App\Enums;

enum NewbornRecordStatus: string
{
    case ACTIVE = 'active';
    case UNDER_OBSERVATION = 'under_observation';
    case STABLE = 'stable';
    case AT_RISK = 'at_risk';
    case TRANSFERRED = 'transferred';
    case DECEASED = 'deceased';
    case DISCHARGED = 'discharged';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('maternity.newborn_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE, self::UNDER_OBSERVATION => 'primary',
            self::STABLE, self::DISCHARGED, self::CLOSED => 'success',
            self::AT_RISK => 'warning',
            self::TRANSFERRED => 'info',
            self::DECEASED => 'dark',
            self::CANCELLED => 'secondary',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::TRANSFERRED, self::DECEASED, self::DISCHARGED, self::CLOSED, self::CANCELLED], true);
    }
}
