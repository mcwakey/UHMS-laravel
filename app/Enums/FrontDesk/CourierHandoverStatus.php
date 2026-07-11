<?php

namespace App\Enums\FrontDesk;

enum CourierHandoverStatus: string
{
    case AWAITING_HANDOVER = 'awaiting_handover';
    case HANDED_OVER = 'handed_over';
    case IN_TRANSIT = 'in_transit';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';
    case LOST = 'lost';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::AWAITING_HANDOVER => 'Awaiting Handover',
            self::HANDED_OVER => 'Handed Over',
            self::IN_TRANSIT => 'In Transit',
            self::DELIVERED => 'Delivered',
            self::RETURNED => 'Returned',
            self::LOST => 'Lost',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.handover_status.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DELIVERED => 'success',
            self::IN_TRANSIT, self::HANDED_OVER => 'info',
            self::AWAITING_HANDOVER => 'warning',
            self::RETURNED => 'secondary',
            self::LOST => 'danger',
            self::CANCELLED => 'dark',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
