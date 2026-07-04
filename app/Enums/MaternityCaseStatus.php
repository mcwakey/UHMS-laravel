<?php

namespace App\Enums;

enum MaternityCaseStatus: string
{
    case OPEN = 'open';
    case UNDER_OBSERVATION = 'under_observation';
    case ADMITTED = 'admitted';
    case REFERRED = 'referred';
    case TRANSFERRED = 'transferred';
    case CLOSED = 'closed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('maternity.case_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'success',
            self::UNDER_OBSERVATION => 'info',
            self::ADMITTED => 'primary',
            self::REFERRED, self::TRANSFERRED => 'warning',
            self::CLOSED, self::CANCELLED => 'secondary',
        };
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::CLOSED, self::CANCELLED], true);
    }
}
