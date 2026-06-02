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
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'transfused_at' => 'datetime',
        'returned_at' => 'datetime',
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

    public static function generateIssueNumber(): string
    {
        return static::generateNumber('BIS', 'blood_issues', 'issue_number');
    }
}
