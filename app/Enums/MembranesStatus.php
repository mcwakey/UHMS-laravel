<?php

namespace App\Enums;

enum MembranesStatus: string
{
    case INTACT = 'intact';
    case RUPTURED = 'ruptured';
    case UNKNOWN = 'unknown';

    public function label(): string
    {
        return __('maternity.membranes_statuses.' . $this->value);
    }
}
