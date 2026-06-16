<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxPayment extends Model
{
    protected $fillable = [
        'tax_type_id', 'payment_number', 'payment_date', 'amount',
        'unallocated_amount', 'payment_account_id', 'status',
        'journal_entry_id', 'created_by', 'notes',
    ];

    protected function casts(): array
    {
        return ['payment_date' => 'date', 'amount' => 'decimal:2', 'unallocated_amount' => 'decimal:2'];
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TaxPaymentAllocation::class);
    }
}
