<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ConsultationSpecialtyOrderSetItem extends Model
{
    protected $fillable = [
        'consultation_specialty_order_set_id',
        'item_type',
        'label',
        'description',
        'target_section',
        'target_field',
        'favoritable_type',
        'favoritable_id',
        'code',
        'payload',
        'apply_mode',
        'is_required',
        'sort_order',
        'is_active',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'is_required' => 'boolean',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function orderSet(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyOrderSet::class, 'consultation_specialty_order_set_id');
    }

    public function favoritable(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('label');
    }

    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('item_type', $type);
    }
}
