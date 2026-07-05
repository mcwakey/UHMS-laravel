<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConsultationSpecialtySection extends Model
{
    use HasFactory;

    protected $fillable = [
        'consultation_specialty_profile_id',
        'section_key',
        'label',
        'component',
        'display_order',
        'is_required',
        'is_visible',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'display_order' => 'integer',
            'is_required' => 'boolean',
            'is_visible' => 'boolean',
            'config' => 'array',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyProfile::class, 'consultation_specialty_profile_id');
    }

    public function scopeVisible(Builder $query): Builder
    {
        return $query->where('is_visible', true);
    }

    public function scopeRequired(Builder $query): Builder
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('section_key');
    }
}
