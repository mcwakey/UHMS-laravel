<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicationAdministration extends Model
{
    use HasFactory;

    public const STATUS_GIVEN = 'GIVEN';

    public const STATUS_PARTIALLY_GIVEN = 'PARTIALLY_GIVEN';

    public const STATUS_MISSED = 'MISSED';

    public const STATUS_HELD = 'HELD';

    public const STATUS_REFUSED = 'REFUSED';

    public const STATUS_SKIPPED = 'SKIPPED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_NOT_GIVEN = 'NOT_GIVEN';

    public const SOURCE_PATIENT_STOCK = 'PATIENT_DISPENSED_STOCK';

    public const SOURCE_WARD_STOCK = 'WARD_STOCK';

    public const SOURCE_EMERGENCY_STOCK = 'EMERGENCY_STOCK';

    public const SOURCE_OTHER_DEPARTMENT_STOCK = 'OTHER_DEPARTMENT_STOCK';

    protected $fillable = [
        'medication_order_id',
        'schedule_id',
        'clinical_task_id',
        'visit_id',
        'admission_id',
        'emergency_case_id',
        'emergency_session_id',
        'medical_record_id',
        'consultation_route_id',
        'patient_id',
        'administered_by',
        'administered_at',
        'scheduled_at',
        'dose_given',
        'dose_unit',
        'route',
        'status',
        'reason_not_given',
        'notes',
        'reaction',
        'source_stock_type',
        'stock_location_id',
        'stock_movement_id',
        'witnessed_by',
        'corrected_by',
        'corrected_at',
        'correction_reason',
    ];

    protected $casts = [
        'administered_at' => 'datetime',
        'scheduled_at' => 'datetime',
        'corrected_at' => 'datetime',
    ];

    public function medicationOrder()
    {
        return $this->belongsTo(MedicationOrder::class);
    }

    public function schedule()
    {
        return $this->belongsTo(MedicationAdministrationSchedule::class, 'schedule_id');
    }

    public function clinicalTask()
    {
        return $this->belongsTo(ClinicalTask::class);
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

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function consultationRoute()
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function administeredBy()
    {
        return $this->belongsTo(User::class, 'administered_by');
    }

    public function stockLocation()
    {
        return $this->belongsTo(StockLocation::class);
    }

    public function stockMovement()
    {
        return $this->belongsTo(StockMovement::class);
    }

    public function witness()
    {
        return $this->belongsTo(User::class, 'witnessed_by');
    }
}
