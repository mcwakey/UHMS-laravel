<?php

namespace App\Models;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskStatus;
use App\Enums\VisitPaymentArrangementSource;
use App\Enums\VisitPaymentArrangementStatus;
use App\Enums\VisitPaymentTimingPolicy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A per-visit payment arrangement request/approval record (Payment Timing Policy
 * Phase 7).
 *
 * An APPROVED arrangement is administrative only — it drives no payment gate,
 * invoice or visit change in Phase 7. Accessors here must never call the gate,
 * resolver or override services.
 */
class VisitPaymentArrangement extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'visit_payment_policy_id',
        'requested_policy',
        'approved_policy',
        'source',
        'status',
        'request_reason_code',
        'request_reason',
        'supporting_reference',
        'requested_by',
        'requested_at',
        'reviewed_by',
        'reviewed_at',
        'review_decision_reason',
        'approved_by',
        'approved_at',
        'effective_from',
        'expires_at',
        'withdrawn_by',
        'withdrawn_at',
        'withdrawal_reason',
        'revoked_by',
        'revoked_at',
        'revocation_reason',
        'replaced_by_arrangement_id',
        'requires_approval',
        'requires_separate_approver',
        'risk_level_snapshot',
        'risk_status_snapshot',
        'baseline_policy_snapshot',
        'recommended_policy_snapshot',
        'finance_review_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'requested_policy' => VisitPaymentTimingPolicy::class,
            'approved_policy' => VisitPaymentTimingPolicy::class,
            'source' => VisitPaymentArrangementSource::class,
            'status' => VisitPaymentArrangementStatus::class,
            'risk_level_snapshot' => PatientFinancialRiskLevel::class,
            'risk_status_snapshot' => PatientFinancialRiskStatus::class,
            'baseline_policy_snapshot' => VisitPaymentTimingPolicy::class,
            'recommended_policy_snapshot' => VisitPaymentTimingPolicy::class,
            'requires_approval' => 'boolean',
            'requires_separate_approver' => 'boolean',
            'finance_review_snapshot' => 'boolean',
            'requested_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'approved_at' => 'datetime',
            'withdrawn_at' => 'datetime',
            'revoked_at' => 'datetime',
            'effective_from' => 'date',
            'expires_at' => 'date',
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

    public function materializedPolicy(): BelongsTo
    {
        return $this->belongsTo(VisitPaymentPolicy::class, 'visit_payment_policy_id');
    }

    public function requester(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function withdrawer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'withdrawn_by');
    }

    public function revoker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'revoked_by');
    }

    public function replacement(): BelongsTo
    {
        return $this->belongsTo(self::class, 'replaced_by_arrangement_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(VisitPaymentArrangementHistory::class)->latest('performed_at')->latest('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', VisitPaymentArrangementStatus::PENDING->value);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', VisitPaymentArrangementStatus::APPROVED->value);
    }

    /** The single current approved arrangement (approved and not yet terminal). */
    public function scopeCurrent(Builder $query): Builder
    {
        return $query->approved();
    }

    public function scopeExpired(Builder $query, ?\DateTimeInterface $asOf = null): Builder
    {
        return $query->approved()
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $asOf ?? now());
    }

    public function scopeForRequestedPolicy(Builder $query, VisitPaymentTimingPolicy|string $policy): Builder
    {
        return $query->where('requested_policy', $policy instanceof VisitPaymentTimingPolicy ? $policy->value : $policy);
    }

    public function scopeForApprovedPolicy(Builder $query, VisitPaymentTimingPolicy|string $policy): Builder
    {
        return $query->where('approved_policy', $policy instanceof VisitPaymentTimingPolicy ? $policy->value : $policy);
    }

    public function scopeRequiringReview(Builder $query): Builder
    {
        return $query->pending()->where('requires_approval', true);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers (state only — no payment decisions)
    |--------------------------------------------------------------------------
    */

    public function isPending(): bool
    {
        return $this->status === VisitPaymentArrangementStatus::PENDING;
    }

    public function isApproved(): bool
    {
        return $this->status === VisitPaymentArrangementStatus::APPROVED;
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }
}
