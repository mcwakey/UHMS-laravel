<?php

namespace App\Models;

use App\Enums\LaborObservationStatus;
use App\Enums\LaborStage;
use App\Enums\LiquorColour;
use App\Enums\MembranesStatus;
use App\Enums\UrineGlucoseResult;
use App\Enums\UrineProteinResult;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaborObservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'labor_episode_id',
        'pregnancy_profile_id',
        'patient_id',
        'visit_id',
        'admission_id',
        'recorded_by',
        'observed_at',
        'labor_stage',
        'cervical_dilation_cm',
        'fetal_heart_rate',
        'contractions_per_10_min',
        'contraction_duration_seconds',
        'descent',
        'moulding',
        'caput',
        'membranes_status',
        'liquor_colour',
        'maternal_pulse',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'temperature',
        'respiratory_rate',
        'urine_protein',
        'urine_glucose',
        'urine_volume_ml',
        'pain_score',
        'oxytocin',
        'fluids',
        'medication',
        'risk_flags',
        'danger_signs',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
            'risk_flags' => 'array',
            'danger_signs' => 'array',
            'labor_stage' => LaborStage::class,
            'status' => LaborObservationStatus::class,
            'membranes_status' => MembranesStatus::class,
            'liquor_colour' => LiquorColour::class,
            'urine_protein' => UrineProteinResult::class,
            'urine_glucose' => UrineGlucoseResult::class,
        ];
    }

    public function laborEpisode()
    {
        return $this->belongsTo(LaborEpisode::class);
    }

    public function pregnancyProfile()
    {
        return $this->belongsTo(PregnancyProfile::class);
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

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'labor_observation',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
