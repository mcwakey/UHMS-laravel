<?php

namespace App\Enums;

enum AdmissionDischargeClearanceStatus: string
{
    case PENDING = 'pending';
    case CLEARED = 'cleared';
    case BLOCKED = 'blocked';
    case REVOKED = 'revoked';
    case NOT_REQUIRED = 'not_required';

    public function label(): string
    {
        return __('admissions.discharge_clearance_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::CLEARED, self::NOT_REQUIRED => 'success',
            self::BLOCKED => 'danger',
            self::REVOKED => 'secondary',
            self::PENDING => 'warning',
        };
    }

    public function isReady(): bool
    {
        return in_array($this, [self::CLEARED, self::NOT_REQUIRED], true);
    }
}
