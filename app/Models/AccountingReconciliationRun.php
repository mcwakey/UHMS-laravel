<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountingReconciliationRun extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_RUNNING = 'running';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_SUPERSEDED = 'superseded';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_RUNNING,
        self::STATUS_COMPLETED,
        self::STATUS_APPROVED,
        self::STATUS_CANCELLED,
        self::STATUS_SUPERSEDED,
    ];

    public const TYPES = [
        'accounts_receivable',
        'accounts_payable',
        'inventory',
        'payroll',
        'cash_bank',
        'paye',
        'pension',
    ];

    protected $fillable = [
        'reconciliation_type', 'period_start', 'period_end', 'as_of_date', 'status',
        'tolerance_amount', 'subledger_total', 'gl_total', 'difference_amount',
        'difference_classification', 'source_snapshot', 'gl_snapshot', 'summary_snapshot',
        'started_by', 'started_at', 'completed_by', 'completed_at',
        'approved_by', 'approved_at', 'cancelled_by', 'cancelled_at',
        'cancellation_reason', 'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'as_of_date' => 'date',
            'tolerance_amount' => 'decimal:2',
            'subledger_total' => 'decimal:2',
            'gl_total' => 'decimal:2',
            'difference_amount' => 'decimal:2',
            'source_snapshot' => 'array',
            'gl_snapshot' => 'array',
            'summary_snapshot' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'approved_at' => 'datetime',
            'cancelled_at' => 'datetime',
        ];
    }

    public function items(): HasMany
    {
        return $this->hasMany(AccountingReconciliationItem::class);
    }

    public function resolutions(): HasMany
    {
        return $this->hasMany(AccountingReconciliationResolution::class);
    }

    public function startedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_SUPERSEDED]);
    }

    public function availability(): string
    {
        return (string) data_get($this->summary_snapshot, 'availability', 'available');
    }

    public function hasUnresolvedDifferences(): bool
    {
        return $this->items()
            ->whereNotIn('classification', ['balanced', 'not_available'])
            ->where('resolution_status', 'open')
            ->exists();
    }
}
