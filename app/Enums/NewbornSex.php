<?php

namespace App\Enums;

enum NewbornSex: string
{
    case MALE = 'male';
    case FEMALE = 'female';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.newborn_sexes.' . $this->value);
    }
}
