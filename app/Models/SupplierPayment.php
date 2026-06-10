<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Money paid to a supplier — settles supplier payables and posts
 * Dr Supplier Payables / Cr Cash|Bank|Mobile Money.
 */
class SupplierPayment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';
    public const METHOD_BANK = 'bank';
    public const METHOD_MOBILE_MONEY = 'mobile_money';

    protected $fillable = [
        'payment_number', 'supplier_id', 'supplier_payable_id', 'supplier_ledger_entry_id',
        'payment_date', 'amount', 'payment_method', 'reference', 'notes',
        'journal_entry_id', 'accounting_status', 'accounting_posted_at', 'accounting_error',
        'reversal_journal_entry_id', 'reversed_at', 'reversed_by', 'reversal_reason',
        'created_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'amount' => 'decimal:2',
        'accounting_posted_at' => 'datetime',
        'reversed_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function payable(): BelongsTo
    {
        return $this->belongsTo(SupplierPayable::class, 'supplier_payable_id');
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }

    public static function generateNumber(): string
    {
        $prefix = 'SPV-'.now()->format('Y').'-';
        $last = static::query()->where('payment_number', 'like', $prefix.'%')->orderByDesc('id')->value('payment_number');
        $seq = ($last && preg_match('/(\d+)$/', $last, $m)) ? (int) $m[1] : 0;

        return $prefix.str_pad((string) ($seq + 1), 4, '0', STR_PAD_LEFT);
    }
}
