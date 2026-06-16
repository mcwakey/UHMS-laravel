<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetCommitmentMovement extends Model
{
    protected $fillable = [
        'budget_commitment_id', 'movement_type', 'amount', 'remaining_after',
        'source_type', 'source_id', 'notes', 'created_by',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'remaining_after' => 'decimal:2'];
    }

    public function commitment(): BelongsTo
    {
        return $this->belongsTo(BudgetCommitment::class, 'budget_commitment_id');
    }
}
