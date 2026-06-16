<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxReturnLine extends Model
{
    protected $fillable = ['tax_return_id', 'tax_ledger_entry_id', 'line_type', 'tax_base_amount', 'tax_amount'];

    protected function casts(): array
    {
        return ['tax_base_amount' => 'decimal:2', 'tax_amount' => 'decimal:2'];
    }
}
