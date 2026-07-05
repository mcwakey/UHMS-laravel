<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class ConsultationSpecialtyFavorite extends Model
{
    public const TYPE_DIAGNOSIS = 'diagnosis';
    public const TYPE_INVESTIGATION = 'investigation';
    public const TYPE_PROCEDURE = 'procedure';
    public const TYPE_DRUG = 'drug';
    public const TYPE_FREQUENCY = 'frequency';
    public const TYPE_TASK = 'task';
    public const TYPE_FOLLOW_UP_INSTRUCTION = 'follow_up_instruction';
    public const TYPE_CLINICAL_INSTRUCTION = 'clinical_instruction';

    protected $fillable = [
        'consultation_specialty_profile_id',
        'favorite_type',
        'favoritable_type',
        'favoritable_id',
        'code',
        'label',
        'description',
        'search_terms',
        'metadata',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function profile(): BelongsTo
    {
        return $this->belongsTo(ConsultationSpecialtyProfile::class, 'consultation_specialty_profile_id');
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
        return $query->where('favorite_type', $type);
    }

    public function scopeForProfile(Builder $query, ConsultationSpecialtyProfile $profile): Builder
    {
        return $query->where('consultation_specialty_profile_id', $profile->id);
    }
}
