<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Drug extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'product_id',
        'category_id',
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

    public function stocks(): HasMany
    {
        return $this->hasMany(DrugStock::class);
    }

    public function activeStocks(): HasMany
    {
        return $this->stocks()
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', now())
            ->orderBy('expiry_date'); // FEFO: First Expiry, First Out
    }

    /**
     * Stock balances (source of truth) — one row per (drug, stock_location).
     */
    public function balances(): HasMany
    {
        return $this->hasMany(\App\Models\StockBalance::class);
    }

    public function dispensingRecords(): HasManyThrough
    {
        return $this->hasManyThrough(DispensingRecord::class, DrugStock::class);
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
     * Aggregate on-hand quantity across all stock locations.
     * Reads from stock_balances (single source of truth).
     */
    public function getTotalStockAttribute()
    {
        return (float) $this->balances()->sum('quantity_on_hand');
    }

    public function getIsLowStockAttribute(): bool
    {
        $reorder = (float) ($this->reorder_level ?? 0);
        return $this->total_stock <= $reorder;
    }

    public function getDisplayNameAttribute(): string
    {
        $name = $this->name;
        if ($this->strength) {
            $name .= " ({$this->strength})";
        }
        return $name;
    }
}
