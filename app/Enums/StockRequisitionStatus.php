<?php

namespace App\Enums;

enum StockRequisitionStatus: string
{
    case DRAFT = 'draft';
    case SUBMITTED = 'submitted';
    case APPROVED = 'approved';
    case PARTIALLY_APPROVED = 'partially_approved';
    case AWAITING_ACKNOWLEDGEMENT = 'awaiting_acknowledgement';
    case PARTIALLY_ACKNOWLEDGED = 'partially_acknowledged';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::DRAFT => 'Draft',
            self::SUBMITTED => 'Submitted',
            self::APPROVED => 'Approved',
            self::PARTIALLY_APPROVED => 'Partially Approved',
            self::AWAITING_ACKNOWLEDGEMENT => 'Awaiting Acknowledgement',
            self::PARTIALLY_ACKNOWLEDGED => 'Partially Acknowledged',
            self::COMPLETED => 'Completed',
            self::CANCELLED => 'Cancelled',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.requisition.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::DRAFT => 'secondary',
            self::SUBMITTED => 'info',
            self::APPROVED => 'primary',
            self::PARTIALLY_APPROVED => 'warning',
            self::AWAITING_ACKNOWLEDGEMENT => 'purple',
            self::PARTIALLY_ACKNOWLEDGED => 'warning',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }
}
