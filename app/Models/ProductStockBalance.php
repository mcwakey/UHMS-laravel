<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductStockBalance extends Model
{
    protected $fillable = [
        'product_id',
        'stock_location_id',
        'quantity_on_hand',
        'last_movement_at',
    ];

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'decimal:4',
            'last_movement_at' => 'datetime',
        ];
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }
}
