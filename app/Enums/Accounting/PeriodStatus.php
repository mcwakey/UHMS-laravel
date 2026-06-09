<?php

namespace App\Enums\Accounting;

enum PeriodStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'success',
            self::CLOSED => 'secondary',
        };
    }
}
