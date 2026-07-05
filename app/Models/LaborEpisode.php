<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Enums\LaborEpisodeStatus;
use App\Enums\LaborStage;
use App\Enums\LiquorColour;
use App\Enums\MaternityRiskLevel;
use App\Enums\MembranesStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class LaborEpisode extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pregnancy_profile_id',
        'maternity_case_id',
        'antenatal_visit_id',
        'patient_id',
        'visit_id',
        'admission_id',
        'department_id',
        'started_by',
        'updated_by',
        'started_at',
        'labor_onset_at',
        'membranes_status',
        'rupture_of_membranes_at',
        'liquor_colour',
        'presentation',
        'fetal_position',
        'contractions_started_at',
        'labor_stage',
        'status',
        'risk_level',
        'referral_source',
        'delivery_mode_planned',
        'theatre_escalation_required',
        'emergency_escalation_required',
        'clinical_summary',
        'initial_assessment',
        'complications',
        'closed_at',
        'closed_by',
        'closure_reason',
    ];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'labor_onset_at' => 'datetime',
            'rupture_of_membranes_at' => 'datetime',
            'contractions_started_at' => 'datetime',
            'closed_at' => 'datetime',
            'theatre_escalation_required' => 'boolean',
            'emergency_escalation_required' => 'boolean',
            'complications' => 'array',
            'labor_stage' => LaborStage::class,
            'status' => LaborEpisodeStatus::class,
            'risk_level' => MaternityRiskLevel::class,
            'membranes_status' => MembranesStatus::class,
            'liquor_colour' => LiquorColour::class,
            'delivery_mode_planned' => DeliveryMode::class,
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

    public function antenatalVisit()
    {
        return $this->belongsTo(AntenatalVisit::class);
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

    public function startedBy()
    {
        return $this->belongsTo(User::class, 'started_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function observations()
    {
        return $this->hasMany(LaborObservation::class)->latest('observed_at')->latest('id');
    }

    public function latestObservation()
    {
        return $this->hasOne(LaborObservation::class)->latestOfMany('observed_at');
    }

    public function deliveryRecords()
    {
        return $this->hasMany(DeliveryRecord::class)->latest('delivery_at')->latest('id');
    }

    public function latestDeliveryRecord()
    {
        return $this->hasOne(DeliveryRecord::class)->latestOfMany('delivery_at');
    }

    public function newbornRecords()
    {
        return $this->hasMany(NewbornRecord::class)->orderBy('birth_order')->orderBy('id');
    }

    public function postnatalCases()
    {
        return $this->hasMany(PostnatalCase::class)->latest('opened_at')->latest('id');
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            LaborEpisodeStatus::DELIVERED->value,
            LaborEpisodeStatus::CANCELLED->value,
            LaborEpisodeStatus::CLOSED->value,
            LaborEpisodeStatus::REFERRED->value,
            LaborEpisodeStatus::TRANSFERRED->value,
        ]);
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'labor_episode',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
