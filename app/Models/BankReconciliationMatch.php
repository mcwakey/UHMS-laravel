<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Links a statement line to a book transaction (journal line, payment, etc.).
 * Matches are reversible before reconciliation approval; approval locks them.
 * A match never alters the source transaction.
 */
class BankReconciliationMatch extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVERSED = 'reversed';

    public const METHOD_MANUAL = 'manual';
    public const METHOD_SUGGESTED_EXACT = 'suggested_exact';
    public const METHOD_SUGGESTED_REFERENCE = 'suggested_reference';
    public const METHOD_SUGGESTED_AMOUNT_DATE = 'suggested_amount_date';
    public const METHOD_SUGGESTED_MANY_TO_ONE = 'suggested_many_to_one';
    public const METHOD_SUGGESTED_ONE_TO_MANY = 'suggested_one_to_many';

    public const METHODS = [
        self::METHOD_MANUAL,
        self::METHOD_SUGGESTED_EXACT,
        self::METHOD_SUGGESTED_REFERENCE,
        self::METHOD_SUGGESTED_AMOUNT_DATE,
        self::METHOD_SUGGESTED_MANY_TO_ONE,
        self::METHOD_SUGGESTED_ONE_TO_MANY,
    ];

    protected $fillable = [
        'bank_reconciliation_id',
        'bank_statement_line_id',
        'matchable_type',
        'matchable_id',
        'matched_amount',
        'match_method',
        'confidence_score',
        'status',
        'matched_by',
        'matched_at',
        'unmatched_by',
        'unmatched_at',
        'unmatch_reason',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'matched_amount' => 'decimal:2',
            'confidence_score' => 'decimal:2',
            'matched_at' => 'datetime',
            'unmatched_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(BankStatementLine::class, 'bank_statement_line_id');
    }

    public function matchable(): MorphTo
    {
        return $this->morphTo();
    }

    public function matchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'matched_by');
    }

    public function unmatchedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'unmatched_by');
    }

    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }
}
