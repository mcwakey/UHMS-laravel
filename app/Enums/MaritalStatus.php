<?php

namespace App\Enums;

use Illuminate\Support\Facades\Lang;

enum MaritalStatus: string
{
    case SINGLE = 'single';
    case MARRIED = 'married';
    case DIVORCED = 'divorced';
    case WIDOWED = 'widowed';

    public function label(): string
    {
        return match ($this) {
            self::SINGLE => 'Single',
            self::MARRIED => 'Married',
            self::DIVORCED => 'Divorced',
            self::WIDOWED => 'Widowed',
        };
    }

    public function translatedLabel(): string
    {
        $key = 'statuses.default.' . $this->value;

        return Lang::has($key) ? __($key) : $this->label();
    }
}
