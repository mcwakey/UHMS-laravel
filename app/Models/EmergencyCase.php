<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyCase extends Model
{
    use HasFactory;

    public const STATUS_ARRIVED = 'ARRIVED';

    public const STATUS_WAITING_TRIAGE = 'WAITING_TRIAGE';

    public const STATUS_TRIAGED = 'TRIAGED';

    public const STATUS_UNDER_CARE = 'UNDER_EMERGENCY_CARE';

    public const STATUS_OBSERVATION = 'OBSERVATION';

    public const STATUS_READY_FOR_DISPOSITION = 'READY_FOR_DISPOSITION';

    public const STATUS_DISPOSED = 'DISPOSED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const TRIAGE_RED = 'RED';

    public const TRIAGE_ORANGE = 'ORANGE';

    public const TRIAGE_YELLOW = 'YELLOW';

    public const TRIAGE_GREEN = 'GREEN';

    public const TRIAGE_BLACK = 'BLACK';

    public const DISPOSITION_ADMITTED = 'ADMITTED';

    public const DISPOSITION_DISCHARGED = 'DISCHARGED';

    public const DISPOSITION_TRANSFERRED_TO_OPD = 'TRANSFERRED_TO_OPD';

    public const DISPOSITION_TRANSFERRED_TO_THEATRE = 'TRANSFERRED_TO_THEATRE';

    public const DISPOSITION_REFERRED_OUT = 'REFERRED_OUT';

    public const DISPOSITION_LEFT_AGAINST_MEDICAL_ADVICE = 'LEFT_AGAINST_MEDICAL_ADVICE';

    public const DISPOSITION_ABSCONDED = 'ABSCONDED';

    public const DISPOSITION_DIED = 'DIED';

    public const DISPOSITION_DEAD_ON_ARRIVAL = 'DEAD_ON_ARRIVAL';

    protected $fillable = [
        'emergency_number',
        'visit_id',
        'patient_id',
        'admission_id',
        'emergency_bay_id',
        'arrival_mode',
        'arrival_time',
        'brought_by',
        'source',
        'referral_facility',
        'chief_complaint',
        'initial_condition',
        'triage_category',
        'auto_triage_category',
        'final_triage_category',
        'triage_score',
        'triage_notes',
        'triage_override_reason',
        'triage_reasons',
        'triage_warnings',
        'avpu',
        'pain_score',
        'danger_signs',
        'emergency_status',
        'assigned_doctor_id',
        'assigned_nurse_id',
        'created_by',
        'triaged_by',
        'triaged_at',
        'disposition',
        'disposition_notes',
        'disposition_time',
        'disposed_by',
    ];

    protected $casts = [
        'arrival_time' => 'datetime',
        'triaged_at' => 'datetime',
        'disposition_time' => 'datetime',
        'triage_reasons' => 'array',
        'triage_warnings' => 'array',
        'danger_signs' => 'array',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function bay()
    {
        return $this->belongsTo(EmergencyBay::class, 'emergency_bay_id');
    }

    public function assignedDoctor()
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function assignedNurse()
    {
        return $this->belongsTo(User::class, 'assigned_nurse_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function triagedBy()
    {
        return $this->belongsTo(User::class, 'triaged_by');
    }

    public function disposedBy()
    {
        return $this->belongsTo(User::class, 'disposed_by');
    }

    public function emergencySessions()
    {
        return $this->hasMany(EmergencySession::class);
    }

    public function activeEmergencySession()
    {
        return $this->hasOne(EmergencySession::class)
            ->whereIn('status', [
                EmergencySession::STATUS_PENDING,
                EmergencySession::STATUS_ACTIVE,
                EmergencySession::STATUS_OBSERVATION,
            ])
            ->latestOfMany();
    }

    public function contributors()
    {
        return $this->hasMany(EmergencySessionContributor::class);
    }

    public function bayAssignments()
    {
        return $this->hasMany(EmergencyBayAssignment::class)->latest('assigned_at');
    }

    public function activeBayAssignment()
    {
        return $this->hasOne(EmergencyBayAssignment::class)
            ->where('status', EmergencyBayAssignment::STATUS_ACTIVE)
            ->latestOfMany('assigned_at');
    }

    public function logs()
    {
        return $this->hasMany(EmergencyCaseLog::class)->latest();
    }

    public function notes()
    {
        return $this->hasMany(EmergencyNote::class)->latest();
    }

    public function vitals()
    {
        return $this->hasMany(Vital::class)->latest('recorded_at');
    }

    public function latestVitals()
    {
        return $this->hasOne(Vital::class)->latestOfMany('recorded_at');
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

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    public function procedureRequests()
    {
        return $this->hasMany(ProcedureRequest::class);
    }

    public function consumableUsages()
    {
        return $this->hasMany(ConsumableUsage::class);
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('emergency_status', [
            self::STATUS_DISPOSED,
            self::STATUS_CANCELLED,
        ]);
    }

    public function getTriageBadgeClassAttribute(): string
    {
        return match ($this->final_triage_category ?: $this->triage_category) {
            self::TRIAGE_RED => 'bg-danger',
            self::TRIAGE_ORANGE => 'bg-warning text-dark',
            self::TRIAGE_YELLOW => 'bg-yellow text-dark',
            self::TRIAGE_GREEN => 'bg-success',
            self::TRIAGE_BLACK => 'bg-dark',
            default => 'bg-secondary',
        };
    }

    public function getWaitingMinutesAttribute(): int
    {
        return (int) $this->arrival_time?->diffInMinutes(now());
    }

    public function getCurrentTriageCategoryAttribute(): ?string
    {
        return $this->final_triage_category ?: $this->triage_category;
    }
}
