<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyNote extends Model
{
    use HasFactory;

    public const TYPE_DOCTOR_ASSESSMENT = 'DOCTOR_ASSESSMENT';

    public const TYPE_NURSING_NOTE = 'NURSING_NOTE';

    public const TYPE_RESUSCITATION_NOTE = 'RESUSCITATION_NOTE';

    public const TYPE_OBSERVATION_NOTE = 'OBSERVATION_NOTE';

    public const TYPE_GENERAL_NOTE = 'GENERAL_NOTE';

    protected $fillable = [
        'emergency_case_id',
        'emergency_session_id',
        'medical_record_id',
        'consultation_route_id',
        'visit_id',
        'patient_id',
        'note_type',
        'content',
        'created_by',
        'updated_by',
    ];

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

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
