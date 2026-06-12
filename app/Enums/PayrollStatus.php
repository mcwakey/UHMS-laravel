<?php

namespace App\Enums;

enum PayrollStatus: string
{
    case DRAFT = 'draft';
    case APPROVED = 'approved';
    case PAID = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::APPROVED => 'Approved',
            self::PAID => 'Paid',
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
            self::APPROVED => 'info',
            self::PAID => 'success',
        };
    }
}
