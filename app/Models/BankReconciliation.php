<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A formal bank reconciliation for a period. Approved reconciliations are
 * locked; reopening requires elevated permission and a reason, and reversal
 * preserves the original record.
 */
class BankReconciliation extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PREPARED = 'prepared';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REOPENED = 'reopened';
    public const STATUS_REVERSED = 'reversed';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PREPARED,
        self::STATUS_APPROVED,
        self::STATUS_REOPENED,
        self::STATUS_REVERSED,
        self::STATUS_CANCELLED,
    ];

    /** Statuses that may still be edited (matched, adjusted, re-prepared). */
    public const EDITABLE_STATUSES = [self::STATUS_DRAFT, self::STATUS_PREPARED, self::STATUS_REOPENED];

    protected $fillable = [
        'bank_account_id',
        'period_start',
        'period_end',
        'statement_opening_balance',
        'statement_closing_balance',
        'book_opening_balance',
        'book_closing_balance',
        'outstanding_deposits_total',
        'outstanding_withdrawals_total',
        'adjustments_total',
        'difference',
        'status',
        'prepared_by',
        'prepared_at',
        'approved_by',
        'approved_at',
        'reopened_by',
        'reopened_at',
        'reopen_reason',
        'reversed_by',
        'reversed_at',
        'reversal_reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'statement_opening_balance' => 'decimal:2',
            'statement_closing_balance' => 'decimal:2',
            'book_opening_balance' => 'decimal:2',
            'book_closing_balance' => 'decimal:2',
            'outstanding_deposits_total' => 'decimal:2',
            'outstanding_withdrawals_total' => 'decimal:2',
            'adjustments_total' => 'decimal:2',
            'difference' => 'decimal:2',
            'prepared_at' => 'datetime',
            'approved_at' => 'datetime',
            'reopened_at' => 'datetime',
            'reversed_at' => 'datetime',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function matches(): HasMany
    {
        return $this->hasMany(BankReconciliationMatch::class);
    }

    public function activeMatches(): HasMany
    {
        return $this->hasMany(BankReconciliationMatch::class)
            ->where('status', BankReconciliationMatch::STATUS_ACTIVE);
    }

    public function adjustments(): HasMany
    {
        return $this->hasMany(BankReconciliationAdjustment::class);
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function reopenedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reopened_by');
    }

    public function reversedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reversed_by');
    }

    public function scopeForAccount(Builder $query, int $bankAccountId): Builder
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    public function isLocked(): bool
    {
        return $this->status === self::STATUS_APPROVED;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, self::EDITABLE_STATUSES, true);
    }
}
