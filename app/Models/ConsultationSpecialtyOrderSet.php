<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ConsultationSpecialtyOrderSet extends Model
{
    protected $fillable = [
        'consultation_specialty_profile_id',
        'code',
        'name',
        'description',
        'category',
        'icon',
        'color',
        'is_active',
        'sort_order',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
            'metadata' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyProfile::class, 'consultation_specialty_profile_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyOrderSetItem::class);
    }

    public function activeItems(): HasMany
    {
        return $this->items()->active()->ordered();
    }

    public function applications(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyOrderSetApplication::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeForProfile(Builder $query, ConsultationSpecialtyProfile $profile): Builder
    {
        return $query->where('consultation_specialty_profile_id', $profile->id);
    }

    public function scopeOfCategory(Builder $query, ?string $category): Builder
    {
        return $category ? $query->where('category', $category) : $query;
    }
}
