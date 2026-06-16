<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetRevisionLine extends Model
{
    protected $fillable = ['budget_revision_id', 'department_id', 'account_id', 'amount_delta', 'notes'];

    protected function casts(): array
    {
        return ['amount_delta' => 'decimal:2'];
    }

    public function revision(): BelongsTo
    {
        return $this->belongsTo(BudgetRevision::class, 'budget_revision_id');
    }
}
