<?php

namespace App\Enums;

enum PostnatalObservationStatus: string
{
    case RECORDED = 'recorded';
    case REVIEW_REQUIRED = 'review_required';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('maternity.postnatal_observation_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::RECORDED => 'success',
            self::REVIEW_REQUIRED => 'warning',
            self::CANCELLED => 'secondary',
        };
    }

    public function isCancelled(): bool
    {
        return $this === self::CANCELLED;
    }
}
