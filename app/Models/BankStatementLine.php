<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * One immutable line from an imported bank statement. A line is a debit OR a
 * credit on the bank account; it must never be matched above its own amount.
 */
class BankStatementLine extends Model
{
    use HasFactory;

    public const MATCH_UNMATCHED = 'unmatched';
    public const MATCH_SUGGESTED = 'suggested';
    public const MATCH_PARTIAL = 'partially_matched';
    public const MATCH_MATCHED = 'matched';
    public const MATCH_IGNORED = 'ignored';

    public const MATCH_STATUSES = [
        self::MATCH_UNMATCHED,
        self::MATCH_SUGGESTED,
        self::MATCH_PARTIAL,
        self::MATCH_MATCHED,
        self::MATCH_IGNORED,
    ];

    protected $fillable = [
        'bank_statement_import_id',
        'bank_account_id',
        'line_number',
        'transaction_date',
        'value_date',
        'reference',
        'normalized_reference',
        'description',
        'debit_amount',
        'credit_amount',
        'balance_after',
        'external_transaction_id',
        'line_hash',
        'match_status',
        'matched_amount',
        'unmatched_amount',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'transaction_date' => 'date',
            'value_date' => 'date',
            'debit_amount' => 'decimal:2',
            'credit_amount' => 'decimal:2',
            'balance_after' => 'decimal:2',
            'matched_amount' => 'decimal:2',
            'unmatched_amount' => 'decimal:2',
            'line_number' => 'integer',
            'metadata_snapshot' => 'array',
        ];
    }

    public function import(): BelongsTo
    {
        return $this->belongsTo(BankStatementImport::class, 'bank_statement_import_id');
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

    public function scopeForAccount(Builder $query, int $bankAccountId): Builder
    {
        return $query->where('bank_account_id', $bankAccountId);
    }

    /** The line's signed amount on the bank account (credit positive, debit negative). */
    public function signedAmount(): float
    {
        return round((float) $this->credit_amount - (float) $this->debit_amount, 2);
    }

    /** The gross amount of the line regardless of side. */
    public function grossAmount(): float
    {
        return round((float) $this->credit_amount + (float) $this->debit_amount, 2);
    }

    /** Amount still available to match. */
    public function remainingToMatch(): float
    {
        return round($this->grossAmount() - (float) $this->matched_amount, 2);
    }
}
