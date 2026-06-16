<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxType extends Model
{
    protected $fillable = ['code', 'name', 'category', 'authority_name', 'return_frequency', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(TaxLedgerEntry::class);
    }
}
