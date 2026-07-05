<?php

namespace App\Models;

use App\Enums\AntenatalReferralType;
use App\Enums\AntenatalVisitStatus;
use App\Enums\FetalPresentation;
use App\Enums\UrineGlucoseResult;
use App\Enums\UrineProteinResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class AntenatalVisit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pregnancy_profile_id',
        'maternity_case_id',
        'patient_id',
        'visit_id',
        'admission_id',
        'department_id',
        'recorded_by',
        'visit_number',
        'visit_date',
        'gestational_age_weeks',
        'gestational_age_days',
        'weight_kg',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse',
        'temperature',
        'respiratory_rate',
        'fundal_height_cm',
        'fetal_heart_rate',
        'fetal_movement',
        'presentation',
        'urine_protein',
        'urine_glucose',
        'oedema',
        'haemoglobin',
        'danger_signs',
        'risk_flags',
        'assessment',
        'plan',
        'counselling',
        'supplements',
        'immunisations',
        'next_visit_date',
        'referral_type',
        'referral_reason',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'visit_date' => 'datetime',
            'danger_signs' => 'array',
            'risk_flags' => 'array',
            'supplements' => 'array',
            'immunisations' => 'array',
            'next_visit_date' => 'date',
            'status' => AntenatalVisitStatus::class,
            'referral_type' => AntenatalReferralType::class,
            'presentation' => FetalPresentation::class,
            'urine_protein' => UrineProteinResult::class,
            'urine_glucose' => UrineGlucoseResult::class,
        ];
    }

    public function pregnancyProfile()
    {
        return $this->belongsTo(PregnancyProfile::class);
    }

    public function maternityCase()
    {
        return $this->belongsTo(MaternityCase::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'antenatal_visit',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
