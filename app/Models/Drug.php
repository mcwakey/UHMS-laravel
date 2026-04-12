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
        'category_id',
        'name',
        'generic_name',
        'brand_name',
        'dosage_form',
        'strength',
        'unit',
        'price',
        'requires_prescription',
        'is_active',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'requires_prescription' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(DrugCategory::class, 'category_id');
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

    public function dispensingRecords(): HasMany
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

    public function getTotalStockAttribute(): int
    {
        return $this->activeStocks()->sum('quantity');
    }

    public function getIsLowStockAttribute(): bool
    {
        $stock = $this->activeStocks()->first();
        if (!$stock) return true;

        return $this->total_stock <= $stock->reorder_level;
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
