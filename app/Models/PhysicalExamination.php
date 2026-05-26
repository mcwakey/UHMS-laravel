<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PhysicalExamination extends Model
{
    use HasFactory;

    protected $fillable = [
        'medical_record_id',
        'consultation_route_id',
        'visit_id',
        'patient_id',
        'department_id',
        'doctor_id',
        'created_by',
        'updated_by',
        'findings',
        'general_examination',
        'systemic_examination',
        'cardiovascular',
        'respiratory',
        'gastrointestinal',
        'central_nervous_system',
        'musculoskeletal',
        'specialty_examination',
        'local_examination',
        'notes',
        'source_pattern_id',
    ];

    public function medicalRecord(): BelongsTo
    {
        return $this->belongsTo(MedicalRecord::class);
    }

    public function consultationRoute(): BelongsTo
    {
        return $this->belongsTo(VisitConsultationRoute::class, 'consultation_route_id');
    }

    public function visit(): BelongsTo
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient(): BelongsTo
    {
        return $this->belongsTo(Patient::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function doctor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'doctor_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function createdBy(): BelongsTo
    {
        return $this->creator();
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function sourcePattern(): BelongsTo
    {
        return $this->belongsTo(MedicalPattern::class, 'source_pattern_id');
    }
}
