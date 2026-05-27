<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicationOrder extends Model
{
    use HasFactory;

    public const STATUS_PENDING_DISPENSING = 'PENDING_DISPENSING';

    public const STATUS_PARTIALLY_DISPENSED = 'PARTIALLY_DISPENSED';

    public const STATUS_DISPENSED = 'DISPENSED';

    public const STATUS_ACTIVE_ADMINISTRATION = 'ACTIVE_ADMINISTRATION';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_HELD = 'HELD';

    public const STATUS_STOPPED = 'STOPPED';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_EXPIRED = 'EXPIRED';

    protected $fillable = [
        'visit_id',
        'admission_id',
        'emergency_case_id',
        'patient_id',
        'medical_record_id',
        'consultation_route_id',
        'prescription_id',
        'prescription_item_id',
        'prescribed_by',
        'product_id',
        'drug_id',
        'drug_name',
        'dose',
        'dose_unit',
        'route',
        'frequency_id',
        'frequency_code',
        'duration_value',
        'duration_unit',
        'total_doses',
        'quantity_ordered',
        'quantity_dispensed',
        'start_at',
        'end_at',
        'instructions',
        'status',
        'source_type',
        'source_id',
    ];

    protected $casts = [
        'quantity_ordered' => 'decimal:4',
        'quantity_dispensed' => 'decimal:4',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
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

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function consultationRoute()
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
    }

    public function prescription()
    {
        return $this->belongsTo(Prescription::class);
    }

    public function prescriptionItem()
    {
        return $this->belongsTo(PrescriptionItem::class);
    }

    public function prescriber()
    {
        return $this->belongsTo(User::class, 'prescribed_by');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function drug()
    {
        return $this->belongsTo(Drug::class);
    }

    public function frequency()
    {
        return $this->belongsTo(MedicationFrequency::class, 'frequency_id');
    }

    public function schedules()
    {
        return $this->hasMany(MedicationAdministrationSchedule::class);
    }

    public function administrations()
    {
        return $this->hasMany(MedicationAdministration::class);
    }

    public function logs()
    {
        return $this->hasMany(MedicationAdministrationLog::class);
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->drug_name ?: ($this->drug?->display_name ?: ($this->product?->name ?: 'Medication'));
    }

    public function getClosedStatuses(): array
    {
        return [
            MedicationAdministrationSchedule::STATUS_GIVEN,
            MedicationAdministrationSchedule::STATUS_MISSED,
            MedicationAdministrationSchedule::STATUS_SKIPPED,
            MedicationAdministrationSchedule::STATUS_REFUSED,
            MedicationAdministrationSchedule::STATUS_HELD,
            MedicationAdministrationSchedule::STATUS_CANCELLED,
            MedicationAdministrationSchedule::STATUS_VOIDED,
        ];
    }
}
