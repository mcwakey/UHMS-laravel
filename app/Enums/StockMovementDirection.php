<?php

namespace App\Enums;

enum StockMovementDirection: string
{
    case IN  = 'in';
    case OUT = 'out';

    public function sign(): int
    {
        return $this === self::IN ? 1 : -1;
    }

    public function label(): string
    {
        return $this === self::IN ? 'In' : 'Out';
    }
}
