<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaxRegistration extends Model
{
    protected $fillable = ['tax_type_id', 'registration_number', 'authority_name', 'effective_from', 'effective_to', 'is_active'];

    protected function casts(): array
    {
        return ['effective_from' => 'date', 'effective_to' => 'date', 'is_active' => 'boolean'];
    }
}
