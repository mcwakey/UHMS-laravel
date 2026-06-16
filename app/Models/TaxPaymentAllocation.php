<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxPaymentAllocation extends Model
{
    protected $fillable = ['tax_payment_id', 'tax_return_id', 'tax_ledger_entry_id', 'amount', 'created_by'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }
}
