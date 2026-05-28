<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClinicalTask extends Model
{
    use HasFactory;

    public const TYPE_MEDICATION_ADMINISTRATION = 'MEDICATION_ADMINISTRATION';

    public const TYPE_VITALS_MONITORING = 'VITALS_MONITORING';

    public const TYPE_WOUND_DRESSING = 'WOUND_DRESSING';

    public const TYPE_IV_FLUID_CHECK = 'IV_FLUID_CHECK';

    public const TYPE_BLOOD_SUGAR_CHECK = 'BLOOD_SUGAR_CHECK';

    public const TYPE_DOCTOR_REVIEW = 'DOCTOR_REVIEW';

    public const TYPE_FOLLOW_UP = 'FOLLOW_UP';

    public const TYPE_INVESTIGATION_FOLLOW_UP = 'INVESTIGATION_FOLLOW_UP';

    public const TYPE_PROCEDURE_PREPARATION = 'PROCEDURE_PREPARATION';

    public const TYPE_NURSING_OBSERVATION = 'NURSING_OBSERVATION';

    public const TYPE_OTHER = 'OTHER';

    public const STATUS_SCHEDULED = 'SCHEDULED';

    public const STATUS_DUE = 'DUE';

    public const STATUS_OVERDUE = 'OVERDUE';

    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_MISSED = 'MISSED';

    public const STATUS_HELD = 'HELD';

    public const STATUS_REFUSED = 'REFUSED';

    public const STATUS_SKIPPED = 'SKIPPED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'visit_id',
        'admission_id',
        'emergency_case_id',
        'emergency_session_id',
        'patient_id',
        'task_type',
        'title',
        'description',
        'scheduled_at',
        'due_at',
        'status',
        'priority',
        'assigned_to',
        'assigned_role',
        'assigned_department_id',
        'source_type',
        'source_id',
        'completed_by',
        'completed_at',
        'notes',
        'escalation_level',
        'last_reminded_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'due_at' => 'datetime',
        'completed_at' => 'datetime',
        'last_reminded_at' => 'datetime',
    ];

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function emergencyCase()
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function emergencySession()
    {
        return $this->belongsTo(EmergencySession::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function assignedDepartment()
    {
        return $this->belongsTo(Department::class, 'assigned_department_id');
    }

    public function completedBy()
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function schedule()
    {
        return $this->hasOne(MedicationAdministrationSchedule::class);
    }

    public function administration()
    {
        return $this->hasOne(MedicationAdministration::class);
    }

    public function getComputedStatusAttribute(): string
    {
        if (in_array($this->status, [
            self::STATUS_COMPLETED,
            self::STATUS_MISSED,
            self::STATUS_HELD,
            self::STATUS_REFUSED,
            self::STATUS_SKIPPED,
            self::STATUS_CANCELLED,
        ], true)) {
            return $this->status;
        }

        if ($this->due_at && $this->due_at->isPast()) {
            return self::STATUS_DUE;
        }

        return $this->status;
    }
}
