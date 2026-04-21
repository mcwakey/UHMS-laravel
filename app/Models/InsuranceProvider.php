<?php

namespace App\Models;

use App\Enums\InsuranceType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class InsuranceProvider extends Model
{
    use HasFactory, LogsActivity;

    protected $fillable = [
        'name',
        'short_name',
        'type',
        'contact_phone',
        'contact_email',
        'address',
        'contract_number',
        'is_active',
        'annual_limit',
        'per_visit_limit',
        'max_per_month',
        'max_visits_per_month',
        'tier',
        'coverage_percentage',
        'is_default',
    ];

    protected $casts = [
        'type' => InsuranceType::class,
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'annual_limit' => 'decimal:2',
        'per_visit_limit' => 'decimal:2',
        'max_per_month' => 'decimal:2',
        'max_visits_per_month' => 'integer',
        'coverage_percentage' => 'decimal:2',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('insurance');
    }

    // ── Relationships ────────────────────────────────
    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function patientInsurances(): HasMany
    {
        return $this->hasMany(PatientInsurance::class);
    }

    // ── Scopes ───────────────────────────────────────
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function scopeByType($query, InsuranceType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) return $query;

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
              ->orWhere('short_name', 'like', "%{$search}%");
        });
    }
}
