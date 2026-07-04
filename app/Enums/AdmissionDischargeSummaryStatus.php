<?php

namespace App\Enums;

enum AdmissionDischargeSummaryStatus: string
{
    case DRAFT = 'draft';
    case PREPARED = 'prepared';
    case APPROVED = 'approved';
    case FINALISED = 'finalised';

    public function label(): string
    {
        return __('admissions.discharge_summary_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::PREPARED => 'info',
            self::APPROVED, self::FINALISED => 'success',
        };
    }

    public function isLocked(): bool
    {
        return in_array($this, [self::APPROVED, self::FINALISED], true);
    }

    public function isApproved(): bool
    {
        return in_array($this, [self::APPROVED, self::FINALISED], true);
    }
}
