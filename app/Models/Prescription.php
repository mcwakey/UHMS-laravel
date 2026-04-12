<?php

namespace App\Models;

use App\Enums\PrescriptionStatus;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Prescription extends Model
{
    use HasFactory, GeneratesNumbers;

    protected $fillable = [
        'medical_record_id',
        'visit_id',
        'patient_id',
        'doctor_id',
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

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(User::class, 'doctor_id');
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
