<?php

namespace App\Models;

use App\Enums\PatientFinancialRiskLevel;
use App\Enums\PatientFinancialRiskReason;
use App\Enums\PatientFinancialRiskStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Patient financial-risk profile (Payment Timing Policy Phase 5).
 *
 * A controlled administrative classification. It performs NO payment decision:
 * accessors here must not call payment gates, resolvers or override services.
 */
class PatientFinancialRiskProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'risk_level',
        'primary_reason',
        'reason_details',
        'status',
        'credit_limit',
        'effective_from',
        'review_due_at',
        'expires_at',
        'reference',
        'set_by',
        'reviewed_by',
        'reviewed_at',
        'suspended_by',
        'suspended_at',
        'cleared_by',
        'cleared_at',
        'clearance_reason',
    ];

    protected function casts(): array
    {
        return [
            'risk_level' => PatientFinancialRiskLevel::class,
            'primary_reason' => PatientFinancialRiskReason::class,
            'status' => PatientFinancialRiskStatus::class,
            'credit_limit' => 'decimal:2',
            'effective_from' => 'date',
            'review_due_at' => 'date',
            'expires_at' => 'date',
            'reviewed_at' => 'datetime',
            'suspended_at' => 'datetime',
            'cleared_at' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function setter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'set_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function suspender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    public function clearer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cleared_by');
    }

    public function history(): HasMany
    {
        return $this->hasMany(PatientFinancialRiskHistory::class)->latest('performed_at')->latest('id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    /** Profiles occupying the patient's single active slot (active / under_review). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(
            fn (PatientFinancialRiskStatus $s) => $s->value,
            PatientFinancialRiskStatus::activeSlotStatuses(),
        ));
    }

    /** Active-slot profiles carrying an actual restriction (not "normal"). */
    public function scopeRestrictive(Builder $query): Builder
    {
        return $query->active()->whereIn('risk_level', array_map(
            fn (PatientFinancialRiskLevel $l) => $l->value,
            PatientFinancialRiskLevel::restrictive(),
        ));
    }

    public function scopeDueForReview(Builder $query, ?\DateTimeInterface $asOf = null): Builder
    {
        $asOf ??= now();

        return $query->active()
            ->whereNotNull('review_due_at')
            ->whereDate('review_due_at', '<=', $asOf);
    }

    /** Active-slot or suspended profiles whose expiry date has passed. */
    public function scopeExpired(Builder $query, ?\DateTimeInterface $asOf = null): Builder
    {
        $asOf ??= now();

        return $query->whereIn('status', [
            PatientFinancialRiskStatus::ACTIVE->value,
            PatientFinancialRiskStatus::UNDER_REVIEW->value,
            PatientFinancialRiskStatus::SUSPENDED->value,
        ])
            ->whereNotNull('expires_at')
            ->whereDate('expires_at', '<=', $asOf);
    }

    public function scopeForRiskLevel(Builder $query, PatientFinancialRiskLevel|string $level): Builder
    {
        return $query->where('risk_level', $level instanceof PatientFinancialRiskLevel ? $level->value : $level);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers (no payment decisions — display/state only)
    |--------------------------------------------------------------------------
    */

    public function occupiesActiveSlot(): bool
    {
        return $this->status->occupiesActiveSlot();
    }

    public function isRestrictive(): bool
    {
        return $this->occupiesActiveSlot() && $this->risk_level->isRestrictive();
    }

    public function isTerminal(): bool
    {
        return $this->status->isTerminal();
    }

    public function isReviewOverdue(?\DateTimeInterface $asOf = null): bool
    {
        $asOf ??= now();

        return $this->occupiesActiveSlot()
            && $this->review_due_at !== null
            && $this->review_due_at->lte($asOf);
    }

    public function isPastExpiry(?\DateTimeInterface $asOf = null): bool
    {
        $asOf ??= now();

        return $this->expires_at !== null && $this->expires_at->lte($asOf);
    }
}
