<?php

namespace App\Models;

use App\Enums\AdmissionRequestSource;
use App\Enums\AdmissionRequestStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class AdmissionRequest extends Model
{
    use HasFactory, LogsActivity, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'visit_id',
        'source_type',
        'source_id',
        'requested_by',
        'accepted_by',
        'rejected_by',
        'cancelled_by',
        'requested_ward_id',
        'preferred_bed_type',
        'reserved_bed_id',
        'priority',
        'provisional_diagnosis',
        'clinical_summary',
        'status',
        'requested_at',
        'accepted_at',
        'rejected_at',
        'cancelled_at',
        'converted_at',
        'reason',
    ];

    protected function casts(): array
    {
        return [
            'source_type' => AdmissionRequestSource::class,
            'status' => AdmissionRequestStatus::class,
            'requested_at' => 'datetime',
            'accepted_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'converted_at' => 'datetime',
        ];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'requested_ward_id', 'reserved_bed_id', 'priority'])
            ->logOnlyDirty()
            ->useLogName('admission_requests')
            ->dontSubmitEmptyLogs();
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function acceptedBy()
    {
        return $this->belongsTo(User::class, 'accepted_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function cancelledBy()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function requestedWard()
    {
        return $this->belongsTo(Ward::class, 'requested_ward_id');
    }

    public function reservedBed()
    {
        return $this->belongsTo(Bed::class, 'reserved_bed_id');
    }

    public function admission()
    {
        return $this->hasOne(Admission::class);
    }

    public function bedReservations()
    {
        return $this->hasMany(BedReservation::class);
    }

    public function activeBedReservation()
    {
        return $this->hasOne(BedReservation::class)->where('status', 'active')->latestOfMany();
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            AdmissionRequestStatus::CONVERTED->value,
            AdmissionRequestStatus::REJECTED->value,
            AdmissionRequestStatus::CANCELLED->value,
        ]);
    }

    public function scopeSearch($query, ?string $term)
    {
        if (! $term) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('priority', 'like', "%{$term}%")
                ->orWhere('provisional_diagnosis', 'like', "%{$term}%")
                ->orWhereHas('patient', function ($patient) use ($term) {
                    $patient->where('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%")
                        ->orWhere('patient_number', 'like', "%{$term}%");
                })
                ->orWhereHas('visit', function ($visit) use ($term) {
                    $visit->where('visit_number', 'like', "%{$term}%");
                });
        });
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_request_id' => $this->id,
            'source_type' => 'admission_request',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
