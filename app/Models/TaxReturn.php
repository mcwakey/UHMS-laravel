<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class TaxReturn extends Model
{
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_APPROVED = 'approved';

    protected $fillable = [
        'tax_return_period_id', 'return_number', 'status', 'total_tax_due',
        'total_payments', 'balance_due', 'prepared_by', 'prepared_at',
        'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'total_tax_due' => 'decimal:2',
            'total_payments' => 'decimal:2',
            'balance_due' => 'decimal:2',
            'prepared_at' => 'datetime',
            'approved_at' => 'datetime',
        ];
    }

    public function period(): BelongsTo
    {
        return $this->belongsTo(TaxReturnPeriod::class, 'tax_return_period_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(TaxReturnLine::class);
    }
}
