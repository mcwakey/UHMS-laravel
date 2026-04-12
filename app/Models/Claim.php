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
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    protected $fillable = [
        'claim_number',
        'insurance_provider_id',
        'patient_id',
        'visit_id',
        'invoice_id',
        'claim_date',
        'period_from',
        'period_to',
        'total_amount',
        'approved_amount',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewer_notes',
        'assigned_doctor_id',
        'created_by',
    ];

    protected $casts = [
        'status' => ClaimStatus::class,
        'claim_date' => 'date',
        'period_from' => 'date',
        'period_to' => 'date',
        'total_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
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
        if ($from) $query->where('claim_date', '>=', $from);
        if ($to) $query->where('claim_date', '<=', $to);
        return $query;
    }

    public function scopeSearch($query, ?string $search)
    {
        if (! $search) return $query;

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
        return in_array($this->status, [ClaimStatus::SUBMITTED, ClaimStatus::UNDER_REVIEW, ClaimStatus::APPEALED]);
    }

    public function recalculateTotal(): void
    {
        $this->update([
            'total_amount' => $this->items()->sum('total_price'),
        ]);
    }

    public function recalculateApproved(): void
    {
        $this->update([
            'approved_amount' => $this->items()->whereNotNull('approved_amount')->sum('approved_amount'),
        ]);
    }
}
