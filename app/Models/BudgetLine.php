<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Validation\ValidationException;

class BudgetLine extends Model
{
    protected $fillable = [
        'budget_id', 'department_id', 'account_id', 'branch_code', 'project_code',
        'grant_code', 'amount', 'notes',
    ];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2'];
    }

    protected static function booted(): void
    {
        $guard = function (BudgetLine $line): void {
            $budget = $line->budget ?: Budget::find($line->budget_id);
            if ($budget?->isApprovedLike()) {
                throw ValidationException::withMessages([
                    'budget' => 'Approved budget lines are immutable. Use a revision or transfer instead.',
                ]);
            }
        };

        static::creating($guard);
        static::updating($guard);
        static::deleting($guard);
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }
}
