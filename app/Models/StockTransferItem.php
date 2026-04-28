<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_transfer_id',
        'drug_id',
        'investigation_item_id',
        'item_type',
        'quantity',
        'batch_number',
    ];

    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function investigationItem(): BelongsTo
    {
        return $this->belongsTo(InvestigationItem::class);
    }

    public function getItemNameAttribute(): string
    {
        if ($this->item_type === 'investigation') {
            return $this->investigationItem?->name ?? '—';
        }
        return $this->drug?->brand_name ?? $this->drug?->generic_name ?? '—';
    }
}
