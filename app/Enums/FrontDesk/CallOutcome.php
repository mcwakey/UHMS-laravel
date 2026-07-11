<?php

namespace App\Enums\FrontDesk;

enum CallOutcome: string
{
    case ANSWERED = 'answered';
    case MISSED = 'missed';
    case TRANSFERRED = 'transferred';
    case CALLBACK_REQUIRED = 'callback_required';
    case RESOLVED = 'resolved';
    case UNRESOLVED = 'unresolved';
    case WRONG_NUMBER = 'wrong_number';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::ANSWERED => 'Answered',
            self::MISSED => 'Missed',
            self::TRANSFERRED => 'Transferred',
            self::CALLBACK_REQUIRED => 'Callback Required',
            self::RESOLVED => 'Resolved',
            self::UNRESOLVED => 'Unresolved',
            self::WRONG_NUMBER => 'Wrong Number',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.call_outcome.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::RESOLVED, self::ANSWERED => 'success',
            self::CALLBACK_REQUIRED => 'warning',
            self::MISSED, self::UNRESOLVED => 'danger',
            self::TRANSFERRED => 'info',
            self::WRONG_NUMBER, self::CANCELLED => 'secondary',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
