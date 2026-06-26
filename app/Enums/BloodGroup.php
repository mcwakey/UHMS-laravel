<?php

namespace App\Enums;

use Illuminate\Support\Facades\Lang;

enum BloodGroup: string
{
    case A_POSITIVE = 'A+';
    case A_NEGATIVE = 'A-';
    case B_POSITIVE = 'B+';
    case B_NEGATIVE = 'B-';
    case AB_POSITIVE = 'AB+';
    case AB_NEGATIVE = 'AB-';
    case O_POSITIVE = 'O+';
    case O_NEGATIVE = 'O-';

    public function label(): string
    {
        return $this->value;
    }

    public function translatedLabel(): string
    {
        $key = 'statuses.default.' . $this->value;

        return Lang::has($key) ? __($key) : $this->label();
    }
}
