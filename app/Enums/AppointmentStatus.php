<?php

namespace App\Enums;

enum AppointmentStatus: string
{
    case SCHEDULED = 'scheduled';
    case CONFIRMED = 'confirmed';
    case CHECKED_IN = 'checked_in';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case NO_SHOW = 'no_show';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::SCHEDULED => 'Scheduled',
            self::CONFIRMED => 'Confirmed',
            self::CHECKED_IN => 'Checked In',
            self::IN_PROGRESS => 'In Progress',
            self::COMPLETED => 'Completed',
            self::NO_SHOW => 'No Show',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::SCHEDULED => 'secondary',
            self::CONFIRMED => 'info',
            self::CHECKED_IN => 'primary',
            self::IN_PROGRESS => 'warning',
            self::COMPLETED => 'success',
            self::NO_SHOW => 'dark',
            self::CANCELLED => 'danger',
        };
    }

    public function allowedTransitions(): array
    {
        return match ($this) {
            self::SCHEDULED => [self::CONFIRMED, self::CANCELLED],
            self::CONFIRMED => [self::CHECKED_IN, self::NO_SHOW, self::CANCELLED],
            self::CHECKED_IN => [self::IN_PROGRESS, self::CANCELLED],
            self::IN_PROGRESS => [self::COMPLETED],
            self::COMPLETED => [],
            self::NO_SHOW => [],
            self::CANCELLED => [],
        };
    }

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->allowedTransitions());
    }
}
