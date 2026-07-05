<?php

namespace App\Enums;

enum DeliveryRecordStatus: string
{
    case DRAFT = 'draft';
    case RECORDED = 'recorded';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return __('maternity.delivery_record_statuses.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::RECORDED => 'primary',
            self::COMPLETED => 'success',
            self::CANCELLED => 'secondary',
        };
    }
}
