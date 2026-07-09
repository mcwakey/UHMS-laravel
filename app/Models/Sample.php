<?php

namespace App\Models;

use App\Enums\SampleStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sample extends Model
{
    use GeneratesNumbers;

    protected $fillable = [
        'sample_number',
        'barcode',
        'lab_request_id',
        'specimen_type',
        'container',
        'status',
        'collected_by',
        'collected_at',
        'received_by',
        'received_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'disposed_by',
        'disposed_at',
        'notes',
    ];

    protected $casts = [
        'status'       => SampleStatus::class,
        'collected_at' => 'datetime',
        'received_at'  => 'datetime',
        'rejected_at'  => 'datetime',
        'disposed_at'  => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function labRequest(): BelongsTo
    {
        return $this->belongsTo(LabRequest::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(LabRequestItem::class);
    }

    public function collectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'collected_by');
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function rejectedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function disposedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeSpecimenType($query, string $type)
    {
        return $query->where('specimen_type', $type);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('sample_number', 'like', "%{$term}%")
              ->orWhere('barcode', 'like', "%{$term}%")
              ->orWhereHas('labRequest', fn ($rq) => $rq->search($term));
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function generateSampleNumber(): string
    {
        return static::generateNumber('SMP', 'samples', 'sample_number');
    }

    /** Config definition for this sample's specimen type. */
    public function specimenConfig(): array
    {
        return config('specimens.types.' . $this->specimen_type, [
            'label'     => ucfirst(str_replace('_', ' ', (string) $this->specimen_type)),
            'container' => null,
            'color'     => 'secondary',
            'icon'      => 'ti-flask',
        ]);
    }

    /** Translated, human label for the specimen type. */
    public function specimenLabel(): string
    {
        $key = 'samples.specimen.' . $this->specimen_type;

        return \Illuminate\Support\Facades\Lang::has($key)
            ? __($key)
            : ($this->specimenConfig()['label'] ?? (string) $this->specimen_type);
    }

    public function getStatusEnumAttribute(): SampleStatus
    {
        return $this->status instanceof SampleStatus
            ? $this->status
            : (SampleStatus::tryFrom((string) $this->status) ?? SampleStatus::PENDING);
    }

    public function getStatusColorAttribute(): string
    {
        return $this->status_enum->color();
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->status_enum->translatedLabel();
    }

    public function isReceived(): bool
    {
        return $this->status_enum->isReceived();
    }

    public function isTerminal(): bool
    {
        return $this->status_enum->isTerminal();
    }

    /**
     * Patient/visit/clinical context for the activity log → patient timeline.
     */
    public function toActivityContext(): array
    {
        $request = $this->relationLoaded('labRequest') ? $this->labRequest : $this->labRequest()->first();

        return array_filter([
            'patient_id'               => $request?->patient_id,
            'visit_id'                 => $request?->visit_id,
            'medical_record_id'        => $request?->medical_record_id,
            'consultation_route_id'    => $request?->consultation_route_id,
            'emergency_case_id'        => $request?->emergency_case_id,
            'department_id'            => $request?->target_department_id ?: $request?->department_id,
            'investigation_request_id' => $this->lab_request_id,
            'sample_id'                => $this->id,
            'source_type'              => 'sample',
            'source_id'                => $this->id,
        ], fn ($v) => $v !== null);
    }
}
