<?php

namespace App\Models;

use App\Enums\PregnancyProfileStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PregnancyProfile extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'patient_id',
        'visit_id',
        'admission_id',
        'department_id',
        'created_by',
        'updated_by',
        'gravida',
        'para',
        'abortions',
        'living_children',
        'last_menstrual_period',
        'estimated_due_date',
        'gestational_age_weeks',
        'gestational_age_days',
        'blood_group',
        'rhesus_status',
        'known_risks',
        'allergies_snapshot',
        'previous_caesarean',
        'previous_postpartum_haemorrhage',
        'hypertensive_disorder_risk',
        'diabetes_risk',
        'multiple_pregnancy',
        'profile_status',
        'closed_at',
        'closed_by',
        'closure_reason',
    ];

    protected function casts(): array
    {
        return [
            'last_menstrual_period' => 'date',
            'estimated_due_date' => 'date',
            'known_risks' => 'array',
            'previous_caesarean' => 'boolean',
            'previous_postpartum_haemorrhage' => 'boolean',
            'hypertensive_disorder_risk' => 'boolean',
            'diabetes_risk' => 'boolean',
            'multiple_pregnancy' => 'boolean',
            'profile_status' => PregnancyProfileStatus::class,
            'closed_at' => 'datetime',
        ];
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

    public function maternityCases()
    {
        return $this->hasMany(MaternityCase::class)->latest('opened_at')->latest('id');
    }

    public function antenatalVisits()
    {
        return $this->hasMany(AntenatalVisit::class)->latest('visit_date')->latest('id');
    }

    public function latestAntenatalVisit()
    {
        return $this->hasOne(AntenatalVisit::class)->latestOfMany('visit_date');
    }

    public function nextAntenatalVisit()
    {
        return $this->hasOne(AntenatalVisit::class)
            ->whereNotNull('next_visit_date')
            ->whereDate('next_visit_date', '>=', today())
            ->oldestOfMany('next_visit_date');
    }

    public function scopeActive($query)
    {
        return $query->whereIn('profile_status', [
            PregnancyProfileStatus::ACTIVE->value,
            PregnancyProfileStatus::HIGH_RISK->value,
        ]);
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'pregnancy_profile',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
