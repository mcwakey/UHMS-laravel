<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drug extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'category_id',
        'generic_name_id',
        'name',
        'generic_name',
        'brand_name',
        'dosage_form',
        'strength',
        'unit',
        'price',
        'opening_stock',
        'reorder_level',
        'requires_prescription',
        'is_active',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'opening_stock' => 'decimal:4',
        'reorder_level' => 'decimal:4',
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DrugCategory::class, 'category_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function genericName(): BelongsTo
    {
        return $this->belongsTo(DrugGenericName::class, 'generic_name_id');
    }

    /**
     * Resolve the displayed name: prefer the linked Product's name, fall back to the local column.
     */
    public function getDisplayNameAttribute(): string
    {
        $base = $this->product?->name ?? ($this->name ?? '—');
        if ($this->strength) {
            $base .= " ({$this->strength})";
        }
        return $base;
    }

    /**
     * Resolve the generic name: prefer the linked DrugGenericName, fall back to the legacy string column.
     */
    public function getGenericLabelAttribute(): ?string
    {
        return $this->relationLoaded('genericName')
            ? ($this->genericName?->name ?? $this->generic_name)
            : ($this->generic_name_id ? optional($this->genericName)->name : $this->generic_name);
    }

    /**
     * Stock balances (source of truth) — one row per (drug, stock_location).
     */
    public function balances(): HasMany
    {
        return $this->hasMany(\App\Models\StockBalance::class);
    }

    public function dispensingRecords(): HasMany
    {
        return $this->hasMany(DispensingRecord::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('generic_name', 'like', "%{$term}%")
              ->orWhere('brand_name', 'like', "%{$term}%");
        });
    }

    /**
     * Quantity-on-hand available at pharmacy locations for this drug.
     * Only pharmacy-type stock locations are counted here. Use stock transfers
     * to move goods from Main Store into Pharmacy before dispensing.
     */
    public function getTotalStockAttribute()
    {
        if (! $this->product_id) {
            return 0.0;
        }

        $pharmacyLocIds = \App\Models\StockLocation::where('type', 'pharmacy')->pluck('id');
        if ($pharmacyLocIds->isEmpty()) {
            return 0.0;
        }

        return (float) \App\Models\StockBalance::query()
            ->where('product_id', $this->product_id)
            ->whereIn('stock_location_id', $pharmacyLocIds)
            ->sum('quantity_on_hand');
    }

    public function getIsLowStockAttribute(): bool
    {
        $reorder = (float) ($this->reorder_level ?? 0);
        return $this->total_stock <= $reorder;
    }
}
