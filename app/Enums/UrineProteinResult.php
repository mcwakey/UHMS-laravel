<?php

namespace App\Enums;

enum UrineProteinResult: string
{
    case NOT_DONE = 'not_done';
    case NEGATIVE = 'negative';
    case TRACE = 'trace';
    case PLUS_1 = '1_plus';
    case PLUS_2 = '2_plus';
    case PLUS_3 = '3_plus';
    case PLUS_4 = '4_plus';

    public function label(): string
    {
        return __('maternity.urine_results.' . $this->value);
    }
}
