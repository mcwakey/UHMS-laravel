<?php

namespace App\Models;

use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
// use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class LabRequest extends Model
{
    use GeneratesNumbers;

    protected $fillable = [
        'request_number',
        'sample_id',
        'visit_id',
        'emergency_case_id',
        'emergency_session_id',
        'medical_record_id',
        'consultation_route_id',
        'patient_id',
        'external_party_name',
        'external_party_contact',
        'requested_by',
        'department_id',
        'target_department_id',
        'clinical_info',
        'urgency',
        'is_emergency',
        'status',
    ];

    protected $casts = [
        'is_emergency' => 'boolean',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function emergencyCase(): BelongsTo
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function emergencySession(): BelongsTo
    {
        return $this->belongsTo(EmergencySession::class);
    }

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function consultationRoute(): BelongsTo
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function targetDepartment(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'target_department_id');
    }

    public function getResultTypeAttribute(): ?\App\Enums\ResultType
    {
        return $this->targetDepartment?->result_type;
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabRequestItem::class);
    }

    public function results(): HasMany
    {
        return $this->hasMany(LabResult::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('request_number', 'like', "%{$term}%")
              ->orWhere('clinical_info', 'like', "%{$term}%")
              ->orWhereHas('patient', function ($pq) use ($term) {
                  $pq->where('first_name', 'like', "%{$term}%")
                     ->orWhere('last_name', 'like', "%{$term}%")
                     ->orWhere('patient_number', 'like', "%{$term}%");
              });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function generateRequestNumber(): string
    {
        return static::generateNumber('INV', 'lab_requests', 'request_number');
    }

    /** Display name for the subject — facility patient or external / walk-in party. */
    public function partyName(): string
    {
        return $this->patient?->full_name ?: ($this->external_party_name ?: 'Unknown');
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'warning',
            'processing' => 'info',
            'completed' => 'success',
            'cancelled' => 'danger',
            default => 'secondary',
        };
    }

    public function getStatusLabelAttribute(): string
    {
        return ucfirst($this->status);
    }

    public function getUrgencyColorAttribute(): string
    {
        return match ($this->urgency) {
            'emergency' => 'danger',
            'urgent' => 'warning',
            default => 'secondary',
        };
    }

    public function getCompletionPercentageAttribute(): int
    {
        $total = $this->items->count();
        if ($total === 0) return 0;

        $completed = $this->items->where('status', 'completed')->count();
        return (int) round(($completed / $total) * 100);
    }

    /**
     * Patient/visit/clinical context for the activity log → patient timeline.
     */
    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'medical_record_id' => $this->medical_record_id,
            'consultation_route_id' => $this->consultation_route_id,
            'emergency_case_id' => $this->emergency_case_id,
            'department_id' => $this->target_department_id ?: $this->department_id,
            'investigation_request_id' => $this->id,
            'sample_id' => $this->sample_id,
            'source_type' => 'lab_request',
            'source_id' => $this->id,
        ], fn ($v) => $v !== null);
    }
}
