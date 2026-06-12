<?php

namespace App\Enums;

use App\Enums\StockMovementDirection;

enum StockMovementType: string
{
    case OPENING_STOCK          = 'opening_stock';
    case PURCHASE_RECEIVED      = 'purchase_received';
    case PHARMACY_DISPENSED     = 'pharmacy_dispensed';
    case INVESTIGATION_CONSUMED = 'investigation_consumed';
    case PROCEDURE_CONSUMED     = 'procedure_consumed';
    case WARD_CONSUMED          = 'ward_consumed';
    case EMERGENCY_ADMINISTRATION_OUT = 'emergency_administration_out';
    case TRANSFER_IN            = 'transfer_in';
    case TRANSFER_OUT        = 'transfer_out';
    case RETURN_IN           = 'return_in';
    case RETURN_OUT          = 'return_out';
    case ADJUSTMENT_IN       = 'adjustment_in';
    case ADJUSTMENT_OUT      = 'adjustment_out';
    case DAMAGED             = 'damaged';
    case EXPIRED             = 'expired';
    case REVERSAL_IN         = 'reversal_in';
    case REVERSAL_OUT        = 'reversal_out';

    /**
     * Return the inherent direction for this movement type.
     */
    public function direction(): StockMovementDirection
    {
        return match ($this) {
            self::OPENING_STOCK,
            self::PURCHASE_RECEIVED,
            self::TRANSFER_IN,
            self::RETURN_IN,
            self::ADJUSTMENT_IN,
            self::REVERSAL_IN          => StockMovementDirection::IN,

            self::PHARMACY_DISPENSED,
            self::INVESTIGATION_CONSUMED,
            self::PROCEDURE_CONSUMED,
            self::EMERGENCY_ADMINISTRATION_OUT,
            self::WARD_CONSUMED,
            self::TRANSFER_OUT,
            self::RETURN_OUT,
            self::ADJUSTMENT_OUT,
            self::DAMAGED,
            self::EXPIRED,
            self::REVERSAL_OUT         => StockMovementDirection::OUT,
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::OPENING_STOCK       => 'Opening Stock',
            self::PURCHASE_RECEIVED   => 'Purchase Received',
            self::PHARMACY_DISPENSED  => 'Pharmacy Dispensed',
            self::INVESTIGATION_CONSUMED => 'Investigation Consumed',
            self::PROCEDURE_CONSUMED  => 'Procedure Consumed',
            self::WARD_CONSUMED       => 'Ward Consumed',
            self::EMERGENCY_ADMINISTRATION_OUT => 'Emergency Administration Out',
            self::TRANSFER_IN         => 'Transfer In',
            self::TRANSFER_OUT        => 'Transfer Out',
            self::RETURN_IN           => 'Return In',
            self::RETURN_OUT          => 'Return Out',
            self::ADJUSTMENT_IN       => 'Adjustment (In)',
            self::ADJUSTMENT_OUT      => 'Adjustment (Out)',
            self::DAMAGED             => 'Damaged',
            self::EXPIRED             => 'Expired',
            self::REVERSAL_IN         => 'Reversal In',
            self::REVERSAL_OUT        => 'Reversal Out',
        };
    }

    public function translatedLabel(): string
    {
        return __('statuses.default.' . $this->value);
    }
}
