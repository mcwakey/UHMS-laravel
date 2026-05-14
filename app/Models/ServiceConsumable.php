<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ServiceConsumable extends Model
{
    protected $fillable = [
        'service_id',
        'product_id',
        'default_quantity',
        'is_required',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'default_quantity' => 'decimal:4',
            'is_required'      => 'boolean',
        ];
    }

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
