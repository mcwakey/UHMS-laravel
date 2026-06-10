<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
/**
 * Goods Received Note — header record produced when a purchase order receives goods.
 *
 * @property int $id
 * @property string $grn_number
 * @property int $purchase_order_id
 * @property int|null $supplier_id
 * @property Carbon $received_date
 * @property string|null $supplier_delivery_no
 * @property int|null $received_by
 * @property string|null $notes
 */
class GoodsReceivedNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'grn_number',
        'purchase_order_id',
        'supplier_id',
        'received_date',
        'supplier_delivery_no',
        'received_by',
        'notes',
        'supplier_payable_id',
        'journal_entry_id',
        'accounting_status',
        'accounting_posted_at',
        'accounting_error',
    ];

    protected $casts = [
        'received_date' => 'datetime',
        'accounting_posted_at' => 'datetime',
    ];

    public function journalEntry(): BelongsTo
    {
        return $this->belongsTo(JournalEntry::class);
    }

    public function supplierPayable(): BelongsTo
    {
        return $this->belongsTo(SupplierPayable::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function receivedByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(GoodsReceivedNoteItem::class);
    }

    /**
     * GRN-2026-0001 style number.
     */
    public static function generateGrnNumber(): string
    {
        $prefix = 'GRN-' . now()->format('Y') . '-';
        $last   = static::query()
            ->where('grn_number', 'like', $prefix . '%')
            ->orderByDesc('id')
            ->value('grn_number');

        $seq = 0;
        if ($last && preg_match('/(\d+)$/', $last, $m)) {
            $seq = (int) $m[1];
        }

        return $prefix . str_pad((string) ($seq + 1), 4, '0', STR_PAD_LEFT);
    }
}
