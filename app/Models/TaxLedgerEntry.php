<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxLedgerEntry extends Model
{
    public const STATUS_OPEN = 'open';
    public const STATUS_SETTLED = 'settled';

    protected $fillable = [
        'tax_type_id', 'fiscal_year_id', 'accounting_period_id', 'entry_date',
        'source_type', 'source_id', 'source_reference', 'direction',
        'tax_base_amount', 'tax_amount', 'remaining_amount', 'status',
        'journal_entry_id', 'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'tax_base_amount' => 'decimal:2',
            'tax_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
        ];
    }

    public function taxType(): BelongsTo
    {
        return $this->belongsTo(TaxType::class);
    }

    public function allocations(): HasMany
    {
        return $this->hasMany(TaxPaymentAllocation::class);
    }
}
