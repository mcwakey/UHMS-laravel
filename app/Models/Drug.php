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
     * Quantity-on-hand available **at the Pharmacy** for this drug.
     *
    * Single source of truth = `stock_balances`. Drugs are surfaced
     * as a filtered catalogue of products; the Pharmacy can only dispense
     * what has been transferred into the Pharmacy stock location. Stock that
     * still lives in Main Store is intentionally NOT counted here.
     */
    public function getTotalStockAttribute()
    {
        if (! $this->product_id) {
            return 0.0;
        }

        $pharmacyLocationIds = \App\Models\StockLocation::query()
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('type', 'pharmacy')
                  ->orWhereHas('department', fn ($dq) => $dq->where('type', 'pharmacy'));
            })
            ->pluck('id');

        if ($pharmacyLocationIds->isEmpty()) {
            return 0.0;
        }

        return (float) \App\Models\StockBalance::query()
            ->where('product_id', $this->product_id)
            ->whereIn('stock_location_id', $pharmacyLocationIds)
            ->sum('quantity_on_hand');
    }

    public function getIsLowStockAttribute(): bool
    {
        $reorder = (float) ($this->reorder_level ?? 0);
        return $this->total_stock <= $reorder;
    }
}
