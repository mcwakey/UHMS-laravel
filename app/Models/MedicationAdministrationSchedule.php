<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicationAdministrationSchedule extends Model
{
    use HasFactory;

    public const STATUS_SCHEDULED = 'SCHEDULED';

    public const STATUS_DUE = 'DUE';

    public const STATUS_OVERDUE = 'OVERDUE';

    public const STATUS_GIVEN = 'GIVEN';

    public const STATUS_PARTIALLY_GIVEN = 'PARTIALLY_GIVEN';

    public const STATUS_MISSED = 'MISSED';

    public const STATUS_SKIPPED = 'SKIPPED';

    public const STATUS_REFUSED = 'REFUSED';

    public const STATUS_HELD = 'HELD';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_VOIDED = 'VOIDED';

    public const STATUS_CORRECTED = 'CORRECTED';

    protected $fillable = [
        'medication_order_id',
        'visit_id',
        'admission_id',
        'emergency_case_id',
        'emergency_session_id',
        'patient_id',
        'scheduled_at',
        'dose',
        'dose_unit',
        'route',
        'status',
        'sequence_number',
        'clinical_task_id',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    public function medicationOrder()
    {
        return $this->belongsTo(MedicationOrder::class);
    }

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

    public function clinicalTask()
    {
        return $this->belongsTo(ClinicalTask::class);
    }

    public function administration()
    {
        return $this->hasOne(MedicationAdministration::class, 'schedule_id');
    }
}
