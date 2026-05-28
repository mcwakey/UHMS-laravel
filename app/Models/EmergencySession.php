<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencySession extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_OBSERVATION = 'OBSERVATION';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'emergency_case_id',
        'visit_id',
        'patient_id',
        'department_id',
        'medical_record_id',
        'main_doctor_id',
        'primary_nurse_id',
        'status',
        'started_at',
        'ended_at',
        'started_by',
        'ended_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
        ];
    }

    public function emergencyCase()
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function mainDoctor()
    {
        return $this->belongsTo(User::class, 'main_doctor_id');
    }

    public function primaryNurse()
    {
        return $this->belongsTo(User::class, 'primary_nurse_id');
    }

    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function endedBy()
    {
        return $this->belongsTo(User::class, 'ended_by');
    }

    public function contributors()
    {
        return $this->hasMany(\App\Models\EmergencySessionContributor::class);
    }

    public function notes()
    {
        return $this->hasMany(EmergencyNote::class);
    }

    public function vitals()
    {
        return $this->hasMany(Vital::class);
    }

    public function medicationOrders()
    {
        return $this->hasMany(MedicationOrder::class);
    }

    public function labRequests()
    {
        return $this->hasMany(LabRequest::class);
    }

    public function procedureRequests()
    {
        return $this->hasMany(ProcedureRequest::class);
    }

    public function clinicalTasks()
    {
        return $this->hasMany(ClinicalTask::class);
    }
}