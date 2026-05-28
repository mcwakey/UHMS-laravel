<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmergencyBayAssignment extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_RELEASED = 'RELEASED';

    public const STATUS_TRANSFERRED = 'TRANSFERRED';

    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'emergency_case_id',
        'emergency_session_id',
        'ward_id',
        'bed_id',
        'emergency_bay_id',
        'assigned_by',
        'assigned_at',
        'released_by',
        'released_at',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'released_at' => 'datetime',
        ];
    }

    public function emergencyCase()
    {
        return $this->belongsTo(EmergencyCase::class);
    }

    public function emergencySession()
    {
        return $this->belongsTo(\App\Models\EmergencySession::class);
    }

    public function ward()
    {
        return $this->belongsTo(Ward::class);
    }

    public function bed()
    {
        return $this->belongsTo(Bed::class);
    }

    public function emergencyBay()
    {
        return $this->belongsTo(EmergencyBay::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function releasedBy()
    {
        return $this->belongsTo(User::class, 'released_by');
    }
}