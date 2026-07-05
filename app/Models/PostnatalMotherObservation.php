<?php

namespace App\Models;

use App\Enums\BleedingStatus;
use App\Enums\BreastfeedingStatus;
use App\Enums\PostnatalObservationStatus;
use App\Enums\UterusCondition;
use App\Enums\WoundCondition;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostnatalMotherObservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'postnatal_case_id',
        'delivery_record_id',
        'pregnancy_profile_id',
        'mother_patient_id',
        'visit_id',
        'admission_id',
        'department_id',
        'observed_by',
        'updated_by',
        'cancelled_by',
        'observed_at',
        'blood_pressure_systolic',
        'blood_pressure_diastolic',
        'pulse',
        'temperature',
        'respiratory_rate',
        'bleeding_status',
        'uterus_condition',
        'pain_score',
        'wound_condition',
        'breastfeeding_status',
        'mobility',
        'urination',
        'mental_wellbeing_note',
        'danger_signs',
        'risk_flags',
        'assessment',
        'plan',
        'counselling',
        'status',
        'cancelled_at',
        'cancellation_reason',
    ];

    protected function casts(): array
    {
        return [
            'observed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'danger_signs' => 'array',
            'risk_flags' => 'array',
            'bleeding_status' => BleedingStatus::class,
            'uterus_condition' => UterusCondition::class,
            'wound_condition' => WoundCondition::class,
            'breastfeeding_status' => BreastfeedingStatus::class,
            'status' => PostnatalObservationStatus::class,
        ];
    }

    public function postnatalCase() { return $this->belongsTo(PostnatalCase::class); }
    public function deliveryRecord() { return $this->belongsTo(DeliveryRecord::class); }
    public function pregnancyProfile() { return $this->belongsTo(PregnancyProfile::class); }
    public function mother() { return $this->belongsTo(Patient::class, 'mother_patient_id'); }
    public function visit() { return $this->belongsTo(Visit::class); }
    public function admission() { return $this->belongsTo(Admission::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function observedBy() { return $this->belongsTo(User::class, 'observed_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function cancelledBy() { return $this->belongsTo(User::class, 'cancelled_by'); }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->mother_patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'postnatal_mother_observation',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
