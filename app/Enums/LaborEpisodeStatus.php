<?php

namespace App\Enums;

enum LaborEpisodeStatus: string
{
    case ACTIVE = 'active';
    case MONITORING = 'monitoring';
    case DELIVERY_PENDING = 'delivery_pending';
    case DELIVERED = 'delivered';
    case REFERRED = 'referred';
    case TRANSFERRED = 'transferred';
    case CANCELLED = 'cancelled';
    case CLOSED = 'closed';

    public function label(): string
    {
        return __('maternity.labor_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE, self::MONITORING => 'primary',
            self::DELIVERY_PENDING => 'warning',
            self::DELIVERED, self::CLOSED => 'success',
            self::REFERRED, self::TRANSFERRED => 'info',
            self::CANCELLED => 'secondary',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::DELIVERED, self::CANCELLED, self::CLOSED, self::REFERRED, self::TRANSFERRED], true);
    }
}
