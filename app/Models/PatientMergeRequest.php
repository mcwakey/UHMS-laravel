<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PatientMergeRequest extends Model
{
    use HasFactory;

    public const STATUS_PENDING_REVIEW = 'PENDING_REVIEW';
    public const STATUS_APPROVED = 'APPROVED';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'request_number',
        'main_patient_id',
        'duplicate_patient_id',
        'requested_by',
        'approved_by',
        'executed_by',
        'status',
        'reason',
        'match_confidence',
        'field_resolution',
        'preview_summary',
        'approved_at',
        'executed_at',
        'rejected_at',
        'rejection_reason',
    ];

    protected function casts(): array
    {
        return [
            'field_resolution' => 'array',
            'preview_summary' => 'array',
            'approved_at' => 'datetime',
            'executed_at' => 'datetime',
            'rejected_at' => 'datetime',
        ];
    }

    public function mainPatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'main_patient_id');
    }

    public function duplicatePatient(): BelongsTo
    {
        return $this->belongsTo(Patient::class, 'duplicate_patient_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function executedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'executed_by');
    }

    public function logs(): HasMany
    {
        return $this->hasMany(\App\Models\PatientMergeLog::class, 'patient_merge_request_id')->latest('occurred_at');
    }

    public function getCanExecuteAttribute(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING_REVIEW, self::STATUS_APPROVED], true);
    }
}
