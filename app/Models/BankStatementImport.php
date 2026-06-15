<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A single imported bank statement file. Imported/approved imports and their
 * lines are immutable; rejected imports stay historically visible but unusable.
 */
class BankStatementImport extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_VALIDATED = 'validated';
    public const STATUS_IMPORTED = 'imported';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_VALIDATED,
        self::STATUS_IMPORTED,
        self::STATUS_APPROVED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    /** Statuses whose lines may be used for reconciliation/matching. */
    public const USABLE_STATUSES = [self::STATUS_IMPORTED, self::STATUS_APPROVED];

    protected $fillable = [
        'bank_account_id',
        'format',
        'original_filename',
        'file_hash',
        'period_start',
        'period_end',
        'opening_balance',
        'closing_balance',
        'total_debit',
        'total_credit',
        'line_count',
        'status',
        'imported_by',
        'imported_at',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'error_summary',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'opening_balance' => 'decimal:2',
            'closing_balance' => 'decimal:2',
            'total_debit' => 'decimal:2',
            'total_credit' => 'decimal:2',
            'line_count' => 'integer',
            'imported_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'error_summary' => 'array',
            'metadata_snapshot' => 'array',
        ];
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BankStatementLine::class);
    }

    public function importedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function isUsable(): bool
    {
        return in_array($this->status, self::USABLE_STATUSES, true);
    }

    public function isImmutable(): bool
    {
        return in_array($this->status, self::USABLE_STATUSES, true);
    }
}
