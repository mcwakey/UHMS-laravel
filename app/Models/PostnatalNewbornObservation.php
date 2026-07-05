<?php

namespace App\Models;

use App\Enums\JaundiceStatus;
use App\Enums\NewbornBreathingStatus;
use App\Enums\NewbornCordStatus;
use App\Enums\NewbornFeedingStatus;
use App\Enums\PostnatalObservationStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostnatalNewbornObservation extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'postnatal_case_id',
        'newborn_record_id',
        'delivery_record_id',
        'pregnancy_profile_id',
        'mother_patient_id',
        'newborn_patient_id',
        'visit_id',
        'admission_id',
        'department_id',
        'observed_by',
        'updated_by',
        'cancelled_by',
        'observed_at',
        'temperature',
        'weight_kg',
        'feeding_status',
        'breathing_status',
        'cord_status',
        'jaundice_status',
        'stooling',
        'urination',
        'activity',
        'danger_signs',
        'risk_flags',
        'immunisation_note',
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
            'feeding_status' => NewbornFeedingStatus::class,
            'breathing_status' => NewbornBreathingStatus::class,
            'cord_status' => NewbornCordStatus::class,
            'jaundice_status' => JaundiceStatus::class,
            'status' => PostnatalObservationStatus::class,
        ];
    }

    public function postnatalCase() { return $this->belongsTo(PostnatalCase::class); }
    public function newbornRecord() { return $this->belongsTo(NewbornRecord::class); }
    public function deliveryRecord() { return $this->belongsTo(DeliveryRecord::class); }
    public function pregnancyProfile() { return $this->belongsTo(PregnancyProfile::class); }
    public function mother() { return $this->belongsTo(Patient::class, 'mother_patient_id'); }
    public function newbornPatient() { return $this->belongsTo(Patient::class, 'newborn_patient_id'); }
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
            'source_type' => 'postnatal_newborn_observation',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
