<?php

namespace App\Enums;

enum ShiftStatus: string
{
    case OPEN = 'open';
    case CLOSED = 'closed';
    case VERIFIED = 'verified';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::CLOSED => 'Closed',
            self::VERIFIED => 'Verified',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'success',
            self::CLOSED => 'warning',
            self::VERIFIED => 'info',
        };
    }
}
