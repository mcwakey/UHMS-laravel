<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ConsumableUsage extends Model
{
    protected $fillable = [
        'visit_id',
        'patient_id',
        'service_id',
        'source_type',
        'source_id',
        'product_id',
        'stock_location_id',
        'quantity_used',
        'stock_movement_id',
        'used_by',
        'used_at',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'quantity_used' => 'decimal:4',
            'used_at'       => 'datetime',
        ];
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function movement()
    {
        return $this->belongsTo(ProductStockMovement::class, 'stock_movement_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'used_by');
    }
}
