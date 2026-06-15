<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case DRAFT = 'draft';
    case UNDER_REVIEW = 'under_review';
    case APPROVED = 'approved';
    case POSTED = 'posted';
    case PAID = 'paid';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::UNDER_REVIEW => 'Under Review',
            self::APPROVED => 'Approved',
            self::POSTED => 'Posted',
            self::PAID => 'Paid',
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
            self::DRAFT => 'warning',
            self::UNDER_REVIEW => 'primary',
            self::APPROVED => 'info',
            self::POSTED => 'secondary',
            self::PAID => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
