<?php

namespace App\Models;

use App\Enums\EmergencyArrivalMode;
use App\Enums\EmergencyCaseStatus;
use App\Enums\EmergencyDisposition;
use App\Enums\EmergencyTriageCategory;
use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $emergency_number
 * @property int $visit_id
 * @property int $patient_id
 * @property EmergencyArrivalMode $arrival_mode
 * @property EmergencyTriageCategory|null $triage_category
 * @property EmergencyCaseStatus $status
 * @property EmergencyDisposition|null $disposition
 */
class EmergencyCase extends Model
{
    use HasFactory, GeneratesNumbers, SoftDeletes;

    protected $fillable = [
        'emergency_number',
        'visit_id',
        'patient_id',
        'registered_by',
        'arrival_mode',
        'arrival_time',
        'brought_by',
        'accompanied_by',
        'referral_source',
        'chief_complaint',
        'triage_category',
        'triaged_at',
        'triaged_by',
        'treatment_area_id',
        'assigned_doctor_id',
        'assigned_nurse_id',
        'status',
        'disposition',
        'disposition_at',
        'disposition_by',
        'discharge_summary',
        'referral_facility',
        'referral_reason',
        'death_time',
        'death_cause',
        'certified_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'arrival_mode'    => EmergencyArrivalMode::class,
            'arrival_time'    => 'datetime',
            'triage_category' => EmergencyTriageCategory::class,
            'triaged_at'      => 'datetime',
            'status'          => EmergencyCaseStatus::class,
            'disposition'     => EmergencyDisposition::class,
            'disposition_at'  => 'datetime',
            'death_time'      => 'datetime',
        ];
    }

    public static function generateEmergencyNumber(): string
    {
        return static::generateNumber('ER', 'emergency_cases', 'emergency_number');
    }

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function registeredBy()
    {
        return $this->belongsTo(User::class, 'registered_by');
    }

    public function triagedBy()
    {
        return $this->belongsTo(User::class, 'triaged_by');
    }

    public function assignedDoctor()
    {
        return $this->belongsTo(User::class, 'assigned_doctor_id');
    }

    public function assignedNurse()
    {
        return $this->belongsTo(User::class, 'assigned_nurse_id');
    }

    public function dispositionBy()
    {
        return $this->belongsTo(User::class, 'disposition_by');
    }

    public function treatmentArea()
    {
        return $this->belongsTo(Department::class, 'treatment_area_id');
    }

    public function admission()
    {
        return $this->hasOne(Admission::class, 'emergency_case_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */

    public function scopeOpen($q)
    {
        return $q->whereNotIn('status', [
            EmergencyCaseStatus::DISPOSED->value,
            EmergencyCaseStatus::CLOSED->value,
        ]);
    }

    public function scopeToday($q)
    {
        return $q->whereDate('arrival_time', today());
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
    */

    public function isOpen(): bool
    {
        return $this->status->isOpen();
    }
}
