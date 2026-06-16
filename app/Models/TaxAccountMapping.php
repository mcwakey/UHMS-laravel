<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxAccountMapping extends Model
{
    protected $fillable = ['tax_type_id', 'payable_account_id', 'receivable_account_id', 'effective_from', 'effective_to', 'is_active'];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean'];
    }
}
