<?php

namespace App\Enums;

enum NursingTaskStatus: string
{
    case OPEN = 'open';
    case IN_PROGRESS = 'in_progress';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('admissions.nursing_task_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::OPEN => 'warning',
            self::IN_PROGRESS => 'info',
            self::COMPLETED => 'success',
            self::CANCELLED => 'secondary',
        };
    }

    public function isOpen(): bool
    {
        return in_array($this, [self::OPEN, self::IN_PROGRESS], true);
    }
}
