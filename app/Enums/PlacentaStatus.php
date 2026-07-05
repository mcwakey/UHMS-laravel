<?php

namespace App\Enums;

enum PlacentaStatus: string
{
    case NOT_RECORDED = 'not_recorded';
    case COMPLETE = 'complete';
    case INCOMPLETE = 'incomplete';
    case RETAINED = 'retained';
    case REFERRED = 'referred';

    public function label(): string
    {
        return __('maternity.placenta_statuses.' . $this->value);
    }
}
