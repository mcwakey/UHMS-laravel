<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Lang;

class ConsultationSpecialtyProfile extends Model
{
    use HasFactory;

    public const GENERAL_MEDICINE = 'general_medicine';

    protected $fillable = [
        'code',
        'name',
        'description',
        'department_type',
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
            'metadata' => 'array',
        ];
    }

    public function sections(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtySection::class);
    }

    public function templates(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyTemplate::class);
    }

    public function entries(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyEntry::class);
    }

    public function favorites(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyFavorite::class);
    }

    public function orderSets(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyOrderSet::class);
    }

    public function mappings(): HasMany
    {
        return $this->hasMany(ConsultationSpecialtyProfileMapping::class);
    }

    public function doctorPreferences(): HasMany
    {
        return $this->hasMany(DoctorConsultationPreference::class, 'default_consultation_specialty_profile_id');
    }

    public function activeSections(): HasMany
    {
        return $this->sections()->visible()->ordered();
    }

    public function defaultTemplates(): HasMany
    {
        return $this->templates()->defaults()->active()->ordered();
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    public function scopeForDepartmentType(Builder $query, ?string $departmentType): Builder
    {
        return $departmentType === null
            ? $query
            : $query->where('department_type', $departmentType);
    }

    public function isGeneral(): bool
    {
        return $this->code === self::GENERAL_MEDICINE;
    }

    public function translatedName(): string
    {
        $key = 'consultation_specialties.profiles.' . $this->code;

        return Lang::has($key) ? __($key) : $this->name;
    }
}
