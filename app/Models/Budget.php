<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Budget extends Model
{
    public const STATUS_DRAFT = 'draft';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'fiscal_year_id', 'name', 'budget_type', 'status', 'enforcement_mode',
        'version', 'notes', 'created_by', 'submitted_by', 'submitted_at',
        'approved_by', 'approved_at', 'closed_by', 'closed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function fiscalYear(): BelongsTo
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function periods(): HasMany
    {
        return $this->hasMany(BudgetPeriod::class);
    }

    public function revisions(): HasMany
    {
        return $this->hasMany(BudgetRevision::class);
    }

    public function transfers(): HasMany
    {
        return $this->hasMany(BudgetTransfer::class);
    }

    public function isApprovedLike(): bool
    {
        return in_array($this->status, [self::STATUS_APPROVED, self::STATUS_ACTIVE, self::STATUS_CLOSED], true);
    }
}
