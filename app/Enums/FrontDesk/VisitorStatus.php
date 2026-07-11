<?php

namespace App\Enums\FrontDesk;

enum VisitorStatus: string
{
    case CHECKED_IN = 'checked_in';
    case CHECKED_OUT = 'checked_out';
    case DENIED = 'denied';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::CHECKED_IN => 'Checked In',
            self::CHECKED_OUT => 'Checked Out',
            self::DENIED => 'Denied',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.visitor_status.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::CHECKED_IN => 'success',
            self::CHECKED_OUT => 'secondary',
            self::DENIED => 'danger',
            self::CANCELLED => 'dark',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
