<?php

namespace App\Models;

use App\Traits\GeneratesNumbers;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodRequest extends Model
{
    use GeneratesNumbers, HasFactory;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_APPROVED = 'APPROVED';

    public const STATUS_PARTIALLY_ISSUED = 'PARTIALLY_ISSUED';

    public const STATUS_ISSUED = 'ISSUED';

    public const STATUS_COMPLETED = 'COMPLETED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'request_number',
        'visit_id',
        'patient_id',
        'admission_id',
        'emergency_case_id',
        'department_id',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
        'needed_at',
        'blood_group',
        'component_type',
        'units_requested',
        'units_issued',
        'priority',
        'status',
        'hb_level',
        'diagnosis',
        'indication',
        'notes',
        'invoice_item_id',
    ];

    protected $casts = [
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'needed_at' => 'datetime',
    ];

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

    public function department()
    {
        return $this->belongsTo(Department::class);
    }

    public function requestedBy()
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function invoiceItem()
    {
        return $this->belongsTo(InvoiceItem::class);
    }

    public function crossmatches()
    {
        return $this->hasMany(BloodCrossmatch::class, 'blood_request_id');
    }

    public function issues()
    {
        return $this->hasMany(BloodIssue::class, 'blood_request_id');
    }

    public function reservedUnits()
    {
        return $this->hasMany(BloodUnit::class, 'reserved_for_request_id');
    }

    public static function generateRequestNumber(): string
    {
        return static::generateNumber('BRQ', 'blood_requests', 'request_number');
    }
}
