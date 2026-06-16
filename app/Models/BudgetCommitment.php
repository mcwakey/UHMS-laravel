<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BudgetCommitment extends Model
{
    public const STATUS_ACTIVE = 'active';
    public const STATUS_PARTIALLY_RELEASED = 'partially_released';
    public const STATUS_RELEASED = 'released';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'budget_id', 'fiscal_year_id', 'department_id', 'account_id', 'source_type',
        'source_id', 'source_reference', 'status', 'original_amount', 'remaining_amount',
        'is_over_budget', 'over_budget_acknowledged', 'created_by', 'released_at',
        'released_by', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'original_amount' => 'decimal:2',
            'remaining_amount' => 'decimal:2',
            'is_over_budget' => 'boolean',
            'over_budget_acknowledged' => 'boolean',
            'released_at' => 'datetime',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function movements(): HasMany
    {
        return $this->hasMany(BudgetCommitmentMovement::class);
    }
}
