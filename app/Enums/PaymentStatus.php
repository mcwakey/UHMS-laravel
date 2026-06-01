<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case ACTIVE = 'active';
    case REVERSED = 'reversed';
    case REVERSAL = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::ACTIVE => 'Active',
            self::REVERSED => 'Reversed',
            self::REVERSAL => 'Reversal',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::REVERSED => 'secondary',
            self::REVERSAL => 'danger',
        };
    }
}
