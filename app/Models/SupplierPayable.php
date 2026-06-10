<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A supplier liability (accounts payable). Created when goods are received /
 * a supplier invoice is recognised. Reconciles with the supplier ledger and
 * feeds AP aging.
 */
class SupplierPayable extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PARTIALLY_PAID = 'partially_paid';
    public const STATUS_PAID = 'paid';
    public const STATUS_OVERDUE = 'overdue';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_WRITTEN_OFF = 'written_off';

    protected $fillable = [
        'supplier_id', 'purchase_order_id', 'goods_received_note_id', 'supplier_invoice_id', 'supplier_ledger_entry_id',
        'original_amount', 'paid_amount', 'credit_note_amount', 'return_amount', 'adjustment_amount', 'balance',
        'invoice_date', 'aging_start_date', 'due_date', 'status',
        'journal_entry_id', 'accounting_status', 'accounting_posted_at', 'accounting_error',
        'created_by', 'updated_by',
    ];

    protected $casts = [
        'original_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'credit_note_amount' => 'decimal:2',
        'return_amount' => 'decimal:2',
        'adjustment_amount' => 'decimal:2',
        'balance' => 'decimal:2',
        'invoice_date' => 'date',
        'aging_start_date' => 'date',
        'due_date' => 'date',
        'accounting_posted_at' => 'datetime',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    /** Outstanding (unsettled, non-cancelled) payables. */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereNotIn('status', [self::STATUS_PAID, self::STATUS_CANCELLED, self::STATUS_WRITTEN_OFF])
            ->where('balance', '>', 0);
    }

    /** Recompute balance + status from amounts. */
    public function recompute(): self
    {
        $balance = round(
            (float) $this->original_amount
            - (float) $this->paid_amount
            - (float) $this->credit_note_amount
            - (float) $this->return_amount
            - (float) $this->adjustment_amount,
            2,
        );
        $balance = max(0.0, $balance);

        $status = $this->status;
        if (! in_array($status, [self::STATUS_CANCELLED, self::STATUS_WRITTEN_OFF], true)) {
            $status = match (true) {
                $balance <= 0.0 && (float) $this->original_amount > 0 => self::STATUS_PAID,
                (float) $this->paid_amount > 0 || (float) $this->return_amount > 0 || (float) $this->credit_note_amount > 0 => self::STATUS_PARTIALLY_PAID,
                $this->due_date && $this->due_date->isPast() => self::STATUS_OVERDUE,
                default => self::STATUS_PENDING,
            };
        }

        $this->forceFill(['balance' => $balance, 'status' => $status])->save();

        return $this;
    }
}
