<?php

namespace App\Models;

use App\Enums\PrescriptionStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Prescription extends Model
{
    use GeneratesNumbers, HasFactory, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'notes'])
            ->logOnlyDirty()
            ->useLogName('pharmacy')
            ->dontSubmitEmptyLogs();
    }

    protected $fillable = [
        'medical_record_id',
        'consultation_route_id',
        'visit_id',
        'patient_id',
        'department_id',
        'doctor_id',
        'created_by',
        'updated_by',
        'source_pattern_id',
        'prescription_number',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => PrescriptionStatus::class,
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function consultationRoute()
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
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

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy()
    {
        return $this->creator();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sourcePattern()
    {
        return $this->belongsTo(MedicalPattern::class, 'source_pattern_id');
    }

    public function items()
    {
        return $this->hasMany(PrescriptionItem::class);
    }

    public function dispensingRecords()
    {
        return $this->hasMany(DispensingRecord::class);
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public static function generatePrescriptionNumber(): string
    {
        return static::generateNumber('RX', 'prescriptions', 'prescription_number');
    }
}
