<?php

namespace App\Enums;

enum InsuranceType: string
{
    case SELF = 'self';
    case NHIA = 'nhia';
    case PRIVATE = 'private';
    case CORPORATE = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::SELF => 'SELF SPONSORED',
            self::NHIA => 'NHIA',
            self::PRIVATE => 'PRIVATE',
            self::CORPORATE => 'CORPORATE',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SELF => 'danger',
            self::NHIA => 'primary',
            self::PRIVATE => 'info',
            self::CORPORATE => 'warning',
        };
    }
}
