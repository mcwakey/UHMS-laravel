<?php

namespace App\Models;

use App\Enums\MemberType;
use App\Enums\InvoiceStatus;
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
        'insurance_tier_id',
        'member_type',
        'card_holder_insurance_id',
        'membership_number',
        'policy_number',
        'ccc_code',
        'start_date',
        'expiry_date',
        'is_primary',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'start_date'  => 'date',
            'expiry_date' => 'date',
            'is_primary'  => 'boolean',
            'is_active'   => 'boolean',
            'member_type' => MemberType::class,
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

    public function insuranceTier(): BelongsTo
    {
        return $this->belongsTo(InsuranceTier::class);
    }

    /** Card holder record (for beneficiaries). */
    public function cardHolder(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class, 'card_holder_insurance_id');
    }

    /** All beneficiaries enrolled under this card holder record. */
    public function beneficiaries(): HasMany
    {
        return $this->hasMany(PatientInsurance::class, 'card_holder_insurance_id');
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

    public function scopeHolders($query)
    {
        return $query->where('member_type', 'holder');
    }

    public function scopeBeneficiaries($query)
    {
        return $query->where('member_type', 'beneficiary');
    }

    // ── Accessors ────────────────────────────────────

    public function getIsExpiredAttribute(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function getIsValidAttribute(): bool
    {
        return $this->is_active && ! $this->is_expired;
    }

    public function getIsHolderAttribute(): bool
    {
        return ($this->member_type?->value ?? 'holder') === 'holder';
    }

    public function getIsBeneficiaryAttribute(): bool
    {
        return ($this->member_type?->value ?? 'holder') === 'beneficiary';
    }

    /**
     * Remaining annual limit based on tier constraints for this member type.
     */
    public function getRemainingAnnualLimitAttribute(): ?float
    {
        $tier = $this->insuranceTier;
        if (! $tier) {
            return null;
        }

        $limit = $tier->effectiveConstraints($this->member_type?->value ?? 'holder')['annual_limit'];
        if ($limit === null) {
            return null;
        }

        return max(0, (float) $limit - $this->usedThisYear());
    }

    /**
     * Remaining monthly limit based on tier constraints for this member type.
     */
    public function getRemainingMonthlyLimitAttribute(): ?float
    {
        $tier = $this->insuranceTier;
        if (! $tier) {
            return null;
        }

        $limit = $tier->effectiveConstraints($this->member_type?->value ?? 'holder')['max_per_month'];
        if ($limit === null) {
            return null;
        }

        return max(0, (float) $limit - $this->usedThisMonth());
    }

    // ── Usage helpers ─────────────────────────────────

    public function usedThisYear(): float
    {
        $usageTotal = (float) $this->usages()
            ->where('created_at', '>=', now()->startOfYear())
            ->sum('amount_covered');

        $billedTotal = (float) $this->coveredInvoiceItemsQuery()
            ->where('invoice_items.created_at', '>=', now()->startOfYear())
            ->sum('insurance_covered');

        return max($usageTotal, $billedTotal);
    }

    public function usedThisMonth(): float
    {
        $usageTotal = (float) $this->usages()
            ->where('created_at', '>=', now()->startOfMonth())
            ->sum('amount_covered');

        $billedTotal = (float) $this->coveredInvoiceItemsQuery()
            ->where('invoice_items.created_at', '>=', now()->startOfMonth())
            ->sum('insurance_covered');

        return max($usageTotal, $billedTotal);
    }

    public function usedForVisit(int $visitId): float
    {
        $usageTotal = (float) $this->usages()
            ->where('visit_id', $visitId)
            ->sum('amount_covered');

        $billedTotal = (float) $this->coveredInvoiceItemsQuery()
            ->where('visit_id', $visitId)
            ->sum('insurance_covered');

        return max($usageTotal, $billedTotal);
    }

    public function visitsThisMonth(): int
    {
        $usageVisitIds = $this->usages()
            ->where('created_at', '>=', now()->startOfMonth())
            ->distinct('visit_id')
            ->pluck('visit_id');

        $billedVisitIds = $this->coveredInvoiceItemsQuery()
            ->where('invoice_items.created_at', '>=', now()->startOfMonth())
            ->whereNotNull('visit_id')
            ->distinct('visit_id')
            ->pluck('visit_id');

        return $usageVisitIds->merge($billedVisitIds)
            ->filter()
            ->unique()
            ->count();
    }

    private function coveredInvoiceItemsQuery()
    {
        return InvoiceItem::query()
            ->where('insurance_covered', '>', 0)
            ->whereNotIn('payment_status', ['cancelled', 'voided'])
            ->where(function ($query) {
                $query->where('patient_insurance_id', $this->id)
                    ->orWhere(function ($fallback) {
                        $fallback->whereNull('patient_insurance_id')
                            ->where('patient_id', $this->patient_id)
                            ->where('insurance_provider_id', $this->insurance_provider_id);
                    });
            })
            ->whereHas('invoice', function ($query) {
                $query->whereNotIn('status', [
                    InvoiceStatus::CANCELLED->value,
                    InvoiceStatus::REFUNDED->value,
                ]);
            });
    }

    // ── Beneficiary helpers ───────────────────────────

    /**
     * Count of active beneficiaries under this card holder record.
     */
    public function activeBeneficiaryCount(): int
    {
        return $this->beneficiaries()->where('is_active', true)->count();
    }

    /**
     * Whether another beneficiary can be added under this card holder, given the tier limit.
     */
    public function canAddBeneficiary(): bool
    {
        $tier = $this->insuranceTier;
        if (! $tier || $tier->max_beneficiaries === null) {
            return true;
        }

        return $this->activeBeneficiaryCount() < $tier->max_beneficiaries;
    }

    /**
     * Check if per-visit limit allows the given amount (uses tier constraints).
     */
    public function canCoverAmount(float $amount): bool
    {
        $tier = $this->insuranceTier;
        if (! $tier) {
            return false;
        }

        $constraints = $tier->effectiveConstraints($this->member_type?->value ?? 'holder');

        if ($constraints['per_visit_limit'] !== null && $amount > $constraints['per_visit_limit']) {
            return false;
        }

        $remaining = $this->remaining_annual_limit;
        if ($remaining !== null && $amount > $remaining) {
            return false;
        }

        return true;
    }
}
