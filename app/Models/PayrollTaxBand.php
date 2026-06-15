<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollTaxBand extends Model
{
    protected $fillable = ['payroll_tax_table_id', 'band_order', 'band_label', 'lower_bound', 'upper_bound', 'band_amount', 'rate_percent', 'fixed_tax_amount', 'cumulative_tax', 'is_excess_band'];
    protected $casts = ['is_excess_band' => 'boolean', 'lower_bound' => 'decimal:2', 'upper_bound' => 'decimal:2', 'band_amount' => 'decimal:2', 'rate_percent' => 'decimal:4'];
    public function taxTable(): BelongsTo { return $this->belongsTo(PayrollTaxTable::class, 'payroll_tax_table_id'); }
}
