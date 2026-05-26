<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MedicalRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'visit_id',
        'patient_id',
        'doctor_id',
        'service_id',
        'department_id',
        'consultation_route_id',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

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

    public function service()
    {
        return $this->belongsTo(ServiceCatalog::class, 'service_id');
    }

    public function department()
    {
        return $this->belongsTo(Department::class, 'department_id');
    }

    public function consultationRoute()
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
    }

    public function complaints()
    {
        return $this->hasMany(Complaint::class);
    }

    public function historiesOfPresentingComplaint()
    {
        return $this->hasMany(HistoryOfPresentingComplaint::class);
    }

    public function historyOfPresentingComplaints()
    {
        return $this->historiesOfPresentingComplaint();
    }

    public function physicalExaminations()
    {
        return $this->hasMany(PhysicalExamination::class);
    }

    public function diagnoses()
    {
        return $this->hasMany(Diagnosis::class);
    }

    public function investigations()
    {
        return $this->hasMany(Investigation::class);
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class);
    }

    public function tasks()
    {
        return $this->hasMany(ConsultationTask::class);
    }

    public function pendingTasks()
    {
        return $this->hasMany(ConsultationTask::class)->whereIn('status', ['pending', 'in_progress']);
    }
}
