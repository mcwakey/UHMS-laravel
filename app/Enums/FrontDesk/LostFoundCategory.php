<?php

namespace App\Enums\FrontDesk;

enum LostFoundCategory: string
{
    case PHONE = 'phone';
    case WALLET = 'wallet';
    case ID_CARD = 'id_card';
    case DOCUMENT = 'document';
    case KEYS = 'keys';
    case BAG = 'bag';
    case CLOTHING = 'clothing';
    case MONEY = 'money';
    case MEDICAL_ITEM = 'medical_item';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PHONE => 'Phone',
            self::WALLET => 'Wallet',
            self::ID_CARD => 'ID Card',
            self::DOCUMENT => 'Document',
            self::KEYS => 'Keys',
            self::BAG => 'Bag',
            self::CLOTHING => 'Clothing',
            self::MONEY => 'Money',
            self::MEDICAL_ITEM => 'Medical Item',
            self::OTHER => 'Other',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.lost_found_category.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::MONEY, self::WALLET => 'warning',
            self::PHONE, self::ID_CARD, self::DOCUMENT => 'info',
            self::MEDICAL_ITEM => 'danger',
            default => 'secondary',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
