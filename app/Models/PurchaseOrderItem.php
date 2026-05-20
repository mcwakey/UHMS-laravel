<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseOrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'drug_id',
        'investigation_item_id',
        'product_id',
        'item_type',
        'quantity_ordered',
        'quantity_received',
        'unit_cost',
        'total_cost',
        'batch_number',
        'expiry_date',
    ];

    protected $casts = [
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function investigationItem(): BelongsTo
    {
        return $this->belongsTo(InvestigationItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Returns the resolved item name regardless of type.
     */
    public function getItemNameAttribute(): string
    {
        if ($this->product_id) {
            return $this->product?->name ?? '—';
        }
        if ($this->item_type === 'investigation') {
            return $this->investigationItem?->name ?? '—';
        }
        if ($this->item_type === 'product') {
            return $this->product?->name ?? '—';
        }
        return $this->drug?->brand_name ?? $this->drug?->generic_name ?? '—';
    }

    public function getRemainingQuantityAttribute(): int
    {
        return $this->quantity_ordered - $this->quantity_received;
    }

    public function getIsFullyReceivedAttribute(): bool
    {
        return $this->quantity_received >= $this->quantity_ordered;
    }
}
