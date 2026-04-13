<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PatientInsurance extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'insurance_provider_id',
        'membership_number',
        'policy_number',
        'expiry_date',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
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
     * Check if this insurance has remaining annual limit.
     */
    public function getRemainingAnnualLimitAttribute(): ?float
    {
        $limit = $this->insuranceProvider->annual_limit;
        if ($limit === null) {
            return null; // No limit (e.g., Cash & Carry)
        }

        $yearStart = now()->startOfYear();
        $usedAmount = \App\Models\Invoice::where('patient_id', $this->patient_id)
            ->whereHas('visit', fn ($q) => $q->where('visit_insurance_id', $this->id))
            ->where('created_at', '>=', $yearStart)
            ->sum('total_amount');

        return max(0, $limit - $usedAmount);
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
