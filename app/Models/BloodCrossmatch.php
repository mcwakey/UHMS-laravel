<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BloodCrossmatch extends Model
{
    use HasFactory;

    public const RESULT_PENDING = 'PENDING';

    public const RESULT_COMPATIBLE = 'COMPATIBLE';

    public const RESULT_INCOMPATIBLE = 'INCOMPATIBLE';

    public const RESULT_CANCELLED = 'CANCELLED';

    public const COMPAT_COMPATIBLE = 'COMPATIBLE';

    public const COMPAT_WITH_CAUTION = 'COMPATIBLE_WITH_CAUTION';

    public const COMPAT_INCOMPATIBLE = 'INCOMPATIBLE';

    public const COMPAT_EMERGENCY_OVERRIDE = 'EMERGENCY_OVERRIDE';

    protected $fillable = [
        'blood_request_id',
        'blood_unit_id',
        'visit_id',
        'patient_id',
        'performed_by',
        'performed_at',
        'result',
        'method',
        'recipient_blood_group',
        'donor_blood_group',
        'component_type',
        'compatibility_status',
        'compatibility_reason',
        'verified_by',
        'verified_at',
        'notes',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'verified_at' => 'datetime',
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

    public function performedBy()
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    public function verifiedBy()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}
