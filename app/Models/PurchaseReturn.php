<?php

namespace App\Models;

use App\Enums\PurchaseReturnStatus;
use App\Models\PurchaseReturnItem;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PurchaseReturn extends Model
{
    use HasFactory, GeneratesNumbers;

    protected $fillable = [
        'return_number',
        'supplier_id',
        'purchase_order_id',
        'goods_received_note_id',
        'stock_location_id',
        'return_date',
        'total_amount',
        'status',
        'reason',
        'notes',
        'created_by',
        'approved_by',
        'posted_by',
        'posted_at',
        'journal_entry_id',
        'accounting_status',
        'accounting_posted_at',
        'accounting_error',
    ];

    protected $casts = [
        'return_date' => 'date',
        'total_amount' => 'decimal:2',
        'status' => PurchaseReturnStatus::class,
        'posted_at' => 'datetime',
        'accounting_posted_at' => 'datetime',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public static function generateReturnNumber(): string
    {
        return self::generateNumber('PR', 'purchase_returns', 'return_number');
    }

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

    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approvedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function postedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function getIsEditableAttribute(): bool
    {
        return $this->status === PurchaseReturnStatus::DRAFT;
    }

    public function getIsPostableAttribute(): bool
    {
        return $this->status === PurchaseReturnStatus::APPROVED;
    }
}
