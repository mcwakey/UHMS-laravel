<?php

namespace App\Enums\FrontDesk;

enum CourierType: string
{
    case LETTER = 'letter';
    case PARCEL = 'parcel';
    case DOCUMENT = 'document';
    case SAMPLE = 'sample';
    case INVOICE = 'invoice';
    case REPORT = 'report';
    case MEDICINE = 'medicine';
    case EQUIPMENT = 'equipment';
    case OTHER = 'other';

    public function label(): string
    {
        return match ($this) {
            self::LETTER => 'Letter',
            self::PARCEL => 'Parcel',
            self::DOCUMENT => 'Document',
            self::SAMPLE => 'Sample',
            self::INVOICE => 'Invoice',
            self::REPORT => 'Report',
            self::MEDICINE => 'Medicine',
            self::EQUIPMENT => 'Equipment',
            self::OTHER => 'Other',
        };
    }

    public function translatedLabel(): string
    {
        return __('front_desk.courier_type.' . $this->value);
    }

    public function color(): string
    {
        return match ($this) {
            self::MEDICINE, self::SAMPLE => 'danger',
            self::INVOICE => 'warning',
            self::REPORT, self::DOCUMENT => 'info',
            self::PARCEL, self::EQUIPMENT => 'primary',
            default => 'secondary',
        };
    }

    /** @return array<int, self> */
    public static function options(): array
    {
        return self::cases();
    }
}
