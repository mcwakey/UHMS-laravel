<?php

namespace App\Enums\FrontDesk;

enum LostFoundStatus: string
{
    case FOUND = 'found';
    case REPORTED_LOST = 'reported_lost';
    case CLAIMED = 'claimed';
    case RELEASED = 'released';
    case DISPOSED = 'disposed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::FOUND => 'Found',
            self::REPORTED_LOST => 'Reported Lost',
            self::CLAIMED => 'Claimed',
            self::RELEASED => 'Released',
            self::DISPOSED => 'Disposed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.lost_found_status.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::FOUND => 'info',
            self::REPORTED_LOST => 'warning',
            self::CLAIMED => 'primary',
            self::RELEASED => 'success',
            self::DISPOSED => 'secondary',
            self::CANCELLED => 'dark',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
