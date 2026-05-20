<?php

namespace App\Enums;

enum PurchaseReturnStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case POSTED = 'posted';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::APPROVED => 'Approved',
            self::POSTED => 'Posted',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::APPROVED => 'primary',
            self::POSTED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
