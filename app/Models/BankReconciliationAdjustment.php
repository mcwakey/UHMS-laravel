<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A reconciliation adjustment (bank charge, interest, etc.). Adjustments are
 * proposed first, approved, then posted through accounting services. Unapproved
 * adjustments must never affect a reconciliation as final.
 */
class BankReconciliationAdjustment extends Model
{
    use HasFactory;

    public const TYPE_BANK_CHARGE = 'bank_charge';
    public const TYPE_INTEREST_INCOME = 'interest_income';
    public const TYPE_TRANSFER_FEE = 'transfer_fee';
    public const TYPE_CORRECTION = 'correction';
    public const TYPE_OTHER = 'other';

    public const TYPES = [
        self::TYPE_BANK_CHARGE,
        self::TYPE_INTEREST_INCOME,
        self::TYPE_TRANSFER_FEE,
        self::TYPE_CORRECTION,
        self::TYPE_OTHER,
    ];

    public const SIDE_DEBIT = 'debit';
    public const SIDE_CREDIT = 'credit';
    public const SIDES = [self::SIDE_DEBIT, self::SIDE_CREDIT];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PROPOSED = 'proposed';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_POSTED = 'posted';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REVERSED = 'reversed';

    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_PROPOSED,
        self::STATUS_APPROVED,
        self::STATUS_POSTED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
        self::STATUS_REVERSED,
    ];

    /** Adjustment types that increase the bank/book balance (debit the bank account). */
    public const INCREASES_BANK = [self::TYPE_INTEREST_INCOME];

    protected $fillable = [
        'bank_reconciliation_id',
        'bank_account_id',
        'type',
        'description',
        'amount',
        'side',
        'account_id',
        'journal_entry_id',
        'status',
        'proposed_by',
        'proposed_at',
        'approved_by',
        'approved_at',
        'posted_by',
        'posted_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'metadata_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'proposed_at' => 'datetime',
            'approved_at' => 'datetime',
            'posted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata_snapshot' => 'array',
        ];
    }

    public function reconciliation(): BelongsTo
    {
        return $this->belongsTo(BankReconciliation::class, 'bank_reconciliation_id');
    }

    public function bankAccount(): BelongsTo
    {
        return $this->belongsTo(BankAccount::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class, 'journal_entry_id');
    }

    public function proposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'proposed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /** Signed effect on the bank/book balance for reconciliation maths. */
    public function signedBankEffect(): float
    {
        $amount = (float) $this->amount;

        return in_array($this->type, self::INCREASES_BANK, true) ? $amount : -$amount;
    }
}
