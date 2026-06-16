<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetPeriod extends Model
{
    protected $fillable = ['budget_id', 'accounting_period_id', 'name', 'start_date', 'end_date', 'amount'];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'amount' => 'decimal:2'];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
