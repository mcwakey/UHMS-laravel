<?php

namespace App\Enums\FrontDesk;

enum FrontDeskIncidentStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case ESCALATED = 'escalated';
    case RESOLVED = 'resolved';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::OPEN => 'Open',
            self::IN_PROGRESS => 'In Progress',
            self::ESCALATED => 'Escalated',
            self::RESOLVED => 'Resolved',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.incident_status.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'primary',
            self::IN_PROGRESS => 'info',
            self::ESCALATED => 'danger',
            self::RESOLVED => 'success',
            self::CANCELLED => 'dark',
        };
    }

    /** Statuses that count as "open" for dashboards. */
    public static function openValues(): array
    {
        return [self::OPEN->value, self::IN_PROGRESS->value, self::ESCALATED->value];
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
