<?php

namespace App\Models;

use App\Enums\StockMovementDirection;
use App\Enums\StockMovementType;
use Illuminate\Database\Eloquent\Model;

class ProductStockMovement extends Model
{
    protected $fillable = [
        'product_id',
        'stock_location_id',
        'movement_type',
        'direction',
        'quantity',
        'unit_cost',
        'batch_no',
        'expiry_date',
        'source_type',
        'source_id',
        'performed_by',
        'movement_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'movement_type' => StockMovementType::class,
            'direction'     => StockMovementDirection::class,
            'quantity'      => 'decimal:4',
            'unit_cost'     => 'decimal:2',
            'expiry_date'   => 'date',
            'movement_date' => 'datetime',
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

    public function location()
    {
        return $this->belongsTo(StockLocation::class, 'stock_location_id');
    }

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function performer()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
