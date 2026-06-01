<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Complaint extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_id',
        'complaint_catalogue_id',
        'consultation_route_id',
        'visit_id',
        'patient_id',
        'department_id',
        'doctor_id',
        'emergency_case_id',
        'emergency_session_id',
        'admission_id',
        'created_by',
        'updated_by',
        'source_pattern_id',
        'description',
        'duration',
        'duration_unit',
        'severity',
        'notes',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function medicalRecord()
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function complaintCatalogue()
    {
        return $this->belongsTo(ComplaintCatalogue::class);
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

    public function emergencyCase()
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function emergencySession()
    {
        return $this->belongsTo(EmergencySession::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }
}
