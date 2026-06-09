<?php

namespace App\Enums\Accounting;

enum NormalBalance: string
{
    case DEBIT = 'debit';
    case CREDIT = 'credit';

    public function label(): string
    {
        return ucfirst($this->value);
    }
}
