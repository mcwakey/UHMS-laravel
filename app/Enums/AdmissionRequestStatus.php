<?php

namespace App\Enums;

enum AdmissionRequestStatus: string
{
    case REQUESTED = 'requested';
    case UNDER_REVIEW = 'under_review';
    case ACCEPTED = 'accepted';
    case BED_PENDING = 'bed_pending';
    case RESERVED = 'reserved';
    case CONVERTED = 'converted';
    case REJECTED = 'rejected';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('admissions.request_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::REQUESTED => 'primary',
            self::UNDER_REVIEW => 'info',
            self::ACCEPTED => 'success',
            self::BED_PENDING => 'warning',
            self::RESERVED => 'purple',
            self::CONVERTED => 'dark',
            self::REJECTED, self::CANCELLED => 'secondary',
        };
    }

    public function canConvert(): bool
    {
        return in_array($this, [self::ACCEPTED, self::BED_PENDING, self::RESERVED], true);
    }

    public function isClosed(): bool
    {
        return in_array($this, [self::CONVERTED, self::REJECTED, self::CANCELLED], true);
    }
}
