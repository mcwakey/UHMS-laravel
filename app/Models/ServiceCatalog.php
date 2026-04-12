<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCatalog extends Model
{
    use HasFactory;

    protected $table = 'service_catalog';

    protected $fillable = [
        'name',
        'code',
        'category',
        'price',
        'nhis_price',
        'is_nhis_covered',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'nhis_price' => 'decimal:2',
            'is_nhis_covered' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function invoiceItems()
    {
        return $this->hasMany(InvoiceItem::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeNhisCovered($query)
    {
        return $query->where('is_nhis_covered', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getFormattedPriceAttribute(): string
    {
        return '₵' . number_format($this->price, 2);
    }

    public function getFormattedNhisPriceAttribute(): string
    {
        return $this->nhis_price ? '₵' . number_format($this->nhis_price, 2) : '—';
    }
}
