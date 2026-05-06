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
        'is_default',
        'verification_driver',
        'verification_method',
        'verification_channel',
        'verification_config',
        'verification_credentials_key',
    ];

    protected $casts = [
        'type'                => InsuranceType::class,
        'is_active'           => 'boolean',
        'is_default'          => 'boolean',
        'verification_config' => 'array',
    ];

    public function requiresVerification(): bool
    {
        return ! empty($this->verification_driver);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'type', 'is_active'])
            ->logOnlyDirty()
            ->useLogName('insurance');
    }

    // ── Relationships ────────────────────────────────
    public function tiers(): HasMany
    {
        return $this->hasMany(InsuranceTier::class)->orderBy('sort_order')->orderBy('name');
    }

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
