<?php

namespace App\Enums\FrontDesk;

enum CourierHandoffAction: string
{
    case RECEIVED = 'received';
    case DISPATCHED = 'dispatched';
    case HANDED_OVER = 'handed_over';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';
    case CANCELLED = 'cancelled';
    case NOTE = 'note';

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED => 'Received',
            self::DISPATCHED => 'Dispatched',
            self::HANDED_OVER => 'Handed Over',
            self::DELIVERED => 'Delivered',
            self::RETURNED => 'Returned',
            self::CANCELLED => 'Cancelled',
            self::NOTE => 'Note',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.handoff_action.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DELIVERED => 'success',
            self::DISPATCHED, self::HANDED_OVER => 'info',
            self::RECEIVED => 'primary',
            self::RETURNED => 'secondary',
            self::CANCELLED => 'dark',
            self::NOTE => 'light',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
