<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LabTest extends Model
{
    protected $fillable = [
        'category_id',
        'name',
        'code',
        'normal_range',
        'unit',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(LabTestCategory::class, 'category_id');
    }

    public function requestItems(): HasMany
    {
        return $this->hasMany(LabRequestItem::class);
    }

    public function criteria(): HasMany
    {
        return $this->hasMany(\App\Models\LabTestCriterion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
