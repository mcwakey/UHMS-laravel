<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InsuranceTier extends Model
{
    protected $fillable = [
        'insurance_provider_id',
        'name',
        'code',
        'description',
        'is_default',
        'is_active',
        'sort_order',
        // Base constraints
        'coverage_percentage',
        'per_visit_limit',
        'annual_limit',
        'max_per_month',
        'max_visits_per_month',
        // Family & interval
        'min_visit_interval_days',
        'max_beneficiaries',
        // Card-holder overrides
        'holder_per_visit_limit',
        'holder_annual_limit',
        'holder_max_per_month',
        'holder_max_visits_per_month',
        // Beneficiary overrides
        'beneficiary_per_visit_limit',
        'beneficiary_annual_limit',
        'beneficiary_max_per_month',
        'beneficiary_max_visits_per_month',
    ];

    protected $casts = [
        'is_default'                      => 'boolean',
        'is_active'                       => 'boolean',
        'sort_order'                      => 'integer',
        'coverage_percentage'             => 'decimal:2',
        'per_visit_limit'                 => 'decimal:2',
        'annual_limit'                    => 'decimal:2',
        'max_per_month'                   => 'decimal:2',
        'max_visits_per_month'            => 'integer',
        'min_visit_interval_days'         => 'integer',
        'max_beneficiaries'               => 'integer',
        'holder_per_visit_limit'          => 'decimal:2',
        'holder_annual_limit'             => 'decimal:2',
        'holder_max_per_month'            => 'decimal:2',
        'holder_max_visits_per_month'     => 'integer',
        'beneficiary_per_visit_limit'     => 'decimal:2',
        'beneficiary_annual_limit'        => 'decimal:2',
        'beneficiary_max_per_month'       => 'decimal:2',
        'beneficiary_max_visits_per_month'=> 'integer',
    ];

    // ── Relationships ────────────────────────────────────────────────────────

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function patientInsurances(): HasMany
    {
        return $this->hasMany(PatientInsurance::class);
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    // ── Business Logic ───────────────────────────────────────────────────────

    /**
     * Resolve the effective constraint set for a given member type.
     *
     * Holder/beneficiary-specific values take precedence over the base values
     * when they are explicitly set (non-null). If a specific override is null,
     * the base constraint applies. If the base is also null, there is no limit.
     *
     * @param  string $memberType  'holder' | 'beneficiary'
     * @return array{
     *   coverage_percentage: float,
     *   per_visit_limit: float|null,
     *   annual_limit: float|null,
     *   max_per_month: float|null,
     *   max_visits_per_month: int|null,
     *   min_visit_interval_days: int|null,
     *   max_beneficiaries: int|null,
     * }
     */
    public function effectiveConstraints(string $memberType = 'holder'): array
    {
        $isHolder = $memberType === 'holder';

        return [
            'coverage_percentage'    => (float) ($this->coverage_percentage ?? 100),
            'per_visit_limit'        => $isHolder
                ? ($this->holder_per_visit_limit      ?? $this->per_visit_limit)
                : ($this->beneficiary_per_visit_limit ?? $this->per_visit_limit),
            'annual_limit'           => $isHolder
                ? ($this->holder_annual_limit          ?? $this->annual_limit)
                : ($this->beneficiary_annual_limit     ?? $this->annual_limit),
            'max_per_month'          => $isHolder
                ? ($this->holder_max_per_month         ?? $this->max_per_month)
                : ($this->beneficiary_max_per_month    ?? $this->max_per_month),
            'max_visits_per_month'   => $isHolder
                ? ($this->holder_max_visits_per_month         ?? $this->max_visits_per_month)
                : ($this->beneficiary_max_visits_per_month    ?? $this->max_visits_per_month),
            'min_visit_interval_days'=> $this->min_visit_interval_days, // same for all member types
            'max_beneficiaries'      => $this->max_beneficiaries,
        ];
    }

    /**
     * Count active beneficiaries currently enrolled under a specific card holder.
     */
    public function activeBeneficiaryCount(int $cardHolderInsuranceId): int
    {
        return PatientInsurance::where('card_holder_insurance_id', $cardHolderInsuranceId)
            ->where('insurance_tier_id', $this->id)
            ->where('member_type', 'beneficiary')
            ->where('is_active', true)
            ->count();
    }
}
