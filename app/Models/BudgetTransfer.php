<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BudgetTransfer extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'budget_id', 'transfer_number', 'status', 'from_department_id', 'from_account_id',
        'to_department_id', 'to_account_id', 'amount', 'reason', 'created_by', 'approved_by', 'approved_at',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'approved_at' => 'datetime'];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }
}
