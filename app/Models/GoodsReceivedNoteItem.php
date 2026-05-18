<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Goods Received Note Item — one product line on a GRN.
 *
 * @property int $id
 * @property int $goods_received_note_id
 * @property int|null $purchase_order_item_id
 * @property int|null $product_id
 * @property int $stock_location_id
 * @property float $quantity_received
 * @property float|null $unit_cost
 * @property string|null $batch_no
 * @property Carbon|null $expiry_date
 * @property int|null $product_stock_movement_id
 * @property int|null $stock_movement_id
 */
class GoodsReceivedNoteItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'goods_received_note_id',
        'purchase_order_item_id',
        'product_id',
        'stock_location_id',
        'quantity_received',
        'unit_cost',
        'batch_no',
        'expiry_date',
        'product_stock_movement_id',
        'stock_movement_id',
    ];

    protected $casts = [
        'quantity_received' => 'decimal:4',
        'unit_cost'         => 'decimal:2',
        'expiry_date'       => 'date',
    ];

    public function goodsReceivedNote(): BelongsTo
    {
        return $this->belongsTo(GoodsReceivedNote::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class);
    }
}
