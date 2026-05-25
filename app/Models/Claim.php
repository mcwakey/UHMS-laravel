<?php

namespace App\Models;

use App\Enums\ClaimStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Claim extends Model
{
    use GeneratesNumbers, HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'claim_number',
        'claim_type_code',
        'claim_workflow_code',
        'insurance_type_id',
        'insurance_provider_id',
        'patient_id',
        'visit_id',
        'invoice_id',
        'patient_insurance_id',
        'insurance_verification_id',
        'verification_reference',
        'membership_number',
        'verification_code',
        'authorization_code',
        'claim_date',
        'claim_period_start',
        'claim_period_end',
        'period_from',
        'period_to',
        'total_claim_amount',
        'total_amount',
        'approved_amount',
        'rejected_amount',
        'paid_amount',
        'status',
        'submission_mode',
        'submission_reference',
        'export_file_path',
        'submitted_at',
        'submitted_by',
        'reviewed_at',
        'prepared_by',
        'reviewed_by',
        'approved_by',
        'reviewer_notes',
        'rejection_reason',
        'notes',
        'assigned_doctor_id',
        'created_by',
    ];

    protected $casts = [
        'status' => ClaimStatus::class,
        'claim_date' => 'date',
        'claim_period_start' => 'date',
        'claim_period_end' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'total_claim_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'rejected_amount' => 'decimal:2',
        'paid_amount' => 'decimal:2',
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'total_amount', 'approved_amount', 'assigned_doctor_id'])
            ->logOnlyDirty()
            ->useLogName('claims');
    }

    // ── Relationships ────────────────────────────────
    public function insuranceProvider(): BelongsTo
    {
        return $this->belongsTo(InsuranceProvider::class);
    }

    public function insuranceType(): BelongsTo
    {
        return $this->belongsTo(InsuranceType::class);
    }

    public function patientInsurance(): BelongsTo
    {
        return $this->belongsTo(PatientInsurance::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ClaimItem::class);
    }

    public function assignedDoctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function createdByUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function preparedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function submittedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(ClaimStatusLog::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ClaimPayment::class);
    }

    // ── Scopes ───────────────────────────────────────
    public function scopeByStatus($query, ClaimStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByProvider($query, int $providerId)
    {
        return $query->where('insurance_provider_id', $providerId);
    }

    public function scopeDateRange($query, ?string $from, ?string $to)
    {
        if ($from) {
            $query->where('claim_date', '>=', $from);
        }
        if ($to) {
            $query->where('claim_date', '<=', $to);
        }

        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) {
            return $query;
        }

        return $query->where(function ($q) use ($search) {
            $q->where('claim_number', 'like', "%{$search}%")
                ->orWhereHas('patient', function ($pq) use ($search) {
                    $pq->where('first_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%")
                        ->orWhere('patient_number', 'like', "%{$search}%");
                });
        });
    }

    // ── Helpers ──────────────────────────────────────
    public static function generateClaimNumber(): string
    {
        return self::generateNumber('CLM', 'claims', 'claim_number');
    }

    public function getIsEditableAttribute(): bool
    {
        return in_array($this->status, [ClaimStatus::DRAFT, ClaimStatus::REJECTED]);
    }

    public function getIsReviewableAttribute(): bool
    {
        return in_array($this->status, [
            ClaimStatus::SUBMITTED,
            ClaimStatus::ACKNOWLEDGED,
            ClaimStatus::UNDER_REVIEW,
            ClaimStatus::APPEALED,
            ClaimStatus::RESUBMITTED,
        ]);
    }

    public function recalculateTotal(): void
    {
        $total = $this->items()->sum('claim_amount') ?: $this->items()->sum('total_price');

        $this->update([
            'total_amount' => $total,
            'total_claim_amount' => $total,
        ]);
    }

    public function recalculateApproved(): void
    {
        $this->update([
            'approved_amount' => $this->items()->whereNotNull('approved_amount')->sum('approved_amount'),
        ]);
    }
}
