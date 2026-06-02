<?php

namespace App\Models;

use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodIssue extends Model
{
    use GeneratesNumbers, HasFactory;

    public const STATUS_ISSUED = 'ISSUED';

    public const STATUS_TRANSFUSED = 'TRANSFUSED';

    public const STATUS_RETURNED = 'RETURNED';

    public const STATUS_REACTION_RECORDED = 'REACTION_RECORDED';

    public const STATUS_VOIDED = 'VOIDED';

    // Transfusion outcomes
    public const OUTCOME_COMPLETED = 'COMPLETED';

    public const OUTCOME_STOPPED_REACTION = 'STOPPED_DUE_TO_REACTION';

    public const OUTCOME_PARTIAL = 'PARTIALLY_TRANSFUSED';

    public const OUTCOME_CANCELLED = 'CANCELLED';

    public const REACTION_TYPES = [
        'FEVER', 'CHILLS', 'RASH', 'BREATHING_DIFFICULTY', 'HYPOTENSION',
        'HEMOLYTIC_REACTION_SUSPECTED', 'ANAPHYLAXIS', 'OTHER',
    ];

    protected $fillable = [
        'issue_number',
        'blood_request_id',
        'blood_unit_id',
        'visit_id',
        'patient_id',
        'admission_id',
        'emergency_case_id',
        'issued_by',
        'received_by',
        'received_by_name',
        'issued_at',
        'transfused_by',
        'transfused_at',
        'transfusion_status',
        'reaction_notes',
        'notes',
        'returned_at',
        'returned_by',
        'return_reason',
        'is_emergency_release',
        'emergency_release_type',
        'emergency_release_reason',
        'compatibility_status',
        'authorized_by',
        'witnessed_by',
        'transfusion_started_at',
        'transfusion_completed_at',
        'pre_transfusion_vitals',
        'post_transfusion_vitals',
        'reaction_occurred',
        'reaction_type',
        'outcome',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'transfused_at' => 'datetime',
        'returned_at' => 'datetime',
        'transfusion_started_at' => 'datetime',
        'transfusion_completed_at' => 'datetime',
        'pre_transfusion_vitals' => 'array',
        'post_transfusion_vitals' => 'array',
        'is_emergency_release' => 'boolean',
        'reaction_occurred' => 'boolean',
    ];

    public function request()
    {
        return $this->belongsTo(BloodRequest::class, 'blood_request_id');
    }

    public function unit()
    {
        return $this->belongsTo(BloodUnit::class, 'blood_unit_id');
    }

    public function visit()
    {
        return $this->belongsTo(Visit::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function admission()
    {
        return $this->belongsTo(Admission::class);
    }

    public function emergencyCase()
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function issuedBy()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    public function transfusedBy()
    {
        return $this->belongsTo(User::class, 'transfused_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    public function authorizedBy()
    {
        return $this->belongsTo(User::class, 'authorized_by');
    }

    public function witnessedBy()
    {
        return $this->belongsTo(User::class, 'witnessed_by');
    }

    public static function generateIssueNumber(): string
    {
        return static::generateNumber('BIS', 'blood_issues', 'issue_number');
    }
}
