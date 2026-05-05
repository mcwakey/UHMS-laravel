<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'invoice_id',
        'service_catalog_id',
        'description',
        'quantity',
        'unit_price',
        'total_price',
        'is_nhis_covered',
        'nhis_approved_amount',
        'cash_price',
        'selected_price',
        'discount_amount',
        'payer_type',
        'insurance_provider_id',
        'pricing_source',
    ];

    protected function casts(): array
    {
        return [
            'unit_price' => 'decimal:2',
            'total_price' => 'decimal:2',
            'nhis_approved_amount' => 'decimal:2',
            'cash_price' => 'decimal:2',
            'selected_price' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'is_nhis_covered' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function serviceCatalog()
    {
        return $this->belongsTo(ServiceCatalog::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedTotalAttribute(): string
    {
        return '₵' . number_format($this->total_price, 2);
    }
}
