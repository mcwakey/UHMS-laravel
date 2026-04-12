<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class StockLow
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $drugName,
        public int $currentQuantity,
        public int $reorderLevel
    ) {}
}
