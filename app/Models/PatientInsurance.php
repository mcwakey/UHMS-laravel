<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'insurance_provider_id',
        'membership_number',
        'policy_number',
        'start_date',
        'expiry_date',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'expiry_date' => 'date',
            'is_primary' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    // ── Relationships ────────────────────────────────

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function usages(): HasMany
    {
        return $this->hasMany(InsuranceUsage::class);
    }

    // ── Scopes ───────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('expiry_date')
                  ->orWhere('expiry_date', '>', now());
            });
    }

    // ── Accessors ────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsValidAttribute(): bool
    {
        return $this->is_active && !$this->is_expired;
    }

    /**
     * Total insurance-covered amount already used this year.
     */
    public function usedThisYear(): float
    {
        return (float) $this->usages()
            ->where('created_at', '>=', now()->startOfYear())
            ->sum('amount_covered');
    }

    /**
     * Total insurance-covered amount already used this month.
     */
    public function usedThisMonth(): float
    {
        return (float) $this->usages()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount_covered');
    }

    /**
     * Total insurance-covered amount already used for a specific visit.
     */
    public function usedForVisit(int $visitId): float
    {
        return (float) $this->usages()
            ->where('visit_id', $visitId)
            ->sum('amount_covered');
    }

    /**
     * Number of distinct visits covered this month.
     */
    public function visitsThisMonth(): int
    {
        return $this->usages()
            ->where('created_at', '>=', now()->startOfMonth())
            ->distinct('visit_id')
            ->count('visit_id');
    }

    /**
     * Check if this insurance has remaining annual limit (uses insurance_usages).
     */
    public function getRemainingAnnualLimitAttribute(): ?float
    {
        $limit = $this->insuranceProvider->annual_limit;
        if ($limit === null) {
            return null;
        }

        return max(0, (float) $limit - $this->usedThisYear());
    }

    /**
     * Remaining monthly limit.
     */
    public function getRemainingMonthlyLimitAttribute(): ?float
    {
        $limit = $this->insuranceProvider->max_per_month;
        if ($limit === null) {
            return null;
        }

        return max(0, (float) $limit - $this->usedThisMonth());
    }

    /**
     * Check if per-visit limit allows the given amount.
     */
    public function canCoverAmount(float $amount): bool
    {
        $provider = $this->insuranceProvider;

        if ($provider->per_visit_limit !== null && $amount > $provider->per_visit_limit) {
            return false;
        }

        $remaining = $this->remaining_annual_limit;
        if ($remaining !== null && $amount > $remaining) {
            return false;
        }

        return true;
    }
}
