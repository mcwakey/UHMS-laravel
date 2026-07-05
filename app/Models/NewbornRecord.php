<?php

namespace App\Models;

use App\Enums\NewbornBreathingStatus;
use App\Enums\NewbornCondition;
use App\Enums\NewbornCordStatus;
use App\Enums\NewbornFeedingStatus;
use App\Enums\NewbornOutcome;
use App\Enums\NewbornRecordStatus;
use App\Enums\NewbornSex;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class NewbornRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_record_id',
        'labor_episode_id',
        'pregnancy_profile_id',
        'maternity_case_id',
        'mother_patient_id',
        'newborn_patient_id',
        'visit_id',
        'admission_id',
        'department_id',
        'recorded_by',
        'baby_number',
        'birth_order',
        'sex',
        'birth_time',
        'birth_weight_kg',
        'length_cm',
        'head_circumference_cm',
        'apgar_1_min',
        'apgar_5_min',
        'apgar_10_min',
        'cried_at_birth',
        'resuscitation_required',
        'resuscitation_details',
        'congenital_concerns',
        'feeding_status',
        'temperature',
        'breathing_status',
        'cord_status',
        'colour',
        'risk_flags',
        'danger_signs',
        'neonatal_condition',
        'outcome',
        'status',
        'transferred_to',
        'notes',
        'created_by',
        'updated_by',
        'closed_at',
        'closed_by',
        'closure_reason',
    ];

    protected function casts(): array
    {
        return [
            'birth_time' => 'datetime',
            'cried_at_birth' => 'boolean',
            'resuscitation_required' => 'boolean',
            'risk_flags' => 'array',
            'danger_signs' => 'array',
            'closed_at' => 'datetime',
            'sex' => NewbornSex::class,
            'feeding_status' => NewbornFeedingStatus::class,
            'breathing_status' => NewbornBreathingStatus::class,
            'cord_status' => NewbornCordStatus::class,
            'neonatal_condition' => NewbornCondition::class,
            'outcome' => NewbornOutcome::class,
            'status' => NewbornRecordStatus::class,
        ];
    }

    public function deliveryRecord()
    {
        return $this->belongsTo(DeliveryRecord::class);
    }

    public function laborEpisode()
    {
        return $this->belongsTo(LaborEpisode::class);
    }

    public function pregnancyProfile()
    {
        return $this->belongsTo(PregnancyProfile::class);
    }

    public function maternityCase()
    {
        return $this->belongsTo(MaternityCase::class);
    }

    public function mother()
    {
        return $this->belongsTo(Patient::class, 'mother_patient_id');
    }

    public function newbornPatient()
    {
        return $this->belongsTo(Patient::class, 'newborn_patient_id');
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

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function postnatalNewbornObservations()
    {
        return $this->hasMany(PostnatalNewbornObservation::class)->latest('observed_at')->latest('id');
    }

    public function latestPostnatalObservation()
    {
        return $this->hasOne(PostnatalNewbornObservation::class)->latestOfMany('observed_at');
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->mother_patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'newborn_record',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
