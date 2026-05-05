<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case CASH = 'cash';
    case MTN_MOMO = 'mtn_momo';
    case VODAFONE_CASH = 'vodafone_cash';
    case AIRTELTIGO_MONEY = 'airteltigo_money';
    case BANK_TRANSFER = 'bank_transfer';
    case CARD = 'card';
    case INSURANCE = 'insurance';
    case CHEQUE = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::CASH => 'Cash',
            self::MTN_MOMO => 'MTN Mobile Money',
            self::VODAFONE_CASH => 'Vodafone Cash',
            self::AIRTELTIGO_MONEY => 'AirtelTigo Money',
            self::BANK_TRANSFER => 'Bank Transfer',
            self::CARD => 'Card',
            self::INSURANCE => 'Insurance Settlement',
            self::CHEQUE => 'Cheque',
        };
    }
}
