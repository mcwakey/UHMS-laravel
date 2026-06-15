<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PayrollTaxTable extends Model
{
    protected $fillable = ['country_code', 'name', 'tax_type', 'period_basis', 'resident_type', 'currency', 'effective_from', 'effective_to', 'flat_rate_percent', 'is_active', 'notes', 'created_by', 'updated_by'];
    protected $casts = ['effective_from' => 'date', 'effective_to' => 'date', 'flat_rate_percent' => 'decimal:4', 'is_active' => 'boolean'];
    public function bands(): HasMany { return $this->hasMany(PayrollTaxBand::class)->orderBy('band_order'); }
}
