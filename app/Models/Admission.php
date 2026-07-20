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
    use GeneratesNumbers, HasFactory, LogsActivity, SoftDeletes;

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
        'admission_request_id',
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
        'care_flags',
        'discharge_planning_started_at',
        'discharge_planning_started_by',
        'expected_discharge_at',
        'discharge_planning_note',
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
            'care_flags' => 'array',
            'discharge_planning_started_at' => 'datetime',
            'expected_discharge_at' => 'datetime',
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

    public function admissionRequest()
    {
        return $this->belongsTo(AdmissionRequest::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function locationHistories()
    {
        return $this->hasMany(AdmissionLocationHistory::class)->orderByDesc('moved_at')->orderByDesc('id');
    }

    public function bedReservations()
    {
        return $this->hasMany(BedReservation::class);
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

    public function dischargePlanningStartedBy()
    {
        return $this->belongsTo(User::class, 'discharge_planning_started_by');
    }

    public function wardRounds()
    {
        return $this->hasMany(WardRound::class)->orderByDesc('round_date');
    }

    public function vitals()
    {
        return $this->hasMany(Vital::class)->orderByDesc('recorded_at');
    }

    public function medicationOrders()
    {
        return $this->hasMany(MedicationOrder::class);
    }

    public function medicationSchedules()
    {
        return $this->hasMany(MedicationAdministrationSchedule::class);
    }

    public function medicationAdministrations()
    {
        return $this->hasMany(MedicationAdministration::class);
    }

    public function clinicalTasks()
    {
        return $this->hasMany(ClinicalTask::class);
    }

    public function nursingNotes()
    {
        return $this->hasMany(NursingNote::class)->latest('observed_at')->latest('id');
    }

    public function nursingTasks()
    {
        return $this->hasMany(NursingTask::class)->orderByRaw('completed_at is not null')->orderBy('due_at');
    }

    public function openNursingTasks()
    {
        return $this->hasMany(NursingTask::class)->open()->orderBy('due_at');
    }

    public function dischargeClearances()
    {
        return $this->hasMany(AdmissionDischargeClearance::class)->orderBy('clearance_type');
    }

    public function dischargeSummaryRecord()
    {
        return $this->hasOne(AdmissionDischargeSummary::class);
    }

    public function pregnancyProfiles()
    {
        return $this->hasMany(PregnancyProfile::class);
    }

    public function maternityCases()
    {
        return $this->hasMany(MaternityCase::class);
    }

    public function antenatalVisits()
    {
        return $this->hasMany(AntenatalVisit::class);
    }

    public function laborEpisodes()
    {
        return $this->hasMany(LaborEpisode::class);
    }

    public function deliveryRecords()
    {
        return $this->hasMany(DeliveryRecord::class);
    }

    public function newbornRecords()
    {
        return $this->hasMany(NewbornRecord::class);
    }

    public function postnatalCases()
    {
        return $this->hasMany(PostnatalCase::class)->latest('opened_at')->latest('id');
    }

    public function postnatalMotherObservations()
    {
        return $this->hasMany(PostnatalMotherObservation::class)->latest('observed_at')->latest('id');
    }

    public function postnatalNewbornObservations()
    {
        return $this->hasMany(PostnatalNewbornObservation::class)->latest('observed_at')->latest('id');
    }

    public function serviceRenderings()
    {
        return $this->hasMany(ServiceRendering::class);
    }

    public function bloodRequests()
    {
        return $this->hasMany(BloodRequest::class);
    }

    public function bloodIssues()
    {
        return $this->hasMany(BloodIssue::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeActive($query)
    {
        return $query
            ->whereIn('status', [AdmissionStatus::ADMITTED->value, AdmissionStatus::ON_LEAVE->value])
            ->whereNull('actual_discharge_date');
    }

    public function scopeTerminal($query)
    {
        return $query->whereIn('status', [
            AdmissionStatus::DISCHARGED->value,
            AdmissionStatus::TRANSFERRED->value,
            AdmissionStatus::DECEASED->value,
        ]);
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
        if (! $term) {
            return $query;
        }

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
        if (! $this->admission_date) {
            return null;
        }

        $end = $this->actual_discharge_date ?? now();

        return (int) $this->admission_date->diffInDays($end);
    }

    public function getIsActiveAttribute(): bool
    {
        return in_array($this->status, [AdmissionStatus::ADMITTED, AdmissionStatus::ON_LEAVE], true)
            && $this->actual_discharge_date === null;
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

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->id,
            'bed_id' => $this->bed_id,
            'ward_id' => $this->bed?->ward_id,
            'source_type' => 'admission',
            'source_id' => $this->id,
        ], fn ($v) => $v !== null);
    }
}
