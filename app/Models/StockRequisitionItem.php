<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockRequisitionItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'stock_requisition_id',
        'product_id',
        'quantity_requested',
        'quantity_approved',
        'quantity_issued',
        'quantity_acknowledged',
        'transfer_out_movement_id',
        'transfer_in_movement_id',
        'notes',
    ];

    protected $casts = [
        'quantity_requested' => 'decimal:4',
        'quantity_approved' => 'decimal:4',
        'quantity_issued' => 'decimal:4',
        'quantity_acknowledged' => 'decimal:4',
    ];

    public function stockRequisition(): BelongsTo
    {
        return $this->belongsTo(StockRequisition::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function transferOutMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'transfer_out_movement_id');
    }

    public function transferInMovement(): BelongsTo
    {
        return $this->belongsTo(StockMovement::class, 'transfer_in_movement_id');
    }
}
