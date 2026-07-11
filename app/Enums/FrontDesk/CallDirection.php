<?php

namespace App\Enums\FrontDesk;

enum CallDirection: string
{
    case INCOMING = 'incoming';
    case OUTGOING = 'outgoing';

    public function label(): string
    {
        return match ($this) {
            self::INCOMING => 'Incoming',
            self::OUTGOING => 'Outgoing',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.call_direction.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::INCOMING => 'info',
            self::OUTGOING => 'primary',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
