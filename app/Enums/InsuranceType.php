<?php

namespace App\Enums;

enum InsuranceType: string
{
    case NHIS = 'nhis';
    case PRIVATE = 'private';
    case CORPORATE = 'corporate';

    public function label(): string
    {
        return match ($this) {
            self::NHIS => 'NHIS',
            self::PRIVATE => 'Private',
            self::CORPORATE => 'Corporate',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::NHIS => 'primary',
            self::PRIVATE => 'info',
            self::CORPORATE => 'warning',
        };
    }
}
