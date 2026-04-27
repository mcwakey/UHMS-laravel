<?php

namespace App\Models;

use App\Enums\AdmissionStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Admission extends Model
{
    use HasFactory, SoftDeletes, GeneratesNumbers, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'bed_id', 'admission_date', 'actual_discharge_date'])
            ->logOnlyDirty()
            ->useLogName('admissions')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'admission_number',
        'visit_id',
        'patient_id',
        'bed_id',
        'admitted_by',
        'admitting_diagnosis',
        'admission_date',
        'expected_discharge_date',
        'actual_discharge_date',
        'discharged_by',
        'discharge_summary',
        'discharge_instructions',
        'status',
        'admission_type',
        'admission_fee_service_id',
        'consumable_fee_service_id',
    ];

    protected function casts(): array
    {
        return [
            'status' => AdmissionStatus::class,
            'admission_date' => 'datetime',
            'expected_discharge_date' => 'date',
            'actual_discharge_date' => 'datetime',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function ward()
    {
        return $this->hasOneThrough(Ward::class, Bed::class, 'id', 'id', 'bed_id', 'ward_id');
    }

    public function admittedBy()
    {
        return $this->belongsTo(User::class, 'admitted_by');
    }

    public function dischargedBy()
    {
        return $this->belongsTo(User::class, 'discharged_by');
    }

    public function wardRounds()
    {
        return $this->hasMany(WardRound::class)->orderByDesc('round_date');
    }

    public function vitals()
    {
        return $this->hasMany(Vital::class)->orderByDesc('recorded_at');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query->where('status', AdmissionStatus::ADMITTED);
    }

    public function scopeByStatus($query, AdmissionStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByWard($query, $wardId)
    {
        return $query->whereHas('bed', function ($q) use ($wardId) {
            $q->where('ward_id', $wardId);
        });
    }

    public function scopeSearch($query, ?string $term)
    {
        if (!$term) return $query;

        return $query->where(function ($q) use ($term) {
            $q->where('admission_number', 'like', "%{$term}%")
              ->orWhereHas('patient', function ($pq) use ($term) {
                  $pq->where('first_name', 'like', "%{$term}%")
                     ->orWhere('last_name', 'like', "%{$term}%")
                     ->orWhere('patient_number', 'like', "%{$term}%");
              });
        });
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */

    public function getLengthOfStayAttribute(): ?int
    {
        if (!$this->admission_date) return null;

        $end = $this->actual_discharge_date ?? now();
        return (int) $this->admission_date->diffInDays($end);
    }

    public function getIsActiveAttribute(): bool
    {
        return $this->status === AdmissionStatus::ADMITTED;
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function generateAdmissionNumber(): string
    {
        return static::generateNumber('ADM', 'admissions', 'admission_number');
    }
}
