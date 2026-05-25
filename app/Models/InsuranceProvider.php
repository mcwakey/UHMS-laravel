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
        'code',
        'type',
        'insurance_type_id',
        'description',
        'contact_phone',
        'contact_email',
        'address',
        'contract_number',
        'is_active',
        'is_default',
        'requires_claim_submission',
        'requires_verification_code',
        'verification_code_label',
        'claim_workflow_override',
        'claim_export_format_override',
        'verification_driver',
        'verification_method',
        'verification_channel',
        'verification_config',
        'verification_credentials_key',
    ];

    protected $casts = [
        'type' => InsuranceType::class,
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'requires_claim_submission' => 'boolean',
        'requires_verification_code' => 'boolean',
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

    public function insuranceType()
    {
        return $this->belongsTo(\App\Models\InsuranceType::class);
    }

    public function claims(): HasMany
    {
        return $this->hasMany(Claim::class);
    }

    public function patientInsurances(): HasMany
    {
        return $this->hasMany(PatientInsurance::class);
    }

    public function claimWorkflowCode(): string
    {
        $legacyType = $this->type instanceof InsuranceType ? strtoupper($this->type->value) : strtoupper((string) $this->type);

        return strtoupper((string) ($this->claim_workflow_override
            ?: $this->insuranceType?->claim_workflow
            ?: ($legacyType === 'NHIA' ? 'NHIA' : null)
            ?: 'GENERIC'));
    }

    public function claimTypeCode(): ?string
    {
        if ($this->insuranceType?->code) {
            return $this->insuranceType->code;
        }

        $legacyType = $this->type instanceof InsuranceType ? strtoupper($this->type->value) : strtoupper((string) $this->type);

        return match ($legacyType) {
            'NHIA', 'NHIS', 'PUBLIC' => 'NHIA',
            'CORPORATE' => 'CORPORATE',
            'SELF' => 'SELF',
            'PRIVATE' => 'PRIVATE',
            default => null,
        };
    }

    public function claimExportFormat(): string
    {
        return strtoupper((string) ($this->claim_export_format_override
            ?: $this->insuranceType?->default_claim_export_format
            ?: 'CSV'));
    }

    public function verificationCodeLabel(): string
    {
        return $this->verification_code_label
            ?: $this->insuranceType?->verification_code_label
            ?: ($this->claimWorkflowCode() === 'NHIA' ? 'CCC Code' : null)
            ?: 'Verification Code';
    }

    public function requiresClaimSubmission(): bool
    {
        if ($this->requires_claim_submission !== null) {
            return (bool) $this->requires_claim_submission;
        }

        if ($this->insuranceType?->requires_claim_submission !== null) {
            return (bool) $this->insuranceType->requires_claim_submission;
        }

        return $this->claimWorkflowCode() === 'NHIA';
    }

    public function requiresVerificationCode(): bool
    {
        if ($this->requires_verification_code !== null) {
            return (bool) $this->requires_verification_code;
        }

        if ($this->insuranceType?->requires_verification_code !== null) {
            return (bool) $this->insuranceType->requires_verification_code;
        }

        return $this->claimWorkflowCode() === 'NHIA';
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
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('name', 'like', "%{$search}%")
                ->orWhere('short_name', 'like', "%{$search}%");
        });
    }
}
