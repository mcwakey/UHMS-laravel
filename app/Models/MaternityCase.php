<?php

namespace App\Models;

use App\Enums\MaternityCaseStatus;
use App\Enums\MaternityCaseType;
use App\Enums\MaternityRiskLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MaternityCase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'pregnancy_profile_id',
        'patient_id',
        'visit_id',
        'admission_id',
        'department_id',
        'source_type',
        'source_id',
        'case_type',
        'status',
        'priority',
        'risk_level',
        'opened_by',
        'opened_at',
        'closed_by',
        'closed_at',
        'reason',
        'clinical_summary',
    ];

    protected function casts(): array
    {
        return [
            'case_type' => MaternityCaseType::class,
            'status' => MaternityCaseStatus::class,
            'risk_level' => MaternityRiskLevel::class,
            'opened_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function openedBy()
    {
        return $this->belongsTo(User::class, 'opened_by');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    public function antenatalVisits()
    {
        return $this->hasMany(AntenatalVisit::class)->latest('visit_date')->latest('id');
    }

    public function laborEpisodes()
    {
        return $this->hasMany(LaborEpisode::class)->latest('started_at')->latest('id');
    }

    public function deliveryRecords()
    {
        return $this->hasMany(DeliveryRecord::class)->latest('delivery_at')->latest('id');
    }

    public function scopeOpen($query)
    {
        return $query->whereNotIn('status', [
            MaternityCaseStatus::CLOSED->value,
            MaternityCaseStatus::CANCELLED->value,
        ]);
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'maternity_case',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
