<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockBalance extends Model
{
    use HasFactory;

    protected $fillable = [
        'drug_id',
        'product_id',
        'stock_location_id',
        'quantity_on_hand',
        'last_movement_at',
        'average_cost',
        'total_value',
        'last_valued_at',
    ];

    protected $casts = [
        'quantity_on_hand' => 'decimal:4',
        'last_movement_at' => 'datetime',
        'average_cost' => 'decimal:4',
        'total_value' => 'decimal:2',
        'last_valued_at' => 'datetime',
    ];

    public function drug(): BelongsTo
    {
        return $this->belongsTo(Drug::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function stockLocation(): BelongsTo
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }
}
