<?php

namespace App\Models;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskStatus;
use App\Enums\VisitPaymentPolicySource;
use App\Enums\VisitPaymentTimingPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Observational visit-payment-policy record (Payment Timing Policy Phase 6).
 *
 * Stores the baseline typed policy SEPARATELY from a non-operational risk-based
 * recommendation. Accessors here must never call resolvers, gates or refresh
 * logic — reading this model triggers no policy computation.
 */
class VisitPaymentPolicy extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'resolved_policy',
        'resolution_source',
        'resolution_reason_code',
        'recommended_policy',
        'recommendation_source',
        'recommendation_reason_code',
        'requires_finance_review',
        'global_default_snapshot',
        'visit_type_policy_snapshot',
        'visit_type_snapshot',
        'emergency_protection_snapshot',
        'compatible_override_type_snapshot',
        'compatible_override_scope_snapshot',
        'compatible_override_id_snapshot',
        'patient_financial_risk_profile_id',
        'patient_risk_level_snapshot',
        'patient_risk_status_snapshot',
        'patient_risk_reason_snapshot',
        'patient_risk_observed_at',
        'resolution_version',
        'materialized_at',
        'last_refreshed_at',
        // Phase 7 — additive link to the current approved arrangement (administrative only).
        'current_approved_arrangement_id',
        'approved_policy_snapshot',
        'approved_arrangement_observed_at',
    ];

    protected function casts(): array
    {
        return [
            'resolved_policy' => VisitPaymentTimingPolicy::class,
            'resolution_source' => VisitPaymentPolicySource::class,
            'recommended_policy' => VisitPaymentTimingPolicy::class,
            'recommendation_source' => VisitPaymentPolicySource::class,
            'patient_risk_level_snapshot' => PatientFinancialRiskLevel::class,
            'patient_risk_status_snapshot' => PatientFinancialRiskStatus::class,
            'requires_finance_review' => 'boolean',
            'emergency_protection_snapshot' => 'boolean',
            'patient_risk_observed_at' => 'datetime',
            'materialized_at' => 'datetime',
            'last_refreshed_at' => 'datetime',
            'approved_policy_snapshot' => VisitPaymentTimingPolicy::class,
            'approved_arrangement_observed_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patientFinancialRiskProfile(): BelongsTo
    {
        return $this->belongsTo(PatientFinancialRiskProfile::class, 'patient_financial_risk_profile_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(VisitPaymentPolicyHistory::class)->latest('performed_at')->latest('id');
    }

    public function arrangements(): HasMany
    {
        return $this->hasMany(VisitPaymentArrangement::class, 'visit_payment_policy_id');
    }

    public function currentApprovedArrangement(): BelongsTo
    {
        return $this->belongsTo(VisitPaymentArrangement::class, 'current_approved_arrangement_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeRequiringFinanceReview(Builder $query): Builder
    {
        return $query->where('requires_finance_review', true);
    }

    public function scopeForResolvedPolicy(Builder $query, VisitPaymentTimingPolicy|string $policy): Builder
    {
        return $query->where('resolved_policy', $policy instanceof VisitPaymentTimingPolicy ? $policy->value : $policy);
    }

    public function scopeForRecommendation(Builder $query, VisitPaymentTimingPolicy|string $policy): Builder
    {
        return $query->where('recommended_policy', $policy instanceof VisitPaymentTimingPolicy ? $policy->value : $policy);
    }

    public function scopeWithRiskSnapshot(Builder $query): Builder
    {
        return $query->whereNotNull('patient_financial_risk_profile_id');
    }

    public function scopeMaterializedBetween(Builder $query, mixed $from, mixed $to): Builder
    {
        return $query->whereBetween('materialized_at', [$from, $to]);
    }
}
