<?php

namespace App\Models;

use App\Enums\MaternityRiskLevel;
use App\Enums\PostnatalCaseStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PostnatalCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'delivery_record_id',
        'labor_episode_id',
        'pregnancy_profile_id',
        'maternity_case_id',
        'mother_patient_id',
        'visit_id',
        'admission_id',
        'department_id',
        'opened_by',
        'updated_by',
        'closed_by',
        'mother_ready_by',
        'newborn_ready_by',
        'ready_for_discharge_by',
        'referral_marked_by',
        'opened_at',
        'closed_at',
        'mother_ready_at',
        'newborn_ready_at',
        'ready_for_discharge_at',
        'referral_marked_at',
        'status',
        'risk_level',
        'referral_required',
        'referral_reason',
        'follow_up_date',
        'follow_up_instructions',
        'notes',
        'closure_reason',
    ];

    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
            'mother_ready_at' => 'datetime',
            'newborn_ready_at' => 'datetime',
            'ready_for_discharge_at' => 'datetime',
            'referral_marked_at' => 'datetime',
            'follow_up_date' => 'date',
            'referral_required' => 'boolean',
            'status' => PostnatalCaseStatus::class,
            'risk_level' => MaternityRiskLevel::class,
        ];
    }

    public function deliveryRecord() { return $this->belongsTo(DeliveryRecord::class); }
    public function laborEpisode() { return $this->belongsTo(LaborEpisode::class); }
    public function pregnancyProfile() { return $this->belongsTo(PregnancyProfile::class); }
    public function maternityCase() { return $this->belongsTo(MaternityCase::class); }
    public function mother() { return $this->belongsTo(Patient::class, 'mother_patient_id'); }
    public function visit() { return $this->belongsTo(Visit::class); }
    public function admission() { return $this->belongsTo(Admission::class); }
    public function department() { return $this->belongsTo(Department::class); }
    public function openedBy() { return $this->belongsTo(User::class, 'opened_by'); }
    public function updatedBy() { return $this->belongsTo(User::class, 'updated_by'); }
    public function closedBy() { return $this->belongsTo(User::class, 'closed_by'); }

    public function motherObservations()
    {
        return $this->hasMany(PostnatalMotherObservation::class)->latest('observed_at')->latest('id');
    }

    public function newbornObservations()
    {
        return $this->hasMany(PostnatalNewbornObservation::class)->latest('observed_at')->latest('id');
    }

    public function latestMotherObservation()
    {
        return $this->hasOne(PostnatalMotherObservation::class)->latestOfMany('observed_at');
    }

    public function latestNewbornObservation()
    {
        return $this->hasOne(PostnatalNewbornObservation::class)->latestOfMany('observed_at');
    }

    public function liveNewbornRecords()
    {
        return $this->deliveryRecord?->newbornRecords()
            ->where('outcome', \App\Enums\NewbornOutcome::LIVE_BIRTH->value)
            ->get() ?? collect();
    }

    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [PostnatalCaseStatus::CLOSED->value, PostnatalCaseStatus::CANCELLED->value]);
    }

    public function motherReady(): bool
    {
        return (bool) $this->mother_ready_at;
    }

    public function newbornReady(): bool
    {
        if ($this->relationLoaded('deliveryRecord') && $this->deliveryRecord?->relationLoaded('newbornRecords')) {
            $newbornsRequiringObservation = $this->deliveryRecord->newbornRecords->reject(fn ($record) => $record->outcome === \App\Enums\NewbornOutcome::STILLBIRTH);
            return $newbornsRequiringObservation->isEmpty() || (bool) $this->newborn_ready_at;
        }

        return $this->deliveryRecord?->newbornRecords()->where('outcome', '!=', \App\Enums\NewbornOutcome::STILLBIRTH->value)->doesntExist()
            || (bool) $this->newborn_ready_at;
    }

    public function readyForDischarge(): bool
    {
        return (bool) $this->ready_for_discharge_at || ($this->motherReady() && $this->newbornReady());
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->mother_patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'postnatal_case',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
