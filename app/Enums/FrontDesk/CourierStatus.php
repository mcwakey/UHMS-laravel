<?php

namespace App\Enums\FrontDesk;

enum CourierStatus: string
{
    case RECEIVED = 'received';
    case PENDING_DISPATCH = 'pending_dispatch';
    case DISPATCHED = 'dispatched';
    case DELIVERED = 'delivered';
    case RETURNED = 'returned';
    case LOST = 'lost';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::RECEIVED => 'Received',
            self::PENDING_DISPATCH => 'Pending Dispatch',
            self::DISPATCHED => 'Dispatched',
            self::DELIVERED => 'Delivered',
            self::RETURNED => 'Returned',
            self::LOST => 'Lost',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.courier_status.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DELIVERED => 'success',
            self::PENDING_DISPATCH, self::DISPATCHED => 'warning',
            self::RECEIVED => 'info',
            self::RETURNED => 'secondary',
            self::LOST => 'danger',
            self::CANCELLED => 'dark',
        };
    }

    /**
     * Statuses that still require front-desk action (not yet delivered/closed).
     *
     * @return array<int, string>
     */
    public static function pendingValues(): array
    {
        return [
            self::RECEIVED->value,
            self::PENDING_DISPATCH->value,
            self::DISPATCHED->value,
        ];
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
