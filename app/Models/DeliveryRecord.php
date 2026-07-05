<?php

namespace App\Models;

use App\Enums\DeliveryMode;
use App\Enums\DeliveryOutcome;
use App\Enums\DeliveryRecordStatus;
use App\Enums\MaternalCondition;
use App\Enums\PlacentaStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class DeliveryRecord extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'labor_episode_id',
        'pregnancy_profile_id',
        'maternity_case_id',
        'patient_id',
        'visit_id',
        'admission_id',
        'department_id',
        'recorded_by',
        'delivery_at',
        'delivery_mode',
        'delivery_outcome',
        'placenta_status',
        'estimated_blood_loss_ml',
        'maternal_condition',
        'complications',
        'attending_staff_id',
        'theatre_case_id',
        'emergency_case_id',
        'newborn_count',
        'newborn_records_pending',
        'notes',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'delivery_at' => 'datetime',
            'complications' => 'array',
            'newborn_records_pending' => 'boolean',
            'delivery_mode' => DeliveryMode::class,
            'delivery_outcome' => DeliveryOutcome::class,
            'placenta_status' => PlacentaStatus::class,
            'maternal_condition' => MaternalCondition::class,
            'status' => DeliveryRecordStatus::class,
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

    public function attendingStaff()
    {
        return $this->belongsTo(User::class, 'attending_staff_id');
    }

    public function newbornRecords()
    {
        return $this->hasMany(NewbornRecord::class)->orderBy('birth_order')->orderBy('id');
    }

    public function postnatalCase()
    {
        return $this->hasOne(PostnatalCase::class);
    }

    public function postnatalCases()
    {
        return $this->hasMany(PostnatalCase::class)->latest('opened_at')->latest('id');
    }

    public function newbornCountExpected(): int
    {
        return max(0, (int) ($this->newborn_count ?? 0));
    }

    public function newbornCountRecorded(): int
    {
        return $this->newbornRecords()->count();
    }

    public function newbornRecordsComplete(): bool
    {
        $expected = $this->newbornCountExpected();

        return $expected > 0
            && $this->newbornRecords()->count() >= $expected
            && ! $this->newbornRecords()
                ->where(function ($query) {
                    $query->whereNull('outcome')->orWhereNull('status');
                })
                ->exists();
    }

    public function toActivityContext(): array
    {
        return array_filter([
            'patient_id' => $this->patient_id,
            'visit_id' => $this->visit_id,
            'admission_id' => $this->admission_id,
            'source_type' => 'delivery_record',
            'source_id' => $this->id,
        ], fn ($value) => $value !== null);
    }
}
