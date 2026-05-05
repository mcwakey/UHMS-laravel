<?php

namespace App\Enums;

enum InsuranceType: string
{
    case SELF = 'self';
    case PUBLIC = 'public';
    case PRIVATE = 'private';
    case CORPORATE = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::SELF => 'SELF SPONSORED',
            self::PUBLIC => 'PUBLIC',
            self::PRIVATE => 'PRIVATE',
            self::CORPORATE => 'CORPORATE',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SELF => 'danger',
            self::PUBLIC => 'primary',
            self::PRIVATE => 'info',
            self::CORPORATE => 'warning',
        };
    }
}
