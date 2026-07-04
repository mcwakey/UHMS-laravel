<?php

namespace App\Enums;

enum PregnancyProfileStatus: string
{
    case ACTIVE = 'active';
    case HIGH_RISK = 'high_risk';
    case DELIVERED = 'delivered';
    case CLOSED = 'closed';
    case TRANSFERRED = 'transferred';

    public function label(): string
    {
        return __('maternity.profile_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::HIGH_RISK => 'danger',
            self::DELIVERED => 'info',
            self::TRANSFERRED => 'warning',
            self::CLOSED => 'secondary',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::DELIVERED, self::CLOSED, self::TRANSFERRED], true);
    }
}
